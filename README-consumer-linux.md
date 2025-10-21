# Consumer Messenger MYLOCCA - Guide Linux

Ce guide explique comment installer et configurer le consumer Messenger pour MYLOCCA sur un serveur Linux.

## 📋 Prérequis

- Serveur Linux (Ubuntu 20.04+, CentOS 7+, Debian 10+)
- PHP 7.4+ avec extensions : mysql, xml, mbstring, curl, zip, gd, intl, bcmath
- MySQL/MariaDB
- Accès root ou sudo
- Symfony Messenger installé

## 🚀 Installation Rapide

### 1. Installation automatique (recommandée)

```bash
# Télécharger et exécuter le script d'installation
wget https://votre-domaine.com/install-consumer-linux.sh
chmod +x install-consumer-linux.sh
sudo ./install-consumer-linux.sh
```

### 2. Installation manuelle

```bash
# 1. Configurer l'environnement
chmod +x configure-consumer-linux.sh
sudo ./configure-consumer-linux.sh

# 2. Installer le service systemd
sudo cp mylocca-consumer.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable mylocca-consumer

# 3. Démarrer le service
sudo systemctl start mylocca-consumer
```

## ⚙️ Configuration

### Variables d'environnement

Modifiez le fichier de service `/etc/systemd/system/mylocca-consumer.service` :

```ini
[Unit]
Description=MYLOCCA Messenger Consumer
After=network.target mysql.service

[Service]
Type=simple
User=www-data                    # Adaptez selon votre configuration
Group=www-data                   # Adaptez selon votre configuration
WorkingDirectory=/home/Lokaprot/myloccahome  # Chemin vers votre projet
ExecStart=/usr/bin/php bin/console messenger:consume async --time-limit=0 --memory-limit=256 --sleep=5
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

### Permissions

```bash
# Configurer les permissions
sudo chown -R www-data:www-data /home/Lokaprot/myloccahome
sudo chmod -R 755 /home/Lokaprot/myloccahome
sudo chmod -R 775 /home/Lokaprot/myloccahome/var
```

## 🔧 Utilisation

### Commandes de base

```bash
# Démarrer le service
sudo systemctl start mylocca-consumer

# Arrêter le service
sudo systemctl stop mylocca-consumer

# Redémarrer le service
sudo systemctl restart mylocca-consumer

# Vérifier le statut
sudo systemctl status mylocca-consumer

# Voir les logs en temps réel
sudo journalctl -u mylocca-consumer -f

# Voir les logs du consumer
tail -f /home/Lokaprot/myloccahome/var/log/consumer.log
```

### Scripts utilitaires

```bash
# Script de démarrage manuel
./start-consumer.sh start

# Script avec surveillance automatique
./start-consumer.sh monitor

# Vérifier le statut
./start-consumer.sh status

# Voir les logs
./start-consumer.sh logs
```

## 📊 Monitoring

### Surveillance automatique

Le script `monitor-consumer.sh` vérifie automatiquement le service :

```bash
# Exécuter manuellement
./monitor-consumer.sh

# Configurer Cron (surveillance toutes les 5 minutes)
sudo crontab -e
# Ajouter cette ligne :
*/5 * * * * /home/Lokaprot/myloccahome/monitor-consumer.sh
```

### Maintenance quotidienne

```bash
# Exécuter la maintenance
./maintain-consumer.sh

# Configurer Cron (maintenance quotidienne à 2h du matin)
sudo crontab -e
# Ajouter cette ligne :
0 2 * * * /home/Lokaprot/myloccahome/maintain-consumer.sh
```

## 🧪 Tests

### Test du consumer

```bash
# Tester l'envoi d'un message
cd /home/Lokaprot/myloccahome
php bin/console app:messenger:dispatch-task --task-type=TEST_EMAIL_SETTINGS --email=votre@email.com

# Vérifier que le message est traité
tail -f var/log/consumer.log
```

### Test des tâches

```bash
# Lister les tâches disponibles
php bin/console app:messenger:list-tasks

