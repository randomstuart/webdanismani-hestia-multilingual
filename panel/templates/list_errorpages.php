<?php
/**
 * WebDanışmanı — "Custom Error Pages" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_errorpages.php
 */

$tok = $_SESSION["token"] ?? "";
$secili = $WD_SAYFALAR[$wd_dosya] ?? ["kod" => "?", "ad" => "", "aciklama" => ""];
?>

<div class="container">
	<div class="wd-page">

		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title"><?= wd_esc__("Custom Error Pages") ?></h1>
					<p class="wd-subtitle">
						<?= wd_esc__("Edit the page visitors see when they open a missing URL or when an error occurs on the site.") ?>
					</p>
				</div>
				<?php if ($wd_domain !== "") { ?>
					<div class="wd-page-actions">
						<a class="button button-secondary" target="_blank" rel="noopener"
							href="https://<?= wd_e($wd_domain) ?>/error/<?= wd_e($wd_dosya) ?>">
							<i class="fas fa-arrow-up-right-from-square"></i> <?= wd_esc__("Preview") ?>
						</a>
					</div>
				<?php } ?>
			</div>

			<?php if ($wd_hata !== "") { ?>
				<div class="wd-note wd-note-err">
					<i class="fas fa-circle-exclamation"></i>
					<span><?= wd_e($wd_hata) ?></span>
				</div>
			<?php } elseif ($wd_bilgi !== "") { ?>
				<div class="wd-note wd-note-ok">
					<i class="fas fa-circle-check"></i>
					<span><?= wd_e($wd_bilgi) ?></span>
				</div>
			<?php } ?>

			<?php if (empty($wd_doms)) { ?>

				<div class="wd-note">
					<i class="fas fa-circle-info"></i>
					<span><?= wd_esc__("You have no web domains yet.") ?></span>
				</div>

			<?php } else { ?>

				<?php // --- Domain picker ---
    if (count($wd_doms) > 1) { ?>
					<div class="wd-card">
						<div class="wd-card-head"><?= wd_esc__("Domain") ?></div>
						<div class="wd-card-body wd-card-body-pad">
							<form method="get" class="wd-inline-select">
								<input type="hidden" name="f" value="<?= wd_e($wd_dosya) ?>">
								<select class="form-select" name="domain" onchange="this.form.submit()">
									<?php foreach ($wd_doms as $d => $r) { ?>
										<option value="<?= wd_e($d) ?>" <?= $d === $wd_domain ? "selected" : "" ?>>
											<?= wd_e($d) ?>
										</option>
									<?php } ?>
								</select>
								<noscript><button type="submit" class="button button-secondary"><?= wd_esc__("Select") ?></button></noscript>
							</form>
						</div>
					</div>
				<?php } ?>

				<!-- --- Error code tabs --- -->
				<div class="wd-err-tabs" role="tablist">
					<?php foreach ($WD_SAYFALAR as $dosya => $bilgi) {
     	$aktif = $dosya === $wd_dosya; ?>
						<a class="wd-err-tab<?= $aktif ? " aktif" : "" ?>"
							role="tab" aria-selected="<?= $aktif ? "true" : "false" ?>"
							href="/list/errorpages/?domain=<?= urlencode($wd_domain) ?>&amp;f=<?= urlencode($dosya) ?>">
							<span class="wd-err-kod"><?= wd_e($bilgi["kod"]) ?></span>
							<span class="wd-err-ad"><?= wd_e($bilgi["ad"]) ?></span>
						</a>
					<?php } ?>
				</div>

				<div class="wd-card">
					<div class="wd-card-head">
						<?= wd_e($secili["kod"]) ?> — <?= wd_e($secili["ad"]) ?>
						<span class="wd-card-note"><?= wd_e($secili["aciklama"]) ?></span>
					</div>
					<div class="wd-card-body wd-card-body-pad">

						<?php if ($wd_okunamadi) { ?>
							<div class="wd-note wd-note-warn wd-note-inline">
								<i class="fas fa-triangle-exclamation"></i>
								<span>
									<?= wd_esc__("This file does not exist yet or cannot be read. It will be created when you save.") ?>
								</span>
							</div>
						<?php } ?>

						<form method="post" class="wd-err-form">
							<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
							<input type="hidden" name="ok" value="1">
							<input type="hidden" name="v_domain" value="<?= wd_e($wd_domain) ?>">
							<input type="hidden" name="v_file" value="<?= wd_e($wd_dosya) ?>">

							<label class="form-label" for="v_content"><?= wd_esc__("HTML content") ?></label>
							<textarea class="form-control u-console wd-err-editor" id="v_content"
								name="v_content" spellcheck="false"><?= wd_e($wd_icerik) ?></textarea>

							<div class="wd-err-actions">
								<button type="submit" name="islem" value="kaydet" class="button">
									<?= wd_esc__("Save") ?>
								</button>
								<button type="submit" name="islem" value="varsayilan"
									class="button button-secondary"
									onclick="return confirm(<?= htmlspecialchars(json_encode(wd__("This page will be restored to the default content. Your changes will be lost. Continue?")), ENT_QUOTES, "UTF-8") ?>);">
									<?= wd_esc__("Restore Default") ?>
								</button>
								<span class="wd-err-boyut" id="wdBoyut"></span>
							</div>
						</form>

					</div>
				</div>

			<?php } ?>

		</div>

		<!-- ================= RIGHT RAIL ================= -->
		<aside class="wd-rail">

			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How Does It Work?") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Live immediately") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("Takes effect as soon as you save; no server restart needed.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Path") ?></span>
						<span class="wd-v-small wd-mono">
							/error/<?= wd_e($wd_dosya) ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Links") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("Image and CSS paths inside the error page must use") ?>
							<b><?= wd_esc__("absolute paths") ?></b>
							<?= wd_esc__("(root-relative like") ?>
							<span class="wd-mono">/logo.png</span>) —
							<?= wd_esc__("relative paths resolve against the failing URL.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("PHP does not run") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("These files are served as plain HTML; PHP code is not executed.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Why 5xx matters") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("During a server error your site is down; this page is the only place left to give visitors a contact path.") ?>
						</span>
					</div>
				</div>
			</div>

		</aside>
	</div>
</div>

<script>
	(function () {
		var ta = document.getElementById("v_content");
		var et = document.getElementById("wdBoyut");
		if (!ta || !et) { return; }
		var AZAMI = <?= (int) WD_AZAMI_BOYUT ?>;
		function guncelle() {
			// Server limit is in BYTES; count bytes here too so multi-byte
			// characters do not make the counter misleading.
			var bayt = new TextEncoder().encode(ta.value).length;
			et.textContent = (bayt / 1024).toFixed(1) + " KB / " +
				(AZAMI / 1024) + " KB";
			et.classList.toggle("asti", bayt > AZAMI);
		}
		ta.addEventListener("input", guncelle);
		guncelle();
	})();
</script>
