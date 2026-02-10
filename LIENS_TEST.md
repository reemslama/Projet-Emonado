# 🔗 Liens de Test - Projet Emonado

## 🌐 URL de Base
**Serveur Local**: `http://127.0.0.1:8080` (lancer avec `start-server.bat` ou `php -S 127.0.0.1:8080 -t public`)

---

## 🔐 Authentification

### Page d'accueil
- **URL**: http://127.0.0.1:8080/
- **Description**: Page d'accueil avec liens de navigation

### Connexion
- **URL**: http://127.0.0.1:8080/login
- **Description**: Page de connexion pour patients, psychologues et admins

### Inscription
- **URL**: http://127.0.0.1:8080/register
- **Description**: Créer un nouveau compte patient

### Déconnexion
- **URL**: http://127.0.0.1:8080/logout
- **Description**: Se déconnecter (nécessite d'être connecté)

---

## 👤 Fonctionnalités Patient

### Tableau de bord Patient
- **URL**: http://127.0.0.1:8080/patient
- **Prérequis**: Être connecté en tant que patient (ROLE_PATIENT)
- **Description**: Page d'accueil du patient avec icônes de navigation

### Profil Patient
- **URL**: http://127.0.0.1:8080/patient/profil
- **Prérequis**: Être connecté en tant que patient
- **Description**: Modifier les informations du profil patient

### Dossier Médical Patient
- **URL**: http://127.0.0.1:8080/patient/dossier
- **Prérequis**: Être connecté en tant que patient
- **Description**: Consulter et modifier le dossier médical personnel

---

## 🧠 Fonctionnalités Psychologue

### Tableau de bord Psychologue
- **URL**: http://127.0.0.1:8080/psychologue
- **Prérequis**: Être connecté en tant que psychologue (ROLE_PSYCHOLOGUE)
- **Description**: Page d'accueil du psychologue

### Profil Psychologue
- **URL**: http://127.0.0.1:8080/psychologue/profil
- **Prérequis**: Être connecté en tant que psychologue
- **Description**: Modifier les informations du profil psychologue (incluant la spécialité)

### Liste des Dossiers Médicaux
- **URL**: http://127.0.0.1:8080/psychologue/dossiers
- **Prérequis**: Être connecté en tant que psychologue
- **Description**: Voir tous les dossiers médicaux des patients

### Vue d'un Dossier Médical
- **URL**: http://127.0.0.1:8080/psychologue/dossier/{id}
- **Prérequis**: Être connecté en tant que psychologue, remplacer {id} par l'ID du dossier
- **Exemple**: http://127.0.0.1:8080/psychologue/dossier/1
- **Description**: Consulter et modifier un dossier médical spécifique, ajouter des consultations

### Créer un Dossier Médical
- **URL**: http://127.0.0.1:8080/psychologue/dossier/create/{patientId}
- **Prérequis**: Être connecté en tant que psychologue, remplacer {patientId} par l'ID du patient
- **Exemple**: http://127.0.0.1:8080/psychologue/dossier/create/1
- **Description**: Créer un nouveau dossier médical pour un patient

---

## 👨‍💼 Fonctionnalités Admin

### Tableau de bord Admin
- **URL**: http://127.0.0.1:8080/admin
- **Prérequis**: Être connecté en tant qu'admin (ROLE_ADMIN)
- **Description**: Vue d'ensemble avec liste des patients et psychologues

### Ajouter un Utilisateur
- **URL**: http://127.0.0.1:8080/admin/user/add
- **Prérequis**: Être connecté en tant qu'admin
- **Description**: Créer un nouveau compte (patient ou psychologue)

### Modifier un Utilisateur
- **URL**: http://127.0.0.1:8080/admin/user/edit/{id}
- **Prérequis**: Être connecté en tant qu'admin, remplacer {id} par l'ID de l'utilisateur
- **Exemple**: http://127.0.0.1:8080/admin/user/edit/1
- **Description**: Modifier les informations d'un utilisateur

### Supprimer un Utilisateur
- **URL**: http://127.0.0.1:8080/admin/user/delete/{id}
- **Prérequis**: Être connecté en tant qu'admin, méthode POST uniquement
- **Exemple**: http://127.0.0.1:8080/admin/user/delete/1
- **Description**: Supprimer un utilisateur (nécessite une requête POST)

---

## 📋 Ordre Recommandé de Test

### 1. Test Initial (Sans connexion)
1. ✅ http://127.0.0.1:8080/ - Page d'accueil
2. ✅ http://127.0.0.1:8080/register - Créer un compte patient
3. ✅ http://127.0.0.1:8080/login - Se connecter

### 2. Test Patient
1. ✅ http://127.0.0.1:8080/patient - Tableau de bord
2. ✅ http://127.0.0.1:8080/patient/dossier - Dossier médical (sera créé automatiquement)
3. ✅ http://127.0.0.1:8080/patient/profil - Modifier le profil

### 3. Test Admin (Créer un psychologue)
1. ✅ http://127.0.0.1:8080/admin - Tableau de bord admin
2. ✅ http://127.0.0.1:8080/admin/user/add - Créer un psychologue
3. Se déconnecter et se connecter en tant que psychologue

### 4. Test Psychologue
1. ✅ http://127.0.0.1:8080/psychologue - Tableau de bord
2. ✅ http://127.0.0.1:8080/psychologue/dossiers - Liste des dossiers
3. ✅ http://127.0.0.1:8080/psychologue/dossier/{id} - Voir un dossier (remplacer {id})
4. ✅ Ajouter une consultation depuis la vue du dossier
5. ✅ Modifier les notes psychologiques

### 5. Vérification Patient
1. ✅ Se reconnecter en tant que patient
2. ✅ http://127.0.0.1:8080/patient/dossier - Vérifier que la consultation apparaît

---

## 🔍 Tests de Sécurité

### Pages qui doivent rediriger vers /login si non connecté :
- ❌ http://127.0.0.1:8080/patient
- ❌ http://127.0.0.1:8080/psychologue
- ❌ http://127.0.0.1:8080/admin
- ❌ http://127.0.0.1:8080/patient/dossier
- ❌ http://127.0.0.1:8080/psychologue/dossiers

### Pages qui doivent retourner 403 si mauvais rôle :
- ❌ Patient essayant d'accéder à /psychologue/*
- ❌ Patient essayant d'accéder à /admin/*
- ❌ Psychologue essayant d'accéder à /admin/*

---

## 📝 Notes Importantes

1. **Remplacez {id}** dans les URLs par des IDs réels de votre base de données
2. **Méthode POST** : Certaines routes nécessitent une requête POST (comme la suppression)
3. **Rôles requis** : Assurez-vous d'être connecté avec le bon rôle pour chaque page
4. **Premier accès** : Le dossier médical sera créé automatiquement lors du premier accès à `/patient/dossier`

---

## 🚀 Démarrage Rapide

1. **Démarrer le serveur** :
   ```bash
   symfony server:start
   ```
   ou
   ```bash
   php -S localhost:8000 -t public
   ```

2. **Ouvrir dans le navigateur** :
   ```
   http://127.0.0.1:8080
   ```

3. **Créer un compte** :
   ```
   http://127.0.0.1:8080/register
   ```

4. **Commencer les tests** selon l'ordre recommandé ci-dessus.

---

**Date de création**: 2026-02-09
