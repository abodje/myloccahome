#!/bin/bash
# Script pour vérifier le statut du worker Messenger

LOG_PATH="/home/lokaprot/logs"
PID_FILE="$LOG_PATH/messenger-worker.pid"

echo "🔍 Vérification du worker Messenger"
echo "===================================="
echo ""

if [ ! -f "$PID_FILE" ]; then
    echo "❌ Status: ARRÊTÉ"
    echo "   Aucun PID file trouvé"
    exit 1
fi

PID=$(cat "$PID_FILE")

if ps -p $PID > /dev/null 2>&1; then
    echo "✅ Status: ACTIF"
    echo "   PID: $PID"
    echo ""

    # Afficher les infos du processus
    echo "📊 Informations du processus:"
    ps -p $PID -o pid,vsz,rss,etime,cmd
    echo ""

    # Afficher les dernières lignes des logs
    if [ -f "$LOG_PATH/messenger-worker.log" ]; then
        echo "📝 Dernières lignes des logs:"
        tail -n 10 "$LOG_PATH/messenger-worker.log"
    fi
else
    echo "❌ Status: ARRÊTÉ"
    echo "   Le processus (PID: $PID) n'est plus actif"
    echo ""
    echo "💡 Pour démarrer le worker:"
    echo "   ./start-messenger-worker.sh"
fi
