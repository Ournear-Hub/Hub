# Diagnostic: Vite port listeners and HTTP health (OurNear Hire). Does not kill processes.
$ErrorActionPreference = 'Continue'
. (Join-Path $PSScriptRoot 'local-vite-lib.ps1')

$repoRoot = Get-OurnearRepoRoot -ScriptRoot $PSScriptRoot
$expectedUrl = Get-LocalViteDevUrl -RepoRoot $repoRoot
$devPort = Get-LocalViteDevPort -RepoRoot $repoRoot

Write-Host "=== Vite probe ($repoRoot) ==="
Write-Host "Expected Vite URL: $expectedUrl (port $devPort)"
Write-VitePortDiagnostics -RepoRoot $repoRoot -Port $devPort

$found = Find-ReachableViteDevUrl -PrimaryUrl $expectedUrl -TimeoutSec 8
Write-Host "HTTP health: $(if ($found) { 'OK at ' + $found } else { 'FAILED' })"

$hotFile = Join-Path $repoRoot 'public\hot'
Write-Host "public/hot present: $(Test-Path $hotFile)"
if (Test-Path $hotFile) {
    Write-Host "public/hot content: $((Get-Content $hotFile -Raw).Trim())"
}
