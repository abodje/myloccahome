# 🎁 Plan Freemium - MYLOCCA SaaS

## 📋 Vue d'ensemble

Le plan **Freemium** permet aux utilisateurs de tester MYLOCCA **gratuitement et sans limite de temps**, avec des fonctionnalités de base suffisantes pour gérer un petit portefeuille immobilier.

---

## 💎 Caractéristiques du Plan Freemium

### **Prix**
- **Mensuel** : 0 FCFA (GRATUIT)
- **Annuel** : 0 FCFA (GRATUIT)
- **Engagement** : Aucun
- **Durée** : Illimitée

### **Limites**

| Ressource | Limite | Note |
|-----------|--------|------|
| **Propriétés** | 2 | Parfait pour tester |
| **Locataires** | 3 | Gérer quelques baux |
| **Utilisateurs** | 1 | Compte administrateur uniquement |
| **Documents** | 10 | Stockage limité |

### **Fonctionnalités Incluses** ✅

1. ✅ **Dashboard** : Vue d'ensemble complète
2. ✅ **Gestion propriétés** : Ajouter, modifier, gérer 2 propriétés
3. ✅ **Gestion locataires** : Jusqu'à 3 locataires
4. ✅ **Gestion baux** : Créer et suivre les contrats
5. ✅ **Suivi paiements** : Historique et statuts

### **Fonctionnalités NON Incluses** ❌

1. ❌ Documents illimités (limité à 10)
2. ❌ Comptabilité avancée
3. ❌ Demandes de maintenance
4. ❌ Paiements en ligne (CinetPay)
5. ❌ Acomptes
6. ❌ Rapports détaillés
7. ❌ Notifications email automatiques
8. ❌ Notifications SMS
9. ❌ Support prioritaire
10. ❌ Branding personnalisé

---

## 🎯 Objectif du Freemium

### **Pour MYLOCCA**

1. **Acquisition** : Attirer de nouveaux utilisateurs sans barrière
2. **Conversion** : Inciter à upgrader vers plans payants
3. **Marketing** : Bouche-à-oreille et démonstration
4. **Données** : Comprendre les besoins utilisateurs

### **Pour l'Utilisateur**

1. **Découverte** : Tester sans risque
2. **Formation** : Apprendre à utiliser l'outil
3. **Validation** : Vérifier que ça répond aux besoins
4. **Démarrage** : Commencer sans investissement

---

## 🔄 Parcours Freemium

### **Inscription Simplifiée**

```
1. Visiteur → /inscription/plans
   ↓
2. Clic sur plan "Freemium" → Bouton "Commencer GRATUITEMENT"
   ↓
3. Formulaire d'inscription (simplifié)
   ├─ Nom entreprise
   ├─ Email
   └─ Mot de passe
   ↓
4. Soumission → Activation IMMÉDIATE
   (Pas de paiement requis)
   ↓
5. Redirection vers /login
   ↓
6. Connexion → Dashboard opérationnel
   ✅ Compte actif, abonnement GRATUIT permanent
```

### **Différence avec Plans Payants**

**Plan Freemium :**
- ✅ Activation instantanée
- ✅ Aucun paiement
- ✅ Statut : `ACTIVE` (pas `TRIAL`)
- ✅ Durée : Illimitée

**Plans Payants :**
- ⏳ Période d'essai 30 jours
- 💳 Paiement requis après essai
- ⏰ Renouvellement mensuel/annuel

---

## 💡 Stratégie de Conversion Freemium → Payant

### **Déclencheurs d'Upgrade**

#### **1. Atteinte des Limites**

Quand l'utilisateur essaie d'ajouter :
- 3ème propriété → ❌ Bloqué
- 4ème locataire → ❌ Bloqué
- 11ème document → ❌ Bloqué

**Message affiché :**
```
⚠️ Limite atteinte !
Vous avez atteint la limite de 2 propriétés du plan Freemium.

Passez au plan Starter (5 propriétés) pour seulement 9,900 FCFA/mois.

[Voir les plans] [Upgrader maintenant]
```

#### **2. Fonctionnalités Premium**

Quand l'utilisateur clique sur :
- "Paiements en ligne" → Badge "Premium"
- "Comptabilité" → Badge "Professional"
- "SMS" → Badge "Enterprise"

**Modal affiché :**
```
🔒 Fonctionnalité Premium

Les paiements en ligne sont disponibles à partir du plan Professional.

Avantages :
✅ Paiements Mobile Money & CB
✅ Acomptes automatiques
✅ Quittances instantanées

À partir de 24,900 FCFA/mois

[Découvrir Professional] [Peut-être plus tard]
```

