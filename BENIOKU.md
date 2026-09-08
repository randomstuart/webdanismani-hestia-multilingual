# WebDanışmanı — HestiaCP Teması ve Ek Modüller

HestiaCP için modern bir arayüz teması ve panelde karşılığı olmayan **yirmi ek
sayfa**: sağlık denetimi, güvenlik taraması, disk kırılımı, WordPress araçları,
klon/staging, WAF, Node.js, Git dağıtım, bayi yönetimi ve dahası.

Ücretsizdir ve MIT lisansıyla dağıtılır; dilediğiniz sunucuda kullanabilir,
değiştirebilir, yeniden dağıtabilirsiniz.

**Tema ve modüller:** <https://webdanismani.com>
**Destek, hata bildirimi ve özellik istekleri:** <https://oblifex.com>

> Arayüz dili **Türkçe**dir. Etiketler HestiaCP'nin dil sistemine değil,
> doğrudan şablonlara yazılmıştır; çeviri katkıları memnuniyetle kabul edilir.

---

## Kurulum

`webdanismani-hestia.tar.gz` dosyasını sunucuya yükleyin (SFTP, `scp` ya da
panelin dosya yöneticisiyle), sonra **root** olarak:

```bash
cd /root
tar xzf webdanismani-hestia.tar.gz
cd webdanismani-hestia
bash kur.sh
```

Kurulum bitince panelde **CTRL+F5** yapın. Giriş sonrası açılış sayfası
**Araçlar** (`/list/tools/`) olur; tüm modüller oradan erişilir.

GitHub'dan klonlayarak da kurabilirsiniz:

```bash
cd /root
git clone <depo-adresi> webdanismani-hestia
cd webdanismani-hestia
bash kur.sh
```

> **Dosya bütünlüğü.** Arşiv olarak indirdiyseniz
> `sha256sum webdanismani-hestia.tar.gz` çıktısını, dosyayı aldığınız yerde
> yayınlanan özetle karşılaştırın. Tutmuyorsa kurmayın.

