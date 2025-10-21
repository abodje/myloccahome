#!/bin/bash

# Script d'installation du consumer Messenger pour LOKAPRO sur serveur Linux
# Compatible Ubuntu/CentOS/Debian

set -e

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/home/Lokaprot/myloccahome"
SERVICE_NAME="mylocca-consumer"
SERVICE_USER="www-data"
SERVICE_GROUP="www-data"

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

# Fonction pour vérifier si le script est exécuté en tant que root
check_root() {
    if [ "$EUID" -ne 0 ]; then
        log_error "Ce script doit être exécuté en tant que root ou avec sudo"
        exit 1
    fi
}

# Fonction pour détecter la distribution Linux
detect_distro() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        DISTRO=$ID
        VERSION=$VERSION_ID
    else
        log_error "Impossible de détecter la distribution Linux"
        exit 1
    fi

    log "Distribution détectée: $DISTRO $VERSION"
}

# Fonction pour installer les dépendances
install_dependencies() {
    log "Installation des dépendances..."

    case $DISTRO in
        ubuntu|debian)
            apt-get update
            apt-get install -y php-cli php-mysql php-xml php-mbstring php-curl php-zip php-gd php-intl php-bcmath
            ;;
        centos|rhel|fedora)
            yum update -y
            yum install -y php-cli php-mysql php-xml php-mbstring php-curl php-zip php-gd php-intl php-bcmath
            ;;
        *)
            log_warning "Distribution non supportée: $DISTRO"
            log "Veuillez installer manuellement PHP et les extensions nécessaires"
            ;;
    esac

    log_success "Dépendances installées"
}

# Fonction pour vérifier PHP
check_php() {
    if ! command -v php > /dev/null 2>&1; then
        log_error "PHP n'est pas installé"
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
        log "Installez-les avec: apt-get install php-${missing_extensions[0]} php-${missing_extensions[1]} ..."
        exit 1
    fi

    log_success "PHP et extensions vérifiés"
}

# Fonction pour configurer les permissions
setup_permissions() {
    log "Configuration des permissions..."

    # Créer l'utilisateur et le groupe s'ils n'existent pas
    if ! id "$SERVICE_USER" > /dev/null 2>&1; then
        useradd -r -s /bin/false -d "$PROJECT_DIR" "$SERVICE_USER"
        log "Utilisateur $SERVICE_USER créé"
    fi

    # Configurer les permissions du projet
    chown -R "$SERVICE_USER:$SERVICE_GROUP" "$PROJECT_DIR"
    chmod -R 755 "$PROJECT_DIR"
    chmod -R 775 "$PROJECT_DIR/var"

    # Permissions spéciales pour les fichiers sensibles
    chmod 600 "$PROJECT_DIR/.env.local" 2>/dev/null || true
    chmod 600 "$PROJECT_DIR/.env" 2>/dev/null || true

    log_success "Permissions configurées"
}

# Fonction pour installer le service systemd
install_systemd_service() {
    log "Installation du service systemd..."

    # Copier le fichier de service
    cp "$PROJECT_DIR/mylocca-consumer.service" "/etc/systemd/system/$SERVICE_NAME.service"

    # Adapter le fichier de service selon l'environnement
    sed -i "s|WorkingDirectory=.*|WorkingDirectory=$PROJECT_DIR|g" "/etc/systemd/system/$SERVICE_NAME.service"
    sed -i "s|User=.*|User=$SERVICE_USER|g" "/etc/systemd/system/$SERVICE_NAME.service"
    sed -i "s|Group=.*|Group=$SERVICE_GROUP|g" "/etc/systemd/system/$SERVICE_NAME.service"

    # Recharger systemd
    systemctl daemon-reload

    # Activer le service pour qu'il démarre automatiquement
    systemctl enable "$SERVICE_NAME"

    log_success "Service systemd installé et activé"
}

# Fonction pour configurer les logs
setup_logging() {
    log "Configuration des logs..."

    # Créer le répertoire de logs
    mkdir -p "$PROJECT_DIR/var/log"
    chown -R "$SERVICE_USER:$SERVICE_GROUP" "$PROJECT_DIR/var/log"

    # Configuration logrotate pour les logs du consumer
    cat > "/etc/logrotate.d/mylocca-consumer" << EOF
$PROJECT_DIR/var/log/consumer.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 $SERVICE_USER $SERVICE_GROUP
    postrotate
        systemctl reload $SERVICE_NAME > /dev/null 2>&1 || true
    endscript
}
EOF

    log_success "Configuration des logs terminée"
}

