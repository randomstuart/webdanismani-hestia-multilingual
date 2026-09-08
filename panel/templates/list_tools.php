<?php
/**
 * WebDanışmanı — "Araçlar" sayfası şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_tools.php
 *
 * $panel, $user  -> render_page() tarafından sağlanır
 * $wd_*          -> list/tools/index.php tarafından sağlanır
 */

$p = $panel[$wd_user] ?? ($panel[$user] ?? []);
$tok = $_SESSION["token"] ?? "";
$pma = $_SESSION["DB_PMA_ALIAS"] ?? "phpmyadmin";
$wma = $_SESSION["WEBMAIL_ALIAS"] ?? "webmail";

$primary = $wd_primary["domain"] ?? null;
$pmail = $wd_primary_mail ?? null;

/* ---------------------------------------------------------------------------
   Araç grupları. Yalnızca GERÇEKTEN var olan sayfalara bağlantı verilir;
   HestiaCP'de karşılığı olmayan bir özellik için kutu üretilmez.
   --------------------------------------------------------------------------- */
$groups = [];

/* --- WEB --- */
if (!empty($_SESSION["WEB_SYSTEM"]) && ($p["WEB_DOMAINS"] ?? "0") !== "0") {
	$t = [
		["Web Alan Adları", "/list/web/", "fa-globe"],
		["Yeni Alan Adı Ekle", "/add/web/", "fa-circle-plus"],
	];
	if ($primary) {
		$t[] = ["Alan Adı Ayarları", "/edit/web/?domain=" . urlencode($primary) . "&token=" . $tok, "fa-sliders"];
		$t[] = ["SSL / Let's Encrypt", "/edit/web/?domain=" . urlencode($primary) . "&token=" . $tok, "fa-lock"];
		if (($_SESSION["PLUGIN_APP_INSTALLER"] ?? "") === "true") {
			$t[] = ["Hızlı Kurulum", "/add/webapp/?domain=" . urlencode($primary) . "&token=" . $tok, "fa-wand-magic-sparkles"];
		}
	}
	// web-log ZORUNLU ?domain= ister; parametresiz açılırsa 500 verir
	// (quoteshellarg tanımsız anahtarla patlar). Alan adı yoksa bağlantı konmaz.
	if ($primary) {
		$t[] = ["Web Günlükleri", "/list/web-log/?domain=" . urlencode($primary) . "&type=access", "fa-file-lines"];
	}
	$t[] = ["Web İstatistikleri", "/list/stats/", "fa-chart-line"];
	// Panelde stok karşılığı OLMAYAN, bu eklentiyle gelen araçlar
	$t[] = ["Dizin Şifre Koruma", "/list/httpauth/", "fa-lock"];
	$t[] = ["Özel Hata Sayfaları", "/list/errorpages/", "fa-triangle-exclamation"];
	$t[] = ["Yönlendirmeler", "/list/yonlendirme/", "fa-right-left"];
	if (($_SESSION["FILE_MANAGER"] ?? "") === "true") {
		$t[] = ["Dosya Yöneticisi", "/fm/", "fa-folder-open"];
	}
	$groups[] = ["key" => "web", "title" => "WEB — Alan Adları", "icon" => "fa-earth-americas", "tools" => $t];
}

