#!/usr/bin/env python3
"""
WebDanışmanı — shared gettext for root helper scripts (panel/bin/wd-*).

Installed to: /usr/local/hestia/wd/lib/wd_i18n.py

Usage in helpers:
    from wd_i18n import _, ngettext
    _("No PTR record.")

Language resolution (first hit wins):
  1. WD_LANG environment (set by the panel when calling sudo)
  2. LANGUAGE / LANG environment
  3. Hestia LANGUAGE= in hestia.conf
  4. en

Catalogs: /usr/local/hestia/web/locale/{lang}/LC_MESSAGES/webdanismani.mo
(same domain as PHP wd__()).
"""

from __future__ import annotations

import gettext
import os
import re
from functools import lru_cache

DOMAIN = "webdanismani"
LOCALE_DIR = "/usr/local/hestia/web/locale"
HESTIA_CONF = "/usr/local/hestia/conf/hestia.conf"

# Dev fallback when not installed yet
_PKG_LOCALE = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "locale")


def _hestia_language() -> str:
    try:
        with open(HESTIA_CONF, encoding="utf-8", errors="replace") as f:
            for line in f:
                m = re.match(r'^LANGUAGE\s*=\s*[\'"]?([A-Za-z_]+)[\'"]?', line.strip())
                if m:
                    return m.group(1).lower()
    except OSError:
        pass
    return "en"


def detect_lang() -> str:
    for key in ("WD_LANG", "LANGUAGE", "LANG"):
        raw = os.environ.get(key, "").strip()
        if not raw:
            continue
        # LANGUAGE may be "tr:en"; LANG may be "tr_TR.UTF-8"
        code = raw.split(":")[0].split(".")[0].split("_")[0].lower()
        if re.fullmatch(r"[a-z]{2}", code):
            return code
    return _hestia_language()


def _locale_dir() -> str:
    if os.path.isdir(LOCALE_DIR):
        return LOCALE_DIR
    if os.path.isdir(_PKG_LOCALE):
        return _PKG_LOCALE
    return LOCALE_DIR


@lru_cache(maxsize=8)
def _translation(lang: str) -> gettext.NullTranslations:
    localedir = _locale_dir()
    try:
        return gettext.translation(DOMAIN, localedir=localedir, languages=[lang], fallback=True)
    except Exception:
        return gettext.NullTranslations()


def get_translator(lang: str | None = None):
    return _translation(lang or detect_lang())


def _(message: str) -> str:
    """Translate a singular msgid (English source)."""
    if not message:
        return message
    return get_translator().gettext(message)


def ngettext(singular: str, plural: str, n: int) -> str:
    return get_translator().ngettext(singular, plural, n)


def pgettext(context: str, message: str) -> str:
    # Python <3.8 has no pgettext on NullTranslations in some builds; emulate.
    t = get_translator()
    if hasattr(t, "pgettext"):
        return t.pgettext(context, message)
    return t.gettext(message)
