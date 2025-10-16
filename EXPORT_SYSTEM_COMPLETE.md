# 📊 Système d'Export Excel/PDF - MYLOCCA

## ✅ Fonctionnalités Implémentées

### **🎯 Exports Disponibles**

#### **📈 Rapports Financiers**
- **Rapport Financier** : Paiements, revenus, dépenses par mois
- **Export Paiements** : Liste détaillée avec filtres par date et statut
- **Paiements Impayés** : Loyers en retard avec calcul des jours de retard

#### **👥 Gestion Locataires et Biens**
- **Liste Locataires** : Données complètes avec option historique
- **Inventaire Biens** : Propriétés avec inventaire détaillé
- **Export Baux** : Contrats par statut (actifs, expirés, etc.)

#### **💰 Rapports Comptables et Fiscaux**
- **Déclaration Fiscale** : Rapport annuel avec calcul base imposable
- **Rapport Comptable** : Analyse complète par période
- **Export Complet** : Toutes les données dans un ZIP

---

## 🚀 Installation et Configuration

### **1. Dépendances Installées**
```bash
composer require phpoffice/phpspreadsheet
```

### **2. Structure Créée**
```
src/Controller/Admin/ExportController.php    ← Contrôleur principal
src/Service/ExportService.php                ← Service de génération
templates/admin/export/index.html.twig      ← Interface utilisateur
var/exports/                                 ← Dossier de stockage temporaire
```

### **3. Menu Ajouté**
- **📊 Exports** dans le menu Administration
- Accessible uniquement aux `ROLE_ADMIN`

---

## 📋 Utilisation

### **Accès aux Exports**
1. Connectez-vous en tant qu'administrateur
2. Menu **Administration** → **📊 Exports**
3. Choisissez le type d'export souhaité
4. Sélectionnez le format (Excel/PDF/ZIP)
5. Téléchargez le fichier généré

### **Types de Fichiers Générés**

#### **Excel (.xlsx)**
- ✅ Formatage professionnel avec couleurs
- ✅ En-têtes stylisés et bordures
- ✅ Auto-resize des colonnes
- ✅ Calculs automatiques (totaux, moyennes)

#### **PDF (.pdf)**
- ⚠️ **À implémenter** avec TCPDF ou DomPDF
- Actuellement retourne une erreur

#### **ZIP (.zip)**
- ✅ Export complet de toutes les données
- ✅ Fichiers CSV séparés par entité
- ✅ Sauvegarde complète du système

---

## 🔧 Configuration Technique

### **ExportService - Fonctionnalités**

#### **Méthodes Principales**
```php
// Rapports financiers
generateFinancialReport($year, $month, $format)
generatePaymentsExport($startDate, $endDate, $status, $format)
generateOverduePaymentsExport($format)

// Gestion locataires/biens
generateTenantsExport($includeHistory, $format)
generatePropertiesExport($includeInventory, $format)
generateLeasesExport($status, $format)

// Comptabilité/fiscal
generateTaxDeclaration($year, $format)
generateAccountingReport($startDate, $endDate, $format)
generateCompleteExport($year, $format)
```

#### **Fonctionnalités Excel**
- **Style professionnel** : Couleurs, bordures, alignement
- **En-têtes dynamiques** : Titre, période, totaux
- **Données formatées** : Dates, montants, statuts
- **Auto-resize** : Colonnes adaptées au contenu

#### **Filtres Disponibles**
- **Période** : Date de début/fin
- **Statut** : Paiements (completed, pending, overdue)
- **Année/Mois** : Rapports périodiques
- **Options** : Historique, inventaire complet

---

## 📊 Exemples d'Exports

### **Rapport Financier Mensuel**
```
Titre: Rapport Financier 2025-10
Période: 2025-10
Total des revenus: 15,420.00 €
Nombre de paiements: 12

| Date       | Locataire    | Propriété     | Montant    | Statut    | Méthode |
|------------|--------------|---------------|------------|-----------|---------|
| 01/10/2025 | Dupont Jean  | 123 Rue ABC   | 1,200.00 €| completed | Virement|
| 02/10/2025 | Martin Marie | 456 Rue DEF   | 850.00 €  | completed | Chèque  |
```

