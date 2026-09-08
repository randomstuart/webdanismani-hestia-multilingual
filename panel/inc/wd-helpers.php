<?php
/**
 * WebDanışmanı — HestiaCP panel eklentisi: yardımcı fonksiyonlar
 *
 * Kurulum yeri: /usr/local/hestia/web/inc/wd-helpers.php
 * Bu YENİ bir dosyadır; HestiaCP güncellemeleri mevcut dosyaları değiştirir,
 * yenilerini silmez. Dolayısıyla güncellemeden etkilenmez.
 *
 * Buradaki tüm metrikler /proc'tan okunur (hestiaweb kullanıcısı okuyabilir)
 * veya $panel dizisinden gelir. Uydurma/varsayılan değer ÜRETİLMEZ — veri
 * yoksa null döner ve arayüz o kartı göstermez.
 */

if (!defined("WD_PANEL_VERSION")) {
	define("WD_PANEL_VERSION", "1.0");
}

/** İşlemci kullanım yüzdesi. İki örnekleme arası fark alınır. */
function wd_cpu_percent(int $sample_us = 120000): ?float {
	$read = static function (): ?array {
		$line = @file("/proc/stat");
		if (!$line || !isset($line[0])) {
			return null;
		}
		$p = preg_split('/\s+/', trim($line[0]));
		if (!$p || $p[0] !== "cpu" || count($p) < 6) {
			return null;
		}
		$vals = array_map("intval", array_slice($p, 1, 8));
		return ["total" => array_sum($vals), "idle" => ($vals[3] ?? 0) + ($vals[4] ?? 0)];
	};

	$a = $read();
	if ($a === null) {
		return null;
	}
	usleep($sample_us);
	$b = $read();
	if ($b === null) {
		return null;
	}

	$dt = $b["total"] - $a["total"];
	$di = $b["idle"] - $a["idle"];
	if ($dt <= 0) {
		return null;
	}
	return round(max(0, min(100, (1 - $di / $dt) * 100)), 1);
}

/** Bellek kullanımı (kB cinsinden). */
function wd_memory(): ?array {
	$lines = @file("/proc/meminfo");
	if (!$lines) {
		return null;
	}
	$m = [];
	foreach ($lines as $l) {
		if (preg_match('/^(\w+):\s+(\d+)/', $l, $x)) {
			$m[$x[1]] = (int) $x[2];
		}
	}
	if (empty($m["MemTotal"])) {
		return null;
	}
	$total = $m["MemTotal"];
	$avail = $m["MemAvailable"] ?? ($m["MemFree"] ?? 0) + ($m["Cached"] ?? 0) + ($m["Buffers"] ?? 0);
	$used = max(0, $total - $avail);
	return [
		"total_kb" => $total,
		"used_kb" => $used,
		"pct" => $total > 0 ? round($used / $total * 100, 1) : null,
	];
}

/** Sistem yükü ve çekirdek sayısı. */
function wd_load(): ?array {
	$raw = @file_get_contents("/proc/loadavg");
	if (!$raw) {
		return null;
	}
	$p = preg_split('/\s+/', trim($raw));
	if (count($p) < 3) {
		return null;
	}
	$cores = wd_cpu_cores();
	$l1 = (float) $p[0];
	return [
		"l1" => $l1,
		"l5" => (float) $p[1],
		"l15" => (float) $p[2],
		"cores" => $cores,
		"pct" => $cores > 0 ? round(min(100, $l1 / $cores * 100), 1) : null,
	];
}

function wd_cpu_cores(): int {
	$raw = @file_get_contents("/proc/cpuinfo");
	if ($raw) {
		$n = substr_count($raw, "processor\t:");
		if ($n < 1) {
			$n = preg_match_all('/^processor\s*:/m', $raw);
		}
		if ($n > 0) {
			return $n;
		}
	}
	return 1;
}

/** Sunucu çalışma süresi (saniye). */
function wd_uptime_seconds(): ?int {
	$raw = @file_get_contents("/proc/uptime");
	if (!$raw) {
		return null;
	}
	$p = preg_split('/\s+/', trim($raw));
	return isset($p[0]) ? (int) (float) $p[0] : null;
}

/** Saniyeyi Türkçe okunur süreye çevirir. */
function wd_human_uptime(?int $sec): string {
	if ($sec === null) {
		return "—";
	}
	$d = intdiv($sec, 86400);
	$h = intdiv($sec % 86400, 3600);
	$m = intdiv($sec % 3600, 60);
	if ($d > 0) {
		return $d . " gün" . ($h > 0 ? " " . $h . " saat" : "");
	}
	if ($h > 0) {
		return $h . " saat" . ($m > 0 ? " " . $m . " dk" : "");
	}
	return max(1, $m) . " dakika";
}

