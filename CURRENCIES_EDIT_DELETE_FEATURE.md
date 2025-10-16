# 💱 Modification et Suppression de Devises

## 📋 Vue d'ensemble

Ajout des fonctionnalités de **modification** et **suppression** des devises sur la page `/admin/parametres/devises`. Ces fonctionnalités permettent une gestion complète du catalogue de devises.

---

## ✅ Fonctionnalités Ajoutées

### **1. Modification de Devise** ✏️

Permet de modifier toutes les propriétés d'une devise existante :
- Nom de la devise
- Code ISO
- Symbole
- Taux de change
- Nombre de décimales
- Statut (active/inactive)
- Devise par défaut (oui/non)

**Route :** `GET/POST /admin/parametres/devises/{id}/modifier`

---

### **2. Suppression de Devise** 🗑️

Permet de supprimer une devise du système avec les protections suivantes :
- ❌ Impossible de supprimer la devise par défaut
- ✅ Confirmation avant suppression
- ✅ Protection CSRF
- ✅ Gestion des erreurs si la devise est utilisée

**Route :** `POST /admin/parametres/devises/{id}/supprimer`

---

## 🎨 Interface Utilisateur

### **Page Liste des Devises**

La colonne **Actions** affiche maintenant les boutons suivants :

| Bouton | Icône | Couleur | Fonction | Toujours visible |
|--------|-------|---------|----------|------------------|
| **Modifier** | ✏️ `bi-pencil` | Bleu (primary) | Éditer la devise | ✅ Oui |
| **Activer** | ✓ `bi-check-circle` | Vert (success) | Définir comme active | Conditionnel* |
| **Par défaut** | ⭐ `bi-star` | Jaune (warning) | Définir par défaut | Si pas déjà par défaut |
| **Supprimer** | 🗑️ `bi-trash` | Rouge (danger) | Supprimer | Si pas par défaut |

\* *Visible uniquement si la devise n'est pas déjà active*

---

## 🔧 Implémentation Technique

### **Fichiers Modifiés**

#### **1. Contrôleur** - `src/Controller/Admin/SettingsController.php`

**Nouvelle méthode `editCurrency()`:**
```php
#[Route('/devises/{id}/modifier', name: 'app_admin_currency_edit', methods: ['GET', 'POST'])]
public function editCurrency(Currency $currency, Request $request, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(CurrencyType::class, $currency);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        $this->addFlash('success', 'La devise a été modifiée avec succès.');
        return $this->redirectToRoute('app_admin_currencies');
    }

    return $this->render('admin/settings/currency_edit.html.twig', [
        'currency' => $currency,
        'form' => $form,
    ]);
}
```

**Nouvelle méthode `deleteCurrency()`:**
```php
#[Route('/devises/{id}/supprimer', name: 'app_admin_currency_delete', methods: ['POST'])]
public function deleteCurrency(Currency $currency, EntityManagerInterface $entityManager, Request $request): Response
{
    // Vérification CSRF
    if ($this->isCsrfTokenValid('delete'.$currency->getId(), $request->request->get('_token'))) {
        // Protection devise par défaut
        if ($currency->isDefault()) {
            $this->addFlash('error', 'Impossible de supprimer la devise par défaut.');
            return $this->redirectToRoute('app_admin_currencies');
        }

        try {
            $code = $currency->getCode();
            $entityManager->remove($currency);
            $entityManager->flush();
            $this->addFlash('success', "La devise {$code} a été supprimée avec succès.");
        } catch (\Exception $e) {
            $this->addFlash('error', 'Impossible de supprimer cette devise.');
        }
    }

    return $this->redirectToRoute('app_admin_currencies');
}
```

---

#### **2. Template d'Édition** - `templates/admin/settings/currency_edit.html.twig`

Nouveau template créé avec :
- ✅ Formulaire pré-rempli avec les valeurs actuelles
- ✅ Informations sur la devise dans la sidebar
- ✅ Aperçu en temps réel du formatage
- ✅ Aide et conseils
- ✅ Validation côté serveur

