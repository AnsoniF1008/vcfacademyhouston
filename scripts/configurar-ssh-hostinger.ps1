# Configura SSH para conectar a Hostinger desde Cursor (Remote-SSH)
$sshDir = Join-Path $env:USERPROFILE ".ssh"
$configPath = Join-Path $sshDir "config"

$block = @"

Host hostinger-vcf
    HostName 31.170.166.193
    User u766140586
    Port 65002
"@

if (-not (Test-Path $sshDir)) {
    New-Item -ItemType Directory -Path $sshDir -Force | Out-Null
}

$exists = Test-Path $configPath
if ($exists) {
    $content = Get-Content $configPath -Raw
    if ($content -match "Host hostinger-vcf") {
        Write-Host "Ya estaba configurado 'hostinger-vcf' en tu SSH config." -ForegroundColor Green
        exit 0
    }
}

Add-Content -Path $configPath -Value $block -Encoding UTF8
Write-Host "Listo. SSH configurado para Hostinger." -ForegroundColor Green
Write-Host ""
Write-Host "En Cursor: Ctrl+Shift+P -> escribe 'Remote-SSH: Connect to Host' -> elige 'hostinger-vcf'." -ForegroundColor Cyan
Write-Host "Te pedira la contraseña SSH (la del panel de Hostinger, Acceso SSH)." -ForegroundColor Cyan
