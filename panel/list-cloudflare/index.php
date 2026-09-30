<?php
/**
 * WebDanışmanı — "Cloudflare" page
 * Installs to: /usr/local/hestia/web/list/cloudflare/index.php
 *
 * NEW file; unaffected by HestiaCP updates.
 *
 * ADMIN ONLY. The Cloudflare token can manage ALL zones on the account;
 * it must not be exposed to a single customer.
 *
 * TOKEN SECURITY
 * The token is never written on the command line — it would show in `ps`.
 * It is passed to the child process STDIN via proc_open. The panel does not
 * read the token file; the file is 0600 root and all actions go through
 * sudo'd wd-cloudflare.
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
		return [1, "", wd__("could not start process")];
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
			$wd_hata = wd__("Token looks too short.");
		} else {
			[$rc, $out] = wd_cf_calistir("jeton-kaydet", $jeton);
			$d = json_decode($out, true);
			if ($rc === 0 && !empty($d["ok"])) {
				header("Location: /list/cloudflare/?durum=jeton-ok");
				exit();
			}
			$wd_hata = sprintf(wd__("Token could not be verified: %s"), $d["hata"] ?? wd__("unknown error"));
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
		$wd_hata = sprintf(wd__("Could not update IP list: %s"), trim($err ?: $out));
	} elseif ($islem === "onbellek" || $islem === "gelistirme" || $islem === "dns") {
		// Alan adı yalnızca Cloudflare'den gelen bölge listesinden seçilebilir.
		$dom = trim((string) ($_POST["v_zone"] ?? ""));
		if (!preg_match('/^[a-z0-9.-]{3,253}$/i', $dom)) {
			$wd_hata = wd__("Invalid zone.");
		} elseif ($islem === "onbellek") {
			[$rc, $out] = wd_cf_calistir("onbellek-temizle " . quoteshellarg($dom));
			$d = json_decode($out, true);
			if (!empty($d["ok"])) {
				header("Location: /list/cloudflare/?durum=onbellek&d=" . urlencode($dom));
				exit();
			}
			$wd_hata = sprintf(wd__("Could not purge cache: %s"), $d["hata"] ?? "");
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
			$wd_hata = sprintf(wd__("Could not change development mode: %s"), $d["hata"] ?? "");
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
				$wd_hata = wd__("No DNS zone for this domain in the panel.");
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
					sprintf(
						wd__("Could not push DNS: %s"),
						implode("; ", array_slice($d["hatalar"] ?? [wd__("unknown error")], 0, 3)),
					);
			}
		}
	}
}

if ($wd_hata === "" && isset($_GET["durum"])) {
	$d = htmlspecialchars((string) ($_GET["d"] ?? ""), ENT_QUOTES, "UTF-8");
	switch ($_GET["durum"]) {
		case "jeton-ok":
			$wd_bilgi = wd__("Cloudflare token verified and saved.");
			break;
		case "jeton-silindi":
			$wd_bilgi = wd__("Token deleted.");
			break;
		case "ip-ok":
			$wd_bilgi = sprintf(
				wd__("Cloudflare IP list updated (%d IPv4 ranges). Real visitor IPs are being read correctly again."),
				(int) ($_GET["v4"] ?? 0),
			);
			break;
		case "onbellek":
			$wd_bilgi = sprintf(wd__("Cache purged: %s"), $d);
			break;
		case "gelistirme-on":
			$wd_bilgi = sprintf(wd__("Development mode enabled (3 hours): %s"), $d);
			break;
		case "gelistirme-off":
			$wd_bilgi = sprintf(wd__("Development mode disabled: %s"), $d);
			break;
		case "dns":
			$wd_bilgi = sprintf(
				wd__("DNS pushed: %s — %d added, %d updated."),
				$d,
				(int) ($_GET["e"] ?? 0),
				(int) ($_GET["g"] ?? 0),
			);
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