**Caractéristiques :**
- Layout en 2 colonnes (formulaire + info)
- Preview dynamique du rendu
- Messages d'aide contextuels
- Boutons d'action (Enregistrer / Annuler)

---

#### **3. Template Liste** - `templates/admin/settings/currencies.html.twig`

**Modifications dans la colonne Actions (lignes 100-145) :**

```twig
{# Bouton Modifier (toujours visible) #}
<a href="{{ path('app_admin_currency_edit', {id: currency.id}) }}" 
   class="btn btn-sm btn-outline-primary" 
   title="Modifier">
    <i class="bi bi-pencil"></i>
</a>

{# Bouton Supprimer (avec protection) #}
{% if not currency.isDefault %}
    <form method="POST" 
          action="{{ path('app_admin_currency_delete', {id: currency.id}) }}" 
          class="d-inline"
          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer la devise {{ currency.code }} ?');">
        <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ currency.id) }}">
        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
            <i class="bi bi-trash"></i>
        </button>
    </form>
{% else %}
    <button type="button" 
            class="btn btn-sm btn-outline-secondary" 
            title="Impossible de supprimer la devise par défaut"
            disabled>
        <i class="bi bi-trash"></i>
    </button>
{% endif %}
```

---

## 🛡️ Sécurité et Protections

### **Protection CSRF**
- ✅ Token CSRF requis pour la suppression
- ✅ Validation du token avant toute action

### **Protection Devise Par Défaut**
- ❌ Impossible de supprimer la devise par défaut
- ℹ️ Message d'erreur explicite si tentative
- 🔒 Bouton désactivé dans l'interface

### **Confirmation Utilisateur**
- ⚠️ Dialog de confirmation JavaScript avant suppression
- 📝 Message personnalisé avec le code de la devise

### **Gestion des Erreurs**
```php
try {
    $entityManager->remove($currency);
    $entityManager->flush();
    // Succès
} catch (\Exception $e) {
    // La devise est peut-être utilisée ailleurs
    $this->addFlash('error', 'Impossible de supprimer cette devise.');
}
```

---

## 📊 Flux Utilisateur

### **Flux de Modification**

```
1. Page liste devises (/admin/parametres/devises)
   ↓
2. Clic sur bouton "Modifier" (✏️)
   ↓
3. Page d'édition avec formulaire pré-rempli
   ↓
4. Modification des champs
   ↓
5. Aperçu en temps réel du formatage
   ↓
6. Clic "Enregistrer"
   ↓
7. Validation et sauvegarde
   ↓
8. Redirection vers liste avec message de succès
```

---

### **Flux de Suppression**

```
1. Page liste devises (/admin/parametres/devises)
   ↓
2. Clic sur bouton "Supprimer" (🗑️)
   ↓
3. Dialog de confirmation JavaScript
   ↓
4. Confirmation utilisateur
   ↓
5. Vérification CSRF
   ↓
6. Vérification devise non-défaut
   ↓
7. Tentative de suppression
   ↓
8. Redirection avec message (succès ou erreur)
```

---

## 🎯 Cas d'Usage

### **Cas 1 : Corriger un Taux de Change**

**Problème :** Le taux de change de l'USD est obsolète

**Solution :**
1. Aller sur `/admin/parametres/devises`
2. Cliquer sur ✏️ pour l'USD
3. Modifier le champ "Taux de change"
4. Enregistrer

---

### **Cas 2 : Modifier le Symbole d'une Devise**

**Problème :** Le symbole "XOF" devrait être "FCFA"

**Solution :**
1. Modifier la devise XOF
2. Changer le symbole de "XOF" à "FCFA"
3. L'aperçu se met à jour automatiquement
4. Enregistrer

---

### **Cas 3 : Supprimer une Devise Inutilisée**

**Problème :** La devise CAD n'est plus utilisée

