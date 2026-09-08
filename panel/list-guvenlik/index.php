<?php
/**
 * WebDanışmanı — "Güvenlik" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/guvenlik/index.php
 *
 * İki bölüm tek sayfada: sunucu güvenlik denetimi + zararlı yazılım taraması.
 * Kavramsal olarak aynı iş oldukları için ayrı sayfalara bölünmedi.
 *
 * YALNIZCA YÖNETİCİ. Bulgular sunucu geneline aittir.
 *
 * SERTLEŞTİRME İŞLEMLERİ
 * Panel yalnızca "uygula <islem>" çağırır; hangi işlemin güvenli olduğuna
 * wd-guvenlik karar verir ve ön koşul sağlanmıyorsa REDDEDER. Kilitlenme
 * koruması burada değil, kök betikte yaşar — panel atlatılabilir.
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
		$wd_hata = "Bilinmeyen işlem.";
	}
}

if ($wd_hata === "" && isset($_GET["durum"])) {
	$mesaj = [
		"tarandi" => "Zararlı yazılım taraması yeniden çalıştırıldı.",
		"denetlendi" => "Güvenlik denetimi yeniden çalıştırıldı.",
		"uygulandi" => "İşlem uygulandı: " . htmlspecialchars((string) ($_GET["i"] ?? ""), ENT_QUOTES, "UTF-8"),
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
