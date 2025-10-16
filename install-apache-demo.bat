@echo off
echo ========================================
echo Installation Apache MYLOCCA Demo System
echo ========================================
echo.

REM Vérifier les privilèges administrateur
net session >nul 2>&1
if %errorLevel% == 0 (
    echo [OK] Privilèges administrateur détectés
) else (
    echo [ERREUR] Ce script nécessite des privilèges administrateur
    echo Veuillez exécuter en tant qu'administrateur
    pause
    exit /b 1
)

echo.
echo [1/6] Copie de la configuration Apache...
copy "apache-mylocca-complet.conf" "C:\wamp64\bin\apache\apache2.4.54\conf\extra\mylocca-demo.conf"
if %errorLevel% == 0 (
    echo [OK] Configuration Apache copiée
) else (
    echo [ERREUR] Impossible de copier la configuration Apache
    pause
    exit /b 1
)

echo.
echo [2/6] Ajout de l'include dans httpd.conf...
findstr /C:"Include conf/extra/mylocca-demo.conf" "C:\wamp64\bin\apache\apache2.4.54\conf\httpd.conf" >nul
if %errorLevel% == 0 (
    echo [OK] Include déjà présent dans httpd.conf
) else (
    echo # Configuration MYLOCCA Demo System >> "C:\wamp64\bin\apache\apache2.4.54\conf\httpd.conf"
    echo Include conf/extra/mylocca-demo.conf >> "C:\wamp64\bin\apache\apache2.4.54\conf\httpd.conf"
    echo [OK] Include ajouté dans httpd.conf
)

echo.
echo [3/6] Configuration du fichier hosts...
findstr /C:"mylocca.local" "C:\Windows\System32\drivers\etc\hosts" >nul
if %errorLevel% == 0 (
    echo [OK] Entrées MYLOCCA déjà présentes dans hosts
) else (
    echo # MYLOCCA Demo System >> "C:\Windows\System32\drivers\etc\hosts"
    echo 127.0.0.1 mylocca.local >> "C:\Windows\System32\drivers\etc\hosts"
    echo 127.0.0.1 demo.mylocca.local >> "C:\Windows\System32\drivers\etc\hosts"
    echo 127.0.0.1 *.demo.mylocca.local >> "C:\Windows\System32\drivers\etc\hosts"
    echo [OK] Entrées ajoutées dans hosts
)

echo.
echo [4/6] Création des dossiers de logs...
if not exist "C:\wamp64\logs" mkdir "C:\wamp64\logs"
echo [OK] Dossier de logs créé

echo.
echo [5/6] Création du dossier demo_data...
if not exist "demo_data" mkdir "demo_data"
echo [OK] Dossier demo_data créé

echo.
echo [6/6] Vérification des modules Apache...
findstr /C:"mod_rewrite" "C:\wamp64\bin\apache\apache2.4.54\conf\httpd.conf" >nul
if %errorLevel% == 0 (
    echo [OK] Module mod_rewrite activé
) else (
    echo [ATTENTION] Module mod_rewrite non détecté - vérifiez la configuration
)

findstr /C:"mod_headers" "C:\wamp64\bin\apache\apache2.4.54\conf\httpd.conf" >nul
if %errorLevel% == 0 (
    echo [OK] Module mod_headers activé
) else (
    echo [ATTENTION] Module mod_headers non détecté - vérifiez la configuration
)

echo.
echo ========================================
echo Installation terminée avec succès !
echo ========================================
echo.
echo Prochaines étapes :
echo 1. Redémarrez Apache via WAMP
echo 2. Testez : http://mylocca.local
echo 3. Testez : http://demo.mylocca.local
echo 4. Créez un compte pour tester les environnements de démo
echo.
echo URLs de test :
echo - Site principal : http://mylocca.local
echo - Environnement démo : http://demo.mylocca.local
echo - Inscription : http://mylocca.local/inscription/freemium
echo.
pause
