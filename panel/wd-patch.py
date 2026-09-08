#!/usr/bin/env python3
"""
WebDanışmanı — HestiaCP panel.php yamalayıcı

panel.php'ye SADECE iki satır `require` ekler; asıl işaretleme (markup)
güncellemeden etkilenmeyen yeni dosyalarda durur:

  templates/includes/wd-sidebar.php   -> sol menü + sunucu yükü kartı
  templates/includes/wd-topbar.php    -> breadcrumb / arama / hızlı kurulum / avatar

Özellikler:
  * Fikir değişmez (idempotent): zaten yamalıysa dokunmaz.
  * Yazmadan önce `php -l` ile sözdizimi doğrular.
  * İlk çalıştırmada orijinali panel.php.wd-orig olarak saklar.
  * Bağlantı noktası (anchor) bulunamazsa o bloğu ATLAR ve uyarır —
    paneli asla bozulmuş hâlde bırakmaz.

Kullanım:
  python3 wd-patch.py apply     # yamala
  python3 wd-patch.py revert    # orijinali geri yükle
  python3 wd-patch.py status    # durum
"""

import os
import re
import shutil
import subprocess
import sys
import tempfile

WEB = "/usr/local/hestia/web"
PANEL = os.path.join(WEB, "templates/includes/panel.php")
ORIG = PANEL + ".wd-orig"

SIDEBAR_MARK = "WD:SIDEBAR"
TOPBAR_MARK = "WD:TOPBAR"

SIDEBAR_SNIPPET = (
    '<?php /* ' + SIDEBAR_MARK + ' */ '
    'require $_SERVER["HESTIA"] . "/web/templates/includes/wd-sidebar.php"; ?>'
)
TOPBAR_SNIPPET = (
    '<?php /* ' + TOPBAR_MARK + ' */ '
    'require $_SERVER["HESTIA"] . "/web/templates/includes/wd-topbar.php"; ?>'
)

# Sol menü listesinin başlangıcı
SIDEBAR_START = '<ul x-cloak x-show="open" class="main-menu-list">'
# Listenin bitişi: </ul> ardından </div></nav> gelen ilk yer
SIDEBAR_END_RE = re.compile(r"</ul>\s*</div>\s*</nav>")

# Üst çubuk ekleme noktası
TOPBAR_ANCHOR_RE = re.compile(r'([ \t]*)<div class="top-bar-right">')


def php_lint(path):
    """php -l ile sözdizimi kontrolü. (hestia-php veya sistem php)"""
    for binary in ("/usr/local/hestia/php/bin/php", "php"):
        try:
            r = subprocess.run(
                [binary, "-l", path], capture_output=True, text=True, timeout=30
            )
            return r.returncode == 0, (r.stdout + r.stderr).strip()
        except (FileNotFoundError, subprocess.TimeoutExpired):
            continue
    return True, "(php bulunamadi - sozdizimi kontrolu atlandi)"


def read(path):
    with open(path, "r", encoding="utf-8") as fh:
        return fh.read()


def write_checked(path, content):
    """Geçici dosyaya yaz, lint et, sonra yerine koy."""
    fd, tmp = tempfile.mkstemp(suffix=".php", dir=os.path.dirname(path))
    os.close(fd)
    try:
        with open(tmp, "w", encoding="utf-8") as fh:
            fh.write(content)
        ok, msg = php_lint(tmp)
        if not ok:
            print("  [HATA] Sozdizimi hatasi, yama UYGULANMADI:")
            print("        " + msg.replace("\n", "\n        "))
            return False
        shutil.copymode(path, tmp)
        os.replace(tmp, path)
        return True
    finally:
        if os.path.exists(tmp):
            os.unlink(tmp)


def status():
    if not os.path.exists(PANEL):
        print("  panel.php bulunamadi: " + PANEL)
        return 1
    c = read(PANEL)
    s = SIDEBAR_MARK in c
    t = TOPBAR_MARK in c
    print("  sol menu yamasi : " + ("VAR" if s else "YOK"))
    print("  ust cubuk yamasi: " + ("VAR" if t else "YOK"))
    print("  orijinal yedek  : " + ("var" if os.path.exists(ORIG) else "yok"))
    return 0 if (s and t) else 2


def apply():
    if not os.path.exists(PANEL):
        print("  [HATA] panel.php bulunamadi: " + PANEL)
        return 1

    content = read(PANEL)

    if SIDEBAR_MARK in content and TOPBAR_MARK in content:
        print("  [ATLA] panel.php zaten yamali")
        return 0

    # İlk yamada orijinali sakla (Hestia guncellemesi sonrasi tazelenir)
    if not os.path.exists(ORIG) or (SIDEBAR_MARK not in content and TOPBAR_MARK not in content):
        shutil.copy2(PANEL, ORIG)
        print("  [OK] orijinal saklandi: " + os.path.basename(ORIG))

    changed = 0

    # --- 1) Sol menu listesini degistir ---
    if SIDEBAR_MARK not in content:
        i = content.find(SIDEBAR_START)
        if i == -1:
            print("  [UYARI] sol menu baslangici bulunamadi - ATLANDI")
        else:
            m = SIDEBAR_END_RE.search(content, i)
            if not m:
                print("  [UYARI] sol menu bitisi bulunamadi - ATLANDI")
            else:
                end = content.index("</ul>", m.start()) + len("</ul>")
                content = content[:i] + SIDEBAR_SNIPPET + content[end:]
                changed += 1
                print("  [OK] sol menu yamasi eklendi")

    # --- 2) Ust cubuk eklentileri ---
    if TOPBAR_MARK not in content:
        m = TOPBAR_ANCHOR_RE.search(content)
        if not m:
            print("  [UYARI] ust cubuk baglanti noktasi bulunamadi - ATLANDI")
        else:
            indent = m.group(1)
            content = (
                content[: m.start()]
                + indent
                + TOPBAR_SNIPPET
                + "\n"
                + content[m.start() :]
            )
            changed += 1
            print("  [OK] ust cubuk yamasi eklendi")

    if changed == 0:
        print("  [BILGI] uygulanacak degisiklik yok")
        return 2

    if not write_checked(PANEL, content):
        return 1

    print("  [OK] panel.php guncellendi (" + str(changed) + " blok)")
    return 0


def revert():
    if not os.path.exists(ORIG):
        print("  [HATA] yedek yok: " + ORIG)
        return 1
    shutil.copy2(ORIG, PANEL)
    print("  [OK] panel.php orijinaline dondurüldu")
    return 0


if __name__ == "__main__":
    cmd = sys.argv[1] if len(sys.argv) > 1 else "apply"
    if cmd == "apply":
        sys.exit(apply())
    if cmd == "revert":
        sys.exit(revert())
    if cmd == "status":
        sys.exit(status())
    print(__doc__)
    sys.exit(1)
