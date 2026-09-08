<?php
/**
 * WebDanışmanı — "Node.js Uygulamaları" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_node.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$bos_doms = array_filter(array_keys($wd_doms), fn($d) => !in_array($d, $wd_kullanilan, true));
$durum_rozet = function (string $d): string {
	if ($d === "active") {
		return '<span class="wdm-rozet wdm-rozet-ok"><span class="wdm-nokta wdm-nokta-ok"></span>çalışıyor</span>';
	}
	if ($d === "activating") {
		return '<span class="wdm-rozet wdm-rozet-warn">başlıyor</span>';
	}
	return '<span class="wdm-rozet wdm-rozet-err"><span class="wdm-nokta wdm-nokta-err"></span>' . wd_e($d) . "</span>";
};
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik("Node.js Uygulamaları", "Uygulamanız kendi hesabınızla servis olarak çalışır, çökünce yeniden başlar ve alan adınıza bağlanır."); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>
			<?php if ($wd_node === "") {
				wd_modul_not("Sunucuda Node.js kurulu değil. Yönetici NodeSource deposundan nodejs kurduğunda bu sayfa etkinleşir.", "warn");
			} ?>

			<?php if ($wd_gunluk !== null) { ?>
				<div class="wd-card">
					<div class="wd-card-head">Günlük · <?= wd_e($wd_gunluk["domain"]) ?> <?= $durum_rozet((string) ($wd_gunluk["durum"] ?? "")) ?></div>
					<div class="wd-card-body wd-card-body-pad">
						<?php if (!empty($wd_gunluk["journal"])) { ?><p class="wdm-ipucu">systemd:</p><pre class="wdm-kod wdm-kod-kucuk"><?= wd_e(implode("\n", (array) $wd_gunluk["journal"])) ?></pre><?php } ?>
						<p class="wdm-ipucu" style="margin-top:6px">Uygulama çıktısı (private/node.log):</p>
						<pre class="wdm-kod"><?= wd_e(implode("\n", (array) ($wd_gunluk["satirlar"] ?? [])) ?: "(boş)") ?></pre>
					</div>
				</div>
			<?php } ?>

			<?php if (!empty($wd_uygulamalar)) { ?>
				<div class="wd-card">
					<div class="wd-card-head">Çalışan uygulamalar</div>
					<div class="wdm-liste">
						<?php foreach ($wd_uygulamalar as $a) { ?>
							<div class="wdm-oge">
								<div class="wdm-oge-bas">
									<span class="wdm-oge-ad"><a href="https://<?= wd_e($a["domain"]) ?>/" target="_blank" rel="noopener"><?= wd_e($a["domain"]) ?></a> <?= $durum_rozet((string) ($a["durum"] ?? "")) ?></span>
									<span class="wdm-oge-alt"><span class="wdm-mono"><?= wd_e(($a["alt_dizin"] ? $a["alt_dizin"] . "/" : "") . $a["giris"]) ?></span> · 127.0.0.1:<?= (int) $a["port"] ?> · <?= wd_e(wd_modul_tarih($a["ts"] ?? null)) ?></span>
								</div>
								<div class="wdm-oge-eylem">
									<form method="post"><?= wd_modul_form_gizli("gunluk", $a["domain"]) ?><button type="submit" class="wd-mini-btn">Günlük</button></form>
									<form method="post"><?= wd_modul_form_gizli("yeniden", $a["domain"]) ?><button type="submit" class="wd-mini-btn"><i class="fas fa-rotate-right"></i> Yeniden başlat</button></form>
									<form method="post" onsubmit="return confirm('<?= wd_e($a["domain"]) ?> için servis durdurulup kaldırılacak; alan adı stok PHP şablonuna döner. Dosyalar silinmez. Devam?');"><?= wd_modul_form_gizli("kaldir", $a["domain"]) ?><button type="submit" class="wd-mini-btn wd-mini-btn-danger">Kaldır</button></form>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>

			<?php if (!empty($bos_doms) && $wd_node !== "") { ?>
				<form method="post" class="wdm-form">
					<?= wd_modul_form_gizli("ekle") ?>
					<div class="wd-card">
						<div class="wd-card-head">Yeni uygulama</div>
						<div class="wd-card-body wd-card-body-pad">
							<div class="wdm-satir">
								<div class="wdm-alan">
									<label class="form-label" for="v_domain">Alan adı</label>
									<select class="form-select" id="v_domain" name="v_domain">
										<?php foreach ($bos_doms as $d) { ?><option value="<?= wd_e($d) ?>"><?= wd_e($d) ?></option><?php } ?>
									</select>
									<span class="wdm-ipucu">Bu alan adının tüm istekleri uygulamaya gider; PHP çalışmaz.</span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_giris">Giriş dosyası</label>
									<input class="form-control wdm-mono" id="v_giris" name="v_giris" value="app.js" pattern="[A-Za-z0-9._/-]{1,120}">
									<span class="wdm-ipucu">server.js, index.js, dist/main.js …</span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_alt">Alt dizin <span class="wdm-ipucu">boş = public_html</span></label>
									<input class="form-control wdm-mono" id="v_alt" name="v_alt" placeholder="api">
								</div>
							</div>
							<label class="wd-secim" style="margin-top:8px"><input type="checkbox" name="v_npm" value="1" checked><span><b>package.json varsa bağımlılıkları kur</b><span class="wd-v-small">npm install --omit=dev (node_modules yoksa)</span></span></label>
						</div>
					</div>
					<div class="wdm-dugmeler"><button type="submit" class="button"><i class="fas fa-play"></i> Başlat</button></div>
				</form>
			<?php } elseif ($wd_node !== "" && empty($bos_doms)) { ?>
				<?php wd_modul_not("Tüm alan adlarınızda uygulama tanımlı ya da alan adınız yok."); ?>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head">Nasıl Çalışır?</div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k">Port</span><span class="wd-v-small">Uygulamanıza <span class="wd-mono">PORT</span> ortam değişkeni verilir (3000-3999 arası, otomatik). Kodunuz <span class="wd-mono">process.env.PORT</span>'u dinlemeli ve <span class="wd-mono">127.0.0.1</span>'e bağlanmalı.</span></div>
					<div class="wd-kv"><span class="wd-k">Servis</span><span class="wd-v-small">systemd birimi olarak, hesabınızın kullanıcısıyla çalışır; çökerse 5 saniyede yeniden başlar; sunucu açılışında otomatik başlar.</span></div>
					<div class="wd-kv"><span class="wd-k">Nginx</span><span class="wd-v-small">Alan adı "wd-node" şablonuna alınır; WebSocket dahil tüm istekler uygulamaya proxylenir. SSL ve Let's Encrypt aynen çalışır.</span></div>
					<div class="wd-kv"><span class="wd-k">Günlük</span><span class="wd-v-small"><span class="wd-mono">private/node.log</span> — Dosya Yöneticisi'nden de okunabilir.</span></div>
					<div class="wd-kv"><span class="wd-k">Node sürümü</span><span class="wd-v-small"><?= $wd_node !== "" ? wd_e($wd_node) : "kurulu değil" ?></span></div>
				</div>
			</div>
		</aside>
	</div>
</div>