/**
 * Kota yüzdesi. "unlimited" ise null döner (çubuk çizilmez —
 * sınırsız bir değeri yüzdeye çevirmek yanıltıcı olur).
 */
function wd_quota_pct($used, $limit): ?float {
	if ($limit === "unlimited" || $limit === "" || $limit === null) {
		return null;
	}
	$limit = (float) $limit;
	if ($limit <= 0) {
		return null;
	}
	return round(min(100, (float) $used / $limit * 100), 1);
}

/** Yüzdeye göre durum sınıfı: normal / uyarı / kritik. */
function wd_level(?float $pct): string {
	if ($pct === null) {
		return "wd-ok";
	}
	if ($pct >= 90) {
		return "wd-crit";
	}
	if ($pct >= 75) {
		return "wd-warn";
	}
	return "wd-ok";
}

/**
 * Kullanıcının son başarılı girişi.
 *
 * NOT: /usr/local/hestia/data/users dizini root:root drwxr-x--- olduğu için
 * paneli çalıştıran `hestiaweb` kullanıcısı auth.log'u DOĞRUDAN OKUYAMAZ.
 * Bu yüzden sudo'lu v-list-user-auth-log kullanılır.
 */
function wd_last_login(string $user): ?array {
	$out = [];
	$rc = 0;
	exec(HESTIA_CMD . "v-list-user-auth-log " . escapeshellarg($user) . " json", $out, $rc);
	$data = json_decode(implode("", $out), true);
	if (!is_array($data) || empty($data)) {
		return null;
	}

	$current = $_SESSION["token"] ?? "";
	$fallback = null;

	// Kayıtlar kronolojik; sondan başa tara
	foreach (array_reverse($data) as $row) {
		if (($row["ACTION"] ?? "") !== "login" || ($row["STATUS"] ?? "") !== "success") {
			continue;
		}
		$item = [
			"date" => $row["DATE"] ?? "",
			"time" => $row["TIME"] ?? "",
			"ip" => $row["IP"] ?? "",
		];
		if ($fallback === null) {
			$fallback = $item;
		}
		// Şu anki oturumu atla — "son giriş" bir öncekini göstermeli
		if (($row["SESSION"] ?? "") !== $current) {
			return $item;
		}
	}
	return $fallback;
}

/** gg.aa.yyyy biçimi. */
function wd_date_tr(string $ymd): string {
	$p = explode("-", $ymd);
	return count($p) === 3 ? $p[2] . "." . $p[1] . "." . $p[0] : $ymd;
}

/**
 * Kullanıcının web alan adları (önbellekli). Birincil alan adı, IP ve
 * Let's Encrypt durumu buradan çıkarılır.
 */
function wd_web_domains(string $user): array {
	static $cache = [];
	if (isset($cache[$user])) {
		return $cache[$user];
	}
	$out = [];
	$rc = 0;
	exec(HESTIA_CMD . "v-list-web-domains " . escapeshellarg($user) . " json", $out, $rc);
	$data = json_decode(implode("", $out), true);
	$cache[$user] = is_array($data) ? $data : [];
	return $cache[$user];
}

/** Kullanıcının mail alan adları. */
function wd_mail_domains(string $user): array {
	static $cache = [];
	if (isset($cache[$user])) {
		return $cache[$user];
	}
	$out = [];
	$rc = 0;
	exec(HESTIA_CMD . "v-list-mail-domains " . escapeshellarg($user) . " json", $out, $rc);
	$data = json_decode(implode("", $out), true);
	$cache[$user] = is_array($data) ? $data : [];
	return $cache[$user];
}

/**
 * Kullanıcının İLK web alan adı — üst çubuktaki "Hızlı Kurulum" bağlantısı için.
 *
 * Bu fonksiyon HER sayfa yüklemesinde çalıştığı için pahalı olmamalı.
 * web.conf doğrudan okunamıyor (hestiaweb'in yetkisi yok), bu yüzden sudo'lu
 * komutun sonucu OTURUMDA 5 dakika önbelleklenir.
 */
