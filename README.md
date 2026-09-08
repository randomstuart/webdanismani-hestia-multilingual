# WebDanışmanı — HestiaCP Theme & Extra Modules

A modern theme for [HestiaCP](https://hestiacp.com) plus **twenty extra panel
pages** that HestiaCP does not ship: health checks, security audit, disk
breakdown, WordPress tools, clone/staging, WAF, Node.js apps, Git deploy,
reseller management and more.

Free, MIT-licensed. Install it on any server you like.

**Theme & modules:** <https://webdanismani.com>
**Support, bug reports, feature requests:** <https://oblifex.com>

> **The UI is in Turkish.** Labels are written directly into the templates
> rather than into HestiaCP's i18n system. Translation contributions are very
> welcome. Turkish documentation: [BENIOKU.md](BENIOKU.md).

---

## Install

Upload `webdanismani-hestia.tar.gz` to the server, then as **root**:

```bash
cd /root
tar xzf webdanismani-hestia.tar.gz
cd webdanismani-hestia
bash kur.sh
```

Or clone:

```bash
cd /root
git clone <repository-url> webdanismani-hestia
cd webdanismani-hestia
bash kur.sh
```

Press **CTRL+F5** in the panel afterwards. The post-login landing page becomes
**Tools** (`/list/tools/`), which links every module.

Everything is reversible: `bash kur.sh --kaldir` restores stock HestiaCP.

### Requirements

| | |
|---|---|
| HestiaCP | 1.9 or newer (developed and tested on 1.10.4) |
| OS | Ubuntu / Debian, as supported by HestiaCP |
| Web server | nginx (+ optional Apache) — HestiaCP default |
| Tools | `python3`, `curl` — already shipped with HestiaCP |
| Privileges | root |

Optional: `node` for the Node.js module (the page warns if missing);
`wp-cli` for the WordPress module (downloaded from GitHub during install if
absent).

Fonts (Inter, JetBrains Mono) are downloaded **once at install time** and
served from your server; the panel makes no external requests at runtime.

---

## What you get

### Theme
Dark-green sidebar, Inter + JetBrains Mono, edge-to-edge content (stock
HestiaCP squeezes content into 1024 px), reworked list and form pages, a
search box in the top bar (`/` shortcut) and a quick-install button. The
**file manager** gets the same theme, the panel sidebar and a persistent folder
tree.

### Panel pages

| Page | URL | What it does |
|---|---|---|
| Tools | `/list/tools/` | One-page entry point to every tool of the account |
| Health Center | `/list/health/` | PTR, MX, SPF, DKIM, DMARC, A record, SSL expiry, RBL blacklists, mail queue — compares what the panel *thinks* with what public DNS *actually* serves, with a fix hint per finding |
| Security *(admin)* | `/list/guvenlik/` | SSH, 2FA, API exposure, fail2ban, pending security updates, open ports, world-writable files; **behaviour-based malware scan**; guarded "fix now" actions |
| Disk Usage | `/list/disk/` | Category breakdown, per-domain usage, mailboxes, top-20 files and top-15 directories |
| Mail Report | `/list/mailrapor/` | Per-sender volume and bounce rate — catch a compromised mailbox before the IP gets blacklisted |
| Resource History | `/list/gecmis/` | 30-day CPU/RAM per site, hourly *peak* (averages hide 5-minute spikes) |
| Cloudflare | `/list/cloudflare/` | Real visitor IP (nginx + Apache), cache purge, dev mode, push DNS, IP range refresh |
| Redirects & Rules | `/list/yonlendirme/` | Path redirects, security headers, hotlink protection, IP blocking; nginx is validated and rolled back on error |
| Directory Password | `/list/httpauth/` | HTTP basic auth for a site (HestiaCP has the CLI, not the UI) |
| Custom Error Pages | `/list/errorpages/` | Edit 403 / 404 / 410 / 5xx pages; live instantly, revert to default |

### Extra modules (the cPanel gap)

| Page | URL | cPanel equivalent | What it does |
|---|---|---|---|
| PHP Settings | `/list/phpayar/` | MultiPHP INI Editor | Per-domain `.user.ini`: upload size, timeouts, error display, PHP error log |
| Web Application Firewall | `/list/waf/` | ModSecurity | Blocks bad bots and attack patterns, rate-limits login pages |
| Uptime Monitor | `/list/erisim/` | — | External HTTP check every 5 min, notification after 2 consecutive failures, 30-day history |
| WordPress Tools | `/list/wp/` | WP Toolkit | Core/plugin/theme updates, auto-update, integrity check, one-click admin login |
| Clone / Staging | `/list/klon/` | WP Toolkit staging | Clone a site (rsync + DB + search-replace), push changes live, backup before publish |
| App Installer *(admin)* | `/list/kur/` | Softaculous | Installs PHP apps from **your own catalog** (see `panel/MODULLER.md`) |
| Git Deploy | `/list/git/` | Git Version Control | Clone a repo into a site, per-account deploy key, manual or automatic pull |
| Node.js Apps | `/list/node/` | Application Manager | Run a Node.js app as a systemd service and bind it to a domain |
| Backup Browser | `/list/yedek/` | JetBackup file restore | Browse inside a backup archive, restore a single file or folder |
| Reseller Management | `/list/bayi/` | WHM reseller | Admin defines resellers; a reseller creates, suspends and re-packages their own customers |

### Per-site resource limits
The stock php-fpm template has **no** `memory_limit` and no
`request_terminate_timeout`; php.ini is only a default that customer code can
raise with `ini_set`. This module generates a php-fpm template per package and
enforces limits with `php_admin_value`.

```bash
/usr/local/hestia/wd/bin/wd-kaynak durum      # show what would change
/usr/local/hestia/wd/bin/wd-kaynak uygula     # assign by package
/usr/local/hestia/wd/bin/wd-kaynak geri-al    # revert to stock templates
```

Templates are **generated but never assigned automatically** — silently
changing a live site's pool would be wrong.

### Alerts
Health, disk, backup, security, malware and uptime findings are pushed into
HestiaCP's **own notification bell**; optional e-mail. Alerts repeat every 24 h
and a "resolved" notice is sent when the issue disappears. Thresholds live in
`/usr/local/hestia/wd/uyari.json`.

---

## What changes on your server

| What | Where |
|---|---|
| New files: theme, 20 pages, 21 root helper scripts | `/usr/local/hestia/web/…`, `/usr/local/hestia/wd/…` |
| Three tiny patches (below) | `web/templates/includes/panel.php`, `web/fm/configuration.php`, `web/index.php` |
| sudo rules (helper scripts only) | `/etc/sudoers.d/wd-panel`, `/etc/sudoers.d/wd-modul` |
| cron entries (cache refresh, self-repair) | `/etc/cron.d/wd-panel`, `/etc/cron.d/wd-modul` |
| WAF login rate-limit zone | `/etc/nginx/conf.d/wd-waf.conf` |
| Node.js nginx template (derived from stock) | `data/templates/web/nginx/…/wd-node.tpl` |
| Resource-limit php-fpm templates | `data/templates/web/php-fpm/wd-*.tpl` |
| logrotate for php slow log | `/etc/logrotate.d/wd-php-slowlog` |
| wp-cli (if missing) | `/usr/local/bin/wp` |

**Patches:** two `require` lines in `panel.php` (sidebar and top bar), one
`<link>` line in the file manager's `configuration.php`, and the landing
target in `index.php` (`list/user` → `list/tools`). Each is checked with
`php -l` before being written; originals are kept next to them as `.wd-orig`.

## Survives HestiaCP updates

HestiaCP updates overwrite **existing** files but never delete **new** ones.
Every page, template, stylesheet and helper here is a new file. The patches
above are re-applied daily by cron (`kur.sh --onar` at 05:30,
`kur-modul.sh --onar` at 05:40). Check status any time:

```bash
bash /usr/local/hestia/wd/src/kur.sh --durum
bash /usr/local/hestia/wd/src/kur-modul.sh --durum
```

## Uninstall

```bash
cd /root/webdanismani-hestia
bash kur.sh --kaldir
```

Reverts the patches, removes the added files, restores the default theme and
moves resource-limited sites back to stock php-fpm templates. User settings
(`wd/bayi.json`, the `wd/uygulamalar/` catalog) are kept.

## Security model

- The panel runs as `hestiaweb` and cannot read `/proc`, certificates, exim
  logs or customer files. The sudoers rules therefore allow **only** the helper
  scripts under `/usr/local/hestia/wd/bin/` — no shell, no other command.
  Files are validated with `visudo -c` before being installed.
- Scripts that take actions from the panel are executed by `sudo` without a
  shell, with arguments as an array; write payloads travel as JSON on STDIN.
  No helper uses `shell=True` or `os.system`.
- Pages that write always take the user **from the session**, never from the
  request; domain ownership is verified and every POST goes through HestiaCP's
  own CSRF check.
- Anything touching nginx is validated with `nginx -t` and rolled back on
  failure; SSH hardening validates with `sshd -t` and uses `reload`.
- The malware scan **never deletes or quarantines** files; it only reports.

## Known limits

- Turkish UI only.
- No "Type" column in the file manager (FileGator's table breaks when columns
  are injected); type is shown with a coloured icon instead.
- Resource metering counts PHP workers only; nginx, Apache and MariaDB are
  shared and cannot be attributed per site reliably.
- The App Installer ships with an empty catalog — you upload your own packages.
- No commercial support commitment.

## Development

- Edit `tema/_govde.css`; `tema/derle.ps1` (Windows PowerShell) builds
  `tema/webdanismani.css`. Do not edit the generated file.
- Module pattern and catalog manifest format: `panel/MODULLER.md` (Turkish).
- PHP is linted with `php -l` on the server during install, Python with
  `ast.parse`; a file that fails is not installed.

## License

MIT — see [LICENSE](LICENSE).
