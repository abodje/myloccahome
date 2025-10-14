# 👨‍💼 Guide Administration Super Admin - MYLOCCA

## ✅ Fonctionnalité Implémentée

Le **Super Admin** peut maintenant administrer **toutes les organisations et sociétés** du système.

---

## 🎯 Objectif

Permettre au Super Admin de :
- ✅ Voir toutes les organisations
- ✅ Créer/Modifier/Supprimer des organisations
- ✅ Activer/Désactiver des organisations
- ✅ Voir toutes les sociétés
- ✅ Créer/Modifier/Supprimer des sociétés  
- ✅ Activer/Désactiver des sociétés
- ✅ Consulter les statistiques détaillées
- ✅ Naviguer entre organisations et sociétés

---

## 📁 Fichiers Créés

### **Contrôleurs**
| Fichier | Fonctions |
|---------|-----------|
| `src/Controller/Admin/OrganizationController.php` | CRUD complet organisations |
| `src/Controller/Admin/CompanyController.php` | CRUD complet sociétés |

### **Formulaires**
| Fichier | Usage |
|---------|-------|
| `src/Form/OrganizationType.php` | Formulaire organisation |
| `src/Form/CompanyType.php` | Formulaire société |

### **Templates Organizations**
| Template | Route | Description |
|----------|-------|-------------|
| `admin/organization/index.html.twig` | `/admin/organisations` | Liste des organisations |
| `admin/organization/show.html.twig` | `/admin/organisations/{id}` | Détail organisation |
| `admin/organization/new.html.twig` | `/admin/organisations/nouvelle` | Créer organisation |
| `admin/organization/edit.html.twig` | `/admin/organisations/{id}/modifier` | Modifier organisation |

### **Templates Companies**
| Template | Route | Description |
|----------|-------|-------------|
| `admin/company/index.html.twig` | `/admin/societes` | Liste des sociétés |
| `admin/company/show.html.twig` | `/admin/societes/{id}` | Détail société |
| `admin/company/new.html.twig` | `/admin/societes/nouvelle` | Créer société |
| `admin/company/edit.html.twig` | `/admin/societes/{id}/modifier` | Modifier société |

---

## 🔗 Routes Disponibles

### **Organizations**
```
GET    /admin/organisations                Liste
GET    /admin/organisations/nouvelle       Formulaire création
POST   /admin/organisations/nouvelle       Créer
GET    /admin/organisations/{id}           Détail
GET    /admin/organisations/{id}/modifier  Formulaire édition
POST   /admin/organisations/{id}/modifier  Modifier
POST   /admin/organisations/{id}/supprimer Supprimer
POST   /admin/organisations/{id}/activer   Activer/Désactiver
GET    /admin/organisations/{id}/statistiques  Statistiques
```

### **Companies**
```
GET    /admin/societes                Liste
GET    /admin/societes/nouvelle       Formulaire création
POST   /admin/societes/nouvelle       Créer
GET    /admin/societes/{id}           Détail
GET    /admin/societes/{id}/modifier  Formulaire édition
POST   /admin/societes/{id}/modifier  Modifier
POST   /admin/societes/{id}/supprimer Supprimer
POST   /admin/societes/{id}/activer   Activer/Désactiver
GET    /admin/societes/{id}/statistiques  Statistiques
```

---

## 🎨 Fonctionnalités par Page

### **1. Liste des Organisations** (`/admin/organisations`)

**Affichage :**
- Carte avec 4 KPIs :
  - Organisations totales
  - Organisations actives
  - Sociétés totales
  - Utilisateurs totaux
- Table avec toutes les organisations :
  - Nom et n° enregistrement
  - Contact (email, téléphone)
  - Nombre de sociétés
  - Nombre d'utilisateurs
  - Nombre de biens
  - Statut (Active/Inactive)
  - Boutons actions (Voir, Modifier, Stats)

**Actions :**
- Créer nouvelle organisation
- Voir détail d'une organisation
- Modifier une organisation
- Consulter statistiques

---

### **2. Détail Organisation** (`/admin/organisations/{id}`)

**Section gauche :**
- Informations complètes :
  - Nom, Email, Téléphone
  - Adresse, Site web
  - N° enregistrement, N° fiscal
  - Statut, Dates création/modification
- Liste des sociétés rattachées :
  - Nom, Email, Statut
  - Lien vers détail société

