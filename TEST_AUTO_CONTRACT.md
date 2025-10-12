# 🧪 Guide de test - Génération automatique de contrats

## ✅ PRÉREQUIS

Avant de tester, assurez-vous que :
- [x] Les migrations sont appliquées
- [x] Le dossier `public/uploads/documents` existe
- [x] Vous êtes connecté en tant qu'admin
- [x] Il existe au moins un bail actif

---

## 🎯 TEST COMPLET PAS À PAS

### Étape 1 : Créer un dossier pour les documents (si nécessaire)

**Sur Windows** :
```bash
mkdir public\uploads
mkdir public\uploads\documents
```

**Sur Linux/Mac** :
```bash
mkdir -p public/uploads/documents
chmod 777 public/uploads/documents
```

---

### Étape 2 : Créer ou vérifier un bail

1. Accédez à `/contrats`
2. Assurez-vous d'avoir un bail avec :
   - Statut : "Actif"
   - Montant de caution défini
   - Locataire assigné
   - Propriété assignée

**OU créez un nouveau bail** :
```
/contrats/nouveau
→ Sélectionner un locataire
→ Sélectionner une propriété
→ Date début : Aujourd'hui
→ Date fin : Dans 1 an
→ Loyer : 1200
→ Charges : 100
→ Caution : 1200  ← IMPORTANT !
→ Créer
```

---

### Étape 3 : Créer un paiement de caution

1. Accédez à `/mes-paiements/nouveau`
2. Remplissez :
   - **Bail** : Sélectionner le bail créé
   - **Type** : **"Dépôt de garantie"** ← CRUCIAL !
   - **Montant** : 1200 (le montant de la caution)
   - **Date d'échéance** : Aujourd'hui
   - **Statut** : "En attente" (par défaut)
3. Cliquez sur "Créer le paiement"

---

### Étape 4 : Marquer le paiement comme payé

1. Vous êtes redirigé vers la page du paiement
2. Cherchez le bouton **"Marquer comme payé"** ou formulaire similaire
3. Remplissez :
   - **Date de paiement** : Aujourd'hui
   - **Mode de paiement** : Virement (ou autre)
   - **Référence** : TEST123 (optionnel)
4. **Cliquez sur "Valider" ou "Enregistrer"**

---

### ✨ RÉSULTAT ATTENDU

Après avoir cliqué, vous devriez voir **2 messages** :

```
✅ Le paiement a été marqué comme payé.

📄 Le contrat de bail a été généré automatiquement 
   et est disponible dans les documents !
```

---

### Étape 5 : Vérifier que le contrat a été généré

#### Option A : Via les documents
1. Accédez à `/mes-documents`
2. Cherchez la catégorie "Bail"
3. Vous devriez voir : **"Contrat de bail - X"**

#### Option B : Via le bail
1. Retournez à `/contrats/{id}` (fiche du bail)
2. Section "Documents" ou similaire
3. Le contrat devrait être listé

#### Option C : Vérifier sur le disque
```bash
# Vérifier que le fichier existe
dir public\uploads\documents\Contrat_Bail*.pdf

# Sur Linux/Mac
ls -la public/uploads/documents/Contrat_Bail*.pdf
```

#### Option D : Vérifier en base de données
```sql
SELECT * FROM document WHERE type = 'Bail' ORDER BY id DESC LIMIT 1;
```

---

## 🐛 SI ÇA NE FONCTIONNE PAS

### Diagnostic 1 : Vérifier le type de paiement

Le type du paiement DOIT être **exactement** :
- "Dépôt de garantie" (recommandé)
- OU "Caution"

**Vérifiez** :
```php
// Dans PaymentController, ligne 122
if ($payment->getType() === 'Dépôt de garantie' || $payment->getType() === 'Caution')
```

### Diagnostic 2 : Vérifier les messages d'erreur

Si vous voyez :
```
⚠️ Le paiement est enregistré mais le contrat n'a pas pu être généré : [MESSAGE]
```

L'erreur est affichée ! Lisez le message pour comprendre le problème.

### Diagnostic 3 : Vérifier les logs

```bash
# Windows
type var\log\dev.log | findstr /i "contract"

# Linux/Mac
tail -f var/log/dev.log | grep -i contract
```

