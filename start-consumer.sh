#!/bin/bash

# Script de démarrage du consumer Messenger pour LOKAPRO
# Ce script redémarre automatiquement le consumer s'il s'arrête
# Optimisé pour serveurs Linux (Ubuntu/CentOS/Debian) et serveurs partagés

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="$SCRIPT_DIR/var/log/consumer.log"
PID_FILE="$SCRIPT_DIR/var/consumer.pid"
CONSUMER_COMMAND="php bin/console messenger:consume async --time-limit=3600 --memory-limit=256 --sleep=5"

# Détection automatique de l'environnement
if [ "$EUID" -eq 0 ]; then
    # Serveur dédié avec droits root
    ENVIRONMENT="dedicated"
    USER="www-data"
    GROUP="www-data"
    PHP_BIN="php"
    USE_SUDO=true
else
    # Serveur partagé sans droits root
    ENVIRONMENT="shared"
    USER="$(whoami)"
    GROUP="$(id -gn)"
    PHP_BIN="php"
    USE_SUDO=false
    # Ajuster les limites pour serveur partagé
    CONSUMER_COMMAND="php bin/console messenger:consume async --time-limit=300 --memory-limit=128 --sleep=5"
fi

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction de logging
log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')] ✅ $1${NC}" | tee -a "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] ⚠️ $1${NC}" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] ❌ $1${NC}" | tee -a "$LOG_FILE"
}

# Fonction pour vérifier si le consumer est déjà en cours d'exécution
is_consumer_running() {
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

# Fonction pour arrêter le consumer
stop_consumer() {
    if [ -f "$PID_FILE" ]; then
        local pid=$(cat "$PID_FILE")
        log "Arrêt du consumer (PID: $pid)..."
        kill "$pid" 2>/dev/null
        sleep 2
        if ps -p "$pid" > /dev/null 2>&1; then
            log_warning "Le consumer ne s'est pas arrêté, forçage de l'arrêt..."
            kill -9 "$pid" 2>/dev/null
        fi
        rm -f "$PID_FILE"
        log_success "Consumer arrêté"
    fi
}

# Fonction pour démarrer le consumer
start_consumer() {
    log "Démarrage du consumer Messenger (Environnement: $ENVIRONMENT)..."

    # Vérifier que nous sommes dans le bon répertoire
    if [ ! -f "bin/console" ]; then
        log_error "Le fichier bin/console n'existe pas. Assurez-vous d'être dans le répertoire racine du projet."
        exit 1
    fi

    # Vérifier que PHP est disponible
    if ! command -v "$PHP_BIN" > /dev/null 2>&1; then
        log_error "PHP n'est pas installé ou n'est pas dans le PATH. Vérifiez l'installation de PHP."
        exit 1
    fi

    # Vérifier que la base de données est accessible
    if ! $PHP_BIN bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; then
        log_error "Impossible de se connecter à la base de données. Vérifiez la configuration."
        exit 1
    fi

    # Vérifier les permissions
    if [ ! -w "$(dirname "$LOG_FILE")" ]; then
        log_error "Pas de permission d'écriture sur le répertoire de logs: $(dirname "$LOG_FILE")"
        exit 1
    fi

    # Démarrer le consumer selon l'environnement
    log "Exécution de la commande: $CONSUMER_COMMAND"

    if [ "$ENVIRONMENT" = "dedicated" ] && [ "$USE_SUDO" = true ] && [ "$(whoami)" != "$USER" ]; then
        # Serveur dédié avec sudo
        log "Démarrage avec l'utilisateur: $USER"
        sudo -u "$USER" nohup $CONSUMER_COMMAND >> "$LOG_FILE" 2>&1 &
        local consumer_pid=$!
    else
        # Serveur partagé ou utilisateur actuel
        log "Démarrage avec l'utilisateur actuel: $(whoami)"
        nohup $CONSUMER_COMMAND >> "$LOG_FILE" 2>&1 &
        local consumer_pid=$!
    fi

    # Sauvegarder le PID
    echo "$consumer_pid" > "$PID_FILE"

    # Attendre un peu pour vérifier que le processus démarre correctement
    sleep 3

    if ps -p "$consumer_pid" > /dev/null 2>&1; then
        log_success "Consumer démarré avec succès (PID: $consumer_pid, Environnement: $ENVIRONMENT)"
        return 0
    else
        log_error "Échec du démarrage du consumer"
        rm -f "$PID_FILE"
        return 1
    fi
}

# Fonction pour surveiller le consumer
monitor_consumer() {
    local restart_count=0
    local max_restarts=10

    log "Surveillance du consumer démarrée..."

    while true; do
        if ! is_consumer_running; then
            restart_count=$((restart_count + 1))

            if [ $restart_count -gt $max_restarts ]; then
                log_error "Nombre maximum de redémarrages atteint ($max_restarts). Arrêt de la surveillance."
                break
            fi

            log_warning "Consumer arrêté détecté. Redémarrage #$restart_count..."

            if start_consumer; then
                restart_count=0  # Reset le compteur si le redémarrage réussit
            else
                log_error "Échec du redémarrage #$restart_count"
            fi
        fi

        # Attendre 30 secondes avant la prochaine vérification
        sleep 30
    done
}

# Fonction pour afficher l'aide
show_help() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  start     Démarrer le consumer (défaut)"
    echo "  stop      Arrêter le consumer"
    echo "  restart   Redémarrer le consumer"
    echo "  status    Afficher le statut du consumer"
    echo "  monitor   Démarrer la surveillance automatique"
    echo "  logs      Afficher les logs du consumer"
    echo "  help      Afficher cette aide"
    echo ""
    echo "Exemples:"
    echo "  $0 start          # Démarrer le consumer"
    echo "  $0 monitor        # Démarrer avec surveillance automatique"
    echo "  $0 status         # Vérifier le statut"
    echo "  $0 logs           # Voir les logs"
}

# Fonction pour afficher le statut
show_status() {
    if is_consumer_running; then
        local pid=$(cat "$PID_FILE")
        log_success "Consumer en cours d'exécution (PID: $pid)"

        # Afficher les informations du processus
        echo ""
        echo "Informations du processus:"
        ps -p "$pid" -o pid,ppid,cmd,etime,pcpu,pmem
    else
        log_warning "Consumer non démarré"
    fi
}

# Fonction pour afficher les logs
show_logs() {
    if [ -f "$LOG_FILE" ]; then
        echo "=== Derniers logs du consumer ==="
        tail -n 50 "$LOG_FILE"
    else
        log_warning "Fichier de log non trouvé: $LOG_FILE"
    fi
}

# Gestion des signaux
trap 'log "Signal reçu, arrêt du consumer..."; stop_consumer; exit 0' SIGINT SIGTERM

# Créer le répertoire de logs s'il n'existe pas
mkdir -p "$(dirname "$LOG_FILE")"

# Traitement des arguments
case "${1:-start}" in
    "start")
        if is_consumer_running; then
            log_warning "Consumer déjà en cours d'exécution"
            show_status
        else
            start_consumer
        fi
        ;;
    "stop")
        stop_consumer
        ;;
    "restart")
        stop_consumer
        sleep 2
        start_consumer
        ;;
    "status")
        show_status
        ;;
    "monitor")
        if is_consumer_running; then
            log_warning "Consumer déjà en cours d'exécution"
            show_status
        else
            start_consumer
        fi
        monitor_consumer
        ;;
    "logs")
        show_logs
        ;;
    "help"|"-h"|"--help")
        show_help
        ;;
    *)
        log_error "Option inconnue: $1"
        show_help
        exit 1
        ;;
esac