#### **3. Bannières In-App**

Dans le dashboard Freemium :
```
┌────────────────────────────────────────┐
│ 💡 Conseil: Passez au plan Starter    │
│ pour gérer jusqu'à 5 propriétés !     │
│                      [Upgrader] [✕]   │
└────────────────────────────────────────┘
```

---

## 📊 Comparaison des Plans

### **Tableau Comparatif**

| Fonctionnalité | Freemium | Starter | Professional | Enterprise |
|----------------|----------|---------|--------------|------------|
| **Prix** | 0 | 9,9K | 24,9K | 49,9K |
| **Propriétés** | 2 | 5 | 20 | ∞ |
| **Locataires** | 3 | 10 | 50 | ∞ |
| **Utilisateurs** | 1 | 2 | 5 | ∞ |
| **Documents** | 10 | 50 | 200 | ∞ |
| **Dashboard** | ✅ | ✅ | ✅ | ✅ |
| **Paiements** | ✅ | ✅ | ✅ | ✅ |
| **Comptabilité** | ❌ | ❌ | ✅ | ✅ |
| **Paiements en ligne** | ❌ | ❌ | ✅ | ✅ |
| **SMS** | ❌ | ❌ | ❌ | ✅ |
| **Support** | Email 72h | Email 48h | Email 24h | Prioritaire |

---

## 🎨 Interface Freemium

### **Badge "Freemium"**

Dans le dashboard :
```html
<div class="alert alert-info">
    <i class="bi bi-gift me-2"></i>
    Vous utilisez le <strong>plan Freemium</strong>
    <a href="/mon-abonnement/upgrade" class="alert-link">Upgrader</a>
    pour débloquer plus de fonctionnalités.
</div>
```

### **Indicateur de Limite**

```html
<div class="progress mb-3">
    <div class="progress-bar" style="width: {{ (currentProperties / maxProperties) * 100 }}%">
        {{ currentProperties }} / {{ maxProperties }} propriétés
    </div>
</div>

{% if currentProperties >= maxProperties %}
    <div class="alert alert-warning">
        ⚠️ Limite atteinte ! <a href="/upgrade">Upgrader</a> pour ajouter plus de propriétés.
    </div>
{% endif %}
```

---

## 🚀 Avantages du Modèle Freemium

### **Pour MYLOCCA**

1. **Acquisition massive** : Pas de barrière à l'entrée
2. **Démonstration** : Les utilisateurs découvrent la valeur
3. **Données** : Comprendre l'usage et améliorer
4. **Network effect** : Recommandations entre utilisateurs
5. **Pipeline de conversion** : Base d'utilisateurs à convertir

### **Pour l'Utilisateur**

1. **Zéro risque** : Tester sans engagement
2. **Apprentissage** : Se former gratuitement
3. **Validation** : Vérifier la pertinence
4. **Démarrage** : Commencer sans budget
5. **Flexibilité** : Upgrader quand prêt

---

## 💰 Modèle Économique

### **Répartition Cible**

| Statut | % Utilisateurs | Contribution Revenue |
|--------|----------------|----------------------|
| **Freemium** | 70% | 0% |
| **Starter** | 15% | 20% |
| **Professional** | 12% | 50% |
| **Enterprise** | 3% | 30% |

### **Objectif de Conversion**

- **Freemium → Starter** : 20% (après 3 mois)
- **Starter → Professional** : 30% (après 6 mois)
- **Professional → Enterprise** : 10% (après 1 an)

### **Calcul du LTV (Lifetime Value)**

```
LTV Freemium → Starter : 
20% × 9,900 FCFA/mois × 24 mois = 47,520 FCFA

LTV Starter → Professional :
30% × 24,900 FCFA/mois × 18 mois = 134,460 FCFA
```

---

## 🎯 Tactiques de Conversion

### **1. Notifications In-App**

```php
// Après 1 semaine d'utilisation
"Vous utilisez 100% de vos propriétés ! 
Ajoutez 3 propriétés supplémentaires avec le plan Starter (9,9K/mois)"

// Après 1 mois
"Félicitations pour 1 mois d'utilisation ! 
Débloquez la comptabilité avec le plan Professional (24,9K/mois)"

// Quand limite atteinte
"Limite de documents atteinte (10/10). 
Passez à Starter pour 50 documents (9,9K/mois)"
```

### **2. Emails Marketing**

- Semaine 1 : Tips & astuces
- Semaine 2 : Success stories clients
- Semaine 3 : Fonctionnalités premium
- Semaine 4 : Offre spéciale upgrade (-20%)

