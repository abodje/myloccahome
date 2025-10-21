# Script PowerShell pour démarrer le consumer Messenger pour LOKAPRO
# Ce script redémarre automatiquement le consumer s'il s'arrête

param(
    [string]$Action = "start"
)

# Configuration
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$LogFile = Join-Path $ScriptDir "var\log\consumer.log"
$PidFile = Join-Path $ScriptDir "var\consumer.pid"
$ConsumerCommand = "php bin/console messenger:consume async --time-limit=3600 --memory-limit=256 --sleep=5"

# Fonction de logging
function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] $Message"

    switch ($Level) {
        "SUCCESS" { Write-Host $logMessage -ForegroundColor Green }
        "WARNING" { Write-Host $logMessage -ForegroundColor Yellow }
        "ERROR" { Write-Host $logMessage -ForegroundColor Red }
        default { Write-Host $logMessage -ForegroundColor Blue }
    }

    # Créer le répertoire de logs s'il n'existe pas
    $logDir = Split-Path -Parent $LogFile
    if (!(Test-Path $logDir)) {
        New-Item -ItemType Directory -Path $logDir -Force | Out-Null
    }

    Add-Content -Path $LogFile -Value $logMessage
}

# Fonction pour vérifier si le consumer est en cours d'exécution
function Test-ConsumerRunning {
    if (Test-Path $PidFile) {
        $pid = Get-Content $PidFile
        try {
            $process = Get-Process -Id $pid -ErrorAction Stop
            return $true
        }
        catch {
            Remove-Item $PidFile -Force -ErrorAction SilentlyContinue
            return $false
        }
    }
    return $false
}

# Fonction pour arrêter le consumer
function Stop-Consumer {
    if (Test-Path $PidFile) {
        $pid = Get-Content $PidFile
        Write-Log "Arrêt du consumer (PID: $pid)..."

        try {
            $process = Get-Process -Id $pid -ErrorAction Stop
            $process.Kill()
            Start-Sleep -Seconds 2

            # Vérifier si le processus est toujours en cours
            try {
                Get-Process -Id $pid -ErrorAction Stop | Out-Null
                Write-Log "Le consumer ne s'est pas arrêté, forçage de l'arrêt..." "WARNING"
                $process.Kill($true)
            }
            catch {
                # Le processus s'est arrêté
            }

            Remove-Item $PidFile -Force -ErrorAction SilentlyContinue
            Write-Log "Consumer arrêté" "SUCCESS"
        }
        catch {
            Write-Log "Erreur lors de l'arrêt du consumer: $($_.Exception.Message)" "ERROR"
        }
    }
}

# Fonction pour démarrer le consumer
function Start-Consumer {
    Write-Log "Démarrage du consumer Messenger..."

    # Vérifier que nous sommes dans le bon répertoire
    if (!(Test-Path "bin\console")) {
        Write-Log "Le fichier bin\console n'existe pas. Assurez-vous d'être dans le répertoire racine du projet." "ERROR"
        return $false
    }

    # Vérifier que la base de données est accessible
    try {
        $null = php bin/console doctrine:query:sql "SELECT 1" 2>$null
        if ($LASTEXITCODE -ne 0) {
            Write-Log "Impossible de se connecter à la base de données. Vérifiez la configuration." "ERROR"
            return $false
        }
    }
    catch {
        Write-Log "Erreur lors de la vérification de la base de données: $($_.Exception.Message)" "ERROR"
        return $false
    }

    # Démarrer le consumer
    Write-Log "Exécution de la commande: $ConsumerCommand"

    try {
        $process = Start-Process -FilePath "php" -ArgumentList "bin/console", "messenger:consume", "async", "--time-limit=3600", "--memory-limit=256", "--sleep=5" -PassThru -RedirectStandardOutput $LogFile -RedirectStandardError $LogFile -WindowStyle Hidden

        # Sauvegarder le PID
        $process.Id | Out-File -FilePath $PidFile -Encoding ASCII

        # Attendre un peu pour vérifier que le processus démarre correctement
        Start-Sleep -Seconds 3

        if (!$process.HasExited) {
            Write-Log "Consumer démarré avec succès (PID: $($process.Id))" "SUCCESS"
            return $true
        }
        else {
            Write-Log "Échec du démarrage du consumer" "ERROR"
            Remove-Item $PidFile -Force -ErrorAction SilentlyContinue
            return $false
        }
    }
    catch {
        Write-Log "Erreur lors du démarrage du consumer: $($_.Exception.Message)" "ERROR"
        return $false
    }
}

