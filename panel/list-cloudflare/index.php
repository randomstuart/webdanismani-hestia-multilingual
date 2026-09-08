<?php
/**
 * WebDanışmanı — "Cloudflare" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/cloudflare/index.php
 *
 * YENİ dosyadır; HestiaCP güncellemelerinden etkilenmez.
 *
 * YALNIZCA YÖNETİCİ. Cloudflare jetonu hesaptaki TÜM bölgeleri yönetebilir;
 * bu tek bir müşteriye açılamaz.
 *
 * JETON GÜVENLİĞİ
 * Jeton hiçbir zaman komut satırına yazılmaz — `ps` çıktısında görünürdü.
 * proc_open ile alt sürecin STDIN'ine verilir. Panel jeton dosyasını okumaz;
 * dosya 0600 root'tur ve tüm işlemler sudo'lu wd-cloudflare üzerinden geçer.
 */

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
$TAB = "CLOUDFLARE";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-helpers.php";

$wd_user = empty($_SESSION["look"]) ? $_SESSION["user"] : $_SESSION["look"];
$wd_is_admin = $_SESSION["userContext"] === "admin" && empty($_SESSION["look"]);

if (!$wd_is_admin) {
	header("Location: /list/tools/");
	exit();
}

const WD_CF = "/usr/local/hestia/wd/bin/wd-cloudflare";

$wd_hata = "";
$wd_bilgi = "";

/** wd-cloudflare çağırır; $stdin verilirse komut satırına DEĞİL STDIN'e yazar. */
function wd_cf_calistir(string $args, ?string $stdin = null): array {
	$cmd = "/usr/bin/sudo " . WD_CF . " " . $args;
	$desc = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
	$p = proc_open($cmd, $desc, $boru);
	if (!is_resource($p)) {
		return [1, "", "süreç başlatılamadı"];
	}
	if ($stdin !== null) {
		fwrite($boru[0], $stdin);
	}
	fclose($boru[0]);
	$out = stream_get_contents($boru[1]);
	$err = stream_get_contents($boru[2]);
	fclose($boru[1]);
	fclose($boru[2]);
	return [proc_close($p), $out, $err];
}

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	$islem = $_POST["islem"] ?? "";

	if ($islem === "jeton") {
		$jeton = trim((string) ($_POST["v_token"] ?? ""));
		if (strlen($jeton) < 20) {
			$wd_hata = "Jeton çok kısa görünüyor.";
		} else {
			[$rc, $out] = wd_cf_calistir("jeton-kaydet", $jeton);
			$d = json_decode($out, true);
			if ($rc === 0 && !empty($d["ok"])) {
				header("Location: /list/cloudflare/?durum=jeton-ok");
				exit();
			}
			$wd_hata = "Jeton doğrulanamadı: " . ($d["hata"] ?? "bilinmeyen hata");
		}
	} elseif ($islem === "jeton-sil") {
		wd_cf_calistir("jeton-sil");
		header("Location: /list/cloudflare/?durum=jeton-silindi");
		exit();
	} elseif ($islem === "ip") {
		[$rc, $out, $err] = wd_cf_calistir("ip-guncelle");
		$d = json_decode($out, true);
		if ($rc === 0 && !empty($d["ok"])) {
			header("Location: /list/cloudflare/?durum=ip-ok&v4=" . (int) $d["v4"]);
			exit();
		}
		$wd_hata = "IP listesi güncellenemedi: " . trim($err ?: $out);
	} elseif ($islem === "onbellek" || $islem === "gelistirme" || $islem === "dns") {
		// Alan adı yalnızca Cloudflare'den gelen bölge listesinden seçilebilir.
		$dom = trim((string) ($_POST["v_zone"] ?? ""));
		if (!preg_match('/^[a-z0-9.-]{3,253}$/i', $dom)) {
			$wd_hata = "Geçersiz bölge.";
		} elseif ($islem === "onbellek") {
			[$rc, $out] = wd_cf_calistir("onbellek-temizle " . quoteshellarg($dom));
			$d = json_decode($out, true);
			if (!empty($d["ok"])) {
				header("Location: /list/cloudflare/?durum=onbellek&d=" . urlencode($dom));
				exit();
			}
			$wd_hata = "Önbellek temizlenemedi: " . ($d["hata"] ?? "");
		} elseif ($islem === "gelistirme") {
			$deger = ($_POST["v_deger"] ?? "off") === "on" ? "on" : "off";
			[$rc, $out] = wd_cf_calistir(
				"gelistirme " . quoteshellarg($dom) . " " . $deger,
			);
			$d = json_decode($out, true);
			if (!empty($d["ok"])) {
				header("Location: /list/cloudflare/?durum=gelistirme-" . $deger . "&d=" . urlencode($dom));
				exit();
			}
			$wd_hata = "Geliştirme modu değiştirilemedi: " . ($d["hata"] ?? "");
		} else {
			// DNS gönderimi: hangi Hestia kullanıcısının bölgesi olduğu bulunur.
			$sahip = "";
			foreach (wd_kullanici_listesi() as $u) {
				if (isset(wd_dns_domains($u)[$dom])) {
					$sahip = $u;
					break;
				}
			}
			if ($sahip === "") {
				$wd_hata = "Bu alan adı için panelde bir DNS bölgesi yok.";
			} else {
				[$rc, $out] = wd_cf_calistir(
					"dns-gonder " . quoteshellarg($sahip) . " " . quoteshellarg($dom),
				);
				$d = json_decode($out, true);
				if (!empty($d["ok"])) {
					header(
						"Location: /list/cloudflare/?durum=dns&d=" .
							urlencode($dom) .
							"&e=" . (int) ($d["eklendi"] ?? 0) .
							"&g=" . (int) ($d["guncellendi"] ?? 0),
					);
					exit();
				}
				$wd_hata =
					"DNS gönderilemedi: " .
					implode("; ", array_slice($d["hatalar"] ?? ["bilinmeyen hata"], 0, 3));
			}
		}
	}
}

