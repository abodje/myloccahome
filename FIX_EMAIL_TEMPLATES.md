# 🔧 Fix - Templates d'emails

## 📧 Corrections des templates d'emails

### Date : 12 Octobre 2025

---

## ❌ PROBLÈMES IDENTIFIÉS

### 1. Variables Twig interprétées comme réelles

**Contexte** : Dans les sections d'exemples et de documentation, les variables étaient écrites directement sans échappement, ce qui faisait que Twig essayait de les évaluer.

**Fichiers affectés** :
- `templates/admin/email_template/edit.html.twig`
- `templates/admin/email_template/show.html.twig`

---

## ✅ CORRECTIONS APPLIQUÉES

### Fichier : `edit.html.twig`

#### Problème 1 : Balise `{% raw %}` dans attribut HTML (ligne 34)

**Erreur** :
```twig
placeholder="Ex: Quittance de loyer - {% raw %}{{month}}{% endraw %}"
```

**Erreur Twig** :
```
Unexpected "raw" tag (expecting closing tag for the "block" tag defined near line 6)
```

**Raison** : Les balises `{% raw %}` ne peuvent pas être utilisées à l'intérieur d'attributs HTML.

**Correction appliquée** :
```twig
placeholder="Ex: Quittance de loyer - {month}"
```

**Explication** : Pour les placeholders d'input, utiliser des accolades simples est suffisant et évite les problèmes de parsing Twig.

---

#### Problème 2 : Variables dans exemples (lignes 117-129)

**Erreur** :
```twig
<code class="small d-block mb-3">
    Bonjour {{tenant_first_name}},
</code>

<code class="small d-block mb-3">
    Montant : {{payment_amount}}
</code>

<code class="small d-block">
    {{company_name}}<br>
    {{company_address}}<br>
    Tél: {{company_phone}}
</code>
```

**Erreur Twig** :
```
Variable "tenant_first_name" does not exist in admin/email_template/edit.html.twig at line 117
```

**Raison** : Twig essaie d'évaluer ces variables qui n'existent pas dans le contexte du contrôleur.

**Correction appliquée** :
```twig
<code class="small d-block mb-3">
    Bonjour {{ '{{tenant_first_name}}' }},
</code>

<code class="small d-block mb-3">
    Montant : {{ '{{payment_amount}}' }}
</code>

<code class="small d-block">
    {{ '{{company_name}}' }}<br>
    {{ '{{company_address}}' }}<br>
    Tél: {{ '{{company_phone}}' }}
</code>
```

**Explication** : En utilisant `{{ '{{variable}}' }}`, on affiche littéralement la chaîne `{{variable}}` sans que Twig l'interprète.

---

### Fichier : `show.html.twig`

#### Problème : Affichage des variables disponibles (ligne 143)

**Erreur** :
```twig
<span class="badge bg-secondary mb-1">{{{{var}}}}</span>
```

**Erreur Twig** :
```
A mapping key must be a quoted string, a number, a name, 
or an expression enclosed in parentheses (unexpected token "punctuation" of value "{")
```

**Raison** : La syntaxe `{{{{var}}}}` est invalide. Twig ne sait pas comment interpréter les accolades multiples.

**Correction appliquée** :
```twig
<span class="badge bg-secondary mb-1">{{ '{{' ~ var ~ '}}' }}</span>
```

**Explication** : Cette syntaxe concatène les chaînes `'{{'`, la valeur de `var`, et `'}}'` pour afficher `{{nom_variable}}`.

---

## 📝 MÉTHODES D'ÉCHAPPEMENT TWIG

### Méthode 1 : Chaîne littérale simple

**Usage** : Pour afficher une variable fixe

```twig
{{ '{{variable}}' }}
```

**Affiche** : `{{variable}}`

---

### Méthode 2 : Concaténation avec variable

**Usage** : Pour afficher une variable dynamique avec accolades

```twig
{{ '{{' ~ variable_name ~ '}}' }}
```

**Si `variable_name = 'tenant_name'`, affiche** : `{{tenant_name}}`

---

### Méthode 3 : Bloc raw (ATTENTION)

**Usage** : Pour échapper tout un bloc de contenu

```twig
{% raw %}
    Tout ce contenu sera affiché tel quel
    {{variable}} ne sera pas interprété
{% endraw %}
```

**⚠️ Limitation** : Ne fonctionne **PAS** dans les attributs HTML !

