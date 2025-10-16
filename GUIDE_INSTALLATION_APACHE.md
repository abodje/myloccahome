# 🔧 Configuration Apache Complète pour MYLOCCA - Guide d'Installation

## 📋 **Fichiers Créés**

### **1. Configuration Apache**
- **`apache-mylocca-complet.conf`** : Configuration complète Apache
- **`install-apache-demo.bat`** : Script d'installation automatique

### **2. Fonctionnalités Incluses**
- **Site principal** : `mylocca.local`
- **Environnements de démo** : `*.demo.mylocca.local`
- **Configuration SSL** (optionnelle)
- **Sécurité renforcée** avec headers et protection
- **Performance optimisée** avec compression et cache
- **Logs séparés** pour chaque environnement

---

## 🚀 **Installation Automatique**

### **1. Exécution du Script**
```cmd
# Exécuter en tant qu'administrateur
install-apache-demo.bat
```

### **2. Ce que fait le Script**
- ✅ **Copie** la configuration Apache
- ✅ **Ajoute** l'include dans `httpd.conf`
- ✅ **Configure** le fichier `hosts` Windows
- ✅ **Crée** les dossiers de logs
- ✅ **Vérifie** les modules Apache requis

---

## 🔧 **Installation Manuelle**

### **1. Configuration Apache**
```bash
# Copier le fichier de configuration
copy apache-mylocca-complet.conf C:\wamp64\bin\apache\apache2.4.54\conf\extra\mylocca-demo.conf

# Ajouter l'include dans httpd.conf
echo # Configuration MYLOCCA Demo System >> C:\wamp64\bin\apache\apache2.4.54\conf\httpd.conf
echo Include conf/extra/mylocca-demo.conf >> C:\wamp64\bin\apache\apache2.4.54\conf\httpd.conf
```

### **2. Configuration DNS Local**
```bash
# Ajouter dans C:\Windows\System32\drivers\etc\hosts
127.0.0.1 mylocca.local
127.0.0.1 demo.mylocca.local
127.0.0.1 *.demo.mylocca.local
```

### **3. Création des Dossiers**
```bash
# Créer le dossier de logs
mkdir C:\wamp64\logs

# Créer le dossier demo_data
mkdir demo_data
```

---

## 🌐 **Configuration Détaillée**

### **1. Site Principal**
```apache
<VirtualHost *:80>
    ServerName mylocca.local
    ServerAlias www.mylocca.local
    DocumentRoot "C:/wamp64/mylocca/public"
    
    <Directory "C:/wamp64/mylocca/public">
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
        ErrorDocument 404 /index.php
    </Directory>
    
    SetEnv APP_ENV prod
    SetEnv APP_DEBUG 0
</VirtualHost>
```

### **2. Environnements de Démo**
```apache
<VirtualHost *:80>
    ServerName demo.mylocca.local
    ServerAlias *.demo.mylocca.local
    DocumentRoot "C:/wamp64/mylocca/public"
    
    <Directory "C:/wamp64/mylocca/public">
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
        ErrorDocument 404 /index.php
    </Directory>
    
    SetEnv APP_ENV demo
    SetEnv APP_DEBUG 1
    
    # Détection du sous-domaine
    RewriteEngine On
    RewriteCond %{HTTP_HOST} ^([^.]+)\.demo\.mylocca\.local$ [NC]
    RewriteRule ^(.*)$ - [E=DEMO_SUBDOMAIN:%1]
</VirtualHost>
```

### **3. Configuration SSL (Optionnelle)**
```apache
<VirtualHost *:443>
    ServerName mylocca.local
    ServerAlias www.mylocca.local
    DocumentRoot "C:/wamp64/mylocca/public"
    
    SSLEngine on
    SSLCertificateFile "C:/wamp64/bin/apache/apache2.4.54/conf/ssl.crt/server.crt"
    SSLCertificateKeyFile "C:/wamp64/bin/apache/apache2.4.54/conf/ssl.key/server.key"
    
    # Configuration identique au HTTP
</VirtualHost>
```

---

## 🔒 **Sécurité Configurée**

### **1. Headers de Sécurité**
```apache
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

### **2. Protection contre les Attaques**
```apache
# Bloquer les fichiers sensibles
RewriteCond %{REQUEST_URI} \.(env|log|sql|bak|backup)$ [NC]
RewriteRule ^(.*)$ - [F,L]

