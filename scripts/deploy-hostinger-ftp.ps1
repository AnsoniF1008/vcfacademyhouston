# Despliega el sitio a Hostinger por FTP (puerto 21).
# Uso recomendado:
#   .\scripts\deploy-hostinger-ftp.ps1
# Opcionales:
#   -Password 'TU_CONTRASEÑA_FTP'
#   -PasswordFile 'config\deploy-ftp.txt'   (primera línea = contraseña; archivo en .gitignore)
#   -DryRun                                 (simulación, no sube archivos)

param(
    [Parameter(Mandatory=$false)]
    [string]$Password,
    [Parameter(Mandatory=$false)]
    [string]$PasswordFile,
    [Parameter(Mandatory=$false)]
    [switch]$DryRun
)

function ConvertTo-RemotePath {
    param([string]$path)
    if ([string]::IsNullOrWhiteSpace($path)) { return "" }
    return (($path -replace '\\', '/').Trim().Trim('/'))
}

function Join-RemotePath {
    param(
        [string]$basePath,
        [string]$childPath
    )
    $basePath = ConvertTo-RemotePath $basePath
    $childPath = ConvertTo-RemotePath $childPath
    if ($basePath -eq "") { return $childPath }
    if ($childPath -eq "") { return $basePath }
    if ($childPath -eq $basePath -or $childPath.StartsWith("$basePath/")) { return $childPath }
    return "$basePath/$childPath"
}

function Get-PhpCredentialsMap {
    param([string]$filePath)
    $map = @{}
    if (-not (Test-Path $filePath)) { return $map }
    $content = Get-Content $filePath -Raw
    $quoted = [regex]::Matches($content, "['""](?<key>[^'""]+)['""]\s*=>\s*['""](?<value>[^'""]*)['""]")
    foreach ($m in $quoted) {
        $map[$m.Groups["key"].Value] = $m.Groups["value"].Value
    }
    $numeric = [regex]::Matches($content, "['""](?<key>[^'""]+)['""]\s*=>\s*(?<value>\d+)")
    foreach ($m in $numeric) {
        if (-not $map.ContainsKey($m.Groups["key"].Value)) {
            $map[$m.Groups["key"].Value] = $m.Groups["value"].Value
        }
    }
    return $map
}

function New-FtpDirectoryIfMissing {
    param([string]$dirPath)
    $dirPath = ConvertTo-RemotePath $dirPath
    if ([string]::IsNullOrWhiteSpace($dirPath)) { return }
    if ($DryRun) {
        Write-Host "  [dry-run] mkdir $dirPath" -ForegroundColor DarkGray
        return
    }
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
        $code = 0
        if ($_.Exception.Response) { $code = [int]$_.Exception.Response.StatusCode }
        if ($code -ne 550 -and $code -ne 0) { throw }
    }
}

function New-FtpDirectoryRecursive {
    param([string]$remotePath)
    $remotePath = ConvertTo-RemotePath $remotePath
    if ($remotePath -eq "") { return }
    $parts = $remotePath.Split('/')
    $acc = ""
    foreach ($p in $parts) {
        if ($p -eq "") { continue }
        $acc = if ($acc -eq "") { $p } else { "$acc/$p" }
        New-FtpDirectoryIfMissing $acc
    }
}

function Send-FtpFile {
    param(
        [string]$localPath,
        [string]$remotePath,
        [int]$MaxAttempts = 3
    )
    $remotePath = ConvertTo-RemotePath $remotePath
    if ([string]::IsNullOrWhiteSpace($remotePath)) { return }
    if ($DryRun) {
        Write-Host "  [dry-run] upload $remotePath" -ForegroundColor DarkGray
        return
    }
    for ($attempt = 1; $attempt -le $MaxAttempts; $attempt++) {
        try {
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
            return
        } catch {
            if ($attempt -ge $MaxAttempts) { throw }
            $waitMs = [Math]::Min(4000, 400 * $attempt * $attempt)
            Write-Host "  Reintento $attempt/$MaxAttempts para $remotePath en ${waitMs}ms..." -ForegroundColor Yellow
            Start-Sleep -Milliseconds $waitMs
        }
    }
}

