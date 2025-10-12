# 🏠 Commande de génération automatique des loyers

## 📋 Vue d'ensemble

**Commande** : `app:generate-rents`

Cette commande génère automatiquement les échéances de loyer pour tous les contrats actifs, en respectant :
- ✅ La date de fin du bail
- ✅ Le jour d'échéance défini dans chaque contrat
- ✅ Les loyers déjà existants (pas de doublon)

---

## 🚀 Utilisation

### Commande de base

```bash
# Génère les loyers du mois suivant pour tous les contrats actifs
php bin/console app:generate-rents
```

### Options disponibles

#### 1. Mode simulation (--dry-run)
```bash
# Voir ce qui serait généré sans créer les loyers
php bin/console app:generate-rents --dry-run
```

#### 2. Spécifier le mois (--month)
```bash
# Générer pour janvier 2026
php bin/console app:generate-rents --month=2026-01

# Générer pour décembre 2025
php bin/console app:generate-rents --month=2025-12
```

#### 3. Nombre de mois à générer (--months-ahead)
```bash
# Générer 3 mois à l'avance
php bin/console app:generate-rents --months-ahead=3

# Générer 6 mois à l'avance
php bin/console app:generate-rents --months-ahead=6

# Générer 12 mois à l'avance
php bin/console app:generate-rents --months-ahead=12
```

#### 4. Combinaisons
```bash
# Simuler la génération de 6 mois à partir de janvier 2026
php bin/console app:generate-rents --month=2026-01 --months-ahead=6 --dry-run

# Générer 3 mois à partir du mois suivant
php bin/console app:generate-rents --months-ahead=3
```

---

## 📊 Exemple de sortie

### Exécution normale

```bash
$ php bin/console app:generate-rents

🏠 Génération automatique des loyers - MYLOCCA
==============================================

📊 Contrats actifs trouvés : 4
📅 Génération pour 1 mois à partir de December 2025


📋 Résultats par contrat
------------------------

 ----------------------- --------------------------- -------------------- 
  Locataire              Propriété                   Résultat            
 ----------------------- --------------------------- -------------------- 
  Marie Dubois           1-9 Avenue de Limburg       ✅ 1 loyer(s)        
  Jean Martin            45 rue de la Paix           ✅ 1 loyer(s)        
  Sophie Laurent         12 Boulevard des Belges     ✅ 1 loyer(s)        
  Pierre Durand          8 Place Bellecour           ✅ 1 loyer(s)        
 ----------------------- --------------------------- -------------------- 

📊 Résumé global
----------------

 ------------------ ------ 
  Loyers générés     4     
  Contrats traités   4     
  Mode               RÉEL  
 ------------------ ------ 

 [OK] ✅ 4 loyer(s) générés avec succès !
```

### Avec simulation (--dry-run)

```bash
$ php bin/console app:generate-rents --dry-run

⚠️  MODE SIMULATION - Aucune donnée ne sera enregistrée

📊 Contrats actifs trouvés : 4
📅 Génération pour 1 mois à partir de December 2025

...

 [OK] ✅ 4 loyer(s) seraient générés (simulation)
```

### Avec bail expiré

```bash
📋 Résultats par contrat
------------------------

 ----------------------- --------------------------- --------------------------------- 
  Locataire              Propriété                   Résultat                         
 ----------------------- --------------------------- --------------------------------- 
  Marie Dubois           1-9 Avenue de Limburg       ✅ 2 loyer(s)                     
  Jean Martin            45 rue de la Paix           ⚠️  Bail expiré le 31/12/2025    
 ----------------------- --------------------------- --------------------------------- 
```

---

## ⚙️ Configuration dans les tâches CRON

### Linux/Mac

```bash
# Générer les loyers le 25 de chaque mois à 9h
0 9 25 * * cd /path/to/mylocca && php bin/console app:generate-rents >> /var/log/mylocca-rents.log 2>&1

# Générer 2 mois à l'avance le 20 de chaque mois
0 9 20 * * cd /path/to/mylocca && php bin/console app:generate-rents --months-ahead=2 >> /var/log/mylocca-rents.log 2>&1
```

### Windows (Planificateur de tâches)

**Programme** : `C:\wamp64\bin\php\php8.x.x\php.exe`

**Arguments** : `C:\wamp64\mylocca\bin\console app:generate-rents`

**Déclencheur** : Mensuel, le 25 à 09:00

---

## 🔒 Sécurités intégrées

### 1. Vérification de la date de fin du bail
```php
if ($lease->getEndDate() && $dueDate > $lease->getEndDate()) {
    break; // Arrêter la génération
}
```

### 2. Pas de doublon
```php
$existingPayment = $entityManager->getRepository(Payment::class)->findOneBy([
    'lease' => $lease,
    'dueDate' => $dueDate,
    'type' => 'Loyer'
]);

if (!$existingPayment) {
    // Créer seulement si n'existe pas
}
```

### 3. Respect du jour d'échéance
```php
$dueDate->setDate(
    $targetMonth->format('Y'),
    $targetMonth->format('n'),
    $lease->getRentDueDay() ?? 1  // Jour d'échéance ou 1er par défaut
);
```

