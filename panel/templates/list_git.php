<?php
/**
 * WebDanışmanı — "Git Dağıtım" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_git.php
 */
wd_modul_css();
$tok = $_SESSION["token"] ?? "";
$gizli = function (string $islem, array $r, array $ek = []): string {
	$s = wd_modul_form_gizli($islem, $r["domain"]) . '<input type="hidden" name="v_alt" value="' . wd_e($r["alt_dizin"] ?? "") . '">';
	foreach ($ek as $k => $v) {
		$s .= '<input type="hidden" name="' . wd_e($k) . '" value="' . wd_e($v) . '">';
	}
	return $s;
};
?>

<div class="container">
	<div class="wd-page">
		<div class="wd-main">

			<?php wd_modul_baslik("Git Dağıtım", "Depoyu siteye klonlayın; değişiklikleri tek tıkla ya da otomatik çekin."); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>

			<?php if (!empty($wd_depolar)) { ?>
				<div class="wd-card">
					<div class="wd-card-head">Bağlı depolar</div>
					<div class="wdm-liste">
						<?php foreach ($wd_depolar as $r) {
							$g = $r["git"] ?? ["var" => false]; ?>
							<div class="wdm-oge">
								<div class="wdm-oge-bas">
									<span class="wdm-oge-ad"><?= wd_e($r["domain"]) ?><?= !empty($r["alt_dizin"]) ? "/" . wd_e($r["alt_dizin"]) : "" ?> <?= !empty($r["otomatik"]) ? '<span class="wdm-rozet wdm-rozet-info">otomatik</span>' : "" ?><?= empty($g["var"]) ? '<span class="wdm-rozet wdm-rozet-err">.git yok</span>' : "" ?></span>
									<span class="wdm-oge-alt"><span class="wdm-mono"><?= wd_e($r["repo_goster"] ?? $r["repo"]) ?></span><?= !empty($r["dal"]) ? " · dal " . wd_e($r["dal"]) : "" ?></span>
									<?php if (!empty($g["var"])) { ?>
										<span class="wdm-oge-alt"><span class="wdm-mono"><?= wd_e($g["commit"]) ?></span> <?= wd_e($g["mesaj"]) ?> · <?= wd_e($g["yazar"]) ?> · <?= wd_e($g["tarih"]) ?><?= (int) ($g["degisen"] ?? 0) > 0 ? ' · <span style="color:var(--wd-amber)">' . (int) $g["degisen"] . " yerel değişiklik</span>" : "" ?></span>
									<?php } ?>
									<?php if (!empty($r["son_cekim"])) { ?><span class="wdm-eskime">son çekim <?= wd_e(wd_modul_tarih((int) $r["son_cekim"])) ?> · <?= wd_e($r["son_sonuc"] ?? "") ?></span><?php } ?>
								</div>
								<div class="wdm-oge-eylem">
									<form method="post"><?= $gizli("cek", $r) ?><button type="submit" class="wd-mini-btn"><i class="fas fa-download"></i> Çek</button></form>
									<form method="post"><?= $gizli("otomatik", $r, ["v_deger" => !empty($r["otomatik"]) ? "off" : "on"]) ?><button type="submit" class="wd-mini-btn"><?= !empty($r["otomatik"]) ? "Otomatiği kapat" : "Otomatik çek" ?></button></form>
									<form method="post" onsubmit="return confirm('Kayıt kaldırılacak; dosyalar ve .git dizini yerinde kalır. Devam?');"><?= $gizli("kaldir", $r) ?><button type="submit" class="wd-mini-btn wd-mini-btn-danger">Kaldır</button></form>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>

			<?php if (empty($wd_doms)) { ?>
				<?php wd_modul_not("Henüz web alan adınız yok."); ?>
			<?php } else { ?>
				<form method="post" class="wdm-form">
					<?= wd_modul_form_gizli("klonla") ?>
					<div class="wd-card">
						<div class="wd-card-head">Depo bağla</div>
						<div class="wd-card-body wd-card-body-pad">
							<div class="wdm-satir">
								<div class="wdm-alan wdm-alan-genis">
									<label class="form-label" for="v_repo">Depo adresi</label>
									<input class="form-control wdm-mono" id="v_repo" name="v_repo" required placeholder="https://github.com/kullanici/depo.git ya da git@github.com:kullanici/depo.git">
									<span class="wdm-ipucu">Özel depo için <b>git@</b> adresi kullanın ve sağdaki deploy anahtarını depoya ekleyin.</span>
								</div>
							</div>
							<div class="wdm-satir" style="margin-top:10px">
								<div class="wdm-alan">
									<label class="form-label" for="v_domain">Alan adı</label>
									<select class="form-select" id="v_domain" name="v_domain">
										<?php foreach ($wd_doms as $d => $_) { ?><option value="<?= wd_e($d) ?>"><?= wd_e($d) ?></option><?php } ?>
									</select>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_alt">Alt dizin <span class="wdm-ipucu">boş = site kökü</span></label>
									<input class="form-control wdm-mono" id="v_alt" name="v_alt" placeholder="api">
									<span class="wdm-ipucu">Hedef boş olmalı.</span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_dal">Dal <span class="wdm-ipucu">boş = varsayılan</span></label>
									<input class="form-control wdm-mono" id="v_dal" name="v_dal" placeholder="main">
								</div>
							</div>
						</div>
					</div>
					<div class="wdm-dugmeler"><button type="submit" class="button"><i class="fas fa-code-branch"></i> Klonla</button></div>
				</form>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head">Deploy Anahtarı</div>
				<div class="wd-card-body wd-card-body-pad">
					<?php if ($wd_anahtar !== "") { ?>
						<p class="wdm-ipucu" style="margin:0 0 6px">Özel depolar için bu açık anahtarı GitHub → Settings → Deploy keys (ya da GitLab → Deploy Keys) bölümüne ekleyin. Yalnızca okuma yetkisi yeterlidir.</p>
						<pre class="wdm-kod wdm-kod-kucuk" id="wdGitAnahtar"><?= wd_e($wd_anahtar) ?></pre>
						<button type="button" class="wd-mini-btn" style="margin-top:6px" onclick="var t=document.getElementById('wdGitAnahtar').textContent;(navigator.clipboard?navigator.clipboard.writeText(t):Promise.reject()).then(function(){},function(){window.prompt('Kopyalayın:',t);});">Kopyala</button>
					<?php } else { ?>
						<p class="wd-empty">Anahtar üretilemedi.</p>
					<?php } ?>
				</div>
			</div>
			<div class="wd-card">
				<div class="wd-card-head">Nasıl Çalışır?</div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k">Klon</span><span class="wd-v-small">Depo sığ (depth 1) olarak, hesabınızın kullanıcısıyla klonlanır. Derleme adımı (composer, npm build) gerekiyorsa Web Terminali'nden çalıştırın.</span></div>
					<div class="wd-kv"><span class="wd-k">Çekme</span><span class="wd-v-small"><span class="wd-mono">git pull --ff-only</span>: sunucuda elle değiştirilmiş dosyalar varsa çekme reddedilir; önce değişiklikleri depoya alın.</span></div>
					<div class="wd-kv"><span class="wd-k">Otomatik</span><span class="wd-v-small">5 dakikada bir çekilir. Webhook yerine bu yöntem seçildi: sunucuya açık bir uç nokta gerekmez.</span></div>
					<div class="wd-kv"><span class="wd-k">Güvenlik</span><span class="wd-v-small"><span class="wd-mono">.git</span> dizini web'den erişime kapalıdır (nginx dotfile kuralı).</span></div>
				</div>
			</div>
		</aside>
	</div>
</div>
