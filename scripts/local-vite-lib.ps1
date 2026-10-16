# Shared local Vite helpers for OurNear Hire (dot-source from other scripts).

function Get-OurnearRepoRoot {
    param([string]$ScriptRoot = $PSScriptRoot)
    Split-Path -Parent $ScriptRoot
}

function Get-LocalViteDevUrl {
    param([string]$RepoRoot)
    $devHost = '127.0.0.1'
    $devPort = 5173
    $envFile = Join-Path $RepoRoot '.env'
    if (Test-Path $envFile) {
        foreach ($line in Get-Content $envFile -ErrorAction SilentlyContinue) {
            if ($line -match '^\s*VITE_DEV_HOST=(.+)$') {
                $devHost = $Matches[1].Trim().Trim('"').Trim("'")
            }
            if ($line -match '^\s*VITE_DEV_PORT=(.+)$') {
                $devPort = [int]$Matches[1].Trim().Trim('"').Trim("'")
            }
        }
    }
    return "http://${devHost}:$devPort"
}

function Get-LocalViteDevPort {
    param([string]$RepoRoot)
    $url = Get-LocalViteDevUrl -RepoRoot $RepoRoot
    if ($url -match ':(\d+)$') {
        return [int]$Matches[1]
    }
    return 5173
}

function Get-ViteProbeBaseUrls {
    param([string]$PrimaryUrl)
    $urls = New-Object System.Collections.Generic.List[string]
    foreach ($candidate in @($PrimaryUrl)) {
        $trimmed = $candidate.TrimEnd('/')
        if ($trimmed -match '^https?://' -and -not $urls.Contains($trimmed)) {
            [void]$urls.Add($trimmed)
        }
    }
    if ($PrimaryUrl -match '^http://127\.0\.0\.1:(\d+)$') {
        $alt = "http://localhost:$($Matches[1])"
        if (-not $urls.Contains($alt)) {
            [void]$urls.Add($alt)
        }
    }
    return $urls
}

function Get-TcpListenersOnPort {
    param([int]$Port)
    Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
        Select-Object LocalAddress, LocalPort, OwningProcess -Unique
}

function Get-ProcessCommandLine {
    param([int]$ProcessId)
    $proc = Get-CimInstance Win32_Process -Filter "ProcessId=$ProcessId" -ErrorAction SilentlyContinue
    if (-not $proc) {
        return $null
    }
    return [PSCustomObject]@{
        ProcessId = $ProcessId
        Name = $proc.Name
        CommandLine = $proc.CommandLine
    }
}

function Test-IsOurnearHireViteProcess {
    param(
        [string]$CommandLine,
        [string]$RepoRoot
    )
    if (-not $CommandLine) {
        return $false
    }
    if ($CommandLine -notmatch 'vite') {
        return $false
    }
    $rootVariants = @(
        $RepoRoot,
        ($RepoRoot -replace '\\', '/'),
        ($RepoRoot -replace '\\', '\\\\')
    )
    foreach ($variant in $rootVariants) {
        if ($CommandLine -like "*$variant*") {
            return $true
        }
    }
    # npm.cmd often omits the repo path from the parent line; the node child includes node_modules/vite.
    if ($CommandLine -match '[\\/]node_modules[\\/]vite[\\/]' -and $CommandLine -match 'Ournear Hire') {
        return $true
    }
    return $false
}

function Get-OurnearViteListener {
    param(
        [string]$RepoRoot,
        [int]$Port
    )
    foreach ($listener in (Get-TcpListenersOnPort -Port $Port)) {
        $info = Get-ProcessCommandLine -ProcessId $listener.OwningProcess
        if ($info -and (Test-IsOurnearHireViteProcess -CommandLine $info.CommandLine -RepoRoot $RepoRoot)) {
            return [PSCustomObject]@{
                Listener = $listener
                Process = $info
            }
        }
    }
    return $null
}

function Test-ViteDevServerReachableOnce {
    param(
        [string]$BaseUrl,
        [int]$TimeoutSec = 8
    )
    $probeUrl = ($BaseUrl.TrimEnd('/')) + '/@vite/client'
    $curl = Get-Command curl.exe -ErrorAction SilentlyContinue
    if ($curl) {
        $code = (& curl.exe -s -o NUL -w '%{http_code}' --connect-timeout 5 --max-time $TimeoutSec $probeUrl 2>$null | Out-String).Trim()
        if ($code -match '^\d+$') {
            $status = [int]$code
            if ($status -ge 200 -and $status -lt 400) {
                return $true
            }
        }
    }
    try {
        $response = Invoke-WebRequest -Uri $probeUrl -UseBasicParsing -TimeoutSec $TimeoutSec
        return ($response.StatusCode -ge 200 -and $response.StatusCode -lt 400)
    } catch {
        return $false
    }
}

function Find-ReachableViteDevUrl {
    param(
        [string]$PrimaryUrl,
        [int]$TimeoutSec = 8
    )
    foreach ($baseUrl in (Get-ViteProbeBaseUrls -PrimaryUrl $PrimaryUrl)) {
        if (Test-ViteDevServerReachableOnce -BaseUrl $baseUrl -TimeoutSec $TimeoutSec) {
            return $baseUrl
        }
    }
    return $null
}

function Test-ViteDevServerReachable {
    param(
        [string]$BaseUrl,
        [int]$TimeoutSec = 8
    )
    return [bool](Find-ReachableViteDevUrl -PrimaryUrl $BaseUrl -TimeoutSec $TimeoutSec)
}