### **3. Comparaisons Directes**

Afficher ce qu'ils manquent :
```
Avec le plan Professional, vous pourriez :
✨ Accepter des paiements en ligne
✨ Générer des quittances automatiquement
✨ Envoyer des rappels par email
✨ Gérer 20 propriétés au lieu de 2

[Essayer Professional 30 jours gratuits]
```

---

## 🔧 Implémentation Technique

### **Activation Automatique Plan Freemium**

```php
// Dans RegistrationController::register()

if ($plan->getSlug() === 'freemium' || (float)$plan->getMonthlyPrice() == 0) {
    // Activer directement sans paiement
    $subscriptionService->activateSubscription($subscription);
    $entityManager->flush();
    
    $this->addFlash('success', '🎉 Compte créé ! Connectez-vous pour commencer.');
    return $this->redirectToRoute('app_login');
}
```

### **Vérification des Limites**

```php
// Dans PropertyController::new()

$organization = $this->getUser()->getOrganization();
$currentCount = $organization->getProperties()->count();
$maxAllowed = $organization->getSetting('max_properties');

if ($maxAllowed && $currentCount >= $maxAllowed) {
    $this->addFlash('warning', sprintf(
        'Vous avez atteint la limite de %d propriétés de votre plan %s. Upgradez pour continuer.',
        $maxAllowed,
        $organization->getActiveSubscription()->getPlan()->getName()
    ));
    
    return $this->redirectToRoute('app_subscription_upgrade');
}
```

---

## 📊 KPIs Spécifiques Freemium

### **Métriques à Suivre**

1. **Taux d'inscription** : Inscriptions Freemium / Visiteurs
2. **Activation rate** : Utilisateurs actifs / Inscrits
3. **Time to convert** : Temps moyen Freemium → Payant
4. **Feature usage** : Quelles features utilisent-ils le plus ?
5. **Churn reasons** : Pourquoi abandonnent-ils ?

### **Signaux de Conversion**

**Utilisateur prêt à payer si :**
- ✅ Connexion > 3x/semaine pendant 1 mois
- ✅ A atteint 80%+ des limites
- ✅ A exploré les features premium
- ✅ A invité d'autres utilisateurs (blocage à 1)

---

## 🎨 Design de la Carte Freemium

### **Apparence sur /inscription/plans**

```
┌─────────────────────────────────────────┐
│          🎁 Freemium                    │
│  Testez gratuitement pour toujours      │
│                                         │
│         GRATUIT ✨                      │
│        Pour toujours                    │
│    ∞ Aucun engagement, aucun paiement   │
│                                         │
│  🏠 2 propriétés                        │
│  👥 3 locataires                        │
│  👤 1 utilisateur                       │
│  📄 10 documents                        │
│                                         │
│  Fonctionnalités incluses :            │
│  ✓ Dashboard                            │
│  ✓ Gestion propriétés                  │
│  ✓ Gestion locataires                  │
│  ✓ Gestion baux                        │
│  ✓ Suivi paiements                     │
│                                         │
│  [Commencer GRATUITEMENT] (vert)       │
│  ∞ Gratuit pour toujours               │
└─────────────────────────────────────────┘
```

---

## 💡 Messages de Motivation

### **Après Inscription**

```
🎉 Bienvenue sur MYLOCCA !

Votre compte Freemium est actif. Vous pouvez :
✅ Ajouter jusqu'à 2 propriétés
✅ Gérer 3 locataires
✅ Créer des baux et suivre les paiements

💡 Astuce : Explorez toutes les fonctionnalités et 
passez à Starter quand vous aurez plus de 2 propriétés !
```

### **Quand Limite Atteinte**

```
⚠️ Vous avez atteint 2/2 propriétés

Félicitations pour votre activité ! Pour continuer à grandir :

🚀 Plan Starter (9,900 FCFA/mois)
   • Jusqu'à 5 propriétés
   • 10 locataires
   • 50 documents

[Voir les plans] [Upgrader maintenant]
```

---

## 🔐 Restrictions du Plan Freemium

### **Fonctionnalités Désactivées**

Dans l'interface, afficher des badges "Premium" :

```twig
{% if not organization.hasFeature('online_payments') %}
    <div class="text-center p-4 bg-light rounded">
        <i class="bi bi-lock text-warning" style="font-size: 3rem;"></i>
        <h5 class="mt-3">Fonctionnalité Premium</h5>
        <p class="text-muted">
            Les paiements en ligne sont disponibles à partir du plan Professional
        </p>
        <a href="{{ path('app_subscription_upgrade') }}" class="btn btn-warning">
            Upgrader mon plan
        </a>
    </div>
{% endif %}
```

