# 🔧 Résolution du Problème de Migration

## 🐛 Erreur

```
SQLSTATE[42S22]: Column not found: 1054 
Champ 't17.organization_id' inconnu dans field list
```

## 🔍 Cause

Doctrine essaie d'utiliser les colonnes `organization_id` et `company_id` dans les requêtes, **MAIS** ces colonnes n'existent pas encore en base de données.

**Pourquoi ?**
Les migrations automatiques Symfony ont échoué parce que :
1. Certaines tables existent déjà
2. Certaines colonnes existent partiellement
3. MySQL n'a pas de `IF NOT EXISTS` pour `ALTER TABLE`

---

## ✅ SOLUTIONS (3 options)

### **Option 1 : SQL Manuel (RECOMMANDÉ - Le plus rapide)**

**Étape 1** : Ouvrir **phpMyAdmin** ou un client MySQL

**Étape 2** : Sélectionner la base de données `myloccahomz`

**Étape 3** : Exécuter ce script SQL (j'ai créé le fichier `setup_company_columns.sql`) :

```sql
-- Ajouter les colonnes (ignorer les erreurs si existent déjà)
ALTER TABLE property ADD organization_id INT DEFAULT NULL;
ALTER TABLE property ADD company_id INT DEFAULT NULL;

ALTER TABLE tenant ADD organization_id INT DEFAULT NULL;
ALTER TABLE tenant ADD company_id INT DEFAULT NULL;

ALTER TABLE lease ADD organization_id INT DEFAULT NULL;
ALTER TABLE lease ADD company_id INT DEFAULT NULL;

ALTER TABLE payment ADD organization_id INT DEFAULT NULL;
ALTER TABLE payment ADD company_id INT DEFAULT NULL;

ALTER TABLE user ADD company_id INT DEFAULT NULL;

ALTER TABLE expense ADD organization_id INT DEFAULT NULL;
ALTER TABLE expense ADD company_id INT DEFAULT NULL;
```

**Étape 4** : Rafraîchir la page - ✅ Ça devrait fonctionner !

---

### **Option 2 : Commande MySQL directe**

```bash
mysql -u root -p myloccahomz < setup_company_columns.sql
```

---

### **Option 3 : Script PHP Manuel**

Créer et exécuter ce fichier `setup_db.php` :

```php
<?php
require 'vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$params = [
    'dbname' => 'myloccahomz',
    'user' => 'root',
    'password' => '',
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
];

$conn = DriverManager::getConnection($params);

$tables = ['property', 'tenant', 'lease', 'payment', 'expense'];

foreach ($tables as $table) {
    try {
        $conn->executeStatement("ALTER TABLE {$table} ADD organization_id INT DEFAULT NULL");
        echo "✅ {$table}.organization_id créé\n";
    } catch (\Exception $e) {
        echo "ℹ️  {$table}.organization_id existe déjà\n";
    }
    
    try {
        $conn->executeStatement("ALTER TABLE {$table} ADD company_id INT DEFAULT NULL");
        echo "✅ {$table}.company_id créé\n";
    } catch (\Exception $e) {
        echo "ℹ️  {$table}.company_id existe déjà\n";
    }
}

// User (juste company_id)
try {
    $conn->executeStatement("ALTER TABLE user ADD company_id INT DEFAULT NULL");
    echo "✅ user.company_id créé\n";
} catch (\Exception $e) {
    echo "ℹ️  user.company_id existe déjà\n";
}

echo "\n✅ Terminé !\n";
```

Puis exécuter :
```bash
php setup_db.php
```

---

## 🎯 APRÈS la Correction

Une fois les colonnes créées, vous pourrez :

1. ✅ Aller sur `/inscription/plans`
2. ✅ S'inscrire au plan Freemium
3. ✅ Une **Organization** sera créée
4. ✅ Une **Company** (siège social) sera créée automatiquement
5. ✅ Un **User ROLE_ADMIN** sera créé
6. ✅ Une **Subscription ACTIVE** sera créée

---

## 📊 Vérification après correction

```sql
-- Vérifier que les colonnes existent
SHOW COLUMNS FROM property;
-- Devrait afficher organization_id et company_id

-- Vérifier que la table company existe
SELECT * FROM company;
-- Devrait être vide au début

-- Après inscription
SELECT * FROM organization;
SELECT * FROM company;
SELECT * FROM subscription;
SELECT * FROM user WHERE roles LIKE '%ADMIN%';
```

---

## 💡 Pourquoi ce problème ?

Les migrations Symfony/Doctrine sont **sensibles** :
- Elles ne peuvent pas gérer les cas où des colonnes existent partiellement
- MySQL n'a pas de `IF NOT EXISTS` pour tous les `ALTER TABLE`
- Les contraintes FK échouent s'il y a des données existantes

**Solution la plus simple = SQL manuel pour ajouter les colonnes** ✅

---

## ✅ SOLUTION RECOMMANDÉE

**Exécutez ce SQL via phpMyAdmin** :

```sql
ALTER TABLE property ADD organization_id INT DEFAULT NULL;
ALTER TABLE property ADD company_id INT DEFAULT NULL;
ALTER TABLE tenant ADD organization_id INT DEFAULT NULL;
ALTER TABLE tenant ADD company_id INT DEFAULT NULL;
ALTER TABLE lease ADD organization_id INT DEFAULT NULL;
ALTER TABLE lease ADD company_id INT DEFAULT NULL;
ALTER TABLE payment ADD organization_id INT DEFAULT NULL;
ALTER TABLE payment ADD company_id INT DEFAULT NULL;
ALTER TABLE user ADD company_id INT DEFAULT NULL;
ALTER TABLE expense ADD organization_id INT DEFAULT NULL;
ALTER TABLE expense ADD company_id INT DEFAULT NULL;
```

**Puis** :
```bash
php bin/console cache:clear
```

**Ensuite testez l'inscription !** 🚀

