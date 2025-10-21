@echo off
REM Script batch pour démarrer le consumer Messenger pour LOKAPRO
REM Ce script redémarre automatiquement le consumer s'il s'arrête

setlocal enabledelayedexpansion

REM Configuration
set SCRIPT_DIR=%~dp0
set LOG_FILE=%SCRIPT_DIR%var\log\consumer.log
set PID_FILE=%SCRIPT_DIR%var\consumer.pid
set CONSUMER_COMMAND=php bin/console messenger:consume async --time-limit=3600 --memory-limit=256 --sleep=5

REM Créer le répertoire de logs s'il n'existe pas
if not exist "%SCRIPT_DIR%var\log" mkdir "%SCRIPT_DIR%var\log"

REM Fonction pour afficher l'aide
:show_help
echo Usage: %0 [OPTIONS]
echo.
echo Options:
echo   start     Démarrer le consumer (défaut)
echo   stop      Arrêter le consumer
echo   restart   Redémarrer le consumer
echo   status    Afficher le statut du consumer
echo   monitor   Démarrer la surveillance automatique
echo   logs      Afficher les logs du consumer
echo   help      Afficher cette aide
echo.
echo Exemples:
echo   %0 start          # Démarrer le consumer
echo   %0 monitor        # Démarrer avec surveillance automatique
echo   %0 status         # Vérifier le statut
echo   %0 logs           # Voir les logs
goto :eof

REM Fonction pour vérifier si le consumer est en cours d'exécution
:is_consumer_running
if exist "%PID_FILE%" (
    set /p CONSUMER_PID=<"%PID_FILE%"
    tasklist /FI "PID eq !CONSUMER_PID!" 2>nul | find /I "!CONSUMER_PID!" >nul
    if !errorlevel! equ 0 (
        exit /b 0
    ) else (
        del "%PID_FILE%" 2>nul
        exit /b 1
    )
) else (
    exit /b 1
)
goto :eof

REM Fonction pour arrêter le consumer
:stop_consumer
if exist "%PID_FILE%" (
    set /p CONSUMER_PID=<"%PID_FILE%"
    echo [%date% %time%] Arrêt du consumer (PID: !CONSUMER_PID!)...
    echo [%date% %time%] Arrêt du consumer (PID: !CONSUMER_PID!)... >> "%LOG_FILE%"

    taskkill /PID !CONSUMER_PID! /F 2>nul
    timeout /t 2 /nobreak >nul

    tasklist /FI "PID eq !CONSUMER_PID!" 2>nul | find /I "!CONSUMER_PID!" >nul
    if !errorlevel! equ 0 (
        echo [%date% %time%] Le consumer ne s'est pas arrêté, forçage de l'arrêt...
        echo [%date% %time%] Le consumer ne s'est pas arrêté, forçage de l'arrêt... >> "%LOG_FILE%"
        taskkill /PID !CONSUMER_PID! /F /T 2>nul
    )

    del "%PID_FILE%" 2>nul
    echo [%date% %time%] Consumer arrêté
    echo [%date% %time%] Consumer arrêté >> "%LOG_FILE%"
)
goto :eof

REM Fonction pour démarrer le consumer
:start_consumer
echo [%date% %time%] Démarrage du consumer Messenger...
echo [%date% %time%] Démarrage du consumer Messenger... >> "%LOG_FILE%"

REM Vérifier que nous sommes dans le bon répertoire
if not exist "bin\console" (
    echo [%date% %time%] ERREUR: Le fichier bin\console n'existe pas. Assurez-vous d'être dans le répertoire racine du projet.
    echo [%date% %time%] ERREUR: Le fichier bin\console n'existe pas. Assurez-vous d'être dans le répertoire racine du projet. >> "%LOG_FILE%"
    exit /b 1
)

REM Vérifier que la base de données est accessible
php bin/console doctrine:query:sql "SELECT 1" >nul 2>&1
if !errorlevel! neq 0 (
    echo [%date% %time%] ERREUR: Impossible de se connecter à la base de données. Vérifiez la configuration.
    echo [%date% %time%] ERREUR: Impossible de se connecter à la base de données. Vérifiez la configuration. >> "%LOG_FILE%"
    exit /b 1
)

REM Démarrer le consumer
echo [%date% %time%] Exécution de la commande: %CONSUMER_COMMAND%
echo [%date% %time%] Exécution de la commande: %CONSUMER_COMMAND% >> "%LOG_FILE%"

start /B "" php bin/console messenger:consume async --time-limit=3600 --memory-limit=256 --sleep=5 > "%LOG_FILE%" 2>&1

