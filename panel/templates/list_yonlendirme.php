<?php
/**
 * WebDanışmanı — "Redirects" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_yonlendirme.php
 */

$tok = $_SESSION["token"] ?? "";
$k = $wd_kurallar;
$basliklar = [
	"nosniff" => ["X-Content-Type-Options", wd__("Stops the browser from guessing file types.")],
	"frame" => ["X-Frame-Options", wd__("Prevents embedding the site in another page's frame (clickjacking).")],
	"referrer" => ["Referrer-Policy", wd__("Reduces leaking full URL details to external sites.")],
	"hsts" => ["Strict-Transport-Security", wd__("Browser uses HTTPS only for one year. Enable only if SSL is solid.")],
];
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title"><?= wd_esc__("Redirects and Site Rules") ?></h1>
					<p class="wd-subtitle">
						<?= wd_esc__("Path redirects, security headers, hotlink protection, and IP blocking.") ?>
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

			<?php if (empty($wd_doms)) { ?>
				<div class="wd-note"><i class="fas fa-circle-info"></i>
					<span><?= wd_esc__("You have no web domains yet.") ?></span></div>
			<?php } else { ?>

				<?php if (count($wd_doms) > 1) { ?>
					<div class="wd-card">
						<div class="wd-card-head"><?= wd_esc__("Domain") ?></div>
						<div class="wd-card-body wd-card-body-pad">
							<form method="get" class="wd-inline-select">
								<select class="form-select" name="domain" onchange="this.form.submit()">
									<?php foreach ($wd_doms as $d => $_) { ?>
										<option value="<?= wd_e($d) ?>" <?= $d === $wd_domain ? "selected" : "" ?>><?= wd_e($d) ?></option>
									<?php } ?>
								</select>
								<noscript><button type="submit" class="button button-secondary"><?= wd_esc__("Select") ?></button></noscript>
							</form>
						</div>
					</div>
				<?php } ?>

				<form method="post">
					<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
					<input type="hidden" name="ok" value="1">
					<input type="hidden" name="islem" value="kaydet">
					<input type="hidden" name="v_domain" value="<?= wd_e($wd_domain) ?>">

					<!-- Path redirects -->
					<div class="wd-card">
						<div class="wd-card-head">
							<?= wd_esc__("Path Redirects") ?>
							<span class="wd-card-note"><?= wd_esc__("301 permanent · 302 temporary") ?></span>
						</div>
						<div class="wd-card-body wd-card-body-pad">
							<div id="wdYonListe">
								<?php $satirlar = $k["yollar"];
        if (empty($satirlar)) {
        	$satirlar = [["kaynak" => "", "hedef" => "", "kod" => "301", "tam" => false]];
        }
        foreach ($satirlar as $r) { ?>
									<div class="wd-yon-satir">
										<input class="form-control" type="text" name="v_kaynak[]"
											value="<?= wd_e($r["kaynak"]) ?>" placeholder="/eski-sayfa"
											pattern="/[^\s\x22'{}]*" title="<?= wd_esc__("Must start with /") ?>">
										<span class="wd-yon-ok">→</span>
										<input class="form-control" type="text" name="v_hedef[]"
											value="<?= wd_e($r["hedef"]) ?>" placeholder="/yeni-sayfa or https://...">
										<select class="form-select wd-yon-kod" name="v_kod[]">
											<option value="301" <?= (string) $r["kod"] === "301" ? "selected" : "" ?>>301</option>
											<option value="302" <?= (string) $r["kod"] === "302" ? "selected" : "" ?>>302</option>
										</select>
										<label class="wd-yon-tam" title="<?= wd_esc__("If checked, only this exact URL; otherwise sub-paths move too") ?>">
											<input type="checkbox" name="v_tam[]" value="1" <?= !empty($r["tam"]) ? "checked" : "" ?>>
											<?= wd_esc__("exact") ?>
										</label>
										<button type="button" class="wd-mini-btn wd-mini-btn-danger" onclick="wdSatirSil(this)">×</button>
									</div>
								<?php } ?>
							</div>
							<button type="button" class="wd-mini-btn" onclick="wdSatirEkle()">+ <?= wd_esc__("Add Row") ?></button>
							<p class="wd-aciklama wd-aciklama-kucuk">
								<?= wd_esc__("If “exact” is unchecked, a") ?>
								<span class="wd-mono">/blog</span>
								<?= wd_esc__("rule also moves") ?>
								<span class="wd-mono">/blog/yazi-1</span>.
							</p>
						</div>
					</div>

					<!-- Security headers -->
					<div class="wd-card">
						<div class="wd-card-head"><?= wd_esc__("Security Headers") ?></div>
						<div class="wd-card-body wd-card-body-pad">
							<?php foreach ($basliklar as $anahtar => $bilgi) { ?>
								<label class="wd-secim">
									<input type="checkbox" name="v_baslik[]" value="<?= wd_e($anahtar) ?>"
										<?= in_array($anahtar, $k["basliklar"], true) ? "checked" : "" ?>>
									<span>
										<b class="wd-mono"><?= wd_e($bilgi[0]) ?></b>
										<span class="wd-v-small"><?= wd_e($bilgi[1]) ?></span>
									</span>
								</label>
							<?php } ?>
						</div>
					</div>

					<!-- Hotlink + IP -->
					<div class="wd-card">
						<div class="wd-card-head"><?= wd_esc__("Access Rules") ?></div>
						<div class="wd-card-body wd-card-body-pad">
							<label class="wd-secim">
								<input type="checkbox" name="v_hotlink" value="1" <?= !empty($k["hotlink"]) ? "checked" : "" ?>>
								<span>
									<b><?= wd_esc__("Hotlink protection") ?></b>
									<span class="wd-v-small">
										<?= wd_esc__("Other sites cannot display your images on their pages — stops them burning your bandwidth.") ?>
									</span>
								</span>
							</label>
							<div class="wd-auth-field wd-auth-field-genis">
								<label class="form-label" for="v_hotlink_izinli"><?= wd_esc__("Domains allowed to hotlink") ?></label>
								<input class="form-control" type="text" id="v_hotlink_izinli" name="v_hotlink_izinli"
									value="<?= wd_e(implode(" ", $k["hotlink_izinli"])) ?>"
									placeholder="cdn.yoursite.com partner.com">
							</div>
							<div class="wd-auth-field wd-auth-field-genis">
								<label class="form-label" for="v_ip"><?= wd_esc__("IP addresses to block") ?></label>
								<textarea class="form-control" id="v_ip" name="v_ip" rows="3"
									placeholder="203.0.113.7&#10;198.51.100.0/24"><?= wd_e(implode("\n", $k["engelli_ip"])) ?></textarea>
							</div>
						</div>
					</div>

					<div class="wd-err-actions">
						<button type="submit" class="button"><?= wd_esc__("Save and Publish") ?></button>
						<button type="submit" name="islem" value="sil" class="button button-secondary"
							onclick="return confirm(<?= htmlspecialchars(json_encode(wd__("ALL custom rules for this domain will be removed. Continue?")), ENT_QUOTES, "UTF-8") ?>);">
							<?= wd_esc__("Remove All Rules") ?>
						</button>
					</div>
				</form>

			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How Does It Work?") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Is it safe?") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("After rules are written,") ?>
							<b><?= wd_esc__("nginx validation") ?></b>
							<?= wd_esc__("runs. If invalid, the previous state is restored — a bad rule cannot take the site down.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("301 or 302?") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("Use") ?> <b>301</b>
							<?= wd_esc__("for permanent moves (passes search ranking). Use") ?>
							<b>302</b>
							<?= wd_esc__("for temporary campaigns.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("HSTS caution") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("After enabling, browsers force HTTPS for a year. If SSL is not solid,") ?>
							<b><?= wd_esc__("do not enable") ?></b>;
							<?= wd_esc__("undoing it is hard.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Where stored") ?></span>
						<span class="wd-v-small wd-mono">conf/web/&lt;domain&gt;/nginx.conf_wd</span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>

<script>
	function wdSatirEkle() {
		var liste = document.getElementById("wdYonListe");
		var ilk = liste.querySelector(".wd-yon-satir");
		var yeni = ilk.cloneNode(true);
		yeni.querySelectorAll("input").forEach(function (i) {
			if (i.type === "checkbox") { i.checked = false; } else { i.value = ""; }
		});
		liste.appendChild(yeni);
	}
	function wdSatirSil(btn) {
		var liste = document.getElementById("wdYonListe");
		// Last row is not removed; an empty form would block adding new rules.
		if (liste.querySelectorAll(".wd-yon-satir").length > 1) {
			btn.closest(".wd-yon-satir").remove();
		} else {
			btn.closest(".wd-yon-satir").querySelectorAll("input").forEach(function (i) {
				if (i.type === "checkbox") { i.checked = false; } else { i.value = ""; }
			});
		}
	}
</script>
