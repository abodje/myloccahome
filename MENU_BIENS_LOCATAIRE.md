# 🏠 Menu "Mes biens" pour les Locataires

## 📋 Vue d'ensemble

Le menu "Mes biens" est maintenant accessible aux locataires et affiche uniquement les propriétés qu'ils louent, avec des fonctionnalités adaptées selon leur rôle.

---

## ✅ Modifications Apportées

### **1. Service de Menu (`MenuService.php`)**

**Modification :** Ajout du rôle `ROLE_TENANT` au menu "Mes biens"

```php
'properties' => [
    'label' => 'Mes biens',
    'icon' => 'bi-building',
    'route' => 'app_property_index',
    'roles' => ['ROLE_USER', 'ROLE_TENANT', 'ROLE_MANAGER', 'ROLE_ADMIN'], // ✅ ROLE_TENANT ajouté
    'order' => 3,
],
```

**Avant :** Seuls `ROLE_MANAGER` et `ROLE_ADMIN` pouvaient voir ce menu  
**Après :** Tous les utilisateurs connectés (y compris les locataires) peuvent voir le menu

---

### **2. Contrôleur Propriétés (`PropertyController.php`)**

**Modification :** Filtrage des propriétés selon le rôle de l'utilisateur

#### **Nouvelle logique de filtrage :**

```php
public function index(PropertyRepository $propertyRepository, Request $request): Response
{
    /** @var \App\Entity\User|null $user */
    $user = $this->getUser();
    
    // Filtrer selon le rôle
    if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
        // LOCATAIRE : propriétés qu'il loue uniquement
        $tenant = $user->getTenant();
        if ($tenant) {
            $properties = $propertyRepository->findByTenantWithFilters($tenant->getId(), ...);
        }
    } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
        // GESTIONNAIRE : ses propriétés
        $owner = $user->getOwner();
        if ($owner) {
            $properties = $propertyRepository->findByOwnerWithFilters($owner->getId(), ...);
        }
    } else {
        // ADMIN : toutes les propriétés
        $properties = $propertyRepository->findWithFilters(...);
    }
    
    $isTenantView = $user && in_array('ROLE_TENANT', $user->getRoles());
}
```

#### **Fonctionnalités par rôle :**

| Rôle | Propriétés Affichées | Actions Disponibles |
|------|---------------------|-------------------|
| **LOCATAIRE** | Celles qu'il loue | Consultation + Demande maintenance |
| **GESTIONNAIRE** | Ses propriétés | Consultation + Création + Gestion |
| **ADMIN** | Toutes les propriétés | Toutes les actions |

---

### **3. Repository Propriétés (`PropertyRepository.php`)**

**Nouvelles méthodes ajoutées :**

#### **`findByTenantWithFilters()`**
```php
public function findByTenantWithFilters(int $tenantId, ?string $search = null, ?string $status = null, ?string $type = null): array
{
    $qb = $this->createQueryBuilder('p')
        ->join('p.leases', 'l')
        ->where('l.tenant = :tenantId')
        ->andWhere('l.status = :leaseStatus')
        ->setParameter('tenantId', $tenantId)
        ->setParameter('leaseStatus', 'active');
    
    // Filtres additionnels (recherche, statut, type)
    // ...
}
```

**Logique de filtrage :** Recherche les propriétés avec des baux actifs du locataire

#### **`findByOwnerWithFilters()`**
```php
public function findByOwnerWithFilters(int $ownerId, ?string $search = null, ?string $status = null, ?string $type = null): array
{
    $qb = $this->createQueryBuilder('p')
        ->where('p.owner = :ownerId')
        ->setParameter('ownerId', $ownerId);
    
    // Filtres additionnels
    // ...
}
```

**Logique de filtrage :** Recherche les propriétés appartenant au propriétaire/gestionnaire

---

### **4. Template Propriétés (`property/index.html.twig`)**

**Modifications :**

#### **Masquage des actions pour les locataires**
```twig
{% block page_actions %}
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
            Faire une demande
        </button>
        {% if not is_tenant_view %}
        <a href="{{ path('app_property_new') }}" class="btn btn-outline-primary">
            <i class="bi bi-plus-circle me-2"></i>
            Ajouter un bien
        </a>
        {% endif %}
    </div>
{% endblock %}
```

**Résultat :**
- ✅ **Locataires** : Peuvent faire des demandes de maintenance
- ❌ **Locataires** : Ne peuvent pas ajouter de nouveaux biens
- ✅ **Gestionnaires/Admins** : Toutes les actions disponibles

---

## 🎯 Résultat Final

### **Pour un LOCATAIRE connecté :**

#### **Menu visible :**
- ✅ Mon tableau de bord
- ✅ Mes demandes  
- ✅ Ma comptabilité
- ✅ Mes paiements
- ✅ **Mes biens** ← **NOUVEAU**
- ✅ Mes documents

#### **Page "Mes biens" :**
- **Propriétés affichées :** Uniquement celles qu'il loue avec des baux actifs
- **Actions disponibles :**
  - ✅ Consulter ses propriétés
  - ✅ Faire des demandes de maintenance
  - ✅ Filtrer par recherche/statut/type
  - ❌ Ajouter de nouveaux biens

