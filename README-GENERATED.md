# PIDEV – Documentation du projet

Ce document décrit l’architecture, les fonctionnalités, les composants clés et les instructions d’exécution du projet, sans modifier le code existant. Il se base sur l’arborescence actuellement présente dans le dépôt.

---

## 1. Vue d’ensemble

Le projet est une application Symfony (PHP) avec:
- Un front public (templates, assets, contrôleurs publics)
- Un module d’administration pour la gestion des questions/réponses
- Un module de questionnaire orienté scoring
- Une authentification/autorisation (sécurité Symfony)
- Des migrations Doctrine et des fixtures pour les données initiales

Le code inclut aussi un sous-dossier « emonado/ » qui semble correspondre à une autre application Symfony (ou une variation/monorepo) avec ses propres configs, assets et templates. Le cœur ciblé par les fichiers ouverts concerne surtout le module « questionnaire » et l’administration des questions/réponses dans le dossier racine src/.

---

## 2. Prérequis techniques

- PHP compatible avec la version Symfony utilisée (voir composer.json)
- Extensions PHP nécessaires à Symfony et Doctrine
- Composer
- Base de données (ex. MySQL/MariaDB ou autre selon .env)
- Node/Asset Mapper si vous souhaitez recompiler des assets (optionnel selon usage)

---

## 3. Installation et exécution

1) Installer les dépendances PHP:
- composer install

2) Configurer l’environnement:
- Copier .env en .env.local et ajuster les variables (DATABASE_URL, MAILER_DSN, APP_ENV, etc.)

3) Créer/mettre à jour le schéma de BDD:
- php bin/console doctrine:database:create (si nécessaire)
- php bin/console doctrine:migrations:migrate

4) (Optionnel) Charger des jeux de données:
- php bin/console doctrine:fixtures:load

5) Lancer le serveur de développement:
- symfony serve -d (ou php -S 127.0.0.1:8080 -t public)

L’application sera disponible via public/index.php (par défaut http://127.0.0.1:8080/).

---

## 4. Structure principale (racine)

- public/
  - index.php: Front controller Symfony (point d’entrée HTTP)
  - assets/, css/, js/: ressources statiques (images, CSS, JS)
- src/
  - Controller/: Contrôleurs HTTP côté racine (dont Admin/ et QuestionnaireController)
  - Entity/: Entités Doctrine (Question, Reponse, User côté app principale)
  - Form/: FormTypes pour générer et valider les formulaires Symfony
  - Repository/: Repositories Doctrine
  - Service/: Services métiers (ScoreCalculatorService)
  - DataFixtures/: Jeux de données initiaux (fixtures)
  - Kernel.php: Kernel Symfony de l’application
- templates/: Vues Twig (base.html.twig, dossier questionnaire/, dossier admin/ question/ …)
- config/: Configuration Symfony (framework, doctrine, twig, routing, services…)
- migrations/: Migrations Doctrine générées
- .env, .env.dev, .env.test: Configuration d’environnement
- composer.json: Dépendances et autoload

Le dossier emonado/ contient une deuxième arborescence Symfony (configs, templates, assets, etc.). S’il ne s’agit pas d’un module activement utilisé, l’application principale se base surtout sur la racine.

---

## 5. Fonctionnalités clés par domaine

### 5.1 Module Questionnaire

- src/Controller/QuestionnaireController.php
  - Gère les parcours questionnaire (affichage du test, choix, calcul des scores, restitution des résultats)
  - Utilise les entités Question et Reponse, et probablement ScoreCalculatorService pour le calcul.

- src/Service/ScoreCalculatorService.php
  - Service métier responsable de calculer un score (ex. en fonction des réponses sélectionnées). Centralise la logique de scoring pour isoler la complexité du contrôleur.

- src/Entity/Question.php et src/Entity/Reponse.php
  - Modèles de données Doctrine pour représenter les questions et leurs réponses associées (relations OneToMany / ManyToOne attendues).
  - Reponse contient probablement un poids/valeur permettant de contribuer au score global.

- templates/questionnaire/
  - test.html.twig: Vue d’affichage du questionnaire/test
  - choix.html.twig: Écran de sélection/choix (par ex. démarrer un test, sélectionner des options)
  - resultat.html.twig: Page de résultats (affichage du score, recommandations…)

- src/DataFixtures/QuestionnaireFixtures.php
  - Injecte un jeu de questions/réponses de démonstration pour faciliter les tests.

### 5.2 Administration des questions/réponses

- src/Controller/Admin/QuestionController.php
  - CRUD complet sur les entités Question (liste, création, édition, affichage, suppression)
  - Liens vers la création/édition de Reponses associées si form configuré

- src/Form/QuestionType.php et src/Form/ReponseType.php
  - Définissent les champs des formulaires (texte de la question, propositions de réponse, poids, etc.)

- templates/admin/question/
  - index.html.twig: Liste des questions
  - new.html.twig: Création
  - edit.html.twig: Édition
  - show.html.twig: Détail
  - _form.html.twig: Fragment Twig partagé pour le formulaire
  - _delete_form.html.twig: Fragment Twig pour la suppression sécurisée

- src/Repository/QuestionRepository.php et src/Repository/ReponseRepository.php
  - Accès aux données (requêtes custom) pour Question et Reponse.

### 5.3 Sécurité / Authentification

- src/Controller/Admin/SecurityController.php
  - Logique de login sur l’interface admin

- templates/admin/security/login.html.twig
  - Vue de connexion pour l’admin

- config/packages/security.yaml (principalement dans emonado/config également)
  - Configuration des firewalls, providers, access controls (selon l’instance réellement utilisée)

- src/Security/LoginFormAuthenticator.php (présent dans l’autre arborescence src/)
  - Authenticator personnalisé pour le login classique (e-mail/mot de passe)

### 5.4 Front et pages diverses

- templates/base.html.twig
  - Layout principal (hérité par les vues)

- public/css/style.css, public/css/custom.css et public/js/scripts.js
  - Styles et JavaScript global

- src/Controller/HomeController.php, PatientController.php, PsychologueController.php, etc. (dans l’autre arborescence src/)
  - Gèrent des pages front additionnelles (profils, psychologues, tests de démo…)

### 5.5 Commandes et utilitaires

- src/Command/CreateDefaultUsersCommand.php
  - Commande console pour créer des utilisateurs par défaut (ex. admin). Utile à l’initialisation.

### 5.6 Migrations & Fixtures

- migrations/Version*.php
  - Historique des modifications de schéma Doctrine

- src/DataFixtures/AppFixtures.php, QuestionnaireFixtures.php
  - Chargent des entités de démo (questions/réponses, utilisateurs… selon ce qui est défini)

---

## 6. Flux typique du questionnaire

1) Un utilisateur accède à la page du questionnaire (route gérée par QuestionnaireController).
2) Le contrôleur récupère les questions/réponses (via les repositories) et rend la vue Twig (test.html.twig/choix.html.twig).
3) L’utilisateur soumet ses choix. Les réponses sélectionnées sont validées (FormType/contrôleur).
4) ScoreCalculatorService calcule un score global à partir des poids/valeurs des réponses.
5) Le contrôleur affiche resultat.html.twig avec le score et, éventuellement, des recommandations.

