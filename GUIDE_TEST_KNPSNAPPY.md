# 🧪 Guide de Test - KnpSnappyBundle (Génération PDF)

## 📋 Plan de Test

Ce guide vous permet de tester complètement la génération de PDF pour les tests psychologiques.

---

## ✅ Étape 1 : Charger les données de test

### 1.1 Charger les fixtures

```bash
php bin/console doctrine:fixtures:load
```

**Répondez `yes` quand demandé.**

Cela créera :
- ✅ 3 utilisateurs (admin, psy, user)
- ✅ 5 tests adaptatifs avec différents scores
- ✅ Questions et réponses complètes

### 1.2 Vérifier les données créées

```bash
php bin/console doctrine:query:sql "SELECT id, categorie, score_actuel, termine FROM test_adaptatif"
```

Vous devriez voir :
```
id | categorie  | score_actuel | termine
1  | stress     | 16           | 1
2  | depression | 12           | 1
3  | iq         | 7            | 1
4  | anxiete    | 2            | 1
5  | stress     | 2            | 0
```

---

## ✅ Étape 2 : Démarrer le serveur

```bash
symfony server:start
```

Ou sur un port spécifique :
```bash
symfony server:start --port=8000
```

Le serveur devrait afficher :
```
[OK] Web server listening on http://127.0.0.1:8000
```

---

## ✅ Étape 3 : Tester la prévisualisation HTML

### 3.1 Test simple - ID 1 (Stress)

Ouvrez votre navigateur : **http://localhost:8000/test/pdf/1/previsualiser**

**Résultat attendu :**
- ✅ Page HTML stylée affichée
- ✅ En-tête "Résultat du Test Psychologique - STRESS"
- ✅ Score global : 16/20
- ✅ Interprétation : Niveau "Critique" ou similaire
- ✅ Liste des 5 questions/réponses
- ✅ Analyse détaillée en bas

**Captures d'écran à vérifier :**
- Design professionnel avec gradients bleu/violet
- Sections bien séparées
- Texte lisible

### 3.2 Test IQ - ID 3

**URL :** http://localhost:8000/test/pdf/3/previsualiser

**Résultat attendu :**
- ✅ 7 questions logiques affichées
- ✅ Score : 7/28
- ✅ Interprétation positive

### 3.3 Test Anxiété - ID 4

**URL :** http://localhost:8000/test/pdf/4/previsualiser

**Résultat attendu :**
- ✅ Score faible (2)
- ✅ Interprétation "Excellent" ou "Faible"
- ✅ Message positif

---

## ✅ Étape 4 : Tester le téléchargement PDF

### 4.1 Télécharger le test Stress

**URL :** http://localhost:8000/test/pdf/1/telecharger

**Résultat attendu :**
- ✅ Téléchargement automatique d'un fichier PDF
- ✅ Nom du fichier : `test_stress_YYYY-MM-DD.pdf`
- ✅ Taille : ~100-300 Ko

### 4.2 Ouvrir le PDF téléchargé

Vérifiez :
- ✅ Le PDF s'ouvre dans un lecteur PDF
- ✅ Toutes les sections sont présentes
- ✅ Les couleurs sont correctes
- ✅ Texte sélectionnable (pas une image)
- ✅ Pas d'erreurs d'encodage (accents corrects : é, è, à, ç)

### 4.3 Télécharger d'autres tests

- **Dépression :** http://localhost:8000/test/pdf/2/telecharger
- **IQ :** http://localhost:8000/test/pdf/3/telecharger
- **Anxiété :** http://localhost:8000/test/pdf/4/telecharger

---

## ✅ Étape 5 : Tester le rapport patient complet

### 5.1 Identifier l'ID du patient

```bash
php bin/console doctrine:query:sql "SELECT DISTINCT patient_id FROM test_adaptatif WHERE patient_id IS NOT NULL LIMIT 1"
```

Supposons que l'ID est **1**.

### 5.2 Prévisualiser le rapport

**URL :** http://localhost:8000/test/pdf/patient/1/rapport

**Résultat attendu - Page multi-sections :**
- ✅ En-tête "Rapport Psychologique Complet"
- ✅ Informations patient (nom, email)
- ✅ Vue d'ensemble : 
  - Nombre de tests : 5
  - Questions traitées : total
  - Catégories évaluées : 3-4
- ✅ Graphique d'évolution des scores
- ✅ Cartes détaillées pour chaque test
- ✅ Recommandations générales

