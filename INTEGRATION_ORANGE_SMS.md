# 📱 Intégration Orange SMS - Documentation

## 📋 Vue d'ensemble

Système complet d'envoi de SMS via l'API Orange SMS, permettant d'envoyer des notifications, rappels et alertes aux locataires et gestionnaires.

---

## 🎯 Fonctionnalités Implémentées

### **1. Service Orange SMS (`OrangeSmsService`)**

Service complet pour interagir avec l'API Orange SMS.

#### **Méthodes Principales**

##### **`envoyerSms(string $telephone, string $message): array`**
Méthode simplifiée pour envoyer un SMS rapidement.

**Paramètres :**
- `$telephone` : Numéro de téléphone (format: `0700000000`)
- `$message` : Contenu du SMS (max 160 caractères)

**Retour :**
```php
[
    'status' => 'success',
    'response' => [...] // Réponse complète de l'API
]
```

**Exemple d'utilisation :**
```php
$orangeSms = new OrangeSmsService($settingsService);
$result = $orangeSms->envoyerSms('0700000000', 'Votre loyer est dû le 5/11/2025');
```

##### **`sendSms(string $senderAddress, string $receiverAddress, string $message, string $senderName): array`**
Méthode avancée avec contrôle complet des paramètres.

**Paramètres :**
- `$senderAddress` : Adresse expéditeur (format: `tel:+225XXXXXXXX`)
- `$receiverAddress` : Adresse destinataire (format: `tel:+225XXXXXXXX`)
- `$message` : Contenu du SMS
- `$senderName` : Nom de l'expéditeur (max 11 caractères)

##### **`getTokenFromConsumerKey(): array`**
Récupère un token d'accès OAuth depuis Orange.

**Retour :**
```php
[
    'access_token' => 'eyJ...',
    'token_type' => 'Bearer',
    'expires_in' => 3600
]
```

##### **Méthodes Administratives**
- `getAdminStats()` : Statistiques d'utilisation
- `getAdminContracts()` : Nombre de SMS restants
- `getAdminPurchasedBundles()` : Historique des achats

---

## ⚙️ Configuration

### **Paramètres Système Requis**

| Paramètre | Description | Exemple |
|-----------|-------------|---------|
| `orange_sms_client_id` | Client ID Orange Developer | `tsiF2Pw1RhiDig...` |
| `orange_sms_client_secret` | Client Secret | `amttqOuajF9nmFcz` |
| `orange_sms_sender_name` | Nom de l'expéditeur | `MYLOCCA` |
| `orange_sms_enabled` | Activer/Désactiver SMS | `true`/`false` |

### **Page d'Administration**

**URL :** `/admin/parametres/orange-sms`

**Accès :** Menu Admin → Paramètres → 📱 Orange SMS

**Fonctionnalités :**
- ✅ Configuration des identifiants
- ✅ Activation/Désactivation du service
- ✅ Test de configuration
- ✅ Instructions pour obtenir les identifiants

---

## 🔐 Authentification OAuth

### **Flux d'Authentification**

```
1. Application demande un token
   ↓
   POST /oauth/v3/token
   Authorization: Basic base64(clientId:clientSecret)
   ↓
2. Orange répond avec access_token
   ↓
3. Token utilisé pour tous les appels SMS
   ↓
   Authorization: Bearer {access_token}
```

### **Exemple de Requête**

```php
$credentials = base64_encode($clientId . ':' . $clientSecret);

curl -X POST https://api.orange.com/oauth/v3/token \
  -H "Authorization: Basic $credentials" \
  -d "grant_type=client_credentials"
```

