# 🚀 Guide de démarrage rapide - MYLOCCA

## ⚡ Installation en 5 minutes

### Étape 1 : Base de données (si nécessaire)

Si la base de données n'existe pas ou est vide :

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Appliquer toutes les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Charger les données de test
php bin/console doctrine:fixtures:load --no-interaction
```

### Étape 2 : Créer l'administrateur

```bash
php bin/console app:create-user admin@mylocca.com admin123 Admin MYLOCCA --role=admin
```

### Étape 3 : Vider le cache

```bash
php bin/console cache:clear
```

### Étape 4 : Démarrer le serveur

```bash
php -S localhost:8000 -t public/
```

### Étape 5 : Se connecter

1. Ouvrez votre navigateur : http://localhost:8000/login
2. Email : `admin@mylocca.com`
3. Mot de passe : `admin123`
4. Cliquez sur "Se connecter"

---

## 🎯 Première utilisation

### Une fois connecté en tant qu'admin :

#### 1. Initialiser les devises
- Menu : **Administration > Paramètres > Devises**
- Cliquez sur **"Initialiser"** (si pas déjà fait)
- Cliquez sur **✓** pour définir EUR comme devise active

#### 2. Initialiser les tâches automatisées
- Menu : **Administration > Tâches**
- Cliquez sur **"Initialiser"**
- 4 tâches seront créées automatiquement

#### 3. Initialiser les templates d'emails
- Menu : **Administration > Templates d'emails**
- Cliquez sur **"Initialiser les templates"**
- 4 templates par défaut seront créés

#### 4. Configurer SMTP (optionnel)
- Menu : **Administration > Paramètres > Email**
- Remplissez les informations SMTP
- Testez via **Administration > Tâches**

---

## ❌ Résolution de l'erreur "Payment not found"

Cette erreur apparaît quand vous essayez d'accéder à un paiement qui n'existe pas.

### Solution : Charger les données de test

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

Cela créera :
- ✅ 2 propriétaires
- ✅ 4 propriétés
- ✅ 4 locataires
- ✅ 4 baux actifs
- ✅ 20+ paiements
- ✅ 10+ dépenses
- ✅ 8+ demandes de maintenance
- ✅ Documents de test

### Vérifier que les données sont chargées

Accédez à :
- `/` - Dashboard (devrait afficher des statistiques)
- `/biens` - Liste des biens (devrait montrer 4 biens)
- `/mes-paiements` - Paiements (devrait montrer les paiements)

---

## 🔧 Commandes utiles

### Base de données

```bash
# Créer la base
php bin/console doctrine:database:create

# Voir le statut des migrations
php bin/console doctrine:migrations:status

# Appliquer les migrations
php bin/console doctrine:migrations:migrate

# Réinitialiser complètement (⚠️ EFFACE TOUT)
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

### Utilisateurs

```bash
# Créer un admin
php bin/console app:create-user admin@mylocca.com admin123 Admin MYLOCCA --role=admin

# Créer un gestionnaire
php bin/console app:create-user manager@example.com password123 Jean Dupont --role=manager

# Créer un locataire
php bin/console app:create-user tenant@example.com password123 Marie Martin --role=tenant
```

### Tâches

```bash
# Exécuter les tâches dues
php bin/console app:tasks:run

# Envoyer les quittances
php bin/console app:send-rent-receipts --month=2025-10

# Simulation
php bin/console app:send-rent-receipts --dry-run
```

### Cache

```bash
# Vider le cache
php bin/console cache:clear

# Réchauffer le cache
php bin/console cache:warmup
```

### Debug

```bash
# Lister toutes les routes
php bin/console debug:router

# Voir une route spécifique
php bin/console debug:router app_payment_receipt_pdf

# Voir les services
php bin/console debug:container PdfService
```

---

## 📝 Comptes de test (après fixtures)

Si vous avez chargé les fixtures, vous pouvez vous connecter avec :

**Admin** (à créer manuellement) :
- Email : admin@mylocca.com
- Mot de passe : admin123

**Note** : Les fixtures créent des Tenants et Owners, mais PAS de comptes User associés. Vous devez les créer manuellement.

---

## 🎯 Tester les fonctionnalités

### 1. Dashboard
- `/` - Voir les statistiques

### 2. Gestion des biens
- `/biens` - Liste des propriétés
- Cliquer sur un bien pour voir les détails
- Télécharger un PDF de contrat ou d'échéancier

### 3. Paiements
- `/mes-paiements` - Historique
- Télécharger un reçu en PDF
- Télécharger une quittance mensuelle

### 4. Tâches (Admin)
- `/admin/taches` - Gérer les tâches
- Exécuter une tâche manuellement
- Envoyer un email de test

### 5. Templates email (Admin)
- `/admin/templates-email` - Personnaliser les emails
- Modifier un template
- Prévisualiser le rendu

### 6. Utilisateurs (Admin)
- `/admin/utilisateurs` - Gérer les utilisateurs
- Créer de nouveaux comptes
- Assigner des rôles

---

## 🐛 Problèmes courants

### "Invalid CSRF token"
✅ **Résolu** : CSRF désactivé temporairement dans security.yaml

### "Payment not found" / "Property not found"
**Solution** : Charger les fixtures
```bash
php bin/console doctrine:fixtures:load --no-interaction
```

### "Template not found"
**Solution** : Vider le cache
```bash
php bin/console cache:clear
```

### "Unable to connect to database"
**Solution** : Vérifier la configuration dans `.env` ou créer la base
```bash
php bin/console doctrine:database:create
```

### Erreur 500
**Solution** : Vérifier les logs
```bash
tail -f var/log/dev.log
```

---

## 📚 Documentation complète

Pour plus de détails, consultez :

1. **COMPLETE_SYSTEM_SUMMARY.md** - Vue d'ensemble complète
2. **TASK_MANAGER_README.md** - Tâches et notifications
3. **PDF_SERVICE_README.md** - Génération de PDFs
4. **EMAIL_CUSTOMIZATION_README.md** - Personnalisation emails
5. **AUTH_SYSTEM_README.md** - Authentification
6. **CURRENCY_USAGE.md** - Devises
7. **INSTALLATION_CHECKLIST.md** - Check-list complète

---

## ✅ Check-list de démarrage

- [ ] Base de données créée
- [ ] Migrations appliquées
- [ ] Fixtures chargées
- [ ] Admin créé (admin@mylocca.com)
- [ ] Cache vidé
- [ ] Serveur démarré
- [ ] Connexion réussie
- [ ] Devises initialisées
- [ ] Tâches initialisées
- [ ] Templates email initialisés
- [ ] SMTP configuré (optionnel)

---

## 🎉 Une fois tout configuré

Vous aurez accès à un système complet de gestion locative avec :
- 🏠 Gestion immobilière complète
- 👥 Multi-utilisateurs avec permissions
- 📄 Génération automatique de PDFs
- 📧 Emails personnalisables
- 💱 Multi-devises
- ⚙️ Tâches automatisées
- 📊 Tableaux de bord et statistiques

**Félicitations ! MYLOCCA est opérationnel !** 🚀

