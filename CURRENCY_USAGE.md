# Guide d'utilisation de la devise dans MYLOCCA

## Vue d'ensemble

Le système de devises de MYLOCCA permet de gérer plusieurs devises et d'afficher automatiquement tous les montants avec la devise active sélectionnée en administration.

## Configuration

### Définir la devise active

1. Accédez à **Administration > Paramètres > Devises**
2. Cliquez sur le bouton **✓** (Définir comme devise active) à côté de la devise souhaitée
3. La devise sera immédiatement appliquée partout dans l'application

## Utilisation dans les templates Twig

### Filtre `currency`

Le filtre `currency` formate automatiquement un montant avec la devise active :

```twig
{# Affiche: 1 234,56 € (si EUR est la devise active) #}
{{ 1234.56|currency }}

{# Affiche: 1 234,56 (sans symbole) #}
{{ 1234.56|currency(false) }}
```

### Fonction `default_currency()`

Récupère l'objet de la devise active :

```twig
{# Affiche le code de la devise (ex: EUR) #}
{{ default_currency().code }}

{# Affiche le symbole (ex: €) #}
{{ default_currency().symbol }}

{# Affiche le nom (ex: Euro) #}
{{ default_currency().name }}
```

### Filtre `currency_symbol`

Récupère uniquement le symbole de la devise active :

```twig
{# Affiche: € #}
{{ ''|currency_symbol }}
```

### Fonction `format_amount()`

Formate un montant avec une devise spécifique :

```twig
{# Formate avec la devise active #}
{{ format_amount(1234.56) }}

{# Formate avec une devise spécifique #}
{{ format_amount(1234.56, 'USD') }}

{# Formate sans symbole #}
{{ format_amount(1234.56, null, false) }}
```

## Exemples pratiques

### Dans les tableaux

```twig
<table class="table">
    <thead>
        <tr>
            <th>Description</th>
            <th>Montant</th>
        </tr>
    </thead>
    <tbody>
        {% for payment in payments %}
            <tr>
                <td>{{ payment.type }}</td>
                <td>{{ payment.amount|currency }}</td>
            </tr>
        {% endfor %}
    </tbody>
</table>
```

### Dans les cartes de statistiques

```twig
<div class="card">
    <div class="card-body text-center">
        <h3>{{ totalRevenue|currency }}</h3>
        <p class="text-muted">Revenu total</p>
    </div>
</div>
```

### Dans les formulaires

```twig
<div class="input-group">
    {{ form_widget(form.amount, {'class': 'form-control'}) }}
    <span class="input-group-text">{{ ''|currency_symbol }}</span>
</div>
```

## Migration des templates existants

### Remplacer les anciennes méthodes

**Avant :**
```twig
{{ property.monthlyRent|number_format(2, ',', ' ') }} €
```

**Après :**
```twig
{{ property.monthlyRent|currency }}
```

### Avantages

1. **Automatique** : La devise change automatiquement dans toute l'application
2. **Cohérent** : Tous les montants utilisent le même format
3. **Flexible** : Facile de changer de devise sans modifier les templates
4. **Internationalisé** : Support de multiples devises et formats

## Exemples de conversion

Le système inclut un convertisseur intégré dans l'administration pour faciliter les conversions entre devises.

### Utilisation programmatique

```php
// Dans un contrôleur ou service
$currencyService = $this->container->get(CurrencyService::class);

// Formater un montant
$formatted = $currencyService->formatAmount(1234.56);

// Convertir entre devises
$eur = $currencyService->getCurrencyByCode('EUR');
$usd = $currencyService->getCurrencyByCode('USD');
$converted = $currencyService->convertAmount(1000, $eur, $usd);
```

## Notes importantes

- **Devise par défaut** : Utilisée lors de l'initialisation du système
- **Devise active** : Celle actuellement utilisée dans l'application
- **Taux de change** : Peuvent être mis à jour automatiquement via l'administration
- **Calculs** : Toujours effectués en devise de base (celle avec taux = 1.0)

## Support

Pour toute question sur l'utilisation des devises, consultez la documentation ou contactez le support technique.

