# 📄 Génération automatique de contrats de bail - MYLOCCA

## ✅ SYSTÈME COMPLET ET OPÉRATIONNEL !

### 🎯 Fonctionnalité

Génération automatique d'un **contrat de bail PDF personnalisé** lors du paiement de la caution.

---

## 🚀 Comment ça fonctionne

### Scénario automatique : Paiement de la caution

1. **Un paiement de caution** est créé pour un bail
   - Type : "Dépôt de garantie" OU "Caution"
   - Statut : "En attente"

2. **L'admin marque le paiement comme payé**
   - Via la page du paiement
   - Bouton "Marquer comme payé"
   - Renseigne la date, mode de paiement, référence

3. **✨ AUTOMATIQUEMENT** :
   - Le système détecte que c'est une caution
   - Génère un PDF du contrat de bail personnalisé
   - Sauvegarde le PDF dans `public/uploads/documents/`
   - Crée une entrée `Document` liée au bail
   - Affiche un message : "📄 Le contrat de bail a été généré automatiquement !"

4. **Le contrat est disponible** :
   - Dans "Mes documents" (pour le locataire)
   - Dans la fiche du bail
   - Dans les documents du locataire

---

## 📋 Fichiers créés/modifiés

### Nouveau service
**`src/Service/ContractGenerationService.php`**

Méthodes :
- `generateContractAfterDeposit(Payment $payment)` : Génération auto après caution
- `generateContractManually(Lease $lease)` : Génération manuelle
- `checkRequiredDocuments(Lease $lease)` : Vérifie les docs requis

### Contrôleurs modifiés

**`src/Controller/PaymentController.php`**
- Méthode `markPaid()` améliorée
- Détection automatique du paiement de caution
- Appel à `ContractGenerationService`

**`src/Controller/LeaseController.php`**
- Nouvelle route `/contrats/{id}/generer-contrat-document`
- Permet génération manuelle du contrat

---

## 🎨 Le contrat PDF généré contient

### Informations de l'entreprise (depuis Paramètres)
- ✅ Nom de l'entreprise : `{{company_name}}`
- ✅ Adresse : `{{company_address}}`
- ✅ Téléphone : `{{company_phone}}`
- ✅ Email : `{{company_email}}`

### Informations du bailleur
- ✅ Nom complet (propriétaire/gestionnaire)
- ✅ Adresse
- ✅ Contact

### Informations du locataire
- ✅ Nom complet : `{{tenant.firstName}} {{tenant.lastName}}`
- ✅ Date de naissance
- ✅ Email et téléphone
- ✅ Profession
- ✅ Adresse

### Informations du bien
- ✅ Adresse complète : `{{property.fullAddress}}`
- ✅ Type de bien : `{{property.propertyType}}`
- ✅ Surface : `{{property.surface}} m²`
- ✅ Nombre de pièces : `{{property.rooms}}`

### Conditions du bail
- ✅ Date de début : `{{lease.startDate}}`
- ✅ Date de fin : `{{lease.endDate}}`
- ✅ Durée calculée automatiquement
- ✅ Loyer mensuel : `{{lease.monthlyRent|currency}}`
- ✅ Charges : `{{lease.charges|currency}}`
- ✅ Dépôt de garantie : `{{lease.deposit|currency}}`
- ✅ Jour d'échéance : `{{lease.rentDueDay}}`

### Clauses légales
- ✅ Obligations du locataire
- ✅ Obligations du bailleur
- ✅ Clause résolutoire
- ✅ Signatures

### Format
- ✅ A4 Portrait
- ✅ Style professionnel
- ✅ Logo et en-tête
- ✅ Numérotation des articles
- ✅ Espace pour signatures

---

## 📝 Exemple de workflow complet

### Création d'un bail de A à Z

**Étape 1 : Créer le locataire**
```
/locataires/nouveau
→ Cocher "Créer automatiquement un compte utilisateur"
→ Créer
→ Compte créé avec mot de passe affiché
```

**Étape 2 : Créer le bail**
```
/contrats/nouveau
→ Sélectionner locataire et propriété
→ Renseigner dates, loyer, caution
→ Créer le bail
```

**Étape 3 : Créer le paiement de caution**
```
/mes-paiements/nouveau
→ Type : "Dépôt de garantie"
→ Montant : (montant de la caution)
→ Créer
```

**Étape 4 : Marquer la caution comme payée**
```
Page du paiement → "Marquer comme payé"
→ Date de paiement
→ Mode : Virement, chèque, etc.
→ Valider
```

**✨ AUTOMATIQUE : Le contrat est généré !**
```
✅ Message : "Le contrat de bail a été généré automatiquement"
✅ PDF créé : Contrat_Bail_X_Dupont_2025-11-01.pdf
✅ Document enregistré en base
✅ Accessible dans les documents
```

---

## 🎯 Génération manuelle

Si vous voulez générer le contrat sans attendre la caution :

### Depuis la page d'un bail

1. Accédez à `/contrats/{id}`
2. **Nouveau bouton** : "Générer et enregistrer le contrat"
3. Cliquez
4. Le contrat est généré et sauvegardé dans les documents

