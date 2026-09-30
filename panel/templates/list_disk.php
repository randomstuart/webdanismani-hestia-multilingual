<?php
/**
 * WebDanışmanı — "Disk Usage" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_disk.php
 *
 * $panel, $user -> provided by render_page()
 * $wd_*         -> provided by list/disk/index.php
 */

$tok = $_SESSION["token"] ?? "";
$kullanicilar = $wd_disk["kullanicilar"] ?? [];

/* Category colors — match wd-disk category paths (stable keys). */
$wd_renk = [
	"web" => "var(--wd-green)",
	"mail" => "#2f6fb0",
	"backup" => "var(--wd-amber)",
	"tmp" => "#7c5cd6",
	"other" => "var(--wd-sep)",
	"" => "var(--wd-sep)",
];

$wd_toplam_hepsi = 0;
foreach ($kullanicilar as $k) {
	$wd_toplam_hepsi += (int) $k["toplam"];
}
?>

<div class="container">
	<div class="wd-page">

		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title"><?= wd_esc__("Disk Usage") ?></h1>
					<p class="wd-subtitle">
						<?= wd_esc__("Shows where space went — broken down by domain, directory, and file.") ?>
					</p>
				</div>
				<div class="wd-page-actions">
					<a class="button button-secondary" href="/list/disk/?yenile=1&amp;token=<?= wd_e($tok) ?>">
						<i class="fas fa-rotate"></i> <?= wd_esc__("Rescan") ?>
					</a>
				</div>
			</div>

			<?php if (!empty($wd_yenilendi)) { ?>
				<div class="wd-note wd-note-ok">
					<i class="fas fa-circle-check"></i>
					<span><?= wd_esc__("Scan re-run; the values below were just measured.") ?></span>
				</div>
			<?php } ?>

			<?php if ($wd_disk === null) { ?>

				<div class="wd-note wd-note-warn">
					<i class="fas fa-triangle-exclamation"></i>
					<span>
						<?= wd_esc__("The disk analysis tool has not run yet.") ?>
						<?= wd_esc__("On the server run") ?> <span class="wd-mono">bash /usr/local/hestia/wd/src/kur.sh</span>
						<?= wd_esc__("or click Rescan above.") ?>
					</span>
				</div>

			<?php } elseif (empty($kullanicilar)) { ?>

				<div class="wd-note">
					<i class="fas fa-circle-info"></i>
					<span><?= wd_esc__("No accounts to display.") ?></span>
				</div>

			<?php } else {
    foreach ($kullanicilar as $k) {
    	$toplam = max(1, (int) $k["toplam"]);
    	$kota_mb = $k["kota_mb"] ?? null;
    	$kota_b = $kota_mb !== null ? $kota_mb * 1048576 : null;
    	$kota_pct = $kota_b ? min(100, round($k["toplam"] / $kota_b * 100, 1)) : null; ?>

				<div class="wd-group" data-wd-open>
					<div class="wd-group-head wd-group-head-static">
						<span class="wd-group-icon"><i class="fas fa-hard-drive"></i></span>
						<span class="wd-group-title"><?= wd_e($k["user"]) ?></span>
						<?php if (!empty($k["paket"])) { ?>
							<span class="wd-group-count"><?= wd_e($k["paket"]) ?></span>
						<?php } ?>
						<span class="wd-disk-total">
							<?= wd_e(wd_bayt((int) $k["toplam"])) ?>
							<?php if ($kota_b) { ?>
								<span class="wd-v-dim">/ <?= wd_e(wd_bayt($kota_b)) ?></span>
							<?php } else { ?>
								<span class="wd-v-dim">/ <?= wd_esc__("unlimited") ?></span>
							<?php } ?>
						</span>
					</div>

					<div class="wd-disk-body">

						<?php if (!empty($k["eksik"])) { ?>
							<div class="wd-note wd-note-warn wd-note-inline">
								<i class="fas fa-triangle-exclamation"></i>
								<span>
									<?= wd_esc__("This account's scan hit the time limit; values below") ?>
									<b><?= wd_esc__("may be incomplete") ?></b>.
									<?= wd_esc__("For a full result run") ?>
									<span class="wd-mono">wd-disk refresh</span>
									<?= wd_esc__("on the server.") ?>
								</span>
							</div>
						<?php } ?>

						<?php // --- Stacked category bar ---
      if (!empty($k["kategoriler"])) { ?>
							<div class="wd-stack" role="img"
								aria-label="<?= wd_esc__("Disk breakdown:") ?> <?= wd_e(implode(", ", array_map(function ($c) {
        	return wd__($c["etiket"] ?? "") . " " . wd_bayt((int) $c["bayt"]);
        }, $k["kategoriler"]))) ?>">
								<?php foreach ($k["kategoriler"] as $c) {
         	$w = $c["bayt"] / $toplam * 100;
         	if ($w < 0.4) {
         		continue;
         	}
         	$renk = $wd_renk[$c["yol"] ?? ""] ?? "var(--wd-sep)"; ?>
									<span style="width: <?= round($w, 2) ?>%; background: <?= $renk ?>"
										title="<?= wd_e(wd__($c["etiket"] ?? "") . " — " . wd_bayt((int) $c["bayt"])) ?>"></span>
								<?php } ?>
							</div>

							<div class="wd-legend">
								<?php foreach ($k["kategoriler"] as $c) {
									$renk = $wd_renk[$c["yol"] ?? ""] ?? "var(--wd-sep)"; ?>
									<span class="wd-legend-item" title="<?= wd_e(wd__($c["ipucu"] ?? "")) ?>">
										<span class="wd-legend-dot" style="background: <?= $renk ?>"></span>
										<?= wd_e(wd__($c["etiket"] ?? "")) ?>
										<b><?= wd_e(wd_bayt((int) $c["bayt"])) ?></b>
									</span>
								<?php } ?>
							</div>
						<?php }

      if ($kota_pct !== null) { ?>
							<div class="wd-usage">
								<div class="wd-usage-top">
									<span class="wd-usage-label"><?= wd_esc__("Quota usage") ?></span>
									<span class="wd-usage-num"><?= wd_e(number_format($kota_pct, 1, ".", ",")) ?>%</span>
								</div>
								<div class="wd-bar <?= wd_level((float) $kota_pct) ?>">
									<span style="width: <?= max(2, $kota_pct) ?>%"></span>
								</div>
							</div>
						<?php } ?>

						<?php // --- Per-domain breakdown ---
      if (!empty($k["alanlar"])) { ?>
							<div class="wd-disk-sec">
								<div class="wd-disk-sec-head"><?= wd_esc__("By domain") ?></div>
								<?php foreach ($k["alanlar"] as $d) { ?>
									<div class="wd-disk-row">
										<div class="wd-disk-row-top">
											<span class="wd-disk-name" title="<?= wd_e($d["domain"]) ?>"><?= wd_e($d["domain"]) ?></span>
											<span class="wd-disk-size"><?= wd_e(wd_bayt((int) $d["bayt"])) ?></span>
										</div>
										<div class="wd-bar wd-ok">
											<span style="width: <?= max(2, min(100, $d["bayt"] / $toplam * 100)) ?>%"></span>
										</div>
										<?php if (!empty($d["bolumler"])) { ?>
											<div class="wd-disk-parts">
												<?php foreach (array_slice($d["bolumler"], 0, 6) as $b) { ?>
													<span class="wd-disk-part">
														<?= wd_e($b["ad"]) ?> <b><?= wd_e(wd_bayt((int) $b["bayt"])) ?></b>
													</span>
												<?php } ?>
											</div>
										<?php } ?>
									</div>
								<?php } ?>
							</div>
						<?php } ?>

						<?php // --- Mailboxes ---
      if (!empty($k["postalar"])) { ?>
							<div class="wd-disk-sec">
								<div class="wd-disk-sec-head"><?= wd_esc__("Mailboxes") ?></div>
								<?php foreach ($k["postalar"] as $m) { ?>
									<div class="wd-disk-line">
										<span class="wd-disk-name"><?= wd_e($m["domain"]) ?></span>
										<span class="wd-disk-size"><?= wd_e(wd_bayt((int) $m["bayt"])) ?></span>
									</div>
								<?php } ?>
							</div>
						<?php } ?>

						<?php // --- Largest files ---
      if (!empty($k["dosyalar"])) { ?>
							<div class="wd-disk-sec">
								<div class="wd-disk-sec-head">
									<?= wd_esc__("Largest files") ?>
									<span class="wd-card-note"><?= wd_esc__("look here first to free space") ?></span>
								</div>
								<?php foreach ($k["dosyalar"] as $f) {
         	$kisa = preg_replace("#^" . preg_quote($k["home"], "#") . "#", "~", $f["yol"]); ?>
									<div class="wd-disk-line">
										<span class="wd-disk-path wd-mono" title="<?= wd_e($f["yol"]) ?>"><?= wd_e(wd_yol_kisalt($kisa)) ?></span>
										<span class="wd-disk-size"><?= wd_e(wd_bayt((int) $f["bayt"])) ?></span>
									</div>
								<?php } ?>
							</div>
						<?php } ?>

						<?php // --- Largest directories ---
      if (!empty($k["dizinler"])) { ?>
							<div class="wd-disk-sec">
								<div class="wd-disk-sec-head"><?= wd_esc__("Largest directories") ?></div>
								<?php foreach ($k["dizinler"] as $d) { ?>
									<div class="wd-disk-line">
										<span class="wd-disk-path wd-mono" title="<?= wd_e($d["yol"]) ?>"><?= wd_e(wd_yol_kisalt($d["yol"])) ?></span>
										<span class="wd-disk-size"><?= wd_e(wd_bayt((int) $d["bayt"])) ?></span>
									</div>
								<?php } ?>
							</div>
						<?php } ?>

					</div>
				</div>

			<?php }
   } ?>

		</div>

		<!-- ================= RIGHT RAIL ================= -->
		<aside class="wd-rail">

			<?php if ($wd_disk !== null) { ?>
				<div class="wd-card">
					<div class="wd-card-head"><?= wd_esc__("Scan") ?></div>
					<div class="wd-card-body">
						<div class="wd-kv">
							<span class="wd-k"><?= wd_esc__("Last Scan") ?></span>
							<span class="wd-v">
								<?php $ts = (int) ($wd_disk["ts"] ?? 0);
        if ($ts > 0) {
        	echo wd_e(date("d.m.Y H:i", $ts));
        	echo '<span class="wd-v-dim"> · ' . wd_e(wd_human_uptime(max(0, time() - $ts))) . " " . wd__("ago") . "</span>";
        } else {
        	echo "—";
        } ?>
							</span>
						</div>
						<div class="wd-kv">
							<span class="wd-k"><?= wd_esc__("Accounts") ?></span>
							<span class="wd-v"><?= count($kullanicilar) ?></span>
						</div>
						<?php if ($wd_is_admin && count($kullanicilar) > 1) { ?>
							<div class="wd-kv">
								<span class="wd-k"><?= wd_esc__("Total") ?></span>
								<span class="wd-v"><?= wd_e(wd_bayt($wd_toplam_hepsi)) ?></span>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>

			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How to Free Space?") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">logs</span>
						<span class="wd-v-small"><?= wd_esc__("Web logs. Safe to delete; the server creates new ones. Usually the fastest win.") ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">tmp</span>
						<span class="wd-v-small"><?= wd_esc__("Session and upload temp files. Safe to empty.") ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">backup</span>
						<span class="wd-v-small"><?= wd_esc__("Old backup archives. Can be deleted after downloading them elsewhere.") ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Mail") ?></span>
						<span class="wd-v-small"><?= wd_esc__("Large mailboxes fill the quota quickly. Clean old attachments.") ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Caution") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("Files under") ?> <b>public_html</b> <?= wd_esc__("are your site itself — take a backup before deleting.") ?>
						</span>
					</div>
				</div>
			</div>

		</aside>
	</div>
</div>
