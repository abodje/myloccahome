# 🔧 Nettoyage Manuel - Sous-domaine Existant

## Problème Rencontré

Le sous-domaine `demo-test001.lokapro.tech` existe déjà dans cPanel et empêche les tests.

## ✅ Solution Rapide - Supprimer via cPanel

### Étape 1 : Se connecter à cPanel

1. Allez sur : https://s9621.fra1.stableserver.net:2083
2. Connectez-vous avec vos identifiants

### Étape 2 : Supprimer le Sous-domaine

1. Dans cPanel, cherchez **"Subdomains"** ou **"Sous-domaines"**
2. Trouvez `demo-test001` dans la liste
3. Cliquez sur **"Remove"** ou **"Supprimer"**
4. Confirmez la suppression

### Étape 3 : Supprimer la Base de Données (si existe)

1. Dans cPanel, allez dans **"MySQL® Databases"** ou **"Bases de données MySQL"**
2. Cherchez `lokaprot_demo_test001`
3. Cliquez sur **"Delete"** si elle existe

### Étape 4 : Supprimer l'Utilisateur MySQL (si existe)

1. Toujours dans **"MySQL® Databases"**
2. Section **"Current Users"** ou **"Utilisateurs actuels"**
3. Cherchez `lokaprot_d_test001`
4. Cliquez sur **"Delete"** si il existe

### Étape 5 : Retester

```bash
php bin/console app:demo:cpanel-test --create --demo-id=test002
```

## 🔍 Problème Identifié avec l'API

L'API cPanel pour supprimer les sous-domaines semble utiliser une nomenclature différente.

### API Testée (ne fonctionne pas)
- ❌ `SubDomain/delete`
- ❌ `SubDomain/delsubdomain`

### À Investiguer
Les bonnes fonctions API cPanel UAPI sont :
- Pour créer : `SubDomain/addsubdomain` ✅ (fonctionne)
- Pour lister : `SubDomain/list_domains`
- Pour supprimer : À déterminer (peut-être via API2 au lieu d'UAPI)

## 📝 Alternative - Utiliser un Autre ID de Test

Au lieu de nettoyer, utilisez simplement un autre ID :

```bash
# Essayer avec test002, test003, etc.
php bin/console app:demo:cpanel-test --create --demo-id=test002
```

## 🚀 Pour Production

En production, **n'utilisez pas d'IDs de test**. L'application générera automatiquement des IDs uniques basés sur l'email de l'utilisateur :

Format : `{base_email}-{hash}` 
Exemple : `johndoe-a3f5`

Cela évite les conflits !

---

**Note** : Le bug de suppression sera corrigé dans une prochaine version en utilisant la bonne API cPanel.
