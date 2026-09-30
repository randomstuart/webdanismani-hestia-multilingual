<?php
/**
 * WebDanışmanı — "Cloudflare" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_cloudflare.php
 */

$tok = $_SESSION["token"] ?? "";
$gercek_ip = !empty($wd_cf["gercek_ip_aktif"]);
$jeton_var = !empty($wd_cf["jeton_var"]);
$zonlar = $wd_cf["zonlar"] ?? [];
?>

<div class="container">
	<div class="wd-page">

		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title">Cloudflare</h1>
					<p class="wd-subtitle">
						<?= wd_esc__("Real visitor IP, cache purge, and DNS push.") ?>
					</p>
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

			<?php if ($wd_cf === null) { ?>
				<div class="wd-note wd-note-warn">
					<i class="fas fa-triangle-exclamation"></i>
					<span><?= wd_esc__("Cloudflare tool could not run. On the server run") ?>
						<span class="wd-mono">bash /usr/local/hestia/wd/src/kur.sh</span>.</span>
				</div>
			<?php } else { ?>

			<!-- ============ 1) Real visitor IP ============ -->
			<div class="wd-card">
				<div class="wd-card-head">
					<?= wd_esc__("Real Visitor IP") ?>
					<span class="wd-card-note"><?= wd_esc__("no token required") ?></span>
				</div>
				<div class="wd-card-body wd-card-body-pad">
					<p class="wd-aciklama">
						<?= wd_esc__("When the site is behind Cloudflare, connections to the server come from Cloudflare.") ?>
						<?= wd_esc__("If this list is not configured,") ?>
						<b><?= wd_esc__("all visitors appear as Cloudflare IPs") ?></b> —
						<?= wd_esc__("logs are useless and fail2ban bans the wrong address, or even bans Cloudflare and takes the whole site down.") ?>
					</p>

					<div class="wd-kv-satir">
						<span class="wd-durum-rozet <?= $gercek_ip ? "wd-hs-ok" : "wd-hs-fail" ?>">
							<?= $gercek_ip ? wd_esc__("Enabled") : wd_esc__("Disabled") ?>
						</span>
						<span class="wd-v-dim">
							<?= (int) ($wd_cf["ip_v4"] ?? 0) ?> IPv4 · <?= (int) ($wd_cf["ip_v6"] ?? 0) ?> <?= wd_esc__("IPv6 ranges") ?>
							<?php if (!empty($wd_cf["ip_guncelleme"])) { ?>
								· <?= wd_esc__("last update") ?> <?= wd_e($wd_cf["ip_guncelleme"]) ?>
							<?php } ?>
						</span>
					</div>

					<form method="post" class="wd-inline-form">
						<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
						<input type="hidden" name="ok" value="1">
						<input type="hidden" name="islem" value="ip">
						<button type="submit" class="button button-secondary">
							<i class="fas fa-rotate"></i> <?= wd_esc__("Update IP List") ?>
						</button>
					</form>
					<p class="wd-aciklama wd-aciklama-kucuk">
						<?= wd_esc__("The list refreshes automatically every day. Before updating, nginx and Apache configs are validated; if invalid, the change is rolled back.") ?>
					</p>
				</div>
			</div>

			<!-- ============ 2) API token ============ -->
			<div class="wd-card">
				<div class="wd-card-head">
					<?= wd_esc__("API Token") ?>
					<span class="wd-card-note"><?= $jeton_var ? wd_esc__("saved") : wd_esc__("not set") ?></span>
				</div>
				<div class="wd-card-body wd-card-body-pad">
					<?php if ($jeton_var) { ?>
						<div class="wd-kv-satir">
							<span class="wd-durum-rozet wd-hs-ok"><?= wd_esc__("Verified") ?></span>
							<span class="wd-v-dim">
								<?php
        $zn = count($zonlar);
        echo wd_e(sprintf(wd_n__("%d zone visible", "%d zones visible", $zn), $zn));
        ?>
								<?php if (!empty($wd_cf["dogrulandi"])) { ?>
									· <?= wd_e(date("d.m.Y H:i", (int) $wd_cf["dogrulandi"])) ?>
								<?php } ?>
							</span>
						</div>
						<?php if (!empty($wd_cf["hata"])) { ?>
							<div class="wd-note wd-note-warn wd-note-inline">
								<i class="fas fa-triangle-exclamation"></i>
								<span><?= wd_esc__("API response:") ?> <?= wd_e($wd_cf["hata"]) ?></span>
							</div>
						<?php } ?>
						<form method="post" class="wd-inline-form"
							onsubmit="return confirm(<?= htmlspecialchars(json_encode(wd__("Delete the token? API actions will become unavailable.")), ENT_QUOTES, "UTF-8") ?>);">
							<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
							<input type="hidden" name="ok" value="1">
							<input type="hidden" name="islem" value="jeton-sil">
							<button type="submit" class="button button-secondary"><?= wd_esc__("Delete Token") ?></button>
						</form>
					<?php } else { ?>
						<p class="wd-aciklama">
							<?= wd_esc__("In the Cloudflare panel create a token via") ?>
							<b>My Profile → API Tokens → Create Token</b>.
							<?= wd_esc__("Required permissions:") ?>
							<span class="wd-mono">Zone:Read</span>,
							<span class="wd-mono">DNS:Edit</span>,
							<span class="wd-mono">Cache Purge:Purge</span>.
							<?= wd_esc__("Do") ?> <b><?= wd_esc__("not") ?></b>
							<?= wd_esc__("use a Global API Key — that key has access to the whole account.") ?>
						</p>
						<form method="post" class="wd-auth-form">
							<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
							<input type="hidden" name="ok" value="1">
							<input type="hidden" name="islem" value="jeton">
							<div class="wd-auth-field wd-auth-field-genis">
								<label class="form-label" for="v_token"><?= wd_esc__("API Token") ?></label>
								<input class="form-control" type="password" id="v_token" name="v_token"
									autocomplete="off" required minlength="20"
									placeholder="Cloudflare API token">
							</div>
							<button type="submit" class="button"><?= wd_esc__("Save and Verify") ?></button>
						</form>
					<?php } ?>
				</div>
			</div>

			<!-- ============ 3) Zones ============ -->
			<?php if ($jeton_var && !empty($zonlar)) {
    foreach ($zonlar as $z) {
    	$panelde = isset($wd_dns_bolgeleri[$z["ad"]]); ?>
					<details class="wd-group">
						<summary class="wd-group-head">
							<span class="wd-group-icon"><i class="fas fa-cloud"></i></span>
							<span class="wd-group-title"><?= wd_e($z["ad"]) ?></span>
							<span class="wd-hs-badge <?= $z["durum"] === "active" ? "wd-hs-ok" : "wd-hs-warn" ?>">
								<?= wd_e($z["durum"]) ?>
							</span>
							<span class="wd-group-count"><?= wd_e($z["plan"]) ?></span>
							<i class="fas fa-chevron-down wd-group-chevron"></i>
						</summary>
						<div class="wd-auth-body">

							<?php if (!empty($z["ns"])) { ?>
								<div class="wd-kv">
									<span class="wd-k"><?= wd_esc__("Cloudflare nameserver") ?></span>
									<span class="wd-v wd-mono"><?= wd_e(implode(", ", $z["ns"])) ?></span>
								</div>
							<?php } ?>

							<div class="wd-cf-eylemler">
								<form method="post" class="wd-inline-form">
									<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
									<input type="hidden" name="ok" value="1">
									<input type="hidden" name="islem" value="onbellek">
									<input type="hidden" name="v_zone" value="<?= wd_e($z["ad"]) ?>">
									<button type="submit" class="wd-mini-btn"><?= wd_esc__("Purge Cache") ?></button>
								</form>

								<form method="post" class="wd-inline-form">
									<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
									<input type="hidden" name="ok" value="1">
									<input type="hidden" name="islem" value="gelistirme">
									<input type="hidden" name="v_zone" value="<?= wd_e($z["ad"]) ?>">
									<input type="hidden" name="v_deger" value="on">
									<button type="submit" class="wd-mini-btn"><?= wd_esc__("Enable Development Mode (3 h)") ?></button>
								</form>

								<form method="post" class="wd-inline-form">
									<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
									<input type="hidden" name="ok" value="1">
									<input type="hidden" name="islem" value="gelistirme">
									<input type="hidden" name="v_zone" value="<?= wd_e($z["ad"]) ?>">
									<input type="hidden" name="v_deger" value="off">
									<button type="submit" class="wd-mini-btn"><?= wd_esc__("Disable") ?></button>
								</form>

								<?php if ($panelde) { ?>
									<form method="post" class="wd-inline-form"
										onsubmit="return confirm(<?= htmlspecialchars(json_encode(wd__("Panel DNS records will be pushed to Cloudflare. Extra records already in Cloudflare are NOT deleted; only missing ones are added and differing ones updated. Continue?")), ENT_QUOTES, "UTF-8") ?>);">
										<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
										<input type="hidden" name="ok" value="1">
										<input type="hidden" name="islem" value="dns">
										<input type="hidden" name="v_zone" value="<?= wd_e($z["ad"]) ?>">
										<button type="submit" class="wd-mini-btn"><?= wd_esc__("Push Panel DNS") ?></button>
									</form>
								<?php } ?>
							</div>

							<?php if (!$panelde) { ?>
								<p class="wd-aciklama wd-aciklama-kucuk">
									<?= wd_esc__("This zone has no DNS zone in the panel, so the push option is disabled.") ?>
								</p>
							<?php } ?>
						</div>
					</details>
				<?php }
   } ?>

			<?php } ?>
		</div>

		<!-- ================= RIGHT RAIL ================= -->
		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("Things to Know") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Orange cloud") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("If a record is proxied through Cloudflare, Let's Encrypt") ?>
							<b><?= wd_esc__("HTTP validation may fail") ?></b>
							<?= wd_esc__("for that domain. Temporarily set the cloud to grey while issuing a certificate.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Development mode") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("Disables cache for 3 hours. Use while working on the site so changes appear immediately.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("DNS push") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("One-way: panel → Cloudflare. Records in Cloudflare are") ?>
							<b><?= wd_esc__("not deleted") ?></b>;
							<?= wd_esc__("only missing ones are added and differing ones updated.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Where the token lives") ?></span>
						<span class="wd-v-small">
							<span class="wd-mono">wd/cloudflare.json</span>,
							<?= wd_esc__("readable by root only. The panel does not read the file; actions go through the sudo tool.") ?>
						</span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>
