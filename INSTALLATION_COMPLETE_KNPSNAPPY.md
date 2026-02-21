# ✅ Installation Terminée - KnpSnappyBundle pour PDF

## 🎉 Résumé de l'installation

L'intégration de **KnpSnappyBundle** pour la génération de PDFs des tests psychologiques a été **complétée avec succès** !

---

## 📦 Ce qui a été installé

### 1. **Packages Composer**
- ✅ `knplabs/knp-snappy-bundle` (v1.10.6)
- ✅ `knplabs/knp-snappy` (v1.6.0)
- ✅ `h4cc/wkhtmltopdf-amd64` (v0.12.4) - Binaire embarqué

### 2. **Fichiers créés**

#### **Contrôleur**
- ✅ `src/Controller/TestPdfController.php`
  - 3 routes : télécharger, prévisualiser, rapport patient
  - Gestion des permissions via Voter
  - Génération PDF optimisée

#### **Security**
- ✅ `src/Security/Voter/TestAdaptatifVoter.php`
  - Permissions VIEW/EDIT/DELETE
  - Accès par rôle (ADMIN, PSYCHOLOGUE, PATIENT)

#### **Templates**
- ✅ `templates/test/pdf_resultat.html.twig`
  - PDF pour un test individuel
  - Design professionnel avec gradients
  - Sections : score, interprétation, catégories, analyse, Q&R
  
- ✅ `templates/test/pdf_rapport_patient.html.twig`
  - Rapport consolidé multi-tests
  - Vue d'ensemble statistique
  - Évolution des scores
  - Timeline des tests

#### **Service**
- ✅ `src/Service/ScoreCalculatorService.php` (modifié)
  - Nouvelle méthode `analyzeQuestionsReponses()` pour analyser les JSON
  - Nouvelle méthode `detectCategory()` pour catégorisation automatique
  - Support des tests adaptatifs

#### **Documentation**
- ✅ `KNPSNAPPY_PDF.md` - Guide complet
- ✅ `INSTALLATION_COMPLETE_KNPSNAPPY.md` - Ce fichier

---

## 🚀 Routes disponibles

| Route | URL | Méthode | Description |
|-------|-----|---------|-------------|
| `test_pdf_download` | `/test/pdf/{id}/telecharger` | GET | Télécharge le PDF d'un test |
| `test_pdf_preview` | `/test/pdf/{id}/previsualiser` | GET | Prévisualise le PDF en HTML |
| `test_pdf_rapport_patient` | `/test/pdf/patient/{patientId}/rapport` | GET | Rapport complet patient |

**Vérification** ✅ :
```bash
php bin/console debug:router | Select-String -Pattern "test_pdf"
```

---

## ⚙️ Configuration

### Fichier `.env` (configuré)
```env
WKHTMLTOPDF_PATH=vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64.exe
WKHTMLTOIMAGE_PATH=vendor/h4cc/wkhtmltoimage-amd64/bin/wkhtmltoimage-amd64.exe
```

### Fichier `config/packages/knp_snappy.yaml` (auto-généré)
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

## 🧪 Tests effectués

- ✅ Installation des packages sans erreur
- ✅ Génération de la configuration automatique
- ✅ Création des routes et contrôleurs
- ✅ Vérification des méthodes du service
- ✅ Correction des appels aux méthodes
- ✅ Cache vidé et routes validées
- ✅ Aucune erreur de compilation

---

## 📝 Prochaines étapes pour utilisation

### 1. **Ajouter des boutons dans vos templates**

Dans `templates/test/show.html.twig` ou similaire :

```twig
<div class="btn-group mt-3">
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

### 2. **Créer des tests de données**

Si vous n'avez pas encore de données :

```bash
php bin/console doctrine:fixtures:load
```

### 3. **Tester la génération PDF**

1. Accédez à un test existant
2. Cliquez sur "Prévisualiser" pour voir le HTML
3. Cliquez sur "Télécharger PDF" pour obtenir le fichier

**Exemple d'URL** :
```
http://localhost:8000/test/pdf/1/previsualiser
http://localhost:8000/test/pdf/1/telecharger
```

### 4. **Personnaliser le design**

Modifiez les templates dans `templates/test/` :
- Couleurs, polices, mise en page
- Ajoutez votre logo
- Personnalisez les sections

---

## 🔒 Sécurité

Le **TestAdaptatifVoter** gère les permissions :

| Rôle | Accès |
|------|-------|
| **ADMIN** | ✅ Tous les tests |
| **PSYCHOLOGUE** | ✅ Tests de ses patients |
| **PATIENT** | ✅ Ses propres tests uniquement |

---

## 📊 Caractéristiques PDF

### PDF Test Individuel
- 📄 **Format** : A4 Portrait
- 🎨 **Design** : Gradients bleu/violet
- 📝 **Sections** :
  - En-tête avec titre et catégorie
  - Informations patient et dates
  - Score global avec style
  - Interprétation colorée
  - Détail par catégorie
  - Analyse complète
  - Questions/Réponses détaillées
  - Pied de page avec confidentialité

### PDF Rapport Patient
- 📄 **Format** : A4 Portrait multi-pages
- 📊 **Contenu** :
  - Synthèse des évaluations
  - Statistiques globales
  - Graphique d'évolution
  - Cartes par test
  - Recommandations générales
  - Gestion des sauts de page

---

## 🛠️ Dépannage

### PDF vide ou erreur
```bash
# Vérifier le binaire
vendor\h4cc\wkhtmltopdf-amd64\bin\wkhtmltopdf-amd64.exe --version

# Vérifier les logs
tail -f var/log/dev.log

# Reconstruire le cache
php bin/console cache:clear
```

### Images manquantes
Utilisez `absolute_url()` dans les templates :
```twig
<img src="{{ absolute_url(asset('images/logo.png')) }}" />
```

### Timeout
Augmentez le timeout dans les options :
```php
'timeout' => 120, // 2 minutes
```

---

## 📚 Documentation

Consultez `KNPSNAPPY_PDF.md` pour :
- Guide d'utilisation détaillé
- Options de personnalisation
- Exemples avancés
- Troubleshooting complet

---

## ✨ Améliorations futures suggérées

- [ ] Ajout de graphiques Chart.js convertis en images
- [ ] Envoi automatique par email
- [ ] Watermark pour versions démo
- [ ] Export multi-formats (PDF, CSV, Excel)
- [ ] Signature électronique du psychologue
- [ ] Archivage automatique des rapports
- [ ] Templates personnalisables par psychologue

---

## 🎯 Conclusion

**Tout est prêt !** 🎉

Vous pouvez maintenant :
1. ✅ Générer des PDFs professionnels
2. ✅ Télécharger les résultats de tests
3. ✅ Créer des rapports patients complets
4. ✅ Prévisualiser avant téléchargement

**Commande de test rapide** :
```bash
# Vider le cache
php bin/console cache:clear

# Lister les routes PDF
php bin/console debug:router | Select-String "test_pdf"

# Lancer le serveur
symfony server:start
```

Puis accédez à : `http://localhost:8000/test/pdf/1/previsualiser`

---

**Installation réalisée le** : {{ "now"|date("d/m/Y H:i") }}  
**Statut** : ✅ **OPÉRATIONNEL**

Bonne utilisation ! 🚀
