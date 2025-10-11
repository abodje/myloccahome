# 🏠 Logiciel de Gestion Locative

Un système complet de gestion de biens immobiliers locatifs développé avec Symfony 7.3 et Bootstrap 5.

## 📋 Fonctionnalités

### 🏢 Gestion des Propriétés
- ✅ Ajout, modification et suppression de propriétés
- ✅ Gestion des informations détaillées (adresse, surface, loyer, etc.)
- ✅ Suivi du statut des biens (Libre, Occupé, En travaux)
- ✅ Recherche et filtrage par ville et statut

### 👥 Gestion des Locataires  
- ✅ Profils complets des locataires
- ✅ Informations personnelles et professionnelles
- ✅ Contacts d'urgence
- ✅ Historique des locations

### 📄 Gestion des Contrats de Location
- ✅ Création de contrats avec dates de début/fin
- ✅ Gestion des loyers et charges
- ✅ Suivi des cautions
- ✅ Contrats à durée déterminée ou indéterminée

### 💰 Gestion des Paiements
- ✅ Suivi des loyers mensuels
- ✅ Génération automatique des échéances
- ✅ Gestion des retards de paiement
- ✅ Historique des paiements par locataire/propriété

### 🧾 Gestion des Dépenses
- ✅ Enregistrement des frais par propriété
- ✅ Catégorisation des dépenses (Réparations, Entretien, etc.)
- ✅ Suivi des factures et fournisseurs
- ✅ Statistiques par catégorie

### 📊 Tableau de Bord
- ✅ Vue d'ensemble des revenus et dépenses
- ✅ Alertes pour les paiements en retard
- ✅ Contrats se terminant bientôt
- ✅ Statistiques globales du parc immobilier

## 🚀 Installation

### Prérequis
- PHP 8.2 ou supérieur
- Composer
- Base de données (SQLite par défaut)

### Étapes d'installation

1. **Cloner le repository**
```bash
git clone <repository-url>
cd gestion-locative
```

2. **Installer les dépendances**
```bash
composer install
```

3. **Configurer la base de données**
```bash
# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate
```

4. **Démarrer le serveur de développement**
```bash
symfony serve
# ou
php -S localhost:8000 -t public/
```

5. **Accéder à l'application**
Ouvrir votre navigateur à l'adresse : `http://localhost:8000`

## 🛠️ Structure du Projet

```
src/
├── Controller/          # Contrôleurs pour chaque module
├── Entity/             # Entités Doctrine (Property, Tenant, Lease, Payment, Expense)
├── Repository/         # Repositories avec requêtes personnalisées
└── ...

templates/
├── base.html.twig      # Template de base avec sidebar
├── dashboard/          # Templates du tableau de bord
├── property/           # Templates de gestion des propriétés
├── tenant/             # Templates de gestion des locataires
├── lease/              # Templates de gestion des contrats
├── payment/            # Templates de gestion des paiements
└── expense/            # Templates de gestion des dépenses

migrations/             # Migrations de base de données
```

## 📱 Interface Utilisateur

L'interface est développée avec **Bootstrap 5** et **Font Awesome** pour une expérience moderne et responsive :

- 🎨 Design moderne avec dégradés colorés
- 📱 Interface responsive (mobile, tablette, desktop)
- 🔍 Recherche et filtres intuitifs
- 📊 Cartes de statistiques interactives
- ⚡ Actions rapides depuis le tableau de bord

## 🔧 Technologies Utilisées

- **Backend :** Symfony 7.3 (PHP 8.2+)
- **Base de données :** Doctrine ORM avec SQLite
- **Frontend :** Bootstrap 5.3, Font Awesome 6.5
- **Architecture :** MVC avec Repository Pattern

## 📋 Utilisation

### 1. Ajouter une propriété
1. Aller dans **Propriétés** > **Nouvelle propriété**
2. Remplir les informations (adresse, type, loyer, etc.)
3. Sauvegarder

### 2. Créer un locataire
1. Aller dans **Locataires** > **Nouveau locataire** 
2. Saisir les informations personnelles
3. Ajouter les contacts d'urgence si nécessaire

### 3. Établir un contrat de location
1. Aller dans **Contrats** > **Nouveau contrat**
2. Sélectionner la propriété et le locataire
3. Définir les dates et conditions
4. Générer automatiquement les échéances de loyer

### 4. Gérer les paiements
1. Les échéances sont générées automatiquement
2. Marquer les paiements comme "Payé" quand reçus
3. Suivre les retards depuis le tableau de bord

### 5. Enregistrer les dépenses
1. Aller dans **Dépenses** > **Nouvelle dépense**
2. Associer à une propriété ou marquer comme "Générale"
3. Catégoriser pour les statistiques

## 🔒 Sécurité

- Protection CSRF sur tous les formulaires
- Validation des données côté serveur
- Sanitisation des entrées utilisateur
- Gestion sécurisée des fichiers

## 📈 Évolutions Futures

- [ ] Système d'authentification multi-utilisateurs
- [ ] Export PDF des contrats et quittances
- [ ] Notifications par email automatiques
- [ ] Module de maintenance préventive
- [ ] API REST pour intégrations externes
- [ ] Application mobile

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Committer vos changements
4. Pousser vers la branche
5. Créer une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 📞 Support

Pour toute question ou problème :
- Créer une issue sur GitHub
- Consulter la documentation Symfony : https://symfony.com/doc

---

**Développé avec ❤️ pour simplifier la gestion locative**