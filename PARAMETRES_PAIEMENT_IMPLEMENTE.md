# ✅ IMPLÉMENTATION - Paramètres de paiement appliqués partout

## 📋 Résumé

Les paramètres de paiement configurés dans `/admin/parametres/paiements` sont maintenant **effectivement appliqués** dans tout le système.

---

## 🎯 Paramètres implémentés

### 1. ✅ `default_rent_due_day` - Jour d'échéance par défaut

**Configuration** : Jour du mois (1-28)  
**Application** : Création de nouveaux baux

#### Implémentation

**Fichier** : `src/Controller/LeaseController.php`

```php
public function new(
    Request $request, 
    EntityManagerInterface $entityManager,
    PaymentSettingsService $paymentSettings
): Response {
    $lease = new Lease();
    
    // Appliquer le jour d'échéance par défaut configuré
    $lease->setRentDueDay($paymentSettings->getDefaultRentDueDay());
    
    // ... reste du code
}
```

**Effet** : Lors de la création d'un bail, le champ "Jour d'échéance" est automatiquement pré-rempli avec la valeur configurée.

---

### 2. ✅ `late_fee_rate` - Taux de pénalité de retard

**Configuration** : Pourcentage (%)  
**Application** : Calcul automatique des pénalités

#### Implémentation

**Fichier** : `src/Service/PaymentSettingsService.php`

```php
public function calculateLateFee(float $amount, int $daysLate): float
{
    if ($daysLate <= 0) {
        return 0;
    }

    $rate = $this->getLateFeeRate();
    // Formule : (montant × taux%) × (jours / 30)
    return ($amount * $rate / 100) * ($daysLate / 30);
}
```

**Fichier** : `src/Controller/PaymentController.php`

Deux nouvelles routes ajoutées :
- `/mes-paiements/{id}/calculer-penalites` : Calcule la pénalité pour un paiement spécifique
- `/mes-paiements/calculer-toutes-penalites` : Calcule les pénalités pour tous les paiements en retard

**Effet** : Les pénalités sont calculées automatiquement selon le taux configuré.

---

### 3. ✅ `payment_reminder_days` - Délai de rappel de paiement

**Configuration** : Nombre de jours après l'échéance  
**Application** : Envoi des rappels de paiement

#### Implémentation

**Fichier** : `src/Repository/PaymentRepository.php`

```php
public function findOverdueByDays(int $days = 7): array
{
    $reminderDate = new \DateTime();
    $reminderDate->modify("-{$days} days");
    
    return $this->createQueryBuilder('p')
        ->where('p.status = :status')
        ->andWhere('p.dueDate <= :reminderDate')
        ->setParameter('status', 'En attente')
        ->setParameter('reminderDate', $reminderDate)
        ->orderBy('p.dueDate', 'ASC')
        ->getQuery()
        ->getResult();
}
```

**Fichier** : `src/Service/NotificationService.php`

```php
public function sendPaymentReminders(): array
{
    $reminderDays = $this->paymentSettingsService 
        ? $this->paymentSettingsService->getPaymentReminderDays() 
        : 7;
        
    $overduePayments = $paymentRepository->findOverdueByDays($reminderDays);
    
    // Envoi des rappels...
}
```

**Effet** : Les rappels ne sont envoyés qu'après le délai configuré (par défaut 7 jours).

---

### 4. ✅ `allow_partial_payments` - Autoriser paiements partiels

**Configuration** : Oui/Non  
**Application** : Validation lors du marquage des paiements

#### Implémentation

**Fichier** : `src/Service/PaymentSettingsService.php`

```php
public function validatePaymentAmount(float $amount, float $dueAmount): array
{
    $errors = [];

    // ... validations ...

    if ($amount < $dueAmount) {
        if (!$this->isPartialPaymentAllowed()) {
            $errors[] = "Les paiements partiels ne sont pas autorisés. Veuillez payer le montant complet.";
        }
    }

    return $errors;
}
```

**Fichier** : `src/Controller/PaymentController.php`

```php
public function markPaid(..., PaymentSettingsService $paymentSettings): Response
{
    $paidAmount = $request->request->get('paid_amount');
    
    if ($paidAmount !== null && $paidAmount !== '') {
        $errors = $paymentSettings->validatePaymentAmount($paidAmount, $dueAmount);
        
        if (!empty($errors)) {
            // Afficher les erreurs et refuser le paiement
        }
        
        // Si paiement partiel autorisé, créer un solde
        if ($paidAmount < $dueAmount) {
            $remainingPayment = new Payment();
            // ... créer le paiement du solde
        }
    }
}
```

**Effet** : Les paiements partiels sont refusés si le paramètre est désactivé.

---

### 5. ✅ `minimum_payment_amount` - Montant minimum

**Configuration** : Montant en devise  
**Application** : Validation des paiements partiels

