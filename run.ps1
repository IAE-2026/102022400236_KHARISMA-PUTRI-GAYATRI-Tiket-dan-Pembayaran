# Script menjalankan Ticket & Payment Service (Docker)
$ErrorActionPreference = "Stop"

Write-Host "=== Ticket & Payment Service ===" -ForegroundColor Cyan

# Cek Docker
try {
    docker info *> $null
} catch {
    Write-Host "`n[ERROR] Docker Desktop belum berjalan." -ForegroundColor Red
    Write-Host "Buka Docker Desktop, tunggu sampai status Running, lalu jalankan lagi:" -ForegroundColor Yellow
    Write-Host "  .\run.ps1`n"
    exit 1
}

Set-Location $PSScriptRoot

# Pastikan .env ada
if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "[INFO] File .env dibuat dari .env.example" -ForegroundColor Green
}

Write-Host "[1/3] Build & start container..." -ForegroundColor Yellow
docker compose up -d --build

Write-Host "[2/3] Migrasi database..." -ForegroundColor Yellow
Start-Sleep -Seconds 8
docker compose exec app php artisan migrate --force --seed

Write-Host "[3/3] Generate Swagger docs..." -ForegroundColor Yellow
docker compose exec app php artisan l5-swagger:generate

Write-Host "`n=== Service siap! ===" -ForegroundColor Green
Write-Host "  Swagger UI : http://localhost:8000/api/documentation"
Write-Host "  API Status : http://localhost:8000/api/v1/status"
Write-Host "  GraphQL    : http://localhost:8000/graphql"
Write-Host "`nHeader wajib: X-IAE-KEY = 102022400236`n"
