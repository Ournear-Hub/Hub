# Keep public/hot aligned with a reachable Vite dev server (HTTP probe, not TCP-only).
$ErrorActionPreference = 'Continue'
. (Join-Path $PSScriptRoot 'local-vite-lib.ps1')

$repoRoot = Get-OurnearRepoRoot -ScriptRoot $PSScriptRoot
$expectedUrl = Get-LocalViteDevUrl -RepoRoot $repoRoot
if (Sync-PublicViteHotFile -RepoRoot $repoRoot -ExpectedUrl $expectedUrl -AllowRemoveStale) {
    exit 0
}
exit 1
