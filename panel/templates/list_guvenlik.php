<?php
/**
 * WebDanışmanı — "Security" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_guvenlik.php
 */

$tok = $_SESSION["token"] ?? "";
$ozet = $wd_guv["ozet"] ?? ["ok" => 0, "warn" => 0, "fail" => 0, "bilinmiyor" => 0];
$tozet = $wd_tar["ozet"] ?? ["fail" => 0, "warn" => 0];
$bulgu = ($tozet["fail"] ?? 0) + ($tozet["warn"] ?? 0);
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title"><?= wd_esc__("Security") ?></h1>
					<p class="wd-subtitle">
						<?= wd_esc__("Server hardening status and malware scanning on customer sites.") ?>
					</p>
				</div>
				<div class="wd-page-actions">
					<form method="post" class="wd-inline-form">
						<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
						<input type="hidden" name="ok" value="1">
						<input type="hidden" name="islem" value="denetle">
						<button type="submit" class="button button-secondary"><i class="fas fa-rotate"></i> <?= wd_esc__("Audit") ?></button>
					</form>
					<form method="post" class="wd-inline-form">
						<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
						<input type="hidden" name="ok" value="1">
						<input type="hidden" name="islem" value="tara">
						<button type="submit" class="button button-secondary"><i class="fas fa-magnifying-glass"></i> <?= wd_esc__("Scan") ?></button>
					</form>
				</div>
			</div>

			<?php if ($wd_hata !== "") { ?>
				<div class="wd-note wd-note-err">
					<i class="fas fa-circle-exclamation"></i><span><?= wd_e($wd_hata) ?></span>
				</div>
			<?php } elseif ($wd_bilgi !== "") { ?>
				<div class="wd-note wd-note-ok">
					<i class="fas fa-circle-check"></i><span><?= wd_e($wd_bilgi) ?></span>
				</div>
			<?php } ?>

			<div class="wd-stats">
				<div class="wd-stat">
					<div class="wd-stat-label"><?= wd_esc__("OPEN") ?></div>
					<div class="wd-stat-value"><?= (int) ($ozet["fail"] ?? 0) ?><span class="wd-stat-of"><?= wd_esc__("urgent") ?></span></div>
					<div class="wd-bar <?= ($ozet["fail"] ?? 0) > 0 ? "wd-crit" : "wd-ok" ?>">
						<span style="width: <?= ($ozet["fail"] ?? 0) > 0 ? 100 : 2 ?>%"></span></div>
				</div>
				<div class="wd-stat">
					<div class="wd-stat-label"><?= wd_esc__("WARNINGS") ?></div>
					<div class="wd-stat-value"><?= (int) ($ozet["warn"] ?? 0) ?><span class="wd-stat-of"><?= wd_esc__("review") ?></span></div>
					<div class="wd-bar <?= ($ozet["warn"] ?? 0) > 0 ? "wd-warn" : "wd-ok" ?>">
						<span style="width: <?= ($ozet["warn"] ?? 0) > 0 ? 100 : 2 ?>%"></span></div>
				</div>
				<div class="wd-stat">
					<div class="wd-stat-label"><?= wd_esc__("OK") ?></div>
					<div class="wd-stat-value"><?= (int) ($ozet["ok"] ?? 0) ?><span class="wd-stat-of"><?= wd_esc__("checks") ?></span></div>
					<div class="wd-bar wd-ok"><span style="width:100%"></span></div>
				</div>
				<div class="wd-stat" title="<?= wd_esc__("Number of files that need review from the malware scan") ?>">
					<div class="wd-stat-label"><?= wd_esc__("SUSPICIOUS FILES") ?></div>
					<div class="wd-stat-value"><?= (int) $bulgu ?><span class="wd-stat-of"><?= wd_esc__("to review") ?></span></div>
					<div class="wd-bar <?= $bulgu > 0 ? "wd-crit" : "wd-ok" ?>">
						<span style="width: <?= $bulgu > 0 ? 100 : 2 ?>%"></span></div>
				</div>
			</div>

			<?php // ================= SERVER AUDIT =================
   if ($wd_guv === null) { ?>
				<div class="wd-note wd-note-warn">
					<i class="fas fa-triangle-exclamation"></i>
					<span><?= wd_esc__("Security audit has not run yet. Click Audit above.") ?></span>
				</div>
			<?php } else { ?>
				<details class="wd-group" open>
					<summary class="wd-group-head">
						<span class="wd-group-icon"><i class="fas fa-shield-halved"></i></span>
						<span class="wd-group-title"><?= wd_esc__("Server Audit") ?></span>
						<span class="wd-group-count"><?php
       $n = count($wd_guv["kontroller"] ?? []);
       echo wd_e(sprintf(wd_n__("%d check", "%d checks", $n), $n));
       ?></span>
						<i class="fas fa-chevron-down wd-group-chevron"></i>
					</summary>
					<div class="wd-checks">
						<?php foreach ($wd_guv["kontroller"] ?? [] as $c) {
       	$durum = $c["durum"] ?? "bilinmiyor";
       	$sorunlu = in_array($durum, ["fail", "warn"], true); ?>
							<div class="wd-check wd-check-<?= wd_e($durum) ?>">
								<span class="wd-check-dot <?= wd_e(wd_health_sinif($durum)) ?>"
									title="<?= wd_e(wd_health_etiket($durum)) ?>"></span>
								<div class="wd-check-body">
									<div class="wd-check-top">
										<span class="wd-check-label"><?= wd_e(wd__($c["label"])) ?></span>
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
									<?php } ?>
									<?php if ($sorunlu && !empty($c["islem"])) { ?>
										<form method="post" class="wd-inline-form"
											onsubmit="return confirm(<?= htmlspecialchars(json_encode(wd__("This will change a server setting. Continue?\n\nIf prerequisites are not met the action is rejected — lockout protection is in place.")), ENT_QUOTES, "UTF-8") ?>);">
											<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
											<input type="hidden" name="ok" value="1">
											<input type="hidden" name="islem" value="<?= wd_e($c["islem"]) ?>">
											<button type="submit" class="wd-mini-btn"><?= wd_esc__("Fix now") ?></button>
										</form>
									<?php } ?>
								</div>
							</div>
						<?php } ?>
					</div>
				</details>

				<?php if (!empty($wd_guv["saldirganlar"])) { ?>
					<details class="wd-group">
						<summary class="wd-group-head">
							<span class="wd-group-icon"><i class="fas fa-user-secret"></i></span>
							<span class="wd-group-title"><?= wd_esc__("Top Attacking IPs") ?></span>
							<span class="wd-group-count">SSH</span>
							<i class="fas fa-chevron-down wd-group-chevron"></i>
						</summary>
						<div class="wd-auth-body">
							<?php foreach ($wd_guv["saldirganlar"] as $s) {
       	$deneme = (int) $s["deneme"]; ?>
								<div class="wd-disk-line">
									<span class="wd-disk-name wd-mono"><?= wd_e($s["ip"]) ?></span>
									<span class="wd-disk-size"><?= wd_e(sprintf(wd_n__("%d attempt", "%d attempts", $deneme), $deneme)) ?></span>
								</div>
							<?php } ?>
							<p class="wd-aciklama wd-aciklama-kucuk">
								<?= wd_esc__("fail2ban is already blocking these. If a block keeps repeating you can ban it permanently from the Firewall.") ?>
							</p>
						</div>
					</details>
				<?php } ?>
			<?php } ?>

			<?php // ================= MALWARE SCAN ================= ?>
			<details class="wd-group" <?= $bulgu > 0 ? "open" : "" ?>>
				<summary class="wd-group-head">
					<span class="wd-group-icon"><i class="fas fa-viruses"></i></span>
					<span class="wd-group-title"><?= wd_esc__("Malware Scan") ?></span>
					<?php if ($wd_tar !== null) {
     	$taranan = (int) ($wd_tar["sayac"]["taranan"] ?? 0); ?>
						<span class="wd-group-count">
							<?= wd_e(sprintf(wd_n__("%d file", "%d files", $taranan), $taranan)) ?> ·
							<?= wd_e(date("d.m.Y H:i", (int) ($wd_tar["ts"] ?? 0))) ?>
						</span>
					<?php } ?>
					<i class="fas fa-chevron-down wd-group-chevron"></i>
				</summary>
				<div class="wd-auth-body">
					<?php if ($wd_tar === null) { ?>
						<p class="wd-empty"><?= wd_esc__("Scan has not run yet. Click Scan above.") ?></p>
					<?php } elseif (!empty($wd_tar["eksik"])) { ?>
						<div class="wd-note wd-note-warn wd-note-inline">
							<i class="fas fa-triangle-exclamation"></i>
							<span><?= wd_esc__("Scan hit the time/file limit; results may be incomplete.") ?></span>
						</div>
					<?php } ?>

					<?php if ($wd_tar !== null && empty($wd_tar["bulgular"])) { ?>
						<p class="wd-empty"><?= wd_esc__("No suspicious files found.") ?></p>
					<?php } ?>

					<?php foreach ($wd_tar["bulgular"] ?? [] as $b) { ?>
						<div class="wd-check wd-check-<?= wd_e($b["seviye"]) ?>">
							<span class="wd-check-dot <?= wd_e(wd_health_sinif($b["seviye"])) ?>"></span>
							<div class="wd-check-body">
								<div class="wd-check-top">
									<span class="wd-check-label wd-mono"><?= wd_e(wd_yol_kisalt($b["kisa"], 66)) ?></span>
									<span class="wd-check-value"><?= wd_esc__("score") ?> <?= (int) $b["puan"] ?> · <?= wd_e($b["user"]) ?></span>
								</div>
								<?php foreach ($b["bulgular"] as $x) {
        	if (($x["puan"] ?? 0) <= 0) {
        		continue;
        	} ?>
									<p class="wd-check-note">• <?= wd_e(wd__($x["aciklama"])) ?></p>
								<?php } ?>
								<p class="wd-check-fix">
									<i class="fas fa-screwdriver-wrench"></i>
									<span>
										<?= wd_esc__("Open the file in File Manager and inspect it. Take a") ?>
										<b><?= wd_esc__("backup") ?></b>
										<?= wd_esc__("before deleting — a finding is not a conviction, it is a signal to review. Full path:") ?>
										<span class="wd-mono"><?= wd_e($b["yol"]) ?></span>
									</span>
								</p>
							</div>
						</div>
					<?php } ?>
				</div>
			</details>

		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("Things to Know") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Lockout protection") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("Actions like “disable SSH password login” are rejected if there is no") ?>
							<b><?= wd_esc__("usable") ?></b>
							<?= wd_esc__("login key on the server. Keys on accounts with an") ?>
							<span class="wd-mono">nologin</span>
							<?= wd_esc__("shell do not count.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Nothing is deleted") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("The scan only reports. It does not quarantine or delete — accidentally destroying a customer's working file does more harm than a late-found webshell.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("What the score means") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("Sum of behavior patterns. 40+ is reported, 70+ is serious. A single pattern is not a conviction; legitimate code uses similar functions.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Blacklist link") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("If the server IP is blacklisted, start here: a compromised site is often sending spam. Read together with Mail Report.") ?>
						</span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>
