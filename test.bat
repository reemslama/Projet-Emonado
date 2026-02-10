@echo off
echo ========================================
echo   Guide de Test - Projet Emonado
echo ========================================
echo.

echo [1/5] Verification de PHP...
php --version
if %errorlevel% neq 0 (
    echo ERREUR: PHP n'est pas installe ou pas dans le PATH
    pause
    exit /b 1
)
echo.

echo [2/5] Verification de Composer...
composer --version
if %errorlevel% neq 0 (
    echo ERREUR: Composer n'est pas installe
    pause
    exit /b 1
)
echo.

echo [3/5] Installation des dependances...
call composer install
if %errorlevel% neq 0 (
    echo ERREUR: Echec de l'installation des dependances
    pause
    exit /b 1
)
echo.

echo [4/5] Application des migrations...
echo ATTENTION: Assurez-vous que MySQL est demarre et que la base de donnees existe
echo Appuyez sur une touche pour continuer...
pause >nul
php bin/console doctrine:migrations:migrate --no-interaction
if %errorlevel% neq 0 (
    echo ERREUR: Echec de l'application des migrations
    echo Verifiez votre configuration de base de donnees dans .env
    pause
    exit /b 1
)
echo.

echo [5/5] Creation des utilisateurs par defaut...
php bin/console app:create-default-users
if %errorlevel% neq 0 (
    echo ATTENTION: Echec de la creation des utilisateurs (peut-etre deja crees)
)
echo.

echo ========================================
echo   Configuration terminee !
echo ========================================
echo.
echo Comptes de test crees:
echo   - Admin: admin@emonaso.com / Admin123
echo   - Psychologue: psy@emonaso.com / Psy123
echo.
echo Pour demarrer le serveur:
echo   symfony server:start
echo   OU
echo   php -S localhost:8000 -t public
echo.
echo L'application sera accessible sur: http://localhost:8000
echo.
pause