### Diagnostic 4 : Tester manuellement la génération

Créez un fichier de test temporaire :

```php
// test-contract.php à la racine
<?php
require_once __DIR__.'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$contractService = $container->get('App\Service\ContractGenerationService');
$entityManager = $container->get('doctrine')->getManager();

// Récupérer un bail
$lease = $entityManager->getRepository('App\Entity\Lease')->find(1); // Remplacer 1 par l'ID de votre bail

if ($lease) {
    try {
        $document = $contractService->generateContractManually($lease);
        echo "✅ Contrat généré : " . $document->getFilePath() . "\n";
    } catch (\Exception $e) {
        echo "❌ Erreur : " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Bail introuvable\n";
}
```

Exécuter :
```bash
php test-contract.php
```

---

## 🔧 SOLUTIONS AUX PROBLÈMES COURANTS

### Problème 1 : "Cannot rename" ou erreur de cache
**Solution** :
```bash
# Supprimer manuellement le cache
rmdir /s var\cache\dev     # Windows
rm -rf var/cache/dev       # Linux/Mac

# Recréer
php bin/console cache:warmup
```

### Problème 2 : Dossier documents n'existe pas
**Solution** :
```bash
mkdir public\uploads\documents
# Donner les permissions
icacls public\uploads\documents /grant Everyone:F  # Windows
chmod 777 public/uploads/documents                 # Linux/Mac
```

### Problème 3 : Le service n'est pas injecté
**Solution** : Vérifier que `services.yaml` contient :
```yaml
App\Service\ContractGenerationService:
    arguments:
        $documentsDirectory: '%documents_directory%'
```

### Problème 4 : "Payment not found"
**Solution** : Charger les fixtures
```bash
php bin/console doctrine:fixtures:load --no-interaction
```

---

## 📝 ALTERNATIVE : Génération manuelle

Si la génération automatique ne fonctionne pas encore, utilisez la génération manuelle :

### Depuis la page d'un bail

1. Accédez à `/contrats/{id}`
2. Cherchez le bouton **"Générer et enregistrer le contrat"**
3. Cliquez
4. Le contrat sera généré et sauvegardé

**Route** : `/contrats/{id}/generer-contrat-document` (POST)

---

## ✅ CHECK-LIST DE VALIDATION

Après le test, vérifiez que :

- [ ] Le paiement est marqué "Payé"
- [ ] Message "Le contrat a été généré" affiché
- [ ] Fichier PDF créé dans `public/uploads/documents/`
- [ ] Entrée dans la table `document`
- [ ] Document visible dans "Mes documents"
- [ ] Document téléchargeable
- [ ] PDF contient toutes les informations

---

## 🎯 EXEMPLE DE COMMANDE RAPIDE

Pour tester rapidement sans passer par l'interface :

```bash
# Créer un bail de test
php bin/console doctrine:fixtures:load --append

# Générer un contrat manuellement via console (à créer si besoin)
# OU utiliser l'interface web
```

---

## 🎉 RÉSULTAT ATTENDU

### Le contrat PDF devrait contenir :

✅ **En-tête** : MYLOCCA (ou votre nom d'app)  
✅ **Entreprise** : Vos infos depuis `/admin/parametres/application`  
✅ **Bailleur** : Infos du propriétaire  
✅ **Locataire** : Prénom, nom, date naissance, email, profession  
✅ **Bien** : Adresse complète, type, surface, pièces  
✅ **Dates** : Début, fin, durée calculée  
✅ **Finances** : Loyer avec DEVISE ACTIVE, charges, caution  
✅ **Clauses** : Obligations, résolution  
✅ **Signatures** : Espaces prévus  

---

## 📞 SI TOUJOURS UN PROBLÈME

Vérifiez dans cet ordre :

1. ✅ `config/services.yaml` contient la config de ContractGenerationService
2. ✅ Dossier `public/uploads/documents` existe et accessible
3. ✅ Type de paiement = "Dépôt de garantie" ou "Caution"
4. ✅ Paiement marqué comme "Payé"
5. ✅ Cache vidé
6. ✅ Vérifier `var/log/dev.log` pour erreurs

**Essayez maintenant et vous devriez voir la génération automatique fonctionner !** 🚀

