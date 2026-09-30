#!/usr/bin/env python3
"""Extract wd__/wd_n__/wd_esc__ strings from PHP into webdanismani.pot and merge into .po files."""
from __future__ import annotations

import datetime as dt
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]  # panel/
LOCALE = Path(__file__).resolve().parent
MSGFMT = LOCALE / "bin" / "msgfmt.py"
DOMAIN = "webdanismani"

# wd__/wd_esc__/wd_n__ (PHP) and _ / N_ / ngettext (Python)
RE_SING = re.compile(
    r"""\b(?:wd__|wd_esc__|N_|_)\(\s*(['"])(?P<s>(?:\\.|(?!\1).)*)\1\s*\)""",
    re.DOTALL,
)
RE_PLUR = re.compile(
    r"""\b(?:wd_n__|ngettext)\(\s*(['"])(?P<a>(?:\\.|(?!\1).)*)\1\s*,\s*(['"])(?P<b>(?:\\.|(?!\3).)*)\3\s*,""",
    re.DOTALL,
)
RE_WDT = re.compile(
    r"""\bwdt\(\s*(['"])(?P<s>(?:\\.|(?!\1).)*)\1\s*\)""",
    re.DOTALL,
)


def php_unescape(s: str, quote: str) -> str:
    out = []
    i = 0
    while i < len(s):
        if s[i] == "\\" and i + 1 < len(s):
            n = s[i + 1]
            mapping = {"n": "\n", "t": "\t", "r": "\r", "\\": "\\", quote: quote, "$": "$"}
            out.append(mapping.get(n, n))
            i += 2
            continue
        out.append(s[i])
        i += 1
    return "".join(out)


def po_escape(s: str) -> str:
    return s.replace("\\", "\\\\").replace('"', '\\"').replace("\n", "\\n").replace("\t", "\\t")


def collect(files: list[Path]) -> dict[tuple[str, str | None], list[str]]:
    """Map (msgid, msgid_plural|None) -> list of file:line refs."""
    entries: dict[tuple[str, str | None], list[str]] = {}
    root_repo = ROOT.parent  # webdanismani-hestia-en/
    for path in files:
        text = path.read_text(encoding="utf-8")
        try:
            rel = path.relative_to(ROOT).as_posix()
        except ValueError:
            rel = path.relative_to(root_repo).as_posix()
        for m in RE_SING.finditer(text):
            msg = php_unescape(m.group("s"), m.group(1))
            if not msg:
                continue
            line = text.count("\n", 0, m.start()) + 1
            key = (msg, None)
            entries.setdefault(key, []).append(f"{rel}:{line}")
        for m in RE_PLUR.finditer(text):
            a = php_unescape(m.group("a"), m.group(1))
            b = php_unescape(m.group("b"), m.group(3))
            line = text.count("\n", 0, m.start()) + 1
            key = (a, b)
            entries.setdefault(key, []).append(f"{rel}:{line}")
        for m in RE_WDT.finditer(text):
            msg = php_unescape(m.group("s"), m.group(1))
            if not msg:
                continue
            line = text.count("\n", 0, m.start()) + 1
            entries.setdefault((msg, None), []).append(f"{rel}:{line}")
    return entries


def iter_source_files() -> list[Path]:
    files: list[Path] = list(ROOT.rglob("*.php"))
    bin_dir = ROOT / "bin"
    if bin_dir.is_dir():
        files.extend(p for p in bin_dir.iterdir() if p.is_file() and not p.name.startswith("."))
    tema = ROOT.parent / "tema"
    for name in ("wd-fm.js", "wd-fm-i18n.tr.js"):
        p = tema / name
        if p.is_file():
            files.append(p)
    return sorted(set(files))


def write_pot(entries: dict[tuple[str, str | None], list[str]], dest: Path) -> None:
    now = dt.datetime.now(dt.timezone.utc).strftime("%Y-%m-%d %H:%M+0000")
    lines = [
        '# WebDanışmanı panel UI translations.',
        '# Copyright (C) WebDanışmanı contributors',
        '# This file is distributed under the same license as the package.',
        f'msgid ""',
        f'msgstr ""',
        f'"Project-Id-Version: webdanismani-hestia 1.0\\n"',
        f'"Report-Msgid-Bugs-To: \\n"',
        f'"POT-Creation-Date: {now}\\n"',
        f'"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"',
        f'"Last-Translator: \\n"',
        f'"Language-Team: \\n"',
        f'"Language: \\n"',
        f'"MIME-Version: 1.0\\n"',
        f'"Content-Type: text/plain; charset=UTF-8\\n"',
        f'"Content-Transfer-Encoding: 8bit\\n"',
        f'"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n"',
        "",
    ]
    for (msgid, plural), refs in sorted(entries.items(), key=lambda x: x[0][0].lower()):
        for ref in refs[:8]:
            lines.append(f"#: {ref}")
        if plural is None:
            lines.append(f'msgid "{po_escape(msgid)}"')
            lines.append('msgstr ""')
        else:
            lines.append(f'msgid "{po_escape(msgid)}"')
            lines.append(f'msgid_plural "{po_escape(plural)}"')
            lines.append('msgstr[0] ""')
            lines.append('msgstr[1] ""')
        lines.append("")
    dest.write_text("\n".join(lines) + "\n", encoding="utf-8")