function Wait-ViteDevServerHealthy {
    param(
        [string]$BaseUrl,
        [int]$Attempts = 25,
        [int]$DelaySec = 2,
        [int]$TimeoutSec = 8
    )
    for ($i = 1; $i -le $Attempts; $i++) {
        $found = Find-ReachableViteDevUrl -PrimaryUrl $BaseUrl -TimeoutSec $TimeoutSec
        if ($found) {
            return $found
        }
        if ($i -lt $Attempts) {
            Start-Sleep -Seconds $DelaySec
        }
    }
    return $null
}

function Write-VitePortDiagnostics {
    param(
        [string]$RepoRoot,
        [int]$Port
    )
    $listeners = Get-TcpListenersOnPort -Port $Port
    if (-not $listeners) {
        Write-Host "TCP listen on port ${Port}: NO"
        return
    }
    foreach ($l in $listeners) {
        Write-Host "TCP listen: $($l.LocalAddress):$($l.LocalPort) pid=$($l.OwningProcess)"
        $info = Get-ProcessCommandLine -ProcessId $l.OwningProcess
        if ($info) {
            Write-Host "  process: $($info.Name)"
            Write-Host "  cmdline: $($info.CommandLine)"
        }
        $isOurs = Test-IsOurnearHireViteProcess -CommandLine $info.CommandLine -RepoRoot $RepoRoot
        Write-Host "  ournear-hire vite: $isOurs"
    }
}

function Sync-PublicViteHotFile {
    param(
        [string]$RepoRoot,
        [string]$ExpectedUrl,
        [switch]$AllowRemoveStale
    )
    $hotFile = Join-Path $RepoRoot 'public\hot'
    $devPort = Get-LocalViteDevPort -RepoRoot $RepoRoot
    $healthyUrl = Wait-ViteDevServerHealthy -BaseUrl $ExpectedUrl -Attempts 8 -DelaySec 1 -TimeoutSec 8

    if ($healthyUrl) {
        $hotUrl = $ExpectedUrl
        if (-not (Test-ViteDevServerReachableOnce -BaseUrl $hotUrl -TimeoutSec 8)) {
            $hotUrl = $healthyUrl
        }
        $currentUrl = ''
        if (Test-Path $hotFile) {
            $currentUrl = (Get-Content $hotFile -Raw).Trim()
        }
        if ($currentUrl -ne $hotUrl) {
            Set-Content -Path $hotFile -Value $hotUrl -NoNewline -Encoding ascii
            Write-Host "Vite dev server OK; wrote public/hot -> $hotUrl"
        } else {
            Write-Host "Vite dev server OK ($hotUrl)."
        }
        return $true
    }

    if (Test-Path $hotFile) {
        $hotUrl = (Get-Content $hotFile -Raw).Trim()
        if ($hotUrl -match '^https?://') {
            $hotHealthy = Wait-ViteDevServerHealthy -BaseUrl $hotUrl -Attempts 5 -DelaySec 1 -TimeoutSec 8
            if ($hotHealthy) {
                Write-Host "Vite dev server OK ($hotHealthy)."
                return $true
            }
        }
        $ourVite = Get-OurnearViteListener -RepoRoot $RepoRoot -Port $devPort
        $listenersOnPort = Get-TcpListenersOnPort -Port $devPort
        $foreignListener = $listenersOnPort | Where-Object {
            $info = Get-ProcessCommandLine -ProcessId $_.OwningProcess
            -not (Test-IsOurnearHireViteProcess -CommandLine $info.CommandLine -RepoRoot $RepoRoot)
        }
        if ($AllowRemoveStale -and $foreignListener -and -not $ourVite) {
            Write-Host "Vite dev server: NOT REACHABLE; port $devPort held by a non-OurNear process; removing stale public/hot ($hotUrl)."
            Remove-Item $hotFile -Force
        } elseif ($AllowRemoveStale -and -not $ourVite -and -not $listenersOnPort) {
            Write-Host "Vite dev server: NOT REACHABLE after retries; removing stale public/hot ($hotUrl)."
            Remove-Item $hotFile -Force
        } elseif ($ourVite) {
            Write-Host "Vite dev server: HTTP probe failed but this repo's Vite is still listening (PID $($ourVite.Process.ProcessId)); keeping public/hot."
        } else {
            Write-Host "Vite dev server: NOT REACHABLE yet; keeping public/hot ($hotUrl)."
        }
    } else {
        Write-Host 'Vite hot file: not present (using public/build manifest).'
        Write-Host 'If /jobs shows the legacy hero, filters, or split-pane layout, start Vite from this repo: npm run dev'
    }

    return $false
}

function Get-ViteLauncherLogPath {
    param([string]$RepoRoot)
    Join-Path $RepoRoot 'storage\logs\local-vite-dev.log'
}

function Show-ViteLauncherLogTail {
    param(
        [string]$RepoRoot,
        [int]$Lines = 25
    )
    $logFile = Get-ViteLauncherLogPath -RepoRoot $RepoRoot
    if (-not (Test-Path $logFile)) {
        Write-Host "No Vite log at $logFile"
        return
    }
    Write-Host "--- Last $Lines lines of storage/logs/local-vite-dev.log ---"
    Get-Content $logFile -Tail $Lines -ErrorAction SilentlyContinue | ForEach-Object { Write-Host $_ }
    $errFile = Join-Path (Split-Path $logFile -Parent) 'local-vite-dev.stderr.log'
    if (Test-Path $errFile) {
        Write-Host "--- Last $Lines lines of storage/logs/local-vite-dev.stderr.log ---"
        Get-Content $errFile -Tail $Lines -ErrorAction SilentlyContinue | ForEach-Object { Write-Host $_ }
    }
    Write-Host '--- end Vite log ---'
}
