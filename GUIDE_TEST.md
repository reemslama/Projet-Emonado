# Guide de Test Complet - Projet Emonado

## ✅ État Initial
- ✅ Base de données créée et synchronisée
- ✅ Tables créées : `user`, `dossier_medical`, `consultation`

---

## 📋 Plan de Test par Fonctionnalité

### 1. 🔐 Authentification et Inscription

#### 1.1 Page d'accueil
- **URL**: `http://localhost:8000/`
- **Test**: Vérifier que la page s'affiche correctement
- **Actions**: 
  - Vérifier les liens de navigation (Connexion, Inscription)
  - Vérifier le design et la mise en page

#### 1.2 Inscription (Register)
- **URL**: `http://localhost:8000/register`
- **Test**: Créer un nouveau compte patient
- **Actions**:
  - Remplir le formulaire avec :
    - Email: `test.patient@test.com`
    - Mot de passe: `password123`
    - Nom: `Dupont`
    - Prénom: `Jean`
    - Téléphone: `0612345678`
    - Sexe: `Homme`
    - Date de naissance: `1990-01-15`
  - Soumettre le formulaire
  - Vérifier la redirection vers la page de connexion
  - Vérifier le message de succès

#### 1.3 Connexion (Login)
- **URL**: `http://localhost:8000/login`
- **Test**: Se connecter avec un compte existant
- **Actions**:
  - Entrer l'email et le mot de passe
  - Vérifier la redirection vers le tableau de bord approprié selon le rôle

#### 1.4 Déconnexion (Logout)
- **URL**: `http://localhost:8000/logout`
- **Test**: Se déconnecter
- **Actions**:
  - Cliquer sur "Déconnexion"
  - Vérifier la redirection vers la page d'accueil
  - Vérifier que l'utilisateur n'est plus connecté

---

### 2. 👤 Fonctionnalités Patient

#### 2.1 Tableau de bord Patient
- **URL**: `http://localhost:8000/patient`
- **Prérequis**: Être connecté en tant que patient
- **Test**: Vérifier l'affichage du tableau de bord
- **Actions**:
  - Vérifier le message de bienvenue avec le nom et prénom
  - Vérifier les icônes disponibles :
    - Mon dossier
    - Profil
    - Chat (non fonctionnel)
    - Messagerie (non fonctionnel)
  - Cliquer sur chaque icône pour vérifier la navigation

#### 2.2 Profil Patient
- **URL**: `http://localhost:8000/patient/profil`
- **Prérequis**: Être connecté en tant que patient
- **Test**: Modifier le profil
- **Actions**:
  - Vérifier l'affichage des informations actuelles
  - Modifier le téléphone
  - Modifier la date de naissance
  - Optionnellement changer le mot de passe
  - Soumettre le formulaire
  - Vérifier que les modifications sont sauvegardées

#### 2.3 Dossier Médical Patient
- **URL**: `http://localhost:8000/patient/dossier`
- **Prérequis**: Être connecté en tant que patient
- **Test**: Consulter et modifier le dossier médical
- **Actions**:
  - Vérifier l'affichage des statistiques :
    - Nombre de consultations
    - Nombre de psychologues
    - Dernière consultation
    - Date de création du dossier
  - Vérifier l'affichage des informations personnelles
  - Modifier l'historique médical :
    - Cliquer sur "Modifier"
    - Ajouter du texte dans le champ historique médical
    - Sauvegarder
    - Vérifier que les modifications sont enregistrées
  - Vérifier l'affichage des consultations (si disponibles)
  - Vérifier l'affichage des notes psychologiques (si disponibles)

---

### 3. 🧠 Fonctionnalités Psychologue

#### 3.1 Tableau de bord Psychologue
- **URL**: `http://localhost:8000/psychologue`
- **Prérequis**: Être connecté en tant que psychologue
- **Test**: Vérifier l'affichage du tableau de bord
- **Actions**:
  - Vérifier le message de bienvenue
  - Vérifier les liens de navigation disponibles

#### 3.2 Profil Psychologue
- **URL**: `http://localhost:8000/psychologue/profil`
- **Prérequis**: Être connecté en tant que psychologue
- **Test**: Modifier le profil psychologue
- **Actions**:
  - Modifier la spécialité
  - Modifier les informations personnelles
  - Sauvegarder et vérifier les modifications

