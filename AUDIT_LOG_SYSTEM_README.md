# 📜 Système d'Audit Log / Historique des Actions

## 🎯 Vue d'ensemble

Le **Système d'Audit Log** permet de tracer toutes les actions importantes effectuées dans l'application MYLOCCA. Chaque action (création, modification, suppression, consultation) est enregistrée avec tous les détails nécessaires pour la traçabilité et la conformité.

---

## ✅ Fonctionnalités Implémentées

### **1. Entité AuditLog**

**Fichier :** `src/Entity/AuditLog.php`

**Champs principaux :**
- `user` - Utilisateur ayant effectué l'action
- `action` - Type d'action (CREATE, UPDATE, DELETE, etc.)
- `entityType` - Type d'entité concernée (Property, Tenant, etc.)
- `entityId` - ID de l'entité
- `description` - Description textuelle
- `oldValues` - Valeurs avant modification (JSON)
- `newValues` - Valeurs après modification (JSON)
- `ipAddress` - Adresse IP de l'utilisateur
- `userAgent` - Navigateur utilisé
- `createdAt` - Date/heure de l'action
- `organization` - Organisation (multi-tenant)
- `company` - Société (multi-tenant)

---

### **2. Service AuditLogService**

**Fichier :** `src/Service/AuditLogService.php`

**Méthodes principales :**

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `log()` | Enregistrement générique | - |
| `logCreate()` | Log une création | Nouveau bien créé |
| `logUpdate()` | Log une modification | Locataire modifié |
| `logDelete()` | Log une suppression | Document supprimé |
| `logView()` | Log une consultation | Bail consulté |
| `logLogin()` | Log une connexion | Utilisateur connecté |
| `logLogout()` | Log une déconnexion | Utilisateur déconnecté |
| `logDownload()` | Log un téléchargement | PDF téléchargé |
| `logExport()` | Log un export | Export Excel |
| `logEmailSent()` | Log envoi email | Email quittance envoyé |
| `logSmsSent()` | Log envoi SMS | SMS rappel envoyé |

---

### **3. Contrôleur Admin**

**Fichier :** `src/Controller/Admin/AuditLogController.php`

**Routes :**

| Route | Nom | Description |
|-------|-----|-------------|
| `GET /admin/audit` | `app_admin_audit_index` | Liste avec filtres |
| `GET /admin/audit/{id}` | `app_admin_audit_show` | Détail d'un log |
| `GET /admin/audit/entity/{type}/{id}` | `app_admin_audit_entity` | Historique d'une entité |
| `GET /admin/audit/statistiques` | `app_admin_audit_stats` | Statistiques |
| `POST /admin/audit/nettoyage` | `app_admin_audit_cleanup` | Nettoyage |

---

### **4. EventSubscriber**

**Fichier :** `src/EventSubscriber/AuditLogSubscriber.php`

**Événements tracés automatiquement :**
- ✅ Connexions réussies
- ✅ Déconnexions

---

## 🚀 Utilisation

### **Enregistrer une Action Manuellement**

Dans n'importe quel contrôleur ou service :

```php
use App\Service\AuditLogService;

class PropertyController extends AbstractController
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {
    }

    public function create(Request $request): Response
    {
        $property = new Property();
        // ... configuration de la propriété
        
        $this->entityManager->persist($property);
        $this->entityManager->flush();

        // Log la création
        $this->auditLogService->logCreate(
            entityType: 'Property',
            entityId: $property->getId(),
            description: "Création du bien {$property->getAddress()}",
            data: [
                'address' => $property->getAddress(),
                'type' => $property->getType(),
                'price' => $property->getPrice()
            ]
        );

        return $this->redirectToRoute('app_property_show', ['id' => $property->getId()]);
    }
}
```

### **Logger une Modification avec Changements**

```php
public function edit(Property $property, Request $request): Response
{
    // Sauvegarder les anciennes valeurs
    $oldValues = [
        'address' => $property->getAddress(),
        'price' => $property->getPrice()
    ];

    // Appliquer les modifications
    $property->setAddress($newAddress);
    $property->setPrice($newPrice);

    $this->entityManager->flush();

    // Logger avec anciennes et nouvelles valeurs
    $this->auditLogService->logUpdate(
        entityType: 'Property',
        entityId: $property->getId(),
        description: "Modification du bien {$property->getAddress()}",
        oldValues: $oldValues,
        newValues: [
            'address' => $property->getAddress(),
            'price' => $property->getPrice()
        ]
    );
}
```

### **Logger une Suppression**

