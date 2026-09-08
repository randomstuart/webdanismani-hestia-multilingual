<?php
/**
 * WebDanışmanı — "Uygulama Güvenlik Duvarı" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/waf/index.php
 */

ob_start();
$TAB = "WAF";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-modul.php";

$wd_user = wd_modul_kullanici();
$wd_is_admin = wd_modul_admin();
$wd_doms = wd_web_domains($wd_user);

$wd_hata = "";
$wd_bilgi = "";

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
			$d = wd_modul_json("wd-waf", ["sil", $wd_user, $wd_domain]);
			if (!empty($d["ok"])) {
				header("Location: /list/waf/?domain=" . urlencode($wd_domain) . "&durum=silindi");
				exit();
			}
			$wd_hata = "Kaldırılamadı: " . ($d["hata"] ?? "");
		} else {
			$ipler = array_values(array_filter(array_map("trim", preg_split('/[\s,]+/', (string) ($_POST["v_izinli_ip"] ?? "")) ?: []), "strlen"));
			$kurallar = [
				"etkin" => !empty($_POST["v_etkin"]),
				"bot_engel" => !empty($_POST["v_bot"]),
				"arac_engel" => !empty($_POST["v_arac"]),
				"sorgu_engel" => !empty($_POST["v_sorgu"]),
				"yukleme_php_engel" => !empty($_POST["v_yukleme"]),
				"xmlrpc_kapat" => !empty($_POST["v_xmlrpc"]),
				"giris_koruma" => !empty($_POST["v_giris"]),
				"hassas_dosya_engel" => !empty($_POST["v_hassas"]),
				"izinli_ip" => $ipler,
			];
			$d = wd_modul_json("wd-waf", ["yaz", $wd_user, $wd_domain], json_encode($kurallar));
			if (!empty($d["ok"])) {
				header("Location: /list/waf/?domain=" . urlencode($wd_domain) . "&durum=" . (!empty($d["etkin"]) ? "kaydedildi" : "kapatildi"));
				exit();
			}
			$wd_hata = "Kaydedilemedi: " . ($d["hata"] ?? "");
		}
	}
}

if ($wd_hata === "") {
	$wd_bilgi = wd_modul_durum_mesaji([
		"kaydedildi" => "Güvenlik duvarı kuralları kaydedildi ve yayına alındı.",
		"kapatildi" => "Güvenlik duvarı bu alan adı için kapatıldı (kurallar saklandı).",
		"silindi" => "Tüm WAF kuralları kaldırıldı.",
	]);
}

$wd_waf = null;
$wd_ist = null;
if ($wd_domain !== "") {
	$wd_waf = wd_modul_json("wd-waf", ["oku", $wd_user, $wd_domain]);
	if (empty($wd_waf["ok"])) {
		$wd_hata = $wd_hata !== "" ? $wd_hata : ("Kurallar okunamadı: " . ($wd_waf["hata"] ?? ""));
		$wd_waf = null;
	} else {
		$wd_ist = wd_modul_json("wd-waf", ["istatistik", $wd_user, $wd_domain], null, 60);
	}
}

render_page($user, $TAB, "list_waf");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
