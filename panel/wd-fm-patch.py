#!/usr/bin/env python3
"""
WebDanışmanı — Dosya Yöneticisi teması yaması

Kurulum yeri: /usr/local/hestia/wd/wd-fm-patch.py

NE YAPAR?
  Dosya yöneticisi (FileGator) sayfasına TEK bir satır ekler:

      <link rel="stylesheet" href="/fm/css/wd-fm.css">

  Satır GÖVDEYE (`add_to_body`) eklenir, başlığa değil. Sebebi ölçülerek
  bulundu: FileGator `add_to_head` içeriğini uygulamanın kendi stillerinden
  ÖNCE basıyor —

      hst-custom.css  →  wd-fm.css  →  app.css  →  chunk-vendors.css

  yani başlığa konan kurallar Bulma tarafından eziliyor. HestiaCP kendi
  hst-custom.css dosyasında bu yüzden neredeyse her satıra `!important`
  koymuş. Gövdeye konan bağlantı ise belge sırasında app.css'ten SONRA gelir
  ve eşit özgüllükte kazanır; böylece yüzlerce `!important` gerekmez.
  (hst-custom.css'in `!important` kuralları için wd-fm.css yine `!important`
  kullanır — orada kaynak sırası yetmez.)

NEDEN YAMA GEREKİYOR?
  wd-fm.css YENİ bir dosyadır ve HestiaCP güncellemesinde silinmez. Ancak onu
  yükleyen `fm/configuration.php` STOK bir dosyadır ve güncellemede geri
  alınır. Bu yüzden değişiklik tek satırla sınırlı tutulur ve günlük cron
  (`kur.sh --onar`) yamayı yeniden uygular.

GÜVENLİK
  Yazmadan önce geçici dosyada `php -l` çalıştırılır; sözdizimi bozuksa
  DEĞİŞİKLİK YAPILMAZ. Bozuk bir configuration.php dosya yöneticisini
  tamamen çalışmaz hâle getirirdi.

Kullanım:
  wd-fm-patch.py apply    yamayı uygula (zaten varsa dokunmaz)
  wd-fm-patch.py revert   orijinaline döndür
  wd-fm-patch.py status   durum raporu
"""

import os
import subprocess
import sys
import tempfile

HEDEF = "/usr/local/hestia/web/fm/configuration.php"
YEDEK = HEDEF + ".wd-orig"

# Yamanın tutunduğu çapa: gövdeye eklenen bloğun başı. Bulunamazsa
# (HestiaCP bu bölümü değiştirmişse) tahmin YAPILMAZ, yama atlanır.
#
# Enjekte edilen metin PHP'de TEK TIRNAKLI bir dizgenin içine girer; bu
# yüzden içinde tek tırnak KULLANILMAZ, yalnızca çift tırnak vardır.
CAPA = '"add_to_body" => \'\n<script>'
STIL = '<link rel="stylesheet" href="/fm/css/wd-fm.css">'
BETIK = '<script src="/fm/js/wd-fm.js"></script>'
EKLENEN = STIL + "\n" + BETIK


def php_bin():
    for b in ("/usr/local/hestia/php/bin/php", "php", "php8.3", "php8.2"):
        try:
            subprocess.run([b, "-v"], capture_output=True, check=True)
            return b
        except Exception:
            continue
    return None


def lint(icerik):
    """Sozdizimi denetimi. PHP yoksa None doner (denetlenemedi)."""
    b = php_bin()
    if b is None:
        return None
    fd, yol = tempfile.mkstemp(suffix=".php")
    try:
        with os.fdopen(fd, "w", encoding="utf-8") as fh:
            fh.write(icerik)
        r = subprocess.run([b, "-l", yol], capture_output=True, text=True)
        return (r.returncode == 0, (r.stdout + r.stderr).strip())
    finally:
        os.unlink(yol)


def oku():
    with open(HEDEF, encoding="utf-8") as fh:
        return fh.read()


