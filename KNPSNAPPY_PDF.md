# 📄 KnpSnappyBundle - Génération de PDF pour les Tests Psychologiques

## 📋 Vue d'ensemble

KnpSnappyBundle a été intégré au projet pour permettre la génération automatique de rapports PDF professionnels des résultats de tests psychologiques.

### ✨ Fonctionnalités

- **Export PDF de test individuel** : Télécharger le résultat complet d'un test avec analyse
- **Rapport patient complet** : Générer un rapport PDF consolidant tous les tests d'un patient
- **Prévisualisation HTML** : Visualiser le rapport avant de le télécharger en PDF
- **Design professionnel** : Mise en page optimisée pour l'impression et la lecture

---

## 🔧 Installation

### 1. Packages installés

```bash
composer require knplabs/knp-snappy-bundle
composer require h4cc/wkhtmltopdf-amd64 --dev
```

### 2. Configuration

**Fichier `.env`** (déjà configuré) :
```env
WKHTMLTOPDF_PATH=vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64.exe
WKHTMLTOIMAGE_PATH=vendor/h4cc/wkhtmltoimage-amd64/bin/wkhtmltoimage-amd64.exe
```

**Fichier `config/packages/knp_snappy.yaml`** (automatique) :
```yaml
knp_snappy:
    pdf:
        enabled: true
        binary: '%env(WKHTMLTOPDF_PATH)%'
        options: []
    image:
        enabled: true
        binary: '%env(WKHTMLTOIMAGE_PATH)%'
        options: []
```

---

## 📂 Structure des fichiers créés

```
src/
├── Controller/
│   └── TestPdfController.php          # Contrôleur pour la génération PDF
└── Security/
    └── Voter/
        └── TestAdaptatifVoter.php     # Gestion des permissions

templates/
└── test/
    ├── pdf_resultat.html.twig          # Template PDF pour un test
    └── pdf_rapport_patient.html.twig   # Template PDF rapport complet
```

---

## 🚀 Utilisation

### Routes disponibles

#### 1. Télécharger un test en PDF

```
GET /test/pdf/{id}/telecharger
Route: test_pdf_download
```

**Exemple** :
```twig
<a href="{{ path('test_pdf_download', {id: test.id}) }}" class="btn btn-primary">
    📥 Télécharger le PDF
</a>
```

**Fonctionnalités** :
- Génère un PDF complet du test
- Inclut score, interprétation, analyse et détail des questions
- Nom du fichier : `test_{categorie}_{date}.pdf`

---

#### 2. Prévisualiser le rapport (HTML)

```
GET /test/pdf/{id}/previsualiser
Route: test_pdf_preview
```

**Exemple** :
```twig
<a href="{{ path('test_pdf_preview', {id: test.id}) }}" class="btn btn-secondary" target="_blank">
    👁️ Prévisualiser
</a>
```

**Fonctionnalités** :
- Affiche le rendu HTML du PDF dans le navigateur
- Utile pour vérifier avant téléchargement

---

#### 3. Rapport complet patient

```
GET /test/pdf/patient/{patientId}/rapport
Route: test_pdf_rapport_patient
```

**Exemple** :
```twig
<a href="{{ path('test_pdf_rapport_patient', {patientId: patient.id}) }}" class="btn btn-success">
    📊 Rapport Complet PDF
</a>
```

**Fonctionnalités** :
- Génère un rapport consolidé de tous les tests du patient
- Vue d'ensemble avec statistiques
- Évolution des scores
- Détail de chaque test
- Nom du fichier : `rapport_patient_{id}_{date}.pdf`

---

## 🎨 Contenu des PDF

### PDF Test Individuel (`pdf_resultat.html.twig`)

**Sections incluses** :
1. **En-tête** : Titre du test, catégorie
2. **Informations** : Patient, dates, durée, nombre de questions
3. **Score global** : Score actuel / score maximum
4. **Interprétation** : Niveau (Excellent, Bon, Moyen, Faible, Critique) avec message
5. **Détail par catégorie** : Scores par sous-catégorie avec barres de progression
6. **Analyse détaillée** : Texte d'analyse généré par l'IA
7. **Questions/Réponses** : Liste complète des Q&R avec points
8. **Pied de page** : Mention de confidentialité, date de génération

