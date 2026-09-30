#!/bin/bash
# =============================================================================
# WebDanışmanı — HestiaCP panel eklentisi kurulumu
#
#   bash kur.sh              -> kur / güncelle
#   bash kur.sh --onar       -> sessiz onarım (cron kullanır)
#   bash kur.sh --kaldir     -> tamamen kaldır, HestiaCP'yi orijinaline döndür
#   bash kur.sh --durum      -> durum raporu
#
# Ne kurulur:
#   YENİ dosyalar (HestiaCP güncellemesi bunları SİLMEZ):
#     web/inc/wd-helpers.php, web/inc/wd-i18n.php
#     web/locale/{en,tr}/LC_MESSAGES/webdanismani.mo
#     web/list/tools/index.php
#     web/list/health/index.php
#     web/templates/pages/list_tools.php
#     web/templates/pages/list_health.php
#     web/templates/includes/wd-sidebar.php
#     web/templates/includes/wd-topbar.php
#     web/css/themes/custom/webdanismani.css
#   KÖK YETKİLİ TOPLAYICILAR (panel bunları sudo ile çağırır):
#     wd/bin/wd-site-usage   site başına CPU/RAM  (/proc hidepid=invisible)
#     wd/bin/wd-health       mail/DNS/SSL denetimi (dig, exim, sertifika)
#   YAMA (güncelleme bunu geri alır -> cron otomatik yeniden uygular):
#     web/templates/includes/panel.php   (yalnızca iki satır `require`)
# =============================================================================
set -u

H=/usr/local/hestia
W=$H/web
WD=$H/wd
SRC="$(cd "$(dirname "$0")" && pwd)"
MODE="${1:-}"

QUIET=0
[ "$MODE" = "--onar" ] && QUIET=1

say()  { [ "$QUIET" = "1" ] || printf '%s\n' "$*"; }
ok()   { [ "$QUIET" = "1" ] || printf '  \033[0;32m[OK]\033[0m   %s\n' "$*"; }
inf()  { [ "$QUIET" = "1" ] || printf '  \033[0;36m[ ]\033[0m    %s\n' "$*"; }
err()  { printf '  \033[0;31m[HATA]\033[0m %s\n' "$*" >&2; }

# --- Hestia web dosyalarının sahipliğini örnekle ---
own_of() {
	stat -c '%U:%G' "$W/templates/includes/panel.php" 2>/dev/null || echo "root:root"
}

