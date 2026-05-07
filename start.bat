@echo off
chcp 65001 >nul
echo ========================================
echo   Emonado - Demarrage du projet
echo ========================================
echo.

set PHP=C:\xampp\php\php.exe
cd /d "%~dp0"

REM Cree le dossier var si necessaire
if not exist "var" mkdir var

echo [1/4] Creation du schema (tables SQLite)...
"%PHP%" bin/console doctrine:schema:create 2>nul
if errorlevel 1 (
    "%PHP%" bin/console doctrine:schema:update --force 2>nul
)

echo [3/4] Utilisateurs par defaut...
"%PHP%" bin/console app:create-default-users 2>nul
echo.

echo [4/4] Demarrage du serveur...
echo.
echo ========================================
echo   Projet : http://127.0.0.1:8000
echo   Inscription : http://127.0.0.1:8000/register
echo   Connexion   : http://127.0.0.1:8000/login
echo   Admin       : admin@emonado.com / Admin123
echo   Psychologue : psy@emonado.com / Psy123
echo ========================================
echo.
"%PHP%" -S 127.0.0.1:8000 -t public
