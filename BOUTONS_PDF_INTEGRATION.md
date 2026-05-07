# 🎯 Boutons PDF - Guide d'Intégration

## ✅ Boutons ajoutés dans votre application

### 1. **Page de Résultat du Test** (`resultat.html.twig`)

Après avoir terminé un test, l'utilisateur voit maintenant :

```twig
{# Boutons de téléchargement PDF #}
<div class="mb-4">
    <a href="{{ path('test_pdf_preview', {id: test.id}) }}" 
       class="btn btn-outline-info btn-lg px-4 me-2 mb-2" 
       target="_blank">
        <i class="fa-solid fa-eye"></i> Prévisualiser le PDF
    </a>
    <a href="{{ path('test_pdf_download', {id: test.id}) }}" 
       class="btn btn-success btn-lg px-4 me-2 mb-2">
        <i class="fa-solid fa-file-pdf"></i> Télécharger le PDF
    </a>
</div>
```

**Résultat :**
- ✅ Bouton "Prévisualiser le PDF" (s'ouvre dans un nouvel onglet)
- ✅ Bouton "Télécharger le PDF" (téléchargement immédiat)

---

### 2. **Page Historique des Tests** (`historique.html.twig`)

#### A. Bouton Rapport Complet (en haut)

Dans la section "Vue d'ensemble" :

```twig
<a href="{{ path('test_pdf_rapport_patient', {patientId: app.user.id}) }}" 
   class="btn btn-success btn-sm">
    <i class="fa-solid fa-file-pdf"></i> Rapport Complet PDF
</a>
```

**Fonctionnalité :** Télécharge un rapport PDF avec TOUS les tests du patient

#### B. Bouton PDF par Test (dans chaque carte)

Pour chaque test listé :

```twig
<a href="{{ path('test_pdf_download', {id: test.id}) }}" 
   class="btn btn-success btn-sm" 
   title="Télécharger le PDF">
    <i class="fa-solid fa-file-pdf"></i> PDF
</a>
```

**Fonctionnalité :** Télécharge le PDF d'un test spécifique

---

## 🔧 Comment ajouter les boutons ailleurs

### Template Twig générique

```twig
{# Pour un test spécifique #}
{% if test is defined and test.id %}
    <div class="btn-group">
        {# Prévisualisation #}
        <a href="{{ path('test_pdf_preview', {id: test.id}) }}" 
           class="btn btn-outline-secondary" 
           target="_blank">
            👁️ Aperçu
        </a>
        
        {# Téléchargement #}
        <a href="{{ path('test_pdf_download', {id: test.id}) }}" 
           class="btn btn-primary">
            📥 Télécharger
        </a>
    </div>
{% endif %}

{# Rapport complet patient #}
{% if app.user %}
    <a href="{{ path('test_pdf_rapport_patient', {patientId: app.user.id}) }}" 
       class="btn btn-success">
        📊 Mon Rapport Complet
    </a>
{% endif %}
```

---

## 🎨 Styles de boutons disponibles

### Style 1 : Boutons séparés
```twig
<a href="{{ path('test_pdf_preview', {id: test.id}) }}" 
   class="btn btn-info" target="_blank">
    Prévisualiser
</a>
<a href="{{ path('test_pdf_download', {id: test.id}) }}" 
   class="btn btn-success">
    Télécharger
</a>
```

### Style 2 : Groupe de boutons
```twig
<div class="btn-group" role="group">
    <a href="{{ path('test_pdf_preview', {id: test.id}) }}" 
       class="btn btn-outline-primary" target="_blank">
        👁️
    </a>
    <a href="{{ path('test_pdf_download', {id: test.id}) }}" 
       class="btn btn-primary">
        📥 PDF
    </a>
</div>
```

### Style 3 : Dropdown (menu déroulant)
```twig
<div class="dropdown">
    <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
        📄 PDF
    </button>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item" href="{{ path('test_pdf_preview', {id: test.id}) }}" target="_blank">
                👁️ Prévisualiser
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ path('test_pdf_download', {id: test.id}) }}">
                📥 Télécharger
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item" href="{{ path('test_pdf_rapport_patient', {patientId: app.user.id}) }}">
                📊 Rapport Complet
            </a>
        </li>
    </ul>
</div>
```

### Style 4 : Icônes uniquement (compact)
```twig
<a href="{{ path('test_pdf_preview', {id: test.id}) }}" 
   class="btn btn-sm btn-outline-info" 
   target="_blank" 
   title="Prévisualiser">
    <i class="fa-solid fa-eye"></i>
</a>
<a href="{{ path('test_pdf_download', {id: test.id}) }}" 
   class="btn btn-sm btn-success" 
   title="Télécharger PDF">
    <i class="fa-solid fa-file-pdf"></i>
</a>
```

---

## 📍 Emplacements recommandés

### 1. **Dashboard Patient**
```twig
{# templates/patient/dashboard.html.twig #}
<div class="card">
    <div class="card-header">
        <h5>Mes Tests Récents</h5>
    </div>
    <div class="card-body">
        {% for test in recentTests %}
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span>Test {{ test.categorie }} - {{ test.dateDebut|date('d/m/Y') }}</span>
            <a href="{{ path('test_pdf_download', {id: test.id}) }}" 
               class="btn btn-sm btn-success">
                <i class="fa-solid fa-download"></i>
            </a>
        </div>
        {% endfor %}
    </div>
</div>
```

### 2. **Profil Psychologue**
```twig
{# Pour voir les tests d'un patient spécifique #}
<a href="{{ path('test_pdf_rapport_patient', {patientId: patient.id}) }}" 
   class="btn btn-primary">
    📊 Voir le Rapport Complet de {{ patient.nom }}
</a>
```

### 3. **Email de notification**
```twig
{# Dans le template d'email après un test #}
<p>Votre test est terminé !</p>
<a href="{{ absolute_url(path('test_pdf_download', {id: test.id})) }}" 
   style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none;">
    Télécharger le Résultat PDF
</a>
```

---

## 🔒 Sécurité

Les permissions sont automatiquement gérées par le **TestAdaptatifVoter** :

| Rôle | Peut télécharger |
|------|------------------|
| **Patient** | ✅ Ses propres tests uniquement |
| **Psychologue** | ✅ Tests de ses patients |
| **Admin** | ✅ Tous les tests |

**Aucune vérification supplémentaire nécessaire** dans les templates !

---

## ✅ Checklist d'intégration

Lorsque vous ajoutez des boutons PDF ailleurs :

- [ ] Vérifier que `test.id` existe
- [ ] Utiliser `target="_blank"` pour la prévisualisation
- [ ] Ajouter des icônes FontAwesome pour la clarté
- [ ] Tester avec un utilisateur patient (permissions)
- [ ] Tester le téléchargement (fichier téléchargé correctement)
- [ ] Vérifier le responsive (mobile/tablette)

---

## 🧪 Test en ligne de commande

```bash
# Tester le téléchargement
Invoke-WebRequest "http://localhost:8000/test/pdf/55/telecharger" -OutFile "mon_test.pdf"

# Vérifier que le fichier existe
Get-Item mon_test.pdf
```

---

## 📱 Responsive

Les boutons s'adaptent automatiquement aux petits écrans grâce aux classes Bootstrap :

- `btn-lg` → Boutons larges sur desktop
- `btn-sm` → Boutons compacts pour listes
- `mb-2` → Marge pour empiler sur mobile

---

## 🎨 Personnalisation des couleurs

```twig
{# Vert pour téléchargement #}
<a href="..." class="btn btn-success">PDF</a>

{# Bleu pour prévisualisation #}
<a href="..." class="btn btn-info">Aperçu</a>

{# Orange pour rapport complet #}
<a href="..." class="btn btn-warning">Rapport</a>

{# Rouge pour actions importantes #}
<a href="..." class="btn btn-danger">Urgent</a>
```

---

## 📊 Exemples réels

### Dashboard avec statistiques
```twig
<div class="row">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3>{{ testsCount }}</h3>
                <p>Tests passés</p>
                <a href="{{ path('test_pdf_rapport_patient', {patientId: app.user.id}) }}" 
                   class="btn btn-primary btn-block">
                    📥 Tout télécharger
                </a>
            </div>
        </div>
    </div>
</div>
```

### Table avec actions
```twig
<table class="table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Score</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        {% for test in tests %}
        <tr>
            <td>{{ test.dateDebut|date('d/m/Y') }}</td>
            <td>{{ test.categorie }}</td>
            <td>{{ test.scoreActuel }}</td>
            <td>
                <a href="{{ path('test_pdf_download', {id: test.id}) }}" 
                   class="btn btn-sm btn-success">
                    PDF
                </a>
            </td>
        </tr>
        {% endfor %}
    </tbody>
</table>
```

---

**Prêt à utiliser !** 🎉

Les boutons sont maintenant disponibles partout où vous affichez des tests.
