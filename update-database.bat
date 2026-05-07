@echo off
chcp 65001 >nul
title Mise à jour de la base de données - Emonado
cd /d "%~dp0"

echo.
echo  ============================================================
echo    Mise à jour de la base de données (Doctrine)
echo  ============================================================
echo.
echo  Assurez-vous que MySQL/MariaDB est démarré et que
echo  DATABASE_URL dans .env est correct.
echo.

echo  [1/4] Synchronisation du stockage des migrations...
php bin/console doctrine:migrations:sync-metadata-storage 2>nul
echo.

echo  [2/4] Création de la base si elle n'existe pas...
php bin/console doctrine:database:create 2>nul
echo  (ignoré si la base existe déjà)
echo.

echo  [3/4] Mise à jour du schéma (tables/colonnes manquants)...
php bin/console doctrine:schema:update --force
if errorlevel 1 (
    echo.
    echo  ERREUR : La mise à jour du schéma a échoué.
    echo  Vérifiez que MySQL est démarré et que .env contient
    echo  la bonne DATABASE_URL (ex: mysql://root:motdepasse@127.0.0.1:3306/emonado_db^)
    echo.
    pause
    exit /b 1
)
echo.

echo  [4/4] Vérification du schéma...
php bin/console doctrine:schema:validate
echo.

echo  ============================================================
echo    Terminé. Base à jour.
echo  ============================================================
echo.
pause
