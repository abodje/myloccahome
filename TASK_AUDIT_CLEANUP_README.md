# 🧹 Tâche de Nettoyage d'Audit Log - AUDIT_CLEANUP

## 📋 Vue d'ensemble

La tâche **AUDIT_CLEANUP** permet de nettoyer automatiquement les anciens enregistrements d'audit log pour optimiser la taille de la base de données tout en conservant un historique récent pour la traçabilité.

---

## ✅ Fonctionnalités

### **Type de Tâche : AUDIT_CLEANUP**

Supprime automatiquement les enregistrements d'audit plus anciens qu'une période définie.

**Par défaut :**
- Fréquence : MONTHLY (tous les mois)
- Jour du mois : 1er du mois
- Période de conservation : 90 jours

---

## ⚙️ Paramètres

### **Paramètre `days`**

Nombre de jours d'historique à conserver.

**Valeurs recommandées :**
- `30` - Conservation minimale (1 mois)
- `90` - Recommandé (3 mois)
- `180` - Conservation étendue (6 mois)
- `365` - Conservation annuelle (1 an)

**Minimum absolu :** 30 jours (pour raison de sécurité)

### **Exemple de Paramètres**

```json
{
  "days": 90
}
```

---

## 🚀 Utilisation

### **Méthode 1 : Tâche Automatique (Recommandé)**

La tâche est créée automatiquement lors de l'initialisation du système.

**Configuration par défaut :**
```php
[
    'name' => 'Nettoyage de l\'historique d\'audit',
    'type' => 'AUDIT_CLEANUP',
    'description' => 'Supprime les anciens enregistrements d\'audit',
    'frequency' => 'MONTHLY',
    'parameters' => [
        'day_of_month' => 1,  // 1er du mois
        'days' => 90          // Conserver 90 jours
    ]
]
```

**Pour modifier :**
1. Accédez à l'interface de gestion des tâches
2. Trouvez la tâche "Nettoyage de l'historique d'audit"
3. Modifiez le paramètre `days` selon vos besoins

---

### **Méthode 2 : Commande CLI**

```bash
# Nettoyer avec conservation de 90 jours
php bin/console app:audit:cleanup --days=90

# Forcer sans confirmation
php bin/console app:audit:cleanup --days=90 --force

# Conservation minimale (30 jours)
php bin/console app:audit:cleanup --days=30
```

---

### **Méthode 3 : Via Interface Web**

1. Accédez à `/admin/audit/statistiques`
2. Section "Nettoyage Automatique"
3. Sélectionnez la période à conserver
4. Cliquez sur "Nettoyer"

---

### **Méthode 4 : Programmation Manuelle**

```php
use App\Entity\Task;

$task = new Task();
$task->setName('Nettoyage Audit Log')
     ->setType('AUDIT_CLEANUP')
     ->setFrequency('ONCE') // ou MONTHLY
     ->setParameters(['days' => 60])
     ->setStatus('ACTIVE');

$entityManager->persist($task);
$entityManager->flush();

// Exécuter immédiatement
$taskManager->executeTask($task);
```

---

## 🔄 Comportement de la Tâche

### **Cas 1 : Nettoyage Réussi**

```
📋 Paramètres : conserver 90 jours
    ↓
🔍 Recherche enregistrements > 90 jours
    ↓
🗑️ Suppression de 1,234 enregistrements
    ↓
✅ Log de succès
```

**Log produit :**
```
[info] ✅ Nettoyage de l'audit log terminé : 1,234 enregistrement(s) supprimé(s) (conservation: 90 jours)
```

---

### **Cas 2 : Aucun Enregistrement à Supprimer**

```
📋 Paramètres : conserver 90 jours
    ↓
🔍 Tous les enregistrements sont récents (< 90 jours)
    ↓
ℹ️ Aucune suppression nécessaire
    ↓
✅ Tâche terminée sans action
```

**Log produit :**
```
[info] Aucun enregistrement à supprimer (tous plus récents que 90 jours)
```

---

### **Cas 3 : Paramètres Invalides**

```
❌ days < 30 jours
    ↓
Exception levée
    ↓
Tâche marquée comme FAILED
```

**Erreur produite :**
```
La période minimum est de 30 jours pour des raisons de sécurité
```

