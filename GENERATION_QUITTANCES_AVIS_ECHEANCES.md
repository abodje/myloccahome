# 📄 Génération Automatique des Quittances et Avis d'Échéances

## 📋 Vue d'ensemble

Système complet de génération automatique des **quittances de loyer** et **avis d'échéances** en PDF, avec intégration dans la section documents et possibilité d'automatisation via tâches planifiées.

---

## 🎯 Fonctionnalités Implémentées

### **1. Service de Génération (`RentReceiptService`)**

#### **Méthodes Principales**

##### **`generateRentReceipt(Payment $payment): Document`**
- Génère une quittance de loyer pour un paiement **payé**
- Vérifie si une quittance existe déjà (évite les doublons)
- Crée un PDF professionnel avec toutes les informations
- Enregistre le document dans la base de données
- Catégorie : "Quittance de loyer"

##### **`generatePaymentNotice(Payment $payment): Document`**
- Génère un avis d'échéance pour un paiement **en attente**
- Vérifie si un avis existe déjà
- Crée un PDF avec appel de loyer
- Enregistre le document dans la base de données
- Catégorie : "Avis d'échéance"

##### **`generateMonthlyReceipts(\DateTime $month): array`**
- Génère toutes les quittances pour un mois donné
- Filtre les paiements payés du mois
- Retourne la liste des documents générés

##### **`generateUpcomingNotices(\DateTime $dueMonth): array`**
- Génère tous les avis d'échéance pour un mois à venir
- Filtre les paiements en attente
- Retourne la liste des documents générés

---

## 📄 Templates PDF

### **1. Quittance de Loyer (`pdf/rent_receipt.html.twig`)**

#### **Sections du Document**

```
┌──────────────────────────────────────────┐
│         QUITTANCE DE LOYER               │
│   Document officiel de paiement          │
├──────────────────────────────────────────┤
│ Entreprise                               │
│ MYLOCCA                                  │
│ Adresse, Téléphone, Email                │
├──────────────────────────────────────────┤
│ LOCATAIRE                                │
│ ▸ Nom et Prénom                          │
│ ▸ Adresse                                │
│ ▸ Téléphone, Email                       │
├──────────────────────────────────────────┤
│ BIEN LOUÉ                                │
│ ▸ Adresse du bien                        │
│ ▸ Type, Surface, Pièces                  │
├──────────────────────────────────────────┤
│ DÉTAILS DU PAIEMENT                      │
│ ┌────────────────────────────────────┐   │
│ │ Loyer - Janvier 2025    50,000 FCFA│   │
│ │ Charges locatives        2,500 FCFA│   │
│ ├────────────────────────────────────┤   │
│ │ TOTAL PAYÉ             52,500 FCFA │   │
│ └────────────────────────────────────┘   │
├──────────────────────────────────────────┤
│    MONTANT TOTAL RÉGLÉ                   │
│        52,500 FCFA                       │
├──────────────────────────────────────────┤
│ Informations de paiement:                │
│ Méthode: CinetPay                        │
│ Date: 15/01/2025                         │
│ Référence: TRX123456                     │
├──────────────────────────────────────────┤
│ Mentions légales                         │
│ (Article 21 de la loi n° 89-462)        │
├──────────────────────────────────────────┤
│  Le Propriétaire      Le Locataire       │
│  [Signature]          [Signature]        │
├──────────────────────────────────────────┤
│ Document généré le 15/01/2025 à 10:30   │
│ Quittance #123 - Bail #45                │
└──────────────────────────────────────────┘
```

#### **Caractéristiques**
- ✅ Design professionnel avec en-tête coloré
- ✅ Tableau récapitulatif détaillé
- ✅ Encadré du montant total (vert)
- ✅ Mentions légales conformes
- ✅ Espace pour signatures
- ✅ Pied de page avec références

---

### **2. Avis d'Échéance (`pdf/payment_notice.html.twig`)**

#### **Sections du Document**

