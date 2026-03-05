# Descarga desde Hostinger (archivos por SCP + dump de BD en el servidor).
# Uso: .\scripts\backup_from_hostinger.ps1 -Password 'TU_CONTRASEÑA_SSH'
# Genera: backup/production-YYYYMMDD-HHMM/files/ y valencia-database.sql

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

$timestamp = Get-Date -Format "yyyyMMdd-HHmm"
$backupDir = Join-Path $root "backup\production-$timestamp"
$filesDir = Join-Path $backupDir "files"
$dumpLocal = Join-Path $backupDir "valencia-database.sql"

if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
}

# Comprobar modulo Posh-SSH
if (-not (Get-Module -ListAvailable -Name Posh-SSH)) {
    Write-Host "Instalando modulo Posh-SSH (solo esta vez)..." -ForegroundColor Yellow
    try {
        [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
        Install-Module -Name Posh-SSH -Scope CurrentUser -Force -SkipPublisherCheck -AllowClobber
    } catch {
        Write-Host "No se pudo instalar Posh-SSH. Instala manualmente: Install-Module -Name Posh-SSH" -ForegroundColor Red
        exit 1
    }
}
Import-Module Posh-SSH -Force

$secPass = ConvertTo-SecureString $Password -AsPlainText -Force
$cred = New-Object System.Management.Automation.PSCredential($user, $secPass)

$commonParams = @{
    ComputerName = $hostName
    Port         = $port
    Credential   = $cred
    Force        = $true
    AcceptKey    = $true
}

Write-Host "Conectando a Hostinger..." -ForegroundColor Cyan
try {
    $session = New-SSHSession -ComputerName $hostName -Port $port -Credential $cred -Force
} catch {
    Write-Host "Error al conectar: $_" -ForegroundColor Red
    exit 1
}

# Detectar ruta remota (igual que deploy)
$r = Invoke-SSHCommand -SessionId $session.SessionId -Command "ls -la ~ 2>/dev/null; ls -la ~/public_html 2>/dev/null; ls -la ~/domains/vcfacademyhouston.com/public_html 2>/dev/null"
$remotePath = "public_html"
if ($r.Output -match "vcfacademyhouston") { $remotePath = "domains/vcfacademyhouston.com/public_html" }

Write-Host "Descargando archivos desde ~/$remotePath a $filesDir ..." -ForegroundColor Cyan
if (-not (Test-Path $filesDir)) {
    New-Item -ItemType Directory -Path $filesDir -Force | Out-Null
}

try {
    Get-SCPFolder @commonParams -RemoteFolder $remotePath -LocalFolder $filesDir -Force
} catch {
    try {
        Get-SCPFolder -SessionId $session.SessionId -RemoteFolder $remotePath -LocalFolder $filesDir -Force
    } catch {
        Write-Host "No se pudo descargar la carpeta (Get-SCPFolder): $_" -ForegroundColor Red
        Write-Host "Comprueba que la ruta remota sea correcta: $remotePath" -ForegroundColor Yellow
        Remove-SSHSession -SessionId $session.SessionId -ErrorAction SilentlyContinue
        exit 1
    }
}

# Exportar BD en el servidor (mysqldump usando config/database.local.php)
Write-Host "Exportando base de datos en el servidor..." -ForegroundColor Cyan
$phpCode = 'require "config/database.local.php"; $cmd = "mysqldump -h " . escapeshellarg($DB_HOST) . " -u " . escapeshellarg($DB_USER) . " -p" . escapeshellarg($DB_PASS) . " " . escapeshellarg($DB_NAME) . " 2>/dev/null"; file_put_contents("/tmp/valencia-dump.sql", shell_exec($cmd));'
$phpEscaped = $phpCode.Replace("'", "'\''")
$sshCmd = "cd $remotePath && php -r '$phpEscaped'"
$dumpResult = Invoke-SSHCommand -SessionId $session.SessionId -Command $sshCmd

if ($dumpResult.ExitStatus -ne 0) {
    Write-Host "AVISO: No se pudo ejecutar mysqldump en el servidor: $($dumpResult.Error)" -ForegroundColor Yellow
    Write-Host "Exporta la BD manualmente desde Hostinger -> phpMyAdmin -> Exportar y guarda el archivo como:" -ForegroundColor Yellow
    Write-Host "  $dumpLocal" -ForegroundColor White
} else {
    # Descargar el dump
    try {
        Get-SCPFile @commonParams -RemoteFile "/tmp/valencia-dump.sql" -LocalFile $dumpLocal -Force
    } catch {
        try {
            Get-SCPFile -SessionId $session.SessionId -RemoteFile "/tmp/valencia-dump.sql" -LocalFile $dumpLocal -Force
        } catch {
            try {
                Get-SCPItem @commonParams -Path "/tmp/valencia-dump.sql" -PathType File -Destination $backupDir -Force
                $downloaded = Join-Path $backupDir "valencia-dump.sql"
                if (Test-Path $downloaded) { Move-Item -Path $downloaded -Destination $dumpLocal -Force }
            } catch {
                Write-Host "No se pudo descargar el dump. Exporta desde phpMyAdmin y guarda en: $dumpLocal" -ForegroundColor Yellow
            }
        }
    }
    # Limpiar en el servidor
    Invoke-SSHCommand -SessionId $session.SessionId -Command "rm -f /tmp/valencia-dump.sql" | Out-Null
}

Remove-SSHSession -SessionId $session.SessionId -ErrorAction SilentlyContinue | Out-Null

Write-Host "Backup guardado en: $backupDir" -ForegroundColor Green
if (Test-Path $dumpLocal) {
    Write-Host "Base de datos: $dumpLocal" -ForegroundColor Green
    Write-Host "Siguiente paso: php scripts/import_to_local.php" -ForegroundColor Cyan
} else {
    Write-Host "Pon valencia-database.sql en esa carpeta (export desde phpMyAdmin) y luego: php scripts/import_to_local.php" -ForegroundColor Cyan
}
