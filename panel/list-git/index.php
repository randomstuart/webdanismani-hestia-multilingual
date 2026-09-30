<?php
/**
 * WebDanışmanı — "Git Deploy" page
 * Installs to: /usr/local/hestia/web/list/git/index.php
 */

ob_start();
$TAB = "GIT";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-modul.php";

$wd_user = wd_modul_kullanici();
$wd_is_admin = wd_modul_admin();
$wd_doms = wd_web_domains($wd_user);

$wd_hata = "";
$wd_bilgi = "";

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	$islem = (string) ($_POST["islem"] ?? "");
	$dom = strtolower(trim((string) ($_POST["v_domain"] ?? "")));
	$alt = trim((string) ($_POST["v_alt"] ?? ""), "/ ");
	if (!isset($wd_doms[$dom])) {
		$wd_hata = wd__("Invalid domain.");
	} elseif ($alt !== "" && !preg_match('/^[A-Za-z0-9._-]{1,60}$/', $alt)) {
		$wd_hata = wd__("Invalid subdirectory.");
	} elseif ($islem === "klonla") {
		$secenek = ["repo" => trim((string) ($_POST["v_repo"] ?? "")), "dal" => trim((string) ($_POST["v_dal"] ?? "")), "alt_dizin" => $alt];
		$d = wd_modul_json("wd-git", ["klonla", $wd_user, $dom], json_encode($secenek), 900);
		if (!empty($d["ok"])) {
			header("Location: /list/git/?durum=klonlandi&d=" . urlencode($dom . ($alt !== "" ? "/" . $alt : "")));
			exit();
		}
		$wd_hata = $d["hata"] ?? wd__("Could not clone.");
	} elseif ($islem === "cek") {
		$args = ["cek", $wd_user, $dom];
		if ($alt !== "") {
			$args[] = $alt;
		}
		$d = wd_modul_json("wd-git", $args, null, 600);
		if (!empty($d["ok"])) {
			header("Location: /list/git/?durum=cekildi&d=" . urlencode((string) ($d["mesaj"] ?? "")));
			exit();
		}
		$wd_hata = sprintf(wd__("Pull failed: %s"), $d["hata"] ?? "");
	} elseif ($islem === "otomatik") {
		$deger = ($_POST["v_deger"] ?? "on") === "on" ? "on" : "off";
		$args = ["otomatik", $wd_user, $dom];
		if ($alt !== "") {
			$args[] = $alt;
		}
		$args[] = $deger;
		$d = wd_modul_json("wd-git", $args);
		header("Location: /list/git/?durum=" . (!empty($d["ok"]) ? "otomatik-" . $deger : "hata"));
		exit();
	} elseif ($islem === "kaldir") {
		$args = ["kaldir", $wd_user, $dom];
		if ($alt !== "") {
			$args[] = $alt;
		}
		$d = wd_modul_json("wd-git", $args);
		header("Location: /list/git/?durum=" . (!empty($d["ok"]) ? "kaldirildi" : "hata"));
		exit();
	}
}

if ($wd_hata === "") {
	$wd_bilgi = wd_modul_durum_mesaji([
		"klonlandi" => wd__("Repository cloned: {d}"),
		"cekildi" => wd__("Pulled — {d}"),
		"otomatik-on" => wd__("Auto-pull enabled; tried every 5 minutes."),
		"otomatik-off" => wd__("Auto-pull disabled."),
		"kaldirildi" => wd__("Record removed; files left in place."),
		"hata" => wd__("Action could not be completed."),
	]);
}

$l = wd_modul_json("wd-git", ["liste", $wd_user], null, 120);
$wd_depolar = !empty($l["ok"]) ? (array) ($l["depolar"] ?? []) : [];
$a = wd_modul_json("wd-git", ["anahtar", $wd_user], null, 60);
$wd_anahtar = !empty($a["ok"]) ? (string) ($a["acik_anahtar"] ?? "") : "";

render_page($user, $TAB, "list_git");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