**Réponse :**
```json
{
  "access_token": "eyJhbGciOiJSUzI1NiIs...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

---

## 📨 Envoi de SMS

### **Format des Adresses**

**Expéditeur et Destinataire :**
```
tel:+225XXXXXXXXXX
```

**Exemples :**
- ✅ `tel:+2250700000000`
- ✅ `tel:+2250101010101`
- ❌ `0700000000` (format incorrect)
- ❌ `+225 07 00 00 00 00` (espaces non autorisés)

### **Structure de la Requête**

```json
{
  "outboundSMSMessageRequest": {
    "address": "tel:+2250700000000",
    "senderAddress": "tel:+2250700000000",
    "senderName": "MYLOCCA",
    "outboundSMSTextMessage": {
      "message": "Votre loyer de 50,000 FCFA est dû le 05/11/2025"
    }
  }
}
```

### **Exemple Complet**

```php
use App\Service\OrangeSmsService;

// Dans un contrôleur ou service
$orangeSms = new OrangeSmsService($settingsService);
$orangeSms->setVerifyPeerSSL(false); // Optionnel en développement

try {
    $result = $orangeSms->envoyerSms(
        '0700000000', 
        'Rappel: Votre loyer de 50,000 FCFA est dû demain'
    );
    
    if (isset($result['error'])) {
        // Gérer l'erreur
        echo "Erreur: " . $result['error'];
    } else {
        // SMS envoyé avec succès
        echo "SMS envoyé !";
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage();
}
```

---

## 🎯 Cas d'Usage

### **1. Rappel de Paiement**

```php
public function envoyerRappelPaiement(Payment $payment, OrangeSmsService $sms): void
{
    $tenant = $payment->getLease()->getTenant();
    $message = sprintf(
        "Rappel MYLOCCA: Votre loyer de %s est dû le %s. Payez en ligne sur mylocca.com",
        number_format($payment->getAmount(), 0, ',', ' ') . ' FCFA',
        $payment->getDueDate()->format('d/m/Y')
    );
    
    $sms->envoyerSms($tenant->getPhone(), $message);
}
```

### **2. Confirmation de Paiement**

```php
public function envoyerConfirmationPaiement(Payment $payment, OrangeSmsService $sms): void
{
    $tenant = $payment->getLease()->getTenant();
    $message = sprintf(
        "MYLOCCA: Paiement de %s bien reçu le %s. Merci !",
        number_format($payment->getAmount(), 0, ',', ' ') . ' FCFA',
        $payment->getPaidDate()->format('d/m/Y')
    );
    
    $sms->envoyerSms($tenant->getPhone(), $message);
}
```

### **3. Alerte Maintenance Urgente**

```php
public function envoyerAlerteMaintenance(MaintenanceRequest $request, OrangeSmsService $sms): void
{
    $owner = $request->getProperty()->getOwner();
    $message = sprintf(
        "URGENT MYLOCCA: Demande de maintenance à %s. Priorité: %s",
        $request->getProperty()->getAddress(),
        $request->getPriority()
    );
    
    $sms->envoyerSms($owner->getPhone(), $message);
}
```

### **4. Expiration de Bail**

```php
public function envoyerAlerteBail(Lease $lease, OrangeSmsService $sms): void
{
    $tenant = $lease->getTenant();
    $message = sprintf(
        "MYLOCCA: Votre bail expire le %s. Contactez-nous pour renouvellement.",
        $lease->getEndDate()->format('d/m/Y')
    );
    
    $sms->envoyerSms($tenant->getPhone(), $message);
}
```

---

## 🔧 Paramètres d'Administration

### **Page de Configuration**

**Chemin :** `/admin/parametres/orange-sms`

**Champs :**

1. **Activer Orange SMS** (Switch)
   - Active/désactive l'envoi de SMS

2. **Client ID** (Obligatoire)
   - Identifiant unique de votre application Orange

3. **Client Secret** (Obligatoire)
   - Clé secrète pour authentification

4. **Nom de l'expéditeur** (Optionnel)
   - Nom affiché (max 11 caractères)
   - Par défaut : "MYLOCCA"

### **Test de Configuration**

**Fonctionnalité :**
- Saisir un numéro de téléphone
- Cliquer sur "Tester maintenant"
- Vérifie que les identifiants sont valides
- (Optionnel) Envoie un SMS de test

---

## 📊 Statistiques et Monitoring

### **Consulter les Statistiques**

```php
$orangeSms = new OrangeSmsService($settingsService);
$stats = $orangeSms->getAdminStats();

// Retourne les statistiques d'utilisation par pays/application
```

### **Vérifier le Crédit SMS**

```php
$contracts = $orangeSms->getAdminContracts('CI'); // Pour la Côte d'Ivoire
// Affiche combien de SMS restent disponibles
```

### **Historique des Achats**

```php
$bundles = $orangeSms->getAdminPurchasedBundles('CI');
// Liste tous les packs SMS achetés
```

---

## 🚀 Intégration dans les Tâches

### **Exemple : Rappels Automatiques**

Modifier `TaskManagerService::executePaymentReminderTask()` :

```php
private function executePaymentReminderTask(Task $task): void
{
    // ... code existant ...
    
    // Envoyer SMS si activé
    if ($this->settingsService->get('orange_sms_enabled', false)) {
        $orangeSms = new OrangeSmsService($this->settingsService);
        
        foreach ($overduePayments as $payment) {
            $tenant = $payment->getLease()->getTenant();
            $message = sprintf(
                "Rappel: Votre loyer de %s FCFA est en retard (échéance: %s)",
                number_format($payment->getAmount(), 0),
                $payment->getDueDate()->format('d/m/Y')
            );
            
            try {
                $orangeSms->envoyerSms($tenant->getPhone(), $message);
            } catch (\Exception $e) {
                $this->logger->error("Erreur SMS: " . $e->getMessage());
            }
        }
    }
}
```

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
├── 📱 Orange SMS ← NOUVEAU
└── Maintenance système
```

### **Page de Configuration**

```
┌─────────────────────────────────────────┐
│  Configuration Orange SMS               │
├─────────────────────────────────────────┤
│  ✅ Orange SMS est configuré !          │
├─────────────────────────────────────────┤
│  ℹ️ Comment obtenir vos identifiants ?  │
│  1. developer.orange.com                │
│  2. Créer une application               │
│  3. Activer l'API SMS                   │
│  ...                                    │
├─────────────────────────────────────────┤
│  Paramètres Orange SMS                  │
│  ┌────────────────────────────────────┐ │
│  │ ☑ Activer Orange SMS               │ │
│  │                                    │ │
│  │ Client ID: ___________________    │ │
│  │ Client Secret: _______________    │ │
│  │ Nom expéditeur: MYLOCCA ______    │ │
│  │                                    │ │
│  │ [Enregistrer] [Tester config]     │ │
│  └────────────────────────────────────┘ │
├─────────────────────────────────────────┤
│  Tester l'envoi de SMS                  │
│  ┌────────────────────────────────────┐ │
│  │ Numéro: +225 [0700000000]         │ │
│  │ [Tester maintenant]                │ │
│  └────────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

---

## 📝 Limitations et Contraintes

### **API Orange SMS**

1. **Longueur du message** : Max 160 caractères
2. **Nom de l'expéditeur** : Max 11 caractères alphanumériques
3. **Format du numéro** : `tel:+225XXXXXXXXXX` (obligatoire)
4. **Pays supportés** : Selon votre contrat Orange
5. **Crédit SMS** : Limité selon votre forfait

### **Bonnes Pratiques**

1. ✅ **Vérifier le crédit** avant envois massifs
2. ✅ **Logger les erreurs** pour traçabilité
3. ✅ **Limiter la fréquence** (éviter le spam)
4. ✅ **Respecter les horaires** (pas de SMS la nuit)
5. ✅ **Messages courts** et clairs

---

## 🔒 Sécurité

### **Stockage des Credentials**

- ✅ Client ID et Secret stockés dans `Settings` (base de données)
- ✅ Pas de hardcoding dans le code
- ✅ Accès restreint aux administrateurs
- ✅ Possibilité de chiffrer les credentials (TODO)

### **Vérification SSL**

En **production** :
```php
$orangeSms->setVerifyPeerSSL(true); // Vérifier le certificat
```

En **développement** :
```php
$orangeSms->setVerifyPeerSSL(false); // Désactiver pour tests locaux
```

---

## 🧪 Tests

### **Test de Configuration**

**Via l'Interface :**
1. Admin → Paramètres → Orange SMS
2. Remplir Client ID et Secret
3. Entrer un numéro de test
4. Cliquer sur "Tester maintenant"
5. Vérifier le résultat affiché

**Via Console (à créer) :**
```bash
php bin/console app:test-orange-sms 0700000000
```

### **Test d'Envoi Réel**

Décommenter dans le contrôleur (`testOrangeSms`) :
```php
$osms = new OrangeSmsService($settingsService);
$osms->setVerifyPeerSSL(false);
$result = $osms->envoyerSms($testPhone, 'Test SMS depuis MYLOCCA');
```

---

## 💡 Cas d'Usage Recommandés

### **1. Rappels de Paiement**
- 3 jours avant échéance
- Le jour de l'échéance
- 3 jours après (retard)

### **2. Confirmations**
- Paiement reçu
- Quittance disponible
- Rendez-vous confirmé

### **3. Alertes Urgentes**
- Maintenance urgente
- Coupure d'eau/électricité
- Travaux imprévus

### **4. Notifications Importantes**
- Expiration de bail (60 jours avant)
- Augmentation de loyer
- Changement de RIB

---

## 🎨 Templates de Messages

### **Rappel de Paiement**
```
Rappel MYLOCCA: Votre loyer de 50,000 FCFA est dû le 05/11/2025. Payez en ligne: mylocca.com/paiement
```

### **Confirmation de Paiement**
```
MYLOCCA: Paiement de 50,000 FCFA bien reçu le 03/11/2025. Votre quittance est disponible. Merci!
```

### **Maintenance Urgente**
```
URGENT MYLOCCA: Demande de maintenance enregistrée pour votre bien au 123 Rue X. Intervention prévue sous 24h.
```

### **Expiration de Bail**
```
MYLOCCA: Votre bail expire le 31/12/2025 (dans 60 jours). Contactez-nous pour renouvellement: 0700000000
```

---

## 📞 Support Orange Developer

### **Liens Utiles**

- **Portail Developer** : https://developer.orange.com
- **Documentation API SMS** : https://developer.orange.com/apis/sms-ci/
- **Support** : https://developer.orange.com/support
- **Forum** : https://developer.orange.com/forum

### **Obtenir les Identifiants**

1. Créer un compte sur https://developer.orange.com
2. Créer une nouvelle application
3. Sélectionner l'API "SMS CI" (Côte d'Ivoire)
4. Récupérer le **Client ID** et **Client Secret**
5. Acheter un forfait SMS si nécessaire

---

## 📝 Fichiers Créés

- ✅ `src/Service/OrangeSmsService.php` : Service principal
- ✅ `src/Controller/Admin/SettingsController.php` : Actions ajoutées
- ✅ `templates/admin/settings/orange_sms.html.twig` : Page de configuration
- ✅ `src/Service/MenuService.php` : Menu ajouté

---

## 🔄 Prochaines Évolutions

### **Fonctionnalités à Ajouter**

1. **Envoi massif** : Envoyer à plusieurs destinataires
2. **Templates de SMS** : Messages prédéfinis configurables
3. **Planification** : Programmer l'envoi de SMS
4. **Historique** : Logger tous les SMS envoyés
5. **Statistiques** : Tableau de bord des envois
6. **Webhooks** : Recevoir les statuts de livraison

### **Intégrations Suggérées**

1. **NotificationService** : Intégrer SMS comme canal
2. **TaskManager** : Tâches d'envoi de SMS planifiées
3. **Events** : Déclencher SMS sur événements
4. **RentReceiptService** : Notifier par SMS quand document prêt

---

**Date de création :** 12 octobre 2025  
**Version :** 1.0  
**Statut :** ✅ Service créé et configuré
