<?php
/**
 * WebDanışmanı — "PHP Ayarları" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/phpayar/index.php
 *
 * cPanel "MultiPHP INI Editor" karşılığı. Değerler belge kökündeki
 * .user.ini dosyasına yazılır; root betiği wd-phpayar tavanları uygular.
 */

ob_start();
$TAB = "PHPAYAR";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-modul.php";

$wd_user = wd_modul_kullanici();
$wd_is_admin = wd_modul_admin();
$wd_doms = wd_web_domains($wd_user);

$wd_hata = "";
$wd_bilgi = "";
$wd_notlar = [];

$wd_ham_domain = isset($_POST["v_domain"]) ? (string) $_POST["v_domain"] : (string) ($_GET["domain"] ?? "");
$wd_domain = wd_modul_domain_sec($wd_doms, $wd_ham_domain);

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	if ($wd_ham_domain === "" || !isset($wd_doms[$wd_ham_domain])) {
		$wd_hata = "Geçersiz alan adı.";
	} else {
		$wd_domain = $wd_ham_domain;
		$islem = (string) ($_POST["islem"] ?? "kaydet");
		if ($islem === "sil") {
			$d = wd_modul_json("wd-phpayar", ["sil", $wd_user, $wd_domain]);
			if (!empty($d["ok"])) {
				header("Location: /list/phpayar/?domain=" . urlencode($wd_domain) . "&durum=silindi");
				exit();
			}
			$wd_hata = "Kaldırılamadı: " . ($d["hata"] ?? "");
		} elseif ($islem === "gunluk-temizle") {
			$d = wd_modul_json("wd-phpayar", ["gunluk-temizle", $wd_user, $wd_domain]);
			header("Location: /list/phpayar/?domain=" . urlencode($wd_domain) . "&durum=gunluk-temiz");
			exit();
		} else {
			$veri = [
				"upload_max_filesize" => (string) ($_POST["v_upload"] ?? "8M"),
				"post_max_size" => (string) ($_POST["v_post"] ?? "10M"),
				"memory_limit" => (string) ($_POST["v_memory"] ?? "128M"),
				"max_execution_time" => (string) ($_POST["v_exec"] ?? "30"),
				"max_input_time" => (string) ($_POST["v_input"] ?? "60"),
				"max_input_vars" => (string) ($_POST["v_vars"] ?? "1000"),
				"session.gc_maxlifetime" => (string) ($_POST["v_session"] ?? "1440"),
				"display_errors" => !empty($_POST["v_display"]) ? "On" : "Off",
				"log_errors" => !empty($_POST["v_log"]) ? "On" : "Off",
				"short_open_tag" => !empty($_POST["v_short"]) ? "On" : "Off",
				"date.timezone" => (string) ($_POST["v_tz"] ?? "Europe/Istanbul"),
			];
			$d = wd_modul_json("wd-phpayar", ["yaz", $wd_user, $wd_domain], json_encode($veri));
			if (!empty($d["ok"])) {
				$n = !empty($d["notlar"]) ? "&n=" . urlencode(implode(" · ", (array) $d["notlar"])) : "";
				header("Location: /list/phpayar/?domain=" . urlencode($wd_domain) . "&durum=kaydedildi" . $n);
				exit();
			}
			$wd_hata = "Kaydedilemedi: " . ($d["hata"] ?? "");
		}
	}
}

if ($wd_hata === "") {
	$wd_bilgi = wd_modul_durum_mesaji([
		"kaydedildi" => "PHP ayarları kaydedildi. PHP en geç 5 dakika içinde yeni değerleri kullanır.",
		"silindi" => "Özel ayarlar kaldırıldı; sunucu varsayılanları geçerli.",
		"gunluk-temiz" => "Hata günlüğü temizlendi.",
	]);
	if (!empty($_GET["n"])) {
		$wd_notlar = explode(" · ", (string) $_GET["n"]);
	}
}

$wd_ayar = null;
$wd_gunluk = [];
if ($wd_domain !== "") {
	$wd_ayar = wd_modul_json("wd-phpayar", ["oku", $wd_user, $wd_domain]);
	if (empty($wd_ayar["ok"])) {
		$wd_hata = $wd_hata !== "" ? $wd_hata : ("Ayarlar okunamadı: " . ($wd_ayar["hata"] ?? ""));
		$wd_ayar = null;
	} else {
		$g = wd_modul_json("wd-phpayar", ["gunluk", $wd_user, $wd_domain, "80"]);
		$wd_gunluk = !empty($g["ok"]) ? (array) ($g["satirlar"] ?? []) : [];
	}
}

render_page($user, $TAB, "list_phpayar");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