# Built-in Turkish catalog for known shared UI strings (seed; extract fills the rest as empty).
TR_SEED: dict[str, str] = {
    "Tools": "Araçlar",
    "TOOLS": "ARAÇLAR",
    "All management tools": "Tüm yönetim araçları",
    "Health Center": "Sağlık Merkezi",
    "HEALTH": "SAĞLIK",
    "Mail, DNS and SSL checks": "Mail, DNS ve SSL denetimi",
    "All checks OK": "Tüm kontroller sorunsuz",
    "Security": "Güvenlik",
    "SECURITY": "GÜVENLİK",
    "Server hardening and malware scan": "Sunucu sertleştirme ve zararlı kod taraması",
    "Disk Usage": "Disk Kullanımı",
    "DISK": "DİSK",
    "Shows where disk space is used": "Yerin nereye gittiğini gösterir",
    "Users": "Kullanıcılar",
    "USERS": "KULLANICI",
    "Domains": "Alan Adları",
    "DNS Zones": "DNS Bölgeleri",
    "Zones": "Bölgeler",
    "Email": "E-posta",
    "MAIL": "POSTA",
    "Databases": "Veritabanları",
    "CRON Jobs": "CRON Görevleri",
    "Jobs": "Görevler",
    "Backups": "Yedekler",
    "BACKUP": "YEDEK",
    "File Manager": "Dosya Yöneticisi",
    "FILES": "DOSYA",
    "Web Terminal": "Web Terminali",
    "TERMINAL": "TERMİNAL",
    "Statistics": "İstatistikler",
    "STATS": "İSTATİSTİK",
    "Server Settings": "Sunucu Ayarları",
    "SERVER": "SUNUCU",
    "Logs": "Günlükler",
    "LOGS": "GÜNLÜK",
    "SERVER LOAD": "SUNUCU YÜKÜ",
    "cores": "çekirdek",
    "Uptime": "Çalışma süresi",
    "Theme & modules": "Tema & modüller",
    "Support & forum": "Destek & forum",
    "Quick Install": "Hızlı Kurulum",
    "Add Domain": "Alan Adı Ekle",
    "Location": "Konum",
    "Search": "Ara",
    "Search tools ( / )": "Araçlarda ara ( / )",
    "Searches domains, email accounts, databases, and cron jobs": "Alan adı, e-posta hesabı, veritabanı ve cron görevlerinde arar",
    "Log out": "Çıkış yap",
    "PHP Settings": "PHP Ayarları",
    "Web Application Firewall": "Uygulama Güvenlik Duvarı",
    "Uptime Monitor": "Erişim İzleme",
    "UPTIME": "ERİŞİM",
    "WordPress Tools": "WordPress Araçları",
    "Clone / Staging": "Klon / Staging",
    "CLONE": "KLON",
    "App Installer": "Uygulama Kurucu",
    "INSTALL": "KUR",
    "Git Deploy": "Git Dağıtım",
    "Node.js Apps": "Node.js Uygulamaları",
    "Backup Browser": "Yedek Gezgini",
    "BROWSER": "GEZGİN",
    "Reseller Management": "Bayi Yönetimi",
    "RESELLER": "BAYİ",
    "My Customers": "Müşterilerim",
    "CUSTOMERS": "MÜŞTERİ",
    "Domain": "Alan Adı",
    "Select": "Seç",
    "OK": "Sorunsuz",
    "Warning": "Uyarı",
    "Issue": "Sorun",
    "Could not check": "Kontrol edilemedi",
    "day": "gün",
    "days": "gün",
    "hour": "saat",
    "hours": "saat",
    "minute": "dakika",
    "minutes": "dakika",
    "min": "dk",
    "s": "sn",
    "h": "sa",
    "Upload size, timeouts, error display, and PHP error log": "Yükleme boyutu, zaman aşımı, hata gösterimi ve PHP hata günlüğü",
    "Blocks bad bots and attack patterns; rate-limits login pages": "Kötü bot ve saldırı kalıplarını engeller, giriş sayfasını kaba kuvvete karşı korur",
    "External HTTP check every 5 minutes; notify after failures": "Siteler 5 dakikada bir dışarıdan denetlenir; çökünce bildirim gelir",
    "Updates, auto-update, integrity check, one-click admin login": "Güncellemeler, otomatik güncelleme, bütünlük doğrulama, tek tık yönetici girişi",
    "Clone a site for testing, then push changes live": "Sitenin test kopyasını çıkar, değişiklikleri yayına al",
    "Install catalog apps onto a selected domain": "Katalogdaki uygulama paketlerini seçilen alan adına kur",
    "Clone a repo into a site; manual or automatic pull": "Depoyu siteye klonla, tek tıkla ya da otomatik çek",
    "Run a Node.js app as a service and bind it to a domain": "Node.js uygulamasını servis olarak çalıştır, alan adına bağla",
    "Browse a backup archive; restore a single file or folder": "Yedeğin içinde gez, tek dosya ya da klasörü geri yükle",
    "Create, suspend, and re-package your own customers": "Kendi müşterilerinizi açın, askıya alın, paketini değiştirin",
}

