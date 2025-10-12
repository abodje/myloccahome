# 💰 Bouton "Payer un Acompte" pour les Locataires - Documentation

## 📋 Vue d'ensemble

Les locataires disposent maintenant d'un accès facile et visible aux **paiements en avance (acomptes)**, avec plusieurs points d'accès dans l'interface. Cette fonctionnalité n'est visible que si elle est **activée dans les paramètres système**.

---

## 🎯 Fonctionnalités Implémentées

### **1. Menu Latéral "Acomptes"**

Un nouveau menu dédié apparaît dans la barre latérale pour les locataires :

#### **Caractéristiques :**
- **Icône** : 🐷 Tirelire (`bi-piggy-bank`)
- **Label** : "Acomptes"
- **Route** : `app_advance_payment_index`
- **Rôle requis** : `ROLE_TENANT`
- **Condition** : Activé uniquement si `allow_advance_payments` est `true`

#### **Code dans MenuService :**
```php
'advance_payments' => [
    'label' => 'Acomptes',
    'icon' => 'bi-piggy-bank',
    'route' => 'app_advance_payment_index',
    'roles' => ['ROLE_TENANT'],
    'order' => 6.5,
    'visible_condition' => 'allow_advance_payments',
],
```

---

### **2. Bouton dans la Page "Mes Paiements"**

Un bouton proéminent apparaît en haut de la page `/mes-paiements/` :

#### **Caractéristiques :**
- **Position** : Section `page_actions` (en haut à droite)
- **Style** : Bouton vert (`btn-success`)
- **Icône** : 🐷 Tirelire
- **Texte** : "Payer un acompte"
- **Route** : `app_online_payment_pay_advance`

#### **Code dans le Template :**
```twig
{% if is_granted('ROLE_TENANT') and app_setting('allow_advance_payments', true) %}
    <a href="{{ path('app_online_payment_pay_advance') }}" class="btn btn-success btn-sm me-2">
        <i class="bi bi-piggy-bank me-2"></i>
        Payer un acompte
    </a>
{% endif %}
```

---

### **3. Carte d'Information sur les Acomptes**

Une carte informative détaillée apparaît sur la page des paiements :

#### **Contenu de la Carte :**
- **Icône géante** : Tirelire (3rem)
- **Titre** : "Paiements en avance disponibles"
- **Description** : Explication de la fonctionnalité
- **Avantage** : Message clair sur l'automatisation
- **Bouton CTA** : "Payer un acompte" (vert)
- **Solde disponible** : Affiché si le locataire a des acomptes

#### **Exemple Visuel :**
```
┌────────────────────────────────────────────────────────────┐
│  🐷   ℹ️ Paiements en avance disponibles                   │
│                                                             │
│       Vous pouvez effectuer des paiements en avance        │
│       (acomptes) pour faciliter la gestion de votre        │
│       budget. Ces acomptes seront automatiquement          │
│       appliqués à vos prochaines échéances de loyer.       │
│                                                             │
│       💡 Avantage: Payez quand vous le pouvez, nous        │
│       déduisons automatiquement de vos futurs loyers !     │
│                                                             │
│                              [Payer un acompte] ──────►     │
│                              ✓ Solde acompte disponible:   │
│                                 5,000 FCFA                  │
└────────────────────────────────────────────────────────────┘
```

---

## 🔧 Implémentation Technique

### **Fichiers Modifiés**

#### **1. MenuService (`src/Service/MenuService.php`)**

**Ajout du menu "Acomptes" :**
```php
'advance_payments' => [
    'label' => 'Acomptes',
    'icon' => 'bi-piggy-bank',
    'route' => 'app_advance_payment_index',
    'roles' => ['ROLE_TENANT'],
    'order' => 6.5,
    'visible_condition' => 'allow_advance_payments',
],
```

**Modification de `canAccessMenuItem()` :**
```php
public function canAccessMenuItem(array $menuItem): bool
{
    // ... code existant ...

    // Vérifier la condition de visibilité (paramètre système)
    if (isset($menuItem['visible_condition'])) {
        $settingValue = $this->settingsService->get($menuItem['visible_condition'], false);
        if (!$settingValue) {
            return false;
        }
    }

    return true;
}
```

**Injection du SettingsService :**
```php
public function __construct(
    private Security $security,
    private SettingsService $settingsService  // ✅ Ajouté
) {
}
```

#### **2. Template Paiements (`templates/payment/index.html.twig`)**

**Section `page_actions` :**
```twig
{% block page_actions %}
    {% if is_granted('ROLE_TENANT') and app_setting('allow_advance_payments', true) %}
        <a href="{{ path('app_online_payment_pay_advance') }}" class="btn btn-success btn-sm me-2">
            <i class="bi bi-piggy-bank me-2"></i>
            Payer un acompte
        </a>
    {% endif %}
    
    {% if not is_granted('ROLE_TENANT') %}
        <a href="{{ path('app_payment_new') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-2"></i>
            Nouveau paiement
        </a>
    {% endif %}
{% endblock %}
```

