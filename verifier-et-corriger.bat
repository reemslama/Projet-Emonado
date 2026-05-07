@echo off
chcp 65001 >nul
cd /d "%~dp0"

echo === 1. Vidage du cache ===
php bin/console cache:clear
if errorlevel 1 (echo ERREUR cache:clear & exit /b 1)

echo.
echo === 2. Verification du conteneur ===
php bin/console lint:container
if errorlevel 1 (echo ERREUR lint:container & exit /b 1)

echo.
echo === 3. Verification des YAML ===
php bin/console lint:yaml config/
if errorlevel 1 (echo ERREUR lint:yaml & exit /b 1)

echo.
echo === 4. Verification des templates Twig ===
php bin/console lint:twig templates/
if errorlevel 1 (echo ERREUR lint:twig & exit /b 1)

echo.
echo === 5. Synchronisation du schema BDD ===
php bin/console doctrine:schema:update --force
if errorlevel 1 (echo ERREUR schema:update & exit /b 1)

echo.
echo === Toutes les verifications et corrections sont terminees. ===
pause
