# 🎭 Gestion des Rôles - MYLOCCA SaaS Multi-Tenant

## 📊 Hiérarchie des Rôles

### **1️⃣ ROLE_SUPER_ADMIN** (Vous - Propriétaire de MYLOCCA)
**Qui ?** Les créateurs/propriétaires de la plateforme MYLOCCA

**Pouvoirs :**
- ✅ Accès à TOUTES les organisations
- ✅ Peut suspendre/activer des organisations
- ✅ Peut voir les statistiques globales
- ✅ Peut gérer tous les abonnements
- ✅ Accès au backend d'administration système
- ✅ Hérite automatiquement de tous les rôles inférieurs

**Comment créer ?**
```bash
php bin/console app:create-super-admin
```

**Caractéristiques :**
- ❌ N'est PAS lié à une organisation spécifique
- ❌ Ne peut PAS être créé via l'inscription publique
- ✅ Créé uniquement via console/commande

---

### **2️⃣ ROLE_ADMIN** (Chef d'entreprise qui s'inscrit)
**Qui ?** La personne qui s'inscrit via `/inscription/plans`

**Pouvoirs :**
- ✅ Gère SA propre organisation uniquement
- ✅ Peut créer/modifier/supprimer des gestionnaires
- ✅ Peut créer/modifier/supprimer des locataires
- ✅ Peut gérer tous les biens de l'organisation
- ✅ Peut upgrader/downgrader l'abonnement
- ✅ Accès à toutes les fonctionnalités de son plan
- ✅ Peut configurer les paramètres de l'organisation
- ✅ Hérite des droits de ROLE_MANAGER et ROLE_TENANT

**Comment créer ?**
- Automatiquement lors de l'inscription publique
- Rôle attribué dans `RegistrationController::register()`

**Exemple de code :**
```php
// Dans RegistrationController
$user->setRoles(['ROLE_ADMIN']); // ✅ Correct !
$user->setOrganization($organization);
```

**Caractéristiques :**
- ✅ Lié à UNE organisation spécifique
- ✅ Premier utilisateur de l'organisation
- ✅ Peut inviter d'autres admins

---

### **3️⃣ ROLE_MANAGER** (Gestionnaire de biens)
**Qui ?** Un employé/gestionnaire créé par l'ADMIN de l'organisation

**Pouvoirs :**
- ✅ Gère les biens qui lui sont assignés
- ✅ Peut créer des locataires pour ses biens
- ✅ Peut gérer les baux de ses biens
- ✅ Peut voir les paiements de ses locataires
- ✅ Peut gérer les demandes de maintenance de ses biens
- ❌ Ne peut PAS voir les biens des autres gestionnaires
- ❌ Ne peut PAS gérer l'abonnement
- ✅ Hérite des droits de ROLE_TENANT

**Comment créer ?**
- Via l'interface admin de l'organisation
- Menu "Administration" → "Utilisateurs" → "Nouveau Gestionnaire"

**Cas d'usage :**
Une agence immobilière avec plusieurs gestionnaires de portefeuille :
- **Admin** : Directeur de l'agence (gère tout)
- **Manager 1** : Gère 10 appartements quartier Nord
- **Manager 2** : Gère 15 maisons quartier Sud
- Chaque manager voit uniquement ses propres biens

---

### **4️⃣ ROLE_TENANT** (Locataire)
**Qui ?** Un locataire créé par ADMIN ou MANAGER

**Pouvoirs :**
- ✅ Voir ses propres biens loués
- ✅ Voir ses baux
- ✅ Payer ses loyers en ligne
- ✅ Faire des paiements anticipés (acomptes)
- ✅ Créer des demandes de maintenance
- ✅ Voir ses documents (contrats, quittances)
- ✅ Utiliser la messagerie avec admin/manager
- ✅ Voir sa comptabilité personnelle
- ❌ Ne peut PAS voir les données des autres locataires
- ❌ Ne peut PAS créer de nouveaux biens
- ❌ Ne peut PAS gérer les utilisateurs

**Comment créer ?**
- Via "Locataires" → "Nouveau Locataire"
- L'admin/manager crée un compte pour le locataire

**Interface spéciale :**
Le dashboard et les menus s'adaptent automatiquement pour n'afficher que les fonctionnalités pertinentes.

---

## 🔐 Configuration de la Hiérarchie

**Fichier :** `config/packages/security.yaml`

```yaml
role_hierarchy:
    ROLE_TENANT: []
    ROLE_MANAGER: [ROLE_TENANT]
    ROLE_ADMIN: [ROLE_MANAGER]
    ROLE_SUPER_ADMIN: [ROLE_ADMIN]
```

**Signification :**
- Un `ROLE_MANAGER` hérite automatiquement de `ROLE_TENANT`
- Un `ROLE_ADMIN` hérite de `ROLE_MANAGER` et `ROLE_TENANT`
- Un `ROLE_SUPER_ADMIN` hérite de TOUS les rôles

---

## 🎯 Isolation des Données (Multi-Tenant)

### **Concept Clé : Organization**
Chaque utilisateur (sauf SUPER_ADMIN) est lié à UNE organisation :

```php
$user->getOrganization() // → Organization #42
```

