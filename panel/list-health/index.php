<?php
/**
 * WebDanışmanı — "Health Center" page
 * Installs to: /usr/local/hestia/web/list/health/index.php
 *
 * NEW file; unaffected by HestiaCP updates.
 *
 * WHAT IT DOES
 * Compares the record INSIDE the panel with what DNS actually shows from
 * OUTSIDE. If HestiaCP says "DKIM enabled" but the record was never published,
 * mail still goes unsigned — the panel does not notice on its own. These
 * checks catch exactly that gap.
 */

$TAB = "HEALTH";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// WebDanışmanı yardımcıları
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-helpers.php";

$wd_user = empty($_SESSION["look"]) ? $_SESSION["user"] : $_SESSION["look"];
$wd_is_admin = $_SESSION["userContext"] === "admin" && empty($_SESSION["look"]);

/* ---------------------------------------------------------------------------
   Elle tazeleme.

   Toplama saniyeler sürer (DNS sorguları), bu yüzden normalde önbellekten
   okunur ve cron tazeler. Kullanıcı bir kaydı düzelttikten sonra sonucu hemen
   görmek isteyebilir; bunun için açık bir tazeleme bağlantısı var.

   Token kontrolü zorunlu: aksi hâlde başka bir siteye konan bir <img> etiketi
   sunucuya sürekli toplama yaptırabilirdi.
   --------------------------------------------------------------------------- */
$wd_yenilendi = false;
if (
	isset($_GET["yenile"]) &&
	isset($_GET["token"]) &&
	!empty($_SESSION["token"]) &&
	hash_equals((string) $_SESSION["token"], (string) $_GET["token"])
) {
	$bin = "/usr/local/hestia/wd/bin/wd-health";
	if (is_file($bin)) {
		exec("/usr/bin/sudo " . $bin . " refresh 2>/dev/null");
		$wd_yenilendi = true;
	}
}

// Önbellekten oku. Cron 15 dakikada bir tazelediği için 3600 sn'lik tavan
// pratikte hiç devreye girmez; yalnızca cron hiç çalışmadıysa ilk açılışta
// bir kez toplama yapar.
$wd_saglik = wd_health($wd_is_admin ? "" : $wd_user, $wd_is_admin, 3600);

// Render page
render_page($user, $TAB, "list_health");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
