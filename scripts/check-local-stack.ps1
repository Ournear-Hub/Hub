# Non-destructive local stack probe for OurNear Hire (no secrets printed).
$ErrorActionPreference = 'Continue'
Write-Host '=== OurNear Hire local stack ==='
$svc = Get-Service MySQL84 -ErrorAction SilentlyContinue
if ($svc) {
    Write-Host "MySQL84 service: $($svc.Status)"
} else {
    Write-Host 'MySQL84 service: NOT FOUND'
}
$port3306 = Test-NetConnection 127.0.0.1 -Port 3306 -WarningAction SilentlyContinue
Write-Host "TCP 3306 open: $($port3306.TcpTestSucceeded)"
$port8000 = Test-NetConnection 127.0.0.1 -Port 8000 -WarningAction SilentlyContinue
Write-Host "TCP 8000 open: $($port8000.TcpTestSucceeded)"
$repoRoot = Split-Path -Parent $PSScriptRoot
. (Join-Path $PSScriptRoot 'local-vite-lib.ps1')
$viteUrl = Get-LocalViteDevUrl -RepoRoot $repoRoot
$viteHealthy = Find-ReachableViteDevUrl -PrimaryUrl $viteUrl -TimeoutSec 8
Write-Host "Vite HTTP healthy ($viteUrl): $(if ($viteHealthy) { $viteHealthy } else { 'NO' })"
$hotFile = Join-Path $repoRoot 'public\hot'
Write-Host "public/hot present: $(Test-Path $hotFile)"
$jobsIndexSrc = Join-Path $repoRoot 'resources\js\Pages\Jobs\Index.tsx'
$jobsIndexBuild = Get-ChildItem -Path (Join-Path $repoRoot 'public\build\assets') -Filter 'Index-*.js' -ErrorAction SilentlyContinue | Select-Object -First 1
if ((Test-Path $jobsIndexSrc) -and ($null -ne $jobsIndexBuild)) {
    if ((Get-Item $jobsIndexSrc).LastWriteTimeUtc -gt $jobsIndexBuild.LastWriteTimeUtc -and -not $viteHealthy) {
        Write-Warning 'Jobs/Index.tsx is newer than public/build and Vite is down — /jobs likely shows the legacy UI. Run npm run dev or npm run build.'
    }
}
Push-Location $repoRoot
php scripts/sync-local-homepage-build.php 2>&1 | ForEach-Object {
    if ($_ -match 'Build integrity:') { Write-Warning $_ }
}
Pop-Location
Push-Location $repoRoot
& (Join-Path $repoRoot 'scripts\sync-local-vite-hot.ps1')
Pop-Location
Push-Location $repoRoot
php scripts/audit-public-build-integrity.php
php scripts/verify-local-env.php
php scripts/probe_local_mysql.php
Pop-Location
Write-Host 'If MySQL84 is Stopped, start it from an elevated PowerShell: Start-Service MySQL84'
Write-Host 'Then run: scripts/start-local-web-server.ps1'
