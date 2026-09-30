<?php
/**
 * WebDanışmanı — sidebar
 * Installs to: /usr/local/hestia/web/templates/includes/wd-sidebar.php
 *
 * Replaces the stock <ul class="main-menu-list"> block in panel.php.
 * $panel, $user, $TAB come from panel.php scope.
 *
 * Stock class names (main-menu-*) are kept on purpose so theme CSS still
 * works after an unpatched HestiaCP update.
 */

require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-helpers.php";

$wd_p = $panel[$user] ?? [];
$wd_adm = $_SESSION["userContext"] === "admin" && ($_SESSION["look"] ?? "") === "";
$wd_imp = !empty($_SESSION["look"]);

/** Render one menu item. */
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
	// Long label for sidebar; short label for <1024px horizontal menu.
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

/** "used / limit" tooltip text. */
$wd_tip = function ($label, $used, $limit) {
	return $label . ": " . $used . " / " . ($limit === "unlimited" ? "∞" : $limit);
};
?>
<ul x-cloak x-show="open" class="main-menu-list">

	<?php
 $wd_item([
 	"tabs" => ["TOOLS"],
 	"label" => wd__("Tools"), "short" => wd__("TOOLS"),
 	"icon" => "fa-grip",
 	"href" => "/list/tools/",
 	"title" => wd__("All management tools"),
 ]); ?>

	<?php
 // Badge from CACHE only — no DNS/sudo here.
 $wd_hs = wd_health_sorun_sayisi(
 	empty($_SESSION["look"]) ? $_SESSION["user"] : $_SESSION["look"],
 	$wd_adm,
 );
 $wd_item([
 	"tabs" => ["HEALTH"],
 	"label" => wd__("Health Center"), "short" => wd__("HEALTH"),
 	"icon" => "fa-stethoscope",
 	"href" => "/list/health/",
 	"badge" => $wd_hs !== null && $wd_hs > 0 ? $wd_hs : null,
 	"badge_class" => "wd-menu-badge-alert",
 	"title" => $wd_hs === null
 		? wd__("Mail, DNS and SSL checks")
 		: ($wd_hs > 0
 			? sprintf(wd_n__("%d check needs attention", "%d checks need attention", $wd_hs), $wd_hs)
 			: wd__("All checks OK")),
 ]); ?>

	<?php
 if ($wd_adm) {
 	$wd_item([
 		"tabs" => ["GUVENLIK"],
 		"label" => wd__("Security"), "short" => wd__("SECURITY"),
 		"icon" => "fa-shield-halved",
 		"href" => "/list/guvenlik/",
 		"title" => wd__("Server hardening and malware scan"),
 	]);
 } ?>

	<?php
 $wd_item([
 	"tabs" => ["DISK"],
 	"label" => wd__("Disk Usage"), "short" => wd__("DISK"),
 	"icon" => "fa-hard-drive",
 	"href" => "/list/disk/",
 	"title" => wd__("Shows where disk space is used"),
 ]); ?>

	<?php
 if ($wd_adm) {
 	$uc = $wd_p["U_USERS"] ?? 0;
 	if (($_SESSION["user"] ?? "") !== "admin" && ($_SESSION["POLICY_SYSTEM_HIDE_ADMIN"] ?? "") === "yes") {
 		$uc = max(0, $uc - 1);
 	}
 	$wd_item([
 		"tabs" => ["USER", "LOG"],
 		"label" => wd__("Users"), "short" => wd__("USERS"),
 		"icon" => "fa-users",
 		"href" => "/list/user/",
 		"badge" => $uc,
 		"title" => sprintf(wd__("Users: %s · Suspended: %s"), $uc, $wd_p["SUSPENDED_USERS"] ?? 0),
 	]);
 } ?>

	<?php
 if (!empty($_SESSION["WEB_SYSTEM"]) && ($wd_p["WEB_DOMAINS"] ?? "0") !== "0") {
 	$wd_item([
 		"tabs" => ["WEB"],
 		"label" => wd__("WEB — Domains"), "short" => "WEB",
 		"icon" => "fa-earth-americas",
 		"href" => "/list/web/",
 		"badge" => $wd_p["U_WEB_DOMAINS"] ?? 0,
 		"title" => $wd_tip(wd__("Domains"), $wd_p["U_WEB_DOMAINS"] ?? 0, $wd_p["WEB_DOMAINS"] ?? "unlimited") .
 			" · " . sprintf(wd__("Aliases: %s"), $wd_p["U_WEB_ALIASES"] ?? 0) .
 			" · " . sprintf(wd__("Suspended: %s"), $wd_p["SUSPENDED_WEB"] ?? 0),
 	]);
 } ?>

	<?php
 if (!empty($_SESSION["DNS_SYSTEM"]) && ($wd_p["DNS_DOMAINS"] ?? "0") !== "0") {
 	$wd_item([
 		"tabs" => ["DNS"],
 		"label" => wd__("DNS Zones"), "short" => "DNS",
 		"icon" => "fa-book-atlas",
 		"href" => "/list/dns/",
 		"badge" => $wd_p["U_DNS_DOMAINS"] ?? 0,
 		"title" => $wd_tip(wd__("Zones"), $wd_p["U_DNS_DOMAINS"] ?? 0, $wd_p["DNS_DOMAINS"] ?? "unlimited") .
 			" · " . sprintf(wd__("Records: %s"), $wd_p["U_DNS_RECORDS"] ?? 0),
 	]);
 } ?>

	<?php
 if (!empty($_SESSION["MAIL_SYSTEM"]) && ($wd_p["MAIL_DOMAINS"] ?? "0") !== "0") {
 	$wd_item([
 		"tabs" => ["MAIL"],
 		"label" => wd__("MAIL — Email"), "short" => wd__("MAIL"),
 		"icon" => "fa-envelopes-bulk",
 		"href" => "/list/mail/",
 		"badge" => $wd_p["U_MAIL_ACCOUNTS"] ?? 0,
 		"title" => $wd_tip(wd__("Domains"), $wd_p["U_MAIL_DOMAINS"] ?? 0, $wd_p["MAIL_DOMAINS"] ?? "unlimited") .
 			" · " . sprintf(wd__("Accounts: %s"), $wd_p["U_MAIL_ACCOUNTS"] ?? 0),
 	]);
 } ?>

	<?php
 if (!empty($_SESSION["DB_SYSTEM"]) && ($wd_p["DATABASES"] ?? "0") !== "0") {
 	$wd_item([
 		"tabs" => ["DB"],
 		"label" => wd__("DB — Databases"), "short" => "DB",
 		"icon" => "fa-database",
 		"href" => "/list/db/",
 		"badge" => $wd_p["U_DATABASES"] ?? 0,
 		"title" => $wd_tip(wd__("Databases"), $wd_p["U_DATABASES"] ?? 0, $wd_p["DATABASES"] ?? "unlimited"),
 	]);
 } ?>

	<?php
 if (!empty($_SESSION["CRON_SYSTEM"]) && ($wd_p["CRON_JOBS"] ?? "0") !== "0") {
 	$wd_item([
 		"tabs" => ["CRON"],
 		"label" => wd__("CRON Jobs"), "short" => "CRON",
 		"icon" => "fa-clock",
 		"href" => "/list/cron/",
 		"badge" => $wd_p["U_CRON_JOBS"] ?? 0,
 		"title" => $wd_tip(wd__("Jobs"), $wd_p["U_CRON_JOBS"] ?? 0, $wd_p["CRON_JOBS"] ?? "unlimited"),
 	]);
 } ?>

	<?php
 if (
 	!empty($_SESSION["BACKUP_SYSTEM"]) &&
 	(($wd_p["BACKUPS"] ?? "0") !== "0" ||
 		($wd_p["U_BACKUPS"] ?? "0") !== "0" ||
 		($wd_p["BACKUPS_INCREMENTAL"] ?? "no") === "yes")
 ) {
 	$wd_item([
 		"tabs" => ["BACKUP"],
 		"label" => wd__("Backups"), "short" => wd__("BACKUP"),
 		"icon" => "fa-file-zipper",
 		"href" => "/list/backup/",
 		"badge" => $wd_p["U_BACKUPS"] ?? 0,
 		"title" => $wd_tip(wd__("Backups"), $wd_p["U_BACKUPS"] ?? 0, $wd_p["BACKUPS"] ?? "unlimited"),
 	]);
 } ?>

	<li class="main-menu-sep" aria-hidden="true"></li>

	<?php
 // Extra modules — short list only; full list on Tools page.
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

	<?php
 if (($_SESSION["FILE_MANAGER"] ?? "") === "true") {
 	$hide_fm = $_SESSION["userContext"] === "admin" && ($_SESSION["look"] ?? "") === "admin" && ($_SESSION["POLICY_SYSTEM_PROTECTED_ADMIN"] ?? "") === "yes";
 	if (!$hide_fm) {
 		$wd_item(["tabs" => ["FM"], "label" => wd__("File Manager"), "short" => wd__("FILES"), "icon" => "fa-folder-open", "href" => "/fm/"]);
 	}
 } ?>

	<?php
 if (($_SESSION["WEB_TERMINAL"] ?? "") === "true" && ($_SESSION["login_shell"] ?? "") !== "nologin") {
 	$wd_item(["tabs" => ["TERMINAL"], "label" => wd__("Web Terminal"), "short" => wd__("TERMINAL"), "icon" => "fa-terminal", "href" => "/list/terminal/"]);
 } ?>

	<?php
 $wd_item(["tabs" => ["STATS"], "label" => wd__("Statistics"), "short" => wd__("STATS"), "icon" => "fa-chart-line", "href" => "/list/stats/"]); ?>

	<?php
 if ((($_SESSION["userContext"] === "admin" && ($_SESSION["POLICY_SYSTEM_HIDE_SERVICES"] ?? "") !== "yes") ||
 	($_SESSION["user"] ?? "") === ($_SESSION["ROOT_USER"] ?? "")) && !$wd_imp) {
 	$wd_item([
 		"tabs" => ["SERVER", "IP", "RRD", "FIREWALL", "UPDATES", "PACKAGE", "NOTIFICATIONS"],
 		"label" => wd__("Server Settings"), "short" => wd__("SERVER"),
 		"icon" => "fa-gear",
 		"href" => "/list/server/",
 	]);
 } ?>

	<?php
 if ($wd_adm) {
 	$wd_item(["tabs" => ["LOG"], "label" => wd__("Logs"), "short" => wd__("LOGS"), "icon" => "fa-clock-rotate-left", "href" => "/list/log/"]);
 } ?>

