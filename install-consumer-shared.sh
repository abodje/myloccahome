#!/bin/bash

# Script d'installation du consumer Messenger pour MYLOCCA sur serveur partagé
# Compatible avec les hébergements partagés (sans droits root)

set -e

# Configuration pour serveur partagé
PROJECT_DIR="/home/Lokaprot/myloccahome"
SERVICE_NAME="mylocca-consumer"
USER="$(whoami)"
GROUP="$(id -gn)"

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction de logging
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

# Fonction pour vérifier l'environnement partagé
check_shared_hosting() {
    log "Vérification de l'environnement d'hébergement partagé..."

    # Vérifier que nous ne sommes pas root
    if [ "$EUID" -eq 0 ]; then
        log_warning "Vous êtes connecté en tant que root. Sur un serveur partagé, utilisez votre compte utilisateur."
        exit 1
    fi

    # Vérifier que nous sommes dans le bon répertoire
    if [ ! -f "bin/console" ]; then
        log_error "Le fichier bin/console n'existe pas. Assurez-vous d'être dans le répertoire racine du projet."
        exit 1
    fi

    # Vérifier PHP
    if ! command -v php > /dev/null 2>&1; then
        log_error "PHP n'est pas disponible. Contactez votre hébergeur."
        exit 1
    fi

    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    log "Version PHP: $PHP_VERSION"

    # Vérifier les extensions PHP nécessaires
    local required_extensions=("mysql" "xml" "mbstring" "curl" "zip" "gd" "intl" "bcmath")
    local missing_extensions=()

    for ext in "${required_extensions[@]}"; do
        if ! php -m | grep -q "^$ext$"; then
            missing_extensions+=("$ext")
        fi
    done

    if [ ${#missing_extensions[@]} -ne 0 ]; then
        log_error "Extensions PHP manquantes: ${missing_extensions[*]}"
        log "Contactez votre hébergeur pour installer ces extensions."
        exit 1
    fi

    log_success "Environnement vérifié"
}

# Fonction pour configurer les permissions (sans root)
setup_permissions() {
    log "Configuration des permissions..."

    # Créer les répertoires nécessaires
    mkdir -p "$PROJECT_DIR/var/log"
    mkdir -p "$PROJECT_DIR/var/cache"
    mkdir -p "$PROJECT_DIR/var/sessions"

    # Configurer les permissions (dans les limites du serveur partagé)
    chmod -R 755 "$PROJECT_DIR"
    chmod -R 775 "$PROJECT_DIR/var" 2>/dev/null || chmod -R 755 "$PROJECT_DIR/var"

    # Permissions spéciales pour les fichiers sensibles
    chmod 600 "$PROJECT_DIR/.env.local" 2>/dev/null || true
    chmod 600 "$PROJECT_DIR/.env" 2>/dev/null || true

    log_success "Permissions configurées"
}

# Fonction pour créer un script de démarrage adapté au serveur partagé
create_shared_startup_script() {
    log "Création du script de démarrage pour serveur partagé..."

    cat > "$PROJECT_DIR/start-consumer-shared.sh" << 'EOF'
#!/bin/bash

# Script de démarrage du consumer Messenger pour serveur partagé
# Ce script utilise Cron au lieu de systemd

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="$SCRIPT_DIR/var/log/consumer.log"
PID_FILE="$SCRIPT_DIR/var/consumer.pid"
LOCK_FILE="$SCRIPT_DIR/var/consumer.lock"

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

# Fonction pour vérifier si le consumer est en cours d'exécution
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

# Fonction pour démarrer le consumer
start_consumer() {
    log "Démarrage du consumer Messenger..."

    # Vérifier que nous sommes dans le bon répertoire
    if [ ! -f "bin/console" ]; then
        log_error "Le fichier bin/console n'existe pas."
        exit 1
    fi

    # Vérifier que la base de données est accessible
    if ! php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; then
        log_error "Impossible de se connecter à la base de données."
        exit 1
    fi

    # Démarrer le consumer avec une limite de temps pour éviter les timeouts
    log "Exécution de la commande: php bin/console messenger:consume async --time-limit=300 --memory-limit=128 --sleep=5"
    nohup php bin/console messenger:consume async --time-limit=300 --memory-limit=128 --sleep=5 >> "$LOG_FILE" 2>&1 &
    local consumer_pid=$!

    # Sauvegarder le PID
    echo "$consumer_pid" > "$PID_FILE"

    # Attendre un peu pour vérifier que le processus démarre correctement
    sleep 3

    if ps -p "$consumer_pid" > /dev/null 2>&1; then
        log_success "Consumer démarré avec succès (PID: $consumer_pid)"
        return 0
    else
        log_error "Échec du démarrage du consumer"
        rm -f "$PID_FILE"
        return 1
    fi
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

# Fonction pour surveiller le consumer
monitor_consumer() {
    local restart_count=0
    local max_restarts=5

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
                restart_count=0
            else
                log_error "Échec du redémarrage #$restart_count"
            fi
        fi

        # Attendre 30 secondes avant la prochaine vérification
        sleep 30
    done
}

# Traitement des arguments
case "${1:-start}" in
    "start")
        if is_consumer_running; then
            log_warning "Consumer déjà en cours d'exécution"
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
        if is_consumer_running; then
            local pid=$(cat "$PID_FILE")
            log_success "Consumer en cours d'exécution (PID: $pid)"
        else
            log_warning "Consumer non démarré"
        fi
        ;;
    "monitor")
        if is_consumer_running; then
            log_warning "Consumer déjà en cours d'exécution"
        else
            start_consumer
        fi
        monitor_consumer
        ;;
    *)
        echo "Usage: $0 [start|stop|restart|status|monitor]"
        exit 1
        ;;
esac
EOF

    chmod +x "$PROJECT_DIR/start-consumer-shared.sh"
    log_success "Script de démarrage créé"
}