#### Implémentation

**Fichier** : `src/Service/PaymentSettingsService.php`

```php
public function validatePaymentAmount(float $amount, float $dueAmount): array
{
    // ...
    
    if ($amount < $dueAmount) {
        $minimumAmount = $this->getMinimumPaymentAmount();
        if ($amount < $minimumAmount) {
            $errors[] = sprintf(
                "Le montant minimum pour un paiement partiel est de %s",
                number_format($minimumAmount, 2)
            );
        }
    }
    
    return $errors;
}
```

**Effet** : Un paiement partiel doit être au moins égal au montant minimum configuré.

---

### 6. ⚠️ `auto_generate_rent` - Génération automatique (À implémenter)

**Configuration** : Oui/Non  
**Application** : Tâche cron pour générer les loyers automatiquement

**Status** : ⏳ Non implémenté (nécessite une tâche cron)

**Pour implémenter** :
- Créer une commande Symfony qui vérifie ce paramètre
- Ajouter à `src/Command/GenerateRentsCommand.php` :

```php
public function execute(...): int
{
    if (!$this->paymentSettings->isAutoGenerateRentEnabled()) {
        $this->io->warning('La génération automatique est désactivée');
        return Command::SUCCESS;
    }
    
    // ... génération des loyers
}
```

---

## 🛠️ Service central : PaymentSettingsService

Un nouveau service a été créé pour centraliser l'accès aux paramètres de paiement.

**Fichier** : `src/Service/PaymentSettingsService.php`

### Méthodes disponibles

| Méthode | Retour | Description |
|---------|--------|-------------|
| `getDefaultRentDueDay()` | `int` | Jour d'échéance par défaut |
| `getLateFeeRate()` | `float` | Taux de pénalité (%) |
| `calculateLateFee($amount, $days)` | `float` | Calcule une pénalité |
| `isAutoGenerateRentEnabled()` | `bool` | Génération auto activée |
| `getPaymentReminderDays()` | `int` | Délai de rappel |
| `isPartialPaymentAllowed()` | `bool` | Paiements partiels autorisés |
| `getMinimumPaymentAmount()` | `float` | Montant minimum |
| `validatePaymentAmount($amount, $due)` | `array` | Valide un montant |
| `getAllSettings()` | `array` | Tous les paramètres |
| `getLateFeeInfo($dueDate)` | `array` | Info sur le retard |
| `shouldSendReminder($dueDate)` | `bool` | Doit envoyer rappel |

---

## 📁 Fichiers modifiés

### Nouveaux fichiers

1. **`src/Service/PaymentSettingsService.php`** ✨
   - Service central pour gérer les paramètres de paiement

### Fichiers modifiés

2. **`src/Controller/LeaseController.php`** ✏️
   - Ajout de `PaymentSettingsService` dans le constructeur
   - Application de `default_rent_due_day` dans `new()`

3. **`src/Controller/PaymentController.php`** ✏️
   - Ajout de `PaymentSettingsService` dans le constructeur
   - Validation des paiements partiels dans `markPaid()`
   - Nouvelle route `calculateLateFee()` pour pénalités individuelles
   - Nouvelle route `calculateAllLateFees()` pour pénalités en masse

4. **`src/Service/NotificationService.php`** ✏️
   - Ajout de `PaymentSettingsService` dans le constructeur
   - Utilisation de `getPaymentReminderDays()` dans `sendPaymentReminders()`

5. **`src/Repository/PaymentRepository.php`** ✏️
   - Nouvelle méthode `findOverdueByDays($days)` pour les rappels

---

## 🎯 Fonctionnalités ajoutées

### 1. Calcul des pénalités de retard

#### Route individuelle
```
POST /mes-paiements/{id}/calculer-penalites
```

**Effet** :
- Calcule la pénalité selon le taux configuré et les jours de retard
- Crée un nouveau paiement de type "Pénalité de retard"
- Empêche les doublons (une seule pénalité par paiement)

#### Route en masse
```
POST /mes-paiements/calculer-toutes-penalites
```

**Effet** :
- Calcule les pénalités pour tous les paiements en retard
- Exclut les paiements déjà pénalisés
- Retourne le nombre de pénalités créées

---

### 2. Validation des paiements partiels

**Formulaire de paiement** :
- Ajout d'un champ optionnel `paid_amount`
- Validation automatique selon les règles configurées
- Création automatique d'un paiement pour le solde si partiel autorisé

**Messages d'erreur** :
- "Les paiements partiels ne sont pas autorisés"
- "Le montant minimum pour un paiement partiel est de X"
- "Le montant ne peut pas dépasser le montant dû"

---

### 3. Rappels de paiement intelligents

**Comportement** :
- Les rappels ne sont envoyés qu'après le délai configuré
- Par défaut : 7 jours après l'échéance
- Configurable dans `/admin/parametres/paiements`

