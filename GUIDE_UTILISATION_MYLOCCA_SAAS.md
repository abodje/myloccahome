# 🚀 Guide d'Utilisation - MYLOCCA SaaS Multi-Tenant

## 📋 Table des Matières
1. [Démarrage Rapide](#démarrage-rapide)
2. [Inscription et Choix de Plan](#inscription)
3. [Gestion des Sociétés](#sociétés)
4. [Rôles et Permissions](#rôles)
5. [Génération des Documents](#documents)
6. [Commandes Console](#commandes)
7. [FAQ](#faq)

---

## 🎯 Démarrage Rapide

### **1. Initialiser le Système**
```bash
# Créer les plans d'abonnement
php bin/console app:create-default-plans

# Créer votre compte Super Admin (propriétaire MYLOCCA)
php bin/console app:create-super-admin
```

### **2. Tester l'Inscription**
```
Aller sur : http://localhost:8000/inscription/plans
```

### **3. Choisir un Plan**
- **Freemium** : GRATUIT - Parfait pour tester (2 propriétés, 3 locataires)
- **Professional** : 24 900 FCFA/mois - Recommandé (20 propriétés, comptabilité, paiements en ligne)
- **Enterprise** : 49 900 FCFA/mois - Illimité (SMS, API, branding)

---

## 📝 Inscription

### **Étapes d'Inscription**

**1. Choisir votre plan**
```
/inscription/plans
→ Cliquez sur "Commencer GRATUITEMENT" ou "Commencer l'essai gratuit"
```

**2. Remplir le formulaire**
```
Informations entreprise:
- Nom : "Mon Agence Immobilière"
- Email : contact@monagence.fr
- Téléphone : 01 23 45 67 89

Informations personnelles:
- Prénom : Jean
- Nom : Dupont
- Email : jean@monagence.fr
- Mot de passe : ******** (min 8 caractères)
```

**3. Validation**
```
✅ Tous les champs remplis ?
✅ Email unique ?
✅ Mot de passe >= 8 caractères ?
```

**4. Création automatique**
```
Le système crée:
✅ Organization "Mon Agence Immobilière"
✅ Company "Mon Agence Immobilière" (siège social)
✅ User "Jean Dupont" (ROLE_ADMIN)
✅ Subscription (Plan choisi, statut ACTIVE si Freemium)
```

**5. Connexion**
```
→ Redirection vers /login
→ Connectez-vous avec jean@monagence.fr
→ Accès au dashboard personnalisé
```

---

## 🏢 Gestion des Sociétés

### **Concept : Organization → Company**

**Organization** = Votre compte principal MYLOCCA (qui paie l'abonnement)
**Company** = Vos sociétés/agences/filiales (structure interne)

### **Exemples**

#### **Cas 1 : Petite Agence (1 société)**
```
Organization: "Mon Agence"
  └── Company: "Mon Agence" (siège, créé automatiquement)
```

#### **Cas 2 : Groupe Multi-Sites**
```
Organization: "Groupe Immobilier ABC"
  ├── Company: "ABC Agence Paris"
  ├── Company: "ABC Agence Lyon"
  └── Company: "ABC Agence Marseille"
```

### **Avantages du Système Company**

1. ✅ **Documents Personnalisés** : Chaque société a ses propres coordonnées sur les quittances
2. ✅ **Reporting Séparé** : CA par société
3. ✅ **Délégation** : Assigner un manager par société
4. ✅ **Isolation** : Un manager ne voit que SA société
5. ✅ **SIRET Distinct** : Chaque société a son propre SIRET

### **Création de Sociétés Supplémentaires**

```
À venir: Menu "Sociétés" → "Nouvelle Société"

Formulaire:
- Nom: "ABC Agence Paris"
- Raison sociale: "ABC PARIS SARL"
- SIRET: 12345678900012
- Adresse: 123 rue de Vaugirard, 75015 Paris
- Manager assigné: Jean Dupont
```

---

## 🎭 Rôles et Permissions

### **ROLE_SUPER_ADMIN** (Vous, propriétaire MYLOCCA)
```bash
# Créer avec:
php bin/console app:create-super-admin

Peut:
✅ Voir TOUTES les organizations
✅ Suspendre/Activer des organizations
✅ Gérer tous les abonnements
✅ Accès système complet
```

### **ROLE_ADMIN** (Client qui s'inscrit)
```
Créé automatiquement à l'inscription

Peut:
✅ Gérer SON organization
✅ Créer des sociétés (companies)
✅ Créer des managers
✅ Créer des locataires
✅ Gérer l'abonnement
✅ Voir TOUTES les données de son organization
```

### **ROLE_MANAGER** (Gestionnaire de société)
```
Créé par l'ADMIN

Peut:
✅ Gérer SA société uniquement
✅ Créer des locataires pour SA société
✅ Gérer les biens de SA société
❌ Ne voit PAS les autres sociétés
❌ Ne peut PAS gérer l'abonnement
```

### **ROLE_TENANT** (Locataire)
```
Créé par ADMIN ou MANAGER

Peut:
✅ Voir ses propres données
✅ Payer ses loyers
✅ Créer des demandes de maintenance
✅ Voir ses documents
❌ Ne voit rien d'autre
```

---

## 📄 Génération des Documents

### **Quittances de Loyer**

**Automatique (via tâche planifiée)** :
```bash
php bin/console app:generate-rent-documents --month=current
```

**Manuel (par paiement)** :
```
Interface : Mes paiements → Clic sur "Générer quittance"
```

**Ce qui apparaît sur le PDF** :
```
┌─────────────────────────────────────┐
│ ABC AGENCE PARIS                    │
│ SIRET : 12345678900012              │
│ 123 rue de Vaugirard                │
│ 75015 Paris                         │
│ Tél : 01 23 45 67 89                │
│ Email : paris@abc.fr                │
│ Web : www.abc-immo.fr               │
├─────────────────────────────────────┤
│ [Détails du paiement]               │
└─────────────────────────────────────┘
```

---

## 🖥️ Commandes Console

### **Génération des Loyers**
```bash
# Tous les contrats actifs
php bin/console app:generate-rents

# Pour une société spécifique
php bin/console app:generate-rents --company=5

# Pour une organization
php bin/console app:generate-rents --organization=2

# Simulation (dry-run)
php bin/console app:generate-rents --dry-run

# Générer pour 3 mois d'avance
php bin/console app:generate-rents --months-ahead=3
```

### **Génération des Documents**
```bash
# Mois en cours
php bin/console app:generate-rent-documents --month=current

# Mois précédent
php bin/console app:generate-rent-documents --month=last

# Mois spécifique
php bin/console app:generate-rent-documents --month=2025-10
```

### **Gestion du Système**
```bash
# Créer les plans
php bin/console app:create-default-plans

# Créer un Super Admin
php bin/console app:create-super-admin

# Initialiser tout
php bin/console app:initialize-system

# Vider le cache
php bin/console cache:clear
```

---

## ❓ FAQ

### **Q: Quelle est la différence entre Organization et Company ?**
**R:** 
- **Organization** = Votre compte MYLOCCA (celui qui paie l'abonnement)
- **Company** = Vos sociétés/agences/filiales (structure interne)

Une Organization peut avoir plusieurs Companies.

### **Q: Quel rôle est créé lors de l'inscription ?**
**R:** `ROLE_ADMIN` - Vous devenez administrateur de VOTRE organization (pas super-admin du système).

### **Q: Comment créer un Super Admin ?**
**R:** `php bin/console app:create-super-admin` (réservé au propriétaire de MYLOCCA)

### **Q: Les quittances affichent quelles coordonnées ?**
**R:** Les coordonnées de la **Company** (société émettrice), incluant :
- Nom légal (raison sociale)
- SIRET
- Adresse complète
- Téléphone, email, website

### **Q: Un manager voit-il toutes les sociétés ?**
**R:** Non, un manager voit UNIQUEMENT la société qui lui est assignée.

### **Q: Comment upgrader mon plan ?**
**R:** Menu "Mon Abonnement" → "Passer à un plan supérieur"

### **Q: Le plan Freemium expire-t-il ?**
**R:** Non ! Le plan Freemium est GRATUIT pour toujours (avec limites: 2 propriétés, 3 locataires).

### **Q: Les tâches console génèrent pour toutes les sociétés ?**
**R:** Oui par défaut, mais vous pouvez filtrer avec `--company=X` ou `--organization=Y`.

### **Q: Que se passe-t-il si j'atteins la limite de mon plan ?**
**R:** Un message vous propose d'upgrader vers un plan supérieur. Vous ne pouvez pas créer plus de ressources.

---

## 🎯 Cas d'Usage Réels

### **Scénario 1 : Propriétaire Solo**
```
Plan: Freemium (GRATUIT)
→ 1 Organization
→ 1 Company (siège)
→ 1 User (ADMIN)
→ 2 propriétés max
→ 3 locataires max
```

### **Scénario 2 : Agence Immobilière**
```
Plan: Professional (24 900 FCFA/mois)
→ 1 Organization "Agence Durand"
→ 1 Company "Agence Durand"
→ 1 ADMIN + 2 MANAGERS
→ 20 propriétés
→ 50 locataires
→ Comptabilité + Paiements en ligne
```

### **Scénario 3 : Groupe avec Filiales**
```
Plan: Enterprise (49 900 FCFA/mois)
→ 1 Organization "Groupe ABC Holdings"
→ 3 Companies:
   - ABC Résidentiel
   - ABC Commercial
   - ABC Gestion
→ 1 ADMIN + 5 MANAGERS (1-2 par company)
→ Propriétés illimitées
→ Toutes les fonctionnalités (SMS, API, branding)
```

---

## 📞 Support

### **Documentation Disponible**
- `SYSTEME_FEATURES_PROFESSIONNELLES.md` - Features
- `GESTION_ROLES_SAAS.md` - Rôles
- `STRUCTURE_ORGANIZATION_COMPANY.md` - Architecture
- `IMPACT_COMPANY_SUR_SYSTEME.md` - Impact global

### **Commandes Utiles**
```bash
# Vérifier les migrations
php bin/console doctrine:migrations:status

# Lister les organizations
php bin/console doctrine:query:dql "SELECT o.id, o.name FROM App\Entity\Organization o"

# Lister les companies
php bin/console doctrine:query:dql "SELECT c.id, c.name FROM App\Entity\Company c"

# Voir les plans
php bin/console doctrine:query:dql "SELECT p.name, p.monthlyPrice FROM App\Entity\Plan p"
```

---

## 🎉 MYLOCCA SaaS est PRÊT !

**Vous avez maintenant une plateforme complète pour :**
- ✅ Proposer des abonnements SaaS
- ✅ Gérer plusieurs organizations (clients)
- ✅ Gérer plusieurs sociétés par organization
- ✅ Générer des documents professionnels
- ✅ Contrôler les accès par rôle et par plan
- ✅ Automatiser la gestion locative

**Bon succès commercial avec MYLOCCA ! 🚀**

