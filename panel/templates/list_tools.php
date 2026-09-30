<?php
/**
 * WebDanışmanı — "Tools" page template
 * Install path: /usr/local/hestia/web/templates/pages/list_tools.php
 *
 * $panel, $user  -> provided by render_page()
 * $wd_*          -> provided by list/tools/index.php
 */

$p = $panel[$wd_user] ?? ($panel[$user] ?? []);
$tok = $_SESSION["token"] ?? "";
$pma = $_SESSION["DB_PMA_ALIAS"] ?? "phpmyadmin";
$wma = $_SESSION["WEBMAIL_ALIAS"] ?? "webmail";

$primary = $wd_primary["domain"] ?? null;
$pmail = $wd_primary_mail ?? null;

/* ---------------------------------------------------------------------------
   Tool groups. Only link to pages that actually exist;
   no box is produced for a feature with no HestiaCP counterpart.
   --------------------------------------------------------------------------- */
$groups = [];

/* --- WEB --- */
if (!empty($_SESSION["WEB_SYSTEM"]) && ($p["WEB_DOMAINS"] ?? "0") !== "0") {
	$t = [
		[wd__("Web Domains"), "/list/web/", "fa-globe"],
		[wd__("Add New Domain"), "/add/web/", "fa-circle-plus"],
	];
	if ($primary) {
		$t[] = [wd__("Domain Settings"), "/edit/web/?domain=" . urlencode($primary) . "&token=" . $tok, "fa-sliders"];
		$t[] = [wd__("SSL / Let's Encrypt"), "/edit/web/?domain=" . urlencode($primary) . "&token=" . $tok, "fa-lock"];
		if (($_SESSION["PLUGIN_APP_INSTALLER"] ?? "") === "true") {
			$t[] = [wd__("Quick Install"), "/add/webapp/?domain=" . urlencode($primary) . "&token=" . $tok, "fa-wand-magic-sparkles"];
		}
	}
	// web-log REQUIRES ?domain=; opening without it returns 500
	// (quoteshellarg crashes on an undefined key). No link if no domain.
	if ($primary) {
		$t[] = [wd__("Web Logs"), "/list/web-log/?domain=" . urlencode($primary) . "&type=access", "fa-file-lines"];
	}
	$t[] = [wd__("Web Statistics"), "/list/stats/", "fa-chart-line"];
	// Tools with NO stock panel counterpart, provided by this plugin
	$t[] = [wd__("Directory Password Protection"), "/list/httpauth/", "fa-lock"];
	$t[] = [wd__("Custom Error Pages"), "/list/errorpages/", "fa-triangle-exclamation"];
	$t[] = [wd__("Redirects"), "/list/yonlendirme/", "fa-right-left"];
	if (($_SESSION["FILE_MANAGER"] ?? "") === "true") {
		$t[] = [wd__("File Manager"), "/fm/", "fa-folder-open"];
	}
	$groups[] = ["key" => "web", "title" => wd__("WEB — Domains"), "icon" => "fa-earth-americas", "tools" => $t];
}

/* --- MAIL --- */
if (!empty($_SESSION["MAIL_SYSTEM"]) && ($p["MAIL_DOMAINS"] ?? "0") !== "0") {
	$t = [
		[wd__("Mail Domains"), "/list/mail/", "fa-envelopes-bulk"],
		[wd__("New Mail Domain"), "/add/mail/", "fa-circle-plus"],
	];
	if ($pmail) {
		$t[] = [wd__("Mail Accounts"), "/list/mail/?domain=" . urlencode($pmail), "fa-at"];
		$t[] = [wd__("New Mail Account"), "/add/mail/?domain=" . urlencode($pmail), "fa-user-plus"];
		$t[] = [wd__("Mail Settings"), "/edit/mail/?domain=" . urlencode($pmail) . "&token=" . $tok, "fa-shield-halved"];
		if (($_SESSION["WEBMAIL_SYSTEM"] ?? "") !== "") {
			$t[] = [wd__("Webmail"), "https://" . $wma . "." . $pmail . "/", "fa-inbox", true];
		}
	}
	// SPF/DKIM/DMARC/MX check — mail delivery issues often come from DNS,
	// so this is also reachable from the mail group.
	$t[] = [wd__("Mail Health Check"), "/list/health/", "fa-stethoscope"];
	if ($wd_is_admin) {
		$t[] = [wd__("Mail Report"), "/list/mailrapor/", "fa-chart-column"];
	}
	$groups[] = ["key" => "mail", "title" => wd__("MAIL — Email"), "icon" => "fa-envelopes-bulk", "tools" => $t];
}

