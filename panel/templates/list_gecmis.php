<?php
/**
 * WebDanışmanı — "Resource History" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_gecmis.php
 */

$tok = $_SESSION["token"] ?? "";
$siteler = $wd_gecmis["siteler"] ?? [];
$seri = $wd_gecmis["seri"] ?? [];

/* Per-site hourly series for sparklines. */
$site_seri = [];
foreach ($seri as $s) {
	$site_seri[$s["site"]][] = $s;
}

/* Highest peak across all sites — bars scale to this so sites are comparable. */
$tepe = 0.0;
foreach ($seri as $s) {
	$tepe = max($tepe, (float) $s["cpu_max"]);
}
$tepe = max($tepe, 1.0);
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title"><?= wd_esc__("Resource History") ?></h1>
					<p class="wd-subtitle">
						<?= wd_esc__("Per-site CPU and memory usage over time — only PHP consumption is measured.") ?>
					</p>
				</div>
			</div>

			<?php if ($wd_gecmis === null) { ?>
				<div class="wd-note wd-note-warn">
					<i class="fas fa-triangle-exclamation"></i>
					<span><?= wd_esc__("History tool could not run. On the server run") ?>
						<span class="wd-mono">bash /usr/local/hestia/wd/src/kur.sh</span>.</span>
				</div>
			<?php } elseif (empty($siteler)) { ?>
				<div class="wd-note">
					<i class="fas fa-circle-info"></i>
					<span>
						<?= wd_esc__("No records for this period. The collector samples every 5 minutes; charts become meaningful a few hours after install.") ?>
					</span>
				</div>
			<?php } else { ?>

				<div class="wd-err-tabs" role="tablist">
					<?php foreach ([1, 7, 30] as $g) { ?>
						<a class="wd-err-tab<?= $g === $wd_gun ? " aktif" : "" ?>"
							href="/list/gecmis/?gun=<?= $g ?>">
							<span class="wd-err-kod"><?= $g ?></span>
							<span class="wd-err-ad"><?= wd_e(wd_n__("day", "days", $g)) ?></span>
						</a>
					<?php } ?>
				</div>

				<div class="wd-card">
					<div class="wd-card-head">
						<?= wd_esc__("Sites") ?>
						<span class="wd-card-note"><?php
      $ornek = (int) ($wd_gecmis["ornek"] ?? 0);
      echo wd_e(sprintf(wd_n__("%d sample", "%d samples", $ornek), $ornek));
      ?></span>
					</div>
					<div class="wd-card-body">
						<?php foreach ($siteler as $s) {
       	$noktalar = $site_seri[$s["site"]] ?? [];
       	$olcum = (int) $s["ornek"]; ?>
							<div class="wd-usage">
								<div class="wd-usage-top">
									<span class="wd-usage-label wd-usage-site" title="<?= wd_e($s["site"]) ?>"><?= wd_e($s["site"]) ?></span>
									<span class="wd-usage-num">
										<?= wd_esc__("peak") ?> <?= wd_e(number_format($s["cpu_max"], 1, ".", ",")) ?>%
									</span>
								</div>
								<div class="wd-site-metrics">
									<span class="wd-site-metric"><?= wd_esc__("avg") ?> <b><?= wd_e(number_format($s["cpu_ort"], 1, ".", ",")) ?>%</b></span>
									<span class="wd-site-metric"><?= wd_esc__("mem peak") ?> <b><?= wd_e(wd_bayt((int) $s["mem_max"] * 1024)) ?></b></span>
									<span class="wd-site-metric"><?= wd_e(sprintf(wd_n__("%d reading", "%d readings", $olcum), $olcum)) ?></span>
								</div>

								<?php // Simple column chart from hourly peaks.
        // Peak is drawn, not average: a short but site-locking spike is hidden by averages.
        if (!empty($noktalar)) { ?>
									<div class="wd-spark" role="img"
										aria-label="<?= wd_e($s["site"]) ?> <?= wd_esc__("hourly peak CPU usage") ?>">
										<?php foreach (array_slice($noktalar, -72) as $n) {
           	$y = max(2, min(100, $n["cpu_max"] / $tepe * 100));
           	$sinif = $n["cpu_max"] >= $tepe * 0.75 ? " yuksek" : ""; ?>
											<span class="wd-spark-cubuk<?= $sinif ?>" style="height: <?= round($y, 1) ?>%"
												title="<?= wd_e(date("d.m H:i", (int) $n["t"])) ?> — <?= wd_esc__("peak") ?> <?= wd_e(number_format($n["cpu_max"], 1, ".", ",")) ?>%"></span>
										<?php } ?>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
					</div>
				</div>

			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How to Read This?") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Why peak") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("Bars show hourly") ?>
							<b><?= wd_esc__("peak") ?></b>
							<?= wd_esc__("values, not averages. A five-minute load that locks the server is hidden by averages.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("What is measured") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("Only the site's PHP workers. nginx, Apache, and MariaDB are shared and cannot be split per site.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Idle periods") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("With") ?>
							<span class="wd-mono">pm = ondemand</span>
							<?= wd_esc__("an idle site has no workers; zero means “no usage”, not “no data”.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Retention") ?></span>
						<span class="wd-v-small"><?= wd_esc__("30 days; older records are deleted automatically.") ?></span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>