### **Filtrage Automatique**
`OrganizationFilterSubscriber` s'assure que :
- Un ADMIN ne voit QUE les données de SON organisation
- Un MANAGER ne voit QUE les biens qui lui sont assignés
- Un TENANT ne voit QUE ses propres données

**Exemple :**
```php
// Organisation #1 (Agence Durand)
- Admin: durand@agence.com
- 50 propriétés
- 120 locataires

// Organisation #2 (Agence Martin)  
- Admin: martin@immo.com
- 30 propriétés
- 80 locataires

// ✅ Durand ne voit JAMAIS les données de Martin
// ✅ Martin ne voit JAMAIS les données de Durand
// ✅ SUPER_ADMIN voit TOUT
```

---

## 📋 Tableau Récapitulatif des Permissions

| Action | SUPER_ADMIN | ADMIN | MANAGER | TENANT |
|--------|-------------|-------|---------|--------|
| **Voir toutes les organisations** | ✅ | ❌ | ❌ | ❌ |
| **Gérer son organisation** | ✅ | ✅ | ❌ | ❌ |
| **Upgrader abonnement** | ✅ | ✅ | ❌ | ❌ |
| **Créer utilisateurs** | ✅ | ✅ | ❌ | ❌ |
| **Gérer tous les biens de l'org** | ✅ | ✅ | ❌ | ❌ |
| **Gérer ses biens assignés** | ✅ | ✅ | ✅ | ❌ |
| **Créer des locataires** | ✅ | ✅ | ✅ | ❌ |
| **Voir tous les paiements** | ✅ | ✅ | ❌ | ❌ |
| **Voir paiements de ses biens** | ✅ | ✅ | ✅ | ❌ |
| **Payer son loyer** | ✅ | ✅ | ✅ | ✅ |
| **Créer demandes maintenance** | ✅ | ✅ | ✅ | ✅ |
| **Voir ses documents** | ✅ | ✅ | ✅ | ✅ |
| **Messagerie** | ✅ | ✅ | ✅ | ✅ |

---

## 🚀 Scénarios d'Utilisation

### **Scénario 1 : Petite Agence (1 personne)**
```
Inscription → Plan Freemium
↓
Utilisateur: jean@agence.com
Rôle: ROLE_ADMIN
Organisation: "Agence Jean"
↓
Jean gère tout seul:
- Ajoute ses 2 propriétés
- Crée ses 3 locataires
- Gère les paiements
```

### **Scénario 2 : Moyenne Agence (3 personnes)**
```
Inscription → Plan Professional
↓
Admin: patron@agence.com (ROLE_ADMIN)
Organisation: "Immo Pro"
↓
Patron crée 2 gestionnaires:
- Manager 1: gestionnaire1@agence.com (ROLE_MANAGER)
- Manager 2: gestionnaire2@agence.com (ROLE_MANAGER)
↓
Chaque manager gère ses propres biens
Patron voit TOUT
```

### **Scénario 3 : Grande Agence (10+ personnes)**
```
Inscription → Plan Enterprise
↓
Admin: direction@bigagency.com (ROLE_ADMIN)
Organisation: "BigAgency Corp"
↓
Direction crée:
- 5 ROLE_ADMIN (directeurs régionaux)
- 20 ROLE_MANAGER (gestionnaires)
- 500 ROLE_TENANT (locataires)
↓
Hiérarchie complète avec délégation
```

---

## 🔧 Commandes Utiles

### **Créer un Super Admin**
```bash
php bin/console app:create-super-admin
```

### **Lister les utilisateurs par rôle**
```bash
php bin/console doctrine:query:sql "SELECT email, roles FROM user WHERE roles LIKE '%SUPER_ADMIN%'"
```

### **Changer le rôle d'un utilisateur**
```sql
UPDATE user SET roles = '["ROLE_SUPER_ADMIN"]' WHERE email = 'admin@mylocca.com';
```

---

## ⚠️ Bonnes Pratiques

### **✅ À FAIRE**
1. Créer UN seul SUPER_ADMIN (vous, le propriétaire)
2. Laisser l'inscription créer des ROLE_ADMIN
3. Permettre aux ADMIN de créer des MANAGER
4. Permettre aux ADMIN/MANAGER de créer des TENANT
5. Toujours associer un utilisateur à une organisation (sauf SUPER_ADMIN)

### **❌ À NE PAS FAIRE**
1. ❌ Donner ROLE_SUPER_ADMIN à un client
2. ❌ Créer des ROLE_ADMIN manuellement pour les clients
3. ❌ Permettre aux TENANT de créer d'autres utilisateurs
4. ❌ Mélanger les données de plusieurs organisations
5. ❌ Laisser un utilisateur sans organisation (sauf SUPER_ADMIN)

---

## 🎯 Résumé

**Question initiale :** "C'est quoi qui doit être le role lors de l'inscription ?"

**Réponse :** `ROLE_ADMIN` ✅

**Pourquoi ?**
- La personne qui s'inscrit devient **admin de SA propre organisation**
- Elle peut ensuite créer des managers et locataires
- Elle gère son propre abonnement
- Elle n'a PAS accès aux autres organisations

**ROLE_SUPER_ADMIN** est réservé à VOUS (propriétaire de MYLOCCA) et créé via commande console uniquement.

---

**Le système de rôles est maintenant parfaitement clair et sécurisé ! 🎉**

