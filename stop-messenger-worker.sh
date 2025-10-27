#!/bin/bash
# Script pour arrêter le worker Messenger

LOG_PATH="/home/lokaprot/logs"
PID_FILE="$LOG_PATH/messenger-worker.pid"

if [ ! -f "$PID_FILE" ]; then
    echo "❌ Aucun worker Messenger actif"
    exit 1
fi

PID=$(cat "$PID_FILE")

if ps -p $PID > /dev/null 2>&1; then
    echo "🛑 Arrêt du worker Messenger (PID: $PID)..."
    kill $PID
    sleep 2

    # Vérifier si le processus est toujours actif
    if ps -p $PID > /dev/null 2>&1; then
        echo "⚠️  Forçage de l'arrêt..."
        kill -9 $PID
    fi

    rm -f "$PID_FILE"
    echo "✅ Worker Messenger arrêté"
else
    echo "⚠️  Le worker (PID: $PID) n'est pas actif"
    rm -f "$PID_FILE"
fi
