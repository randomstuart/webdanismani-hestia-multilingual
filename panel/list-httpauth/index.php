<?php
/**
 * WebDanışmanı — "Dizin Şifre Koruma" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/httpauth/index.php
 *
 * YENİ dosyadır; HestiaCP güncellemelerinden etkilenmez.
 *
 * NE İŞE YARAR?
 * Bir web sitesini tarayıcı tabanlı kullanıcı adı/parola ile korur
 * (HTTP Basic Auth). HestiaCP'de bu yetenek CLI olarak vardır
 * (v-add/change/delete-web-domain-httpauth) ama PANELDE ARAYÜZÜ YOKTUR.
 * cPanel'deki "Directory Privacy", Plesk'teki "Password-protected
 * directories" karşılığıdır.
 *
 * GÜVENLİK NOTLARI
 *  - Kullanıcı adı DAİMA oturumdan alınır. İstekten gelen bir kullanıcı adı
 *    kabul edilseydi, bir müşteri başka bir hesabın sitesini koruyabilir ya da
 *    korumasını kaldırabilirdi.
 *  - Alan adı, kullanıcının KENDİ alan adları arasında olmak zorundadır;
 *    v-komutu da bunu doğrular ama savunma tek katmana bırakılmaz.
 *  - Tüm POST istekleri HestiaCP'nin kendi verify_csrf() denetiminden geçer.
 *  - Kabuk argümanları quoteshellarg() ile kaçırılır.
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
		$wd_hata = _("Invalid domain") . ": " . htmlspecialchars($domain);
	} elseif (!wd_auth_ad_gecerli($auth_user)) {
		$wd_hata =
			"Kullanıcı adı 2-32 karakter olmalı ve yalnızca harf, rakam, nokta, " .
			"alt çizgi veya tire içerebilir.";
	} elseif ($islem !== "sil" && strlen($parola) < 8) {
		$wd_hata = "Parola en az 8 karakter olmalı.";
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
			$wd_hata = "Bilinmeyen işlem.";
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
					$wd_hata = "İşlem başarısız (kod " . (int) $rc . ").";
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
		"koruma-eklendi" => "Şifre koruması eklendi: " . $d,
		"parola-degisti" => "Parola güncellendi: " . $d,
		"koruma-kaldirildi" => "Şifre koruması kaldırıldı: " . $d,
	];
	$wd_bilgi = $mesajlar[$_GET["durum"]] ?? "";
}

// Alan adları (POST sonrası taze okunur)
$wd_doms = wd_web_domains($wd_user);

render_page($user, $TAB, "list_httpauth");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
