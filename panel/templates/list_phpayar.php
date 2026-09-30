<?php
/**
 * WebDanışmanı — "PHP Settings" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_phpayar.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$deg = $wd_ayar["degerler"] ?? [];
$tav = $wd_ayar["tavan"] ?? ["bellek_mb" => 512, "sure" => 300, "yukleme_mb" => 512, "input_vars" => 10000, "kademe" => null];
$acik = function ($k, $vars = "Off") use ($deg) {
	return strtolower((string) ($deg[$k] ?? $vars)) === "on";
};
$secenek_boyut = function ($secili, $tavan_mb) {
	$s = "";
	foreach ([2, 4, 8, 16, 32, 64, 128, 256, 384, 512, 1024] as $mb) {
		if ($mb > $tavan_mb) {
			break;
		}
		$v = $mb . "M";
		$s .= '<option value="' . $v . '"' . (strtoupper((string) $secili) === $v ? " selected" : "") . ">" . $v . "</option>";
	}
	return $s;
};
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik(wd__("PHP Settings"), wd__("Upload size, timeout, memory and error display — per domain.")); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			}
			foreach ($wd_notlar as $n) {
				wd_modul_not($n, "warn");
			} ?>

			<?php if (empty($wd_doms)) { ?>
				<?php wd_modul_not(wd__("You do not have any web domains yet.")); ?>
			<?php } elseif ($wd_ayar === null) { ?>
				<?php wd_modul_not(wd__("Could not read settings for this domain."), "warn"); ?>
			<?php } else { ?>

				<?php wd_modul_domain_kutusu($wd_doms, $wd_domain, "/list/phpayar/"); ?>

				<form method="post" class="wdm-form">
					<?= wd_modul_form_gizli("kaydet", $wd_domain) ?>

					<div class="wd-card">
						<div class="wd-card-head">
							<?= wd_esc__("Limits") ?>
							<span class="wd-card-note">PHP <?= wd_e($wd_ayar["php"] ?: "?") ?><?= $tav["kademe"] ? " · " . wd_e(sprintf(wd__("tier: %s"), $tav["kademe"])) : "" ?></span>
						</div>
						<div class="wd-card-body wd-card-body-pad">
							<div class="wdm-satir">
								<div class="wdm-alan">
									<label class="form-label" for="v_upload"><?= wd_esc__("File upload limit") ?></label>
									<select class="form-select" id="v_upload" name="v_upload"><?= $secenek_boyut($deg["upload_max_filesize"] ?? "8M", $tav["yukleme_mb"]) ?></select>
									<span class="wdm-ipucu">upload_max_filesize · <?= wd_esc__("ceiling") ?> <b><?= (int) $tav["yukleme_mb"] ?>M</b></span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_post"><?= wd_esc__("Form post limit") ?></label>
									<select class="form-select" id="v_post" name="v_post"><?= $secenek_boyut($deg["post_max_size"] ?? "10M", $tav["yukleme_mb"]) ?></select>
									<span class="wdm-ipucu">post_max_size · <?= wd_esc__("cannot be smaller than the upload limit") ?></span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_memory"><?= wd_esc__("Memory limit") ?></label>
									<select class="form-select" id="v_memory" name="v_memory"><?= $secenek_boyut($deg["memory_limit"] ?? "128M", $tav["bellek_mb"]) ?></select>
									<span class="wdm-ipucu">memory_limit · <?= wd_esc__("ceiling") ?> <b><?= (int) $tav["bellek_mb"] ?>M</b></span>
								</div>
							</div>
							<div class="wdm-satir" style="margin-top:12px">
								<div class="wdm-alan">
									<label class="form-label" for="v_exec"><?= wd_esc__("Execution time (sec)") ?></label>
									<input class="form-control" type="number" id="v_exec" name="v_exec" min="1" max="<?= (int) $tav["sure"] ?>" value="<?= wd_e($deg["max_execution_time"] ?? "30") ?>">
									<span class="wdm-ipucu">max_execution_time · <?= wd_esc__("ceiling") ?> <b><?= (int) $tav["sure"] ?> <?= wd_esc__("sec") ?></b></span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_input"><?= wd_esc__("Input parsing time (sec)") ?></label>
									<input class="form-control" type="number" id="v_input" name="v_input" min="-1" max="<?= (int) $tav["sure"] ?>" value="<?= wd_e($deg["max_input_time"] ?? "60") ?>">
									<span class="wdm-ipucu">max_input_time · <?= wd_esc__("-1 = same as execution time") ?></span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_vars"><?= wd_esc__("Max form fields") ?></label>
									<input class="form-control" type="number" id="v_vars" name="v_vars" min="100" max="<?= (int) $tav["input_vars"] ?>" value="<?= wd_e($deg["max_input_vars"] ?? "1000") ?>">
									<span class="wdm-ipucu">max_input_vars · <?= wd_esc__("use 3000+ for WooCommerce/menu save issues") ?></span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_session"><?= wd_esc__("Session lifetime (sec)") ?></label>
									<input class="form-control" type="number" id="v_session" name="v_session" min="300" max="604800" value="<?= wd_e($deg["session.gc_maxlifetime"] ?? "1440") ?>">
									<span class="wdm-ipucu">session.gc_maxlifetime · <?= wd_esc__("1440 = 24 min") ?></span>
								</div>
							</div>
						</div>
					</div>

					<div class="wd-card">
						<div class="wd-card-head"><?= wd_esc__("Behavior") ?></div>
						<div class="wd-card-body wd-card-body-pad">
							<div class="wdm-satir">
								<div class="wdm-alan">
									<label class="form-label" for="v_tz"><?= wd_esc__("Timezone") ?></label>
									<select class="form-select" id="v_tz" name="v_tz">
										<?php foreach ((array) ($wd_ayar["saat_dilimleri"] ?? ["Europe/Istanbul"]) as $tz) { ?>
											<option value="<?= wd_e($tz) ?>" <?= ($deg["date.timezone"] ?? "Europe/Istanbul") === $tz ? "selected" : "" ?>><?= wd_e($tz) ?></option>
										<?php } ?>
									</select>
									<span class="wdm-ipucu">date.timezone</span>
								</div>
							</div>
							<label class="wd-secim">
								<input type="checkbox" name="v_display" value="1" <?= $acik("display_errors") ? "checked" : "" ?>>
								<span><b><?= wd_esc__("Show errors on screen") ?></b>
									<span class="wd-v-small">display_errors · <?= wd_esc__("Enable only during development; file paths can leak to visitors.") ?></span></span>
							</label>
							<label class="wd-secim">
								<input type="checkbox" name="v_log" value="1" <?= $acik("log_errors") ? "checked" : "" ?>>
								<span><b><?= wd_esc__("Log errors") ?></b>
									<span class="wd-v-small">log_errors · <?= wd_esc__('Shown in the "PHP Error Log" box below. Recommended.') ?></span></span>
							</label>
							<label class="wd-secim">
								<input type="checkbox" name="v_short" value="1" <?= $acik("short_open_tag") ? "checked" : "" ?>>
								<span><b><?= wd_esc__("Short open tag") ?></b>
									<span class="wd-v-small">short_open_tag · <?= wd_esc__("Enable if legacy scripts use") ?> <span class="wd-mono">&lt;?</span>.</span></span>
							</label>
						</div>
					</div>

					<?php if (!empty($wd_ayar["yabanci"])) { ?>
						<div class="wd-card">
							<div class="wd-card-head"><?= wd_esc__("Other lines in the file") ?> <span class="wd-card-note"><?= wd_esc__("preserved") ?></span></div>
							<div class="wd-card-body wd-card-body-pad">
								<pre class="wdm-kod wdm-kod-kucuk"><?= wd_e(implode("\n", (array) $wd_ayar["yabanci"])) ?></pre>
							</div>
						</div>
					<?php } ?>

					<div class="wdm-dugmeler">
						<button type="submit" class="button"><?= wd_esc__("Save") ?></button>
						<?php if (!empty($wd_ayar["var"])) { ?>
							<button type="submit" name="islem" value="sil" class="button button-secondary"
								onclick="return confirm('<?= wd_esc__("Custom PHP settings will be removed and server defaults restored. Continue?") ?>');"><?= wd_esc__("Reset to Defaults") ?></button>
						<?php } ?>
					</div>
				</form>

				<div class="wd-card">
					<div class="wd-card-head">
						<?= wd_esc__("PHP Error Log") ?>
						<span class="wd-card-note"><?= !empty($wd_ayar["gunluk_boyut"]) ? wd_e(wd_modul_bayt((int) $wd_ayar["gunluk_boyut"])) : wd_esc__("empty") ?></span>
					</div>
					<div class="wd-card-body wd-card-body-pad">
						<?php if (empty($wd_gunluk)) { ?>
							<p class="wd-empty"><?= $acik("log_errors") ? wd_esc__("Log is empty — no recorded errors.") : wd_esc__('Logging is off. Enable "Log errors".') ?></p>
						<?php } else { ?>
							<pre class="wdm-kod"><?= wd_e(implode("\n", $wd_gunluk)) ?></pre>
							<form method="post" class="wdm-dugmeler" style="margin-top:8px">
								<?= wd_modul_form_gizli("gunluk-temizle", $wd_domain) ?>
								<button type="submit" class="button button-secondary"><?= wd_esc__("Clear Log") ?></button>
								<span class="wdm-ipucu"><?= wd_esc__("Path:") ?> <span class="wd-mono"><?= wd_e($wd_ayar["gunluk_yolu"] ?? "") ?></span></span>
							</form>
						<?php } ?>
					</div>
				</div>

			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How It Works") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Where it is written") ?></span>
						<span class="wd-v-small"><?= wd_esc__("To the") ?> <span class="wd-mono">.user.ini</span> <?= wd_esc__("file in the document root. The file belongs to you; the server is not restarted.") ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("When it takes effect") ?></span>
						<span class="wd-v-small"><?= wd_esc__("PHP-FPM re-reads the file at most every") ?> <b><?= wd_esc__("5 minutes") ?></b>.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Why ceilings exist") ?></span>
						<span class="wd-v-small"><?= wd_esc__("Your account resource tier sets upper bounds for memory and time; values above the tier silently fail. This page shows the ceiling and lowers anything that exceeds it.") ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Verify") ?></span>
						<span class="wd-v-small"><?= wd_esc__("You can place a file with") ?> <span class="wd-mono">phpinfo()</span> <?= wd_esc__("on your site to verify the value; delete it when done.") ?></span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>
