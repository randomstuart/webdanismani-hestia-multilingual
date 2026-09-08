#!/bin/bash
# =============================================================================
# WebDanışmanı — HestiaCP EK MODÜLLER kurulumu
#
#   bash kur-modul.sh              -> kur / güncelle
#   bash kur-modul.sh --onar       -> sessiz onarım (cron)
#   bash kur-modul.sh --kaldir     -> modülleri kaldır (temel eklentiye dokunmaz)
#   bash kur-modul.sh --durum      -> durum raporu
#
# kur.sh bu betiği sonunda çağırır; tek başına da çalıştırılabilir.
#
# Modüller (hepsi YENİ dosya — HestiaCP güncellemesi silmez):
#   phpayar  PHP ayarları           erisim  erişim izleme      wp     WordPress araçları
#   waf      güvenlik duvarı        klon    klon/staging       kur    uygulama kurucu
#   git      git dağıtım            node    Node.js            yedek  yedek gezgini
#   bayi     bayi katmanı
#
# Ayrı dosyalar (temel eklentininkilere DOKUNMAZ):
#   /etc/sudoers.d/wd-modul     /etc/cron.d/wd-modul
#   /etc/nginx/conf.d/wd-waf.conf   (giriş hız sınırı bölgesi)
# =============================================================================
set -u

H=/usr/local/hestia
W=$H/web
WD=$H/wd
SRC="$(cd "$(dirname "$0")" && pwd)"
MODE="${1:-}"

MODULLER="phpayar waf erisim wp klon kur git node yedek bayi"
BETIKLER="wd-phpayar wd-waf wd-erisim wd-wp wd-klon wd-kur wd-git wd-node wd-yedek wd-bayi"
# Kaldirilan moduller (eski kurulumlardan temizlenir)
ESKI_MODULLER="destek"
ESKI_BETIKLER="wd-destek"

QUIET=0
[ "$MODE" = "--onar" ] && QUIET=1

say()  { [ "$QUIET" = "1" ] || printf '%s\n' "$*"; }
ok()   { [ "$QUIET" = "1" ] || printf '  \033[0;32m[OK]\033[0m   %s\n' "$*"; }
inf()  { [ "$QUIET" = "1" ] || printf '  \033[0;36m[ ]\033[0m    %s\n' "$*"; }
err()  { printf '  \033[0;31m[HATA]\033[0m %s\n' "$*" >&2; }

own_of() { stat -c '%U:%G' "$W/templates/includes/panel.php" 2>/dev/null || echo "root:root"; }
# Panel php-fpm'i hestiaweb olarak calisir (dosya sahibi root olsa bile!). Dosya
# sahibinden turetmek sudoers'i root'a yazar ve panel "I'm afraid I can't do that" alir.
web_user() { if id hestiaweb >/dev/null 2>&1; then echo "hestiaweb"; else echo "admin"; fi; }

# -----------------------------------------------------------------------------
# KALDIR
# -----------------------------------------------------------------------------
if [ "$MODE" = "--kaldir" ]; then
	say "WebDanismani ek modulleri kaldiriliyor..."
	for m in $MODULLER; do
		[ -f "$W/list/$m/index.php" ] && unlink "$W/list/$m/index.php" && rmdir "$W/list/$m" 2>/dev/null
		[ -f "$W/templates/pages/list_$m.php" ] && unlink "$W/templates/pages/list_$m.php"
	done
	for b in $BETIKLER; do [ -f "$WD/bin/$b" ] && unlink "$WD/bin/$b"; done
	for f in "$W/inc/wd-modul.php" "$W/css/themes/custom/wd-modul.css" \
		/etc/sudoers.d/wd-modul /etc/cron.d/wd-modul /etc/nginx/conf.d/wd-waf.conf; do
		[ -e "$f" ] && unlink "$f" && inf "silindi: $f"
	done
	for t in "$H/data/templates/web/nginx/php-fpm" "$H/data/templates/web/nginx"; do
		[ -f "$t/wd-node.tpl" ] && unlink "$t/wd-node.tpl"
		[ -f "$t/wd-node.stpl" ] && unlink "$t/wd-node.stpl"
	done
	if nginx -t >/dev/null 2>&1; then systemctl reload nginx >/dev/null 2>&1; fi
	inf "Ayarlar korundu: $WD/bayi.json $WD/uygulamalar/"
	ok "Ek moduller kaldirildi."
	exit 0
fi