# -----------------------------------------------------------------------------
# KALDIR
# -----------------------------------------------------------------------------
if [ "$MODE" = "--kaldir" ]; then
	say "WebDanismani panel eklentisi kaldiriliyor..."
	if [ -f "$WD/wd-patch.py" ]; then
		python3 "$WD/wd-patch.py" revert || err "panel.php geri alinamadi"
	fi
	if [ -f "$WD/wd-fm-patch.py" ]; then
		python3 "$WD/wd-fm-patch.py" revert
	fi
	[ -f "$W/fm/dist/css/wd-fm.css" ] && unlink "$W/fm/dist/css/wd-fm.css"
	[ -f "$W/fm/dist/js/wd-fm.js" ] && unlink "$W/fm/dist/js/wd-fm.js"
	[ -f "$W/fm/dist/js/wd-fm-i18n.js" ] && unlink "$W/fm/dist/js/wd-fm-i18n.js"
	[ -f "$W/fm/dist/js/wd-fm-i18n.tr.json" ] && unlink "$W/fm/dist/js/wd-fm-i18n.tr.json"
	[ -f "$W/fm/wd-fm-i18n.php" ] && unlink "$W/fm/wd-fm-i18n.php"
	[ -f "$WD/lib/wd_i18n.py" ] && unlink "$WD/lib/wd_i18n.py"
	rmdir "$WD/lib" 2>/dev/null
	# Kaynak sablonlarini silmeden ONCE onlara bagli siteleri stok sablona
	# dondur; ters sirada yapilirsa siteler havuzsuz kalir.
	if [ -x "$WD/bin/wd-kaynak" ]; then
		inf "kaynak limitleri geri aliniyor..."
		"$WD/bin/wd-kaynak" geri-al || err "kaynak limitleri geri alinamadi"
	fi
	for f in \
		"$W/inc/wd-helpers.php" \
		"$W/inc/wd-i18n.php" \
		"$W/list/tools/index.php" \
		"$W/list/health/index.php" \
		"$W/list/disk/index.php" \
		"$W/list/httpauth/index.php" \
		"$W/list/errorpages/index.php" \
		"$W/list/cloudflare/index.php" \
		"$W/list/mailrapor/index.php" \
		"$W/list/yonlendirme/index.php" \
		"$W/list/guvenlik/index.php" \
		"$W/list/gecmis/index.php" \
		"$W/templates/pages/list_tools.php" \
		"$W/templates/pages/list_health.php" \
		"$W/templates/pages/list_disk.php" \
		"$W/templates/pages/list_httpauth.php" \
		"$W/templates/pages/list_errorpages.php" \
		"$W/templates/pages/list_cloudflare.php" \
		"$W/templates/pages/list_mailrapor.php" \
		"$W/templates/pages/list_yonlendirme.php" \
		"$W/templates/pages/list_guvenlik.php" \
		"$W/templates/pages/list_gecmis.php" \
		"$W/templates/includes/wd-sidebar.php" \
		"$W/templates/includes/wd-topbar.php" \
		"$WD/bin/wd-site-usage" \
		"$WD/bin/wd-health" \
		"$WD/bin/wd-kaynak" \
		"$WD/bin/wd-disk" \
		"$WD/cache/health.json" \
		"$WD/cache/disk.json" \
		"/etc/sudoers.d/wd-panel" \
		"/etc/logrotate.d/wd-php-slowlog" \
		"/etc/cron.d/wd-panel"; do
		[ -e "$f" ] && unlink "$f" && inf "silindi: $f"
	done
	# Gettext catalogs
	for lang in en tr; do
		for ext in mo po; do
			f="$W/locale/$lang/LC_MESSAGES/webdanismani.$ext"
			[ -e "$f" ] && unlink "$f" && inf "silindi: $f"
		done
	done
	rmdir "$W/list/tools" "$W/list/health" "$W/list/disk" "$W/list/httpauth" \
		"$W/list/errorpages" "$WD/bin" "$WD/cache" 2>/dev/null
	[ -d "$WD/skel" ] && rm -rf "$WD/skel"
	if [ -f "$W/index.php.wd-orig" ]; then
		cp "$W/index.php.wd-orig" "$W/index.php" && inf "index.php geri alindi"
	fi
	ok "Panel eklentisi kaldirildi. (Tema icin: bash tema/kur.sh --kaldir)"
	exit 0
fi

