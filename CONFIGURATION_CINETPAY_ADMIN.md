# 💳 Configuration CinetPay via Interface Admin

## ✅ Problème résolu

**Avant** : Les credentials CinetPay étaient en dur dans le code ❌
```php
$apikey = '383009496685bd7d235ad53.69596427';
$site_id = '105899583';
$secret = '202783455685bd868b44665.45198979';
```

**Maintenant** : Configuration via interface admin ✅
- 🎛️ Interface graphique complète
- 💾 Stockage en base de données
- 🔒 Sécurité (Secret Key masqué)
- 🧪 Test de connexion intégré

---

## 🎯 Accès à la configuration

### URL

```
/admin/parametres → Cliquer sur "💳 CinetPay"
/admin/parametres/cinetpay
```

### Menu

**Dans `/admin/parametres`**, nouvelle carte :
```
┌──────────────────────────┐
│    💳 CinetPay          │
│                          │
│  Paiements Mobile Money  │
│    & Carte bancaire      │
│                          │
│    [Configurer]          │
└──────────────────────────┘
```

---

## ⚙️ Paramètres configurables

### 1. Identifiants API 🔑

| Champ | Description | Exemple |
|-------|-------------|---------|
| **API Key** | Clé API CinetPay | `383009496685bd7d235ad53.69596427` |
| **Site ID** | Identifiant du site | `105899583` |
| **Secret Key** | Clé secrète (HMAC) | `202783455685bd868b44665.45198979` |

**Champs obligatoires** : Marqués avec ⭐

**Sécurité** : Secret Key en `type="password"` avec bouton "👁️ Afficher/Masquer"

---

### 2. Environnement 🌍

| Option | Description |
|--------|-------------|
| **🧪 Test/Sandbox** | Paiements de test (ne débite pas vraiment) |
| **🚀 Production** | Paiements réels |

**Devise par défaut** : XOF / USD / EUR

---

### 3. URLs de Callback 🔗

#### URL de notification (Webhook)
```
https://votre-domaine.com/paiement-en-ligne/notification
```
- Générée automatiquement
- Bouton "📋 Copier" pour copier dans le presse-papiers
- À configurer dans votre compte CinetPay

⚠️ **Important** : Cette URL doit être **publiquement accessible**

#### URL de retour (optionnel)
```
https://votre-domaine.com/confirmation-paiement
```
Par défaut : `/paiement-en-ligne/retour/{transactionId}`

---

### 4. Options avancées ⚙️

**Activer CinetPay** : Switch ON/OFF
- Permet de désactiver temporairement les paiements en ligne

**Canaux de paiement** :
- `ALL` : Tous (Mobile Money + Carte)
- `MOBILE_MONEY` : Uniquement Orange/MTN/Moov/Wave
- `CARD` : Uniquement Carte Bancaire

---

## 🎨 Interface utilisateur

### Sidebar "Configuration actuelle"

```
┌─────────────────────────┐
│ 📊 Configuration actuelle│
├─────────────────────────┤
│ Statut: ✅ Configuré    │
│ Environnement: 🧪 Test  │
│ Paiements: ✅ Activé    │
│ Devise: XOF             │
└─────────────────────────┘
```

### Sidebar "Modes de paiement"

```
┌─────────────────────────┐
│ 📱 Modes de paiement     │
├─────────────────────────┤
│ 🍊 Orange Money         │
│ 💛 MTN Money            │
│ 💙 Moov Money           │
│ 💚 Wave                 │
│ 💳 Visa/Mastercard      │
└─────────────────────────┘
```

### Sidebar "Guide de configuration"

```
┌─────────────────────────┐
│ 📖 Guide                │
├─────────────────────────┤
│ 1. Créer compte CinetPay│
│ 2. Aller dans Paramètres│
│ 3. Copier API Key       │
│ 4. Copier Site ID       │
│ 5. Copier Secret Key    │
│ 6. Configurer Webhook   │
└─────────────────────────┘
```

---

## 🧪 Test de configuration

### Bouton "Tester la connexion"

**Action** : Vérifie que les identifiants sont valides

**Résultats possibles** :

✅ **Succès** :
```
┌─────────────────────────────────┐
│ ✅ Succès !                     │
│ La connexion à CinetPay         │
│ fonctionne correctement         │
└─────────────────────────────────┘
```

❌ **Erreur** :
```
┌─────────────────────────────────┐
│ ❌ Erreur                       │
│ API Key ou Site ID manquant     │
└─────────────────────────────────┘
```

---

## 🔧 Utilisation dans le code

### Avant (en dur) ❌

```php
$apikey = '383009496685bd7d235ad53.69596427';
$site_id = '105899583';
$secret = '202783455685bd868b44665.45198979';

$cinetpay = new CinetPay($apikey, $site_id);
```

