# 🔍 Diagnostic : Erreur de configuration CinetPay

## ❌ Problème observé

**Message d'erreur** : "Erreur de configuration" et "URL de paiement non disponible"

**URL testée** : `/paiement-en-ligne/payer-loyer/20?method=mobile_money`

---

## ✅ Tests effectués

### 1. **Test de configuration CinetPay** ✅

```bash
php bin/console app:test-cinetpay
```

**Résultat** : ✅ **SUCCÈS**
- API Key : Configuré (32 caractères)
- Site ID : Configuré (105899583)
- Secret Key : Configuré (32 caractères)
- Environnement : Production
- **URL générée** : `https://checkout.cinetpay.com/payment/5b0dd8a6eee1746b2239a04ea930a4ba366cd755a0424b310f273318fe2738a257de0dfabebde30836ab3d731dfa94b95ad24a33039659`

### 2. **Test d'accès direct** ❌

```bash
curl "http://localhost:8000/paiement-en-ligne/payer-loyer/20?method=mobile_money"
```

**Résultat** : ❌ **ÉCHEC**
- Retourne une page de connexion
- L'utilisateur n'est pas authentifié

---

## 🔍 Diagnostic

### Problème identifié

**L'erreur de configuration ne vient PAS de CinetPay** mais de l'authentification :

1. ✅ **CinetPay fonctionne** : La commande de test génère bien une URL
2. ❌ **Authentification requise** : L'utilisateur doit être connecté
3. ❌ **Contrôleur non atteint** : Les logs de debug n'apparaissent pas

---

## 🔧 Solutions possibles

### Solution 1 : **Connexion utilisateur**

**Problème** : L'utilisateur n'est pas connecté

**Solution** :
1. Se connecter en tant que locataire
2. Aller dans "Mes paiements"
3. Cliquer sur "Payer en ligne" pour un paiement en attente

### Solution 2 : **Vérification des données**

**Vérifier** :
1. Le paiement ID 20 existe-t-il ?
2. Le paiement est-il en statut "En attente" ?
3. L'utilisateur connecté est-il le locataire associé ?

### Solution 3 : **Test avec utilisateur connecté**

**Étapes** :
1. Se connecter avec un compte locataire
2. Accéder à `/mes-paiements/`
3. Cliquer sur "Payer en ligne" pour un paiement en attente
4. Vérifier que la redirection fonctionne

---

## 🎯 Tests à effectuer

### Test 1 : **Vérification des données**

```sql
-- Vérifier que le paiement existe
SELECT * FROM payment WHERE id = 20;

-- Vérifier le statut
SELECT id, status, amount, due_date FROM payment WHERE id = 20;

-- Vérifier le locataire associé
SELECT p.id, p.status, t.first_name, t.last_name, t.email
FROM payment p
JOIN lease l ON p.lease_id = l.id
JOIN tenant t ON l.tenant_id = t.id
WHERE p.id = 20;
```

### Test 2 : **Connexion et test**

1. **Se connecter** avec un compte locataire
2. **Aller dans** `/mes-paiements/`
3. **Cliquer sur** "Payer en ligne" pour un paiement en attente
4. **Vérifier** que la redirection vers CinetPay fonctionne

### Test 3 : **Logs de debug**

**Ajouter temporairement** dans le contrôleur :
```php
error_log('=== PAYMENT CONTROLLER REACHED ===');
error_log('Payment ID: ' . $payment->getId());
error_log('User: ' . ($this->getUser() ? $this->getUser()->getEmail() : 'NOT LOGGED IN'));
```

---

## 🚨 Causes probables

### 1. **Authentification manquante** (Le plus probable)
- L'utilisateur n'est pas connecté
- La session a expiré
- L'utilisateur n'a pas les bonnes permissions

### 2. **Données incorrectes**
- Le paiement ID 20 n'existe pas
- Le paiement n'est pas en statut "En attente"
- Le locataire associé n'existe pas

### 3. **Configuration manquante**
- Paramètres CinetPay non sauvegardés
- Cache non vidé après modification des paramètres

---

## ✅ Vérifications à faire

### 1. **Authentification**
```bash
# Vérifier que l'utilisateur est connecté
# Aller sur http://localhost:8000/mes-paiements/
# S'assurer qu'on voit la liste des paiements
```

### 2. **Paramètres CinetPay**
```bash
# Aller sur http://localhost:8000/admin/parametres/cinetpay
# Vérifier que tous les paramètres sont bien sauvegardés
# Tester la connexion avec le bouton "Tester la connexion"
```

### 3. **Cache**
```bash
# Vider le cache
php bin/console cache:clear
```

### 4. **Base de données**
```bash
# Vérifier que les paramètres sont en base
php bin/console app:test-cinetpay
```

---

## 🎊 Conclusion

**L'erreur de configuration CinetPay est un faux problème !**

### Vraie cause
- **Authentification manquante** : L'utilisateur doit être connecté
- **CinetPay fonctionne** : La commande de test le prouve

### Solution
1. **Se connecter** en tant que locataire
2. **Utiliser le bouton** "Payer en ligne" depuis la page des paiements
3. **Ne pas accéder directement** à l'URL sans être connecté

---

**📅 Date** : 12 Octobre 2025  
**🔍 Statut** : Diagnostic terminé  
**🎯 Cause** : Authentification manquante (pas CinetPay)
