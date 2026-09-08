# WebDanışmanı HestiaCP — Ek Modüller

cPanel'de olup HestiaCP'de olmayan işlevleri kapatan 10 modül. Hepsi **yeni
dosyadır**; HestiaCP güncellemeleri silmez. Kurulum `bash panel/kur.sh` ile
(sonunda `kur-modul.sh`'ı çağırır) ya da yalnız modüller için
`bash panel/kur-modul.sh`.

| Modül | Sayfa | cPanel karşılığı | Root betiği | Not |
|---|---|---|---|---|
| PHP Ayarları | `/list/phpayar/` | MultiPHP INI Editor | `wd-phpayar` | `.user.ini`; tavanlar wd-kaynak kademesinden; PHP hata günlüğü |
| Uygulama Güvenlik Duvarı | `/list/waf/` | ModSecurity | `wd-waf` | nginx `nginx.conf_wdwaf`; giriş hız sınırı için `/etc/nginx/conf.d/wd-waf.conf` |
| Erişim İzleme | `/list/erisim/` | — | `wd-erisim` | 5 dk'da bir dış HTTP kontrolü, 2 ardışık hata → bildirim, 30 gün geçmiş |
| WordPress Araçları | `/list/wp/` | WP Toolkit | `wd-wp` | wp-cli (kur-modul.sh indirir); tek tıklık giriş mu-plugin ile |
| Klon / Staging | `/list/klon/` | WP Toolkit staging | `wd-klon` | rsync + DB kopya + search-replace; yayına alma öncesi yedek |
| Uygulama Kurucu (yalnız yönetici) | `/list/kur/` | Softaculous | `wd-kur` | katalog `/usr/local/hestia/wd/uygulamalar/<KOD>/` (aşağıda) |
| Git Dağıtım | `/list/git/` | Git Version Control | `wd-git` | hesap başına deploy key; otomatik çekme cron |
| Node.js Uygulamaları | `/list/node/` | Application Manager | `wd-node` | systemd birimi + `wd-node` nginx şablonu (stok şablondan türetilir) |
| Yedek Gezgini | `/list/yedek/` | JetBackup dosya geri yükleme | `wd-yedek` | tar yedeğinin içinde gezer; kopya al / yerine koy |
| Bayi Yönetimi | `/list/bayi/` | WHM reseller | `wd-bayi` | yönetici bayi tanımlar (`wd/bayi.json`); bayi kendi müşterilerini yönetir |

## Ortak desen

- Panel (`hestiaweb`) kullanıcı dosyalarını okuyamaz → her modülün **root
  betiği** vardır (`/usr/local/hestia/wd/bin/wd-*`), `/etc/sudoers.d/wd-modul`
  yalnız bunlara izin verir.
- Yazma işlemleri JSON olarak **STDIN**'den geçer; betik girdiyi süzer, nginx'e
  dokunan her işlem `nginx -t` ile doğrulanır ve geçersizse geri alınır.
- Ortak yardımcılar `web/inc/wd-modul.php` (`wd_modul_json`,
  `wd_modul_onbellek`, `wd_modul_listesi` kayıt dizisi). Araçlar sayfası ve
  kenar çubuğu bu diziden beslenir.
- Modül CSS'i ayrı: `web/css/themes/custom/wd-modul.css` (tema derlemesine
  dokunmaz).
- Zamanlanmış işler `/etc/cron.d/wd-modul` (erişim 5 dk, WP gecelik, git 5 dk,
  onarım 05:40).
- Sunucu vhost'unun sonundaki `include conf/web/<domain>/nginx.conf_*` tüm
  modüllerin uzantı noktasıdır (yönlendirme, WAF, staging noindex). `location /`
  değişimi (Node.js) ise ancak şablonla yapılır; `wd-node sablon` stok
  default.tpl/.stpl'den türetir.

## Yeni modül eklemek

1. `panel/bin/wd-<ad>` — root betiği (Python 3, JSON stdin/stdout, `shell=True` yok).
2. `panel/list-<ad>/index.php` — `$TAB` tanımla, `inc/main.php` + `inc/wd-modul.php` yükle, `render_page($user, $TAB, "list_<ad>")`.
3. `panel/templates/list_<ad>.php` — şablon; başta `wd_modul_css()`.
4. `inc/wd-modul.php` içindeki `wd_modul_listesi()` dizisine bir satır.
5. `kur-modul.sh` içinde `MODULLER` ve `BETIKLER` listelerine ekle.

Kullanıcı adı **daima oturumdan** (`wd_modul_kullanici()`), alan adı
`wd_modul_domain_sec()` ile doğrulanır; ham istek değeri doğrulanmadan hiçbir
yan etki üretilmez.

## Uygulama Kurucu kataloğu

Katalog boş gelir. Yönetici panelden (Uygulama Kurucu → Kataloğa paket ekle)
ya da komut satırından paket ekler:

```
/usr/local/hestia/wd/uygulamalar/<KOD>/manifest.json
/usr/local/hestia/wd/uygulamalar/<KOD>/paket.zip
```

`manifest.json` alanları:

| Alan | Anlamı |
|---|---|
| `kod` | Büyük harf kısa kod (`BLOG`, `CRM`) |
| `ad`, `surum`, `aciklama` | Listede gösterilir |
| `php_min` | En düşük PHP sürümü (`"8.1"`) |
| `db` | `true` ise kurulumda veritabanı + kullanıcı açılır |
| `kabuk` | `true` ise kurulumda lisans anahtarı zorunludur; `license.key` olarak yazılır |
| `kurulum_yolu` | Kurulum sonrası açılacak yol (`"/install.php"`) |
| `zip_kok` | Zip içindeki uygulama kökü (boş = zip kökü) |
| `config` | İsteğe bağlı `{"dosya": "config/config.php", "sablon": "..."}` |

Şablon yer tutucuları: `{{DB_AD}} {{DB_KULLANICI}} {{DB_SIFRE}} {{DB_SUNUCU}}
{{SITE_URL}} {{ALAN_ADI}} {{LISANS}}`.

Komut satırı:

```bash
wd-kur liste                                   # katalog (JSON)
wd-kur katalog-ekle KOD /yol/paket.zip < manifest.json
wd-kur katalog-sil KOD
wd-kur kur USER DOMAIN KOD [LISANS] [ALTDIZIN]
wd-kur durum USER DOMAIN                       # alan adında kurulu uygulamalar
```

Kurulum adımları: hedef dizin boş mu (Hestia iskelet dosyaları sayılmaz) →
zip güvenli açılır (zip-slip korumalı), sahiplik site kullanıcısına verilir →
gerekirse `v-add-database` → yapılandırma şablonu ve `license.key` yazılır →
`.wd-kur.json` işareti bırakılır.

## Dağıtım

- Sunucuya kopyalayın, `bash kur.sh`. Durum: `bash panel/kur-modul.sh --durum`.
  Kaldırma: `--kaldir` (ayar dosyaları ve katalog korunur).
- PHP lint sunucuda: kur-modul.sh `php -l` çalıştırır; hatalı dosya varsa
  raporlar.