# -----------------------------------------------------------------------------
# DURUM
# -----------------------------------------------------------------------------
if [ "$MODE" = "--durum" ]; then
	say "WebDanismani panel eklentisi durumu:"
	for f in \
		"$W/inc/wd-helpers.php" \
		"$W/inc/wd-i18n.php" \
		"$W/locale/en/LC_MESSAGES/webdanismani.mo" \
		"$W/locale/tr/LC_MESSAGES/webdanismani.mo" \
		"$W/list/tools/index.php" \
		"$W/list/health/index.php" \
		"$W/list/disk/index.php" \
		"$W/list/httpauth/index.php" \
		"$W/list/errorpages/index.php" \
		"$W/list/cloudflare/index.php" \
		"$W/list/mailrapor/index.php" \
		"$W/list/yonlendirme/index.php" \
		"$W/list/guvenlik/index.php" \
		"$W/list/gecmis/index.php" \
		"$W/templates/pages/list_tools.php" \
		"$W/templates/pages/list_health.php" \
		"$W/templates/pages/list_disk.php" \
		"$W/templates/pages/list_httpauth.php" \
		"$W/templates/pages/list_errorpages.php" \
		"$W/templates/pages/list_cloudflare.php" \
		"$W/templates/pages/list_mailrapor.php" \
		"$W/templates/pages/list_yonlendirme.php" \
		"$W/templates/pages/list_guvenlik.php" \
		"$W/templates/pages/list_gecmis.php" \
		"$W/templates/includes/wd-sidebar.php" \
		"$W/templates/includes/wd-topbar.php" \
		"$W/css/themes/custom/webdanismani.css" \
		"$WD/bin/wd-site-usage" \
		"$WD/bin/wd-health" \
		"$WD/bin/wd-kaynak" \
		"$WD/bin/wd-disk"; do
		# Iki farkli sayfa da index.php adini tasidigi icin ust dizinle birlikte
		# yazilir; yoksa hangisinin eksik oldugu anlasilmaz.
		kisa=$(basename "$(dirname "$f")")/$(basename "$f")
		if [ -f "$f" ]; then ok "$kisa"; else err "EKSIK: $f"; fi
	done
	[ -f "$WD/wd-patch.py" ] && python3 "$WD/wd-patch.py" status
	[ -f "$WD/wd-fm-patch.py" ] && python3 "$WD/wd-fm-patch.py" status
	[ -f /etc/sudoers.d/wd-panel ] && ok "sudo yetkisi kurulu" || err "sudo yetkisi YOK"
	[ -f /etc/cron.d/wd-panel ] && ok "onarim cron'u kurulu" || err "onarim cron'u YOK"
	if [ -f "$WD/cache/health.json" ]; then
		ok "saglik onbellegi: $(date -r "$WD/cache/health.json" '+%d.%m.%Y %H:%M')"
	else
		err "saglik onbellegi YOK (cron henuz calismadi mi?)"
	fi
	if [ -f "$WD/cache/kaynak.json" ]; then
		ok "kaynak onbellegi: $(date -r "$WD/cache/kaynak.json" '+%d.%m.%Y %H:%M')"
	else
		err "kaynak onbellegi YOK"
	fi
	TPLN=$(ls "$H/data/templates/web/php-fpm/" 2>/dev/null | grep -c '^wd-')
	if [ "$TPLN" -gt 0 ]; then ok "kaynak limiti sablonu: $TPLN adet"; else err "kaynak limiti sablonu YOK"; fi
	if [ -x "$WD/bin/wd-kaynak" ]; then
		say ""
		say "  Sitelerin kaynak kademeleri:"
		"$WD/bin/wd-kaynak" durum | sed 's/^/    /'
	fi
	exit 0
fi

# -----------------------------------------------------------------------------
# KUR / ONAR
# -----------------------------------------------------------------------------
say "WebDanismani panel eklentisi kuruluyor..."

if [ ! -d "$W" ]; then
	err "HestiaCP web dizini bulunamadi: $W"
	exit 1
fi

OWN=$(own_of)

# --- 1) Kaynagi kalici depoya kopyala (guncelleme sonrasi onarim icin) ---
# Keep the package root (…/panel's parent) before S is rewritten to wd/src,
# so FM theme files are taken from the fresh checkout, not a stale wd/tema.
PKG_ROOT="$(cd "$SRC/.." && pwd)"
mkdir -p "$WD/src"
if [ "$SRC" != "$WD/src" ]; then
	cp -r "$SRC/." "$WD/src/" 2>/dev/null
	inf "kaynak $WD/src altina kopyalandi"
fi
S="$WD/src"
# Mirror FM/theme assets into wd/tema for --onar and future updates
if [ -d "$PKG_ROOT/tema" ]; then
	mkdir -p "$WD/tema"
	for f in wd-fm.css wd-fm.js wd-fm-i18n.php wd-fm-i18n.tr.json wd-fm-i18n.js \
		wd-modul.css webdanismani.css; do
		[ -f "$PKG_ROOT/tema/$f" ] && cp -f "$PKG_ROOT/tema/$f" "$WD/tema/$f"
	done
fi
cp -f "$S/wd-patch.py" "$WD/wd-patch.py" 2>/dev/null
chmod 700 "$WD/wd-patch.py" 2>/dev/null

# --- 2) Dosyalari yerlestir ---
install_file() {
	local from="$1" to="$2"
	if [ ! -f "$from" ]; then err "kaynak yok: $from"; return 1; fi
	mkdir -p "$(dirname "$to")"
	cp -f "$from" "$to" || return 1
	chown "$OWN" "$to" 2>/dev/null
	chmod 644 "$to"
	return 0
}

FAIL=0
install_file "$S/inc/wd-i18n.php"           "$W/inc/wd-i18n.php"                           || FAIL=1
install_file "$S/inc/wd-helpers.php"        "$W/inc/wd-helpers.php"                        || FAIL=1

