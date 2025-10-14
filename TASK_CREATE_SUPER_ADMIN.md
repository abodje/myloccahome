# 🔐 Tâche de Création de Super Administrateur

## 📋 Vue d'ensemble

La tâche **CREATE_SUPER_ADMIN** permet de créer automatiquement un compte Super Administrateur MYLOCCA via le système de tâches planifiées. Cette fonctionnalité est utile pour l'initialisation automatique de la plateforme ou pour créer des comptes d'urgence.

---

## ✅ Fonctionnalités Implémentées

### **1. Type de Tâche : CREATE_SUPER_ADMIN**

Cette tâche crée un compte utilisateur avec le rôle `ROLE_SUPER_ADMIN` qui a un accès complet à toutes les organisations de la plateforme.

### **2. Paramètres Requis**

La tâche nécessite les paramètres suivants dans le champ `parameters` :

```php
[
    'email' => 'admin@mylocca.com',          // Email valide (requis)
    'firstName' => 'Admin',                   // Prénom (requis)
    'lastName' => 'MYLOCCA',                  // Nom (requis)
    'password' => 'MotDePasseSecurise123'     // Minimum 8 caractères (requis)
]
```

### **3. Validations Automatiques**

- ✅ **Email** : Validation du format email
- ✅ **Mot de passe** : Minimum 8 caractères
- ✅ **Unicité** : Vérifie si l'email existe déjà
- ✅ **Rôle existant** : Si l'utilisateur existe avec ROLE_SUPER_ADMIN, la tâche ne fait rien

---

## 🎯 Utilisation

### **Méthode 1 : Via l'Interface Web**

#### **Créer une Tâche Manuelle**

1. Accéder à `/admin/tasks/new`
2. Remplir le formulaire :
   - **Nom** : Création Super Admin
   - **Type** : CREATE_SUPER_ADMIN
   - **Fréquence** : ONCE (une seule fois)
   - **Paramètres** (JSON) :
     ```json
     {
       "email": "admin@mylocca.com",
       "firstName": "Admin",
       "lastName": "MYLOCCA",
       "password": "SecurePassword123"
     }
     ```
3. Sauvegarder et exécuter immédiatement

---

### **Méthode 2 : Via Code PHP**

```php
use App\Entity\Task;
use App\Service\TaskManagerService;

// Créer la tâche
$task = new Task();
$task->setName('Création Super Admin')
     ->setType('CREATE_SUPER_ADMIN')
     ->setDescription('Création automatique du compte super administrateur')
     ->setFrequency('ONCE')
     ->setParameters([
         'email' => 'admin@mylocca.com',
         'firstName' => 'Admin',
         'lastName' => 'MYLOCCA',
         'password' => 'SecurePassword123'
     ])
     ->setStatus('ACTIVE');

$entityManager->persist($task);
$entityManager->flush();

// Exécuter immédiatement
$taskManager->executeTask($task);
```

---

### **Méthode 3 : Via la Base de Données**

```sql
INSERT INTO task (
    name, 
    type, 
    description, 
    frequency, 
    parameters, 
    status,
    created_at,
    updated_at
) VALUES (
    'Création Super Admin',
    'CREATE_SUPER_ADMIN',
    'Création automatique du compte super administrateur',
    'ONCE',
    '{"email":"admin@mylocca.com","firstName":"Admin","lastName":"MYLOCCA","password":"SecurePassword123"}',
    'ACTIVE',
    NOW(),
    NOW()
);
```

---

## 🔄 Comportement de la Tâche

### **Cas 1 : Création Réussie**

```
✅ Email n'existe pas
→ Création du compte avec ROLE_SUPER_ADMIN
→ Hash du mot de passe
→ Sauvegarde en base de données
→ Log de succès
```

**Log produit :**
```
[info] ✅ Super Administrateur créé avec succès : Admin MYLOCCA (admin@mylocca.com)
```

---

### **Cas 2 : Email Déjà Existant avec ROLE_SUPER_ADMIN**

```
⚠️ Email existe et possède déjà ROLE_SUPER_ADMIN
→ Aucune action
→ Log d'information
→ Tâche marquée comme COMPLETED
```

**Log produit :**
```
[info] Super Admin admin@mylocca.com existe déjà avec ce rôle
```

---

### **Cas 3 : Email Existant SANS ROLE_SUPER_ADMIN**

```
❌ Email existe mais avec un autre rôle (ROLE_ADMIN, ROLE_MANAGER, etc.)
→ Erreur levée
→ Tâche marquée comme FAILED
```

**Erreur produite :**
```
Un utilisateur avec l'email admin@mylocca.com existe déjà mais n'est pas super admin
```

---

### **Cas 4 : Paramètres Invalides**

```
❌ Email invalide, mot de passe trop court, ou paramètres manquants
→ Exception InvalidArgumentException
→ Tâche marquée comme FAILED
```

**Erreurs possibles :**
- `Email invalide ou manquant dans les paramètres de la tâche`
- `Prénom et nom requis dans les paramètres de la tâche`
- `Mot de passe manquant ou trop court (minimum 8 caractères)`

---

## 🔒 Sécurité

### **Bonnes Pratiques**

