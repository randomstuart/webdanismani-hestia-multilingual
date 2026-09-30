<?php
/**
 * WebDanışmanı — "Clone / Staging" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_klon.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$kaynaklar = array_filter(array_keys($wd_doms), fn($d) => !in_array($d, $wd_klon_hedefler, true));
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik(wd__("Clone / Staging"), wd__("Copy the site files and database to a test address; push live with one click when done.")); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>

			<?php if ($wd_sonuc !== null) { ?>
				<div class="wd-card">
					<div class="wd-card-head"><span style="color:var(--wd-green)"><i class="fas fa-circle-check"></i> <?= wd_esc__("Staging created") ?></span></div>
					<dl class="wdm-kv">
						<dt><?= wd_esc__("URL") ?></dt><dd><a href="https://<?= wd_e($wd_sonuc["hedef"]) ?>/" target="_blank" rel="noopener"><?= wd_e($wd_sonuc["hedef"]) ?></a> <?= !empty($wd_sonuc["ssl"]) ? '<span class="wdm-rozet wdm-rozet-ok">SSL</span>' : '<span class="wdm-rozet wdm-rozet-warn">' . wd_esc__("SSL could not be obtained (DNS may not resolve yet)") . '</span>' ?></dd>
						<?php if (!empty($wd_sonuc["db"])) { ?>
							<dt><?= wd_esc__("Database") ?></dt><dd><span class="wdm-mono"><?= wd_e($wd_sonuc["db"]) ?></span> · <?= wd_esc__("user") ?> <span class="wdm-mono"><?= wd_e($wd_sonuc["db_kullanici"]) ?></span> · <?= wd_esc__("password") ?> <span class="wdm-mono"><?= wd_e($wd_sonuc["db_sifre"]) ?></span><br><span class="wdm-ipucu"><?= !empty($wd_sonuc["wp"]) ? wd_esc__("WordPress configuration was updated automatically.") : wd_esc__("Enter these details into your application's config file manually.") ?> <?= wd_esc__("Password is shown once.") ?></span></dd>
						<?php } ?>
					</dl>
				</div>
			<?php } ?>

			<?php if (!empty($wd_klonlar)) { ?>
				<div class="wd-card">
					<div class="wd-card-head"><?= wd_esc__("Staging sites") ?></div>
					<div class="wdm-liste">
						<?php foreach ($wd_klonlar as $k) { ?>
							<div class="wdm-oge">
								<div class="wdm-oge-bas">
									<span class="wdm-oge-ad"><a href="https://<?= wd_e($k["hedef"]) ?>/" target="_blank" rel="noopener"><?= wd_e($k["hedef"]) ?></a> <span class="wdm-rozet wdm-rozet-info">staging</span></span>
									<span class="wdm-oge-alt"><?= wd_esc__("Source:") ?> <b><?= wd_e($k["kaynak"]) ?></b> · <?= wd_e(wd_modul_tarih($k["ts"] ?? null)) ?><?= !empty($k["wp"]) ? " · WordPress" : "" ?><?= !empty($k["db_hedef"]) ? " · DB: " . wd_e($k["db_hedef"]) : "" ?><?= !empty($k["son_yayin"]) ? " · " . wd_e(sprintf(wd__("last publish %s"), wd_modul_tarih((int) $k["son_yayin"]))) : "" ?></span>
								</div>
								<div class="wdm-oge-eylem">
									<form method="post" onsubmit="return confirm('<?= wd_e(sprintf(wd__("Files and database on %s will be transferred to the LIVE site %s. The current live site is backed up under private/. Continue?"), $k["hedef"], $k["kaynak"])) ?>');">
										<?= wd_modul_form_gizli("yayinla") ?><input type="hidden" name="v_hedef" value="<?= wd_e($k["hedef"]) ?>">
										<button type="submit" class="wd-mini-btn" title="<?= wd_esc__("Staging → live") ?>"><i class="fas fa-rocket"></i> <?= wd_esc__("Publish") ?></button>
									</form>
									<form method="post" onsubmit="return confirm('<?= wd_e(sprintf(wd__("Domain %s, its files, and the staging database will be deleted. The live site is not affected. Continue?"), $k["hedef"])) ?>');">
										<?= wd_modul_form_gizli("sil") ?><input type="hidden" name="v_hedef" value="<?= wd_e($k["hedef"]) ?>">
										<button type="submit" class="wd-mini-btn wd-mini-btn-danger"><?= wd_esc__("Delete") ?></button>
									</form>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>

			<?php if (empty($kaynaklar)) { ?>
				<?php wd_modul_not(wd__("You have no domains to clone.")); ?>
			<?php } else { ?>
				<form method="post" class="wdm-form" onsubmit="return confirm('<?= wd_esc__("Copying may take a few minutes depending on site size. Continue?") ?>');">
					<?= wd_modul_form_gizli("olustur") ?>
					<div class="wd-card">
						<div class="wd-card-head"><?= wd_esc__("New staging") ?></div>
						<div class="wd-card-body wd-card-body-pad">
							<div class="wdm-satir">
								<div class="wdm-alan">
									<label class="form-label" for="v_kaynak"><?= wd_esc__("Source site") ?></label>
									<select class="form-select" id="v_kaynak" name="v_kaynak">
										<?php foreach ($kaynaklar as $d) { ?><option value="<?= wd_e($d) ?>"><?= wd_e($d) ?></option><?php } ?>
									</select>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_alt"><?= wd_esc__("Subdomain") ?></label>
									<input class="form-control wdm-mono" id="v_alt" name="v_alt" value="staging" pattern="[a-z0-9-]{1,40}">
									<span class="wdm-ipucu"><?= wd_esc__("Target:") ?> <span class="wdm-mono"><span id="wdKlonOnizle">staging.…</span></span> — <?= wd_esc__("if your domain's DNS is on this panel, the A record is added automatically.") ?></span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_db"><?= wd_esc__("Database") ?></label>
									<select class="form-select" id="v_db" name="v_db">
										<option value="auto"><?= wd_esc__("Automatic (copy if WordPress)") ?></option>
										<option value=""><?= wd_esc__("Do not copy (files only)") ?></option>
										<?php foreach ($wd_dbler as $db) { ?><option value="<?= wd_e($db) ?>"><?= wd_e(sprintf(wd__("Copy %s"), $db)) ?></option><?php } ?>
									</select>
								</div>
							</div>
							<div class="wdm-alan wdm-alan-genis" style="margin-top:8px">
								<label class="form-label" for="v_hedef"><?= wd_esc__("Different target domain") ?> <span class="wdm-ipucu"><?= wd_esc__("optional") ?></span></label>
								<input class="form-control wdm-mono" id="v_hedef" name="v_hedef" placeholder="<?= wd_esc__("test.otherdomain.com (empty = subdomain above)") ?>">
							</div>
						</div>
					</div>
					<div class="wdm-dugmeler"><button type="submit" class="button"><i class="fas fa-clone"></i> <?= wd_esc__("Create Staging") ?></button></div>
				</form>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How It Works") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Creation") ?></span><span class="wd-v-small"><?= wd_esc__("A new domain is opened with the same PHP version and files are copied. For WordPress a new database is created, content is transferred, and all URLs are rewritten to the staging address. Staging is closed to search engines.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Publishing") ?></span><span class="wd-v-small"><?= wd_esc__("First the live site files and database are backed up as") ?> <span class="wd-mono">private/wd-klon-yedek-*</span>. <?= wd_esc__("Then staging files are copied to live (except wp-config.php), the staging database is imported to live, and URLs are rewritten back.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Caution") ?></span><span class="wd-v-small"><?= wd_esc__('During publish, data added on live in the meantime (orders/comments) is REPLACED by staging data. For store sites, use "Do not copy (files only)" for theme/plugin-only work.') ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Non-WordPress") ?></span><span class="wd-v-small"><?= wd_esc__("The database is copied but application configuration must be updated manually; details are shown once after creation.") ?></span></div>
				</div>
			</div>
		</aside>
	</div>
</div>

<script>
	(function () {
		var k = document.getElementById("v_kaynak"), a = document.getElementById("v_alt"), o = document.getElementById("wdKlonOnizle");
		function g() { if (k && a && o) { o.textContent = (a.value || "staging") + "." + k.value; } }
		if (k) { k.addEventListener("change", g); }
		if (a) { a.addEventListener("input", g); }
		g();
	})();
</script>
