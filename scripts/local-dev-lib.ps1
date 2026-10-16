# Shared local development helpers for OurNear Hire (dot-source from launcher scripts).

function Get-OurnearRepoRootFromScript {
    param([string]$ScriptRoot = $PSScriptRoot)
    $root = (Resolve-Path -LiteralPath (Join-Path $ScriptRoot '..') -ErrorAction Stop).Path
    return $root
}

function Assert-LocalDevPrerequisites {
    param([string]$RepoRoot)

    $required = @(
        (Join-Path $RepoRoot 'public\index.php'),
        (Join-Path $RepoRoot 'scripts\local-dev-server-router.php'),
        (Join-Path $RepoRoot 'artisan'),
        (Join-Path $RepoRoot 'vendor\autoload.php'),
        (Join-Path $RepoRoot 'package.json'),
        (Join-Path $RepoRoot 'vite.config.js')
    )

    $missing = @()
    foreach ($path in $required) {
        if (-not (Test-Path -LiteralPath $path)) {
            $missing += $path
        }
    }

    if ($missing.Count -gt 0) {
        throw @(
            'Local dev prerequisites missing:',
            ($missing -join [Environment]::NewLine)
        )
    }
}

# Requires local-vite-lib.ps1 (Get-TcpListenersOnPort, Get-ProcessCommandLine) when using PHP listener helpers.

function Test-IsOurnearHirePhpDevServer {
    param(
        [string]$CommandLine,
        [string]$RepoRoot
    )
    if (-not $CommandLine) {
        return $false
    }
    if ($CommandLine -notmatch '127\.0\.0\.1:8000|localhost:8000') {
        return $false
    }
    if ($CommandLine -notmatch 'local-dev-server-router\.php') {
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
    return $false
}

function Get-OurnearPhpDevListener {
    param(
        [string]$RepoRoot,
        [int]$Port = 8000
    )
    foreach ($listener in (Get-TcpListenersOnPort -Port $Port)) {
        $info = Get-ProcessCommandLine -ProcessId $listener.OwningProcess
        if ($info -and (Test-IsOurnearHirePhpDevServer -CommandLine $info.CommandLine -RepoRoot $RepoRoot)) {
            return [PSCustomObject]@{
                Listener = $listener
                Process = $info
            }
        }
    }
    return $null
}

function Test-HttpOk {
    param(
        [string]$Url,
        [int]$TimeoutSec = 10
    )
    $curl = Get-Command curl.exe -ErrorAction SilentlyContinue
    if ($curl) {
        $code = (& curl.exe -s -o NUL -w '%{http_code}' --connect-timeout 5 --max-time $TimeoutSec $Url 2>$null | Out-String).Trim()
        if ($code -match '^\d+$') {
            $status = [int]$code
            return ($status -ge 200 -and $status -lt 500)
        }
    }
    try {
        $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec $TimeoutSec
        return ($response.StatusCode -ge 200 -and $response.StatusCode -lt 500)
    } catch {
        return $false
    }
}

function Write-LocalDevPortConflict {
    param(
        [int]$Port,
        [string]$Label
    )
    $listeners = Get-TcpListenersOnPort -Port $Port
    if (-not $listeners) {
        return
    }
    Write-Host "${Label}: port $Port is in use:"
    foreach ($l in $listeners) {
        Write-Host "  listen $($l.LocalAddress):$($l.LocalPort) pid=$($l.OwningProcess)"
        $info = Get-ProcessCommandLine -ProcessId $l.OwningProcess
        if ($info) {
            Write-Host "    $($info.Name): $($info.CommandLine)"
        }
    }
}

function Invoke-OurnearPhpCliScript {
    param(
        [Parameter(Mandatory = $true)]
        [string]$ScriptPath,
        [switch]$FailOnNonZeroExit
    )
    if (-not (Test-Path -LiteralPath $ScriptPath)) {
        throw "PHP script not found: $ScriptPath"
    }

    $prevErrorAction = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        # Merge stderr into the stream so PHP notices on stderr do not trigger NativeCommandError under Stop.
        $output = & php $ScriptPath 2>&1
        $exitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $prevErrorAction
    }

    foreach ($line in @($output)) {
        if ($null -eq $line) {
            continue
        }
        if ($line -is [System.Management.Automation.ErrorRecord]) {
            $text = $line.ToString()
            if ($text) {
                Write-Host $text
            }
        } else {
            Write-Host $line
        }
    }

    if ($FailOnNonZeroExit -and $exitCode -ne 0) {
        throw "PHP script exited with code ${exitCode}: $ScriptPath"
    }

    return $exitCode
}

function Write-LocalDevFinalStatus {
    param(
        [string]$RepoRoot,
        [bool]$MysqlOk,
        [bool]$ViteOk,
        [string]$ViteUrl,
        [bool]$HotOk,
        [string]$PhpUrl,
        [bool]$PhpAlreadyRunning
    )

    Write-Host ''
    Write-Host '========== OurNear Hire local dev status =========='
    Write-Host ("MySQL (app connection): " + $(if ($MysqlOk) { 'OK' } else { 'FAILED' }))
    Write-Host ("Vite (@vite/client):   " + $(if ($ViteOk) { "OK $ViteUrl" } else { 'NOT READY' }))
    Write-Host ("public/hot:             " + $(if ($HotOk) { 'present and aligned' } else { 'missing or stale' }))
    if ($PhpAlreadyRunning) {
        Write-Host ("PHP (Laravel):          already running - $PhpUrl (reuse existing process)")
    } else {
        Write-Host ("PHP (Laravel):          starting foreground - $PhpUrl")
    }
    Write-Host 'Document root:          public/ (via -t public + local-dev-server-router.php)'
    Write-Host 'Upload tmp dir:         ' (Join-Path $env:LOCALAPPDATA 'Temp\ournear-hire-uploads')
    Write-Host '==================================================='
    Write-Host 'Keep this terminal open while developing. Edit code in Cursor; refresh the browser for Vite HMR.'
}
