<?php
/**
 * WebDanışmanı — "Reseller Management" page
 * Installs to: /usr/local/hestia/web/list/bayi/index.php
 *
 * TWO VIEWS:
 *   Admin    : defines resellers (package limits, customer cap, customer assignment)
 *   Reseller : opens/suspends own customers and changes packages and passwords
 *
 * Admin commands ("ayar", "bayi-*") run ONLY in admin context;
 * sudo cannot distinguish this, so the check here is required.
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
			$wd_hata = wd__("Invalid reseller.");
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
			$wd_hata = $d["hata"] ?? wd__("Could not save.");
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
			$wd_hata = $d["hata"] ?? wd__("Action could not be completed.");
		} else {
			$wd_hata = wd__("Invalid user.");
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
			$wd_hata = $d["hata"] ?? wd__("Could not create account.");
		} elseif (!preg_match($u_desen, $hedef)) {
			$wd_hata = wd__("Invalid user.");
		} elseif (in_array($islem, ["askiya", "askidan-cikar", "sil"], true)) {
			$d = wd_modul_json("wd-bayi", [$islem, $wd_user, $hedef], null, 600);
			if (!empty($d["ok"])) {
				header("Location: /list/bayi/?durum=" . $islem . "&d=" . urlencode($hedef));
				exit();
			}
			$wd_hata = $d["hata"] ?? wd__("Action could not be completed.");
		} elseif ($islem === "sifre") {
			$d = wd_modul_json("wd-bayi", ["sifre", $wd_user, $hedef], json_encode(["sifre" => (string) ($_POST["v_sifre"] ?? "")]));
			if (!empty($d["ok"])) {
				header("Location: /list/bayi/?durum=sifre&d=" . urlencode($hedef));
				exit();
			}
			$wd_hata = $d["hata"] ?? wd__("Could not change password.");
		} elseif ($islem === "paket") {
			$d = wd_modul_json("wd-bayi", ["paket", $wd_user, $hedef, (string) ($_POST["v_paket"] ?? "")]);
			if (!empty($d["ok"])) {
				header("Location: /list/bayi/?durum=paket&d=" . urlencode($hedef));
				exit();
			}
			$wd_hata = $d["hata"] ?? wd__("Could not change package.");
		}
	}
}

if ($wd_hata === "") {
	$wd_bilgi = wd_modul_durum_mesaji([
		"bayi-ok" => wd__("Reseller saved: {d}"),
		"bayi-silindi" => wd__("Reseller definition removed: {d} (customer accounts remain)."),
		"atandi" => wd__("{d} linked to reseller."),
		"cikarildi" => wd__("{d} unlinked from reseller."),
		"eklendi" => wd__("Customer account created: {d}"),
		"askiya" => wd__("{d} suspended."),
		"askidan-cikar" => wd__("{d} unsuspended."),
		"sil" => wd__("{d} deleted."),
		"sifre" => wd__("Password changed for {d}."),
		"paket" => wd__("Package changed for {d}."),
		"hata" => wd__("Action could not be completed."),
	]);
}

$wd_ayar = $wd_is_admin ? wd_modul_json("wd-bayi", ["ayar"], null, 120) : null;
$wd_liste = $wd_bayi_mi ? wd_modul_json("wd-bayi", ["liste", $wd_user], null, 120) : null;
if ($wd_liste !== null && empty($wd_liste["ok"])) {
	$wd_hata = $wd_hata !== "" ? $wd_hata : ($wd_liste["hata"] ?? wd__("Could not read list."));
	$wd_liste = null;
}

render_page($user, $TAB, "list_bayi");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
