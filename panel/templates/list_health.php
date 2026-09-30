<?php
/**
 * WebDanışmanı — "Health Center" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_health.php
 *
 * $panel, $user  -> provided by render_page()
 * $wd_*          -> provided by list/health/index.php
 */

$tok = $_SESSION["token"] ?? "";

/* Links to the related panel page for each check. Reporting a problem is
   not enough; the user should reach the fix page in one click. */
$wd_duzelt = function (string $kid, string $domain) use ($tok) {
	switch ($kid) {
		case "mx":
		case "spf":
		case "dmarc":
			return ["/list/dns/?domain=" . urlencode($domain), wd__("Open DNS records")];
		case "dkim":
			return ["/edit/mail/?domain=" . urlencode($domain) . "&token=" . $tok, wd__("Open mail settings")];
		case "a":
			return ["/list/dns/?domain=" . urlencode($domain), wd__("Open DNS records")];
		case "ssl":
			return ["/edit/web/?domain=" . urlencode($domain) . "&token=" . $tok, wd__("Open SSL settings")];
		default:
			return null;
	}
};

$ozet = $wd_saglik["ozet"] ?? ["ok" => 0, "warn" => 0, "fail" => 0, "bilinmiyor" => 0];
$sorunlu = ($ozet["fail"] ?? 0) + ($ozet["warn"] ?? 0);

/* Checks that touch DNS records. If the domain's DNS is at another provider,
   linking to the panel DNS page is WRONG: the zone there is ignored and
   changes have no effect. */
$wd_dns_kontrolu = ["mx", "spf", "dmarc", "a", "dkim"];

/** Render one check row. */
$wd_kontrol_satiri = function (array $c, ?string $domain, array $dom = []) use (
	$wd_duzelt,
	$wd_dns_kontrolu
) {
	$durum = $c["durum"] ?? "bilinmiyor";
	$sorunlu = $durum === "fail" || $durum === "warn";
	$disarida = !empty($dom["dns_disarida"]);
	$dns_ile_ilgili = in_array($c["id"] ?? "", $wd_dns_kontrolu, true); ?>
	<div class="wd-check wd-check-<?= wd_e($durum) ?>">
		<span class="wd-check-dot <?= wd_e(wd_health_sinif($durum)) ?>"
			title="<?= wd_e(wd_health_etiket($durum)) ?>" aria-label="<?= wd_e(wd_health_etiket($durum)) ?>"></span>
		<div class="wd-check-body">
			<div class="wd-check-top">
				<span class="wd-check-label"><?= wd_e(wd__($c["label"] ?? "")) ?></span>
				<?php if (($c["deger"] ?? "") !== "") { ?>
					<span class="wd-check-value wd-mono"><?= wd_e($c["deger"]) ?></span>
				<?php } ?>
			</div>
			<?php if (!empty($c["not"])) { ?>
				<p class="wd-check-note"><?= wd_e(wd__($c["not"])) ?></p>
			<?php } ?>
			<?php if ($sorunlu && !empty($c["cozum"])) { ?>
				<p class="wd-check-fix">
					<i class="fas fa-screwdriver-wrench"></i>
					<span><?= wd_e(wd__($c["cozum"])) ?></span>
				</p>
			<?php }
   if ($sorunlu && $domain !== null) {
   	if ($disarida && $dns_ile_ilgili) {
   		// DNS at another provider: panel DNS page does not affect this record.
   		$ns = !empty($dom["ns"]) ? implode(", ", array_slice($dom["ns"], 0, 3)) : "";
   		?>
					<p class="wd-check-disari">
						<i class="fas fa-circle-info"></i>
						<span>
							<?= wd_esc__("You cannot fix this record from the panel") ?> — <?= wd_esc__("the domain's DNS is not on this server.") ?>
							<?php if ($ns !== "") { ?>
								<?= wd_esc__("Managing nameserver:") ?> <span class="wd-mono"><?= wd_e($ns) ?></span>.
							<?php } ?>
							<?= wd_esc__("Add the record in that provider's DNS panel.") ?>
						</span>
					</p>
				<?php } else {
   		$link = $wd_duzelt($c["id"] ?? "", $domain);
   		if ($link !== null) { ?>
					<a class="wd-check-link" href="<?= wd_e($link[0]) ?>"><?= wd_e($link[1]) ?> <i class="fas fa-arrow-right"></i></a>
				<?php }
   	}
   } ?>
		</div>
	</div>
<?php };

