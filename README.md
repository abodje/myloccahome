# 🏠 Système de Gestion Immobilière

Une application web complète pour la gestion de propriétés immobilières, développée avec Symfony 7.3.

## ✨ Fonctionnalités

### 🏢 Gestion des Propriétés
- **Inventaire complet** : Ajout, modification et suivi de toutes vos propriétés
- **Informations détaillées** : Surface, nombre de pièces, équipements, classe énergétique
- **Gestion financière** : Loyers, charges, dépôts de garantie
- **Statuts dynamiques** : Disponible, occupé, en travaux, indisponible

### 👥 Gestion des Locataires
- **Profils complets** : Informations personnelles et professionnelles
- **Historique de location** : Suivi de tous les contrats passés et actuels
- **Contacts d'urgence** : Informations pour les situations critiques
- **Statuts** : Actif, inactif, liste noire

### 📋 Contrats de Location
- **Création assistée** : Formulaires guidés pour nouveaux contrats
- **Gestion des échéances** : Suivi des dates de fin et renouvellements
- **Conditions spéciales** : Clauses particulières personnalisables
- **Résiliation et renouvellement** : Workflows automatisés

### 💰 Gestion des Paiements
- **Suivi des loyers** : Génération automatique des échéances
- **Modes de paiement** : Virement, chèque, espèces, prélèvement
- **Pénalités de retard** : Calcul automatique des frais
- **Rapports financiers** : Revenus mensuels et annuels

### 🔧 Maintenance & Interventions
- **Signalement** : Interface simple pour reporter les problèmes
- **Priorisation** : Urgente, haute, normale, basse
- **Suivi des coûts** : Devis et factures
- **Calendrier** : Planning des interventions

### 📊 Tableau de Bord & Rapports
- **Vue d'ensemble** : Statistiques temps réel
- **Alertes automatiques** : Contrats expirants, paiements en retard
- **Graphiques** : Évolution des revenus, répartition du parc
- **Activité récente** : Historique des dernières actions

### 🔐 Sécurité & Authentification
- **Connexion sécurisée** : Système d'authentification Symfony
- **Rôles utilisateurs** : Admin et utilisateur standard
- **Protection CSRF** : Sécurisation des formulaires

## 🚀 Installation

### Prérequis
- PHP 8.2 ou supérieur
- Composer
- PostgreSQL 16+
- Symfony CLI (optionnel)

### Installation pas à pas

1. **Cloner le projet**
```bash
git clone https://github.com/votre-repo/gestion-immobiliere.git
cd gestion-immobiliere
```

2. **Installer les dépendances**
```bash
composer install
```

3. **Configuration de la base de données**
```bash
# Copier le fichier d'environnement
cp .env .env.local

# Éditer .env.local et configurer votre base de données
# DATABASE_URL="postgresql://user:password@127.0.0.1:5432/gestion_immobiliere"
```

4. **Créer et initialiser la base de données**
```bash
# Créer la base de données
php bin/console doctrine:database:create

# Ou exécuter le fichier SQL fourni
psql -U username -d gestion_immobiliere -f migrations/schema.sql
```

5. **Créer un utilisateur administrateur**
```bash
php bin/console app:create-user admin@example.com password Admin Système --admin
```

6. **Démarrer le serveur de développement**
```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

7. **Accéder à l'application**
Ouvrez votre navigateur et allez sur `http://localhost:8000`

## 👤 Comptes de démonstration

L'application est livrée avec des données de démonstration :

| Rôle | Email | Mot de passe | Description |
|------|-------|--------------|-------------|
| Admin | admin@demo.com | password | Accès complet à toutes les fonctionnalités |
| Utilisateur | user@demo.com | password | Accès aux fonctionnalités standard |

## 🎯 Utilisation

### Premier pas
1. Connectez-vous avec un compte administrateur
2. Ajoutez vos premières propriétés via le menu "Propriétés"
3. Enregistrez vos locataires dans "Locataires"
4. Créez des contrats de location
5. Configurez les échéances de paiement