/* --- DNS --- */
if (!empty($_SESSION["DNS_SYSTEM"]) && ($p["DNS_DOMAINS"] ?? "0") !== "0") {
	$t = [
		[wd__("DNS Zones"), "/list/dns/", "fa-book-atlas"],
		[wd__("Add New Zone"), "/add/dns/", "fa-circle-plus"],
	];
	if ($wd_primary_dns) {
		$t[] = [wd__("DNS Records"), "/list/dns/?domain=" . urlencode($wd_primary_dns), "fa-list-ul"];
		$t[] = [wd__("New DNS Record"), "/add/dns/?domain=" . urlencode($wd_primary_dns), "fa-plus"];
	}
	$groups[] = ["key" => "dns", "title" => wd__("DNS"), "icon" => "fa-book-atlas", "tools" => $t];
}

/* --- DATABASE --- */
if (!empty($_SESSION["DB_SYSTEM"]) && ($p["DATABASES"] ?? "0") !== "0") {
	$t = [
		[wd__("Databases"), "/list/db/", "fa-database"],
		[wd__("New Database"), "/add/db/", "fa-circle-plus"],
	];
	if ($primary && strpos($_SESSION["DB_SYSTEM"], "mysql") !== false) {
		$t[] = [wd__("phpMyAdmin"), "https://" . $primary . "/" . $pma . "/", "fa-table", true];
	}
	$groups[] = ["key" => "db", "title" => wd__("DATABASE"), "icon" => "fa-database", "tools" => $t];
}

/* --- CRON --- */
if (!empty($_SESSION["CRON_SYSTEM"]) && ($p["CRON_JOBS"] ?? "0") !== "0") {
	$groups[] = [
		"key" => "cron",
		"title" => wd__("CRON — Scheduled Jobs"),
		"icon" => "fa-clock",
		"tools" => [[wd__("Cron Jobs"), "/list/cron/", "fa-clock"], [wd__("Add New Job"), "/add/cron/", "fa-circle-plus"]],
	];
}

/* --- BACKUP --- */
if (!empty($_SESSION["BACKUP_SYSTEM"])) {
	$groups[] = [
		"key" => "backup",
		"title" => wd__("BACKUPS"),
		"icon" => "fa-file-zipper",
		"tools" => [[wd__("Backups"), "/list/backup/", "fa-file-zipper"]],
	];
}

/* --- ACCOUNT --- */
$t = [[wd__("Health Center"), "/list/health/", "fa-stethoscope"]];
$t[] = [wd__("Account Settings"), "/edit/user/?user=" . urlencode($wd_user) . "&token=" . $tok, "fa-circle-user"];
$t[] = [wd__("SSH Keys"), "/list/access-key/", "fa-key"];
$t[] = [wd__("Statistics"), "/list/stats/", "fa-chart-line"];
$t[] = [wd__("Resource History"), "/list/gecmis/", "fa-chart-area"];
// NOTE: /list/notifications/ is NOT a page, only an AJAX endpoint
// (HestiaCP 1.10.4 has no list_notifications.php template). Opening it
// directly yields a blank page; notifications are reached from the bell
// icon in the top bar, so no link is placed here.
if ($wd_is_admin) {
	$t[] = [wd__("Logs"), "/list/log/", "fa-clock-rotate-left"];
}
$groups[] = ["key" => "account", "title" => wd__("ACCOUNT"), "icon" => "fa-circle-user", "tools" => $t];

/* --- SERVER (admin only) --- */
if ($wd_is_admin) {
	$t = [
		[wd__("Server Settings"), "/list/server/", "fa-gear"],
		[wd__("Users"), "/list/user/", "fa-users"],
		[wd__("Hosting Packages"), "/list/package/", "fa-box-open"],
		[wd__("Firewall"), "/list/firewall/", "fa-shield-halved"],
		[wd__("IP Addresses"), "/list/ip/", "fa-network-wired"],
		[wd__("Updates"), "/list/updates/", "fa-rotate"],
		[wd__("Graphs"), "/list/rrd/", "fa-chart-area"],
		[wd__("Cloudflare"), "/list/cloudflare/", "fa-cloud"],
		[wd__("Security"), "/list/guvenlik/", "fa-shield-halved"],
	];
	if (($_SESSION["WEB_TERMINAL"] ?? "") === "true" && ($_SESSION["login_shell"] ?? "") !== "nologin") {
		$t[] = [wd__("Web Terminal"), "/list/terminal/", "fa-terminal"];
	}
	$groups[] = ["key" => "server", "title" => wd__("SERVER MANAGEMENT"), "icon" => "fa-server", "tools" => $t];
}

