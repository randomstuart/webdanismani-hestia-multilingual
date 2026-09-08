<?php
/**
 * WebDanışmanı — "Sağlık Merkezi" sayfa şablonu
 * Kurulum yeri: /usr/local/hestia/web/templates/pages/list_health.php
 *
 * $panel, $user  -> render_page() tarafından sağlanır
 * $wd_*          -> list/health/index.php tarafından sağlanır
 */

$tok = $_SESSION["token"] ?? "";

/* Kontrolün ilgili olduğu panel sayfasına götüren bağlantılar. Sorunu
   bildirmek yetmez; kullanıcı tek tıkla düzelteceği yere gidebilmeli. */
$wd_duzelt = function (string $kid, string $domain) use ($tok) {
	switch ($kid) {
		case "mx":
		case "spf":
		case "dmarc":
			return ["/list/dns/?domain=" . urlencode($domain), "DNS kayıtlarını aç"];
		case "dkim":
			return ["/edit/mail/?domain=" . urlencode($domain) . "&token=" . $tok, "Mail ayarlarını aç"];
		case "a":
			return ["/list/dns/?domain=" . urlencode($domain), "DNS kayıtlarını aç"];
		case "ssl":
			return ["/edit/web/?domain=" . urlencode($domain) . "&token=" . $tok, "SSL ayarlarını aç"];
		default:
			return null;
	}
};

$ozet = $wd_saglik["ozet"] ?? ["ok" => 0, "warn" => 0, "fail" => 0, "bilinmiyor" => 0];
$sorunlu = ($ozet["fail"] ?? 0) + ($ozet["warn"] ?? 0);

/* DNS kaydına dokunan kontroller. Alan adının DNS'i başka sağlayıcıdaysa
   bunlar için panelin DNS sayfasına yönlendirmek YANLIŞTIR: oradaki bölge
   yok sayılır, yapılan değişikliğin hiçbir etkisi olmaz. */
$wd_dns_kontrolu = ["mx", "spf", "dmarc", "a", "dkim"];

/** Tek bir kontrol satırı çizer. */
$wd_kontrol_satiri = function (array $c, ?string $domain, array $dom = []) use (
	$wd_duzelt,
	$wd_dns_kontrolu
) {
	$durum = $c["durum"] ?? "bilinmiyor";
	$sorunlu = $durum === "fail" || $durum === "warn";
	$disarida = !empty($dom["dns_disarida"]);
	$dns_ile_ilgili = in_array($c["id"] ?? "", $wd_dns_kontrolu, true); ?>
	<div class="wd-check wd-check-<?= wd_e($durum) ?>">
		<span class="wd-check-dot <?= wd_e(wd_health_sinif($durum)) ?>"
			title="<?= wd_e(wd_health_etiket($durum)) ?>" aria-label="<?= wd_e(wd_health_etiket($durum)) ?>"></span>
		<div class="wd-check-body">
			<div class="wd-check-top">
				<span class="wd-check-label"><?= wd_e($c["label"] ?? "") ?></span>
				<?php if (($c["deger"] ?? "") !== "") { ?>
					<span class="wd-check-value wd-mono"><?= wd_e($c["deger"]) ?></span>
				<?php } ?>
			</div>
			<?php if (!empty($c["not"])) { ?>
				<p class="wd-check-note"><?= wd_e($c["not"]) ?></p>
			<?php } ?>
			<?php if ($sorunlu && !empty($c["cozum"])) { ?>
				<p class="wd-check-fix">
					<i class="fas fa-screwdriver-wrench"></i>
					<span><?= wd_e($c["cozum"]) ?></span>
				</p>
			<?php }
   if ($sorunlu && $domain !== null) {
   	if ($disarida && $dns_ile_ilgili) {
   		// DNS başka sağlayıcıda: panelin DNS sayfası bu kaydı etkilemez.
   		$ns = !empty($dom["ns"]) ? implode(", ", array_slice($dom["ns"], 0, 3)) : "";
   		?>
					<p class="wd-check-disari">
						<i class="fas fa-circle-info"></i>
						<span>
							Bu kaydı <b>panelden düzeltemezsiniz</b> — alan adının DNS'i bu sunucuda değil.
							<?php if ($ns !== "") { ?>
								Yöneten nameserver: <span class="wd-mono"><?= wd_e($ns) ?></span>.
							<?php } ?>
							Kaydı o sağlayıcının DNS panelinde ekleyin.
						</span>
					</p>
				<?php } else {
   		$link = $wd_duzelt($c["id"] ?? "", $domain);
   		if ($link !== null) { ?>
					<a class="wd-check-link" href="<?= wd_e($link[0]) ?>"><?= wd_e($link[1]) ?> <i class="fas fa-arrow-right"></i></a>
				<?php }
   	}
   } ?>
		</div>
	</div>
<?php };

