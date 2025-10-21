# Consumer Messenger MYLOCCA - Guide Serveur Partagé

Ce guide explique comment installer et configurer le consumer Messenger pour MYLOCCA sur un serveur partagé (hébergement mutualisé).

## 📋 Prérequis

- Serveur partagé avec accès SSH ou terminal
- PHP 7.4+ avec extensions : mysql, xml, mbstring, curl, zip, gd, intl, bcmath
- Accès à la configuration Cron (cPanel/Plesk)
- Symfony Messenger installé
- **PAS de droits root** (normal sur serveur partagé)

## 🚀 Installation Rapide

### 1. Installation automatique (recommandée)

```bash
# Télécharger et exécuter le script d'installation
wget https://votre-domaine.com/install-consumer-shared.sh
chmod +x install-consumer-shared.sh
./install-consumer-shared.sh
```

### 2. Installation manuelle

```bash
# 1. Configurer l'environnement
chmod +x configure-consumer-linux.sh
./configure-consumer-linux.sh

# 2. Créer les scripts adaptés au serveur partagé
# (Les scripts sont créés automatiquement)
```

## ⚙️ Configuration Cron (OBLIGATOIRE)

Sur un serveur partagé, vous devez utiliser Cron au lieu de systemd.

### Via cPanel

1. **Connectez-vous à votre cPanel**
2. **Allez dans "Cron Jobs"**
3. **Ajoutez ces tâches :**

#### Tâche 1 : Consumer Principal
```
Commande: /home/Lokaprot/myloccahome/cron-consumer.sh
Minute: */5
Heure: *
Jour: *
Mois: *
Jour de la semaine: *
```

#### Tâche 2 : Surveillance
```
Commande: /home/Lokaprot/myloccahome/monitor-consumer-shared.sh
Minute: 0
Heure: */2
Jour: *
Mois: *
Jour de la semaine: *
```

### Via Plesk

1. **Connectez-vous à votre Plesk**
2. **Allez dans "Tâches planifiées"**
3. **Créez les mêmes tâches que ci-dessus**

### Via ligne de commande (si disponible)

```bash
# Ajouter les tâches Cron
crontab -e

# Ajouter ces lignes :
*/5 * * * * /home/Lokaprot/myloccahome/cron-consumer.sh
0 */2 * * * /home/Lokaprot/myloccahome/monitor-consumer-shared.sh
```

## 🔧 Utilisation

### Commandes de base

```bash
# Démarrer le consumer manuellement
./start-consumer-shared.sh start

# Arrêter le consumer
./start-consumer-shared.sh stop

# Redémarrer le consumer
./start-consumer-shared.sh restart

# Vérifier le statut
./start-consumer-shared.sh status

# Surveillance continue (pour tests)
./start-consumer-shared.sh monitor
```

### Scripts créés

1. **`start-consumer-shared.sh`** - Démarrage manuel
2. **`cron-consumer.sh`** - Démarrage via Cron
3. **`monitor-consumer-shared.sh`** - Surveillance

## 📊 Monitoring

### Logs disponibles

```bash
# Logs du consumer
tail -f var/log/consumer.log

# Logs de Cron
tail -f var/log/cron-consumer.log

# Logs de surveillance
tail -f var/log/consumer-monitor.log

# Logs Symfony
tail -f var/log/dev.log
```

### Vérification du fonctionnement

```bash
# Vérifier que Cron fonctionne
ls -la var/consumer.pid

# Vérifier les processus PHP
ps aux | grep php

# Vérifier les tâches Cron
crontab -l
```

## 🧪 Tests

### Test du consumer

```bash
# Tester l'envoi d'un message
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

#### 1. Consumer ne démarre pas

```bash
# Vérifier les permissions
ls -la start-consumer-shared.sh

# Tester manuellement
./start-consumer-shared.sh start

# Vérifier les logs
tail -f var/log/consumer.log
```

#### 2. Cron ne fonctionne pas

```bash
# Vérifier que Cron est configuré
crontab -l

# Vérifier les logs de Cron
tail -f var/log/cron-consumer.log

# Tester le script Cron manuellement
./cron-consumer.sh
```

#### 3. Messages non traités

```bash
# Vérifier que les tâches sont actives
php bin/console app:tasks:activate-all

# Vérifier la configuration Messenger
cat config/packages/messenger.yaml