# Gettext catalogs (WordPress-style .po/.mo) — language follows Hestia user language
install_locales() {
	local lang mo_src mo_dst
	for lang in en tr; do
		mo_src="$S/locale/$lang/LC_MESSAGES/webdanismani.mo"
		mo_dst="$W/locale/$lang/LC_MESSAGES/webdanismani.mo"
		if [ -f "$mo_src" ]; then
			install_file "$mo_src" "$mo_dst" || return 1
		else
			err "locale eksik: $mo_src (python3 panel/locale/extract-po.py)"
			return 1
		fi
		# Keep .po next to .mo for translators editing on the server
		if [ -f "$S/locale/$lang/LC_MESSAGES/webdanismani.po" ]; then
			install_file "$S/locale/$lang/LC_MESSAGES/webdanismani.po" \
				"$W/locale/$lang/LC_MESSAGES/webdanismani.po" || true
		fi
	done
	return 0
}
install_locales || FAIL=1

# Python gettext helper for root scripts
mkdir -p "$WD/lib"
if [ -f "$S/lib/wd_i18n.py" ]; then
	cp -f "$S/lib/wd_i18n.py" "$WD/lib/wd_i18n.py"
	chown root:root "$WD/lib/wd_i18n.py" 2>/dev/null
	chmod 644 "$WD/lib/wd_i18n.py"
	ok "wd_i18n.py"
else
	err "wd_i18n.py yok"
	FAIL=1
fi

install_file "$S/list-tools/index.php"      "$W/list/tools/index.php"                      || FAIL=1
install_file "$S/list-health/index.php"     "$W/list/health/index.php"                     || FAIL=1
install_file "$S/list-disk/index.php"       "$W/list/disk/index.php"                       || FAIL=1
install_file "$S/list-httpauth/index.php"   "$W/list/httpauth/index.php"                   || FAIL=1
install_file "$S/list-errorpages/index.php" "$W/list/errorpages/index.php"                 || FAIL=1
install_file "$S/list-cloudflare/index.php" "$W/list/cloudflare/index.php"                 || FAIL=1
install_file "$S/list-mailrapor/index.php"  "$W/list/mailrapor/index.php"                  || FAIL=1
install_file "$S/list-yonlendirme/index.php" "$W/list/yonlendirme/index.php"               || FAIL=1
install_file "$S/list-guvenlik/index.php"   "$W/list/guvenlik/index.php"                   || FAIL=1
install_file "$S/list-gecmis/index.php"     "$W/list/gecmis/index.php"                     || FAIL=1
install_file "$S/templates/list_tools.php"  "$W/templates/pages/list_tools.php"            || FAIL=1
install_file "$S/templates/list_health.php" "$W/templates/pages/list_health.php"           || FAIL=1
install_file "$S/templates/list_disk.php"   "$W/templates/pages/list_disk.php"             || FAIL=1
install_file "$S/templates/list_httpauth.php"   "$W/templates/pages/list_httpauth.php"     || FAIL=1
install_file "$S/templates/list_errorpages.php" "$W/templates/pages/list_errorpages.php"   || FAIL=1
install_file "$S/templates/list_cloudflare.php" "$W/templates/pages/list_cloudflare.php"   || FAIL=1
install_file "$S/templates/list_mailrapor.php"  "$W/templates/pages/list_mailrapor.php"    || FAIL=1
install_file "$S/templates/list_yonlendirme.php" "$W/templates/pages/list_yonlendirme.php" || FAIL=1
install_file "$S/templates/list_guvenlik.php"   "$W/templates/pages/list_guvenlik.php"     || FAIL=1
install_file "$S/templates/list_gecmis.php"     "$W/templates/pages/list_gecmis.php"       || FAIL=1

# Varsayilan hata sayfasi sablonlari: panel (hestiaweb) $H/data altini
# okuyamaz, bu yuzden "Varsayilana Don" icin okunur bir kopya tutulur.
mkdir -p "$WD/skel/document_errors"
for e in 403 404 410 50x; do
	src="$H/data/templates/web/skel/document_errors/$e.html"
	[ -f "$src" ] && cp -f "$src" "$WD/skel/document_errors/$e.html"
