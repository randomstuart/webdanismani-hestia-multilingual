<?php
/**
 * WebDanışmanı — "Güvenlik" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_guvenlik.php
 */

$tok = $_SESSION["token"] ?? "";
$ozet = $wd_guv["ozet"] ?? ["ok" => 0, "warn" => 0, "fail" => 0, "bilinmiyor" => 0];
$tozet = $wd_tar["ozet"] ?? ["fail" => 0, "warn" => 0];
$bulgu = ($tozet["fail"] ?? 0) + ($tozet["warn"] ?? 0);
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title">Güvenlik</h1>
					<p class="wd-subtitle">
						Sunucu sertleştirme durumu ve müşteri sitelerinde zararlı kod taraması.
					</p>
				</div>
				<div class="wd-page-actions">
					<form method="post" class="wd-inline-form">
						<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
						<input type="hidden" name="ok" value="1">
						<input type="hidden" name="islem" value="denetle">
						<button type="submit" class="button button-secondary"><i class="fas fa-rotate"></i> Denetle</button>
					</form>
					<form method="post" class="wd-inline-form">
						<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
						<input type="hidden" name="ok" value="1">
						<input type="hidden" name="islem" value="tara">
						<button type="submit" class="button button-secondary"><i class="fas fa-magnifying-glass"></i> Tara</button>
					</form>
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

			<div class="wd-stats">
				<div class="wd-stat">
					<div class="wd-stat-label">AÇIK</div>
					<div class="wd-stat-value"><?= (int) ($ozet["fail"] ?? 0) ?><span class="wd-stat-of">acil</span></div>
					<div class="wd-bar <?= ($ozet["fail"] ?? 0) > 0 ? "wd-crit" : "wd-ok" ?>">
						<span style="width: <?= ($ozet["fail"] ?? 0) > 0 ? 100 : 2 ?>%"></span></div>
				</div>
				<div class="wd-stat">
					<div class="wd-stat-label">UYARI</div>
					<div class="wd-stat-value"><?= (int) ($ozet["warn"] ?? 0) ?><span class="wd-stat-of">gözden geçir</span></div>
					<div class="wd-bar <?= ($ozet["warn"] ?? 0) > 0 ? "wd-warn" : "wd-ok" ?>">
						<span style="width: <?= ($ozet["warn"] ?? 0) > 0 ? 100 : 2 ?>%"></span></div>
				</div>
				<div class="wd-stat">
					<div class="wd-stat-label">SORUNSUZ</div>
					<div class="wd-stat-value"><?= (int) ($ozet["ok"] ?? 0) ?><span class="wd-stat-of">kontrol</span></div>
					<div class="wd-bar wd-ok"><span style="width:100%"></span></div>
				</div>
				<div class="wd-stat" title="Zararlı kod taramasında incelenmesi gereken dosya sayısı">
					<div class="wd-stat-label">ŞÜPHELİ DOSYA</div>
					<div class="wd-stat-value"><?= (int) $bulgu ?><span class="wd-stat-of">incelenmeli</span></div>
					<div class="wd-bar <?= $bulgu > 0 ? "wd-crit" : "wd-ok" ?>">
						<span style="width: <?= $bulgu > 0 ? 100 : 2 ?>%"></span></div>
				</div>
			</div>

			<?php // ================= SUNUCU DENETİMİ =================
   if ($wd_guv === null) { ?>
				<div class="wd-note wd-note-warn">
					<i class="fas fa-triangle-exclamation"></i>
					<span>Güvenlik denetimi henüz çalışmamış. Yukarıdan "Denetle" deyin.</span>
				</div>
			<?php } else { ?>
				<details class="wd-group" open>
					<summary class="wd-group-head">
						<span class="wd-group-icon"><i class="fas fa-shield-halved"></i></span>
						<span class="wd-group-title">Sunucu Denetimi</span>
						<span class="wd-group-count"><?= count($wd_guv["kontroller"] ?? []) ?> kontrol</span>
						<i class="fas fa-chevron-down wd-group-chevron"></i>
					</summary>
					<div class="wd-checks">
						<?php foreach ($wd_guv["kontroller"] ?? [] as $c) {
       	$durum = $c["durum"] ?? "bilinmiyor";
       	$sorunlu = in_array($durum, ["fail", "warn"], true); ?>
							<div class="wd-check wd-check-<?= wd_e($durum) ?>">
								<span class="wd-check-dot <?= wd_e(wd_health_sinif($durum)) ?>"
									title="<?= wd_e(wd_health_etiket($durum)) ?>"></span>
								<div class="wd-check-body">
									<div class="wd-check-top">
										<span class="wd-check-label"><?= wd_e($c["label"]) ?></span>
										<?php if (($c["deger"] ?? "") !== "") { ?>
											<span class="wd-check-value wd-mono"><?= wd_e($c["deger"]) ?></span>
										<?php } ?>
									</div>
									<?php if (!empty($c["not"])) { ?>
										<p class="wd-check-note"><?= wd_e($c["not"]) ?></p>
									<?php } ?>
									<?php if ($sorunlu && !empty($c["cozum"])) { ?>
										<p class="wd-check-fix">
											<i class="fas fa-screwdriver-wrench"></i>
											<span><?= wd_e($c["cozum"]) ?></span>
										</p>
									<?php } ?>
									<?php if ($sorunlu && !empty($c["islem"])) { ?>
										<form method="post" class="wd-inline-form"
											onsubmit="return confirm('Bu işlem sunucu ayarını değiştirecek. Devam edilsin mi?\n\nÖn koşul sağlanmıyorsa işlem reddedilir — kilitlenme koruması vardır.');">
											<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
											<input type="hidden" name="ok" value="1">
											<input type="hidden" name="islem" value="<?= wd_e($c["islem"]) ?>">
											<button type="submit" class="wd-mini-btn">Şimdi düzelt</button>
										</form>
									<?php } ?>
								</div>
							</div>
						<?php } ?>
					</div>
				</details>

				<?php if (!empty($wd_guv["saldirganlar"])) { ?>
					<details class="wd-group">
						<summary class="wd-group-head">
							<span class="wd-group-icon"><i class="fas fa-user-secret"></i></span>
							<span class="wd-group-title">En Çok Deneyen IP'ler</span>
							<span class="wd-group-count">SSH</span>
							<i class="fas fa-chevron-down wd-group-chevron"></i>
						</summary>
						<div class="wd-auth-body">
							<?php foreach ($wd_guv["saldirganlar"] as $s) { ?>
								<div class="wd-disk-line">
									<span class="wd-disk-name wd-mono"><?= wd_e($s["ip"]) ?></span>
									<span class="wd-disk-size"><?= (int) $s["deneme"] ?> deneme</span>
								</div>
							<?php } ?>
							<p class="wd-aciklama wd-aciklama-kucuk">
								fail2ban bunları zaten engelliyor. Sürekli tekrar eden bir blok varsa
								Güvenlik Duvarı'ndan kalıcı olarak yasaklayabilirsiniz.
							</p>
						</div>
					</details>
				<?php } ?>
			<?php } ?>

			<?php // ================= ZARARLI TARAMA ================= ?>
			<details class="wd-group" <?= $bulgu > 0 ? "open" : "" ?>>
				<summary class="wd-group-head">
					<span class="wd-group-icon"><i class="fas fa-viruses"></i></span>
					<span class="wd-group-title">Zararlı Kod Taraması</span>
					<?php if ($wd_tar !== null) { ?>
						<span class="wd-group-count">
							<?= (int) ($wd_tar["sayac"]["taranan"] ?? 0) ?> dosya ·
							<?= wd_e(date("d.m.Y H:i", (int) ($wd_tar["ts"] ?? 0))) ?>
						</span>
					<?php } ?>
					<i class="fas fa-chevron-down wd-group-chevron"></i>
				</summary>
				<div class="wd-auth-body">
					<?php if ($wd_tar === null) { ?>
						<p class="wd-empty">Tarama henüz çalışmamış. Yukarıdan "Tara" deyin.</p>
					<?php } elseif (!empty($wd_tar["eksik"])) { ?>
						<div class="wd-note wd-note-warn wd-note-inline">
							<i class="fas fa-triangle-exclamation"></i>
							<span>Tarama süre/dosya sınırına takıldı; sonuç eksik olabilir.</span>
						</div>
					<?php } ?>

					<?php if ($wd_tar !== null && empty($wd_tar["bulgular"])) { ?>
						<p class="wd-empty">Şüpheli dosya bulunamadı.</p>
					<?php } ?>

					<?php foreach ($wd_tar["bulgular"] ?? [] as $b) { ?>
						<div class="wd-check wd-check-<?= wd_e($b["seviye"]) ?>">
							<span class="wd-check-dot <?= wd_e(wd_health_sinif($b["seviye"])) ?>"></span>
							<div class="wd-check-body">
								<div class="wd-check-top">
									<span class="wd-check-label wd-mono"><?= wd_e(wd_yol_kisalt($b["kisa"], 66)) ?></span>
									<span class="wd-check-value">puan <?= (int) $b["puan"] ?> · <?= wd_e($b["user"]) ?></span>
								</div>
								<?php foreach ($b["bulgular"] as $x) {
        	if (($x["puan"] ?? 0) <= 0) {
        		continue;
        	} ?>
									<p class="wd-check-note">• <?= wd_e($x["aciklama"]) ?></p>
								<?php } ?>
								<p class="wd-check-fix">
									<i class="fas fa-screwdriver-wrench"></i>
									<span>
										Dosyayı Dosya Yöneticisi'nden açıp inceleyin. Silmeden önce
										<b>yedek alın</b> — bulgu kesin suçlama değil, incelenmesi gereken
										bir işarettir. Tam yol:
										<span class="wd-mono"><?= wd_e($b["yol"]) ?></span>
									</span>
								</p>
							</div>
						</div>
					<?php } ?>
				</div>
			</details>

		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head">Bilmeniz Gerekenler</div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">Kilitlenme koruması</span>
						<span class="wd-v-small">
							"SSH parola girişini kapat" gibi işlemler, sunucuda <b>giriş
							yapabilen</b> bir anahtar yoksa reddedilir. Anahtarı olan ama kabuğu
							<span class="wd-mono">nologin</span> olan hesap sayılmaz.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Hiçbir şey silinmez</span>
						<span class="wd-v-small">
							Tarama yalnızca raporlar. Karantina veya silme yapmaz — müşterinin
							çalışan dosyasını yanlışlıkla yok etmek, geç fark edilen bir
							webshell'den daha büyük zarar verir.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Puan ne demek</span>
						<span class="wd-v-small">
							Davranış kalıplarının toplamı. 40 ve üstü bildirilir, 70 üstü ciddi.
							Tek bir kalıp suçlama değildir; meşru kod da benzer fonksiyonlar kullanır.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Kara liste bağlantısı</span>
						<span class="wd-v-small">
							Sunucu IP'si kara listeye düştüyse ilk bakılacak yer burasıdır:
							ele geçirilmiş bir site genelde spam gönderiyordur. Mail Raporu ile
							birlikte okuyun.
						</span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>