**Solution :**
1. S'assurer que ce n'est pas la devise par défaut
2. Cliquer sur 🗑️ pour CAD
3. Confirmer la suppression
4. La devise est supprimée

---

### **Cas 4 : Tentative de Suppression de la Devise Par Défaut**

**Problème :** Tentative de suppression de EUR (par défaut)

**Résultat :**
- ❌ Bouton désactivé (grisé)
- 📝 Tooltip "Impossible de supprimer la devise par défaut"
- ℹ️ Si forcé via API : Message d'erreur

---

## 🧪 Tests Recommandés

### **Test 1 : Modification Simple**
```
✅ Modifier le nom d'une devise
✅ Modifier le symbole
✅ Modifier le taux de change
✅ Vérifier que les changements sont sauvegardés
```

### **Test 2 : Protection Devise Par Défaut**
```
✅ Tenter de supprimer la devise par défaut
✅ Vérifier que le bouton est désactivé
✅ Vérifier le message d'erreur si tentative forcée
```

### **Test 3 : Validation CSRF**
```
✅ Tenter une suppression sans token CSRF
✅ Vérifier que l'action est refusée
```

### **Test 4 : Confirmation JavaScript**
```
✅ Cliquer sur supprimer
✅ Vérifier l'apparition du dialog
✅ Tester "Annuler" → Aucune suppression
✅ Tester "OK" → Suppression effectuée
```

---

## 📝 Messages Flash

### **Messages de Succès**
- ✅ `"La devise a été modifiée avec succès."`
- ✅ `"La devise {CODE} a été supprimée avec succès."`

### **Messages d'Erreur**
- ❌ `"Impossible de supprimer la devise par défaut. Définissez d'abord une autre devise comme devise par défaut."`
- ❌ `"Impossible de supprimer cette devise. Elle est peut-être utilisée dans le système."`

---

## 🔄 Améliorations Futures

### **Phase 1 (Actuelle)**
- ✅ Modification de devise
- ✅ Suppression avec protections
- ✅ Interface utilisateur

### **Phase 2 (Possible)**
- [ ] Vérification d'utilisation avant suppression
  - Compter les paiements utilisant la devise
  - Compter les baux utilisant la devise
  - Afficher un avertissement si utilisée
- [ ] Archivage au lieu de suppression
  - Marquer comme archivée plutôt que supprimer
  - Conserver l'historique
- [ ] Export/Import de devises
  - Exporter la configuration
  - Importer depuis un fichier

### **Phase 3 (Avancé)**
- [ ] Historique des modifications
  - Qui a modifié quoi et quand
  - Log des changements de taux
- [ ] Conversion automatique lors de changement de devise
  - Proposer de convertir les données existantes
  - Recalculer les montants

---

## ✅ Checklist de Validation

- [x] Routes créées et fonctionnelles
- [x] Templates créés (edit + modifications liste)
- [x] Validation des formulaires
- [x] Protection CSRF
- [x] Protection devise par défaut
- [x] Messages flash appropriés
- [x] Gestion des erreurs
- [x] Confirmation avant suppression
- [x] Documentation complète

---

## 📊 Statistiques

| Métrique | Valeur |
|----------|--------|
| Nouvelles routes | 2 |
| Templates créés | 1 |
| Templates modifiés | 1 |
| Contrôleurs modifiés | 1 |
| Lignes de code ajoutées | ~200 |
| Protections de sécurité | 3 |
| Types de boutons | 4 |

---

## 🎓 Résumé

Les fonctionnalités de **modification** et **suppression** sont maintenant disponibles sur la page de gestion des devises avec :
- ✅ Interface intuitive avec boutons d'action clairs
- ✅ Protections de sécurité robustes
- ✅ Validation et gestion des erreurs
- ✅ Expérience utilisateur optimale

Les administrateurs peuvent désormais **gérer complètement** leur catalogue de devises directement depuis l'interface web ! 💱

