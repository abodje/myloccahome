#!/bin/bash

# Script de configuration du consumer Messenger pour différents environnements Linux
# Supporte Ubuntu, CentOS, Debian, et autres distributions

set -e

# Configuration par défaut
DEFAULT_PROJECT_DIR="/home/Lokaprot/public_html"
DEFAULT_USER="www-data"
DEFAULT_GROUP="www-data"
DEFAULT_PHP_BIN="php"

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

# Fonction pour détecter l'environnement
detect_environment() {
    log "Détection de l'environnement..."

    # Détecter la distribution
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        DISTRO=$ID
        VERSION=$VERSION_ID
    else
        log_error "Impossible de détecter la distribution Linux"
        exit 1
    fi

    # Détecter le serveur web
    if systemctl is-active --quiet apache2 2>/dev/null; then
        WEB_SERVER="apache2"
        WEB_USER="www-data"
        WEB_GROUP="www-data"
    elif systemctl is-active --quiet nginx 2>/dev/null; then
        WEB_SERVER="nginx"
        WEB_USER="www-data"
        WEB_GROUP="www-data"
    elif systemctl is-active --quiet httpd 2>/dev/null; then
        WEB_SERVER="httpd"
        WEB_USER="apache"
        WEB_GROUP="apache"
    else
        log_warning "Aucun serveur web détecté"
        WEB_SERVER="unknown"
        WEB_USER="www-data"
        WEB_GROUP="www-data"
    fi

    # Détecter PHP
    if command -v php8.1 > /dev/null 2>&1; then
        PHP_BIN="php8.1"
    elif command -v php8.0 > /dev/null 2>&1; then
        PHP_BIN="php8.0"
    elif command -v php7.4 > /dev/null 2>&1; then
        PHP_BIN="php7.4"
    elif command -v php > /dev/null 2>&1; then
        PHP_BIN="php"
    else
        log_error "PHP n'est pas installé"
        exit 1
    fi

    # Détecter le répertoire du projet
    if [ -d "/var/www/html" ]; then
        PROJECT_DIR="/var/www/html"
    elif [ -d "/home/Lokaprot/public_html" ]; then
        PROJECT_DIR="/home/Lokaprot/public_html"
    elif [ -d "/var/www" ]; then
        PROJECT_DIR="/var/www"
    else
        PROJECT_DIR="$DEFAULT_PROJECT_DIR"
    fi

    log "Distribution: $DISTRO $VERSION"
    log "Serveur web: $WEB_SERVER"
    log "Utilisateur web: $WEB_USER"
    log "Groupe web: $WEB_GROUP"
    log "PHP: $PHP_BIN"
    log "Répertoire projet: $PROJECT_DIR"
}

# Fonction pour adapter la configuration selon l'environnement
adapt_configuration() {
    log "Adaptation de la configuration..."

    # Adapter le script start-consumer.sh
    sed -i "s|USER=\"www-data\"|USER=\"$WEB_USER\"|g" "$PROJECT_DIR/start-consumer.sh"
    sed -i "s|GROUP=\"www-data\"|GROUP=\"$WEB_GROUP\"|g" "$PROJECT_DIR/start-consumer.sh"
    sed -i "s|PHP_BIN=\"php\"|PHP_BIN=\"$PHP_BIN\"|g" "$PROJECT_DIR/start-consumer.sh"

    # Adapter le service systemd
    sed -i "s|WorkingDirectory=.*|WorkingDirectory=$PROJECT_DIR|g" "$PROJECT_DIR/mylocca-consumer.service"
    sed -i "s|User=.*|User=$WEB_USER|g" "$PROJECT_DIR/mylocca-consumer.service"
    sed -i "s|Group=.*|Group=$WEB_GROUP|g" "$PROJECT_DIR/mylocca-consumer.service"
    sed -i "s|ExecStart=.*|ExecStart=/usr/bin/$PHP_BIN bin/console messenger:consume async --time-limit=0 --memory-limit=256 --sleep=5|g" "$PROJECT_DIR/mylocca-consumer.service"

    # Adapter les permissions selon l'environnement
    case $DISTRO in
        ubuntu|debian)
            # Ubuntu/Debian utilise www-data
            chown -R "$WEB_USER:$WEB_GROUP" "$PROJECT_DIR"
            ;;
        centos|rhel|fedora)
            # CentOS/RHEL utilise apache
            chown -R "$WEB_USER:$WEB_GROUP" "$PROJECT_DIR"
            ;;
        *)
            log_warning "Distribution non reconnue, utilisation des permissions par défaut"
            ;;
    esac

    log_success "Configuration adaptée pour $DISTRO"
}

