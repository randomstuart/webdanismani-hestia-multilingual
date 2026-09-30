<?php
/**
 * WebDanışmanı — "Mail Report" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_mailrapor.php
 */

$tok = $_SESSION["token"] ?? "";
$t = $wd_rapor["toplam"] ?? ["kabul" => 0, "teslim" => 0, "sekme" => 0, "ertelenen" => 0];
$kuyruk = $wd_rapor["kuyruk"] ?? ["bekleyen" => 0, "donmus" => 0];
$sekme_orani = $t["kabul"] > 0 ? round($t["sekme"] / $t["kabul"] * 100, 1) : 0.0;
?>

<div class="container">
	<div class="wd-page">

		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title"><?= wd_esc__("Mail Report") ?></h1>
					<p class="wd-subtitle">
						<?= wd_esc__("Who is sending how much mail, and what is bouncing. Spot a compromised account here before the IP hits a blacklist.") ?>
					</p>
				</div>
				<div class="wd-page-actions">
					<a class="button button-secondary" href="/list/mailrapor/?yenile=1&amp;gun=<?= (int) $wd_gun ?>&amp;token=<?= wd_e($tok) ?>">
						<i class="fas fa-rotate"></i> <?= wd_esc__("Rescan") ?>
					</a>
				</div>
			</div>

			<?php if (!empty($wd_yenilendi)) { ?>
				<div class="wd-note wd-note-ok">
					<i class="fas fa-circle-check"></i><span><?= wd_esc__("Logs rescanned.") ?></span>
				</div>
			<?php } ?>

			<?php if ($wd_rapor === null) { ?>

				<div class="wd-note wd-note-warn">
					<i class="fas fa-triangle-exclamation"></i>
					<span><?= wd_esc__("No report yet. Click Rescan above.") ?></span>
				</div>

			<?php } else { ?>

				<div class="wd-stats">
					<div class="wd-stat" title="<?= wd_esc__("Messages accepted by the system") ?>">
						<div class="wd-stat-label"><?= wd_esc__("ACCEPTED") ?></div>
						<div class="wd-stat-value"><?= (int) $t["kabul"] ?><span class="wd-stat-of"><?= wd_esc__("messages") ?></span></div>
						<div class="wd-bar wd-ok"><span style="width:100%"></span></div>
					</div>
					<div class="wd-stat" title="<?= wd_esc__("Delivered to the recipient") ?>">
						<div class="wd-stat-label"><?= wd_esc__("DELIVERED") ?></div>
						<div class="wd-stat-value"><?= (int) $t["teslim"] ?><span class="wd-stat-of"><?= wd_esc__("successful") ?></span></div>
						<div class="wd-bar wd-ok"><span style="width:100%"></span></div>
					</div>
					<div class="wd-stat" title="<?= wd_esc__("Permanently undeliverable (bounce)") ?>">
						<div class="wd-stat-label"><?= wd_esc__("BOUNCED") ?></div>
						<div class="wd-stat-value"><?= (int) $t["sekme"] ?><span class="wd-stat-of"><?= wd_e(number_format($sekme_orani, 1, ".", ",")) ?>%</span></div>
						<div class="wd-bar <?= $sekme_orani >= 35 ? "wd-crit" : ($sekme_orani >= 15 ? "wd-warn" : "wd-ok") ?>">
							<span style="width: <?= max(2, min(100, $sekme_orani)) ?>%"></span>
						</div>
					</div>
					<div class="wd-stat" title="<?= wd_esc__("Queued and frozen messages") ?>">
						<div class="wd-stat-label"><?= wd_esc__("QUEUE") ?></div>
						<div class="wd-stat-value"><?= (int) $kuyruk["bekleyen"] ?><span class="wd-stat-of"><?php
       $don = (int) $kuyruk["donmus"];
       echo wd_e(sprintf(wd_n__("%d frozen", "%d frozen", $don), $don));
       ?></span></div>
						<div class="wd-bar <?= $kuyruk["donmus"] > 0 ? "wd-warn" : "wd-ok" ?>">
							<span style="width: <?= $kuyruk["bekleyen"] > 0 ? 100 : 2 ?>%"></span>
						</div>
					</div>
				</div>

				<div class="wd-err-tabs" role="tablist">
					<?php foreach ([1, 7, 30] as $g) { ?>
						<a class="wd-err-tab<?= $g === $wd_gun ? " aktif" : "" ?>"
							href="/list/mailrapor/?yenile=1&amp;gun=<?= $g ?>&amp;token=<?= wd_e($tok) ?>">
							<span class="wd-err-kod"><?= $g ?></span>
							<span class="wd-err-ad"><?= wd_e(wd_n__("day", "days", $g)) ?></span>
						</a>
					<?php } ?>
				</div>

				<div class="wd-card">
					<div class="wd-card-head">
						<?= wd_esc__("Senders") ?>
						<span class="wd-card-note"><?php
       $gun = (int) ($wd_rapor["gun"] ?? 7);
       echo wd_e(sprintf(wd_n__("last %d day", "last %d days", $gun), $gun));
       ?></span>
					</div>
					<div class="wd-card-body">
						<?php if (empty($wd_rapor["gonderenler"])) { ?>
							<p class="wd-empty"><?= wd_esc__("No sending in this period.") ?></p>
						<?php } else {
      foreach ($wd_rapor["gonderenler"] as $g) {
      	$ileti = (int) $g["ileti"]; ?>
								<div class="wd-usage">
									<div class="wd-usage-top">
										<span class="wd-usage-label wd-usage-site" title="<?= wd_e($g["gonderen"]) ?>">
											<span class="wd-check-dot <?= wd_e(wd_health_sinif($g["durum"])) ?>"></span>
											<?= wd_e($g["gonderen"]) ?>
										</span>
										<span class="wd-usage-num"><?= wd_e(sprintf(wd_n__("%d message", "%d messages", $ileti), $ileti)) ?></span>
									</div>
									<div class="wd-site-metrics">
										<span class="wd-site-metric"><b><?= (int) $g["teslim"] ?></b> <?= wd_esc__("delivered") ?></span>
										<span class="wd-site-metric"><b><?= (int) $g["sekme"] ?></b> <?= wd_esc__("bounced") ?></span>
										<span class="wd-site-metric"><?= wd_e(number_format($g["sekme_orani"], 1, ".", ",")) ?>%</span>
										<span class="wd-site-metric"><?= wd_e(wd_bayt((int) $g["bayt"])) ?></span>
									</div>
									<?php if ($g["durum"] !== "ok") { ?>
										<p class="wd-check-fix">
											<i class="fas fa-screwdriver-wrench"></i>
											<span>
												<?= wd_esc__("Bounce rate is high. The account password may be compromised or the recipient list is broken. Change the password and inspect the outbound queue:") ?>
												<span class="wd-mono">exim4 -bp</span>
											</span>
										</p>
									<?php } ?>
								</div>
							<?php }
     } ?>
					</div>
				</div>

				<?php if (!empty($wd_rapor["alici_alanlar"])) { ?>
					<div class="wd-card">
						<div class="wd-card-head"><?= wd_esc__("Most Written Domains") ?></div>
						<div class="wd-card-body">
							<?php foreach ($wd_rapor["alici_alanlar"] as $a) { ?>
								<div class="wd-disk-line">
									<span class="wd-disk-name"><?= wd_e($a["alan"]) ?></span>
									<span class="wd-disk-size"><?= (int) $a["adet"] ?></span>
								</div>
							<?php } ?>
						</div>
					</div>
				<?php } ?>

			<?php } ?>
		</div>

		<!-- ================= RIGHT RAIL ================= -->
		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How to Read This?") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Bounce rate") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("Above 15% is a warning, above 35% is serious. Only accounts sending more than 20 messages are flagged — ratios mislead on small samples.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Sudden volume spike") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("If an account that normally sends a few mails a day suddenly sends hundreds, the password is almost always compromised.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Frozen messages") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("Undeliverable messages stuck in the queue. If they pile up, clear the queue:") ?>
							<span class="wd-mono">exim4 -bpru | awk '{print $3}' | xargs -r exim4 -Mrm</span>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Rejected") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("In this period") ?>
							<b><?= (int) ($wd_rapor["reddedilen"] ?? 0) ?></b>
							<?= wd_esc__("connections were rejected. Most are bot scans; that is normal.") ?>
						</span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>
