<?php
/**
 * WebDanışmanı — "Directory Password Protection" page
 * Installs to: /usr/local/hestia/web/list/httpauth/index.php
 *
 * NEW file; unaffected by HestiaCP updates.
 *
 * WHAT IT DOES
 * Protects a site with browser-based username/password (HTTP Basic Auth).
 * HestiaCP has this capability on the CLI
 * (v-add/change/delete-web-domain-httpauth) but NO PANEL UI.
 * Counterpart to cPanel "Directory Privacy" / Plesk "Password-protected
 * directories".
 *
 * SECURITY NOTES
 *  - Username ALWAYS comes from the session. Accepting a username from the
 *    request would let a customer protect or unprotect another account's site.
 *  - Domain must be among the user's OWN domains; the v-command also checks
 *    this, but defense is not left to one layer.
 *  - All POST requests pass HestiaCP's own verify_csrf().
 *  - Shell arguments are escaped with quoteshellarg().
 */

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
$TAB = "WEB";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-helpers.php";

$wd_user = empty($_SESSION["look"]) ? $_SESSION["user"] : $_SESSION["look"];

$wd_hata = "";
$wd_bilgi = "";

/** Alan adı gerçekten bu kullanıcıya mı ait? */
function wd_domain_sahibi_mi(string $user, string $domain): bool {
	$doms = wd_web_domains($user);
	return isset($doms[$domain]);
}

/** HTTP auth kullanıcı adı biçimi. */
function wd_auth_ad_gecerli(string $ad): bool {
	return (bool) preg_match('/^[A-Za-z0-9._-]{2,32}$/', $ad);
}

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);

	$islem = $_POST["islem"] ?? "";
	$domain = trim((string) ($_POST["v_domain"] ?? ""));
	$auth_user = trim((string) ($_POST["v_auth_user"] ?? ""));
	$parola = (string) ($_POST["v_password"] ?? "");

	if (!wd_domain_sahibi_mi($wd_user, $domain)) {
		$wd_hata = wd__("Invalid domain") . ": " . htmlspecialchars($domain);
	} elseif (!wd_auth_ad_gecerli($auth_user)) {
		$wd_hata = wd__("Username must be 2–32 characters and contain only letters, digits, dots, underscores, or hyphens.");
	} elseif ($islem !== "sil" && strlen($parola) < 8) {
		$wd_hata = wd__("Password must be at least 8 characters.");
	} else {
		$cmd = "";
		if ($islem === "ekle") {
			$cmd = "v-add-web-domain-httpauth";
		} elseif ($islem === "parola") {
			$cmd = "v-change-web-domain-httpauth";
		} elseif ($islem === "sil") {
			$cmd = "v-delete-web-domain-httpauth";
		}

		if ($cmd === "") {
			$wd_hata = wd__("Unknown action.");
		} else {
			$arg =
				quoteshellarg($wd_user) .
				" " .
				quoteshellarg($domain) .
				" " .
				quoteshellarg($auth_user);
			if ($islem !== "sil") {
				$arg .= " " . quoteshellarg($parola);
			}

			$out = [];
			$rc = 0;
			exec(HESTIA_CMD . $cmd . " " . $arg, $out, $rc);

			if ($rc !== 0) {
				// Parolayı ASLA günlüğe/çıktıya taşımayız; yalnızca komutun
				// kendi hata metni gösterilir.
				$wd_hata = trim(implode(" ", $out));
				if ($wd_hata === "") {
					$wd_hata = sprintf(wd__("Action failed (code %d)."), (int) $rc);
				}
			} else {
				$msg = [
					"ekle" => "koruma-eklendi",
					"parola" => "parola-degisti",
					"sil" => "koruma-kaldirildi",
				];
				// POST-Redirect-GET: sayfa yenilendiğinde işlem tekrarlanmasın.
				header(
					"Location: /list/httpauth/?durum=" .
						$msg[$islem] .
						"&d=" .
						urlencode($domain),
				);
				exit();
			}
		}
	}
}

if (empty($wd_hata) && isset($_GET["durum"])) {
	$d = htmlspecialchars((string) ($_GET["d"] ?? ""), ENT_QUOTES, "UTF-8");
	$mesajlar = [
		"koruma-eklendi" => sprintf(wd__("Password protection added: %s"), $d),
		"parola-degisti" => sprintf(wd__("Password updated: %s"), $d),
		"koruma-kaldirildi" => sprintf(wd__("Password protection removed: %s"), $d),
	];
	$wd_bilgi = $mesajlar[$_GET["durum"]] ?? "";
}

// Alan adları (POST sonrası taze okunur)
$wd_doms = wd_web_domains($wd_user);

render_page($user, $TAB, "list_httpauth");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