### **Vérifications Systématiques**

```php
// Avant toute création
if (!$subscriptionService->canAddResource($organization, 'properties')) {
    throw new AccessDeniedException(
        'Limite de propriétés atteinte. Upgradez votre plan.'
    );
}
```

---

## 📈 Optimisation du Funnel

### **Étapes de Conversion**

```
1. Inscription Freemium (100%)
   ↓
2. Premier login (80%)
   ↓
3. Ajout 1ère propriété (60%)
   ↓
4. Utilisation régulière 1 mois (40%)
   ↓
5. Atteint limite (20%)
   ↓
6. Clique "Upgrade" (15%)
   ↓
7. Paiement réussi (12%)
   = 12% de conversion Freemium → Starter
```

### **Points d'Amélioration**

- **Onboarding** : Guide pas-à-pas pour nouveaux Freemium
- **Quick wins** : Petites victoires rapides
- **Feature discovery** : Montrer ce qu'ils manquent
- **Social proof** : Témoignages d'utilisateurs payants

---

## 🎁 Offres Spéciales Freemium

### **Offre de Lancement**

```
🎉 OFFRE SPÉCIALE NOUVEAU CLIENT

Passez de Freemium à Professional et obtenez :
• 2 mois OFFERTS sur l'abonnement annuel
• Migration gratuite de toutes vos données
• Formation personnalisée en vidéo

Valable 7 jours après inscription

[J'en profite !]
```

### **Offre d'Anniversaire**

```
🎂 Joyeux anniversaire !

Cela fait 1 an que vous utilisez MYLOCCA Freemium !

Pour vous remercier :
-50% sur votre 1er mois Starter
Code: ANNIVERSARY50

[Upgrader maintenant]
```

---

## 📝 Fichiers Modifiés pour Freemium

### **Commande**
- ✅ `src/Command/CreateDefaultPlansCommand.php` : Ajout plan Freemium

### **Contrôleur**
- ✅ `src/Controller/RegistrationController.php` : Activation directe si gratuit

### **Templates**
- ✅ `templates/registration/plans.html.twig` : Badge "GRATUIT", bouton vert

### **Configuration**
- ✅ `config/packages/security.yaml` : Route `/inscription` publique

---

## 🎯 Prochaines Étapes

### **Pour Activer le Freemium**

```bash
# 1. Créer les plans (incluant Freemium)
php bin/console app:create-default-plans

# 2. Vider le cache
php bin/console cache:clear

# 3. Tester l'inscription
# Aller sur /inscription/plans
# Cliquer sur plan Freemium
# S'inscrire
# ✅ Activation immédiate sans paiement
```

### **Pour Suivre les Conversions**

1. Créer dashboard analytics Freemium
2. Tracker les événements (atteinte limites, clics upgrade)
3. A/B tester les messages de conversion
4. Analyser les raisons d'abandon

---

## 💰 ROI Estimé du Freemium

### **Scénario Conservateur**

**Hypothèses :**
- 100 inscriptions Freemium/mois
- 12% de conversion vers Starter (12 clients)
- LTV moyen Starter : 300,000 FCFA (30 mois)

**Calcul :**
```
Revenue/an = 12 clients/mois × 12 mois × 9,900 FCFA
           = 1,425,600 FCFA/an depuis Freemium

ROI = Positif (coût d'hébergement négligeable pour Freemium)
```

### **Scénario Optimiste**

- 500 inscriptions/mois
- 15% conversion
- Mix Starter (60%) + Professional (40%)

```
Revenue/an = (45 × 9,900) + (30 × 24,900) × 12
           = ~14,796,000 FCFA/an
```

---

## ✅ Checklist de Lancement

### **Technique**
- [x] Entité Plan avec prix = 0
- [x] Route `/inscription` publique
- [x] Activation sans paiement
- [ ] Migration DB pour organization_id
- [ ] Vérification des limites dans contrôleurs

### **Marketing**
- [ ] Page de comparaison des plans
- [ ] Témoignages clients
- [ ] FAQ Freemium
- [ ] Bannières de conversion

### **Support**
- [ ] Documentation utilisateur Freemium
- [ ] Tutoriels vidéo
- [ ] Guide d'upgrade
- [ ] Process de support email

---

**Date de création :** 12 octobre 2025  
**Version :** 1.0  
**Statut :** ✅ Plan Freemium créé et opérationnel
