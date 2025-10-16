# 📄 Guide - Contrat de bail générique et configurable

## 📋 Vue d'ensemble

Le système de génération de contrats de bail est maintenant **entièrement configurable** via l'interface d'administration ! Plus besoin de modifier le code pour personnaliser l'apparence, les couleurs, les textes ou la mise en page des contrats PDF.

---

## ✨ NOUVELLES FONCTIONNALITÉS

### 1. Interface de configuration des contrats

**URL** : `/admin/contract-config`

**Fonctionnalités** :
- ✅ **Thèmes prédéfinis** : 5 thèmes de couleurs (défaut, vert, rouge, violet, orange)
- ✅ **Apparence** : Police, taille, couleurs, marges
- ✅ **Informations entreprise** : Nom, adresse, logo
- ✅ **Titres des sections** : Personnaliser tous les titres d'articles
- ✅ **Signatures** : Textes et lieux de signature
- ✅ **Aperçu en temps réel** : Voir les changements immédiatement
- ✅ **Validation** : Contrôle des formats (couleurs hex, tailles)

---

### 2. Template générique

**Fichier** : `templates/pdf/lease_contract.html.twig`

**Variables configurables** :
- `contract_language` : Langue du document
- `contract_main_title` : Titre principal
- `contract_primary_color` : Couleur principale
- `contract_font_family` : Police utilisée
- `contract_section_X_title` : Titres des 8 articles
- `contract_company_name` : Nom de l'entreprise
- `contract_logo_url` : URL du logo
- Et 20+ autres variables...

---

### 3. Service de configuration

**Fichier** : `src/Service/ContractConfigService.php`

**Méthodes** :
- `getContractConfig()` : Récupère la config complète
- `getConfigValue(key, default)` : Récupère une valeur spécifique
- `setConfigValue(key, value)` : Met à jour une valeur
- `getAvailableThemes()` : Liste des thèmes prédéfinis
- `applyTheme(themeName)` : Applique un thème
- `validateConfig(config)` : Valide la configuration

---

## 🎨 PERSONNALISATION COMPLÈTE

### Thèmes prédéfinis

| Thème | Couleur principale | Usage |
|-------|-------------------|-------|
| **Défaut** | #0066cc (bleu) | Usage standard |
| **Vert** | #28a745 | Écologique/nature |
| **Rouge** | #dc3545 | Urgence/important |
| **Violet** | #6f42c1 | Premium/luxe |
| **Orange** | #fd7e14 | Énergique/dynamique |

---

### Apparence

**Polices disponibles** :
- DejaVu Sans (par défaut)
- Arial
- Times New Roman

**Tailles configurables** :
- Police générale : `11pt`
- Titre principal : `24pt`
- Largeur des labels : `180px`

**Couleurs** :
- Couleur principale (titres, bordures)
- Couleur du texte
- Couleur de fond des informations
- Couleur de surbrillance (tableaux)

---

### Contenu personnalisable

#### Titres des sections
- Article 1 : Les parties
- Article 2 : Désignation du bien loué
- Article 3 : Durée du bail
- Article 4 : Loyer et charges
- Article 5 : Dépôt de garantie
- Article 6 : Obligations du locataire
- Article 7 : Obligations du bailleur
- Article 8 : Clause résolutoire

#### Signatures
- Titre bailleur : "Le Bailleur"
- Titre locataire : "Le Locataire"
- Lieu : "Fait à ____________"
- Texte signature bailleur : "Signature"
- Texte signature locataire : "Signature précédée de la mention 'Lu et approuvé'"

---

## 🚀 UTILISATION

### Accéder à la configuration

1. **Se connecter en tant qu'admin**
2. **Aller dans** : Admin → Configuration contrats
3. **URL directe** : `http://localhost:8000/admin/contract-config`

---

### Appliquer un thème

1. **Cliquer sur un thème** dans la section "Thèmes prédéfinis"
2. **Le thème est appliqué immédiatement**
3. **Les couleurs sont mises à jour** automatiquement

---

### Personnaliser l'apparence

