# Comment corriger les erreurs du projet Emonado

## Option 1 : Tout en une fois (recommandé)

Dans un terminal PowerShell, à la racine du projet :

```powershell
.\verifier-et-corriger.ps1
```

Ce script exécute dans l’ordre :
1. `cache:clear`
2. `lint:container`
3. `lint:yaml config/`
4. `lint:twig templates/`
5. `doctrine:schema:update --force`

---

## Option 2 : Commandes une par une

À exécuter dans l’ordre à la racine du projet (`c:\Users\LENOVO\gesjournal`) :

```powershell
# 1. Vider le cache
php bin/console cache:clear

# 2. Vérifier le conteneur Symfony
php bin/console lint:container

# 3. Vérifier les fichiers YAML de config
php bin/console lint:yaml config/

# 4. Vérifier les templates Twig
php bin/console lint:twig templates/

# 5. Synchroniser la base de données avec les entités (corrige l’erreur "schema not in sync")
php bin/console doctrine:schema:update --force
```

---

## Si une erreur persiste

- **Cache** : supprimez le dossier `var/cache/` puis relancez `php bin/console cache:clear`.
- **Schéma BDD** : si `doctrine:schema:validate` affiche encore une erreur après `schema:update --force`, l’application peut quand même fonctionner (écart mineur de métadonnées). Pour enregistrer les changements de schéma dans les migrations :  
  `php bin/console make:migration`  
  puis  
  `php bin/console doctrine:migrations:migrate --no-interaction`
- **Base de données** : vérifiez dans `.env` que `DATABASE_URL` pointe vers une base MySQL existante (ex. `journal_db`). Créez la base si besoin :  
  `CREATE DATABASE journal_db;`

---

## Résumé des vérifications

| Commande | Rôle |
|----------|------|
| `cache:clear` | Vide le cache Symfony |
| `lint:container` | Vérifie les services et injections |
| `lint:yaml config/` | Vérifie la syntaxe des YAML |
| `lint:twig templates/` | Vérifie la syntaxe des templates |
| `doctrine:schema:update --force` | Met la BDD en accord avec les entités |