```
┌──────────────────────────────────────────┐
│    ⚠️  AVIS D'ÉCHÉANCE                   │
│   Appel de loyer et charges locatives    │
├──────────────────────────────────────────┤
│ Entreprise                               │
│ MYLOCCA                                  │
│ Adresse, Téléphone, Email                │
├──────────────────────────────────────────┤
│       ⏰ ÉCHÉANCE À VENIR                │
├──────────────────────────────────────────┤
│ DESTINATAIRE                             │
│ ▸ Nom et Prénom                          │
│ ▸ Adresse, Téléphone, Email              │
├──────────────────────────────────────────┤
│ BIEN LOUÉ                                │
│ ▸ Adresse, Type, Surface                │
│ ▸ Loyer mensuel                          │
├──────────────────────────────────────────┤
│ DÉTAIL DE L'ÉCHÉANCE                     │
│ ┌────────────────────────────────────┐   │
│ │ Désignation   Période     Montant  │   │
│ ├────────────────────────────────────┤   │
│ │ Loyer        Fév 2025  50,000 FCFA │   │
│ │ Charges      Fév 2025   2,500 FCFA │   │
│ ├────────────────────────────────────┤   │
│ │ TOTAL À PAYER          52,500 FCFA │   │
│ └────────────────────────────────────┘   │
├──────────────────────────────────────────┤
│    MONTANT TOTAL DÛ                      │
│        52,500 FCFA                       │
│   À PAYER AVANT LE 05/02/2025           │
├──────────────────────────────────────────┤
│ 💳 MOYENS DE PAIEMENT                    │
│ • Paiement en ligne (Mobile Money, CB)   │
│ • Virement bancaire                      │
│ • Espèces (sur rendez-vous)             │
│ • Chèque                                 │
├──────────────────────────────────────────┤
│ ⚠️ IMPORTANT                             │
│ Tout retard peut entraîner pénalités    │
├──────────────────────────────────────────┤
│ Ce document n'est pas une quittance      │
└──────────────────────────────────────────┘
```

#### **Caractéristiques**
- ✅ Design avec alerte visuelle (orange)
- ✅ Icône d'horloge pour l'urgence
- ✅ Encadré du montant dû (rouge)
- ✅ Date limite bien visible
- ✅ Liste des moyens de paiement
- ✅ Avertissement sur les retards

---

## 🎮 Contrôleur (`DocumentController`)

### **Routes Ajoutées**

#### **1. Générer une Quittance Individuelle**
```php
Route: /mes-documents/generer-quittance/{paymentId}
Name: app_document_generate_receipt
Method: POST
```

**Conditions :**
- Le paiement doit exister
- Le statut doit être "Payé"

**Retour :**
- Succès → Redirection vers le document généré
- Erreur → Message flash + redirection

#### **2. Générer un Avis d'Échéance Individuel**
```php
Route: /mes-documents/generer-avis-echeance/{paymentId}
Name: app_document_generate_notice
Method: POST
```

**Conditions :**
- Le paiement doit exister
- Le statut doit être "En attente"

#### **3. Générer Tous les Documents du Mois**
```php
Route: /mes-documents/generer-documents-mois
Name: app_document_generate_monthly
Method: POST
```

**Paramètres :**
- `month` : Format YYYY-MM (ex: 2025-10)

**Actions :**
- Génère toutes les quittances du mois
- Génère tous les avis d'échéance pour le mois suivant

---

## 🎨 Intégration Interface

### **1. Page des Paiements (`payment/index.html.twig`)**

Pour chaque paiement dans le tableau :

#### **Si Paiement = "Payé"**
```html
<button class="btn btn-outline-success" title="Générer une quittance">
    <i class="bi bi-file-earmark-pdf"></i>
</button>
```

#### **Si Paiement = "En attente"**
```html
<button class="btn btn-outline-warning" title="Générer un avis d'échéance">
    <i class="bi bi-file-earmark-text"></i>
</button>
```

### **2. Page des Documents (`document/index.html.twig`)**

Bouton global pour les admins/gestionnaires :
```html
<button class="btn btn-success btn-sm">
    <i class="bi bi-file-earmark-pdf me-2"></i>
    Générer Documents du Mois
</button>
```