---

## 7. Entités et relations attendues

- Question
  - id, intitule/label, catégorie éventuelle, etc.
  - Relation OneToMany vers Reponse

- Reponse
  - id, label, poids/score_value, booléen « correcte » (selon le type de questionnaire), …
  - Relation ManyToOne vers Question

- User (dans l’autre arborescence):
  - id, email, password, roles, etc.

Vérifier les annotations/attributs Doctrine exacts dans src/Entity/*.php.

---

## 8. Routing et vues

- config/routes.yaml et config/routes/*.yaml
  - Définissent les routes applicatives (contrôleurs admin, questionnaire, sécurité, etc.)

- templates/*.html.twig
  - Les vues Twig utilisent le layout base.html.twig.
  - Les fragments (_form, _delete_form) sont inclus dans les vues CRUD.

---

## 9. Styles, JS et assets

- public/css/custom.css, public/css/styles.css: Feuilles de style pour le front et l’admin.
- public/js/scripts.js: Comportements JS (navigation, interactions formulaires, etc.).
- public/assets/img: Images (miniatures, fonds, icônes…)

Si Asset Mapper est utilisé (cf. importmap.php et config/packages/asset_mapper.yaml), les assets peuvent être gérés sans Webpack Encore. Sinon, utilisez les fichiers déjà présents sous public/.

---

## 10. Sécurité et authentification (détails)

- Login via une page Twig dédiée (templates/admin/security/login.html.twig)
- Contrôleur de sécurité admin pour gérer la connexion/déconnexion
- Authenticator (dans l’autre arborescence) pouvant servir d’exemple
- Contrôles d’accès configurés dans security.yaml (selon l’instance activée au runtime)

Vérifiez quelle arborescence « active » le Kernel charge (Kernel.php au niveau racine) et quels fichiers de config sont réellement utilisés au runtime.

---

## 11. Tests

- tests/RegistrationControllerTest.php (dans l’autre arborescence)
  - Exemple de test fonctionnel/HTTP pour la page d’inscription.
- phpunit.dist.xml: Configuration de PHPUnit.

Exécution des tests:
- php bin/phpunit

---

## 12. Bonnes pratiques et extensions possibles

- Centraliser les règles de scoring dans ScoreCalculatorService pour faciliter l’évolution (pondérations, barèmes, profils).
- Ajouter une entité « Questionnaire » si plusieurs questionnaires coexistent, et lier Question/Reponse à un questionnaire.
- Logger les prises de questionnaires (User, date, détail des réponses) pour analytics.
- Internationaliser les libellés via le composant Translation (fichiers dans translations/).
- Ajouter des validations (validator.yaml, contraintes sur entités et forms) pour renforcer la qualité des données.

---

## 13. Dépannage

- Cache: php bin/console cache:clear
- Base de données: vérifier DATABASE_URL dans .env/.env.local, puis doctrine:database:create, doctrine:migrations:migrate
- Problèmes d’assets: confirmer les chemins utilisés dans les vues Twig et l’existence des fichiers sous public/
- Erreurs 404/500: vérifier routes.yaml, noms des contrôleurs/actions, et logs var/log/

---

## 14. Licence et auteurs

- Voir éventuellement le fichier README.md existant à la racine pour les informations officielles.
- Ce fichier est une documentation générée pour faciliter la prise en main sans modifier le code.