function wd_first_web_domain(string $user): ?string {
	$key = "wd_first_domain_" . $user;
	$at = "wd_first_domain_at_" . $user;

	if (isset($_SESSION[$at]) && time() - (int) $_SESSION[$at] < 300) {
		return $_SESSION[$key] !== "" ? $_SESSION[$key] : null;
	}

	$out = [];
	$rc = 0;
	exec(HESTIA_CMD . "v-list-web-domains " . escapeshellarg($user) . " json", $out, $rc);
	$data = json_decode(implode("", $out), true);
	$first = is_array($data) && !empty($data) ? (string) array_key_first($data) : "";

	$_SESSION[$key] = $first;
	$_SESSION[$at] = time();

	return $first !== "" ? $first : null;
}

/** Sunucudaki tüm kullanıcı adları (yalnızca yönetici bağlamında anlamlı). */
function wd_kullanici_listesi(): array {
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}
	$out = [];
	$rc = 0;
	exec(HESTIA_CMD . "v-list-users json", $out, $rc);
	$data = json_decode(implode("", $out), true);
	$cache = is_array($data) ? array_keys($data) : [];
	return $cache;
}

/** Kullanıcının DNS bölgeleri. */
function wd_dns_domains(string $user): array {
	static $cache = [];
	if (isset($cache[$user])) {
		return $cache[$user];
	}
	$out = [];
	$rc = 0;
	exec(HESTIA_CMD . "v-list-dns-domains " . escapeshellarg($user) . " json", $out, $rc);
	$data = json_decode(implode("", $out), true);
	$cache[$user] = is_array($data) ? $data : [];
	return $cache[$user];
}

/** Birincil (ilk) alan adı ve SSL durumu. */
function wd_primary_domain(string $user): ?array {
	$doms = wd_web_domains($user);
	if (empty($doms)) {
		return null;
	}
	$name = array_key_first($doms);
	$d = $doms[$name];
	return [
		"domain" => $name,
		"ip" => $d["IP"] ?? "",
		"ssl" => ($d["SSL"] ?? "no") === "yes",
		"letsencrypt" => ($d["LETSENCRYPT"] ?? "no") === "yes",
	];
}

/**
 * Site başına CPU / RAM kullanımı.
 *
 * Her web sitesi kendi PHP-FPM havuzunda çalışır ve işçi süreçler
 * `php-fpm: pool <alanadi>` olarak, site sahibinin kullanıcısıyla koşar.
 * /proc `hidepid=invisible` ile bağlı olduğu için panel süreçleri göremez;
 * ölçüm root olarak çalışan ayrı bir toplayıcıdan alınır.
 *
 * ÖNEMLİ: Yalnızca PHP tüketimi ölçülür. nginx/apache/MariaDB paylaşımlı
 * süreçlerdir ve site başına ayrıştırılamaz. `pm = ondemand` olduğu için
 * boştaki sitenin işçisi yoktur; 0 değeri "kullanmıyor" demektir.
 *
 * @param string $user  boş ise tüm siteler (yönetici görünümü)
 */
function wd_site_usage(string $user = "", int $sample_ms = 220): ?array {
	static $cache = [];
	$key = $user . "|" . $sample_ms;
	if (isset($cache[$key])) {
		return $cache[$key];
	}

	$bin = "/usr/local/hestia/wd/bin/wd-site-usage";
	if (!is_file($bin)) {
		return null;
	}

	$cmd = "/usr/bin/sudo " . $bin . " json " . (int) $sample_ms;
	if ($user !== "") {
		$cmd .= " " . escapeshellarg($user);
	}

	$out = [];
	$rc = 0;
	exec($cmd . " 2>/dev/null", $out, $rc);
	if ($rc !== 0) {
		$cache[$key] = null;
		return null;
	}
	$data = json_decode(implode("", $out), true);
	$cache[$key] = is_array($data) ? $data : null;
	return $cache[$key];
}

/** kB değerini okunur birime çevirir (MB / GB). */
function wd_kb_human(int $kb): array {
	if ($kb >= 1048576) {
		return [number_format($kb / 1048576, 1, ",", "."), "GB"];
	}
	if ($kb >= 1024) {
		return [number_format($kb / 1024, 0, ",", "."), "MB"];
	}
	return [(string) $kb, "KB"];
}

/**
 * Birincil alan adının Let's Encrypt sertifikasının kalan gün sayısı.
 * Sertifika okunamazsa null döner (uydurma tarih üretilmez).
 */