#### Couleurs
1. **Utiliser les sélecteurs de couleur** ou saisir le code hex
2. **Format requis** : `#RRGGBB` (ex: #0066cc)
3. **Synchronisation automatique** entre sélecteur et texte

#### Police
1. **Choisir dans la liste déroulante**
2. **Modifier la taille** (format : `XXpt`)
3. **Ajuster la largeur des labels** si nécessaire

---

### Personnaliser le contenu

#### Informations entreprise
1. **Nom de l'entreprise** : Saisir le nom complet
2. **Adresse** : Adresse complète (multiligne)
3. **Logo** : URL complète vers l'image

#### Titres des sections
1. **Modifier chaque titre d'article**
2. **Garder la cohérence** avec la numérotation
3. **Rester professionnel** dans le libellé

---

### Aperçu et validation

#### Aperçu
1. **Cliquer sur "Aperçu"** pour voir la configuration
2. **Vérifier les couleurs** et l'apparence
3. **Contrôler les informations** entreprise

#### Validation automatique
- ✅ **Couleurs hexadécimales** : Format `#RRGGBB`
- ✅ **Tailles de police** : Format `XXpt`
- ✅ **URLs de logo** : Format URL valide
- ✅ **Champs obligatoires** : Vérification des saisies

---

## 📦 ARCHITECTURE TECHNIQUE

### 1. Template générique

**Avant** (codé en dur) :
```twig
<h1>CONTRAT DE BAIL D'HABITATION</h1>
<div class="section-title" style="background-color: #0066cc;">
```

**Après** (configurable) :
```twig
<h1>{{ contract_main_title ?? 'CONTRAT DE BAIL D\'HABITATION' }}</h1>
<div class="section-title" style="background-color: {{ contract_primary_color ?? '#0066cc' }};">
```

---

### 2. Service de configuration

```php
class ContractConfigService
{
    private array $defaultConfig = [
        'contract_primary_color' => '#0066cc',
        'contract_main_title' => 'CONTRAT DE BAIL D\'HABITATION',
        // ... 20+ autres paramètres
    ];

    public function getContractConfig(): array
    {
        // Fusionne la config par défaut avec les paramètres BDD
    }
}
```

---

### 3. Intégration dans PdfService

```php
public function generateLeaseContract(Lease $lease): string
{
    $contractConfig = $this->contractConfigService->getContractConfig();
    
    $html = $this->twig->render('pdf/lease_contract.html.twig', array_merge([
        'lease' => $lease,
        'property' => $lease->getProperty(),
        // ... données du bail
    ], $contractConfig));
}
```

---

## 🎯 EXEMPLES PRATIQUES

### Exemple 1 : Changer les couleurs

**Objectif** : Passer du bleu au vert

**Actions** :
1. Aller dans Admin → Configuration contrats
2. Cliquer sur le thème "Vert"
3. Ou modifier manuellement :
   - Couleur principale : `#28a745`
   - Couleur fond info : `#f0f8f0`
   - Couleur surbrillance : `#e8f5e8`

**Résultat** : Tous les contrats générés auront le thème vert !

---

### Exemple 2 : Personnaliser l'entreprise

**Objectif** : Afficher "IMMO LUXE" au lieu de "MYLOCCA"

**Actions** :
1. Nom entreprise : "IMMO LUXE"
2. Adresse : "123 Avenue des Champs-Élysées, 75008 Paris"
3. Logo : "https://mon-site.com/logo.png"

**Résultat** : En-tête personnalisé sur tous les contrats !

---

### Exemple 3 : Modifier les titres

**Objectif** : Rendre les titres plus formels

**Modifications** :
- Article 1 : "ARTICLE 1 : IDENTIFICATION DES PARTIES CONTRACTANTES"
- Article 2 : "ARTICLE 2 : DÉSIGNATION COMPLÈTE DU BIEN IMMOBILIER"
- Article 3 : "ARTICLE 3 : DURÉE ET CONDITIONS DU BAIL"

**Résultat** : Contrats plus formels et professionnels !

---

## 🔧 CONFIGURATION AVANCÉE

### Variables disponibles dans les templates

```twig
{# Données du bail #}
{{ '{{lease.id}}' }} - ID du bail
{{ '{{lease.startDate|date}}' }} - Date de début
{{ '{{lease.monthlyRent|currency}}' }} - Loyer formaté

{# Données du locataire #}
{{ '{{tenant.firstName}}' }} - Prénom
{{ '{{tenant.lastName}}' }} - Nom
{{ '{{tenant.email}}' }} - Email

{# Données du bien #}
{{ '{{property.address}}' }} - Adresse
{{ '{{property.surface}}' }} - Surface
{{ '{{property.rooms}}' }} - Nombre de pièces

{# Données système #}
{{ '{{generated_at|date}}' }} - Date de génération
{{ '{{company.company_name}}' }} - Nom entreprise
```

---

### Formats de validation

**Couleurs** :
- ✅ Valide : `#0066cc`, `#28a745`, `#dc3545`
- ❌ Invalide : `blue`, `0066cc`, `#06c`

**Tailles** :
- ✅ Valide : `11pt`, `24pt`, `12pt`
- ❌ Invalide : `11px`, `large`, `12`

**URLs** :
- ✅ Valide : `https://example.com/logo.png`
- ❌ Invalide : `logo.png`, `www.example.com`

---

## 🎊 AVANTAGES

### 1. Flexibilité totale

✅ **Aucun code à modifier** pour personnaliser les contrats  
✅ **Interface intuitive** pour les non-développeurs  
✅ **Changements instantanés** sans redémarrage  
✅ **Thèmes prédéfinis** pour un démarrage rapide  

### 2. Professionnalisme

✅ **Contrats cohérents** avec l'identité visuelle  
✅ **Personnalisation complète** des textes  
✅ **Support des logos** et images  
✅ **Validation automatique** des formats  

### 3. Maintenance

✅ **Configuration centralisée** en un seul endroit  
✅ **Sauvegarde automatique** des paramètres  
✅ **Reset facile** aux valeurs par défaut  
✅ **Aperçu en temps réel** des modifications  

### 4. Évolutivité

✅ **Ajout facile** de nouveaux thèmes  
✅ **Extension simple** avec de nouvelles variables  
✅ **Support multilingue** préparé  
✅ **Intégration** avec les paramètres existants  

---

## 🚀 PROCHAINES ÉTAPES POSSIBLES

### Améliorations futures

1. **Templates multiples** : Contrats commerciaux, baux étudiants
2. **Éditeur WYSIWYG** : Modification visuelle du contenu
3. **Import/Export** : Sauvegarde des configurations
4. **Prévisualisation PDF** : Aperçu direct du PDF généré
5. **Historique** : Suivi des modifications de configuration
6. **Templates par type** : Configuration différente selon le type de bail

---

## 📚 DOCUMENTATION LIÉE

- `GESTION_MENUS_ADMIN.md` - Gestion des menus via interface
- `ACL_SYSTEM_GUIDE.md` - Système de permissions
- `RECAPITULATIF_ACL.md` - Récapitulatif ACL

---

**📅 Version** : 1.0  
**📄 Date** : 12 Octobre 2025  
**✨ Statut** : Opérationnel  

---

**📄 Le système de contrats est maintenant entièrement configurable via l'interface !**