### Workflow typique
1. **Nouvelle propriété** → Créer la fiche propriété
2. **Nouveau locataire** → Enregistrer les informations du locataire
3. **Contrat de location** → Lier propriété et locataire
4. **Génération des échéances** → Créer automatiquement les paiements
5. **Suivi quotidien** → Tableau de bord et alertes

## 📁 Structure du projet

```
src/
├── Controller/          # Contrôleurs Symfony
│   ├── DashboardController.php
│   ├── PropertyController.php
│   ├── TenantController.php
│   ├── RentalContractController.php
│   ├── PaymentController.php
│   ├── MaintenanceController.php
│   └── SecurityController.php
├── Entity/             # Entités Doctrine
│   ├── Property.php
│   ├── Tenant.php
│   ├── RentalContract.php
│   ├── Payment.php
│   ├── Maintenance.php
│   └── User.php
├── Form/               # Formulaires Symfony
├── Repository/         # Repositories Doctrine
└── Command/           # Commandes CLI

templates/
├── base.html.twig     # Template de base
├── dashboard/         # Tableau de bord
├── property/          # Gestion des propriétés
├── tenant/           # Gestion des locataires
├── contract/         # Gestion des contrats
├── payment/          # Gestion des paiements
├── maintenance/      # Gestion de la maintenance
└── security/         # Authentification
```

## 🔧 Configuration

### Base de données
Modifiez le fichier `.env.local` pour configurer votre base de données :

```env
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/gestion_immobiliere?serverVersion=16&charset=utf8"
```

### Email (optionnel)
Pour l'envoi d'emails (notifications, rappels) :

```env
MAILER_DSN=smtp://user:pass@smtp.example.com:587
```

### Sécurité
Générez une clé secrète forte :

```env
APP_SECRET=your-secret-key-here
```

## 🎨 Personnalisation

### Thème
L'interface utilise Bootstrap 5 avec un thème personnalisé. Modifiez les variables CSS dans `templates/base.html.twig` :

```css
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --success-color: #27ae60;
    /* ... */
}
```

### Fonctionnalités
- Ajoutez de nouveaux champs aux entités
- Créez des rapports personnalisés
- Intégrez des services externes (API bancaire, etc.)

## 📊 Fonctionnalités avancées

### Rapports et Exports
- Revenus par période
- État des lieux automatisé
- Export Excel/PDF
- Statistiques détaillées

### Notifications
- Contrats arrivant à échéance
- Paiements en retard
- Maintenances urgentes
- Rappels automatiques

### Intégrations possibles
- Services bancaires (import des virements)
- Calendriers externes (Google Calendar)
- Services de communication (SMS, Email)
- Solutions de signature électronique

## 🐛 Dépannage

### Problèmes courants

**Erreur de base de données**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

**Erreur de permissions**
```bash
chmod -R 755 var/
chown -R www-data:www-data var/
```

**Cache non mis à jour**
```bash
php bin/console cache:clear
```

## 🤝 Contribution

1. Fork le projet
2. Créez une branche feature (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 🆘 Support

- **Documentation** : [Wiki du projet](https://github.com/votre-repo/wiki)
- **Issues** : [GitHub Issues](https://github.com/votre-repo/issues)
- **Discussions** : [GitHub Discussions](https://github.com/votre-repo/discussions)

## 🔮 Roadmap

### Version 2.0
- [ ] API REST complète
- [ ] Application mobile
- [ ] Intégration comptabilité
- [ ] Multi-propriétaires
- [ ] Système de notifications push

### Version 1.5
- [ ] Import/Export avancé
- [ ] Modèles de contrats
- [ ] Signature électronique
- [ ] Rapports avancés

---

**Développé avec ❤️ et Symfony**

Pour toute question ou suggestion, n'hésitez pas à ouvrir une issue !