function Test-FtpConnection {
    param([string]$basePath)
    if ($DryRun) { return }
    $basePath = ConvertTo-RemotePath $basePath
    $uriPath = if ($basePath -eq "") { "" } else { "$basePath/" }
    $uri = "ftp://${ftpHost}:${ftpPort}/$uriPath"
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $Password)
    $req.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $req.UsePassive = $true
    $req.UseBinary = $true
    $req.KeepAlive = $false
    $resp = $req.GetResponse()
    $resp.Close()
}

function Push-FtpItem {
    param([string]$localFull, [string]$relativePath)
    $relativePath = ConvertTo-RemotePath $relativePath
    if (Test-Path $localFull -PathType Container) {
        $children = Get-ChildItem -Path $localFull -Force
        foreach ($c in $children) {
            $rel = if ($relativePath -eq "") { $c.Name } else { "$relativePath/$($c.Name)" }
            Push-FtpItem -localFull $c.FullName -relativePath $rel
        }
    } else {
        # No subir credenciales locales a producción
        if ($relativePath -eq 'config/deploy-credentials.php') { return }
        # No subir database.local.php: producción ya tiene su propia copia con
        # credenciales del panel de Hostinger. Sobrescribir es una mina de tiempo.
        if ($relativePath -eq 'config/database.local.php') { return }
        # No subir site.php: cada entorno tiene el suyo (p.ej. donations propias).
        if ($relativePath -eq 'config/site.php') { return }
        # Excluir vídeos de reels (el usuario los sube desde el admin)
        if ($relativePath -match '^assets/uploads/reels/.*\.(mp4|webm|mov)$') { return }
        $parent = ConvertTo-RemotePath (Split-Path -Parent $relativePath)
        if ($parent -and $parent -ne ".") {
            $remoteDir = Join-RemotePath $remoteBase $parent
            try {
                New-FtpDirectoryRecursive $remoteDir
            } catch {
                Write-Host "  Error creando dir $remoteDir : $_" -ForegroundColor Red
            }
        }
        $remoteFile = Join-RemotePath $remoteBase $relativePath
        $script:uploadCount += 1
        Write-Host "  [$script:uploadCount] $relativePath"
        try {
            Send-FtpFile -localPath $localFull -remotePath $remoteFile -MaxAttempts 3
        } catch {
            Write-Host "  Error: $_" -ForegroundColor Red
        }
        Start-Sleep -Milliseconds 50
    }
}

# Raíz del proyecto (carpeta que contiene index.php)
$root = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path (Join-Path $root "index.php"))) { $root = "c:\xampp\htdocs\valencia" }

$ErrorActionPreference = "Stop"
$phpCred = Join-Path $root "config\deploy-credentials.php"
$deployCreds = Get-PhpCredentialsMap -filePath $phpCred

if (-not $Password -and $PasswordFile) {
    $credPath = Join-Path $root $PasswordFile
    if (Test-Path $credPath) {
        $Password = (Get-Content $credPath -TotalCount 1).Trim()
    }
}
if (-not $Password -and $deployCreds.ContainsKey("hostinger_ftp_pass")) {
    $Password = $deployCreds["hostinger_ftp_pass"].Trim()
}
if (-not $Password -and $deployCreds.ContainsKey("ftp_pass")) {
    $Password = $deployCreds["ftp_pass"].Trim()
}

$ftpHost = "ftp.vcfacademyhouston.com"
if ($deployCreds.ContainsKey("hostinger_ftp_host")) { $ftpHost = $deployCreds["hostinger_ftp_host"].Trim() }
elseif ($deployCreds.ContainsKey("ftp_host")) { $ftpHost = $deployCreds["ftp_host"].Trim() }

$ftpUser = "u766140586.ansonif"
if ($deployCreds.ContainsKey("hostinger_ftp_user")) { $ftpUser = $deployCreds["hostinger_ftp_user"].Trim() }
elseif ($deployCreds.ContainsKey("ftp_user")) { $ftpUser = $deployCreds["ftp_user"].Trim() }

