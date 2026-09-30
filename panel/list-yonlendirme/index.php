<?php
/**
 * WebDanışmanı — "Redirects and Site Rules" page
 * Installs to: /usr/local/hestia/web/list/yonlendirme/index.php
 *
 * Path redirects, security headers, hotlink protection, and IP blocking.
 * Counterpart to cPanel "Redirects" + Plesk "Apache & nginx Settings".
 *
 * SECURITY
 *  - Username ALWAYS from the session; domain ownership is checked here and
 *    again inside wd-yonlendirme.
 *  - Rules are passed as JSON on STDIN; never on the shell command line.
 *  - Each value is filtered with regexes in wd-yonlendirme, and after writing
 *    nginx config `nginx -t` validates it; on failure the old state is
 *    restored. A bad fragment would take ALL sites down, so this step is mandatory.
 */

ob_start();
$TAB = "WEB";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-helpers.php";

$wd_user = empty($_SESSION["look"]) ? $_SESSION["user"] : $_SESSION["look"];
$wd_doms = wd_web_domains($wd_user);

const WD_YON = "/usr/local/hestia/wd/bin/wd-yonlendirme";

$wd_hata = "";
$wd_bilgi = "";

function wd_yon_calistir(string $mod, string $user, string $domain, ?string $json = null): array {
	$cmd =
		"/usr/bin/sudo " . WD_YON . " " . escapeshellarg($mod) . " " .
		escapeshellarg($user) . " " . escapeshellarg($domain);
	$desc = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
	$p = proc_open($cmd, $desc, $boru);
	if (!is_resource($p)) {
		return [1, "", wd__("could not start process")];
	}
	if ($json !== null) {
		fwrite($boru[0], $json);
	}
	fclose($boru[0]);
	$out = stream_get_contents($boru[1]);
	$err = stream_get_contents($boru[2]);
	fclose($boru[1]);
	fclose($boru[2]);
	return [proc_close($p), $out, $err];
}

// --- Seçili alan adı: HAM değer doğrulanır, geri düşüş yalnızca görüntüleme için ---
$wd_ham_domain = isset($_POST["v_domain"])
	? (string) $_POST["v_domain"]
	: (string) ($_GET["domain"] ?? "");
$wd_domain = $wd_ham_domain;
if ($wd_domain === "" || !isset($wd_doms[$wd_domain])) {
	$wd_domain = !empty($wd_doms) ? (string) array_key_first($wd_doms) : "";
}

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);

	if ($wd_ham_domain === "" || !isset($wd_doms[$wd_ham_domain])) {
		$wd_hata = wd__("Invalid domain.");
	} else {
		$wd_domain = $wd_ham_domain;
		$islem = $_POST["islem"] ?? "kaydet";

		if ($islem === "sil") {
			[$rc, $out] = wd_yon_calistir("sil", $wd_user, $wd_domain);
			$d = json_decode($out, true);
			if (!empty($d["ok"])) {
				header("Location: /list/yonlendirme/?domain=" . urlencode($wd_domain) . "&durum=silindi");
				exit();
			}
			$wd_hata = sprintf(wd__("Could not remove rules: %s"), $d["hata"] ?? "");
		} else {
			// Formdan gelen satırlar kurallara çevrilir.
			$yollar = [];
			$kaynaklar = $_POST["v_kaynak"] ?? [];
			$hedefler = $_POST["v_hedef"] ?? [];
			$kodlar = $_POST["v_kod"] ?? [];
			$tamlar = $_POST["v_tam"] ?? [];
			if (is_array($kaynaklar)) {
				foreach ($kaynaklar as $i => $k) {
					$k = trim((string) $k);
					$h = trim((string) ($hedefler[$i] ?? ""));
					if ($k === "" || $h === "") {
						continue;
					}
					$yollar[] = [
						"kaynak" => $k,
						"hedef" => $h,
						"kod" => ($kodlar[$i] ?? "301") === "302" ? "302" : "301",
						"tam" => !empty($tamlar[$i]),
					];
				}
			}

			$ipler = array_values(array_filter(array_map(
				"trim",
				preg_split('/[\s,]+/', (string) ($_POST["v_ip"] ?? "")) ?: [],
			), "strlen"));

			$hl_izinli = array_values(array_filter(array_map(
				"trim",
				preg_split('/[\s,]+/', (string) ($_POST["v_hotlink_izinli"] ?? "")) ?: [],
			), "strlen"));

			$kurallar = [
				"yollar" => $yollar,
				"basliklar" => array_values(array_filter(
					(array) ($_POST["v_baslik"] ?? []),
					function ($b) {
						return in_array($b, ["nosniff", "frame", "referrer", "hsts"], true);
					},
				)),
				"engelli_ip" => $ipler,
				"hotlink" => !empty($_POST["v_hotlink"]),
				"hotlink_izinli" => $hl_izinli,
			];

			[$rc, $out, $err] = wd_yon_calistir(
				"yaz",
				$wd_user,
				$wd_domain,
				json_encode($kurallar),
			);
			$d = json_decode($out, true);
			if (!empty($d["ok"])) {
				header(
					"Location: /list/yonlendirme/?domain=" . urlencode($wd_domain) .
						"&durum=kaydedildi&k=" . (int) ($d["kural"] ?? 0),
				);
				exit();
			}
			$wd_hata = $d["hata"] ?? trim($err ?: wd__("Could not save."));
		}
	}
}

if ($wd_hata === "" && isset($_GET["durum"])) {
	$wd_bilgi =
		$_GET["durum"] === "silindi"
			? wd__("All custom rules for this domain were removed.")
			: sprintf(wd__("Rules saved and published (%d redirect(s))."), (int) ($_GET["k"] ?? 0));
}

// --- Mevcut kurallar ---
$wd_kurallar = ["yollar" => [], "basliklar" => [], "engelli_ip" => [],
	"hotlink" => false, "hotlink_izinli" => []];
if ($wd_domain !== "") {
	[$rc, $out] = wd_yon_calistir("oku", $wd_user, $wd_domain);
	$d = json_decode($out, true);
	if (!empty($d["ok"]) && is_array($d["kurallar"] ?? null)) {
		$wd_kurallar = array_merge($wd_kurallar, $d["kurallar"]);
	}
}

render_page($user, $TAB, "list_yonlendirme");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
