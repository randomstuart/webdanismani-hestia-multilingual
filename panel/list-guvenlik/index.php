<?php
/**
 * WebDanışmanı — "Security" page
 * Installs to: /usr/local/hestia/web/list/guvenlik/index.php
 *
 * Two sections on one page: server security audit + malware scan.
 * Kept together because they are the same conceptual job.
 *
 * ADMIN ONLY. Findings are server-wide.
 *
 * HARDENING ACTIONS
 * The panel only calls "uygula <islem>"; wd-guvenlik decides whether an
 * action is safe and REFUSES if a precondition is missing. Lockout
 * protection lives in the root script, not here — the panel can be bypassed.
 */

ob_start();
$TAB = "GUVENLIK";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-helpers.php";

$wd_is_admin = $_SESSION["userContext"] === "admin" && empty($_SESSION["look"]);
if (!$wd_is_admin) {
	header("Location: /list/tools/");
	exit();
}

const WD_GUV = "/usr/local/hestia/wd/bin/wd-guvenlik";
const WD_TAR = "/usr/local/hestia/wd/bin/wd-tarama";

/* Panelden çalıştırılmasına izin verilen sertleştirme işlemleri.
   İstekten gelen serbest metin ASLA komuta geçmez. */
$WD_ISLEMLER = ["ssh-parola-kapat", "ssh-root-kapat", "ftp-kapat", "eol-php-kaldir"];

$wd_hata = "";
$wd_bilgi = "";

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	$islem = (string) ($_POST["islem"] ?? "");

	if ($islem === "tara") {
		exec("/usr/bin/sudo " . WD_TAR . " refresh 0 2>/dev/null");
		header("Location: /list/guvenlik/?durum=tarandi");
		exit();
	}
	if ($islem === "denetle") {
		exec("/usr/bin/sudo " . WD_GUV . " refresh 2>/dev/null");
		header("Location: /list/guvenlik/?durum=denetlendi");
		exit();
	}
	if (in_array($islem, $WD_ISLEMLER, true)) {
		$out = [];
		$rc = 0;
		exec("/usr/bin/sudo " . WD_GUV . " uygula " . escapeshellarg($islem) . " 2>&1", $out, $rc);
		$d = json_decode(implode("", $out), true);
		if (!empty($d["ok"])) {
			header("Location: /list/guvenlik/?durum=uygulandi&i=" . urlencode($islem));
			exit();
		}
		$wd_hata = $d["hata"] ?? trim(implode(" ", $out));
	} elseif ($islem !== "") {
		$wd_hata = wd__("Unknown action.");
	}
}

if ($wd_hata === "" && isset($_GET["durum"])) {
	$mesaj = [
		"tarandi" => wd__("Malware scan re-run."),
		"denetlendi" => wd__("Security audit re-run."),
		"uygulandi" => sprintf(wd__("Action applied: %s"), htmlspecialchars((string) ($_GET["i"] ?? ""), ENT_QUOTES, "UTF-8")),
	];
	$wd_bilgi = $mesaj[$_GET["durum"]] ?? "";
}

/** Önbellek dosyasını doğrudan okur (0644); toplayıcıyı sayfa açılışında çalıştırmaz. */
function wd_onbellek(string $ad): ?array {
	$yol = "/usr/local/hestia/wd/cache/" . $ad;
	if (!is_readable($yol)) {
		return null;
	}
	$ham = @file_get_contents($yol);
	$d = $ham !== false ? json_decode($ham, true) : null;
	return is_array($d) ? $d : null;
}

$wd_guv = wd_onbellek("guvenlik.json");
$wd_tar = wd_onbellek("tarama.json");

render_page($user, $TAB, "list_guvenlik");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
