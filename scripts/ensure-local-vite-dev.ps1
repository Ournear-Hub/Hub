# Ensure OurNear Hire Vite is HTTP-healthy before public/hot is trusted (Windows local dev).
$ErrorActionPreference = 'Continue'
. (Join-Path $PSScriptRoot 'local-vite-lib.ps1')

$repoRoot = Get-OurnearRepoRoot -ScriptRoot $PSScriptRoot
$viteUrl = Get-LocalViteDevUrl -RepoRoot $repoRoot
$vitePort = Get-LocalViteDevPort -RepoRoot $repoRoot
$logFile = Get-ViteLauncherLogPath -RepoRoot $repoRoot
$logsDir = Split-Path $logFile -Parent
if (-not (Test-Path $logsDir)) {
    New-Item -ItemType Directory -Force -Path $logsDir | Out-Null
}

$alreadyHealthy = Wait-ViteDevServerHealthy -BaseUrl $viteUrl -Attempts 2 -DelaySec 1 -TimeoutSec 8
if ($alreadyHealthy) {
    Write-Host "Vite dev server already healthy at $alreadyHealthy."
    if (Sync-PublicViteHotFile -RepoRoot $repoRoot -ExpectedUrl $viteUrl) {
        exit 0
    }
    exit 1
}

$ourVite = Get-OurnearViteListener -RepoRoot $repoRoot -Port $vitePort
$anyListener = Get-TcpListenersOnPort -Port $vitePort

if ($anyListener -and -not $ourVite) {
    Write-Host "Port $vitePort is in use but is not OurNear Hire Vite, and HTTP probe failed for $viteUrl."
    Write-VitePortDiagnostics -RepoRoot $repoRoot -Port $vitePort
    Write-Host @(
        "Cannot start Vite with strictPort on $vitePort.",
        'Stop the process above or set VITE_DEV_PORT in .env (and restart Vite from this repo root).'
    )
    Sync-PublicViteHotFile -RepoRoot $repoRoot -ExpectedUrl $viteUrl -AllowRemoveStale | Out-Null
    exit 1
}

if ($ourVite) {
    Write-Host "OurNear Hire Vite is listening (PID $($ourVite.Process.ProcessId)); waiting for HTTP health..."
    $warmed = Wait-ViteDevServerHealthy -BaseUrl $viteUrl -Attempts 30 -DelaySec 2 -TimeoutSec 8
    if ($warmed) {
        Write-Host "Vite dev server healthy at $warmed."
        if (Sync-PublicViteHotFile -RepoRoot $repoRoot -ExpectedUrl $viteUrl) {
            exit 0
        }
    }
    Show-ViteLauncherLogTail -RepoRoot $repoRoot
    Sync-PublicViteHotFile -RepoRoot $repoRoot -ExpectedUrl $viteUrl | Out-Null
    exit 1
}

Write-Host "Starting Vite (npm run dev) for $viteUrl ..."
if (Test-Path $logFile) {
    Add-Content -Path $logFile -Value "`n--- ensure-local-vite-dev $(Get-Date -Format o) ---`n"
}

$logErr = Join-Path $logsDir 'local-vite-dev.stderr.log'
Start-Process -FilePath 'npm.cmd' -ArgumentList @('run', 'dev') -WorkingDirectory $repoRoot -WindowStyle Hidden `
    -RedirectStandardOutput $logFile -RedirectStandardError $logErr | Out-Null

$ready = Wait-ViteDevServerHealthy -BaseUrl $viteUrl -Attempts 45 -DelaySec 2 -TimeoutSec 8
if ($ready) {
    Write-Host "Vite dev server healthy at $ready."
    if (Sync-PublicViteHotFile -RepoRoot $repoRoot -ExpectedUrl $viteUrl) {
        exit 0
    }
}

Write-Host 'Vite did not respond to HTTP health checks within ~90s.'
Write-VitePortDiagnostics -RepoRoot $repoRoot -Port $vitePort
Show-ViteLauncherLogTail -RepoRoot $repoRoot
Write-Host 'Recovery: from the repo root run npm run dev, or fix errors in storage/logs/local-vite-dev.log.'
Sync-PublicViteHotFile -RepoRoot $repoRoot -ExpectedUrl $viteUrl -AllowRemoveStale | Out-Null
exit 1
