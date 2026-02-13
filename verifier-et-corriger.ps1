# Script pour verifier et corriger les erreurs du projet Emonado
# Executer dans PowerShell : .\verifier-et-corriger.ps1

$ErrorActionPreference = "Stop"
$projectRoot = $PSScriptRoot
Set-Location $projectRoot

Write-Host "=== 1. Vidage du cache ===" -ForegroundColor Cyan
php bin/console cache:clear
if ($LASTEXITCODE -ne 0) { Write-Host "ERREUR cache:clear" -ForegroundColor Red; exit 1 }
Write-Host "OK" -ForegroundColor Green

Write-Host "`n=== 2. Verification du conteneur (lint:container) ===" -ForegroundColor Cyan
php bin/console lint:container
if ($LASTEXITCODE -ne 0) { Write-Host "ERREUR lint:container" -ForegroundColor Red; exit 1 }
Write-Host "OK" -ForegroundColor Green

Write-Host "`n=== 3. Verification des YAML (lint:yaml) ===" -ForegroundColor Cyan
php bin/console lint:yaml config/
if ($LASTEXITCODE -ne 0) { Write-Host "ERREUR lint:yaml" -ForegroundColor Red; exit 1 }
Write-Host "OK" -ForegroundColor Green

Write-Host "`n=== 4. Verification des templates Twig ===" -ForegroundColor Cyan
php bin/console lint:twig templates/
if ($LASTEXITCODE -ne 0) { Write-Host "ERREUR lint:twig" -ForegroundColor Red; exit 1 }
Write-Host "OK" -ForegroundColor Green

Write-Host "`n=== 5. Synchronisation du schéma BDD (doctrine:schema:update) ===" -ForegroundColor Cyan
php bin/console doctrine:schema:update --force
if ($LASTEXITCODE -ne 0) { Write-Host "ERREUR schema:update" -ForegroundColor Red; exit 1 }
Write-Host "OK" -ForegroundColor Green

Write-Host "`n=== Toutes les verifications et corrections sont terminees. ===" -ForegroundColor Green
Write-Host "Vous pouvez lancer l'application: start-server.bat ou php -S 127.0.0.1:8080 -t public" -ForegroundColor Yellow