/* --- MAIL --- */
if (!empty($_SESSION["MAIL_SYSTEM"]) && ($p["MAIL_DOMAINS"] ?? "0") !== "0") {
	$t = [
		["Mail Alan Adları", "/list/mail/", "fa-envelopes-bulk"],
		["Yeni Mail Alan Adı", "/add/mail/", "fa-circle-plus"],
	];
	if ($pmail) {
		$t[] = ["Mail Hesapları", "/list/mail/?domain=" . urlencode($pmail), "fa-at"];
		$t[] = ["Yeni Mail Hesabı", "/add/mail/?domain=" . urlencode($pmail), "fa-user-plus"];
		$t[] = ["Mail Ayarları", "/edit/mail/?domain=" . urlencode($pmail) . "&token=" . $tok, "fa-shield-halved"];
		if (($_SESSION["WEBMAIL_SYSTEM"] ?? "") !== "") {
			$t[] = ["Webmail", "https://" . $wma . "." . $pmail . "/", "fa-inbox", true];
		}
	}
	// SPF/DKIM/DMARC/MX denetimi — mail teslim sorunlarının kaynağı
	// çoğunlukla DNS'tir, bu yüzden mail grubundan da erişilebilir.
	$t[] = ["Mail Sağlık Denetimi", "/list/health/", "fa-stethoscope"];
	if ($wd_is_admin) {
		$t[] = ["Mail Raporu", "/list/mailrapor/", "fa-chart-column"];
	}
	$groups[] = ["key" => "mail", "title" => "MAIL — E-posta", "icon" => "fa-envelopes-bulk", "tools" => $t];
}

/* --- DNS --- */
if (!empty($_SESSION["DNS_SYSTEM"]) && ($p["DNS_DOMAINS"] ?? "0") !== "0") {
	$t = [
		["DNS Bölgeleri", "/list/dns/", "fa-book-atlas"],
		["Yeni Bölge Ekle", "/add/dns/", "fa-circle-plus"],
	];
	if ($wd_primary_dns) {
		$t[] = ["DNS Kayıtları", "/list/dns/?domain=" . urlencode($wd_primary_dns), "fa-list-ul"];
		$t[] = ["Yeni DNS Kaydı", "/add/dns/?domain=" . urlencode($wd_primary_dns), "fa-plus"];
	}
	$groups[] = ["key" => "dns", "title" => "DNS", "icon" => "fa-book-atlas", "tools" => $t];
}

/* --- VERİTABANI --- */
if (!empty($_SESSION["DB_SYSTEM"]) && ($p["DATABASES"] ?? "0") !== "0") {
	$t = [
		["Veritabanları", "/list/db/", "fa-database"],
		["Yeni Veritabanı", "/add/db/", "fa-circle-plus"],
	];
	if ($primary && strpos($_SESSION["DB_SYSTEM"], "mysql") !== false) {
		$t[] = ["phpMyAdmin", "https://" . $primary . "/" . $pma . "/", "fa-table", true];
	}
	$groups[] = ["key" => "db", "title" => "VERİTABANI", "icon" => "fa-database", "tools" => $t];
}

/* --- CRON --- */
if (!empty($_SESSION["CRON_SYSTEM"]) && ($p["CRON_JOBS"] ?? "0") !== "0") {
	$groups[] = [
		"key" => "cron",
		"title" => "CRON — Zamanlanmış Görevler",
		"icon" => "fa-clock",
		"tools" => [["Cron Görevleri", "/list/cron/", "fa-clock"], ["Yeni Görev Ekle", "/add/cron/", "fa-circle-plus"]],
	];
}

/* --- YEDEK --- */
if (!empty($_SESSION["BACKUP_SYSTEM"])) {
	$groups[] = [
		"key" => "backup",
		"title" => "YEDEKLER",
		"icon" => "fa-file-zipper",
		"tools" => [["Yedekler", "/list/backup/", "fa-file-zipper"]],
	];
}

/* --- HESAP --- */
$t = [["Sağlık Merkezi", "/list/health/", "fa-stethoscope"]];
$t[] = ["Hesap Ayarları", "/edit/user/?user=" . urlencode($wd_user) . "&token=" . $tok, "fa-circle-user"];
$t[] = ["SSH Anahtarları", "/list/access-key/", "fa-key"];
$t[] = ["İstatistikler", "/list/stats/", "fa-chart-line"];
$t[] = ["Kaynak Geçmişi", "/list/gecmis/", "fa-chart-area"];
// NOT: /list/notifications/ bir SAYFA değil, yalnızca AJAX uç noktasıdır
// (HestiaCP 1.10.4'te list_notifications.php şablonu yoktur). Doğrudan
// açılırsa boş sayfa üretir; bildirimlere üst çubuktaki zil ikonundan
// erişilir, bu yüzden buraya bağlantı konmaz.
if ($wd_is_admin) {
	$t[] = ["Günlükler", "/list/log/", "fa-clock-rotate-left"];
}
$groups[] = ["key" => "account", "title" => "HESAP", "icon" => "fa-circle-user", "tools" => $t];