### **Paiements Impayés**
```
Titre: Paiements Impayés
Total des impayés: 3,650.00 €

| Locataire    | Propriété     | Montant    | Date Échéance | Jours Retard | Actions      |
|--------------|---------------|------------|----------------|--------------|--------------|
| Dupont Jean  | 123 Rue ABC   | 1,200.00 €| 01/10/2025    | 15 jours     | Relance requise|
| Martin Marie | 456 Rue DEF   | 850.00 €  | 05/10/2025    | 11 jours     | Relance requise|
```

### **Déclaration Fiscale Annuelle**
```
Titre: Déclaration Fiscale 2025
Année: 2025
Revenus bruts: 185,040.00 €
Base imposable (70%): 129,528.00 €

| Mois    | Revenus      | Nb Paiements | Moyenne mensuelle |
|---------|--------------|--------------|-------------------|
| 2025-01 | 15,420.00 €  | 12          | 1,285.00 €       |
| 2025-02 | 15,420.00 €  | 12          | 1,285.00 €       |
| 2025-03 | 15,420.00 €  | 12          | 1,285.00 €       |
```

---

## 🛠️ Améliorations Futures

### **PDF Support**
```php
// À implémenter avec TCPDF ou DomPDF
composer require tecnickcom/tcpdf
// ou
composer require dompdf/dompdf
```

### **Filtres Avancés**
- Filtrage par propriétaire/gestionnaire
- Filtrage par type de propriété
- Filtrage par montant (min/max)
- Filtrage par méthode de paiement

### **Templates Personnalisés**
- Logo de l'entreprise
- En-têtes/pieds de page personnalisés
- Couleurs de marque
- Formats de date localisés

### **Planification Automatique**
- Exports automatiques mensuels
- Envoi par email
- Stockage cloud (AWS S3, Google Drive)

---

## 🔍 Tests et Validation

### **Tests à Effectuer**

#### **1. Test Excel**
```bash
# Vérifier qu'un fichier Excel se génère correctement
curl "https://127.0.0.1:8000/admin/exports/paiements?format=excel"
```

#### **2. Test Interface**
1. Aller sur `/admin/exports`
2. Cliquer sur chaque bouton d'export
3. Vérifier le téléchargement
4. Ouvrir le fichier Excel généré

#### **3. Test Données**
- Vérifier que toutes les colonnes sont présentes
- Contrôler le formatage des montants
- Valider les calculs de totaux
- Tester avec différents filtres

---

## 📝 Notes Techniques

### **Sécurité**
- ✅ Accès restreint aux `ROLE_ADMIN`
- ✅ Validation des paramètres d'entrée
- ✅ Nettoyage automatique des fichiers temporaires
- ✅ Protection CSRF sur les formulaires

### **Performance**
- ✅ Génération à la demande (pas de cache)
- ✅ Fichiers temporaires supprimés après 24h
- ✅ Requêtes optimisées avec JOIN
- ✅ Limitation de la mémoire avec `entityManager->clear()`

### **Compatibilité**
- ✅ Excel 2016+ (.xlsx)
- ✅ LibreOffice Calc
- ✅ Google Sheets (import)
- ✅ Multi-navigateurs

---

## 🎯 Prochaines Étapes

1. **✅ Implémenter le support PDF** avec TCPDF
2. **✅ Ajouter des filtres avancés** dans l'interface
3. **✅ Créer des templates personnalisés** avec logo
4. **✅ Ajouter la planification automatique** des exports
5. **✅ Intégrer l'envoi par email** des rapports

---

**Le système d'export est maintenant opérationnel ! 🚀**

**Accès :** Menu Administration → 📊 Exports
