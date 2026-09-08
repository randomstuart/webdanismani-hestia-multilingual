<?php
/**
 * WebDanışmanı — "Yedek Gezgini" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/yedek/index.php
 *
 * Yedeğin içinde gezdirir, tek dosya/klasör geri yükler. Yönetici "look"
 * ile müşteriye bakarken o müşterinin yedeklerini görür.
 */

ob_start();
$TAB = "YEDEKGEZGIN";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-modul.php";

$wd_user = wd_modul_kullanici();
$wd_is_admin = wd_modul_admin();
$wd_doms = wd_web_domains($wd_user);

$wd_hata = "";
$wd_bilgi = "";

$wd_yedek = (string) ($_POST["v_yedek"] ?? ($_GET["yedek"] ?? ""));
$wd_domain = (string) ($_POST["v_domain"] ?? ($_GET["domain"] ?? ""));
$wd_yol = trim((string) ($_POST["v_yol"] ?? ($_GET["yol"] ?? "")), "/");
if (!preg_match('/^[a-z0-9._-]{1,32}\.\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.tar$/i', $wd_yedek)) {
	$wd_yedek = "";
}
if ($wd_domain !== "" && !isset($wd_doms[$wd_domain])) {
	$wd_domain = "";
}
if ($wd_yol !== "" && (strpos($wd_yol, "..") !== false || !preg_match('#^[^\x00]{1,600}$#', $wd_yol))) {
	$wd_yol = "";
}

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	$islem = (string) ($_POST["islem"] ?? "");
	if ($islem === "geri" && $wd_yedek !== "" && $wd_domain !== "" && $wd_yol !== "") {
		$args = ["geri-yukle", $wd_user, $wd_yedek, $wd_domain, $wd_yol];
		if (!empty($_POST["v_yerine"])) {
			$args[] = "--yerine";
		}
		$d = wd_modul_json("wd-yedek", $args, null, 3600);
		if (!empty($d["ok"])) {
			$ust = dirname($wd_yol);
			header("Location: /list/yedek/?yedek=" . urlencode($wd_yedek) . "&domain=" . urlencode($wd_domain) . "&yol=" . urlencode($ust === "." ? "" : $ust)
				. "&durum=" . (!empty($d["yerine"]) ? "yerine" : "kopya") . "&d=" . urlencode((string) ($d["yol"] ?? "")));
			exit();
		}
		$wd_hata = "Geri yüklenemedi: " . ($d["hata"] ?? "");
	} else {
		$wd_hata = "Eksik seçim.";
	}
}

if ($wd_hata === "") {
	$wd_bilgi = wd_modul_durum_mesaji([
		"kopya" => "Geri yüklendi (canlı dosyalara dokunulmadı): {d}. Dosya Yöneticisi ile inceleyip istediğinizi taşıyın.",
		"yerine" => "Canlı dosyanın yerine yazıldı: {d}. Önceki hâli private/wd-geri altında saklandı.",
	]);
}

$l = wd_modul_json("wd-yedek", ["liste", $wd_user], null, 120);
$wd_yedekler = !empty($l["ok"]) ? (array) ($l["yedekler"] ?? []) : [];
$wd_uzak = $l["uzak"] ?? null;
$wd_mod = (string) ($l["mod"] ?? "");
if ($wd_yedek === "" && !empty($wd_yedekler)) {
	$wd_yedek = (string) $wd_yedekler[0]["ad"];
}
$wd_secili = null;
foreach ($wd_yedekler as $y) {
	if ($y["ad"] === $wd_yedek) {
		$wd_secili = $y;
	}
}
if ($wd_domain === "" && $wd_secili && !empty($wd_secili["web"])) {
	foreach ($wd_secili["web"] as $w) {
		if (isset($wd_doms[$w])) {
			$wd_domain = $w;
			break;
		}
	}
}
$wd_icerik = null;
if ($wd_secili && $wd_domain !== "" && !empty($wd_secili["dosya_var"])) {
	$wd_icerik = wd_modul_json("wd-yedek", ["icerik", $wd_user, $wd_yedek, $wd_domain, $wd_yol], null, 1800);
	if (empty($wd_icerik["ok"])) {
		$wd_hata = $wd_hata !== "" ? $wd_hata : ("İçerik okunamadı: " . ($wd_icerik["hata"] ?? ""));
		$wd_icerik = null;
	}
}

render_page($user, $TAB, "list_yedek");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
