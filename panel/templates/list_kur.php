<?php
/**
 * WebDanışmanı — "Uygulama Kurucu" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_kur.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik("Uygulama Kurucu", "Hazır yazılımları seçtiğiniz alan adına tek tıkla kurun; veritabanı ve yapılandırma otomatik hazırlanır."); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>

			<?php if ($wd_sonuc !== null) { ?>
				<div class="wd-card">
					<div class="wd-card-head"><span style="color:var(--wd-green)"><i class="fas fa-circle-check"></i> Dosyalar yerleştirildi</span> <span class="wd-card-note"><?= wd_e($wd_sonuc["ad"] ?? "") ?> <?= wd_e($wd_sonuc["surum"] ?? "") ?></span></div>
					<div class="wd-card-body">
						<dl class="wdm-kv">
							<dt>Adres</dt><dd><a href="<?= wd_e($wd_sonuc["site_url"] ?? "") ?>" target="_blank" rel="noopener"><?= wd_e($wd_sonuc["site_url"] ?? "") ?></a></dd>
							<dt>Dosya</dt><dd><?= (int) ($wd_sonuc["dosya"] ?? 0) ?> dosya, hedef <span class="wdm-mono"><?= wd_e($wd_sonuc["hedef"] ?? "/") ?></span></dd>
							<?php if (!empty($wd_sonuc["db"])) { ?>
								<dt>Veritabanı</dt><dd><span class="wdm-kopya"><code><?= wd_e($wd_sonuc["db"]["ad"]) ?></code></span></dd>
								<dt>DB kullanıcısı</dt><dd><span class="wdm-kopya"><code><?= wd_e($wd_sonuc["db"]["kullanici"]) ?></code></span></dd>
								<dt>DB şifresi</dt><dd><span class="wdm-kopya"><code><?= wd_e($wd_sonuc["db"]["sifre"]) ?></code></span> <span class="wdm-ipucu">bir kez gösterilir — kaydedin</span></dd>
								<dt>DB sunucusu</dt><dd><span class="wdm-mono">localhost</span></dd>
							<?php } ?>
							<?php if (!empty($wd_sonuc["lisans"])) { ?><dt>Lisans</dt><dd>license.key yazıldı</dd><?php } ?>
						</dl>
						<div class="wdm-dugmeler" style="padding:0 16px 14px">
							<a class="button" href="<?= wd_e($wd_sonuc["kurulum_url"] ?? "#") ?>" target="_blank" rel="noopener">Kurulumu Tamamla <i class="fas fa-arrow-up-right-from-square"></i></a>
							<span class="wdm-ipucu">Yazılımın kurulum sihirbazı açılır; veritabanı bilgilerini oradan girin.</span>
						</div>
					</div>
				</div>
			<?php } ?>

			<?php if (empty($wd_doms)) { ?>
				<?php wd_modul_not("Henüz web alan adınız yok. Önce bir alan adı ekleyin."); ?>
			<?php } else { ?>
				<?php wd_modul_domain_kutusu($wd_doms, $wd_domain, "/list/kur/"); ?>

				<?php if (!empty($wd_kurulu)) { ?>
					<div class="wd-card">
						<div class="wd-card-head">Kurulu uygulamalar <span class="wd-card-note"><?= wd_e($wd_domain) ?></span></div>
						<div class="wdm-liste">
							<?php foreach ($wd_kurulu as $u) { ?>
								<div class="wdm-oge">
									<div class="wdm-oge-bas">
										<span class="wdm-oge-ad"><?= wd_e($u["ad"] ?? $u["kod"]) ?> <?= !empty($u["surum"]) ? '<span class="wd-card-note">' . wd_e($u["surum"]) . "</span>" : "" ?></span>
										<span class="wdm-oge-alt"><span class="wdm-mono"><?= wd_e($u["yol"] ?? "/") ?></span><?= !empty($u["ts"]) ? " · " . wd_e(wd_modul_tarih((int) $u["ts"])) : "" ?><?= !empty($u["db"]) ? " · DB: " . wd_e($u["db"]) : "" ?></span>
									</div>
									<div class="wdm-oge-eylem">
										<a class="wd-mini-btn" href="https://<?= wd_e($wd_domain) ?><?= wd_e(rtrim($u["yol"] ?? "/", "/")) ?>/" target="_blank" rel="noopener">Aç</a>
										<?php if (($u["kod"] ?? "") === "WORDPRESS") { ?><a class="wd-mini-btn" href="/list/wp/?domain=<?= urlencode($wd_domain) ?>">WP Araçları</a><?php } ?>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				<?php } ?>

				<form method="post" class="wdm-form" id="wdKurForm">
					<?= wd_modul_form_gizli("kur", $wd_domain) ?>
					<div class="wd-card">
						<div class="wd-card-head">Uygulama seçin <span class="wd-card-note"><?= count($wd_katalog) ?> uygulama</span></div>
						<?php if (empty($wd_katalog)) { ?>
							<div class="wdm-bos"><i class="fas fa-box-open"></i>Katalog boş.<?= $wd_is_admin ? " Sağdaki kutudan uygulama ekleyin." : " Yöneticiniz henüz uygulama eklememiş." ?></div>
						<?php } else { ?>
							<div class="wdm-kartlar">
								<?php foreach ($wd_katalog as $i => $a) { ?>
									<label class="wdm-kart<?= $i === 0 ? " secili" : "" ?>">
										<input type="radio" name="v_kod" value="<?= wd_e($a["kod"]) ?>" <?= $i === 0 ? "checked" : "" ?> data-kabuk="<?= !empty($a["kabuk"]) ? "1" : "0" ?>" <?= empty($a["paket_var"]) ? "disabled" : "" ?>>
										<b><i class="fas <?= wd_e($a["ikon"] ?? "fa-cube") ?>"></i> <?= wd_e($a["ad"]) ?></b>
										<span><?= wd_e($a["aciklama"] ?: "—") ?></span>
										<small>v<?= wd_e($a["surum"]) ?> · PHP <?= wd_e($a["php_min"]) ?>+<?= !empty($a["db"]) ? " · veritabanı" : "" ?><?= !empty($a["kabuk"]) ? " · lisanslı" : "" ?><?= empty($a["paket_var"]) ? " · paket yok" : "" ?></small>
									</label>
								<?php } ?>
							</div>
							<div class="wd-card-body wd-card-body-pad" style="border-top:1px solid var(--wd-line)">
								<div class="wdm-satir">
									<div class="wdm-alan">
										<label class="form-label" for="v_alt">Alt dizin <span class="wdm-ipucu">boş = site kökü</span></label>
										<input class="form-control wdm-mono" id="v_alt" name="v_alt" placeholder="örn. panel" pattern="[A-Za-z0-9._-]{1,60}">
										<span class="wdm-ipucu"><?= $wd_bos ? "Site kökü boş; doğrudan köke kurabilirsiniz." : "Site kökünde dosya var; köke kurmak için önce boşaltın ya da alt dizin verin." ?></span>
									</div>
									<div class="wdm-alan" id="wdLisansAlan">
										<label class="form-label" for="v_lisans">Lisans anahtarı</label>
										<input class="form-control wdm-mono" id="v_lisans" name="v_lisans" placeholder="XXXX-XXXX-XXXX-XXXX-XXXX" autocomplete="off">
										<span class="wdm-ipucu">Lisanslı yazılımlarda zorunlu; yazılımın satıcısından aldığınız anahtarı girin.</span>
									</div>
								</div>
							</div>
						<?php } ?>
					</div>
					<?php if (!empty($wd_katalog)) { ?>
						<div class="wdm-dugmeler">
							<button type="submit" class="button" onclick="return confirm('Seçilen uygulama <?= wd_e($wd_domain) ?> alan adına kurulacak. Devam?');">Kur</button>
							<?php if ($wd_quick) { ?>
								<a class="button button-secondary" href="/add/webapp/?domain=<?= urlencode($wd_domain) ?>&token=<?= wd_e($tok) ?>">WordPress / diğerleri (Hızlı Kurulum)</a>
							<?php } ?>
						</div>
					<?php } ?>
				</form>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<?php if ($wd_is_admin) { ?>
				<div class="wd-card">
					<div class="wd-card-head">Kataloğa Ekle <span class="wd-card-note">yalnız yönetici</span></div>
					<div class="wd-card-body wd-card-body-pad">
						<form method="post" enctype="multipart/form-data" class="wdm-form">
							<?= wd_modul_form_gizli("katalog-ekle") ?>
							<div class="wdm-alan"><label class="form-label" for="v_kod">Kod</label><input class="form-control wdm-mono" id="v_kod" name="v_kod" placeholder="BLOG" pattern="[A-Za-z0-9_-]{2,31}" required></div>
							<div class="wdm-alan"><label class="form-label" for="v_ad">Ad</label><input class="form-control" id="v_ad" name="v_ad" placeholder="Blog Yazılımı" required></div>
							<div class="wdm-satir">
								<div class="wdm-alan"><label class="form-label" for="v_surum">Sürüm</label><input class="form-control wdm-mono" id="v_surum" name="v_surum" value="1.0"></div>
								<div class="wdm-alan"><label class="form-label" for="v_php_min">En düşük PHP</label><input class="form-control wdm-mono" id="v_php_min" name="v_php_min" value="8.1"></div>
							</div>
							<div class="wdm-alan"><label class="form-label" for="v_aciklama">Açıklama</label><input class="form-control" id="v_aciklama" name="v_aciklama" maxlength="300"></div>
							<div class="wdm-alan"><label class="form-label" for="v_zip">Paket (.zip)</label><input class="form-control" type="file" id="v_zip" name="v_zip" accept=".zip"><span class="wdm-ipucu">Var olan koda yeniden yüklemek paketi günceller; boş bırakılırsa eski paket kalır.</span></div>
							<div class="wdm-satir">
								<div class="wdm-alan"><label class="form-label" for="v_zip_kok">Zip içi kök</label><input class="form-control wdm-mono" id="v_zip_kok" name="v_zip_kok" placeholder="blog (isteğe bağlı)"></div>
								<div class="wdm-alan"><label class="form-label" for="v_kurulum_yolu">Kurulum yolu</label><input class="form-control wdm-mono" id="v_kurulum_yolu" name="v_kurulum_yolu" value="/install.php"></div>
							</div>
							<label class="wd-secim"><input type="checkbox" name="v_db" value="1" checked><span><b>Veritabanı açılsın</b></span></label>
							<label class="wd-secim"><input type="checkbox" name="v_kabuk" value="1"><span><b>Lisanslı yazılım</b><span class="wd-v-small">Kurulumda license.key zorunlu olur.</span></span></label>
							<div class="wdm-alan"><label class="form-label" for="v_cfg_dosya">Yapılandırma dosyası <span class="wdm-ipucu">isteğe bağlı</span></label><input class="form-control wdm-mono" id="v_cfg_dosya" name="v_cfg_dosya" placeholder="config/config.php"></div>
							<div class="wdm-alan"><label class="form-label" for="v_cfg_sablon">Yapılandırma şablonu</label><textarea class="form-control wdm-mono" id="v_cfg_sablon" name="v_cfg_sablon" rows="5" placeholder="<?= wd_e("<?php\nreturn ['db'=>['host'=>'{{DB_SUNUCU}}','name'=>'{{DB_AD}}','user'=>'{{DB_KULLANICI}}','pass'=>'{{DB_SIFRE}}'],'url'=>'{{SITE_URL}}'];") ?>"></textarea><span class="wdm-ipucu">Yer tutucular: {{DB_AD}} {{DB_KULLANICI}} {{DB_SIFRE}} {{DB_SUNUCU}} {{SITE_URL}} {{ALAN_ADI}} {{LISANS}}</span></div>
							<div class="wdm-dugmeler"><button type="submit" class="button">Kataloğa Ekle</button></div>
						</form>
					</div>
				</div>
				<?php if (!empty($wd_katalog)) { ?>
					<div class="wd-card">
						<div class="wd-card-head">Katalog</div>
						<div class="wdm-liste">
							<?php foreach ($wd_katalog as $a) { ?>
								<div class="wdm-oge">
									<div class="wdm-oge-bas"><span class="wdm-oge-ad"><?= wd_e($a["ad"]) ?></span><span class="wdm-oge-alt"><span class="wdm-mono"><?= wd_e($a["kod"]) ?></span> · v<?= wd_e($a["surum"]) ?> · <?= !empty($a["paket_var"]) ? wd_e(wd_modul_bayt((int) $a["paket_boyut"])) : "paket yok" ?></span></div>
									<div class="wdm-oge-eylem">
										<form method="post" onsubmit="return confirm('<?= wd_e($a["kod"]) ?> katalogdan silinecek (kurulu siteler etkilenmez). Devam?');">
											<?= wd_modul_form_gizli("katalog-sil") ?><input type="hidden" name="v_kod" value="<?= wd_e($a["kod"]) ?>">
											<button type="submit" class="wd-mini-btn wd-mini-btn-danger">Sil</button>
										</form>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				<?php } ?>
			<?php } ?>
			<div class="wd-card">
				<div class="wd-card-head">Nasıl Çalışır?</div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k">Ne yapılır</span><span class="wd-v-small">Paket alan adınıza açılır, gerekiyorsa veritabanı ve kullanıcı açılır, lisans dosyası yazılır. Ardından yazılımın kendi kurulum sihirbazı tamamlar.</span></div>
					<div class="wd-kv"><span class="wd-k">Hedef boş olmalı</span><span class="wd-v-small">Mevcut dosyaların üstüne yazılmaz. Köke kurmak için kökü boşaltın ya da bir alt dizin verin.</span></div>
					<div class="wd-kv"><span class="wd-k">Şifre</span><span class="wd-v-small">Veritabanı şifresi yalnızca kurulum sonrası ekranda bir kez gösterilir; Veritabanları sayfasından yenisini verebilirsiniz.</span></div>
				</div>
			</div>
		</aside>
	</div>
</div>

<script>
	(function () {
		var kartlar = document.querySelectorAll(".wdm-kart");
		var lisans = document.getElementById("wdLisansAlan");
		function guncelle() {
			kartlar.forEach(function (k) {
				var r = k.querySelector("input[type=radio]");
				k.classList.toggle("secili", !!(r && r.checked));
				if (r && r.checked && lisans) { lisans.style.opacity = r.dataset.kabuk === "1" ? "1" : "0.55"; }
			});
		}
		kartlar.forEach(function (k) { k.addEventListener("change", guncelle); });
		guncelle();
	})();
</script>