# Tester une tâche spécifique
php bin/console app:messenger:dispatch-task --task-type=GENERATE_RENTS --logDetails
```

## 🔍 Dépannage

### Problèmes courants

#### 1. Service ne démarre pas

```bash
# Vérifier les logs
sudo journalctl -u mylocca-consumer -n 50

# Vérifier les permissions
ls -la /home/Lokaprot/myloccahome/bin/console

# Tester manuellement
cd /home/Lokaprot/myloccahome
sudo -u www-data php bin/console messenger:consume async --time-limit=60
```

#### 2. Messages non traités

```bash
# Vérifier que les tâches sont actives
php bin/console app:tasks:activate-all

# Vérifier la configuration Messenger
cat config/packages/messenger.yaml

# Vérifier la base de données
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM messenger_messages"
```

#### 3. Problèmes de permissions

```bash
# Corriger les permissions
sudo chown -R www-data:www-data /home/Lokaprot/myloccahome
sudo chmod -R 775 /home/Lokaprot/myloccahome/var
sudo chmod +x /home/Lokaprot/myloccahome/start-consumer.sh
```

#### 4. Consommation mémoire élevée

```bash
# Vérifier l'utilisation mémoire
ps aux | grep php

# Redémarrer le service
sudo systemctl restart mylocca-consumer

# Ajuster les limites dans le service
sudo systemctl edit mylocca-consumer
# Ajouter :
# [Service]
# MemoryLimit=256M
```

### Logs utiles

```bash
# Logs du service systemd
sudo journalctl -u mylocca-consumer -f

# Logs du consumer
tail -f /home/Lokaprot/myloccahome/var/log/consumer.log

# Logs de surveillance
tail -f /home/Lokaprot/myloccahome/var/log/consumer-monitor.log

# Logs Symfony
tail -f /home/Lokaprot/myloccahome/var/log/dev.log
```

## 📈 Optimisation

### Performance

```bash
# Optimiser PHP pour le consumer
sudo nano /etc/php/8.1/cli/php.ini
# Modifier :
# memory_limit = 512M
# max_execution_time = 0
# opcache.enable = 1
```

### Surveillance avancée

```bash
# Installer htop pour surveiller les processus
sudo apt install htop  # Ubuntu/Debian
sudo yum install htop  # CentOS/RHEL

# Surveiller en temps réel
htop -p $(pgrep -f "messenger:consume")
```

## 🔒 Sécurité

### Configuration sécurisée

```bash
# Limiter l'accès aux fichiers sensibles
sudo chmod 600 /home/Lokaprot/myloccahome/.env.local
sudo chmod 600 /home/Lokaprot/myloccahome/.env

# Configurer un firewall
sudo ufw allow 22    # SSH
sudo ufw allow 80   # HTTP
sudo ufw allow 443  # HTTPS
sudo ufw enable
```

### Sauvegarde

```bash
# Script de sauvegarde
cat > backup-consumer.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/mylocca-consumer"
mkdir -p "$BACKUP_DIR"

# Sauvegarder la configuration
cp /etc/systemd/system/mylocca-consumer.service "$BACKUP_DIR/mylocca-consumer.service.$DATE"

# Sauvegarder les logs
tar -czf "$BACKUP_DIR/consumer-logs.$DATE.tar.gz" /home/Lokaprot/myloccahome/var/log/

echo "Sauvegarde terminée: $BACKUP_DIR"
EOF

chmod +x backup-consumer.sh
```

## 📞 Support

En cas de problème :

1. Vérifiez les logs : `sudo journalctl -u mylocca-consumer -f`
2. Testez manuellement : `./start-consumer.sh start`
3. Vérifiez les permissions : `ls -la bin/console`
4. Consultez la documentation Symfony Messenger

## 📚 Ressources

- [Documentation Symfony Messenger](https://symfony.com/doc/current/messenger.html)
- [Documentation systemd](https://systemd.io/)
- [Guide Cron](https://crontab.guru/)

---

**Note :** Adaptez les chemins et utilisateurs selon votre configuration spécifique.