# Fonction pour configurer Cron
setup_cron() {
    log "Configuration de Cron pour serveur partagé..."

    # Créer un script Cron qui démarre le consumer toutes les 5 minutes
    cat > "$PROJECT_DIR/cron-consumer.sh" << 'EOF'
#!/bin/bash

# Script Cron pour le consumer Messenger
# À exécuter toutes les 5 minutes

PROJECT_DIR="/home/Lokaprot/myloccahome"
LOG_FILE="$PROJECT_DIR/var/log/cron-consumer.log"
LOCK_FILE="$PROJECT_DIR/var/cron-consumer.lock"

# Vérifier si le script est déjà en cours d'exécution
if [ -f "$LOCK_FILE" ]; then
    # Vérifier si le processus est toujours actif
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

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Démarrage du consumer via Cron" >> "$LOG_FILE"

# Changer vers le répertoire du projet
cd "$PROJECT_DIR"

# Vérifier si le consumer est déjà en cours d'exécution
if [ -f "var/consumer.pid" ]; then
    local pid=$(cat "var/consumer.pid")
    if ps -p "$pid" > /dev/null 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Consumer déjà actif (PID: $pid)" >> "$LOG_FILE"
        exit 0
    else
        rm -f "var/consumer.pid"
    fi
fi

# Démarrer le consumer
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Démarrage du consumer..." >> "$LOG_FILE"
php bin/console messenger:consume async --time-limit=300 --memory-limit=128 --sleep=5 >> "var/log/consumer.log" 2>&1 &
local consumer_pid=$!

# Sauvegarder le PID
echo "$consumer_pid" > "var/consumer.pid"

# Attendre un peu pour vérifier que le processus démarre
sleep 3

if ps -p "$consumer_pid" > /dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Consumer démarré avec succès (PID: $consumer_pid)" >> "$LOG_FILE"
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Échec du démarrage du consumer" >> "$LOG_FILE"
    rm -f "var/consumer.pid"
fi
EOF

    chmod +x "$PROJECT_DIR/cron-consumer.sh"

    log_success "Script Cron créé"
    log_warning "IMPORTANT: Vous devez maintenant configurer Cron dans votre panneau d'administration :"
    log "Commande: $PROJECT_DIR/cron-consumer.sh"
    log "Fréquence: */5 * * * * (toutes les 5 minutes)"
}

