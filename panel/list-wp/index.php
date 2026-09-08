<?php
/**
 * WebDanışmanı — "WordPress Araçları" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/wp/index.php
 *
 * Site listesi ve güncelleme bilgisi ÖNBELLEKTEN gelir (gecelik tarama);
 * her işlem sonrası yalnızca o site tazelenir.
 */

ob_start();
$TAB = "WP";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-modul.php";

$wd_user = wd_modul_kullanici();
$wd_is_admin = wd_modul_admin();
$wd_doms = wd_web_domains($wd_user);

$wd_hata = "";
$wd_bilgi = "";
$wd_cikti = [];

/** Site sahipliği: yönetici her siteyi, müşteri yalnız kendi alan adlarını yönetir. */
$wd_sahip = function (string $u, string $d) use ($wd_is_admin, $wd_user, $wd_doms): bool {
	if ($wd_is_admin) {
		return preg_match('/^[a-z0-9._-]{1,32}$/i', $u) === 1;
	}
	return $u === $wd_user && isset($wd_doms[$d]);
};

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	$islem = (string) ($_POST["islem"] ?? "");
	$u = (string) ($_POST["v_user"] ?? $wd_user);
	$d = (string) ($_POST["v_domain"] ?? "");
	$y = (string) ($_POST["v_yol"] ?? "/");
	$geri = "/list/wp/" . ($d !== "" ? "?domain=" . urlencode($d) : "");

	if ($islem === "tara") {
		$args = $wd_is_admin ? ["tara"] : ["tara", $wd_user];
		$r = wd_modul_json("wd-wp", $args, null, 600);
		header("Location: /list/wp/?durum=" . (!empty($r["ok"]) ? "tarandi" : "hata"));
		exit();
	}
	if (!$wd_sahip($u, $d) || !preg_match('#^/([A-Za-z0-9._-]{1,60}(/[A-Za-z0-9._-]{1,60})?)?$#', $y)) {
		$wd_hata = "Geçersiz site.";
	} else {
		switch ($islem) {
			case "guncelle":
				$ne = in_array($_POST["v_ne"] ?? "", ["cekirdek", "eklentiler", "temalar", "hepsi"], true) ? $_POST["v_ne"] : "hepsi";
				$r = wd_modul_json("wd-wp", ["guncelle", $u, $d, $y, $ne], null, 900);
				if (!empty($r["ok"])) {
					$_SESSION["wd_wp_cikti"] = (array) ($r["cikti"] ?? []);
					header("Location: " . $geri . "&durum=guncellendi");
					exit();
				}
				$wd_hata = "Güncelleme başarısız: " . ($r["hata"] ?? "");
				break;
			case "eklenti":
				$ad = (string) ($_POST["v_ad"] ?? "");
				$ne = in_array($_POST["v_ne"] ?? "", ["activate", "deactivate", "update"], true) ? $_POST["v_ne"] : "update";
				$r = wd_modul_json("wd-wp", ["eklenti", $u, $d, $y, $ad, $ne], null, 300);
				if (!empty($r["ok"])) {
					header("Location: " . $geri . "&durum=eklenti-ok&d=" . urlencode($ad));
					exit();
				}
				$wd_hata = "Eklenti işlemi başarısız: " . ($r["hata"] ?? "");
				break;
			case "otomatik":
			case "bakim":
				$deger = ($_POST["v_deger"] ?? "on") === "on" ? "on" : "off";
				$r = wd_modul_json("wd-wp", [$islem, $u, $d, $y, $deger], null, 200);
				if (!empty($r["ok"])) {
					header("Location: " . $geri . "&durum=" . $islem . "-" . $deger);
					exit();
				}
				$wd_hata = "İşlem başarısız: " . ($r["hata"] ?? "");
				break;
			case "dogrula":
				$r = wd_modul_json("wd-wp", ["dogrula", $u, $d, $y], null, 300);
				if (!empty($r["ok"])) {
					header("Location: " . $geri . "&durum=" . (!empty($r["dogrulama"]["ok"]) ? "dogru" : "sorunlu"));
					exit();
				}
				$wd_hata = "Doğrulama yapılamadı: " . ($r["hata"] ?? "");
				break;
			case "giris":
				$r = wd_modul_json("wd-wp", ["giris", $u, $d, $y], null, 90);
				if (!empty($r["ok"]) && !empty($r["url"])) {
					header("Location: " . $r["url"]);
					exit();
				}
				$wd_hata = "Giriş bağlantısı üretilemedi: " . ($r["hata"] ?? "");
				break;
			case "onbellek":
				$r = wd_modul_json("wd-wp", ["onbellek", $u, $d, $y], null, 200);
				if (!empty($r["ok"])) {
					$_SESSION["wd_wp_cikti"] = (array) ($r["notlar"] ?? []);
					header("Location: " . $geri . "&durum=onbellek");
					exit();
				}
				$wd_hata = "Önbellek temizlenemedi: " . ($r["hata"] ?? "");
				break;
		}
	}
}

if ($wd_hata === "") {
	$wd_bilgi = wd_modul_durum_mesaji([
		"tarandi" => "Tüm siteler yeniden tarandı.",
		"hata" => "Tarama yapılamadı; wp-cli kurulu mu?",
		"guncellendi" => "Güncelleme tamamlandı.",
		"eklenti-ok" => "{d} için işlem yapıldı.",
		"otomatik-on" => "Otomatik güncelleme açıldı (çekirdek + eklentiler + temalar).",
		"otomatik-off" => "Otomatik güncelleme kapatıldı (çekirdek yalnız küçük sürümler).",
		"bakim-on" => "Bakım modu açıldı; ziyaretçiler bakım sayfası görür.",
		"bakim-off" => "Bakım modu kapatıldı.",
		"dogru" => "Çekirdek dosyalar resmî sürümle birebir aynı.",
		"sorunlu" => "Çekirdekte değiştirilmiş ya da fazladan dosya var; aşağıdaki listeyi inceleyin.",
		"onbellek" => "Önbellek temizlendi.",
	]);
	if (!empty($_SESSION["wd_wp_cikti"])) {
		$wd_cikti = (array) $_SESSION["wd_wp_cikti"];
		unset($_SESSION["wd_wp_cikti"]);
	}
}

$wd_veri = wd_modul_onbellek("wp");
$wd_siteler = [];
if ($wd_veri && !empty($wd_veri["siteler"])) {
	foreach ($wd_veri["siteler"] as $s) {
		if (!$wd_is_admin && ($s["user"] ?? "") !== $wd_user) {
			continue;
		}
		$wd_siteler[] = $s;
	}
}
usort($wd_siteler, fn($a, $b) => strcmp($a["domain"] . $a["yol"], $b["domain"] . $b["yol"]));
$wd_secili = (string) ($_GET["domain"] ?? "");
$wd_wp_cli = $wd_veri === null ? null : !empty($wd_veri["wp_cli"]);
$wd_yas = wd_modul_onbellek_yas("wp");

render_page($user, $TAB, "list_wp");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
