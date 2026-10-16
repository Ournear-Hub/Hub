# Ensure Windows MySQL84 is running and Laravel can connect (OurNear Hire local dev).
$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot

$service = Get-Service -Name 'MySQL84' -ErrorAction SilentlyContinue
if (-not $service) {
    throw 'MySQL84 Windows service was not found. Install or repair MySQL 8.4 for local development.'
}

if ($service.Status -ne 'Running') {
    Write-Host 'MySQL84 service is stopped; attempting to start...'
    try {
        Start-Service -Name 'MySQL84' -ErrorAction Stop
        Start-Sleep -Seconds 3
        $service = Get-Service -Name 'MySQL84'
    } catch {
        Write-Host 'Start-Service failed (often needs Administrator). Checking TCP 3306 and Laravel PDO next...'
    }
}

$tcp = Test-NetConnection -ComputerName 127.0.0.1 -Port 3306 -WarningAction SilentlyContinue
if (-not $tcp.TcpTestSucceeded) {
    throw @(
        'MySQL is not reachable on 127.0.0.1:3306.',
        'Open PowerShell as Administrator and run:  Start-Service MySQL84',
        'Then verify:  Get-Service MySQL84'
    )
}

Push-Location $repoRoot
php (Join-Path $repoRoot 'scripts\verify-local-mysql-connection.php')
if ($LASTEXITCODE -ne 0) {
    Pop-Location
    throw 'Laravel cannot connect to MySQL with the current .env database settings.'
}
Pop-Location

if ($service.Status -ne 'Running') {
    Write-Host 'Note: MySQL84 Windows service is not Running, but mysqld is listening and Laravel connected (MYSQL_OK).'
    Write-Host 'For a clean state after reboot, run as Administrator:  Start-Service MySQL84'
}
