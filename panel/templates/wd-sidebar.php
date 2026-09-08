<?php
/**
 * WebDanışmanı — sol menü (sidebar)
 * Kurulum yeri: /usr/local/hestia/web/templates/includes/wd-sidebar.php
 *
 * panel.php içindeki stok <ul class="main-menu-list"> bloğunun yerini alır.
 * $panel, $user, $TAB  -> panel.php kapsamından gelir.
 *
 * Stok sınıf adları (main-menu-*) BİLEREK korunur; böylece yama uygulanmamış
 * bir HestiaCP güncellemesinden sonra bile tema CSS'i tutarlı kalır.
 */

require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-helpers.php";

$wd_p = $panel[$user] ?? [];
$wd_adm = $_SESSION["userContext"] === "admin" && ($_SESSION["look"] ?? "") === "";
$wd_imp = !empty($_SESSION["look"]);

/** Menü öğesi çizer. */
$wd_item = function (array $o) use ($TAB) {
	$active = in_array($TAB, $o["tabs"], true) ? " active" : "";
	$badge = $o["badge"] ?? null;
	$ext = !empty($o["ext"]);
	echo '<li class="main-menu-item">';
	echo '<a class="main-menu-item-link' . $active . '" href="' . wd_e($o["href"]) . '"';
	if (!empty($o["title"])) {
		echo ' title="' . wd_e($o["title"]) . '"';
	}
	if ($ext) {
		echo ' target="_blank" rel="noopener"';
	}
	echo ">";
	// Uzun etiket sidebar için; kısa etiket <1024px'teki yatay menü için.
	// Aynı metni CSS ile kısaltmak mümkün olmadığından ikisi de basılıp
	// kırılım noktasında biri gizleniyor.
	$short = $o["short"] ?? $o["label"];
	echo '<p class="main-menu-item-label"><i class="fas ' . wd_e($o["icon"]) . '"></i>';
	echo '<span class="wd-label-long">' . wd_e($o["label"]) . "</span>";
	echo '<span class="wd-label-short">' . wd_e($short) . "</span>";
	echo "</p>";
	if ($badge !== null && $badge !== "") {
		$bcls = !empty($o["badge_class"]) ? " " . $o["badge_class"] : "";
		echo '<span class="wd-menu-badge' . $bcls . '">' . wd_e($badge) . "</span>";
	}
	echo "</a></li>";
};

