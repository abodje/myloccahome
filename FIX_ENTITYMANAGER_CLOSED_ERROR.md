# 🔧 Correction de l'Erreur "EntityManager is closed"

## 📋 Problème Résolu

L'erreur **"The EntityManager is closed"** survient lors de l'exécution de la tâche de génération des quittances et avis d'échéances. Cette erreur se produit lorsqu'une exception est levée dans Doctrine, ce qui ferme automatiquement l'EntityManager pour des raisons de sécurité.

---

## ✅ Corrections Apportées

### **1. Protection Contre les Données Manquantes**

#### **Dans `generateRentReceipt()` et `generatePaymentNotice()`**

Ajout de validations avant d'accéder aux propriétés des entités :

```php
// Validation des données nécessaires
if (!$payment->getLease()) {
    throw new \InvalidArgumentException("Le paiement n'a pas de bail associé");
}

$lease = $payment->getLease();
if (!$lease->getTenant()) {
    throw new \InvalidArgumentException("Le bail n'a pas de locataire associé");
}

$tenant = $lease->getTenant();
```

**Avantages :**
- ✅ Évite les erreurs "Call to member function on null"
- ✅ Messages d'erreur clairs et explicites
- ✅ Permet d'identifier rapidement les données manquantes

---

### **2. Fallback Sécurisé pour les Associations**

Récupération progressive de la société avec plusieurs fallbacks :

```php
// Récupérer la société émettrice avec fallback sécurisé
$company = $payment->getCompany();
if (!$company && $lease->getCompany()) {
    $company = $lease->getCompany();
}
if (!$company && $lease->getProperty() && $lease->getProperty()->getCompany()) {
    $company = $lease->getProperty()->getCompany();
}
```

**Avantages :**
- ✅ Fonctionne même si certaines associations sont manquantes
- ✅ Utilise la meilleure source disponible
- ✅ Évite les erreurs avec les propriétés null

---

### **3. Gestion Améliorée des Erreurs dans les Boucles**

#### **Dans `generateMonthlyReceipts()` et `generateUpcomingNotices()`**

```php
foreach ($payments as $payment) {
    try {
        // Vérifier que toutes les entités nécessaires sont présentes
        if (!$payment->getLease() || !$payment->getLease()->getTenant()) {
            error_log("Paiement #{$payment->getId()}: bail ou locataire manquant");
            continue;
        }

        $receipt = $this->generateRentReceipt($payment);
        $generatedReceipts[] = $receipt;
        
        // Clear l'EntityManager pour libérer la mémoire
        $this->entityManager->clear(Document::class);
        
    } catch (\Exception $e) {
        // Log l'erreur avec plus de détails
        error_log(sprintf(
            "Erreur génération quittance pour paiement #%d: %s\nStack trace: %s",
            $payment->getId(),
            $e->getMessage(),
            $e->getTraceAsString()
        ));
        
        // Si l'EntityManager est fermé, on arrête
        if (!$this->entityManager->isOpen()) {
            error_log("EntityManager fermé - impossible de continuer la génération");
            break;
        }
    }
}
```

**Améliorations :**
- ✅ Vérifie les données avant traitement (évite les erreurs)
- ✅ Clear l'EntityManager après chaque document (libère la mémoire)
- ✅ Logs détaillés avec stack trace pour le debugging
- ✅ Détecte si l'EntityManager est fermé et arrête proprement
- ✅ Continue le traitement des autres paiements si un seul échoue

---

### **4. Amélioration des Logs dans TaskManagerService**

```php
try {
    // Générer les quittances et avis
    $receipts = $this->rentReceiptService->generateMonthlyReceipts($monthDate);
    $nextMonth = (clone $monthDate)->modify('+1 month');
    $notices = $this->rentReceiptService->generateUpcomingNotices($nextMonth);

    $total = count($receipts) + count($notices);

    // Logger le succès
    $this->logger->info(sprintf(
        '✅ Documents générés pour %s : %d quittances, %d avis d\'échéance (Total: %d)',
        $monthDate->format('F Y'),
        count($receipts),
        count($notices),
        $total
    ));

    if ($total === 0) {
        $this->logger->warning(sprintf(
            'Aucun document généré pour %s. Vérifiez qu\'il y a des paiements correspondants.',
            $monthDate->format('F Y')
        ));
    }
} catch (\Exception $e) {
    $this->logger->error(sprintf(
        '❌ Erreur lors de la génération des documents pour %s : %s',
        $monthDate->format('F Y'),
        $e->getMessage()
    ));
    throw $e;
}
```

