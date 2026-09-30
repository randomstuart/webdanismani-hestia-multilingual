<?php
/**
 * WebDanışmanı — "Reseller Management" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_bayi.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$kota = function ($kul, $lim): string {
	$k = $lim === "unlimited" || $lim === "" ? "∞" : wd_modul_bayt((int) $lim * 1048576);
	return wd_modul_bayt((int) $kul * 1048576) . " / " . $k;
};
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik(
				$wd_is_admin ? wd__("Reseller Management") : wd__("My Customers"),
				$wd_is_admin ? wd__("Define resellers: packages they can open, customer limit, and linked accounts.") : wd__("Open your own customer accounts, suspend them, and change packages and passwords."),
			); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>

			<?php if ($wd_is_admin && $wd_ayar !== null) { ?>
				<?php if (empty($wd_ayar["bayiler"])) { ?>
					<div class="wd-card"><div class="wdm-bos"><i class="fas fa-users-gear"></i><?= wd_esc__("No resellers defined yet. Make a user a reseller from the box on the right.") ?></div></div>
				<?php } ?>
				<?php foreach ((array) $wd_ayar["bayiler"] as $b) { ?>
					<div class="wd-card">
						<div class="wd-card-head">
							<span><b><?= wd_e($b["bayi"]) ?></b><?= !empty($b["ad"]) ? " · " . wd_e($b["ad"]) : "" ?> <span class="wd-card-note"><?= wd_e(sprintf(wd__("%d / %d customers · packages: %s"), (int) $b["musteri_sayisi"], (int) $b["azami"], implode(", ", (array) $b["paketler"]) ?: wd__("all"))) ?></span></span>
							<form method="post" onsubmit="return confirm('<?= wd_e(sprintf(wd__("Reseller definition for %s will be removed; customer accounts are not deleted. Continue?"), $b["bayi"])) ?>');"><?= wd_modul_form_gizli("bayi-sil") ?><input type="hidden" name="v_bayi" value="<?= wd_e($b["bayi"]) ?>"><button type="submit" class="wd-mini-btn wd-mini-btn-danger"><?= wd_esc__("Remove reseller") ?></button></form>
						</div>
						<div class="wd-card-body wd-card-body-pad">
							<?php if (empty($b["musteriler"])) { ?><p class="wd-empty"><?= wd_esc__("No linked customers.") ?></p><?php } else { ?>
								<div class="wdm-dugmeler">
									<?php foreach ((array) $b["musteriler"] as $m) { ?>
										<form method="post" style="display:inline-flex;align-items:center;gap:4px" onsubmit="return confirm('<?= wd_e(sprintf(wd__("%s will be unlinked from the reseller (account is not deleted). Continue?"), $m)) ?>');">
											<?= wd_modul_form_gizli("bayi-cikar") ?><input type="hidden" name="v_bayi" value="<?= wd_e($b["bayi"]) ?>"><input type="hidden" name="v_kullanici" value="<?= wd_e($m) ?>">
											<span class="wd-limit-tag"><?= wd_e($m) ?> <button type="submit" style="border:0;background:none;cursor:pointer;color:var(--wd-red);padding:0 0 0 4px" title="<?= wd_esc__("Unlink from reseller") ?>">×</button></span>
										</form>
									<?php } ?>
								</div>
							<?php } ?>
							<form method="post" class="wdm-dugmeler" style="margin-top:10px">
								<?= wd_modul_form_gizli("bayi-ata") ?><input type="hidden" name="v_bayi" value="<?= wd_e($b["bayi"]) ?>">
								<select class="form-select" name="v_kullanici" style="height:28px;font-size:12px;max-width:240px">
									<?php foreach ((array) $wd_ayar["kullanicilar"] as $k) {
										if ($k === $b["bayi"] || $k === ($wd_ayar["yonetici"] ?? "admin") || in_array($k, (array) $b["musteriler"], true)) {
											continue;
										} ?>
										<option value="<?= wd_e($k) ?>"><?= wd_e($k) ?></option>
									<?php } ?>
								</select>
								<button type="submit" class="wd-mini-btn"><?= wd_esc__("Link existing user") ?></button>
							</form>
						</div>
					</div>
				<?php } ?>
			<?php } ?>

			<?php if ($wd_bayi_mi && $wd_liste !== null) { ?>
				<div class="wd-stats">
					<div class="wd-stat"><div class="wd-stat-label"><?= wd_esc__("CUSTOMERS") ?></div><div class="wd-stat-value"><?= count((array) $wd_liste["musteriler"]) ?><span class="wd-stat-of">/ <?= (int) $wd_liste["azami"] ?></span></div></div>
					<div class="wd-stat"><div class="wd-stat-label"><?= wd_esc__("SUSPENDED") ?></div><div class="wd-stat-value"><?= count(array_filter((array) $wd_liste["musteriler"], fn($m) => !empty($m["askida"]))) ?></div></div>
					<div class="wd-stat"><div class="wd-stat-label"><?= wd_esc__("PACKAGE OPTIONS") ?></div><div class="wd-stat-value"><?= count((array) $wd_liste["paketler"]) ?></div></div>
				</div>

				<div class="wd-card">
					<div class="wd-card-head"><?= wd_esc__("Customer accounts") ?></div>
					<?php if (empty($wd_liste["musteriler"])) { ?><div class="wdm-bos"><i class="fas fa-users"></i><?= wd_esc__("You have no customers yet.") ?></div><?php } else { ?>
						<div class="wdm-tablo-sar"><table class="wdm-tablo">
							<thead><tr><th><?= wd_esc__("Account") ?></th><th><?= wd_esc__("Package") ?></th><th><?= wd_esc__("Disk") ?></th><th><?= wd_esc__("Bandwidth") ?></th><th><?= wd_esc__("Web / Mail / DB") ?></th><th><?= wd_esc__("Status") ?></th><th class="daralt"></th></tr></thead>
							<tbody>
								<?php foreach ((array) $wd_liste["musteriler"] as $m) { ?>
									<tr>
										<td><b><?= wd_e($m["kullanici"]) ?></b><br><span class="wdm-eskime"><?= wd_e($m["ad"]) ?> · <?= wd_e($m["eposta"]) ?></span></td>
										<td>
											<form method="post" class="wd-inline-form" style="display:flex;gap:4px;align-items:center">
												<?= wd_modul_form_gizli("paket") ?><input type="hidden" name="v_kullanici" value="<?= wd_e($m["kullanici"]) ?>">
												<select class="form-select" name="v_paket" style="height:26px;font-size:11.5px" onchange="this.form.submit()">
													<?php foreach ((array) $wd_liste["paketler"] as $p) { ?><option value="<?= wd_e($p["ad"]) ?>" <?= $p["ad"] === $m["paket"] ? "selected" : "" ?>><?= wd_e($p["ad"]) ?></option><?php } ?>
													<?php if (!in_array($m["paket"], array_map(fn($p) => $p["ad"], (array) $wd_liste["paketler"]), true)) { ?><option value="<?= wd_e($m["paket"]) ?>" selected><?= wd_e($m["paket"]) ?></option><?php } ?>
												</select>
											</form>
										</td>
										<td class="mono"><?= wd_e($kota($m["disk"], $m["disk_kota"])) ?></td>
										<td class="mono"><?= wd_e($kota($m["bant"], $m["bant_kota"])) ?></td>
										<td class="mono"><?= (int) $m["web"] ?> / <?= (int) $m["mail"] ?> / <?= (int) $m["db"] ?></td>
										<td><?= !empty($m["askida"]) ? '<span class="wdm-rozet wdm-rozet-warn">' . wd_esc__("suspended") . '</span>' : '<span class="wdm-rozet wdm-rozet-ok">' . wd_esc__("enabled") . '</span>' ?></td>
										<td class="daralt">
											<div class="wdm-oge-eylem">
												<?php if (!empty($m["askida"])) { ?>
													<form method="post"><?= wd_modul_form_gizli("askidan-cikar") ?><input type="hidden" name="v_kullanici" value="<?= wd_e($m["kullanici"]) ?>"><button type="submit" class="wd-mini-btn"><?= wd_esc__("Unsuspend") ?></button></form>
												<?php } else { ?>
													<form method="post" onsubmit="return confirm('<?= wd_e(sprintf(wd__("%s will be suspended; their sites and mail stop. Continue?"), $m["kullanici"])) ?>');"><?= wd_modul_form_gizli("askiya") ?><input type="hidden" name="v_kullanici" value="<?= wd_e($m["kullanici"]) ?>"><button type="submit" class="wd-mini-btn"><?= wd_esc__("Suspend") ?></button></form>
												<?php } ?>
												<form method="post" onsubmit="var s=prompt('<?= wd_e(sprintf(wd__("New password for %s (at least 8 characters):"), $m["kullanici"])) ?>');if(!s){return false;}this.querySelector('[name=v_sifre]').value=s;return true;"><?= wd_modul_form_gizli("sifre") ?><input type="hidden" name="v_kullanici" value="<?= wd_e($m["kullanici"]) ?>"><input type="hidden" name="v_sifre" value=""><button type="submit" class="wd-mini-btn"><?= wd_esc__("Password") ?></button></form>
												<form method="post" onsubmit="return confirm('<?= wd_e(sprintf(wd__("WARNING: account %s, all its sites, mail, and databases will be PERMANENTLY deleted. Continue?"), $m["kullanici"])) ?>');"><?= wd_modul_form_gizli("sil") ?><input type="hidden" name="v_kullanici" value="<?= wd_e($m["kullanici"]) ?>"><button type="submit" class="wd-mini-btn wd-mini-btn-danger"><?= wd_esc__("Delete") ?></button></form>
											</div>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table></div>
					<?php } ?>
				</div>

				<?php if (count((array) $wd_liste["musteriler"]) < (int) $wd_liste["azami"]) { ?>
					<form method="post" class="wdm-form">
						<?= wd_modul_form_gizli("ekle") ?>
						<div class="wd-card">
							<div class="wd-card-head"><?= wd_esc__("New customer account") ?></div>
							<div class="wd-card-body wd-card-body-pad">
								<div class="wdm-satir">
									<div class="wdm-alan"><label class="form-label" for="v_yeni_kullanici"><?= wd_esc__("Username") ?></label><input class="form-control wdm-mono" id="v_yeni_kullanici" name="v_yeni_kullanici" required pattern="[a-z][a-z0-9_-]{1,31}" placeholder="customer1"></div>
									<div class="wdm-alan"><label class="form-label" for="v_sifre"><?= wd_esc__("Password") ?></label><input class="form-control wdm-mono" type="password" id="v_sifre" name="v_sifre" required minlength="8" autocomplete="new-password"></div>
									<div class="wdm-alan"><label class="form-label" for="v_eposta"><?= wd_esc__("Email") ?></label><input class="form-control" type="email" id="v_eposta" name="v_eposta" required></div>
								</div>
								<div class="wdm-satir" style="margin-top:10px">
									<div class="wdm-alan"><label class="form-label" for="v_ad"><?= wd_esc__("Full name") ?></label><input class="form-control" id="v_ad" name="v_ad"></div>
									<div class="wdm-alan"><label class="form-label" for="v_paket"><?= wd_esc__("Package") ?></label>
										<select class="form-select" id="v_paket" name="v_paket">
											<?php foreach ((array) $wd_liste["paketler"] as $p) { ?><option value="<?= wd_e($p["ad"]) ?>"><?= wd_e($p["ad"]) ?> — <?= wd_esc__("disk") ?> <?= wd_e($p["disk"] === "unlimited" ? "∞" : $p["disk"] . " MB") ?>, <?= wd_e($p["web"]) ?> <?= wd_esc__("sites") ?>, <?= wd_e($p["mail"]) ?> mail, <?= wd_e($p["db"]) ?> DB</option><?php } ?>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="wdm-dugmeler"><button type="submit" class="button"><i class="fas fa-user-plus"></i> <?= wd_esc__("Create Account") ?></button></div>
					</form>
				<?php } else { ?>
					<?php wd_modul_not(wd__("You have reached your customer limit. Ask the admin to raise it."), "warn"); ?>
				<?php } ?>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<?php if ($wd_is_admin && $wd_ayar !== null) {
				$wd_adaylar = array_values(array_filter((array) ($wd_ayar["kullanicilar"] ?? []), fn($k) => $k !== ($wd_ayar["yonetici"] ?? "admin"))); ?>
				<div class="wd-card">
					<div class="wd-card-head"><?= wd_esc__("Define / update reseller") ?></div>
					<div class="wd-card-body wd-card-body-pad">
						<?php if (empty($wd_adaylar)) { ?>
							<?php wd_modul_not(wd__("No user available to make a reseller. First open an account for the reseller on the Users page; then select them here."), "warn"); ?>
							<div class="wdm-dugmeler"><a class="button button-secondary" href="/add/user/"><?= wd_esc__("Add User") ?></a></div>
						<?php } else { ?>
						<form method="post" class="wdm-form">
							<?= wd_modul_form_gizli("bayi-ekle") ?>
							<div class="wdm-alan"><label class="form-label" for="v_bayi"><?= wd_esc__("User") ?></label>
								<select class="form-select" id="v_bayi" name="v_bayi">
									<?php foreach ($wd_adaylar as $k) { ?>
										<option value="<?= wd_e($k) ?>"><?= wd_e($k) ?></option>
									<?php } ?>
								</select>
								<span class="wdm-ipucu"><?= wd_esc__('The reseller logs into the panel with their own account and sees the "My Customers" page.') ?></span>
							</div>
							<div class="wdm-alan"><label class="form-label" for="v_ad"><?= wd_esc__("Reseller name") ?></label><input class="form-control" id="v_ad" name="v_ad" placeholder="<?= wd_esc__("Company / person") ?>"></div>
							<div class="wdm-alan"><label class="form-label" for="v_azami"><?= wd_esc__("Max customers") ?></label><input class="form-control" type="number" id="v_azami" name="v_azami" value="10" min="1" max="500"></div>
							<div class="wdm-alan"><label class="form-label"><?= wd_esc__("Packages they can open") ?> <span class="wdm-ipucu"><?= wd_esc__("none selected = all") ?></span></label>
								<?php foreach ((array) $wd_ayar["paketler"] as $p) { ?>
									<label class="wd-secim" style="padding:4px 0"><input type="checkbox" name="v_paketler[]" value="<?= wd_e($p) ?>"><span><?= wd_e($p) ?></span></label>
								<?php } ?>
							</div>
							<div class="wdm-dugmeler"><button type="submit" class="button"><?= wd_esc__("Save") ?></button></div>
						</form>
						<?php } ?>
					</div>
				</div>
			<?php } ?>
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How It Works") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Permissions") ?></span><span class="wd-v-small"><?= wd_esc__("A reseller only sees and manages accounts linked to them; they cannot list other users or enter server settings.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Package limit") ?></span><span class="wd-v-small"><?= wd_esc__("The admin chooses which packages the reseller may open; the reseller cannot assign other packages.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Customer login") ?></span><span class="wd-v-small"><?= wd_esc__("Customers log into this panel with their own username and password. The reseller can reset the password but cannot open a session as the customer.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Resources") ?></span><span class="wd-v-small"><?= wd_esc__("Customer accounts share server quotas; the reseller limit and package quotas cap total usage.") ?></span></div>
				</div>
			</div>
		</aside>
	</div>
</div>
