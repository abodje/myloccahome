# Module de Gestion des Propriétaires

## 📋 Résumé

Un système complet de gestion des propriétaires de biens immobiliers a été ajouté à LOKAPRO.

## ✅ Fonctionnalités implémentées

### 1. Entité Owner mise à jour
- ✅ Ajout de la relation `ManyToOne` avec `Organization`
- ✅ Ajout de la relation `ManyToOne` avec `Company`
- ✅ Chaque propriétaire est maintenant lié à une organisation et une société

### 2. Entités Organization et Company mises à jour
- ✅ Ajout de la relation inverse `OneToMany` avec `Owner`
- ✅ Méthodes `getOwners()`, `addOwner()`, `removeOwner()` ajoutées

### 3. Contrôleur OwnerController
- ✅ `index()` - Liste des propriétaires (filtrée par organisation)
- ✅ `new()` - Création d'un nouveau propriétaire (organisation/société auto-assignées)
- ✅ `show()` - Affichage des détails d'un propriétaire
- ✅ `edit()` - Modification d'un propriétaire
- ✅ `delete()` - Suppression d'un propriétaire
- ✅ `properties()` - Liste des propriétés d'un propriétaire
- ✅ `statistics()` - Statistiques des propriétaires

### 4. Formulaire OwnerType
- ✅ Type de propriétaire (Particulier, SCI, SARL, SA, SAS, Société)
- ✅ Informations personnelles (prénom, nom, email, téléphone)
- ✅ Adresse complète
- ✅ SIRET (pour les sociétés)
- ✅ Compte bancaire (IBAN)
- ✅ Taux de commission (%)
- ✅ Notes
- ✅ Validation des champs avec contraintes

### 5. Templates Twig
- ✅ `owner/index.html.twig` - Liste avec recherche et filtres
- ✅ `owner/new.html.twig` - Formulaire de création
- ✅ `owner/show.html.twig` - Détails avec statistiques
- ✅ `owner/edit.html.twig` - Formulaire de modification

### 6. Menu de navigation
- ✅ Ajout du menu "Propriétaires" dans `MenuService`
- ✅ Accessible aux rôles MANAGER et ADMIN
- ✅ Icône: `bi-person-badge`
- ✅ Position: entre "Mes biens" et "Locataires"

### 7. Migration de base de données
- ✅ Migration créée: `Version20251016211639.php`
- ✅ Ajout des colonnes `organization_id` et `company_id` à la table `owner`
- ✅ Contraintes de clés étrangères configurées
- ✅ Index créés pour optimiser les requêtes

## 🎯 Caractéristiques

### Filtrage automatique
- Les propriétaires sont automatiquement filtrés par organisation de l'utilisateur connecté
- Impossible de voir les propriétaires d'autres organisations

### Assignation automatique
- Lors de la création d'un propriétaire, l'organisation et la société de l'utilisateur sont automatiquement assignées
- Pas besoin de sélectionner manuellement ces informations

### Sécurité
- Protection contre la suppression si le propriétaire possède des biens
- Validation CSRF sur toutes les actions sensibles
- Contraintes d'intégrité référentielle en base de données

### Statistiques
- Nombre total de propriétaires
- Particuliers vs Sociétés
- Propriétés actives par propriétaire
- Loyers mensuels totaux
- Propriétés libres

## 📊 Types de propriétaires supportés
- ✅ Particulier
- ✅ SCI (Société Civile Immobilière)
- ✅ SARL
- ✅ SA
- ✅ SAS
- ✅ Autre société

## 🔗 Relations
```
Organization 1----* Owner
Company 1----* Owner
Owner 1----* Property
```

## 🚀 Prochaines étapes

### Pour appliquer les changements en base de données :

```bash
# Exécuter la migration
php bin/console doctrine:migrations:migrate

# Ou si vous voulez voir le SQL avant d'exécuter
php bin/console doctrine:migrations:migrate --dry-run
```

### Fonctionnalités futures possibles :
- [ ] Export des propriétaires en CSV/Excel
- [ ] Génération de rapports par propriétaire
- [ ] Calcul automatique des commissions
- [ ] Historique des transactions par propriétaire
- [ ] Documents liés au propriétaire
- [ ] Tableau de bord propriétaire

## 📝 Notes

- Les propriétaires existants (créés avant cette mise à jour) auront `organization_id` et `company_id` à NULL
- Il faudra les mettre à jour manuellement ou via un script de migration de données
- Le formulaire masque/affiche automatiquement le champ SIRET selon le type de propriétaire

## 🎨 Interface utilisateur

- Design cohérent avec le reste de l'application
- Responsive (mobile-friendly)
- Icônes Bootstrap Icons
- Badges de couleur pour les types et statuts
- Recherche et filtres intuitifs
- Actions rapides (Voir, Modifier, Supprimer)

## 🔐 Permissions

| Action | Rôle requis |
|--------|-------------|
| Voir la liste | MANAGER, ADMIN |
| Créer | MANAGER, ADMIN |
| Voir détails | MANAGER, ADMIN |
| Modifier | MANAGER, ADMIN |
| Supprimer | MANAGER, ADMIN |

---

**Date de création**: 16 octobre 2025  
**Version**: 1.0  
**Développé pour**: LOKAPRO - Gestion Immobilière

