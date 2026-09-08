<?php
/**
 * WebDanışmanı — "Kaynak Geçmişi" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_gecmis.php
 */

$tok = $_SESSION["token"] ?? "";
$siteler = $wd_gecmis["siteler"] ?? [];
$seri = $wd_gecmis["seri"] ?? [];

/* Site başına saatlik seri: sparkline için. */
$site_seri = [];
foreach ($seri as $s) {
	$site_seri[$s["site"]][] = $s;
}

/* Tüm sitelerdeki en yüksek tepe — çubuklar buna göre ölçeklenir ki
   siteler birbiriyle karşılaştırılabilsin. */
$tepe = 0.0;
foreach ($seri as $s) {
	$tepe = max($tepe, (float) $s["cpu_max"]);
}
$tepe = max($tepe, 1.0);
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title">Kaynak Geçmişi</h1>
					<p class="wd-subtitle">
						Site başına CPU ve bellek kullanımının zaman içindeki seyri —
						yalnızca PHP tüketimi ölçülür.
					</p>
				</div>
			</div>

			<?php if ($wd_gecmis === null) { ?>
				<div class="wd-note wd-note-warn">
					<i class="fas fa-triangle-exclamation"></i>
					<span>Geçmiş aracı çalıştırılamadı. Sunucuda
						<span class="wd-mono">bash /usr/local/hestia/wd/src/kur.sh</span> çalıştırın.</span>
				</div>
			<?php } elseif (empty($siteler)) { ?>
				<div class="wd-note">
					<i class="fas fa-circle-info"></i>
					<span>
						Bu dönem için kayıt yok. Toplayıcı 5 dakikada bir örnek alır;
						ilk grafikler kurulumdan birkaç saat sonra anlamlı olur.
					</span>
				</div>
			<?php } else { ?>

				<div class="wd-err-tabs" role="tablist">
					<?php foreach ([1, 7, 30] as $g) { ?>
						<a class="wd-err-tab<?= $g === $wd_gun ? " aktif" : "" ?>"
							href="/list/gecmis/?gun=<?= $g ?>">
							<span class="wd-err-kod"><?= $g ?></span>
							<span class="wd-err-ad">gün</span>
						</a>
					<?php } ?>
				</div>

				<div class="wd-card">
					<div class="wd-card-head">
						Siteler
						<span class="wd-card-note"><?= (int) ($wd_gecmis["ornek"] ?? 0) ?> örnek</span>
					</div>
					<div class="wd-card-body">
						<?php foreach ($siteler as $s) {
       	$noktalar = $site_seri[$s["site"]] ?? []; ?>
							<div class="wd-usage">
								<div class="wd-usage-top">
									<span class="wd-usage-label wd-usage-site" title="<?= wd_e($s["site"]) ?>"><?= wd_e($s["site"]) ?></span>
									<span class="wd-usage-num">
										tepe %<?= wd_e(number_format($s["cpu_max"], 1, ",", ".")) ?>
									</span>
								</div>
								<div class="wd-site-metrics">
									<span class="wd-site-metric">ortalama <b>%<?= wd_e(number_format($s["cpu_ort"], 1, ",", ".")) ?></b></span>
									<span class="wd-site-metric">bellek tepe <b><?= wd_e(wd_bayt((int) $s["mem_max"] * 1024)) ?></b></span>
									<span class="wd-site-metric"><?= (int) $s["ornek"] ?> ölçüm</span>
								</div>

								<?php // Saatlik tepe değerlerden basit bir sütun grafik.
        // Ortalama değil TEPE çizilir: kısa süreli ama sunucuyu kilitleyen
        // bir yüklenmeyi ortalama gizler.
        if (!empty($noktalar)) { ?>
									<div class="wd-spark" role="img"
										aria-label="<?= wd_e($s["site"]) ?> saatlik tepe CPU kullanımı">
										<?php foreach (array_slice($noktalar, -72) as $n) {
           	$y = max(2, min(100, $n["cpu_max"] / $tepe * 100));
           	$sinif = $n["cpu_max"] >= $tepe * 0.75 ? " yuksek" : ""; ?>
											<span class="wd-spark-cubuk<?= $sinif ?>" style="height: <?= round($y, 1) ?>%"
												title="<?= wd_e(date("d.m H:i", (int) $n["t"])) ?> — tepe %<?= wd_e(number_format($n["cpu_max"], 1, ",", ".")) ?>"></span>
										<?php } ?>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
					</div>
				</div>

			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head">Nasıl Okunur?</div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">Neden tepe</span>
						<span class="wd-v-small">
							Çubuklar saatlik <b>tepe</b> değeri gösterir, ortalamayı değil.
							Beş dakika süren ama sunucuyu kilitleyen bir yüklenmeyi ortalama gizler.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Ne ölçülüyor</span>
						<span class="wd-v-small">
							Yalnızca sitenin PHP işçileri. nginx, Apache ve MariaDB paylaşımlıdır
							ve site başına ayrıştırılamaz.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Boş dönem</span>
						<span class="wd-v-small">
							<span class="wd-mono">pm = ondemand</span> olduğu için boştaki sitenin
							işçisi olmaz; sıfır "veri yok" değil "kullanım yok" demektir.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Saklama</span>
						<span class="wd-v-small">30 gün; eski kayıtlar kendiliğinden silinir.</span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>
