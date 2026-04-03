# Despliega el sitio a Hostinger por FTP (puerto 21).
# Uso: .\scripts\deploy-hostinger-ftp.ps1 -Password 'TU_CONTRASEÑA_FTP'
#   o: .\scripts\deploy-hostinger-ftp.ps1 -PasswordFile 'config\deploy-ftp.txt'  (primera línea = contraseña; archivo en .gitignore)
# No guardes la contraseña en el proyecto.

param(
    [Parameter(Mandatory=$false)]
    [string]$Password,
    [Parameter(Mandatory=$false)]
    [string]$PasswordFile
)

# Raíz del proyecto (carpeta que contiene index.php)
$root = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path (Join-Path $root "index.php"))) { $root = "c:\xampp\htdocs\valencia" }

if (-not $Password -and $PasswordFile) {
    $credPath = Join-Path $root $PasswordFile
    if (Test-Path $credPath) {
        $Password = (Get-Content $credPath -TotalCount 1).Trim()
    }
}
# Opcional: leer hostinger_ftp_pass desde config/deploy-credentials.php
if (-not $Password) {
    $phpCred = Join-Path $root "config\deploy-credentials.php"
    if (Test-Path $phpCred) {
        $content = Get-Content $phpCred -Raw
        if ($content -match "'hostinger_ftp_pass'\s*=>\s*'([^']+)'") {
            $Password = $Matches[1].Trim()
        }
    }
}
if (-not $Password) {
    Write-Host "Indica la contraseña FTP: -Password 'TU_CONTRASEÑA' o -PasswordFile 'config\deploy-ftp.txt'" -ForegroundColor Red
    Write-Host "O añade en config/deploy-credentials.php: 'hostinger_ftp_pass' => 'tu_contraseña_hostinger_ftp'" -ForegroundColor Yellow
    exit 1
}

$ErrorActionPreference = "Stop"
$ftpHost = "ftp.vcfacademyhouston.com"
$ftpPort = 21
$ftpUser = "u766140586.AnsoniF"
# Esta cuenta FTP adicional ya está enjaulada al public_html del sitio.
$remoteBases = @("")
function Ensure-FtpDirectory {
    param([string]$dirPath)
    if ([string]::IsNullOrWhiteSpace($dirPath)) { return }
    $uri = "ftp://${ftpHost}:${ftpPort}/$dirPath"
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $Password)
    $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
    $req.UsePassive = $true
    $req.UseBinary = $true
    try {
        $resp = $req.GetResponse()
        $resp.Close()
    } catch [System.Net.WebException] {
        $code = [int]$_.Exception.Response.StatusCode
        if ($code -ne 550 -and $code -ne 0) { throw }
    }
}

function New-FtpDirectoryRecursive {
    param([string]$remotePath)
    $parts = $remotePath.Trim('/').Split('/')
    $acc = ""
    foreach ($p in $parts) {
        if ($p -eq "") { continue }
        $acc = if ($acc -eq "") { $p } else { "$acc/$p" }
        Ensure-FtpDirectory $acc
    }
}

function Send-FtpFile {
    param([string]$localPath, [string]$remotePath)
    if ([string]::IsNullOrWhiteSpace($remotePath)) { return }
    $uri = "ftp://${ftpHost}:${ftpPort}/$remotePath"
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $Password)
    $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $req.UsePassive = $true
    $req.UseBinary = $true
    $req.KeepAlive = $false
    $fileBytes = [System.IO.File]::ReadAllBytes($localPath)
    $req.ContentLength = $fileBytes.Length
    $reqStream = $req.GetRequestStream()
    $reqStream.Write($fileBytes, 0, $fileBytes.Length)
    $reqStream.Close()
    $resp = $req.GetResponse()
    $resp.Close()
}

function Push-FtpItem {
    param([string]$localFull, [string]$relativePath)
    $relativePath = $relativePath -replace '\\', '/'
    if (Test-Path $localFull -PathType Container) {
        $children = Get-ChildItem -Path $localFull -Force
        foreach ($c in $children) {
            $rel = if ($relativePath -eq "") { $c.Name } else { "$relativePath/$($c.Name)" }
            Push-FtpItem -localFull $c.FullName -relativePath $rel
        }
    } else {
        # Excluir vídeos de reels (el usuario los sube desde el admin)
        if ($relativePath -match '^assets/uploads/reels/.*\.(mp4|webm|mov)$') { return }
        $parent = (Split-Path -Parent $relativePath) -replace '\\', '/'
        if ($parent -and $parent -ne ".") {
            $remoteDir = if ($remoteBase -eq "") { $parent } else { "$remoteBase/$parent" }
            try {
                New-FtpDirectoryRecursive $remoteDir
            } catch {
                Write-Host "  Error creando dir $remoteDir : $_" -ForegroundColor Red
            }
        }
        $remoteFile = if ($remoteBase -eq "") { $relativePath } else { "$remoteBase/$relativePath" }
        $script:uploadCount += 1
        Write-Host "  [$script:uploadCount] $relativePath"
        try {
            Send-FtpFile -localPath $localFull -remotePath $remoteFile
        } catch {
            Write-Host "  Error: $_" -ForegroundColor Red
        }
        Start-Sleep -Milliseconds 50
    }
}

Write-Host "Conectando por FTP a ${ftpHost}:${ftpPort} ..." -ForegroundColor Cyan
# Primero archivos sueltos, luego carpetas (para verificar conexión)
$toUpload = @(
    "index.php", "join.php", "contact.php", "calendar.php", "privacy.php", "recaudaciones.php", "deploy.php",
    "robots.txt", "sitemap.xml", "sitemap.php",
    "admin", "assets", "config", "includes", "api"
)
# sql/, docs/, scripts/ excluidos: no son necesarios en producción

$script:uploadCount = 0
Write-Host "Items a subir: $($toUpload.Count)" -ForegroundColor Gray
foreach ($remoteBase in $remoteBases) {
    $label = if ($remoteBase -eq "") { "(raíz FTP)" } else { "~/$remoteBase" }
    Write-Host "Subiendo a $label ..." -ForegroundColor Cyan
    if ($remoteBase -ne "") {
        try { New-FtpDirectoryRecursive $remoteBase } catch { }
    }
    foreach ($item in $toUpload) {
        $local = Join-Path $root $item
        if (-not (Test-Path $local)) { Write-Host "  (omitido: $item no existe)" -ForegroundColor Yellow; continue }
        try {
            Push-FtpItem -localFull $local -relativePath $item
        } catch {
            Write-Host "  Error en $item : $_" -ForegroundColor Red
        }
    }
}
Write-Host "Total subidos: $script:uploadCount archivos" -ForegroundColor Cyan

Write-Host "Listo. Comprueba https://vcfacademyhouston.com" -ForegroundColor Green
Write-Host "Asegura config/database.local.php en el servidor con los datos de MySQL de Hostinger." -ForegroundColor Yellow