---

## 🔐 Sécurité

### **Protection Minimum 30 Jours**

Pour éviter la suppression accidentelle de données récentes :

```php
if ($daysToKeep < 30) {
    throw new \InvalidArgumentException('Minimum 30 jours requis');
}
```

### **Politique de Rétention Recommandée**

| Environnement | Conservation | Raison |
|---------------|--------------|--------|
| **Production** | 90-180 jours | Conformité & audits |
| **Test** | 30-60 jours | Optimisation espace |
| **Développement** | 30 jours | Données minimales |

---

## 📊 Exemples de Configuration

### **Configuration Conservative (1 an)**

```json
{
  "days": 365
}
```

**Usage :** Environnements avec exigences légales strictes

---

### **Configuration Standard (3 mois)**

```json
{
  "days": 90
}
```

**Usage :** Configuration par défaut recommandée

---

### **Configuration Agressive (1 mois)**

```json
{
  "days": 30
}
```

**Usage :** Environnements avec peu d'espace disque

---

## 🔄 Planification Recommandée

### **Fréquence MONTHLY**

```
Exécution : 1er de chaque mois
Avantage : Nettoyage régulier sans surcharge
Idéal pour : Production
```

### **Fréquence WEEKLY**

```
Exécution : Chaque semaine
Avantage : Base de données toujours optimisée
Idéal pour : Environnements à haute activité
```

### **Fréquence ONCE**

```
Exécution : Une seule fois
Avantage : Nettoyage ponctuel
Idéal pour : Maintenance exceptionnelle
```

---

## 📈 Impact sur les Performances

### **Base de Données**

**Avant nettoyage (6 mois d'activité) :**
```
audit_log : 100,000 enregistrements
Taille : 50 MB
Requêtes : ~200ms
```

**Après nettoyage (90 jours) :**
```
audit_log : 15,000 enregistrements
Taille : 8 MB
Requêtes : ~30ms
```

**Gain :** -85% d'enregistrements, -84% de taille, -85% de temps de requête

---

## 🧪 Tests

### **Test 1 : Nettoyage Manuel**

```bash
# Créer des enregistrements de test anciens
# Puis exécuter :
php bin/console app:audit:cleanup --days=30

# Vérifier le résultat
```

### **Test 2 : Tâche Automatique**

```php
// Créer la tâche
$task = new Task();
$task->setType('AUDIT_CLEANUP')
     ->setParameters(['days' => 60])
     ->setFrequency('ONCE')
     ->setStatus('ACTIVE');

$em->persist($task);
$em->flush();

// Exécuter
$taskManager->executeTask($task);
```

### **Test 3 : Validation Minimum**

```bash
# Devrait échouer (< 30 jours)
php bin/console app:audit:cleanup --days=15

# Message attendu : "La période minimum est de 30 jours"
```

---

## 📊 Monitoring

### **Logs à Surveiller**

```
[info] ✅ Nettoyage terminé : X enregistrements supprimés
[info] Aucun enregistrement à supprimer
[error] ❌ Erreur lors du nettoyage : ...
```

### **Métriques à Suivre**

- Nombre d'enregistrements supprimés par exécution
- Taille de la table `audit_log`
- Temps d'exécution du nettoyage
- Fréquence de déclenchement

---

## ✅ Checklist

- [x] Tâche AUDIT_CLEANUP ajoutée au TaskManagerService
- [x] Méthode executeAuditCleanupTask() créée
- [x] Validation minimum 30 jours
- [x] Logs détaillés
- [x] Commande CLI disponible
- [x] Tâche par défaut créée (MONTHLY, 1er du mois, 90 jours)
- [x] Documentation complète

---

## 🎓 Résumé

La tâche **AUDIT_CLEANUP** permet de :
- ✅ Nettoyer automatiquement l'historique ancien
- ✅ Optimiser la base de données
- ✅ Respecter les politiques de rétention
- ✅ Maintenir les performances
- ✅ Logs détaillés de chaque nettoyage

**Accès :**
- CLI : `php bin/console app:audit:cleanup`
- Web : `/admin/audit/statistiques`
- Auto : Tâche planifiée (MONTHLY)

**Impact :** Maintenance automatisée et performances optimales ! 🧹✨