function wd_ssl_days_left(string $user, string $domain): ?int {
	$safe_u = preg_replace('/[^a-zA-Z0-9._-]/', "", $user);
	$safe_d = preg_replace('/[^a-zA-Z0-9._-]/', "", $domain);
	if ($safe_u === "" || $safe_d === "") {
		return null;
	}
	$out = [];
	$rc = 0;
	// Sertifika dosyası hestiaweb tarafından okunamaz; sudo'lu komut kullanılır.
	exec(
		HESTIA_CMD . "v-list-web-domain-ssl " . escapeshellarg($user) . " " . escapeshellarg($domain) . " json",
		$out,
		$rc,
	);
	if ($rc !== 0) {
		return null;
	}
	$data = json_decode(implode("", $out), true);
	if (!is_array($data)) {
		return null;
	}
	$rec = reset($data);
	$until = $rec["CRT_VALID_UNTIL"] ?? ($rec["VALID_UNTIL"] ?? null);
	if (!$until) {
		return null;
	}
	$ts = strtotime($until);
	if ($ts === false) {
		return null;
	}
	return (int) floor(($ts - time()) / 86400);
}

/**
 * Sağlık Merkezi verisi.
 *
 * Toplayıcı root olarak çalışır (dig, exim kuyruğu, sertifika dosyaları) ve
 * SONUCU ÖNBELLEĞE yazar. Sayfa önbelleği okur; cron tazeler. DNS sorguları
 * saniyeler sürdüğü için sayfa açılışında canlı sorgu YAPILMAZ — $max_age
 * bilerek cron aralığından uzun tutulur.
 *
 * Toplayıcı sunucudaki TÜM kullanıcıların alan adlarını döner; müşteri
 * yalnızca kendi kayıtlarını görmelidir, bu yüzden burada süzülür.
 *
 * @param string $user     süzülecek kullanıcı ("" -> süzme yok)
 * @param bool   $is_admin sunucu düzeyi kontroller (PTR/RBL/kuyruk) gösterilsin mi
 */
function wd_health(string $user = "", bool $is_admin = false, int $max_age = 3600): ?array {
	static $cache = [];
	$key = $user . "|" . ($is_admin ? "1" : "0") . "|" . $max_age;
	if (array_key_exists($key, $cache)) {
		return $cache[$key];
	}

	// Önce önbellek dosyası doğrudan okunur (0644, dizin 0755). Taze ise sudo
	// çağrısı hiç yapılmaz — bu fonksiyon sidebar rozeti için HER sayfada
	// çalıştığından süreç başlatma maliyeti kabul edilemez.
	$data = wd_health_cache_read();
	if ($data === null || time() - (int) ($data["ts"] ?? 0) > $max_age) {
		$bin = "/usr/local/hestia/wd/bin/wd-health";
		if (!is_file($bin)) {
			$cache[$key] = null;
			return null;
		}
		$out = [];
		$rc = 0;
		exec("/usr/bin/sudo " . $bin . " json " . (int) $max_age . " 2>/dev/null", $out, $rc);
		if ($rc !== 0) {
			$cache[$key] = null;
			return null;
		}
		$data = json_decode(implode("", $out), true);
	}
	if (!is_array($data)) {
		$cache[$key] = null;
		return null;
	}

	// Sunucu düzeyi kontrolleri müşteri düzeltemez (rDNS, kara liste, kuyruk);
	// yalnızca yöneticiye gösterilir.
	if (!$is_admin) {
		$data["sunucu"] = [];
	}

	if ($user !== "") {
		foreach (["mail", "web"] as $grup) {
			$data[$grup] = array_values(
				array_filter($data[$grup] ?? [], function ($d) use ($user) {
					return ($d["user"] ?? "") === $user;
				}),
			);
		}
	}

	// Özet süzmeden SONRA yeniden sayılır; aksi hâlde müşteriye başkasının
	// sorunu sayılırdı.
	$data["ozet"] = wd_health_ozet($data);

	$cache[$key] = $data;
	return $data;
}

/**
 * Disk kullanım analizi.
 *
 * Önbellek dosyasını DOĞRUDAN okur (0644). Tarama `du`/`find` ile saniyeler
 * sürdüğü için sayfa açılışında ASLA çalıştırılmaz; cron tazeler, sayfada
 * elle "Yeniden Tara" bağlantısı vardır.
 *
 * @param string $user boş ise tüm kullanıcılar (yönetici görünümü)
 */
