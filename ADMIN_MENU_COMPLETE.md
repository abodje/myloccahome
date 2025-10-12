# 👑 Menu Administration complet - MYLOCCA

## 📋 Menu visible uniquement pour les ADMINISTRATEURS

Le menu d'administration est maintenant **complètement organisé** avec une séparation visuelle (ligne horizontale) entre les menus utilisateurs et les menus admin.

---

## 🎨 Structure du menu (dans la sidebar)

### Section UTILISATEUR (Tous les rôles connectés)

1. **Mon tableau de bord** 📊
   - Route : `/`
   - Icône : `bi-speedometer2`
   - Visible pour : Tous

2. **Mes demandes** 🔧
   - Route : `/demandes`
   - Icône : `bi-tools`
   - Visible pour : Tous

### Section GESTIONNAIRE (ROLE_MANAGER et ROLE_ADMIN)

3. **Mes biens** 🏠
   - Route : `/biens`
   - Icône : `bi-house`
   - Visible pour : Manager, Admin

4. **Locataires** 👥
   - Route : `/locataires`
   - Icône : `bi-people`
   - Visible pour : Manager, Admin

5. **Contrats** 📄
   - Route : `/contrats`
   - Icône : `bi-file-text`
   - Visible pour : Manager, Admin

### Section PAIEMENTS (Tous)

6. **Mes paiements** 💳
   - Route : `/mes-paiements`
   - Icône : `bi-credit-card`
   - Visible pour : Tous

### Section COMPTABILITÉ (Managers)

7. **Ma comptabilité** 🧮
   - Route : `/comptabilite`
   - Icône : `bi-calculator`
   - Visible pour : Manager, Admin

### Section DOCUMENTS (Tous)

8. **Mes documents** 📁
   - Route : `/mes-documents`
   - Icône : `bi-file-earmark-text`
   - Visible pour : Tous

---

### ════════════════════════════════════
### **SECTION ADMINISTRATION** (ROLE_ADMIN uniquement)
### ════════════════════════════════════

9. **Admin Dashboard** 📊
   - Route : `/admin`
   - Icône : `bi-speedometer`
   - Fonctionnalités :
     - Statistiques globales
     - Activité récente
     - Paiements en retard
     - Vue d'ensemble système

10. **Tâches automatisées** ⚙️
    - Route : `/admin/taches`
    - Icône : `bi-clock-history`
    - Fonctionnalités :
      - Liste des tâches programmées
      - Exécution manuelle
      - Statistiques d'exécution
      - Test de configuration email
      - Envoi manuel de quittances
      - Initialisation des tâches par défaut

11. **Templates emails** 📧
    - Route : `/admin/templates-email`
    - Icône : `bi-envelope-paper`
    - Fonctionnalités :
      - Liste des templates
      - Créer/Modifier templates
      - 60+ variables disponibles
      - Prévisualisation en temps réel
      - Duplication de templates
      - Statistiques d'utilisation

12. **Utilisateurs** 👥
    - Route : `/admin/utilisateurs`
    - Icône : `bi-people-fill`
    - Fonctionnalités :
      - Liste de tous les utilisateurs
      - Créer/Modifier/Supprimer
      - Filtrer par rôle
      - Activer/Désactiver comptes
      - Voir dernières connexions

13. **Paramètres** ⚙️
    - Route : `/admin/parametres`
    - Icône : `bi-gear`
    - Sous-sections :
      - **Application** : Nom, logo, informations entreprise
      - **Email** : Configuration SMTP
      - **Paiements** : Échéances, pénalités
      - **Devises** : Gestion multi-devises
      - **Localisation** : Formats date/heure, timezone

---

## 🎯 Accès rapide par URL

### URLs principales administration

```
/admin                          → Dashboard admin
/admin/taches                   → Tâches automatisées
/admin/templates-email          → Templates emails
/admin/utilisateurs             → Gestion utilisateurs
/admin/parametres               → Paramètres généraux
/admin/parametres/devises       → Gestion des devises
/admin/parametres/email         → Configuration email
/admin/parametres/paiements     → Paramètres paiements
/admin/parametres/application   → Infos entreprise
```

---

## 🎨 Apparence du menu

