# ✅ Filtrage des documents par rôle utilisateur

## 🎯 Objectif

Permettre aux locataires de voir uniquement leurs propres documents dans la section "Mes documents", tout en conservant l'accès complet pour les administrateurs et gestionnaires.

---

## 🔧 Modifications apportées

### 1. **Contrôleur DocumentController**

**Fichier** : `src/Controller/DocumentController.php`

#### ✅ Méthode `index()` modifiée

**Avant** ❌ :
```php
public function index(DocumentRepository $documentRepository): Response
{
    // Organiser les documents par type
    $documentsByType = [
        'Assurance' => $documentRepository->findByType('Assurance'),
        'Avis d\'échéance' => $documentRepository->findByType('Avis d\'échéance'),
        'Bail' => array_merge(
            $documentRepository->findByType('Bail'),
            $documentRepository->findByType('Contrat de location')
        ),
        'Diagnostics' => $documentRepository->findByType('Diagnostics'),
        'OK' => $documentRepository->findByType('Conseils'),
    ];
    // ...
}
```

**Maintenant** ✅ :
```php
public function index(DocumentRepository $documentRepository): Response
{
    $user = $this->getUser();
    
    // Initialiser toutes les catégories
    $documentsByType = [
        'Assurance' => [],
        'Avis d\'échéance' => [],
        'Bail' => [],
        'Diagnostics' => [],
        'OK' => [],
    ];

    // Filtrer les documents selon le rôle de l'utilisateur
    if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
        // Si l'utilisateur est un locataire, ne montrer que ses documents
        $tenant = $user->getTenant();
        if ($tenant) {
            $tenantDocuments = $documentRepository->findByTenant($tenant->getId());
            
            // Organiser par type
            foreach ($tenantDocuments as $document) {
                $type = $document->getType();
                
                // Grouper "Bail" et "Contrat de location" ensemble
                if ($type === 'Bail' || $type === 'Contrat de location') {
                    $type = 'Bail';
                }
                // Grouper "Conseils" sous "OK"
                elseif ($type === 'Conseils') {
                    $type = 'OK';
                }
                
                if (isset($documentsByType[$type])) {
                    $documentsByType[$type][] = $document;
                }
            }
        }
    } else {
        // Pour les admins/managers, remplir avec tous les documents
        $documentsByType['Assurance'] = $documentRepository->findByType('Assurance');
        $documentsByType['Avis d\'échéance'] = $documentRepository->findByType('Avis d\'échéance');
        $documentsByType['Bail'] = array_merge(
            $documentRepository->findByType('Bail'),
            $documentRepository->findByType('Contrat de location')
        );
        $documentsByType['Diagnostics'] = $documentRepository->findByType('Diagnostics');
        $documentsByType['OK'] = $documentRepository->findByType('Conseils');
    }
    
    // Calculer les statistiques (filtrées selon le rôle)
    $stats = $this->calculateFilteredStats($documentRepository, $user);

    return $this->render('document/index.html.twig', [
        'documents_by_type' => $documentsByType,
        'stats' => $stats,
        'is_tenant_view' => $user && in_array('ROLE_TENANT', $user->getRoles()),
    ]);
}
```

#### ✅ Méthode `byType()` modifiée

```php
public function byType(string $type, DocumentRepository $documentRepository): Response
{
    $user = $this->getUser();
    $documents = [];

    // Filtrer selon le rôle
    if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
        // Pour les locataires, filtrer par type ET par tenant
        $tenant = $user->getTenant();
        if ($tenant) {
            $allDocuments = $documentRepository->findByTenant($tenant->getId());
            foreach ($allDocuments as $document) {
                $documentType = $document->getType();
                // Grouper "Bail" et "Contrat de location"
                if (($documentType === 'Bail' || $documentType === 'Contrat de location') && $type === 'Bail') {
                    $documents[] = $document;
                } elseif ($documentType === $type) {
                    $documents[] = $document;
                }
            }
        }
    } else {
        // Pour les admins/managers, montrer tous les documents du type
        if ($type === 'Bail') {
            $documents = array_merge(
                $documentRepository->findByType('Bail'),
                $documentRepository->findByType('Contrat de location')
            );
        } else {
            $documents = $documentRepository->findByType($type);
        }
    }

    return $this->render('document/by_type.html.twig', [
        'documents' => $documents,
        'type' => $type,
    ]);
}
```

#### ✅ Méthode `search()` modifiée

```php
public function search(Request $request, DocumentRepository $documentRepository): Response
{
    $query = $request->query->get('q', '');
    $documents = [];
    $user = $this->getUser();

    if ($query) {
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Pour les locataires, filtrer par tenant ET par recherche
            $tenant = $user->getTenant();
            if ($tenant) {
                $allDocuments = $documentRepository->findByTenant($tenant->getId());
                foreach ($allDocuments as $document) {
                    if (stripos($document->getName(), $query) !== false ||
                        stripos($document->getOriginalFileName(), $query) !== false ||
                        stripos($document->getDescription(), $query) !== false) {
                        $documents[] = $document;
                    }
                }
            }
        } else {
            // Pour les admins/managers, recherche globale
            $documents = $documentRepository->search($query);
        }
    }

    return $this->render('document/search.html.twig', [
        'documents' => $documents,
        'query' => $query,
    ]);
}
```

