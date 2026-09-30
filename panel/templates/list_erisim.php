<?php
/**
 * WebDanışmanı — "Access Monitoring" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_erisim.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$down = 0;
$yavas = 0;
foreach ($wd_siteler as $s) {
	if ($s["durum"] === "down") {
		$down++;
	} elseif ($s["durum"] === "yavas") {
		$yavas++;
	}
}
$rozet = function (string $durum): string {
	switch ($durum) {
		case "down":
			return '<span class="wdm-rozet wdm-rozet-err"><span class="wdm-nokta wdm-nokta-err"></span>' . wd_esc__("Unreachable") . '</span>';
		case "yavas":
			return '<span class="wdm-rozet wdm-rozet-warn"><span class="wdm-nokta wdm-nokta-warn"></span>' . wd_esc__("Slow") . '</span>';
		default:
			return '<span class="wdm-rozet wdm-rozet-ok"><span class="wdm-nokta wdm-nokta-ok"></span>' . wd_esc__("Up") . '</span>';
	}
};
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik(
				wd__("Uptime Monitoring"),
				wd__("Your sites are checked from outside every 5 minutes; you get a panel notification when they go down."),
			); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>

			<div class="wd-stats">
				<div class="wd-stat">
					<div class="wd-stat-label"><?= wd_esc__("MONITORED SITES") ?></div>
					<div class="wd-stat-value"><?= count($wd_siteler) ?></div>
				</div>
				<div class="wd-stat">
					<div class="wd-stat-label"><?= wd_esc__("UNREACHABLE") ?></div>
					<div class="wd-stat-value" style="<?= $down > 0 ? "color:var(--wd-red)" : "" ?>"><?= $down ?></div>
				</div>
				<div class="wd-stat">
					<div class="wd-stat-label"><?= wd_esc__("SLOW") ?></div>
					<div class="wd-stat-value" style="<?= $yavas > 0 ? "color:var(--wd-amber)" : "" ?>"><?= $yavas ?></div>
				</div>
				<div class="wd-stat">
					<div class="wd-stat-label"><?= wd_esc__("LAST CHECK") ?></div>
					<div class="wd-stat-value"><?= $wd_yas === null ? "—" : wd_e(wd_modul_sure($wd_yas)) ?><span class="wd-stat-of"><?= wd_esc__("ago") ?></span></div>
				</div>
			</div>

			<?php if (empty($wd_siteler)) { ?>
				<div class="wd-card"><div class="wdm-bos"><i class="fas fa-heart-pulse"></i>
					<?= $wd_veri === null ? wd_esc__("No check has run yet; the first result arrives in a few minutes.") : wd_esc__("No monitored sites.") ?>
				</div></div>
			<?php } else { ?>
				<?php foreach ($wd_siteler as $s) {
					$ds = $s["down_since"] ?? null; ?>
					<div class="wd-card">
						<div class="wd-card-head">
							<span style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
								<?= $rozet($s["durum"]) ?>
								<a href="https://<?= wd_e($s["domain"]) ?>/" target="_blank" rel="noopener" style="font-weight:500"><?= wd_e($s["domain"]) ?></a>
								<?php if ($wd_is_admin) { ?><span class="wd-card-note"><?= wd_e($s["user"]) ?></span><?php } ?>
							</span>
							<form method="post" style="margin:0">
								<?= wd_modul_form_gizli("simdi", $s["domain"]) ?>
								<button type="submit" class="wd-mini-btn" title="<?= wd_esc__("Probe from outside now") ?>"><?= wd_esc__("Check Now") ?></button>
							</form>
						</div>
						<div class="wd-card-body wd-card-body-pad">
							<div class="wdm-oge-orta" style="padding:0 0 8px">
								<span class="wdm-metrik"><b><?= wd_e(wd_modul_yuzde($s["uptime_24s"] ?? null)) ?></b><span><?= wd_esc__("24 hours") ?></span></span>
								<span class="wdm-metrik"><b><?= wd_e(wd_modul_yuzde($s["uptime_7g"] ?? null)) ?></b><span><?= wd_esc__("7 days") ?></span></span>
								<span class="wdm-metrik"><b><?= wd_e(wd_modul_yuzde($s["uptime_30g"] ?? null)) ?></b><span><?= wd_esc__("30 days") ?></span></span>
								<span class="wdm-metrik"><b><?= isset($s["ms"]) ? (int) $s["ms"] . " ms" : "—" ?></b><span><?= wd_esc__("last response") ?></span></span>
								<?php if (!empty($s["ortalama_ms_24s"])) { ?>
									<span class="wdm-metrik"><b><?= (int) $s["ortalama_ms_24s"] ?> ms</b><span><?= wd_esc__("24h avg") ?></span></span>
								<?php } ?>
								<span class="wdm-eskime"><?= wd_esc__("last check") ?> <?= wd_e(wd_modul_tarih($s["son_kontrol"] ?? null)) ?></span>
							</div>
							<?php if ($s["durum"] === "down" && $ds) { ?>
								<?php wd_modul_not(
									sprintf(wd__("Site has been unreachable since %s (%s)."), wd_modul_tarih((int) $ds), wd_modul_sure(time() - (int) $ds)),
									"err",
								); ?>
							<?php } ?>
							<div class="wdm-uptime" title="<?= wd_esc__("Last 8 hours, 5-minute intervals (left to right: oldest to newest)") ?>">
								<?php foreach ((array) ($s["gecmis"] ?? []) as $g) { ?>
									<span class="<?= $g === 0 ? "down" : ($g === 2 ? "yavas" : "up") ?>"></span>
								<?php } ?>
							</div>
							<?php if (!empty($s["kesinti"])) { ?>
								<details style="margin-top:10px">
									<summary style="cursor:pointer;font-size:11.5px;color:var(--wd-muted)"><?= wd_e(sprintf(wd__("Recent outages (%d)"), count($s["kesinti"]))) ?></summary>
									<div class="wdm-tablo-sar" style="margin-top:6px">
										<table class="wdm-tablo">
											<thead><tr><th><?= wd_esc__("Start") ?></th><th><?= wd_esc__("End") ?></th><th class="sag"><?= wd_esc__("Duration") ?></th></tr></thead>
											<tbody>
												<?php foreach ($s["kesinti"] as $k) { ?>
													<tr>
														<td><?= wd_e(wd_modul_tarih((int) $k["bas"])) ?></td>
														<td><?= wd_e(wd_modul_tarih((int) $k["bit"])) ?></td>
														<td class="sag"><?= wd_e(wd_modul_sure((int) $k["sure"])) ?></td>
													</tr>
												<?php } ?>
											</tbody>
										</table>
									</div>
								</details>
							<?php } ?>
						</div>
					</div>
				<?php } ?>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How It Works") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("What counts") ?></span>
						<span class="wd-v-small"><?= wd_esc__("HTTP 5xx, connection errors, timeouts, and SSL errors count as") ?> <b><?= wd_esc__("unreachable") ?></b>. <?= wd_esc__("Responses like 401/403/404 show the server answered and count as") ?> <b><?= wd_esc__("up") ?></b>.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Notification") ?></span>
						<span class="wd-v-small"><?= wd_esc__("After two consecutive failed checks (10 min) a panel notification is sent; when the site recovers a second notification includes the outage duration. One-off blips do not alarm.") ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Slow") ?></span>
						<span class="wd-v-small"><?= wd_e(sprintf(wd__("Shown in amber when response exceeds %d seconds; no notification is sent."), (int) (($wd_veri["yavas_ms"] ?? 3000) / 1000))) ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Limit") ?></span>
						<span class="wd-v-small"><?= wd_esc__("Checks run from this server; if the server itself is down this page also fails. Off-server monitoring needs a separate service.") ?></span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>
