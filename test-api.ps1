# Script PowerShell pour tester l'API Mobile MyLocca
# Usage: .\test-api.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "    Test API Mobile MyLocca           " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$baseUrl = "http://localhost:8000"
$apiUrl = "$baseUrl/api/tenant"

# Fonctions d'affichage avec couleurs
function Write-Success {
    param([string]$message)
    Write-Host "✓ $message" -ForegroundColor Green
}

function Write-ErrorMessage {
    param([string]$message)
    Write-Host "✗ $message" -ForegroundColor Red
}

function Write-Info {
    param([string]$message)
    Write-Host "ℹ $message" -ForegroundColor Cyan
}

# Test 1: Vérifier que le serveur Symfony est démarré
Write-Info "Test 1: Vérification du serveur Symfony..."
try {
    $response = Invoke-WebRequest -Uri $baseUrl -Method GET -TimeoutSec 5
    Write-Success "Serveur Symfony accessible"
} catch {
    Write-ErrorMessage "Serveur Symfony non accessible. Lancez: symfony server:start"
    exit 1
}

# Test 2: Login
Write-Info "Test 2: Test de connexion..."
$loginData = @{
    email = "test@example.com"
    password = "password"
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "$apiUrl/login" `
        -Method POST `
        -ContentType "application/json" `
        -Body $loginData

    if ($loginResponse.success) {
        Write-Success "Connexion réussie"
        $userEmail = $loginResponse.user.email
        Write-Host "  Email: $($loginResponse.user.email)" -ForegroundColor Gray
        Write-Host "  Token: $($loginResponse.token.Substring(0, 20))..." -ForegroundColor Gray
    } else {
        Write-ErrorMessage "Échec de connexion: $($loginResponse.message)"
        exit 1
    }
} catch {
    Write-ErrorMessage "Erreur lors de la connexion"
    Write-Host "  Message: $($_.Exception.Message)" -ForegroundColor Gray
    Write-Info "Créez d'abord un utilisateur de test dans la base de données"
    exit 1
}

# Test 3: Dashboard
Write-Info "Test 3: Récupération du tableau de bord..."
try {
    $headers = @{
        "X-User-Email" = $userEmail
    }

    $dashboardResponse = Invoke-RestMethod -Uri "$apiUrl/dashboard" `
        -Method GET `
        -Headers $headers

    if ($dashboardResponse.success) {
        Write-Success "Tableau de bord récupéré"
        $tenant = $dashboardResponse.tenant
        Write-Host "  Locataire: $($tenant.firstName) $($tenant.lastName)" -ForegroundColor Gray
        Write-Host "  Compte N°: $($tenant.accountNumber)" -ForegroundColor Gray

        if ($dashboardResponse.property) {
            $property = $dashboardResponse.property
            Write-Host "  Bien: $($property.fullAddress)" -ForegroundColor Gray
            Write-Host "  Pièces: $($property.rooms)" -ForegroundColor Gray
        }

        $balances = $dashboardResponse.balances
        Write-Host "  Solde: $($balances.soldAt) €" -ForegroundColor Gray
        Write-Host "  À payer: $($balances.toPay) €" -ForegroundColor Gray
    } else {
        Write-ErrorMessage "Échec récupération dashboard"
    }
} catch {
    Write-ErrorMessage "Erreur lors de la récupération du dashboard"
    Write-Host "  Message: $($_.Exception.Message)" -ForegroundColor Gray
}

# Test 4: Profil
Write-Info "Test 4: Récupération du profil..."
try {
    $profileResponse = Invoke-RestMethod -Uri "$apiUrl/profile" `
        -Method GET `
        -Headers $headers

    if ($profileResponse.success) {
        Write-Success "Profil récupéré"
        $profile = $profileResponse.profile
        Write-Host "  Nom: $($profile.firstName) $($profile.lastName)" -ForegroundColor Gray
        Write-Host "  Email: $($profile.email)" -ForegroundColor Gray
        Write-Host "  Téléphone: $($profile.phone)" -ForegroundColor Gray
    } else {
        Write-ErrorMessage "Échec récupération profil"
    }
} catch {
    Write-ErrorMessage "Erreur lors de la récupération du profil"
    Write-Host "  Message: $($_.Exception.Message)" -ForegroundColor Gray
}

# Test 5: Paiements
Write-Info "Test 5: Récupération des paiements..."
try {
    $paymentsResponse = Invoke-RestMethod -Uri "$apiUrl/payments" `
        -Method GET `
        -Headers $headers

    if ($paymentsResponse.success) {
        Write-Success "Paiements récupérés"
        $stats = $paymentsResponse.statistics
        Write-Host "  Nombre: $($paymentsResponse.count)" -ForegroundColor Gray
        Write-Host "  Total: $($stats.total) €" -ForegroundColor Gray
        Write-Host "  Payé: $($stats.paid) €" -ForegroundColor Gray
        Write-Host "  En attente: $($stats.pending) €" -ForegroundColor Gray
    } else {
        Write-ErrorMessage "Échec récupération paiements"
    }
} catch {
    Write-ErrorMessage "Erreur lors de la récupération des paiements"
    Write-Host "  Message: $($_.Exception.Message)" -ForegroundColor Gray
}

# Test 6: Demandes
Write-Info "Test 6: Récupération des demandes..."
try {
    $requestsResponse = Invoke-RestMethod -Uri "$apiUrl/requests" `
        -Method GET `
        -Headers $headers

    if ($requestsResponse.success) {
        Write-Success "Demandes récupérées"
        Write-Host "  Nombre: $($requestsResponse.count)" -ForegroundColor Gray
        $stats = $requestsResponse.statistics
        Write-Host "  En attente: $($stats.pending)" -ForegroundColor Gray
        Write-Host "  En cours: $($stats.inProgress)" -ForegroundColor Gray
        Write-Host "  Terminées: $($stats.completed)" -ForegroundColor Gray
    } else {
        Write-ErrorMessage "Échec récupération demandes"
    }
} catch {
    Write-ErrorMessage "Erreur lors de la récupération des demandes"
    Write-Host "  Message: $($_.Exception.Message)" -ForegroundColor Gray
}

# Test 7: Documents
Write-Info "Test 7: Récupération des documents..."
try {
    $documentsResponse = Invoke-RestMethod -Uri "$apiUrl/documents" `
        -Method GET `
        -Headers $headers

    if ($documentsResponse.success) {
        Write-Success "Documents récupérés"
        Write-Host "  Nombre: $($documentsResponse.count)" -ForegroundColor Gray
    } else {
        Write-ErrorMessage "Échec récupération documents"
    }
} catch {
    Write-ErrorMessage "Erreur lors de la récupération des documents"
    Write-Host "  Message: $($_.Exception.Message)" -ForegroundColor Gray
}

# Test 8: Bien immobilier
Write-Info "Test 8: Récupération du bien immobilier..."
try {
    $propertyResponse = Invoke-RestMethod -Uri "$apiUrl/property" `
        -Method GET `
        -Headers $headers

    if ($propertyResponse.success) {
        if ($propertyResponse.property) {
            Write-Success "Bien immobilier récupéré"
            $property = $propertyResponse.property
            Write-Host "  Référence: $($property.reference)" -ForegroundColor Gray
            Write-Host "  Nom: $($property.name)" -ForegroundColor Gray
            Write-Host "  Adresse: $($property.fullAddress)" -ForegroundColor Gray
            Write-Host "  Type: $($property.type)" -ForegroundColor Gray
            Write-Host "  Surface: $($property.surface) m²" -ForegroundColor Gray
        } else {
            Write-Success "Aucun bail actif trouvé (normal si pas de données)"
        }
    } else {
        Write-ErrorMessage "Échec récupération bien immobilier"
    }
} catch {
    Write-ErrorMessage "Erreur lors de la récupération du bien immobilier"
    Write-Host "  Message: $($_.Exception.Message)" -ForegroundColor Gray
}

# Test 9: Comptabilité
Write-Info "Test 9: Récupération de la comptabilité..."
try {
    $accountingResponse = Invoke-RestMethod -Uri "$apiUrl/accounting" `
        -Method GET `
        -Headers $headers

    if ($accountingResponse.success) {
        Write-Success "Comptabilité récupérée"
        $accounting = $accountingResponse.accounting
        Write-Host "  Balance: $($accounting.balance) €" -ForegroundColor Gray
        Write-Host "  Total payé: $($accounting.totalPaid) €" -ForegroundColor Gray
        Write-Host "  Total dû: $($accounting.totalDue) €" -ForegroundColor Gray
        Write-Host "  À payer: $($accounting.toPay) €" -ForegroundColor Gray
    } else {
        Write-ErrorMessage "Échec récupération comptabilité"
    }
} catch {
    Write-ErrorMessage "Erreur lors de la récupération de la comptabilité"
    Write-Host "  Message: $($_.Exception.Message)" -ForegroundColor Gray
}

# Test 10: Création d'une demande
Write-Info "Test 10: Création d'une demande d'intervention..."
$requestData = @{
    title = "Test - Fuite d'eau"
    category = "Plomberie"
    description = "Test automatique via API"
    priority = "Normale"
} | ConvertTo-Json

try {
    $createResponse = Invoke-RestMethod -Uri "$apiUrl/requests" `
        -Method POST `
        -Headers $headers `
        -ContentType "application/json" `
        -Body $requestData

    if ($createResponse.success) {
        Write-Success "Demande créée avec succès"
        Write-Host "  ID: $($createResponse.request.id)" -ForegroundColor Gray
        Write-Host "  Référence: $($createResponse.request.reference)" -ForegroundColor Gray
        Write-Host "  Statut: $($createResponse.request.status)" -ForegroundColor Gray
    } else {
        Write-ErrorMessage "Échec création demande: $($createResponse.message)"
    }
} catch {
    Write-ErrorMessage "Erreur lors de la création de la demande"
    Write-Host "  Message: $($_.Exception.Message)" -ForegroundColor Gray
    Write-Info "Normal si aucun bail actif n'existe pour ce locataire"
}

# Résumé
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "    Résumé des tests                   " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Success "Tests API terminés !"
Write-Host ""
Write-Info "Notes importantes:"
Write-Host "  - Assurez-vous d'avoir créé un utilisateur de test" -ForegroundColor Yellow
Write-Host "  - Email: test@example.com" -ForegroundColor Yellow
Write-Host "  - Mot de passe: password" -ForegroundColor Yellow
Write-Host "  - Rôle: ROLE_TENANT" -ForegroundColor Yellow
Write-Host ""
Write-Info "Pour créer un utilisateur de test:"
Write-Host "  php bin/console security:hash-password" -ForegroundColor Yellow
Write-Host "  Puis insérer dans la base de données" -ForegroundColor Yellow
Write-Host ""
Write-Info "Documentation complète:"
Write-Host "  - docs/API_MOBILE_GUIDE.md" -ForegroundColor Yellow
Write-Host "  - docs/FLUTTER_APP_GUIDE.md" -ForegroundColor Yellow
Write-Host "  - docs/API_MOBILE_COMPLETE.md" -ForegroundColor Yellow
Write-Host ""
