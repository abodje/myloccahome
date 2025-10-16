# 💾 Système de Sauvegardes Automatiques - MYLOCCA

## 🎯 Vue d'ensemble

Le **Système de Sauvegardes Automatiques** protège vos données en créant régulièrement des copies de sécurité de votre base de données et de vos fichiers importants.

---

## ✅ Fonctionnalités Implémentées

### **1. Service BackupService**

**Fichier :** `src/Service/BackupService.php`

**Méthodes principales :**

| Méthode | Description |
|---------|-------------|
| `createFullBackup()` | Sauvegarde complète (BDD + fichiers) |
| `backupDatabase()` | Sauvegarde base de données (mysqldump) |
| `backupFiles()` | Sauvegarde fichiers (tar.gz ou zip) |
| `listBackups()` | Liste toutes les sauvegardes |
| `cleanOldBackups()` | Supprime les anciennes sauvegardes |
| `deleteBackup()` | Supprime une sauvegarde spécifique |
| `getBackupStatistics()` | Statistiques des sauvegardes |

---

### **2. Commande CLI**

**Fichier :** `src/Command/BackupCommand.php`

**Usage :**
```bash
# Sauvegarde complète
php bin/console app:backup

# Sauvegarde base de données uniquement
php bin/console app:backup --database-only

# Sauvegarde fichiers uniquement
php bin/console app:backup --files-only

# Avec nettoyage automatique
php bin/console app:backup --clean=30
```

---

### **3. Tâche Automatisée**

**Type :** `BACKUP`

**Configuration par défaut :**
- Fréquence : DAILY (quotidien)
- Heure : 2h du matin
- Nettoyage auto : Oui
- Rétention : 30 jours

**Paramètres :**
```json
{
  "hour": 2,
  "clean_old": true,
  "keep_days": 30
}
```

---

### **4. Interface Web**

**Route :** `/admin/sauvegardes`

**Fonctionnalités :**
- ✅ Liste des sauvegardes
- ✅ Création manuelle
- ✅ Téléchargement (BDD + Fichiers)
- ✅ Suppression
- ✅ Nettoyage automatique
- ✅ Statistiques

---

## 📦 Contenu des Sauvegardes

### **Base de Données**

**Fichier :** `database_YYYY-MM-DD_HH-mm-ss.sql.gz`

**Contenu :**
- Dump complet de la base MySQL
- Toutes les tables
- Toutes les données
- Structure + données

**Méthode :**
- Principal : `mysqldump` (rapide et fiable)
- Fallback : Export PHP (si mysqldump non disponible)

**Compression :** GZIP (.gz) - Économise ~70% d'espace

---

### **Fichiers**

**Fichier :** `files_YYYY-MM-DD_HH-mm-ss.tar.gz` ou `.zip`

**Dossiers sauvegardés :**
- `public/uploads/` - Documents, photos, PDFs
- `config/` - Fichiers de configuration
- `.env.local` - Variables d'environnement

**Méthode :**
- Principal : `tar -czf` (compression optimale)
- Fallback : ZIP PHP (si tar non disponible)

---

### **Manifest**

**Fichier :** `manifest_YYYY-MM-DD_HH-mm-ss.json`

**Contenu :**
```json
{
  "timestamp": "2024-10-14_02-00-00",
  "date": "2024-10-14 02:00:00",
  "database": {
    "file": "database_2024-10-14_02-00-00.sql.gz",
    "size": 1048576,
    "compressed": true
  },
  "files": {
    "file": "files_2024-10-14_02-00-00.tar.gz",
    "size": 5242880,
    "count": 3
  },
  "php_version": "8.1.0",
  "symfony_version": "6.3"
}
```

---

## 🚀 Utilisation

### **Méthode 1 : Interface Web (Recommandé)**

1. Accédez à `/admin/sauvegardes`
2. Cliquez sur "Créer une sauvegarde"
3. Attendez quelques secondes
4. ✅ Sauvegarde créée !

**Pour télécharger :**
- Cliquez sur <i class="bi bi-database"></i> pour la base de données
- Cliquez sur <i class="bi bi-folder"></i> pour les fichiers

---

### **Méthode 2 : Commande CLI**

```bash
# Sauvegarde complète
php bin/console app:backup

# Avec statistiques
php bin/console app:backup -v

# Sauvegarde + nettoyage
php bin/console app:backup --clean=30
```

**Sortie :**
```
💾 Sauvegarde MYLOCCA
═════════════════════

📦 Création de la sauvegarde
───────────────────────────
Timestamp : 2024-10-14_14-30-00

📊 Sauvegarde de la base de données... ✅ Terminé
   📁 Fichier : database_2024-10-14_14-30-00.sql.gz (2.5 MB)

📁 Sauvegarde des fichiers... ✅ Terminé
   📁 Fichier : files_2024-10-14_14-30-00.tar.gz (15 MB)

✅ Sauvegarde créée avec succès !
```

---

### **Méthode 3 : Tâche Automatique (Recommandé pour Production)**

La tâche `BACKUP` est créée automatiquement et s'exécute **quotidiennement à 2h du matin**.

**Pour modifier :**
1. Accédez à l'interface de gestion des tâches
2. Trouvez "Sauvegarde automatique"
3. Modifiez les paramètres :
   - `hour` : Heure d'exécution
   - `keep_days` : Nombre de jours à conserver

---

## 📁 Emplacement des Sauvegardes

