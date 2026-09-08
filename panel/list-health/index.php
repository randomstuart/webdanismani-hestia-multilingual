<?php
/**
 * WebDanışmanı — "Sağlık Merkezi" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/health/index.php
 *
 * YENİ dosyadır; HestiaCP güncellemelerinden etkilenmez.
 *
 * NE İŞE YARAR?
 * Panelin İÇİNDEKİ kayıtla DIŞARIDAN gerçekten görünen DNS'i karşılaştırır.
 * HestiaCP "DKIM etkin" der ama kayıt DNS'e yazılmamışsa mail yine imzasız
 * gider — panel bunu kendiliğinden fark etmez. Buradaki kontroller tam olarak
 * o farkı yakalar.
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