# Bloquer les injections SQL
RewriteCond %{QUERY_STRING} (<|%3C).*script.*(>|%3E) [NC,OR]
RewriteCond %{QUERY_STRING} GLOBALS(=|\[|\%[0-9A-Z]{0,2}) [OR]
RewriteCond %{QUERY_STRING} _REQUEST(=|\[|\%[0-9A-Z]{0,2}) [OR]
RewriteRule ^(.*)$ - [F,L]
```

### **3. SSL/TLS (si activé)**
```apache
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

---

## ⚡ **Performance Optimisée**

### **1. Compression**
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

### **2. Cache des Ressources**
```apache
ExpiresActive On
ExpiresByType text/css "access plus 1 month"
ExpiresByType application/javascript "access plus 1 month"
ExpiresByType image/png "access plus 1 month"
ExpiresByType image/jpg "access plus 1 month"
ExpiresByType image/jpeg "access plus 1 month"
```

---

## 📊 **Logs Configurés**

### **1. Logs Séparés**
- **Site principal** : `C:/wamp64/logs/mylocca_error.log`
- **Environnements démo** : `C:/wamp64/logs/demo_error.log`
- **SSL principal** : `C:/wamp64/logs/mylocca_ssl_error.log`
- **SSL démo** : `C:/wamp64/logs/demo_ssl_error.log`

### **2. Format Personnalisé**
```apache
LogFormat "%h %l %u %t \"%r\" %>s %O \"%{Referer}i\" \"%{User-Agent}i\" %D" mylocca_format
```

---

## 🧪 **Test de la Configuration**

### **1. URLs de Test**
- **Site principal** : `http://mylocca.local`
- **Environnement démo** : `http://demo.mylocca.local`
- **Inscription** : `http://mylocca.local/inscription/freemium`

### **2. Vérifications**
```cmd
# Tester la résolution DNS
ping mylocca.local
ping demo.mylocca.local

# Tester les environnements de démo
curl -H "Host: test.demo.mylocca.local" http://127.0.0.1
```

### **3. Logs Apache**
```cmd
# Vérifier les logs d'erreur
type C:\wamp64\logs\mylocca_error.log
type C:\wamp64\logs\demo_error.log
```

---

## 🔧 **Modules Apache Requis**

### **1. Modules Obligatoires**
```apache
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
LoadModule deflate_module modules/mod_deflate.so
LoadModule expires_module modules/mod_expires.so
```

### **2. Modules Optionnels**
```apache
LoadModule ssl_module modules/mod_ssl.so
LoadModule cache_module modules/mod_cache.so
```

---

## 🚀 **Déploiement en Production**

### **1. Configuration DNS**
```
mylocca.com → IP_SERVEUR
*.demo.mylocca.com → IP_SERVEUR
```

### **2. Certificats SSL**
```bash
# Générer un certificat wildcard
openssl req -new -newkey rsa:2048 -nodes -keyout mylocca.key -out mylocca.csr
# Demander un certificat wildcard *.mylocca.com
```

### **3. Configuration Serveur**
```apache
<VirtualHost *:80>
    ServerName mylocca.com
    ServerAlias *.demo.mylocca.com
    DocumentRoot "/var/www/mylocca/public"
    
    # Configuration identique mais avec chemins Linux
</VirtualHost>
```

---

## 🎯 **Fonctionnalités Avancées**

### **1. Détection de Sous-domaine**
```apache
# Le sous-domaine est automatiquement détecté
RewriteCond %{HTTP_HOST} ^([^.]+)\.demo\.mylocca\.local$ [NC]
RewriteRule ^(.*)$ - [E=DEMO_SUBDOMAIN:%1]
```

### **2. Variables d'Environnement**
```apache
# Pour les environnements de démo
SetEnv APP_ENV demo
SetEnv APP_DEBUG 1
SetEnv DEMO_SUBDOMAIN jean-dupont-a1b2
```

### **3. Headers Spécifiques**
```apache
# Identifier les environnements de démo
Header always set X-Demo-Environment "true"
```

---

## 🔍 **Dépannage**

### **1. Problèmes Courants**
- **DNS non résolu** : Vérifier le fichier `hosts`
- **Module non chargé** : Vérifier `httpd.conf`
- **Permissions** : Vérifier les droits sur les dossiers
- **Port occupé** : Vérifier les autres services

### **2. Commandes de Diagnostic**
```cmd
# Vérifier la configuration Apache
C:\wamp64\bin\apache\apache2.4.54\bin\httpd.exe -t

# Vérifier les modules chargés
C:\wamp64\bin\apache\apache2.4.54\bin\httpd.exe -M

# Tester la résolution DNS
nslookup mylocca.local
```

### **3. Logs de Debug**
```cmd
# Activer les logs de debug
SetEnv APP_DEBUG 1

# Vérifier les logs Symfony
type var\log\dev.log
```

---

## ✅ **Checklist d'Installation**

### **1. Prérequis**
- [ ] WAMP installé et fonctionnel
- [ ] Privilèges administrateur
- [ ] Modules Apache requis activés
- [ ] Symfony fonctionnel

### **2. Installation**
- [ ] Configuration Apache copiée
- [ ] Include ajouté dans `httpd.conf`
- [ ] Fichier `hosts` configuré
- [ ] Dossiers de logs créés
- [ ] Apache redémarré

### **3. Test**
- [ ] Site principal accessible
- [ ] Environnement démo accessible
- [ ] Inscription fonctionnelle
- [ ] Environnement de démo créé automatiquement

---

**La configuration Apache complète est maintenant prête ! 🚀**

**Exécutez le script d'installation et testez le système !** ✅
