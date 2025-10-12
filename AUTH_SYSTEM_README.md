# 🔐 Système d'authentification et permissions - MYLOCCA

## 📋 Vue d'ensemble

Le système d'authentification de MYLOCCA permet une gestion complète des utilisateurs avec 3 niveaux de permissions :

1. **ADMIN** : Accès complet à toute l'application
2. **MANAGER (Gestionnaire)** : Accès aux biens et locataires qu'il gère
3. **TENANT (Locataire)** : Accès uniquement à ses propres informations

## 🗂️ Fichiers créés

### Entités

#### `src/Entity/User.php`
Entité principale pour la gestion des utilisateurs avec :
- Implémente `UserInterface` et `PasswordAuthenticatedUserInterface` de Symfony
- Champs : email, password, roles, firstName, lastName, phone, isActive
- Relations :
  - `OneToOne` avec `Tenant` (un User peut être un locataire)
  - `OneToOne` avec `Owner` (un User peut être un gestionnaire)
- Méthodes de vérification : `isAdmin()`, `isManager()`, `isTenant()`

#### Modifications des entités existantes

**`src/Entity/Tenant.php`**
- Ajout d'une relation `OneToOne` avec `User`
- Permet à un locataire d'avoir un compte de connexion

**`src/Entity/Owner.php`**
- Ajout d'une relation `OneToOne` avec `User`
- Permet à un gestionnaire d'avoir un compte de connexion

### Repository

#### `src/Repository/UserRepository.php`
Méthodes disponibles :
- `findByEmail(string $email)` : Trouver par email
- `findActive()` : Tous les utilisateurs actifs
- `findByRole(string $role)` : Filtrer par rôle
- `findAdmins()` : Tous les administrateurs
- `findManagers()` : Tous les gestionnaires
- `findTenants()` : Tous les locataires

### Configuration

#### `config/packages/security.yaml`
Configuration complète de la sécurité :

**Providers** :
```yaml
app_user_provider:
    entity:
        class: App\Entity\User
        property: email
```

**Firewall** :
- Authentification par formulaire
- Remember me (1 semaine)
- Logout configuré
- Switch user activé (pour les admins)

**Access Control** :
- `/login` et `/register` : PUBLIC
- `/admin/*` : ROLE_ADMIN uniquement
- `/biens`, `/locataires`, `/contrats` : ROLE_MANAGER ou ROLE_ADMIN
- `/mes-*`, `/mon-profil` : ROLE_USER (tous les utilisateurs connectés)

**Hiérarchie des rôles** :
```yaml
role_hierarchy:
    ROLE_TENANT: ROLE_USER
    ROLE_MANAGER: [ROLE_USER, ROLE_TENANT]
    ROLE_ADMIN: [ROLE_MANAGER, ROLE_USER]
```

### Contrôleurs

#### `src/Controller/SecurityController.php`
Deux actions :
- `login()` : Affiche le formulaire de connexion
- `logout()` : Déconnexion (géré par Symfony)

### Templates

#### `templates/security/login.html.twig`
Page de connexion professionnelle avec :
- Design moderne en deux colonnes
- Formulaire de connexion avec CSRF
- Option "Se souvenir de moi"
- Messages d'erreur
- Responsive

## 🚀 Les 3 rôles

### 1. ROLE_ADMIN (Administrateur)

**Permissions** :
- ✅ Accès complet à `/admin`
- ✅ Voir et gérer TOUS les biens
- ✅ Voir et gérer TOUS les locataires
- ✅ Voir et gérer TOUS les contrats
- ✅ Voir et gérer TOUS les paiements
- ✅ Accès aux paramètres système
- ✅ Gestion des devises
- ✅ Gestion des tâches automatisées
- ✅ Peut se faire passer pour n'importe quel utilisateur (switch_user)

**Menu visible** :
- Dashboard
- Mes biens
- Locataires
- Contrats
- Paiements
- Comptabilité
- Demandes
- Documents
- **Administration** ⭐
- Profil

### 2. ROLE_MANAGER (Gestionnaire)

