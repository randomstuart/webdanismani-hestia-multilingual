<?php
/**
 * WebDanışmanı — üst çubuk eklentileri (breadcrumb / arama / hızlı kurulum / avatar)
 * Kurulum yeri: /usr/local/hestia/web/templates/includes/wd-topbar.php
 *
 * panel.php içinde .top-bar-inner'ın doğrudan çocuğu olarak, .top-bar-right'ın
 * HEMEN ÖNÜNE eklenir. Öğeler CSS `order` ile sıralanır; avatar .top-bar-right'ın
 * sağına düşer.
 */

require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-helpers.php";

$wd_host = preg_replace('/:\d+$/', "", $_SERVER["HTTP_HOST"] ?? "");
$wd_u2 = empty($_SESSION["look"]) ? $_SESSION["user"] : $_SESSION["look"];
$wd_tok = $_SESSION["token"] ?? "";
$wd_name = $panel[$user]["NAME"] ?? $wd_u2;

// Baş harfler (avatar rozeti): ad-soyaddan en fazla iki harf
$wd_ini = "";
foreach (preg_split('/\s+/', trim((string) $wd_name), -1, PREG_SPLIT_NO_EMPTY) as $part) {
	$wd_ini .= mb_strtoupper(mb_substr($part, 0, 1, "UTF-8"), "UTF-8");
	if (mb_strlen($wd_ini, "UTF-8") >= 2) {
		break;
	}
}
if ($wd_ini === "") {
	$wd_ini = mb_strtoupper(mb_substr($wd_u2, 0, 2, "UTF-8"), "UTF-8");
}

// Hızlı kurulum hedefi — gerçek bir alan adı varsa uygulama kurucusu, yoksa alan adı ekleme
$wd_fastdom = wd_first_web_domain($wd_u2);
if ($wd_fastdom !== null && ($_SESSION["PLUGIN_APP_INSTALLER"] ?? "") === "true") {
	$wd_quick_href = "/add/webapp/?domain=" . urlencode($wd_fastdom) . "&token=" . $wd_tok;
	$wd_quick_label = "Hızlı Kurulum";
} else {
	$wd_quick_href = "/add/web/";
	$wd_quick_label = "Alan Adı Ekle";
}
$wd_show_quick = !empty($_SESSION["WEB_SYSTEM"]) && ($panel[$user]["WEB_DOMAINS"] ?? "0") !== "0";
?>

<nav class="wd-crumb" aria-label="Konum">
	<a class="wd-crumb-item" href="/list/tools/"><?= wd_e($wd_host) ?></a>
	<span class="wd-crumb-sep" aria-hidden="true">/</span>
	<span class="wd-crumb-item wd-crumb-current"><?= wd_e($wd_u2) ?></span>
</nav>

<form class="wd-search" action="/search/" method="get" role="search">
	<input type="hidden" name="token" value="<?= wd_e($wd_tok) ?>">
	<label class="u-hidden" for="wd-q">Ara</label>
	<input id="wd-q" class="wd-search-input" type="search" name="q" placeholder="Araçlarda ara ( / )" autocomplete="off"
		title="Alan adı, e-posta hesabı, veritabanı ve cron görevlerinde arar">
	<button class="wd-search-btn" type="submit" title="Ara">
		<i class="fas fa-magnifying-glass"></i>
		<span class="u-hidden">Ara</span>
	</button>
</form>
<script>
	/* "/" tuşu aramaya odaklanır. HestiaCP'nin kendi kısayolları s/l/n/d/enter/ok
	   tuşlarını kullanıyor, "/" boşta. Bir alana yazarken devreye girmez. */
	(function () {
		document.addEventListener("keydown", function (e) {
			if (e.key !== "/" || e.ctrlKey || e.metaKey || e.altKey) return;
			var t = e.target;
			if (t && (t.tagName === "INPUT" || t.tagName === "TEXTAREA" || t.tagName === "SELECT" || t.isContentEditable)) return;
			var box = document.getElementById("wd-q");
			if (!box) return;
			e.preventDefault();
			box.focus();
			box.select();
		});
	})();
</script>

<?php if ($wd_show_quick) { ?>
	<a class="wd-quick" href="<?= wd_e($wd_quick_href) ?>">
		<i class="fas fa-plus"></i><span class="wd-quick-label"><?= wd_e($wd_quick_label) ?></span>
	</a>
<?php } ?>

<?php // Avatar — CSS order ile .top-bar-right'ın sağına yerleşir
if (($panel[$user]["SUSPENDED"] ?? "no") === "no") { ?>
	<a class="wd-avatar" href="/edit/user/?user=<?= urlencode($wd_u2) ?>&token=<?= wd_e($wd_tok) ?>"
		title="<?= wd_e($wd_u2 . " (" . $wd_name . ")") ?>">
		<span class="wd-avatar-badge"><?= wd_e($wd_ini) ?></span>
		<span class="wd-avatar-name"><?= wd_e($wd_u2) ?></span>
	</a>
<?php } ?>

<?php // Çıkış — stok üst çubuktaki ikon masaüstünde gizlenip buraya, avatarın sağına alınır ?>
<a class="wd-logout" href="/logout/?token=<?= wd_e($wd_tok) ?>" title="Çıkış yap">
	<i class="fas fa-right-from-bracket"></i>
	<span class="u-hidden">Çıkış yap</span>
</a>
