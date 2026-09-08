<?php
/**
 * WebDanışmanı — "Yönlendirmeler ve Site Kuralları" sayfası
 * Kurulum yeri: /usr/local/hestia/web/list/yonlendirme/index.php
 *
 * Yol yönlendirmeleri, güvenlik başlıkları, hotlink koruması ve IP engelleme.
 * cPanel "Redirects" + Plesk "Apache & nginx Settings" karşılığı.
 *
 * GÜVENLİK
 *  - Kullanıcı adı DAİMA oturumdan; alan adı sahipliği burada ve ayrıca
 *    wd-yonlendirme içinde doğrulanır.
 *  - Kurallar JSON olarak STDIN'den geçirilir; kabuk satırına konmaz.
 *  - Her değer wd-yonlendirme tarafında düzenli ifadelerle süzülür ve
 *    nginx yapılandırması yazıldıktan sonra `nginx -t` ile doğrulanır;
 *    geçersizse eski hâl geri yüklenir. Bozuk bir parça TÜM siteleri
 *    düşüreceği için bu adım atlanamaz.
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
		return [1, "", "süreç başlatılamadı"];
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
		$wd_hata = "Geçersiz alan adı.";
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
			$wd_hata = "Kurallar kaldırılamadı: " . ($d["hata"] ?? "");
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
			$wd_hata = $d["hata"] ?? trim($err ?: "Kaydedilemedi.");
		}
	}
}

if ($wd_hata === "" && isset($_GET["durum"])) {
	$wd_bilgi =
		$_GET["durum"] === "silindi"
			? "Bu alan adının tüm özel kuralları kaldırıldı."
			: "Kurallar kaydedildi ve yayına alındı (" .
				(int) ($_GET["k"] ?? 0) .
				" yönlendirme).";
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
