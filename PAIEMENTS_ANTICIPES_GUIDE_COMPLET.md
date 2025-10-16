# 💰 GUIDE COMPLET - Paiements Anticipés (Acomptes)

## 📋 Vue d'ensemble

Le système de **paiements anticipés** permet aux locataires de constituer un solde (acompte) qui sera automatiquement utilisé pour payer leurs loyers futurs. C'est idéal pour les loyers élevés où le locataire préfère payer "petit à petit".

---

## 🎯 Cas d'usage concret

### Exemple : Loyer de 20 000€

**Sans paiements anticipés** :
- Le locataire doit payer 20 000€ d'un coup chaque mois
- Stress financier important
- Risque de retard de paiement

**Avec paiements anticipés** :
1. **Semaine 1** : Le locataire verse **5 000€** (acompte)
   - Solde disponible : 5 000€
   
2. **Semaine 2** : Le locataire verse **7 000€** (acompte)
   - Solde disponible : 12 000€
   
3. **Semaine 3** : Le locataire verse **8 000€** (acompte)
   - Solde disponible : 20 000€
   
4. **À l'échéance du loyer** (ex : 1er du mois) :
   - Le système génère le loyer de 20 000€
   - 💰 **Utilisation automatique** des 20 000€ d'acomptes
   - ✅ **Loyer soldé automatiquement !**
   - Le locataire n'a rien à faire

---

## ⚙️ Configuration

### 1. Activer les paiements anticipés

**Accès** : `/admin/parametres/paiements`

**Paramètres** :
- ✅ **Autoriser les paiements anticipés** : Active/désactive le système
- 💵 **Montant minimum** : Montant minimum pour un acompte (ex: 50€)

### 2. Paramètres par défaut

```php
// Valeurs par défaut
allow_advance_payments = true (activé)
minimum_advance_amount = 50€
```

---

## 🚀 Utilisation

### Pour les administrateurs

#### 1. Créer un acompte

**URL** : `/acomptes/nouveau`

**Formulaire** :
- Bail concerné
- Montant de l'acompte
- Mode de paiement (Virement, Chèque, Espèces, CB)
- Référence (optionnel)
- Notes (optionnel)

**Traitement automatique** :
1. L'acompte est enregistré
2. Une écriture comptable est créée (**Crédit**)
3. Le système applique automatiquement l'acompte aux paiements en attente
4. Si un loyer est totalement couvert, il est marqué "Payé" automatiquement

#### 2. Consulter les acomptes

**URL** : `/acomptes`

**Affichage** :
- Liste de tous les acomptes
- Statut : Disponible, Utilisé partiellement, Utilisé, Remboursé
- Solde restant
- Statistiques globales

#### 3. Détails d'un acompte

**URL** : `/acomptes/{id}`

**Informations** :
- Montant total versé
- Montant utilisé
- Solde restant
- Pourcentage d'utilisation
- Historique d'utilisation
- Actions disponibles :
  - 💸 Rembourser le solde
  - 🔄 Transférer vers un autre bail

---

### Pour le système

#### Génération automatique de loyers

**Commande** : `php bin/console app:generate-rents`

**Ou via l'interface** : `/mes-paiements` → "Générer loyers"

**Processus** :
1. Génère les loyers du mois
2. Pour chaque loyer créé :
   - Vérifie s'il y a des acomptes disponibles
   - **Applique automatiquement** les acomptes (FIFO: First In First Out)
   - Si le loyer est entièrement couvert : marque comme "Payé"
   - Si partiellement couvert : réduit le montant à payer

**Exemple de message** :
```
✅ 15 loyers ont été générés pour 01/2025
💰 8 loyer(s) ont été payés automatiquement avec les acomptes disponibles !
```

---

## 🔄 Flux de traitement

### Schéma complet