```php
public function delete(Property $property): Response
{
    $propertyId = $property->getId();
    $propertyData = [
        'address' => $property->getAddress(),
        'type' => $property->getType()
    ];

    $this->entityManager->remove($property);
    $this->entityManager->flush();

    // Logger la suppression
    $this->auditLogService->logDelete(
        entityType: 'Property',
        entityId: $propertyId,
        description: "Suppression du bien {$propertyData['address']}",
        data: $propertyData
    );
}
```

### **Logger un Téléchargement**

```php
public function downloadDocument(Document $document): Response
{
    // Logger le téléchargement
    $this->auditLogService->logDownload(
        entityType: 'Document',
        entityId: $document->getId(),
        fileName: $document->getFileName()
    );

    return $this->file($document->getFilePath());
}
```

### **Logger un Export**

```php
public function exportTenants(): Response
{
    // Générer l'export...
    
    $this->auditLogService->logExport(
        entityType: 'Tenant',
        format: 'Excel',
        description: "Export de tous les locataires"
    );

    return new Response($excelContent);
}
```

---

## 📊 Visualisation

### **Page Principale** - `/admin/audit`

**Fonctionnalités :**
- ✅ Liste paginée des actions
- ✅ Filtres multiples (action, entité, dates)
- ✅ Affichage des changements (old vs new)
- ✅ Statistiques rapides
- ✅ Recherche avancée

**Filtres disponibles :**
- Type d'action (CREATE, UPDATE, DELETE, etc.)
- Type d'entité (Property, Tenant, etc.)
- Date début/fin
- Limite de résultats (50, 100, 250, 500)

### **Page Statistiques** - `/admin/audit/statistiques`

**Affiche :**
- 📊 Actions par type (graphique)
- 📈 Entités modifiées (graphique)
- 📉 Activité des 30 derniers jours (Chart.js)
- 👥 Utilisateurs les plus actifs
- 🧹 Outil de nettoyage

---

## 🎨 Types d'Actions Disponibles

| Action | Label | Badge | Icône | Usage |
|--------|-------|-------|-------|-------|
| `CREATE` | Création | success (vert) | bi-plus-circle | Création d'entité |
| `UPDATE` | Modification | warning (jaune) | bi-pencil | Modification |
| `DELETE` | Suppression | danger (rouge) | bi-trash | Suppression |
| `VIEW` | Consultation | info (bleu) | bi-eye | Consultation |
| `LOGIN` | Connexion | primary (bleu) | bi-box-arrow-in-right | Connexion |
| `LOGOUT` | Déconnexion | secondary (gris) | bi-box-arrow-right | Déconnexion |
| `DOWNLOAD` | Téléchargement | info (bleu) | bi-download | Téléchargement |
| `EXPORT` | Export | info (bleu) | bi-file-earmark-arrow-down | Export |
| `SEND_EMAIL` | Envoi email | primary (bleu) | bi-envelope | Email envoyé |
| `SEND_SMS` | Envoi SMS | primary (bleu) | bi-phone | SMS envoyé |

---

## 🔍 Recherche et Filtres

### **Exemple : Trouver toutes les modifications de propriétés**

1. Aller sur `/admin/audit`
2. Sélectionner Action = UPDATE
3. Sélectionner Type d'entité = Property
4. Cliquer sur "Filtrer"

### **Exemple : Voir l'historique d'un bien spécifique**

```php
// Depuis un template
<a href="{{ path('app_admin_audit_entity', {
    entityType: 'Property',
    entityId: property.id
}) }}">
    Voir l'historique
</a>
```

### **Exemple : Activité d'aujourd'hui**

```php
$auditLogRepository->findToday();
```

---

## 🧹 Nettoyage Automatique

### **Depuis l'Interface**

1. Aller sur `/admin/audit/statistiques`
2. Section "Nettoyage Automatique"
3. Choisir la période à conserver (30, 60, 90, 180, 365 jours)
4. Cliquer sur "Nettoyer"

### **Via Code**

```php
// Garder seulement les 90 derniers jours
$deleted = $auditLogService->cleanOldLogs(90);
```

### **Via Commande Symfony (Optionnel)**

Créer une commande pour nettoyage automatique :

```bash
php bin/console app:audit:cleanup --days=90
```

---

## 📋 Migration de Base de Données

Créer la table `audit_log` :

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

Ou utiliser le SQL fourni dans `migration_audit_log.sql`.

---

## 🔐 Sécurité & Conformité

### **RGPD**