def temizle(icerik):
    """
    Bilinen TÜM enjeksiyon parçalarını söker.

    Ayrı bir işlev olmasının sebebi: eklentinin daha eski bir sürümü yalnızca
    CSS satırını koymuş olabilir. Yalnızca birleşik metni arayan bir geri alma
    o satırı bırakır, sonraki `apply` çapayı bulamaz ve yama sessizce hiç
    uygulanmaz. (Bu bir kez yaşandı.)
    """
    for parca in ("\n\t" + EKLENEN, EKLENEN + "\n", EKLENEN,
                  "\n\t" + BETIK, BETIK + "\n", BETIK,
                  "\n\t" + STIL, STIL + "\n", STIL):
        icerik = icerik.replace(parca, "")
    return icerik


def apply():
    if not os.path.isfile(HEDEF):
        print("  [HATA] dosya yoneticisi yapilandirmasi yok: %s" % HEDEF)
        return 1

    ham = oku()

    if STIL in ham and BETIK in ham:
        print("  [ATLA] dosya yoneticisi temasi zaten bagli")
        return 2

    # Eski/kısmi enjeksiyonu önce sök ki çapa yeniden ortaya çıksın.
    icerik = temizle(ham)

    if CAPA not in icerik:
        # Cikarim yapmak yerine durulur: yanlis yere eklenen bir satir
        # dosya yoneticisini bozabilir.
        print("  [ATLA] beklenen capa bulunamadi - configuration.php degismis")
        return 2

    yeni = icerik.replace(
        CAPA, '"add_to_body" => \'\n' + EKLENEN + "\n<script>", 1
    )

    sonuc = lint(yeni)
    if sonuc is not None and not sonuc[0]:
        print("  [HATA] yama sonrasi sozdizimi bozuk - UYGULANMADI")
        print("         %s" % sonuc[1])
        return 1

    if not os.path.exists(YEDEK):
        with open(YEDEK, "w", encoding="utf-8") as fh:
            fh.write(ham)

    gecici = HEDEF + ".wd-tmp"
    with open(gecici, "w", encoding="utf-8") as fh:
        fh.write(yeni)
    os.chmod(gecici, os.stat(HEDEF).st_mode & 0o777)
    os.replace(gecici, HEDEF)
    print("  [OK]   dosya yoneticisi temasi baglandi")
    return 0


def revert():
    if not os.path.isfile(HEDEF):
        return 1
    icerik = oku()
    if STIL not in icerik and BETIK not in icerik:
        print("  [ATLA] yama zaten yok")
        return 2
    yeni = temizle(icerik)
    sonuc = lint(yeni)
    if sonuc is not None and not sonuc[0]:
        print("  [HATA] geri alma sonrasi sozdizimi bozuk - DOKUNULMADI")
        return 1
    gecici = HEDEF + ".wd-tmp"
    with open(gecici, "w", encoding="utf-8") as fh:
        fh.write(yeni)
    os.chmod(gecici, os.stat(HEDEF).st_mode & 0o777)
    os.replace(gecici, HEDEF)
    print("  [OK]   dosya yoneticisi temasi kaldirildi")
    return 0


def status():
    if not os.path.isfile(HEDEF):
        print("  dosya yoneticisi   : YOK")
        return 1
    icerik = oku()
    print("  fm tema yamasi     : %s" % (
        "VAR" if (STIL in icerik and BETIK in icerik)
        else ("EKSIK" if (STIL in icerik or BETIK in icerik) else "YOK")))
    print("  fm tema dosyasi    : %s" % (
        "VAR" if os.path.isfile("/usr/local/hestia/web/fm/dist/css/wd-fm.css") else "EKSIK"))
    print("  fm kabuk betigi    : %s" % (
        "VAR" if os.path.isfile("/usr/local/hestia/web/fm/dist/js/wd-fm.js") else "EKSIK"))
    print("  orijinal yedek     : %s" % ("var" if os.path.exists(YEDEK) else "yok"))
    return 0


def main():
    mod = sys.argv[1] if len(sys.argv) > 1 else "status"
    if mod == "apply":
        return apply()
    if mod == "revert":
        return revert()
    if mod == "status":
        return status()
    sys.stderr.write(__doc__)
    return 2


if __name__ == "__main__":
    sys.exit(main())
