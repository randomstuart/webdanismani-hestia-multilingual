<?php
/**
 * WebDanışmanı — ek modüller ortak katmanı
 *
 * Kurulum yeri: /usr/local/hestia/web/inc/wd-modul.php
 *
 * YENİ bir dosyadır; HestiaCP güncellemeleri mevcut dosyaları değiştirir,
 * yenilerini silmez. wd-helpers.php'ye DOKUNMAZ; onu yükleyip üstüne ekler.
 *
 * Buradaki modüller (kur-modul.sh ile kurulur):
 *   phpayar  PHP Ayarları              (cPanel MultiPHP INI Editor)
 *   waf      Uygulama güvenlik duvarı  (ModSecurity karşılığı, hafif)
 *   erisim   Site erişim izleme        (uptime)
 *   wp       WordPress araçları        (WP Toolkit)
 *   klon     Klon / staging
 *   kur      Uygulama kurucu           (Softaculous; kendi kataloğunuz)
 *   git      Git dağıtım
 *   node     Node.js uygulamaları      (Application Manager)
 *   yedek    Yedek gezgini             (JetBackup; tek dosya geri yükleme)
 *   bayi     Bayi katmanı              (WHM reseller)
 *   (kur yalnız yönetici: kataloğa paket yüklemek ve kurmak yönetici işidir)
 *
 * ORTAK DESEN
 *   Panel `hestiaweb` olarak çalışır ve kullanıcı dosyalarını okuyamaz. Her
 *   modülün root yetkili bir toplayıcısı vardır (/usr/local/hestia/wd/bin/wd-*),
 *   sudoers yalnızca o betiklere izin verir. Yazma işlemleri JSON olarak
 *   STDIN'den geçer — kabuk satırına asla ham kullanıcı verisi konmaz.
 */

require_once __DIR__ . "/wd-helpers.php";

if (!defined("WD_MODUL_VERSION")) {
	define("WD_MODUL_VERSION", "1.0");
}
if (!defined("WD_MODUL_BIN")) {
	define("WD_MODUL_BIN", "/usr/local/hestia/wd/bin");
}
if (!defined("WD_MODUL_CACHE")) {
	define("WD_MODUL_CACHE", "/usr/local/hestia/wd/cache");
}

/** Etkin kullanıcı: yönetici "look" ile başkasına bakıyorsa o kullanıcı. */
function wd_modul_kullanici(): string {
	return empty($_SESSION["look"]) ? (string) ($_SESSION["user"] ?? "") : (string) $_SESSION["look"];
}

/** Gerçek yönetici bağlamı (başkasına bakmıyorken). */
function wd_modul_admin(): bool {
	return ($_SESSION["userContext"] ?? "") === "admin" && empty($_SESSION["look"]);
}

/**
 * Root betiği sudo ile çalıştırır.
 *
 * @param string      $betik  wd-bin altındaki betik adı (wd-phpayar gibi)
 * @param array       $args   komut satırı argümanları — her biri escapeshellarg'dan geçer
 * @param string|null $stdin  verilirse alt sürecin STDIN'ine yazılır (JSON)
 * @return array [rc, stdout, stderr]
 */
function wd_modul_calistir(string $betik, array $args = [], ?string $stdin = null, int $zaman_asimi = 180): array {
	if (!preg_match('/^wd-[a-z0-9-]+$/', $betik)) {
		return [1, "", wd__("invalid script name")];
	}
	$yol = WD_MODUL_BIN . "/" . $betik;
	if (!is_file($yol)) {
		return [1, "", sprintf(wd__("script not installed: %s"), $betik)];
	}
	$cmd = "/usr/bin/sudo " . $yol;
	foreach ($args as $a) {
		$cmd .= " " . escapeshellarg((string) $a);
	}
	$desc = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
	$env = wd_sudo_env();
	$p = proc_open($cmd, $desc, $boru, null, $env);
	if (!is_resource($p)) {
		return [1, "", wd__("could not start process")];
	}
	if ($stdin !== null) {
		fwrite($boru[0], $stdin);
	}
	fclose($boru[0]);
	// Long jobs (clone, backup restore): stream timeout instead of blocking forever.
	stream_set_timeout($boru[1], $zaman_asimi);
	$out = stream_get_contents($boru[1]);
	$err = stream_get_contents($boru[2]);
	fclose($boru[1]);
	fclose($boru[2]);
	return [proc_close($p), (string) $out, (string) $err];
}