function wd_disk(string $user = ""): ?array {
	static $cache = [];
	if (array_key_exists($user, $cache)) {
		return $cache[$user];
	}
	$yol = "/usr/local/hestia/wd/cache/disk.json";
	if (!is_readable($yol)) {
		$cache[$user] = null;
		return null;
	}
	$ham = @file_get_contents($yol);
	$d = $ham !== false ? json_decode($ham, true) : null;
	if (!is_array($d) || !isset($d["kullanicilar"])) {
		$cache[$user] = null;
		return null;
	}
	if ($user !== "") {
		// Müşteri yalnızca kendi hesabını görür.
		$d["kullanicilar"] = array_values(
			array_filter($d["kullanicilar"], function ($k) use ($user) {
				return ($k["user"] ?? "") === $user;
			}),
		);
	}
	$cache[$user] = $d;
	return $d;
}

/**
 * Uzun dosya yolunu ORTADAN kısaltır: başlangıç ve dosya adı korunur.
 *
 *   ~/.config/composer/cache/files/symfony/console/13d7e6…zip
 *   -> ~/.config/composer/…/console/13d7e6…zip
 *
 * Sondan kırpmak dosya adını, baştan kırpmak konumu gizlerdi; ikisi de gerekli.
 * CSS ile soldan kırpma (direction:rtl) denendi ve yol bölümlerini görsel
 * olarak yeniden sıraladığı için terk edildi.
 */
function wd_yol_kisalt(string $yol, int $azami = 72): string {
	if (mb_strlen($yol, "UTF-8") <= $azami) {
		return $yol;
	}
	$parcalar = explode("/", $yol);
	$son = array_pop($parcalar);
	// Dosya adı tek başına sığmıyorsa onu da ortadan kırp.
	if (mb_strlen($son, "UTF-8") > $azami - 10) {
		$bas = mb_substr($son, 0, (int) (($azami - 12) / 2), "UTF-8");
		$kuyruk = mb_substr($son, -8, null, "UTF-8");
		$son = $bas . "…" . $kuyruk;
	}
	$bas = "";
	foreach ($parcalar as $p) {
		if (mb_strlen($bas . "/" . $p, "UTF-8") > $azami - mb_strlen($son, "UTF-8") - 6) {
			break;
		}
		$bas .= ($bas === "" ? "" : "/") . $p;
	}
	$onceki = count($parcalar) ? end($parcalar) : "";
	if ($onceki !== "" && strpos($bas, $onceki) === false) {
		return $bas . "/…/" . $onceki . "/" . $son;
	}
	return $bas . "/…/" . $son;
}

/** Baytı okunur birime çevirir. */
function wd_bayt(int $b): string {
	$birimler = ["B", "KB", "MB", "GB", "TB"];
	$i = 0;
	$v = (float) $b;
	while ($v >= 1024 && $i < count($birimler) - 1) {
		$v /= 1024;
		$i++;
	}
	return $i === 0
		? $b . " B"
		: number_format($v, $v >= 100 ? 0 : 1, ",", ".") . " " . $birimler[$i];
}

/**
 * Kaynak limiti kademeleri (bellek / süre / işlem sayısı).
 *
 * Dosyayı DOĞRUDAN okur (0644) — bu bilgi Araçlar sayfasında her site satırı
 * için gerektiğinden alt süreç çalıştırmak kabul edilemez. Dosya yoksa
 * (wd-kaynak hiç çalışmadıysa) boş dizi döner ve arayüz kademe göstermez.
 */
function wd_kaynak_kademeleri(): array {
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}
	$yol = "/usr/local/hestia/wd/kaynak.json";
	$cache = [];
	if (is_readable($yol)) {
		$ham = @file_get_contents($yol);
		$d = $ham !== false ? json_decode($ham, true) : null;
		if (is_array($d) && !empty($d["kademeler"])) {
			$cache = $d["kademeler"];
		}
	}
	return $cache;
}

/**
 * Alan adı -> kaynak kademesi eşlemesi.
 *
 * Cron tarafından yazılan önbellekten DOĞRUDAN okunur. Araçlar sayfası her
 * site satırı için bu bilgiyi ister; yöneticide tüm kullanıcıları taramak
 * sayfa başına onlarca alt süreç demek olurdu.
 *
 * @return array<string,array>  domain => kademe bilgisi
 */
function wd_kaynak_siteleri(): array {
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}
	$cache = [];
	$yol = "/usr/local/hestia/wd/cache/kaynak.json";
	if (!is_readable($yol)) {
		return $cache;
	}
	$ham = @file_get_contents($yol);
	$d = $ham !== false ? json_decode($ham, true) : null;
	if (!is_array($d) || empty($d["siteler"])) {
		return $cache;
	}
	foreach ($d["siteler"] as $s) {
		if (!empty($s["domain"])) {
			$cache[$s["domain"]] = $s;
		}
	}
	return $cache;
}