/** Bir alan adı bloğunun en kötü durumunu bulur (rozet için). */
$wd_en_kotu = function (array $checks) {
	$sira = ["ok" => 0, "bilinmiyor" => 1, "warn" => 2, "fail" => 3];
	$en = "ok";
	foreach ($checks as $c) {
		$d = $c["durum"] ?? "bilinmiyor";
		if (($sira[$d] ?? 0) > ($sira[$en] ?? 0)) {
			$en = $d;
		}
	}
	return $en;
};
?>

<div class="container">
	<div class="wd-page">

		<div class="wd-main">

			<div class="wd-page-head">
				<div>
					<h1 class="wd-title">Sağlık Merkezi</h1>
					<p class="wd-subtitle">
						Panelin kaydettiği ayarlarla dışarıdan gerçekten görünen DNS karşılaştırılır —
						mail teslimi, alan adı yönlendirmesi ve SSL burada denetlenir.
					</p>
				</div>
				<div class="wd-page-actions">
					<a class="button button-secondary" href="/list/health/?yenile=1&amp;token=<?= wd_e($tok) ?>">
						<i class="fas fa-rotate"></i> Şimdi Denetle
					</a>
				</div>
			</div>

			<?php if (!empty($wd_yenilendi)) { ?>
				<div class="wd-note wd-note-ok">
					<i class="fas fa-circle-check"></i>
					<span>Denetim yeniden çalıştırıldı; aşağıdaki sonuçlar az önce alındı.</span>
				</div>
			<?php } ?>

			<?php if ($wd_saglik === null) { ?>

				<div class="wd-note wd-note-warn">
					<i class="fas fa-triangle-exclamation"></i>
					<span>
						Denetim aracı kurulu değil ya da çalıştırılamıyor.
						Sunucuda <span class="wd-mono">bash /usr/local/hestia/wd/src/kur.sh</span> komutunu çalıştırın.
					</span>
				</div>

			<?php } else { ?>

				<!-- Özet kartları -->
				<div class="wd-stats">
					<div class="wd-stat">
						<div class="wd-stat-label">SORUN</div>
						<div class="wd-stat-value"><?= wd_e($ozet["fail"] ?? 0) ?><span class="wd-stat-of">acil</span></div>
						<div class="wd-bar <?= ($ozet["fail"] ?? 0) > 0 ? "wd-crit" : "wd-ok" ?>">
							<span style="width: <?= ($ozet["fail"] ?? 0) > 0 ? 100 : 2 ?>%"></span>
						</div>
					</div>
					<div class="wd-stat">
						<div class="wd-stat-label">UYARI</div>
						<div class="wd-stat-value"><?= wd_e($ozet["warn"] ?? 0) ?><span class="wd-stat-of">gözden geçir</span></div>
						<div class="wd-bar <?= ($ozet["warn"] ?? 0) > 0 ? "wd-warn" : "wd-ok" ?>">
							<span style="width: <?= ($ozet["warn"] ?? 0) > 0 ? 100 : 2 ?>%"></span>
						</div>
					</div>
					<div class="wd-stat">
						<div class="wd-stat-label">SORUNSUZ</div>
						<div class="wd-stat-value"><?= wd_e($ozet["ok"] ?? 0) ?><span class="wd-stat-of">kontrol</span></div>
						<div class="wd-bar wd-ok"><span style="width:100%"></span></div>
					</div>
					<?php if (($ozet["bilinmiyor"] ?? 0) > 0) { ?>
						<div class="wd-stat" title="Sorgu yapılamadı — sonuç bilinmiyor demektir, sorun var demek değildir">
							<div class="wd-stat-label">BİLİNMİYOR</div>
							<div class="wd-stat-value"><?= wd_e($ozet["bilinmiyor"]) ?><span class="wd-stat-of">sorgulanamadı</span></div>
							<div class="wd-bar wd-bar-none" aria-hidden="true"><span style="width:100%"></span></div>
						</div>
					<?php } ?>
				</div>

				<?php if ($sorunlu === 0) { ?>
					<div class="wd-note wd-note-ok">
						<i class="fas fa-circle-check"></i>
						<span>Tüm kontroller sorunsuz. Mail, alan adı ve SSL ayarlarınızda düzeltilmesi gereken bir şey yok.</span>
					</div>
				<?php } ?>

				<?php // --- Sunucu düzeyi (yalnızca yönetici) ---
    if (!empty($wd_saglik["sunucu"])) { ?>
					<details class="wd-group" open>
						<summary class="wd-group-head">
							<span class="wd-group-icon"><i class="fas fa-server"></i></span>
							<span class="wd-group-title">Sunucu</span>
							<span class="wd-group-count"><?= wd_e($wd_saglik["hostname"] ?? "") ?></span>
							<i class="fas fa-chevron-down wd-group-chevron"></i>
						</summary>
						<div class="wd-checks">
							<?php foreach ($wd_saglik["sunucu"] as $c) {
       	$wd_kontrol_satiri($c, null);
       } ?>
						</div>
					</details>
				<?php } ?>

				<?php // --- Yedekler ---
    // Yedek almak yetmez; geri yüklenebildiğini bilmek gerekir. Bu bölüm
    // arşivi gerçekten açıp okunabilirliğini denetler.
    foreach ($wd_saglik["yedek"] ?? [] as $y) {
    	$kotu = $wd_en_kotu($y["checks"] ?? []); ?>
					<details class="wd-group" <?= $kotu === "ok" ? "" : "open" ?>>
						<summary class="wd-group-head">
							<span class="wd-group-icon"><i class="fas fa-file-zipper"></i></span>
							<span class="wd-group-title">Yedekler<?= count($wd_saglik["yedek"]) > 1
       	? " — " . wd_e($y["user"])
       	: "" ?></span>
							<span class="wd-hs-badge <?= wd_e(wd_health_sinif($kotu)) ?>"><?= wd_e(wd_health_etiket($kotu)) ?></span>
							<i class="fas fa-chevron-down wd-group-chevron"></i>
						</summary>
						<div class="wd-checks">
							<?php foreach ($y["checks"] as $c) {
       	$wd_kontrol_satiri($c, null);
       } ?>
						</div>
					</details>
				<?php } ?>

				<?php // --- Mail alan adları ---
    foreach ($wd_saglik["mail"] ?? [] as $d) {
    	$kotu = $wd_en_kotu($d["checks"] ?? []); ?>
					<details class="wd-group" <?= $kotu === "ok" ? "" : "open" ?>>
						<summary class="wd-group-head">
							<span class="wd-group-icon"><i class="fas fa-envelopes-bulk"></i></span>
							<span class="wd-group-title"><?= wd_e($d["domain"]) ?></span>
							<span class="wd-hs-badge <?= wd_e(wd_health_sinif($kotu)) ?>"><?= wd_e(wd_health_etiket($kotu)) ?></span>
							<span class="wd-group-count">mail</span>
							<i class="fas fa-chevron-down wd-group-chevron"></i>
						</summary>
						<div class="wd-checks">
							<?php foreach ($d["checks"] as $c) {
       	$wd_kontrol_satiri($c, $d["domain"], $d);
       } ?>
						</div>
					</details>
				<?php } ?>

				<?php // --- Web alan adları ---
    foreach ($wd_saglik["web"] ?? [] as $d) {
    	$kotu = $wd_en_kotu($d["checks"] ?? []); ?>
					<details class="wd-group" <?= $kotu === "ok" ? "" : "open" ?>>
						<summary class="wd-group-head">
							<span class="wd-group-icon"><i class="fas fa-earth-americas"></i></span>
							<span class="wd-group-title"><?= wd_e($d["domain"]) ?></span>
							<span class="wd-hs-badge <?= wd_e(wd_health_sinif($kotu)) ?>"><?= wd_e(wd_health_etiket($kotu)) ?></span>
							<span class="wd-group-count">web</span>
							<i class="fas fa-chevron-down wd-group-chevron"></i>
						</summary>
						<div class="wd-checks">
							<?php foreach ($d["checks"] as $c) {
       	$wd_kontrol_satiri($c, $d["domain"], $d);
       } ?>
						</div>
					</details>
				<?php } ?>

				<?php if (empty($wd_saglik["mail"]) && empty($wd_saglik["web"]) && empty($wd_saglik["sunucu"])) { ?>
					<div class="wd-note">
						<i class="fas fa-circle-info"></i>
						<span>Denetlenecek alan adı yok. Web veya mail alan adı ekledikten sonra burada görünür.</span>
					</div>
				<?php } ?>

			<?php } ?>

		</div>

		<!-- ================= SAĞ PANEL ================= -->
		<aside class="wd-rail">

			<?php if ($wd_saglik !== null) { ?>
				<div class="wd-card">
					<div class="wd-card-head">Denetim</div>
					<div class="wd-card-body">
						<div class="wd-kv">
							<span class="wd-k">Son Kontrol</span>
							<span class="wd-v">
								<?php $ts = (int) ($wd_saglik["ts"] ?? 0);
        if ($ts > 0) {
        	$fark = max(0, time() - $ts);
        	echo wd_e(date("d.m.Y H:i", $ts));
        	echo '<span class="wd-v-dim"> · ' . wd_e(wd_human_uptime($fark)) . " önce</span>";
        } else {
        	echo "—";
        } ?>
							</span>
						</div>
						<?php if (!empty($wd_saglik["ip"])) { ?>
							<div class="wd-kv">
								<span class="wd-k">Sunucu IP</span>
								<span class="wd-v wd-mono"><?= wd_e($wd_saglik["ip"]) ?></span>
							</div>
						<?php } ?>
						<div class="wd-kv">
							<span class="wd-k">Denetlenen</span>
							<span class="wd-v">
								<?= count($wd_saglik["mail"] ?? []) ?> mail ·
								<?= count($wd_saglik["web"] ?? []) ?> web alan adı
							</span>
						</div>
					</div>
				</div>
			<?php } ?>

			<div class="wd-card">
				<div class="wd-card-head">Kontroller Ne Anlama Geliyor?</div>
				<div class="wd-card-body">
					<div class="wd-kv">
						<span class="wd-k">MX</span>
						<span class="wd-v-small">Bu alan adına dışarıdan mail hangi sunucuya gelecek. Yanlışsa mail hiç ulaşmaz.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">SPF</span>
						<span class="wd-v-small">Bu alan adı adına hangi sunucuların mail atabileceğini bildirir. Yoksa mailler spam'e düşer.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">DKIM</span>
						<span class="wd-v-small">Mailleri imzalar. Panelde etkin görünse bile kayıt DNS'te yoksa imza doğrulanamaz.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">DMARC</span>
						<span class="wd-v-small">SPF/DKIM başarısız olursa alıcı ne yapsın. Yoksa adınıza sahte mail atılabilir.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">A kaydı</span>
						<span class="wd-v-small">Alan adı hangi sunucuya çözümleniyor. Buradan farklıysa site bu sunucudan yayınlanmaz.</span>
					</div>
					<div class="wd-kv">
						<span class="wd-k">SSL</span>
						<span class="wd-v-small">Sertifikanın kalan süresi. Otomatik yenileme sessizce başarısız olabilir.</span>
					</div>
				</div>
			</div>

		</aside>
	</div>
</div>
