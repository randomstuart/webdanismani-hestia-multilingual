<?php
/**
 * WebDanışmanı — "WordPress Araçları" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_wp.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$form = function (array $s, string $islem, array $ek = []): string {
	$h = '<input type="hidden" name="token" value="' . wd_e($_SESSION["token"] ?? "") . '"><input type="hidden" name="ok" value="1">';
	$h .= '<input type="hidden" name="islem" value="' . wd_e($islem) . '">';
	$h .= '<input type="hidden" name="v_user" value="' . wd_e($s["user"]) . '"><input type="hidden" name="v_domain" value="' . wd_e($s["domain"]) . '">';
	$h .= '<input type="hidden" name="v_yol" value="' . wd_e($s["yol"]) . '">';
	foreach ($ek as $k => $v) {
		$h .= '<input type="hidden" name="' . wd_e($k) . '" value="' . wd_e($v) . '">';
	}
	return $h;
};
$toplam_g = 0;
foreach ($wd_siteler as $s) {
	$toplam_g += (int) ($s["guncelleme_sayisi"] ?? 0);
}
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php
			$eylem = '<form method="post" style="margin:0">' . wd_modul_form_gizli("tara") .
				'<button type="submit" class="button button-secondary" title="Tüm siteleri yeniden tara (wp-cli, biraz sürebilir)"><i class="fas fa-rotate"></i> Yeniden Tara</button></form>';
			wd_modul_baslik("WordPress Araçları", "Güncellemeler, otomatik güncelleme, bütünlük doğrulama, bakım modu ve tek tıkla yönetici girişi.", $eylem);
			?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, ($_GET["durum"] ?? "") === "sorunlu" || ($_GET["durum"] ?? "") === "hata" ? "warn" : "ok");
			} ?>
			<?php if (!empty($wd_cikti)) { ?>
				<div class="wd-card"><div class="wd-card-body wd-card-body-pad"><pre class="wdm-kod wdm-kod-kucuk"><?= wd_e(implode("\n", $wd_cikti)) ?></pre></div></div>
			<?php } ?>
			<?php if ($wd_wp_cli === false) {
				wd_modul_not("Sunucuda wp-cli bulunamadı; yönetici kur-modul.sh çalıştırarak kurmalı. Liste görüntülenir ama işlemler çalışmaz.", "warn");
			} ?>

			<div class="wd-stats">
				<div class="wd-stat"><div class="wd-stat-label">WORDPRESS SİTESİ</div><div class="wd-stat-value"><?= count($wd_siteler) ?></div></div>
				<div class="wd-stat"><div class="wd-stat-label">BEKLEYEN GÜNCELLEME</div><div class="wd-stat-value" style="<?= $toplam_g > 0 ? "color:var(--wd-amber)" : "" ?>"><?= $toplam_g ?></div></div>
				<div class="wd-stat"><div class="wd-stat-label">SON TARAMA</div><div class="wd-stat-value"><?= $wd_yas === null ? "—" : wd_e(wd_modul_sure($wd_yas)) ?><span class="wd-stat-of">önce</span></div></div>
			</div>

			<?php if (empty($wd_siteler)) { ?>
				<div class="wd-card"><div class="wdm-bos"><i class="fas fa-w"></i><?= $wd_veri === null ? "Henüz tarama yapılmadı. \"Yeniden Tara\" ile başlatın." : "WordPress kurulumu bulunamadı. Uygulama Kurucu > Hızlı Kurulum ile WordPress kurabilirsiniz." ?></div></div>
			<?php } ?>

			<?php foreach ($wd_siteler as $s) {
				$acik = $wd_secili === "" ? count($wd_siteler) === 1 : $wd_secili === $s["domain"];
				$g = (int) ($s["guncelleme_sayisi"] ?? 0);
				$dog = $s["dogrulama"] ?? null; ?>
				<details class="wd-card" <?= $acik ? "open" : "" ?>>
					<summary class="wd-card-head" style="cursor:pointer;list-style:none">
						<span style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
							<b><?= wd_e($s["domain"]) ?><?= $s["yol"] !== "/" ? wd_e($s["yol"]) : "" ?></b>
							<span class="wd-card-note">WP <?= wd_e($s["surum"] ?: "?") ?><?= !empty($s["ad"]) ? " · " . wd_e($s["ad"]) : "" ?><?= $wd_is_admin ? " · " . wd_e($s["user"]) : "" ?></span>
							<?php if (!empty($s["hata"])) { ?><span class="wdm-rozet wdm-rozet-warn"><?= wd_e($s["hata"]) ?></span><?php } ?>
							<?php if ($g > 0) { ?><span class="wdm-rozet wdm-rozet-warn"><?= $g ?> güncelleme</span><?php } else { ?><span class="wdm-rozet wdm-rozet-ok">güncel</span><?php } ?>
							<?php if (!empty($s["bakim"])) { ?><span class="wdm-rozet wdm-rozet-info">bakım modu</span><?php } ?>
							<?php if ($dog !== null && empty($dog["ok"])) { ?><span class="wdm-rozet wdm-rozet-err">bütünlük sorunu</span><?php } ?>
						</span>
						<i class="fas fa-chevron-down wd-group-chevron"></i>
					</summary>
					<div class="wd-card-body wd-card-body-pad">
						<div class="wdm-dugmeler">
							<form method="post"><?= $form($s, "giris") ?><button type="submit" class="button" formtarget="_blank" title="Şifre girmeden yönetici paneline gir (120 sn geçerli tek kullanımlık bağlantı)"><i class="fas fa-right-to-bracket"></i> Yönetici olarak gir</button></form>
							<?php if ($g > 0) { ?>
								<form method="post" onsubmit="return confirm('Çekirdek, eklenti ve temalar güncellenecek. Önce yedek almanız önerilir. Devam?');"><?= $form($s, "guncelle", ["v_ne" => "hepsi"]) ?><button type="submit" class="button button-secondary"><i class="fas fa-arrow-up"></i> Tümünü güncelle</button></form>
							<?php } ?>
							<form method="post"><?= $form($s, "otomatik", ["v_deger" => !empty($s["otomatik_cekirdek"]) && (int) ($s["otomatik_eklenti"] ?? 0) > 0 ? "off" : "on"]) ?><button type="submit" class="button button-secondary" title="Çekirdek + eklenti + tema otomatik güncellemesi"><?= !empty($s["otomatik_cekirdek"]) && (int) ($s["otomatik_eklenti"] ?? 0) > 0 ? "Otomatik güncellemeyi kapat" : "Otomatik güncellemeyi aç" ?></button></form>
							<form method="post"><?= $form($s, "bakim", ["v_deger" => !empty($s["bakim"]) ? "off" : "on"]) ?><button type="submit" class="button button-secondary"><?= !empty($s["bakim"]) ? "Bakım modunu kapat" : "Bakım modu" ?></button></form>
							<form method="post"><?= $form($s, "dogrula") ?><button type="submit" class="button button-secondary" title="Çekirdek dosyaları resmî sürümle karşılaştırır"><i class="fas fa-fingerprint"></i> Bütünlük doğrula</button></form>
							<form method="post"><?= $form($s, "onbellek") ?><button type="submit" class="button button-secondary"><i class="fas fa-broom"></i> Önbelleği temizle</button></form>
						</div>

						<?php if (!empty($s["cekirdek_guncelleme"])) { ?>
							<div class="wd-note wd-note-warn" style="margin-top:10px"><i class="fas fa-arrow-up"></i><span>Çekirdek güncellemesi var: <b><?= wd_e($s["cekirdek_guncelleme"]) ?></b>
								<form method="post" style="display:inline;margin-left:8px"><?= $form($s, "guncelle", ["v_ne" => "cekirdek"]) ?><button type="submit" class="wd-mini-btn">Yalnız çekirdeği güncelle</button></form></span></div>
						<?php } ?>

						<?php if ($dog !== null && empty($dog["ok"]) && !empty($dog["sorunlu"])) { ?>
							<div class="wd-note wd-note-err" style="margin-top:10px"><i class="fas fa-triangle-exclamation"></i>
								<span><b>Çekirdek dosyalar resmî sürümden farklı</b> (<?= wd_e(wd_modul_tarih((int) ($dog["ts"] ?? 0))) ?>). Bu dosyalar eklenti değil, WordPress'in kendi dosyalarıdır; değişmiş olmaları ele geçirilme belirtisi olabilir. Yedekten dönmeden önce "Yalnız çekirdeği güncelle" ile resmî dosyaları geri yükleyin.
									<pre class="wdm-kod wdm-kod-kucuk" style="margin-top:6px"><?= wd_e(implode("\n", (array) $dog["sorunlu"])) ?></pre></span>
							</div>
						<?php } ?>

						<?php if (!empty($s["eklentiler"])) { ?>
							<div class="wdm-tablo-sar" style="margin-top:12px">
								<table class="wdm-tablo">
									<thead><tr><th>Eklenti</th><th>Durum</th><th>Sürüm</th><th>Otomatik</th><th class="daralt"></th></tr></thead>
									<tbody>
										<?php foreach ($s["eklentiler"] as $e) {
											$guncel = ($e["update"] ?? "") === "available"; ?>
											<tr>
												<td><b><?= wd_e($e["title"] ?: $e["name"]) ?></b><br><span class="wdm-eskime wdm-mono"><?= wd_e($e["name"]) ?></span></td>
												<td><?= ($e["status"] ?? "") === "active" ? '<span class="wdm-rozet wdm-rozet-ok">etkin</span>' : (($e["status"] ?? "") === "must-use" ? '<span class="wdm-rozet wdm-rozet-info">zorunlu</span>' : '<span class="wdm-rozet">pasif</span>') ?></td>
												<td class="mono"><?= wd_e($e["version"] ?? "") ?><?= $guncel ? ' <span class="wdm-rozet wdm-rozet-warn">→ ' . wd_e($e["update_version"] ?? "") . "</span>" : "" ?></td>
												<td><?= in_array((string) ($e["auto_update"] ?? ""), ["on", "1", "true"], true) ? "açık" : "kapalı" ?></td>
												<td class="daralt">
													<div class="wdm-oge-eylem">
														<?php if ($guncel) { ?><form method="post"><?= $form($s, "eklenti", ["v_ad" => $e["name"], "v_ne" => "update"]) ?><button type="submit" class="wd-mini-btn">Güncelle</button></form><?php } ?>
														<?php if (($e["status"] ?? "") === "active") { ?><form method="post" onsubmit="return confirm('<?= wd_e($e["name"]) ?> devre dışı bırakılacak. Devam?');"><?= $form($s, "eklenti", ["v_ad" => $e["name"], "v_ne" => "deactivate"]) ?><button type="submit" class="wd-mini-btn">Kapat</button></form>
														<?php } elseif (($e["status"] ?? "") === "inactive") { ?><form method="post"><?= $form($s, "eklenti", ["v_ad" => $e["name"], "v_ne" => "activate"]) ?><button type="submit" class="wd-mini-btn">Etkinleştir</button></form><?php } ?>
													</div>
												</td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						<?php } ?>

						<?php if (!empty($s["temalar"])) { ?>
							<details style="margin-top:10px">
								<summary style="cursor:pointer;font-size:11.5px;color:var(--wd-muted)">Temalar (<?= count($s["temalar"]) ?>)<?= (int) ($s["tema_guncelleme"] ?? 0) > 0 ? " · " . (int) $s["tema_guncelleme"] . " güncelleme" : "" ?></summary>
								<div class="wdm-tablo-sar" style="margin-top:6px">
									<table class="wdm-tablo">
										<thead><tr><th>Tema</th><th>Durum</th><th>Sürüm</th></tr></thead>
										<tbody>
											<?php foreach ($s["temalar"] as $t) { ?>
												<tr>
													<td><?= wd_e($t["title"] ?: $t["name"]) ?></td>
													<td><?= ($t["status"] ?? "") === "active" ? '<span class="wdm-rozet wdm-rozet-ok">etkin</span>' : (($t["status"] ?? "") === "parent" ? '<span class="wdm-rozet wdm-rozet-info">üst tema</span>' : '<span class="wdm-rozet">pasif</span>') ?></td>
													<td class="mono"><?= wd_e($t["version"] ?? "") ?><?= ($t["update"] ?? "") === "available" ? ' <span class="wdm-rozet wdm-rozet-warn">→ ' . wd_e($t["update_version"] ?? "") . "</span>" : "" ?></td>
												</tr>
											<?php } ?>
										</tbody>
									</table>
								</div>
								<?php if ((int) ($s["tema_guncelleme"] ?? 0) > 0) { ?>
									<form method="post" style="margin-top:6px"><?= $form($s, "guncelle", ["v_ne" => "temalar"]) ?><button type="submit" class="wd-mini-btn">Temaları güncelle</button></form>
								<?php } ?>
							</details>
						<?php } ?>
					</div>
				</details>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head">Nasıl Çalışır?</div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k">Yönetici girişi</span><span class="wd-v-small">Sitenize küçük bir "must-use" eklenti konur; panel 120 saniye geçerli, tek kullanımlık bir bağlantı üretir. Jeton dosyası yokken eklenti hiçbir şey yapmaz.</span></div>
					<div class="wd-kv"><span class="wd-k">Bütünlük</span><span class="wd-v-small">Çekirdek dosyalar wordpress.org'daki resmî sağlama toplamlarıyla karşılaştırılır. Fark, eklenti değil çekirdek dosya değişikliğidir; en sık nedeni zararlı kod enjeksiyonudur.</span></div>
					<div class="wd-kv"><span class="wd-k">Güncelleme</span><span class="wd-v-small">wp-cli site kullanıcısıyla çalışır; güncellemeden önce Yedekler sayfasından yedek almanız önerilir.</span></div>
					<div class="wd-kv"><span class="wd-k">Tarama</span><span class="wd-v-small">Liste her gece yenilenir; "Yeniden Tara" anında yeniler. Her işlemden sonra ilgili site tek başına tazelenir.</span></div>
				</div>
			</div>
		</aside>
	</div>
</div>