Kurulum **geri alınabilir**: `bash kur.sh --kaldir` her şeyi eski hâline
döndürür (bkz. [Kaldırma](#kaldırma)).

### Gereksinimler

| | |
|---|---|
| HestiaCP | 1.9 veya üstü (1.10.4 üzerinde geliştirildi ve sınandı) |
| İşletim sistemi | Ubuntu / Debian (HestiaCP'nin desteklediği sürümler) |
| Web sunucu | nginx (+ isteğe bağlı Apache) — HestiaCP'nin varsayılanı |
| Gerekli araçlar | `python3`, `curl` — HestiaCP ile birlikte zaten gelir |
| Yetki | root |

İsteğe bağlı: **Node.js** modülü için sunucuda `node` kurulu olmalı (değilse
sayfa uyarı gösterir). **WordPress Araçları** için `wp-cli` gerekir; kurulum
sırasında yoksa GitHub'dan indirilir.

Yazı tipleri (Inter, JetBrains Mono) kurulum sırasında bir kez indirilir ve
**sunucuda barındırılır**; panel çalışırken dışarıya istek gitmez.

---

## Ne geliyor?

### Tema
Koyu yeşil sol menü, Inter + JetBrains Mono tipografi, kenardan kenara içerik
(stok HestiaCP içeriği 1024 px'e sıkıştırır), yeniden düzenlenmiş liste ve form
sayfaları, üst çubukta arama (`/` kısayolu) ve hızlı kurulum. **Dosya
yöneticisi de** aynı temaya alınır; panelin sol menüsü ile kalıcı bir klasör
ağacı kazanır.

### Panel sayfaları

| Sayfa | Adres | Ne yapar |
|---|---|---|
| Araçlar | `/list/tools/` | Hesabın tüm yönetim araçlarını tek sayfada toplayan giriş noktası |
| Sağlık Merkezi | `/list/health/` | PTR, MX, SPF, DKIM, DMARC, A kaydı, SSL bitişi, kara liste (RBL), mail kuyruğu; her bulgu için ne yapılacağı |
| Güvenlik *(yönetici)* | `/list/guvenlik/` | SSH, 2FA, API, fail2ban, bekleyen güvenlik güncellemeleri, açık portlar, dünyaya yazılabilir dosyalar; **davranış tabanlı zararlı kod taraması**; korumalı "şimdi düzelt" |
| Disk Kullanımı | `/list/disk/` | Kategori dağılımı, alan adı başına kullanım, posta kutuları, en büyük 20 dosya ve 15 dizin |
| Mail Raporu | `/list/mailrapor/` | Gönderen başına ileti hacmi ve sekme oranı; ele geçirilmiş hesabı kara listeye düşmeden yakalar |
| Kaynak Geçmişi | `/list/gecmis/` | Site başına CPU/RAM'in 30 günlük seyri, saatlik tepe değer |
| Cloudflare | `/list/cloudflare/` | Gerçek ziyaretçi IP'si (nginx + Apache), önbellek temizleme, geliştirme modu, DNS gönderme, IP aralığı tazeleme |
| Yönlendirmeler | `/list/yonlendirme/` | Yol yönlendirmeleri, güvenlik başlıkları, hotlink koruması, IP engelleme; nginx doğrulanır, geçersizse geri alınır |
| Dizin Şifre Koruma | `/list/httpauth/` | Siteyi kullanıcı adı + parola ile korur (Hestia'da CLI var, arayüz yok) |
| Özel Hata Sayfaları | `/list/errorpages/` | 403 / 404 / 410 / 5xx sayfalarını düzenler; anında yayına girer, varsayılana dönülebilir |

### Ek modüller (cPanel'de olup HestiaCP'de olmayanlar)

| Sayfa | Adres | cPanel karşılığı | Ne yapar |
|---|---|---|---|
| PHP Ayarları | `/list/phpayar/` | MultiPHP INI Editor | Alan adı başına `.user.ini`: yükleme boyutu, zaman aşımı, hata gösterimi, PHP hata günlüğü |
| Uygulama Güvenlik Duvarı | `/list/waf/` | ModSecurity | Kötü bot ve saldırı kalıplarını engeller, giriş sayfasını kaba kuvvete karşı hız sınırıyla korur |
| Erişim İzleme | `/list/erisim/` | — | Siteler 5 dakikada bir dışarıdan denetlenir; 2 ardışık hata → bildirim; 30 gün geçmiş |
| WordPress Araçları | `/list/wp/` | WP Toolkit | Çekirdek/eklenti/tema güncellemeleri, otomatik güncelleme, bütünlük doğrulama, tek tık yönetici girişi |
| Klon / Staging | `/list/klon/` | WP Toolkit staging | Sitenin test kopyasını çıkar (rsync + DB + search-replace), değişiklikleri yayına al; yayın öncesi yedek |
| Uygulama Kurucu *(yönetici)* | `/list/kur/` | Softaculous | Kendi kataloğunuzdaki PHP uygulamalarını seçilen alan adına kurar (bkz. `panel/MODULLER.md`) |
| Git Dağıtım | `/list/git/` | Git Version Control | Depoyu siteye klonla, hesap başına deploy key, tek tıkla ya da otomatik çek |
| Node.js Uygulamaları | `/list/node/` | Application Manager | Node.js uygulamasını systemd servisi olarak çalıştır, alan adına bağla |
| Yedek Gezgini | `/list/yedek/` | JetBackup dosya geri yükleme | Yedeğin içinde gez, tek dosya ya da klasörü kopya al / yerine koy |
| Bayi Yönetimi | `/list/bayi/` | WHM reseller | Yönetici bayi tanımlar; bayi kendi müşterilerini açar, askıya alır, paketini değiştirir |

### Site Kaynak Limitleri
Stok php-fpm şablonunda `memory_limit` ve `request_terminate_timeout` **yoktur**.
php.ini'deki değer bir varsayılandır: müşteri kodu `ini_set` ile aşabilir. Bu
modül paket başına şablon üretir ve sınırı `php_admin_value` ile **zorunlu**
kılar; takılan istekleri de kesin olarak sonlandırır.

```bash
/usr/local/hestia/wd/bin/wd-kaynak durum      # ne değişecek
/usr/local/hestia/wd/bin/wd-kaynak uygula     # paketlere göre ata
/usr/local/hestia/wd/bin/wd-kaynak geri-al    # stok şablona döndür
```

Kademeler `/usr/local/hestia/wd/kaynak.json` içinden düzenlenebilir.
Şablonlar kurulumda **üretilir ama otomatik ATANMAZ** — çalışan bir sitenin
havuzunu habersiz değiştirmek doğru olmaz.

### Uyarı katmanı
Sağlık, disk, yedek, güvenlik, tarama ve erişim bulguları HestiaCP'nin kendi
bildirim sistemine (üst çubuktaki zil) düşer; isteğe bağlı e-posta gönderilir.
Aynı uyarı 24 saatte bir tekrarlanır, sorun kaybolunca "düzeldi" bildirimi
gider. Eşikler `/usr/local/hestia/wd/uyari.json` içinden ayarlanır.

---

## Sunucunuzda ne değişir?

Başkasının sunucusuna kurulan bir yazılımın bunu açıkça yazması gerekir.

| Ne | Nereye |
|---|---|
| Yeni dosyalar: tema, 20 sayfa, 21 kök betiği | `/usr/local/hestia/web/…`, `/usr/local/hestia/wd/…` |
| Üç küçük yama (aşağıda) | `web/templates/includes/panel.php`, `web/fm/configuration.php`, `web/index.php` |
| sudo kuralları (yalnızca kök betiklerine) | `/etc/sudoers.d/wd-panel`, `/etc/sudoers.d/wd-modul` |
| cron kayıtları (önbellek tazeleme, onarım) | `/etc/cron.d/wd-panel`, `/etc/cron.d/wd-modul` |
| WAF giriş hız sınırı bölgesi | `/etc/nginx/conf.d/wd-waf.conf` |
| Node.js nginx şablonu (stoktan türetilir) | `data/templates/web/nginx/…/wd-node.tpl` |
| Kaynak limiti php-fpm şablonları | `data/templates/web/php-fpm/wd-*.tpl` |
| Günlük döndürme (php yavaş istek günlüğü) | `/etc/logrotate.d/wd-php-slowlog` |
| wp-cli (yoksa indirilir) | `/usr/local/bin/wp` |

**Yamalar:** `panel.php`'ye iki satır `require` (sol menü ve üst çubuk),
dosya yöneticisinin `configuration.php`'sine bir satır `<link>`, `index.php`'de
açılış hedefi `list/user` → `list/tools`. Her biri yazılmadan önce `php -l` ile
denetlenir; orijinaller `.wd-orig` uzantısıyla yanına yedeklenir.

---

## Güncelleme güvenliği

HestiaCP güncellemesi **mevcut** dosyaları geri alır, **yeni** dosyaları silmez.
Bu paket buna göre tasarlandı:

- Eklenen sayfa, şablon, CSS ve toplayıcıların tamamı yeni dosyadır.
- Stok dosyalara yalnızca yukarıdaki küçük yamalar uygulanır.
- `/etc/cron.d/wd-panel` her gün 05:30'da, `/etc/cron.d/wd-modul` 05:40'ta
  `--onar` çalıştırarak yamaları yeniden uygular. Güncelleme sonrası elle bir
  şey yapmanız gerekmez.

Durum kontrolü:

```bash
bash /usr/local/hestia/wd/src/kur.sh --durum
bash /usr/local/hestia/wd/src/kur-modul.sh --durum
```

---

## Kaldırma

```bash
cd /root/webdanismani-hestia
bash kur.sh --kaldir
```

Yamaları geri alır, eklenen dosyaları siler, temayı varsayılana döndürür ve
kaynak limiti atanmış siteleri stok php-fpm şablonuna geri taşır. Kullanıcı
ayar dosyaları (`wd/bayi.json`, `wd/uygulamalar/` kataloğu) korunur.

---

## Arka planda çalışanlar

| Ne | Ne zaman | Niçin |
|---|---|---|
| `wd-health refresh` | 15 dakikada bir | DNS sorguları yavaştır; sayfa önbellekten okur |
| `wd-kaynak onbellek` | 15 dakikada bir | Alan adı → kademe eşlemesi |
| `wd-disk refresh` | 6 saatte bir | `du`/`find` büyük hesaplarda pahalıdır |
| `wd-uyari calistir` | 20 dakikada bir | Bulguları panel bildirimine taşır |
| `wd-cloudflare ip-guncelle` | günde bir | Eskiyen IP listesi gerçek ziyaretçi IP'sini bozar |
| `wd-mailrapor refresh` | 4 saatte bir | exim günlüğü taraması |
| `wd-guvenlik refresh` | günde iki kez | Sertleştirme denetimi |
| `wd-tarama refresh 2` | gecelik | Son 2 günde değişen dosyalarda zararlı kod |
| `wd-gecmis ornekle` | 5 dakikada bir | Kaynak geçmişi örneği |
| `wd-erisim kontrol` | 5 dakikada bir | Dış HTTP erişim denetimi |
| `wd-wp tara` | gecelik | WordPress güncelleme taraması |
| `wd-git otomatik-cek` | 5 dakikada bir | İşaretli depoları çeker |
| `kur.sh --onar` / `kur-modul.sh --onar` | her gün 05:30 / 05:40 | Güncelleme yamayı sildiyse yeniden uygular |

Panel sayfaları bu toplayıcıları **çağırmaz**, yalnızca önbellek dosyalarını
okur. Sayfa açılışı yavaşlamaz.

---

## Güvenlik notları

- **Panele verilen root yetkisi.** Panel PHP'si `hestiaweb` kullanıcısı olarak
  çalışır ve `/proc`'u, sertifikaları, exim günlüklerini, müşteri dosyalarını
  okuyamaz. Bu yüzden `/etc/sudoers.d/wd-panel` ve `wd-modul` ile **yalnızca
  `/usr/local/hestia/wd/bin/` altındaki betiklere** parolasız çalıştırma izni
  verilir. Kabuk erişimi verilmez, başka hiçbir komut eklenmez. Dosyalar
  `visudo -c` ile doğrulanmadan yerine konmaz.
- Panelden **eylem** alan betikler (`wd-guvenlik`, `wd-yonlendirme`,
  `wd-cloudflare` ve ek modüller) `sudo` tarafından kabuk olmadan, argümanlar
  dizi hâlinde çalıştırılır; yazma verisi JSON olarak STDIN'den geçer. Kabuk
  satırına asla ham kullanıcı verisi konmaz.
- Betiklerin hiçbiri `shell=True` veya `os.system` kullanmaz.
- Yazma yapan sayfalar kullanıcı adını **daima oturumdan** alır, istekten değil.
  Alan adı sahipliği ayrıca doğrulanır ve tüm POST istekleri HestiaCP'nin kendi
  CSRF denetiminden geçer.
- nginx'e dokunan her işlem `nginx -t` ile doğrulanır; geçersizse eski hâl geri
  yüklenir. SSH sertleştirme `sshd -t` ile doğrulanır ve `reload` kullanır.
- Zararlı kod taraması **hiçbir dosyayı silmez veya karantinaya almaz**;
  yalnızca bildirir.

---

## Bilinen sınırlar

- Arayüz yalnızca Türkçedir.
- Dosya yöneticisinde "Tür" sütunu yoktur; tür bilgisi ad hücresindeki renkli
  ikonla verilir (FileGator tablosuna dışarıdan sütun eklemek sıralamayı bozar).
- Kaynak ölçümü yalnızca PHP işçilerini sayar; nginx, Apache ve MariaDB
  paylaşımlıdır ve site başına güvenilir biçimde ayrıştırılamaz.
- Uygulama Kurucu boş bir katalogla gelir; paketleri siz yüklersiniz.
- Kurumsal destek taahhüdü yoktur.

---

## Geliştirme

- Tema CSS'i `tema/_govde.css` içinde düzenlenir ve `tema/derle.ps1` ile
  `tema/webdanismani.css` üretilir (Windows PowerShell). `webdanismani.css`
  doğrudan düzenlenirse bir sonraki derlemede kaybolur.
- Yeni modül deseni ve katalog biçimi: `panel/MODULLER.md`.
- PHP dosyaları kurulum sırasında sunucuda `php -l`, Python betikleri
  `ast.parse` ile denetlenir; hatalı dosya kurulmaz.

## Lisans

MIT — bkz. `LICENSE`. Hata bildirimi ve özellik istekleri için:
<https://oblifex.com>