**Permissions** :
- ✅ Voir et gérer les biens qu'il possède (via relation Owner)
- ✅ Voir et gérer les locataires de ses biens
- ✅ Voir et gérer les contrats de ses biens
- ✅ Voir les paiements de ses locataires
- ✅ Voir la comptabilité de ses biens
- ❌ PAS d'accès à l'administration
- ❌ Ne peut pas voir les biens/locataires des autres gestionnaires

**Menu visible** :
- Dashboard (ses statistiques uniquement)
- Mes biens
- Locataires (de ses biens)
- Contrats (de ses biens)
- Paiements (de ses locataires)
- Comptabilité (de ses biens)
- Demandes (de ses biens)
- Documents (de ses biens)
- Profil

### 3. ROLE_TENANT (Locataire)

**Permissions** :
- ✅ Voir son bail actuel
- ✅ Voir ses paiements
- ✅ Voir ses documents
- ✅ Créer des demandes de maintenance
- ✅ Modifier son profil
- ❌ NE PEUT PAS voir les informations des autres locataires
- ❌ NE PEUT PAS voir les biens qui ne sont pas les siens
- ❌ PAS d'accès à l'administration
- ❌ PAS d'accès à la comptabilité complète

**Menu visible** :
- Dashboard (ses statistiques uniquement)
- Mon bail
- Mes paiements
- Mes demandes
- Mes documents
- Mon profil

## 🔒 Sécurité

### CSRF Protection
Tous les formulaires incluent la protection CSRF :
```twig
<input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">
```

### Password Hashing
Utilisation de l'algorithme auto de Symfony (bcrypt/argon2):
```yaml
password_hashers:
    App\Entity\User:
        algorithm: auto
```

### Remember Me
Cookie sécurisé de 7 jours :
```yaml
remember_me:
    secret: '%kernel.secret%'
    lifetime: 604800
    path: /
    always_remember_me: true
```

## 📊 Filtrage des données par rôle

### Pour les gestionnaires

Dans les contrôleurs, filtrer par propriétaire :

```php
public function index(): Response
{
    $user = $this->getUser();
    
    if ($user->isAdmin()) {
        // Admin voit tout
        $properties = $propertyRepository->findAll();
    } elseif ($user->isManager() && $user->getOwner()) {
        // Manager voit uniquement ses biens
        $properties = $user->getOwner()->getProperties();
    }
    
    return $this->render('property/index.html.twig', [
        'properties' => $properties,
    ]);
}
```

### Pour les locataires

Dans les contrôleurs, filtrer par locataire :

```php
public function myPayments(): Response
{
    $user = $this->getUser();
    
    if ($user->isTenant() && $user->getTenant()) {
        $tenant = $user->getTenant();
        $lease = $tenant->getCurrentLease();
        
        if ($lease) {
            $payments = $paymentRepository->findByLease($lease);
        }
    }
    
    return $this->render('payment/index.html.twig', [
        'payments' => $payments ?? [],
    ]);
}
```

## 🎨 Adaptation des templates

### Affichage conditionnel du menu

```twig
{% if is_granted('ROLE_ADMIN') %}
    <a href="{{ path('app_admin') }}" class="nav-link">
        <i class="bi bi-gear"></i> Administration
    </a>
{% endif %}

{% if is_granted('ROLE_MANAGER') %}
    <a href="{{ path('app_property_index') }}" class="nav-link">
        <i class="bi bi-building"></i> Mes biens
    </a>
    <a href="{{ path('app_tenant_index') }}" class="nav-link">
        <i class="bi bi-people"></i> Locataires
    </a>
{% endif %}

{% if is_granted('ROLE_TENANT') %}
    <a href="{{ path('app_my_lease') }}" class="nav-link">
        <i class="bi bi-file-earmark-text"></i> Mon bail
    </a>
    <a href="{{ path('app_payment_index') }}" class="nav-link">
        <i class="bi bi-credit-card"></i> Mes paiements
    </a>
{% endif %}
```

### Vérifications dans les templates

```twig
{% if app.user.isAdmin() %}
    <button class="btn btn-danger">Supprimer</button>
{% endif %}

{% if app.user.isManager() or app.user.isAdmin() %}
    <a href="{{ path('app_property_edit', {id: property.id}) }}">Modifier</a>
{% endif %}
```

