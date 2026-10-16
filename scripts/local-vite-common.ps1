# Shared helpers for OurNear Hire local Vite (Windows). Dot-source from other scripts.
$ErrorActionPreference = 'Continue'

function Get-OurnearRepoRoot {
    return Split-Path -Parent $PSScriptRoot
}

function Get-LocalViteDevUrl {
    param([string]$Root = (Get-OurnearRepoRoot))
    $devHost = '127.0.0.1'
    $devPort = 5173
    $envFile = Join-Path $Root '.env'
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
    param([string]$Root = (Get-OurnearRepoRoot))
    $url = Get-LocalViteDevUrl -Root $Root
    if ($url -match ':(\d+)/?$') {
        return [int]$Matches[1]
    }
    return 5173
}

function Get-ViteProbeUrls {
    param([string]$PrimaryUrl)
    $urls = New-Object System.Collections.Generic.List[string]
    foreach ($candidate in @($PrimaryUrl)) {
        if ($candidate -and $candidate -match '^https?://' -and -not $urls.Contains($candidate.TrimEnd('/'))) {
            [void]$urls.Add($candidate.TrimEnd('/'))
        }
    }
    $primary = $PrimaryUrl.TrimEnd('/')
    if ($primary -match '^http://127\.0\.0\.1:(\d+)$') {
        $port = $Matches[1]
        $alt = "http://localhost:$port"
        if (-not $urls.Contains($alt)) {
            [void]$urls.Add($alt)
        }
    }
    return $urls
}

function Test-ViteDevServerReachableOnce {
    param([string]$BaseUrl)
    $probeUrl = ($BaseUrl.TrimEnd('/')) + '/@vite/client'
    $curl = Get-Command curl.exe -ErrorAction SilentlyContinue
    if ($curl) {
        $code = & curl.exe -s -o NUL -w '%{http_code}' --connect-timeout 5 --max-time 10 $probeUrl 2>$null
        if ($code -match '^\d+$') {
            $status = [int]$code
            if ($status -ge 200 -and $status -lt 400) {
                return $true
            }
        }
    }
    try {
        $response = Invoke-WebRequest -Uri $probeUrl -UseBasicParsing -TimeoutSec 10
        return ($response.StatusCode -ge 200 -and $response.StatusCode -lt 400)
    } catch {
        return $false
    }
}

function Test-ViteDevServerReachable {
    param(
        [string]$BaseUrl,
        [int]$Attempts = 1,
        [int]$DelaySeconds = 2
    )
    $urls = Get-ViteProbeUrls -PrimaryUrl $BaseUrl
    for ($attempt = 1; $attempt -le $Attempts; $attempt++) {
        foreach ($url in $urls) {
            if (Test-ViteDevServerReachableOnce -BaseUrl $url) {
                return @{ Ok = $true; Url = $url }
            }
        }
        if ($attempt -lt $Attempts) {
            Start-Sleep -Seconds $DelaySeconds
        }
    }
    return @{ Ok = $false; Url = $null }
}

function Get-TcpListenersOnPort {
    param([int]$Port)
    return @(Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue)
}

function Get-ListenerProcessSummary {
    param([int]$Port)
    $rows = @()
    foreach ($conn in (Get-TcpListenersOnPort -Port $Port)) {
        $proc = Get-CimInstance Win32_Process -Filter "ProcessId=$($conn.OwningProcess)" -ErrorAction SilentlyContinue
        $rows += [PSCustomObject]@{
            Address = $conn.LocalAddress
            Port    = $conn.LocalPort
            Pid     = $conn.OwningProcess
            Name    = $proc.Name
            Command = $proc.CommandLine
        }
    }
    return $rows
}

function Test-ProcessLooksLikeOurnearVite {
    param(
        [string]$CommandLine,
        [string]$RepoRoot = (Get-OurnearRepoRoot)
    )
    if (-not $CommandLine) {
        return $false
    }
    if ($CommandLine -notmatch '(\\|\/)vite(\.cmd|\.js)?(\s|$)|npm\.cmd.*run\s+dev') {
        return $false
    }
    $rootPattern = [regex]::Escape($RepoRoot)
    return ($CommandLine -match $rootPattern)
}

function Write-PortBlockerDiagnostics {
    param(
        [int]$Port,
        [string]$RepoRoot = (Get-OurnearRepoRoot)
    )
    $listeners = Get-ListenerProcessSummary -Port $Port
    if (-not $listeners.Count) {
        Write-Host "Port $Port is not listening (TCP)."
        return
    }
    foreach ($row in $listeners) {
        Write-Host "Port $Port listener: $($row.Address):$($row.Port) pid=$($row.Pid) process=$($row.Name)"
        if ($row.Command) {
            Write-Host "  command: $($row.Command)"
        }
        if (-not (Test-ProcessLooksLikeOurnearVite -CommandLine $row.Command -RepoRoot $RepoRoot)) {
            Write-Host '  This does not look like OurNear Hire Vite from this repo root.'
        }
    }
}

function Show-LocalViteDevLogTail {
    param(
        [string]$RepoRoot = (Get-OurnearRepoRoot),
        [int]$Lines = 25
    )
    $logFile = Join-Path $RepoRoot 'storage\logs\local-vite-dev.log'
    if (-not (Test-Path $logFile)) {
        Write-Host "No Vite log at $logFile"
        return
    }
    Write-Host "--- Last $Lines lines of storage/logs/local-vite-dev.log ---"
    Get-Content $logFile -Tail $Lines -ErrorAction SilentlyContinue | ForEach-Object { Write-Host $_ }
    Write-Host '--- end Vite log ---'
}

function Sync-LocalViteHotFile {
    param(
        [string]$RepoRoot = (Get-OurnearRepoRoot),
        [int]$ProbeAttempts = 3
    )
    $hotFile = Join-Path $RepoRoot 'public\hot'
    $expectedUrl = Get-LocalViteDevUrl -Root $RepoRoot

    $health = Test-ViteDevServerReachable -BaseUrl $expectedUrl -Attempts $ProbeAttempts -DelaySeconds 2
    if ($health.Ok) {
        $healthyUrl = $health.Url
        $currentUrl = ''
        if (Test-Path $hotFile) {
            $currentUrl = (Get-Content $hotFile -Raw).Trim()
        }
        if ($currentUrl -ne $healthyUrl) {
            Set-Content -Path $hotFile -Value $healthyUrl -NoNewline -Encoding ascii
            Write-Host "Vite dev server OK; wrote public/hot -> $healthyUrl"
        } else {
            Write-Host "Vite dev server OK ($healthyUrl)."
        }
        return 0
    }

    if (Test-Path $hotFile) {
        $hotUrl = (Get-Content $hotFile -Raw).Trim()
        if ($hotUrl -match '^https?://') {
            $hotHealth = Test-ViteDevServerReachable -BaseUrl $hotUrl -Attempts $ProbeAttempts -DelaySeconds 2
            if ($hotHealth.Ok) {
                Write-Host "Vite dev server OK ($($hotHealth.Url))."
                return 0
            }
        }
        Write-Host "Vite dev server: NOT REACHABLE after $ProbeAttempts probe attempt(s); removing stale public/hot ($hotUrl)."
        Remove-Item $hotFile -Force
    }

    Write-Host 'Vite hot file: not present (using public/build manifest).'
    Write-Host 'If /jobs shows the legacy hero, filters, or split-pane layout, start Vite from this repo: npm run dev'
    return 1
}
