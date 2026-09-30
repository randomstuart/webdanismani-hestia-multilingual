<?php
/**
 * WebDanışmanı — gettext i18n (WordPress-style text domain)
 *
 * Domain: webdanismani
 * Catalogs: /usr/local/hestia/web/locale/{lang}/LC_MESSAGES/webdanismani.mo
 *
 * Source strings in code are English (msgid). Translations live in .po/.mo.
 * Language follows HestiaCP's user/session language — no second picker.
 *
 * Usage (like WP __ / _n):
 *   wd__('Save')
 *   wd_n__('%d day', '%d days', $n)
 *   sprintf(wd__('Hello %s'), $name)
 */

if (!defined("WD_I18N_DOMAIN")) {
	define("WD_I18N_DOMAIN", "webdanismani");
}
if (!defined("WD_I18N_PATH")) {
	// Prefer installed Hestia locale tree; fall back to package path during dev.
	$wd_i18n_hestia = "/usr/local/hestia/web/locale";
	define(
		"WD_I18N_PATH",
		is_dir($wd_i18n_hestia) ? $wd_i18n_hestia : dirname(__DIR__) . "/locale",
	);
}

/**
 * Bind the webdanismani text domain once per request.
 * Relies on Hestia having already called setlocale() from the user language.
 */
function wd_i18n_boot(): void {
	static $booted = false;
	if ($booted) {
		return;
	}
	$booted = true;

	if (!function_exists("bindtextdomain") || !function_exists("dgettext")) {
		return;
	}

	bindtextdomain(WD_I18N_DOMAIN, WD_I18N_PATH);
	if (function_exists("bind_textdomain_codeset")) {
		bind_textdomain_codeset(WD_I18N_DOMAIN, "UTF-8");
	}

	// So root helpers called via exec()/sudo inherit the panel language.
	wd_putenv_lang();
}

/**
 * Export the current panel language for Python helpers (WD_LANG / LANGUAGE / LANG).
 */
function wd_putenv_lang(): void {
	$lang = strtolower((string) ($_SESSION["language"] ?? $_SESSION["LANGUAGE"] ?? "en"));
	$lang = preg_replace("/[^a-z]/", "", $lang) ?: "en";
	$locale = $lang . "_" . strtoupper($lang) . ".UTF-8";
	putenv("WD_LANG=" . $lang);
	putenv("LANGUAGE=" . $lang);
	putenv("LANG=" . $locale);
	putenv("LC_ALL=" . $locale);
	putenv("LC_MESSAGES=" . $locale);
	$_ENV["WD_LANG"] = $lang;
	$_ENV["LANGUAGE"] = $lang;
	$_ENV["LANG"] = $locale;
}

/** Translate a singular string (gettext msgid = English). */
function wd__(string $msgid): string {
	wd_i18n_boot();
	if (!function_exists("dgettext")) {
		return $msgid;
	}
	$out = dgettext(WD_I18N_DOMAIN, $msgid);
	return $out !== "" ? $out : $msgid;
}

/**
 * Translate with plural forms.
 * Example: sprintf(wd_n__('%d day', '%d days', $n), $n)
 */
function wd_n__(string $singular, string $plural, int $count): string {
	wd_i18n_boot();
	if (!function_exists("dngettext")) {
		return $count === 1 ? $singular : $plural;
	}
	$out = dngettext(WD_I18N_DOMAIN, $singular, $plural, $count);
	return $out !== "" ? $out : ($count === 1 ? $singular : $plural);
}

/** Translate and HTML-escape (safe for attributes / text nodes). */
function wd_esc__(string $msgid): string {
	return htmlspecialchars(wd__($msgid), ENT_QUOTES, "UTF-8");
}

/**
 * Map Hestia language code → preferred locale candidates for setlocale.
 * Only used if Hestia has not set a locale yet (should be rare).
 */
function wd_i18n_ensure_locale(): void {
	if (!function_exists("setlocale")) {
		return;
	}
	$cur = setlocale(LC_MESSAGES, "0");
	if (is_string($cur) && $cur !== "" && $cur !== "C" && stripos($cur, "UTF-8") !== false) {
		return;
	}
	$lang = strtolower((string) ($_SESSION["language"] ?? $_SESSION["LANGUAGE"] ?? "en"));
	$lang = preg_replace("/[^a-z_]/", "", $lang) ?: "en";
	$map = [
		"en" => ["en_US.UTF-8", "en_US.utf8", "C.UTF-8", "C"],
		"tr" => ["tr_TR.UTF-8", "tr_TR.utf8", "C.UTF-8", "C"],
		"de" => ["de_DE.UTF-8", "de_DE.utf8", "C.UTF-8", "C"],
		"es" => ["es_ES.UTF-8", "es_ES.utf8", "C.UTF-8", "C"],
		"fr" => ["fr_FR.UTF-8", "fr_FR.utf8", "C.UTF-8", "C"],
		"nl" => ["nl_NL.UTF-8", "nl_NL.utf8", "C.UTF-8", "C"],
		"pt" => ["pt_PT.UTF-8", "pt_PT.utf8", "C.UTF-8", "C"],
		"ru" => ["ru_RU.UTF-8", "ru_RU.utf8", "C.UTF-8", "C"],
		"ar" => ["ar_SA.UTF-8", "ar_SA.utf8", "C.UTF-8", "C"],
	];
	$cands = $map[$lang] ?? array_merge(
		[$lang . "_" . strtoupper($lang) . ".UTF-8", $lang . ".UTF-8"],
		["C.UTF-8", "C"],
	);
	@setlocale(LC_MESSAGES, ...$cands);
}
