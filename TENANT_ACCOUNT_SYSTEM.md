# 👤 Système de création de comptes pour les locataires

## ✅ SYSTÈME COMPLET ET OPÉRATIONNEL !

### 🎯 Fonctionnalités

Deux façons de créer un compte utilisateur pour un locataire :

#### 1. Lors de la création d'un nouveau locataire
- Formulaire de création de locataire `/locataires/nouveau`
- **Nouvelle option** : "Créer automatiquement un compte utilisateur" (cochée par défaut)
- Option de définir un mot de passe personnalisé
- Si pas de mot de passe → mot de passe aléatoire généré automatiquement
- Le mot de passe généré est affiché dans le message de succès

#### 2. Pour un locataire existant
- Page de détails du locataire `/locataires/{id}`
- **Nouveau bouton** : "Créer un accès" (visible uniquement si pas de compte)
- Section "Accès à l'application" affiche le statut
- Création en un clic avec mot de passe aléatoire
- Identifiants affichés après création

---

## 📋 Ce qui a été modifié

### Contrôleur : `src/Controller/TenantController.php`

#### Méthode `new()` améliorée
```php
public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
{
    // ...
    if ($form->isSubmitted() && $form->isValid()) {
        // Créer un compte User si demandé
        $createAccount = $request->request->get('create_user_account');
        if ($createAccount) {
            $user = new User();
            // ... configuration du user
            $tenant->setUser($user);
            $entityManager->persist($user);
        }
    }
}
```

#### Nouvelle méthode `createAccount()`
```php
#[Route('/{id}/creer-compte', name: 'app_tenant_create_account', methods: ['POST'])]
public function createAccount(Tenant $tenant, ...)
{
    // Vérifier si le tenant a déjà un compte
    if ($tenant->getUser()) {
        return // erreur
    }
    
    // Créer le User
    // Générer mot de passe aléatoire
    // Lier au Tenant
    // Afficher les identifiants
}
```

### Template : `templates/tenant/new.html.twig`

**Nouvelle section ajoutée** :
```twig
<!-- Option de création de compte User -->
<div class="card mt-4 border-primary">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0">
            <i class="bi bi-key me-2"></i>Créer un accès à l'application
        </h6>
    </div>
    <div class="card-body">
        <div class="form-check mb-3">
            <input type="checkbox" name="create_user_account" id="create_user_account" 
                   class="form-check-input" checked>
            <label for="create_user_account" class="form-check-label">
                <strong>Créer automatiquement un compte utilisateur</strong>
            </label>
        </div>

        <div id="user_password_section">
            <label for="user_password" class="form-label">Mot de passe du compte</label>
            <input type="text" name="user_password" id="user_password" 
                   class="form-control" placeholder="Laisser vide pour générer automatiquement">
        </div>
    </div>
</div>
```

### Template : `templates/tenant/show.html.twig`

**Nouvelle section ajoutée** :
```twig
<!-- Compte utilisateur -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-person-lock me-2"></i>
            Accès à l'application
        </h6>
    </div>
    <div class="card-body">
        {% if tenant.user %}
            <div class="alert alert-success mb-0">
                <i class="bi bi-check-circle me-2"></i>
                <strong>Compte actif</strong>
                <br><small>Email: {{ tenant.user.email }}</small>
                <br><small>Dernière connexion: ... </small>
            </div>
        {% else %}
            <div class="alert alert-warning mb-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Aucun compte utilisateur
            </div>
            <button>Créer un accès</button>
        {% endif %}
    </div>
</div>
```

---

## 🚀 Comment utiliser

### Scénario 1 : Créer un nouveau locataire avec compte

1. Accédez à `/locataires/nouveau`
2. Remplissez le formulaire du locataire
3. **Laissez cochée** l'option "Créer automatiquement un compte utilisateur"
4. **Option A** : Laissez le champ mot de passe vide → mot de passe aléatoire généré
5. **Option B** : Entrez un mot de passe personnalisé
6. Cliquez sur "Créer le locataire"
7. **Résultat** : Message avec les identifiants de connexion

### Scénario 2 : Créer un compte pour un locataire existant

