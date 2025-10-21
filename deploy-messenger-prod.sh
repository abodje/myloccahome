#!/bin/bash

# Script de déploiement Messenger pour serveur de production MySQL 8
# À exécuter sur votre serveur de production

set -e

# Configuration
PROJECT_DIR="/home/lokaprot/myloccahome"
BACKUP_DIR="$PROJECT_DIR/backup-messenger-$(date +%Y%m%d_%H%M%S)"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')] ✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] ⚠️ $1${NC}"
}

log_error() {
    echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] ❌ $1${NC}"
}

# Fonction pour vérifier MySQL 8
check_mysql8() {
    log "Vérification de la version MySQL..."

    MYSQL_VERSION=$(php bin/console doctrine:query:sql "SELECT VERSION()" | grep -o '[0-9]\+\.[0-9]\+' | head -1)

    if [[ "$MYSQL_VERSION" == "8."* ]]; then
        log_success "MySQL 8.x détecté (Version: $MYSQL_VERSION)"
        return 0
    else
        log_error "MySQL 8.x requis, version actuelle: $MYSQL_VERSION"
        return 1
    fi
}

# Fonction pour sauvegarder la configuration actuelle
backup_config() {
    log "Sauvegarde de la configuration actuelle..."

    mkdir -p "$BACKUP_DIR"

    # Sauvegarder la configuration Messenger
    if [ -f "config/packages/messenger.yaml" ]; then
        cp "config/packages/messenger.yaml" "$BACKUP_DIR/messenger.yaml.backup"
        log_success "Configuration Messenger sauvegardée"
    fi

    # Sauvegarder la table messenger_messages
    php bin/console doctrine:query:sql "SELECT COUNT(*) as total FROM messenger_messages" > "$BACKUP_DIR/messenger_stats.txt"

    log_success "Sauvegarde terminée dans: $BACKUP_DIR"
}

# Fonction pour déployer la configuration production
deploy_production_config() {
    log "Déploiement de la configuration production..."

    # Copier la configuration production
    cp "config/packages/messenger-prod.yaml" "config/packages/messenger.yaml"

    log_success "Configuration production déployée"
}