/**
 * Environment for sudo helpers: pass panel language so Python gettext matches UI.
 *
 * @return array<string, string>
 */
function wd_sudo_env(): array {
	$env = [];
	foreach ($_ENV as $k => $v) {
		if (is_string($k) && is_string($v)) {
			$env[$k] = $v;
		}
	}
	foreach ($_SERVER as $k => $v) {
		if (is_string($k) && is_string($v) && !isset($env[$k]) && preg_match('/^[A-Z_][A-Z0-9_]*$/', $k)) {
			$env[$k] = $v;
		}
	}
	$lang = strtolower((string) ($_SESSION["language"] ?? $_SESSION["LANGUAGE"] ?? "en"));
	$lang = preg_replace("/[^a-z]/", "", $lang) ?: "en";
	$env["WD_LANG"] = $lang;
	$env["LANGUAGE"] = $lang;
	$env["LANG"] = $lang . "_" . strtoupper($lang) . ".UTF-8";
	$env["LC_ALL"] = $env["LANG"];
	$env["LC_MESSAGES"] = $env["LANG"];
	return $env;
}

/**
 * Betiği çalıştırıp JSON çıktısını çözer. Çözülemezse null.
 * Betikler hata durumunda da {"ok":false,"hata":"..."} döner; çağıran
 * "ok" anahtarına bakar.
 */
function wd_modul_json(string $betik, array $args = [], ?string $stdin = null, int $zaman_asimi = 180): ?array {
	[$rc, $out, $err] = wd_modul_calistir($betik, $args, $stdin, $zaman_asimi);
	$d = json_decode(trim($out), true);
	if (is_array($d)) {
		if (!isset($d["ok"])) {
			$d["ok"] = $rc === 0;
		}
		return $d;
	}
	$hata = trim($err) !== "" ? trim($err) : trim($out);
	return ["ok" => false, "hata" => $hata !== "" ? mb_substr($hata, 0, 300) : "betik yanıt vermedi (kod " . $rc . ")"];
}

/** Önbellek dosyasını DOĞRUDAN okur (0644); süreç başlatmaz. */
function wd_modul_onbellek(string $ad): ?array {
	static $cache = [];
	if (array_key_exists($ad, $cache)) {
		return $cache[$ad];
	}
	$yol = WD_MODUL_CACHE . "/" . preg_replace('/[^a-z0-9._-]/', "", $ad) . ".json";
	$cache[$ad] = null;
	if (is_readable($yol)) {
		$ham = @file_get_contents($yol);
		$d = $ham !== false ? json_decode($ham, true) : null;
		if (is_array($d)) {
			$cache[$ad] = $d;
		}
	}
	return $cache[$ad];
}

/** Önbellek dosyasının yaşı (saniye); yoksa null. */
function wd_modul_onbellek_yas(string $ad): ?int {
	$yol = WD_MODUL_CACHE . "/" . preg_replace('/[^a-z0-9._-]/', "", $ad) . ".json";
	if (!is_file($yol)) {
		return null;
	}
	$t = @filemtime($yol);
	return $t ? max(0, time() - $t) : null;
}

/** Kullanıcı bayi mi? Yalnızca bayi ADLARINI içeren 0644 önbellekten okur. */
function wd_modul_bayi_mi(string $user): bool {
	$d = wd_modul_onbellek("bayi");
	if (!$d || empty($d["bayiler"]) || !is_array($d["bayiler"])) {
		return false;
	}
	return in_array($user, $d["bayiler"], true);
}

/**
 * Modül kaydı. Araçlar sayfası ve kenar çubuğu buradan beslenir; yeni modül
 * eklemek için yalnızca bu diziye satır eklenir.
 *
 *   grup : web | backup | account | server   (Araçlar sayfasındaki grup anahtarı)
 *   tab  : $TAB değeri (kenar çubuğunda etkin sekme)
 */