/** "kullanılan / limit" biçiminde tooltip metni. */
$wd_tip = function ($label, $used, $limit) {
	return $label . ": " . $used . " / " . ($limit === "unlimited" ? "∞" : $limit);
};
?>
<ul x-cloak x-show="open" class="main-menu-list">

	<?php // --- Araçlar (kontrol paneli ana sayfası) ---
 $wd_item([
 	"tabs" => ["TOOLS"],
 	"label" => "Araçlar", "short" => "ARAÇLAR",
 	"icon" => "fa-grip",
 	"href" => "/list/tools/",
 	"title" => "Tüm yönetim araçları",
 ]); ?>

	<?php // --- Sağlık Merkezi ---
 // Rozet yalnızca ÖNBELLEKTEN gelir; burada DNS sorgusu ya da sudo çağrısı
 // yapılmaz. Veri yoksa rozet çizilmez — "0" göstermek "sorun yok" demek
 // olurdu, oysa henüz bilinmiyor.
 $wd_hs = wd_health_sorun_sayisi(
 	empty($_SESSION["look"]) ? $_SESSION["user"] : $_SESSION["look"],
 	$wd_adm,
 );
 $wd_item([
 	"tabs" => ["HEALTH"],
 	"label" => "Sağlık Merkezi", "short" => "SAĞLIK",
 	"icon" => "fa-stethoscope",
 	"href" => "/list/health/",
 	"badge" => $wd_hs !== null && $wd_hs > 0 ? $wd_hs : null,
 	"badge_class" => "wd-menu-badge-alert",
 	"title" => $wd_hs === null
 		? "Mail, DNS ve SSL denetimi"
 		: ($wd_hs > 0
 			? $wd_hs . " kontrol dikkat istiyor"
 			: "Tüm kontroller sorunsuz"),
 ]); ?>

	<?php // --- Güvenlik (yalnızca yönetici) ---
 if ($wd_adm) {
 	$wd_item([
 		"tabs" => ["GUVENLIK"],
 		"label" => "Güvenlik", "short" => "GÜVENLİK",
 		"icon" => "fa-shield-halved",
 		"href" => "/list/guvenlik/",
 		"title" => "Sunucu sertleştirme ve zararlı kod taraması",
 	]);
 } ?>

	<?php // --- Disk Kullanımı ---
 $wd_item([
 	"tabs" => ["DISK"],
 	"label" => "Disk Kullanımı", "short" => "DİSK",
 	"icon" => "fa-hard-drive",
 	"href" => "/list/disk/",
 	"title" => "Yerin nereye gittiğini gösterir",
 ]); ?>

	<?php // --- Kullanıcılar (yalnızca yönetici) ---
 if ($wd_adm) {
 	$uc = $wd_p["U_USERS"] ?? 0;
 	if (($_SESSION["user"] ?? "") !== "admin" && ($_SESSION["POLICY_SYSTEM_HIDE_ADMIN"] ?? "") === "yes") {
 		$uc = max(0, $uc - 1);
 	}
 	$wd_item([
 		"tabs" => ["USER", "LOG"],
 		"label" => "Kullanıcılar", "short" => "KULLANICI",
 		"icon" => "fa-users",
 		"href" => "/list/user/",
 		"badge" => $uc,
 		"title" => "Kullanıcılar: " . $uc . " · Askıya alınmış: " . ($wd_p["SUSPENDED_USERS"] ?? 0),
 	]);
 } ?>

	<?php // --- WEB ---
 if (!empty($_SESSION["WEB_SYSTEM"]) && ($wd_p["WEB_DOMAINS"] ?? "0") !== "0") {
 	$wd_item([
 		"tabs" => ["WEB"],
 		"label" => "WEB — Alan Adları", "short" => "WEB",
 		"icon" => "fa-earth-americas",
 		"href" => "/list/web/",
 		"badge" => $wd_p["U_WEB_DOMAINS"] ?? 0,
 		"title" => $wd_tip("Alan adları", $wd_p["U_WEB_DOMAINS"] ?? 0, $wd_p["WEB_DOMAINS"] ?? "unlimited") .
 			" · Takma adlar: " . ($wd_p["U_WEB_ALIASES"] ?? 0) .
 			" · Askıya alınmış: " . ($wd_p["SUSPENDED_WEB"] ?? 0),
 	]);
 } ?>

	<?php // --- DNS ---
 if (!empty($_SESSION["DNS_SYSTEM"]) && ($wd_p["DNS_DOMAINS"] ?? "0") !== "0") {
 	$wd_item([
 		"tabs" => ["DNS"],
 		"label" => "DNS Bölgeleri", "short" => "DNS",
 		"icon" => "fa-book-atlas",
 		"href" => "/list/dns/",
 		"badge" => $wd_p["U_DNS_DOMAINS"] ?? 0,
 		"title" => $wd_tip("Bölgeler", $wd_p["U_DNS_DOMAINS"] ?? 0, $wd_p["DNS_DOMAINS"] ?? "unlimited") .
 			" · Kayıtlar: " . ($wd_p["U_DNS_RECORDS"] ?? 0),
 	]);
 } ?>

	<?php // --- MAIL ---
 if (!empty($_SESSION["MAIL_SYSTEM"]) && ($wd_p["MAIL_DOMAINS"] ?? "0") !== "0") {
 	$wd_item([
 		"tabs" => ["MAIL"],
 		"label" => "MAIL — E-posta", "short" => "POSTA",
 		"icon" => "fa-envelopes-bulk",
 		"href" => "/list/mail/",
 		"badge" => $wd_p["U_MAIL_ACCOUNTS"] ?? 0,
 		"title" => $wd_tip("Alan adları", $wd_p["U_MAIL_DOMAINS"] ?? 0, $wd_p["MAIL_DOMAINS"] ?? "unlimited") .
 			" · Hesaplar: " . ($wd_p["U_MAIL_ACCOUNTS"] ?? 0),
 	]);
 } ?>

	<?php // --- VERİTABANI ---
 if (!empty($_SESSION["DB_SYSTEM"]) && ($wd_p["DATABASES"] ?? "0") !== "0") {
 	$wd_item([
 		"tabs" => ["DB"],
 		"label" => "DB — Veritabanları", "short" => "DB",
 		"icon" => "fa-database",
 		"href" => "/list/db/",
 		"badge" => $wd_p["U_DATABASES"] ?? 0,
 		"title" => $wd_tip("Veritabanları", $wd_p["U_DATABASES"] ?? 0, $wd_p["DATABASES"] ?? "unlimited"),
 	]);
 } ?>

	<?php // --- CRON ---
 if (!empty($_SESSION["CRON_SYSTEM"]) && ($wd_p["CRON_JOBS"] ?? "0") !== "0") {
 	$wd_item([
 		"tabs" => ["CRON"],
 		"label" => "CRON Görevleri", "short" => "CRON",
 		"icon" => "fa-clock",
 		"href" => "/list/cron/",
 		"badge" => $wd_p["U_CRON_JOBS"] ?? 0,
 		"title" => $wd_tip("Görevler", $wd_p["U_CRON_JOBS"] ?? 0, $wd_p["CRON_JOBS"] ?? "unlimited"),
 	]);
 } ?>

	<?php // --- YEDEK ---
 if (
 	!empty($_SESSION["BACKUP_SYSTEM"]) &&
 	(($wd_p["BACKUPS"] ?? "0") !== "0" ||
 		($wd_p["U_BACKUPS"] ?? "0") !== "0" ||
 		($wd_p["BACKUPS_INCREMENTAL"] ?? "no") === "yes")
 ) {
 	$wd_item([
 		"tabs" => ["BACKUP"],
 		"label" => "Yedekler", "short" => "YEDEK",
 		"icon" => "fa-file-zipper",
 		"href" => "/list/backup/",
 		"badge" => $wd_p["U_BACKUPS"] ?? 0,
 		"title" => $wd_tip("Yedekler", $wd_p["U_BACKUPS"] ?? 0, $wd_p["BACKUPS"] ?? "unlimited"),
 	]);
 } ?>

	<li class="main-menu-sep" aria-hidden="true"></li>

	<?php // --- Ek modüller (inc/wd-modul.php): yalnız kısa liste; tamamı Araçlar sayfasında.
 // Rozetler ÖNBELLEKTEN gelir (erişim: çökük site, WP: bekleyen güncelleme); sudo çağrısı yok.
 if (is_file($_SERVER["DOCUMENT_ROOT"] . "/inc/wd-modul.php")) {
 	require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-modul.php";
 	$wd_mod_user = empty($_SESSION["look"]) ? $_SESSION["user"] : $_SESSION["look"];
 	$wd_mod_kisa = ["erisim", "wp", "bayi"];
 	foreach (wd_modul_listesi($wd_adm, $wd_mod_user) as $wd_m) {
 		if (!in_array($wd_m["kod"], $wd_mod_kisa, true)) {
 			continue;
 		}
 		$wd_r = wd_modul_rozet($wd_m["kod"], $wd_adm, $wd_mod_user);
 		$wd_item([
 			"tabs" => [$wd_m["tab"]],
 			"label" => $wd_m["ad"], "short" => $wd_m["kisa"],
 			"icon" => $wd_m["ikon"],
 			"href" => $wd_m["href"],
 			"badge" => $wd_r !== null && $wd_r > 0 ? $wd_r : null,
 			"badge_class" => $wd_m["kod"] === "erisim" ? "wd-menu-badge-alert" : "",
 			"title" => $wd_m["aciklama"],
 		]);
 	}
 } ?>

	<?php // --- Dosya Yöneticisi ---
 if (($_SESSION["FILE_MANAGER"] ?? "") === "true") {
 	$hide_fm = $_SESSION["userContext"] === "admin" && ($_SESSION["look"] ?? "") === "admin" && ($_SESSION["POLICY_SYSTEM_PROTECTED_ADMIN"] ?? "") === "yes";
 	if (!$hide_fm) {
 		$wd_item(["tabs" => ["FM"], "label" => "Dosya Yöneticisi", "short" => "DOSYA", "icon" => "fa-folder-open", "href" => "/fm/"]);
 	}
 } ?>

	<?php // --- Web Terminali ---
 if (($_SESSION["WEB_TERMINAL"] ?? "") === "true" && ($_SESSION["login_shell"] ?? "") !== "nologin") {
 	$wd_item(["tabs" => ["TERMINAL"], "label" => "Web Terminali", "short" => "TERMİNAL", "icon" => "fa-terminal", "href" => "/list/terminal/"]);
 } ?>

	<?php // --- İstatistikler ---
 $wd_item(["tabs" => ["STATS"], "label" => "İstatistikler", "short" => "İSTATİSTİK", "icon" => "fa-chart-line", "href" => "/list/stats/"]); ?>

	<?php // --- Sunucu Ayarları (yalnızca yönetici) ---
 if ((($_SESSION["userContext"] === "admin" && ($_SESSION["POLICY_SYSTEM_HIDE_SERVICES"] ?? "") !== "yes") ||
 	($_SESSION["user"] ?? "") === ($_SESSION["ROOT_USER"] ?? "")) && !$wd_imp) {
 	$wd_item([
 		"tabs" => ["SERVER", "IP", "RRD", "FIREWALL", "UPDATES", "PACKAGE", "NOTIFICATIONS"],
 		"label" => "Sunucu Ayarları", "short" => "SUNUCU",
 		"icon" => "fa-gear",
 		"href" => "/list/server/",
 	]);
 } ?>

	<?php // --- Günlükler ---
 if ($wd_adm) {
 	$wd_item(["tabs" => ["LOG"], "label" => "Günlükler", "short" => "GÜNLÜK", "icon" => "fa-clock-rotate-left", "href" => "/list/log/"]);
 } ?>

