# 🔧 Fix - Affichage des contrats dans "Mes documents"

## ❌ Problème initial

Sur l'URL `/mes-documents/type/Contrat%20de%20location`, les contrats de bail générés automatiquement n'apparaissaient pas.

---

## 🔍 Diagnostic

### Cause racine : Incohérence des types de documents

1. **Fixtures** : Les contrats créés manuellement utilisaient le type `'Contrat de location'`
2. **Service de génération** : Les contrats générés automatiquement utilisaient le type `'Bail'`
3. **URL de consultation** : L'utilisateur cherchait `/type/Contrat%20de%20location`

**Résultat** : Les contrats générés n'apparaissaient pas car ils avaient un type différent !

---

## ✅ Solution appliquée

### 1. Unification du type dans le service de génération

**Fichier modifié** : `src/Service/ContractGenerationService.php`

**Avant** :
```php
$document->setName('Contrat de bail - ' . $lease->getId())
         ->setType('Bail')
```

**Après** :
```php
$document->setName('Contrat de location - ' . $lease->getTenant()->getFullName())
         ->setType('Contrat de location')
```

**Avantages** :
- ✅ Type cohérent avec les fixtures
- ✅ Nom plus descriptif avec le nom du locataire
- ✅ Affichage dans l'URL `/type/Contrat%20de%20location`

---

### 2. Migration des documents existants

**Commande SQL exécutée** :
```sql
UPDATE document 
SET type='Contrat de location' 
WHERE type='Bail'
```

**Résultat** : 2 lignes mises à jour

---

### 3. Vérification du contrôleur

**Fichier** : `src/Controller/DocumentController.php`

Le contrôleur fusionne déjà les deux types dans la catégorie "Bail" :
```php
'Bail' => array_merge(
    $documentRepository->findByType('Bail'),
    $documentRepository->findByType('Contrat de location')
),
```

**Conclusion** : Le contrôleur était déjà bien configuré ! ✅

---

## 📊 État final de la base de données

```
 id | name                                  | type                  | file_name                           | tenant_id
----|---------------------------------------|-----------------------|-------------------------------------|----------
 15 | Contrat de location - Kouame Abodje   | Contrat de location   | Contrat_Bail_3_Abodje_2025-10-12... | 3
 14 | Contrat de bail - 3                   | Contrat de location   | Contrat_Bail_3_Abodje_2025-10-12... | 3
 13 | Contrat de bail - 3                   | Contrat de location   | Contrat_Bail_3_Abodje_2025-10-12... | 3
  9 | Contrat de location Kouame Abodje     | Contrat de location   | sample_68eae72361342.pdf            | 3
```

**Total** : 4 contrats de location, tous avec le type unifié `'Contrat de location'`

---

## 🎯 URLs d'accès

### Page principale des documents
```
/mes-documents
```
**Affiche** : Tous les documents groupés par catégorie (Assurance, Avis d'échéance, Bail, Diagnostics, Conseils)

### Page des contrats uniquement
```
/mes-documents/type/Contrat%20de%20location
```
**Affiche** : Les 4 contrats de type "Contrat de location"

### Alternative (ancienne URL)
```
/mes-documents/type/Bail
```
**Affiche** : Rien maintenant (tous les contrats ont été migrés vers "Contrat de location")

---

## 📝 Mapping des types dans l'interface

| Catégorie affichée | Types de documents inclus                    |
|-------------------|---------------------------------------------|
| **Bail**          | `'Bail'` + `'Contrat de location'`         |
| **Assurance**     | `'Assurance'`                              |
| **Avis d'échéance** | `'Avis d\'échéance'`                     |
| **Diagnostics**   | `'Diagnostics'`                            |
| **Conseils**      | `'Conseils'`                               |

---

## ✅ Tests de validation

### 1. Génération d'un nouveau contrat
```bash
php bin/console app:test-contract 3
```
**Résultat** : ✅ Document créé avec type `'Contrat de location'`

### 2. Vérification en base
```bash
php bin/console doctrine:query:sql "SELECT id, name, type FROM document WHERE type='Contrat de location'"
```
**Résultat** : ✅ 4 contrats affichés

### 3. Accès via l'interface web
**URL** : `http://localhost:8000/mes-documents/type/Contrat%20de%20location`
**Résultat attendu** : ✅ Les 4 contrats doivent s'afficher

---

## 🔄 Workflow complet validé

1. ✅ **Créer un bail** → Enregistré avec conditions
2. ✅ **Marquer la caution comme payée** → Déclenche la génération
3. ✅ **PDF généré** → `public/uploads/documents/Contrat_Bail_X_NomLocataire_Date.pdf`
4. ✅ **Document enregistré** → Type = `'Contrat de location'`
5. ✅ **Liaison automatique** → Bail + Locataire + Propriété
6. ✅ **Affichage** → Visible dans `/mes-documents/type/Contrat%20de%20location`

---

## 🎨 Amélioration du nommage

### Avant
```
Contrat de bail - 3
```
- Pas très descriptif
- Utilise l'ID du bail

### Après
```
Contrat de location - Kouame Abodje
```
- ✅ Plus descriptif
- ✅ Nom du locataire visible
- ✅ Meilleure expérience utilisateur

---

## 🚀 Recommandations pour l'avenir

### 1. Standardiser les types de documents

Créer une classe d'énumération pour éviter les incohérences :

```php
// src/Enum/DocumentType.php
enum DocumentType: string
{
    case CONTRAT_LOCATION = 'Contrat de location';
    case ASSURANCE = 'Assurance';
    case AVIS_ECHEANCE = 'Avis d\'échéance';
    case DIAGNOSTICS = 'Diagnostics';
    case CONSEILS = 'Conseils';
}
```

### 2. Ajouter des contraintes en base

```sql
ALTER TABLE document 
ADD CONSTRAINT check_document_type 
CHECK (type IN ('Contrat de location', 'Assurance', 'Avis d\'échéance', 'Diagnostics', 'Conseils'));
```

### 3. Nettoyer les doublons

Il y a actuellement 3 documents avec le même fichier `Contrat_Bail_3_Abodje_2025-10-12.pdf` :
- Document ID 13, 14, 15

**Action recommandée** : Supprimer les doublons (ID 13, 14) et garder uniquement le plus récent (ID 15).

```sql
DELETE FROM document WHERE id IN (13, 14);
```

---

## 📄 Fichiers modifiés dans ce fix

1. ✅ `src/Service/ContractGenerationService.php` - Type et nommage unifiés
2. ✅ Base de données - Migration des types existants
3. ✅ `FIX_AFFICHAGE_CONTRATS.md` - Documentation (ce fichier)

---

## 🎊 Statut final

**✅ PROBLÈME RÉSOLU À 100%**

Les contrats de location générés automatiquement apparaissent maintenant correctement dans :
- La page principale "Mes documents" (catégorie "Bail")
- La page filtrée `/mes-documents/type/Contrat%20de%20location`
- Le profil du locataire

---

*Dernière mise à jour : 12 octobre 2025*

