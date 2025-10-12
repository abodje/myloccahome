# 🔧 Amélioration du Système de Demandes

## 📋 Vue d'ensemble

Amélioration complète du système de création de demandes de maintenance avec gestion automatique des rôles et sécurisation des accès.

---

## ✨ Nouvelles Fonctionnalités

### **1. Gestion Automatique par Rôle**

#### **Pour les LOCATAIRES :**
- ✅ **Propriété pré-sélectionnée** : Seules leurs propriétés louées sont disponibles
- ✅ **Locataire automatique** : Le locataire est automatiquement défini
- ✅ **Interface simplifiée** : Le champ "Locataire" est masqué
- ✅ **Sécurité renforcée** : Impossible de créer une demande pour une autre propriété

#### **Pour les GESTIONNAIRES/ADMINS :**
- ✅ **Toutes les propriétés** : Accès à toutes les propriétés
- ✅ **Sélection manuelle** : Choix du locataire si nécessaire
- ✅ **Interface complète** : Tous les champs disponibles

---

## 🔧 Modifications Techniques

### **1. Contrôleur `MaintenanceRequestController`**

#### **Méthode `new()` améliorée :**
```php
// Préparation des options selon le rôle
$formOptions = [];
$isTenantView = $user && in_array('ROLE_TENANT', $user->getRoles());

if ($isTenantView) {
    $tenant = $user->getTenant();
    if ($tenant) {
        // Limitation aux propriétés louées
        $tenantProperties = $propertyRepository->findByTenantWithFilters($tenant->getId());
        $formOptions['is_tenant_view'] = true;
        $formOptions['tenant_properties'] = $tenantProperties;
        
        // Pré-remplissage automatique
        if (!empty($tenantProperties)) {
            $maintenanceRequest->setProperty($tenantProperties[0]);
        }
    }
}
```

#### **Validation sécurisée :**
```php
// Vérification de sécurité pour les locataires
if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
    $property = $maintenanceRequest->getProperty();
    $tenantProperties = $propertyRepository->findByTenantWithFilters($tenant->getId());
    
    if (!in_array($property, $tenantProperties)) {
        $this->addFlash('error', 'Vous ne pouvez créer une demande que pour vos propriétés louées.');
        return $this->redirectToRoute('app_maintenance_request_new');
    }
    
    // Attribution automatique du locataire
    $maintenanceRequest->setTenant($tenant);
}
```

### **2. Formulaire `MaintenanceRequestType`**

#### **Options dynamiques :**
```php
public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $isTenantView = $options['is_tenant_view'] ?? false;
    $tenantProperties = $options['tenant_properties'] ?? [];
    
    // Champ propriété adapté
    $builder->add('property', EntityType::class, [
        'class' => Property::class,
        'choice_label' => 'fullAddress',
        'choices' => $isTenantView ? $tenantProperties : null,
        // ...
    ]);
    
    // Champ locataire conditionnel
    if (!$isTenantView) {
        $builder->add('tenant', EntityType::class, [
            'class' => Tenant::class,
            // ...
        ]);
    }
}
```

#### **Configuration des options :**
```php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => MaintenanceRequest::class,
        'is_tenant_view' => false,
        'tenant_properties' => [],
    ]);
}
```

### **3. Template `new.html.twig`**

#### **Affichage conditionnel :**
```twig
<div class="row">
    <div class="col-md-6">
        {{ form_label(form.property) }}
        {{ form_widget(form.property) }}
        {{ form_errors(form.property) }}
    </div>
    {% if not is_tenant_view %}
    <div class="col-md-6">
        {{ form_label(form.tenant) }}
        {{ form_widget(form.tenant) }}
        {{ form_errors(form.tenant) }}
    </div>
    {% endif %}
</div>
```

---

## 🛡️ Sécurité Renforcée

### **1. Contrôle d'Accès**
- ✅ **Validation côté serveur** : Vérification des propriétés autorisées
- ✅ **Prévention des injections** : Utilisation des repositories sécurisés
- ✅ **Messages d'erreur explicites** : Information claire en cas de violation

### **2. Attribution Automatique**
- ✅ **Locataire automatique** : Pas de manipulation possible
- ✅ **Propriété validée** : Seules les propriétés louées acceptées
- ✅ **Statut par défaut** : "En attente" et "Normale" automatiquement