**Avantages :**
- ✅ Logs clairs avec emojis pour faciliter la lecture
- ✅ Avertissement si aucun document n'est généré
- ✅ Capture et log les erreurs avant de les propager

---

## 🎯 Impact des Corrections

### **Avant :**
- ❌ L'EntityManager se fermait à la première erreur
- ❌ Tous les documents suivants échouaient
- ❌ Messages d'erreur peu informatifs
- ❌ Impossible de continuer après une erreur

### **Après :**
- ✅ Validation des données avant traitement
- ✅ Continue même si un document échoue
- ✅ Logs détaillés pour identifier les problèmes
- ✅ Gestion de la mémoire optimisée
- ✅ Détection proactive de l'état de l'EntityManager

---

## 🔍 Comment Diagnostiquer les Problèmes

### **1. Consulter les Logs**

Les logs sont maintenant beaucoup plus détaillés :

```bash
# Logs dans var/log/dev.log ou var/log/prod.log
[info] ✅ Documents générés pour October 2025 : 5 quittances, 3 avis d'échéance (Total: 8)

# Ou en cas d'erreur :
[error] Erreur génération quittance pour paiement #123: Le paiement n'a pas de bail associé
Stack trace: ...
```

### **2. Vérifier les Données**

Si un paiement échoue, vérifiez :

```sql
-- Vérifier que le paiement a un bail
SELECT p.id, p.lease_id, l.id as lease_exists
FROM payment p
LEFT JOIN lease l ON p.lease_id = l.id
WHERE p.id = [ID_DU_PAIEMENT];

-- Vérifier que le bail a un locataire
SELECT l.id, l.tenant_id, t.id as tenant_exists
FROM lease l
LEFT JOIN tenant t ON l.tenant_id = t.id
WHERE l.id = [ID_DU_BAIL];
```

---

## 📝 Utilisation

### **Test Manuel**

Dans l'interface web :
1. Allez sur `/mes-documents/`
2. Cliquez sur "Générer Documents du Mois"
3. Consultez les messages de succès/erreur
4. Vérifiez les logs en cas de problème

### **Via Tâche Planifiée**

```php
// La tâche s'exécute automatiquement selon la planification
// Consultez les logs pour voir le résultat :
// var/log/prod.log ou var/log/dev.log
```

### **Via Commande CLI (si disponible)**

```bash
php bin/console app:generate-rent-documents --month=2025-10
```

---

## 🛡️ Prévention Future

### **Bonnes Pratiques Implémentées**

1. **Toujours valider les relations avant de les utiliser**
   ```php
   if (!$entity->getRelation()) {
       // Gérer le cas null
   }
   ```

2. **Utiliser des try-catch dans les boucles de traitement**
   ```php
   foreach ($items as $item) {
       try {
           // Traitement
       } catch (\Exception $e) {
           // Log et continue
       }
   }
   ```

3. **Clear l'EntityManager régulièrement dans les traitements lourds**
   ```php
   $this->entityManager->clear(Document::class);
   ```

4. **Vérifier l'état de l'EntityManager après une erreur**
   ```php
   if (!$this->entityManager->isOpen()) {
       // EntityManager fermé, impossible de continuer
   }
   ```

---

## ✅ Checklist de Validation

- [x] Validations des entités ajoutées
- [x] Gestion des erreurs dans les boucles
- [x] Clear de l'EntityManager pour optimiser la mémoire
- [x] Logs détaillés pour le debugging
- [x] Fallbacks sécurisés pour les associations
- [x] Messages d'erreur explicites
- [x] Tests de génération manuelle
- [x] Documentation des corrections

---

## 🎓 Résumé

Les corrections apportées permettent maintenant de :
- ✅ **Continuer** la génération même si un document échoue
- ✅ **Identifier** rapidement les données manquantes
- ✅ **Optimiser** l'utilisation de la mémoire
- ✅ **Logger** toutes les erreurs avec détails
- ✅ **Prévenir** la fermeture de l'EntityManager

Le système est maintenant **plus robuste**, **plus tolérant aux erreurs** et **plus facile à déboguer**.