**Carte d'information :**
```twig
{% if is_granted('ROLE_TENANT') and app_setting('allow_advance_payments', true) %}
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-success">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-1 text-center">
                        <i class="bi bi-piggy-bank text-success" style="font-size: 3rem;"></i>
                    </div>
                    <div class="col-md-8">
                        <h5 class="card-title text-success mb-2">
                            <i class="bi bi-info-circle me-2"></i>Paiements en avance disponibles
                        </h5>
                        <p class="card-text mb-0">
                            Vous pouvez effectuer des <strong>paiements en avance (acomptes)</strong> 
                            pour faciliter la gestion de votre budget. Ces acomptes seront automatiquement 
                            appliqués à vos prochaines échéances de loyer.
                        </p>
                        <small class="text-muted">
                            💡 <strong>Avantage :</strong> Payez quand vous le pouvez, nous déduisons 
                            automatiquement de vos futurs loyers !
                        </small>
                    </div>
                    <div class="col-md-3 text-end">
                        <a href="{{ path('app_online_payment_pay_advance') }}" class="btn btn-success">
                            <i class="bi bi-piggy-bank me-2"></i>
                            Payer un acompte
                        </a>
                        {% if advance_stats.available_balance > 0 %}
                        <div class="mt-2">
                            <small class="text-success">
                                <i class="bi bi-check-circle"></i>
                                Solde acompte disponible : <strong>{{ advance_stats.available_balance|currency }}</strong>
                            </small>
                        </div>
                        {% endif %}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{% endif %}
```

---

## 🎛️ Activation/Désactivation

### **Paramètre Système**

La fonctionnalité est contrôlée par le paramètre :
- **Clé** : `allow_advance_payments`
- **Valeur par défaut** : `true`
- **Page d'administration** : `/admin/parametres/paiements`

### **Comment Activer/Désactiver**

1. Connectez-vous en tant qu'administrateur
2. Accédez à **Paramètres > Paiements**
3. Cochez/Décochez **"Autoriser les paiements en avance (acomptes)"**
4. Enregistrez

**Effet immédiat :**
- ✅ **Activé** : Le menu et les boutons apparaissent pour les locataires
- ❌ **Désactivé** : Tout disparaît automatiquement

---

## 🎯 Points d'Accès pour les Locataires

### **1. Menu Latéral**
```
📱 Barre latérale
   ├── Mon tableau de bord
   ├── Mes demandes
   ├── Mes biens
   ├── Mes paiements
   ├── 🐷 Acomptes         ← NOUVEAU (si activé)
   ├── Ma comptabilité
   └── Mes documents
```

### **2. Page Paiements**
```
📄 /mes-paiements/
   ├── [Payer un acompte] ← Bouton en haut à droite
   ├── Solde actuel
   ├── 💳 Carte d'information acomptes ← NOUVEAU (si activé)
   ├── Filtres
   └── Historique des paiements
```

### **3. Routes Disponibles**
- `app_advance_payment_index` : Liste des acomptes
- `app_online_payment_pay_advance` : Formulaire de paiement
- `app_advance_payment_show` : Détails d'un acompte

---

## 📊 Cas d'Usage

### **Scénario 1 : Locataire avec Acompte Disponible**

**Affichage :**
```
┌─────────────────────────────────────────┐
│  💰 Solde actuel : -12,500 FCFA         │
│  ⚠️ Vous avez un solde débiteur         │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│  🐷 Paiements en avance disponibles     │
│  ... description ...                     │
│  [Payer un acompte]                     │
│  ✓ Solde acompte disponible: 5,000 FCFA│
└─────────────────────────────────────────┘
```

### **Scénario 2 : Locataire sans Acompte**

**Affichage :**
```
┌─────────────────────────────────────────┐
│  💰 Solde actuel : -12,500 FCFA         │
│  ⚠️ Vous avez un solde débiteur         │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│  🐷 Paiements en avance disponibles     │
│  ... description ...                     │
│  [Payer un acompte]                     │
└─────────────────────────────────────────┘
```

### **Scénario 3 : Acomptes Désactivés**

**Affichage :**
```
┌─────────────────────────────────────────┐
│  💰 Solde actuel : -12,500 FCFA         │
│  ⚠️ Vous avez un solde débiteur         │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│  Filtres                                 │
│  ... filtres de recherche ...            │
└─────────────────────────────────────────┘

(Pas de carte d'information, pas de bouton dans le menu)
```

---

## 🔐 Sécurité

### **Contrôles d'Accès**