---

## 🎯 Cas d'utilisation

### Scénario 1 : Génération mensuelle automatique (CRON)

**Objectif** : Générer automatiquement le loyer du mois suivant

**Configuration CRON** :
```bash
# Chaque 25 du mois à 9h
0 9 25 * * php bin/console app:generate-rents
```

**Résultat** :
- Le 25 octobre → Génère les loyers du 1er novembre
- Le 25 novembre → Génère les loyers du 1er décembre
- etc.

### Scénario 2 : Génération anticipée

**Objectif** : Générer plusieurs mois à l'avance

**Commande** :
```bash
php bin/console app:generate-rents --months-ahead=6
```

**Résultat** :
- Génère les 6 prochains mois pour tous les contrats actifs
- Utile en début d'année ou pour planification

### Scénario 3 : Correction manuelle

**Objectif** : Générer un mois spécifique qui a été oublié

**Commande** :
```bash
php bin/console app:generate-rents --month=2025-11
```

**Résultat** :
- Génère uniquement les loyers de novembre 2025
- Idéal pour correction

### Scénario 4 : Test avant production

**Objectif** : Vérifier ce qui serait généré

**Commande** :
```bash
php bin/console app:generate-rents --months-ahead=3 --dry-run
```

**Résultat** :
- Affiche ce qui serait créé
- Aucune modification en base
- Parfait pour vérifier avant exécution réelle

---

## 📈 Statistiques affichées

Pour chaque contrat :
- **Locataire** : Nom complet
- **Propriété** : Adresse
- **Résultat** :
  - ✅ X loyer(s) → Générés avec succès
  - ➖ Déjà générés → Loyers déjà existants
  - ⚠️  Bail expiré → Date de fin dépassée

### Résumé global :
- **Loyers générés** : Nombre total créé
- **Contrats traités** : Nombre de contrats actifs
- **Mode** : RÉEL ou SIMULATION

---

## 🎓 Exemples pratiques

### Exemple 1 : Début de mois

```bash
# Le 25 octobre, générer les loyers de novembre
php bin/console app:generate-rents

# Résultat : Crée les paiements avec échéance au 01/11/2025 (ou jour configuré)
```

### Exemple 2 : Préparation annuelle

```bash
# En janvier, générer toute l'année
php bin/console app:generate-rents --months-ahead=12

# Résultat : 12 mois de loyers créés pour tous les contrats actifs
```

### Exemple 3 : Vérification avant génération

```bash
# Vérifier d'abord
php bin/console app:generate-rents --months-ahead=12 --dry-run

# Si OK, générer réellement
php bin/console app:generate-rents --months-ahead=12
```

---

## 🔗 Intégration avec les tâches automatisées

Cette commande est déjà intégrée dans le système de tâches :

**Tâche** : "Génération automatique des loyers"
- **Type** : `GENERATE_RENTS`
- **Fréquence** : Mensuelle
- **Exécution** : 25ème jour du mois

**Via l'interface** : `/admin/taches`

---

## 📝 Logs recommandés

### Créer un fichier de log dédié

```bash
# Linux/Mac
php bin/console app:generate-rents >> /var/log/mylocca-rents.log 2>&1

# Windows (dans le Planificateur)
php bin/console app:generate-rents > C:\wamp64\mylocca\var\log\rents.log 2>&1
```

### Rotation des logs

Pour éviter que les logs deviennent trop gros, configurez une rotation (Linux) :

```bash
# /etc/logrotate.d/mylocca-rents
/var/log/mylocca-rents.log {
    weekly
    rotate 4
    compress
    missingok
}
```

---

## ✅ Avantages de cette commande

1. **Automatisable** : Parfait pour CRON
2. **Sécurisée** : Vérifie les dates de fin
3. **Intelligente** : Pas de doublons
4. **Flexible** : Plusieurs options
5. **Testable** : Mode dry-run
6. **Informative** : Affichage détaillé
7. **Fiable** : Gestion des erreurs

---

## 🎯 Recommandation de configuration

### Configuration idéale :

```bash
# Crontab
# Générer le loyer du mois suivant le 25 de chaque mois
0 9 25 * * cd /path/to/mylocca && php bin/console app:generate-rents

# OU via l'interface web
# Administration > Tâches > Initialiser
# La tâche "Génération automatique des loyers" sera créée automatiquement
```

---

## 🎉 Résumé

Vous disposez maintenant d'une **commande console professionnelle** pour :

✅ Générer automatiquement les loyers  
✅ Respecter la fin des baux  
✅ Éviter les doublons  
✅ Simuler avant de créer  
✅ Générer plusieurs mois à l'avance  
✅ Suivre précisément ce qui est créé  
✅ Intégrer dans CRON facilement  

**La génération de loyers est maintenant PARFAITE !** 🚀

---

**Commande créée** : `src/Command/GenerateRentsCommand.php`  
**Date** : 11 Octobre 2025  
**Status** : ✅ 100% Opérationnel

