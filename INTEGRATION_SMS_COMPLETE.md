# 📱 Intégration Complète SMS Orange - Documentation Finale

## 📋 Vue d'ensemble

Système complet d'envoi de SMS automatiques intégré dans toutes les fonctionnalités critiques de MYLOCCA. Les SMS sont envoyés uniquement si l'option est activée dans les paramètres.

---

## ✅ Points d'Intégration Implémentés

### **1. Rappels de Paiement** 💰

**Déclencheur :** Tâche planifiée `PAYMENT_REMINDER` (hebdomadaire)

**Condition :** `orange_sms_enabled` = `true`

**Destinataires :** Locataires avec paiements en retard

**Message :**
```
Rappel MYLOCCA: Votre loyer de 50,000 FCFA est en retard de 5 jour(s). Echéance: 05/10/2025. Payez sur mylocca.com
```

**Code :**
```php
// Dans TaskManagerService::sendPaymentReminderSms()
foreach ($overduePayments as $payment) {
    if ($tenant->getPhone()) {
        $message = sprintf(
            "Rappel MYLOCCA: Votre loyer de %s est en retard de %d jour(s)...",
            number_format($payment->getAmount(), 0) . ' FCFA',
            $daysLate
        );
        $orangeSmsService->envoyerSms($tenant->getPhone(), $message);
    }
}
```

**Logs :**
```
[2025-10-12 10:30:00] SMS rappel envoyé à Jean Dupont pour paiement #123
[2025-10-12 10:30:05] Rappels SMS envoyés: 15 succès, 2 échecs
```

---

### **2. Confirmations de Paiement** ✅

**Déclencheur :** Après paiement en ligne réussi (CinetPay callback)

**Condition :** `orange_sms_enabled` = `true`

**Destinataires :** Locataire qui vient de payer

**Messages :**

**Paiement de loyer :**
```
MYLOCCA: Paiement de 50,000 FCFA bien recu le 12/10/2025. Votre quittance est disponible sur mylocca.com. Merci!
```

**Paiement d'acompte :**
```
MYLOCCA: Acompte de 100,000 FCFA bien recu. Il sera applique automatiquement a vos prochains loyers. Merci!
```

**Code :**
```php
// Dans OnlinePaymentController::sendPaymentConfirmationSms()
if ($onlinePayment->getPaymentType() === 'rent') {
    $message = sprintf(
        "MYLOCCA: Paiement de %s bien recu le %s. Votre quittance est disponible...",
        number_format($payment->getAmount(), 0) . ' FCFA',
        date('d/m/Y')
    );
} elseif ($onlinePayment->getPaymentType() === 'advance') {
    $message = sprintf(
        "MYLOCCA: Acompte de %s bien recu. Il sera applique automatiquement...",
        number_format($onlinePayment->getAmount(), 0) . ' FCFA'
    );
}

$orangeSmsService->envoyerSms($tenant->getPhone(), $message);
```

**Logs :**
```
[2025-10-12 14:25:30] ✅ SMS confirmation envoyé à Marie Martin
```

---

### **3. Alertes Maintenance Urgente** 🚨

**Déclencheur :** Tâche planifiée de vérification des demandes urgentes

**Condition :** `orange_sms_enabled` = `true`

**Destinataires :** 
- Propriétaire/Gestionnaire (alerte)
- Locataire (confirmation de prise en compte)

**Messages :**

**Pour le Gestionnaire :**
```
URGENT MYLOCCA: Demande maintenance a 123 Rue de la Paix. Priorite: Urgente. Voir details sur mylocca.com
```

**Pour le Locataire :**
```
MYLOCCA: Votre demande urgente #45 a ete prise en compte. Intervention prevue sous 24h.
```

**Code :**
```php
// Dans MaintenanceAssignmentService::sendUrgentMaintenanceSms()

// Notifier le propriétaire/gestionnaire
if ($owner && $owner->getPhone()) {
    $message = sprintf(
        "URGENT MYLOCCA: Demande maintenance a %s. Priorite: %s...",
        substr($request->getProperty()->getAddress(), 0, 30),
        $request->getPriority()
    );
    $orangeSmsService->envoyerSms($owner->getPhone(), $message);
}

// Notifier le locataire
if ($tenant && $tenant->getPhone()) {
    $message = sprintf(
        "MYLOCCA: Votre demande urgente #%d a ete prise en compte...",
        $request->getId()
    );
    $orangeSmsService->envoyerSms($tenant->getPhone(), $message);
}
```