#### **Exemple d'affichage pour un locataire :**
```
Propriétés que je loue :
┌─────────────────────────────────────────┐
│ 🏠 Appartement T3 - 75m²               │
│ 📍 123 Rue de la Paix, Paris           │
│ 💰 1 200 €/mois                        │
│ 📅 Bail actif depuis 01/01/2024        │
│ 🔧 [Faire une demande]                 │
└─────────────────────────────────────────┘
```

---

## 🔒 Sécurité et Isolation

### **Isolation des Données**
- ✅ **Locataires** : Ne voient que les propriétés qu'ils louent
- ✅ **Gestionnaires** : Voient leurs propres propriétés
- ✅ **Admins** : Voient toutes les propriétés

### **Logique de Filtrage**
Les propriétés sont filtrées selon :
1. **Pour locataires** : Baux actifs liés au locataire
2. **Pour gestionnaires** : Propriétés appartenant au propriétaire
3. **Pour admins** : Aucun filtre (accès total)

### **Relations entre Entités**
```
User (ROLE_TENANT)
  └─> Tenant
       └─> Lease(s) [status = 'active']
            └─> Property(ies)

User (ROLE_MANAGER)
  └─> Owner
       └─> Property(ies)
```

---

## 🎮 Test de la Fonctionnalité

### **1. Test en tant que Locataire**
```bash
# Se connecter avec un compte locataire
# Naviguer vers /mes-biens/
```

**Résultat attendu :**
- Menu "Mes biens" visible dans la sidebar
- Page affiche uniquement les propriétés louées
- Bouton "Ajouter un bien" masqué
- Bouton "Faire une demande" disponible

### **2. Test en tant que Gestionnaire**
```bash
# Se connecter avec un compte gestionnaire
# Naviguer vers /mes-biens/
```

**Résultat attendu :**
- Menu "Mes biens" visible
- Page affiche les propriétés du gestionnaire
- Toutes les actions disponibles

### **3. Test en tant qu'Admin**
```bash
# Se connecter avec un compte admin
# Naviguer vers /mes-biens/
```

**Résultat attendu :**
- Menu "Mes biens" visible
- Page affiche toutes les propriétés
- Toutes les actions disponibles

---

## 📊 Fonctionnalités par Rôle

### **Pour Locataires :**
- ✅ **Consultation** : Voir les détails de leurs propriétés
- ✅ **Demandes** : Créer des demandes de maintenance
- ✅ **Filtrage** : Rechercher et filtrer leurs propriétés
- ❌ **Gestion** : Ne peuvent pas créer/modifier des propriétés

### **Pour Gestionnaires :**
- ✅ **Consultation** : Voir leurs propriétés
- ✅ **Gestion** : Créer, modifier, supprimer des propriétés
- ✅ **Demandes** : Gérer les demandes de maintenance
- ✅ **Filtrage** : Rechercher et filtrer leurs propriétés

### **Pour Admins :**
- ✅ **Consultation** : Voir toutes les propriétés
- ✅ **Gestion** : Gérer toutes les propriétés
- ✅ **Administration** : Accès complet au système

---

## 🔄 Relations et Jointures

### **Requête pour Locataires :**
```sql
SELECT p.* FROM property p
JOIN lease l ON p.id = l.property_id
WHERE l.tenant_id = ? 
AND l.status = 'active'
```

### **Requête pour Gestionnaires :**
```sql
SELECT p.* FROM property p
WHERE p.owner_id = ?
```

### **Requête pour Admins :**
```sql
SELECT p.* FROM property p
-- Aucun filtre
```

---

## 📝 Fichiers Modifiés

1. ✅ **src/Service/MenuService.php**
   - Ajout de `ROLE_TENANT` au menu "Mes biens"

2. ✅ **src/Controller/PropertyController.php**
   - Filtrage par rôle dans `index()`
   - Passage de `is_tenant_view` au template

3. ✅ **src/Repository/PropertyRepository.php**
   - `findByTenantWithFilters()`
   - `findByOwnerWithFilters()`

4. ✅ **templates/property/index.html.twig**
   - Masquage du bouton "Ajouter un bien" pour locataires

---

## 🚀 Avantages

### **Pour les Locataires :**
- ✅ **Transparence** : Voir les propriétés qu'ils louent
- ✅ **Convenance** : Accès rapide aux informations de leurs biens
- ✅ **Communication** : Faire des demandes de maintenance facilement
- ✅ **Simplicité** : Interface épurée, sans fonctions complexes

### **Pour les Gestionnaires :**
- ✅ **Vision claire** : Voir leurs propriétés organisées
- ✅ **Gestion efficace** : Créer et modifier des propriétés
- ✅ **Suivi** : Suivre les demandes de leurs locataires

### **Pour les Admins :**
- ✅ **Vue d'ensemble** : Accès complet à toutes les propriétés
- ✅ **Gestion globale** : Toutes les fonctionnalités disponibles

---

## 📞 Support

Pour tester la fonctionnalité :

1. **Connectez-vous en tant que locataire**
2. **Vérifiez que le menu "Mes biens" apparaît**
3. **Cliquez dessus pour voir vos propriétés**
4. **Testez le bouton "Faire une demande"**

---

**Date de mise à jour :** 12 octobre 2025  
**Version :** 1.0  
**Statut :** ✅ Implémenté et testé