REM Attendre un peu pour vérifier que le processus démarre correctement
timeout /t 3 /nobreak >nul

REM Trouver le PID du processus PHP qui vient de démarrer
for /f "tokens=2" %%i in ('tasklist /FI "IMAGENAME eq php.exe" /FO CSV ^| findstr /C:"php.exe"') do (
    set CONSUMER_PID=%%i
    set CONSUMER_PID=!CONSUMER_PID:"=!
)

if defined CONSUMER_PID (
    echo !CONSUMER_PID! > "%PID_FILE%"
    echo [%date% %time%] Consumer démarré avec succès (PID: !CONSUMER_PID!)
    echo [%date% %time%] Consumer démarré avec succès (PID: !CONSUMER_PID!) >> "%LOG_FILE%"
    exit /b 0
) else (
    echo [%date% %time%] Échec du démarrage du consumer
    echo [%date% %time%] Échec du démarrage du consumer >> "%LOG_FILE%"
    del "%PID_FILE%" 2>nul
    exit /b 1
)
goto :eof

REM Fonction pour afficher le statut
:show_status
call :is_consumer_running
if !errorlevel! equ 0 (
    set /p CONSUMER_PID=<"%PID_FILE%"
    echo [%date% %time%] Consumer en cours d'exécution (PID: !CONSUMER_PID!)
    echo.
    echo Informations du processus:
    tasklist /FI "PID eq !CONSUMER_PID!" /FO TABLE
) else (
    echo [%date% %time%] Consumer non démarré
)
goto :eof

REM Fonction pour afficher les logs
:show_logs
if exist "%LOG_FILE%" (
    echo === Derniers logs du consumer ===
    powershell "Get-Content '%LOG_FILE%' | Select-Object -Last 50"
) else (
    echo [%date% %time%] Fichier de log non trouvé: %LOG_FILE%
)
goto :eof

REM Fonction pour surveiller le consumer
:monitor_consumer
set RESTART_COUNT=0
set MAX_RESTARTS=10

echo [%date% %time%] Surveillance du consumer démarrée...
echo [%date% %time%] Surveillance du consumer démarrée... >> "%LOG_FILE%"

:monitor_loop
call :is_consumer_running
if !errorlevel! neq 0 (
    set /a RESTART_COUNT+=1

    if !RESTART_COUNT! gtr %MAX_RESTARTS% (
        echo [%date% %time%] ERREUR: Nombre maximum de redémarrages atteint (%MAX_RESTARTS%). Arrêt de la surveillance.
        echo [%date% %time%] ERREUR: Nombre maximum de redémarrages atteint (%MAX_RESTARTS%). Arrêt de la surveillance. >> "%LOG_FILE%"
        goto :eof
    )

    echo [%date% %time%] AVERTISSEMENT: Consumer arrêté détecté. Redémarrage #!RESTART_COUNT!...
    echo [%date% %time%] AVERTISSEMENT: Consumer arrêté détecté. Redémarrage #!RESTART_COUNT!... >> "%LOG_FILE%"

    call :start_consumer
    if !errorlevel! equ 0 (
        set RESTART_COUNT=0
    ) else (
        echo [%date% %time%] ERREUR: Échec du redémarrage #!RESTART_COUNT!
        echo [%date% %time%] ERREUR: Échec du redémarrage #!RESTART_COUNT! >> "%LOG_FILE%"
    )
)

REM Attendre 30 secondes avant la prochaine vérification
timeout /t 30 /nobreak >nul
goto :monitor_loop
goto :eof

REM Traitement des arguments
if "%1"=="" set "1=start"

if "%1"=="start" (
    call :is_consumer_running
    if !errorlevel! equ 0 (
        echo [%date% %time%] AVERTISSEMENT: Consumer déjà en cours d'exécution
        call :show_status
    ) else (
        call :start_consumer
    )
) else if "%1"=="stop" (
    call :stop_consumer
) else if "%1"=="restart" (
    call :stop_consumer
    timeout /t 2 /nobreak >nul
    call :start_consumer
) else if "%1"=="status" (
    call :show_status
) else if "%1"=="monitor" (
    call :is_consumer_running
    if !errorlevel! equ 0 (
        echo [%date% %time%] AVERTISSEMENT: Consumer déjà en cours d'exécution
        call :show_status
    ) else (
        call :start_consumer
    )
    call :monitor_consumer
) else if "%1"=="logs" (
    call :show_logs
) else if "%1"=="help" (
    call :show_help
) else (
    echo [%date% %time%] ERREUR: Option inconnue: %1
    call :show_help
    exit /b 1
)

endlocal