function wd_modul_listesi(bool $is_admin, string $user): array {
	$hepsi = [
		["kod" => "phpayar", "ad" => wd__("PHP Settings"), "kisa" => "PHP", "ikon" => "fa-code", "href" => "/list/phpayar/", "tab" => "PHPAYAR", "grup" => "web", "yetki" => "hepsi", "aciklama" => wd__("Upload size, timeouts, error display, and PHP error log")],
		["kod" => "waf", "ad" => wd__("Web Application Firewall"), "kisa" => "WAF", "ikon" => "fa-shield-virus", "href" => "/list/waf/", "tab" => "WAF", "grup" => "web", "yetki" => "hepsi", "aciklama" => wd__("Blocks bad bots and attack patterns; rate-limits login pages")],
		["kod" => "erisim", "ad" => wd__("Uptime Monitor"), "kisa" => wd__("UPTIME"), "ikon" => "fa-heart-pulse", "href" => "/list/erisim/", "tab" => "ERISIM", "grup" => "web", "yetki" => "hepsi", "aciklama" => wd__("External HTTP check every 5 minutes; notify after failures")],
		["kod" => "wp", "ad" => wd__("WordPress Tools"), "kisa" => "WP", "ikon" => "fa-w", "href" => "/list/wp/", "tab" => "WP", "grup" => "web", "yetki" => "hepsi", "aciklama" => wd__("Updates, auto-update, integrity check, one-click admin login")],
		["kod" => "klon", "ad" => wd__("Clone / Staging"), "kisa" => wd__("CLONE"), "ikon" => "fa-clone", "href" => "/list/klon/", "tab" => "KLON", "grup" => "web", "yetki" => "hepsi", "aciklama" => wd__("Clone a site for testing, then push changes live")],
		["kod" => "kur", "ad" => wd__("App Installer"), "kisa" => wd__("INSTALL"), "ikon" => "fa-download", "href" => "/list/kur/", "tab" => "KUR", "grup" => "server", "yetki" => "admin", "aciklama" => wd__("Install catalog apps onto a selected domain")],
		["kod" => "git", "ad" => wd__("Git Deploy"), "kisa" => "GIT", "ikon" => "fa-code-branch", "href" => "/list/git/", "tab" => "GIT", "grup" => "web", "yetki" => "hepsi", "aciklama" => wd__("Clone a repo into a site; manual or automatic pull")],
		["kod" => "node", "ad" => wd__("Node.js Apps"), "kisa" => "NODE", "ikon" => "fa-cube", "href" => "/list/node/", "tab" => "NODE", "grup" => "web", "yetki" => "hepsi", "aciklama" => wd__("Run a Node.js app as a service and bind it to a domain")],
		["kod" => "yedek", "ad" => wd__("Backup Browser"), "kisa" => wd__("BROWSER"), "ikon" => "fa-box-archive", "href" => "/list/yedek/", "tab" => "YEDEKGEZGIN", "grup" => "backup", "yetki" => "hepsi", "aciklama" => wd__("Browse a backup archive; restore a single file or folder")],
		["kod" => "bayi", "ad" => wd__("Reseller Management"), "kisa" => wd__("RESELLER"), "ikon" => "fa-users-gear", "href" => "/list/bayi/", "tab" => "BAYI", "grup" => "server", "yetki" => "bayi", "aciklama" => wd__("Create, suspend, and re-package your own customers")],
	];

	$gorunen = [];
	$bayi = !$is_admin && wd_modul_bayi_mi($user);
	foreach ($hepsi as $m) {
		if (!is_dir($_SERVER["DOCUMENT_ROOT"] . "/list/" . $m["kod"])) {
			continue; // skip modules not installed
		}
		if ($m["yetki"] === "admin" && !$is_admin) {
			continue;
		}
		if ($m["yetki"] === "bayi") {
			if (!$is_admin && !$bayi) {
				continue;
			}
			if ($bayi) {
				$m["grup"] = "account";
				$m["ad"] = wd__("My Customers");
				$m["kisa"] = wd__("CUSTOMERS");
			}
		}
		$gorunen[] = $m;
	}
	return $gorunen;
}