# Fonction pour créer un script de monitoring
create_monitoring_script() {
    log "Création du script de monitoring..."

    cat > "$PROJECT_DIR/monitor-consumer.sh" << 'EOF'
#!/bin/bash

# Script de monitoring du consumer LOKAPRO
# À exécuter via Cron toutes les 5 minutes

PROJECT_DIR="/home/Lokaprot/myloccahome"
SERVICE_NAME="mylocca-consumer"
LOG_FILE="$PROJECT_DIR/var/log/consumer-monitor.log"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Vérifier si le service est actif
if ! systemctl is-active --quiet "$SERVICE_NAME"; then
    log "ERREUR: Service $SERVICE_NAME inactif, redémarrage..."
    systemctl start "$SERVICE_NAME"

    if systemctl is-active --quiet "$SERVICE_NAME"; then
        log "SUCCESS: Service $SERVICE_NAME redémarré avec succès"
    else
        log "ERREUR: Échec du redémarrage du service $SERVICE_NAME"
    fi
else
    log "INFO: Service $SERVICE_NAME actif"
fi

# Vérifier l'utilisation mémoire
MEMORY_USAGE=$(ps -o pid,ppid,cmd,%mem,%cpu --sort=-%mem -C php | head -2 | tail -1 | awk '{print $4}')
if (( $(echo "$MEMORY_USAGE > 80" | bc -l) )); then
    log "AVERTISSEMENT: Utilisation mémoire élevée: ${MEMORY_USAGE}%"
fi
EOF

    chmod +x "$PROJECT_DIR/monitor-consumer.sh"
    chown "$SERVICE_USER:$SERVICE_GROUP" "$PROJECT_DIR/monitor-consumer.sh"

    log_success "Script de monitoring créé"
}

# Fonction pour configurer Cron
setup_cron() {
    log "Configuration de Cron..."

    # Ajouter une tâche Cron pour le monitoring
    (crontab -u root -l 2>/dev/null; echo "*/5 * * * * $PROJECT_DIR/monitor-consumer.sh") | crontab -u root -

    # Ajouter une tâche Cron pour nettoyer les logs anciens
    (crontab -u root -l 2>/dev/null; echo "0 2 * * * find $PROJECT_DIR/var/log -name '*.log' -mtime +30 -delete") | crontab -u root -

    log_success "Configuration Cron terminée"
}

# Fonction pour tester l'installation
test_installation() {
    log "Test de l'installation..."

    # Vérifier que le service peut démarrer
    if systemctl start "$SERVICE_NAME"; then
        log_success "Service démarré avec succès"

        # Attendre un peu et vérifier qu'il est toujours actif
        sleep 5
        if systemctl is-active --quiet "$SERVICE_NAME"; then
            log_success "Service actif et stable"
        else
            log_error "Service s'est arrêté après le démarrage"
            systemctl status "$SERVICE_NAME"
            return 1
        fi
    else
        log_error "Échec du démarrage du service"
        systemctl status "$SERVICE_NAME"
        return 1
    fi

    # Tester l'envoi d'un message
    cd "$PROJECT_DIR"
    if sudo -u "$SERVICE_USER" php bin/console app:messenger:dispatch-task --task-type=TEST_EMAIL_SETTINGS --email=test@example.com > /dev/null 2>&1; then
        log_success "Test d'envoi de message réussi"
    else
        log_warning "Test d'envoi de message échoué (normal si pas de configuration email)"
    fi

    log_success "Installation testée avec succès"
}

# Fonction pour afficher les informations de configuration
show_configuration() {
    log_success "Installation terminée !"
    echo ""
    echo "=== Configuration du Consumer LOKAPRO ==="
    echo ""
    echo "Service systemd: $SERVICE_NAME"
    echo "Utilisateur: $SERVICE_USER"
    echo "Répertoire: $PROJECT_DIR"
    echo ""
    echo "=== Commandes utiles ==="
    echo "Démarrer le service:     systemctl start $SERVICE_NAME"
    echo "Arrêter le service:     systemctl stop $SERVICE_NAME"
    echo "Redémarrer le service:  systemctl restart $SERVICE_NAME"
    echo "Statut du service:      systemctl status $SERVICE_NAME"
    echo "Logs du service:        journalctl -u $SERVICE_NAME -f"
    echo "Logs du consumer:       tail -f $PROJECT_DIR/var/log/consumer.log"
    echo ""
    echo "=== Monitoring ==="
    echo "Script de monitoring:   $PROJECT_DIR/monitor-consumer.sh"
    echo "Logs de monitoring:     $PROJECT_DIR/var/log/consumer-monitor.log"
    echo ""
    echo "=== Test ==="
    echo "Tester l'envoi:         cd $PROJECT_DIR && php bin/console app:messenger:dispatch-task --task-type=TEST_EMAIL_SETTINGS --email=votre@email.com"
    echo ""
}

# Fonction principale
main() {
    log "=== Installation du Consumer LOKAPRO ==="

    check_root
    detect_distro
    install_dependencies
    check_php
    setup_permissions
    install_systemd_service
    setup_logging
    create_monitoring_script
    setup_cron
    test_installation
    show_configuration

    log_success "Installation complète ! Le consumer est maintenant opérationnel."
}

# Exécution
main "$@"
