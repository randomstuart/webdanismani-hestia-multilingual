<?php
/**
 * WebDanışmanı — "Mail Raporu" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_mailrapor.php
 */

$tok = $_SESSION["token"] ?? "";
$t = $wd_rapor["toplam"] ?? ["kabul" => 0, "teslim" => 0, "sekme" => 0, "ertelenen" => 0];
$kuyruk = $wd_rapor["kuyruk"] ?? ["bekleyen" => 0, "donmus" => 0];
$sekme_orani = $t["kabul"] > 0 ? round($t["sekme"] / $t["kabul"] * 100, 1) : 0.0;
?>

<div class="container">
	<div class="wd-page">

		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title">Mail Raporu</h1>
					<p class="wd-subtitle">
						Kim ne kadar mail gönderiyor, ne sekiyor. Ele geçirilmiş bir hesabı
						IP kara listeye düşmeden önce burada fark edersiniz.
					</p>
				</div>
				<div class="wd-page-actions">
					<a class="button button-secondary" href="/list/mailrapor/?yenile=1&amp;gun=<?= (int) $wd_gun ?>&amp;token=<?= wd_e($tok) ?>">
						<i class="fas fa-rotate"></i> Yeniden Tara
					</a>
				</div>
			</div>

			<?php if (!empty($wd_yenilendi)) { ?>
				<div class="wd-note wd-note-ok">
					<i class="fas fa-circle-check"></i><span>Günlük yeniden tarandı.</span>
				</div>
			<?php } ?>

			<?php if ($wd_rapor === null) { ?>

				<div class="wd-note wd-note-warn">
					<i class="fas fa-triangle-exclamation"></i>
					<span>Henüz rapor üretilmemiş. Yukarıdan "Yeniden Tara" deyin.</span>
				</div>

			<?php } else { ?>

				<div class="wd-stats">
					<div class="wd-stat" title="Sisteme kabul edilen ileti sayısı">
						<div class="wd-stat-label">KABUL</div>
						<div class="wd-stat-value"><?= (int) $t["kabul"] ?><span class="wd-stat-of">ileti</span></div>
						<div class="wd-bar wd-ok"><span style="width:100%"></span></div>
					</div>
					<div class="wd-stat" title="Alıcıya ulaştırılan">
						<div class="wd-stat-label">TESLİM</div>
						<div class="wd-stat-value"><?= (int) $t["teslim"] ?><span class="wd-stat-of">başarılı</span></div>
						<div class="wd-bar wd-ok"><span style="width:100%"></span></div>
					</div>
					<div class="wd-stat" title="Kalıcı olarak teslim edilemeyen (bounce)">
						<div class="wd-stat-label">SEKME</div>
						<div class="wd-stat-value"><?= (int) $t["sekme"] ?><span class="wd-stat-of">%<?= wd_e(number_format($sekme_orani, 1, ",", ".")) ?></span></div>
						<div class="wd-bar <?= $sekme_orani >= 35 ? "wd-crit" : ($sekme_orani >= 15 ? "wd-warn" : "wd-ok") ?>">
							<span style="width: <?= max(2, min(100, $sekme_orani)) ?>%"></span>
						</div>
					</div>
					<div class="wd-stat" title="Kuyrukta bekleyen ve donmuş iletiler">
						<div class="wd-stat-label">KUYRUK</div>
						<div class="wd-stat-value"><?= (int) $kuyruk["bekleyen"] ?><span class="wd-stat-of"><?= (int) $kuyruk["donmus"] ?> donmuş</span></div>
						<div class="wd-bar <?= $kuyruk["donmus"] > 0 ? "wd-warn" : "wd-ok" ?>">
							<span style="width: <?= $kuyruk["bekleyen"] > 0 ? 100 : 2 ?>%"></span>
						</div>
					</div>
				</div>

				<div class="wd-err-tabs" role="tablist">
					<?php foreach ([1, 7, 30] as $g) { ?>
						<a class="wd-err-tab<?= $g === $wd_gun ? " aktif" : "" ?>"
							href="/list/mailrapor/?yenile=1&amp;gun=<?= $g ?>&amp;token=<?= wd_e($tok) ?>">
							<span class="wd-err-kod"><?= $g ?></span>
							<span class="wd-err-ad">gün</span>
						</a>
					<?php } ?>
				</div>

				<div class="wd-card">
					<div class="wd-card-head">
						Gönderenler
						<span class="wd-card-note">son <?= (int) ($wd_rapor["gun"] ?? 7) ?> gün</span>
					</div>
					<div class="wd-card-body">
						<?php if (empty($wd_rapor["gonderenler"])) { ?>
							<p class="wd-empty">Bu dönemde gönderim yok.</p>
						<?php } else {
      foreach ($wd_rapor["gonderenler"] as $g) { ?>
								<div class="wd-usage">
									<div class="wd-usage-top">
										<span class="wd-usage-label wd-usage-site" title="<?= wd_e($g["gonderen"]) ?>">
											<span class="wd-check-dot <?= wd_e(wd_health_sinif($g["durum"])) ?>"></span>
											<?= wd_e($g["gonderen"]) ?>
										</span>
										<span class="wd-usage-num"><?= (int) $g["ileti"] ?> ileti</span>
									</div>
									<div class="wd-site-metrics">
										<span class="wd-site-metric"><b><?= (int) $g["teslim"] ?></b> teslim</span>
										<span class="wd-site-metric"><b><?= (int) $g["sekme"] ?></b> sekme</span>
										<span class="wd-site-metric">%<?= wd_e(number_format($g["sekme_orani"], 1, ",", ".")) ?></span>
										<span class="wd-site-metric"><?= wd_e(wd_bayt((int) $g["bayt"])) ?></span>
									</div>
									<?php if ($g["durum"] !== "ok") { ?>
										<p class="wd-check-fix">
											<i class="fas fa-screwdriver-wrench"></i>
											<span>
												Sekme oranı yüksek. Hesabın parolası ele geçirilmiş olabilir ya da
												alıcı listesi bozuk. Parolayı değiştirin ve giden kuyruğu inceleyin:
												<span class="wd-mono">exim4 -bp</span>
											</span>
										</p>
									<?php } ?>
								</div>
							<?php }
     } ?>
					</div>
				</div>

				<?php if (!empty($wd_rapor["alici_alanlar"])) { ?>
					<div class="wd-card">
						<div class="wd-card-head">En Çok Yazılan Alan Adları</div>
						<div class="wd-card-body">
							<?php foreach ($wd_rapor["alici_alanlar"] as $a) { ?>
								<div class="wd-disk-line">
									<span class="wd-disk-name"><?= wd_e($a["alan"]) ?></span>
									<span class="wd-disk-size"><?= (int) $a["adet"] ?></span>
								</div>
							<?php } ?>
						</div>
					</div>
				<?php } ?>

			<?php } ?>
		</div>

		<!-- ================= SAĞ PANEL ================= -->
		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head">Nasıl Okunur?</div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">Sekme oranı</span>
						<span class="wd-v-small">
							%15 üzeri uyarı, %35 üzeri ciddi. Yalnızca 20'den fazla ileti gönderen
							hesaplar damgalanır — az örnekte oran yanıltır.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Ani hacim artışı</span>
						<span class="wd-v-small">
							Normalde günde birkaç mail atan bir hesap birden yüzlerce atıyorsa,
							neredeyse her zaman parolası ele geçirilmiştir.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Donmuş ileti</span>
						<span class="wd-v-small">
							Teslim edilemeyip kuyrukta kalanlar. Birikirse kuyruğu temizleyin:
							<span class="wd-mono">exim4 -bpru | awk '{print $3}' | xargs -r exim4 -Mrm</span>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Reddedilen</span>
						<span class="wd-v-small">
							Bu dönemde <b><?= (int) ($wd_rapor["reddedilen"] ?? 0) ?></b> bağlantı reddedildi.
							Çoğu bot taramasıdır; normaldir.
						</span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>