/** Find the worst status in a domain block (for the badge). */
$wd_en_kotu = function (array $checks) {
	$sira = ["ok" => 0, "bilinmiyor" => 1, "warn" => 2, "fail" => 3];
	$en = "ok";
	foreach ($checks as $c) {
		$d = $c["durum"] ?? "bilinmiyor";
		if (($sira[$d] ?? 0) > ($sira[$en] ?? 0)) {
			$en = $d;
		}
	}
	return $en;
};
?>

<div class="container">
	<div class="wd-page">

		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title"><?= wd_esc__("Health Center") ?></h1>
					<p class="wd-subtitle">
						<?= wd_esc__("Compares settings stored in the panel with what DNS actually shows from outside — mail delivery, domain routing, and SSL are checked here.") ?>
					</p>
				</div>
				<div class="wd-page-actions">
					<a class="button button-secondary" href="/list/health/?yenile=1&amp;token=<?= wd_e($tok) ?>">
						<i class="fas fa-rotate"></i> <?= wd_esc__("Check Now") ?>
					</a>
				</div>
			</div>

			<?php if (!empty($wd_yenilendi)) { ?>
				<div class="wd-note wd-note-ok">
					<i class="fas fa-circle-check"></i>
					<span><?= wd_esc__("Check re-run; the results below were just collected.") ?></span>
				</div>
			<?php } ?>

			<?php if ($wd_saglik === null) { ?>

				<div class="wd-note wd-note-warn">
					<i class="fas fa-triangle-exclamation"></i>
					<span>
						<?= wd_esc__("The check tool is not installed or cannot run.") ?>
						<?= wd_esc__("On the server run") ?> <span class="wd-mono">bash /usr/local/hestia/wd/src/kur.sh</span>.
					</span>
				</div>

			<?php } else { ?>

				<!-- Summary cards -->
				<div class="wd-stats">
					<div class="wd-stat">
						<div class="wd-stat-label"><?= wd_esc__("ISSUES") ?></div>
						<div class="wd-stat-value"><?= wd_e($ozet["fail"] ?? 0) ?><span class="wd-stat-of"><?= wd_esc__("urgent") ?></span></div>
						<div class="wd-bar <?= ($ozet["fail"] ?? 0) > 0 ? "wd-crit" : "wd-ok" ?>">
							<span style="width: <?= ($ozet["fail"] ?? 0) > 0 ? 100 : 2 ?>%"></span>
						</div>
					</div>
					<div class="wd-stat">
						<div class="wd-stat-label"><?= wd_esc__("WARNINGS") ?></div>
						<div class="wd-stat-value"><?= wd_e($ozet["warn"] ?? 0) ?><span class="wd-stat-of"><?= wd_esc__("review") ?></span></div>
						<div class="wd-bar <?= ($ozet["warn"] ?? 0) > 0 ? "wd-warn" : "wd-ok" ?>">
							<span style="width: <?= ($ozet["warn"] ?? 0) > 0 ? 100 : 2 ?>%"></span>
						</div>
					</div>
					<div class="wd-stat">
						<div class="wd-stat-label"><?= wd_esc__("OK") ?></div>
						<div class="wd-stat-value"><?= wd_e($ozet["ok"] ?? 0) ?><span class="wd-stat-of"><?= wd_esc__("checks") ?></span></div>
						<div class="wd-bar wd-ok"><span style="width:100%"></span></div>
					</div>
					<?php if (($ozet["bilinmiyor"] ?? 0) > 0) { ?>
						<div class="wd-stat" title="<?= wd_esc__("Query failed — unknown does not mean there is a problem") ?>">
							<div class="wd-stat-label"><?= wd_esc__("UNKNOWN") ?></div>
							<div class="wd-stat-value"><?= wd_e($ozet["bilinmiyor"]) ?><span class="wd-stat-of"><?= wd_esc__("not queried") ?></span></div>
							<div class="wd-bar wd-bar-none" aria-hidden="true"><span style="width:100%"></span></div>
						</div>
					<?php } ?>
				</div>

				<?php if ($sorunlu === 0) { ?>
					<div class="wd-note wd-note-ok">
						<i class="fas fa-circle-check"></i>
						<span><?= wd_esc__("All checks passed. Nothing needs fixing in your mail, domain, or SSL settings.") ?></span>
					</div>
				<?php } ?>

				<?php // --- Server level (admin only) ---
    if (!empty($wd_saglik["sunucu"])) { ?>
					<details class="wd-group" open>
						<summary class="wd-group-head">
							<span class="wd-group-icon"><i class="fas fa-server"></i></span>
							<span class="wd-group-title"><?= wd_esc__("Server") ?></span>
							<span class="wd-group-count"><?= wd_e($wd_saglik["hostname"] ?? "") ?></span>
							<i class="fas fa-chevron-down wd-group-chevron"></i>
						</summary>
						<div class="wd-checks">
							<?php foreach ($wd_saglik["sunucu"] as $c) {
       	$wd_kontrol_satiri($c, null);
       } ?>
						</div>
					</details>
				<?php } ?>

				<?php // --- Backups ---
    // Taking a backup is not enough; you need to know it can be restored.
    // This section opens the archive and checks readability.
    foreach ($wd_saglik["yedek"] ?? [] as $y) {
    	$kotu = $wd_en_kotu($y["checks"] ?? []); ?>
					<details class="wd-group" <?= $kotu === "ok" ? "" : "open" ?>>
						<summary class="wd-group-head">
							<span class="wd-group-icon"><i class="fas fa-file-zipper"></i></span>
							<span class="wd-group-title"><?= wd_esc__("Backups") ?><?= count($wd_saglik["yedek"]) > 1
       	? " — " . wd_e($y["user"])
       	: "" ?></span>
							<span class="wd-hs-badge <?= wd_e(wd_health_sinif($kotu)) ?>"><?= wd_e(wd_health_etiket($kotu)) ?></span>
							<i class="fas fa-chevron-down wd-group-chevron"></i>
						</summary>
						<div class="wd-checks">
							<?php foreach ($y["checks"] as $c) {
       	$wd_kontrol_satiri($c, null);
       } ?>
						</div>
					</details>
				<?php } ?>

				<?php // --- Mail domains ---
    foreach ($wd_saglik["mail"] ?? [] as $d) {
    	$kotu = $wd_en_kotu($d["checks"] ?? []); ?>
					<details class="wd-group" <?= $kotu === "ok" ? "" : "open" ?>>
						<summary class="wd-group-head">
							<span class="wd-group-icon"><i class="fas fa-envelopes-bulk"></i></span>
							<span class="wd-group-title"><?= wd_e($d["domain"]) ?></span>
							<span class="wd-hs-badge <?= wd_e(wd_health_sinif($kotu)) ?>"><?= wd_e(wd_health_etiket($kotu)) ?></span>
							<span class="wd-group-count">mail</span>
							<i class="fas fa-chevron-down wd-group-chevron"></i>
						</summary>
						<div class="wd-checks">
							<?php foreach ($d["checks"] as $c) {
       	$wd_kontrol_satiri($c, $d["domain"], $d);
       } ?>
						</div>
					</details>
				<?php } ?>

				<?php // --- Web domains ---
    foreach ($wd_saglik["web"] ?? [] as $d) {
    	$kotu = $wd_en_kotu($d["checks"] ?? []); ?>
					<details class="wd-group" <?= $kotu === "ok" ? "" : "open" ?>>
						<summary class="wd-group-head">
							<span class="wd-group-icon"><i class="fas fa-earth-americas"></i></span>
							<span class="wd-group-title"><?= wd_e($d["domain"]) ?></span>
							<span class="wd-hs-badge <?= wd_e(wd_health_sinif($kotu)) ?>"><?= wd_e(wd_health_etiket($kotu)) ?></span>
							<span class="wd-group-count">web</span>
							<i class="fas fa-chevron-down wd-group-chevron"></i>
						</summary>
						<div class="wd-checks">
							<?php foreach ($d["checks"] as $c) {
       	$wd_kontrol_satiri($c, $d["domain"], $d);
       } ?>
						</div>
					</details>
				<?php } ?>

				<?php if (empty($wd_saglik["mail"]) && empty($wd_saglik["web"]) && empty($wd_saglik["sunucu"])) { ?>
					<div class="wd-note">
						<i class="fas fa-circle-info"></i>
						<span><?= wd_esc__("No domains to check. After you add a web or mail domain they appear here.") ?></span>
					</div>
				<?php } ?>

			<?php } ?>

		</div>

		<!-- ================= RIGHT RAIL ================= -->
		<aside class="wd-rail">

			<?php if ($wd_saglik !== null) { ?>
				<div class="wd-card">
					<div class="wd-card-head"><?= wd_esc__("Check") ?></div>
					<div class="wd-card-body">
						<div class="wd-kv">
							<span class="wd-k"><?= wd_esc__("Last Check") ?></span>
							<span class="wd-v">
								<?php $ts = (int) ($wd_saglik["ts"] ?? 0);
        if ($ts > 0) {
        	$fark = max(0, time() - $ts);
        	echo wd_e(date("d.m.Y H:i", $ts));
        	echo '<span class="wd-v-dim"> · ' . wd_e(wd_human_uptime($fark)) . " " . wd__("ago") . "</span>";
        } else {
        	echo "—";
        } ?>
							</span>
						</div>
						<?php if (!empty($wd_saglik["ip"])) { ?>
							<div class="wd-kv">
								<span class="wd-k"><?= wd_esc__("Server IP") ?></span>
								<span class="wd-v wd-mono"><?= wd_e($wd_saglik["ip"]) ?></span>
							</div>
						<?php } ?>
						<div class="wd-kv">
							<span class="wd-k"><?= wd_esc__("Checked") ?></span>
							<span class="wd-v">
								<?= count($wd_saglik["mail"] ?? []) ?> mail ·
								<?= count($wd_saglik["web"] ?? []) ?> <?= wd_esc__("web domains") ?>
							</span>
						</div>
					</div>
				</div>
			<?php } ?>

			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("What Do the Checks Mean?") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">MX</span>
						<span class="wd-v-small"><?= wd_esc__("Which server receives mail for this domain from outside. If wrong, mail never arrives.") ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">SPF</span>
						<span class="wd-v-small"><?= wd_esc__("Which servers may send mail for this domain. Without it, messages land in spam.") ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">DKIM</span>
						<span class="wd-v-small"><?= wd_esc__("Signs mail. Even if enabled in the panel, the signature cannot be verified if the DNS record is missing.") ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">DMARC</span>
						<span class="wd-v-small"><?= wd_esc__("What receivers do when SPF/DKIM fail. Without it, others can spoof mail as you.") ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("A record") ?></span>
						<span class="wd-v-small"><?= wd_esc__("Which server the domain resolves to. If different from here, the site is not served from this server.") ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">SSL</span>
						<span class="wd-v-small"><?= wd_esc__("Remaining certificate lifetime. Auto-renewal can fail silently.") ?></span>
					</div>
				</div>
			</div>

		</aside>
	</div>
</div>
