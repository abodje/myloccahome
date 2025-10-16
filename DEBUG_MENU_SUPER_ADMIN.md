# 🔍 Debug Menu Super Admin - MYLOCCA

## ✅ Correction Appliquée

Le divider "ADMINISTRATION" a été mis à jour pour inclure `ROLE_SUPER_ADMIN`.

---

## 🧪 Tests à Effectuer

### **1. Vider le Cache Symfony**

```powershell
php bin/console cache:clear
```

### **2. Vérifier votre Rôle**

Connectez-vous et vérifiez que vous avez bien le rôle `ROLE_SUPER_ADMIN` :

**Option A : Via la base de données**
```sql
SELECT email, roles FROM user WHERE email = 'votre_email@example.com';
```

Le champ `roles` doit contenir : `["ROLE_SUPER_ADMIN"]` ou `["ROLE_SUPER_ADMIN", "ROLE_ADMIN"]`

**Option B : Via le profiler Symfony**
1. Connectez-vous
2. En bas de la page, cliquez sur l'icône Symfony (barre de debug)
3. Section "Security" → Vérifiez les rôles affichés

### **3. Vérifier la Hiérarchie des Rôles**

Votre `config/packages/security.yaml` doit contenir :

```yaml
role_hierarchy:
    ROLE_TENANT: ROLE_USER
    ROLE_MANAGER: [ROLE_USER, ROLE_TENANT]
    ROLE_ADMIN: [ROLE_MANAGER, ROLE_USER]
    ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_MANAGER, ROLE_USER]
```

✅ **Cela signifie :** Un utilisateur avec `ROLE_SUPER_ADMIN` hérite automatiquement de tous les autres rôles.

---

## 🔧 Solutions selon les Problèmes

### **Problème 1 : L'utilisateur n'a pas ROLE_SUPER_ADMIN**

**Solution :** Ajouter le rôle via SQL ou créer un nouveau super admin

**Via SQL :**
```sql
UPDATE user 
SET roles = '["ROLE_SUPER_ADMIN"]' 
WHERE email = 'votre_email@example.com';
```

**Via Commande (si elle existe) :**
```powershell
php bin/console app:create-super-admin
```

Puis suivre les instructions.

---

### **Problème 2 : Cache Symfony**

**Solution :**
```powershell
# Vider tout le cache
php bin/console cache:clear

# Si ça ne suffit pas
php bin/console cache:clear --env=dev
php bin/console cache:clear --env=prod

# En dernier recours : supprimer manuellement
Remove-Item -Recurse -Force var\cache\*
```

---

### **Problème 3 : Session non rafraîchie**

**Solution :**
1. Déconnectez-vous complètement
2. Fermez le navigateur
3. Reconnectez-vous

Ou effacez les cookies du site.

---

## ✅ Menu Attendu pour ROLE_SUPER_ADMIN

Avec `ROLE_SUPER_ADMIN`, vous devriez voir :

```
Mon tableau de bord
Mes demandes
Mes biens
Locataires
Baux
Mes paiements
Ma comptabilité
Mes documents
Messagerie
Calendrier
Mon Abonnement

────────── ADMINISTRATION ──────────
Organisations        ← NOUVEAU (uniquement SUPER_ADMIN)
Sociétés            ← NOUVEAU (uniquement SUPER_ADMIN)
Administration      ← Visible (hérité de ROLE_ADMIN)
Utilisateurs        ← Visible (hérité de ROLE_ADMIN)
Tâches automatisées ← Visible (hérité de ROLE_ADMIN)
Historique / Audit  ← Visible (hérité de ROLE_ADMIN)
Sauvegardes         ← Visible (hérité de ROLE_ADMIN)
Templates emails    ← Visible (hérité de ROLE_ADMIN)
Gestion des menus   ← Visible (hérité de ROLE_ADMIN)
Configuration contrats ← Visible (hérité de ROLE_ADMIN)
Paramètres          ← Visible (hérité de ROLE_ADMIN)
Rapports            ← Visible (hérité de ROLE_ADMIN)
```

---

## 🔍 Vérification Complète

### **Test 1 : Vérifier les Rôles dans le Code**

Créez temporairement cette route pour tester :

```php
// src/Controller/TestController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test-roles', name: 'test_roles')]
    public function testRoles(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new Response('Non connecté');
        }
        
        $roles = $user->getRoles();
        $isSuper = $this->isGranted('ROLE_SUPER_ADMIN');
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isManager = $this->isGranted('ROLE_MANAGER');
        
        return new Response(sprintf(
            'Rôles : %s<br>SUPER_ADMIN : %s<br>ADMIN : %s<br>MANAGER : %s',
            implode(', ', $roles),
            $isSuper ? 'OUI' : 'NON',
            $isAdmin ? 'OUI' : 'NON',
            $isManager ? 'OUI' : 'NON'
        ));
    }
}
```

Puis allez sur : `https://127.0.0.1:8000/test-roles`

**Résultat attendu pour SUPER_ADMIN :**
```
Rôles : ROLE_SUPER_ADMIN
SUPER_ADMIN : OUI
ADMIN : OUI (grâce à la hiérarchie)
MANAGER : OUI (grâce à la hiérarchie)
```

---

### **Test 2 : Vérifier le MenuService**

Ajoutez temporairement un dump dans le template :

```twig
{# templates/base.html.twig #}
{# Ajoutez ceci avant la boucle du menu #}
{{ dump(app.user.roles) }}
```

Puis rechargez la page et vérifiez les rôles dans la barre de debug Symfony.

---

## 🎯 Checklist de Résolution

- [ ] Vider le cache Symfony
- [ ] Vérifier que l'utilisateur a bien ROLE_SUPER_ADMIN dans la BDD
- [ ] Se déconnecter/reconnecter
- [ ] Vérifier security.yaml (hiérarchie des rôles)
- [ ] Vérifier MenuService.php (divider_admin inclut ROLE_SUPER_ADMIN)
- [ ] Tester avec `/test-roles`
- [ ] Effacer cookies du navigateur si nécessaire

---

## 🚀 Solution Rapide

**Si rien ne fonctionne, exécutez ceci dans l'ordre :**

```powershell
# 1. Vider cache
php bin/console cache:clear

# 2. Vérifier/Ajouter le rôle SUPER_ADMIN
# Via console MySQL/phpMyAdmin
# Ou créer un nouveau super admin

# 3. Redémarrer le serveur Symfony
# Ctrl+C puis relancer
symfony server:start

# 4. Se déconnecter/reconnecter dans le navigateur
# Ou effacer les cookies
```

---

## 📝 Note Importante

La hiérarchie des rôles Symfony fonctionne comme ceci :

```
ROLE_SUPER_ADMIN
    ├── ROLE_ADMIN
    │   ├── ROLE_MANAGER
    │   │   ├── ROLE_USER
    │   │   └── ROLE_TENANT
    │   └── ROLE_USER
    └── ROLE_MANAGER
        ├── ROLE_USER
        └── ROLE_TENANT
```

**Donc :**
- Un SUPER_ADMIN a **TOUS** les droits
- Un ADMIN a les droits de MANAGER et USER
- Un MANAGER a les droits de USER et TENANT

---

## ✅ Validation Finale

Une fois tout corrigé, vous devriez :

1. ✅ Voir le divider "ADMINISTRATION"
2. ✅ Voir "Organisations" (uniquement SUPER_ADMIN)
3. ✅ Voir "Sociétés" (uniquement SUPER_ADMIN)
4. ✅ Voir tous les autres menus admin (hérités)

---

**Si le problème persiste après toutes ces étapes, partagez le résultat de `/test-roles` !** 🔍

