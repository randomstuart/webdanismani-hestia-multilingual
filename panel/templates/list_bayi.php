<?php
/**
 * WebDanışmanı — "Bayi Yönetimi" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_bayi.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$kota = function ($kul, $lim): string {
	$k = $lim === "unlimited" || $lim === "" ? "∞" : wd_modul_bayt((int) $lim * 1048576);
	return wd_modul_bayt((int) $kul * 1048576) . " / " . $k;
};
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik(
				$wd_is_admin ? "Bayi Yönetimi" : "Müşterilerim",
				$wd_is_admin ? "Bayileri tanımlayın: açabilecekleri paketler, müşteri sınırı ve bağlı hesaplar." : "Kendi müşteri hesaplarınızı açın, askıya alın, paket ve şifrelerini değiştirin.",
			); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>

			<?php if ($wd_is_admin && $wd_ayar !== null) { ?>
				<?php if (empty($wd_ayar["bayiler"])) { ?>
					<div class="wd-card"><div class="wdm-bos"><i class="fas fa-users-gear"></i>Henüz bayi tanımlı değil. Sağdaki kutudan bir kullanıcıyı bayi yapın.</div></div>
				<?php } ?>
				<?php foreach ((array) $wd_ayar["bayiler"] as $b) { ?>
					<div class="wd-card">
						<div class="wd-card-head">
							<span><b><?= wd_e($b["bayi"]) ?></b><?= !empty($b["ad"]) ? " · " . wd_e($b["ad"]) : "" ?> <span class="wd-card-note"><?= (int) $b["musteri_sayisi"] ?> / <?= (int) $b["azami"] ?> müşteri · paketler: <?= wd_e(implode(", ", (array) $b["paketler"]) ?: "tümü") ?></span></span>
							<form method="post" onsubmit="return confirm('<?= wd_e($b["bayi"]) ?> bayi tanımı kaldırılacak; müşteri hesapları silinmez. Devam?');"><?= wd_modul_form_gizli("bayi-sil") ?><input type="hidden" name="v_bayi" value="<?= wd_e($b["bayi"]) ?>"><button type="submit" class="wd-mini-btn wd-mini-btn-danger">Bayiliği kaldır</button></form>
						</div>
						<div class="wd-card-body wd-card-body-pad">
							<?php if (empty($b["musteriler"])) { ?><p class="wd-empty">Bağlı müşteri yok.</p><?php } else { ?>
								<div class="wdm-dugmeler">
									<?php foreach ((array) $b["musteriler"] as $m) { ?>
										<form method="post" style="display:inline-flex;align-items:center;gap:4px" onsubmit="return confirm('<?= wd_e($m) ?> bayiden ayrılacak (hesap silinmez). Devam?');">
											<?= wd_modul_form_gizli("bayi-cikar") ?><input type="hidden" name="v_bayi" value="<?= wd_e($b["bayi"]) ?>"><input type="hidden" name="v_kullanici" value="<?= wd_e($m) ?>">
											<span class="wd-limit-tag"><?= wd_e($m) ?> <button type="submit" style="border:0;background:none;cursor:pointer;color:var(--wd-red);padding:0 0 0 4px" title="Bayiden ayır">×</button></span>
										</form>
									<?php } ?>
								</div>
							<?php } ?>
							<form method="post" class="wdm-dugmeler" style="margin-top:10px">
								<?= wd_modul_form_gizli("bayi-ata") ?><input type="hidden" name="v_bayi" value="<?= wd_e($b["bayi"]) ?>">
								<select class="form-select" name="v_kullanici" style="height:28px;font-size:12px;max-width:240px">
									<?php foreach ((array) $wd_ayar["kullanicilar"] as $k) {
										if ($k === $b["bayi"] || $k === ($wd_ayar["yonetici"] ?? "admin") || in_array($k, (array) $b["musteriler"], true)) {
											continue;
										} ?>
										<option value="<?= wd_e($k) ?>"><?= wd_e($k) ?></option>
									<?php } ?>
								</select>
								<button type="submit" class="wd-mini-btn">Mevcut kullanıcıyı bağla</button>
							</form>
						</div>
					</div>
				<?php } ?>
			<?php } ?>

			<?php if ($wd_bayi_mi && $wd_liste !== null) { ?>
				<div class="wd-stats">
					<div class="wd-stat"><div class="wd-stat-label">MÜŞTERİ</div><div class="wd-stat-value"><?= count((array) $wd_liste["musteriler"]) ?><span class="wd-stat-of">/ <?= (int) $wd_liste["azami"] ?></span></div></div>
					<div class="wd-stat"><div class="wd-stat-label">ASKIDA</div><div class="wd-stat-value"><?= count(array_filter((array) $wd_liste["musteriler"], fn($m) => !empty($m["askida"]))) ?></div></div>
					<div class="wd-stat"><div class="wd-stat-label">PAKET SEÇENEĞİ</div><div class="wd-stat-value"><?= count((array) $wd_liste["paketler"]) ?></div></div>
				</div>

				<div class="wd-card">
					<div class="wd-card-head">Müşteri hesapları</div>
					<?php if (empty($wd_liste["musteriler"])) { ?><div class="wdm-bos"><i class="fas fa-users"></i>Henüz müşteriniz yok.</div><?php } else { ?>
						<div class="wdm-tablo-sar"><table class="wdm-tablo">
							<thead><tr><th>Hesap</th><th>Paket</th><th>Disk</th><th>Bant</th><th>Web / Mail / DB</th><th>Durum</th><th class="daralt"></th></tr></thead>
							<tbody>
								<?php foreach ((array) $wd_liste["musteriler"] as $m) { ?>
									<tr>
										<td><b><?= wd_e($m["kullanici"]) ?></b><br><span class="wdm-eskime"><?= wd_e($m["ad"]) ?> · <?= wd_e($m["eposta"]) ?></span></td>
										<td>
											<form method="post" class="wd-inline-form" style="display:flex;gap:4px;align-items:center">
												<?= wd_modul_form_gizli("paket") ?><input type="hidden" name="v_kullanici" value="<?= wd_e($m["kullanici"]) ?>">
												<select class="form-select" name="v_paket" style="height:26px;font-size:11.5px" onchange="this.form.submit()">
													<?php foreach ((array) $wd_liste["paketler"] as $p) { ?><option value="<?= wd_e($p["ad"]) ?>" <?= $p["ad"] === $m["paket"] ? "selected" : "" ?>><?= wd_e($p["ad"]) ?></option><?php } ?>
													<?php if (!in_array($m["paket"], array_map(fn($p) => $p["ad"], (array) $wd_liste["paketler"]), true)) { ?><option value="<?= wd_e($m["paket"]) ?>" selected><?= wd_e($m["paket"]) ?></option><?php } ?>
												</select>
											</form>
										</td>
										<td class="mono"><?= wd_e($kota($m["disk"], $m["disk_kota"])) ?></td>
										<td class="mono"><?= wd_e($kota($m["bant"], $m["bant_kota"])) ?></td>
										<td class="mono"><?= (int) $m["web"] ?> / <?= (int) $m["mail"] ?> / <?= (int) $m["db"] ?></td>
										<td><?= !empty($m["askida"]) ? '<span class="wdm-rozet wdm-rozet-warn">askıda</span>' : '<span class="wdm-rozet wdm-rozet-ok">etkin</span>' ?></td>
										<td class="daralt">
											<div class="wdm-oge-eylem">
												<?php if (!empty($m["askida"])) { ?>
													<form method="post"><?= wd_modul_form_gizli("askidan-cikar") ?><input type="hidden" name="v_kullanici" value="<?= wd_e($m["kullanici"]) ?>"><button type="submit" class="wd-mini-btn">Aç</button></form>
												<?php } else { ?>
													<form method="post" onsubmit="return confirm('<?= wd_e($m["kullanici"]) ?> askıya alınacak; siteleri ve mailleri durur. Devam?');"><?= wd_modul_form_gizli("askiya") ?><input type="hidden" name="v_kullanici" value="<?= wd_e($m["kullanici"]) ?>"><button type="submit" class="wd-mini-btn">Askıya al</button></form>
												<?php } ?>
												<form method="post" onsubmit="var s=prompt('<?= wd_e($m["kullanici"]) ?> için yeni şifre (en az 8 karakter):');if(!s){return false;}this.querySelector('[name=v_sifre]').value=s;return true;"><?= wd_modul_form_gizli("sifre") ?><input type="hidden" name="v_kullanici" value="<?= wd_e($m["kullanici"]) ?>"><input type="hidden" name="v_sifre" value=""><button type="submit" class="wd-mini-btn">Şifre</button></form>
												<form method="post" onsubmit="return confirm('DİKKAT: <?= wd_e($m["kullanici"]) ?> hesabı, tüm siteleri, mailleri ve veritabanlarıyla KALICI olarak silinecek. Devam?');"><?= wd_modul_form_gizli("sil") ?><input type="hidden" name="v_kullanici" value="<?= wd_e($m["kullanici"]) ?>"><button type="submit" class="wd-mini-btn wd-mini-btn-danger">Sil</button></form>
											</div>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table></div>
					<?php } ?>
				</div>

				<?php if (count((array) $wd_liste["musteriler"]) < (int) $wd_liste["azami"]) { ?>
					<form method="post" class="wdm-form">
						<?= wd_modul_form_gizli("ekle") ?>
						<div class="wd-card">
							<div class="wd-card-head">Yeni müşteri hesabı</div>
							<div class="wd-card-body wd-card-body-pad">
								<div class="wdm-satir">
									<div class="wdm-alan"><label class="form-label" for="v_yeni_kullanici">Kullanıcı adı</label><input class="form-control wdm-mono" id="v_yeni_kullanici" name="v_yeni_kullanici" required pattern="[a-z][a-z0-9_-]{1,31}" placeholder="musteri1"></div>
									<div class="wdm-alan"><label class="form-label" for="v_sifre">Şifre</label><input class="form-control wdm-mono" type="password" id="v_sifre" name="v_sifre" required minlength="8" autocomplete="new-password"></div>
									<div class="wdm-alan"><label class="form-label" for="v_eposta">E-posta</label><input class="form-control" type="email" id="v_eposta" name="v_eposta" required></div>
								</div>
								<div class="wdm-satir" style="margin-top:10px">
									<div class="wdm-alan"><label class="form-label" for="v_ad">Ad Soyad</label><input class="form-control" id="v_ad" name="v_ad"></div>
									<div class="wdm-alan"><label class="form-label" for="v_paket">Paket</label>
										<select class="form-select" id="v_paket" name="v_paket">
											<?php foreach ((array) $wd_liste["paketler"] as $p) { ?><option value="<?= wd_e($p["ad"]) ?>"><?= wd_e($p["ad"]) ?> — disk <?= wd_e($p["disk"] === "unlimited" ? "∞" : $p["disk"] . " MB") ?>, <?= wd_e($p["web"]) ?> site, <?= wd_e($p["mail"]) ?> mail, <?= wd_e($p["db"]) ?> DB</option><?php } ?>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="wdm-dugmeler"><button type="submit" class="button"><i class="fas fa-user-plus"></i> Hesabı Aç</button></div>
					</form>
				<?php } else { ?>
					<?php wd_modul_not("Müşteri sınırınıza ulaştınız. Artırmak için yöneticiye başvurun.", "warn"); ?>
				<?php } ?>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<?php if ($wd_is_admin && $wd_ayar !== null) {
				$wd_adaylar = array_values(array_filter((array) ($wd_ayar["kullanicilar"] ?? []), fn($k) => $k !== ($wd_ayar["yonetici"] ?? "admin"))); ?>
				<div class="wd-card">
					<div class="wd-card-head">Bayi tanımla / güncelle</div>
					<div class="wd-card-body wd-card-body-pad">
						<?php if (empty($wd_adaylar)) { ?>
							<?php wd_modul_not("Bayi yapılacak bir kullanıcı yok. Önce Kullanıcılar sayfasından bayi için bir hesap açın; sonra burada seçin.", "warn"); ?>
							<div class="wdm-dugmeler"><a class="button button-secondary" href="/add/user/">Kullanıcı Ekle</a></div>
						<?php } else { ?>
						<form method="post" class="wdm-form">
							<?= wd_modul_form_gizli("bayi-ekle") ?>
							<div class="wdm-alan"><label class="form-label" for="v_bayi">Kullanıcı</label>
								<select class="form-select" id="v_bayi" name="v_bayi">
									<?php foreach ($wd_adaylar as $k) { ?>
										<option value="<?= wd_e($k) ?>"><?= wd_e($k) ?></option>
									<?php } ?>
								</select>
								<span class="wdm-ipucu">Bayi, kendi hesabıyla panele girer ve "Müşterilerim" sayfasını görür.</span>
							</div>
							<div class="wdm-alan"><label class="form-label" for="v_ad">Bayi adı</label><input class="form-control" id="v_ad" name="v_ad" placeholder="Firma / kişi"></div>
							<div class="wdm-alan"><label class="form-label" for="v_azami">En çok müşteri</label><input class="form-control" type="number" id="v_azami" name="v_azami" value="10" min="1" max="500"></div>
							<div class="wdm-alan"><label class="form-label">Açabileceği paketler <span class="wdm-ipucu">hiçbiri seçilmezse tümü</span></label>
								<?php foreach ((array) $wd_ayar["paketler"] as $p) { ?>
									<label class="wd-secim" style="padding:4px 0"><input type="checkbox" name="v_paketler[]" value="<?= wd_e($p) ?>"><span><?= wd_e($p) ?></span></label>
								<?php } ?>
							</div>
							<div class="wdm-dugmeler"><button type="submit" class="button">Kaydet</button></div>
						</form>
						<?php } ?>
					</div>
				</div>
			<?php } ?>
			<div class="wd-card">
				<div class="wd-card-head">Nasıl Çalışır?</div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k">Yetki</span><span class="wd-v-small">Bayi yalnızca kendisine bağlı hesapları görür ve yönetir; başka kullanıcıları listeleyemez, sunucu ayarlarına giremez.</span></div>
					<div class="wd-kv"><span class="wd-k">Paket kısıtı</span><span class="wd-v-small">Yönetici bayinin hangi paketleri açabileceğini seçer; bayi başka paket atayamaz.</span></div>
					<div class="wd-kv"><span class="wd-k">Müşteri girişi</span><span class="wd-v-small">Müşteri kendi kullanıcı adı ve şifresiyle bu panele girer. Bayi şifreyi sıfırlayabilir ama müşteri adına oturum açamaz.</span></div>
					<div class="wd-kv"><span class="wd-k">Kaynak</span><span class="wd-v-small">Müşteri hesapları sunucu kotalarını paylaşır; bayi sınırı ve paket kotaları toplam kullanımı sınırlar.</span></div>
				</div>
			</div>
		</aside>
	</div>
</div>
