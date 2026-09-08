<?php
/**
 * WebDanışmanı — "Erişim İzleme" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/erisim/index.php
 *
 * Siteler cron ile 5 dakikada bir dışarıdan denetlenir; sayfa ÖNBELLEKTEN
 * okur. "Şimdi Dene" düğmesi tek site için anlık kontrol yapar.
 */

ob_start();
$TAB = "ERISIM";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-modul.php";

$wd_user = wd_modul_kullanici();
$wd_is_admin = wd_modul_admin();
$wd_doms = wd_web_domains($wd_user);

$wd_hata = "";
$wd_bilgi = "";

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	$dom = (string) ($_POST["v_domain"] ?? "");
	// Yönetici her siteyi, müşteri yalnız kendi sitesini deneyebilir.
	$sahip = $wd_is_admin || isset($wd_doms[$dom]);
	if (!$sahip || !preg_match('/^[a-z0-9.-]{3,253}$/i', $dom)) {
		$wd_hata = "Geçersiz alan adı.";
	} else {
		$d = wd_modul_json("wd-erisim", ["simdi", $dom], null, 60);
		if (!empty($d["ok"])) {
			header("Location: /list/erisim/?durum=denendi&d=" . urlencode($dom));
			exit();
		}
		$wd_hata = "Kontrol yapılamadı: " . ($d["hata"] ?? "");
	}
}

if ($wd_hata === "") {
	$wd_bilgi = wd_modul_durum_mesaji([
		"denendi" => "{d} şimdi denendi; sonuç aşağıda güncellendi.",
	]);
}

$wd_veri = wd_modul_onbellek("erisim");
$wd_siteler = [];
if ($wd_veri && !empty($wd_veri["siteler"])) {
	foreach ($wd_veri["siteler"] as $s) {
		if (!$wd_is_admin && ($s["user"] ?? "") !== $wd_user) {
			continue;
		}
		$wd_siteler[] = $s;
	}
}
$wd_yas = wd_modul_onbellek_yas("erisim");

render_page($user, $TAB, "list_erisim");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
