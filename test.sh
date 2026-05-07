#!/bin/bash

echo "========================================"
echo "  Guide de Test - Projet Emonado"
echo "========================================"
echo ""

echo "[1/5] Vérification de PHP..."
php --version || { echo "ERREUR: PHP n'est pas installé"; exit 1; }
echo ""

echo "[2/5] Vérification de Composer..."
composer --version || { echo "ERREUR: Composer n'est pas installé"; exit 1; }
echo ""

echo "[3/5] Installation des dépendances..."
composer install || { echo "ERREUR: Échec de l'installation des dépendances"; exit 1; }
echo ""

echo "[4/5] Application des migrations..."
echo "ATTENTION: Assurez-vous que MySQL est démarré et que la base de données existe"
read -p "Appuyez sur Entrée pour continuer..."
php bin/console doctrine:migrations:migrate --no-interaction || {
    echo "ERREUR: Échec de l'application des migrations"
    echo "Vérifiez votre configuration de base de données dans .env"
    exit 1
}
echo ""

echo "[5/5] Création des utilisateurs par défaut..."
php bin/console app:create-default-users || {
    echo "ATTENTION: Échec de la création des utilisateurs (peut-être déjà créés)"
}
echo ""

echo "========================================"
echo "  Configuration terminée !"
echo "========================================"
echo ""
echo "Comptes de test créés:"
echo "  - Admin: admin@emonaso.com / Admin123"
echo "  - Psychologue: psy@emonaso.com / Psy123"
echo ""
echo "Pour démarrer le serveur:"
echo "  symfony server:start"
echo "  OU"
echo "  php -S 127.0.0.1:8080 -t public"
echo ""
echo "L'application sera accessible sur: http://127.0.0.1:8080"
echo ""
