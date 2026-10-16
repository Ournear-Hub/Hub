# Canonical OurNear Hire local dev launcher (MySQL + Vite + public/hot + PHP built-in server).
# Usage from any directory:
#   & "D:\Ournear Hub\Ournear Hire\scripts\start-local-web-server.ps1"
$ErrorActionPreference = 'Stop'

. (Join-Path $PSScriptRoot 'local-vite-lib.ps1')
. (Join-Path $PSScriptRoot 'local-dev-lib.ps1')

$repoRoot = Get-OurnearRepoRootFromScript -ScriptRoot $PSScriptRoot
Assert-LocalDevPrerequisites -RepoRoot $repoRoot

$phpUrl = 'http://127.0.0.1:8000'
$viteUrl = Get-LocalViteDevUrl -RepoRoot $repoRoot
$vitePort = Get-LocalViteDevPort -RepoRoot $repoRoot
$hotFile = Join-Path $repoRoot 'public\hot'
$serverRouter = Join-Path $repoRoot 'scripts\local-dev-server-router.php'
$publicDir = Join-Path $repoRoot 'public'

Write-Host "OurNear Hire repo: $repoRoot"

# --- MySQL (service + Laravel PDO probe, not TCP-only) ---
& (Join-Path $repoRoot 'scripts\ensure-local-mysql84.ps1')
$mysqlOk = ($LASTEXITCODE -eq 0)
if (-not $mysqlOk) {
    throw 'MySQL is not reachable for Laravel. Fix MySQL84 service and .env database settings, then retry.'
}

# --- Vite (HTTP @vite/client health required for dev-ready) ---
& (Join-Path $repoRoot 'scripts\ensure-local-vite-dev.ps1')
$viteOk = ($LASTEXITCODE -eq 0)
if (-not $viteOk) {
    throw @(
        'Vite dev is not healthy. Laravel must not run with stale public/build while claiming dev is ready.',
        "Read storage/logs/local-vite-dev.log and storage/logs/local-vite-dev.stderr.log",
        "Or run manually from repo root: npm run dev",
        "Expected probe: $viteUrl/@vite/client"
    )
}

$hotOk = (Test-Path $hotFile)
if ($hotOk) {
    $hotContent = (Get-Content $hotFile -Raw).Trim()
    Write-Host "public/hot -> $hotContent"
} else {
    throw 'Vite reported healthy but public/hot is missing. Re-run ensure-local-vite-dev.ps1 or sync-local-vite-hot.ps1.'
}

# --- Build integrity (diagnostics; Vite dev is authoritative for assets) ---
$auditExit = Invoke-OurnearPhpCliScript -ScriptPath (Join-Path $repoRoot 'scripts\audit-public-build-integrity.php')
if ($auditExit -ne 0) {
    Write-Host "public/build audit exit $auditExit (informational while Vite dev is healthy)."
}
$syncExit = Invoke-OurnearPhpCliScript -ScriptPath (Join-Path $repoRoot 'scripts\sync-local-homepage-build.php') -FailOnNonZeroExit
if ($syncExit -ne 0) {
    throw "Homepage/build sync failed with exit code $syncExit."
}

# --- PHP built-in server (document root = public) ---
$existingPhp = Get-OurnearPhpDevListener -RepoRoot $repoRoot -Port 8000
if ($existingPhp) {
    $phpHealthy = Test-HttpOk -Url $phpUrl -TimeoutSec 15
    if ($phpHealthy) {
        Write-LocalDevFinalStatus -RepoRoot $repoRoot -MysqlOk $true -ViteOk $true -ViteUrl $viteUrl `
            -HotOk $hotOk -PhpUrl $phpUrl -PhpAlreadyRunning $true
        Write-Host ''
        Write-Host "Laravel is already listening (PID $($existingPhp.Process.ProcessId)). No second PHP server started."
        Write-Host 'To restart PHP: stop that process, then run this script again.'
        exit 0
    }
    throw @(
        "Port 8000 is held by this project's PHP dev server (PID $($existingPhp.Process.ProcessId)) but HTTP probe failed.",
        'Stop the stale PHP process and re-run this script.'
    )
}

$foreign = Get-TcpListenersOnPort -Port 8000
if ($foreign) {
    $phpHealthy = Test-HttpOk -Url $phpUrl -TimeoutSec 15
    if ($phpHealthy) {
        $phpListenerPid = ($foreign | Select-Object -First 1).OwningProcess
        Write-LocalDevFinalStatus -RepoRoot $repoRoot -MysqlOk $true -ViteOk $true -ViteUrl $viteUrl `
            -HotOk $hotOk -PhpUrl $phpUrl -PhpAlreadyRunning $true
        Write-Host ''
        Write-Host "Port 8000 is in use (PID $phpListenerPid) but HTTP probe succeeded. Reusing existing PHP listener."
        exit 0
    }
    Write-LocalDevPortConflict -Port 8000 -Label 'PHP conflict'
    throw 'Port 8000 is in use by another process. Free the port or change the local PHP port in this script.'
}

$uploadTmp = Join-Path $env:LOCALAPPDATA 'Temp\ournear-hire-uploads'
New-Item -ItemType Directory -Force -Path $uploadTmp | Out-Null
$uploadTmpForward = ($uploadTmp -replace '\\', '/')

Remove-Item Env:PHP_CLI_SERVER_WORKERS -ErrorAction SilentlyContinue
$phpZts = & php -r 'echo PHP_ZTS ? "1" : "0";'
if ($LASTEXITCODE -eq 0 -and $phpZts -eq '1') {
    if (-not $env:PHP_CLI_SERVER_WORKERS) {
        $env:PHP_CLI_SERVER_WORKERS = '4'
    }
    Write-Host "PHP ZTS detected; PHP_CLI_SERVER_WORKERS=$($env:PHP_CLI_SERVER_WORKERS)"
} else {
    Write-Host 'PHP NTS: single-request built-in server (worker forking not used on this build).'
}

Write-LocalDevFinalStatus -RepoRoot $repoRoot -MysqlOk $true -ViteOk $true -ViteUrl $viteUrl `
    -HotOk $hotOk -PhpUrl $phpUrl -PhpAlreadyRunning $false

Write-Host ''
Write-Host "Starting PHP: php -S 127.0.0.1:8000 -t public $serverRouter"
Write-Host "upload_tmp_dir=$uploadTmpForward"
Write-Host ''

Set-Location -LiteralPath $repoRoot
& php "-dupload_tmp_dir=$uploadTmpForward" -S '127.0.0.1:8000' -t $publicDir $serverRouter