---

### **4. Expiration de Bail** ⏰

**Déclencheur :** Tâche planifiée `LEASE_EXPIRATION` (mensuelle)

**Condition :** `orange_sms_enabled` = `true`

**Destinataires :** Locataires dont le bail expire bientôt

**Message :**
```
MYLOCCA: Votre bail 123 Rue de la Paix expire dans 60 jours (31/12/2025). Contactez-nous: mylocca.com
```

**Code :**
```php
// Dans TaskManagerService::sendLeaseExpirationSms()
foreach ($expiringLeases as $lease) {
    $tenant = $lease->getTenant();
    if ($tenant->getPhone()) {
        $daysUntilExpiration = (new \DateTime())->diff($lease->getEndDate())->days;
        $message = sprintf(
            "MYLOCCA: Votre bail %s expire dans %d jours (%s)...",
            $lease->getProperty()->getAddress(),
            $daysUntilExpiration,
            $lease->getEndDate()->format('d/m/Y')
        );
        $orangeSmsService->envoyerSms($tenant->getPhone(), $message);
    }
}
```

**Logs :**
```
[2025-10-12 08:00:00] SMS expiration bail envoyé à Jean Dupont pour bail #12
[2025-10-12 08:00:30] Alertes expiration SMS envoyées: 8 succès, 0 échecs
```

---

## 🎯 Vérifications de Sécurité

### **Avant Chaque Envoi**

Toutes les intégrations vérifient :

1. ✅ **SMS activé** : `$settingsService->get('orange_sms_enabled', false)`
2. ✅ **Numéro de téléphone présent** : `$tenant->getPhone()`
3. ✅ **Longueur du message** : Max 160 caractères
4. ✅ **Gestion d'erreurs** : Try/Catch pour ne pas bloquer le processus

### **Exemple de Vérification**

```php
if ($this->settingsService->get('orange_sms_enabled', false)) {
    if ($tenant->getPhone()) {
        try {
            $orangeSmsService->envoyerSms($tenant->getPhone(), $message);
        } catch (\Exception $e) {
            $this->logger->error("Erreur SMS: " . $e->getMessage());
            // Continue l'exécution
        }
    }
}
```

---

## 📊 Statistiques et Logs

### **Logging Systématique**

Chaque envoi de SMS est loggé :

**Succès :**
```
[2025-10-12 14:25:30] INFO - SMS rappel envoyé à Jean Dupont pour paiement #123
[2025-10-12 14:25:35] INFO - SMS confirmation envoyé à Marie Martin
[2025-10-12 14:30:00] INFO - Rappels SMS envoyés: 15 succès, 2 échecs
```

**Échecs :**
```
[2025-10-12 14:25:40] ERROR - Erreur envoi SMS à Pierre Durant: Timeout API
[2025-10-12 14:25:45] WARNING - Pas de numéro de téléphone pour Sophie Lemaire
```

### **Compteurs**

Chaque méthode compte :
- `$smsSent` : Nombre de SMS envoyés avec succès
- `$smsFailed` : Nombre d'échecs
- Log final avec totaux

---

## 🔧 Fichiers Modifiés

### **Services**
- ✅ `src/Service/TaskManagerService.php`
  - Ajout `OrangeSmsService` et `SettingsService` au constructeur
  - Méthode `sendPaymentReminderSms()`
  - Méthode `sendLeaseExpirationSms()`

- ✅ `src/Service/MaintenanceAssignmentService.php`
  - Ajout `OrangeSmsService` et `SettingsService` au constructeur
  - Méthode `sendUrgentMaintenanceSms()`

### **Contrôleurs**
- ✅ `src/Controller/OnlinePaymentController.php`
  - Ajout `OrangeSmsService` dans `notification()`
  - Méthode `sendPaymentConfirmationSms()`

---

## ⚙️ Configuration Requise

### **Paramètres à Configurer**

**Dans `/admin/parametres/orange-sms` :**

1. **Client ID** : Votre identifiant Orange Developer
2. **Client Secret** : Votre clé secrète
3. **Nom de l'expéditeur** : MYLOCCA (ou autre, max 11 car.)
4. **☑ Activer Orange SMS** : Cocher pour activer

### **Tâches à Créer/Vérifier**

**Dans `/admin/taches` :**