# Fonction pour optimiser les performances selon l'environnement
optimize_performance() {
    log "Optimisation des performances..."

    # Configuration PHP pour les performances
    PHP_INI_FILE="/etc/php/$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')/cli/php.ini"

    if [ -f "$PHP_INI_FILE" ]; then
        # Sauvegarder la configuration originale
        cp "$PHP_INI_FILE" "$PHP_INI_FILE.backup.$(date +%Y%m%d_%H%M%S)"

        # Optimisations pour le consumer
        sed -i 's/memory_limit = .*/memory_limit = 512M/' "$PHP_INI_FILE"
        sed -i 's/max_execution_time = .*/max_execution_time = 0/' "$PHP_INI_FILE"
        sed -i 's/opcache.enable=.*/opcache.enable=1/' "$PHP_INI_FILE"
        sed -i 's/opcache.memory_consumption=.*/opcache.memory_consumption=128/' "$PHP_INI_FILE"

        log_success "Configuration PHP optimisée"
    else
        log_warning "Fichier php.ini CLI non trouvé: $PHP_INI_FILE"
    fi

    # Configuration systemd pour les performances
    SYSTEMD_OVERRIDE="/etc/systemd/system/mylocca-consumer.service.d/override.conf"
    mkdir -p "$(dirname "$SYSTEMD_OVERRIDE")"

    cat > "$SYSTEMD_OVERRIDE" << EOF
[Service]
# Optimisations pour le consumer
LimitNOFILE=65536
MemoryLimit=512M
CPUQuota=200%

# Variables d'environnement
Environment=OPCACHE_ENABLE=1
Environment=PHP_MEMORY_LIMIT=512M
EOF

    systemctl daemon-reload
    log_success "Configuration systemd optimisée"
}

# Fonction pour configurer la surveillance
setup_monitoring() {
    log "Configuration de la surveillance..."

    # Script de surveillance adapté
    cat > "$PROJECT_DIR/monitor-consumer.sh" << EOF
#!/bin/bash

# Script de monitoring du consumer MYLOCCA
# Adapté pour l'environnement détecté

PROJECT_DIR="$PROJECT_DIR"
SERVICE_NAME="mylocca-consumer"
LOG_FILE="\$PROJECT_DIR/var/log/consumer-monitor.log"
WEB_USER="$WEB_USER"

log() {
    echo "[\$(date '+%Y-%m-%d %H:%M:%S')] \$1" >> "\$LOG_FILE"
}

# Vérifier si le service est actif
if ! systemctl is-active --quiet "\$SERVICE_NAME"; then
    log "ERREUR: Service \$SERVICE_NAME inactif, redémarrage..."
    systemctl start "\$SERVICE_NAME"

    if systemctl is-active --quiet "\$SERVICE_NAME"; then
        log "SUCCESS: Service \$SERVICE_NAME redémarré avec succès"
    else
        log "ERREUR: Échec du redémarrage du service \$SERVICE_NAME"
        # Envoyer une alerte par email si possible
        echo "Service \$SERVICE_NAME en panne sur \$(hostname)" | mail -s "ALERTE: Consumer MYLOCCA" admin@example.com 2>/dev/null || true
    fi
else
    log "INFO: Service \$SERVICE_NAME actif"
fi

# Vérifier l'utilisation mémoire
MEMORY_USAGE=\$(ps -o pid,ppid,cmd,%mem,%cpu --sort=-%mem -C $PHP_BIN | head -2 | tail -1 | awk '{print \$4}')
if (( \$(echo "\$MEMORY_USAGE > 80" | bc -l) )); then
    log "AVERTISSEMENT: Utilisation mémoire élevée: \${MEMORY_USAGE}%"
fi

# Vérifier les logs d'erreur
if [ -f "\$PROJECT_DIR/var/log/consumer.log" ]; then
    ERROR_COUNT=\$(tail -n 100 "\$PROJECT_DIR/var/log/consumer.log" | grep -c "ERROR" || true)
    if [ "\$ERROR_COUNT" -gt 10 ]; then
        log "AVERTISSEMENT: Nombre élevé d'erreurs détectées: \$ERROR_COUNT"
    fi
fi
EOF

    chmod +x "$PROJECT_DIR/monitor-consumer.sh"
    chown "$WEB_USER:$WEB_GROUP" "$PROJECT_DIR/monitor-consumer.sh"

    log_success "Surveillance configurée"
}