#### 3.3 Liste des Dossiers Médicaux (Psychologue)
- **URL**: `http://localhost:8000/psychologue/dossiers`
- **Prérequis**: Être connecté en tant que psychologue
- **Test**: Consulter la liste des dossiers médicaux
- **Actions**:
  - Vérifier l'affichage de tous les dossiers médicaux
  - Vérifier les informations affichées pour chaque dossier

#### 3.4 Vue d'un Dossier Médical (Psychologue)
- **URL**: `http://localhost:8000/psychologue/dossier/{id}`
- **Prérequis**: Être connecté en tant que psychologue, avoir au moins un dossier
- **Test**: Consulter et modifier un dossier médical
- **Actions**:
  - Vérifier l'affichage des informations du patient
  - Vérifier l'affichage de l'historique médical
  - Ajouter une consultation :
    - Remplir le formulaire de consultation
    - Ajouter des notes
    - Définir une date de consultation
    - Soumettre
    - Vérifier que la consultation apparaît dans la liste
  - Modifier les notes psychologiques :
    - Ajouter ou modifier les notes psychologiques
    - Sauvegarder
    - Vérifier que les modifications sont enregistrées

#### 3.5 Créer un Dossier Médical
- **URL**: `http://localhost:8000/psychologue/dossier/create/{patientId}`
- **Prérequis**: Être connecté en tant que psychologue, avoir un ID de patient valide
- **Test**: Créer un nouveau dossier médical pour un patient
- **Actions**:
  - Accéder à l'URL avec un ID de patient existant
  - Vérifier que le dossier est créé
  - Vérifier la redirection vers la vue du dossier

---

### 4. 👨‍💼 Fonctionnalités Admin

#### 4.1 Tableau de bord Admin
- **URL**: `http://localhost:8000/admin`
- **Prérequis**: Être connecté en tant qu'admin (ROLE_ADMIN)
- **Test**: Consulter le tableau de bord administrateur
- **Actions**:
  - Vérifier l'affichage de la liste des patients
  - Vérifier l'affichage de la liste des psychologues
  - Vérifier les statistiques

#### 4.2 Ajouter un Utilisateur (Admin)
- **URL**: `http://localhost:8000/admin/user/add`
- **Prérequis**: Être connecté en tant qu'admin
- **Test**: Créer un nouvel utilisateur
- **Actions**:
  - Remplir le formulaire pour créer un psychologue :
    - Nom: `Martin`
    - Prénom: `Sophie`
    - Email: `sophie.martin@test.com`
    - Téléphone: `0698765432`
    - Spécialité: `Psychologie clinique`
    - Rôle: `ROLE_PSYCHOLOGUE`
    - Mot de passe: `password123`
  - Soumettre le formulaire
  - Vérifier que l'utilisateur est créé
  - Vérifier la redirection vers le tableau de bord admin

#### 4.3 Modifier un Utilisateur (Admin)
- **URL**: `http://localhost:8000/admin/user/edit/{id}`
- **Prérequis**: Être connecté en tant qu'admin, avoir un ID d'utilisateur valide
- **Test**: Modifier les informations d'un utilisateur
- **Actions**:
  - Modifier les informations de l'utilisateur
  - Sauvegarder
  - Vérifier que les modifications sont enregistrées

#### 4.4 Supprimer un Utilisateur (Admin)
- **URL**: `http://localhost:8000/admin/user/delete/{id}` (POST)
- **Prérequis**: Être connecté en tant qu'admin, avoir un ID d'utilisateur valide
- **Test**: Supprimer un utilisateur
- **Actions**:
  - Envoyer une requête POST pour supprimer un utilisateur
  - Vérifier que l'utilisateur est supprimé
  - Vérifier la redirection vers le tableau de bord admin

---

## 🔍 Tests de Sécurité et Validation

### 5. Tests de Sécurité

#### 5.1 Accès Non Autorisé
- **Test**: Tenter d'accéder à des pages protégées sans être connecté
- **Actions**:
  - Se déconnecter
  - Essayer d'accéder à `/patient`
  - Essayer d'accéder à `/psychologue`
  - Essayer d'accéder à `/admin`
  - Vérifier que toutes ces pages redirigent vers `/login`

