<?php
/**
 * WebDanışmanı — "Erişim İzleme" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_erisim.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$down = 0;
$yavas = 0;
foreach ($wd_siteler as $s) {
	if ($s["durum"] === "down") {
		$down++;
	} elseif ($s["durum"] === "yavas") {
		$yavas++;
	}
}
$rozet = function (string $durum): string {
	switch ($durum) {
		case "down":
			return '<span class="wdm-rozet wdm-rozet-err"><span class="wdm-nokta wdm-nokta-err"></span>Erişilemiyor</span>';
		case "yavas":
			return '<span class="wdm-rozet wdm-rozet-warn"><span class="wdm-nokta wdm-nokta-warn"></span>Yavaş</span>';
		default:
			return '<span class="wdm-rozet wdm-rozet-ok"><span class="wdm-nokta wdm-nokta-ok"></span>Çalışıyor</span>';
	}
};
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik(
				"Erişim İzleme",
				"Siteleriniz 5 dakikada bir dışarıdan denetlenir; çökünce panel bildirimi alırsınız.",
			); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>

			<div class="wd-stats">
				<div class="wd-stat">
					<div class="wd-stat-label">İZLENEN SİTE</div>
					<div class="wd-stat-value"><?= count($wd_siteler) ?></div>
				</div>
				<div class="wd-stat">
					<div class="wd-stat-label">ERİŞİLEMEYEN</div>
					<div class="wd-stat-value" style="<?= $down > 0 ? "color:var(--wd-red)" : "" ?>"><?= $down ?></div>
				</div>
				<div class="wd-stat">
					<div class="wd-stat-label">YAVAŞ</div>
					<div class="wd-stat-value" style="<?= $yavas > 0 ? "color:var(--wd-amber)" : "" ?>"><?= $yavas ?></div>
				</div>
				<div class="wd-stat">
					<div class="wd-stat-label">SON KONTROL</div>
					<div class="wd-stat-value"><?= $wd_yas === null ? "—" : wd_e(wd_modul_sure($wd_yas)) ?><span class="wd-stat-of">önce</span></div>
				</div>
			</div>

			<?php if (empty($wd_siteler)) { ?>
				<div class="wd-card"><div class="wdm-bos"><i class="fas fa-heart-pulse"></i>
					<?= $wd_veri === null ? "Henüz kontrol yapılmadı; ilk sonuç birkaç dakika içinde gelir." : "İzlenen site yok." ?>
				</div></div>
			<?php } else { ?>
				<?php foreach ($wd_siteler as $s) {
					$ds = $s["down_since"] ?? null; ?>
					<div class="wd-card">
						<div class="wd-card-head">
							<span style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
								<?= $rozet($s["durum"]) ?>
								<a href="https://<?= wd_e($s["domain"]) ?>/" target="_blank" rel="noopener" style="font-weight:500"><?= wd_e($s["domain"]) ?></a>
								<?php if ($wd_is_admin) { ?><span class="wd-card-note"><?= wd_e($s["user"]) ?></span><?php } ?>
							</span>
							<form method="post" style="margin:0">
								<?= wd_modul_form_gizli("simdi", $s["domain"]) ?>
								<button type="submit" class="wd-mini-btn" title="Şimdi dışarıdan dene">Şimdi Dene</button>
							</form>
						</div>
						<div class="wd-card-body wd-card-body-pad">
							<div class="wdm-oge-orta" style="padding:0 0 8px">
								<span class="wdm-metrik"><b><?= wd_e(wd_modul_yuzde($s["uptime_24s"] ?? null)) ?></b><span>24 saat</span></span>
								<span class="wdm-metrik"><b><?= wd_e(wd_modul_yuzde($s["uptime_7g"] ?? null)) ?></b><span>7 gün</span></span>
								<span class="wdm-metrik"><b><?= wd_e(wd_modul_yuzde($s["uptime_30g"] ?? null)) ?></b><span>30 gün</span></span>
								<span class="wdm-metrik"><b><?= isset($s["ms"]) ? (int) $s["ms"] . " ms" : "—" ?></b><span>son yanıt</span></span>
								<?php if (!empty($s["ortalama_ms_24s"])) { ?>
									<span class="wdm-metrik"><b><?= (int) $s["ortalama_ms_24s"] ?> ms</b><span>24 sa ort.</span></span>
								<?php } ?>
								<span class="wdm-eskime">son kontrol <?= wd_e(wd_modul_tarih($s["son_kontrol"] ?? null)) ?></span>
							</div>
							<?php if ($s["durum"] === "down" && $ds) { ?>
								<?php wd_modul_not(
									"Site " . wd_modul_tarih((int) $ds) . " tarihinden beri erişilemiyor (" . wd_modul_sure(time() - (int) $ds) . ").",
									"err",
								); ?>
							<?php } ?>
							<div class="wdm-uptime" title="Son 8 saat, 5 dakikalık aralıklarla (soldan sağa: eskiden yeniye)">
								<?php foreach ((array) ($s["gecmis"] ?? []) as $g) { ?>
									<span class="<?= $g === 0 ? "down" : ($g === 2 ? "yavas" : "up") ?>"></span>
								<?php } ?>
							</div>
							<?php if (!empty($s["kesinti"])) { ?>
								<details style="margin-top:10px">
									<summary style="cursor:pointer;font-size:11.5px;color:var(--wd-muted)">Son kesintiler (<?= count($s["kesinti"]) ?>)</summary>
									<div class="wdm-tablo-sar" style="margin-top:6px">
										<table class="wdm-tablo">
											<thead><tr><th>Başlangıç</th><th>Bitiş</th><th class="sag">Süre</th></tr></thead>
											<tbody>
												<?php foreach ($s["kesinti"] as $k) { ?>
													<tr>
														<td><?= wd_e(wd_modul_tarih((int) $k["bas"])) ?></td>
														<td><?= wd_e(wd_modul_tarih((int) $k["bit"])) ?></td>
														<td class="sag"><?= wd_e(wd_modul_sure((int) $k["sure"])) ?></td>
													</tr>
												<?php } ?>
											</tbody>
										</table>
									</div>
								</details>
							<?php } ?>
						</div>
					</div>
				<?php } ?>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head">Nasıl Çalışır?</div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">Ne sayılır</span>
						<span class="wd-v-small">HTTP 5xx, bağlantı hatası, zaman aşımı ve SSL hatası <b>erişilemiyor</b> sayılır. 401/403/404 gibi yanıtlar sunucunun yanıt verdiğini gösterir ve <b>çalışıyor</b> kabul edilir.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Bildirim</span>
						<span class="wd-v-small">İki ardışık başarısız kontrolden (10 dk) sonra panel bildirimi gider; site düzelince ikinci bir bildirimle kesinti süresi yazılır. Tek seferlik dalgalanmalar alarm üretmez.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Yavaş</span>
						<span class="wd-v-small">Yanıt <?= (int) (($wd_veri["yavas_ms"] ?? 3000) / 1000) ?> saniyeyi aşınca sarı gösterilir; bildirim gitmez.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Sınır</span>
						<span class="wd-v-small">Kontrol bu sunucudan yapılır; sunucunun kendisi çökerse bu sayfa da çalışmaz. Sunucu dışı izleme için ayrı bir servis gerekir.</span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>