1. **Vérification du rôle** : `is_granted('ROLE_TENANT')`
2. **Vérification du paramètre** : `app_setting('allow_advance_payments', true)`
3. **Double vérification** : Dans le menu ET dans le template

### **Protection Côté Serveur**

Le `AdvancePaymentController` vérifie également :
```php
if (!$paymentSettings->isAdvancePaymentAllowed()) {
    $this->addFlash('error', 'Les paiements en avance ne sont pas activés.');
    return $this->redirectToRoute('app_payment_index');
}
```

---

## 💡 Avantages

### **Pour le Locataire**

1. **Visibilité accrue** : Plusieurs points d'accès
2. **Information claire** : Carte explicative
3. **Facilité d'utilisation** : Boutons proéminents
4. **Feedback immédiat** : Affichage du solde disponible

### **Pour le Gestionnaire**

1. **Contrôle total** : Activation/désactivation simple
2. **Pas de confusion** : Visible uniquement si activé
3. **Encouragement** : Communication claire des avantages

### **Pour le Système**

1. **Modularité** : Facile à activer/désactiver
2. **Cohérence** : Même logique partout
3. **Maintenabilité** : Code centralisé

---

## 🎨 Design et UX

### **Couleurs Utilisées**

- **Vert (`success`)** : Pour les acomptes (positif, économie)
- **Bleu (`primary`)** : Pour les actions standards
- **Rouge (`danger`)** : Pour les alertes de solde débiteur

### **Iconographie**

- **🐷 Tirelire (`bi-piggy-bank`)** : Symbole d'épargne et d'acompte
- **ℹ️ Info (`bi-info-circle`)** : Pour l'information
- **✓ Check (`bi-check-circle`)** : Pour confirmer un solde disponible

### **Hiérarchie Visuelle**

1. **Solde actuel** : Priorité haute (en haut)
2. **Carte acomptes** : Priorité moyenne (encadrée, colorée)
3. **Filtres** : Priorité basse (fonctionnalité secondaire)

---

## 📝 Messages et Communication

### **Titre de la Carte**
```
ℹ️ Paiements en avance disponibles
```

### **Description**
```
Vous pouvez effectuer des paiements en avance (acomptes) pour faciliter 
la gestion de votre budget. Ces acomptes seront automatiquement appliqués 
à vos prochaines échéances de loyer.
```

### **Avantage**
```
💡 Avantage : Payez quand vous le pouvez, nous déduisons automatiquement 
de vos futurs loyers !
```

---

## 🧪 Tests Recommandés

### **Test 1 : Activation/Désactivation**
1. Activer les acomptes dans les paramètres
2. Vérifier que le menu apparaît pour un locataire
3. Vérifier que la carte apparaît sur `/mes-paiements/`
4. Désactiver les acomptes
5. Vérifier que tout disparaît

### **Test 2 : Visibilité par Rôle**
1. Connectez-vous en tant que locataire → Visible
2. Connectez-vous en tant que gestionnaire → Non visible
3. Connectez-vous en tant qu'admin → Non visible

### **Test 3 : Navigation**
1. Cliquer sur le menu "Acomptes" → Redirige vers la liste
2. Cliquer sur "Payer un acompte" → Redirige vers le formulaire
3. Vérifier que les routes fonctionnent

### **Test 4 : Affichage du Solde**
1. Créer un acompte pour un locataire
2. Vérifier que le solde s'affiche dans la carte
3. Utiliser l'acompte
4. Vérifier que le solde est mis à jour

---

## 🔄 Flux Utilisateur

### **Parcours Locataire : Payer un Acompte**

```
1. Connexion en tant que locataire
   ↓
2. Navigation vers /mes-paiements/
   ↓
3. Visualisation de la carte d'information
   ↓
4. Clic sur [Payer un acompte]
   ↓
5. Choix du bail et du montant
   ↓
6. Paiement en ligne (CinetPay)
   ↓
7. Confirmation et création de l'acompte
   ↓
8. Application automatique aux loyers futurs
   ↓
9. Affichage du solde disponible sur la page
```

---

## 📞 Support et Documentation

### **Pour les Utilisateurs**

**Question** : "Où puis-je payer un acompte ?"
**Réponse** : Trois endroits :
1. Menu latéral → "Acomptes"
2. Page "Mes paiements" → Bouton vert en haut
3. Page "Mes paiements" → Carte d'information

**Question** : "Pourquoi je ne vois pas le menu Acomptes ?"
**Réponse** : Vérifiez que :
1. Vous êtes connecté en tant que locataire
2. Les acomptes sont activés (contactez l'administrateur)

### **Pour les Administrateurs**

**Activation** : `/admin/parametres/paiements` → Cocher "Autoriser les paiements en avance"

---

**Date de création :** 12 octobre 2025  
**Version :** 1.0  
**Statut :** ✅ Implémenté et opérationnel
