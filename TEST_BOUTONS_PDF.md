# ✅ Test des Boutons PDF - Guide Rapide

## 🎯 Ce qui a été ajouté

### ✨ Nouveaux boutons dans votre application

1. **Page de résultat du test** (après avoir terminé un test)
   - ✅ Bouton "Prévisualiser le PDF" 
   - ✅ Bouton "Télécharger le PDF"

2. **Page d'historique des tests**
   - ✅ Bouton "Rapport Complet PDF" (en haut)
   - ✅ Bouton "PDF" pour chaque test individuel

---

## 🧪 Test Étape par Étape

### Étape 1 : Terminer un test

1. Accédez à : **http://localhost:8000/test-adaptatif/demarrer/stress**
2. Répondez aux questions
3. Terminez le test

**➜ Vous devriez voir apparaître :**
```
┌─────────────────────────────────────────┐
│  👁️ Prévisualiser le PDF               │
│  📥 Télécharger le PDF                  │
└─────────────────────────────────────────┘
```

### Étape 2 : Tester la prévisualisation

1. Cliquez sur **"Prévisualiser le PDF"**
2. ✅ Un nouvel onglet s'ouvre
3. ✅ Vous voyez le PDF en HTML
4. ✅ Design professionnel avec gradients

### Étape 3 : Tester le téléchargement

1. Cliquez sur **"Télécharger le PDF"**
2. ✅ Un fichier PDF se télécharge
3. ✅ Nom : `test_stress_2026-02-21.pdf`
4. Ouvrez le PDF
5. ✅ Toutes les sections sont présentes

### Étape 4 : Tester l'historique

1. Accédez à : **http://localhost:8000/test-adaptatif/historique**
2. ✅ Vous voyez le bouton **"Rapport Complet PDF"** en haut
3. ✅ Chaque test a un bouton **"PDF"**

### Étape 5 : Tester le rapport complet

1. Cliquez sur **"Rapport Complet PDF"**
2. ✅ Un PDF multi-pages se télécharge
3. ✅ Contient TOUS vos tests
4. ✅ Statistiques + évolution

---

## 🚀 Test Rapide avec les fixtures

Vous avez déjà 5 tests créés par les fixtures :

| ID | Catégorie | Score | Terminé |
|----|-----------|-------|---------|
| 55 | Stress | 16/20 | ✅ |
| 56 | Dépression | 12/24 | ✅ |
| 57 | IQ | 7/28 | ✅ |
| 58 | Anxiété | 2/20 | ✅ |
| 59 | Stress | 2/8 | ❌ Non terminé |

### URLs directes à tester :

**Historique (voir tous les boutons) :**
```
http://localhost:8000/test-adaptatif/historique
```

**Prévisualiser un test :**
```
http://localhost:8000/test/pdf/55/previsualiser
http://localhost:8000/test/pdf/56/previsualiser
http://localhost:8000/test/pdf/57/previsualiser
http://localhost:8000/test/pdf/58/previsualiser
```

**Télécharger un test :**
```
http://localhost:8000/test/pdf/55/telecharger
http://localhost:8000/test/pdf/56/telecharger
```

**Rapport complet patient :**
```
http://localhost:8000/test/pdf/patient/43/rapport
```

---

## ✅ Checklist de vérification

### Tests individuels
- [ ] Le bouton "Prévisualiser" s'ouvre dans un nouvel onglet
- [ ] Le bouton "Télécharger" déclenche un téléchargement immédiat
- [ ] Le PDF contient toutes les sections (score, interprétation, analyse)
- [ ] Les accents sont corrects (é, è, à, ç)
- [ ] Le design est professionnel

### Historique
- [ ] Le bouton "Rapport Complet PDF" est visible en haut
- [ ] Chaque test a un bouton "PDF" vert
- [ ] Le bouton "Voir" fonctionne toujours
- [ ] Le rapport complet contient tous les tests

