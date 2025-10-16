# 🔧 Guide d'Intégration - Audit Log dans vos Contrôleurs

## 🎯 Objectif

Ce guide vous montre comment intégrer le système d'audit log dans vos contrôleurs existants pour tracer toutes les actions importantes.

---

## 📝 Étape par Étape

### **1. Injecter le Service**

Dans votre contrôleur, ajoutez le service dans le constructeur :

```php
use App\Service\AuditLogService;

class PropertyController extends AbstractController
{
    public function __construct(
        private AuditLogService $auditLog  // ← Ajoutez ceci
    ) {
    }
    
    // Ou directement dans la méthode :
    public function create(Request $request, AuditLogService $auditLog): Response
    {
        // ...
    }
}
```

---

### **2. Logger les Créations**

**Dans DocumentController (exemple déjà présent) :**

```php
#[Route('/nouveau', name: 'app_document_new')]
public function new(
    Request $request, 
    EntityManagerInterface $em,
    AuditLogService $auditLog
): Response {
    $document = new Document();
    // ... gestion formulaire
    
    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($document);
        $em->flush();

        // ✅ Logger la création
        $auditLog->logCreate(
            'Document',
            $document->getId(),
            "Création du document '{$document->getName()}'",
            [
                'name' => $document->getName(),
                'type' => $document->getType(),
                'fileName' => $document->getFileName()
            ]
        );

        $this->addFlash('success', 'Document créé.');
        return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
    }
}
```

---

### **3. Logger les Modifications**

**Dans DocumentController :**

```php
#[Route('/{id}/modifier', name: 'app_document_edit')]
public function edit(
    Document $document, 
    Request $request,
    EntityManagerInterface $em,
    AuditLogService $auditLog
): Response {
    // Sauvegarder les valeurs avant modification
    $oldValues = [
        'name' => $document->getName(),
        'type' => $document->getType(),
        'description' => $document->getDescription()
    ];

    $form = $this->createForm(DocumentType::class, $document);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $document->setUpdatedAt(new \DateTime());
        $em->flush();

        // ✅ Logger la modification
        $auditLog->logUpdate(
            'Document',
            $document->getId(),
            "Modification du document '{$document->getName()}'",
            $oldValues,
            [
                'name' => $document->getName(),
                'type' => $document->getType(),
                'description' => $document->getDescription()
            ]
        );

        $this->addFlash('success', 'Document modifié.');
        return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
    }
}
```

---

### **4. Logger les Suppressions**

**Dans DocumentController :**

```php
#[Route('/{id}/supprimer', name: 'app_document_delete')]
public function delete(
    Document $document,
    Request $request,
    EntityManagerInterface $em,
    AuditLogService $auditLog
): Response {
    if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->getPayload()->getString('_token'))) {
        // Sauvegarder les infos avant suppression
        $documentData = [
            'name' => $document->getName(),
            'type' => $document->getType(),
            'fileName' => $document->getFileName()
        ];
        $documentId = $document->getId();

        // Supprimer le fichier physique
        $filePath = $this->getParameter('documents_directory') . '/' . $document->getFileName();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $em->remove($document);
        $em->flush();

        // ✅ Logger la suppression
        $auditLog->logDelete(
            'Document',
            $documentId,
            "Suppression du document '{$documentData['name']}'",
            $documentData
        );

        $this->addFlash('success', 'Document supprimé.');
    }

    return $this->redirectToRoute('app_document_index');
}
```

---

### **5. Logger les Téléchargements**

**Dans DocumentController :**

```php
#[Route('/{id}/telecharger', name: 'app_document_download')]
public function download(Document $document, AuditLogService $auditLog): Response
{
    $filePath = $this->getParameter('documents_directory') . '/' . $document->getFileName();

    if (!file_exists($filePath)) {
        throw $this->createNotFoundException('Le fichier n\'existe pas.');
    }

    // ✅ Logger le téléchargement
    $auditLog->logDownload(
        'Document',
        $document->getId(),
        $document->getFileName()
    );

    return $this->file($filePath, $document->getOriginalFileName());
}
```

---

## 📋 Check-List d'Intégration

### **Pour Chaque Contrôleur Important**

- [ ] **PropertyController**
  - [ ] logCreate() dans new()
  - [ ] logUpdate() dans edit()
  - [ ] logDelete() dans delete()

- [ ] **TenantController**
  - [ ] logCreate() dans new()
  - [ ] logUpdate() dans edit()
  - [ ] logDelete() dans delete()

- [ ] **LeaseController**
  - [ ] logCreate() dans new()
  - [ ] logUpdate() dans edit()
  - [ ] logDelete() dans delete()
  - [ ] logView() dans show() (optionnel)

