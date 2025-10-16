# 📄 Guide - Génération de contrats de bail depuis la section "Baux"

## 📋 Vue d'ensemble

La section "Baux" dispose maintenant de **fonctionnalités complètes de génération de contrats PDF** ! Vous pouvez générer des contrats individuels ou en masse, directement depuis l'interface utilisateur.

---

## ✨ NOUVELLES FONCTIONNALITÉS

### 1. Génération individuelle depuis la page de détail

**URL** : `/contrats/{id}` (page de détail d'un bail)

**Boutons ajoutés** :
- ✅ **"Générer contrat PDF"** : Génère et sauvegarde le contrat dans les documents
- ✅ **"Télécharger"** : Télécharge directement le PDF sans le sauvegarder

**Emplacement** : Section "Actions" (colonne de droite)

---

### 2. Génération depuis la liste des baux

**URL** : `/contrats/` (liste de tous les baux)

**Menu dropdown "Actions PDF"** :
- ✅ **Générer tous les contrats** : Génère les PDF pour tous les baux
- ✅ **Générer contrats actifs** : Génère les PDF pour les baux actifs uniquement
- ✅ **Générer tous les échéanciers** : Ouvre les échéanciers dans de nouveaux onglets

**Menu dropdown par bail** :
- ✅ **"Générer contrat PDF"** : Génération individuelle
- ✅ **"Télécharger contrat"** : Téléchargement direct

---

## 🎯 UTILISATION

### Génération individuelle

#### Depuis la page de détail
1. **Aller dans** : Baux → Cliquer sur un bail
2. **Dans la section "Actions"** (colonne de droite)
3. **Cliquer sur** :
   - **"Générer contrat PDF"** → Sauvegarde dans les documents du locataire
   - **"Télécharger"** → Téléchargement direct du PDF

#### Depuis la liste des baux
1. **Aller dans** : Baux
2. **Cliquer sur les 3 points** (⋮) d'un bail
3. **Choisir** :
   - **"Générer contrat PDF"** → Sauvegarde dans les documents
   - **"Télécharger contrat"** → Téléchargement direct

---

### Génération en masse

#### Depuis la liste des baux
1. **Aller dans** : Baux
2. **Cliquer sur** "Actions PDF" (bouton bleu en haut)
3. **Choisir** :
   - **"Générer tous les contrats"** → Tous les baux
   - **"Générer contrats actifs"** → Baux actifs uniquement
   - **"Générer tous les échéanciers"** → Échéanciers des baux actifs

---

## 📦 FONCTIONNALITÉS TECHNIQUES

### Routes disponibles

| Route | Méthode | Description |
|-------|---------|-------------|
| `/contrats/{id}/generer-contrat-document` | POST | Génère et sauvegarde le contrat |
| `/contrats/{id}/contrat-pdf` | GET | Télécharge directement le PDF |
| `/contrats/{id}/echeancier-pdf` | GET | Télécharge l'échéancier |

---

### Différence entre les deux modes

#### "Générer contrat PDF" (POST)
- ✅ **Sauvegarde** le PDF dans `public/uploads/documents/`
- ✅ **Enregistre** le document en base de données
- ✅ **Associe** le document au locataire et au bail
- ✅ **Visible** dans la section "Mes documents" du locataire
- ✅ **Persistant** : Le document reste disponible

#### "Télécharger" (GET)
- ✅ **Télécharge** directement le PDF
- ❌ **Ne sauvegarde pas** le fichier
- ❌ **Non visible** dans les documents
- ✅ **Rapide** : Génération à la volée

---

## 🎨 INTERFACE UTILISATEUR

### Page de détail d'un bail

**Section "Actions"** (colonne de droite) :
```
┌─────────────────────────┐
│ 📄 Génération de contrat │
│ ┌─────────────────────┐ │
│ │ Générer contrat PDF │ │ ← Bouton principal
│ └─────────────────────┘ │
│ ┌─────────────────────┐ │
│ │ Télécharger         │ │ ← Bouton secondaire
│ └─────────────────────┘ │
│ ─────────────────────── │
│ 💳 Nouveau paiement     │
│ 🔧 Demande maintenance  │
│ 📁 Ajouter document     │
│ 📅 Générer loyers       │
└─────────────────────────┘
```

---

### Liste des baux

**Bouton "Actions PDF"** (en haut) :
```
Actions PDF ▼
├── Génération de contrats
│   ├── Générer tous les contrats
│   └── Générer contrats actifs
└── Échéanciers
    └── Générer tous les échéanciers
```

**Menu dropdown par bail** (⋮) :
```
⋮ ▼
├── 👁 Voir
├── ✏️ Modifier
├── ─────────────────
├── 📄 Générer contrat PDF
├── ⬇️ Télécharger contrat
├── ─────────────────
├── 🔄 Renouveler (si actif)
└── ❌ Résilier (si actif)
```

---

## 🚀 FONCTIONNALITÉS AVANCÉES

### Génération en masse intelligente

#### Filtrage automatique
- **"Générer tous les contrats"** : Tous les baux (actifs, terminés, résiliés)
- **"Générer contrats actifs"** : Uniquement les baux avec statut "Actif"
- **"Générer tous les échéanciers"** : Uniquement les baux actifs

#### Confirmation utilisateur
- **Dialog de confirmation** avant génération en masse
- **Compteur** : "X contrats en cours de génération..."
- **Gestion des erreurs** : Messages d'alerte si aucun bail trouvé

---

### JavaScript intelligent

#### Détection du statut
```javascript
const statusBadge = row.querySelector('.badge');
if (statusBadge && statusBadge.textContent.trim() === 'Actif') {
    // Générer uniquement pour les baux actifs
}
```

#### Génération asynchrone
```javascript
activeLeases.forEach(row => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/contrats/${leaseId}/generer-contrat-document`;
    form.style.display = 'none';
    document.body.appendChild(form);
    form.submit();
});
```

---

## 📊 EXEMPLES PRATIQUES

### Exemple 1 : Générer un contrat pour un nouveau bail

**Scénario** : Vous venez de créer un bail et voulez générer le contrat

**Actions** :
1. Aller dans Baux → Cliquer sur le nouveau bail
2. Dans "Actions" → Cliquer "Générer contrat PDF"
3. Le contrat est généré et sauvegardé
4. Le locataire peut le voir dans "Mes documents"

---

### Exemple 2 : Générer tous les contrats actifs

**Scénario** : Vous voulez mettre à jour tous les contrats avec la nouvelle configuration

**Actions** :
1. Aller dans Baux
2. Cliquer "Actions PDF" → "Générer contrats actifs"
3. Confirmer dans le dialog
4. Tous les contrats actifs sont générés automatiquement

---

### Exemple 3 : Télécharger un contrat rapidement

**Scénario** : Vous voulez juste voir un contrat sans le sauvegarder

**Actions** :
1. Aller dans Baux
2. Cliquer les 3 points (⋮) du bail
3. Cliquer "Télécharger contrat"
4. Le PDF s'ouvre directement dans le navigateur

---

## 🔧 INTÉGRATION AVEC LE SYSTÈME

### Configuration des contrats

Les contrats générés utilisent automatiquement :
- ✅ **Configuration personnalisée** depuis Admin → Configuration contrats
- ✅ **Thèmes de couleurs** appliqués
- ✅ **Logo et informations** de l'entreprise
- ✅ **Titres personnalisés** des sections

### Gestion des documents

Les contrats générés via "Générer contrat PDF" :
- ✅ **Apparaissent** dans la section "Mes documents" du locataire
- ✅ **Sont associés** au bail et au locataire
- ✅ **Ont un type** : "Contrat de location"
- ✅ **Sont téléchargeables** depuis l'interface

---

## 🎊 AVANTAGES

### 1. Simplicité d'utilisation

✅ **Interface intuitive** : Boutons clairement identifiés  
✅ **Actions rapides** : Génération en 1 clic  
✅ **Feedback utilisateur** : Messages de confirmation  

### 2. Flexibilité

✅ **Génération individuelle** : Pour un bail spécifique  
✅ **Génération en masse** : Pour plusieurs baux  
✅ **Deux modes** : Sauvegarde ou téléchargement direct  

### 3. Intégration complète

✅ **Configuration centralisée** : Utilise les paramètres admin  
✅ **Gestion des documents** : Intégration avec le système de documents  
✅ **Permissions** : Respecte les rôles utilisateur  

### 4. Performance

✅ **Génération rapide** : PDF créés à la volée  
✅ **Gestion mémoire** : Optimisé pour les générations en masse  
✅ **Cache** : Configuration mise en cache  

---

## 🚀 PROCHAINES ÉTAPES POSSIBLES

### Améliorations futures

1. **Barre de progression** : Pour les générations en masse
2. **Notifications** : Alertes quand la génération est terminée
3. **Historique** : Log des générations de contrats
4. **Templates multiples** : Différents types de contrats
5. **Signature électronique** : Intégration avec des services de signature

---

## 📚 DOCUMENTATION LIÉE

- `CONTRAT_GENERIQUE_GUIDE.md` - Configuration des contrats
- `GESTION_MENUS_ADMIN.md` - Gestion des menus
- `ACL_SYSTEM_GUIDE.md` - Système de permissions

---

**📅 Version** : 1.0  
**📄 Date** : 12 Octobre 2025  
**✨ Statut** : Opérationnel  

---

**📄 La génération de contrats depuis la section "Baux" est maintenant complètement fonctionnelle !**