$ftpPort = 21
$portRaw = $null
if ($deployCreds.ContainsKey("hostinger_ftp_port")) { $portRaw = $deployCreds["hostinger_ftp_port"] }
elseif ($deployCreds.ContainsKey("ftp_port")) { $portRaw = $deployCreds["ftp_port"] }
if ($portRaw) {
    $parsedPort = 0
    if ([int]::TryParse("$portRaw", [ref]$parsedPort) -and $parsedPort -gt 0) {
        $ftpPort = $parsedPort
    }
}

$remoteRoot = ""
if ($deployCreds.ContainsKey("hostinger_ftp_remote_path")) {
    $remoteRoot = $deployCreds["hostinger_ftp_remote_path"]
}
$remoteRoot = ConvertTo-RemotePath $remoteRoot
$remoteBases = @($remoteRoot)

if ([string]::IsNullOrWhiteSpace($ftpHost) -or [string]::IsNullOrWhiteSpace($ftpUser)) {
    Write-Host "Falta configurar host o usuario FTP en config/deploy-credentials.php" -ForegroundColor Red
    exit 1
}
if (-not $Password) {
    Write-Host "Indica la contraseña FTP: -Password 'TU_CONTRASEÑA' o -PasswordFile 'config\deploy-ftp.txt'" -ForegroundColor Red
    Write-Host "O añade en config/deploy-credentials.php: 'hostinger_ftp_pass' => 'tu_contraseña_hostinger_ftp'" -ForegroundColor Yellow
    exit 1
}

$destLabel = if ([string]::IsNullOrWhiteSpace($remoteRoot)) {
    "raíz FTP (equivale a public_html si tu usuario FTP ya abre ahí)"
} else {
    "public_html del panel = carpeta remota '$remoteRoot'"
}
Write-Host "Conectando por FTP a ${ftpHost}:${ftpPort} ..." -ForegroundColor Cyan
Write-Host "Destino: $destLabel" -ForegroundColor Gray
if ($DryRun) {
    Write-Host "Modo simulación activado: no se suben archivos." -ForegroundColor Yellow
}

foreach ($base in $remoteBases) {
    try {
        if ($base -ne "") { New-FtpDirectoryRecursive $base }
        Test-FtpConnection $base
    } catch {
        Write-Host "No se pudo validar conexión FTP en '$base': $_" -ForegroundColor Red
        exit 1
    }
}

# Primero archivos sueltos, luego carpetas (para verificar conexión)
$toUpload = @(
    "index.php", "join.php", "contact.php", "calendar.php", "privacy.php", "recaudaciones.php", "deploy.php",
    "match.php", "404.php", "terms.php",
    ".htaccess",
    "robots.txt", "sitemap.xml", "sitemap.php",
    "admin", "assets", "config", "includes", "api"
)
# sql/, docs/, scripts/ excluidos: no son necesarios en producción

$script:uploadCount = 0
Write-Host "Items a subir: $($toUpload.Count)" -ForegroundColor Gray
foreach ($remoteBase in $remoteBases) {
    $label = if ($remoteBase -eq "") { "(raíz FTP)" } else { "~/$remoteBase" }
    Write-Host "Subiendo a $label ..." -ForegroundColor Cyan
    foreach ($item in $toUpload) {
        $local = Join-Path $root $item
        if (-not (Test-Path $local)) {
            Write-Host "  (omitido: $item no existe)" -ForegroundColor Yellow
            continue
        }
        try {
            Push-FtpItem -localFull $local -relativePath $item
        } catch {
            Write-Host "  Error en $item : $_" -ForegroundColor Red
        }
    }
}
Write-Host "Total procesados: $script:uploadCount archivos" -ForegroundColor Cyan
Write-Host "Listo. Comprueba https://vcfacademyhouston.com" -ForegroundColor Green
Write-Host "Asegura config/database.local.php en el servidor con los datos de MySQL de Hostinger." -ForegroundColor Yellow