# -----------------------------------------------------------------------------
# DURUM
# -----------------------------------------------------------------------------
if [ "$MODE" = "--durum" ]; then
	say "WebDanismani ek modulleri durumu:"
	for m in $MODULLER; do
		if [ -f "$W/list/$m/index.php" ] && [ -f "$W/templates/pages/list_$m.php" ]; then ok "modul: $m"; else err "EKSIK modul: $m"; fi
	done
	for b in $BETIKLER; do
		if [ -x "$WD/bin/$b" ]; then ok "betik: $b"; else err "EKSIK betik: $b"; fi
	done
	[ -f "$W/inc/wd-modul.php" ] && ok "wd-modul.php" || err "wd-modul.php YOK"
	[ -f "$W/css/themes/custom/wd-modul.css" ] && ok "wd-modul.css" || err "wd-modul.css YOK"
	[ -f /etc/sudoers.d/wd-modul ] && ok "sudo yetkisi" || err "sudo yetkisi YOK"
	[ -f /etc/cron.d/wd-modul ] && ok "cron" || err "cron YOK"
	[ -f /etc/nginx/conf.d/wd-waf.conf ] && ok "WAF hiz siniri bolgesi" || err "WAF hiz siniri bolgesi YOK"
	[ -x /usr/local/bin/wp ] && ok "wp-cli: $(/usr/local/bin/wp --version 2>/dev/null | head -1)" || err "wp-cli YOK (WordPress araclari calismaz)"
	command -v node >/dev/null 2>&1 && ok "node: $(node --version)" || inf "node yok (Node.js modulu uyari gosterir)"
	for c in erisim wp; do
		if [ -f "$WD/cache/$c.json" ]; then ok "onbellek $c: $(date -r "$WD/cache/$c.json" '+%d.%m.%Y %H:%M')"; else inf "onbellek $c henuz yok"; fi
	done
	exit 0
fi

# -----------------------------------------------------------------------------
# KUR / ONAR
# -----------------------------------------------------------------------------
say "WebDanismani ek modulleri kuruluyor..."
if [ ! -d "$W" ]; then err "HestiaCP web dizini yok: $W"; exit 1; fi
OWN=$(own_of)
WEBU=$(web_user)

# --- 1) Kaynak kalıcı depoya ---
mkdir -p "$WD/src"
if [ "$SRC" != "$WD/src" ]; then cp -r "$SRC/." "$WD/src/" 2>/dev/null; fi
S="$WD/src"

install_file() {
	local from="$1" to="$2"
	if [ ! -f "$from" ]; then err "kaynak yok: $from"; return 1; fi
	mkdir -p "$(dirname "$to")"
	cp -f "$from" "$to" || return 1
	chown "$OWN" "$to" 2>/dev/null
	chmod 644 "$to"
	return 0
}

# --- 1b) Kaldirilan modullerin kalintilari ---
for m in $ESKI_MODULLER; do
	[ -f "$W/list/$m/index.php" ] && unlink "$W/list/$m/index.php" && rmdir "$W/list/$m" 2>/dev/null && inf "eski modul kaldirildi: $m"
	[ -f "$W/templates/pages/list_$m.php" ] && unlink "$W/templates/pages/list_$m.php"
done
for b in $ESKI_BETIKLER; do [ -f "$WD/bin/$b" ] && unlink "$WD/bin/$b"; done

# --- 2) Panel dosyaları ---
FAIL=0
install_file "$S/inc/wd-modul.php" "$W/inc/wd-modul.php" || FAIL=1
for m in $MODULLER; do
	install_file "$S/list-$m/index.php" "$W/list/$m/index.php" || FAIL=1
	install_file "$S/templates/list_$m.php" "$W/templates/pages/list_$m.php" || FAIL=1
done
# CSS: once calistirilan konumun yanindaki tema/, sonra kalici depo. Bulunani
# kalici depoya da kopyala ki --onar (cron) tema klasoru olmadan da calissin.
CSS=""
for c in "${WD_KAYNAK:-$SRC}/../tema/wd-modul.css" "$SRC/../tema/wd-modul.css" "$S/../tema/wd-modul.css" "$S/tema/wd-modul.css"; do [ -f "$c" ] && CSS="$c" && break; done
if [ -n "$CSS" ]; then
	mkdir -p "$S/tema"; [ "$CSS" != "$S/tema/wd-modul.css" ] && cp -f "$CSS" "$S/tema/wd-modul.css"
	install_file "$CSS" "$W/css/themes/custom/wd-modul.css" || FAIL=1
else
	err "wd-modul.css bulunamadi"; FAIL=1
fi
[ "$FAIL" = "0" ] && ok "panel dosyalari yerlestirildi (10 modul)"

# --- 3) Root betikleri ---
mkdir -p "$WD/bin" "$WD/cache" "$WD/cache/erisim" "$WD/cache/yedek" "$WD/uygulamalar" "$WD/yukleme"
chmod 755 "$WD" "$WD/bin" "$WD/cache" "$WD/uygulamalar"
chmod 700 "$WD/cache/erisim" "$WD/cache/yedek"
# Yükleme dizini: panel (hestiaweb) yazar, root okur.
chown "$WEBU:$WEBU" "$WD/yukleme" 2>/dev/null; chmod 770 "$WD/yukleme"
for b in $BETIKLER; do
	if [ -f "$S/bin/$b" ]; then
		cp -f "$S/bin/$b" "$WD/bin/$b"; chown root:root "$WD/bin/$b"; chmod 755 "$WD/bin/$b"
		if python3 -c "import ast,sys; ast.parse(open(sys.argv[1]).read())" "$WD/bin/$b" 2>/dev/null; then
			ok "betik: $b"
		else
			err "$b sozdizimi hatali - KURULMADI"; unlink "$WD/bin/$b"
		fi
	else
		err "kaynak yok: $S/bin/$b"
	fi
