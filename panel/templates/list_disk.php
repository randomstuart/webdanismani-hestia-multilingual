<?php
/**
 * WebDanışmanı — "Disk Kullanımı" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_disk.php
 *
 * $panel, $user -> render_page() tarafından sağlanır
 * $wd_*         -> list/disk/index.php tarafından sağlanır
 */

$tok = $_SESSION["token"] ?? "";
$kullanicilar = $wd_disk["kullanicilar"] ?? [];

/* Kategori renkleri — yığılı çubukta ayırt etmek için. Tasarım paletinden. */
$wd_renk = [
	"Web siteleri" => "var(--wd-green)",
	"E-posta" => "#2f6fb0",
	"Yedekler" => "var(--wd-amber)",
	"Geçici dosyalar" => "#7c5cd6",
	"Diğer" => "var(--wd-sep)",
];

$wd_toplam_hepsi = 0;
foreach ($kullanicilar as $k) {
	$wd_toplam_hepsi += (int) $k["toplam"];
}
?>

<div class="container">
	<div class="wd-page">

		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title">Disk Kullanımı</h1>
					<p class="wd-subtitle">
						Yerin nereye gittiğini gösterir — alan adı, dizin ve dosya kırılımıyla.
					</p>
				</div>
				<div class="wd-page-actions">
					<a class="button button-secondary" href="/list/disk/?yenile=1&amp;token=<?= wd_e($tok) ?>">
						<i class="fas fa-rotate"></i> Yeniden Tara
					</a>
				</div>
			</div>

			<?php if (!empty($wd_yenilendi)) { ?>
				<div class="wd-note wd-note-ok">
					<i class="fas fa-circle-check"></i>
					<span>Tarama yeniden çalıştırıldı; aşağıdaki değerler az önce ölçüldü.</span>
				</div>
			<?php } ?>

			<?php if ($wd_disk === null) { ?>

				<div class="wd-note wd-note-warn">
					<i class="fas fa-triangle-exclamation"></i>
					<span>
						Disk analizi aracı henüz çalışmamış.
						Sunucuda <span class="wd-mono">bash /usr/local/hestia/wd/src/kur.sh</span> komutunu çalıştırın
						ya da yukarıdan "Yeniden Tara" deyin.
					</span>
				</div>

			<?php } elseif (empty($kullanicilar)) { ?>

				<div class="wd-note">
					<i class="fas fa-circle-info"></i>
					<span>Görüntülenecek hesap yok.</span>
				</div>

			<?php } else {
    foreach ($kullanicilar as $k) {
    	$toplam = max(1, (int) $k["toplam"]);
    	$kota_mb = $k["kota_mb"] ?? null;
    	$kota_b = $kota_mb !== null ? $kota_mb * 1048576 : null;
    	$kota_pct = $kota_b ? min(100, round($k["toplam"] / $kota_b * 100, 1)) : null; ?>

				<div class="wd-group" data-wd-open>
					<div class="wd-group-head wd-group-head-static">
						<span class="wd-group-icon"><i class="fas fa-hard-drive"></i></span>
						<span class="wd-group-title"><?= wd_e($k["user"]) ?></span>
						<?php if (!empty($k["paket"])) { ?>
							<span class="wd-group-count"><?= wd_e($k["paket"]) ?></span>
						<?php } ?>
						<span class="wd-disk-total">
							<?= wd_e(wd_bayt((int) $k["toplam"])) ?>
							<?php if ($kota_b) { ?>
								<span class="wd-v-dim">/ <?= wd_e(wd_bayt($kota_b)) ?></span>
							<?php } else { ?>
								<span class="wd-v-dim">/ sınırsız</span>
							<?php } ?>
						</span>
					</div>

					<div class="wd-disk-body">

						<?php if (!empty($k["eksik"])) { ?>
							<div class="wd-note wd-note-warn wd-note-inline">
								<i class="fas fa-triangle-exclamation"></i>
								<span>
									Bu hesabın taraması süre sınırına takıldı; aşağıdaki değerler
									<b>eksik olabilir</b>. Tam sonuç için sunucuda
									<span class="wd-mono">wd-disk refresh</span> çalıştırın.
								</span>
							</div>
						<?php } ?>

						<?php // --- Yığılı kategori çubuğu ---
      if (!empty($k["kategoriler"])) { ?>
							<div class="wd-stack" role="img"
								aria-label="Disk dağılımı: <?= wd_e(implode(", ", array_map(function ($c) {
        	return $c["etiket"] . " " . wd_bayt((int) $c["bayt"]);
        }, $k["kategoriler"]))) ?>">
								<?php foreach ($k["kategoriler"] as $c) {
         	$w = $c["bayt"] / $toplam * 100;
         	if ($w < 0.4) {
         		continue;
         	} ?>
									<span style="width: <?= round($w, 2) ?>%; background: <?= $wd_renk[$c["etiket"]] ?? "var(--wd-sep)" ?>"
										title="<?= wd_e($c["etiket"] . " — " . wd_bayt((int) $c["bayt"])) ?>"></span>
								<?php } ?>
							</div>

							<div class="wd-legend">
								<?php foreach ($k["kategoriler"] as $c) { ?>
									<span class="wd-legend-item" title="<?= wd_e($c["ipucu"] ?? "") ?>">
										<span class="wd-legend-dot" style="background: <?= $wd_renk[$c["etiket"]] ?? "var(--wd-sep)" ?>"></span>
										<?= wd_e($c["etiket"]) ?>
										<b><?= wd_e(wd_bayt((int) $c["bayt"])) ?></b>
									</span>
								<?php } ?>
							</div>
						<?php }

      if ($kota_pct !== null) { ?>
							<div class="wd-usage">
								<div class="wd-usage-top">
									<span class="wd-usage-label">Kota kullanımı</span>
									<span class="wd-usage-num">%<?= wd_e(number_format($kota_pct, 1, ",", ".")) ?></span>
								</div>
								<div class="wd-bar <?= wd_level((float) $kota_pct) ?>">
									<span style="width: <?= max(2, $kota_pct) ?>%"></span>
								</div>
							</div>
						<?php } ?>

						<?php // --- Alan adı kırılımı ---
      if (!empty($k["alanlar"])) { ?>
							<div class="wd-disk-sec">
								<div class="wd-disk-sec-head">Alan adına göre</div>
								<?php foreach ($k["alanlar"] as $d) { ?>
									<div class="wd-disk-row">
										<div class="wd-disk-row-top">
											<span class="wd-disk-name" title="<?= wd_e($d["domain"]) ?>"><?= wd_e($d["domain"]) ?></span>
											<span class="wd-disk-size"><?= wd_e(wd_bayt((int) $d["bayt"])) ?></span>
										</div>
										<div class="wd-bar wd-ok">
											<span style="width: <?= max(2, min(100, $d["bayt"] / $toplam * 100)) ?>%"></span>
										</div>
										<?php if (!empty($d["bolumler"])) { ?>
											<div class="wd-disk-parts">
												<?php foreach (array_slice($d["bolumler"], 0, 6) as $b) { ?>
													<span class="wd-disk-part">
														<?= wd_e($b["ad"]) ?> <b><?= wd_e(wd_bayt((int) $b["bayt"])) ?></b>
													</span>
												<?php } ?>
											</div>
										<?php } ?>
									</div>
								<?php } ?>
							</div>
						<?php } ?>

						<?php // --- Posta kutuları ---
      if (!empty($k["postalar"])) { ?>
							<div class="wd-disk-sec">
								<div class="wd-disk-sec-head">Posta kutuları</div>
								<?php foreach ($k["postalar"] as $m) { ?>
									<div class="wd-disk-line">
										<span class="wd-disk-name"><?= wd_e($m["domain"]) ?></span>
										<span class="wd-disk-size"><?= wd_e(wd_bayt((int) $m["bayt"])) ?></span>
									</div>
								<?php } ?>
							</div>
						<?php } ?>

						<?php // --- En büyük dosyalar ---
      if (!empty($k["dosyalar"])) { ?>
							<div class="wd-disk-sec">
								<div class="wd-disk-sec-head">
									En büyük dosyalar
									<span class="wd-card-note">yer açmak için önce buraya bakın</span>
								</div>
								<?php foreach ($k["dosyalar"] as $f) {
         	$kisa = preg_replace("#^" . preg_quote($k["home"], "#") . "#", "~", $f["yol"]); ?>
									<div class="wd-disk-line">
										<span class="wd-disk-path wd-mono" title="<?= wd_e($f["yol"]) ?>"><?= wd_e(wd_yol_kisalt($kisa)) ?></span>
										<span class="wd-disk-size"><?= wd_e(wd_bayt((int) $f["bayt"])) ?></span>
									</div>
								<?php } ?>
							</div>
						<?php } ?>

						<?php // --- En büyük dizinler ---
      if (!empty($k["dizinler"])) { ?>
							<div class="wd-disk-sec">
								<div class="wd-disk-sec-head">En büyük dizinler</div>
								<?php foreach ($k["dizinler"] as $d) { ?>
									<div class="wd-disk-line">
										<span class="wd-disk-path wd-mono" title="<?= wd_e($d["yol"]) ?>"><?= wd_e(wd_yol_kisalt($d["yol"])) ?></span>
										<span class="wd-disk-size"><?= wd_e(wd_bayt((int) $d["bayt"])) ?></span>
									</div>
								<?php } ?>
							</div>
						<?php } ?>

					</div>
				</div>

			<?php }
   } ?>

		</div>

		<!-- ================= SAĞ PANEL ================= -->
		<aside class="wd-rail">

			<?php if ($wd_disk !== null) { ?>
				<div class="wd-card">
					<div class="wd-card-head">Tarama</div>
					<div class="wd-card-body">
						<div class="wd-kv">
							<span class="wd-k">Son Tarama</span>
							<span class="wd-v">
								<?php $ts = (int) ($wd_disk["ts"] ?? 0);
        if ($ts > 0) {
        	echo wd_e(date("d.m.Y H:i", $ts));
        	echo '<span class="wd-v-dim"> · ' . wd_e(wd_human_uptime(max(0, time() - $ts))) . " önce</span>";
        } else {
        	echo "—";
        } ?>
							</span>
						</div>
						<div class="wd-kv">
							<span class="wd-k">Hesap</span>
							<span class="wd-v"><?= count($kullanicilar) ?></span>
						</div>
						<?php if ($wd_is_admin && count($kullanicilar) > 1) { ?>
							<div class="wd-kv">
								<span class="wd-k">Toplam</span>
								<span class="wd-v"><?= wd_e(wd_bayt($wd_toplam_hepsi)) ?></span>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>

			<div class="wd-card">
				<div class="wd-card-head">Yer Nasıl Açılır?</div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">logs</span>
						<span class="wd-v-small">Web günlükleri. Silinebilir; sunucu yenilerini üretir. Genelde en hızlı kazanç buradadır.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">tmp</span>
						<span class="wd-v-small">Oturum ve yükleme geçici dosyaları. Güvenle boşaltılabilir.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">backup</span>
						<span class="wd-v-small">Eski yedek arşivleri. Dışarı indirdikten sonra silinebilir.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Posta</span>
						<span class="wd-v-small">Büyük posta kutuları kotayı hızla doldurur. Eski ekleri temizleyin.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Dikkat</span>
						<span class="wd-v-small">
							<b>public_html</b> içindeki dosyalar sitenizin kendisidir — silmeden önce
							yedek alın.
						</span>
					</div>
				</div>
			</div>

		</aside>
	</div>
</div>