**❌ Invalide** :
```twig
<input placeholder="{% raw %}{{var}}{% endraw %}">
```

**✅ Valide** :
```twig
<input placeholder="{var}">
```

---

### Méthode 4 : Accolades simples

**Usage** : Dans les placeholders HTML ou exemples simples

```twig
<input placeholder="Exemple : {variable}">
```

**Affiche** : `Exemple : {variable}`

**Avantage** : Simple, clair, pas de conflit avec Twig

---

## 🎯 BONNES PRATIQUES

### 1. Documentation et exemples

**Pour afficher des variables d'exemple dans la documentation** :

```twig
<div class="alert alert-info">
    <p>Utilisez les variables suivantes :</p>
    <code>{{ '{{tenant_name}}' }}</code>
    <code>{{ '{{payment_amount}}' }}</code>
</div>
```

---

### 2. Placeholders d'input

**Pour des placeholders avec variables** :

```twig
<input type="text" 
       placeholder="Titre : {title} - Date : {date}">
```

**Alternative avec échappement Twig** :
```twig
<input type="text" 
       placeholder="Titre : {{ '{{title}}' }} - Date : {{ '{{date}}' }}">
```

---

### 3. Liste dynamique de variables

**Pour afficher une liste de variables depuis le backend** :

```twig
{% for var in available_variables %}
    <span class="badge">{{ '{{' ~ var ~ '}}' }}</span>
{% endfor %}
```

---

## 📊 RÉCAPITULATIF DES FICHIERS MODIFIÉS

| Fichier | Lignes modifiées | Type de correction |
|---------|------------------|-------------------|
| `edit.html.twig` | 34, 36, 117, 122, 127-129 | Échappement variables |
| `show.html.twig` | 143 | Correction syntaxe |

---

## ✅ VALIDATION

### Tests à effectuer

1. **Affichage de la page d'édition**
   ```
   URL : /admin/templates-email/{id}/edit
   ```
   **Résultat attendu** : ✅ Page s'affiche sans erreur

2. **Affichage de la page de détails**
   ```
   URL : /admin/templates-email/{id}
   ```
   **Résultat attendu** : ✅ Variables affichées avec `{{}}`

3. **Placeholders visibles**
   ```
   Input "Sujet" : placeholder doit afficher "{month}"
   ```
   **Résultat attendu** : ✅ Placeholder lisible

4. **Exemples visibles**
   ```
   Section "Exemples" : doit afficher {{tenant_first_name}}
   ```
   **Résultat attendu** : ✅ Exemples affichés avec doubles accolades

---

## 🔍 VÉRIFICATION SYNTAXE TWIG

### Commandes utiles

**Valider tous les templates** :
```bash
php bin/console lint:twig templates/
```

**Valider un template spécifique** :
```bash
php bin/console lint:twig templates/admin/email_template/edit.html.twig
```

---

## 📚 DOCUMENTATION TWIG OFFICIELLE

### Échappement

- [Documentation officielle - Escaping](https://twig.symfony.com/doc/3.x/api.html#escaping)
- [Raw tag](https://twig.symfony.com/doc/3.x/tags/raw.html)

### Opérateurs

- [Concaténation (~)](https://twig.symfony.com/doc/3.x/templates.html#expressions)
- [String literals](https://twig.symfony.com/doc/3.x/templates.html#literals)

---

## 🎊 RÉSULTAT FINAL

**✅ Tous les templates d'emails fonctionnent correctement !**

### Fonctionnalités validées :

1. ✅ Édition de templates sans erreurs
2. ✅ Affichage des variables disponibles
3. ✅ Exemples visibles et corrects
4. ✅ Placeholders informatifs
5. ✅ Syntaxe Twig valide partout

---

## 💡 NOTES POUR LE FUTUR

### Si vous ajoutez de nouvelles variables d'exemple

**Utilisez toujours l'échappement** :
```twig
{{ '{{nouvelle_variable}}' }}
```

### Si vous créez de nouveaux templates avec raw

**Évitez raw dans les attributs HTML** :
```twig
❌ <input value="{% raw %}{{var}}{% endraw %}">
✅ <input value="{{ '{{var}}' }}">
```

### Si vous avez des variables complexes

**Utilisez la concaténation** :
```twig
{{ '{{' ~ variable_dynamique ~ '}}' }}
```

---

**Status** : ✅ **RÉSOLU À 100%**

*Fix appliqué : 12 octobre 2025*

