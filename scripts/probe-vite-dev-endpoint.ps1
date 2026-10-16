# Diagnostic: who listens on the configured Vite port and whether @vite/client responds.
$ErrorActionPreference = 'Continue'
. (Join-Path $PSScriptRoot 'local-vite-lib.ps1')

$repoRoot = Get-OurnearRepoRoot -ScriptRoot $PSScriptRoot
$expectedUrl = Get-LocalViteDevUrl -RepoRoot $repoRoot
$devPort = Get-LocalViteDevPort -RepoRoot $repoRoot

Write-Host "Expected Vite URL: $expectedUrl (port $devPort)"

$listeners = Get-TcpListenersOnPort -Port $devPort
if (-not $listeners) {
    Write-Host "TCP listen on port ${devPort}: NO"
} else {
    foreach ($l in $listeners) {
        Write-Host "TCP listen: $($l.LocalAddress):$($l.LocalPort) pid=$($l.OwningProcess)"
        $info = Get-ProcessCommandLine -ProcessId $l.OwningProcess
        if ($info) {
            Write-Host "  process: $($info.Name)"
            Write-Host "  cmdline: $($info.CommandLine)"
        }
        $isOurs = Test-IsOurnearHireViteProcess -CommandLine $info.CommandLine -RepoRoot $repoRoot
        Write-Host "  ournear-hire vite: $isOurs"
    }
}

foreach ($hostName in @('127.0.0.1', 'localhost', '[::1]')) {
    $url = "http://${hostName}:$devPort"
    $ok = Test-ViteDevServerReachable -BaseUrl $url -TimeoutSec 5
    Write-Host "HTTP probe $url : $(if ($ok) { 'OK' } else { 'FAIL' })"
}

$hotFile = Join-Path $repoRoot 'public\hot'
Write-Host "public/hot present: $(Test-Path $hotFile)"
if (Test-Path $hotFile) {
    Write-Host "public/hot content: $((Get-Content $hotFile -Raw).Trim())"
}