**Design** :
- Couleurs professionnelles (bleu/violet gradient)
- Mise en forme optimisée pour A4
- Marges configurées (10mm)
- Support UTF-8 pour les accents

---

### PDF Rapport Patient (`pdf_rapport_patient.html.twig`)

**Sections incluses** :
1. **En-tête principal** : Titre "Rapport Psychologique Complet"
2. **Informations patient** : Nom, email, nombre de tests, période
3. **Vue d'ensemble statistique** :
   - Nombre total de tests
   - Questions traitées
   - Catégories évaluées
   - Graphique d'évolution des scores
4. **Détail de chaque test** :
   - Carte par test avec score, interprétation
   - Scores par catégorie
   - Extrait de l'analyse
5. **Recommandations générales**
6. **Pied de page** : Confidentialité, date

**Design** :
- Multi-pages avec gestion des sauts de page
- Timeline visuelle
- Graphiques en barres pour l'évolution
- Badges colorés selon les niveaux

---

## 🔒 Permissions (TestAdaptatifVoter)

### Actions supportées
- `VIEW` : Voir un test
- `EDIT` : Modifier un test
- `DELETE` : Supprimer un test

### Règles d'accès

| Rôle | VIEW | EDIT | DELETE |
|------|------|------|--------|
| **ADMIN** | ✅ Tous | ✅ Tous | ✅ Tous |
| **PSYCHOLOGUE** | ✅ Tous ses patients | ✅ Tous ses patients | ❌ Non |
| **PATIENT** | ✅ Ses tests uniquement | ✅ Tests non terminés | ❌ Non |

**Utilisation dans le contrôleur** :
```php
$this->denyAccessUnlessGranted('view', $test);
```

---

## 🛠️ Personnalisation

### Modifier les options PDF

Dans `TestPdfController.php`, vous pouvez ajuster les options :

```php
return new PdfResponse(
    $this->knpSnappyPdf->getOutputFromHtml($html, [
        'encoding' => 'UTF-8',
        'enable-local-file-access' => true,
        'margin-top' => 15,           // Marges personnalisables
        'margin-right' => 15,
        'margin-bottom' => 15,
        'margin-left' => 15,
        'orientation' => 'Portrait',   // ou 'Landscape'
        'page-size' => 'A4',           // A4, Letter, etc.
        'dpi' => 300,                  // Qualité
        'image-quality' => 100,
        'lowquality' => false,
    ]),
    $filename
);
```

### Options disponibles

- **Marges** : `margin-top`, `margin-right`, `margin-bottom`, `margin-left` (en mm)
- **Orientation** : `Portrait` ou `Landscape`
- **Format** : `A4`, `A3`, `Letter`, etc.
- **DPI** : Résolution (300 pour impression pro)
- **Images** : `image-quality` (0-100), `image-dpi`
- **En-têtes/Pieds** : `header-html`, `footer-html`, `footer-center`, etc.

---

## 📊 Ajouter des boutons de téléchargement

### Dans une page de détail de test

```twig
{# templates/test/show.html.twig #}

<div class="btn-group">
    <a href="{{ path('test_pdf_preview', {id: test.id}) }}" 
       class="btn btn-secondary" 
       target="_blank">
        <i class="fas fa-eye"></i> Prévisualiser
    </a>
    
    <a href="{{ path('test_pdf_download', {id: test.id}) }}" 
       class="btn btn-primary">
        <i class="fas fa-file-pdf"></i> Télécharger PDF
    </a>
</div>
```

### Dans une liste de tests

```twig
{# templates/test/list.html.twig #}

{% for test in tests %}
<tr>
    <td>{{ test.categorie }}</td>
    <td>{{ test.dateDebut|date('d/m/Y') }}</td>
    <td>{{ test.scoreActuel }}</td>
    <td>
        <div class="btn-group btn-group-sm">
            <a href="{{ path('test_pdf_preview', {id: test.id}) }}" 
               class="btn btn-sm btn-outline-secondary" 
               target="_blank">
                👁️
            </a>
            <a href="{{ path('test_pdf_download', {id: test.id}) }}" 
               class="btn btn-sm btn-primary">
                📥
            </a>
        </div>
    </td>
</tr>
{% endfor %}
```

### Dans le profil patient

