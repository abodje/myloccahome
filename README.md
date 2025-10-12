# 🏠 MYLOCCA - Système de Gestion Locative Professionnel

![Version](https://img.shields.io/badge/version-2.6-blue.svg)
![Status](https://img.shields.io/badge/status-production%20ready-green.svg)
![PHP](https://img.shields.io/badge/PHP-8.x-777BB4.svg)
![Symfony](https://img.shields.io/badge/Symfony-7.x-000000.svg)

> Application complète de gestion locative avec automatisation, notifications, PDFs et multi-devises

---

## 🎯 Fonctionnalités principales

### 🏠 Gestion immobilière
- Propriétés, locataires, baux
- Paiements et comptabilité
- Demandes de maintenance
- Documents organisés

### ⚙️ Automatisation
- **Tâches programmées** (CRON)
- **Génération auto de contrats** après paiement caution
- **Envoi auto de quittances**
- **Rappels de paiement**
- **Génération mensuelle des loyers**

### 📄 Génération de PDFs
- Contrats de bail professionnels
- Reçus de paiement
- Quittances mensuelles (conformes loi française)
- Échéanciers personnalisables

### 📧 Notifications
- Templates emails personnalisables
- 60+ variables dynamiques
- Envois automatiques programmés
- Prévisualisation temps réel

### 💱 Multi-devises
- EUR, USD, GBP, CHF, CAD
- Changement instantané partout
- Taux de change actualisables

### 🔐 Sécurité
- Authentification Symfony
- 3 niveaux : Admin, Manager, Tenant
- Menu adaptatif par rôle
- Permissions granulaires

---

## 🚀 Installation rapide

### 1. Prérequis
- PHP 8.1+
- MySQL/MariaDB
- Composer
- Symfony CLI (optionnel)

### 2. Installation

```bash
# Cloner le projet
git clone [URL] mylocca
cd mylocca

# Installer les dépendances
composer install

# Configurer la base de données (.env)
DATABASE_URL="mysql://user:password@127.0.0.1:3306/myloccahomz"

# Créer la base et appliquer les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# (Optionnel) Charger des données de test
php bin/console doctrine:fixtures:load

# Créer un administrateur
php bin/console app:create-user admin@mylocca.com admin123 Admin MYLOCCA --role=admin

# Démarrer le serveur
php -S localhost:8000 -t public/
```

### 3. Première connexion

- **URL** : http://localhost:8000/login
- **Email** : admin@mylocca.com
- **Mot de passe** : admin123

### 4. Configuration initiale

1. **Devises** : `/admin/parametres/devises` → Initialiser
2. **Tâches** : `/admin/taches` → Initialiser
3. **Templates email** : `/admin/templates-email` → Initialiser
4. **Entreprise** : `/admin/parametres/application` → Remplir vos infos
5. **SMTP** : `/admin/parametres/email` → Configurer (optionnel)

---

## 📚 Documentation

| Document | Description |
|----------|-------------|
| [QUICK_START_GUIDE.md](QUICK_START_GUIDE.md) | Guide de démarrage rapide |
| [COMPLETE_SYSTEM_SUMMARY.md](COMPLETE_SYSTEM_SUMMARY.md) | Vue d'ensemble complète |
| [TASK_MANAGER_README.md](TASK_MANAGER_README.md) | Tâches automatisées |
| [PDF_SERVICE_README.md](PDF_SERVICE_README.md) | Génération de PDFs |
| [EMAIL_CUSTOMIZATION_README.md](EMAIL_CUSTOMIZATION_README.md) | Personnalisation emails |
| [AUTH_SYSTEM_README.md](AUTH_SYSTEM_README.md) | Authentification |
| [CURRENCY_USAGE.md](CURRENCY_USAGE.md) | Utilisation des devises |
| [AUTO_CONTRACT_GENERATION.md](AUTO_CONTRACT_GENERATION.md) | Contrats automatiques |
| [GENERATE_RENTS_COMMAND.md](GENERATE_RENTS_COMMAND.md) | Génération de loyers |

---

## 🎯 Workflow complet

### Nouveau locataire

1. **Créer le locataire** → Compte utilisateur créé automatiquement
2. **Créer le bail** → Enregistré avec toutes les conditions
3. **Payer la caution** → **Contrat PDF généré automatiquement !**
4. **Générer les loyers** → 6 mois créés en 1 clic
5. **Automatisation** → Quittances et rappels envoyés auto

---

## 🛠️ Commandes console

```bash
# Tâches automatisées
php bin/console app:tasks:run

# Envoyer les quittances
php bin/console app:send-rent-receipts --month=2025-10

# Générer les loyers
php bin/console app:generate-rents --months-ahead=3

# Créer un utilisateur
php bin/console app:create-user email@example.com password Prénom Nom --role=admin

# Cache
php bin/console cache:clear
```

---

## ⚙️ Configuration CRON (Production)

```bash
# Exécuter les tâches toutes les heures
0 * * * * cd /path/to/mylocca && php bin/console app:tasks:run

# Générer les loyers le 25 de chaque mois
0 9 25 * * cd /path/to/mylocca && php bin/console app:generate-rents
```

---

## 📊 Technologies

- **Backend** : Symfony 7.x, PHP 8.x
- **Base de données** : Doctrine ORM (MySQL/MariaDB)
- **Frontend** : Bootstrap 5, Twig
- **PDF** : Dompdf 3.1.2
- **Email** : Symfony Mailer
- **Charts** : Chart.js
- **Icons** : Bootstrap Icons

---

## 🎨 Captures d'écran

### Dashboard
Vue d'ensemble avec statistiques en temps réel, graphiques et activité récente.

### Gestion des biens
Liste complète des propriétés avec statuts, filtres et actions rapides.

### Paiements
Historique détaillé, génération de quittances, reçus PDF téléchargeables.

### Administration
Menu complet : Tâches, Templates emails, Utilisateurs, Paramètres.

---

## 🔒 Sécurité

- Authentification Symfony Security
- Hash bcrypt/argon2 pour mots de passe
- Protection des routes par rôles
- CSRF (à réactiver en production)
- Validation des données

---

## 📈 Statistiques du projet

- **12 entités** Doctrine
- **8 services** métier
- **15+ contrôleurs**
- **4 commandes console**
- **4 extensions Twig**
- **80+ templates**
- **100+ routes**
- **15 fichiers de documentation**

---

## 🎉 Fonctionnalités uniques

✅ **Génération auto de contrats** après paiement caution  
✅ **Création auto de comptes** pour locataires  
✅ **Templates emails** entièrement personnalisables  
✅ **Multi-devises** avec changement instantané  
✅ **Génération de loyers** respectant la fin du bail  
✅ **Menu adaptatif** selon les permissions  
✅ **Tâches automatisées** programmables  

---

## 👥 Rôles et permissions

### ROLE_ADMIN
Accès complet : Gestion, Administration, Paramètres, Utilisateurs

### ROLE_MANAGER
Gestion de ses biens, locataires et contrats uniquement

### ROLE_TENANT
Consultation de son bail, paiements, documents, demandes

---

## 📦 Structure du projet

```
mylocca/
├── src/
│   ├── Entity/          # 12 entités (Property, Tenant, Lease, Payment, etc.)
│   ├── Controller/      # 15+ contrôleurs
│   ├── Service/         # 8 services
│   ├── Repository/      # 14 repositories
│   ├── Command/         # 4 commandes console
│   ├── Form/            # Formulaires Symfony
│   └── Twig/            # 4 extensions Twig
├── templates/
│   ├── base.html.twig   # Layout principal
│   ├── dashboard/       # Tableaux de bord
│   ├── property/        # Propriétés
│   ├── tenant/          # Locataires
│   ├── lease/           # Baux
│   ├── payment/         # Paiements
│   ├── document/        # Documents
│   ├── admin/           # Administration
│   ├── emails/          # Templates emails
│   ├── pdf/             # Templates PDFs
│   └── security/        # Authentification
├── config/              # Configuration Symfony
├── public/
│   └── uploads/
│       └── documents/   # Documents générés
└── migrations/          # Migrations BDD
```

---

## 🚀 Déploiement

### Environnement de production

1. Modifier `.env` : `APP_ENV=prod`
2. Optimiser : `composer install --no-dev --optimize-autoloader`
3. Vider cache : `php bin/console cache:clear --env=prod`
4. Configurer CRON pour les tâches automatisées
5. Configurer serveur web (Apache/Nginx)
6. Activer HTTPS
7. Backup automatique BDD

---

## 📞 Support

- Documentation complète dans les fichiers .md
- Logs : `var/log/dev.log` ou `var/log/prod.log`
- Issues : GitHub (si configuré)

---

## 📜 Licence

Propriétaire - Tous droits réservés

---

## 🙏 Remerciements

Développé avec Symfony, Bootstrap et des bibliothèques open-source.

---

## 🎊 Status

**✅ Projet 100% terminé et opérationnel**  
**✅ Prêt pour la production**  
**✅ Documentation complète**

**Bon développement avec MYLOCCA !** 🚀

---

*Version 2.6 - 11 Octobre 2025*
