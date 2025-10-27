#!/bin/bash
# Script pour configurer les tâches cron sur le serveur de production
# À exécuter via SSH sur le serveur lokaprot

echo "🔧 Configuration des tâches cron pour MyLocca"
echo "=============================================="
echo ""

# Variables
PROJECT_PATH="/home/lokaprot/myloccahome"
PHP_PATH="/usr/local/bin/php"
LOG_PATH="/home/lokaprot/logs"

# Vérifier que le projet existe
if [ ! -d "$PROJECT_PATH" ]; then
    echo "❌ Erreur: Le répertoire $PROJECT_PATH n'existe pas!"
    exit 1
fi

# Vérifier que PHP existe
if [ ! -f "$PHP_PATH" ]; then
    echo "⚠️  Attention: $PHP_PATH n'existe pas. Recherche de PHP..."
    PHP_PATH=$(which php)
    echo "✅ PHP trouvé: $PHP_PATH"
fi

# Créer le dossier logs si nécessaire
mkdir -p "$LOG_PATH"

echo "📋 Configuration détectée:"
echo "   Projet: $PROJECT_PATH"
echo "   PHP: $PHP_PATH"
echo "   Logs: $LOG_PATH"
echo ""

# Sauvegarder le crontab actuel
echo "💾 Sauvegarde du crontab actuel..."
crontab -l > /tmp/crontab_backup_$(date +%Y%m%d_%H%M%S).txt 2>/dev/null
echo "✅ Sauvegarde créée dans /tmp"
echo ""

# Créer le nouveau crontab
echo "📝 Ajout des tâches cron..."

# Afficher les tâches à ajouter
cat << 'EOF'

Tâches qui seront ajoutées:
---------------------------

# MyLocca - Génération des loyers - 1er du mois à 00:00
0 0 1 * * cd /home/lokaprot/myloccahome && /usr/local/bin/php bin/console app:generate-rents >> /home/lokaprot/logs/generate-rents.log 2>&1

# MyLocca - Envoi des quittances - 1er du mois à 06:00
0 6 1 * * cd /home/lokaprot/myloccahome && /usr/local/bin/php bin/console app:send-rent-receipts >> /home/lokaprot/logs/send-receipts.log 2>&1

# MyLocca - Génération des documents - Tous les lundis à 01:00
0 1 * * 1 cd /home/lokaprot/myloccahome && /usr/local/bin/php bin/console app:generate-rent-documents >> /home/lokaprot/logs/generate-docs.log 2>&1

# MyLocca - Génération anticipée - Le 25 de chaque mois à 02:00
0 2 25 * * cd /home/lokaprot/myloccahome && /usr/local/bin/php bin/console app:generate-rents --months-ahead=3 >> /home/lokaprot/logs/generate-future.log 2>&1

EOF

echo ""
read -p "Voulez-vous ajouter ces tâches au crontab ? (o/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[OoYy]$ ]]; then
    # Récupérer le crontab actuel
    (crontab -l 2>/dev/null; echo ""; echo "# MyLocca - Tâches automatiques"; \
    echo "0 0 1 * * cd $PROJECT_PATH && $PHP_PATH bin/console app:generate-rents >> $LOG_PATH/generate-rents.log 2>&1"; \
    echo "0 6 1 * * cd $PROJECT_PATH && $PHP_PATH bin/console app:send-rent-receipts >> $LOG_PATH/send-receipts.log 2>&1"; \
    echo "0 1 * * 1 cd $PROJECT_PATH && $PHP_PATH bin/console app:generate-rent-documents >> $LOG_PATH/generate-docs.log 2>&1"; \
    echo "0 2 25 * * cd $PROJECT_PATH && $PHP_PATH bin/console app:generate-rents --months-ahead=3 >> $LOG_PATH/generate-future.log 2>&1") | crontab -
    
    echo "✅ Tâches cron configurées avec succès!"
    echo ""
    echo "📋 Vérification du crontab:"
    crontab -l | grep "MyLocca"
    echo ""
    echo "✅ Configuration terminée!"
    echo ""
    echo "📊 Pour surveiller les logs:"
    echo "   tail -f $LOG_PATH/generate-rents.log"
    echo "   tail -f $LOG_PATH/send-receipts.log"
    echo ""
    echo "🧪 Pour tester manuellement:"
    echo "   cd $PROJECT_PATH && $PHP_PATH bin/console app:generate-rents --dry-run"
else
    echo "❌ Configuration annulée"
    exit 0
fi