**Section droite :**
- Statistiques visuelles :
  - Sociétés (avec barre de progression)
  - Utilisateurs
  - Propriétés
  - Baux
  - Paiements
  - Documents
- Actions rapides :
  - Ajouter une société
  - Modifier l'organisation
  - Supprimer l'organisation

**Actions :**
- Modifier
- Activer/Désactiver
- Supprimer (avec confirmation)
- Voir statistiques détaillées

---

### **3. Liste des Sociétés** (`/admin/societes`)

**Affichage :**
- Carte avec 3 KPIs :
  - Sociétés totales
  - Sociétés actives
  - Biens totaux
- Table avec toutes les sociétés :
  - Nom et n° enregistrement
  - Organisation parente
  - Contact (email, téléphone)
  - Nombre d'utilisateurs
  - Nombre de biens
  - Statut (Active/Inactive)
  - Boutons actions

**Actions :**
- Créer nouvelle société
- Voir détail société
- Modifier société
- Consulter statistiques

---

### **4. Détail Société** (`/admin/societes/{id}`)

**Section gauche :**
- Informations complètes :
  - Nom, Organisation parente
  - Email, Téléphone
  - Adresse, Site web
  - N° enregistrement, N° fiscal
  - Statut, Dates

**Section droite :**
- Statistiques visuelles :
  - Utilisateurs
  - Propriétés
  - Baux
  - Paiements
  - Documents
- Actions rapides :
  - Modifier la société
  - Supprimer la société

**Actions :**
- Modifier
- Activer/Désactiver
- Supprimer (avec confirmation)
- Voir statistiques détaillées

---

## 🔐 Sécurité

### **Restriction d'Accès**

```php
#[IsGranted('ROLE_SUPER_ADMIN')]
```

**Seuls les Super Admins** peuvent accéder aux routes :
- `/admin/organisations/*`
- `/admin/societes/*`

### **Protection CSRF**

Toutes les actions critiques (supprimer, activer/désactiver) sont protégées par token CSRF :
```php
$this->isCsrfTokenValid('delete'.$organization->getId(), $request->request->get('_token'))
```

### **Gestion des Erreurs**

```php
try {
    // Suppression
    $entityManager->remove($organization);
    $entityManager->flush();
    $this->addFlash('success', 'Suppression réussie');
} catch (\Exception $e) {
    $this->addFlash('error', 'Impossible de supprimer (données associées)');
}
```

---

## 📊 Statistiques Disponibles

### **Par Organisation**
- Nombre de sociétés
- Nombre d'utilisateurs total
- Nombre de propriétés total
- Nombre de baux total
- Nombre de paiements total
- Nombre de documents total

### **Par Société**
- Nombre d'utilisateurs
- Nombre de propriétés
- Nombre de baux
- Nombre de paiements
- Nombre de documents

---

## 🎯 Flux Utilisateur

### **Scénario 1 : Créer une nouvelle organisation**

```
1. Super Admin → Menu "Organisations"
2. Clic "Nouvelle organisation"
3. Remplir formulaire :
   - Nom (requis)
   - Email, Téléphone
   - Adresse, Site web
   - N° enregistrement, N° fiscal
   - Actif (checkbox)
4. Clic "Créer l'organisation"
5. ✅ Redirection vers détail organisation
```

### **Scénario 2 : Créer une société dans une organisation**

```
1. Super Admin → Voir organisation
2. Section "Sociétés" → Clic "Ajouter"
   OU Menu "Sociétés" → "Nouvelle société"
3. Remplir formulaire :
   - Sélectionner organisation
   - Nom société
   - Contact, etc.
4. Clic "Créer la société"
5. ✅ Redirection vers détail société
```

### **Scénario 3 : Désactiver une organisation**

```
1. Super Admin → Voir organisation
2. Clic "Désactiver"
3. ✅ Organisation marquée inactive
4. Message de confirmation
```

### **Scénario 4 : Consulter statistiques**

```
1. Super Admin → Liste organisations
2. Clic "Statistiques" sur une organisation
3. ✅ Page avec stats détaillées
```

---

## 🎨 Interface

### **Design Moderne**
- Cards Bootstrap 5
- Icons Bootstrap Icons
- Tables responsives
- Badges colorés pour statuts
- Boutons groupés
- Barres de progression