if ($wd_hata === "" && isset($_GET["durum"])) {
	$d = htmlspecialchars((string) ($_GET["d"] ?? ""), ENT_QUOTES, "UTF-8");
	switch ($_GET["durum"]) {
		case "jeton-ok":
			$wd_bilgi = "Cloudflare jetonu doğrulandı ve kaydedildi.";
			break;
		case "jeton-silindi":
			$wd_bilgi = "Jeton silindi.";
			break;
		case "ip-ok":
			$wd_bilgi =
				"Cloudflare IP listesi güncellendi (" .
				(int) ($_GET["v4"] ?? 0) .
				" IPv4 aralığı). Gerçek ziyaretçi IP'si yeniden doğru okunuyor.";
			break;
		case "onbellek":
			$wd_bilgi = "Önbellek temizlendi: " . $d;
			break;
		case "gelistirme-on":
			$wd_bilgi = "Geliştirme modu açıldı (3 saat): " . $d;
			break;
		case "gelistirme-off":
			$wd_bilgi = "Geliştirme modu kapatıldı: " . $d;
			break;
		case "dns":
			$wd_bilgi =
				"DNS gönderildi: " . $d .
				" — " . (int) ($_GET["e"] ?? 0) . " eklendi, " .
				(int) ($_GET["g"] ?? 0) . " güncellendi.";
			break;
	}
}

// Durum: her açılışta tazelenir (bölge listesi API'den gelir).
[$rc, $out] = wd_cf_calistir("json");
$wd_cf = json_decode($out, true);
if (!is_array($wd_cf)) {
	$wd_cf = null;
}

$wd_dns_bolgeleri = [];
foreach (wd_kullanici_listesi() as $u) {
	foreach (wd_dns_domains($u) as $d => $_) {
		$wd_dns_bolgeleri[$d] = $u;
	}
}

render_page($user, $TAB, "list_cloudflare");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
