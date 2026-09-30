<?php
/**
 * WebDanışmanı — "Application Firewall" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_waf.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$k = $wd_waf["kurallar"] ?? [];
$secim = function (string $ad, string $anahtar, string $baslik, string $aciklama, bool $varsayilan = false) use ($k) {
	$acik = array_key_exists($anahtar, $k) ? !empty($k[$anahtar]) : $varsayilan;
	echo '<label class="wd-secim"><input type="checkbox" name="' . wd_e($ad) . '" value="1"' . ($acik ? " checked" : "") . ">";
	echo "<span><b>" . wd_e($baslik) . '</b><span class="wd-v-small">' . wd_e($aciklama) . "</span></span></label>\n";
};
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik(wd__("Application Firewall"), wd__("Blocks bad bots, injection patterns, and login brute force at the nginx layer.")); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>

			<?php if (empty($wd_doms)) { ?>
				<?php wd_modul_not(wd__("You do not have any web domains yet.")); ?>
			<?php } elseif ($wd_waf === null) { ?>
				<?php wd_modul_not(wd__("Could not read rules."), "warn"); ?>
			<?php } else { ?>
				<?php wd_modul_domain_kutusu($wd_doms, $wd_domain, "/list/waf/"); ?>

				<?php if ($wd_ist && !empty($wd_ist["var"])) { ?>
					<div class="wd-stats">
						<div class="wd-stat"><div class="wd-stat-label"><?= wd_esc__("BLOCKED (24 H)") ?></div><div class="wd-stat-value"><?= (int) ($wd_ist["engellenen"] ?? 0) ?><span class="wd-stat-of">403</span></div></div>
						<div class="wd-stat"><div class="wd-stat-label"><?= wd_esc__("RATE LIMIT (24 H)") ?></div><div class="wd-stat-value"><?= (int) ($wd_ist["hiz"] ?? 0) ?><span class="wd-stat-of">429</span></div></div>
						<div class="wd-stat"><div class="wd-stat-label"><?= wd_esc__("STATUS") ?></div><div class="wd-stat-value"><?= !empty($wd_waf["var"]) && !empty($k["etkin"]) ? '<span style="color:var(--wd-green)">' . wd_esc__("Enabled") . '</span>' : '<span style="color:var(--wd-muted)">' . wd_esc__("Disabled") . '</span>' ?></div></div>
					</div>
				<?php } ?>

				<form method="post" class="wdm-form">
					<?= wd_modul_form_gizli("kaydet", $wd_domain) ?>
					<div class="wd-card">
						<div class="wd-card-head"><?= wd_esc__("Rules") ?> <span class="wd-card-note"><?= wd_e($wd_domain) ?></span></div>
						<div class="wd-card-body wd-card-body-pad">
							<?php $secim("v_etkin", "etkin", wd__("Firewall enabled"), wd__("When off, rules are kept but not applied."), true); ?>
							<?php $secim("v_bot", "bot_engel", wd__("Block bad bots"), wd__("Scanners and scrapers such as AhrefsBot, SemrushBot, MJ12bot, sqlmap, nikto, wpscan get 403. Google/Bing are not affected."), true); ?>
							<?php $secim("v_arac", "arac_engel", wd__("Block command-line clients"), wd__("curl, wget, python-requests, Go/Java clients. Do NOT enable if your site has an API or webhook receiver."), false); ?>
							<?php $secim("v_sorgu", "sorgu_engel", wd__("Block injection patterns"), wd__("Requests containing patterns like UNION SELECT, base64_decode(, <script, ../../, /etc/passwd, php://input get 403."), true); ?>
							<?php $secim("v_yukleme", "yukleme_php_engel", wd__("Block PHP execution in upload directories"), wd__("A PHP file that sneaks under uploads/, storage/, media/, images/ cannot run. The most common entry point on compromised sites."), true); ?>
							<?php $secim("v_hassas", "hassas_dosya_engel", wd__("Hide sensitive files"), wd__(".sql, .bak, .old, .log, .sh, wp-config.php, readme.html, composer.json cannot be downloaded directly."), true); ?>
							<?php $secim("v_giris", "giris_koruma", wd__("Login page brute-force protection"), wd__("Addresses like wp-login.php, xmlrpc.php, /giris, /login: 30 requests per IP per minute; excess gets 429.") . (empty($wd_waf["giris_koruma_mumkun"]) ? " " . wd__("(Rate-limit zone is not set up on the server; admin must run kur-modul.sh.)") : ""), true); ?>
							<?php $secim("v_xmlrpc", "xmlrpc_kapat", wd__("Disable xmlrpc.php"), wd__("If you do not use Jetpack or the mobile app on WordPress, disable it; it is a target for brute force and DDoS reflection."), false); ?>
							<div class="wd-auth-field wd-auth-field-genis" style="margin-top:10px">
								<label class="form-label" for="v_izinli_ip"><?= wd_esc__("IPs exempt from rules") ?></label>
								<textarea class="form-control" id="v_izinli_ip" name="v_izinli_ip" rows="2" placeholder="<?= wd_esc__("Your office IP — 203.0.113.7") ?>"><?= wd_e(implode("\n", (array) ($k["izinli_ip"] ?? []))) ?></textarea>
								<span class="wdm-ipucu"><?= wd_esc__("Bot and query rules are not applied to these addresses. One IP per line.") ?></span>
							</div>
						</div>
					</div>
					<div class="wdm-dugmeler">
						<button type="submit" class="button"><?= wd_esc__("Save and Publish") ?></button>
						<?php if (!empty($wd_waf["var"])) { ?>
							<button type="submit" name="islem" value="sil" class="button button-secondary"
								onclick="return confirm('<?= wd_esc__("All WAF rules for this domain will be removed. Continue?") ?>');"><?= wd_esc__("Remove Rules") ?></button>
						<?php } ?>
					</div>
				</form>

				<?php if ($wd_ist && !empty($wd_ist["var"]) && (!empty($wd_ist["ipler"]) || !empty($wd_ist["yollar"]))) { ?>
					<div class="wdm-grid2">
						<div class="wd-card">
							<div class="wd-card-head"><?= wd_esc__("Most blocked IPs") ?> <span class="wd-card-note"><?= wd_esc__("24 hours") ?></span></div>
							<div class="wdm-tablo-sar"><table class="wdm-tablo">
								<thead><tr><th>IP</th><th class="sag"><?= wd_esc__("Requests") ?></th></tr></thead>
								<tbody>
									<?php foreach ((array) $wd_ist["ipler"] as $r) { ?>
										<tr><td class="mono"><?= wd_e($r["ip"]) ?></td><td class="sag"><?= (int) $r["adet"] ?></td></tr>
									<?php } ?>
								</tbody>
							</table></div>
						</div>
						<div class="wd-card">
							<div class="wd-card-head"><?= wd_esc__("Targeted paths") ?> <span class="wd-card-note"><?= wd_esc__("24 hours") ?></span></div>
							<div class="wdm-tablo-sar"><table class="wdm-tablo">
								<thead><tr><th><?= wd_esc__("Path") ?></th><th class="sag"><?= wd_esc__("Requests") ?></th></tr></thead>
								<tbody>
									<?php foreach ((array) $wd_ist["yollar"] as $r) { ?>
										<tr><td class="mono"><?= wd_e($r["yol"]) ?></td><td class="sag"><?= (int) $r["adet"] ?></td></tr>
									<?php } ?>
								</tbody>
							</table></div>
						</div>
					</div>
				<?php } ?>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How It Works") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Where it runs") ?></span>
						<span class="wd-v-small"><?= wd_esc__("Rules are written to nginx; requests are cut before reaching PHP. Site code and speed are unaffected.") ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("False positives") ?></span>
						<span class="wd-v-small"><?= wd_esc__('If a plugin stopped working, try disabling "Command-line clients" and "Injection patterns" in turn. Add your own IP to the allow list.') ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Cloudflare</span>
						<span class="wd-v-small"><?= wd_esc__("If the site is behind Cloudflare, the admin must keep the IP list current in the Cloudflare module for real visitor IPs; otherwise rate limiting treats all visitors as one IP.") ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Is it safe?") ?></span>
						<span class="wd-v-small"><?= wd_esc__("nginx validation runs on every save; an invalid rule rolls back to the previous state.") ?></span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>
