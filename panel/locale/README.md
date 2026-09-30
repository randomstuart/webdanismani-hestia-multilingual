# Translations (gettext)

English is the **source language**. Catalogs:

```
panel/locale/webdanismani.pot
panel/locale/en/LC_MESSAGES/webdanismani.po
panel/locale/tr/LC_MESSAGES/webdanismani.po   # fully translated
panel/locale/*/LC_MESSAGES/webdanismani.mo
```

## How language switching works

Follows **HestiaCP’s user language** (per user or system default).

| Layer | Helper | Notes |
|-------|--------|--------|
| PHP UI | `wd__('…')` | Templates / controllers |
| Python helpers | `_("…")` / `N_("…")` | `panel/lib/wd_i18n.py`; `WD_LANG` from panel |
| Cached findings | English msgid → `wd__()` in PHP | health / disk / security / scan |
| File Manager | `wdt("…")` + `/fm/wd-fm-i18n.php` | **Per-user** session language |

## Developer workflow

```bash
python3 panel/locale/extract-po.py
# edit panel/locale/tr/LC_MESSAGES/webdanismani.po if needed
bash panel/kur.sh --onar
```

After changing finding helpers, refresh caches:
`wd-health refresh`, `wd-disk refresh`, `wd-guvenlik refresh`, `wd-tarama refresh`.

## Adding a language

1. Copy `tr` or `en` → e.g. `de/LC_MESSAGES/`
2. Translate `.po`, compile `.mo`
3. Add lang to `install_locales` in `panel/kur.sh`
4. Optional FM: add `tema/wd-fm-i18n.de.json` and extend `wd-fm-i18n.php`