/** Modül tanımını koduyla döner. */
function wd_modul_bul(string $kod): ?array {
	foreach (wd_modul_listesi(true, "") as $m) {
		if ($m["kod"] === $kod) {
			return $m;
		}
	}
	return null;
}

/**
 * Modül CSS'ini bir kez yükler. Tema derlemesine DOKUNMAZ: ayrı dosya,
 * ayrı önbellek anahtarı. Sayfa şablonunun başında çağrılır.
 */
function wd_modul_css(): void {
	static $yuklendi = false;
	if ($yuklendi) {
		return;
	}
	$yuklendi = true;
	$yol = $_SERVER["DOCUMENT_ROOT"] . "/css/themes/custom/wd-modul.css";
	$v = is_file($yol) ? (string) @filemtime($yol) : WD_MODUL_VERSION;
	echo '<link rel="stylesheet" href="/css/themes/custom/wd-modul.css?v=' . wd_e($v) . '">' . "\n";
}

/**
 * Sayfa başlığı bloğu (başlık + alt yazı + isteğe bağlı eylemler).
 * Her modül aynı görünsün diye tek yerden basılır.
 */
function wd_modul_baslik(string $baslik, string $alt, string $eylemler_html = ""): void {
	echo '<div class="wd-page-head"><div><h1 class="wd-title">' . wd_e($baslik) . '</h1>';
	echo '<p class="wd-subtitle">' . wd_e($alt) . "</p></div>";
	if ($eylemler_html !== "") {
		echo '<div class="wd-page-actions">' . $eylemler_html . "</div>";
	}
	echo "</div>\n";
}

/** Bilgi / hata notu. $tur: ok | err | warn | (boş) */
function wd_modul_not(string $metin, string $tur = "", bool $html = false): void {
	$ikon = ["ok" => "fa-circle-check", "err" => "fa-circle-exclamation", "warn" => "fa-triangle-exclamation"][$tur] ?? "fa-circle-info";
	$sinif = $tur !== "" ? " wd-note-" . $tur : "";
	echo '<div class="wd-note' . $sinif . '"><i class="fas ' . $ikon . '"></i><span>' . ($html ? $metin : wd_e($metin)) . "</span></div>\n";
}

/** GET ?durum= → bilgi mesajı eşlemesi (yönlendirme sonrası tek seferlik). */
function wd_modul_durum_mesaji(array $eslem): string {
	$d = (string) ($_GET["durum"] ?? "");
	if ($d === "" || !isset($eslem[$d])) {
		return "";
	}
	$m = $eslem[$d];
	// {d} yer tutucusu: yönlendirmede taşınan güvenli değer (alan adı vb.)
	$deger = (string) ($_GET["d"] ?? "");
	return str_replace("{d}", $deger, $m);
}

/** Alan adı seçimi: HAM değer listede yoksa ilk alan adına düşer. */
function wd_modul_domain_sec(array $doms, string $ham): string {
	if ($ham !== "" && isset($doms[$ham])) {
		return $ham;
	}
	return !empty($doms) ? (string) array_key_first($doms) : "";
}

/** Alan adı seçim kutusu (birden fazla alan adı varsa). */
function wd_modul_domain_kutusu(array $doms, string $secili, string $yol, array $ek_get = []): void {
	if (count($doms) < 2) {
		return;
	}
	echo '<div class="wd-card"><div class="wd-card-head">' . wd_esc__("Domain") . '</div><div class="wd-card-body wd-card-body-pad">';
	echo '<form method="get" action="' . wd_e($yol) . '" class="wd-inline-select">';
	foreach ($ek_get as $k => $v) {
		echo '<input type="hidden" name="' . wd_e($k) . '" value="' . wd_e($v) . '">';
	}
	echo '<select class="form-select" name="domain" onchange="this.form.submit()">';
	foreach ($doms as $d => $_) {
		echo '<option value="' . wd_e($d) . '"' . ($d === $secili ? " selected" : "") . ">" . wd_e($d) . "</option>";
	}
	echo "</select>";
	echo '<noscript><button type="submit" class="button button-secondary">' . wd_esc__("Select") . "</button></noscript>";
	echo "</form></div></div>\n";
}