# Fonction pour surveiller le consumer
function Start-Monitoring {
    $restartCount = 0
    $maxRestarts = 10

    Write-Log "Surveillance du consumer démarrée..."

    while ($true) {
        if (!(Test-ConsumerRunning)) {
            $restartCount++

            if ($restartCount -gt $maxRestarts) {
                Write-Log "Nombre maximum de redémarrages atteint ($maxRestarts). Arrêt de la surveillance." "ERROR"
                break
            }

            Write-Log "Consumer arrêté détecté. Redémarrage #$restartCount..." "WARNING"

            if (Start-Consumer) {
                $restartCount = 0  # Reset le compteur si le redémarrage réussit
            }
            else {
                Write-Log "Échec du redémarrage #$restartCount" "ERROR"
            }
        }

        # Attendre 30 secondes avant la prochaine vérification
        Start-Sleep -Seconds 30
    }
}

# Fonction pour afficher l'aide
function Show-Help {
    Write-Host "Usage: .\start-consumer.ps1 [OPTIONS]" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Options:" -ForegroundColor Yellow
    Write-Host "  start     Démarrer le consumer (défaut)"
    Write-Host "  stop      Arrêter le consumer"
    Write-Host "  restart   Redémarrer le consumer"
    Write-Host "  status    Afficher le statut du consumer"
    Write-Host "  monitor   Démarrer la surveillance automatique"
    Write-Host "  logs      Afficher les logs du consumer"
    Write-Host "  help      Afficher cette aide"
    Write-Host ""
    Write-Host "Exemples:" -ForegroundColor Yellow
    Write-Host "  .\start-consumer.ps1 start          # Démarrer le consumer"
    Write-Host "  .\start-consumer.ps1 monitor        # Démarrer avec surveillance automatique"
    Write-Host "  .\start-consumer.ps1 status         # Vérifier le statut"
    Write-Host "  .\start-consumer.ps1 logs           # Voir les logs"
}

# Fonction pour afficher le statut
function Show-Status {
    if (Test-ConsumerRunning) {
        $pid = Get-Content $PidFile
        Write-Log "Consumer en cours d'exécution (PID: $pid)" "SUCCESS"

        # Afficher les informations du processus
        Write-Host ""
        Write-Host "Informations du processus:" -ForegroundColor Cyan
        try {
            $process = Get-Process -Id $pid -ErrorAction Stop
            Write-Host "PID: $($process.Id)"
            Write-Host "Nom: $($process.ProcessName)"
            Write-Host "Temps CPU: $($process.TotalProcessorTime)"
            Write-Host "Mémoire: $([math]::Round($process.WorkingSet64 / 1MB, 2)) MB"
            Write-Host "Démarrage: $($process.StartTime)"
        }
        catch {
            Write-Log "Impossible d'obtenir les informations du processus" "ERROR"
        }
    }
    else {
        Write-Log "Consumer non démarré" "WARNING"
    }
}

# Fonction pour afficher les logs
function Show-Logs {
    if (Test-Path $LogFile) {
        Write-Host "=== Derniers logs du consumer ===" -ForegroundColor Cyan
        Get-Content $LogFile -Tail 50
    }
    else {
        Write-Log "Fichier de log non trouvé: $LogFile" "WARNING"
    }
}

# Gestion des signaux (Ctrl+C)
$null = Register-EngineEvent -SourceIdentifier PowerShell.Exiting -Action {
    Write-Log "Signal d'arrêt reçu, arrêt du consumer..."
    Stop-Consumer
}

# Créer le répertoire de logs s'il n'existe pas
$logDir = Split-Path -Parent $LogFile
if (!(Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir -Force | Out-Null
}

# Traitement des arguments
switch ($Action.ToLower()) {
    "start" {
        if (Test-ConsumerRunning) {
            Write-Log "Consumer déjà en cours d'exécution" "WARNING"
            Show-Status
        }
        else {
            Start-Consumer
        }
    }
    "stop" {
        Stop-Consumer
    }
    "restart" {
        Stop-Consumer
        Start-Sleep -Seconds 2
        Start-Consumer
    }
    "status" {
        Show-Status
    }
    "monitor" {
        if (Test-ConsumerRunning) {
            Write-Log "Consumer déjà en cours d'exécution" "WARNING"
            Show-Status
        }
        else {
            Start-Consumer
        }
        Start-Monitoring
    }
    "logs" {
        Show-Logs
    }
    "help" {
        Show-Help
    }
    default {
        Write-Log "Option inconnue: $Action" "ERROR"
        Show-Help
        exit 1
    }
}