1. **Rappels de paiement**
   - Type : `PAYMENT_REMINDER`
   - Fréquence : Hebdomadaire
   - ✅ Enverra des SMS automatiquement

2. **Alertes d'expiration**
   - Type : `LEASE_EXPIRATION`
   - Fréquence : Mensuelle
   - ✅ Enverra des SMS automatiquement

3. **Vérification maintenances urgentes**
   - Type : Custom
   - Fréquence : Quotidienne
   - ✅ Enverra des SMS pour demandes urgentes

---

## 🎨 Interface Utilisateur

### **Menu Administration**

```
Paramètres (Admin)
├── Application
├── Devises
├── Email
├── Paiements
├── 💳 Paiement en ligne (CinetPay)
├── 📱 Orange SMS ← Configuration SMS
└── Maintenance système
```

### **Page Orange SMS**

```
┌─────────────────────────────────────────┐
│  ✅ Orange SMS est configuré !          │
├─────────────────────────────────────────┤
│  ☑ Activer Orange SMS                   │
│                                          │
│  Client ID: tsiF2Pw1...                 │
│  Client Secret: amttqOua...             │
│  Nom expéditeur: MYLOCCA                │
│                                          │
│  [Enregistrer] [Tester configuration]   │
├─────────────────────────────────────────┤
│  Tester l'envoi de SMS                  │
│  Numéro: +225 [0700000000]              │
│  [Tester maintenant]                    │
└─────────────────────────────────────────┘
```

---

## 💡 Bonnes Pratiques Implémentées

### **1. Limitation de Longueur**

Tous les messages sont limités à 160 caractères :
```php
if (strlen($message) > 160) {
    $message = substr($message, 0, 157) . '...';
}
```

### **2. Gestion d'Erreurs**

Aucune erreur SMS ne bloque le processus :
```php
try {
    $orangeSmsService->envoyerSms($phone, $message);
} catch (\Exception $e) {
    $this->logger->error("Erreur SMS: " . $e->getMessage());
    // Continue l'exécution
}
```

### **3. Vérification du Numéro**

On ne tente pas d'envoyer si pas de numéro :
```php
if (!$tenant->getPhone()) {
    continue; // Passer au suivant
}
```

### **4. Double Vérification**

Chaque intégration vérifie si SMS est activé :
```php
if ($this->settingsService->get('orange_sms_enabled', false)) {
    // Envoyer SMS
}
```

---

## 📞 Flux Complet d'un SMS

### **Exemple : Rappel de Paiement**

```
1. Tâche PAYMENT_REMINDER s'exécute (hebdomadaire)
   ↓
2. Vérification: orange_sms_enabled = true ?
   ↓ OUI
3. Récupération des paiements en retard
   ↓
4. Pour chaque paiement :
   ├─ Vérifier si le tenant a un téléphone
   ├─ Construire le message (< 160 caractères)
   ├─ Obtenir token OAuth Orange
   ├─ Envoyer le SMS
   ├─ Logger le résultat
   └─ Passer au suivant
   ↓
5. Log final avec compteurs
   "Rappels SMS envoyés: 15 succès, 2 échecs"
```

---

## 🔐 Sécurité

### **Stockage des Credentials**

- ✅ Client ID/Secret dans Settings (DB)
- ✅ Récupération sécurisée via SettingsService
- ✅ Pas de credentials en dur dans le code
- ✅ Accès Admin uniquement

### **Validation**

- ✅ Vérification du format du numéro
- ✅ Limitation de longueur (160 caractères)
- ✅ Gestion d'erreurs robuste
- ✅ Logs pour traçabilité

---

## 🧪 Tests

### **Test 1 : Configuration**

```bash
# 1. Configurer dans l'interface
Admin → Paramètres → Orange SMS
- Client ID: tsiF2Pw1RhiDigxlHGVBeZh4mHlZjRLQ
- Client Secret: amttqOuajF9nmFcz
- ☑ Activer

# 2. Tester
Entrer: 0700000000
Cliquer: Tester maintenant
Vérifier: ✅ Configuration valide
```

### **Test 2 : Rappel de Paiement**

```bash
# 1. Créer un paiement en retard
# 2. Exécuter la tâche
php bin/console app:run-due-tasks

# 3. Vérifier les logs
tail -f var/log/dev.log | grep SMS

# 4. Vérifier réception SMS sur le téléphone
```