# Vérifier la base de données
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM messenger_messages"
```

#### 4. Problèmes de permissions

```bash
# Corriger les permissions
chmod +x start-consumer-shared.sh
chmod +x cron-consumer.sh
chmod +x monitor-consumer-shared.sh
chmod -R 775 var/
```

### Logs utiles

```bash
# Logs du consumer
tail -f var/log/consumer.log

# Logs de Cron
tail -f var/log/cron-consumer.log

# Logs de surveillance
tail -f var/log/consumer-monitor.log

# Logs Symfony
tail -f var/log/dev.log
```

## 📈 Optimisation

### Performance sur serveur partagé

```bash
# Optimiser PHP pour le consumer
php -i | grep memory_limit
php -i | grep max_execution_time

# Ajuster les limites dans les scripts si nécessaire
nano start-consumer-shared.sh
# Modifier : --memory-limit=128 --time-limit=300
```

### Surveillance avancée

```bash
# Script de surveillance personnalisé
cat > monitor-custom.sh << 'EOF'
#!/bin/bash
PROJECT_DIR="/home/Lokaprot/myloccahome"
LOG_FILE="$PROJECT_DIR/var/log/custom-monitor.log"

echo "[$(date)] Vérification du consumer..." >> "$LOG_FILE"

# Vérifier si le consumer est actif
if [ -f "$PROJECT_DIR/var/consumer.pid" ]; then
    pid=$(cat "$PROJECT_DIR/var/consumer.pid")
    if ps -p "$pid" > /dev/null 2>&1; then
        echo "[$(date)] Consumer actif (PID: $pid)" >> "$LOG_FILE"
    else
        echo "[$(date)] Consumer inactif, redémarrage..." >> "$LOG_FILE"
        cd "$PROJECT_DIR"
        ./start-consumer-shared.sh start
    fi
else
    echo "[$(date)] Consumer non démarré, démarrage..." >> "$LOG_FILE"
    cd "$PROJECT_DIR"
    ./start-consumer-shared.sh start
fi
EOF

chmod +x monitor-custom.sh
```

## 🔒 Sécurité

### Configuration sécurisée

```bash
# Limiter l'accès aux fichiers sensibles
chmod 600 .env.local 2>/dev/null || true
chmod 600 .env 2>/dev/null || true

# Vérifier les permissions
ls -la .env*
```

### Sauvegarde

```bash
# Script de sauvegarde simple
cat > backup-consumer.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backup-consumer"

mkdir -p "$BACKUP_DIR"

# Sauvegarder les scripts
cp start-consumer-shared.sh "$BACKUP_DIR/"
cp cron-consumer.sh "$BACKUP_DIR/"
cp monitor-consumer-shared.sh "$BACKUP_DIR/"

# Sauvegarder les logs
tar -czf "$BACKUP_DIR/logs-$DATE.tar.gz" var/log/

echo "Sauvegarde terminée: $BACKUP_DIR"
EOF

chmod +x backup-consumer.sh
```

## 📞 Support

### Vérifications avant support

1. **Cron configuré ?** : `crontab -l`
2. **Scripts exécutables ?** : `ls -la *.sh`
3. **Logs récents ?** : `tail -f var/log/consumer.log`
4. **Test manuel ?** : `./start-consumer-shared.sh start`

### Informations à fournir

```bash
# Informations système
php -v
whoami
pwd
ls -la

# Configuration Cron
crontab -l

# Logs récents
tail -n 50 var/log/consumer.log
tail -n 50 var/log/cron-consumer.log
```

## 📚 Ressources

- [Documentation Symfony Messenger](https://symfony.com/doc/current/messenger.html)
- [Guide Cron cPanel](https://docs.cpanel.net/cpanel/advanced/cron-jobs/)
- [Guide Cron Plesk](https://docs.plesk.com/en-US/obsidian/administrator-guide/server-administration/scheduled-tasks.77925/)

## ⚠️ Limitations serveur partagé

- **Pas de systemd** : Utilisation de Cron obligatoire
- **Limites de ressources** : Mémoire et CPU limités
- **Pas de root** : Permissions limitées
- **Timeouts** : Scripts limités dans le temps
- **Pas de redémarrage automatique** : Surveillance via Cron

---

**Note :** Adaptez les chemins selon votre configuration spécifique. Le consumer fonctionne différemment sur serveur partagé mais reste efficace avec Cron.
