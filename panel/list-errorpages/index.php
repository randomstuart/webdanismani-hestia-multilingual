<?php
/**
 * WebDanışmanı — "Özel Hata Sayfaları" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/errorpages/index.php
 *
 * YENİ dosyadır; HestiaCP güncellemelerinden etkilenmez.
 *
 * NE İŞE YARAR?
 * Ziyaretçi olmayan bir adrese girdiğinde ya da sitede hata oluştuğunda
 * görünen sayfayı düzenler. cPanel'deki "Error Pages" karşılığıdır.
 *
 * BU GERÇEKTEN ÇALIŞIYOR MU?
 * Evet, ölçüldü. /etc/nginx/nginx.conf genelinde:
 *     error_page 403 /error/403.html;  404 -> /error/404.html;
 *     410 -> /error/410.html;  500..505 -> /error/50x.html
 * ve her alan adının kendi yapılandırmasında:
 *     location /error/ { alias <home>/web/<domain>/document_errors/; }
 * Yani buradaki dosyayı değiştirmek canlı hata sayfasını değiştirir.
 *
 * YAZMA NASIL YAPILIYOR?
 * Panel `hestiaweb` olarak çalışır ve site sahibinin dosyalarına yazamaz.
 * İçerik önce /tmp altında geçici bir dosyaya yazılır, sonra
 * `v-copy-fs-file USER KAYNAK HEDEF` ile kopyalanır. Bu komut:
 *   - hedefi `readlink -f` ile çözer ve kullanıcının ev dizini (ya da /tmp)
 *     dışına yazmayı reddeder — yol kaçışı burada kapanır,
 *   - kopyalamayı SİTE SAHİBİ olarak çalıştırır, yani dosya sahipliği doğru
 *     kalır ve işletim sistemi izinleri de ayrıca uygulanır.
 * Test edildi: /etc/ altına yazma denemesi "invalid destination path" ile
 * reddediliyor.
 *
 * EK DOĞRULAMALAR (savunma tek katmana bırakılmaz)
 *   - Kullanıcı adı DAİMA oturumdan alınır, istekten değil.
 *   - Alan adı, kullanıcının kendi alan adları arasında olmalıdır.
 *   - Dosya adı sabit bir listeden seçilir; istekten gelen ad kullanılmaz.
 *   - İçerik boyutu sınırlıdır.
 */

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
$TAB = "WEB";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-helpers.php";

$wd_user = empty($_SESSION["look"]) ? $_SESSION["user"] : $_SESSION["look"];

/* Düzenlenebilecek dosyalar SABİTTİR. İstekten gelen bir dosya adı asla
   yola konmaz; yalnızca bu listedeki anahtar kabul edilir. */
$WD_SAYFALAR = [
	"404.html" => ["kod" => "404", "ad" => "Sayfa Bulunamadı", "aciklama" => "İstenen adres yok"],
	"403.html" => ["kod" => "403", "ad" => "Erişim Reddedildi", "aciklama" => "Dosyaya erişim izni yok"],
	"410.html" => ["kod" => "410", "ad" => "Kaldırıldı", "aciklama" => "İçerik kalıcı olarak silindi"],
	"50x.html" => ["kod" => "5xx", "ad" => "Sunucu Hatası", "aciklama" => "500, 502, 503, 504"],
];

const WD_AZAMI_BOYUT = 262144; // 256 KB

$wd_doms = wd_web_domains($wd_user);
$wd_hata = "";
$wd_bilgi = "";

/** Kullanıcının ev dizini (HestiaCP'den; /home varsayımı yapılmaz). */
function wd_ev_dizini(string $user): ?string {
	$out = [];
	$rc = 0;
	exec(HESTIA_CMD . "v-list-user " . quoteshellarg($user) . " json", $out, $rc);
	if ($rc !== 0) {
		return null;
	}
	$d = json_decode(implode("", $out), true);
	$home = $d[$user]["HOME"] ?? null;
	return is_string($home) && $home !== "" ? rtrim($home, "/") : null;
}

$wd_home = wd_ev_dizini($wd_user);

/** Hata sayfasının tam yolu. Alan adı ve dosya adı ÖNCEDEN doğrulanmış olmalı. */
function wd_hata_yolu(string $home, string $domain, string $dosya): string {
	return $home . "/web/" . $domain . "/document_errors/" . $dosya;
}

/* --- Seçili alan adı ve dosya ---
 *
 * DİKKAT: Ham (doğrulanmamış) değerler AYRI tutulur.
 *
 * Görüntüleme için "geçersizse ilkine düş" davranışı kullanışlıdır, ama bu
 * geri düşüş doğrulamadan önce çalışırsa doğrulamayı ETKİSİZ kılar: geçersiz
 * bir alan adı gönderen istek, sessizce kullanıcının ilk alan adına yazardı;
 * geçersiz bir dosya adı da 404.html'e yazardı. Bu bir kez yaşandı ve testte
 * yakalandı. Bu yüzden POST yolunda HAM değer doğrulanır, geri düşüş yalnızca
 * GET (görüntüleme) için uygulanır.
 */
