# 🚀 Automatisation cPanel - Guide Rapide

## Objectif

Créer automatiquement des environnements de démonstration complets (sous-domaine + base de données + utilisateur MySQL) directement depuis l'interface d'administration MyLocca, sans manipulation manuelle sur cPanel.

## ⚡ Démarrage Rapide

### 1️⃣ Générer le Token cPanel (5 minutes)

1. Connectez-vous à votre cPanel : https://s9621.fra1.stableserver.net:2083
2. Allez dans **Security** → **Manage API Tokens**
3. Cliquez sur **Create**
4. Nom du token : `MyLocca Demo Automation`
5. **Copiez le token** (il ne sera affiché qu'une seule fois !)

### 2️⃣ Configurer l'Application (1 minute)

Créez/modifiez le fichier `.env.local` à la racine du projet :

```env
###> cPanel API Configuration ###
CPANEL_HOST=s9621.fra1.stableserver.net
CPANEL_USERNAME=lokaprot
CPANEL_API_TOKEN=votre_token_copie_ici
CPANEL_PORT=2083
CPANEL_DEMO_DOMAIN=lokapro.tech
###< cPanel API Configuration ###
```

### 3️⃣ Tester la Configuration (30 secondes)

```bash
php bin/console app:demo:cpanel-test --create --demo-id=test001
```

✅ Si vous voyez un tableau avec le sous-domaine et la base de données créés, **c'est bon !**

### 4️⃣ Nettoyer le Test

```bash
php bin/console app:demo:cpanel-test --delete --demo-id=test001
```

## 🎯 Utilisation

### Créer un Environnement de Démo

1. Allez sur : **Admin** → **Environnements de Démo** (`/admin/demo`)
2. Remplissez le formulaire :
   - Email : `client@example.com`
   - Prénom : `Jean`
   - Nom : `Dupont`
3. Cliquez sur **"Créer l'Environnement de Démo"**

**Résultat** :
```
✅ Environnement de démo créé avec succès !

📁 Sous-domaine: demo-client-a3f5.lokapro.tech
🗄️ Base de données: lokaprot_demo_client_a3f5
👤 Utilisateur DB: lokaprot_demo_client_a3f5
🔑 Mot de passe: X7k9P2mQ5nR8vT3w

🌐 URL: https://demo-client-a3f5.demo.lokapro.tech
```

### Supprimer un Environnement

1. Dans la liste des environnements, cliquez sur l'icône 🗑️
2. Confirmez la suppression

**Tout est supprimé automatiquement** :
- ✅ Sous-domaine cPanel
- ✅ Base de données MySQL
- ✅ Utilisateur MySQL
- ✅ Données de l'application

## 📊 Ce qui est Créé Automatiquement

| Élément | Automatique | Description |
|---------|-------------|-------------|
| 📁 **Sous-domaine** | ✅ | `demo-{id}.lokapro.tech` |
| 🗄️ **Base de données** | ✅ | `lokaprot_demo_{id}` |
| 👤 **Utilisateur MySQL** | ✅ | Avec mot de passe sécurisé |
| 🔑 **Privilèges** | ✅ | ALL PRIVILEGES sur la BDD |
| 🏢 **Organisation** | ✅ | Organisation démo dans l'app |
| 🏠 **Données de test** | ✅ | 3 propriétés, 5 locataires, 3 baux |

## 🔍 Vérifier le Statut

Dans l'interface admin, un badge indique si cPanel est actif :

- 🟢 **cPanel Activé** → Tout est automatique
- ⚪ **cPanel Désactivé** → Configuration requise (voir ci-dessus)

## ❓ Problèmes Fréquents

### Le badge affiche "cPanel Désactivé" ?

**Solution** : Vérifiez que `CPANEL_API_TOKEN` est bien défini dans `.env.local`

```bash
# Vérifier
grep CPANEL_API_TOKEN .env.local

# Vider le cache
php bin/console cache:clear
```

### Erreur "Authentication failed" ?

**Solution** : Le token est invalide ou expiré. Régénérez-en un nouveau.

### Erreur "Insufficient privileges" ?

**Solution** : Le token doit avoir les permissions :
- SubDomain (manage)
- Mysql (manage)

Supprimez l'ancien token et créez-en un nouveau avec ces permissions.

## 📚 Documentation Complète

- **Configuration détaillée** : `docs/CPANEL_DEMO_AUTOMATION.md`
- **Guide administrateur** : `docs/ADMIN_DEMO_GUIDE.md`
- **Résumé technique** : `docs/INTEGRATION_CPANEL_SUMMARY.md`

## 🎉 Avantages

Avant cPanel :
- ⏰ 15-20 minutes de configuration manuelle par démo
- ❌ Risques d'erreurs (typos, permissions)
- 📝 Documentation des credentials à faire manuellement

Avec cPanel automatisé :
- ⚡ **30 secondes** pour créer un environnement complet
- ✅ Zéro erreur, tout est vérifié
- 📊 Credentials affichés automatiquement
- 🗑️ Suppression propre en un clic

---

**Besoin d'aide ?** Consultez `docs/CPANEL_DEMO_AUTOMATION.md` ou lancez :
```bash
php bin/console app:demo:cpanel-test --help
```
