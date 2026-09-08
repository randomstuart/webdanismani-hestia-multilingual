#!/bin/bash
# =============================================================================
# WebDanışmanı HestiaCP Teması + Ek Modüller — tek adımda kurulum
#
#   bash kur.sh            kur / güncelle
#   bash kur.sh --kaldir   tamamen kaldır, HestiaCP'yi orijinaline döndür
#   bash kur.sh --durum    durum raporu
#
# Tema ve modüller https://webdanismani.com tarafından hazırlanmıştır.
# Destek ve yeni özellik istekleri: https://oblifex.com
#
# Ne kurulur:
#   * Tema        koyu yeşil sol menü, Inter + JetBrains Mono, tam genişlik
#                 içerik, dosya yöneticisi için ayrı tema + klasör ağacı
#   * Panel sayfaları (panel/kur.sh)
#       Sağlık Merkezi, Güvenlik, Disk Kullanımı, Mail Raporu, Kaynak
#       Geçmişi, Cloudflare, Yönlendirmeler, Dizin Şifre Koruma, Özel Hata
#       Sayfaları, Site Kaynak Limitleri, uyarı katmanı
#   * Ek modüller (panel/kur-modul.sh)
#       PHP Ayarları, Uygulama Güvenlik Duvarı, Erişim İzleme, WordPress
#       Araçları, Klon/Staging, Uygulama Kurucu, Git Dağıtım, Node.js,
#       Yedek Gezgini, Bayi Yönetimi
#
# Güncelleme güvenliği: eklenen dosyaların tamamı YENİdir; HestiaCP
# güncellemesi bunları silmez. Stok dosyalara yalnızca birer satırlık iki yama
# uygulanır ve günlük bir cron bunları yeniden uygular.
# =============================================================================
set -u

renk() { printf '\033[%sm%s\033[0m\n' "$1" "$2"; }
basla() { renk '1;36' "$1"; }
ok() { printf '  \033[0;32m[OK]\033[0m   %s\n' "$1"; }
hata() { printf '  \033[0;31m[HATA]\033[0m %s\n' "$1" >&2; }

SRC="$(cd "$(dirname "$0")" && pwd)"
MODE="${1:-}"

# --- Ön koşullar ---
if [ "$(id -u)" != "0" ]; then
	hata "Bu betik root olarak çalıştırılmalı:  sudo bash kur.sh"
	exit 1
fi

H=/usr/local/hestia
if [ ! -d "$H/web" ]; then
	hata "HestiaCP bulunamadı ($H/web yok)."
	hata "Bu paket HestiaCP kurulu bir sunucu içindir."
	exit 1
fi

# Sürüm hestia.conf içinde VERSION='1.10.4' biçiminde durur. ($H/version diye
# bir dosya YOKTUR; oradan okumak her kurulumda "?" verirdi.) Bulunamazsa
# paket sürümüne düşülür.
SURUM=$(grep -m1 "^VERSION=" "$H/conf/hestia.conf" 2>/dev/null | cut -d"'" -f2)
[ -z "$SURUM" ] && SURUM=$(dpkg-query -W -f='${Version}' hestia 2>/dev/null | cut -d- -f1)
[ -z "$SURUM" ] && SURUM="?"

# Sürüm karşılaştırması SAYISAL yapılır. Glob ile ("1.1*") yapılsaydı hem
# 1.10 hem de çok eski 1.1.x eşleşirdi; ikisi aynı şey değil.
if [ "$SURUM" = "?" ]; then
	renk '0;33' "  [UYARI] HestiaCP sürümü belirlenemedi. Devam ediliyor."
else
	_maj=${SURUM%%.*}
	_kalan=${SURUM#*.}
	_min=${_kalan%%.*}
	case "$_maj$_min" in
		*[!0-9]*) renk '0;33' "  [UYARI] HestiaCP $SURUM okunamadı. Devam ediliyor." ;;
		*)
			if [ "$_maj" -gt 1 ] || { [ "$_maj" -eq 1 ] && [ "$_min" -ge 9 ]; }; then
				:
			else
				renk '0;33' "  [UYARI] HestiaCP $SURUM ile denenmedi (1.9+ bekleniyor). Devam ediliyor."
			fi
			;;
	esac
fi

for k in python3 curl; do
	command -v "$k" > /dev/null 2>&1 || {
		hata "$k bulunamadı; kurulum için gerekli."
		exit 1
	}
done

# --- Kaldır ---
if [ "$MODE" = "--kaldir" ]; then
	basla "WebDanışmanı teması ve modülleri kaldırılıyor..."
	[ -f "$SRC/panel/kur.sh" ] && bash "$SRC/panel/kur.sh" --kaldir
	[ -f "$SRC/tema/kur.sh" ] && bash "$SRC/tema/kur.sh" --kaldir
	renk '1;32' ""
	renk '1;32' "Kaldırıldı. Panelde CTRL+F5 yapın."
	exit 0
fi

# --- Durum ---
if [ "$MODE" = "--durum" ]; then
	bash "$SRC/panel/kur.sh" --durum
	exit 0
fi

# --- Kur ---
basla "WebDanışmanı HestiaCP teması + ek modüller kuruluyor..."
echo "  HestiaCP $SURUM  ·  $(hostname -f 2>/dev/null || hostname)"
echo

renk '1;36' "[1/2] Tema"
bash "$SRC/tema/kur.sh" || {
	hata "Tema kurulumu başarısız."
	exit 1
}

echo
renk '1;36' "[2/2] Panel modülleri"
bash "$SRC/panel/kur.sh" || {
	hata "Modül kurulumu başarısız."
	exit 1
}

echo
renk '1;32' "Kurulum tamamlandı."
echo "  Panel   : https://$(hostname -f 2>/dev/null || hostname):8083"
echo "  Tarayıcıda CTRL+F5 ile sayfayı yenileyin."
echo
echo "  Site kaynak limitlerini uygulamak isterseniz (isteğe bağlı):"
echo "    $H/wd/bin/wd-kaynak durum     # ne değişecek, göster"
echo "    $H/wd/bin/wd-kaynak uygula    # paketlere göre uygula"
echo
renk '0;36' "  Tema ve modüller: https://webdanismani.com"
renk '0;36' "  Destek ve forum : https://oblifex.com"