---

## 🤖 Automatisation avec Tâches Planifiées

### **Type de Tâche : `GENERATE_RENT_DOCUMENTS`**

#### **Configuration dans TaskManagerService**

```php
case 'GENERATE_RENT_DOCUMENTS':
    $this->executeGenerateRentDocumentsTask($task);
    break;
```

#### **Paramètres de la Tâche**

```json
{
    "month": "2025-10"  // Format: YYYY-MM
}
```

#### **Résultat Enregistré**

```json
{
    "receipts_generated": 25,
    "notices_generated": 30,
    "total": 55,
    "month": "2025-10"
}
```

#### **Exemple de Configuration de Tâche**

**Dans l'interface Admin → Tâches :**
- **Nom** : Génération mensuelle des documents
- **Type** : GENERATE_RENT_DOCUMENTS
- **Fréquence** : Mensuelle
- **Jour** : 1er du mois à 08:00
- **Paramètres** : `{"month": "current"}` (utilise le mois en cours)

---

## 💻 Commande Console

### **Commande : `app:generate-rent-documents`**

#### **Utilisation**

```bash
# Générer pour le mois en cours
php bin/console app:generate-rent-documents

# Générer pour un mois spécifique
php bin/console app:generate-rent-documents --month=2025-10

# Générer uniquement les quittances
php bin/console app:generate-rent-documents --receipts-only

# Générer uniquement les avis d'échéance
php bin/console app:generate-rent-documents --notices-only
```

#### **Options**

| Option | Raccourci | Description | Défaut |
|--------|-----------|-------------|--------|
| `--month` | `-m` | Mois (format YYYY-MM) | Mois en cours |
| `--receipts-only` | `-r` | Uniquement quittances | false |
| `--notices-only` | `-n` | Uniquement avis | false |

#### **Exemple de Sortie**

```
Génération des Documents de Loyer
==================================

Mois : Octobre 2025

📄 Génération des Quittances de Loyer
--------------------------------------

Recherche des paiements payés pour le mois de Octobre 2025...

 ✅ 25 quittance(s) générée(s)

  * Quittance #123 - Quittance de loyer - October 2025 (Jean Dupont)
  * Quittance #124 - Quittance de loyer - October 2025 (Marie Martin)
  ...

⏰ Génération des Avis d'Échéance
----------------------------------

Recherche des paiements à venir pour le mois de Novembre 2025...

 ✅ 30 avis d'échéance généré(s)

  * Avis #150 - Avis d'échéance - November 2025 (Jean Dupont)
  * Avis #151 - Avis d'échéance - November 2025 (Marie Martin)
  ...

 [OK] 🎉 Total : 55 document(s) généré(s) avec succès !
```

---

## 🗂️ Stockage des Documents

### **Emplacement des Fichiers**

```
public/uploads/documents/
├── quittance_Dupont_2025_10.pdf
├── quittance_Martin_2025_10.pdf
├── avis_echeance_Dupont_2025_11.pdf
├── avis_echeance_Martin_2025_11.pdf
└── ...
```

### **Nomenclature**

**Quittances :**
```
quittance_{NOM_LOCATAIRE}_{ANNEE}_{MOIS}.pdf
Exemple: quittance_Dupont_2025_10.pdf
```

**Avis d'Échéances :**
```
avis_echeance_{NOM_LOCATAIRE}_{ANNEE}_{MOIS}.pdf
Exemple: avis_echeance_Dupont_2025_11.pdf
```

---

## 🔄 Flux de Génération

### **Flux Automatique Mensuel**

```
1er du mois à 08:00
    ↓
Tâche planifiée "GENERATE_RENT_DOCUMENTS"
    ↓
Génération des quittances du mois passé
(Pour tous les paiements "Payé")
    ↓
Génération des avis du mois en cours
(Pour tous les paiements "En attente")
    ↓
Enregistrement dans la base de données
    ↓
Documents disponibles dans "Mes documents"
    ↓
Notification aux locataires (optionnel)
```

