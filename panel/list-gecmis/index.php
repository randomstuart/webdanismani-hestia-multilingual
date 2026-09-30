<?php
/**
 * WebDanışmanı — "Resource History" page
 * Installs to: /usr/local/hestia/web/list/gecmis/index.php
 *
 * Per-site CPU/RAM over time. Live readings are on the Tools page; this
 * answers "what happened at 3am?"
 */

ob_start();
$TAB = "GECMIS";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-helpers.php";

$wd_user = empty($_SESSION["look"]) ? $_SESSION["user"] : $_SESSION["look"];
$wd_is_admin = $_SESSION["userContext"] === "admin" && empty($_SESSION["look"]);

$wd_gun = isset($_GET["gun"]) ? max(1, min(30, (int) $_GET["gun"])) : 7;

$wd_gecmis = null;
$bin = "/usr/local/hestia/wd/bin/wd-gecmis";
if (is_file($bin)) {
	$out = [];
	$rc = 0;
	exec("/usr/bin/sudo " . $bin . " json " . (int) $wd_gun . " 2>/dev/null", $out, $rc);
	$d = json_decode(implode("", $out), true);
	if ($rc === 0 && is_array($d)) {
		$wd_gecmis = $d;
	}
}

/* Müşteri yalnızca kendi sitelerini görür. */
if ($wd_gecmis !== null && !$wd_is_admin) {
	$kendi = array_keys(wd_web_domains($wd_user));
	$wd_gecmis["siteler"] = array_values(array_filter(
		$wd_gecmis["siteler"] ?? [],
		function ($s) use ($kendi) {
			return in_array($s["site"], $kendi, true);
		},
	));
	$wd_gecmis["seri"] = array_values(array_filter(
		$wd_gecmis["seri"] ?? [],
		function ($s) use ($kendi) {
			return in_array($s["site"], $kendi, true);
		},
	));
}

render_page($user, $TAB, "list_gecmis");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];