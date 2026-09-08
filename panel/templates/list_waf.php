<?php
/**
 * WebDanışmanı — "Uygulama Güvenlik Duvarı" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_waf.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$k = $wd_waf["kurallar"] ?? [];
$secim = function (string $ad, string $anahtar, string $baslik, string $aciklama, bool $varsayilan = false) use ($k) {
	$acik = array_key_exists($anahtar, $k) ? !empty($k[$anahtar]) : $varsayilan;
	echo '<label class="wd-secim"><input type="checkbox" name="' . wd_e($ad) . '" value="1"' . ($acik ? " checked" : "") . ">";
	echo "<span><b>" . wd_e($baslik) . '</b><span class="wd-v-small">' . wd_e($aciklama) . "</span></span></label>\n";
};
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik("Uygulama Güvenlik Duvarı", "Kötü botları, enjeksiyon kalıplarını ve giriş sayfasına kaba kuvveti nginx katmanında keser."); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>

			<?php if (empty($wd_doms)) { ?>
				<?php wd_modul_not("Henüz web alan adınız yok."); ?>
			<?php } elseif ($wd_waf === null) { ?>
				<?php wd_modul_not("Kurallar okunamadı.", "warn"); ?>
			<?php } else { ?>
				<?php wd_modul_domain_kutusu($wd_doms, $wd_domain, "/list/waf/"); ?>

				<?php if ($wd_ist && !empty($wd_ist["var"])) { ?>
					<div class="wd-stats">
						<div class="wd-stat"><div class="wd-stat-label">ENGELLENEN (24 SA)</div><div class="wd-stat-value"><?= (int) ($wd_ist["engellenen"] ?? 0) ?><span class="wd-stat-of">403</span></div></div>
						<div class="wd-stat"><div class="wd-stat-label">HIZ SINIRI (24 SA)</div><div class="wd-stat-value"><?= (int) ($wd_ist["hiz"] ?? 0) ?><span class="wd-stat-of">429</span></div></div>
						<div class="wd-stat"><div class="wd-stat-label">DURUM</div><div class="wd-stat-value"><?= !empty($wd_waf["var"]) && !empty($k["etkin"]) ? '<span style="color:var(--wd-green)">Etkin</span>' : '<span style="color:var(--wd-muted)">Kapalı</span>' ?></div></div>
					</div>
				<?php } ?>

				<form method="post" class="wdm-form">
					<?= wd_modul_form_gizli("kaydet", $wd_domain) ?>
					<div class="wd-card">
						<div class="wd-card-head">Kurallar <span class="wd-card-note"><?= wd_e($wd_domain) ?></span></div>
						<div class="wd-card-body wd-card-body-pad">
							<?php $secim("v_etkin", "etkin", "Güvenlik duvarı etkin", "Kapatınca kurallar saklanır ama uygulanmaz.", true); ?>
							<?php $secim("v_bot", "bot_engel", "Kötü botları engelle", "AhrefsBot, SemrushBot, MJ12bot, sqlmap, nikto, wpscan gibi tarayıcı ve toplayıcılar 403 alır. Google/Bing etkilenmez.", true); ?>
							<?php $secim("v_arac", "arac_engel", "Komut satırı istemcilerini engelle", "curl, wget, python-requests, Go/Java istemcileri. Sitenizin API'si ya da webhook alıcısı varsa AÇMAYIN.", false); ?>
							<?php $secim("v_sorgu", "sorgu_engel", "Enjeksiyon kalıplarını engelle", "UNION SELECT, base64_decode(, <script, ../../, /etc/passwd, php://input gibi kalıplar içeren istekler 403 alır.", true); ?>
							<?php $secim("v_yukleme", "yukleme_php_engel", "Yükleme dizinlerinde PHP çalıştırmayı engelle", "uploads/, storage/, media/, images/ altına sızan bir PHP dosyası çalışamaz. Ele geçirilen sitelerin en yaygın kapısı.", true); ?>
							<?php $secim("v_hassas", "hassas_dosya_engel", "Hassas dosyaları gizle", ".sql, .bak, .old, .log, .sh, wp-config.php, readme.html, composer.json doğrudan indirilemez.", true); ?>
							<?php $secim("v_giris", "giris_koruma", "Giriş sayfası kaba kuvvet koruması", "wp-login.php, xmlrpc.php, /giris, /login gibi adreslere IP başına dakikada 30 istek; fazlası 429 alır." . (empty($wd_waf["giris_koruma_mumkun"]) ? " (Sunucuda hız sınırı bölgesi kurulu değil; yönetici kur-modul.sh çalıştırmalı.)" : ""), true); ?>
							<?php $secim("v_xmlrpc", "xmlrpc_kapat", "xmlrpc.php'yi kapat", "WordPress'te Jetpack veya mobil uygulama kullanmıyorsanız kapatın; kaba kuvvet ve DDoS yansıtma saldırılarının hedefidir.", false); ?>
							<div class="wd-auth-field wd-auth-field-genis" style="margin-top:10px">
								<label class="form-label" for="v_izinli_ip">Kural uygulanmayacak IP adresleri</label>
								<textarea class="form-control" id="v_izinli_ip" name="v_izinli_ip" rows="2" placeholder="Ofis IP'niz — 203.0.113.7"><?= wd_e(implode("\n", (array) ($k["izinli_ip"] ?? []))) ?></textarea>
								<span class="wdm-ipucu">Bot ve sorgu kuralları bu adreslere uygulanmaz. Her satıra tek IP.</span>
							</div>
						</div>
					</div>
					<div class="wdm-dugmeler">
						<button type="submit" class="button">Kaydet ve Yayına Al</button>
						<?php if (!empty($wd_waf["var"])) { ?>
							<button type="submit" name="islem" value="sil" class="button button-secondary"
								onclick="return confirm('Bu alan adının tüm WAF kuralları kaldırılacak. Devam?');">Kuralları Kaldır</button>
						<?php } ?>
					</div>
				</form>

				<?php if ($wd_ist && !empty($wd_ist["var"]) && (!empty($wd_ist["ipler"]) || !empty($wd_ist["yollar"]))) { ?>
					<div class="wdm-grid2">
						<div class="wd-card">
							<div class="wd-card-head">En çok engellenen IP'ler <span class="wd-card-note">24 saat</span></div>
							<div class="wdm-tablo-sar"><table class="wdm-tablo">
								<thead><tr><th>IP</th><th class="sag">İstek</th></tr></thead>
								<tbody>
									<?php foreach ((array) $wd_ist["ipler"] as $r) { ?>
										<tr><td class="mono"><?= wd_e($r["ip"]) ?></td><td class="sag"><?= (int) $r["adet"] ?></td></tr>
									<?php } ?>
								</tbody>
							</table></div>
						</div>
						<div class="wd-card">
							<div class="wd-card-head">Hedeflenen adresler <span class="wd-card-note">24 saat</span></div>
							<div class="wdm-tablo-sar"><table class="wdm-tablo">
								<thead><tr><th>Yol</th><th class="sag">İstek</th></tr></thead>
								<tbody>
									<?php foreach ((array) $wd_ist["yollar"] as $r) { ?>
										<tr><td class="mono"><?= wd_e($r["yol"]) ?></td><td class="sag"><?= (int) $r["adet"] ?></td></tr>
									<?php } ?>
								</tbody>
							</table></div>
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
						<span class="wd-k">Nerede çalışır</span>
						<span class="wd-v-small">Kurallar nginx'e yazılır; istek PHP'ye ulaşmadan kesilir. Sitenin kodu ve hızı etkilenmez.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Yanlış pozitif</span>
						<span class="wd-v-small">Bir eklenti çalışmaz olduysa önce "Komut satırı istemcileri" ve "Enjeksiyon kalıpları" seçeneklerini sırayla kapatıp deneyin. Kendi IP'nizi izinli listeye ekleyin.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Cloudflare</span>
						<span class="wd-v-small">Site Cloudflare arkasındaysa gerçek ziyaretçi IP'si için yöneticinin Cloudflare modülünde IP listesini güncel tutması gerekir; aksi hâlde hız sınırı tüm ziyaretçileri tek IP sayar.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Güvenli mi</span>
						<span class="wd-v-small">Her kayıtta nginx doğrulaması yapılır; geçersiz kural eski hâle döner.</span>
					</div>
				</div>
			</div>
		</aside>
	</div>
</div>