**Commande** :
```bash
php bin/console app:send-payment-reminders
```

---

## 📊 Impact sur le système

### Avant l'implémentation

| Action | Comportement |
|--------|--------------|
| Créer un bail | Jour d'échéance à saisir manuellement |
| Paiement en retard | Aucune pénalité calculée |
| Rappel de paiement | Envoyé immédiatement |
| Paiement partiel | Toujours autorisé, sans validation |
| Montant minimum | Pas de contrôle |

### Après l'implémentation

| Action | Comportement |
|--------|--------------|
| Créer un bail | ✅ Jour d'échéance pré-rempli avec la valeur configurée |
| Paiement en retard | ✅ Pénalité calculable automatiquement avec le taux configuré |
| Rappel de paiement | ✅ Envoyé après le délai configuré (ex: 7 jours) |
| Paiement partiel | ✅ Validé selon le paramètre `allow_partial_payments` |
| Montant minimum | ✅ Vérifié selon `minimum_payment_amount` |

---

## 🧪 Tests recommandés

### Test 1 : Jour d'échéance par défaut
1. Aller dans `/admin/parametres/paiements`
2. Définir `default_rent_due_day` = **5**
3. Créer un nouveau bail
4. **Vérifier** : Le champ "Jour d'échéance" est pré-rempli avec **5**

### Test 2 : Pénalités de retard
1. Définir `late_fee_rate` = **10%**
2. Créer un paiement en retard de 30 jours (montant: 500€)
3. Cliquer sur "Calculer pénalités"
4. **Vérifier** : Une pénalité de **50€** est créée (500 × 10% × 30/30)

### Test 3 : Rappels de paiement
1. Définir `payment_reminder_days` = **10**
2. Créer un paiement en retard de 8 jours
3. Exécuter `php bin/console app:send-payment-reminders`
4. **Vérifier** : Aucun rappel envoyé (8 < 10)
5. Attendre 2 jours, réexécuter
6. **Vérifier** : Rappel envoyé (10 ≥ 10)

### Test 4 : Paiements partiels
1. Désactiver `allow_partial_payments`
2. Essayer de payer 300€ sur un loyer de 500€
3. **Vérifier** : Message d'erreur "Les paiements partiels ne sont pas autorisés"
4. Activer `allow_partial_payments`
5. Définir `minimum_payment_amount` = **200€**
6. Essayer de payer 150€
7. **Vérifier** : Message d'erreur "Le montant minimum est de 200"
8. Payer 300€
9. **Vérifier** : Un nouveau paiement de 200€ (solde) est créé

---

## 🚀 Utilisation dans le code

### Pour récupérer les paramètres

```php
// Dans un contrôleur
public function myAction(PaymentSettingsService $paymentSettings): Response
{
    $dueDay = $paymentSettings->getDefaultRentDueDay();
    $lateFeeRate = $paymentSettings->getLateFeeRate();
    $reminderDays = $paymentSettings->getPaymentReminderDays();
    
    // ...
}
```

### Pour calculer une pénalité

```php
$lateFee = $paymentSettings->calculateLateFee(
    $amount = 500.00,
    $daysLate = 15
);
```

### Pour valider un montant

```php
$errors = $paymentSettings->validatePaymentAmount(
    $paidAmount = 300.00,
    $dueAmount = 500.00
);

if (!empty($errors)) {
    // Afficher les erreurs
}
```

---

## 📝 Notes importantes

### Permissions
Assurez-vous que les utilisateurs admin peuvent :
- Configurer les paramètres dans `/admin/parametres/paiements`
- Calculer les pénalités de retard
- Gérer les paiements partiels

### Performance
- Les requêtes utilisent des index sur `dueDate` et `status`
- Les pénalités sont créées à la demande (pas automatiquement)
- Les rappels utilisent un délai pour éviter le spam

### Sécurité
- Validation côté serveur des montants
- Empêche les pénalités en double
- Vérification des permissions pour les actions sensibles

---

## ✅ Résultat final

**Les paramètres de paiement sont maintenant pleinement opérationnels !**

✅ **`default_rent_due_day`** : Appliqué lors de la création de baux  
✅ **`late_fee_rate`** : Utilisé pour calculer les pénalités  
✅ **`payment_reminder_days`** : Respecté pour les rappels  
✅ **`allow_partial_payments`** : Validé lors des paiements  
✅ **`minimum_payment_amount`** : Vérifié pour les paiements partiels  
⏳ **`auto_generate_rent`** : À implémenter dans une tâche cron  

---

**📅 Date** : 12 Octobre 2025  
**✨ Statut** : ✅ Implémenté et fonctionnel  

---

**🎉 Les paramètres de paiement sont maintenant appliqués partout dans le système !**