### 5.3 Télécharger le rapport PDF

Remplacez `/rapport` par `/rapport` dans l'URL et rechargez.

**Vérifiez :**
- ✅ PDF multi-pages (2-4 pages)
- ✅ Nom : `rapport_patient_1_YYYY-MM-DD.pdf`
- ✅ Tous les tests apparaissent
- ✅ Pagination correcte

---

## ✅ Étape 6 : Tests de permissions (sécurité)

### 6.1 Se connecter en tant que patient

1. Allez sur http://localhost:8000/login
2. Connectez-vous avec :
   - **Email :** user@example.com
   - **Mot de passe :** userpass

### 6.2 Accéder à son propre test

**URL :** http://localhost:8000/test/pdf/1/previsualiser

**Résultat attendu :**
- ✅ Accès autorisé (si le test appartient au patient)
- ✅ PDF visible

### 6.3 Tester l'accès interdit

Créez un autre utilisateur et testez :
- ❌ Accès refusé aux tests d'autres patients
- ✅ Message d'erreur 403 ou redirection

---

## ✅ Étape 7 : Tests avancés

### 7.1 Test avec données manquantes

Créez un test sans analyse :

```bash
php bin/console doctrine:query:sql "UPDATE test_adaptatif SET analyse = NULL WHERE id = 1"
```

Rechargez : http://localhost:8000/test/pdf/1/previsualiser

**Résultat attendu :**
- ✅ PDF généré sans erreur
- ✅ Section "Analyse" ne s'affiche pas ou affiche un message par défaut

### 7.2 Test avec caractères spéciaux

Ajoutez des caractères accentués :

```bash
php bin/console doctrine:query:sql "UPDATE test_adaptatif SET analyse = 'Évaluation complète : très bonne évolution. Résilience accrue.' WHERE id = 1"
```

**Vérifiez :**
- ✅ Accents affichés correctement dans le PDF
- ✅ Encoding UTF-8 respecté

### 7.3 Test de performance

Téléchargez plusieurs PDFs successivement :

```bash
# PowerShell
1..5 | ForEach-Object { Invoke-WebRequest "http://localhost:8000/test/pdf/$_/telecharger" -OutFile "test_$_.pdf" }
```

**Vérifiez :**
- ✅ Tous les PDFs générés sans timeout
- ✅ Temps de génération < 5 secondes par PDF
- ✅ Aucune erreur dans les logs

---

## ✅ Étape 8 : Vérification des logs

### 8.1 Consulter les logs Symfony

```bash
tail -f var/log/dev.log
```

Pendant que vous générez des PDFs, vérifiez :
- ✅ Pas d'erreurs PHP
- ✅ Pas de warnings wkhtmltopdf
- ✅ Requêtes HTTP réussies (200)

### 8.2 Vérifier les erreurs de compilation

```bash
php bin/console debug:container knp_snappy.pdf
```

**Résultat attendu :**
```
Service ID: knp_snappy.pdf
Class: Knp\Snappy\Pdf
Public: yes
Synthetic: no
```

---

## ✅ Étape 9 : Tests de régression

### 9.1 Checklist complète

| Test | URL | Statut attendu | Résultat |
|------|-----|----------------|----------|
| Prévisualisation Test 1 | `/test/pdf/1/previsualiser` | 200 OK | ⬜ |
| Téléchargement Test 1 | `/test/pdf/1/telecharger` | 200 OK + PDF | ⬜ |
| Prévisualisation Test 2 | `/test/pdf/2/previsualiser` | 200 OK | ⬜ |
| Téléchargement Test 2 | `/test/pdf/2/telecharger` | 200 OK + PDF | ⬜ |
| Prévisualisation Test 3 | `/test/pdf/3/previsualiser` | 200 OK | ⬜ |
| Téléchargement Test 3 | `/test/pdf/3/telecharger` | 200 OK + PDF | ⬜ |
| Rapport Patient 1 | `/test/pdf/patient/1/rapport` | 200 OK + PDF | ⬜ |
| Test inexistant | `/test/pdf/999/telecharger` | 404 Not Found | ⬜ |
| Accès non autorisé | `/test/pdf/X/telecharger` (autre user) | 403 Forbidden | ⬜ |

### 9.2 Marquer les tests réussis

Cochez ✅ dans la colonne "Résultat" pour chaque test passé.

---

## ✅ Étape 10 : Nettoyage et maintenance