### Rapport complet
- [ ] Le PDF a plusieurs pages
- [ ] Vue d'ensemble avec statistiques
- [ ] Évolution des scores (graphique)
- [ ] Détail de chaque test
- [ ] Nom du fichier : `rapport_patient_XX_2026-02-21.pdf`

---

## 🎨 Aperçu visuel

### Page de résultat
```
┌──────────────────────────────────────────────────┐
│              🧠 Résultat du Test                 │
│                                                  │
│  Score : 16/20  ████████████░░░░░░ 80%          │
│                                                  │
│  ┌────────────────────────────────────────────┐ │
│  │  👁️ Prévisualiser le PDF                  │ │
│  │  📥 Télécharger le PDF                     │ │
│  └────────────────────────────────────────────┘ │
│                                                  │
│  🔄 Nouveau Test  📜 Mon Historique             │
└──────────────────────────────────────────────────┘
```

### Page historique
```
┌──────────────────────────────────────────────────┐
│  📊 Vue d'ensemble      📄 Rapport Complet PDF  │
│                                                  │
│  Tests : 5    Questions : 30    Moyenne : 6     │
├──────────────────────────────────────────────────┤
│  ⚠️  Stress - 21/02/2026                        │
│  Score : 16/20    👁️ Voir    📥 PDF            │
├──────────────────────────────────────────────────┤
│  💙  Dépression - 21/02/2026                    │
│  Score : 12/24    👁️ Voir    📥 PDF            │
└──────────────────────────────────────────────────┘
```

---

## 🐛 En cas de problème

### Problème : "Route not found"
```bash
php bin/console cache:clear
php bin/console debug:router | Select-String "test_pdf"
```

### Problème : "Access Denied" (403)
➜ Normal ! Le voter protège les tests. Connectez-vous en tant que patient propriétaire du test.

### Problème : PDF vide
```bash
# Vérifier le binaire
vendor\h4cc\wkhtmltopdf-amd64\bin\wkhtmltopdf-amd64.exe --version

# Vérifier les logs
tail -f var/log/dev.log
```

### Problème : Boutons ne s'affichent pas
```bash
# Vider le cache
php bin/console cache:clear

# Vérifier les templates
Get-Content templates\test_adaptatif\resultat.html.twig | Select-String "test_pdf"
```

---

## 📸 Captures d'écran attendues

### 1. Page de résultat
Vous devriez voir :
- ✅ 2 boutons bien visibles (bleu clair + vert)
- ✅ Icônes FontAwesome (œil + PDF)
- ✅ Boutons responsive (s'empilent sur mobile)

### 2. Historique
Vous devriez voir :
- ✅ Bouton vert "Rapport Complet PDF" en haut à droite
- ✅ Colonne "Actions" avec 2 boutons par ligne
- ✅ Bouton "Voir" bleu + bouton "PDF" vert

### 3. PDF téléchargé
Vous devriez voir :
- ✅ En-tête avec titre et catégorie
- ✅ Score en grand (gradient violet/bleu)
- ✅ Interprétation avec badge coloré
- ✅ Section "Analyse détaillée"
- ✅ Liste questions/réponses
- ✅ Pied de page avec confidentialité

---

## 🎉 Résultat final

Si tous les tests passent, vous avez maintenant :

✅ **3 nouveaux boutons PDF intégrés** dans votre application  
✅ **Téléchargement instantané** après chaque test  
✅ **Prévisualisation HTML** avant téléchargement  
✅ **Rapport complet** multi-tests  
✅ **Design professionnel** automatique  
✅ **Sécurité** gérée par les Voters  

---

## 🚀 Commande de test ultra-rapide

```bash
# Tout tester en 30 secondes
start http://localhost:8000/test-adaptatif/historique
start http://localhost:8000/test/pdf/55/previsualiser
start http://localhost:8000/test/pdf/55/telecharger
```

---

**C'est prêt !** Testez maintenant en accédant à votre application. 🎯
