# Script PowerShell pour configurer les tâches planifiées
# Exécuter en tant qu'administrateur

$projectPath = "C:\wamp64\mylocca"
$phpPath = "C:\wamp64\bin\php\php8.3.14\php.exe" # Ajuster selon votre version PHP

# Tâche 1: Génération des loyers (1er jour du mois à 00:00)
$action1 = New-ScheduledTaskAction -Execute $phpPath -Argument "bin/console app:generate-rents" -WorkingDirectory $projectPath
$trigger1 = New-ScheduledTaskTrigger -Daily -At "00:00" -DaysInterval 1
# Limiter au 1er jour du mois via conditions
$settings1 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
Register-ScheduledTask -TaskName "MyLocca - Generate Rents" -Action $action1 -Trigger $trigger1 -Settings $settings1 -Description "Génère les loyers mensuels automatiquement"

# Tâche 2: Envoi des quittances (1er jour du mois à 06:00)
$action2 = New-ScheduledTaskAction -Execute $phpPath -Argument "bin/console app:send-rent-receipts" -WorkingDirectory $projectPath
$trigger2 = New-ScheduledTaskTrigger -Daily -At "06:00" -DaysInterval 1
Register-ScheduledTask -TaskName "MyLocca - Send Receipts" -Action $action2 -Trigger $trigger2 -Settings $settings1 -Description "Envoie les quittances de loyer par email"

# Tâche 3: Génération des documents (tous les lundis à 01:00)
$action3 = New-ScheduledTaskAction -Execute $phpPath -Argument "bin/console app:generate-rent-documents" -WorkingDirectory $projectPath
$trigger3 = New-ScheduledTaskTrigger -Weekly -DaysOfWeek Monday -At "01:00"
Register-ScheduledTask -TaskName "MyLocca - Generate Documents" -Action $action3 -Trigger $trigger3 -Settings $settings1 -Description "Génère les documents de loyer hebdomadaires"

# Tâche 4: Génération anticipée (le 25 de chaque mois à 02:00)
$action4 = New-ScheduledTaskAction -Execute $phpPath -Argument "bin/console app:generate-rents --months-ahead=3" -WorkingDirectory $projectPath
$trigger4 = New-ScheduledTaskTrigger -Daily -At "02:00" -DaysInterval 1
Register-ScheduledTask -TaskName "MyLocca - Generate Future Rents" -Action $action4 -Trigger $trigger4 -Settings $settings1 -Description "Génère les loyers 3 mois à l'avance"

Write-Host "✅ Tâches planifiées configurées avec succès!" -ForegroundColor Green
Write-Host "📋 Vérifiez dans le Planificateur de tâches Windows" -ForegroundColor Cyan