```
1. CRÉATION D'ACOMPTE
   └─> Enregistrement dans AdvancePayment
   └─> Écriture comptable (Crédit)
   └─> Application automatique aux paiements en attente

2. GÉNÉRATION DE LOYER
   └─> Création du Payment (statut: En attente)
   └─> Recherche des acomptes disponibles (FIFO)
   └─> Pour chaque acompte:
       ├─> Utilise le solde disponible
       ├─> Met à jour le solde de l'acompte
       ├─> Ajoute une note au paiement
       └─> Écriture comptable (Débit)
   └─> Si montant couvert à 100%:
       └─> Marque le paiement comme "Payé"

3. STATUTS DES ACOMPTES
   ├─> Disponible (solde = montant)
   ├─> Utilisé partiellement (0 < solde < montant)
   ├─> Utilisé (solde = 0)
   └─> Remboursé (cas spécial)
```

---

## 💾 Structure de données

### Entité AdvancePayment

```php
class AdvancePayment
{
    private int $id;
    private Lease $lease;                    // Bail concerné
    private string $amount;                   // Montant total versé
    private string $remainingBalance;         // Solde restant
    private DateTime $paidDate;              // Date du versement
    private string $paymentMethod;           // Mode de paiement
    private ?string $reference;              // Référence
    private ?string $notes;                  // Notes
    private string $status;                  // Statut
    
    // Méthodes clés
    public function useBalance(float $amount): float;  // Utilise une partie du solde
    public function hasAvailableBalance(): bool;        // Vérifie le solde
    public function getUsedAmount(): float;             // Montant déjà utilisé
    public function getUsedPercentage(): float;         // % utilisé
}
```

### Statuts possibles

| Statut | Description | Condition |
|--------|-------------|-----------|
| **Disponible** | Acompte non utilisé | `solde == montant` |
| **Utilisé partiellement** | Acompte partiellement utilisé | `0 < solde < montant` |
| **Utilisé** | Acompte entièrement utilisé | `solde == 0` |
| **Remboursé** | Acompte remboursé au locataire | Action manuelle |

---

## 📊 Comptabilité

### 1. Réception d'un acompte

**Type** : Crédit  
**Catégorie** : "Acomptes reçus"  
**Description** : "Acompte reçu - [Nom du locataire] - Bail #[ID]"  
**Référence** : "ACOMPTE-[ID]"

### 2. Utilisation d'un acompte

**Type** : Débit  
**Catégorie** : "Utilisation acomptes"  
**Description** : "Utilisation acompte #[ID] pour paiement #[ID] - [Nom]"  
**Référence** : "USE-ACOMPTE-[ID]-[PAYMENT_ID]"

### 3. Remboursement d'un acompte

**Type** : Débit  
**Catégorie** : "Remboursement acomptes"  
**Description** : "Remboursement acompte #[ID] - [Nom] - Raison: [...]"  
**Référence** : "REFUND-ACOMPTE-[ID]"

---

## 🔒 Règles de gestion

### Validation

1. **Montant minimum** : Vérifié selon `minimum_advance_amount`
2. **Bail actif** : L'acompte ne peut être créé que pour un bail actif
3. **Mode de paiement** : Obligatoire

### Utilisation (FIFO)

1. **Ordre d'utilisation** : Premier acompte reçu = Premier utilisé
2. **Utilisation automatique** : Lors de la génération de loyers
3. **Utilisation partielle** : Si le solde ne couvre pas tout le loyer

### Traçabilité

1. **Notes automatiques** : Chaque utilisation est tracée
2. **Historique comptable** : Toutes les opérations sont enregistrées
3. **Timestamps** : `createdAt`, `updatedAt` sur tous les acomptes

---

## 🎨 Interface utilisateur

### Vue principale - Liste des acomptes

**URL** : `/acomptes`

**Éléments affichés** :
- Tableau avec tous les acomptes
- Filtres par statut
- Statistiques globales :
  - Solde total disponible
  - Montant total utilisé
  - Nombre d'acomptes actifs