# Fonction pour créer un script de monitoring
create_monitoring_script() {
    log "Création du script de monitoring..."

    cat > "$PROJECT_DIR/monitor-consumer-shared.sh" << 'EOF'
#!/bin/bash

# Script de monitoring du consumer pour serveur partagé

PROJECT_DIR="/home/Lokaprot/myloccahome"
LOG_FILE="$PROJECT_DIR/var/log/consumer-monitor.log"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Vérifier si le consumer est actif
if [ -f "$PROJECT_DIR/var/consumer.pid" ]; then
    local pid=$(cat "$PROJECT_DIR/var/consumer.pid")
    if ps -p "$pid" > /dev/null 2>&1; then
        log "INFO: Consumer actif (PID: $pid)"
    else
        log "ERREUR: Consumer inactif, redémarrage..."
        cd "$PROJECT_DIR"
        ./start-consumer-shared.sh start
    fi
else
    log "ERREUR: Consumer non démarré, démarrage..."
    cd "$PROJECT_DIR"
    ./start-consumer-shared.sh start
fi

# Vérifier l'utilisation mémoire
MEMORY_USAGE=$(ps -o pid,ppid,cmd,%mem,%cpu --sort=-%mem -C php | head -2 | tail -1 | awk '{print $4}')
if (( $(echo "$MEMORY_USAGE > 80" | bc -l) )); then
    log "AVERTISSEMENT: Utilisation mémoire élevée: ${MEMORY_USAGE}%"
fi

# Nettoyer les logs anciens
find "$PROJECT_DIR/var/log" -name "*.log" -mtime +7 -delete 2>/dev/null || true
EOF

    chmod +x "$PROJECT_DIR/monitor-consumer-shared.sh"
    log_success "Script de monitoring créé"
}

# Fonction pour tester l'installation
test_installation() {
    log "Test de l'installation..."

    # Tester le script de démarrage
    if ./start-consumer-shared.sh start; then
        log_success "Script de démarrage testé avec succès"

        # Attendre un peu et vérifier
        sleep 5
        if ./start-consumer-shared.sh status; then
            log_success "Consumer actif et stable"
        else
            log_warning "Consumer s'est arrêté après le démarrage"
        fi

        # Arrêter le consumer de test
        ./start-consumer-shared.sh stop
    else
        log_error "Échec du test du script de démarrage"
        return 1
    fi

    # Tester l'envoi d'un message
    if php bin/console app:messenger:dispatch-task --task-type=TEST_EMAIL_SETTINGS --email=test@example.com > /dev/null 2>&1; then
        log_success "Test d'envoi de message réussi"
    else
        log_warning "Test d'envoi de message échoué (normal si pas de configuration email)"
    fi

    log_success "Installation testée avec succès"
}

# Fonction pour afficher les instructions de configuration
show_configuration_instructions() {
    log_success "Installation terminée !"
    echo ""
    echo "=== Configuration du Consumer MYLOCCA (Serveur Partagé) ==="
    echo ""
    echo "Scripts créés:"
    echo "  - $PROJECT_DIR/start-consumer-shared.sh (démarrage manuel)"
    echo "  - $PROJECT_DIR/cron-consumer.sh (démarrage via Cron)"
    echo "  - $PROJECT_DIR/monitor-consumer-shared.sh (surveillance)"
    echo ""
    echo "=== Configuration Cron REQUISE ==="
    echo ""
    echo "1. Connectez-vous à votre panneau d'administration (cPanel/Plesk)"
    echo "2. Allez dans la section 'Cron Jobs' ou 'Tâches planifiées'"
    echo "3. Ajoutez cette tâche:"
    echo ""
    echo "   Commande: $PROJECT_DIR/cron-consumer.sh"
    echo "   Minute: */5"
    echo "   Heure: *"
    echo "   Jour: *"
    echo "   Mois: *"
    echo "   Jour de la semaine: *"
    echo ""
    echo "4. Ajoutez aussi une tâche de surveillance:"
    echo ""
    echo "   Commande: $PROJECT_DIR/monitor-consumer-shared.sh"
    echo "   Minute: 0"
    echo "   Heure: */2"
    echo "   Jour: *"
    echo "   Mois: *"
    echo "   Jour de la semaine: *"
    echo ""
    echo "=== Commandes utiles ==="
    echo "Démarrer manuellement:    ./start-consumer-shared.sh start"
    echo "Arrêter:                 ./start-consumer-shared.sh stop"
    echo "Statut:                  ./start-consumer-shared.sh status"
    echo "Surveillance:            ./start-consumer-shared.sh monitor"
    echo "Logs:                    tail -f var/log/consumer.log"
    echo ""
    echo "=== Test ==="
    echo "Tester l'envoi:          php bin/console app:messenger:dispatch-task --task-type=TEST_EMAIL_SETTINGS --email=votre@email.com"
    echo ""
    echo "⚠️  IMPORTANT: Le consumer ne fonctionnera que si vous configurez Cron !"
    echo ""
}

# Fonction principale
main() {
    log "=== Installation du Consumer MYLOCCA (Serveur Partagé) ==="

    check_shared_hosting
    setup_permissions
    create_shared_startup_script
    setup_cron
    create_monitoring_script
    test_installation
    show_configuration_instructions

    log_success "Installation complète ! Configurez maintenant Cron dans votre panneau d'administration."
}

# Exécution
main "$@"