- ✅ Traçabilité complète
- ✅ Identification de l'utilisateur
- ✅ Horodatage précis
- ✅ Possibilité de nettoyage
- ⚠️ Ne pas logger de données sensibles (mots de passe, etc.)

### **Protection**

Le service filtre automatiquement :
- ❌ Mots de passe
- ❌ Tokens
- ❌ Salt
- ❌ Champs sensibles

### **Accès**

Restreindre l'accès aux logs :

```php
// Dans security.yaml
access_control:
    - { path: ^/admin/audit, roles: ROLE_ADMIN }
```

---

## 📈 Bonnes Pratiques

### **✅ À Faire**

1. **Logger les actions importantes**
   - Créations / Modifications / Suppressions
   - Téléchargements de documents sensibles
   - Exports de données
   - Envois d'emails/SMS

2. **Être descriptif**
   ```php
   // ✅ BON
   $this->auditLogService->logCreate(
       'Property',
       $property->getId(),
       "Création du bien '{$property->getAddress()}' de type {$property->getType()}"
   );

   // ❌ MAUVAIS
   $this->auditLogService->logCreate('Property', $property->getId());
   ```

3. **Logger les changements**
   ```php
   $this->auditLogService->logUpdate(
       'Tenant',
       $tenant->getId(),
       "Modification du locataire",
       ['email' => 'old@email.com'],
       ['email' => 'new@email.com']
   );
   ```

### **❌ À Éviter**

1. **Ne pas logger les mots de passe**
2. **Ne pas logger trop de détails (GDPR)**
3. **Ne pas logger dans des boucles intensives**
4. **Ne pas oublier le nettoyage automatique**

---

## 🔧 Configuration Optionnelle

### **Désactiver l'auto-logging**

Si vous voulez désactiver le logging automatique des connexions :

```yaml
# config/services.yaml
services:
    App\EventSubscriber\AuditLogSubscriber:
        tags: []  # Retirer les tags pour désactiver
```

### **Personnaliser la Rétention**

```php
// Dans un service ou commande
$this->auditLogService->cleanOldLogs(30); // 30 jours
```

---

## 📊 Exemples Complets

### **Exemple 1 : Logger dans un Formulaire**

```php
#[Route('/property/{id}/edit', name: 'app_property_edit')]
public function edit(
    Property $property,
    Request $request,
    EntityManagerInterface $em,
    AuditLogService $auditLog
): Response {
    $form = $this->createForm(PropertyType::class, $property);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $changes = $auditLog->extractChanges(
            $originalData, // Sauvegarder avant
            [
                'address' => $property->getAddress(),
                'price' => $property->getPrice()
            ]
        );

        $em->flush();

        if (!empty($changes)) {
            $auditLog->log(
                'UPDATE',
                'Property',
                $property->getId(),
                "Modification du bien",
                array_column($changes, 'old'),
                array_column($changes, 'new')
            );
        }

        return $this->redirectToRoute('app_property_show', ['id' => $property->getId()]);
    }

    return $this->render('property/edit.html.twig', ['form' => $form]);
}
```

### **Exemple 2 : Logger Envoi Email**

```php
public function sendRentReceipt(Payment $payment, MailerInterface $mailer, AuditLogService $auditLog): void
{
    $email = (new Email())
        ->to($payment->getTenant()->getEmail())
        ->subject('Quittance de loyer')
        ->html($this->renderView('emails/rent_receipt.html.twig', ['payment' => $payment]));

    $mailer->send($email);

    // Logger l'envoi
    $auditLog->logEmailSent(
        $payment->getTenant()->getEmail(),
        'Quittance de loyer - ' . $payment->getDueDate()->format('m/Y')
    );
}
```

---

## ✅ Checklist d'Installation

- [x] Entité `AuditLog` créée
- [x] Repository créé
- [x] Service `AuditLogService` créé
- [x] Contrôleur créé
- [x] Templates créés
- [x] EventSubscriber créé
- [ ] Migration exécutée (`php bin/console doctrine:migrations:migrate`)
- [ ] Route ajoutée au menu admin
- [ ] Tests effectués

---

## 🎓 Résumé

Le système d'audit log offre :
- ✅ **Traçabilité complète** des actions
- ✅ **Interface visuelle** intuitive
- ✅ **Filtres avancés** pour recherche
- ✅ **Statistiques** détaillées
- ✅ **Auto-logging** des connexions
- ✅ **Nettoyage automatique** configurable
- ✅ **Conformité RGPD**

**Accès :** `/admin/audit`

**Impact :** Sécurité et conformité renforcées ! 📜✨

