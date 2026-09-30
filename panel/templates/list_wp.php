<?php
/**
 * WebDanışmanı — "WordPress Tools" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_wp.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$form = function (array $s, string $islem, array $ek = []): string {
	$h = '<input type="hidden" name="token" value="' . wd_e($_SESSION["token"] ?? "") . '"><input type="hidden" name="ok" value="1">';
	$h .= '<input type="hidden" name="islem" value="' . wd_e($islem) . '">';
	$h .= '<input type="hidden" name="v_user" value="' . wd_e($s["user"]) . '"><input type="hidden" name="v_domain" value="' . wd_e($s["domain"]) . '">';
	$h .= '<input type="hidden" name="v_yol" value="' . wd_e($s["yol"]) . '">';
	foreach ($ek as $k => $v) {
		$h .= '<input type="hidden" name="' . wd_e($k) . '" value="' . wd_e($v) . '">';
	}
	return $h;
};
$toplam_g = 0;
foreach ($wd_siteler as $s) {
	$toplam_g += (int) ($s["guncelleme_sayisi"] ?? 0);
}
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php
			$eylem = '<form method="post" style="margin:0">' . wd_modul_form_gizli("tara") .
				'<button type="submit" class="button button-secondary" title="' . wd_esc__("Rescan all sites (wp-cli, may take a while)") . '"><i class="fas fa-rotate"></i> ' . wd_esc__("Rescan") . '</button></form>';
			wd_modul_baslik(wd__("WordPress Tools"), wd__("Updates, auto-updates, integrity check, maintenance mode, and one-click admin login."), $eylem);
			?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, ($_GET["durum"] ?? "") === "sorunlu" || ($_GET["durum"] ?? "") === "hata" ? "warn" : "ok");
			} ?>
			<?php if (!empty($wd_cikti)) { ?>
				<div class="wd-card"><div class="wd-card-body wd-card-body-pad"><pre class="wdm-kod wdm-kod-kucuk"><?= wd_e(implode("\n", $wd_cikti)) ?></pre></div></div>
			<?php } ?>
			<?php if ($wd_wp_cli === false) {
				wd_modul_not(wd__("wp-cli was not found on the server; the admin should install it by running kur-modul.sh. The list still shows but actions will not work."), "warn");
			} ?>

			<div class="wd-stats">
				<div class="wd-stat"><div class="wd-stat-label"><?= wd_esc__("WORDPRESS SITES") ?></div><div class="wd-stat-value"><?= count($wd_siteler) ?></div></div>
				<div class="wd-stat"><div class="wd-stat-label"><?= wd_esc__("PENDING UPDATES") ?></div><div class="wd-stat-value" style="<?= $toplam_g > 0 ? "color:var(--wd-amber)" : "" ?>"><?= $toplam_g ?></div></div>
				<div class="wd-stat"><div class="wd-stat-label"><?= wd_esc__("LAST SCAN") ?></div><div class="wd-stat-value"><?= $wd_yas === null ? "—" : wd_e(wd_modul_sure($wd_yas)) ?><span class="wd-stat-of"><?= wd_esc__("ago") ?></span></div></div>
			</div>

			<?php if (empty($wd_siteler)) { ?>
				<div class="wd-card"><div class="wdm-bos"><i class="fas fa-w"></i><?= $wd_veri === null ? wd_esc__('No scan yet. Start with "Rescan".') : wd_esc__("No WordPress install found. You can install WordPress via App Installer > Quick Install.") ?></div></div>
			<?php } ?>

			<?php foreach ($wd_siteler as $s) {
				$acik = $wd_secili === "" ? count($wd_siteler) === 1 : $wd_secili === $s["domain"];
				$g = (int) ($s["guncelleme_sayisi"] ?? 0);
				$dog = $s["dogrulama"] ?? null; ?>
				<details class="wd-card" <?= $acik ? "open" : "" ?>>
					<summary class="wd-card-head" style="cursor:pointer;list-style:none">
						<span style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
							<b><?= wd_e($s["domain"]) ?><?= $s["yol"] !== "/" ? wd_e($s["yol"]) : "" ?></b>
							<span class="wd-card-note">WP <?= wd_e($s["surum"] ?: "?") ?><?= !empty($s["ad"]) ? " · " . wd_e($s["ad"]) : "" ?><?= $wd_is_admin ? " · " . wd_e($s["user"]) : "" ?></span>
							<?php if (!empty($s["hata"])) { ?><span class="wdm-rozet wdm-rozet-warn"><?= wd_e($s["hata"]) ?></span><?php } ?>
							<?php if ($g > 0) { ?><span class="wdm-rozet wdm-rozet-warn"><?= wd_e(sprintf(wd_n__("%d update", "%d updates", $g), $g)) ?></span><?php } else { ?><span class="wdm-rozet wdm-rozet-ok"><?= wd_esc__("up to date") ?></span><?php } ?>
							<?php if (!empty($s["bakim"])) { ?><span class="wdm-rozet wdm-rozet-info"><?= wd_esc__("maintenance mode") ?></span><?php } ?>
							<?php if ($dog !== null && empty($dog["ok"])) { ?><span class="wdm-rozet wdm-rozet-err"><?= wd_esc__("integrity issue") ?></span><?php } ?>
						</span>
						<i class="fas fa-chevron-down wd-group-chevron"></i>
					</summary>
					<div class="wd-card-body wd-card-body-pad">
						<div class="wdm-dugmeler">
							<form method="post"><?= $form($s, "giris") ?><button type="submit" class="button" formtarget="_blank" title="<?= wd_esc__("Enter the admin panel without a password (120s single-use link)") ?>"><i class="fas fa-right-to-bracket"></i> <?= wd_esc__("Log in as admin") ?></button></form>
							<?php if ($g > 0) { ?>
								<form method="post" onsubmit="return confirm('<?= wd_esc__("Core, plugins, and themes will be updated. Taking a backup first is recommended. Continue?") ?>');"><?= $form($s, "guncelle", ["v_ne" => "hepsi"]) ?><button type="submit" class="button button-secondary"><i class="fas fa-arrow-up"></i> <?= wd_esc__("Update all") ?></button></form>
							<?php } ?>
							<form method="post"><?= $form($s, "otomatik", ["v_deger" => !empty($s["otomatik_cekirdek"]) && (int) ($s["otomatik_eklenti"] ?? 0) > 0 ? "off" : "on"]) ?><button type="submit" class="button button-secondary" title="<?= wd_esc__("Core + plugin + theme auto-updates") ?>"><?= !empty($s["otomatik_cekirdek"]) && (int) ($s["otomatik_eklenti"] ?? 0) > 0 ? wd_esc__("Disable auto-updates") : wd_esc__("Enable auto-updates") ?></button></form>
							<form method="post"><?= $form($s, "bakim", ["v_deger" => !empty($s["bakim"]) ? "off" : "on"]) ?><button type="submit" class="button button-secondary"><?= !empty($s["bakim"]) ? wd_esc__("Disable maintenance mode") : wd_esc__("Maintenance mode") ?></button></form>
							<form method="post"><?= $form($s, "dogrula") ?><button type="submit" class="button button-secondary" title="<?= wd_esc__("Compares core files with the official release") ?>"><i class="fas fa-fingerprint"></i> <?= wd_esc__("Verify integrity") ?></button></form>
							<form method="post"><?= $form($s, "onbellek") ?><button type="submit" class="button button-secondary"><i class="fas fa-broom"></i> <?= wd_esc__("Clear cache") ?></button></form>
						</div>

						<?php if (!empty($s["cekirdek_guncelleme"])) { ?>
							<div class="wd-note wd-note-warn" style="margin-top:10px"><i class="fas fa-arrow-up"></i><span><?= wd_esc__("Core update available:") ?> <b><?= wd_e($s["cekirdek_guncelleme"]) ?></b>
								<form method="post" style="display:inline;margin-left:8px"><?= $form($s, "guncelle", ["v_ne" => "cekirdek"]) ?><button type="submit" class="wd-mini-btn"><?= wd_esc__("Update core only") ?></button></form></span></div>
						<?php } ?>

						<?php if ($dog !== null && empty($dog["ok"]) && !empty($dog["sorunlu"])) { ?>
							<div class="wd-note wd-note-err" style="margin-top:10px"><i class="fas fa-triangle-exclamation"></i>
								<span><b><?= wd_esc__("Core files differ from the official release") ?></b> (<?= wd_e(wd_modul_tarih((int) ($dog["ts"] ?? 0))) ?>). <?= wd_esc__('These are WordPress\'s own files, not plugins; changes may indicate compromise. Before restoring from backup, restore official files with "Update core only".') ?>
									<pre class="wdm-kod wdm-kod-kucuk" style="margin-top:6px"><?= wd_e(implode("\n", (array) $dog["sorunlu"])) ?></pre></span>
							</div>
						<?php } ?>

						<?php if (!empty($s["eklentiler"])) { ?>
							<div class="wdm-tablo-sar" style="margin-top:12px">
								<table class="wdm-tablo">
									<thead><tr><th><?= wd_esc__("Plugin") ?></th><th><?= wd_esc__("Status") ?></th><th><?= wd_esc__("Version") ?></th><th><?= wd_esc__("Auto") ?></th><th class="daralt"></th></tr></thead>
									<tbody>
										<?php foreach ($s["eklentiler"] as $e) {
											$guncel = ($e["update"] ?? "") === "available"; ?>
											<tr>
												<td><b><?= wd_e($e["title"] ?: $e["name"]) ?></b><br><span class="wdm-eskime wdm-mono"><?= wd_e($e["name"]) ?></span></td>
												<td><?= ($e["status"] ?? "") === "active" ? '<span class="wdm-rozet wdm-rozet-ok">' . wd_esc__("enabled") . '</span>' : (($e["status"] ?? "") === "must-use" ? '<span class="wdm-rozet wdm-rozet-info">' . wd_esc__("must-use") . '</span>' : '<span class="wdm-rozet">' . wd_esc__("inactive") . '</span>') ?></td>
												<td class="mono"><?= wd_e($e["version"] ?? "") ?><?= $guncel ? ' <span class="wdm-rozet wdm-rozet-warn">→ ' . wd_e($e["update_version"] ?? "") . "</span>" : "" ?></td>
												<td><?= in_array((string) ($e["auto_update"] ?? ""), ["on", "1", "true"], true) ? wd_esc__("on") : wd_esc__("off") ?></td>
												<td class="daralt">
													<div class="wdm-oge-eylem">
														<?php if ($guncel) { ?><form method="post"><?= $form($s, "eklenti", ["v_ad" => $e["name"], "v_ne" => "update"]) ?><button type="submit" class="wd-mini-btn"><?= wd_esc__("Update") ?></button></form><?php } ?>
														<?php if (($e["status"] ?? "") === "active") { ?><form method="post" onsubmit="return confirm('<?= wd_e(sprintf(wd__("%s will be deactivated. Continue?"), $e["name"])) ?>');"><?= $form($s, "eklenti", ["v_ad" => $e["name"], "v_ne" => "deactivate"]) ?><button type="submit" class="wd-mini-btn"><?= wd_esc__("Disable") ?></button></form>
														<?php } elseif (($e["status"] ?? "") === "inactive") { ?><form method="post"><?= $form($s, "eklenti", ["v_ad" => $e["name"], "v_ne" => "activate"]) ?><button type="submit" class="wd-mini-btn"><?= wd_esc__("Enable") ?></button></form><?php } ?>
													</div>
												</td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						<?php } ?>

						<?php if (!empty($s["temalar"])) { ?>
							<details style="margin-top:10px">
								<summary style="cursor:pointer;font-size:11.5px;color:var(--wd-muted)"><?= wd_e(sprintf(wd__("Themes (%d)"), count($s["temalar"]))) ?><?= (int) ($s["tema_guncelleme"] ?? 0) > 0 ? " · " . wd_e(sprintf(wd_n__("%d update", "%d updates", (int) $s["tema_guncelleme"]), (int) $s["tema_guncelleme"])) : "" ?></summary>
								<div class="wdm-tablo-sar" style="margin-top:6px">
									<table class="wdm-tablo">
										<thead><tr><th><?= wd_esc__("Theme") ?></th><th><?= wd_esc__("Status") ?></th><th><?= wd_esc__("Version") ?></th></tr></thead>
										<tbody>
											<?php foreach ($s["temalar"] as $t) { ?>
												<tr>
													<td><?= wd_e($t["title"] ?: $t["name"]) ?></td>
													<td><?= ($t["status"] ?? "") === "active" ? '<span class="wdm-rozet wdm-rozet-ok">' . wd_esc__("enabled") . '</span>' : (($t["status"] ?? "") === "parent" ? '<span class="wdm-rozet wdm-rozet-info">' . wd_esc__("parent theme") . '</span>' : '<span class="wdm-rozet">' . wd_esc__("inactive") . '</span>') ?></td>
													<td class="mono"><?= wd_e($t["version"] ?? "") ?><?= ($t["update"] ?? "") === "available" ? ' <span class="wdm-rozet wdm-rozet-warn">→ ' . wd_e($t["update_version"] ?? "") . "</span>" : "" ?></td>
												</tr>
											<?php } ?>
										</tbody>
									</table>
								</div>
								<?php if ((int) ($s["tema_guncelleme"] ?? 0) > 0) { ?>
									<form method="post" style="margin-top:6px"><?= $form($s, "guncelle", ["v_ne" => "temalar"]) ?><button type="submit" class="wd-mini-btn"><?= wd_esc__("Update themes") ?></button></form>
								<?php } ?>
							</details>
						<?php } ?>
					</div>
				</details>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How It Works") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Admin login") ?></span><span class="wd-v-small"><?= wd_esc__('A small "must-use" plugin is placed on your site; the panel creates a 120-second single-use link. With no token file the plugin does nothing.') ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Integrity") ?></span><span class="wd-v-small"><?= wd_esc__("Core files are compared with official checksums from wordpress.org. A difference is a core file change, not a plugin; the most common cause is malware injection.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Updates") ?></span><span class="wd-v-small"><?= wd_esc__("wp-cli runs as the site user; taking a backup from the Backups page before updating is recommended.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Scan") ?></span><span class="wd-v-small"><?= wd_esc__('The list refreshes nightly; "Rescan" refreshes immediately. After each action the related site is refreshed alone.') ?></span></div>
				</div>
			</div>
		</aside>
	</div>
</div>