/** Gizli form alanları: token + ok + islem + domain. */
function wd_modul_form_gizli(string $islem, string $domain = ""): string {
	$s = '<input type="hidden" name="token" value="' . wd_e($_SESSION["token"] ?? "") . '">';
	$s .= '<input type="hidden" name="ok" value="1">';
	$s .= '<input type="hidden" name="islem" value="' . wd_e($islem) . '">';
	if ($domain !== "") {
		$s .= '<input type="hidden" name="v_domain" value="' . wd_e($domain) . '">';
	}
	return $s;
}

/** Format a unix timestamp for display. */
function wd_modul_tarih(?int $ts): string {
	if (!$ts) {
		return "—";
	}
	return date("Y-m-d H:i", $ts);
}

/** Short duration ("3 min", "2 h 10 min", "5 days"). */
function wd_modul_sure(?int $sn): string {
	if ($sn === null) {
		return "—";
	}
	if ($sn < 60) {
		return $sn . " " . wd__("s");
	}
	if ($sn < 3600) {
		return intdiv($sn, 60) . " " . wd__("min");
	}
	if ($sn < 86400) {
		$h = intdiv($sn, 3600);
		$m = intdiv($sn % 3600, 60);
		return $h . " " . wd__("h") . ($m > 0 ? " " . $m . " " . wd__("min") : "");
	}
	$g = intdiv($sn, 86400);
	$h = intdiv($sn % 86400, 3600);
	return $g . " " . wd_n__("day", "days", $g) . ($h > 0 ? " " . $h . " " . wd__("h") : "");
}

/** Format a percentage. */
function wd_modul_yuzde(?float $v, int $ondalik = 1): string {
	if ($v === null) {
		return "—";
	}
	return number_format($v, $ondalik, ".", ",") . "%";
}

/** Bayt → okunur birim (wd_bayt varsa onu kullanır). */
function wd_modul_bayt(int $b): string {
	if (function_exists("wd_bayt")) {
		return wd_bayt($b);
	}
	$birim = ["B", "KB", "MB", "GB", "TB"];
	$i = 0;
	$v = (float) $b;
	while ($v >= 1024 && $i < 4) {
		$v /= 1024;
		$i++;
	}
	return number_format($v, $i === 0 ? 0 : 1, ".", ",") . " " . $birim[$i];
}

/**
 * Kenar çubuğu rozetleri: yalnızca önbellekten, süreç başlatmadan.
 * erisim -> çökük site sayısı, wp -> bekleyen güncelleme sayısı.
 */
function wd_modul_rozet(string $kod, bool $is_admin, string $user): ?int {
	if ($kod === "erisim") {
		$d = wd_modul_onbellek("erisim");
		if (!$d || empty($d["siteler"])) {
			return null;
		}
		$n = 0;
		foreach ($d["siteler"] as $s) {
			if (!$is_admin && ($s["user"] ?? "") !== $user) {
				continue;
			}
			if (($s["durum"] ?? "") === "down") {
				$n++;
			}
		}
		return $n;
	}
	if ($kod === "wp") {
		$d = wd_modul_onbellek("wp");
		if (!$d || empty($d["siteler"])) {
			return null;
		}
		$n = 0;
		foreach ($d["siteler"] as $s) {
			if (!$is_admin && ($s["user"] ?? "") !== $user) {
				continue;
			}
			$n += (int) ($s["guncelleme_sayisi"] ?? 0);
		}
		return $n;
	}
	return null;
}

/** Ortak "ok/hata" JSON yanıtı (AJAX uçları için). */
function wd_modul_json_yanit(array $veri, int $kod = 200): void {
	http_response_code($kod);
	header("Content-Type: application/json; charset=utf-8");
	header("Cache-Control: no-store");
	echo json_encode($veri, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit();
}
