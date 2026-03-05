# Despliega el sitio a Hostinger por FTP (puerto 21).
# Uso: .\scripts\deploy-hostinger-ftp.ps1 -Password 'TU_CONTRASEÑA_FTP'
# No guardes la contraseña en el proyecto.

param(
    [Parameter(Mandatory=$true)]
    [string]$Password
)

$ErrorActionPreference = "Stop"
$ftpHost = "31.170.166.193"
$ftpPort = 21
$ftpUser = "u766140586"
# Subir a TODAS las rutas posibles: raíz FTP (por si ya estás en public_html), public_html y dominio
$remoteBases = @("", "public_html", "domains/vcfacademyhouston.com/public_html")
$root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
if (-not (Test-Path (Join-Path $root "index.php"))) { $root = "c:\xampp\htdocs\valencia" }

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
    if (Test-Path $localFull -PathType Container) {
        $children = Get-ChildItem -Path $localFull -Force
        foreach ($c in $children) {
            $rel = if ($relativePath -eq "") { $c.Name } else { "$relativePath/$($c.Name)" }
            Push-FtpItem -localFull $c.FullName -relativePath $rel
        }
    } else {
        $parent = Split-Path -Parent $relativePath
        if ($parent -and $parent -ne ".") {
            $remoteDir = if ($remoteBase -eq "") { $parent } else { "$remoteBase/$parent" }
            New-FtpDirectoryRecursive $remoteDir
        }
        $remoteFile = if ($remoteBase -eq "") { $relativePath } else { "$remoteBase/$relativePath" }
        Write-Host "  $relativePath -> servidor"
        try {
            Send-FtpFile -localPath $localFull -remotePath $remoteFile
        } catch {
            Write-Host "  Error: $_" -ForegroundColor Red
        }
        Start-Sleep -Milliseconds 300
    }
}

Write-Host "Conectando por FTP a ${ftpHost}:${ftpPort} ..." -ForegroundColor Cyan
$toUpload = @(
    "admin", "assets", "config", "includes", "api", "sql",
    "index.php", "join.php", "contact.php", "calendar.php", "privacy.php", "deploy.php"
)
if (Test-Path (Join-Path $root "scripts")) {
    $toUpload += "scripts"
}

foreach ($remoteBase in $remoteBases) {
    $label = if ($remoteBase -eq "") { "(raíz FTP)" } else { "~/$remoteBase" }
    Write-Host "Subiendo a $label ..." -ForegroundColor Cyan
    if ($remoteBase -ne "") {
        try { New-FtpDirectoryRecursive $remoteBase } catch { }
    }
    foreach ($item in $toUpload) {
        $local = Join-Path $root $item
        if (-not (Test-Path $local)) { continue }
        Push-FtpItem -localFull $local -relativePath $item
    }
}

Write-Host "Listo. Comprueba https://vcfacademyhouston.com" -ForegroundColor Green
Write-Host "Asegura config/database.local.php en el servidor con los datos de MySQL de Hostinger." -ForegroundColor Yellow