/**
 * Bir alan adının uygulanan kaynak kademesi.
 *
 * Yalnızca GERÇEKTEN atanmış limit döner: şablon adı `wd-<kademe>-PHP-<x>_<y>`
 * biçiminde olmalıdır (sondaki PHP-x_y HestiaCP'nin sürüm çözümlemesi için
 * zorunludur). Stok şablon kullanan sitede null döner — "limit uygulanmamış"
 * demektir ve arayüz o rozeti çizmez. Hedef kademeyi göstermek yanıltıcı
 * olurdu: atanmadıkça limit yoktur.
 */
function wd_site_kademe(string $domain): ?array {
	$siteler = wd_kaynak_siteleri();
	if (!isset($siteler[$domain])) {
		return null;
	}
	$s = $siteler[$domain];
	$backend = (string) ($s["mevcut"] ?? "");
	if (!preg_match('/^wd-([a-z0-9]+)-PHP-\d_\d$/', $backend, $m)) {
		return null;
	}
	$kademeler = wd_kaynak_kademeleri();
	if (!isset($kademeler[$m[1]])) {
		return null;
	}
	$k = $kademeler[$m[1]];
	$k["anahtar"] = $m[1];
	return $k;
}

/**
 * Sağlık önbelleğini DOĞRUDAN okur — süreç başlatmaz.
 * Dosya yoksa/bozuksa null döner; çağıran sudo'ya düşer.
 */
function wd_health_cache_read(): ?array {
	$yol = "/usr/local/hestia/wd/cache/health.json";
	if (!is_readable($yol)) {
		return null;
	}
	$ham = @file_get_contents($yol);
	if ($ham === false || $ham === "") {
		return null;
	}
	$data = json_decode($ham, true);
	return is_array($data) ? $data : null;
}

/**
 * Sidebar rozeti: kullanıcıyı ilgilendiren sorun sayısı.
 *
 * YALNIZCA önbellekten okur — asla toplama tetiklemez. Rozet her sayfada
 * çizildiği için burada bir sudo çağrısı ya da DNS sorgusu olamaz.
 * Veri yoksa null döner ve rozet çizilmez (sıfır göstermek "sorun yok"
 * anlamına gelirdi; oysa bilinmiyor).
 */
function wd_health_sorun_sayisi(string $user, bool $is_admin): ?int {
	$data = wd_health_cache_read();
	if ($data === null) {
		return null;
	}
	if (!$is_admin) {
		$data["sunucu"] = [];
		foreach (["mail", "web"] as $grup) {
			$data[$grup] = array_values(
				array_filter($data[$grup] ?? [], function ($d) use ($user) {
					return ($d["user"] ?? "") === $user;
				}),
			);
		}
	}
	$o = wd_health_ozet($data);
	return ($o["fail"] ?? 0) + ($o["warn"] ?? 0);
}

/** Sağlık verisindeki durumları sayar. */
function wd_health_ozet(array $data): array {
	$o = ["ok" => 0, "warn" => 0, "fail" => 0, "bilinmiyor" => 0];
	$say = function ($checks) use (&$o) {
		foreach ($checks as $c) {
			$d = $c["durum"] ?? "bilinmiyor";
			if (isset($o[$d])) {
				$o[$d]++;
			}
		}
	};
	$say($data["sunucu"] ?? []);
	foreach (["mail", "web"] as $grup) {
		foreach ($data[$grup] ?? [] as $dom) {
			$say($dom["checks"] ?? []);
		}
	}
	return $o;
}

/** Sağlık durumuna karşılık gelen CSS sınıfı. */
function wd_health_sinif(string $durum): string {
	switch ($durum) {
		case "ok":
			return "wd-hs-ok";
		case "warn":
			return "wd-hs-warn";
		case "fail":
			return "wd-hs-fail";
		default:
			return "wd-hs-unknown";
	}
}

/** Sağlık durumunun Türkçe etiketi. */
function wd_health_etiket(string $durum): string {
	switch ($durum) {
		case "ok":
			return "Sorunsuz";
		case "warn":
			return "Uyarı";
		case "fail":
			return "Sorun";
		default:
			return "Kontrol edilemedi";
	}
}

/** Panelde kullanılan güvenli çıktı kısayolu. */
function wd_e($v): string {
	return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}
