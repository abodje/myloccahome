#!/bin/bash
# Script pour démarrer le worker Messenger en arrière-plan
# À utiliser si vous n'avez pas systemd sur votre hébergement partagé

PROJECT_PATH="/home/lokaprot/myloccahome"
PHP_PATH="/usr/local/bin/php"
LOG_PATH="/home/lokaprot/logs"
PID_FILE="$LOG_PATH/messenger-worker.pid"

# Vérifier si un worker est déjà en cours
if [ -f "$PID_FILE" ]; then
    PID=$(cat "$PID_FILE")
    if ps -p $PID > /dev/null 2>&1; then
        echo "⚠️  Un worker Messenger est déjà actif (PID: $PID)"
        exit 1
    else
        echo "🧹 Nettoyage de l'ancien PID file..."
        rm -f "$PID_FILE"
    fi
fi

# Créer le dossier logs si nécessaire
mkdir -p "$LOG_PATH"

echo "🚀 Démarrage du worker Messenger..."
echo "   Projet: $PROJECT_PATH"
echo "   Logs: $LOG_PATH/messenger-worker.log"

# Démarrer le worker en arrière-plan
nohup $PHP_PATH $PROJECT_PATH/bin/console messenger:consume async -vv \
    --time-limit=3600 \
    --memory-limit=256M \
    >> $LOG_PATH/messenger-worker.log 2>&1 &

# Sauvegarder le PID
echo $! > "$PID_FILE"

echo "✅ Worker Messenger démarré (PID: $!)"
echo ""
echo "📊 Pour surveiller les logs:"
echo "   tail -f $LOG_PATH/messenger-worker.log"
echo ""
echo "🛑 Pour arrêter le worker:"
echo "   kill \$(cat $PID_FILE)"
