# =============================================================================
# WebDanışmanı HestiaCP teması — derleyici
#
#   pwsh tema\derle.ps1
#
# webdanismani.css ÜRETİLEN dosyadır; elle düzenlemeyin. Üç parçadan birleşir:
#
#   _baslik.css   sürüm notu + tasarım kaynağı açıklaması
#   _fontlar.css  @font-face blokları (Google Fonts'tan üretilir, bkz. kur.sh)
#   _govde.css    asıl tema — DÜZENLENECEK TEK DOSYA BUDUR
#
# Doğrudan webdanismani.css düzenlenirse bir sonraki derlemede kaybolur.
# =============================================================================
# NOT: Bu dosya UTF-8 BOM ile kaydedilmelidir. Windows PowerShell 5.1 BOM'suz
# betikleri ANSI olarak okur ve Türkçe karakterler ayrıştırma hatası verir.
$ErrorActionPreference = 'Stop'
$tema = $PSScriptRoot
$utf8 = New-Object System.Text.UTF8Encoding($false)

$metin = foreach ($p in '_baslik.css', '_fontlar.css', '_govde.css') {
	$yol = Join-Path $tema $p
	if (-not (Test-Path $yol)) { throw "parça eksik: $yol" }
	[System.IO.File]::ReadAllText($yol, [System.Text.Encoding]::UTF8)
}
$cikti = $metin -join "`n"

# --- Sağlık kontrolleri (bozuk CSS sunucuya gitmesin) ---
$ac = ([regex]::Matches($cikti, '\{')).Count
$kapa = ([regex]::Matches($cikti, '\}')).Count
if ($ac -ne $kapa) { throw "süslü parantez dengesiz: $ac açık / $kapa kapalı" }

$yuzler = ([regex]::Matches($cikti, '@font-face')).Count
if ($yuzler -lt 1) { throw "@font-face bulunamadı — _fontlar.css boş mu?" }

# Yazı tipi dosya adları kur.sh'nin indirdikleriyle birebir aynı olmalı.
# ("Inter".ToLower() Türkçe yerelde 'ınter' üretir; bu kontrol onu yakalar.)
$beklenen = 'inter-latin.woff2', 'inter-latin-ext.woff2',
            'jetbrains-mono-latin.woff2', 'jetbrains-mono-latin-ext.woff2'
foreach ($f in $beklenen) {
	if ($cikti -notmatch [regex]::Escape("/webfonts/$f")) {
		throw "yazı tipi referansı eksik: $f"
	}
}

$hedef = Join-Path $tema 'webdanismani.css'
[System.IO.File]::WriteAllText($hedef, $cikti, $utf8)

$boyut = (Get-Item $hedef).Length
Write-Host "webdanismani.css yazıldı — $boyut bayt, $yuzler @font-face, $ac kural bloğu"
