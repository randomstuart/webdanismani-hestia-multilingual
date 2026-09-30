<?php
/**
 * WebDanışmanı — "Node.js Apps" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_node.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$bos_doms = array_filter(array_keys($wd_doms), fn($d) => !in_array($d, $wd_kullanilan, true));
$durum_rozet = function (string $d): string {
	if ($d === "active") {
		return '<span class="wdm-rozet wdm-rozet-ok"><span class="wdm-nokta wdm-nokta-ok"></span>' . wd_esc__("running") . '</span>';
	}
	if ($d === "activating") {
		return '<span class="wdm-rozet wdm-rozet-warn">' . wd_esc__("starting") . '</span>';
	}
	return '<span class="wdm-rozet wdm-rozet-err"><span class="wdm-nokta wdm-nokta-err"></span>' . wd_e($d) . "</span>";
};
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik(wd__("Node.js Apps"), wd__("Your app runs as a service under your account, restarts on crash, and is bound to your domain.")); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>
			<?php if ($wd_node === "") {
				wd_modul_not(wd__("Node.js is not installed on the server. This page activates when the admin installs nodejs from the NodeSource repository."), "warn");
			} ?>

			<?php if ($wd_gunluk !== null) { ?>
				<div class="wd-card">
					<div class="wd-card-head"><?= wd_esc__("Log") ?> · <?= wd_e($wd_gunluk["domain"]) ?> <?= $durum_rozet((string) ($wd_gunluk["durum"] ?? "")) ?></div>
					<div class="wd-card-body wd-card-body-pad">
						<?php if (!empty($wd_gunluk["journal"])) { ?><p class="wdm-ipucu">systemd:</p><pre class="wdm-kod wdm-kod-kucuk"><?= wd_e(implode("\n", (array) $wd_gunluk["journal"])) ?></pre><?php } ?>
						<p class="wdm-ipucu" style="margin-top:6px"><?= wd_esc__("App output (private/node.log):") ?></p>
						<pre class="wdm-kod"><?= wd_e(implode("\n", (array) ($wd_gunluk["satirlar"] ?? [])) ?: "(" . wd__("empty") . ")") ?></pre>
					</div>
				</div>
			<?php } ?>

			<?php if (!empty($wd_uygulamalar)) { ?>
				<div class="wd-card">
					<div class="wd-card-head"><?= wd_esc__("Running apps") ?></div>
					<div class="wdm-liste">
						<?php foreach ($wd_uygulamalar as $a) { ?>
							<div class="wdm-oge">
								<div class="wdm-oge-bas">
									<span class="wdm-oge-ad"><a href="https://<?= wd_e($a["domain"]) ?>/" target="_blank" rel="noopener"><?= wd_e($a["domain"]) ?></a> <?= $durum_rozet((string) ($a["durum"] ?? "")) ?></span>
									<span class="wdm-oge-alt"><span class="wdm-mono"><?= wd_e(($a["alt_dizin"] ? $a["alt_dizin"] . "/" : "") . $a["giris"]) ?></span> · 127.0.0.1:<?= (int) $a["port"] ?> · <?= wd_e(wd_modul_tarih($a["ts"] ?? null)) ?></span>
								</div>
								<div class="wdm-oge-eylem">
									<form method="post"><?= wd_modul_form_gizli("gunluk", $a["domain"]) ?><button type="submit" class="wd-mini-btn"><?= wd_esc__("Log") ?></button></form>
									<form method="post"><?= wd_modul_form_gizli("yeniden", $a["domain"]) ?><button type="submit" class="wd-mini-btn"><i class="fas fa-rotate-right"></i> <?= wd_esc__("Restart") ?></button></form>
									<form method="post" onsubmit="return confirm('<?= wd_e(sprintf(wd__("The service for %s will be stopped and removed; the domain returns to the stock PHP template. Files are not deleted. Continue?"), $a["domain"])) ?>');"><?= wd_modul_form_gizli("kaldir", $a["domain"]) ?><button type="submit" class="wd-mini-btn wd-mini-btn-danger"><?= wd_esc__("Remove") ?></button></form>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>

			<?php if (!empty($bos_doms) && $wd_node !== "") { ?>
				<form method="post" class="wdm-form">
					<?= wd_modul_form_gizli("ekle") ?>
					<div class="wd-card">
						<div class="wd-card-head"><?= wd_esc__("New app") ?></div>
						<div class="wd-card-body wd-card-body-pad">
							<div class="wdm-satir">
								<div class="wdm-alan">
									<label class="form-label" for="v_domain"><?= wd_esc__("Domain") ?></label>
									<select class="form-select" id="v_domain" name="v_domain">
										<?php foreach ($bos_doms as $d) { ?><option value="<?= wd_e($d) ?>"><?= wd_e($d) ?></option><?php } ?>
									</select>
									<span class="wdm-ipucu"><?= wd_esc__("All requests for this domain go to the app; PHP will not run.") ?></span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_giris"><?= wd_esc__("Entry file") ?></label>
									<input class="form-control wdm-mono" id="v_giris" name="v_giris" value="app.js" pattern="[A-Za-z0-9._/-]{1,120}">
									<span class="wdm-ipucu">server.js, index.js, dist/main.js …</span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_alt"><?= wd_esc__("Subdirectory") ?> <span class="wdm-ipucu"><?= wd_esc__("empty = public_html") ?></span></label>
									<input class="form-control wdm-mono" id="v_alt" name="v_alt" placeholder="api">
								</div>
							</div>
							<label class="wd-secim" style="margin-top:8px"><input type="checkbox" name="v_npm" value="1" checked><span><b><?= wd_esc__("Install dependencies if package.json exists") ?></b><span class="wd-v-small">npm install --omit=dev (<?= wd_esc__("when node_modules is missing") ?>)</span></span></label>
						</div>
					</div>
					<div class="wdm-dugmeler"><button type="submit" class="button"><i class="fas fa-play"></i> <?= wd_esc__("Start") ?></button></div>
				</form>
			<?php } elseif ($wd_node !== "" && empty($bos_doms)) { ?>
				<?php wd_modul_not(wd__("All your domains already have an app defined, or you have no domains.")); ?>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How It Works") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Port") ?></span><span class="wd-v-small"><?= wd_esc__("Your app receives a") ?> <span class="wd-mono">PORT</span> <?= wd_esc__("environment variable (3000–3999, automatic). Your code must listen on") ?> <span class="wd-mono">process.env.PORT</span> <?= wd_esc__("and bind to") ?> <span class="wd-mono">127.0.0.1</span>.</span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Service") ?></span><span class="wd-v-small"><?= wd_esc__("Runs as a systemd unit under your account user; restarts in 5 seconds on crash; starts automatically on boot.") ?></span></div>
					<div class="wd-kv"><span class="wd-k">Nginx</span><span class="wd-v-small"><?= wd_esc__('The domain is switched to the "wd-node" template; all requests including WebSocket are proxied to the app. SSL and Let\'s Encrypt keep working.') ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Log") ?></span><span class="wd-v-small"><span class="wd-mono">private/node.log</span> — <?= wd_esc__("also readable from File Manager.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Node version") ?></span><span class="wd-v-small"><?= $wd_node !== "" ? wd_e($wd_node) : wd_esc__("not installed") ?></span></div>
				</div>
			</div>
		</aside>
	</div>
</div>