done
chmod 755 "$WD/skel" "$WD/skel/document_errors" 2>/dev/null
chmod 644 "$WD"/skel/document_errors/*.html 2>/dev/null
ok "varsayilan hata sayfasi sablonlari hazir"
install_file "$S/templates/wd-sidebar.php"  "$W/templates/includes/wd-sidebar.php"         || FAIL=1
install_file "$S/templates/wd-topbar.php"   "$W/templates/includes/wd-topbar.php"          || FAIL=1
[ "$FAIL" = "0" ] && ok "panel dosyalari yerlestirildi"

# --- 2b) Kok yetkili toplayicilar ---
# Panel `hestiaweb` olarak calisir ve ne /proc'taki surecleri (hidepid=invisible)
# ne de sertifika dosyalarini okuyabilir. Bu iki betik sudo ile cagrilir;
# sudoers YALNIZCA bu iki tam yola izin verir, kabuk erisimi vermez.
mkdir -p "$WD/bin" "$WD/cache"
chmod 755 "$WD" "$WD/bin" "$WD/cache"
for b in wd-site-usage wd-health wd-kaynak wd-disk wd-uyari wd-cloudflare wd-mailrapor wd-yonlendirme wd-guvenlik wd-tarama wd-gecmis; do
	if [ -f "$S/bin/$b" ]; then
		cp -f "$S/bin/$b" "$WD/bin/$b"
		chown root:root "$WD/bin/$b"
		chmod 755 "$WD/bin/$b"
		if python3 -c "import ast,sys; ast.parse(open(sys.argv[1]).read())" "$WD/bin/$b" 2>/dev/null; then
			ok "toplayici: $b"
		else
			err "$b sozdizimi hatali - KURULMADI"
			unlink "$WD/bin/$b"
		fi
	else
		err "kaynak yok: $S/bin/$b"
	fi
done

# --- 2c) sudo yetkisi ---
# visudo -c ile dogrulanmadan yerine konmaz: bozuk bir sudoers dosyasi
# sudo'yu TAMAMEN kullanilamaz hale getirir.
SUDO_TMP=$(mktemp)
cat > "$SUDO_TMP" <<EOF
# WebDanismani panel eklentisi
# Panel (hestiaweb) /proc surecleri ve sertifika dosyalarini okuyamaz.
# Kabuk yetkisi DEGIL, yalnizca bu iki betige calistirma izni verilir.
hestiaweb ALL=NOPASSWD:$WD/bin/wd-site-usage
hestiaweb ALL=NOPASSWD:$WD/bin/wd-health
hestiaweb ALL=NOPASSWD:$WD/bin/wd-kaynak json
hestiaweb ALL=NOPASSWD:$WD/bin/wd-disk refresh
hestiaweb ALL=NOPASSWD:$WD/bin/wd-cloudflare *
hestiaweb ALL=NOPASSWD:$WD/bin/wd-mailrapor refresh *
hestiaweb ALL=NOPASSWD:$WD/bin/wd-yonlendirme *
hestiaweb ALL=NOPASSWD:$WD/bin/wd-guvenlik *
hestiaweb ALL=NOPASSWD:$WD/bin/wd-tarama refresh *
hestiaweb ALL=NOPASSWD:$WD/bin/wd-gecmis json *
EOF
if visudo -c -q -f "$SUDO_TMP" > /dev/null 2>&1; then
	install -m 0440 -o root -g root "$SUDO_TMP" /etc/sudoers.d/wd-panel
	ok "sudo yetkisi tanimlandi"
else
	err "sudoers dogrulamasi basarisiz - DEGISIKLIK YAPILMADI"
fi
rm -f "$SUDO_TMP"

# Tema (varsa; tema ayrica tema/kur.sh ile de kurulabilir)
if [ -f "$S/../tema/webdanismani.css" ]; then
	install_file "$S/../tema/webdanismani.css" "$W/css/themes/custom/webdanismani.css" && ok "tema guncellendi"
elif [ -f "$S/tema/webdanismani.css" ]; then
	install_file "$S/tema/webdanismani.css" "$W/css/themes/custom/webdanismani.css" && ok "tema guncellendi"
fi

# --- 3) Sozdizimi dogrulama ---
PHPBIN=""
for b in "$H/php/bin/php" php; do
	command -v "$b" >/dev/null 2>&1 && PHPBIN="$b" && break
done
if [ -n "$PHPBIN" ]; then
	LINTFAIL=0
	for f in \
		"$W/inc/wd-helpers.php" \
		"$W/inc/wd-i18n.php" \
		"$W/list/tools/index.php" \
		"$W/list/health/index.php" \
		"$W/list/disk/index.php" \
		"$W/list/httpauth/index.php" \
		"$W/list/errorpages/index.php" \
		"$W/list/cloudflare/index.php" \
		"$W/list/mailrapor/index.php" \
		"$W/list/yonlendirme/index.php" \
		"$W/list/guvenlik/index.php" \
		"$W/list/gecmis/index.php" \
		"$W/templates/pages/list_tools.php" \
		"$W/templates/pages/list_health.php" \
		"$W/templates/pages/list_disk.php" \
		"$W/templates/pages/list_httpauth.php" \
		"$W/templates/pages/list_errorpages.php" \
		"$W/templates/pages/list_cloudflare.php" \
		"$W/templates/pages/list_mailrapor.php" \
		"$W/templates/pages/list_yonlendirme.php" \
		"$W/templates/pages/list_guvenlik.php" \
		"$W/templates/pages/list_gecmis.php" \
		"$W/templates/includes/wd-sidebar.php" \
		"$W/templates/includes/wd-topbar.php"; do
		if ! out=$("$PHPBIN" -l "$f" 2>&1); then
			err "sozdizimi hatasi: $f"
			printf '        %s\n' "$out" >&2
			LINTFAIL=1
		fi
	done
	if [ "$LINTFAIL" = "1" ]; then
		err "Yama UYGULANMADI (once PHP hatalarini duzeltin)"
		exit 1
	fi
	ok "PHP sozdizimi temiz"
fi

# --- 4) panel.php yamasi ---
python3 "$WD/wd-patch.py" apply
PATCH_RC=$?
if [ "$PATCH_RC" != "0" ] && [ "$PATCH_RC" != "2" ]; then
	err "panel.php yamasi basarisiz (kod $PATCH_RC)"
fi

# --- 4b) Dosya yoneticisi (FileGator) temasi ---
# wd-fm.css YENI dosyadir (guncellemede silinmez); onu yukleyen tek satirlik
# <link> ise STOK configuration.php'ye eklenir ve guncellemede geri alinir,
# bu yuzden --onar her calistiginda yeniden uygulanir.
if [ -d "$W/fm/dist/css" ]; then
	FMCSS=""
	for c in "$PKG_ROOT/tema/wd-fm.css" "$WD/tema/wd-fm.css" "$S/../tema/wd-fm.css" "$S/tema/wd-fm.css"; do
		[ -f "$c" ] && FMCSS="$c" && break
	done
	if [ -n "$FMCSS" ]; then
		cp -f "$FMCSS" "$W/fm/dist/css/wd-fm.css"
		chown "$OWN" "$W/fm/dist/css/wd-fm.css" 2>/dev/null
		chmod 644 "$W/fm/dist/css/wd-fm.css"
		# Sol menu + klasor agacini ekleyen kabuk betigi
		FMJS="$(dirname "$FMCSS")/wd-fm.js"
		if [ -f "$FMJS" ]; then
			mkdir -p "$W/fm/dist/js"
			cp -f "$FMJS" "$W/fm/dist/js/wd-fm.js"
			chown "$OWN" "$W/fm/dist/js/wd-fm.js" 2>/dev/null
			chmod 644 "$W/fm/dist/js/wd-fm.js"
			# Per-user FM i18n: PHP endpoint reads session language
			FMI18N_PHP="$(dirname "$FMCSS")/wd-fm-i18n.php"
			FMI18N_TR_JSON="$(dirname "$FMCSS")/wd-fm-i18n.tr.json"
			if [ -f "$FMI18N_PHP" ]; then
				cp -f "$FMI18N_PHP" "$W/fm/wd-fm-i18n.php"
				chown "$OWN" "$W/fm/wd-fm-i18n.php" 2>/dev/null
				chmod 644 "$W/fm/wd-fm-i18n.php"
				ok "fm i18n php endpoint"
			else
				err "wd-fm-i18n.php bulunamadi"
			fi
			if [ -f "$FMI18N_TR_JSON" ]; then
				cp -f "$FMI18N_TR_JSON" "$W/fm/dist/js/wd-fm-i18n.tr.json"
				chown "$OWN" "$W/fm/dist/js/wd-fm-i18n.tr.json" 2>/dev/null
				chmod 644 "$W/fm/dist/js/wd-fm-i18n.tr.json"
			fi
			# Keep legacy empty JS as harmless fallback if something still requests it
			printf '%s\n' 'window.WDFM_I18N = window.WDFM_I18N || {};' > "$W/fm/dist/js/wd-fm-i18n.js"
			chown "$OWN" "$W/fm/dist/js/wd-fm-i18n.js" 2>/dev/null
			chmod 644 "$W/fm/dist/js/wd-fm-i18n.js"
		else
			err "wd-fm.js bulunamadi - sol menu eklenmeyecek"
		fi
		ok "dosya yoneticisi temasi kopyalandi"
		cp -f "$S/wd-fm-patch.py" "$WD/wd-fm-patch.py" 2>/dev/null
		chmod 700 "$WD/wd-fm-patch.py" 2>/dev/null
		python3 "$WD/wd-fm-patch.py" apply
	else
		err "wd-fm.css bulunamadi - dosya yoneticisi temasi atlandi"
	fi
fi

# --- 5) Giris sonrasi acilis sayfasi: Araclar ---
# Stok index.php tek satirlik bir yonlendirmedir:
#   header("Location: /" . (isset($_SESSION["user"]) ? "list/user" : "login") . "/");
# Yalnizca hedef parcayi degistiriyoruz; yol birlestirmesine DOKUNMUYORUZ.
if [ -f "$W/index.php" ]; then
	if grep -q '"list/tools"' "$W/index.php"; then
		: # zaten ayarli
	elif grep -q '"list/user"' "$W/index.php" && [ "$(wc -c < "$W/index.php")" -lt 400 ]; then
		[ -f "$W/index.php.wd-orig" ] || cp "$W/index.php" "$W/index.php.wd-orig"
		sed -i 's#"list/user"#"list/tools"#' "$W/index.php"
		if [ -n "$PHPBIN" ] && "$PHPBIN" -l "$W/index.php" >/dev/null 2>&1 \
			&& grep -q '"list/tools"' "$W/index.php" && ! grep -q 'list/tools/.*list/user' "$W/index.php"; then
			ok "acilis sayfasi /list/tools/ olarak ayarlandi"
		else
			cp "$W/index.php.wd-orig" "$W/index.php"
			err "index.php yamasi geri alindi (dogrulama basarisiz)"
		fi
	else
		inf "index.php beklenen bicimde degil - acilis sayfasi degistirilmedi"
	fi
fi

# --- 6) Kendi kendini onaran cron (HestiaCP guncellemesi yamayi silerse) ---
cat > /etc/cron.d/wd-panel <<EOF
# WebDanismani panel eklentisi
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# HestiaCP guncellemesi panel.php yamasini geri alirsa yeniden uygular
30 5 * * * root bash $WD/src/kur.sh --onar > /dev/null 2>&1

# Saglik denetimini tazeler. Sayfa onbellekten okur, canli DNS sorgusu
# YAPMAZ; guncelligi bu satir saglar. Rastgele gecikme ayni dakikada baslayan
# diger islerle cakismayi onler (crontab'da % kacis ister, shuf gerektirmez).
*/15 * * * * root sleep \$(shuf -i 0-45 -n 1); $WD/bin/wd-health refresh > /dev/null 2>&1

