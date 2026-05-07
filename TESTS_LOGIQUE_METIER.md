# Tests de Logique Métier - Système de Tests Psychologiques

## Vue d'ensemble

Ce document décrit l'ensemble des tests de logique métier avancée implémentés pour la partie question-réponse du système de tests psychologiques adaptatifs.

## Architecture des Tests

### 1. Tests Unitaires des Services

#### 1.1 ScoreCalculatorService (22 tests)

**Fichier**: `tests/Service/ScoreCalculatorServiceTest.php`

**Fonctionnalités testées**:
- **Calcul de score total**: Validation du calcul du score basé sur des réponses multiples
- **Interprétation des scores**: Tests pour différents niveaux de scores (moral, stress)
  - Excellent (0-4)
  - Bon (5-8)
  - Moyen (9-12)
  - Préoccupant (13-16)
  - Critique (17-100)
- **Calcul par catégorie**: Agrégation des scores par catégorie psychologique
- **Cas limites**: Gestion des valeurs négatives, nulles, données vides
- **Robustesse**: Tests avec données manquantes ou invalides

**Tests clés**:
```php
testCalculateTotalScore()
testInterpretScoreMoralExcellent()
testCalculateScoreByCategory()
testCalculateScoreByCategoryEmptyData()
testCalculateScoreByCategoryZeroValues()
```

#### 1.2 GroqAiService (15 tests)

**Fichier**: `tests/Service/GroqAiServiceTest.php`

**Fonctionnalités testées**:
- **Génération de questions par IA**: Tests pour différentes catégories
  - Stress
  - Dépression
  - QI (questions logiques)
- **Types de questions adaptatives**:
  - Questions initiales
  - Questions d'approfondissement
  - Questions de confirmation
- **Gestion d'erreurs API**: Fallback sur questions par défaut
- **Anti-répétition**: Validation de la diversité des questions
- **Parsing de réponses JSON**: Tests de robustesse du parsing
- **Construction de prompts**: System et user prompts personnalisés

**Tests clés**:
```php
testGenererQuestionPsychologiqueStress()
testGenererQuestionPsychologiqueIQ()
testGenererAnalyseApiError()
testExtraireThemesAbordes()
testParseResponseValidJson()
```

#### 1.3 QuestionnaireAdaptatifService (25 tests)

**Fichier**: `tests/Service/QuestionnaireAdaptatifServiceTest.php`

**Fonctionnalités testées**:
- **Logique adaptative**: Génération de questions basée sur les réponses précédentes
- **Types de questions adaptées**: 
  - Approfondissement (score élevé)
  - Confirmation (score bas)
  - Exploration (score moyen)
- **Analyse finale**: Génération d'analyses psychologiques complètes
- **Profil patient**: Extraction automatique des caractéristiques psychologiques
- **Gestion de l'historique**: Tracking des questions/réponses
- **Conditions de terminaison**: Tests des critères d'arrêt du test

**Tests clés**:
```php
testGenererProchaineQuestionInitiale()
testGenererProchaineQuestionAdaptative()
testGenererProchaineQuestionTestTermine()
testGenererAnalyseFinale()
testExtraireProfilPatient()
```

#### 1.4 AnalyseEmotionnelleService (12 tests)

**Fichier**: `tests/Service/AnalyseEmotionnelleServiceTest.php`

**Fonctionnalités testées**:
- **Analyse de tendances**: Détection d'amélioration ou d'aggravation
- **Détection de moments critiques**: Identification des pics de détresse
- **Statistiques émotionnelles**:
  - Score moyen
  - Écart-type
  - Coefficient de variation
- **Recommandations personnalisées**: Basées sur le niveau de détresse
- **Chronologie émotionnelle**: Construction de l'évolution temporelle
- **Régularité des réponses**: Analyse de la cohérence

**Tests clés**:
```php
testAnalyserTendances()
testIdentifierMomentsCritiques()
testCalculerStatistiques()
testGenererRecommendations()
testConstruireChronologie()
```

### 2. Tests d'Intégration (5 tests)

**Fichier**: `tests/Service/IntegrationTest.php`

**Scénarios testés**:
1. **Test adaptatif complet**: Workflow de bout en bout
2. **Génération de questions avec IA**: Intégration GroqAI + Questionnaire
3. **Calcul de scores par catégories**: ScoreCalculator + Questions
4. **Analyse émotionnelle complète**: Analyse + Statistiques + Recommandations
5. **Workflow avec erreurs API**: Robustesse avec fallbacks

## Métriques de Couverture

### Couverture par Service

| Service | Tests | Assertions | Couverture Estimée |
|---------|-------|------------|-------------------|
| ScoreCalculatorService | 22 | 53+ | ~95% |
| GroqAiService | 15 | 45+ | ~85% |
| QuestionnaireAdaptatifService | 25 | 75+ | ~90% |
| AnalyseEmotionnelleService | 12 | 35+ | ~80% |
| **Total** | **74** | **208+** | **~88%** |

### Catégories de Tests

#### Tests de Validité Fonctionnelle (40%)
- Tests des chemins principaux (happy path)
- Validation des algorithmes de calcul
- Vérification des résultats attendus