TR_PLURAL_SEED: dict[tuple[str, str], tuple[str, str]] = {
    ("day", "days"): ("gün", "gün"),
    ("hour", "hours"): ("saat", "saat"),
    ("minute", "minutes"): ("dakika", "dakika"),
    ("%d check needs attention", "%d checks need attention"): (
        "%d kontrol dikkat istiyor",
        "%d kontrol dikkat istiyor",
    ),
}


def write_po(
    lang: str,
    entries: dict[tuple[str, str | None], list[str]],
    dest: Path,
    translations: dict[str, str] | None = None,
    plural_translations: dict[tuple[str, str], tuple[str, str]] | None = None,
) -> None:
    translations = translations or {}
    plural_translations = plural_translations or {}
    now = dt.datetime.now(dt.timezone.utc).strftime("%Y-%m-%d %H:%M+0000")
    if lang == "tr":
        plural = "nplurals=2; plural=(n != 1);"
    else:
        plural = "nplurals=2; plural=(n != 1);"
    lines = [
        f"# WebDanışmanı — {lang}",
        'msgid ""',
        'msgstr ""',
        f'"Project-Id-Version: webdanismani-hestia 1.0\\n"',
        f'"POT-Creation-Date: {now}\\n"',
        f'"PO-Revision-Date: {now}\\n"',
        f'"Language: {lang}\\n"',
        f'"MIME-Version: 1.0\\n"',
        f'"Content-Type: text/plain; charset=UTF-8\\n"',
        f'"Content-Transfer-Encoding: 8bit\\n"',
        f'"Plural-Forms: {plural}\\n"',
        "",
    ]
    for (msgid, plural_id), refs in sorted(entries.items(), key=lambda x: x[0][0].lower()):
        for ref in refs[:8]:
            lines.append(f"#: {ref}")
        if plural_id is None:
            lines.append(f'msgid "{po_escape(msgid)}"')
            if lang == "en":
                # English source language: msgstr may mirror msgid (optional).
                lines.append(f'msgstr "{po_escape(msgid)}"')
            else:
                tr = translations.get(msgid, "")
                lines.append(f'msgstr "{po_escape(tr)}"')
        else:
            lines.append(f'msgid "{po_escape(msgid)}"')
            lines.append(f'msgid_plural "{po_escape(plural_id)}"')
            if lang == "en":
                lines.append(f'msgstr[0] "{po_escape(msgid)}"')
                lines.append(f'msgstr[1] "{po_escape(plural_id)}"')
            else:
                a, b = plural_translations.get((msgid, plural_id), ("", ""))
                lines.append(f'msgstr[0] "{po_escape(a)}"')
                lines.append(f'msgstr[1] "{po_escape(b)}"')
        lines.append("")
    dest.write_text("\n".join(lines) + "\n", encoding="utf-8")


def compile_mo(po: Path, mo: Path) -> None:
    mo.parent.mkdir(parents=True, exist_ok=True)
    subprocess.check_call([sys.executable, str(MSGFMT), "-o", str(mo), str(po)])


def main() -> int:
    sources = iter_source_files()
    entries = collect(sources)
    pot = LOCALE / f"{DOMAIN}.pot"
    write_pot(entries, pot)
    print(f"Wrote {pot} ({len(entries)} entries from {len(sources)} files)")

    for lang, trans, plur in (
        ("en", {}, {}),
        ("tr", TR_SEED, TR_PLURAL_SEED),
    ):
        po = LOCALE / lang / "LC_MESSAGES" / f"{DOMAIN}.po"
        mo = LOCALE / lang / "LC_MESSAGES" / f"{DOMAIN}.mo"
        po.parent.mkdir(parents=True, exist_ok=True)
        write_po(lang, entries, po, trans, plur)
        compile_mo(po, mo)
        print(f"Wrote {po} + {mo.name}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