# Alan adi -> kaynak kademesi eslemesini panelin okuyacagi dosyaya yazar.
*/15 * * * * root $WD/bin/wd-kaynak onbellek > /dev/null 2>&1

# Disk kullanim analizi. du/find buyuk hesaplarda pahalidir; 6 saatte bir.
17 */6 * * * root $WD/bin/wd-disk refresh > /dev/null 2>&1

# Uyari katmani: bulgulari panel bildirimine tasir (tekrar korumali).
*/20 * * * * root $WD/bin/wd-uyari calistir > /dev/null 2>&1

# Cloudflare IP araliklari - eskirse gercek ziyaretci IP'si bozulur.
41 4 * * * root $WD/bin/wd-cloudflare ip-guncelle --sessiz > /dev/null 2>&1

# Mail teslim raporu (exim gunlugu taramasi).
23 */4 * * * root $WD/bin/wd-mailrapor refresh 7 > /dev/null 2>&1

# Yeni acilan sitelere kaynak kademesi (kaynak.json > otomatik: true ise).
52 * * * * root $WD/bin/wd-kaynak uygula --yeni > /dev/null 2>&1

# Guvenlik denetimi (gunde iki kez).
7 3,15 * * * root $WD/bin/wd-guvenlik refresh > /dev/null 2>&1

