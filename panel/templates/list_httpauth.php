<?php
/**
 * WebDanışmanı — "Dizin Şifre Koruma" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_httpauth.php
 */

$tok = $_SESSION["token"] ?? "";

/** AUTH_USER alanı iki nokta ile ayrılmış bir listedir: "ali:veli" */
$wd_auth_kullanicilar = function ($rec) {
	$ham = trim((string) ($rec["AUTH_USER"] ?? ""));
	if ($ham === "") {
		return [];
	}
	return array_values(array_filter(array_map("trim", explode(":", $ham)), "strlen"));
};

$wd_korumali = 0;
foreach ($wd_doms as $rec) {
	if ($wd_auth_kullanicilar($rec)) {
		$wd_korumali++;
	}
}
?>

<div class="container">
	<div class="wd-page">

		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title">Dizin Şifre Koruma</h1>
					<p class="wd-subtitle">
						Siteyi ziyaret edenlerden tarayıcı üzerinden kullanıcı adı ve parola ister.
						Yayına almadan önceki siteler ve iç kullanıma açık alanlar için.
					</p>
				</div>
			</div>

			<?php if ($wd_hata !== "") { ?>
				<div class="wd-note wd-note-err">
					<i class="fas fa-circle-exclamation"></i>
					<span><?= wd_e($wd_hata) ?></span>
				</div>
			<?php } elseif ($wd_bilgi !== "") { ?>
				<div class="wd-note wd-note-ok">
					<i class="fas fa-circle-check"></i>
					<span><?= wd_e($wd_bilgi) ?></span>
				</div>
			<?php } ?>

			<?php if (empty($wd_doms)) { ?>

				<div class="wd-note">
					<i class="fas fa-circle-info"></i>
					<span>Henüz web alan adınız yok. Alan adı ekledikten sonra burada görünür.</span>
				</div>

			<?php } else {
    foreach ($wd_doms as $dad => $rec) {
    	$kullanicilar = $wd_auth_kullanicilar($rec);
    	$acik = !empty($kullanicilar); ?>

				<details class="wd-group" <?= $acik ? "open" : "" ?>>
					<summary class="wd-group-head">
						<span class="wd-group-icon"><i class="fas <?= $acik ? "fa-lock" : "fa-lock-open" ?>"></i></span>
						<span class="wd-group-title"><?= wd_e($dad) ?></span>
						<?php if ($acik) { ?>
							<span class="wd-hs-badge wd-hs-ok">Korumalı</span>
						<?php } else { ?>
							<span class="wd-group-count">koruma yok</span>
						<?php } ?>
						<i class="fas fa-chevron-down wd-group-chevron"></i>
					</summary>

					<div class="wd-auth-body">

						<?php if ($acik) { ?>
							<div class="wd-auth-list">
								<?php foreach ($kullanicilar as $au) { ?>
									<div class="wd-auth-row">
										<span class="wd-auth-ad">
											<i class="fas fa-user"></i><?= wd_e($au) ?>
										</span>
										<div class="wd-auth-actions">
											<form method="post" class="wd-inline-form"
												onsubmit="return wdParolaSor(this);">
												<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
												<input type="hidden" name="ok" value="1">
												<input type="hidden" name="islem" value="parola">
												<input type="hidden" name="v_domain" value="<?= wd_e($dad) ?>">
												<input type="hidden" name="v_auth_user" value="<?= wd_e($au) ?>">
												<input type="hidden" name="v_password" value="">
												<button type="submit" class="wd-mini-btn">Parolayı değiştir</button>
											</form>
											<form method="post" class="wd-inline-form"
												onsubmit="return confirm('<?= wd_e($au) ?> kullanıcısı silinsin mi? Bu kullanıcı artık siteye giremez.');">
												<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
												<input type="hidden" name="ok" value="1">
												<input type="hidden" name="islem" value="sil">
												<input type="hidden" name="v_domain" value="<?= wd_e($dad) ?>">
												<input type="hidden" name="v_auth_user" value="<?= wd_e($au) ?>">
												<button type="submit" class="wd-mini-btn wd-mini-btn-danger">Sil</button>
											</form>
										</div>
									</div>
								<?php } ?>
							</div>
						<?php } else { ?>
							<p class="wd-empty">
								Bu site herkese açık. Aşağıdan kullanıcı ekleyerek parola koruması başlatabilirsiniz.
							</p>
						<?php } ?>

						<form method="post" class="wd-auth-form">
							<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
							<input type="hidden" name="ok" value="1">
							<input type="hidden" name="islem" value="ekle">
							<input type="hidden" name="v_domain" value="<?= wd_e($dad) ?>">
							<div class="wd-auth-field">
								<label class="form-label" for="au_<?= wd_e($dad) ?>">Kullanıcı adı</label>
								<input class="form-control" type="text" id="au_<?= wd_e($dad) ?>"
									name="v_auth_user" autocomplete="off" required
									pattern="[A-Za-z0-9._\-]{2,32}"
									title="2-32 karakter; harf, rakam, nokta, alt çizgi veya tire">
							</div>
							<div class="wd-auth-field">
								<label class="form-label" for="ap_<?= wd_e($dad) ?>">Parola</label>
								<input class="form-control" type="password" id="ap_<?= wd_e($dad) ?>"
									name="v_password" autocomplete="new-password" required minlength="8"
									title="En az 8 karakter">
							</div>
							<button type="submit" class="button">Koruma Ekle</button>
						</form>

					</div>
				</details>

			<?php }
   } ?>

		</div>

		<!-- ================= SAĞ PANEL ================= -->
		<aside class="wd-rail">

			<div class="wd-card">
				<div class="wd-card-head">Özet</div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">Alan Adı</span>
						<span class="wd-v"><?= count($wd_doms) ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Korumalı</span>
						<span class="wd-v"><?= (int) $wd_korumali ?></span>
					</div>
				</div>
			</div>

			<div class="wd-card">
				<div class="wd-card-head">Bilmeniz Gerekenler</div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">Neyi korur</span>
						<span class="wd-v-small">
							Sitenin <b>tamamını</b>. Tarayıcı, sayfa açılmadan önce kullanıcı adı ve
							parola sorar.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Arama motorları</span>
						<span class="wd-v-small">
							Korumalı site taranamaz ve dizine eklenmez. Yayına aldığınızda
							korumayı kaldırmayı unutmayın.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Şifreleme</span>
						<span class="wd-v-small">
							Bu yöntem parolayı her istekte gönderir; <b>HTTPS olmadan</b>
							ağda okunabilir. Sitenizde SSL etkin olsun.
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">Yeterli mi</span>
						<span class="wd-v-small">
							Hassas veri için tek başına yeterli değildir — uygulama içi
							oturum açma yerine geçmez.
						</span>
					</div>
				</div>
			</div>

		</aside>
	</div>
</div>

<script>
	// Parola değiştirme: satır içi form gizli bir alan taşır, parola sorulur.
	// prompt() kullanılmasının sebebi tek alanlık bir işlem için ayrı bir sayfa
	// veya kip açmanın gereksiz olması.
	function wdParolaSor(form) {
		var p = window.prompt("Yeni parola (en az 8 karakter):");
		if (p === null) { return false; }
		if (p.length < 8) {
			window.alert("Parola en az 8 karakter olmalı.");
			return false;
		}
		form.elements["v_password"].value = p;
		return true;
	}
</script>
