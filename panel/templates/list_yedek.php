<?php
/**
 * WebDanışmanı — "Yedek Gezgini" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_yedek.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$baglanti = function (string $yol) use ($wd_yedek, $wd_domain): string {
	return "/list/yedek/?yedek=" . urlencode($wd_yedek) . "&domain=" . urlencode($wd_domain) . "&yol=" . urlencode($yol);
};
$kirinti = [];
if ($wd_yol !== "") {
	$topla = "";
	foreach (explode("/", $wd_yol) as $p) {
		$topla = $topla === "" ? $p : $topla . "/" . $p;
		$kirinti[] = [$p, $topla];
	}
}
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik("Yedek Gezgini", "Yedeğin içinde gezin; tek dosya ya da klasörü geri yükleyin. Canlı dosyalara istemediğiniz sürece dokunulmaz."); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>

			<?php if (empty($wd_yedekler)) { ?>
				<div class="wd-card"><div class="wdm-bos"><i class="fas fa-box-archive"></i>Henüz yedek yok. Yedekler sayfasından yedek oluşturabilirsiniz.</div></div>
			<?php } else { ?>
				<div class="wd-card">
					<div class="wd-card-body wd-card-body-pad">
						<form method="get" action="/list/yedek/" class="wdm-satir">
							<div class="wdm-alan">
								<label class="form-label" for="yedek">Yedek</label>
								<select class="form-select" id="yedek" name="yedek" onchange="this.form.submit()">
									<?php foreach ($wd_yedekler as $y) { ?>
										<option value="<?= wd_e($y["ad"]) ?>" <?= $y["ad"] === $wd_yedek ? "selected" : "" ?>><?= wd_e(wd_date_tr($y["tarih"]) . " " . $y["saat"]) ?> · <?= (int) $y["boyut_mb"] ?> MB<?= empty($y["dosya_var"]) ? " · dosya yok" : "" ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="wdm-alan">
								<label class="form-label" for="domain">Alan adı</label>
								<select class="form-select" id="domain" name="domain" onchange="this.form.submit()">
									<?php foreach ((array) ($wd_secili["web"] ?? []) as $w) {
										if (!isset($wd_doms[$w])) {
											continue;
										} ?>
										<option value="<?= wd_e($w) ?>" <?= $w === $wd_domain ? "selected" : "" ?>><?= wd_e($w) ?></option>
									<?php } ?>
								</select>
							</div>
							<noscript><button type="submit" class="button button-secondary">Seç</button></noscript>
						</form>
					</div>
				</div>

				<?php if ($wd_secili && empty($wd_secili["dosya_var"])) { ?>
					<?php wd_modul_not("Bu yedeğin arşiv dosyası sunucuda yok" . ($wd_mod !== "" && $wd_mod !== "zstd" && $wd_mod !== "gzip" ? " (artımlı/uzak yedek modu: " . $wd_mod . ")" : "") . ". Dosya gezgini yalnızca yerel tar yedeklerinde çalışır; tam geri yükleme için Yedekler sayfasını kullanın.", "warn"); ?>
				<?php } elseif ($wd_icerik !== null) { ?>
					<div class="wd-card">
						<div class="wd-card-head">
							<span class="wdm-kirinti">
								<a href="<?= wd_e($baglanti("")) ?>"><i class="fas fa-house"></i> <?= wd_e($wd_domain) ?></a>
								<?php foreach ($kirinti as $k) { ?><span class="ayrac">/</span><a href="<?= wd_e($baglanti($k[1])) ?>"><?= wd_e($k[0]) ?></a><?php } ?>
							</span>
							<span class="wd-card-note"><?= isset($wd_icerik["toplam"]) ? (int) $wd_icerik["toplam"] . " üye" : count((array) $wd_icerik["ogeler"]) . " öğe" ?></span>
						</div>
						<div class="wdm-agac">
							<?php if ($wd_yol !== "") {
								$ust = dirname($wd_yol); ?>
								<div class="wdm-agac-satir"><i class="fas fa-turn-up"></i><span class="wdm-agac-ad"><a href="<?= wd_e($baglanti($ust === "." ? "" : $ust)) ?>">..</a></span></div>
							<?php } ?>
							<?php if (empty($wd_icerik["ogeler"])) { ?>
								<div class="wdm-bos">Boş klasör.</div>
							<?php } ?>
							<?php foreach ((array) $wd_icerik["ogeler"] as $o) {
								$tam = $wd_yol === "" ? $o["ad"] : $wd_yol . "/" . $o["ad"]; ?>
								<div class="wdm-agac-satir">
									<i class="fas <?= !empty($o["dizin"]) ? "fa-folder" : "fa-file" ?>"></i>
									<span class="wdm-agac-ad">
										<?php if (!empty($o["dizin"])) { ?><a href="<?= wd_e($baglanti($tam)) ?>"><?= wd_e($o["ad"]) ?></a><?php } else { ?><?= wd_e($o["ad"]) ?><?php } ?>
									</span>
									<span class="wdm-agac-boyut"><?= wd_e(wd_modul_bayt((int) $o["boyut"])) ?><?= !empty($o["dizin"]) && (int) $o["adet"] > 0 ? " · " . (int) $o["adet"] . " dosya" : "" ?><?= !empty($o["tarih"]) ? " · " . wd_e($o["tarih"]) : "" ?></span>
									<?php if ($wd_yol !== "") { ?>
										<div class="wdm-oge-eylem">
											<form method="post" onsubmit="return confirm('<?= wd_e($tam) ?> yedekten private/wd-geri altına çıkarılacak (canlıya dokunulmaz). Devam?');">
												<?= wd_modul_form_gizli("geri") ?>
												<input type="hidden" name="v_yedek" value="<?= wd_e($wd_yedek) ?>"><input type="hidden" name="v_domain" value="<?= wd_e($wd_domain) ?>"><input type="hidden" name="v_yol" value="<?= wd_e($tam) ?>">
												<button type="submit" class="wd-mini-btn" title="private/wd-geri altına çıkar">Kopya al</button>
											</form>
											<form method="post" onsubmit="return confirm('DİKKAT: <?= wd_e($tam) ?> canlıdaki sürümün YERİNE yazılacak. Mevcut hâli private/wd-geri altına yedeklenir. Devam?');">
												<?= wd_modul_form_gizli("geri") ?>
												<input type="hidden" name="v_yedek" value="<?= wd_e($wd_yedek) ?>"><input type="hidden" name="v_domain" value="<?= wd_e($wd_domain) ?>"><input type="hidden" name="v_yol" value="<?= wd_e($tam) ?>"><input type="hidden" name="v_yerine" value="1">
												<button type="submit" class="wd-mini-btn wd-mini-btn-danger" title="Canlı dosyanın yerine yaz">Yerine koy</button>
											</form>
										</div>
									<?php } ?>
								</div>
							<?php } ?>
							<?php if (!empty($wd_icerik["kirpildi"])) { ?><div class="wdm-bos">Liste 2000 öğede kesildi; alt klasöre inin.</div><?php } ?>
						</div>
					</div>
				<?php } ?>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head">Yedek Durumu</div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k">Yedek sayısı</span><span class="wd-v"><?= count($wd_yedekler) ?></span></div>
					<?php if (!empty($wd_yedekler)) { ?>
						<div class="wd-kv"><span class="wd-k">Son yedek</span><span class="wd-v"><?= wd_e(wd_date_tr($wd_yedekler[0]["tarih"]) . " " . $wd_yedekler[0]["saat"]) ?></span></div>
					<?php } ?>
					<div class="wd-kv"><span class="wd-k">Uzak hedef</span><span class="wd-v-small"><?= $wd_uzak ? wd_e(strtoupper($wd_uzak["tur"]) . " · " . $wd_uzak["sunucu"]) : "Tanımlı değil — yedekler yalnızca bu sunucuda. Sunucu kaybında yedek de kaybolur; yöneticiye uzak yedek hedefi tanımlatın." ?></span></div>
					<?php if ($wd_secili) { ?>
						<div class="wd-kv"><span class="wd-k">Seçili yedek</span><span class="wd-v-small"><?= count((array) $wd_secili["web"]) ?> web · <?= count((array) $wd_secili["db"]) ?> veritabanı · <?= count((array) $wd_secili["mail"]) ?> mail</span></div>
					<?php } ?>
				</div>
			</div>
			<div class="wd-card">
				<div class="wd-card-head">Nasıl Çalışır?</div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k">Kopya al</span><span class="wd-v-small">Seçilen dosya/klasör <span class="wd-mono">private/wd-geri/&lt;zaman&gt;/</span> altına çıkarılır; canlı siteye dokunulmaz. Dosya Yöneticisi ile inceleyip taşırsınız.</span></div>
					<div class="wd-kv"><span class="wd-k">Yerine koy</span><span class="wd-v-small">Canlıdaki sürüm önce <span class="wd-mono">private/wd-geri/&lt;zaman&gt;-onceki/</span> altına alınır, sonra yedekteki sürüm yazılır. Geri almak için ikisini takas edin.</span></div>
					<div class="wd-kv"><span class="wd-k">Veritabanı</span><span class="wd-v-small">Veritabanı geri yüklemesi <a href="/list/backup/">Yedekler</a> sayfasından yapılır (tek veritabanı seçilebilir).</span></div>
					<div class="wd-kv"><span class="wd-k">İlk açılış</span><span class="wd-v-small">Bir yedeğin içeriği ilk kez açıldığında arşiv taranır ve saniyeler sürebilir; sonrası anlıktır.</span></div>
				</div>
			</div>
		</aside>
	</div>
</div>