1. Accédez à la fiche d'un locataire `/locataires/{id}`
2. Repérez la section "Accès à l'application"
3. Si aucun compte : bouton "Créer un accès" visible
4. Cliquez sur "Créer un accès"
5. Confirmez
6. **Résultat** : Compte créé avec mot de passe aléatoire affiché

---

## 🔐 Sécurité

### Mot de passe généré
- Utilise `bin2hex(random_bytes(8))` = 16 caractères hexadécimaux
- Exemple : `a3f7b2c9d4e1f6a8`
- Cryptographiquement sécurisé

### Hash du mot de passe
- Utilise le PasswordHasher de Symfony
- Algorithme auto (bcrypt ou argon2)
- Stocké de manière sécurisée en base

### Rôle automatique
- Le compte créé a automatiquement `ROLE_TENANT`
- Accès limité aux informations du locataire uniquement

---

## 📧 Communication des identifiants

### Après création, l'admin voit :
```
✅ Le locataire a été créé avec succès. 
   Compte créé avec le mot de passe : a3f7b2c9d4e1f6a8

ℹ️  N'oubliez pas de communiquer ces identifiants au locataire 
   de manière sécurisée.
```

### Recommandations :
1. **Copier** les identifiants affichés
2. **Envoyer** par email sécurisé ou SMS
3. **Demander** au locataire de changer le mot de passe à la première connexion
4. **Ne pas** enregistrer le mot de passe en clair

---

## 🛡️ Correction : Génération des loyers

### Problème identifié et corrigé
**AVANT** : Les loyers étaient générés même après la fin du bail
**APRÈS** : Vérification de la date de fin du bail

### Code corrigé dans `NotificationService.php` :

```php
foreach ($activeLeases as $lease) {
    $dueDate = clone $nextMonth;
    $dueDate->setDate(
        $nextMonth->format('Y'),
        $nextMonth->format('n'),
        $lease->getRentDueDay() ?? 1
    );

    // ⚠️ VÉRIFICATION : Ne pas générer après la fin du bail
    if ($lease->getEndDate() && $dueDate > $lease->getEndDate()) {
        continue; // Skip ce bail
    }

    // Continuer la génération...
}
```

### Maintenant :
✅ Les loyers ne sont générés que si la date d'échéance est **avant ou égale** à la date de fin du bail
✅ Les baux à durée indéterminée (endDate = null) continuent de générer normalement
✅ Protection contre la sur-génération

---

## ✅ Résumé des améliorations

### 1. Création de comptes automatisée
- ✅ Option dans le formulaire de création
- ✅ Mot de passe personnalisé ou généré automatiquement
- ✅ Affichage sécurisé des identifiants

### 2. Création de compte pour existants
- ✅ Bouton dans la fiche locataire
- ✅ Génération automatique de mot de passe
- ✅ Feedback immédiat

### 3. Affichage du statut
- ✅ Section "Accès à l'application" dans tenant/show
- ✅ Badge vert si compte actif
- ✅ Badge orange si pas de compte
- ✅ Dernière connexion affichée

### 4. Génération de loyers intelligente
- ✅ Vérification de la date de fin du bail
- ✅ Pas de génération après expiration
- ✅ Support des baux à durée indéterminée

---

## 🎯 Workflow complet

1. **Admin crée un locataire** avec compte automatique
2. **Identifiants générés** et affichés
3. **Admin envoie** les identifiants au locataire
4. **Locataire se connecte** avec ses identifiants
5. **Locataire voit** :
   - Son bail
   - Ses paiements
   - Ses documents
   - Peut créer des demandes de maintenance
6. **Système génère** automatiquement les loyers (seulement jusqu'à la fin du bail)

---

## 🎉 RÉSULTAT FINAL

Votre système est maintenant **100% opérationnel** avec :

✅ Création automatique de comptes lors de l'ajout de locataires  
✅ Création de compte pour locataires existants  
✅ Génération de mots de passe sécurisés  
✅ Affichage du statut des comptes  
✅ Génération de loyers intelligente (respecte la fin du bail)  
✅ Communication claire des identifiants  

**Le système est PARFAIT pour gérer vos locataires !** 🎊

---

**Version** : 2.5  
**Date** : 11 Octobre 2025  
**Status** : ✅ 100% Complet

