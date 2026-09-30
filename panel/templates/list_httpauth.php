<?php
/**
 * WebDanışmanı — "Directory Password Protection" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_httpauth.php
 */

$tok = $_SESSION["token"] ?? "";

/** AUTH_USER is a colon-separated list: "ali:veli" */
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
					<h1 class="wd-title"><?= wd_esc__("Directory Password Protection") ?></h1>
					<p class="wd-subtitle">
						<?= wd_esc__("Asks visitors for a username and password in the browser. For sites not yet live and areas meant for internal use.") ?>
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
					<span><?= wd_esc__("You have no web domains yet. After you add a domain it appears here.") ?></span>
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
							<span class="wd-hs-badge wd-hs-ok"><?= wd_esc__("Protected") ?></span>
						<?php } else { ?>
							<span class="wd-group-count"><?= wd_esc__("no protection") ?></span>
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
												<button type="submit" class="wd-mini-btn"><?= wd_esc__("Change password") ?></button>
											</form>
											<form method="post" class="wd-inline-form"
												onsubmit="return confirm(<?= htmlspecialchars(json_encode(sprintf(wd__("Delete user %s? They will no longer be able to access the site."), $au)), ENT_QUOTES, "UTF-8") ?>);">
												<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
												<input type="hidden" name="ok" value="1">
												<input type="hidden" name="islem" value="sil">
												<input type="hidden" name="v_domain" value="<?= wd_e($dad) ?>">
												<input type="hidden" name="v_auth_user" value="<?= wd_e($au) ?>">
												<button type="submit" class="wd-mini-btn wd-mini-btn-danger"><?= wd_esc__("Delete") ?></button>
											</form>
										</div>
									</div>
								<?php } ?>
							</div>
						<?php } else { ?>
							<p class="wd-empty">
								<?= wd_esc__("This site is public. Add a user below to enable password protection.") ?>
							</p>
						<?php } ?>

						<form method="post" class="wd-auth-form">
							<input type="hidden" name="token" value="<?= wd_e($tok) ?>">
							<input type="hidden" name="ok" value="1">
							<input type="hidden" name="islem" value="ekle">
							<input type="hidden" name="v_domain" value="<?= wd_e($dad) ?>">
							<div class="wd-auth-field">
								<label class="form-label" for="au_<?= wd_e($dad) ?>"><?= wd_esc__("Username") ?></label>
								<input class="form-control" type="text" id="au_<?= wd_e($dad) ?>"
									name="v_auth_user" autocomplete="off" required
									pattern="[A-Za-z0-9._\-]{2,32}"
									title="<?= wd_esc__("2–32 characters; letters, digits, period, underscore, or hyphen") ?>">
							</div>
							<div class="wd-auth-field">
								<label class="form-label" for="ap_<?= wd_e($dad) ?>"><?= wd_esc__("Password") ?></label>
								<input class="form-control" type="password" id="ap_<?= wd_e($dad) ?>"
									name="v_password" autocomplete="new-password" required minlength="8"
									title="<?= wd_esc__("At least 8 characters") ?>">
							</div>
							<button type="submit" class="button"><?= wd_esc__("Add Protection") ?></button>
						</form>

					</div>
				</details>

			<?php }
   } ?>

		</div>

		<!-- ================= RIGHT RAIL ================= -->
		<aside class="wd-rail">

			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("Summary") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Domain") ?></span>
						<span class="wd-v"><?= count($wd_doms) ?></span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Protected") ?></span>
						<span class="wd-v"><?= (int) $wd_korumali ?></span>
					</div>
				</div>
			</div>

			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("Things to Know") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("What it protects") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("The") ?> <b><?= wd_esc__("entire") ?></b>
							<?= wd_esc__("site. The browser asks for username and password before any page loads.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Search engines") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("A protected site cannot be crawled or indexed. Remember to remove protection when you go live.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Encryption") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("This method sends the password with every request; without") ?>
							<b>HTTPS</b>
							<?= wd_esc__("it can be read on the network. Keep SSL enabled on the site.") ?>
						</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k"><?= wd_esc__("Is it enough?") ?></span>
						<span class="wd-v-small">
							<?= wd_esc__("Not enough alone for sensitive data — it does not replace in-app sign-in.") ?>
						</span>
					</div>
				</div>
			</div>

		</aside>
	</div>
</div>

<script>
	// Password change: the inline form carries a hidden field; password is prompted.
	// prompt() is used because a separate page or modal is overkill for one field.
	function wdParolaSor(form) {
		var p = window.prompt(<?= json_encode(wd__("New password (at least 8 characters):"), JSON_UNESCAPED_UNICODE) ?>);
		if (p === null) { return false; }
		if (p.length < 8) {
			window.alert(<?= json_encode(wd__("Password must be at least 8 characters."), JSON_UNESCAPED_UNICODE) ?>);
			return false;
		}
		form.elements["v_password"].value = p;
		return true;
	}
</script>