/* --- SUNUCU (yalnızca yönetici) --- */
if ($wd_is_admin) {
	$t = [
		["Sunucu Ayarları", "/list/server/", "fa-gear"],
		["Kullanıcılar", "/list/user/", "fa-users"],
		["Hosting Paketleri", "/list/package/", "fa-box-open"],
		["Güvenlik Duvarı", "/list/firewall/", "fa-shield-halved"],
		["IP Adresleri", "/list/ip/", "fa-network-wired"],
		["Güncellemeler", "/list/updates/", "fa-rotate"],
		["Grafikler", "/list/rrd/", "fa-chart-area"],
		["Cloudflare", "/list/cloudflare/", "fa-cloud"],
		["Güvenlik", "/list/guvenlik/", "fa-shield-halved"],
	];
	if (($_SESSION["WEB_TERMINAL"] ?? "") === "true" && ($_SESSION["login_shell"] ?? "") !== "nologin") {
		$t[] = ["Web Terminali", "/list/terminal/", "fa-terminal"];
	}
	$groups[] = ["key" => "server", "title" => "SUNUCU YÖNETİMİ", "icon" => "fa-server", "tools" => $t];
}

/* --- Ek modüller (inc/wd-modul.php kayıt dizisi): kurulu olanlar ilgili gruba eklenir --- */
if (is_file($_SERVER["DOCUMENT_ROOT"] . "/inc/wd-modul.php")) {
	require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-modul.php";
	foreach (wd_modul_listesi($wd_is_admin, $wd_user) as $wd_m) {
		$wd_grup_var = false;
		foreach ($groups as &$wd_g) {
			if ($wd_g["key"] === $wd_m["grup"]) {
				$wd_g["tools"][] = [$wd_m["ad"], $wd_m["href"], $wd_m["ikon"]];
				$wd_grup_var = true;
			}
		}
		unset($wd_g);
		// Grup yoksa (ör. bayinin "server" grubu) hesap grubuna düşer
		if (!$wd_grup_var) {
			foreach ($groups as &$wd_g) {
				if ($wd_g["key"] === "account") {
					$wd_g["tools"][] = [$wd_m["ad"], $wd_m["href"], $wd_m["ikon"]];
				}
			}
			unset($wd_g);
		}
	}
}

/* ---------------------------------------------------------------------------
   İstatistik kartları — yalnızca gerçek veri olan kart çizilir.
   --------------------------------------------------------------------------- */
$cards = [];

/* Tasarımda kart iki satır: ETİKET / <b>değer</b> <small>of</small> + çubuk.
   "of" ikincil metin — CPU ve BELLEK sunucu geneli olduğu için orada
   "sunucu ·" öneki taşır; DİSK ve BANT kullanıcının kendi kotasıdır. */
$wd_quota_txt = function ($limit) {
	return $limit === "unlimited" || $limit === "" || $limit === null
		? "/ sınırsız"
		: "/ " . humanize_usage_size($limit) . " " . humanize_usage_measure($limit);
};