done

# --- 4) sudo yetkisi (ayrı dosya; wd-panel'e dokunulmaz) ---
SUDO_TMP=$(mktemp)
{
	echo "# WebDanismani ek modulleri - panel ($WEBU) yalnizca bu betikleri calistirabilir."
	echo "# Arguman belirtilmeyen komut sudoers'ta HER argumanla eslesir; betikler girdiyi kendileri suzer."
	for b in $BETIKLER; do echo "$WEBU ALL=NOPASSWD:$WD/bin/$b"; done
} > "$SUDO_TMP"
if visudo -c -q -f "$SUDO_TMP" >/dev/null 2>&1; then
	install -m 0440 -o root -g root "$SUDO_TMP" /etc/sudoers.d/wd-modul; ok "sudo yetkisi tanimlandi (/etc/sudoers.d/wd-modul)"
else
	err "sudoers dogrulamasi basarisiz - DEGISIKLIK YAPILMADI"
fi
rm -f "$SUDO_TMP"

# --- 5) PHP sözdizimi ---
PHPBIN=""
for b in "$H/php/bin/php" php; do command -v "$b" >/dev/null 2>&1 && PHPBIN="$b" && break; done
if [ -n "$PHPBIN" ]; then
	LINTFAIL=0
	for f in "$W/inc/wd-modul.php" $(for m in $MODULLER; do echo "$W/list/$m/index.php $W/templates/pages/list_$m.php"; done); do
		if ! out=$("$PHPBIN" -l "$f" 2>&1); then err "sozdizimi hatasi: $f"; printf '        %s\n' "$out" >&2; LINTFAIL=1; fi
	done
	[ "$LINTFAIL" = "0" ] && ok "PHP sozdizimi temiz"
fi

# --- 6) WAF http bağlamı (giriş hız sınırı bölgesi) ---
if [ -x "$WD/bin/wd-waf" ]; then
	if "$WD/bin/wd-waf" http-conf >/dev/null 2>&1; then ok "WAF hiz siniri bolgesi yazildi"; else err "WAF http-conf yazilamadi (nginx -t?)"; fi
fi

# --- 7) Node.js nginx şablonu (stok şablon varsa) ---
if [ -x "$WD/bin/wd-node" ]; then
	if "$WD/bin/wd-node" sablon >/dev/null 2>&1; then ok "wd-node nginx sablonu hazir"; else inf "wd-node sablonu uretilemedi (stok default.tpl bulunamadi?)"; fi
fi

# --- 8) wp-cli ---
if [ ! -x /usr/local/bin/wp ]; then
	inf "wp-cli indiriliyor..."
	if curl -fsSL -o /tmp/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar 2>/dev/null \
		&& php /tmp/wp-cli.phar --info >/dev/null 2>&1; then
		install -m 0755 -o root -g root /tmp/wp-cli.phar /usr/local/bin/wp; rm -f /tmp/wp-cli.phar; ok "wp-cli kuruldu"
	else
		rm -f /tmp/wp-cli.phar; err "wp-cli indirilemedi (ag?) - WordPress araclari islem yapamaz; sonra tekrar deneyin"
	fi
else
	ok "wp-cli mevcut"
fi

# --- 9) cron ---
cat > /etc/cron.d/wd-modul <<EOF
# WebDanismani ek modulleri
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# Onarim: HestiaCP guncellemesi sonrasi dosyalari yeniden yerlestirir
40 5 * * * root bash $WD/src/kur-modul.sh --onar > /dev/null 2>&1

# Erisim izleme: 5 dakikada bir tum siteler, gecelik temizlik
*/5 * * * * root $WD/bin/wd-erisim kontrol > /dev/null 2>&1
15 3 * * * root $WD/bin/wd-erisim temizle > /dev/null 2>&1

# WordPress taramasi (gecelik; wp-cli site basina saniyeler surer)
50 3 * * * root $WD/bin/wd-wp tara > /dev/null 2>&1

# Git otomatik cekme (yalnizca isaretli depolar)
*/5 * * * * root $WD/bin/wd-git otomatik-cek > /dev/null 2>&1
EOF
chmod 644 /etc/cron.d/wd-modul
ok "cron kuruldu (/etc/cron.d/wd-modul)"

# --- 10) İlk veriler ---
if [ -x "$WD/bin/wd-erisim" ] && [ ! -f "$WD/cache/erisim.json" ]; then
	"$WD/bin/wd-erisim" kontrol >/dev/null 2>&1 && ok "ilk erisim kontrolu yapildi"
fi

say ""
say "Tamamlandi. Araclar sayfasinda yeni moduller listelenir."