# Fonction pour créer un script de maintenance
create_maintenance_script() {
    log "Création du script de maintenance..."

    cat > "$PROJECT_DIR/maintain-consumer.sh" << EOF
#!/bin/bash

# Script de maintenance du consumer MYLOCCA
# À exécuter quotidiennement

PROJECT_DIR="$PROJECT_DIR"
SERVICE_NAME="mylocca-consumer"
WEB_USER="$WEB_USER"
WEB_GROUP="$WEB_GROUP"

echo "=== Maintenance du Consumer MYLOCCA ==="
echo "Date: \$(date)"
echo ""

# Nettoyer les logs anciens
echo "Nettoyage des logs anciens..."
find "\$PROJECT_DIR/var/log" -name "*.log" -mtime +30 -delete 2>/dev/null || true
echo "✅ Logs anciens nettoyés"

# Vérifier l'espace disque
echo ""
echo "Vérification de l'espace disque..."
df -h "\$PROJECT_DIR" | tail -1 | awk '{print "Espace utilisé: " \$3 "/" \$2 " (" \$5 ")"}'

# Redémarrer le service si nécessaire
echo ""
echo "Vérification du service..."
if systemctl is-active --quiet "\$SERVICE_NAME"; then
    echo "✅ Service actif"
else
    echo "⚠️ Service inactif, redémarrage..."
    systemctl start "\$SERVICE_NAME"
fi

# Vérifier les permissions
echo ""
echo "Vérification des permissions..."
chown -R "\$WEB_USER:\$WEB_GROUP" "\$PROJECT_DIR/var"
echo "✅ Permissions vérifiées"

# Statistiques du service
echo ""
echo "=== Statistiques du service ==="
systemctl status "\$SERVICE_NAME" --no-pager -l

echo ""
echo "=== Maintenance terminée ==="
EOF

    chmod +x "$PROJECT_DIR/maintain-consumer.sh"
    chown "$WEB_USER:$WEB_GROUP" "$PROJECT_DIR/maintain-consumer.sh"

    log_success "Script de maintenance créé"
}

# Fonction pour afficher le résumé de configuration
show_configuration_summary() {
    log_success "Configuration terminée !"
    echo ""
    echo "=== Résumé de la Configuration ==="
    echo ""
    echo "Environnement détecté:"
    echo "  - Distribution: $DISTRO $VERSION"
    echo "  - Serveur web: $WEB_SERVER"
    echo "  - Utilisateur: $WEB_USER"
    echo "  - Groupe: $WEB_GROUP"
    echo "  - PHP: $PHP_BIN"
    echo "  - Répertoire: $PROJECT_DIR"
    echo ""
    echo "Fichiers créés/modifiés:"
    echo "  - $PROJECT_DIR/start-consumer.sh (adapté)"
    echo "  - $PROJECT_DIR/mylocca-consumer.service (adapté)"
    echo "  - $PROJECT_DIR/monitor-consumer.sh (créé)"
    echo "  - $PROJECT_DIR/maintain-consumer.sh (créé)"
    echo ""
    echo "=== Prochaines étapes ==="
    echo "1. Installer le service systemd:"
    echo "   sudo cp $PROJECT_DIR/mylocca-consumer.service /etc/systemd/system/"
    echo "   sudo systemctl daemon-reload"
    echo "   sudo systemctl enable mylocca-consumer"
    echo ""
    echo "2. Démarrer le service:"
    echo "   sudo systemctl start mylocca-consumer"
    echo ""
    echo "3. Configurer Cron pour la surveillance:"
    echo "   sudo crontab -e"
    echo "   Ajouter: */5 * * * * $PROJECT_DIR/monitor-consumer.sh"
    echo ""
    echo "4. Configurer Cron pour la maintenance:"
    echo "   sudo crontab -e"
    echo "   Ajouter: 0 2 * * * $PROJECT_DIR/maintain-consumer.sh"
    echo ""
    echo "=== Commandes utiles ==="
    echo "Statut:     systemctl status mylocca-consumer"
    echo "Logs:       journalctl -u mylocca-consumer -f"
    echo "Redémarrer: systemctl restart mylocca-consumer"
    echo "Test:       cd $PROJECT_DIR && $PHP_BIN bin/console app:messenger:dispatch-task --task-type=TEST_EMAIL_SETTINGS"
    echo ""
}

# Fonction principale
main() {
    log "=== Configuration du Consumer MYLOCCA pour Linux ==="

    detect_environment
    adapt_configuration
    optimize_performance
    setup_monitoring
    create_maintenance_script
    show_configuration_summary

    log_success "Configuration complète !"
}

# Exécution
main "$@"