### Détail d'un acompte

**URL** : `/acomptes/{id}`

**Sections** :
- **Informations générales** : Montant, date, mode de paiement
- **Solde** : Montant total, utilisé, restant (avec barre de progression)
- **Locataire** : Nom, bail concerné
- **Historique** : Liste des utilisations
- **Actions** : Rembourser, Transférer

### Acomptes par bail

**URL** : `/acomptes/bail/{id}`

**Affichage** :
- Tous les acomptes du bail
- Solde total disponible
- Bouton "Appliquer aux paiements en attente"

---

## 🛠️ API et Services

### AdvancePaymentService

```php
// Créer un acompte
$advance = $service->createAdvancePayment(
    $lease, 
    $amount, 
    $paymentMethod, 
    $reference, 
    $notes
);

// Récupérer le solde disponible
$balance = $service->getAvailableBalance($lease);

// Appliquer aux paiements
$amountUsed = $service->applyAdvanceToPayment($payment);

// Appliquer à tous les paiements en attente
$results = $service->applyAdvanceToAllPendingPayments($lease);

// Rembourser un acompte
$service->refundAdvancePayment($advance, $reason);

// Transférer un acompte
$newAdvance = $service->transferAdvance($advance, $newLease, $reason);

// Rapport détaillé
$report = $service->getAdvancePaymentReport($lease);
```

---

## 📈 Statistiques

### Globales

```php
$stats = $repository->getStatistics();
// Retourne:
[
    'total_available' => 125000.00,  // Solde total disponible
    'total_used' => 85000.00,        // Montant total utilisé
    'active_count' => 15,            // Nombre d'acomptes actifs
    'total_amount' => 210000.00,     // Montant total (disponible + utilisé)
]
```

### Par bail

```php
$report = $service->getAdvancePaymentReport($lease);
// Retourne:
[
    'advances' => [...],              // Liste des acomptes
    'total_paid' => 60000.00,        // Total versé
    'total_used' => 40000.00,        // Total utilisé
    'total_available' => 20000.00,   // Total disponible
    'count' => 3,                     // Nombre d'acomptes
]
```

---

## ⚡ Fonctionnalités avancées

### 1. Transfert d'acompte

**Cas d'usage** : Le locataire change de logement

**Action** : `/acomptes/{id}/transferer`

**Processus** :
1. Crée un nouvel acompte pour le nouveau bail
2. Avec le solde restant de l'ancien acompte
3. Marque l'ancien acompte comme "Utilisé"
4. Ajoute des notes expliquant le transfert

### 2. Remboursement

**Cas d'usage** : Fin de bail avec solde restant

**Action** : `/acomptes/{id}/rembourser`

**Processus** :
1. Marque l'acompte comme "Remboursé"
2. Met le solde à zéro
3. Crée une écriture comptable de remboursement
4. Enregistre la raison du remboursement

### 3. Application manuelle

**Cas d'usage** : Application immédiate d'un acompte

**Action** : `/acomptes/bail/{id}/appliquer`

**Processus** :
1. Récupère tous les paiements en attente du bail
2. Applique les acomptes disponibles
3. Affiche un résumé des paiements traités

---

## 🧪 Tests recommandés

### Test 1 : Création et utilisation simple

1. Créer un bail avec loyer 1000€
2. Créer un acompte de 1000€
3. Générer le loyer du mois
4. **Vérifier** : Loyer marqué "Payé" automatiquement
5. **Vérifier** : Acompte avec statut "Utilisé"

### Test 2 : Utilisation partielle

1. Créer un bail avec loyer 2000€
2. Créer un acompte de 1500€
3. Générer le loyer du mois
4. **Vérifier** : Loyer toujours "En attente" (reste 500€ à payer)
5. **Vérifier** : Acompte avec statut "Utilisé"
6. **Vérifier** : Notes du paiement mentionnent l'acompte utilisé

