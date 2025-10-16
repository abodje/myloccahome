# 🏢 MYLOCCA SaaS - Plateforme de Gestion Locative Multi-Tenant

## 🎯 Qu'est-ce que c'est ?

**MYLOCCA** est une plateforme SaaS professionnelle de gestion locative multi-tenant avec :
- ✅ Système d'abonnement (Freemium, Starter, Professional, Enterprise)
- ✅ Gestion multi-organisations (chaque client est isolé)
- ✅ Gestion multi-sociétés (filiales, agences)
- ✅ 21 fonctionnalités contrôlées par plan
- ✅ Documents PDF personnalisés par société
- ✅ Hiérarchie de rôles (Super Admin, Admin, Manager, Tenant)

---

## 🚀 Démarrage Rapide

### **1. Installation**
```bash
# Créer les plans d'abonnement
php bin/console app:create-default-plans

# Créer votre compte Super Admin
php bin/console app:create-super-admin
```

### **2. Inscription Client**
```
URL : http://localhost:8000/inscription/plans
→ Choisir un plan
→ Remplir le formulaire
→ Compte créé automatiquement
```

### **3. Connexion**
```
URL : http://localhost:8000/login
→ Email + Mot de passe
→ Dashboard personnalisé selon rôle et plan
```

---

## 📊 Plans d'Abonnement

| Plan | Prix | Propriétés | Locataires | Features |
|------|------|------------|------------|----------|
| **Freemium** | GRATUIT | 2 | 3 | 5 |
| **Starter** | 9 900 FCFA/mois | 5 | 10 | 6 |
| **Professional** ⭐ | 24 900 FCFA/mois | 20 | 50 | 16 |
| **Enterprise** | 49 900 FCFA/mois | ∞ | ∞ | 21 |

---

## 🏗️ Architecture

```
Organization (Client/Compte Principal)
  │
  ├── Subscription (Abonnement SaaS)
  │   └── Plan (Freemium/Pro/Enterprise)
  │
  ├── Company 1 (Société/Agence A)
  │   ├── Managers (Gestionnaires)
  │   ├── Properties (Biens immobiliers)
  │   ├── Tenants (Locataires)
  │   ├── Leases (Baux)
  │   └── Payments (Paiements)
  │
  └── Company 2 (Société/Agence B)
      ├── Managers
      ├── Properties
      └── ...
```

---

## 🎭 Rôles

- **ROLE_SUPER_ADMIN** : Propriétaire MYLOCCA (voit tout)
- **ROLE_ADMIN** : Admin d'organization (gère son organization)
- **ROLE_MANAGER** : Gestionnaire (gère une société)
- **ROLE_TENANT** : Locataire (voit ses données)

---

## 📄 Documents Générés

### **Quittances de Loyer**
```
Contient:
✅ Coordonnées de la société émettrice
✅ SIRET de la société
✅ Détails du paiement
✅ Informations locataire
✅ Informations propriété
✅ Signatures
```

### **Avis d'Échéance**
```
Contient:
✅ Rappel de paiement
✅ Coordonnées société
✅ Montant à payer
✅ Date d'échéance
✅ Modalités de paiement
```

---

## 🖥️ Commandes Console

```bash
# Générer loyers mensuels
php bin/console app:generate-rents

# Générer documents PDF
php bin/console app:generate-rent-documents --month=current

# Créer plans
php bin/console app:create-default-plans

# Créer Super Admin
php bin/console app:create-super-admin
```

---

## 📚 Documentation Complète

- `GUIDE_UTILISATION_MYLOCCA_SAAS.md` - Guide utilisateur complet
- `GESTION_ROLES_SAAS.md` - Détails des rôles
- `STRUCTURE_ORGANIZATION_COMPANY.md` - Architecture
- `SYSTEME_FEATURES_PROFESSIONNELLES.md` - Features
- `ACCOMPLISSEMENTS_SESSION_FINALE.md` - Récapitulatif technique

---

## ✨ Points Forts

1. 🎨 **Interface Professionnelle** - Templates Bootstrap 5 élégants
2. 🔐 **Sécurité Multi-Niveaux** - Isolation organization + company
3. 💎 **Freemium Gratuit** - Essai sans engagement
4. 📱 **Responsive** - Fonctionne sur tous les appareils
5. 🚀 **Scalable** - Support des holdings et groupes
6. 📊 **Reporting** - Stats par organization et par société
7. 🤖 **Automatisation** - Génération automatique de loyers et documents
8. 💳 **Paiements en Ligne** - CinetPay intégré (Plans Pro+)

---

## 🎯 Prêt pour le Marché

**MYLOCCA SaaS est une solution commerciale complète pour :**
- Propriétaires immobiliers individuels
- Agences immobilières
- Groupes avec plusieurs agences
- Holdings immobilières

**Avec un modèle économique viable :**
- Plan gratuit pour acquisition
- Plans payants pour revenus récurrents
- Fonctionnalités premium pour upsell

---

## 📞 Contact & Support

- **Email** : support@mylocca.com
- **Documentation** : Voir les fichiers `*.md` dans le projet
- **Super Admin** : Créé via console uniquement

---

**🎊 MYLOCCA SaaS - Votre Solution de Gestion Locative Professionnelle ! 🎊**


