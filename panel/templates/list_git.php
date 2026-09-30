<?php
/**
 * WebDanışmanı — "Git Deploy" page template
 * Installs to: /usr/local/hestia/web/templates/pages/list_git.php
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

			<?php wd_modul_baslik(wd__("Git Deploy"), wd__("Clone a repository onto the site; pull changes with one click or automatically.")); ?>

			<?php if ($wd_hata !== "") {
				wd_modul_not($wd_hata, "err");
			} elseif ($wd_bilgi !== "") {
				wd_modul_not($wd_bilgi, "ok");
			} ?>

			<?php if (!empty($wd_depolar)) { ?>
				<div class="wd-card">
					<div class="wd-card-head"><?= wd_esc__("Connected repositories") ?></div>
					<div class="wdm-liste">
						<?php foreach ($wd_depolar as $r) {
							$g = $r["git"] ?? ["var" => false]; ?>
							<div class="wdm-oge">
								<div class="wdm-oge-bas">
									<span class="wdm-oge-ad"><?= wd_e($r["domain"]) ?><?= !empty($r["alt_dizin"]) ? "/" . wd_e($r["alt_dizin"]) : "" ?> <?= !empty($r["otomatik"]) ? '<span class="wdm-rozet wdm-rozet-info">' . wd_esc__("auto") . '</span>' : "" ?><?= empty($g["var"]) ? '<span class="wdm-rozet wdm-rozet-err">' . wd_esc__("no .git") . '</span>' : "" ?></span>
									<span class="wdm-oge-alt"><span class="wdm-mono"><?= wd_e($r["repo_goster"] ?? $r["repo"]) ?></span><?= !empty($r["dal"]) ? " · " . wd_e(sprintf(wd__("branch %s"), $r["dal"])) : "" ?></span>
									<?php if (!empty($g["var"])) { ?>
										<span class="wdm-oge-alt"><span class="wdm-mono"><?= wd_e($g["commit"]) ?></span> <?= wd_e($g["mesaj"]) ?> · <?= wd_e($g["yazar"]) ?> · <?= wd_e($g["tarih"]) ?><?= (int) ($g["degisen"] ?? 0) > 0 ? ' · <span style="color:var(--wd-amber)">' . wd_e(sprintf(wd_n__("%d local change", "%d local changes", (int) $g["degisen"]), (int) $g["degisen"])) . "</span>" : "" ?></span>
									<?php } ?>
									<?php if (!empty($r["son_cekim"])) { ?><span class="wdm-eskime"><?= wd_esc__("last pull") ?> <?= wd_e(wd_modul_tarih((int) $r["son_cekim"])) ?> · <?= wd_e($r["son_sonuc"] ?? "") ?></span><?php } ?>
								</div>
								<div class="wdm-oge-eylem">
									<form method="post"><?= $gizli("cek", $r) ?><button type="submit" class="wd-mini-btn"><i class="fas fa-download"></i> <?= wd_esc__("Pull") ?></button></form>
									<form method="post"><?= $gizli("otomatik", $r, ["v_deger" => !empty($r["otomatik"]) ? "off" : "on"]) ?><button type="submit" class="wd-mini-btn"><?= !empty($r["otomatik"]) ? wd_esc__("Disable auto") : wd_esc__("Auto pull") ?></button></form>
									<form method="post" onsubmit="return confirm('<?= wd_esc__("The record will be removed; files and the .git directory stay in place. Continue?") ?>');"><?= $gizli("kaldir", $r) ?><button type="submit" class="wd-mini-btn wd-mini-btn-danger"><?= wd_esc__("Remove") ?></button></form>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>

			<?php if (empty($wd_doms)) { ?>
				<?php wd_modul_not(wd__("You do not have any web domains yet.")); ?>
			<?php } else { ?>
				<form method="post" class="wdm-form">
					<?= wd_modul_form_gizli("klonla") ?>
					<div class="wd-card">
						<div class="wd-card-head"><?= wd_esc__("Connect repository") ?></div>
						<div class="wd-card-body wd-card-body-pad">
							<div class="wdm-satir">
								<div class="wdm-alan wdm-alan-genis">
									<label class="form-label" for="v_repo"><?= wd_esc__("Repository URL") ?></label>
									<input class="form-control wdm-mono" id="v_repo" name="v_repo" required placeholder="https://github.com/user/repo.git or git@github.com:user/repo.git">
									<span class="wdm-ipucu"><?= wd_esc__("For private repos use a") ?> <b>git@</b> <?= wd_esc__("URL and add the deploy key on the right to the repo.") ?></span>
								</div>
							</div>
							<div class="wdm-satir" style="margin-top:10px">
								<div class="wdm-alan">
									<label class="form-label" for="v_domain"><?= wd_esc__("Domain") ?></label>
									<select class="form-select" id="v_domain" name="v_domain">
										<?php foreach ($wd_doms as $d => $_) { ?><option value="<?= wd_e($d) ?>"><?= wd_e($d) ?></option><?php } ?>
									</select>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_alt"><?= wd_esc__("Subdirectory") ?> <span class="wdm-ipucu"><?= wd_esc__("empty = site root") ?></span></label>
									<input class="form-control wdm-mono" id="v_alt" name="v_alt" placeholder="api">
									<span class="wdm-ipucu"><?= wd_esc__("Target must be empty.") ?></span>
								</div>
								<div class="wdm-alan">
									<label class="form-label" for="v_dal"><?= wd_esc__("Branch") ?> <span class="wdm-ipucu"><?= wd_esc__("empty = default") ?></span></label>
									<input class="form-control wdm-mono" id="v_dal" name="v_dal" placeholder="main">
								</div>
							</div>
						</div>
					</div>
					<div class="wdm-dugmeler"><button type="submit" class="button"><i class="fas fa-code-branch"></i> <?= wd_esc__("Clone") ?></button></div>
				</form>
			<?php } ?>
		</div>

		<aside class="wd-rail">
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("Deploy Key") ?></div>
				<div class="wd-card-body wd-card-body-pad">
					<?php if ($wd_anahtar !== "") { ?>
						<p class="wdm-ipucu" style="margin:0 0 6px"><?= wd_esc__("For private repos add this public key under GitHub → Settings → Deploy keys (or GitLab → Deploy Keys). Read-only access is enough.") ?></p>
						<pre class="wdm-kod wdm-kod-kucuk" id="wdGitAnahtar"><?= wd_e($wd_anahtar) ?></pre>
						<button type="button" class="wd-mini-btn" style="margin-top:6px" onclick="var t=document.getElementById('wdGitAnahtar').textContent;(navigator.clipboard?navigator.clipboard.writeText(t):Promise.reject()).then(function(){},function(){window.prompt('<?= wd_esc__("Copy:") ?>',t);});"><?= wd_esc__("Copy") ?></button>
					<?php } else { ?>
						<p class="wd-empty"><?= wd_esc__("Could not generate key.") ?></p>
					<?php } ?>
				</div>
			</div>
			<div class="wd-card">
				<div class="wd-card-head"><?= wd_esc__("How It Works") ?></div>
				<div class="wd-card-body">
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Clone") ?></span><span class="wd-v-small"><?= wd_esc__("The repo is cloned shallow (depth 1) as your account user. If a build step (composer, npm build) is needed, run it from the Web Terminal.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Pull") ?></span><span class="wd-v-small"><span class="wd-mono">git pull --ff-only</span>: <?= wd_esc__("if files were edited manually on the server, pull is refused; commit those changes to the repo first.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Auto") ?></span><span class="wd-v-small"><?= wd_esc__("Pulls every 5 minutes. This method was chosen instead of webhooks: no open endpoint on the server is required.") ?></span></div>
					<div class="wd-kv"><span class="wd-k"><?= wd_esc__("Security") ?></span><span class="wd-v-small"><span class="wd-mono">.git</span> <?= wd_esc__("is blocked from web access (nginx dotfile rule).") ?></span></div>
				</div>
			</div>
		</aside>
	</div>
</div>