```
┌─────────────────────────────┐
│  🏢 MYLOCCA                 │
├─────────────────────────────┤
│ 📊 Mon tableau de bord      │
│ 🔧 Mes demandes             │
│ 🏠 Mes biens                │  ← Manager & Admin
│ 👥 Locataires               │  ← Manager & Admin
│ 📄 Contrats                 │  ← Manager & Admin
│ 💳 Mes paiements            │
│ 🧮 Ma comptabilité          │  ← Manager & Admin
│ 📁 Mes documents            │
├─────────────────────────────┤  ← Séparateur
│ ADMINISTRATION              │  ← Admin uniquement
├─────────────────────────────┤
│ 📊 Admin Dashboard          │
│ ⚙️ Tâches automatisées      │  ⭐ NOUVEAU
│ 📧 Templates emails         │  ⭐ NOUVEAU
│ 👥 Utilisateurs             │  ⭐ NOUVEAU
│ ⚙️ Paramètres               │
└─────────────────────────────┘
```

---

## 💡 Selon le rôle

### 👑 ADMIN voit :
```
✅ Mon tableau de bord
✅ Mes demandes
✅ Mes biens
✅ Locataires
✅ Contrats
✅ Mes paiements
✅ Ma comptabilité
✅ Mes documents
────────────────────
✅ Admin Dashboard
✅ Tâches automatisées      ← ICI !
✅ Templates emails
✅ Utilisateurs
✅ Paramètres
```

### 🏢 MANAGER voit :
```
✅ Mon tableau de bord
✅ Mes demandes
✅ Mes biens (ses biens uniquement)
✅ Locataires (de ses biens)
✅ Contrats (de ses biens)
✅ Mes paiements
✅ Ma comptabilité
✅ Mes documents
────────────────────
❌ Pas de section Administration
```

### 🏠 TENANT voit :
```
✅ Mon tableau de bord
✅ Mes demandes
✅ Mes paiements
✅ Mes documents
────────────────────
❌ Pas d'accès gestion
❌ Pas de section Administration
```

---

## 🔗 Navigation dans l'administration

### Depuis le menu "Tâches automatisées" :

1. **Vue principale** `/admin/taches`
   - Liste de toutes les tâches
   - Statistiques (total, actives, dues, en cours)
   - Boutons d'action :
     - ▶️ Exécuter les tâches dues
     - 🔄 Initialiser les tâches
     - ➕ Nouvelle tâche

2. **Test de configuration email**
   - Envoyer un email de test
   - Vérifier la config SMTP

3. **Envoi manuel de quittances**
   - Sélectionner le mois
   - Envoyer immédiatement

4. **Actions sur chaque tâche** :
   - 👁️ Voir les détails
   - ▶️ Exécuter maintenant
   - ⏸️ Activer/Désactiver

---

## 🚀 Accès rapide aux tâches

### Depuis le menu admin, vous pouvez :

1. **Voir toutes les tâches programmées**
2. **Exécuter une tâche manuellement**
3. **Créer des tâches personnalisées**
4. **Voir les statistiques d'exécution**
5. **Consulter les erreurs récentes**
6. **Tester la configuration email**
7. **Envoyer des quittances manuellement**

---

## 📝 Fichier modifié

**Fichier** : `templates/base.html.twig`

**Changements** :
- ✅ Ajout d'une ligne de séparation avant la section admin
- ✅ Menu "Admin Dashboard" explicite
- ✅ Menu "Tâches automatisées" avec icône horloge
- ✅ Menu "Templates emails" avec icône enveloppe
- ✅ Menu "Utilisateurs" avec icône personnes
- ✅ Menu "Paramètres" avec icône engrenage

---

## ✅ Maintenant accessible !

**Pour accéder aux tâches** :

1. Connectez-vous en tant qu'admin
2. Dans la sidebar, section **ADMINISTRATION**
3. Cliquez sur **"Tâches automatisées"** (icône horloge)
4. Vous arrivez sur `/admin/taches`

**Vous verrez** :
- 📊 Statistiques des tâches
- 📝 Liste de toutes les tâches
- ✉️ Test de configuration email
- 📄 Envoi manuel de quittances
- ⚙️ Boutons d'action

---

## 🎉 Menu d'administration COMPLET !

Votre menu d'administration est maintenant **parfaitement organisé** avec :

✅ Séparation visuelle claire  
✅ 5 sections admin distinctes  
✅ Icônes explicites  
✅ Navigation intuitive  
✅ Accès rapide à toutes les fonctionnalités  

**Rechargez la page et vous verrez le nouveau menu !** 🚀

---

**Date** : 11 Octobre 2025  
**Status** : ✅ Menu complet et opérationnel

