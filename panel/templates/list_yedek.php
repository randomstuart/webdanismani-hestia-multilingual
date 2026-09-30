<?php
/**
 * WebDanışmanı — "Backup Explorer" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_yedek.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$baglanti = function (string $yol) use ($wd_yedek, $wd_domain): string {
	return "/list/yedek/?yedek=" . urlencode($wd_yedek) . "&domain=" . urlencode($wd_domain) . "&yol=" . urlencode($yol);
};
$kirinti = [];
if ($wd_yol !== "") {
	$topla = "";
	foreach (explode("/", $wd_yol) as $p) {
		$topla = $topla === "" ? $p : $topla . "/" . $p;
		$kirinti[] = [$p, $topla];
	}
}
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik(wd__("Backup Browser"), wd__("Browse inside a backup; restore a single file or folder. Live files are not touched unless you ask.")); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>

			<?php if (empty($wd_yedekler)) { ?>
				<div class="wd-card"><div class="wdm-bos"><i class="fas fa-box-archive"></i><?= wd_esc__("No backups yet. You can create one from the Backups page.") ?></div></div>
			<?php } else { ?>
				<div class="wd-card">
					<div class="wd-card-body wd-card-body-pad">
						<form method="get" action="/list/yedek/" class="wdm-satir">
							<div class="wdm-alan">
								<label class="form-label" for="yedek"><?= wd_esc__("Backup") ?></label>
								<select class="form-select" id="yedek" name="yedek" onchange="this.form.submit()">
									<?php foreach ($wd_yedekler as $y) { ?>
										<option value="<?= wd_e($y["ad"]) ?>" <?= $y["ad"] === $wd_yedek ? "selected" : "" ?>><?= wd_e(wd_date_tr($y["tarih"]) . " " . $y["saat"]) ?> · <?= (int) $y["boyut_mb"] ?> MB<?= empty($y["dosya_var"]) ? " · " . wd_esc__("file missing") : "" ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="wdm-alan">
								<label class="form-label" for="domain"><?= wd_esc__("Domain") ?></label>
								<select class="form-select" id="domain" name="domain" onchange="this.form.submit()">
									<?php foreach ((array) ($wd_secili["web"] ?? []) as $w) {
										if (!isset($wd_doms[$w])) {
											continue;
										} ?>
										<option value="<?= wd_e($w) ?>" <?= $w === $wd_domain ? "selected" : "" ?>><?= wd_e($w) ?></option>
									<?php } ?>
								</select>
							</div>
							<noscript><button type="submit" class="button button-secondary"><?= wd_esc__("Select") ?></button></noscript>
						</form>
					</div>
				</div>

				<?php if ($wd_secili && empty($wd_secili["dosya_var"])) { ?>
					<?php wd_modul_not(wd__("This backup's archive file is not on the server") . ($wd_mod !== "" && $wd_mod !== "zstd" && $wd_mod !== "gzip" ? " " . sprintf(wd__("(incremental/remote backup mode: %s)"), $wd_mod) : "") . ". " . wd__("The file browser only works with local tar backups; use the Backups page for a full restore."), "warn"); ?>
				<?php } elseif ($wd_icerik !== null) { ?>
					<div class="wd-card">
						<div class="wd-card-head">
							<span class="wdm-kirinti">
								<a href="<?= wd_e($baglanti("")) ?>"><i class="fas fa-house"></i> <?= wd_e($wd_domain) ?></a>
								<?php foreach ($kirinti as $k) { ?><span class="ayrac">/</span><a href="<?= wd_e($baglanti($k[1])) ?>"><?= wd_e($k[0]) ?></a><?php } ?>
							</span>
							<span class="wd-card-note"><?= isset($wd_icerik["toplam"]) ? wd_e(sprintf(wd_n__("%d member", "%d members", (int) $wd_icerik["toplam"]), (int) $wd_icerik["toplam"])) : wd_e(sprintf(wd_n__("%d item", "%d items", count((array) $wd_icerik["ogeler"])), count((array) $wd_icerik["ogeler"]))) ?></span>
						</div>
						<div class="wdm-agac">
							<?php if ($wd_yol !== "") {
								$ust = dirname($wd_yol); ?>
								<div class="wdm-agac-satir"><i class="fas fa-turn-up"></i><span class="wdm-agac-ad"><a href="<?= wd_e($baglanti($ust === "." ? "" : $ust)) ?>">..</a></span></div>
							<?php } ?>
							<?php if (empty($wd_icerik["ogeler"])) { ?>
								<div class="wdm-bos"><?= wd_esc__("Empty folder.") ?></div>
							<?php } ?>
							<?php foreach ((array) $wd_icerik["ogeler"] as $o) {
								$tam = $wd_yol === "" ? $o["ad"] : $wd_yol . "/" . $o["ad"]; ?>
								<div class="wdm-agac-satir">
									<i class="fas <?= !empty($o["dizin"]) ? "fa-folder" : "fa-file" ?>"></i>
									<span class="wdm-agac-ad">
										<?php if (!empty($o["dizin"])) { ?><a href="<?= wd_e($baglanti($tam)) ?>"><?= wd_e($o["ad"]) ?></a><?php } else { ?><?= wd_e($o["ad"]) ?><?php } ?>
									</span>
									<span class="wdm-agac-boyut"><?= wd_e(wd_modul_bayt((int) $o["boyut"])) ?><?= !empty($o["dizin"]) && (int) $o["adet"] > 0 ? " · " . wd_e(sprintf(wd_n__("%d file", "%d files", (int) $o["adet"]), (int) $o["adet"])) : "" ?><?= !empty($o["tarih"]) ? " · " . wd_e($o["tarih"]) : "" ?></span>
									<?php if ($wd_yol !== "") { ?>
										<div class="wdm-oge-eylem">
											<form method="post" onsubmit="return confirm('<?= wd_e(sprintf(wd__("%s will be extracted from the backup under private/wd-geri (live site untouched). Continue?"), $tam)) ?>');">
												<?= wd_modul_form_gizli("geri") ?>
												<input type="hidden" name="v_yedek" value="<?= wd_e($wd_yedek) ?>"><input type="hidden" name="v_domain" value="<?= wd_e($wd_domain) ?>"><input type="hidden" name="v_yol" value="<?= wd_e($tam) ?>">
												<button type="submit" class="wd-mini-btn" title="<?= wd_esc__("Extract under private/wd-geri") ?>"><?= wd_esc__("Get copy") ?></button>
											</form>
											<form method="post" onsubmit="return confirm('<?= wd_e(sprintf(wd__("WARNING: %s will OVERWRITE the live version. The current copy is backed up under private/wd-geri. Continue?"), $tam)) ?>');">
												<?= wd_modul_form_gizli("geri") ?>
												<input type="hidden" name="v_yedek" value="<?= wd_e($wd_yedek) ?>"><input type="hidden" name="v_domain" value="<?= wd_e($wd_domain) ?>"><input type="hidden" name="v_yol" value="<?= wd_e($tam) ?>"><input type="hidden" name="v_yerine" value="1">
												<button type="submit" class="wd-mini-btn wd-mini-btn-danger" title="<?= wd_esc__("Overwrite live file") ?>"><?= wd_esc__("Replace") ?></button>
											</form>
										</div>
									<?php } ?>
								</div>
							<?php } ?>
							<?php if (!empty($wd_icerik["kirpildi"])) { ?><div class="wdm-bos"><?= wd_esc__("List truncated at 2,000 items; go into a subfolder.") ?></div><?php } ?>
						</div>
					</div>
				<?php } ?>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("Backup Status") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Backup count") ?></span><span class="wd-v"><?= count($wd_yedekler) ?></span></div>
					<?php if (!empty($wd_yedekler)) { ?>
						<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Latest backup") ?></span><span class="wd-v"><?= wd_e(wd_date_tr($wd_yedekler[0]["tarih"]) . " " . $wd_yedekler[0]["saat"]) ?></span></div>
					<?php } ?>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Remote target") ?></span><span class="wd-v-small"><?= $wd_uzak ? wd_e(strtoupper($wd_uzak["tur"]) . " · " . $wd_uzak["sunucu"]) : wd_esc__("Not configured — backups are only on this server. If the server is lost, so are the backups; ask the admin to set a remote backup target.") ?></span></div>
					<?php if ($wd_secili) { ?>
						<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Selected backup") ?></span><span class="wd-v-small"><?= wd_e(sprintf(wd__("%d web · %d database · %d mail"), count((array) $wd_secili["web"]), count((array) $wd_secili["db"]), count((array) $wd_secili["mail"]))) ?></span></div>
					<?php } ?>
				</div>
			</div>
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How It Works") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Get copy") ?></span><span class="wd-v-small"><?= wd_esc__("The selected file/folder is extracted under") ?> <span class="wd-mono">private/wd-geri/&lt;time&gt;/</span>; <?= wd_esc__("the live site is untouched. Inspect and move it via File Manager.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Replace") ?></span><span class="wd-v-small"><?= wd_esc__("The live version is first moved under") ?> <span class="wd-mono">private/wd-geri/&lt;time&gt;-onceki/</span>, <?= wd_esc__("then the backup version is written. Swap them to undo.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Database") ?></span><span class="wd-v-small"><?= wd_esc__("Database restore is done from the") ?> <a href="/list/backup/"><?= wd_esc__("Backups") ?></a> <?= wd_esc__("page (a single database can be selected).") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("First open") ?></span><span class="wd-v-small"><?= wd_esc__("The first time a backup's contents are opened the archive is scanned and may take seconds; afterwards it is instant.") ?></span></div>
				</div>
			</div>
		</aside>
	</div>
</div>