# Zararli kod taramasi: pahali oldugu icin gecelik, yalnizca son 2 gunde
# degismis dosyalar. Tam tarama panelden elle yapilir.
34 2 * * * root $WD/bin/wd-tarama refresh 2 > /dev/null 2>&1

# Kaynak gecmisi ornegi.
*/5 * * * * root $WD/bin/wd-gecmis ornekle > /dev/null 2>&1
EOF
chmod 644 /etc/cron.d/wd-panel
ok "cron kuruldu (onarim 05:30, saglik denetimi 15 dk)"

# --- 6b) Kaynak limiti sablonlari ---
# Sablonlar YENI dosyadir (wd-<kademe>-PHP-x_y.tpl) ve guncellemede silinmez;
# yine de --onar her calistiginda tazelenir ki kaynak.json degisikligi
# kendiliginden yansisin.
#
# Sablonlar URETILIR ama alan adlarina OTOMATIK ATANMAZ: calisan bir sitenin
# php-fpm havuzunu haber vermeden degistirmek dogru degil. Atama icin:
#   /usr/local/hestia/wd/bin/wd-kaynak uygula
if [ -x "$WD/bin/wd-kaynak" ]; then
	if "$WD/bin/wd-kaynak" sablonlar > /dev/null 2>&1; then
		ok "kaynak limiti sablonlari hazir"
	else
		err "kaynak limiti sablonlari uretilemedi"
	fi
	"$WD/bin/wd-kaynak" onbellek > /dev/null 2>&1