#### 5.2 Accès par Rôle
- **Test**: Vérifier que les patients ne peuvent pas accéder aux pages psychologue/admin
- **Actions**:
  - Se connecter en tant que patient
  - Essayer d'accéder à `/psychologue/dossiers`
  - Essayer d'accéder à `/admin`
  - Vérifier que l'accès est refusé (403 ou redirection)

#### 5.3 Protection CSRF
- **Test**: Vérifier que les formulaires sont protégés contre CSRF
- **Actions**:
  - Vérifier la présence de tokens CSRF dans les formulaires
  - Tenter de soumettre un formulaire sans token
  - Vérifier que la soumission est rejetée

---

## 📊 Tests de Données

### 6. Tests de Persistance

#### 6.1 Création de Dossier Médical
- **Test**: Vérifier qu'un dossier médical est créé automatiquement pour un nouveau patient
- **Actions**:
  - Créer un nouveau compte patient
  - Se connecter avec ce compte
  - Accéder à `/patient/dossier`
  - Vérifier qu'un dossier médical a été créé automatiquement

#### 6.2 Relations entre Entités
- **Test**: Vérifier les relations entre User, DossierMedical et Consultation
- **Actions**:
  - Créer une consultation depuis le compte psychologue
  - Vérifier qu'elle apparaît dans le dossier du patient
  - Vérifier que les informations du psychologue sont correctement liées

---

## 🐛 Tests de Cas Limites

### 7. Gestion des Erreurs

#### 7.1 Données Manquantes
- **Test**: Vérifier le comportement avec des données manquantes
- **Actions**:
  - Créer un dossier sans historique médical
  - Vérifier que la page s'affiche correctement
  - Créer une consultation sans notes
  - Vérifier que la consultation s'affiche correctement

#### 7.2 Dates Null
- **Test**: Vérifier le comportement avec des dates null
- **Actions**:
  - Vérifier qu'une consultation sans date s'affiche correctement
  - Vérifier qu'un patient sans date de naissance s'affiche correctement

---

## ✅ Checklist de Validation

- [ ] Toutes les routes sont accessibles
- [ ] L'authentification fonctionne correctement
- [ ] Les rôles sont correctement appliqués
- [ ] Les formulaires fonctionnent et valident les données
- [ ] Les données sont correctement sauvegardées en base
- [ ] Les relations entre entités fonctionnent
- [ ] Les messages flash s'affichent correctement
- [ ] La navigation entre les pages fonctionne
- [ ] Les pages s'affichent correctement sur différents navigateurs
- [ ] Les erreurs sont gérées correctement

---

## 📝 Notes de Test

### Comptes de Test Recommandés

**Patient:**
- Email: `test.patient@test.com`
- Mot de passe: `password123`
- Rôle: `ROLE_PATIENT`

**Psychologue:**
- Email: `test.psy@test.com`
- Mot de passe: `password123`
- Rôle: `ROLE_PSYCHOLOGUE`
- Spécialité: `Psychologie clinique`

**Admin:**
- Email: `admin@test.com`
- Mot de passe: `admin123`
- Rôle: `ROLE_ADMIN`

---

## 🔧 Commandes Utiles pour les Tests

```bash
# Vider le cache
php bin/console cache:clear

# Vérifier les routes
php bin/console debug:router

# Vérifier le schéma de base de données
php bin/console doctrine:schema:validate

# Créer des utilisateurs de test (si commande disponible)
php bin/console app:create-users
```

---

## 📌 Problèmes Connus et Solutions

### Problème: Table 'dossier_medical' doesn't exist
**Solution**: Exécuter `php bin/console doctrine:schema:update --force`

### Problème: Erreur 500 sur /patient/dossier
**Solution**: Vérifier que les tables sont créées et que l'utilisateur est connecté

### Problème: Accès refusé (403)
**Solution**: Vérifier que l'utilisateur a le bon rôle et est bien connecté

---

**Date de création**: 2026-02-09
**Dernière mise à jour**: 2026-02-09