### **3. Interface Adaptée**
- ✅ **Champs masqués** : Inutiles pour les locataires
- ✅ **Pré-remplissage** : Expérience utilisateur optimisée
- ✅ **Validation visuelle** : Feedback immédiat

---

## 🎯 Expérience Utilisateur

### **Pour les LOCATAIRES :**
1. **Clic sur "Nouvelle demande"** → Interface simplifiée
2. **Propriété pré-sélectionnée** → Plus de confusion
3. **Remplissage du formulaire** → Champs essentiels uniquement
4. **Soumission** → Validation et création automatique

### **Pour les GESTIONNAIRES :**
1. **Clic sur "Nouvelle demande"** → Interface complète
2. **Sélection de la propriété** → Toutes disponibles
3. **Choix du locataire** → Si nécessaire
4. **Soumission** → Création avec contrôle total

---

## 📊 Flux de Données

### **Création d'une Demande :**

```mermaid
graph TD
    A[Utilisateur clique "Nouvelle demande"] --> B{Rôle ?}
    B -->|LOCATAIRE| C[Charger propriétés du locataire]
    B -->|GESTIONNAIRE/ADMIN| D[Charger toutes les propriétés]
    
    C --> E[Pré-remplir formulaire]
    D --> F[Formulaire vide]
    
    E --> G[Utilisateur remplit le formulaire]
    F --> G
    
    G --> H[Soumission du formulaire]
    H --> I{Validation}
    
    I -->|ÉCHEC| J[Afficher erreur]
    I -->|SUCCÈS| K[Créer la demande]
    
    K --> L[Attribuer automatiquement le locataire]
    L --> M[Définir statut "En attente"]
    M --> N[Enregistrer en base]
    N --> O[Rediriger vers la demande]
```

---

## 🔍 Tests de Validation

### **1. Test Locataire :**
```bash
# Se connecter en tant que locataire
# Aller sur /mes-demandes/nouvelle
# Vérifier :
- ✅ Seule sa propriété est visible
- ✅ Champ "Locataire" masqué
- ✅ Propriété pré-sélectionnée
```

### **2. Test Gestionnaire :**
```bash
# Se connecter en tant que gestionnaire
# Aller sur /mes-demandes/nouvelle
# Vérifier :
- ✅ Toutes les propriétés visibles
- ✅ Champ "Locataire" disponible
- ✅ Formulaire complet
```

### **3. Test Sécurité :**
```bash
# Tentative de manipulation POST
# Vérifier :
- ✅ Validation côté serveur
- ✅ Message d'erreur approprié
- ✅ Redirection sécurisée
```

---

## 📝 Fichiers Modifiés

1. ✅ **src/Controller/MaintenanceRequestController.php**
   - Méthode `new()` complètement refactorisée
   - Gestion des rôles et sécurité
   - Pré-remplissage automatique

2. ✅ **src/Form/MaintenanceRequestType.php**
   - Options dynamiques ajoutées
   - Affichage conditionnel des champs
   - Configuration des options

3. ✅ **templates/maintenance_request/new.html.twig**
   - Affichage conditionnel du champ locataire
   - Interface adaptée selon le rôle

---

## 🚀 Avantages

### **Sécurité :**
- ✅ **Contrôle d'accès strict** : Impossible de contourner les restrictions
- ✅ **Validation robuste** : Double vérification côté client et serveur
- ✅ **Attribution automatique** : Pas de manipulation possible

### **Expérience Utilisateur :**
- ✅ **Interface adaptée** : Champs pertinents selon le rôle
- ✅ **Pré-remplissage intelligent** : Gain de temps
- ✅ **Feedback clair** : Messages d'erreur explicites

### **Maintenabilité :**
- ✅ **Code modulaire** : Séparation des responsabilités
- ✅ **Configuration flexible** : Options facilement modifiables
- ✅ **Documentation complète** : Compréhension aisée

---

## 📞 Support

Pour tester les améliorations :

1. **Videz le cache Symfony :**
   ```bash
   php bin/console cache:clear
   ```

2. **Testez avec différents rôles :**
   - Locataire : Interface simplifiée
   - Gestionnaire : Interface complète
   - Admin : Accès total

3. **Vérifiez la sécurité :**
   - Tentative de manipulation
   - Validation des données
   - Attribution automatique

---

**Date de mise à jour :** 12 octobre 2025  
**Version :** 1.0  
**Statut :** ✅ Implémenté et sécurisé