# Fonction pour initialiser la table messenger_messages
setup_messenger_table() {
    log "Configuration de la table messenger_messages..."

    # Créer la table si elle n'existe pas
    php bin/console doctrine:query:sql "
        CREATE TABLE IF NOT EXISTS messenger_messages (
            id BIGINT AUTO_INCREMENT NOT NULL,
            body LONGTEXT NOT NULL,
            headers LONGTEXT NOT NULL,
            queue_name VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL,
            available_at DATETIME NOT NULL,
            delivered_at DATETIME DEFAULT NULL,
            INDEX IDX_75EA56E0FB7336F0 (queue_name),
            INDEX IDX_75EA56E0E3BD61CE (available_at),
            INDEX IDX_75EA56E016BA31DB (delivered_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
    " 2>/dev/null || log_warning "Table messenger_messages existe déjà"

    # Créer la table pour les messages échoués
    php bin/console doctrine:query:sql "
        CREATE TABLE IF NOT EXISTS messenger_messages_failed (
            id BIGINT AUTO_INCREMENT NOT NULL,
            body LONGTEXT NOT NULL,
            headers LONGTEXT NOT NULL,
            queue_name VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL,
            available_at DATETIME NOT NULL,
            delivered_at DATETIME DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
    " 2>/dev/null || log_warning "Table messenger_messages_failed existe déjà"

    log_success "Tables Messenger configurées"
}

# Fonction pour tester la configuration
test_configuration() {
    log "Test de la configuration..."

    # Vérifier la configuration Messenger
    php bin/console debug:messenger > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        log_success "Configuration Messenger valide"
    else
        log_error "Erreur dans la configuration Messenger"
        return 1
    fi

    # Tester l'envoi d'un message
    log "Test d'envoi de message..."
    php bin/console app:messenger:dispatch-task --task-type=TEST_EMAIL_SETTINGS --email=test@example.com > /dev/null 2>&1

    if [ $? -eq 0 ]; then
        log_success "Envoi de message réussi"
    else
        log_error "Échec de l'envoi de message"
        return 1
    fi

    # Vérifier que le message est en queue
    MESSAGE_COUNT=$(php bin/console messenger:stats | grep -o '[0-9]\+' | head -1)
    if [ "$MESSAGE_COUNT" -gt 0 ]; then
        log_success "Message en queue: $MESSAGE_COUNT"
    else
        log_warning "Aucun message en queue"
    fi

    log_success "Configuration testée avec succès"
}

# Fonction pour créer le script de démarrage du worker
create_worker_script() {
    log "Création du script de démarrage du worker..."

    cat > "$PROJECT_DIR/start-messenger-worker.sh" << 'EOF'
#!/bin/bash

# Script de démarrage du worker Messenger pour production MySQL 8
# Optimisé pour serveur partagé

PROJECT_DIR="/home/lokaprot/myloccahome"
LOG_FILE="$PROJECT_DIR/var/log/messenger-worker.log"
PID_FILE="$PROJECT_DIR/var/messenger-worker.pid"

# Créer le répertoire de logs
mkdir -p "$(dirname "$LOG_FILE")"

# Fonction de logging
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Fonction pour vérifier si le worker est actif
is_worker_running() {
    if [ -f "$PID_FILE" ]; then
        local pid=$(cat "$PID_FILE")
        if ps -p "$pid" > /dev/null 2>&1; then
            return 0
        else
            rm -f "$PID_FILE"
            return 1
        fi
    fi
    return 1
}

# Fonction pour démarrer le worker
start_worker() {
    log "Démarrage du worker Messenger..."

    cd "$PROJECT_DIR"

    # Démarrer le worker avec des limites adaptées au serveur partagé
    nohup php bin/console messenger:consume async \
        --time-limit=300 \
        --memory-limit=128 \
        --sleep=5 \
        --queues=async \
        >> "$LOG_FILE" 2>&1 &

    local worker_pid=$!
    echo "$worker_pid" > "$PID_FILE"

    sleep 3

    if ps -p "$worker_pid" > /dev/null 2>&1; then
        log "Worker démarré avec succès (PID: $worker_pid)"
        return 0
    else
        log "Échec du démarrage du worker"
        rm -f "$PID_FILE"
        return 1
    fi
}

# Fonction pour arrêter le worker
stop_worker() {
    if [ -f "$PID_FILE" ]; then
        local pid=$(cat "$PID_FILE")
        log "Arrêt du worker (PID: $pid)..."
        kill "$pid" 2>/dev/null
        sleep 2
        if ps -p "$pid" > /dev/null 2>&1; then
            kill -9 "$pid" 2>/dev/null
        fi
        rm -f "$PID_FILE"
        log "Worker arrêté"
    fi
}

# Traitement des arguments
case "${1:-start}" in
    "start")
        if is_worker_running; then
            log "Worker déjà en cours d'exécution"
        else
            start_worker
        fi
        ;;
    "stop")
        stop_worker
        ;;
    "restart")
        stop_worker
        sleep 2
        start_worker
        ;;
    "status")
        if is_worker_running; then
            local pid=$(cat "$PID_FILE")
            log "Worker actif (PID: $pid)"
        else
            log "Worker inactif"
        fi
        ;;
    *)
        echo "Usage: $0 [start|stop|restart|status]"
        exit 1
        ;;
esac
EOF

    chmod +x "$PROJECT_DIR/start-messenger-worker.sh"
    log_success "Script de démarrage créé"
}

