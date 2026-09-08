<?php
/**
 * WebDanışmanı — "Cloudflare" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_cloudflare.php
 */

$tok = $_SESSION["token"] ?? "";
$gercek_ip = !empty($wd_cf["gercek_ip_aktif"]);
$jeton_var = !empty($wd_cf["jeton_var"]);
$zonlar = $wd_cf["zonlar"] ?? [];
?>

<div class="container">
	<div class="wd-page">

		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title">Cloudflare</h1>
					<p class="wd-subtitle">
						Gerçek ziyaretçi IP'si, önbellek temizleme ve DNS gönderimi.
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

			<?php if ($wd_cf === null) { ?>
				<div class="wd-note wd-note-warn">
					<i class="fas fa-triangle-exclamation"></i>
					<span>Cloudflare aracı çalıştırılamadı. Sunucuda
						<span class="wd-mono">bash /usr/local/hestia/wd/src/kur.sh</span> çalıştırın.</span>
				</div>
			<?php } else { ?>

			<!-- ============ 1) Gerçek ziyaretçi IP'si ============ -->
			<div class="wd-card">
				<div class="wd-card-head">
					Gerçek Ziyaretçi IP'si
					<span class="wd-card-note">jeton gerektirmez</span>
				</div>
				<div class="wd-card-body wd-card-body-pad">
					<p class="wd-aciklama">
						Site Cloudflare arkasındayken sunucuya gelen bağlantı Cloudflare'den gelir.
						Bu liste tanımlı değilse <b>tüm ziyaretçiler Cloudflare IP'si olarak görünür</b> —
						günlükler işe yaramaz ve fail2ban yanlış adresi engeller, hatta Cloudflare'i
						engelleyip siteyi tümden kapatabilir.
					</p>

					<div class="wd-kv-satir">
						<span class="wd-durum-rozet <?= $gercek_ip ? "wd-hs-ok" : "wd-hs-fail" ?>">
							<?= $gercek_ip ? "Etkin" : "Pasif" ?>
						</span>
						<span class="wd-v-dim">
							<?= (int) ($wd_cf["ip_v4"] ?? 0) ?> IPv4 · <?= (int) ($wd_cf["ip_v6"] ?? 0) ?> IPv6 aralığı
							<?php if (!empty($wd_cf["ip_guncelleme"])) { ?>
								· son güncelleme <?= wd_e($wd_cf["ip_guncelleme"]) ?>
							<?php } ?>
						</span>
					</div>

					<form method="post" class="wd-inline-form">
						<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
						<input type="hidden" name="ok" value="1">
						<input type="hidden" name="islem" value="ip">
						<button type="submit" class="button button-secondary">
							<i class="fas fa-rotate"></i> IP Listesini Güncelle
						</button>
					</form>
					<p class="wd-aciklama wd-aciklama-kucuk">
						Liste günlük olarak kendiliğinden tazelenir. Güncelleme öncesi nginx ve
						Apache yapılandırması doğrulanır; geçersizse değişiklik geri alınır.
					</p>
				</div>
			</div>

			<!-- ============ 2) API jetonu ============ -->
			<div class="wd-card">
				<div class="wd-card-head">
					API Jetonu
					<span class="wd-card-note"><?= $jeton_var ? "kayıtlı" : "tanımsız" ?></span>
				</div>
				<div class="wd-card-body wd-card-body-pad">
					<?php if ($jeton_var) { ?>
						<div class="wd-kv-satir">
							<span class="wd-durum-rozet wd-hs-ok">Doğrulandı</span>
							<span class="wd-v-dim">
								<?= count($zonlar) ?> bölge görünüyor
								<?php if (!empty($wd_cf["dogrulandi"])) { ?>
									· <?= wd_e(date("d.m.Y H:i", (int) $wd_cf["dogrulandi"])) ?>
								<?php } ?>
							</span>
						</div>
						<?php if (!empty($wd_cf["hata"])) { ?>
							<div class="wd-note wd-note-warn wd-note-inline">
								<i class="fas fa-triangle-exclamation"></i>
								<span>API yanıtı: <?= wd_e($wd_cf["hata"]) ?></span>
							</div>
						<?php } ?>
						<form method="post" class="wd-inline-form"
							onsubmit="return confirm('Jeton silinsin mi? API işlemleri kullanılamaz hale gelir.');">
							<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
							<input type="hidden" name="ok" value="1">
							<input type="hidden" name="islem" value="jeton-sil">
							<button type="submit" class="button button-secondary">Jetonu Sil</button>
						</form>
					<?php } else { ?>
						<p class="wd-aciklama">
							Cloudflare panelinde <b>My Profile → API Tokens → Create Token</b> ile
							bir jeton oluşturun. Gereken yetkiler:
							<span class="wd-mono">Zone:Read</span>,
							<span class="wd-mono">DNS:Edit</span>,
							<span class="wd-mono">Cache Purge:Purge</span>.
							Global API Key <b>kullanmayın</b> — o anahtar hesabın tamamına erişir.
						</p>
						<form method="post" class="wd-auth-form">
							<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
							<input type="hidden" name="ok" value="1">
							<input type="hidden" name="islem" value="jeton">
							<div class="wd-auth-field wd-auth-field-genis">
								<label class="form-label" for="v_token">API Jetonu</label>
								<input class="form-control" type="password" id="v_token" name="v_token"
									autocomplete="off" required minlength="20"
									placeholder="Cloudflare API token">
							</div>
							<button type="submit" class="button">Kaydet ve Doğrula</button>
						</form>
					<?php } ?>
				</div>
			</div>

			<!-- ============ 3) Bölgeler ============ -->
			<?php if ($jeton_var && !empty($zonlar)) {
    foreach ($zonlar as $z) {
    	$panelde = isset($wd_dns_bolgeleri[$z["ad"]]); ?>
					<details class="wd-group">
						<summary class="wd-group-head">
							<span class="wd-group-icon"><i class="fas fa-cloud"></i></span>
							<span class="wd-group-title"><?= wd_e($z["ad"]) ?></span>
							<span class="wd-hs-badge <?= $z["durum"] === "active" ? "wd-hs-ok" : "wd-hs-warn" ?>">
								<?= wd_e($z["durum"]) ?>
							</span>
							<span class="wd-group-count"><?= wd_e($z["plan"]) ?></span>
							<i class="fas fa-chevron-down wd-group-chevron"></i>
						</summary>
						<div class="wd-auth-body">

							<?php if (!empty($z["ns"])) { ?>
								<div class="wd-kv">
									<span class="wd-k">Cloudflare nameserver</span>
									<span class="wd-v wd-mono"><?= wd_e(implode(", ", $z["ns"])) ?></span>
								</div>
							<?php } ?>

							<div class="wd-cf-eylemler">
								<form method="post" class="wd-inline-form">
									<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
									<input type="hidden" name="ok" value="1">
									<input type="hidden" name="islem" value="onbellek">
									<input type="hidden" name="v_zone" value="<?= wd_e($z["ad"]) ?>">
									<button type="submit" class="wd-mini-btn">Önbelleği Temizle</button>
								</form>

								<form method="post" class="wd-inline-form">
									<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
									<input type="hidden" name="ok" value="1">
									<input type="hidden" name="islem" value="gelistirme">
									<input type="hidden" name="v_zone" value="<?= wd_e($z["ad"]) ?>">
									<input type="hidden" name="v_deger" value="on">
									<button type="submit" class="wd-mini-btn">Geliştirme Modu Aç (3 sa)</button>
								</form>

								<form method="post" class="wd-inline-form">
									<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
									<input type="hidden" name="ok" value="1">
									<input type="hidden" name="islem" value="gelistirme">
									<input type="hidden" name="v_zone" value="<?= wd_e($z["ad"]) ?>">
									<input type="hidden" name="v_deger" value="off">
									<button type="submit" class="wd-mini-btn">Kapat</button>
								</form>

								<?php if ($panelde) { ?>
									<form method="post" class="wd-inline-form"
										onsubmit="return confirm('Paneldeki DNS kayıtları Cloudflare\'e gönderilecek. Cloudflare\'deki fazladan kayıtlar SİLİNMEZ, yalnızca eksikler eklenir ve farklılar güncellenir. Devam?');">
										<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
										<input type="hidden" name="ok" value="1">
										<input type="hidden" name="islem" value="dns">
										<input type="hidden" name="v_zone" value="<?= wd_e($z["ad"]) ?>">
										<button type="submit" class="wd-mini-btn">Panel DNS'ini Gönder</button>
									</form>
								<?php } ?>
							</div>

							<?php if (!$panelde) { ?>
								<p class="wd-aciklama wd-aciklama-kucuk">
									Bu bölgenin panelde bir DNS kaydı yok, bu yüzden gönderim seçeneği kapalı.
								</p>
							<?php } ?>
						</div>
					</details>
				<?php }
   } ?>

			<?php } ?>
		</div>

		<!-- ================= SAĞ PANEL ================= -->
		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head">Bilmeniz Gerekenler</div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">Turuncu bulut</span>
						<span class="wd-v-small">
							Bir kayıt Cloudflare üzerinden geçiyorsa (proxied), o alan adı için
							Let's Encrypt <b>HTTP doğrulaması başarısız olabilir</b>. Sertifika
							alırken bulutu geçici olarak gri yapın.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Geliştirme modu</span>
						<span class="wd-v-small">
							Önbelleği 3 saatliğine devre dışı bırakır. Site üzerinde çalışırken
							değişikliklerin anında görünmesi için.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">DNS gönderimi</span>
						<span class="wd-v-small">
							Tek yönlüdür: panel → Cloudflare. Cloudflare'deki kayıtlar
							<b>silinmez</b>; yalnızca eksikler eklenir, farklılar güncellenir.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Jeton nerede</span>
						<span class="wd-v-small">
							<span class="wd-mono">wd/cloudflare.json</span>, yalnızca root okuyabilir.
							Panel dosyayı okumaz; işlemler sudo'lu araç üzerinden yapılır.
						</span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>