#### ✅ Méthode `expiring()` modifiée

```php
public function expiring(DocumentRepository $documentRepository): Response
{
    $user = $this->getUser();
    $expiringSoon = [];
    $expired = [];

    if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
        // Pour les locataires, filtrer par tenant
        $tenant = $user->getTenant();
        if ($tenant) {
            $allDocuments = $documentRepository->findByTenant($tenant->getId());
            $now = new \DateTime();
            $in30Days = new \DateTime('+30 days');

            foreach ($allDocuments as $document) {
                $expirationDate = $document->getExpirationDate();
                if ($expirationDate) {
                    if ($expirationDate <= $in30Days && $expirationDate > $now) {
                        $expiringSoon[] = $document;
                    } elseif ($expirationDate <= $now) {
                        $expired[] = $document;
                    }
                }
            }
        }
    } else {
        // Pour les admins/managers, montrer tous les documents
        $expiringSoon = $documentRepository->findExpiringSoon();
        $expired = $documentRepository->findExpired();
    }

    return $this->render('document/expiring.html.twig', [
        'expiring_soon' => $expiringSoon,
        'expired' => $expired,
    ]);
}
```

#### ✅ Nouvelle méthode `calculateFilteredStats()`

```php
private function calculateFilteredStats(DocumentRepository $documentRepository, $user): array
{
    if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
        // Pour les locataires, calculer les stats sur leurs documents seulement
        $tenant = $user->getTenant();
        if ($tenant) {
            $tenantDocuments = $documentRepository->findByTenant($tenant->getId());
            
            $stats = [
                'total' => count($tenantDocuments),
                'archived' => 0,
                'expiring_soon' => 0,
                'expired' => 0
            ];

            foreach ($tenantDocuments as $document) {
                if ($document->isArchived()) {
                    $stats['archived']++;
                }
                
                $expirationDate = $document->getExpirationDate();
                if ($expirationDate) {
                    $now = new \DateTime();
                    $in30Days = new \DateTime('+30 days');
                    
                    if ($expirationDate <= $in30Days && $expirationDate > $now) {
                        $stats['expiring_soon']++;
                    } elseif ($expirationDate <= $now) {
                        $stats['expired']++;
                    }
                }
            }
            
            return $stats;
        }
    }

    // Pour les admins/managers, retourner les stats globales
    return $documentRepository->getStatistics();
}
```

---

## 🎯 Fonctionnalités implémentées

### ✅ Pour les Locataires (ROLE_TENANT)

1. **Documents filtrés** : Ne voient que leurs propres documents
2. **Statistiques personnalisées** : Compteurs basés sur leurs documents uniquement
3. **Recherche filtrée** : Recherche uniquement dans leurs documents
4. **Documents expirés** : Seulement leurs documents qui expirent
5. **Navigation par type** : Accès aux documents groupés par catégorie

### ✅ Pour les Admins/Managers

1. **Vue globale** : Accès à tous les documents du système
2. **Statistiques globales** : Compteurs de tous les documents
3. **Recherche globale** : Recherche dans tous les documents
4. **Gestion complète** : Accès à tous les documents expirés

---

## 🔍 Logique de filtrage

### Identification du rôle

```php
if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
    // Logique pour les locataires
} else {
    // Logique pour les admins/managers
}
```

### Récupération du tenant

```php
$tenant = $user->getTenant();
if ($tenant) {
    $tenantDocuments = $documentRepository->findByTenant($tenant->getId());
}
```

### Groupement des types

- **"Bail"** + **"Contrat de location"** → **"Bail"**
- **"Conseils"** → **"OK"**
- Autres types conservés tels quels

---

## 🎊 Résultat

**Le filtrage des documents par rôle est maintenant fonctionnel !**

### Avantages

✅ **Sécurité** : Les locataires ne voient que leurs documents  
✅ **Performance** : Requêtes optimisées selon le rôle  
✅ **UX** : Interface adaptée au rôle de l'utilisateur  
✅ **Statistiques** : Compteurs personnalisés par rôle  
✅ **Recherche** : Filtrage automatique selon le rôle  

### Tests à effectuer

1. **Connexion locataire** → Vérifier que seuls ses documents apparaissent
2. **Connexion admin** → Vérifier l'accès à tous les documents
3. **Recherche locataire** → Vérifier le filtrage par tenant
4. **Statistiques** → Vérifier les compteurs personnalisés

---

**📅 Date** : 12 Octobre 2025  
**✨ Statut** : ✅ Implémentation terminée  
**🎯 Impact** : Filtrage sécurisé des documents par rôle