### Test 3 : Plusieurs acomptes (FIFO)

1. Créer un bail avec loyer 3000€
2. Créer acompte 1 : 1000€ (date: 01/01)
3. Créer acompte 2 : 1500€ (date: 05/01)
4. Créer acompte 3 : 800€ (date: 10/01)
5. Générer le loyer du mois
6. **Vérifier** : Acompte 1 utilisé en premier (1000€)
7. **Vérifier** : Acompte 2 utilisé ensuite (1500€)
8. **Vérifier** : Acompte 3 utilisé partiellement (500€)
9. **Vérifier** : Loyer marqué "Payé"
10. **Vérifier** : Acompte 3 a un solde de 300€

### Test 4 : Comptabilité

1. Créer un acompte de 5000€
2. **Vérifier** : Écriture comptable "Acomptes reçus" (Crédit, 5000€)
3. L'utiliser pour un loyer de 3000€
4. **Vérifier** : Écriture comptable "Utilisation acomptes" (Débit, 3000€)
5. Rembourser le solde de 2000€
6. **Vérifier** : Écriture comptable "Remboursement acomptes" (Débit, 2000€)

---

## 📱 Notifications (à implémenter)

**Suggestions de notifications** :

1. **Acompte reçu** :
   - Email au locataire : "Votre acompte de X€ a été enregistré"
   - SMS : "Acompte reçu : X€. Solde disponible : Y€"

2. **Acompte utilisé** :
   - Email : "Votre loyer de X€ a été payé avec votre acompte"
   - Notification : "Solde d'acompte restant : Y€"

3. **Solde faible** :
   - Email : "Votre solde d'acompte est de X€ (loyer : Y€)"
   - Suggestion : "Pensez à recharger votre solde"

---

## 🎯 Avantages du système

### Pour les locataires

✅ **Flexibilité** : Payer en plusieurs fois selon leurs capacités  
✅ **Automatisation** : Plus besoin de penser au paiement mensuel  
✅ **Tranquillité** : Pas de stress de retard de paiement  
✅ **Transparence** : Suivi en temps réel du solde disponible  

### Pour les gestionnaires

✅ **Réduction des impayés** : Paiements fractionnés plus faciles  
✅ **Automatisation** : Application automatique des acomptes  
✅ **Traçabilité** : Historique complet en comptabilité  
✅ **Professionnalisme** : Service moderne et pratique  

---

## 🔐 Sécurité

1. **Validation des montants** : Montant minimum configuré
2. **Permissions** : Seuls les admins peuvent créer/gérer les acomptes
3. **Intégrité comptable** : Toutes les opérations sont enregistrées
4. **Traçabilité** : Impossible de modifier un acompte utilisé
5. **Audit trail** : Timestamps sur toutes les opérations

---

## 🚀 Migration depuis l'existant

Si vous avez déjà des paiements en cours :

```bash
# 1. Créer la table AdvancePayment
php bin/console doctrine:migrations:migrate

# 2. (Optionnel) Importer des acomptes existants
# Créer un script custom pour importer depuis votre ancien système

# 3. Activer le système
# Aller dans /admin/parametres/paiements
# Cocher "Autoriser les paiements anticipés"

# 4. Tester
# Créer un acompte test
# Générer un loyer
# Vérifier l'application automatique
```

---

## 📞 Support

**En cas de problème** :

1. Vérifier que `allow_advance_payments` est activé
2. Vérifier les logs : `var/log/dev.log`
3. Vérifier les écritures comptables
4. Consulter le rapport d'acomptes : `/acomptes/bail/{id}`

---

**✅ Système de paiements anticipés entièrement fonctionnel !**

📅 Date : 12 Octobre 2025  
🎉 Statut : Production-ready  
💰 Impact : Révolutionnaire pour la gestion des loyers élevés  

---

**Le système est maintenant prêt à être utilisé en production ! 🚀**