- [ ] **PaymentController**
  - [ ] logCreate() dans new()
  - [ ] logUpdate() dans edit()
  - [ ] logDelete() dans delete()

- [ ] **DocumentController** ✅ (Exemples ci-dessus)
  - [ ] logCreate() dans new()
  - [ ] logUpdate() dans edit()
  - [ ] logDelete() dans delete()
  - [ ] logDownload() dans download()

- [ ] **SettingsController**
  - [ ] logUpdate() dans update settings
  - [ ] logCreate/Update/Delete() dans currency management

- [ ] **UserController**
  - [ ] logCreate() dans new()
  - [ ] logUpdate() dans edit()
  - [ ] logDelete() dans delete()
  - [ ] Log toggle active/inactive

---

## 🎯 Exemple Complet : Intégration dans CurrencyController

```php
<?php

namespace App\Controller\Admin;

use App\Entity\Currency;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/parametres/devises')]
class SettingsController extends AbstractController
{
    public function __construct(
        private AuditLogService $auditLog
    ) {
    }

    #[Route('/{id}/modifier', name: 'app_admin_currency_edit')]
    public function editCurrency(
        Currency $currency,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Sauvegarder avant modification
        $oldValues = [
            'code' => $currency->getCode(),
            'name' => $currency->getName(),
            'symbol' => $currency->getSymbol(),
            'exchangeRate' => $currency->getExchangeRate(),
            'isActive' => $currency->isActive(),
            'isDefault' => $currency->isDefault()
        ];

        $form = $this->createForm(CurrencyType::class, $currency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            // Logger les changements
            $this->auditLog->logUpdate(
                'Currency',
                $currency->getId(),
                "Modification de la devise {$currency->getCode()}",
                $oldValues,
                [
                    'code' => $currency->getCode(),
                    'name' => $currency->getName(),
                    'symbol' => $currency->getSymbol(),
                    'exchangeRate' => $currency->getExchangeRate(),
                    'isActive' => $currency->isActive(),
                    'isDefault' => $currency->isDefault()
                ]
            );

            $this->addFlash('success', 'Devise modifiée.');
            return $this->redirectToRoute('app_admin_currencies');
        }

        return $this->render('admin/settings/currency_edit.html.twig', [
            'currency' => $currency,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_currency_delete')]
    public function deleteCurrency(
        Currency $currency,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$currency->getId(), $request->request->get('_token'))) {
            if ($currency->isDefault()) {
                $this->addFlash('error', 'Impossible de supprimer la devise par défaut.');
                return $this->redirectToRoute('app_admin_currencies');
            }

            // Sauvegarder avant suppression
            $currencyData = [
                'code' => $currency->getCode(),
                'name' => $currency->getName(),
                'symbol' => $currency->getSymbol()
            ];
            $currencyId = $currency->getId();

            try {
                $em->remove($currency);
                $em->flush();

                // Logger la suppression
                $this->auditLog->logDelete(
                    'Currency',
                    $currencyId,
                    "Suppression de la devise {$currencyData['code']}",
                    $currencyData
                );

                $this->addFlash('success', "Devise {$currencyData['code']} supprimée.");
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer cette devise.');
            }
        }

        return $this->redirectToRoute('app_admin_currencies');
    }
}
```

---

## 🚀 Démarrage Rapide

### **Ajouter au DocumentController (déjà ouvert)**

Ajoutez simplement dans votre constructeur ou méthodes :

```php
use App\Service\AuditLogService;

// Dans new()
$this->auditLog->logCreate('Document', $document->getId(), "Nouveau document");

// Dans edit()
$this->auditLog->logUpdate('Document', $document->getId(), "Modification");

// Dans delete()
$this->auditLog->logDelete('Document', $documentId, "Suppression");

// Dans download()
$this->auditLog->logDownload('Document', $document->getId(), $document->getFileName());
```

---

## ✅ Avantages du Système

1. **Simple** - Une ligne de code par action
2. **Non-intrusif** - N'affecte pas le code existant
3. **Flexible** - Logger ce que vous voulez
4. **Performant** - Index optimisés
5. **Visuel** - Interface claire pour consultation
6. **Conforme** - RGPD ready

---

## 🎓 Résumé

Pour intégrer l'audit log :
1. ✅ Injectez `AuditLogService`
2. ✅ Appelez `logCreate/Update/Delete()` après actions
3. ✅ Sauvegardez old/new values pour UPDATE
4. ✅ Fournissez descriptions claires

**C'est tout ! Simple et efficace** ! 📜✨