# Fonction pour créer le script Cron
create_cron_script() {
    log "Création du script Cron..."

    cat > "$PROJECT_DIR/cron-messenger-worker.sh" << 'EOF'
#!/bin/bash

# Script Cron pour le worker Messenger
# À exécuter toutes les 5 minutes

PROJECT_DIR="/home/lokaprot/myloccahome"
LOG_FILE="$PROJECT_DIR/var/log/cron-messenger.log"
LOCK_FILE="$PROJECT_DIR/var/cron-messenger.lock"

# Vérifier si le script est déjà en cours d'exécution
if [ -f "$LOCK_FILE" ]; then
    local pid=$(cat "$LOCK_FILE")
    if ps -p "$pid" > /dev/null 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Script déjà en cours d'exécution (PID: $pid)" >> "$LOG_FILE"
        exit 0
    else
        rm -f "$LOCK_FILE"
    fi
fi

# Créer le fichier de verrouillage
echo $$ > "$LOCK_FILE"

# Fonction de nettoyage
cleanup() {
    rm -f "$LOCK_FILE"
    exit 0
}
trap cleanup EXIT

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Démarrage du worker Messenger via Cron" >> "$LOG_FILE"

# Changer vers le répertoire du projet
cd "$PROJECT_DIR"

# Vérifier si le worker est déjà en cours d'exécution
if [ -f "var/messenger-worker.pid" ]; then
    local pid=$(cat "var/messenger-worker.pid")
    if ps -p "$pid" > /dev/null 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Worker déjà actif (PID: $pid)" >> "$LOG_FILE"
        exit 0
    else
        rm -f "var/messenger-worker.pid"
    fi
fi

# Démarrer le worker
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Démarrage du worker..." >> "$LOG_FILE"
php bin/console messenger:consume async \
    --time-limit=300 \
    --memory-limit=128 \
    --sleep=5 \
    --queues=async \
    >> "var/log/messenger-worker.log" 2>&1 &
local worker_pid=$!

# Sauvegarder le PID
echo "$worker_pid" > "var/messenger-worker.pid"

# Attendre un peu pour vérifier que le processus démarre
sleep 3

if ps -p "$worker_pid" > /dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Worker démarré avec succès (PID: $worker_pid)" >> "$LOG_FILE"
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Échec du démarrage du worker" >> "$LOG_FILE"
    rm -f "var/messenger-worker.pid"
fi
EOF

    chmod +x "$PROJECT_DIR/cron-messenger-worker.sh"
    log_success "Script Cron créé"
}

# Fonction pour afficher les instructions de configuration
show_instructions() {
    log_success "Déploiement terminé !"
    echo ""
    echo "=== Configuration Messenger Production MySQL 8 ==="
    echo ""
    echo "✅ Configuration déployée"
    echo "✅ Tables Messenger créées"
    echo "✅ Scripts de démarrage créés"
    echo ""
    echo "=== Prochaines étapes ==="
    echo ""
    echo "1. Configurer Cron dans votre panneau d'administration :"
    echo "   Commande: $PROJECT_DIR/cron-messenger-worker.sh"
    echo "   Fréquence: */5 * * * * (toutes les 5 minutes)"
    echo ""
    echo "2. Tester le worker manuellement :"
    echo "   $PROJECT_DIR/start-messenger-worker.sh start"
    echo ""
    echo "3. Vérifier les logs :"
    echo "   tail -f $PROJECT_DIR/var/log/messenger-worker.log"
    echo ""
    echo "4. Tester l'envoi de messages :"
    echo "   cd $PROJECT_DIR"
    echo "   php bin/console app:messenger:dispatch-task --task-type=TEST_EMAIL_SETTINGS --email=votre@email.com"
    echo ""
    echo "5. Vérifier les messages en queue :"
    echo "   php bin/console messenger:stats"
    echo ""
    echo "=== Commandes utiles ==="
    echo "Démarrer:     $PROJECT_DIR/start-messenger-worker.sh start"
    echo "Arrêter:      $PROJECT_DIR/start-messenger-worker.sh stop"
    echo "Statut:       $PROJECT_DIR/start-messenger-worker.sh status"
    echo "Logs:         tail -f $PROJECT_DIR/var/log/messenger-worker.log"
    echo ""
    echo "=== Sauvegarde ==="
    echo "Configuration sauvegardée dans: $BACKUP_DIR"
    echo ""
}

# Fonction principale
main() {
    log "=== Déploiement Messenger Production MySQL 8 ==="

    check_mysql8 || exit 1
    backup_config
    deploy_production_config
    setup_messenger_table
    test_configuration
    create_worker_script
    create_cron_script
    show_instructions

    log_success "Déploiement complet !"
}

# Exécution
main "$@"
