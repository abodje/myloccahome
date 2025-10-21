# Guide de Déploiement Messenger - Production MySQL 8

Ce guide explique comment déployer et configurer le système Messenger sur votre serveur de production MySQL 8.

## 🚀 Déploiement Automatique

### 1. Télécharger et exécuter le script de déploiement

```bash
# Sur votre serveur de production
cd /home/lokaprot/myloccahome

# Télécharger le script (ou le copier depuis votre développement)
wget https://votre-domaine.com/deploy-messenger-prod.sh
chmod +x deploy-messenger-prod.sh

# Exécuter le déploiement
./deploy-messenger-prod.sh
```

### 2. Le script effectue automatiquement :

- ✅ Vérification MySQL 8
- ✅ Sauvegarde de la configuration actuelle
- ✅ Déploiement de la configuration production
- ✅ Création des tables Messenger
- ✅ Test de la configuration
- ✅ Création des scripts de démarrage
- ✅ Création du script Cron

## ⚙️ Configuration Cron (OBLIGATOIRE)

### Via cPanel

1. **Connectez-vous à votre cPanel**
2. **Allez dans "Cron Jobs"**
3. **Ajoutez cette tâche :**

```
Commande: /home/lokaprot/myloccahome/cron-messenger-worker.sh
Minute: */5
Heure: *
Jour: *
Mois: *
Jour de la semaine: *
```

### Via Plesk

1. **Connectez-vous à votre Plesk**
2. **Allez dans "Tâches planifiées"**
3. **Créez la même tâche que ci-dessus**

## 🔧 Utilisation

### Commandes de base

```bash
# Démarrer le worker manuellement
./start-messenger-worker.sh start

# Arrêter le worker
./start-messenger-worker.sh stop

# Redémarrer le worker
./start-messenger-worker.sh restart

# Vérifier le statut
./start-messenger-worker.sh status
```

### Surveillance

```bash
# Logs du worker
tail -f var/log/messenger-worker.log

# Logs de Cron
tail -f var/log/cron-messenger.log

# Statut des messages
php bin/console messenger:stats
```

## 🧪 Tests

### Test complet

```bash
# 1. Vérifier la configuration
php bin/console debug:messenger

# 2. Envoyer un message de test
php bin/console app:messenger:dispatch-task --task-type=TEST_EMAIL_SETTINGS --email=votre@email.com

# 3. Vérifier que le message est en queue
php bin/console messenger:stats

# 4. Démarrer le worker pour traiter
./start-messenger-worker.sh start

# 5. Vérifier que le message est traité
php bin/console messenger:stats

# 6. Vérifier les logs
tail -f var/log/messenger-worker.log
```

## 📊 Monitoring

### Script de surveillance

```bash
#!/bin/bash
# surveillance-messenger.sh

echo "=== Surveillance Messenger ==="
echo "Date: $(date)"
echo ""

# Vérifier les messages en queue
echo "Messages en queue:"
php bin/console messenger:stats

echo ""
echo "Worker actif:"
if [ -f "var/messenger-worker.pid" ]; then
    pid=$(cat var/messenger-worker.pid)
    if ps -p "$pid" > /dev/null 2>&1; then
        echo "✅ Worker actif (PID: $pid)"
    else
        echo "❌ Worker inactif"
    fi
else
    echo "❌ Worker non démarré"
fi

echo ""
echo "Derniers logs:"
tail -n 5 var/log/messenger-worker.log
```

### Surveillance automatique

Ajoutez cette tâche Cron pour la surveillance :

```
Commande: /home/lokaprot/myloccahome/surveillance-messenger.sh
Minute: 0
Heure: */2
Jour: *
Mois: *
Jour de la semaine: *
```

## 🔍 Dépannage

### Problèmes courants

#### 1. Worker ne démarre pas

```bash
# Vérifier les logs
tail -f var/log/messenger-worker.log

# Vérifier les permissions
ls -la start-messenger-worker.sh

# Tester manuellement
php bin/console messenger:consume async --time-limit=60
```

#### 2. Messages non traités

```bash
# Vérifier la configuration
php bin/console debug:messenger

# Vérifier les messages en queue
php bin/console messenger:stats

# Vérifier que Cron fonctionne
tail -f var/log/cron-messenger.log
```

#### 3. Erreurs MySQL

```bash
# Vérifier la version MySQL
php bin/console doctrine:query:sql "SELECT VERSION()"

# Vérifier les tables
php bin/console doctrine:query:sql "SHOW TABLES LIKE 'messenger%'"
```

### Logs utiles

```bash
# Logs du worker
tail -f var/log/messenger-worker.log

# Logs de Cron
tail -f var/log/cron-messenger.log

# Logs Symfony
tail -f var/log/prod.log

# Logs système (si accessible)
tail -f /var/log/cron
```

## 📈 Optimisation

### Performance MySQL 8

```sql
-- Optimiser la table messenger_messages
ALTER TABLE messenger_messages ENGINE=InnoDB;
CREATE INDEX idx_queue_available ON messenger_messages(queue_name, available_at);
CREATE INDEX idx_delivered ON messenger_messages(delivered_at);
```

### Configuration PHP

```bash
# Optimiser PHP pour le worker
php -i | grep memory_limit
php -i | grep max_execution_time

# Ajuster si nécessaire dans php.ini
memory_limit = 256M
max_execution_time = 0
```

## 🔒 Sécurité

### Permissions

```bash
# Configurer les permissions
chmod 755 start-messenger-worker.sh
chmod 755 cron-messenger-worker.sh
chmod -R 775 var/log/
chmod 600 .env.local
```

### Monitoring de sécurité

```bash
# Surveiller les processus PHP
ps aux | grep "messenger:consume"

# Vérifier l'utilisation mémoire
ps aux | grep php | awk '{sum+=$6} END {print "Mémoire totale PHP: " sum/1024 " MB"}'
```

## 📞 Support

### Informations de diagnostic

En cas de problème, fournissez :

```bash
# Informations système
php -v
mysql --version
php bin/console doctrine:query:sql "SELECT VERSION()"

# Configuration Messenger
php bin/console debug:messenger

# Statut des messages
php bin/console messenger:stats

# Logs récents
tail -n 50 var/log/messenger-worker.log
tail -n 50 var/log/cron-messenger.log

# Configuration Cron
crontab -l
```

### Commandes de récupération

```bash
# Redémarrer complètement
./start-messenger-worker.sh stop
sleep 5
./start-messenger-worker.sh start

# Nettoyer les messages anciens
php bin/console doctrine:query:sql "DELETE FROM messenger_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"

# Réinitialiser les messages échoués
php bin/console doctrine:query:sql "DELETE FROM messenger_messages_failed WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
```

---

**Note :** Ce guide est optimisé pour MySQL 8.0+ et les serveurs partagés avec Cron.