```twig
{# templates/patient/profile.html.twig #}

<a href="{{ path('test_pdf_rapport_patient', {patientId: app.user.id}) }}" 
   class="btn btn-lg btn-success">
    <i class="fas fa-file-download"></i> 
    Télécharger mon Rapport Complet (PDF)
</a>
```

---

## 🧪 Tests

### Tester la génération PDF

1. **Créer un test** :
   ```bash
   php bin/console doctrine:fixtures:load
   ```

2. **Accéder à la route** :
   ```
   http://localhost:8000/test/pdf/1/previsualiser
   ```

3. **Télécharger le PDF** :
   ```
   http://localhost:8000/test/pdf/1/telecharger
   ```

### Debugger les erreurs

Si le PDF ne se génère pas :

1. **Vérifier le binaire** :
   ```bash
   php bin/console debug:container knp_snappy.pdf
   ```

2. **Tester wkhtmltopdf** :
   ```bash
   vendor\h4cc\wkhtmltopdf-amd64\bin\wkhtmltopdf-amd64.exe --version
   ```

3. **Vérifier les logs** :
   ```bash
   tail -f var/log/dev.log
   ```

4. **Activer le debug** dans le contrôleur :
   ```php
   dd($this->knpSnappyPdf->getOutput('https://google.com'));
   ```

---

## 🎯 Cas d'usage avancés

### Ajouter un logo

Modifiez `pdf_resultat.html.twig` :

```twig
<div class="header">
    <img src="{{ absolute_url(asset('images/logo.png')) }}" 
         alt="Logo" 
         style="max-width: 150px; margin-bottom: 10px;">
    <h1>📊 Résultat du Test Psychologique</h1>
</div>
```

**Note** : Utilisez `absolute_url()` pour que wkhtmltopdf trouve l'image.

---

### Ajouter des graphiques

Utilisez Chart.js ou créez des barres CSS :

```twig
<div class="chart">
    <div class="bar" style="width: {{ (score / maxScore * 100) }}%; background: #4A90E2;">
        {{ score }}
    </div>
</div>
```

---

### Numérotation des pages

```php
return new PdfResponse(
    $this->knpSnappyPdf->getOutputFromHtml($html, [
        'footer-right' => 'Page [page] sur [topage]',
        'footer-font-size' => 9,
    ]),
    $filename
);
```

---

## 📚 Ressources

- **KnpSnappyBundle** : [Documentation officielle](https://github.com/KnpLabs/KnpSnappyBundle)
- **wkhtmltopdf** : [Options complètes](https://wkhtmltopdf.org/usage/wkhtmltopdf.txt)
- **Symfony Voters** : [Guide Security](https://symfony.com/doc/current/security/voters.html)

---

## ✅ Checklist de déploiement

- [ ] Vérifier que `wkhtmltopdf` est installé sur le serveur de production
- [ ] Ajuster le chemin dans `.env` pour l'environnement prod
- [ ] Tester toutes les routes PDF
- [ ] Vérifier les permissions (Voter)
- [ ] Optimiser les templates pour la performance
- [ ] Ajouter des logs pour le monitoring
- [ ] Configurer un timeout pour les générations longues

---

## 🐛 Problèmes courants

### Le PDF est vide
- Vérifier que le HTML n'a pas d'erreurs
- Utiliser `enable-local-file-access` dans les options

### Images manquantes
- Utiliser des chemins absolus : `{{ absolute_url(asset('...')) }}`
- Vérifier les permissions des fichiers

### Timeout
- Augmenter le timeout PHP : `set_time_limit(60)`
- Réduire la complexité du HTML

### Caractères mal encodés
- Ajouter `'encoding' => 'UTF-8'` dans les options
- Vérifier le charset HTML : `<meta charset="UTF-8">`

---

## 🎉 Conclusion

KnpSnappyBundle est maintenant intégré et opérationnel ! Vous pouvez :
- ✅ Générer des PDFs professionnels des tests
- ✅ Créer des rapports complets pour les patients
- ✅ Prévisualiser avant téléchargement
- ✅ Gérer les permissions avec le Voter

**Prochaines étapes suggérées** :
- Ajouter des graphiques visuels (Chart.js converti en images)
- Implémenter l'envoi automatique par email
- Créer des modèles de rapports personnalisables
- Ajouter un watermark pour les versions de démo