```
mylocca/
└── var/
    └── backups/
        ├── database_2024-10-14_02-00-00.sql.gz
        ├── files_2024-10-14_02-00-00.tar.gz
        ├── manifest_2024-10-14_02-00-00.json
        ├── database_2024-10-13_02-00-00.sql.gz
        ├── files_2024-10-13_02-00-00.tar.gz
        └── ...
```

---

## 🔄 Restauration

### **Restaurer la Base de Données**

```bash
# 1. Décompresser
gunzip var/backups/database_2024-10-14_02-00-00.sql.gz

# 2. Restaurer
mysql -u root -p mylocca < var/backups/database_2024-10-14_02-00-00.sql
```

### **Restaurer les Fichiers**

```bash
# Décompresser et extraire
cd /path/to/mylocca
tar -xzf var/backups/files_2024-10-14_02-00-00.tar.gz
```

**⚠️ ATTENTION :** La restauration écrase les fichiers existants !

---

## 🧹 Nettoyage Automatique

### **Politique par Défaut**

- Conserver : 30 derniers jours
- Nettoyage : Automatique lors de chaque sauvegarde

### **Depuis l'Interface**

1. Accédez à `/admin/sauvegardes`
2. Section "Nettoyage Automatique"
3. Sélectionnez la période à conserver
4. Cliquez sur "Nettoyer"

### **Via CLI**

```bash
php bin/console app:backup --clean=30
```

---

## ⚙️ Configuration

### **Variables d'Environnement Requises**

```env
# .env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/mylocca?serverVersion=8.0"
```

### **Permissions Requises**

Le serveur web doit avoir :
- ✅ Accès en écriture à `var/backups/`
- ✅ Accès à `mysqldump` (optionnel mais recommandé)
- ✅ Accès à `tar` (optionnel mais recommandé)

### **Vérifier les Outils**

```bash
# Vérifier mysqldump
which mysqldump
mysqldump --version

# Vérifier tar
which tar
tar --version
```

---

## 📊 Statistiques

### **Page Sauvegardes**

Affiche :
- 📊 Nombre total de sauvegardes
- 💾 Espace disque utilisé
- 📅 Date de la dernière sauvegarde
- 📋 Liste détaillée avec tailles

---

## 🔐 Sécurité

### **Protections**

- ✅ Accès réservé aux ROLE_ADMIN
- ✅ Protection CSRF sur suppressions
- ✅ Confirmation avant nettoyage
- ✅ Logs de toutes les actions

### **Recommandations**

1. **Protéger le dossier backups**
   ```apache
   # .htaccess dans var/backups/
   Deny from all
   ```

2. **Chiffrement (optionnel)**
   ```bash
   # Chiffrer une sauvegarde
   gpg -c database_2024-10-14.sql.gz
   ```

3. **Copie externe automatique**
   ```bash
   # Exemple rsync vers serveur distant
   rsync -avz var/backups/ user@backup-server:/backups/mylocca/
   ```

---

## 🎯 Cas d'Usage

### **Cas 1 : Sauvegarde Avant Mise à Jour**

```bash
# Avant de faire une mise à jour
php bin/console app:backup
# Puis faire la mise à jour
```

### **Cas 2 : Sauvegarde Quotidienne Automatique**

La tâche `BACKUP` s'exécute automatiquement tous les jours à 2h.

### **Cas 3 : Copie vers Cloud**

```bash
# Créer la sauvegarde
php bin/console app:backup

# Copier vers AWS S3 (exemple)
aws s3 cp var/backups/ s3://mon-bucket/mylocca-backups/ --recursive

# Ou vers Google Cloud Storage
gsutil -m cp -r var/backups/* gs://mon-bucket/mylocca-backups/
```

---

## 🧪 Tests

### **Test 1 : Sauvegarde Manuelle**

```bash
php bin/console app:backup

# Vérifier les fichiers créés
ls -lh var/backups/
```

### **Test 2 : Interface Web**

```bash
1. Accédez à /admin/sauvegardes
2. Cliquez sur "Créer une sauvegarde"
3. Vérifiez qu'elle apparaît dans la liste
4. Téléchargez-la
5. Supprimez-la
```

### **Test 3 : Restauration**

```bash
# Créer une sauvegarde de test
php bin/console app:backup

# Modifier quelques données
# Puis restaurer
gunzip var/backups/database_XXX.sql.gz
mysql -u root -p mylocca < var/backups/database_XXX.sql

# Vérifier que les données sont restaurées
```

---

## 📋 Checklist

- [x] Service BackupService créé
- [x] Commande app:backup créée
- [x] Tâche BACKUP ajoutée
- [x] Contrôleur BackupController créé
- [x] Template interface créé
- [x] Documentation complète
- [ ] Dossier var/backups créé (auto)
- [ ] Tests effectués
- [ ] Sauvegarde externe configurée (optionnel)
- [ ] Lien ajouté dans menu admin

---

## 🎓 Résumé

Le système de sauvegardes offre :
- ✅ Sauvegarde automatique quotidienne
- ✅ Sauvegarde manuelle (web + CLI)
- ✅ Base de données (mysqldump)
- ✅ Fichiers importants (tar.gz/zip)
- ✅ Compression automatique
- ✅ Nettoyage automatique
- ✅ Interface de gestion
- ✅ Statistiques
- ✅ Téléchargement facile

**Vos données sont maintenant protégées ! 💾🔐**

