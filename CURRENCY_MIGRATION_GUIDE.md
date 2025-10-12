# 💱 Guide de migration vers le système de devises

## 🎯 Problème actuel

Les montants dans les templates utilisent encore `number_format` avec `€` en dur au lieu du filtre `|currency` qui s'adapte automatiquement à la devise configurée.

## ✅ Solution : Remplacements à effectuer

### Pattern à remplacer

**ANCIEN (à remplacer)** :
```twig
{{ montant|number_format(2, ',', ' ') }} €
{{ montant|number_format(0, ',', ' ') }} €
```

**NOUVEAU (à utiliser)** :
```twig
{{ montant|currency }}
```

---

## 📝 Fichiers à mettre à jour (20 fichiers)

### ✅ Déjà corrigés
1. ✅ templates/dashboard/index.html.twig
2. ✅ templates/dashboard/full.html.twig

### ⏳ À corriger

#### Propriétés
3. templates/property/index.html.twig
4. templates/property/show.html.twig

#### Paiements
5. templates/payment/index.html.twig
6. templates/payment/show.html.twig
7. templates/payment/receipt.html.twig

#### Comptabilité
8. templates/accounting/index.html.twig

#### Baux
9. templates/lease/index.html.twig
10. templates/lease/show.html.twig

#### Locataires
11. templates/tenant/index.html.twig
12. templates/tenant/show.html.twig

#### Admin
13. templates/admin/dashboard.html.twig
14. templates/admin/reports.html.twig

#### Emails
15. templates/emails/rent_receipt.html.twig
16. templates/emails/payment_reminder.html.twig
17. templates/emails/lease_expiration.html.twig

#### Maintenance
18. templates/maintenance_request/show.html.twig

#### Devises (à laisser tel quel - affichage technique)
19. templates/admin/settings/currencies.html.twig
20. templates/admin/settings/currency_new.html.twig

---

## 🔍 Exemples de remplacement

### Dans property/show.html.twig

**AVANT** :
```twig
<td>{{ property.monthlyRent|number_format(2, ',', ' ') }} €</td>
<td>{{ property.charges|number_format(2, ',', ' ') }} €</td>
<td>{{ property.deposit|number_format(2, ',', ' ') }} €</td>
```

**APRÈS** :
```twig
<td>{{ property.monthlyRent|currency }}</td>
<td>{{ property.charges|currency }}</td>
<td>{{ property.deposit|currency }}</td>
```

### Dans payment/index.html.twig

**AVANT** :
```twig
<h3>{{ payment.amount|number_format(2, ',', ' ') }} €</h3>
```

**APRÈS** :
```twig
<h3>{{ payment.amount|currency }}</h3>
```

### Dans accounting/index.html.twig

**AVANT** :
```twig
<td>{{ entry.amount|number_format(2, ',', ' ') }} €</td>
```

**APRÈS** :
```twig
<td>{{ entry.amount|currency }}</td>
```

---

## 🚀 Migration automatique (Regex)

Si vous utilisez un éditeur avec recherche/remplacement regex :

### Recherche :
```regex
\{\{\s*([^}]+)\|number_format\([^)]+\)\s*\}\}\s*€
```

### Remplacement :
```
{{ $1|currency }}
```

**OU plus simple** :

### Recherche :
```
|number_format(2, ',', ' ') }} €
```

### Remplacement :
```
|currency }}
```

### Et aussi :
```
|number_format(0, ',', ' ') }} €
```

### Remplacement :
```
|currency }}
```

---

## ✨ Avantages du filtre |currency

1. **Automatique** : S'adapte à la devise active
2. **Cohérent** : Même format partout
3. **Flexible** : Changement de devise en 1 clic
4. **Internationalisé** : Support multi-devises natif

### Exemples

Si devise active = **EUR** :
```
{{ 1234.56|currency }}  → "1 234,56 €"
```

Si devise active = **USD** :
```
{{ 1234.56|currency }}  → "1,234.56 $"
```

Si devise active = **GBP** :
```
{{ 1234.56|currency }}  → "1,234.56 £"
```

---

## 🎯 Plan d'action recommandé

### Option 1 : Modification manuelle (recommandé pour contrôle)
1. Ouvrir chaque fichier de la liste
2. Rechercher `number_format`
3. Remplacer par `|currency`
4. Tester

### Option 2 : Remplacement en masse
1. Utiliser l'éditeur avec regex
2. Remplacer tous d'un coup
3. Vérifier les changements
4. Tester

### Option 3 : Script automatique
Créer un script PHP qui parcourt tous les templates et effectue les remplacements.

---

## ⚠️ Cas particuliers à NE PAS remplacer

### Dans les fichiers de configuration de devises

**NE PAS modifier** :
- `templates/admin/settings/currencies.html.twig`
- `templates/admin/settings/currency_new.html.twig`

Ces fichiers affichent les taux de change bruts et doivent conserver `number_format`.

### Affichage de taux/pourcentages

Si c'est un pourcentage ou un taux :
```twig
{# Garder tel quel #}
{{ rate|number_format(2) }} %
{{ commission|number_format(4) }} %
```

---

## 🧪 Test après migration

### 1. Changer la devise active
- Accédez à `/admin/parametres/devises`
- Cliquez sur ✓ pour EUR
- Vérifiez que tous les montants s'affichent avec €

### 2. Tester avec USD
- Cliquez sur ✓ pour USD  
- Rechargez une page
- Vérifiez que tous les montants s'affichent avec $

### 3. Pages à tester
- Dashboard
- Liste des biens
- Détails d'un bien
- Liste des paiements
- Détails d'un paiement
- Comptabilité
- Baux

---

## 📊 Progression

- ✅ Dashboard (2/2)
- ⏳ Propriétés (0/2)
- ⏳ Paiements (0/3)
- ⏳ Comptabilité (0/1)
- ⏳ Baux (0/2)
- ⏳ Locataires (0/2)
- ⏳ Admin (0/2)
- ⏳ Emails (0/3)
- ⏳ Maintenance (0/1)

**Total** : 2/18 complétés (11%)

---

## 💡 Astuce rapide

Pour voir rapidement tous les endroits à corriger :

```bash
# Rechercher dans tous les templates
grep -r "number_format" templates/

# Compter les occurrences
grep -r "number_format" templates/ | wc -l
```

---

## 🎯 Après la migration complète

Une fois tous les templates mis à jour :

✅ Changer de devise depuis l'admin  
✅ Tous les montants s'adaptent automatiquement  
✅ Format cohérent partout  
✅ Support international complet  

**La devise active sera appliquée PARTOUT automatiquement !** 🌍💱

---

**Action recommandée** : Effectuer les remplacements fichier par fichier en testant au fur et à mesure.

