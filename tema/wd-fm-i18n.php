<?php
/**
 * WebDanışmanı — File Manager i18n (per-user Hestia language)
 *
 * Installed as: /usr/local/hestia/web/fm/wd-fm-i18n.php
 * Injected before wd-fm.js. Outputs: window.WDFM_I18N = {...};
 */
header("Content-Type: application/javascript; charset=utf-8");
header("Cache-Control: private, no-store");

if (session_status() !== PHP_SESSION_ACTIVE) {
	@session_start();
}

$lang = strtolower((string) ($_SESSION["language"] ?? $_SESSION["LANGUAGE"] ?? "en"));
$lang = preg_replace("/[^a-z]/", "", $lang) ?: "en";

$map = [];
if ($lang === "tr") {
	$json = __DIR__ . "/dist/js/wd-fm-i18n.tr.json";
	if (is_readable($json)) {
		$d = json_decode((string) file_get_contents($json), true);
		if (is_array($d)) {
			$map = $d;
		}
	}
}

echo "window.WDFM_I18N = " . json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
