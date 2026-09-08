#!/bin/bash
# WebDanismani HestiaCP temasi - kurulum / guncelleme
#
# Kullanim (sunucuda):
#   bash kur.sh              -> temayi kur + sistem geneli aktif et
#   bash kur.sh --sadece-kur -> sadece dosyayi kopyala, aktif etme
#   bash kur.sh --kaldir     -> temayi kaldir, varsayilana don
#
# Tema dosyasi HestiaCP'nin RESMI ozel tema dizinine yazilir:
#   /usr/local/hestia/web/css/themes/custom/webdanismani.css
# Bu dizin HestiaCP guncellemelerinde SILINMEZ.

set -u
H=/usr/local/hestia
TEMA=webdanismani
HEDEF="$H/web/css/themes/custom/$TEMA.css"
KAYNAK="$(cd "$(dirname "$0")" && pwd)/$TEMA.css"

renk() { printf '\033[%sm%s\033[0m\n' "$1" "$2"; }
ok()   { renk '0;32' "  [OK]   $1"; }
bilgi(){ renk '0;36' "  [ ]    $1"; }
hata() { renk '0;31' "  [HATA] $1"; }

if [ "${1:-}" = "--kaldir" ]; then
	renk '1;36' "WebDanismani temasi kaldiriliyor..."
	$H/bin/v-change-sys-config-value THEME 'default'
	for u in $($H/bin/v-list-users plain | cut -f1); do
		mevcut=$(grep -oP "THEME='\K[^']*" "$H/data/users/$u/user.conf" 2>/dev/null)
		[ "$mevcut" = "$TEMA" ] && $H/bin/v-change-user-theme "$u" 'default' && bilgi "$u -> default"
	done
	[ -f "$HEDEF" ] && unlink "$HEDEF" && ok "tema dosyasi silindi"
	$H/bin/v-refresh-sys-theme >/dev/null 2>&1
	ok "Varsayilan temaya donuldu."
	exit 0
fi

renk '1;36' "WebDanismani HestiaCP temasi kuruluyor..."

if [ ! -f "$KAYNAK" ]; then
	hata "Tema dosyasi bulunamadi: $KAYNAK"
	exit 1
fi

# 1) Ozel tema dizini
mkdir -p "$H/web/css/themes/custom"
ok "ozel tema dizini hazir"

# 1b) Inter + JetBrains Mono (tasarimin yazi tipleri) - panelde barindirilir,
#     boylece calisma aninda dis istek yok. Eksikse Google Fonts'tan cekilir.
#     Ikisi de DEGISKEN font: Google tek dosyayi tum agirliklara sunar, bu
#     yuzden agirlik basina degil ALTKUME basina dosya var (4 adet).
EKSIK=0
for f in inter-latin inter-latin-ext \
         jetbrains-mono-latin jetbrains-mono-latin-ext; do
	[ -f "$H/web/webfonts/$f.woff2" ] || EKSIK=1
done

if [ "$EKSIK" = "1" ]; then
	bilgi "Inter / JetBrains Mono yazi tipleri eksik, indiriliyor..."
	mkdir -p "$H/web/webfonts"
	UA="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36"
	FURL="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
	if curl -fsS -A "$UA" "$FURL" -o /tmp/wd-fontlar.css 2>/dev/null; then
		python3 - <<'PY' && ok "yazi tipleri kuruldu" || hata "yazi tipi indirilemedi - sistem yazi tipine dusulecek"
import re, sys, urllib.request

DEST = '/usr/local/hestia/web/webfonts/'
css = open('/tmp/wd-fontlar.css', encoding='utf-8').read()

# Inter ve JetBrains Mono DEGISKEN fontlardir: Google, istenen tum agirliklar
# icin AYNI dosyayi dondurur. Bu yuzden dosya adi agirliktan degil yalnizca
# aile+altkumeden uretilir; ayni ada ikinci kez rastlanirsa atlanir.
# (Agirligi ada katsaydik 4 dosya 12 ada dagilir, 8'i eksik kalirdi.)
beklenen = {'inter-latin', 'inter-latin-ext',
            'jetbrains-mono-latin', 'jetbrains-mono-latin-ext'}
alinan = set()

for subset, block in re.findall(r'/\*\s*([\w-]+)\s*\*/\s*(@font-face\s*\{.*?\})', css, re.S):
    if subset not in ('latin', 'latin-ext'):
        continue
    fam = re.search(r"font-family:\s*'([^']+)'", block).group(1)
    url = re.search(r"url\((https://[^)]+)\)", block).group(1)
    ad = fam.lower().replace(' ', '-') + '-' + subset
    if ad in alinan:
        continue
    urllib.request.urlretrieve(url, DEST + ad + '.woff2')
    alinan.add(ad)

eksik = beklenen - alinan
if eksik:
    sys.stderr.write('indirilemeyen yazi tipi: ' + ', '.join(sorted(eksik)) + '\n')
    sys.exit(1)
PY
		chmod 644 "$H"/web/webfonts/inter-*.woff2 "$H"/web/webfonts/jetbrains-mono-*.woff2 2>/dev/null
	else
		hata "Google Fonts'a ulasilamadi - panel sistem yazi tipiyle calisir"
	fi
else
	ok "Inter / JetBrains Mono yazi tipleri mevcut"
fi

# 2) Onceki surumu yedekle
if [ -f "$HEDEF" ]; then
	cp "$HEDEF" "$HEDEF.yedek"
	bilgi "onceki surum $HEDEF.yedek olarak yedeklendi"
fi

# 3) Kopyala
cp "$KAYNAK" "$HEDEF"
chown root:root "$HEDEF"
chmod 644 "$HEDEF"
ok "tema kuruldu: $HEDEF ($(wc -c < "$HEDEF") bayt)"

# 4) Tema listesinde gorunuyor mu
if $H/bin/v-list-sys-themes plain | grep -qx "$TEMA"; then
	ok "tema listesine kaydedildi"
else
	hata "tema listede gorunmuyor - dosya adini kontrol edin"
	exit 1
fi

if [ "${1:-}" = "--sadece-kur" ]; then
	bilgi "aktiflestirme atlandi (--sadece-kur)"
	exit 0
fi

# 5) Sistem geneli varsayilan yap
$H/bin/v-change-sys-config-value THEME "$TEMA"
ok "sistem varsayilan temasi: $TEMA"

# 6) Kendi temasini secmis kullanicilari da tasi
for u in $($H/bin/v-list-users plain | cut -f1); do
	mevcut=$(grep -oP "THEME='\K[^']*" "$H/data/users/$u/user.conf" 2>/dev/null)
	if [ -n "$mevcut" ] && [ "$mevcut" != "$TEMA" ]; then
		$H/bin/v-change-user-theme "$u" "$TEMA" && bilgi "$u -> $TEMA"
	fi
done

# 7) Onbellek tazele
$H/bin/v-refresh-sys-theme >/dev/null 2>&1
touch "$H/web/css/themes/custom/$TEMA.css"

renk '1;32' ""
renk '1;32' "Tamamlandi. Panelde CTRL+F5 ile sayfayi yenileyin."
renk '0;36' "Panel: https://$(hostname -f):8083"