if ($wd_cpu !== null) {
	$cards[] = [
		"label" => "CPU",
		"value" => "%" . number_format($wd_cpu, 1, ",", "."),
		"of" => ($wd_load["cores"] ?? 1) . " vCPU",
		"tip" => "Sunucu geneli işlemci kullanımı",
		"pct" => $wd_cpu,
	];
}
if ($wd_mem !== null) {
	$cards[] = [
		"label" => "BELLEK",
		"value" => number_format($wd_mem["used_kb"] / 1048576, 1, ",", ".") . " GB",
		"of" => "/ " . number_format($wd_mem["total_kb"] / 1048576, 1, ",", ".") . " GB",
		"tip" => "Sunucu geneli bellek kullanımı",
		"pct" => $wd_mem["pct"],
	];
}
$cards[] = [
	"label" => "DISK",
	"value" => humanize_usage_size($p["U_DISK"] ?? 0) . " " . humanize_usage_measure($p["U_DISK"] ?? 0),
	"of" => $wd_quota_txt($p["DISK_QUOTA"] ?? "unlimited"),
	"pct" => wd_quota_pct($p["U_DISK"] ?? 0, $p["DISK_QUOTA"] ?? "unlimited"),
];
$cards[] = [
	"label" => "BANT GENİŞLİĞİ",
	"value" => humanize_usage_size($p["U_BANDWIDTH"] ?? 0) . " " . humanize_usage_measure($p["U_BANDWIDTH"] ?? 0),
	"of" => $wd_quota_txt($p["BANDWIDTH"] ?? "unlimited"),
	"pct" => wd_quota_pct($p["U_BANDWIDTH"] ?? 0, $p["BANDWIDTH"] ?? "unlimited"),
];

/* ---------------------------------------------------------------------------
   Paket kullanımı satırları
   --------------------------------------------------------------------------- */
$usage_rows = [];
$add_usage = function ($label, $used, $limit) use (&$usage_rows) {
	if ($limit === "0") {
		return;
	}
	$usage_rows[] = [
		"label" => $label,
		"used" => $used,
		"limit" => $limit,
		"pct" => wd_quota_pct($used, $limit),
	];
};
if (!empty($_SESSION["WEB_SYSTEM"])) {
	$add_usage("Web Alan Adları", $p["U_WEB_DOMAINS"] ?? 0, $p["WEB_DOMAINS"] ?? "unlimited");
}
if (!empty($_SESSION["DNS_SYSTEM"])) {
	$add_usage("DNS Bölgeleri", $p["U_DNS_DOMAINS"] ?? 0, $p["DNS_DOMAINS"] ?? "unlimited");
}
if (!empty($_SESSION["MAIL_SYSTEM"])) {
	$add_usage("Mail Hesapları", $p["U_MAIL_ACCOUNTS"] ?? 0, $p["MAIL_ACCOUNTS"] ?? "unlimited");
}
if (!empty($_SESSION["DB_SYSTEM"])) {
	$add_usage("Veritabanları", $p["U_DATABASES"] ?? 0, $p["DATABASES"] ?? "unlimited");
}
if (!empty($_SESSION["CRON_SYSTEM"])) {
	$add_usage("Cron Görevleri", $p["U_CRON_JOBS"] ?? 0, $p["CRON_JOBS"] ?? "unlimited");
}
if (!empty($_SESSION["BACKUP_SYSTEM"])) {
	$add_usage("Yedekler", $p["U_BACKUPS"] ?? 0, $p["BACKUPS"] ?? "unlimited");
}
?>