### **Flux Manuel Individuel**

```
Admin/Gestionnaire sur /mes-paiements/
    ↓
Clic sur icône PDF (vert pour quittance, orange pour avis)
    ↓
Génération instantanée du document
    ↓
Redirection vers la page du document
    ↓
Téléchargement ou consultation
```

### **Flux Manuel Global**

```
Admin/Gestionnaire sur /mes-documents/
    ↓
Clic sur "Générer Documents du Mois"
    ↓
Sélection du mois (optionnel)
    ↓
Génération de tous les documents
    ↓
Message de confirmation avec compteur
    ↓
Documents listés dans la page
```

---

## 📊 Données Incluses dans les Documents

### **Informations Communes**

- ✅ **Entreprise** : Nom, adresse, téléphone, email (depuis paramètres)
- ✅ **Locataire** : Nom, adresse, téléphone, email
- ✅ **Bien** : Adresse, type, surface, pièces
- ✅ **Bail** : Numéro, loyer mensuel, charges

### **Spécifiques aux Quittances**

- ✅ Date de paiement effectif
- ✅ Méthode de paiement
- ✅ Référence de transaction
- ✅ Montant total payé
- ✅ Décomposition (loyer + charges)
- ✅ Espace pour signatures

### **Spécifiques aux Avis d'Échéance**

- ✅ Date limite de paiement
- ✅ Montant total dû
- ✅ Liste des moyens de paiement
- ✅ Avertissement sur les retards
- ✅ Période concernée

---

## 🎨 Design et Mise en Page

### **Style Visuel**

**Quittances (Vert) :**
- Encadré vert pour le montant payé
- Ton positif (paiement effectué)
- Badge "Document officiel"

**Avis d'Échéance (Orange/Rouge) :**
- Encadré rouge pour le montant dû
- Ton d'alerte (paiement à venir)
- Icônes d'avertissement

### **Police et Formatage**

- **Police** : DejaVu Sans (support UTF-8)
- **Format** : A4 Portrait
- **Marges** : 20px
- **Taille** : 12pt (corps), 24pt (titres)

---

## 🔐 Sécurité et Permissions

### **Génération Individuelle**

| Rôle | Quittances | Avis d'Échéance |
|------|------------|-----------------|
| Admin | ✅ Tous | ✅ Tous |
| Gestionnaire | ✅ Ses locataires | ✅ Ses locataires |
| Locataire | ✅ Les siennes | ✅ Les siens |

### **Génération Globale**

| Action | Admin | Gestionnaire | Locataire |
|--------|-------|--------------|-----------|
| Bouton "Générer Documents du Mois" | ✅ | ✅ | ❌ |
| Commande console | ✅ | ❌ | ❌ |
| Tâche planifiée | ✅ | ❌ | ❌ |

---

## 📞 Utilisation

### **Pour les Administrateurs**

#### **Génération Manuelle Globale**
1. Accédez à `/mes-documents/`
2. Cliquez sur "Générer Documents du Mois"
3. Les quittances et avis sont générés automatiquement
4. Consultez la liste dans "Mes documents"

#### **Génération Individuelle**
1. Accédez à `/mes-paiements/`
2. Pour chaque paiement payé, cliquez sur l'icône PDF verte
3. Pour chaque paiement en attente, cliquez sur l'icône document orange

#### **Via Commande Console**
```bash
# Générer pour le mois en cours
php bin/console app:generate-rent-documents

# Générer pour octobre 2025
php bin/console app:generate-rent-documents --month=2025-10
```

#### **Configuration de Tâche Automatique**
1. Accédez à `/admin/taches`
2. Créez une nouvelle tâche :
   - **Type** : GENERATE_RENT_DOCUMENTS
   - **Fréquence** : Mensuelle
   - **Jour** : 1 à 08:00
   - **Paramètres** : `{"month": "current"}`

### **Pour les Gestionnaires**

1. Accédez à `/mes-paiements/`
2. Générez des quittances/avis pour vos locataires
3. Les documents apparaissent dans `/mes-documents/`

### **Pour les Locataires**