## 🔨 Migration de la base de données

### Créer la migration

```bash
php bin/console make:migration
```

### Appliquer la migration

```bash
php bin/console doctrine:migrations:migrate
```

## 👤 Création des utilisateurs

### Créer un administrateur

```php
// Dans une commande ou un DataFixtures
$user = new User();
$user->setEmail('admin@mylocca.com');
$user->setFirstName('Admin');
$user->setLastName('MYLOCCA');
$user->setRoles(['ROLE_ADMIN']);
$user->setPassword($passwordHasher->hashPassword($user, 'admin123'));

$entityManager->persist($user);
$entityManager->flush();
```

### Créer un gestionnaire lié à un Owner

```php
$owner = new Owner();
$owner->setFirstName('Jean');
$owner->setLastName('Dupont');
$owner->setEmail('jean.dupont@example.com');

$user = new User();
$user->setEmail('jean.dupont@example.com');
$user->setFirstName('Jean');
$user->setLastName('Dupont');
$user->setRoles(['ROLE_MANAGER']);
$user->setPassword($passwordHasher->hashPassword($user, 'password123'));

// Lier l'owner au user
$owner->setUser($user);
// ou
$user->setOwner($owner);

$entityManager->persist($owner);
$entityManager->persist($user);
$entityManager->flush();
```

### Créer un locataire lié à un Tenant

```php
$tenant = new Tenant();
$tenant->setFirstName('Marie');
$tenant->setLastName('Martin');
$tenant->setEmail('marie.martin@example.com');

$user = new User();
$user->setEmail('marie.martin@example.com');
$user->setFirstName('Marie');
$user->setLastName('Martin');
$user->setRoles(['ROLE_TENANT']);
$user->setPassword($passwordHasher->hashPassword($user, 'password123'));

// Lier le tenant au user
$tenant->setUser($user);
// ou
$user->setTenant($tenant);

$entityManager->persist($tenant);
$entityManager->persist($user);
$entityManager->flush();
```

## 🧪 Test du système

### 1. Connexion

Accédez à `/login` et connectez-vous avec :
- Email : admin@mylocca.com
- Mot de passe : admin123

### 2. Vérifier le rôle

Dans un contrôleur :
```php
$user = $this->getUser();
dump($user->getRoles());
dump($user->isAdmin());
dump($user->isManager());
dump($user->isTenant());
```

### 3. Tester les permissions

Essayez d'accéder à :
- `/admin` → Doit être accessible uniquement en tant qu'ADMIN
- `/biens` → Accessible en tant que MANAGER ou ADMIN
- `/mes-paiements` → Accessible pour tous les utilisateurs connectés

### 4. Switch User (pour les admins)

En tant qu'admin, vous pouvez vous faire passer pour un autre utilisateur :
```
/mes-paiements?_switch_user=marie.martin@example.com
```

Pour revenir à votre compte admin :
```
/mes-paiements?_switch_user=_exit
```

## 📝 TODO après installation

- [ ] Créer la migration : `php bin/console make:migration`
- [ ] Appliquer la migration : `php bin/console doctrine:migrations:migrate`
- [ ] Créer un utilisateur admin
- [ ] Mettre à jour les contrôleurs pour filtrer par rôle
- [ ] Mettre à jour le menu principal (base.html.twig)
- [ ] Créer les Voters pour les permissions fines
- [ ] Tester chaque niveau d'accès

## 🎯 Prochaines étapes

1. **Créer les Voters** pour des permissions plus fines
2. **Mettre à jour tous les contrôleurs** avec les filtres par rôle
3. **Adapter le menu** dans base.html.twig selon les rôles
4. **Créer une interface d'inscription** (optionnel)
5. **Ajouter la récupération de mot de passe**
6. **Créer une interface de gestion des utilisateurs** (admin)

## ✅ Conclusion

Le système d'authentification est maintenant prêt ! Il ne reste plus qu'à :
1. Créer et appliquer la migration
2. Créer les utilisateurs de test
3. Mettre à jour les contrôleurs existants
4. Adapter les templates

**Status** : ⚙️ Configuration de base complète - Nécessite migrations et adaptations des contrôleurs

