# 🔍 Diagnostic : URL de paiement = "VIDE"

## ❌ Problème identifié

**Symptôme** : `URL de paiement = "VIDE"` dans la page de redirection CinetPay

**Cause** : CinetPay n'arrive pas à générer l'URL de paiement, probablement à cause d'une configuration incorrecte.

---

## 🔧 Solutions de diagnostic

### 1. **Commande de test créée**

**Nouveau fichier** : `src/Command/TestCinetPayCommand.php`

**Utilisation** :
```bash
php bin/console app:test-cinetpay
```

**Cette commande va** :
- ✅ Vérifier la configuration CinetPay
- ✅ Tester la connexion API
- ✅ Générer une URL de paiement test
- ✅ Afficher les erreurs détaillées

### 2. **Logs d'erreur améliorés**

**Dans le contrôleur** (`src/Controller/OnlinePaymentController.php`) :
```php
} catch (\Exception $e) {
    // Log l'erreur pour debug
    error_log('Erreur CinetPay: ' . $e->getMessage());
    error_log('Payment ID: ' . $payment->getId());
    error_log('Amount: ' . $payment->getAmount());
    
    // Retourner avec erreur détaillée
    return $this->render('online_payment/pay_rent.html.twig', [
        'payment' => $payment,
        'payment_url' => null,
        'error_message' => $e->getMessage(),
    ]);
}
```

### 3. **Interface de debug améliorée**

**Dans le template** (`templates/online_payment/pay_rent.html.twig`) :

```twig
<!-- Debug: Afficher l'URL en mode debug -->
{% if app.debug %}
<div class="alert alert-info mt-3">
    <strong>Debug:</strong> URL de paiement = "{{ payment_url ?? 'VIDE' }}"
    {% if error_message is defined and error_message %}
        <br><strong>Erreur:</strong> {{ error_message }}
    {% endif %}
</div>
{% endif %}

<!-- Message d'erreur si problème -->
{% if error_message is defined and error_message %}
<div class="alert alert-danger mt-3">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Erreur de paiement :</strong> {{ error_message }}
    <br><small class="text-muted">Veuillez vérifier votre configuration CinetPay dans les paramètres admin.</small>
</div>
{% endif %}
```

---

## 🎯 Causes possibles

### 1. **Configuration manquante**

**Vérifier** : `/admin/parametres/cinetpay`

**Paramètres requis** :
- ✅ API Key
- ✅ Site ID  
- ✅ Secret Key

### 2. **Identifiants incorrects**

**Problèmes courants** :
- ❌ API Key invalide ou expirée
- ❌ Site ID incorrect
- ❌ Secret Key mal configuré

### 3. **Environnement incorrect**

**Vérifier** :
- 🧪 Mode Test : Utilise les credentials de test
- 🚀 Mode Production : Utilise les credentials de production

### 4. **Données de paiement invalides**

**Vérifier** :
- ✅ Montant > 0
- ✅ Transaction ID unique
- ✅ URLs de callback valides
- ✅ Données client complètes

---

## 🚀 Étapes de résolution

### Étape 1 : Tester la configuration

```bash
# Exécuter la commande de test
php bin/console app:test-cinetpay
```

**Résultat attendu** :
```
✅ Configuration actuelle
┌─────────────┬─────────────────────────────────┐
│ Paramètre   │ Valeur                          │
├─────────────┼─────────────────────────────────┤
│ API Key     │ ✅ Configuré (32 caractères)    │
│ Site ID     │ ✅ Configuré (105899583)        │
│ Secret Key  │ ✅ Configuré (32 caractères)    │
│ Environnement│ 🧪 Test                        │
└─────────────┴─────────────────────────────────┘

✅ Test de connexion
Tentative d'initialisation d'un paiement test...
✅ Connexion CinetPay réussie !
URL de paiement générée : https://secure.cinetpay.com/...
```

### Étape 2 : Vérifier les paramètres admin

1. **Aller** dans `/admin/parametres/cinetpay`
2. **Vérifier** que tous les champs sont remplis
3. **Cliquer** sur "Tester la connexion"
4. **Corriger** les erreurs si nécessaire

### Étape 3 : Vérifier les logs

**Fichiers de logs** :
- `var/log/dev.log` (environnement de développement)
- `var/log/prod.log` (environnement de production)

**Rechercher** :
```
[ERROR] Erreur CinetPay: ...
Payment ID: 123
Amount: 300000
```

### Étape 4 : Tester un paiement réel

1. **Créer** un paiement en attente
2. **Aller** dans "Mes paiements"
3. **Cliquer** sur le bouton vert "Payer en ligne"
4. **Observer** les messages de debug

---

## 🔍 Messages d'erreur courants

### "API Key invalide"

**Solution** :
- Vérifier l'API Key dans `/admin/parametres/cinetpay`
- S'assurer qu'elle correspond à votre compte CinetPay

### "Site ID incorrect"

**Solution** :
- Vérifier le Site ID dans les paramètres
- S'assurer qu'il correspond à votre site CinetPay

### "Transaction ID déjà utilisé"

**Solution** :
- Le problème se résout automatiquement
- Chaque transaction doit avoir un ID unique

### "Montant invalide"

**Solution** :
- Vérifier que le montant > 0
- Vérifier que le montant est dans la devise correcte (XOF)

### "URL de callback invalide"

**Solution** :
- Vérifier que les URLs de notification et retour sont accessibles
- S'assurer qu'elles sont en HTTPS en production

---

## 🎯 Configuration recommandée

### Paramètres CinetPay

```yaml
# Dans /admin/parametres/cinetpay
API Key: 383009496685bd7d235ad53.69596427
Site ID: 105899583
Secret Key: 202783455685bd868b44665.45198979
Environnement: test (pour les tests)
Devise: XOF
Canaux: ALL
```

### URLs de callback

```
Notification: https://votre-domaine.com/paiement-en-ligne/notification
Retour: https://votre-domaine.com/paiement-en-ligne/retour/{transactionId}
```

---

## 🎊 Résolution

**Une fois le diagnostic effectué** :

1. **Corriger** la configuration CinetPay
2. **Tester** avec la commande `app:test-cinetpay`
3. **Essayer** un paiement réel
4. **Vérifier** que l'URL de paiement se génère correctement

**L'URL de paiement ne sera plus "VIDE" ! 💳✨**

---

**📅 Date** : 12 Octobre 2025  
**✨ Statut** : ✅ Outils de diagnostic créés  
**🎯 Prochaine étape** : Exécuter `php bin/console app:test-cinetpay`