1. Accédez à `/mes-documents/`
2. Consultez vos quittances dans "Quittances de loyer"
3. Consultez vos avis dans "Avis d'échéance"
4. Téléchargez ou imprimez selon vos besoins

---

## 🧪 Tests Recommandés

### **Test 1 : Génération Manuelle**
1. Marquer un paiement comme "Payé"
2. Générer une quittance depuis `/mes-paiements/`
3. Vérifier le PDF généré
4. Vérifier l'apparition dans `/mes-documents/`

### **Test 2 : Génération Globale**
1. Cliquer sur "Générer Documents du Mois"
2. Vérifier le nombre de documents générés
3. Vérifier que tous les paiements concernés ont un document

### **Test 3 : Commande Console**
1. Exécuter `php bin/console app:generate-rent-documents`
2. Vérifier la sortie console
3. Vérifier les fichiers générés
4. Vérifier les entrées en base de données

### **Test 4 : Tâche Planifiée**
1. Créer une tâche GENERATE_RENT_DOCUMENTS
2. Exécuter `php bin/console app:run-due-tasks`
3. Vérifier les logs
4. Vérifier les documents générés

---

## 📝 Fichiers Créés/Modifiés

### **Services**
- ✅ `src/Service/RentReceiptService.php` (créé)

### **Commandes**
- ✅ `src/Command/GenerateRentDocumentsCommand.php` (créé)

### **Contrôleurs**
- ✅ `src/Controller/DocumentController.php` (modifié - 3 actions ajoutées)

### **Templates PDF**
- ✅ `templates/pdf/rent_receipt.html.twig` (créé)
- ✅ `templates/pdf/payment_notice.html.twig` (créé)

### **Templates Interface**
- ✅ `templates/payment/index.html.twig` (modifié - boutons ajoutés)
- ✅ `templates/document/index.html.twig` (modifié - bouton global ajouté)

### **Services Modifiés**
- ✅ `src/Service/TaskManagerService.php` (ajout du type GENERATE_RENT_DOCUMENTS)

---

## 🚀 Avantages

### **Pour les Locataires**

1. **Automatique** : Quittances disponibles dès le paiement
2. **Accessible** : Dans "Mes documents" 24/7
3. **Professionnel** : Documents conformes aux normes
4. **Gratuit** : Génération illimitée

### **Pour les Gestionnaires**

1. **Gain de temps** : Génération en un clic
2. **Conformité** : Documents légaux automatiques
3. **Traçabilité** : Tout est enregistré
4. **Automatisation** : Tâches planifiées

### **Pour le Système**

1. **Centralisé** : Un seul service pour tout
2. **Réutilisable** : Méthodes indépendantes
3. **Scalable** : Fonctionne pour des milliers de documents
4. **Maintenable** : Code clair et documenté

---

## 📌 Notes Importantes

### **Prévention des Doublons**

Le service vérifie automatiquement si un document existe déjà :
```php
$existingReceipt = $this->documentRepository->findOneBy([
    'payment' => $payment,
    'category' => 'Quittance de loyer'
]);

if ($existingReceipt) {
    return $existingReceipt; // Retourne le document existant
}
```

### **Gestion des Erreurs**

En cas d'erreur lors de la génération :
- L'erreur est loggée
- Un message flash est affiché
- La génération continue pour les autres documents
- Aucune interruption de l'application

### **Performance**

Pour de gros volumes :
- Génération asynchrone recommandée
- Utiliser les tâches planifiées
- Limiter la génération par batch
- Utiliser un queue system (Symfony Messenger)

---

## 🎯 Prochaines Évolutions

1. **Envoi par email** : Envoyer automatiquement les documents par email
2. **Personnalisation** : Templates personnalisables par entreprise
3. **Multi-langue** : Support de plusieurs langues
4. **Signature électronique** : Intégration DocuSign ou similaire
5. **Archive** : Archivage automatique après X mois

---

**Date de création :** 12 octobre 2025  
**Version :** 1.0  
**Statut :** ✅ Implémenté et opérationnel
