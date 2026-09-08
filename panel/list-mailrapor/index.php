<?php
/**
 * WebDanışmanı — "Mail Raporu" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/mailrapor/index.php
 *
 * YALNIZCA YÖNETİCİ. Rapor sunucudaki TÜM göndericileri içerir; tek bir
 * müşteriye açılamaz.
 */

ob_start();
$TAB = "MAIL";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-helpers.php";

$wd_is_admin = $_SESSION["userContext"] === "admin" && empty($_SESSION["look"]);
if (!$wd_is_admin) {
	header("Location: /list/tools/");
	exit();
}

$wd_gun = isset($_GET["gun"]) ? max(1, min(30, (int) $_GET["gun"])) : 7;

$wd_yenilendi = false;
if (
	isset($_GET["yenile"]) &&
	isset($_GET["token"]) &&
	!empty($_SESSION["token"]) &&
	hash_equals((string) $_SESSION["token"], (string) $_GET["token"])
) {
	$bin = "/usr/local/hestia/wd/bin/wd-mailrapor";
	if (is_file($bin)) {
		exec("/usr/bin/sudo " . $bin . " refresh " . $wd_gun . " 2>/dev/null");
		$wd_yenilendi = true;
	}
}

/* Önbellekten okunur; günlük taraması pahalıdır ve cron tazeler. */
$wd_rapor = null;
$yol = "/usr/local/hestia/wd/cache/mailrapor.json";
if (is_readable($yol)) {
	$ham = @file_get_contents($yol);
	$d = $ham !== false ? json_decode($ham, true) : null;
	if (is_array($d)) {
		$wd_rapor = $d;
	}
}

render_page($user, $TAB, "list_mailrapor");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