### 10.1 Vider le cache

```bash
php bin/console cache:clear
```

### 10.2 Vérifier l'espace disque

Les PDFs ne sont pas stockés (générés à la volée), mais vérifiez les logs :

```bash
du -sh var/log/
```

### 10.3 Supprimer les données de test

Si vous voulez repartir de zéro :

```bash
php bin/console doctrine:schema:drop --force
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load
```

---

## 🐛 Dépannage

### Problème 1 : PDF vide

**Symptômes :** Le PDF se télécharge mais est vide ou corrompu

**Solutions :**
```bash
# Vérifier le binaire
vendor\h4cc\wkhtmltopdf-amd64\bin\wkhtmltopdf-amd64.exe --version

# Tester manuellement
vendor\h4cc\wkhtmltopdf-amd64\bin\wkhtmltopdf-amd64.exe https://google.com test.pdf

# Augmenter le timeout
# Dans TestPdfController.php, ajouter :
set_time_limit(60);
```

### Problème 2 : Erreur 500

**Vérifier les logs :**
```bash
tail -50 var/log/dev.log
```

**Causes courantes :**
- Service ScoreCalculatorService manquant
- Méthode inexistante
- Problème de permissions sur le fichier

**Solution :**
```bash
php bin/console cache:clear
php bin/console debug:autowiring ScoreCalculatorService
```

### Problème 3 : Images manquantes

**Symptômes :** Les images ne s'affichent pas dans le PDF

**Solution :**
```twig
{# Utilisez absolute_url() #}
<img src="{{ absolute_url(asset('images/logo.png')) }}" />
```

### Problème 4 : Caractères bizarres (encoding)

**Vérifiez :**
- Template contient `<meta charset="UTF-8">`
- Options PDF incluent `'encoding' => 'UTF-8'`

### Problème 5 : Timeout

**Augmentez le timeout :**
```php
// Dans TestPdfController.php
return new PdfResponse(
    $this->knpSnappyPdf->getOutputFromHtml($html, [
        'timeout' => 120, // 2 minutes
        // autres options...
    ]),
    $filename
);
```

---

## 📊 Résultats attendus

### Tests réussis ✅
- 5 tests adaptatifs créés
- Prévisualisation HTML fonctionnelle
- Téléchargement PDF opérationnel
- Rapport patient multi-pages
- Permissions sécurisées
- Encoding UTF-8 correct
- Aucune erreur dans les logs

### Critères de validation

| Critère | Objectif | Résultat |
|---------|----------|----------|
| Génération PDF | < 5s par test | ⬜ |
| Qualité visuelle | Design professionnel | ⬜ |
| Sécurité | Permissions respectées | ⬜ |
| Encodage | UTF-8 correct | ⬜ |
| Multi-pages | Rapport patient complet | ⬜ |
| Robustesse | Pas d'erreur 500 | ⬜ |

---

## 🎯 Commandes rapides

```bash
# Tout tester en une fois
php bin/console doctrine:fixtures:load --no-interaction
symfony server:start --port=8000 --daemon
start http://localhost:8000/test/pdf/1/previsualiser
start http://localhost:8000/test/pdf/1/telecharger
start http://localhost:8000/test/pdf/patient/1/rapport

# Vérifier les routes
php bin/console debug:router | Select-String "test_pdf"

# Voir les logs en temps réel
tail -f var/log/dev.log

# Nettoyer
php bin/console cache:clear
```

---

## ✅ Checklist finale

- [ ] Fixtures chargées
- [ ] Serveur démarré
- [ ] 3 PDFs individuels téléchargés
- [ ] 1 rapport patient téléchargé
- [ ] Prévisualisations testées
- [ ] Permissions vérifiées
- [ ] Aucune erreur dans les logs
- [ ] Accents affichés correctement
- [ ] Design professionnel validé

**Si tous les tests passent : KnpSnappyBundle est opérationnel ! 🎉**

---

## 📚 Ressources

- [KNPSNAPPY_PDF.md](KNPSNAPPY_PDF.md) - Documentation complète
- [INSTALLATION_COMPLETE_KNPSNAPPY.md](INSTALLATION_COMPLETE_KNPSNAPPY.md) - Guide d'installation
- [Documentation officielle](https://github.com/KnpLabs/KnpSnappyBundle)

---

**Test créé le :** {{ "now"|date("d/m/Y H:i") }}  
**Statut :** Prêt pour exécution 🚀
