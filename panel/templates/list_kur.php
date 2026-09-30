<?php
/**
 * WebDanışmanı — "App Installer" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_kur.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik(wd__("App Installer"), wd__("Install ready-made software on the selected domain in one click; database and configuration are prepared automatically.")); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>

			<?php if ($wd_sonuc !== null) { ?>
				<div class="wd-card">
					<div class="wd-card-head"><span style="color:var(--wd-green)"><i class="fas fa-circle-check"></i> <?= wd_esc__("Files placed") ?></span> <span class="wd-card-note"><?= wd_e($wd_sonuc["ad"] ?? "") ?> <?= wd_e($wd_sonuc["surum"] ?? "") ?></span></div>
					<div class="wd-card-body">
						<dl class="wdm-kv">
							<dt><?= wd_esc__("URL") ?></dt><dd><a href="<?= wd_e($wd_sonuc["site_url"] ?? "") ?>" target="_blank" rel="noopener"><?= wd_e($wd_sonuc["site_url"] ?? "") ?></a></dd>
							<dt><?= wd_esc__("Files") ?></dt><dd><?= wd_e(sprintf(wd_n__("%d file", "%d files", (int) ($wd_sonuc["dosya"] ?? 0)), (int) ($wd_sonuc["dosya"] ?? 0))) ?>, <?= wd_esc__("target") ?> <span class="wdm-mono"><?= wd_e($wd_sonuc["hedef"] ?? "/") ?></span></dd>
							<?php if (!empty($wd_sonuc["db"])) { ?>
								<dt><?= wd_esc__("Database") ?></dt><dd><span class="wdm-kopya"><code><?= wd_e($wd_sonuc["db"]["ad"]) ?></code></span></dd>
								<dt><?= wd_esc__("DB user") ?></dt><dd><span class="wdm-kopya"><code><?= wd_e($wd_sonuc["db"]["kullanici"]) ?></code></span></dd>
								<dt><?= wd_esc__("DB password") ?></dt><dd><span class="wdm-kopya"><code><?= wd_e($wd_sonuc["db"]["sifre"]) ?></code></span> <span class="wdm-ipucu"><?= wd_esc__("shown once — save it") ?></span></dd>
								<dt><?= wd_esc__("DB host") ?></dt><dd><span class="wdm-mono">localhost</span></dd>
							<?php } ?>
							<?php if (!empty($wd_sonuc["lisans"])) { ?><dt><?= wd_esc__("License") ?></dt><dd><?= wd_esc__("license.key written") ?></dd><?php } ?>
						</dl>
						<div class="wdm-dugmeler" style="padding:0 16px 14px">
							<a class="button" href="<?= wd_e($wd_sonuc["kurulum_url"] ?? "#") ?>" target="_blank" rel="noopener"><?= wd_esc__("Finish Setup") ?> <i class="fas fa-arrow-up-right-from-square"></i></a>
							<span class="wdm-ipucu"><?= wd_esc__("The software's setup wizard opens; enter database details there.") ?></span>
						</div>
					</div>
				</div>
			<?php } ?>

			<?php if (empty($wd_doms)) { ?>
				<?php wd_modul_not(wd__("You do not have any web domains yet. Add a domain first.")); ?>
			<?php } else { ?>
				<?php wd_modul_domain_kutusu($wd_doms, $wd_domain, "/list/kur/"); ?>

				<?php if (!empty($wd_kurulu)) { ?>
					<div class="wd-card">
						<div class="wd-card-head"><?= wd_esc__("Installed apps") ?> <span class="wd-card-note"><?= wd_e($wd_domain) ?></span></div>
						<div class="wdm-liste">
							<?php foreach ($wd_kurulu as $u) { ?>
								<div class="wdm-oge">
									<div class="wdm-oge-bas">
										<span class="wdm-oge-ad"><?= wd_e($u["ad"] ?? $u["kod"]) ?> <?= !empty($u["surum"]) ? '<span class="wd-card-note">' . wd_e($u["surum"]) . "</span>" : "" ?></span>
										<span class="wdm-oge-alt"><span class="wdm-mono"><?= wd_e($u["yol"] ?? "/") ?></span><?= !empty($u["ts"]) ? " · " . wd_e(wd_modul_tarih((int) $u["ts"])) : "" ?><?= !empty($u["db"]) ? " · DB: " . wd_e($u["db"]) : "" ?></span>
									</div>
									<div class="wdm-oge-eylem">
										<a class="wd-mini-btn" href="https://<?= wd_e($wd_domain) ?><?= wd_e(rtrim($u["yol"] ?? "/", "/")) ?>/" target="_blank" rel="noopener"><?= wd_esc__("Open") ?></a>
										<?php if (($u["kod"] ?? "") === "WORDPRESS") { ?><a class="wd-mini-btn" href="/list/wp/?domain=<?= urlencode($wd_domain) ?>"><?= wd_esc__("WP Tools") ?></a><?php } ?>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				<?php } ?>

				<form method="post" class="wdm-form" id="wdKurForm">
					<?= wd_modul_form_gizli("kur", $wd_domain) ?>
					<div class="wd-card">
						<div class="wd-card-head"><?= wd_esc__("Choose an app") ?> <span class="wd-card-note"><?= wd_e(sprintf(wd_n__("%d app", "%d apps", count($wd_katalog)), count($wd_katalog))) ?></span></div>
						<?php if (empty($wd_katalog)) { ?>
							<div class="wdm-bos"><i class="fas fa-box-open"></i><?= wd_esc__("Catalog is empty.") ?><?= $wd_is_admin ? " " . wd_esc__("Add an app from the box on the right.") : " " . wd_esc__("Your admin has not added any apps yet.") ?></div>
						<?php } else { ?>
							<div class="wdm-kartlar">
								<?php foreach ($wd_katalog as $i => $a) { ?>
									<label class="wdm-kart<?= $i === 0 ? " secili" : "" ?>">
										<input type="radio" name="v_kod" value="<?= wd_e($a["kod"]) ?>" <?= $i === 0 ? "checked" : "" ?> data-kabuk="<?= !empty($a["kabuk"]) ? "1" : "0" ?>" <?= empty($a["paket_var"]) ? "disabled" : "" ?>>
										<b><i class="fas <?= wd_e($a["ikon"] ?? "fa-cube") ?>"></i> <?= wd_e($a["ad"]) ?></b>
										<span><?= wd_e($a["aciklama"] ?: "—") ?></span>
										<small>v<?= wd_e($a["surum"]) ?> · PHP <?= wd_e($a["php_min"]) ?>+<?= !empty($a["db"]) ? " · " . wd_esc__("database") : "" ?><?= !empty($a["kabuk"]) ? " · " . wd_esc__("licensed") : "" ?><?= empty($a["paket_var"]) ? " · " . wd_esc__("no package") : "" ?></small>
									</label>
								<?php } ?>
							</div>
							<div class="wd-card-body wd-card-body-pad" style="border-top:1px solid var(--wd-line)">
								<div class="wdm-satir">
									<div class="wdm-alan">
										<label class="form-label" for="v_alt"><?= wd_esc__("Subdirectory") ?> <span class="wdm-ipucu"><?= wd_esc__("empty = site root") ?></span></label>
										<input class="form-control wdm-mono" id="v_alt" name="v_alt" placeholder="<?= wd_esc__("e.g. panel") ?>" pattern="[A-Za-z0-9._-]{1,60}">
										<span class="wdm-ipucu"><?= $wd_bos ? wd_esc__("Site root is empty; you can install directly to the root.") : wd_esc__("Site root has files; empty it first or provide a subdirectory to install to root.") ?></span>
									</div>
									<div class="wdm-alan" id="wdLisansAlan">
										<label class="form-label" for="v_lisans"><?= wd_esc__("License key") ?></label>
										<input class="form-control wdm-mono" id="v_lisans" name="v_lisans" placeholder="XXXX-XXXX-XXXX-XXXX-XXXX" autocomplete="off">
										<span class="wdm-ipucu"><?= wd_esc__("Required for licensed software; enter the key from the vendor.") ?></span>
									</div>
								</div>
							</div>
						<?php } ?>
					</div>
					<?php if (!empty($wd_katalog)) { ?>
						<div class="wdm-dugmeler">
							<button type="submit" class="button" onclick="return confirm('<?= wd_e(sprintf(wd__("The selected app will be installed on %s. Continue?"), $wd_domain)) ?>');"><?= wd_esc__("Install") ?></button>
							<?php if ($wd_quick) { ?>
								<a class="button button-secondary" href="/add/webapp/?domain=<?= urlencode($wd_domain) ?>&token=<?= wd_e($tok) ?>"><?= wd_esc__("WordPress / others (Quick Install)") ?></a>
							<?php } ?>
						</div>
					<?php } ?>
				</form>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<?php if ($wd_is_admin) { ?>
				<div class="wd-card">
					<div class="wd-card-head"><?= wd_esc__("Add to Catalog") ?> <span class="wd-card-note"><?= wd_esc__("admin only") ?></span></div>
					<div class="wd-card-body wd-card-body-pad">
						<form method="post" enctype="multipart/form-data" class="wdm-form">
							<?= wd_modul_form_gizli("katalog-ekle") ?>
							<div class="wdm-alan"><label class="form-label" for="v_kod"><?= wd_esc__("Code") ?></label><input class="form-control wdm-mono" id="v_kod" name="v_kod" placeholder="BLOG" pattern="[A-Za-z0-9_-]{2,31}" required></div>
							<div class="wdm-alan"><label class="form-label" for="v_ad"><?= wd_esc__("Name") ?></label><input class="form-control" id="v_ad" name="v_ad" placeholder="<?= wd_esc__("Blog Software") ?>" required></div>
							<div class="wdm-satir">
								<div class="wdm-alan"><label class="form-label" for="v_surum"><?= wd_esc__("Version") ?></label><input class="form-control wdm-mono" id="v_surum" name="v_surum" value="1.0"></div>
								<div class="wdm-alan"><label class="form-label" for="v_php_min"><?= wd_esc__("Min PHP") ?></label><input class="form-control wdm-mono" id="v_php_min" name="v_php_min" value="8.1"></div>
							</div>
							<div class="wdm-alan"><label class="form-label" for="v_aciklama"><?= wd_esc__("Description") ?></label><input class="form-control" id="v_aciklama" name="v_aciklama" maxlength="300"></div>
							<div class="wdm-alan"><label class="form-label" for="v_zip"><?= wd_esc__("Package (.zip)") ?></label><input class="form-control" type="file" id="v_zip" name="v_zip" accept=".zip"><span class="wdm-ipucu"><?= wd_esc__("Re-uploading for an existing code updates the package; leave empty to keep the old package.") ?></span></div>
							<div class="wdm-satir">
								<div class="wdm-alan"><label class="form-label" for="v_zip_kok"><?= wd_esc__("Zip inner root") ?></label><input class="form-control wdm-mono" id="v_zip_kok" name="v_zip_kok" placeholder="<?= wd_esc__("blog (optional)") ?>"></div>
								<div class="wdm-alan"><label class="form-label" for="v_kurulum_yolu"><?= wd_esc__("Setup path") ?></label><input class="form-control wdm-mono" id="v_kurulum_yolu" name="v_kurulum_yolu" value="/install.php"></div>
							</div>
							<label class="wd-secim"><input type="checkbox" name="v_db" value="1" checked><span><b><?= wd_esc__("Create database") ?></b></span></label>
							<label class="wd-secim"><input type="checkbox" name="v_kabuk" value="1"><span><b><?= wd_esc__("Licensed software") ?></b><span class="wd-v-small"><?= wd_esc__("license.key becomes required at install.") ?></span></span></label>
							<div class="wdm-alan"><label class="form-label" for="v_cfg_dosya"><?= wd_esc__("Config file") ?> <span class="wdm-ipucu"><?= wd_esc__("optional") ?></span></label><input class="form-control wdm-mono" id="v_cfg_dosya" name="v_cfg_dosya" placeholder="config/config.php"></div>
							<div class="wdm-alan"><label class="form-label" for="v_cfg_sablon"><?= wd_esc__("Config template") ?></label><textarea class="form-control wdm-mono" id="v_cfg_sablon" name="v_cfg_sablon" rows="5" placeholder="<?= wd_e("<?php\nreturn ['db'=>['host'=>'{{DB_SUNUCU}}','name'=>'{{DB_AD}}','user'=>'{{DB_KULLANICI}}','pass'=>'{{DB_SIFRE}}'],'url'=>'{{SITE_URL}}'];") ?>"></textarea><span class="wdm-ipucu"><?= wd_esc__("Placeholders:") ?> {{DB_AD}} {{DB_KULLANICI}} {{DB_SIFRE}} {{DB_SUNUCU}} {{SITE_URL}} {{ALAN_ADI}} {{LISANS}}</span></div>
							<div class="wdm-dugmeler"><button type="submit" class="button"><?= wd_esc__("Add to Catalog") ?></button></div>
						</form>
					</div>
				</div>
				<?php if (!empty($wd_katalog)) { ?>
					<div class="wd-card">
						<div class="wd-card-head"><?= wd_esc__("Catalog") ?></div>
						<div class="wdm-liste">
							<?php foreach ($wd_katalog as $a) { ?>
								<div class="wdm-oge">
									<div class="wdm-oge-bas"><span class="wdm-oge-ad"><?= wd_e($a["ad"]) ?></span><span class="wdm-oge-alt"><span class="wdm-mono"><?= wd_e($a["kod"]) ?></span> · v<?= wd_e($a["surum"]) ?> · <?= !empty($a["paket_var"]) ? wd_e(wd_modul_bayt((int) $a["paket_boyut"])) : wd_esc__("no package") ?></span></div>
									<div class="wdm-oge-eylem">
										<form method="post" onsubmit="return confirm('<?= wd_e(sprintf(wd__("%s will be removed from the catalog (installed sites are not affected). Continue?"), $a["kod"])) ?>');">
											<?= wd_modul_form_gizli("katalog-sil") ?><input type="hidden" name="v_kod" value="<?= wd_e($a["kod"]) ?>">
											<button type="submit" class="wd-mini-btn wd-mini-btn-danger"><?= wd_esc__("Delete") ?></button>
										</form>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				<?php } ?>
			<?php } ?>
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How It Works") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("What happens") ?></span><span class="wd-v-small"><?= wd_esc__("The package is extracted to your domain, a database and user are created if needed, and the license file is written. Then the software's own setup wizard finishes.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Target must be empty") ?></span><span class="wd-v-small"><?= wd_esc__("Existing files are not overwritten. To install to root, empty the root or use a subdirectory.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Password") ?></span><span class="wd-v-small"><?= wd_esc__("The database password is shown only once on the post-install screen; you can set a new one from the Databases page.") ?></span></div>
				</div>
			</div>
		</aside>
	</div>
</div>

<script>
	(function () {
		var kartlar = document.querySelectorAll(".wdm-kart");
		var lisans = document.getElementById("wdLisansAlan");
		function guncelle() {
			kartlar.forEach(function (k) {
				var r = k.querySelector("input[type=radio]");
				k.classList.toggle("secili", !!(r && r.checked));
				if (r && r.checked && lisans) { lisans.style.opacity = r.dataset.kabuk === "1" ? "1" : "0.55"; }
			});
		}
		kartlar.forEach(function (k) { k.addEventListener("change", guncelle); });
		guncelle();
	})();
</script>
