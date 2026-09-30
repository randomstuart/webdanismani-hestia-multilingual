<?php
/**
 * WebDanışmanı — "Disk Usage" page
 * Installs to: /usr/local/hestia/web/list/disk/index.php
 *
 * NEW file; unaffected by HestiaCP updates.
 *
 * WHAT IT DOES
 * HestiaCP only shows disk usage as a TOTAL; the panel has no answer to
 * "why is my quota full?" This page breaks it down: which domain, which
 * directory, which file. (Counterpart to cPanel "Disk Usage" /
 * Plesk "Disk Space Usage".)
 */

$TAB = "DISK";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-helpers.php";

$wd_user = empty($_SESSION["look"]) ? $_SESSION["user"] : $_SESSION["look"];
$wd_is_admin = $_SESSION["userContext"] === "admin" && empty($_SESSION["look"]);

/* Elle tazeleme. Tarama saniyeler sürebilir, bu yüzden normalde cron'un
   yazdığı önbellekten okunur. Token kontrolü zorunlu: aksi hâlde dışarıdan
   konan bir <img> etiketi sunucuya sürekli disk taraması yaptırabilirdi. */
$wd_yenilendi = false;
if (
	isset($_GET["yenile"]) &&
	isset($_GET["token"]) &&
	!empty($_SESSION["token"]) &&
	hash_equals((string) $_SESSION["token"], (string) $_GET["token"])
) {
	$bin = "/usr/local/hestia/wd/bin/wd-disk";
	if (is_file($bin)) {
		exec("/usr/bin/sudo " . $bin . " refresh 2>/dev/null");
		$wd_yenilendi = true;
	}
}

$wd_disk = wd_disk($wd_is_admin ? "" : $wd_user);

render_page($user, $TAB, "list_disk");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