$wd_ham_domain = isset($_POST["v_domain"])
	? (string) $_POST["v_domain"]
	: (string) ($_GET["domain"] ?? "");
$wd_ham_dosya = isset($_POST["v_file"])
	? (string) $_POST["v_file"]
	: (string) ($_GET["f"] ?? "404.html");

$wd_domain = $wd_ham_domain;
if ($wd_domain === "" || !isset($wd_doms[$wd_domain])) {
	$wd_domain = !empty($wd_doms) ? (string) array_key_first($wd_doms) : "";
}

$wd_dosya = $wd_ham_dosya;
if (!isset($WD_SAYFALAR[$wd_dosya])) {
	$wd_dosya = "404.html";
}

// -----------------------------------------------------------------------------
// POST
// -----------------------------------------------------------------------------
if (!empty($_POST["ok"]) && $wd_home !== null) {
	verify_csrf($_POST);

	$islem = $_POST["islem"] ?? "kaydet";

	// HAM değerler doğrulanır — geri düşülmüş değerler DEĞİL.
	if ($wd_ham_domain === "" || !isset($wd_doms[$wd_ham_domain])) {
		$wd_hata = "Geçersiz alan adı: " . wd_e($wd_ham_domain);
	} elseif (!isset($WD_SAYFALAR[$wd_ham_dosya])) {
		$wd_hata = "Geçersiz sayfa: " . wd_e($wd_ham_dosya);
	} else {
		// Buradan sonra ikisi de doğrulanmıştır.
		$wd_domain = $wd_ham_domain;
		$wd_dosya = $wd_ham_dosya;
		if ($islem === "varsayilan") {
			$kaynak = "/usr/local/hestia/wd/skel/document_errors/" . $wd_dosya;
			if (!is_readable($kaynak)) {
				$wd_hata = "Varsayılan şablon bulunamadı: " . wd_e($wd_dosya);
				$icerik = null;
			} else {
				$icerik = (string) file_get_contents($kaynak);
			}
		} else {
			$icerik = (string) ($_POST["v_content"] ?? "");
		}

		if ($wd_hata === "" && $icerik !== null) {
			if (strlen($icerik) > WD_AZAMI_BOYUT) {
				$wd_hata =
					"İçerik çok büyük (" .
					number_format(strlen($icerik) / 1024, 0, ",", ".") .
					" KB). En fazla " .
					WD_AZAMI_BOYUT / 1024 .
					" KB olabilir.";
			} else {
				// v-copy-fs-file kaynağın /tmp ya da ev dizini altında olmasını
				// şart koşar; bu yüzden geçici dosya bilerek /tmp'e yazılır.
				$gecici = tempnam("/tmp", "wd-err-");
				if ($gecici === false) {
					$wd_hata = "Geçici dosya oluşturulamadı.";
				} else {
					file_put_contents($gecici, $icerik);
					// Kopyalama SİTE SAHİBİ olarak çalışır; kaynağı okuyabilmesi
					// için dosya okunur olmalı.
					chmod($gecici, 0644);

					$hedef = wd_hata_yolu($wd_home, $wd_domain, $wd_dosya);
					$out = [];
					$rc = 0;
					exec(
						HESTIA_CMD .
							"v-copy-fs-file " .
							quoteshellarg($wd_user) .
							" " .
							quoteshellarg($gecici) .
							" " .
							quoteshellarg($hedef),
						$out,
						$rc,
					);
					unlink($gecici);

					if ($rc !== 0) {
						$wd_hata = trim(implode(" ", $out));
						if ($wd_hata === "") {
							$wd_hata = "Kaydedilemedi (kod " . (int) $rc . ").";
						}
					} else {
						header(
							"Location: /list/errorpages/?domain=" .
								urlencode($wd_domain) .
								"&f=" .
								urlencode($wd_dosya) .
								"&durum=" .
								($islem === "varsayilan" ? "sifirlandi" : "kaydedildi"),
						);
						exit();
					}
				}
			}
		}
	}
}

if ($wd_hata === "" && isset($_GET["durum"])) {
	$wd_bilgi =
		$_GET["durum"] === "sifirlandi"
			? "Sayfa varsayılan içeriğe döndürüldü."
			: "Sayfa kaydedildi. Değişiklik hemen yayında.";
}

// --- Mevcut içerik ---
$wd_icerik = "";
$wd_okunamadi = false;
if ($wd_domain !== "" && $wd_home !== null) {
	$yol = wd_hata_yolu($wd_home, $wd_domain, $wd_dosya);
	if (is_readable($yol)) {
		$wd_icerik = (string) file_get_contents($yol);
	} else {
		$wd_okunamadi = true;
	}
}

if ($wd_home === null) {
	$wd_hata = "Kullanıcı ev dizini okunamadı.";
}

render_page($user, $TAB, "list_errorpages");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
