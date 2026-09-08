<?php
/**
 * WebDanışmanı — "PHP Ayarları" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_phpayar.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$deg = $wd_ayar["degerler"] ?? [];
$tav = $wd_ayar["tavan"] ?? ["bellek_mb" => 512, "sure" => 300, "yukleme_mb" => 512, "input_vars" => 10000, "kademe" => null];
$acik = function ($k, $vars = "Off") use ($deg) {
	return strtolower((string) ($deg[$k] ?? $vars)) === "on";
};
$secenek_boyut = function ($secili, $tavan_mb) {
	$s = "";
	foreach ([2, 4, 8, 16, 32, 64, 128, 256, 384, 512, 1024] as $mb) {
		if ($mb > $tavan_mb) {
			break;
		}
		$v = $mb . "M";
		$s .= '<option value="' . $v . '"' . (strtoupper((string) $secili) === $v ? " selected" : "") . ">" . $v . "</option>";
	}
	return $s;
};
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik("PHP Ayarları", "Yükleme boyutu, zaman aşımı, bellek ve hata gösterimi — alan adı başına."); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			}
			foreach ($wd_notlar as $n) {
				wd_modul_not($n, "warn");
			} ?>

			<?php if (empty($wd_doms)) { ?>
				<?php wd_modul_not("Henüz web alan adınız yok."); ?>
			<?php } elseif ($wd_ayar === null) { ?>
				<?php wd_modul_not("Bu alan adı için ayarlar okunamadı.", "warn"); ?>
			<?php } else { ?>

				<?php wd_modul_domain_kutusu($wd_doms, $wd_domain, "/list/phpayar/"); ?>

				<form method="post" class="wdm-form">
					<?= wd_modul_form_gizli("kaydet", $wd_domain) ?>

					<div class="wd-card">
						<div class="wd-card-head">
							Sınırlar
							<span class="wd-card-note">PHP <?= wd_e($wd_ayar["php"] ?: "?") ?><?= $tav["kademe"] ? " · kademe: " . wd_e($tav["kademe"]) : "" ?></span>
						</div>
						<div class="wd-card-body wd-card-body-pad">
							<div class="wdm-satir">
								<div class="wdm-alan">
									<label class="form-label" for="v_upload">Dosya yükleme sınırı</label>
									<select class="form-select" id="v_upload" name="v_upload"><?= $secenek_boyut($deg["upload_max_filesize"] ?? "8M", $tav["yukleme_mb"]) ?></select>
									<span class="wdm-ipucu">upload_max_filesize · tavan <b><?= (int) $tav["yukleme_mb"] ?>M</b></span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_post">Form gönderim sınırı</label>
									<select class="form-select" id="v_post" name="v_post"><?= $secenek_boyut($deg["post_max_size"] ?? "10M", $tav["yukleme_mb"]) ?></select>
									<span class="wdm-ipucu">post_max_size · yükleme sınırından küçük olamaz</span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_memory">Bellek sınırı</label>
									<select class="form-select" id="v_memory" name="v_memory"><?= $secenek_boyut($deg["memory_limit"] ?? "128M", $tav["bellek_mb"]) ?></select>
									<span class="wdm-ipucu">memory_limit · tavan <b><?= (int) $tav["bellek_mb"] ?>M</b></span>
								</div>
							</div>
							<div class="wdm-satir" style="margin-top:12px">
								<div class="wdm-alan">
									<label class="form-label" for="v_exec">Çalışma süresi (sn)</label>
									<input class="form-control" type="number" id="v_exec" name="v_exec" min="1" max="<?= (int) $tav["sure"] ?>" value="<?= wd_e($deg["max_execution_time"] ?? "30") ?>">
									<span class="wdm-ipucu">max_execution_time · tavan <b><?= (int) $tav["sure"] ?> sn</b></span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_input">Girdi okuma süresi (sn)</label>
									<input class="form-control" type="number" id="v_input" name="v_input" min="-1" max="<?= (int) $tav["sure"] ?>" value="<?= wd_e($deg["max_input_time"] ?? "60") ?>">
									<span class="wdm-ipucu">max_input_time · -1 = çalışma süresiyle aynı</span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_vars">En çok form alanı</label>
									<input class="form-control" type="number" id="v_vars" name="v_vars" min="100" max="<?= (int) $tav["input_vars"] ?>" value="<?= wd_e($deg["max_input_vars"] ?? "1000") ?>">
									<span class="wdm-ipucu">max_input_vars · WooCommerce/menü kaydetme sorunlarında 3000+</span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_session">Oturum ömrü (sn)</label>
									<input class="form-control" type="number" id="v_session" name="v_session" min="300" max="604800" value="<?= wd_e($deg["session.gc_maxlifetime"] ?? "1440") ?>">
									<span class="wdm-ipucu">session.gc_maxlifetime · 1440 = 24 dk</span>
								</div>
							</div>
						</div>
					</div>

					<div class="wd-card">
						<div class="wd-card-head">Davranış</div>
						<div class="wd-card-body wd-card-body-pad">
							<div class="wdm-satir">
								<div class="wdm-alan">
									<label class="form-label" for="v_tz">Saat dilimi</label>
									<select class="form-select" id="v_tz" name="v_tz">
										<?php foreach ((array) ($wd_ayar["saat_dilimleri"] ?? ["Europe/Istanbul"]) as $tz) { ?>
											<option value="<?= wd_e($tz) ?>" <?= ($deg["date.timezone"] ?? "Europe/Istanbul") === $tz ? "selected" : "" ?>><?= wd_e($tz) ?></option>
										<?php } ?>
									</select>
									<span class="wdm-ipucu">date.timezone</span>
								</div>
							</div>
							<label class="wd-secim">
								<input type="checkbox" name="v_display" value="1" <?= $acik("display_errors") ? "checked" : "" ?>>
								<span><b>Hataları ekranda göster</b>
									<span class="wd-v-small">display_errors · Yalnızca geliştirme sırasında açın; ziyaretçiye dosya yolları sızar.</span></span>
							</label>
							<label class="wd-secim">
								<input type="checkbox" name="v_log" value="1" <?= $acik("log_errors") ? "checked" : "" ?>>
								<span><b>Hataları günlüğe yaz</b>
									<span class="wd-v-small">log_errors · Aşağıdaki "PHP Hata Günlüğü" kutusunda görünür. Önerilir.</span></span>
							</label>
							<label class="wd-secim">
								<input type="checkbox" name="v_short" value="1" <?= $acik("short_open_tag") ? "checked" : "" ?>>
								<span><b>Kısa açılış etiketi</b>
									<span class="wd-v-small">short_open_tag · Eski betikler <span class="wd-mono">&lt;?</span> kullanıyorsa açın.</span></span>
							</label>
						</div>
					</div>

					<?php if (!empty($wd_ayar["yabanci"])) { ?>
						<div class="wd-card">
							<div class="wd-card-head">Dosyadaki diğer satırlar <span class="wd-card-note">korunur</span></div>
							<div class="wd-card-body wd-card-body-pad">
								<pre class="wdm-kod wdm-kod-kucuk"><?= wd_e(implode("\n", (array) $wd_ayar["yabanci"])) ?></pre>
							</div>
						</div>
					<?php } ?>

					<div class="wdm-dugmeler">
						<button type="submit" class="button">Kaydet</button>
						<?php if (!empty($wd_ayar["var"])) { ?>
							<button type="submit" name="islem" value="sil" class="button button-secondary"
								onclick="return confirm('Özel PHP ayarları kaldırılacak, sunucu varsayılanlarına dönülecek. Devam?');">Varsayılana Dön</button>
						<?php } ?>
					</div>
				</form>

				<div class="wd-card">
					<div class="wd-card-head">
						PHP Hata Günlüğü
						<span class="wd-card-note"><?= !empty($wd_ayar["gunluk_boyut"]) ? wd_e(wd_modul_bayt((int) $wd_ayar["gunluk_boyut"])) : "boş" ?></span>
					</div>
					<div class="wd-card-body wd-card-body-pad">
						<?php if (empty($wd_gunluk)) { ?>
							<p class="wd-empty"><?= $acik("log_errors") ? "Günlük boş — kayıtlı hata yok." : "Günlük kapalı. \"Hataları günlüğe yaz\" seçeneğini açın." ?></p>
						<?php } else { ?>
							<pre class="wdm-kod"><?= wd_e(implode("\n", $wd_gunluk)) ?></pre>
							<form method="post" class="wdm-dugmeler" style="margin-top:8px">
								<?= wd_modul_form_gizli("gunluk-temizle", $wd_domain) ?>
								<button type="submit" class="button button-secondary">Günlüğü Temizle</button>
								<span class="wdm-ipucu">Yol: <span class="wd-mono"><?= wd_e($wd_ayar["gunluk_yolu"] ?? "") ?></span></span>
							</form>
						<?php } ?>
					</div>
				</div>

			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head">Nasıl Çalışır?</div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">Nereye yazılır</span>
						<span class="wd-v-small">Belge kökündeki <span class="wd-mono">.user.ini</span> dosyasına. Dosya size aittir; sunucu yeniden başlatılmaz.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Ne zaman geçerli olur</span>
						<span class="wd-v-small">PHP-FPM dosyayı en geç <b>5 dakika</b>da bir yeniden okur.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Tavanlar neden var</span>
						<span class="wd-v-small">Hesabınızın kaynak kademesi bellek ve süre için üst sınır koyar; kademeyi aşan değer sessizce çalışmaz. Bu sayfa tavanı gösterir ve aşanı indirir.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Kontrol</span>
						<span class="wd-v-small">Sitenize <span class="wd-mono">phpinfo()</span> içeren bir dosya koyup değeri doğrulayabilirsiniz; işiniz bitince silin.</span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>
