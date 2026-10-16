# Start Vite for OurNear Hire when missing and sync public/hot (local homepage fix).
$ErrorActionPreference = 'Continue'
. (Join-Path $PSScriptRoot 'local-vite-lib.ps1')

$repoRoot = Get-OurnearRepoRoot -ScriptRoot $PSScriptRoot
Set-Location $repoRoot

$port = Get-LocalViteDevPort -RepoRoot $repoRoot
$devUrl = Get-LocalViteDevUrl -RepoRoot $repoRoot
$existing = Get-OurnearViteListener -RepoRoot $repoRoot -Port $port

if (-not $existing) {
    Write-Host "Starting Vite dev server in background (log: storage/logs/local-vite-dev.log)..."
    $logPath = Get-ViteLauncherLogPath -RepoRoot $repoRoot
    $errPath = Join-Path (Split-Path $logPath -Parent) 'local-vite-dev.stderr.log'
    Start-Process -FilePath 'npm' -ArgumentList 'run', 'dev' -WorkingDirectory $repoRoot -WindowStyle Hidden `
        -RedirectStandardOutput $logPath -RedirectStandardError $errPath | Out-Null
    $healthy = Wait-ViteDevServerHealthy -BaseUrl $devUrl -Attempts 30 -DelaySec 2 -TimeoutSec 8
    if (-not $healthy) {
        Write-Host "Vite did not become reachable; see storage/logs/local-vite-dev.log"
        Show-ViteLauncherLogTail -RepoRoot $repoRoot
        exit 1
    }
}

if (Sync-PublicViteHotFile -RepoRoot $repoRoot -ExpectedUrl $devUrl -AllowRemoveStale) {
    Write-Host "Local frontend ready: $devUrl (public/hot synced)"
    exit 0
}

exit 1
