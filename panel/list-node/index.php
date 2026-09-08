<?php
/**
 * WebDanışmanı — "Node.js Uygulamaları" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/node/index.php
 */

ob_start();
$TAB = "NODE";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-modul.php";

$wd_user = wd_modul_kullanici();
$wd_is_admin = wd_modul_admin();
$wd_doms = wd_web_domains($wd_user);

$wd_hata = "";
$wd_bilgi = "";
$wd_gunluk = null;

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	$islem = (string) ($_POST["islem"] ?? "");
	$dom = strtolower(trim((string) ($_POST["v_domain"] ?? "")));
	if (!isset($wd_doms[$dom])) {
		$wd_hata = "Geçersiz alan adı.";
	} elseif ($islem === "ekle") {
		$secenek = [
			"giris" => trim((string) ($_POST["v_giris"] ?? "app.js")),
			"alt_dizin" => trim((string) ($_POST["v_alt"] ?? "")),
			"npm" => !empty($_POST["v_npm"]),
		];
		$d = wd_modul_json("wd-node", ["ekle", $wd_user, $dom], json_encode($secenek), 1200);
		if (!empty($d["ok"])) {
			header("Location: /list/node/?durum=eklendi&d=" . urlencode($dom . " · port " . (int) ($d["port"] ?? 0) . " · " . (string) ($d["durum"] ?? "")));
			exit();
		}
		$wd_hata = "Uygulama eklenemedi: " . ($d["hata"] ?? "");
	} elseif ($islem === "kaldir" || $islem === "yeniden") {
		$d = wd_modul_json("wd-node", [$islem, $wd_user, $dom], null, 200);
		if (!empty($d["ok"])) {
			header("Location: /list/node/?durum=" . ($islem === "kaldir" ? "kaldirildi" : "yeniden") . "&d=" . urlencode($dom));
			exit();
		}
		$wd_hata = "İşlem yapılamadı: " . ($d["hata"] ?? "");
	} elseif ($islem === "gunluk") {
		$wd_gunluk = wd_modul_json("wd-node", ["gunluk", $wd_user, $dom, "120"], null, 60);
		$wd_gunluk["domain"] = $dom;
	}
}

if ($wd_hata === "") {
	$wd_bilgi = wd_modul_durum_mesaji([
		"eklendi" => "Uygulama başlatıldı: {d}",
		"kaldirildi" => "{d} için Node.js uygulaması kaldırıldı; alan adı stok PHP şablonuna döndü.",
		"yeniden" => "{d} yeniden başlatıldı.",
	]);
}

$l = wd_modul_json("wd-node", ["liste", $wd_user], null, 60);
$wd_uygulamalar = !empty($l["ok"]) ? (array) ($l["uygulamalar"] ?? []) : [];
$wd_node = (string) ($l["node"] ?? "");
$wd_sablon = !empty($l["sablon"]);
$wd_kullanilan = array_map(fn($a) => $a["domain"], $wd_uygulamalar);

render_page($user, $TAB, "list_node");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