</ul>

<?php
if (true) {
	$wd_l = wd_load();
	if ($wd_l !== null) { ?>
		<div class="wd-load">
			<div class="wd-load-label"><?= wd_esc__("SERVER LOAD") ?></div>
			<div class="wd-load-value">
				<?= wd_e(number_format($wd_l["l1"], 2, ".", ",")) ?><span class="wd-load-cores">/ <?= wd_e($wd_l["cores"]) ?> <?= wd_esc__("cores") ?></span>
			</div>
			<div class="wd-bar <?= wd_level($wd_l["pct"]) ?>"
				role="img" aria-label="<?= wd_e(sprintf(wd__("System load: %s / %s cores"), number_format($wd_l["l1"], 2, ".", ","), $wd_l["cores"])) ?>">
				<span style="width: <?= $wd_l["pct"] === null ? 4 : max(2, min(100, $wd_l["pct"])) ?>%"></span>
			</div>
			<div class="wd-load-uptime"><?= wd_esc__("Uptime") ?> <?= wd_e(wd_human_uptime(wd_uptime_seconds())) ?></div>
		</div>
	<?php }
} ?>

<div class="wd-credit">
	<a href="https://webdanismani.com" target="_blank" rel="noopener noreferrer">
		<span class="wd-credit-k"><?= wd_esc__("Theme & modules") ?></span>
		<span class="wd-credit-v">webdanismani.com</span>
	</a>
	<a href="https://oblifex.com" target="_blank" rel="noopener noreferrer">
		<span class="wd-credit-k"><?= wd_esc__("Support & forum") ?></span>
		<span class="wd-credit-v">oblifex.com</span>
	</a>
</div>
