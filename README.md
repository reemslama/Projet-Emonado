# Emonado - Module de Gestion des Utilisateurs

Application Symfony de gestion de dossiers médicaux pour psychologues et patients.

## 🚀 Démarrage Rapide

### Prérequis
- PHP 8.1+
- Composer
- MySQL/MariaDB

### Installation

1. **Installer les dépendances**
```bash
composer install
```

2. **Configurer la base de données**
   
   Modifiez le fichier `.env` avec vos identifiants MySQL :
   ```
   DATABASE_URL="mysql://root:VOTRE_MOT_DE_PASSE@127.0.0.1:3306/emonado_db?serverVersion=8.0"
   ```

3. **Mettre à jour la base de données**
   
   Démarrer MySQL, puis double-cliquez sur **`update-database.bat`**  
   (ou en ligne de commande : `php bin/console doctrine:schema:update --force`)
   
   Voir **BASE-DE-DONNEES.txt** en cas de problème.

4. **Créer les utilisateurs par défaut** (après la mise à jour de la base)
```bash
php bin/console app:create-default-users
```

5. **Démarrer le serveur**
```bash
# Option 1 : Script Windows (double-clic)
start-server.bat

# Option 2 : PHP Built-in Server (port 8080)
php -S 127.0.0.1:8080 -t public

# Option 3 : Symfony CLI
symfony server:start
```

6. **Accéder à l'application**
   - Ouvrez votre navigateur et allez à : **http://127.0.0.1:8080**
   - En cas d'erreur "connexion refusée", voir **DEMARRAGE.txt**

## 👥 Comptes de Test

Après avoir exécuté `app:create-default-users`, vous pouvez vous connecter avec :

- **Administrateur**
  - Email : `admin@emonaso.com`
  - Mot de passe : `Admin123`

- **Psychologue**
  - Email : `psy@emonaso.com`
  - Mot de passe : `Psy123`

## 📚 Documentation

Pour un guide de test complet, consultez [GUIDE_TEST.md](GUIDE_TEST.md)

## 🎯 Fonctionnalités

### Pour les Patients
- Inscription et connexion
- Consultation de leur dossier médical
- Visualisation de leurs consultations
- Gestion de leur profil

### Pour les Psychologues
- Gestion des dossiers médicaux des patients
- Ajout de consultations avec notes
- Mise à jour des notes psychologiques
- Visualisation de l'historique médical

### Pour les Administrateurs
- Gestion complète des utilisateurs (CRUD)
- Vue d'ensemble des patients et psychologues
- Création de comptes psychologues

## 🛠️ Commandes Utiles

```bash
# Vider le cache
php bin/console cache:clear

# Voir les routes disponibles
php bin/console debug:router

# Voir les migrations
php bin/console doctrine:migrations:status

# Créer une nouvelle migration
php bin/console make:migration
```

## 📝 Structure du Projet

```
src/
├── Command/          # Commandes console
├── Controller/       # Contrôleurs
├── Entity/           # Entités Doctrine
├── Form/             # Formulaires Symfony
├── Repository/      # Repositories Doctrine
└── Security/         # Authentification

templates/
├── admin/            # Templates administration
├── dossier_medical/  # Templates dossiers médicaux
├── patient/          # Templates patients
├── psychologue/      # Templates psychologues
└── security/         # Templates sécurité
```

## 🔒 Sécurité

- Authentification par formulaire avec CSRF
- Hachage des mots de passe avec Symfony PasswordHasher
- Contrôle d'accès basé sur les rôles (ROLE_ADMIN, ROLE_PSYCHOLOGUE, ROLE_PATIENT)
- Protection des routes selon les rôles

## 📄 Licence

Proprietary
