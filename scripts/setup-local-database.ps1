# Local only: create DB and migrate after DB_PASSWORD is set in .env
$ErrorActionPreference = "Stop"
Set-Location (Split-Path -Parent $PSScriptRoot)

Write-Host "Probing local MySQL (no secrets printed)..."
php scripts/probe_local_mysql.php
if ($LASTEXITCODE -ne 0) {
    Write-Host "Set DB_USERNAME and DB_PASSWORD in .env for your local MySQL, then re-run."
    exit 1
}

php artisan migrate --force --no-interaction
php artisan db:seed --class=LocalDevelopmentSeeder --force --no-interaction
php artisan storage:link --force 2>$null

Write-Host "Local database setup finished."
