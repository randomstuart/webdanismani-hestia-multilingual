<?php
/**
 * WebDanışmanı — "Uygulama Kurucu" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/kur/index.php
 *
 * Katalogdaki uygulama paketlerini seçilen alan adına kurar.
 * Katalog yönetimi (zip yükleme) yalnız yönetici. Yükleme PHP'nin geçici
 * dosyasıyla gelir; root betiği dosyayı kataloğa taşır.
 */

ob_start();
$TAB = "KUR";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-modul.php";

$wd_user = wd_modul_kullanici();
$wd_is_admin = wd_modul_admin();

// YALNIZ YÖNETİCİ: kataloğa paket yüklemek ve kurmak yönetici işidir.
// Yönetici "look" ile bir müşteriye bakarken de kullanamaz; kendi hesabından kurar.
if (!$wd_is_admin) {
	header("Location: /list/tools/");
	exit();
}
$wd_doms = wd_web_domains($wd_user);

$wd_hata = "";
$wd_bilgi = "";
$wd_sonuc = null;

$wd_ham_domain = isset($_POST["v_domain"]) ? (string) $_POST["v_domain"] : (string) ($_GET["domain"] ?? "");
$wd_domain = wd_modul_domain_sec($wd_doms, $wd_ham_domain);

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	$islem = (string) ($_POST["islem"] ?? "");

	if ($islem === "katalog-ekle" && $wd_is_admin) {
		$kod = strtoupper(trim((string) ($_POST["v_kod"] ?? "")));
		$zip = "-";
		if (!empty($_FILES["v_zip"]["tmp_name"]) && is_uploaded_file($_FILES["v_zip"]["tmp_name"])) {
			// Geçici dosya hestiaweb'e ait; root betiği okuyup taşır. Panelin
			// php-fpm'i PrivateTmp kullanabildiği için /tmp DEĞİL, ortak dizin.
			$dizin = "/usr/local/hestia/wd/yukleme";
			if (!is_dir($dizin) || !is_writable($dizin)) {
				$dizin = sys_get_temp_dir();
			}
			$zip = tempnam($dizin, "wdkur-");
			if ($zip === false || !move_uploaded_file($_FILES["v_zip"]["tmp_name"], $zip)) {
				$wd_hata = "Yüklenen dosya geçici alana alınamadı.";
				$zip = "-";
			} else {
				chmod($zip, 0644);
			}
		} elseif (!empty($_FILES["v_zip"]["error"]) && (int) $_FILES["v_zip"]["error"] !== UPLOAD_ERR_NO_FILE) {
			$wd_hata = "ZIP yüklenemedi (hata kodu " . (int) $_FILES["v_zip"]["error"] . "). Sunucunun upload_max_filesize sınırını kontrol edin.";
		}
		if ($wd_hata === "") {
			$manifest = [
				"ad" => (string) ($_POST["v_ad"] ?? ""),
				"surum" => (string) ($_POST["v_surum"] ?? "1.0"),
				"aciklama" => (string) ($_POST["v_aciklama"] ?? ""),
				"php_min" => (string) ($_POST["v_php_min"] ?? "8.1"),
				"db" => !empty($_POST["v_db"]),
				"kabuk" => !empty($_POST["v_kabuk"]),
				"kurulum_yolu" => (string) ($_POST["v_kurulum_yolu"] ?? "/install.php"),
				"zip_kok" => (string) ($_POST["v_zip_kok"] ?? ""),
				"ikon" => (string) ($_POST["v_ikon"] ?? "fa-cube"),
			];
			$cfg_dosya = trim((string) ($_POST["v_cfg_dosya"] ?? ""));
			$cfg_sablon = (string) ($_POST["v_cfg_sablon"] ?? "");
			if ($cfg_dosya !== "" && trim($cfg_sablon) !== "") {
				$manifest["config"] = ["dosya" => $cfg_dosya, "sablon" => $cfg_sablon];
			}
			$d = wd_modul_json("wd-kur", ["katalog-ekle", $kod, $zip], json_encode($manifest, JSON_UNESCAPED_UNICODE), 300);
			if ($zip !== "-" && is_file($zip)) {
				@unlink($zip);
			}
			if (!empty($d["ok"])) {
				header("Location: /list/kur/?durum=katalog-ok&d=" . urlencode($kod));
				exit();
			}
			$wd_hata = "Kataloğa eklenemedi: " . ($d["hata"] ?? "");
		}
	} elseif ($islem === "katalog-sil" && $wd_is_admin) {
		$kod = strtoupper(trim((string) ($_POST["v_kod"] ?? "")));
		$d = wd_modul_json("wd-kur", ["katalog-sil", $kod]);
		header("Location: /list/kur/?durum=" . (!empty($d["ok"]) ? "katalog-silindi" : "hata") . "&d=" . urlencode($kod));
		exit();
	} elseif ($islem === "kur") {
		if ($wd_ham_domain === "" || !isset($wd_doms[$wd_ham_domain])) {
			$wd_hata = "Geçersiz alan adı.";
		} else {
			$wd_domain = $wd_ham_domain;
			$kod = strtoupper(trim((string) ($_POST["v_kod"] ?? "")));
			$secenek = [
				"alt_dizin" => trim((string) ($_POST["v_alt"] ?? "")),
				"lisans" => trim((string) ($_POST["v_lisans"] ?? "")),
				"db" => true,
			];
			$d = wd_modul_json("wd-kur", ["kur", $wd_user, $wd_domain, $kod, "--stdin"], json_encode($secenek), 300);
			if (!empty($d["ok"])) {
				// Veritabanı şifresi bir kez gösterilir; yönlendirmede taşınmaz, oturumda kısa süre tutulur.
				$_SESSION["wd_kur_sonuc"] = $d;
				header("Location: /list/kur/?domain=" . urlencode($wd_domain) . "&durum=kuruldu");
				exit();
			}
			$wd_hata = "Kurulum yapılamadı: " . ($d["hata"] ?? "");
		}
	}
}

if ($wd_hata === "") {
	$wd_bilgi = wd_modul_durum_mesaji([
		"katalog-ok" => "{d} kataloğa eklendi.",
		"katalog-silindi" => "{d} katalogdan kaldırıldı.",
		"hata" => "İşlem yapılamadı.",
	]);
	if (($_GET["durum"] ?? "") === "kuruldu" && !empty($_SESSION["wd_kur_sonuc"])) {
		$wd_sonuc = $_SESSION["wd_kur_sonuc"];
		unset($_SESSION["wd_kur_sonuc"]);
	}
}

$k = wd_modul_json("wd-kur", ["liste"]);
$wd_katalog = !empty($k["ok"]) ? (array) ($k["uygulamalar"] ?? []) : [];
$wd_kurulu = [];
$wd_bos = true;
if ($wd_domain !== "") {
	$s = wd_modul_json("wd-kur", ["durum", $wd_user, $wd_domain]);
	if (!empty($s["ok"])) {
		$wd_kurulu = (array) ($s["kurulu"] ?? []);
		$wd_bos = !empty($s["bos"]);
	}
}
$wd_quick = ($_SESSION["PLUGIN_APP_INSTALLER"] ?? "") === "true";

render_page($user, $TAB, "list_kur");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