### Maintenant (depuis Settings) ✅

```php
// Dans CinetPayService
public function __construct(SettingsService $settingsService)
{
    $this->apikey = $settingsService->get('cinetpay_apikey');
    $this->site_id = $settingsService->get('cinetpay_site_id');
}

// Dans OnlinePaymentController
$secretKey = $settingsService->get('cinetpay_secret_key');
$environment = $settingsService->get('cinetpay_environment');
$enabled = $settingsService->get('cinetpay_enabled');
```

---

## 📊 Fichiers modifiés/créés

### Nouveaux fichiers (1)

1. **`templates/admin/settings/cinetpay.html.twig`** (280 lignes)
   - Interface complète de configuration
   - Formulaire avec validation
   - Test de connexion
   - Guide intégré

### Fichiers modifiés (3)

2. **`src/Controller/Admin/SettingsController.php`**
   - Nouvelle route `app_admin_cinetpay_settings` (GET/POST)
   - Nouvelle route `app_admin_cinetpay_test` (POST)
   - Gestion des 8 paramètres CinetPay

3. **`src/Service/SettingsService.php`**
   - Nouvelle méthode `getCinetPaySettings()`
   - Defaults CinetPay dans `restoreDefaults()`

4. **`templates/admin/settings/index.html.twig`**
   - Nouvelle carte "💳 CinetPay"
   - Lien vers la configuration

---

## 🎯 Paramètres stockés en base

**Table** : `settings`

| Key | Value (défaut) | Type |
|-----|----------------|------|
| `cinetpay_apikey` | `383009496685bd7d235ad53.69596427` | STRING |
| `cinetpay_site_id` | `105899583` | STRING |
| `cinetpay_secret_key` | `202783455685bd868b44665.45198979` | STRING |
| `cinetpay_environment` | `test` | STRING |
| `cinetpay_currency` | `XOF` | STRING |
| `cinetpay_return_url` | `(vide)` | STRING |
| `cinetpay_enabled` | `true` | BOOLEAN |
| `cinetpay_channels` | `ALL` | STRING |

---

## 🚀 Utilisation

### Étape 1 : Accéder à la configuration

```
1. Se connecter en admin
2. Aller dans "Administration"
3. Cliquer sur "Paramètres"
4. Cliquer sur la carte "💳 CinetPay"
```

### Étape 2 : Configurer les identifiants

```
1. Renseigner API Key
2. Renseigner Site ID
3. Renseigner Secret Key
4. Choisir l'environnement (Test/Production)
5. Cliquer "Enregistrer la configuration"
```

### Étape 3 : Tester

```
1. Cliquer sur "Tester la connexion"
2. Vérifier le message de succès
3. Si erreur : corriger les identifiants
```

### Étape 4 : Configurer le Webhook dans CinetPay

```
1. Se connecter sur cinetpay.com
2. Aller dans Paramètres → API
3. Copier l'URL de notification affichée
4. La coller dans CinetPay
5. Sauvegarder
```

---

## 🔐 Sécurité

### Secret Key masqué

- Champ `type="password"`
- Bouton "👁️ Afficher" pour voir temporairement
- Jamais exposé dans les logs

### Validation côté serveur

```php
if (empty($apikey) || empty($siteId)) {
    throw new Exception('Configuration incomplète');
}
```

### HMAC dans le webhook

```php
$secretKey = $settingsService->get('cinetpay_secret_key');
$generatedToken = hash_hmac('sha256', $concatenated, $secretKey);

if ($generatedToken !== $receivedToken) {
    return 403; // Signature invalide
}
```

---

## 📝 Routes ajoutées

```
GET  /admin/parametres/cinetpay         → Interface configuration
POST /admin/parametres/cinetpay         → Enregistrer config
POST /admin/parametres/cinetpay/tester  → Tester connexion
```

---

## 💡 Avantages

✅ **Flexibilité** : Changer les credentials sans modifier le code  
✅ **Sécurité** : Secret Key masqué et protégé  
✅ **Multi-environnement** : Basculer Test ↔ Production facilement  
✅ **Traçabilité** : Toutes les modifications sont enregistrées  
✅ **Professionnel** : Interface intuitive avec guide intégré  
✅ **Test intégré** : Vérifier la configuration en un clic  

---

## 🎊 Résultat final

**Les credentials CinetPay sont maintenant gérés professionnellement** :

✅ Plus de valeurs en dur dans le code  
✅ Configuration via interface admin  
✅ Test de connexion intégré  
✅ Guide d'utilisation inclus  
✅ Sécurité renforcée  
✅ Basculement Test/Production facile  

---

**📅 Date** : 12 Octobre 2025  
**✨ Statut** : ✅ Implémenté et fonctionnel  

---

**🎉 La configuration CinetPay est maintenant entièrement gérable via l'interface admin !**