<div class="container">
	<div class="wd-page">

		<!-- ================= ANA SÜTUN ================= -->
		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title">Araçlar</h1>
					<p class="wd-subtitle">
						Hosting hesabınızın tüm yönetim araçları — HestiaCP <?= wd_e($_SESSION["VERSION"] ?? "") ?>
					</p>
				</div>
				<div class="wd-page-actions">
					<button type="button" class="button button-secondary" data-wd-toggle="close">Tümünü Kapat</button>
					<button type="button" class="button button-secondary" data-wd-toggle="open">Tümünü Aç</button>
				</div>
			</div>

			<!-- İstatistik kartları -->
			<div class="wd-stats">
				<?php foreach ($cards as $c) { ?>
					<div class="wd-stat"<?= isset($c["tip"]) ? ' title="' . wd_e($c["tip"]) . '"' : "" ?>>
						<div class="wd-stat-label"><?= wd_e($c["label"]) ?></div>
						<div class="wd-stat-value">
							<?= wd_e($c["value"]) ?><span class="wd-stat-of"><?= wd_e($c["of"]) ?></span>
						</div>
						<?php if ($c["pct"] !== null) { ?>
							<div class="wd-bar <?= wd_level($c["pct"]) ?>" role="img"
								aria-label="<?= wd_e($c["label"]) ?>: %<?= wd_e(number_format($c["pct"], 1, ",", ".")) ?>">
								<span style="width: <?= max(2, min(100, $c["pct"])) ?>%"></span>
							</div>
						<?php } else { ?>
							<div class="wd-bar wd-bar-none" aria-hidden="true"><span style="width:100%"></span></div>
						<?php } ?>
					</div>
				<?php } ?>
			</div>

			<!-- Araç grupları -->
			<?php foreach ($groups as $g) { ?>
				<details class="wd-group" data-wd-group="<?= wd_e($g["key"]) ?>" open>
					<summary class="wd-group-head">
						<span class="wd-group-icon"><i class="fas <?= wd_e($g["icon"]) ?>"></i></span>
						<span class="wd-group-title"><?= wd_e($g["title"]) ?></span>
						<span class="wd-group-count"><?= count($g["tools"]) ?> araç</span>
						<i class="fas fa-chevron-down wd-group-chevron"></i>
					</summary>
					<div class="wd-tools">
						<?php foreach ($g["tools"] as $tool) {
       	$ext = !empty($tool[3]); ?>
							<a class="wd-tool" href="<?= wd_e($tool[1]) ?>"<?= $ext
     	? ' target="_blank" rel="noopener"'
     	: "" ?>>
								<i class="fas <?= wd_e($tool[2]) ?>"></i>
								<span><?= wd_e($tool[0]) ?></span>
								<?php if ($ext) { ?><i class="fas fa-arrow-up-right-from-square wd-tool-ext"></i><?php } ?>
							</a>
						<?php } ?>
					</div>
				</details>
			<?php } ?>

		</div>

		<!-- ================= SAĞ PANEL ================= -->
		<aside class="wd-rail">

			<div class="wd-card">
				<div class="wd-card-head">Genel Bilgiler</div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">Geçerli Kullanıcı</span>
						<span class="wd-v"><?= wd_e($wd_user) ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Hosting Paketi</span>
						<span class="wd-v">
							<?= wd_e($p["PACKAGE"] ?? "—") ?>
							<?php if (($p["DISK_QUOTA"] ?? "unlimited") !== "unlimited") { ?>
								<span class="wd-v-dim">· <?= wd_e(humanize_usage_size($p["DISK_QUOTA"]) . " " . humanize_usage_measure($p["DISK_QUOTA"])) ?></span>
							<?php } ?>
						</span>
					</div>
					<?php if ($primary) { ?>
						<div class="wd-kv">
							<span class="wd-k">Birincil Alan Adı</span>
							<span class="wd-v"><a href="https://<?= wd_e($primary) ?>/" target="_blank" rel="noopener"><?= wd_e($primary) ?></a></span>
						</div>
						<div class="wd-kv">
							<span class="wd-k">Let's Encrypt SSL</span>
							<span class="wd-v">
								<?php if ($wd_primary["letsencrypt"]) { ?>
									<span class="wd-dot wd-dot-ok"></span> Etkin
									<?php if ($wd_ssl_days !== null) { ?>
										<span class="wd-v-dim">· <?= $wd_ssl_days > 0
      	? wd_e($wd_ssl_days) . " gün sonra yenilenir"
      	: "süresi doldu" ?></span>
									<?php } ?>
								<?php } elseif ($wd_primary["ssl"]) { ?>
									<span class="wd-dot wd-dot-warn"></span> SSL var (LE değil)
								<?php } else { ?>
									<span class="wd-dot wd-dot-off"></span> Kapalı
								<?php } ?>
							</span>
						</div>
						<?php if (!empty($wd_primary["ip"])) { ?>
							<div class="wd-kv">
								<span class="wd-k">Paylaşımlı IP</span>
								<span class="wd-v wd-mono"><?= wd_e($wd_primary["ip"]) ?></span>
							</div>
						<?php } ?>
					<?php } ?>
					<div class="wd-kv">
						<span class="wd-k">Giriş Dizini</span>
						<span class="wd-v wd-mono"><?= wd_e($p["HOME"] ?? "—") ?></span>
					</div>
					<?php if ($wd_login) { ?>
						<div class="wd-kv">
							<span class="wd-k">Son Giriş</span>
							<span class="wd-v">
								<?= wd_e(wd_date_tr($wd_login["date"]) . " " . substr($wd_login["time"], 0, 5)) ?>
								<?php if (!empty($wd_login["ip"])) { ?>
									<span class="wd-v-dim">· <?= wd_e($wd_login["ip"]) ?></span>
								<?php } ?>
							</span>
						</div>
					<?php } ?>
					<?php if (!empty($p["CONTACT"])) { ?>
						<div class="wd-kv">
							<span class="wd-k">E-posta</span>
							<span class="wd-v"><?= wd_e($p["CONTACT"]) ?></span>
						</div>
					<?php } ?>
				</div>
			</div>

			<?php if (!empty($usage_rows)) { ?>
				<div class="wd-card">
					<div class="wd-card-head">Paket Kullanımı</div>
					<div class="wd-card-body">
						<?php foreach ($usage_rows as $r) { ?>
							<div class="wd-usage">
								<div class="wd-usage-top">
									<span class="wd-usage-label"><?= wd_e($r["label"]) ?></span>
									<span class="wd-usage-num">
										<?= wd_e($r["used"]) ?> / <?= $r["limit"] === "unlimited" ? "∞" : wd_e($r["limit"]) ?>
									</span>
								</div>
								<div class="wd-bar <?= wd_level($r["pct"]) ?>">
									<span style="width: <?= $r["pct"] === null ? 4 : max(2, min(100, $r["pct"])) ?>%"></span>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>

			<?php /* --- Site kaynakları: uygulanan limit + anlık kullanım ---
			 *
			 * Limit sitenin KALICI özelliğidir, anlık kullanım ise geçicidir:
			 * `pm = ondemand` olduğu için boştaki sitenin hiç işçisi olmaz.
			 * Bu yüzden liste ölçümden değil, kademe eşlemesinden kurulur;
			 * ölçüm varsa üzerine eklenir. Aksi hâlde site boştayken limitini
			 * göremezdik.
			 *
			 * Ölçüm yalnızca PHP tüketimidir; nginx/MariaDB paylaşımlıdır ve
			 * site başına ayrıştırılamaz.
			 */
   $wd_olcum = ($wd_usage !== null && !empty($wd_usage["sites"])) ? $wd_usage["sites"] : [];
   $wd_satirlar = [];
   foreach (wd_kaynak_siteleri() as $dname => $meta) {
   	if (!$wd_is_admin && ($meta["user"] ?? "") !== $wd_user) {
   		continue;
   	}
   	$wd_satirlar[$dname] = ["kademe" => wd_site_kademe($dname), "olcum" => $wd_olcum[$dname] ?? null];
   }
   // Eşlemede olmayan ama ölçülen site (henüz önbelleğe girmemiş yeni alan adı)
   foreach ($wd_olcum as $dname => $s) {
   	if (!isset($wd_satirlar[$dname])) {
   		$wd_satirlar[$dname] = ["kademe" => wd_site_kademe((string) $dname), "olcum" => $s];
   	}
   }
   // Çalışanlar üstte, sonra ada göre
   uksort($wd_satirlar, function ($a, $b) use ($wd_satirlar) {
   	$ka = $wd_satirlar[$a]["olcum"] === null ? 1 : 0;
   	$kb = $wd_satirlar[$b]["olcum"] === null ? 1 : 0;
   	return $ka === $kb ? strcmp($a, $b) : $ka <=> $kb;
   });

   if (!empty($wd_satirlar)) { ?>
				<div class="wd-card">
					<div class="wd-card-head">
						Site Kaynakları
						<span class="wd-card-note">limit · canlı PHP</span>
					</div>
					<div class="wd-card-body">
						<?php foreach ($wd_satirlar as $sname => $row) {
      	$kademe = $row["kademe"];
      	$s = $row["olcum"];
      	$cpu = $s !== null ? (float) $s["cpu_pct"] : 0.0; ?>
							<div class="wd-usage">
								<div class="wd-usage-top">
									<span class="wd-usage-label wd-usage-site" title="<?= wd_e($sname) ?>"><?= wd_e($sname) ?></span>
									<?php if ($kademe !== null) { ?>
										<span class="wd-limit-tag"
											title="Kaynak kademesi: <?= wd_e($kademe["ad"]) ?> — en fazla <?= wd_e($kademe["bellek"]) ?> bellek, <?= wd_e($kademe["surec"]) ?> eşzamanlı işlem, istek en çok <?= wd_e($kademe["sure"]) ?> sn">
											<?= wd_e($kademe["bellek"]) ?> · <?= wd_e($kademe["surec"]) ?> işlem
										</span>
									<?php } else { ?>
										<span class="wd-limit-tag" title="Bu siteye kaynak limiti atanmamış; stok şablon kullanılıyor. Sunucuda: wd-kaynak uygula">limitsiz</span>
									<?php } ?>
								</div>
								<?php if ($s !== null) {
       	[$mv, $mu] = wd_kb_human((int) $s["mem_kb"]); ?>
									<div class="wd-site-metrics">
										<span class="wd-site-metric">
											<i class="fas fa-microchip"></i>
											<b>%<?= wd_e(number_format($cpu, 1, ",", ".")) ?></b> cpu
										</span>
										<span class="wd-site-metric">
											<i class="fas fa-memory"></i>
											<b><?= wd_e($mv) ?></b> <?= wd_e($mu) ?>
										</span>
										<span class="wd-site-metric"><?= wd_e($s["procs"]) ?> işlem</span>
									</div>
									<div class="wd-bar <?= wd_level($cpu) ?>">
										<span style="width: <?= max(2, min(100, $cpu)) ?>%"></span>
									</div>
								<?php } else { ?>
									<div class="wd-site-metrics">
										<span class="wd-site-metric wd-site-idle">boşta — çalışan PHP işlemi yok</span>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>

			<?php if ($wd_load !== null) { ?>
				<div class="wd-card">
					<div class="wd-card-head">Sunucu</div>
					<div class="wd-card-body">
						<div class="wd-kv">
							<span class="wd-k">Sistem Yükü</span>
							<span class="wd-v wd-mono"><?= wd_e(number_format($wd_load["l1"], 2, ",", ".")) ?>
								<span class="wd-v-dim">/ <?= wd_e($wd_load["cores"]) ?> çekirdek</span></span>
						</div>
						<div class="wd-kv">
							<span class="wd-k">Çalışma Süresi</span>
							<span class="wd-v"><?= wd_e(wd_human_uptime($wd_uptime)) ?></span>
						</div>
					</div>
				</div>
			<?php } ?>

		</aside>
	</div>
</div>

<script>
	(function () {
		var KEY = "wd-tools-closed";
		var groups = document.querySelectorAll(".wd-group");

		function load() {
			try { return JSON.parse(localStorage.getItem(KEY) || "[]"); } catch (e) { return []; }
		}
		function save(list) {
			try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) { /* özel pencere */ }
		}

		var closed = load();
		groups.forEach(function (g) {
			if (closed.indexOf(g.dataset.wdGroup) !== -1) { g.open = false; }
			g.addEventListener("toggle", function () {
				var list = load();
				var i = list.indexOf(g.dataset.wdGroup);
				if (g.open && i !== -1) { list.splice(i, 1); }
				if (!g.open && i === -1) { list.push(g.dataset.wdGroup); }
				save(list);
			});
		});

		document.querySelectorAll("[data-wd-toggle]").forEach(function (b) {
			b.addEventListener("click", function () {
				var open = b.dataset.wdToggle === "open";
				groups.forEach(function (g) { g.open = open; });
			});
		});
	})();
</script>