/* --- Extra modules (inc/wd-modul.php registry): installed ones are added to the matching group --- */
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
		// If group is missing (e.g. reseller has no "server" group) fall back to account
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
   Stat cards — only draw a card when real data exists.
   --------------------------------------------------------------------------- */
$cards = [];

/* Design: card has two lines: LABEL / <b>value</b> <small>of</small> + bar.
   "of" is secondary text — CPU and MEMORY are server-wide so they carry a
   "server ·" prefix there; DISK and BANDWIDTH are the user's own quota. */
$wd_quota_txt = function ($limit) {
	return $limit === "unlimited" || $limit === "" || $limit === null
		? wd__("/ unlimited")
		: "/ " . humanize_usage_size($limit) . " " . humanize_usage_measure($limit);
};

if ($wd_cpu !== null) {
	$cards[] = [
		"label" => wd__("CPU"),
		"value" => "%" . number_format($wd_cpu, 1, ".", ","),
		"of" => ($wd_load["cores"] ?? 1) . " vCPU",
		"tip" => wd__("Server-wide CPU usage"),
		"pct" => $wd_cpu,
	];
}
if ($wd_mem !== null) {
	$cards[] = [
		"label" => wd__("MEMORY"),
		"value" => number_format($wd_mem["used_kb"] / 1048576, 1, ".", ",") . " GB",
		"of" => "/ " . number_format($wd_mem["total_kb"] / 1048576, 1, ".", ",") . " GB",
		"tip" => wd__("Server-wide memory usage"),
		"pct" => $wd_mem["pct"],
	];
}
$cards[] = [
	"label" => wd__("DISK"),
	"value" => humanize_usage_size($p["U_DISK"] ?? 0) . " " . humanize_usage_measure($p["U_DISK"] ?? 0),
	"of" => $wd_quota_txt($p["DISK_QUOTA"] ?? "unlimited"),
	"pct" => wd_quota_pct($p["U_DISK"] ?? 0, $p["DISK_QUOTA"] ?? "unlimited"),
];
$cards[] = [
	"label" => wd__("BANDWIDTH"),
	"value" => humanize_usage_size($p["U_BANDWIDTH"] ?? 0) . " " . humanize_usage_measure($p["U_BANDWIDTH"] ?? 0),
	"of" => $wd_quota_txt($p["BANDWIDTH"] ?? "unlimited"),
	"pct" => wd_quota_pct($p["U_BANDWIDTH"] ?? 0, $p["BANDWIDTH"] ?? "unlimited"),
];