#### Tests de Robustesse (30%)
- Gestion des données invalides
- Comportement avec données vides
- Gestion des erreurs API
- Fallbacks et questions par défaut

#### Tests de Cas Limites (20%)
- Valeurs extrêmes
- Collections vides
- Données nulles
- Variations importantes

#### Tests d'Intégration (10%)
- Workflows complets
- Intégrations entre services
- Scénarios réalistes

## Patterns de Test Utilisés

### 1. Mock Objects
```php
$mockResponse = $this->createMock(ResponseInterface::class);
$mockResponse->method('toArray')->willReturn([...]);
```

### 2. Reflection pour méthodes privées
```php
$reflection = new \ReflectionClass(GroqAiService::class);
$method = $reflection->getMethod('extraireThemesAbordes');
$method->setAccessible(true);
```

### 3. Assertions multiples
```php
$this->assertIsArray($result);
$this->assertArrayHasKey('question', $result);
$this->assertStringContainsString('stress', $result['question']);
```

### 4. Injection de dépendances
```php
$this->groqAiService = new GroqAiService(
    $this->httpClient,
    $this->logger
);
```

## Exécution des Tests

### Tous les tests de service
```bash
./vendor/bin/phpunit tests/Service/
```

###  Par service spécifique
```bash
./vendor/bin/phpunit tests/Service/ScoreCalculatorServiceTest.php
./vendor/bin/phpunit tests/Service/GroqAiServiceTest.php
./vendor/bin/phpunit tests/Service/QuestionnaireAdaptatifServiceTest.php
./vendor/bin/phpunit tests/Service/AnalyseEmotionnelleServiceTest.php
```

### Tests d'intégration uniquement
```bash
./vendor/bin/phpunit tests/Service/IntegrationTest.php
```

### Avec couverture de code
```bash
./vendor/bin/phpunit --coverage-html var/coverage tests/Service/
```

## Assertions Clés Utilisées

### Assertions de Structure
- `assertIsArray()` - Vérification du type tableau
- `assertArrayHasKey()` - Présence de clé dans tableau
- `assertCount()` - Nombre d'éléments
- `assertInstanceOf()` - Type d'objet

### Assertions de Valeur
- `assertEquals()` - Égalité stricte
- `assertStringContainsString()` - Sous-chaîne présente
- `assertGreaterThan()` - Comparaison numérique
- `assertTrue()`/`assertFalse()` - Valeurs booléennes

### Assertions d'Exceptions
- `expectException()` - Exception attendue
- `expectExceptionMessage()` - Message d'exception

## Couverture des Cas d'Usage Métier

### Scénario 1: Test Psychologique Standard
✅ Génération de questions initiales  
✅ Adaptation des questions selon réponses  
✅ Calcul des scores par catégorie  
✅ Interprétation des résultats  
✅ Recommandations personnalisées  

### Scénario 2: Détection de Détresse
✅ Identification des scores élevés  
✅ Questions d'approfondissement  
✅ Moments critiques détectés  
✅ Recommandations urgentes générées  

### Scénario 3: Analyse Émotionnelle
✅ Tendances émotionnelles calculées  
✅ Statistiques générées  
✅ Chronologie construite  
✅ Recommandations adaptées  

### Scénario 4: Gestion d'Erreurs
✅ Fallback sur questions par défaut  
✅ Messages d'erreur logués  
✅ Continuation du test malgré erreurs API  
✅ Analyse par défaut générée  

## Améliorations Futures

### Tests à Ajouter
1. **Tests de Performance**
   - Temps de génération de questions
   - Temps de calcul des scores
   - Benchmarks avec grands volumes

2. **Tests de Sécurité**
   - Injection SQL (déjà couvert par Doctrine)
   - XSS dans les questions/réponses
   - Validation des entrées utilisateur

3. **Tests de Charge**
   - Multiples tests simultanés
   - Gestion de la concurrence
   - Limites de l'API Groq

4. **Tests End-to-End**
   - Tests avec navigateur (Panther)
   - Workflows utilisateur complets
   - Tests de UI/UX

### Optimisations
1. **Factorisation des Fixtures**
   - Création de builders pour entités
   - Data providers pour cas de test
   - Fixtures partagées

2. **Tests Paramétrés**
   - DataProviders PHPUnit
   - Réduction de la duplication
   - Couverture exhaustive

## Maintenance

### Mise à Jour des Tests
- Vérifier après chaque modification de service
- Exécuter avant chaque commit
- Intégrer dans CI/CD pipeline

### Documentation
- Commenter les tests complexes
- Documenter les cas limites
- Expliquer les assertions non-évidentes

## Conclusion

Cette suite de tests de logique métier assure:
- ✅ **Fiabilité**: Les algorithmes produisent les résultats attendus
- ✅ **Robustesse**: Le système gère les erreurs et cas limites
- ✅ **Maintenabilité**: Les tests documentent le comportement
- ✅ **Confiance**: Modifications sûres avec tests de non-régression

**Couverture globale**: ~88% du code métier critique
**Total tests**: 74 tests unitaires + 5 tests d'intégration
**Assertions**: 208+ validations automatisées
