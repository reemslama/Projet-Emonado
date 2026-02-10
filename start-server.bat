@echo off
chcp 65001 >nul
title Emonado - Serveur (NE PAS FERMER)
cd /d "%~dp0"

set PORT=8080
set URL=http://127.0.0.1:%PORT%/

echo.
echo  ============================================================
echo    EMONADO - Demarrage du serveur
echo  ============================================================
echo.
echo  [1] Verification de PHP...
php -v 2>nul
if errorlevel 1 (
    echo  ERREUR : PHP introuvable. Installez PHP et ajoutez-le au PATH.
    pause
    exit /b 1
)
echo.
echo  [2] Demarrage du serveur sur le port %PORT%...
echo.
echo  ============================================================
echo    IMPORTANT - LISEZ CECI
echo  ============================================================
echo.
echo    L'application est accessible a l'adresse :
echo.
echo      %URL%
echo.
echo    - Ouvrez votre navigateur et tapez exactement cette adresse.
echo    - NE FERMEZ PAS cette fenetre tant que vous utilisez le site.
echo    - Pour arreter le serveur : fermez cette fenetre ou Ctrl+C.
echo.
echo  ============================================================
echo.
echo  Ouverture du navigateur dans 4 secondes...
echo.

ping -n 5 127.0.0.1 >nul
start "" "%URL%"

php -S 127.0.0.1:%PORT% -t public

echo.
echo  Serveur arrete.
pause
