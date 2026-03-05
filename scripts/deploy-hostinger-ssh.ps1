# Despliega el sitio a Hostinger por SSH/SCP (requiere contraseña una vez).
# Uso: .\scripts\deploy-hostinger-ssh.ps1 -Password 'TU_CONTRASEÑA_SSH'
# No guardes la contraseña en el proyecto.

param(
    [Parameter(Mandatory=$true)]
    [string]$Password
)

$ErrorActionPreference = "Stop"
$hostName = "31.170.166.193"
$port = 65002
$user = "u766140586"
$root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
if (-not (Test-Path (Join-Path $root "index.php"))) { $root = "c:\xampp\htdocs\valencia" }

# Comprobar modulo Posh-SSH
if (-not (Get-Module -ListAvailable -Name Posh-SSH)) {
    Write-Host "Instalando modulo Posh-SSH (solo esta vez, puede tardar 1 min)..." -ForegroundColor Yellow
    try {
        [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
        Install-Module -Name Posh-SSH -Scope CurrentUser -Force -SkipPublisherCheck -AllowClobber
    } catch {
        Write-Host "No se pudo instalar Posh-SSH. Sube manualmente: valencia-website.zip por el administrador de archivos de Hostinger, descomprime en public_html y crea config/database.local.php." -ForegroundColor Red
        exit 1
    }
}
Import-Module Posh-SSH -Force

$secPass = ConvertTo-SecureString $Password -AsPlainText -Force
$cred = New-Object System.Management.Automation.PSCredential($user, $secPass)

Write-Host "Conectando a Hostinger..." -ForegroundColor Cyan
try {
    $session = New-SSHSession -ComputerName $hostName -Port $port -Credential $cred -Force
} catch {
    Write-Host "Error al conectar: $_" -ForegroundColor Red
    exit 1
}

# Detectar ruta remota
$r = Invoke-SSHCommand -SessionId $session.SessionId -Command "ls -la ~ 2>/dev/null; ls -la ~/public_html 2>/dev/null; ls -la ~/domains/vcfacademyhouston.com/public_html 2>/dev/null"
$remotePath = "public_html"
if ($r.Output -match "vcfacademyhouston") { $remotePath = "domains/vcfacademyhouston.com/public_html" }

# Una sola sesión SFTP (más estable que SCP con muchas conexiones)
Write-Host "Abriendo sesión SFTP..." -ForegroundColor Cyan
try {
    $sftp = New-SFTPSession -ComputerName $hostName -Port $port -Credential $cred -Force -AcceptKey
} catch {
    Write-Host "Error SFTP: $_" -ForegroundColor Red
    Remove-SSHSession -SessionId $session.SessionId | Out-Null
    exit 1
}

Write-Host "Subiendo archivos a ~/$remotePath ..." -ForegroundColor Cyan
$toUpload = @(
    "admin", "assets", "config", "includes", "api", "sql",
    "index.php", "join.php", "contact.php", "calendar.php", "privacy.php", "deploy.php", "scripts"
)
foreach ($item in $toUpload) {
    $local = Join-Path $root $item
    if (-not (Test-Path $local)) { continue }
    Write-Host "  $item -> servidor"
    try {
        Set-SFTPItem -SessionId $sftp.SessionId -Path $local -Destination $remotePath -Force
    } catch {
        Write-Host "  Error subiendo $item : $_" -ForegroundColor Red
    }
    Start-Sleep -Milliseconds 800
}
Remove-SFTPSession -SessionId $sftp.SessionId | Out-Null

Invoke-SSHCommand -SessionId $session.SessionId -Command "chmod -R 755 $remotePath/assets/uploads 2>/dev/null; echo OK" | Out-Null
Remove-SSHSession -SessionId $session.SessionId | Out-Null

Write-Host "Listo. Comprueba https://vcfacademyhouston.com" -ForegroundColor Green
Write-Host "Crea config/database.local.php en el servidor con los datos de MySQL de Hostinger." -ForegroundColor Yellow
