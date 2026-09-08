<?php
/**
 * WebDanışmanı — "Yönlendirmeler" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_yonlendirme.php
 */

$tok = $_SESSION["token"] ?? "";
$k = $wd_kurallar;
$basliklar = [
	"nosniff" => ["X-Content-Type-Options", "Tarayıcının dosya türünü tahmin etmesini engeller."],
	"frame" => ["X-Frame-Options", "Siteyi başka bir sayfanın çerçevesine gömmeyi engeller (clickjacking)."],
	"referrer" => ["Referrer-Policy", "Dış sitelere tam adres bilgisi sızmasını azaltır."],
	"hsts" => ["Strict-Transport-Security", "Tarayıcı bir yıl boyunca yalnızca HTTPS kullanır. SSL kesin çalışıyorsa açın."],
];
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title">Yönlendirmeler ve Site Kuralları</h1>
					<p class="wd-subtitle">
						Yol yönlendirmeleri, güvenlik başlıkları, hotlink koruması ve IP engelleme.
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
					<span>Henüz web alan adınız yok.</span></div>
			<?php } else { ?>

				<?php if (count($wd_doms) > 1) { ?>
					<div class="wd-card">
						<div class="wd-card-head">Alan Adı</div>
						<div class="wd-card-body wd-card-body-pad">
							<form method="get" class="wd-inline-select">
								<select class="form-select" name="domain" onchange="this.form.submit()">
									<?php foreach ($wd_doms as $d => $_) { ?>
										<option value="<?= wd_e($d) ?>" <?= $d === $wd_domain ? "selected" : "" ?>><?= wd_e($d) ?></option>
									<?php } ?>
								</select>
								<noscript><button type="submit" class="button button-secondary">Seç</button></noscript>
							</form>
						</div>
					</div>
				<?php } ?>

				<form method="post">
					<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
					<input type="hidden" name="ok" value="1">
					<input type="hidden" name="islem" value="kaydet">
					<input type="hidden" name="v_domain" value="<?= wd_e($wd_domain) ?>">

					<!-- Yol yönlendirmeleri -->
					<div class="wd-card">
						<div class="wd-card-head">
							Yol Yönlendirmeleri
							<span class="wd-card-note">301 kalıcı · 302 geçici</span>
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
											pattern="/[^\s\x22'{}]*" title="/ ile başlamalı">
										<span class="wd-yon-ok">→</span>
										<input class="form-control" type="text" name="v_hedef[]"
											value="<?= wd_e($r["hedef"]) ?>" placeholder="/yeni-sayfa ya da https://...">
										<select class="form-select wd-yon-kod" name="v_kod[]">
											<option value="301" <?= (string) $r["kod"] === "301" ? "selected" : "" ?>>301</option>
											<option value="302" <?= (string) $r["kod"] === "302" ? "selected" : "" ?>>302</option>
										</select>
										<label class="wd-yon-tam" title="İşaretliyse yalnızca tam bu adres; değilse alt yollar da taşınır">
											<input type="checkbox" name="v_tam[]" value="1" <?= !empty($r["tam"]) ? "checked" : "" ?>>
											tam
										</label>
										<button type="button" class="wd-mini-btn wd-mini-btn-danger" onclick="wdSatirSil(this)">×</button>
									</div>
								<?php } ?>
							</div>
							<button type="button" class="wd-mini-btn" onclick="wdSatirEkle()">+ Satır Ekle</button>
							<p class="wd-aciklama wd-aciklama-kucuk">
								"tam" işaretli değilse <span class="wd-mono">/blog</span> kuralı
								<span class="wd-mono">/blog/yazi-1</span> adresini de taşır.
							</p>
						</div>
					</div>

					<!-- Güvenlik başlıkları -->
					<div class="wd-card">
						<div class="wd-card-head">Güvenlik Başlıkları</div>
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
						<div class="wd-card-head">Erişim Kuralları</div>
						<div class="wd-card-body wd-card-body-pad">
							<label class="wd-secim">
								<input type="checkbox" name="v_hotlink" value="1" <?= !empty($k["hotlink"]) ? "checked" : "" ?>>
								<span>
									<b>Hotlink koruması</b>
									<span class="wd-v-small">
										Başka siteler resimlerinizi kendi sayfalarında gösteremez —
										bant genişliğinizi harcamalarını önler.
									</span>
								</span>
							</label>
							<div class="wd-auth-field wd-auth-field-genis">
								<label class="form-label" for="v_hotlink_izinli">Hotlink'e izin verilen alan adları</label>
								<input class="form-control" type="text" id="v_hotlink_izinli" name="v_hotlink_izinli"
									value="<?= wd_e(implode(" ", $k["hotlink_izinli"])) ?>"
									placeholder="cdn.siteniz.com ortak.com">
							</div>
							<div class="wd-auth-field wd-auth-field-genis">
								<label class="form-label" for="v_ip">Engellenecek IP adresleri</label>
								<textarea class="form-control" id="v_ip" name="v_ip" rows="3"
									placeholder="203.0.113.7&#10;198.51.100.0/24"><?= wd_e(implode("\n", $k["engelli_ip"])) ?></textarea>
							</div>
						</div>
					</div>

					<div class="wd-err-actions">
						<button type="submit" class="button">Kaydet ve Yayına Al</button>
						<button type="submit" name="islem" value="sil" class="button button-secondary"
							onclick="return confirm('Bu alan adının TÜM özel kuralları kaldırılacak. Devam?');">
							Tüm Kuralları Kaldır
						</button>
					</div>
				</form>

			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head">Nasıl Çalışır?</div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">Güvenli mi</span>
						<span class="wd-v-small">
							Kurallar yazıldıktan sonra <b>nginx doğrulaması</b> yapılır. Geçersizse
							eski hâl geri yüklenir — bozuk bir kural sitenizi düşüremez.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">301 mi 302 mi</span>
						<span class="wd-v-small">
							Kalıcı taşıma için <b>301</b> (arama motoru sıralamasını aktarır).
							Geçici kampanya için <b>302</b>.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">HSTS dikkat</span>
						<span class="wd-v-small">
							Açtıktan sonra tarayıcı bir yıl boyunca HTTPS zorlar. SSL'iniz
							kesin çalışmıyorsa <b>açmayın</b>; geri almak zordur.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Nerede saklanır</span>
						<span class="wd-v-small wd-mono">conf/web/&lt;alan&gt;/nginx.conf_wd</span>
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
		// Son satır silinmez; form tamamen boş kalırsa yeni kural eklenemezdi.
		if (liste.querySelectorAll(".wd-yon-satir").length > 1) {
			btn.closest(".wd-yon-satir").remove();
		} else {
			btn.closest(".wd-yon-satir").querySelectorAll("input").forEach(function (i) {
				if (i.type === "checkbox") { i.checked = false; } else { i.value = ""; }
			});
		}
	}
</script>