1. ✅ **Mot de passe fort** : Utilisez un mot de passe complexe (12+ caractères, majuscules, minuscules, chiffres, symboles)
2. ✅ **Stockage sécurisé** : Le mot de passe est hashé avec `UserPasswordHasherInterface`
3. ✅ **Tâche ONCE** : Utilisez la fréquence `ONCE` pour éviter les créations multiples
4. ✅ **Suppression après exécution** : Supprimez la tâche après création pour ne pas laisser le mot de passe en clair

### **Avertissements**

⚠️ **Le mot de passe est stocké en CLAIR dans les paramètres de la tâche** jusqu'à son exécution

**Recommandations :**
- Exécutez la tâche immédiatement après création
- Supprimez la tâche après exécution réussie
- Changez le mot de passe après la première connexion
- N'utilisez pas cette méthode en production si possible

---

## 📊 Exemples Complets

### **Exemple 1 : Création Simple**

```php
$task = new Task();
$task->setName('Init Super Admin')
     ->setType('CREATE_SUPER_ADMIN')
     ->setFrequency('ONCE')
     ->setParameters([
         'email' => 'superadmin@mylocca.com',
         'firstName' => 'Super',
         'lastName' => 'Admin',
         'password' => 'MyL0cc@2024!'
     ])
     ->setStatus('ACTIVE');

$em->persist($task);
$em->flush();

// Exécuter et supprimer immédiatement
$taskManager->executeTask($task);
$em->remove($task);
$em->flush();
```

---

### **Exemple 2 : Création avec Vérification**

```php
// Vérifier si le super admin existe déjà
$existingSuperAdmin = $userRepository->findOneBy(['email' => 'admin@mylocca.com']);

if (!$existingSuperAdmin) {
    $task = new Task();
    $task->setName('Création Super Admin Initial')
         ->setType('CREATE_SUPER_ADMIN')
         ->setFrequency('ONCE')
         ->setParameters([
             'email' => 'admin@mylocca.com',
             'firstName' => 'Administrateur',
             'lastName' => 'Système',
             'password' => bin2hex(random_bytes(16)) // Mot de passe aléatoire
         ])
         ->setStatus('ACTIVE');

    $em->persist($task);
    $em->flush();

    try {
        $taskManager->executeTask($task);
        $logger->info('Super Admin créé avec succès');
    } catch (\Exception $e) {
        $logger->error('Erreur création super admin: ' . $e->getMessage());
    } finally {
        // Toujours supprimer la tâche pour ne pas laisser le mot de passe
        $em->remove($task);
        $em->flush();
    }
}
```

---

## 🧪 Tests

### **Test 1 : Création Réussie**

```php
public function testCreateSuperAdmin(): void
{
    $task = new Task();
    $task->setType('CREATE_SUPER_ADMIN')
         ->setParameters([
             'email' => 'test@example.com',
             'firstName' => 'Test',
             'lastName' => 'User',
             'password' => 'TestPassword123'
         ])
         ->setStatus('ACTIVE');

    $this->taskManager->executeTask($task);

    $user = $this->userRepository->findOneBy(['email' => 'test@example.com']);
    $this->assertNotNull($user);
    $this->assertContains('ROLE_SUPER_ADMIN', $user->getRoles());
}
```

---

## 🔍 Logs et Debugging

### **Logs de Succès**

```
[2024-10-14 10:30:00] app.INFO: ✅ Super Administrateur créé avec succès : Admin MYLOCCA (admin@mylocca.com)
```

### **Logs d'Avertissement**

```
[2024-10-14 10:30:00] app.INFO: Super Admin admin@mylocca.com existe déjà avec ce rôle
```

### **Logs d'Erreur**

```
[2024-10-14 10:30:00] app.ERROR: Erreur lors de l'exécution de la tâche Création Super Admin: Email invalide ou manquant dans les paramètres de la tâche
```

---

## 📝 Checklist d'Utilisation

- [ ] Définir un email valide unique
- [ ] Choisir un mot de passe fort (12+ caractères)
- [ ] Créer la tâche avec fréquence ONCE
- [ ] Exécuter la tâche immédiatement
- [ ] Vérifier les logs pour confirmer la création
- [ ] Se connecter avec le nouveau compte
- [ ] Changer le mot de passe immédiatement
- [ ] Supprimer la tâche de la base de données

---

## ⚡ Alternative : Utiliser la Commande CLI

Pour une utilisation plus sécurisée, utilisez plutôt la commande interactive :

```bash
php bin/console app:create-super-admin
```

**Avantages :**
- ✅ Saisie interactive du mot de passe (masqué)
- ✅ Pas de stockage du mot de passe en base
- ✅ Validation en temps réel
- ✅ Plus sécurisé

---

## 🎓 Résumé

La tâche **CREATE_SUPER_ADMIN** permet de :
- ✅ Créer automatiquement des comptes super admin
- ✅ Valider les données avant création
- ✅ Éviter les doublons
- ✅ Logger toutes les opérations

**Utilisation recommandée :** Initialisation automatique de la plateforme ou scripts de déploiement

**Utilisation déconseillée :** Création manuelle régulière (préférer la commande CLI interactive)

