<?php
/**
 * WebDanışmanı — "Özel Hata Sayfaları" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_errorpages.php
 */

$tok = $_SESSION["token"] ?? "";
$secili = $WD_SAYFALAR[$wd_dosya] ?? ["kod" => "?", "ad" => "", "aciklama" => ""];
?>

<div class="container">
	<div class="wd-page">

		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title">Özel Hata Sayfaları</h1>
					<p class="wd-subtitle">
						Ziyaretçi olmayan bir adrese girdiğinde ya da sitede hata oluştuğunda
						görünen sayfayı düzenleyin.
					</p>
				</div>
				<?php if ($wd_domain !== "") { ?>
					<div class="wd-page-actions">
						<a class="button button-secondary" target="_blank" rel="noopener"
							href="https://<?= wd_e($wd_domain) ?>/error/<?= wd_e($wd_dosya) ?>">
							<i class="fas fa-arrow-up-right-from-square"></i> Önizle
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
					<span>Henüz web alan adınız yok.</span>
				</div>

			<?php } else { ?>

				<?php // --- Alan adı seçimi ---
    if (count($wd_doms) > 1) { ?>
					<div class="wd-card">
						<div class="wd-card-head">Alan Adı</div>
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
								<noscript><button type="submit" class="button button-secondary">Seç</button></noscript>
							</form>
						</div>
					</div>
				<?php } ?>

				<!-- --- Hata kodu sekmeleri --- -->
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
									Bu dosya henüz yok ya da okunamıyor. Kaydettiğinizde oluşturulacak.
								</span>
							</div>
						<?php } ?>

						<form method="post" class="wd-err-form">
							<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
							<input type="hidden" name="ok" value="1">
							<input type="hidden" name="v_domain" value="<?= wd_e($wd_domain) ?>">
							<input type="hidden" name="v_file" value="<?= wd_e($wd_dosya) ?>">

							<label class="form-label" for="v_content">HTML içeriği</label>
							<textarea class="form-control u-console wd-err-editor" id="v_content"
								name="v_content" spellcheck="false"><?= wd_e($wd_icerik) ?></textarea>

							<div class="wd-err-actions">
								<button type="submit" name="islem" value="kaydet" class="button">
									Kaydet
								</button>
								<button type="submit" name="islem" value="varsayilan"
									class="button button-secondary"
									onclick="return confirm('Bu sayfa varsayılan içeriğe döndürülecek. Yaptığınız değişiklikler kaybolur. Devam edilsin mi?');">
									Varsayılana Dön
								</button>
								<span class="wd-err-boyut" id="wdBoyut"></span>
							</div>
						</form>

					</div>
				</div>

			<?php } ?>

		</div>

		<!-- ================= SAĞ PANEL ================= -->
		<aside class="wd-rail">

			<div class="wd-card">
				<div class="wd-card-head">Nasıl Çalışır?</div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">Anında yayında</span>
						<span class="wd-v-small">
							Kaydettiğiniz an geçerli olur; sunucuyu yeniden başlatmak gerekmez.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Adres</span>
						<span class="wd-v-small wd-mono">
							/error/<?= wd_e($wd_dosya) ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Bağlantılar</span>
						<span class="wd-v-small">
							Hata sayfası içindeki resim ve CSS yolları <b>tam adresle</b>
							(<span class="wd-mono">/logo.png</span> gibi kökten) yazılmalı —
							göreli yollar hata veren adrese göre çözülür.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">PHP çalışmaz</span>
						<span class="wd-v-small">
							Bu dosyalar düz HTML olarak sunulur; PHP kodu çalıştırılmaz.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">5xx neden önemli</span>
						<span class="wd-v-small">
							Sunucu hatası anında siteniz çalışmıyordur; bu sayfa ziyaretçiye
							iletişim yolu bırakmanın tek yeridir.
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
			// Sunucu tarafı sınır BAYT üzerinden; burada da bayt sayılır,
			// yoksa Türkçe karakterli içerikte sayaç yanıltırdı.
			var bayt = new TextEncoder().encode(ta.value).length;
			et.textContent = (bayt / 1024).toFixed(1).replace(".", ",") + " KB / " +
				(AZAMI / 1024) + " KB";
			et.classList.toggle("asti", bayt > AZAMI);
		}
		ta.addEventListener("input", guncelle);
		guncelle();
	})();
</script>