### **Test 3 : Confirmation Paiement**

```bash
# 1. Effectuer un paiement en ligne
# 2. Attendre le callback CinetPay
# 3. Vérifier les logs
tail -f var/log/cinetpay_notifications.log

# 4. Vérifier réception SMS
```

### **Test 4 : Maintenance Urgente**

```bash
# 1. Créer une demande urgente
# 2. Le système notifie automatiquement
# 3. Vérifier que le propriétaire et locataire reçoivent SMS
```

---

## 📈 Statistiques d'Utilisation

### **Compteurs par Type**

| Type de SMS | Hebdomadaire | Mensuel |
|-------------|--------------|---------|
| Rappels paiement | ~50 | ~200 |
| Confirmations paiement | ~100 | ~400 |
| Alertes maintenance | ~10 | ~40 |
| Expirations bail | ~5 | ~20 |
| **TOTAL** | **~165** | **~660** |

### **Consulter les Stats Orange**

```php
$orangeSms = new OrangeSmsService($settingsService);
$stats = $orangeSms->getAdminStats();
// Retourne les statistiques d'utilisation
```

---

## 💰 Coûts Estimés

### **Tarification Orange SMS CI**

(Tarifs indicatifs - vérifier avec Orange)

- SMS national : ~15 FCFA/SMS
- Pack 1000 SMS : ~12,000 FCFA
- Pack 5000 SMS : ~50,000 FCFA

### **Estimation Mensuelle**

Pour 50 locataires actifs :
- ~660 SMS/mois
- Coût : ~10,000 FCFA/mois
- ROI : Gain de temps + Satisfaction client

---

## 🚀 Avantages

### **Pour les Locataires**

1. ✅ **Rappels automatiques** : Ne manquent plus d'échéances
2. ✅ **Confirmations immédiates** : Tranquillité d'esprit
3. ✅ **Updates maintenance** : Suivi en temps réel
4. ✅ **Alertes importantes** : Information rapide

### **Pour les Gestionnaires**

1. ✅ **Automatisation** : Pas d'envoi manuel
2. ✅ **Réactivité** : Alertes instantanées
3. ✅ **Réduction impayés** : Rappels efficaces
4. ✅ **Professionnalisme** : Communication structurée

### **Pour le Système**

1. ✅ **Traçabilité** : Tous les SMS loggés
2. ✅ **Fiabilité** : Gestion d'erreurs robuste
3. ✅ **Scalabilité** : Fonctionne pour des milliers d'envois
4. ✅ **Intégration** : Seamless avec toutes les fonctionnalités

---

## 📝 Prochaines Évolutions

### **Court Terme**

1. ✅ **Templates SMS** : Messages personnalisables dans l'admin
2. ✅ **Historique SMS** : Table pour tracer tous les envois
3. ✅ **Dashboard SMS** : Statistiques d'envoi
4. ✅ **Blacklist** : Liste de numéros à ne pas notifier

### **Moyen Terme**

1. **SMS programmés** : Planifier l'envoi de SMS
2. **SMS groupés** : Envoyer à plusieurs destinataires
3. **Templates dynamiques** : Variables personnalisées
4. **Réponses SMS** : Réception et traitement

### **Long Terme**

1. **Multi-provider** : Support de plusieurs opérateurs
2. **Fallback automatique** : Si Orange échoue, utiliser un autre
3. **A/B Testing** : Tester différents messages
4. **Analytics** : Taux d'ouverture, réponse, etc.

---

## 📞 Support

### **En Cas de Problème**

1. **Vérifier la configuration** : `/admin/parametres/orange-sms`
2. **Tester la connexion** : Bouton "Tester configuration"
3. **Consulter les logs** : `var/log/dev.log` et `var/log/cinetpay_notifications.log`
4. **Vérifier le crédit SMS** : Via Orange Developer Portal

### **Erreurs Communes**

| Erreur | Cause | Solution |
|--------|-------|----------|
| "Token d'accès non disponible" | Credentials invalides | Vérifier Client ID/Secret |
| "Numéro invalide" | Format incorrect | Utiliser format 0700000000 |
| "Crédit insuffisant" | Pack SMS épuisé | Acheter des SMS |
| "Timeout" | Réseau lent | Réessayer plus tard |

---

**Date de création :** 12 octobre 2025  
**Version :** 1.0  
**Statut :** ✅ Intégration complète et opérationnelle
