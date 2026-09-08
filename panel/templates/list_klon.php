<?php
/**
 * WebDanışmanı — "Klon / Staging" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_klon.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$kaynaklar = array_filter(array_keys($wd_doms), fn($d) => !in_array($d, $wd_klon_hedefler, true));
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik("Klon / Staging", "Sitenin dosya ve veritabanı kopyasını test adresine çıkarın; işiniz bitince tek tıkla yayına alın."); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>

			<?php if ($wd_sonuc !== null) { ?>
				<div class="wd-card">
					<div class="wd-card-head"><span style="color:var(--wd-green)"><i class="fas fa-circle-check"></i> Staging oluşturuldu</span></div>
					<dl class="wdm-kv">
						<dt>Adres</dt><dd><a href="https://<?= wd_e($wd_sonuc["hedef"]) ?>/" target="_blank" rel="noopener"><?= wd_e($wd_sonuc["hedef"]) ?></a> <?= !empty($wd_sonuc["ssl"]) ? '<span class="wdm-rozet wdm-rozet-ok">SSL</span>' : '<span class="wdm-rozet wdm-rozet-warn">SSL alınamadı (DNS henüz çözmüyor olabilir)</span>' ?></dd>
						<?php if (!empty($wd_sonuc["db"])) { ?>
							<dt>Veritabanı</dt><dd><span class="wdm-mono"><?= wd_e($wd_sonuc["db"]) ?></span> · kullanıcı <span class="wdm-mono"><?= wd_e($wd_sonuc["db_kullanici"]) ?></span> · şifre <span class="wdm-mono"><?= wd_e($wd_sonuc["db_sifre"]) ?></span><br><span class="wdm-ipucu"><?= !empty($wd_sonuc["wp"]) ? "WordPress yapılandırması otomatik güncellendi." : "Uygulamanızın yapılandırma dosyasına bu bilgileri elle girin." ?> Şifre bir kez gösterilir.</span></dd>
						<?php } ?>
					</dl>
				</div>
			<?php } ?>

			<?php if (!empty($wd_klonlar)) { ?>
				<div class="wd-card">
					<div class="wd-card-head">Staging siteleri</div>
					<div class="wdm-liste">
						<?php foreach ($wd_klonlar as $k) { ?>
							<div class="wdm-oge">
								<div class="wdm-oge-bas">
									<span class="wdm-oge-ad"><a href="https://<?= wd_e($k["hedef"]) ?>/" target="_blank" rel="noopener"><?= wd_e($k["hedef"]) ?></a> <span class="wdm-rozet wdm-rozet-info">staging</span></span>
									<span class="wdm-oge-alt">Kaynak: <b><?= wd_e($k["kaynak"]) ?></b> · <?= wd_e(wd_modul_tarih($k["ts"] ?? null)) ?><?= !empty($k["wp"]) ? " · WordPress" : "" ?><?= !empty($k["db_hedef"]) ? " · DB: " . wd_e($k["db_hedef"]) : "" ?><?= !empty($k["son_yayin"]) ? " · son yayın " . wd_e(wd_modul_tarih((int) $k["son_yayin"])) : "" ?></span>
								</div>
								<div class="wdm-oge-eylem">
									<form method="post" onsubmit="return confirm('<?= wd_e($k["hedef"]) ?> üzerindeki dosya ve veritabanı <?= wd_e($k["kaynak"]) ?> CANLI sitesine aktarılacak. Canlı sitenin şu anki hâli private/ altına yedeklenir. Devam?');">
										<?= wd_modul_form_gizli("yayinla") ?><input type="hidden" name="v_hedef" value="<?= wd_e($k["hedef"]) ?>">
										<button type="submit" class="wd-mini-btn" title="Staging → canlı"><i class="fas fa-rocket"></i> Yayına al</button>
									</form>
									<form method="post" onsubmit="return confirm('<?= wd_e($k["hedef"]) ?> alan adı, dosyaları ve staging veritabanı silinecek. Canlı site etkilenmez. Devam?');">
										<?= wd_modul_form_gizli("sil") ?><input type="hidden" name="v_hedef" value="<?= wd_e($k["hedef"]) ?>">
										<button type="submit" class="wd-mini-btn wd-mini-btn-danger">Sil</button>
									</form>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>

			<?php if (empty($kaynaklar)) { ?>
				<?php wd_modul_not("Klonlanacak bir alan adınız yok."); ?>
			<?php } else { ?>
				<form method="post" class="wdm-form" onsubmit="return confirm('Kopyalama site boyutuna göre birkaç dakika sürebilir. Devam?');">
					<?= wd_modul_form_gizli("olustur") ?>
					<div class="wd-card">
						<div class="wd-card-head">Yeni staging</div>
						<div class="wd-card-body wd-card-body-pad">
							<div class="wdm-satir">
								<div class="wdm-alan">
									<label class="form-label" for="v_kaynak">Kaynak site</label>
									<select class="form-select" id="v_kaynak" name="v_kaynak">
										<?php foreach ($kaynaklar as $d) { ?><option value="<?= wd_e($d) ?>"><?= wd_e($d) ?></option><?php } ?>
									</select>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_alt">Alt alan adı</label>
									<input class="form-control wdm-mono" id="v_alt" name="v_alt" value="staging" pattern="[a-z0-9-]{1,40}">
									<span class="wdm-ipucu">Hedef: <span class="wdm-mono"><span id="wdKlonOnizle">staging.…</span></span> — alan adınızın DNS'i bu panelde ise A kaydı otomatik eklenir.</span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_db">Veritabanı</label>
									<select class="form-select" id="v_db" name="v_db">
										<option value="auto">Otomatik (WordPress ise kopyala)</option>
										<option value="">Kopyalama (yalnız dosyalar)</option>
										<?php foreach ($wd_dbler as $db) { ?><option value="<?= wd_e($db) ?>"><?= wd_e($db) ?> kopyala</option><?php } ?>
									</select>
								</div>
							</div>
							<div class="wdm-alan wdm-alan-genis" style="margin-top:8px">
								<label class="form-label" for="v_hedef">Farklı bir hedef alan adı <span class="wdm-ipucu">isteğe bağlı</span></label>
								<input class="form-control wdm-mono" id="v_hedef" name="v_hedef" placeholder="test.baskadomain.com (boş = yukarıdaki alt alan adı)">
							</div>
						</div>
					</div>
					<div class="wdm-dugmeler"><button type="submit" class="button"><i class="fas fa-clone"></i> Staging Oluştur</button></div>
				</form>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head">Nasıl Çalışır?</div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k">Oluşturma</span><span class="wd-v-small">Yeni alan adı aynı PHP sürümüyle açılır, dosyalar kopyalanır. WordPress'te yeni veritabanı açılır, içerik aktarılır ve tüm adresler staging adresine çevrilir. Staging arama motorlarına kapalıdır.</span></div>
					<div class="wd-kv"><span class="wd-k">Yayına alma</span><span class="wd-v-small">Önce canlı sitenin dosyaları ve veritabanı <span class="wd-mono">private/wd-klon-yedek-*</span> olarak yedeklenir. Sonra staging dosyaları canlıya kopyalanır (wp-config.php hariç), staging veritabanı canlıya aktarılır, adresler geri çevrilir.</span></div>
					<div class="wd-kv"><span class="wd-k">Dikkat</span><span class="wd-v-small">Yayına alma sırasında canlıda o sırada eklenen sipariş/yorum gibi veriler staging'deki veriyle DEĞİŞTİRİLİR. Mağaza sitelerinde yalnız tema/eklenti değişikliği için "Kopyalama (yalnız dosyalar)" ile dosya bazlı çalışın.</span></div>
					<div class="wd-kv"><span class="wd-k">WordPress dışı</span><span class="wd-v-small">Veritabanı kopyalanır ama uygulama yapılandırması elle güncellenmelidir; bilgiler oluşturma sonrası bir kez gösterilir.</span></div>
				</div>
			</div>
		</aside>
	</div>
</div>

<script>
	(function () {
		var k = document.getElementById("v_kaynak"), a = document.getElementById("v_alt"), o = document.getElementById("wdKlonOnizle");
		function g() { if (k && a && o) { o.textContent = (a.value || "staging") + "." + k.value; } }
		if (k) { k.addEventListener("change", g); }
		if (a) { a.addEventListener("input", g); }
		g();
	})();
</script>