</ul>

<?php // --- Sunucu yükü kartı ---
// Yük ortalaması hassas bir veri değil ve tasarımda müşteri görünümünde de var.
if (true) {
	$wd_l = wd_load();
	if ($wd_l !== null) { ?>
		<div class="wd-load">
			<div class="wd-load-label">SUNUCU YÜKÜ</div>
			<div class="wd-load-value">
				<?= wd_e(number_format($wd_l["l1"], 2, ",", ".")) ?><span class="wd-load-cores">/ <?= wd_e($wd_l["cores"]) ?> çekirdek</span>
			</div>
			<div class="wd-bar <?= wd_level($wd_l["pct"]) ?>"
				role="img" aria-label="Sistem yükü: <?= wd_e(number_format($wd_l["l1"], 2, ",", ".")) ?> / <?= wd_e($wd_l["cores"]) ?> çekirdek">
				<span style="width: <?= $wd_l["pct"] === null ? 4 : max(2, min(100, $wd_l["pct"])) ?>%"></span>
			</div>
			<div class="wd-load-uptime">Çalışma süresi <?= wd_e(wd_human_uptime(wd_uptime_seconds())) ?></div>
		</div>
	<?php }
} ?>

<?php // --- Künye ---
// Tema ve ek modüllerin kaynağı ile destek adresi. Sidebar YENİ bir dosya
// olduğu için bu blok HestiaCP güncellemelerinde silinmez. ?>
<div class="wd-credit">
	<a href="https://webdanismani.com" target="_blank" rel="noopener noreferrer">
		<span class="wd-credit-k">Tema &amp; modüller</span>
		<span class="wd-credit-v">webdanismani.com</span>
	</a>
	<a href="https://oblifex.com" target="_blank" rel="noopener noreferrer">
		<span class="wd-credit-k">Destek &amp; forum</span>
		<span class="wd-credit-v">oblifex.com</span>
	</a>
</div>
