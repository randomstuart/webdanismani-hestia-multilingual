<?php
/**
 * WebDanışmanı — "Klon / Staging" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/klon/index.php
 */

ob_start();
$TAB = "KLON";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-modul.php";

$wd_user = wd_modul_kullanici();
$wd_is_admin = wd_modul_admin();
$wd_doms = wd_web_domains($wd_user);

$wd_hata = "";
$wd_bilgi = "";
$wd_sonuc = null;

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	$islem = (string) ($_POST["islem"] ?? "");
	if ($islem === "olustur") {
		$kaynak = strtolower(trim((string) ($_POST["v_kaynak"] ?? "")));
		$alt = strtolower(trim((string) ($_POST["v_alt"] ?? "staging")));
		$hedef = strtolower(trim((string) ($_POST["v_hedef"] ?? "")));
		if ($hedef === "" && $kaynak !== "") {
			$hedef = ($alt !== "" ? $alt : "staging") . "." . $kaynak;
		}
		if (!isset($wd_doms[$kaynak])) {
			$wd_hata = "Kaynak alan adı geçersiz.";
		} elseif (!preg_match('/^[a-z0-9.-]{3,253}$/', $hedef) || $hedef === $kaynak) {
			$wd_hata = "Hedef alan adı geçersiz.";
		} else {
			$db = (string) ($_POST["v_db"] ?? "auto");
			$d = wd_modul_json("wd-klon", ["olustur", $wd_user, $kaynak, $hedef], json_encode(["db" => $db]), 1800);
			if (!empty($d["ok"])) {
				$_SESSION["wd_klon_sonuc"] = $d;
				header("Location: /list/klon/?durum=olusturuldu&d=" . urlencode($hedef));
				exit();
			}
			$wd_hata = "Klon oluşturulamadı: " . ($d["hata"] ?? "");
		}
	} elseif ($islem === "yayinla" || $islem === "sil") {
		$hedef = strtolower(trim((string) ($_POST["v_hedef"] ?? "")));
		if (!preg_match('/^[a-z0-9.-]{3,253}$/', $hedef)) {
			$wd_hata = "Geçersiz alan adı.";
		} else {
			$d = wd_modul_json("wd-klon", [$islem, $wd_user, $hedef], null, 1800);
			if (!empty($d["ok"])) {
				header("Location: /list/klon/?durum=" . ($islem === "sil" ? "silindi" : "yayinlandi") . "&d=" . urlencode($islem === "sil" ? $hedef : (string) ($d["kaynak"] ?? "")));
				exit();
			}
			$wd_hata = ($islem === "sil" ? "Silinemedi: " : "Yayına alınamadı: ") . ($d["hata"] ?? "");
		}
	}
}

if ($wd_hata === "") {
	$wd_bilgi = wd_modul_durum_mesaji([
		"olusturuldu" => "Staging hazır: {d}",
		"yayinlandi" => "Staging değişiklikleri {d} canlı sitesine aktarıldı. Önceki hâl private/ altına yedeklendi.",
		"silindi" => "{d} kaldırıldı.",
	]);
	if (!empty($_SESSION["wd_klon_sonuc"])) {
		$wd_sonuc = $_SESSION["wd_klon_sonuc"];
		unset($_SESSION["wd_klon_sonuc"]);
	}
}

$l = wd_modul_json("wd-klon", ["liste", $wd_user]);
$wd_klonlar = !empty($l["ok"]) ? (array) ($l["klonlar"] ?? []) : [];
$wd_klon_hedefler = array_map(fn($k) => $k["hedef"], $wd_klonlar);
$wd_dbler = [];
$out = [];
exec(HESTIA_CMD . "v-list-databases " . escapeshellarg($wd_user) . " json", $out);
$tmp = json_decode(implode("", $out), true);
if (is_array($tmp)) {
	$wd_dbler = array_keys($tmp);
}

render_page($user, $TAB, "list_klon");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