### **Codes Couleurs**
- 🟢 Vert : Active, Succès
- 🔴 Rouge : Inactive, Danger
- 🔵 Bleu : Information
- 🟡 Jaune : Attention
- ⚪ Gris : Secondaire

---

## 🔄 Intégration Menu

Le menu Super Admin affiche maintenant :

```
ADMINISTRATION
├─ Organisations  (nouveau)
├─ Sociétés       (nouveau)
├─ Administration
├─ Utilisateurs
├─ Tâches automatisées
├─ Historique / Audit
├─ Sauvegardes
├─ Templates emails
├─ Gestion des menus
├─ Configuration contrats
└─ Paramètres
```

**Visibilité :** `ROLE_SUPER_ADMIN` uniquement

---

## 📈 Exemples d'Utilisation

### **Exemple 1 : Multi-Organisation**

```
Organisation A (Immobilière Nord)
├─ Société A1 (Lille)
│  ├─ 10 utilisateurs
│  └─ 50 biens
└─ Société A2 (Paris)
   ├─ 15 utilisateurs
   └─ 75 biens

Organisation B (Immobilière Sud)
├─ Société B1 (Marseille)
│  ├─ 8 utilisateurs
│  └─ 40 biens
└─ Société B2 (Nice)
   ├─ 12 utilisateurs
   └─ 60 biens
```

Le Super Admin peut :
- Voir les 2 organisations
- Voir les 4 sociétés
- Modifier n'importe quelle organisation/société
- Consulter les stats de chacune
- Activer/Désactiver

---

## ✅ Validation

### **Tests à Effectuer**

**Test 1 : Créer organisation**
```
1. Aller sur /admin/organisations
2. Cliquer "Nouvelle organisation"
3. Remplir nom "Test Org"
4. ✅ Organisation créée
```

**Test 2 : Créer société**
```
1. Aller sur /admin/societes
2. Cliquer "Nouvelle société"
3. Sélectionner organisation
4. Remplir nom "Test Company"
5. ✅ Société créée
```

**Test 3 : Lien organisation → sociétés**
```
1. Voir détail organisation
2. Section "Sociétés" affiche liste
3. Cliquer sur une société
4. ✅ Redirection vers détail société
```

**Test 4 : Activer/Désactiver**
```
1. Voir organisation
2. Cliquer "Désactiver"
3. ✅ Statut devient "Inactive"
4. Cliquer "Activer"
5. ✅ Statut devient "Active"
```

**Test 5 : Supprimer**
```
1. Voir organisation vide (sans sociétés)
2. Cliquer "Supprimer"
3. Confirmer
4. ✅ Organisation supprimée
```

---

## 🚀 Résultat Final

```
╔════════════════════════════════════════════╗
║  SUPER ADMIN - GESTION TOTALE             ║
╠════════════════════════════════════════════╣
║                                            ║
║  ✅ Voir toutes les organisations         ║
║  ✅ Créer organisations                   ║
║  ✅ Modifier organisations                ║
║  ✅ Supprimer organisations               ║
║  ✅ Activer/Désactiver organisations      ║
║                                            ║
║  ✅ Voir toutes les sociétés              ║
║  ✅ Créer sociétés                        ║
║  ✅ Modifier sociétés                     ║
║  ✅ Supprimer sociétés                    ║
║  ✅ Activer/Désactiver sociétés           ║
║                                            ║
║  ✅ Statistiques complètes                ║
║  ✅ Navigation fluide                     ║
║  ✅ Interface moderne                     ║
║  ✅ Sécurisé (SUPER_ADMIN only)           ║
║                                            ║
║  🏆 CONTRÔLE TOTAL DU SYSTÈME !           ║
╚════════════════════════════════════════════╝
```

---

## 📊 Récapitulatif Session

### **✨ Fonctionnalité 13 : Administration Multi-Organisation**

| Élément | Quantité |
|---------|----------|
| **Contrôleurs** | 2 |
| **Formulaires** | 2 |
| **Templates** | 8 |
| **Routes** | 18 |
| **Lignes de code** | ~1,200 |

---

**LE SUPER ADMIN A MAINTENANT UN CONTRÔLE TOTAL SUR TOUTES LES ORGANISATIONS ET SOCIÉTÉS ! 👨‍💼👑✨**