/* ---------------------------------------------------------------------------
   Package usage rows
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
	$add_usage(wd__("Web Domains"), $p["U_WEB_DOMAINS"] ?? 0, $p["WEB_DOMAINS"] ?? "unlimited");
}
if (!empty($_SESSION["DNS_SYSTEM"])) {
	$add_usage(wd__("DNS Zones"), $p["U_DNS_DOMAINS"] ?? 0, $p["DNS_DOMAINS"] ?? "unlimited");
}
if (!empty($_SESSION["MAIL_SYSTEM"])) {
	$add_usage(wd__("Mail Accounts"), $p["U_MAIL_ACCOUNTS"] ?? 0, $p["MAIL_ACCOUNTS"] ?? "unlimited");
}
if (!empty($_SESSION["DB_SYSTEM"])) {
	$add_usage(wd__("Databases"), $p["U_DATABASES"] ?? 0, $p["DATABASES"] ?? "unlimited");
}
if (!empty($_SESSION["CRON_SYSTEM"])) {
	$add_usage(wd__("Cron Jobs"), $p["U_CRON_JOBS"] ?? 0, $p["CRON_JOBS"] ?? "unlimited");
}
if (!empty($_SESSION["BACKUP_SYSTEM"])) {
	$add_usage(wd__("Backups"), $p["U_BACKUPS"] ?? 0, $p["BACKUPS"] ?? "unlimited");
}
?>

<div class="container">
	<div class="wd-page">

		<!-- ================= MAIN COLUMN ================= -->
		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title"><?= wd_esc__("Tools") ?></h1>
					<p class="wd-subtitle">
						<?= wd_esc__("All management tools for your hosting account — HestiaCP") ?> <?= wd_e($_SESSION["VERSION"] ?? "") ?>
					</p>
				</div>
				<div class="wd-page-actions">
					<button type="button" class="button button-secondary" data-wd-toggle="close"><?= wd_esc__("Close All") ?></button>
					<button type="button" class="button button-secondary" data-wd-toggle="open"><?= wd_esc__("Open All") ?></button>
				</div>
			</div>

			<!-- Stat cards -->
			<div class="wd-stats">
				<?php foreach ($cards as $c) { ?>
					<div class="wd-stat"<?= isset($c["tip"]) ? ' title="' . wd_e($c["tip"]) . '"' : "" ?>>
						<div class="wd-stat-label"><?= wd_e($c["label"]) ?></div>
						<div class="wd-stat-value">
							<?= wd_e($c["value"]) ?><span class="wd-stat-of"><?= wd_e($c["of"]) ?></span>
						</div>
						<?php if ($c["pct"] !== null) { ?>
							<div class="wd-bar <?= wd_level($c["pct"]) ?>" role="img"
								aria-label="<?= wd_e($c["label"]) ?>: %<?= wd_e(number_format($c["pct"], 1, ".", ",")) ?>">
								<span style="width: <?= max(2, min(100, $c["pct"])) ?>%"></span>
							</div>
						<?php } else { ?>
							<div class="wd-bar wd-bar-none" aria-hidden="true"><span style="width:100%"></span></div>
						<?php } ?>
					</div>
				<?php } ?>
			</div>

			<!-- Tool groups -->
			<?php foreach ($groups as $g) { ?>
				<details class="wd-group" data-wd-group="<?= wd_e($g["key"]) ?>" open>
					<summary class="wd-group-head">
						<span class="wd-group-icon"><i class="fas <?= wd_e($g["icon"]) ?>"></i></span>
						<span class="wd-group-title"><?= wd_e($g["title"]) ?></span>
						<span class="wd-group-count"><?= wd_e(sprintf(wd_n__("%d tool", "%d tools", count($g["tools"])), count($g["tools"]))) ?></span>
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

		<!-- ================= RIGHT RAIL ================= -->
		<aside class="wd-rail">

			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("Overview") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Current User") ?></span>
						<span class="wd-v"><?= wd_e($wd_user) ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Hosting Package") ?></span>
						<span class="wd-v">
							<?= wd_e($p["PACKAGE"] ?? "—") ?>
							<?php if (($p["DISK_QUOTA"] ?? "unlimited") !== "unlimited") { ?>
								<span class="wd-v-dim">· <?= wd_e(humanize_usage_size($p["DISK_QUOTA"]) . " " . humanize_usage_measure($p["DISK_QUOTA"])) ?></span>
							<?php } ?>
						</span>
					</div>
					<?php if ($primary) { ?>
						<div class="wd-kv">
							<span class="wd-k"><?= wd_esc__("Primary Domain") ?></span>
							<span class="wd-v"><a href="https://<?= wd_e($primary) ?>/" target="_blank" rel="noopener"><?= wd_e($primary) ?></a></span>
						</div>
						<div class="wd-kv">
							<span class="wd-k"><?= wd_esc__("Let's Encrypt SSL") ?></span>
							<span class="wd-v">
								<?php if ($wd_primary["letsencrypt"]) { ?>
									<span class="wd-dot wd-dot-ok"></span> <?= wd_esc__("Enabled") ?>
									<?php if ($wd_ssl_days !== null) { ?>
										<span class="wd-v-dim">· <?= $wd_ssl_days > 0
      	? wd_e(sprintf(wd__("renews in %d days"), $wd_ssl_days))
      	: wd_esc__("expired") ?></span>
									<?php } ?>
								<?php } elseif ($wd_primary["ssl"]) { ?>
									<span class="wd-dot wd-dot-warn"></span> <?= wd_esc__("SSL present (not LE)") ?>
								<?php } else { ?>
									<span class="wd-dot wd-dot-off"></span> <?= wd_esc__("Off") ?>
								<?php } ?>
							</span>
						</div>
						<?php if (!empty($wd_primary["ip"])) { ?>
							<div class="wd-kv">
								<span class="wd-k"><?= wd_esc__("Shared IP") ?></span>
								<span class="wd-v wd-mono"><?= wd_e($wd_primary["ip"]) ?></span>
							</div>
						<?php } ?>
					<?php } ?>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Home Directory") ?></span>
						<span class="wd-v wd-mono"><?= wd_e($p["HOME"] ?? "—") ?></span>
					</div>
					<?php if ($wd_login) { ?>
						<div class="wd-kv">
							<span class="wd-k"><?= wd_esc__("Last Login") ?></span>
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
							<span class="wd-k"><?= wd_esc__("Email") ?></span>
							<span class="wd-v"><?= wd_e($p["CONTACT"]) ?></span>
						</div>
					<?php } ?>
				</div>
			</div>

			<?php if (!empty($usage_rows)) { ?>
				<div class="wd-card">
					<div class="wd-card-head"><?= wd_esc__("Package Usage") ?></div>
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

			<?php /* --- Site resources: applied limit + live usage ---
			 *
			 * The limit is a PERMANENT site property; live usage is temporary:
			 * with `pm = ondemand` an idle site has no workers.
			 * So the list is built from the tier map, not from measurements;
			 * measurements are overlaid when present. Otherwise we could not
			 * see a site's limit while it is idle.
			 *
			 * Measurements are PHP consumption only; nginx/MariaDB are shared
			 * and cannot be attributed per site.
			 */
   $wd_olcum = ($wd_usage !== null && !empty($wd_usage["sites"])) ? $wd_usage["sites"] : [];
   $wd_satirlar = [];
   foreach (wd_kaynak_siteleri() as $dname => $meta) {
   	if (!$wd_is_admin && ($meta["user"] ?? "") !== $wd_user) {
   		continue;
   	}
   	$wd_satirlar[$dname] = ["kademe" => wd_site_kademe($dname), "olcum" => $wd_olcum[$dname] ?? null];
   }
   // Measured but not yet in the map (new domain not yet cached)
   foreach ($wd_olcum as $dname => $s) {
   	if (!isset($wd_satirlar[$dname])) {
   		$wd_satirlar[$dname] = ["kademe" => wd_site_kademe((string) $dname), "olcum" => $s];
   	}
   }
   // Running sites first, then by name
   uksort($wd_satirlar, function ($a, $b) use ($wd_satirlar) {
   	$ka = $wd_satirlar[$a]["olcum"] === null ? 1 : 0;
   	$kb = $wd_satirlar[$b]["olcum"] === null ? 1 : 0;
   	return $ka === $kb ? strcmp($a, $b) : $ka <=> $kb;
   });

   if (!empty($wd_satirlar)) { ?>
				<div class="wd-card">
					<div class="wd-card-head">
						<?= wd_esc__("Site Resources") ?>
						<span class="wd-card-note"><?= wd_esc__("limit · live PHP") ?></span>
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
											title="<?= wd_e(sprintf(wd__("Resource tier: %s — max %s memory, %s concurrent processes, request up to %s sec"), $kademe["ad"], $kademe["bellek"], $kademe["surec"], $kademe["sure"])) ?>">
											<?= wd_e($kademe["bellek"]) ?> · <?= wd_e(sprintf(wd__("%s processes"), $kademe["surec"])) ?>
										</span>
									<?php } else { ?>
										<span class="wd-limit-tag" title="<?= wd_esc__("No resource limit assigned to this site; stock template is used. On the server: wd-kaynak uygula") ?>"><?= wd_esc__("unlimited") ?></span>
									<?php } ?>
								</div>
								<?php if ($s !== null) {
       	[$mv, $mu] = wd_kb_human((int) $s["mem_kb"]); ?>
									<div class="wd-site-metrics">
										<span class="wd-site-metric">
											<i class="fas fa-microchip"></i>
											<b>%<?= wd_e(number_format($cpu, 1, ".", ",")) ?></b> cpu
										</span>
										<span class="wd-site-metric">
											<i class="fas fa-memory"></i>
											<b><?= wd_e($mv) ?></b> <?= wd_e($mu) ?>
										</span>
										<span class="wd-site-metric"><?= wd_e(sprintf(wd__("%s processes"), $s["procs"])) ?></span>
									</div>
									<div class="wd-bar <?= wd_level($cpu) ?>">
										<span style="width: <?= max(2, min(100, $cpu)) ?>%"></span>
									</div>
								<?php } else { ?>
									<div class="wd-site-metrics">
										<span class="wd-site-metric wd-site-idle"><?= wd_esc__("idle — no running PHP process") ?></span>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>

			<?php if ($wd_load !== null) { ?>
				<div class="wd-card">
					<div class="wd-card-head"><?= wd_esc__("Server") ?></div>
					<div class="wd-card-body">
						<div class="wd-kv">
							<span class="wd-k"><?= wd_esc__("System Load") ?></span>
							<span class="wd-v wd-mono"><?= wd_e(number_format($wd_load["l1"], 2, ".", ",")) ?>
								<span class="wd-v-dim">/ <?= wd_e(sprintf(wd__("%s cores"), $wd_load["cores"])) ?></span></span>
						</div>
						<div class="wd-kv">
							<span class="wd-k"><?= wd_esc__("Uptime") ?></span>
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
			try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) { /* private window */ }
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