### Depuis l'admin

```php
// Dans un contrôleur ou service
$contractService = $this->container->get(ContractGenerationService::class);
$document = $contractService->generateContractManually($lease);
```

---

## 📁 Stockage des contrats

### Emplacement
```
public/uploads/documents/Contrat_Bail_X_Dupont_2025-11-01.pdf
```

### Base de données
Table `document` :
- `name` : "Contrat de bail - 123"
- `type` : "Bail"
- `category` : "Bail"
- `file_path` : "Contrat_Bail_123_Dupont_2025-11-01.pdf"
- `lease_id` : ID du bail
- `tenant_id` : ID du locataire
- `property_id` : ID de la propriété
- `is_official` : true
- `description` : "Contrat de bail généré automatiquement après paiement de la caution"

### Accès au contrat

**Par le locataire** :
- Menu "Mes documents"
- Catégorie "Bail"
- Téléchargement direct

**Par l'admin** :
- Fiche du bail → Documents
- Fiche du locataire → Documents
- Liste globale des documents

---

## 🔒 Sécurités

### 1. Pas de doublon
```php
// Vérifie si un contrat n'existe pas déjà
$existingContract = $repository->findOneBy([
    'lease' => $lease,
    'type' => 'Bail',
    'name' => 'Contrat de bail - ' . $lease->getId()
]);

if ($existingContract) {
    return $existingContract; // Retourne l'existant
}
```

### 2. Vérification du type de paiement
```php
if ($payment->getType() !== 'Dépôt de garantie' && 
    $payment->getType() !== 'Caution') {
    return null; // Pas une caution, on ne génère pas
}
```

### 3. Vérification du statut
```php
if (!$payment->isPaid()) {
    return null; // Pas payé, on ne génère pas
}
```

---

## 📊 Vérification des documents requis

Le service peut vérifier quels documents sont manquants :

```php
$requiredDocs = $contractService->checkRequiredDocuments($lease);

// Retourne :
[
    'Bail' => true/false,
    'État des lieux entrée' => true/false,
    'Quittance caution' => true/false
]
```

Utilisation possible pour afficher des alertes :
```twig
{% set missing = contract_service.checkRequiredDocuments(lease) %}
{% if not missing['Bail'] %}
    <div class="alert alert-warning">Contrat de bail manquant</div>
{% endif %}
```

---

## 🎨 Personnalisation du template PDF

Le template `templates/pdf/lease_contract.html.twig` utilise automatiquement :

```twig
{# En-tête avec infos entreprise #}
<div class="header">
    <h1>CONTRAT DE BAIL D'HABITATION</h1>
    <p><strong>{{ company.company_name ?? 'MYLOCCA Gestion' }}</strong></p>
    <p>{{ company.company_address ?? '' }}</p>
</div>

{# Informations du bailleur #}
{% if owner %}
    {{ owner.firstName }} {{ owner.lastName }}
    {{ owner.address }}
    {{ owner.email }}
{% else %}
    {{ company.company_name }}
    {{ company.company_address }}
{% endif %}

{# Informations du locataire #}
{{ tenant.firstName }} {{ tenant.lastName }}
{{ tenant.birthDate|date('d/m/Y') }}
{{ tenant.email }}
{{ tenant.phone }}

{# Informations du bien #}
{{ property.address }}, {{ property.postalCode }} {{ property.city }}
{{ property.propertyType }} - {{ property.rooms }} pièces
{{ property.surface }} m²

{# Conditions financières #}
Loyer : {{ lease.monthlyRent|currency }}
Charges : {{ lease.charges|currency }}
Caution : {{ lease.deposit|currency }}
Échéance : Le {{ lease.rentDueDay ?? 1 }} du mois
```

---

## ✅ Résumé

### Workflow automatique :
1. Création du locataire → ✅ Compte utilisateur créé
2. Création du bail → ✅ Bail enregistré
3. Paiement de la caution → ✅ Paiement créé
4. **Marquer comme payé** → ✅ **CONTRAT PDF GÉNÉRÉ AUTOMATIQUEMENT !**
5. Contrat disponible → ✅ Dans les documents

### Ce que le contrat contient :
- ✅ Toutes les infos de votre entreprise
- ✅ Toutes les infos du locataire
- ✅ Toutes les infos du bien
- ✅ Toutes les conditions du bail
- ✅ Clauses légales conformes
- ✅ Format professionnel avec signatures

### Avantages :
- 🚀 **Automatique** - Aucune action manuelle
- 📄 **Personnalisé** - Avec toutes vos infos
- 💾 **Sauvegardé** - Dans la base ET sur disque
- 🔗 **Lié** - Au bail, locataire, propriété
- 📧 **Partageable** - Accessible au locataire
- 🎨 **Professionnel** - Design soigné

---

## 🎉 FÉLICITATIONS !

Votre système génère maintenant **automatiquement des contrats de bail professionnels** après le paiement de la caution !

**Plus besoin de créer les contrats manuellement !** 🚀

---

**Version** : 2.6  
**Date** : 11 Octobre 2025  
**Status** : ✅ 100% Opérationnel

