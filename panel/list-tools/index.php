<?php
/**
 * WebDanışmanı — "Tools" control panel page
 * Installs to: /usr/local/hestia/web/list/tools/index.php
 *
 * NEW file; unaffected by HestiaCP updates.
 */

$TAB = "TOOLS";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// WebDanışmanı yardımcıları
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/wd-helpers.php";

// Geçerli (gerekirse taklit edilen) kullanıcı
$wd_user = empty($_SESSION["look"]) ? $_SESSION["user"] : $_SESSION["look"];
$wd_is_admin = $_SESSION["userContext"] === "admin" && empty($_SESSION["look"]);

// İşlemci/bellek kartları SUNUCU GENELİ değerlerdir (HestiaCP kullanıcı başına
// CPU/RAM tutmaz). Kartların alt satırında bu açıkça yazılır ki müşteri bunu
// kendi kullanımı sanmasın.
$wd_show_server_metrics = true;

$wd_cpu = wd_cpu_percent();
$wd_mem = wd_memory();
$wd_load = wd_load();
$wd_uptime = wd_uptime_seconds();

// Birincil alan adı / mail alan adı (araç bağlantılarını gerçek hedeflere yöneltmek için)
$wd_primary = wd_primary_domain($wd_user);
$wd_mail_doms = wd_mail_domains($wd_user);
$wd_primary_mail = !empty($wd_mail_doms) ? array_key_first($wd_mail_doms) : null;

$wd_dns_doms = wd_dns_domains($wd_user);
$wd_primary_dns = !empty($wd_dns_doms) ? array_key_first($wd_dns_doms) : null;

// Son giriş
$wd_login = wd_last_login($wd_user);

// Site başına CPU / RAM (yönetici tüm siteleri, müşteri yalnızca kendininkini görür)
$wd_usage = wd_site_usage($wd_is_admin ? "" : $wd_user);

// Birincil alan adının SSL yenileme süresi
$wd_ssl_days = null;
if ($wd_primary !== null && !empty($wd_primary["letsencrypt"])) {
	$wd_ssl_days = wd_ssl_days_left($wd_user, $wd_primary["domain"]);
}

// Render page
render_page($user, $TAB, "list_tools");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
