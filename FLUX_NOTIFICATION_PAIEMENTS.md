# 🔔 Flux de Notification des Paiements CinetPay

## ✅ **OUI, les paiements sont automatiquement notifiés !**

Quand un paiement se fait via CinetPay, le système traite automatiquement :

1. ✅ **Notification dans la table `payment`**
2. ✅ **Écritures comptables automatiques**
3. ✅ **Application des acomptes**
4. ✅ **Logs détaillés**

---

## 🔄 **Flux complet de notification**

### **1. Webhook CinetPay** → **`/paiement-en-ligne/notification`**

**Route** : `app_online_payment_notify`
**Méthode** : `POST`
**Sécurité** : Vérification HMAC avec la clé secrète

### **2. Vérification de sécurité** 🔐

```php
// Vérification HMAC
$secretKey = $settingsService->get('cinetpay_secret_key');
$generatedToken = hash_hmac('sha256', $concatenated, $secretKey);
if ($generatedToken !== $receivedToken) {
    return new Response('Signature HMAC invalide', 403);
}
```

### **3. Double vérification avec CinetPay** 🔍

```php
// Vérifier le statut auprès de CinetPay
$status = $cinetpay->checkTransactionStatus($transactionId);
$isSuccess = ($status && $status['code'] == '00' && $status['message'] == 'SUCCES')
          || (strtoupper($data['cpm_error_message']) === 'SUCCES');
```

---

## 💰 **Traitement des paiements de loyer**

### **Quand le paiement est réussi** ✅

```php
if ($onlinePayment->getPaymentType() === 'rent' && $onlinePayment->getPayment()) {
    // 1. Marquer le paiement comme payé
    $payment = $onlinePayment->getPayment();
    $payment->markAsPaid(
        new \DateTime($data['cpm_trans_date']),
        'Paiement en ligne - ' . $paymentMethod,
        $transactionId
    );

    // 2. Ajouter les détails dans les notes
    $payment->setNotes(sprintf(
        "Paiement en ligne via CinetPay\nMéthode: %s\nTéléphone: %s\nDate: %s",
        $paymentMethod,
        $data['cel_phone_num'] ?? 'N/A',
        $data['cpm_trans_date']
    ));

    // 3. Enregistrer en comptabilité
    $accountingService->createEntryFromPayment($payment);
}
```

### **Résultat dans la table `payment`** ✅

- ✅ **`status`** : `'Payé'`
- ✅ **`paid_date`** : Date du paiement
- ✅ **`payment_method`** : `'Paiement en ligne - MOBILE_MONEY'`
- ✅ **`reference`** : ID de transaction CinetPay
- ✅ **`notes`** : Détails du paiement (méthode, téléphone, date)

---

## 💳 **Traitement des acomptes**

### **Quand un acompte est payé** ✅

```php
elseif ($onlinePayment->getPaymentType() === 'advance') {
    // 1. Créer l'acompte
    $advance = $advanceService->createAdvancePayment(
        $onlinePayment->getLease(),
        (float) $data['cpm_amount'],
        'Paiement en ligne - ' . $paymentMethod,
        $transactionId,
        $notes
    );

    // 2. Enregistrer en comptabilité
    $accountingService->recordAdvancePayment($advance);

    // 3. Appliquer automatiquement aux paiements en attente
    $results = $advanceService->applyAdvanceToAllPendingPayments($onlinePayment->getLease());
}
```

### **Résultat** ✅

- ✅ **Table `advance_payment`** : Nouvel acompte créé
- ✅ **Table `payment`** : Paiements en attente automatiquement soldés
- ✅ **Comptabilité** : Écritures pour l'acompte et son utilisation

---

## 📊 **Écritures comptables automatiques**

### **Pour un paiement de loyer** 💰

```php
$accountingService->createEntryFromPayment($payment);
```

**Crée** :
- ✅ **Débit** : `Banque` (Montant du loyer)
- ✅ **Crédit** : `Recettes de loyers` (Montant du loyer)

### **Pour un acompte** 💳

```php
$accountingService->recordAdvancePayment($advance);
```

**Crée** :
- ✅ **Débit** : `Banque` (Montant de l'acompte)
- ✅ **Crédit** : `Acomptes reçus` (Montant de l'acompte)

### **Quand un acompte est utilisé** 🔄

```php
$accountingService->recordAdvanceUsage($advancePayment, $payment, $amountUsed);
```

**Crée** :
- ✅ **Débit** : `Acomptes reçus` (Montant utilisé)
- ✅ **Crédit** : `Recettes de loyers` (Montant utilisé)

---

## 📝 **Logs détaillés**

### **Fichier de log** : `var/log/cinetpay_notifications.log`

**Contient** :
- ✅ **Données reçues** : Tous les paramètres CinetPay
- ✅ **Vérifications** : HMAC, statut, double vérification
- ✅ **Actions effectuées** : Paiements marqués, acomptes créés
- ✅ **Erreurs** : Détails des échecs

### **Exemple de log** 📋

```
2025-10-12 14:30:15 - POST DATA: Array ( [cpm_site_id] => 105899583 [cpm_trans_id] => RENT-20-abc123 ... )
2025-10-12 14:30:15 - ✅ Loyer payé: Payment #20
2025-10-12 14:30:15 - ✅ SUCCESS: Transaction RENT-20-abc123 traitée
```

---

## 🎯 **Résumé des notifications**

### **Table `payment`** ✅
- ✅ Statut mis à jour : `'En attente'` → `'Payé'`
- ✅ Date de paiement enregistrée
- ✅ Méthode de paiement documentée
- ✅ Référence CinetPay stockée
- ✅ Notes détaillées ajoutées

### **Table `accounting_entry`** ✅
- ✅ Écritures automatiques créées
- ✅ Débits et crédits équilibrés
- ✅ Référence au paiement liée
- ✅ Date et description complètes

### **Table `advance_payment`** ✅ (si acompte)
- ✅ Nouvel acompte créé
- ✅ Paiements en attente automatiquement soldés
- ✅ Écritures comptables pour l'acompte et son utilisation

### **Table `online_payment`** ✅
- ✅ Statut mis à jour : `'pending'` → `'completed'`
- ✅ Données de notification stockées
- ✅ Réponse CinetPay enregistrée

---

## 🚀 **Avantages du système**

### **Automatisation complète** 🤖
- ✅ **Aucune intervention manuelle** requise
- ✅ **Synchronisation en temps réel** avec CinetPay
- ✅ **Sécurité renforcée** avec vérification HMAC

### **Traçabilité totale** 📊
- ✅ **Logs détaillés** de tous les paiements
- ✅ **Historique complet** dans les tables
- ✅ **Écritures comptables** automatiques

### **Gestion des acomptes** 💳
- ✅ **Application automatique** aux loyers en attente
- ✅ **Écritures comptables** pour l'acompte et son utilisation
- ✅ **Solde disponible** mis à jour en temps réel

---

## 🎊 **Conclusion**

**OUI, le système de notification est complet et automatique !**

### **Chaque paiement CinetPay déclenche** :

1. ✅ **Notification webhook** → Vérification sécurité
2. ✅ **Double vérification** → Statut CinetPay
3. ✅ **Mise à jour payment** → Statut 'Payé'
4. ✅ **Écritures comptables** → Débits/Crédits
5. ✅ **Logs détaillés** → Traçabilité complète
6. ✅ **Application acomptes** → Si applicable

**Le système est entièrement automatisé et sécurisé ! 💳✨**

---

**📅 Date** : 12 Octobre 2025  
**✅ Statut** : Système de notification fonctionnel  
**🎯 Impact** : Automatisation complète des paiements