fi

# Yavas istek gunlukleri sinirsiz buyumesin.
cat > /etc/logrotate.d/wd-php-slowlog <<'EOF'
# WebDanismani - php-fpm yavas istek gunlukleri
/var/log/php-fpm-slow-*.log {
	weekly
	rotate 4
	missingok
	notifempty
	compress
	delaycompress
	copytruncate
}
EOF
chmod 644 /etc/logrotate.d/wd-php-slowlog

# Ilk saglik verisini hemen uret ki sayfa bos acilmasin.
if [ -x "$WD/bin/wd-health" ] && [ ! -f "$WD/cache/health.json" ]; then
	inf "ilk saglik denetimi calistiriliyor..."
	"$WD/bin/wd-health" refresh > /dev/null 2>&1 && ok "saglik onbellegi olusturuldu" \
		|| err "ilk saglik denetimi basarisiz"
fi

if [ -x "$WD/bin/wd-disk" ] && [ ! -f "$WD/cache/disk.json" ]; then
	inf "ilk disk taramasi calistiriliyor..."
	"$WD/bin/wd-disk" refresh > /dev/null 2>&1 && ok "disk onbellegi olusturuldu" \
		|| err "ilk disk taramasi basarisiz"
fi

# --- 6c) Ek moduller (phpayar, waf, erisim, wp, klon, kur, git, node, yedek, bayi) ---
# Ayri betik, ayri sudoers/cron dosyasi; bu betigin geri kalanina dokunmaz.
if [ -f "$S/kur-modul.sh" ]; then
	# WD_KAYNAK: bu betigin ORIJINAL calistirildigi dizin; tema/wd-modul.css oradan bulunur.
	WD_KAYNAK="$SRC" bash "$S/kur-modul.sh" "$MODE"
fi

# --- 7) Onbellek tazele ---
$H/bin/v-refresh-sys-theme > /dev/null 2>&1

say ""
say "Tamamlandi. Panelde CTRL+F5 yapin."
say "Araclar sayfasi: /list/tools/"
