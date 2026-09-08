<?php
/**
 * WebDanışmanı — "Bayi Yönetimi" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/bayi/index.php
 *
 * İKİ GÖRÜNÜM:
 *   Yönetici : bayileri tanımlar (paket kısıtı, müşteri sınırı, müşteri atama)
 *   Bayi     : kendi müşterilerini açar/askıya alır/paket ve şifre değiştirir
 *
 * Yönetici komutları ("ayar", "bayi-*") YALNIZCA yönetici bağlamında
 * çağrılır; sudo bunu ayıramadığı için denetim burada zorunludur.
 */

ob_start();
$TAB = "BAYI";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-modul.php";

$wd_user = wd_modul_kullanici();
$wd_is_admin = wd_modul_admin();
$wd_bayi_mi = !$wd_is_admin && wd_modul_bayi_mi($wd_user);

if (!$wd_is_admin && !$wd_bayi_mi) {
	header("Location: /list/tools/");
	exit();
}

$wd_hata = "";
$wd_bilgi = "";
$u_desen = '/^[a-z0-9._-]{1,32}$/i';

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	$islem = (string) ($_POST["islem"] ?? "");

	if ($wd_is_admin && in_array($islem, ["bayi-ekle", "bayi-sil", "bayi-ata", "bayi-cikar"], true)) {
		$bayi = trim((string) ($_POST["v_bayi"] ?? ""));
		$hedef = trim((string) ($_POST["v_kullanici"] ?? ""));
		if (!preg_match($u_desen, $bayi)) {
			$wd_hata = "Geçersiz bayi.";
		} elseif ($islem === "bayi-ekle") {
			$veri = [
				"ad" => (string) ($_POST["v_ad"] ?? ""),
				"paketler" => array_values(array_filter((array) ($_POST["v_paketler"] ?? []), "is_string")),
				"azami" => (int) ($_POST["v_azami"] ?? 10),
			];
			$d = wd_modul_json("wd-bayi", ["bayi-ekle", $bayi], json_encode($veri, JSON_UNESCAPED_UNICODE));
			if (!empty($d["ok"])) {
				header("Location: /list/bayi/?durum=bayi-ok&d=" . urlencode($bayi));
				exit();
			}
			$wd_hata = $d["hata"] ?? "Kaydedilemedi.";
		} elseif ($islem === "bayi-sil") {
			$d = wd_modul_json("wd-bayi", ["bayi-sil", $bayi]);
			header("Location: /list/bayi/?durum=" . (!empty($d["ok"]) ? "bayi-silindi" : "hata") . "&d=" . urlencode($bayi));
			exit();
		} elseif (preg_match($u_desen, $hedef)) {
			$d = wd_modul_json("wd-bayi", [$islem, $bayi, $hedef]);
			if (!empty($d["ok"])) {
				header("Location: /list/bayi/?durum=" . ($islem === "bayi-ata" ? "atandi" : "cikarildi") . "&d=" . urlencode($hedef));
				exit();
			}
			$wd_hata = $d["hata"] ?? "İşlem yapılamadı.";
		} else {
			$wd_hata = "Geçersiz kullanıcı.";
		}
	} elseif ($wd_bayi_mi) {
		$hedef = trim((string) ($_POST["v_kullanici"] ?? ""));
		if ($islem === "ekle") {
			$veri = [
				"kullanici" => (string) ($_POST["v_yeni_kullanici"] ?? ""),
				"sifre" => (string) ($_POST["v_sifre"] ?? ""),
				"eposta" => (string) ($_POST["v_eposta"] ?? ""),
				"ad" => (string) ($_POST["v_ad"] ?? ""),
				"paket" => (string) ($_POST["v_paket"] ?? ""),
			];
			$d = wd_modul_json("wd-bayi", ["ekle", $wd_user], json_encode($veri, JSON_UNESCAPED_UNICODE), 300);
			if (!empty($d["ok"])) {
				header("Location: /list/bayi/?durum=eklendi&d=" . urlencode((string) ($d["kullanici"] ?? "")));
				exit();
			}
			$wd_hata = $d["hata"] ?? "Hesap açılamadı.";
		} elseif (!preg_match($u_desen, $hedef)) {
			$wd_hata = "Geçersiz kullanıcı.";
		} elseif (in_array($islem, ["askiya", "askidan-cikar", "sil"], true)) {
			$d = wd_modul_json("wd-bayi", [$islem, $wd_user, $hedef], null, 600);
			if (!empty($d["ok"])) {
				header("Location: /list/bayi/?durum=" . $islem . "&d=" . urlencode($hedef));
				exit();
			}
			$wd_hata = $d["hata"] ?? "İşlem yapılamadı.";
		} elseif ($islem === "sifre") {
			$d = wd_modul_json("wd-bayi", ["sifre", $wd_user, $hedef], json_encode(["sifre" => (string) ($_POST["v_sifre"] ?? "")]));
			if (!empty($d["ok"])) {
				header("Location: /list/bayi/?durum=sifre&d=" . urlencode($hedef));
				exit();
			}
			$wd_hata = $d["hata"] ?? "Şifre değiştirilemedi.";
		} elseif ($islem === "paket") {
			$d = wd_modul_json("wd-bayi", ["paket", $wd_user, $hedef, (string) ($_POST["v_paket"] ?? "")]);
			if (!empty($d["ok"])) {
				header("Location: /list/bayi/?durum=paket&d=" . urlencode($hedef));
				exit();
			}
			$wd_hata = $d["hata"] ?? "Paket değiştirilemedi.";
		}
	}
}

if ($wd_hata === "") {
	$wd_bilgi = wd_modul_durum_mesaji([
		"bayi-ok" => "Bayi kaydedildi: {d}",
		"bayi-silindi" => "Bayi tanımı kaldırıldı: {d} (müşteri hesapları duruyor).",
		"atandi" => "{d} bayiye bağlandı.",
		"cikarildi" => "{d} bayiden ayrıldı.",
		"eklendi" => "Müşteri hesabı açıldı: {d}",
		"askiya" => "{d} askıya alındı.",
		"askidan-cikar" => "{d} yeniden etkin.",
		"sil" => "{d} silindi.",
		"sifre" => "{d} şifresi değiştirildi.",
		"paket" => "{d} paketi değiştirildi.",
		"hata" => "İşlem yapılamadı.",
	]);
}

$wd_ayar = $wd_is_admin ? wd_modul_json("wd-bayi", ["ayar"], null, 120) : null;
$wd_liste = $wd_bayi_mi ? wd_modul_json("wd-bayi", ["liste", $wd_user], null, 120) : null;
if ($wd_liste !== null && empty($wd_liste["ok"])) {
	$wd_hata = $wd_hata !== "" ? $wd_hata : ($wd_liste["hata"] ?? "Liste okunamadı.");
	$wd_liste = null;
}

render_page($user, $TAB, "list_bayi");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
