# 🔧 Configuration .htaccess pour MYLOCCA - Guide d'Installation

## 📋 **Fichier .htaccess Créé**

J'ai créé un fichier `.htaccess` complet qui redirige automatiquement toutes les requêtes vers le dossier `public` de votre projet Symfony.

---

## 🚀 **Installation**

### **1. Placement du Fichier**
```bash
# Copier le fichier .htaccess dans le dossier public_html
copy .htaccess-public_html public_html/.htaccess
```

### **2. Structure des Dossiers**
```
public_html/
├── .htaccess          ← Fichier de redirection
├── public/            ← Dossier Symfony public
│   ├── index.php
│   ├── .htaccess      ← Configuration Symfony
│   └── ...
├── src/               ← Code source Symfony
├── vendor/            ← Dépendances Composer
└── ...
```

---

## 🌐 **Fonctionnalités du .htaccess**

### **1. Redirection Automatique**
```apache
# Rediriger toutes les requêtes vers le dossier public
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ /public/$1 [L,R=301]
```

### **2. Configuration Symfony**
```apache
# Configuration pour le dossier public
<Directory "public">
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</Directory>
```

### **3. Sécurité Renforcée**
```apache
# Bloquer l'accès aux fichiers sensibles
<FilesMatch "\.(env|log|sql|bak|backup|gitignore|htaccess)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Bloquer l'accès aux dossiers sensibles
<DirectoryMatch "(^|/)\.(git|svn|hg|bzr)">
    Order Allow,Deny
    Deny from all
</DirectoryMatch>
```

### **4. Performance Optimisée**
```apache
# Compression des fichiers
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/javascript
</IfModule>

# Cache des ressources statiques
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
</IfModule>
```

---

## 🔒 **Sécurité Configurée**

### **1. Headers de Sécurité**
```apache
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

### **2. Protection des Fichiers**
```apache
# Bloquer l'accès aux fichiers sensibles
<FilesMatch "\.(env|log|sql|bak|backup|gitignore|htaccess|htpasswd)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Bloquer l'accès aux fichiers de configuration
<FilesMatch "\.(ini|conf|config|xml|yaml|yml)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

### **3. Protection des Dossiers**
```apache
# Bloquer l'accès aux dossiers sensibles
<DirectoryMatch "(^|/)\.(git|svn|hg|bzr)">
    Order Allow,Deny
    Deny from all
</DirectoryMatch>

<DirectoryMatch "(^|/)(var|vendor|node_modules|tests)">
    Order Allow,Deny
    Deny from all
</DirectoryMatch>
```

---

## ⚡ **Performance Optimisée**

### **1. Compression**
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE application/json
</IfModule>
```

### **2. Cache des Ressources**
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/svg+xml "access plus 1 month"
    ExpiresByType application/font-woff "access plus 1 month"
    ExpiresByType application/font-woff2 "access plus 1 month"
</IfModule>
```

### **3. Headers de Performance**
```apache
<IfModule mod_headers.c>
    # Performance
    Header always set Cache-Control "public, max-age=31536000" "expr=%{REQUEST_URI} =~ m#\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2)$#"
</IfModule>
```

---

## 🌐 **Support des Environnements de Démo**

### **1. Détection des Sous-domaines**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Détecter les sous-domaines de démo
    RewriteCond %{HTTP_HOST} ^([^.]+)\.demo\.mylocca\.local$ [NC]
    RewriteRule ^(.*)$ - [E=DEMO_SUBDOMAIN:%1]
    
    # Rediriger vers le dossier public
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ /public/$1 [L,R=301]
</IfModule>
```

### **2. Variables d'Environnement**
```apache
# Variables d'environnement
SetEnv APP_ENV prod
SetEnv APP_DEBUG 0
```

---

## 📊 **Types de Fichiers Supportés**

### **1. JavaScript et CSS**
```apache
<IfModule mod_mime.c>
    AddType application/javascript .js
    AddType application/javascript .mjs
    AddType text/css .css
</IfModule>
```

### **2. Images**
```apache
<IfModule mod_mime.c>
    AddType image/svg+xml .svg
    AddType image/webp .webp
    AddType image/avif .avif
</IfModule>
```

### **3. Fonts**
```apache
<IfModule mod_mime.c>
    AddType application/font-woff .woff
    AddType application/font-woff2 .woff2
    AddType application/vnd.ms-fontobject .eot
    AddType font/truetype .ttf
</IfModule>
```

### **4. Documents**
```apache
<IfModule mod_mime.c>
    AddType application/pdf .pdf
    AddType application/msword .doc
    AddType application/vnd.openxmlformats-officedocument.wordprocessingml.document .docx
    AddType application/vnd.ms-excel .xls
    AddType application/vnd.openxmlformats-officedocument.spreadsheetml.sheet .xlsx
</IfModule>
```

---

## 🔧 **Configuration des Erreurs**

### **1. Pages d'Erreur Personnalisées**
```apache
ErrorDocument 400 /public/index.php
ErrorDocument 401 /public/index.php
ErrorDocument 403 /public/index.php
ErrorDocument 404 /public/index.php
ErrorDocument 500 /public/index.php
ErrorDocument 502 /public/index.php
ErrorDocument 503 /public/index.php
```

### **2. Configuration des Logs**
```apache
# Activer les logs d'erreur
LogLevel warn
ErrorLog logs/error.log
CustomLog logs/access.log combined
```

---

## 🚀 **Modules Apache Requis**

### **1. Modules Obligatoires**
```apache
# mod_rewrite (pour les redirections)
LoadModule rewrite_module modules/mod_rewrite.so
```

### **2. Modules Recommandés**
```apache
# mod_headers (pour les headers de sécurité)
LoadModule headers_module modules/mod_headers.so

# mod_deflate (pour la compression)
LoadModule deflate_module modules/mod_deflate.so

# mod_expires (pour le cache)
LoadModule expires_module modules/mod_expires.so

# mod_mime (pour les types MIME)
LoadModule mime_module modules/mod_mime.so
```

### **3. Vérification des Modules**
```apache
<IfModule !mod_rewrite.c>
    # Le module mod_rewrite est requis
    ErrorDocument 500 "Module mod_rewrite requis"
</IfModule>

<IfModule !mod_headers.c>
    # Le module mod_headers est recommandé
    # Les headers de sécurité ne seront pas appliqués
</IfModule>
```

---

## 🧪 **Test de la Configuration**

### **1. URLs de Test**
- **Site principal** : `http://votre-domaine.com`
- **Redirection** : `http://votre-domaine.com/` → `http://votre-domaine.com/public/`
- **Environnement démo** : `http://demo.votre-domaine.com`

### **2. Vérifications**
```bash
# Tester la redirection
curl -I http://votre-domaine.com/

# Vérifier les headers de sécurité
curl -I http://votre-domaine.com/ | grep -i "x-frame-options"

# Tester la compression
curl -H "Accept-Encoding: gzip" -I http://votre-domaine.com/
```

### **3. Logs Apache**
```bash
# Vérifier les logs d'erreur
tail -f logs/error.log

# Vérifier les logs d'accès
tail -f logs/access.log
```

---

## 🔍 **Dépannage**

### **1. Problèmes Courants**
- **Redirection en boucle** : Vérifier la configuration RewriteRule
- **Module non chargé** : Vérifier que mod_rewrite est activé
- **Permissions** : Vérifier les droits sur les fichiers
- **Syntaxe** : Vérifier la syntaxe Apache

### **2. Commandes de Diagnostic**
```bash
# Vérifier la configuration Apache
apache2ctl configtest

# Vérifier les modules chargés
apache2ctl -M

# Tester la configuration
apache2ctl -t
```

### **3. Logs de Debug**
```bash
# Activer les logs de debug
LogLevel debug rewrite:trace3

# Vérifier les logs Symfony
tail -f var/log/dev.log
```

---

## ✅ **Checklist d'Installation**

### **1. Prérequis**
- [ ] Apache installé et fonctionnel
- [ ] Module mod_rewrite activé
- [ ] Projet Symfony dans le dossier public_html
- [ ] Dossier public accessible

### **2. Installation**
- [ ] Fichier .htaccess copié dans public_html
- [ ] Permissions correctes sur le fichier
- [ ] Configuration Apache testée
- [ ] Apache redémarré

### **3. Test**
- [ ] Redirection vers /public/ fonctionnelle
- [ ] Site Symfony accessible
- [ ] Headers de sécurité présents
- [ ] Compression activée
- [ ] Cache configuré

---

## 🎯 **Avantages de cette Configuration**

### **1. Sécurité**
- **Protection** des fichiers sensibles
- **Headers** de sécurité configurés
- **Blocage** des accès non autorisés
- **Protection** contre les attaques courantes

### **2. Performance**
- **Compression** des fichiers
- **Cache** des ressources statiques
- **Optimisation** des headers
- **Réduction** de la bande passante

### **3. Fonctionnalité**
- **Redirection** automatique vers public
- **Support** des environnements de démo
- **Configuration** Symfony optimisée
- **Gestion** des erreurs personnalisée

---

**Le fichier .htaccess est maintenant configuré ! 🚀**

**Copiez-le dans votre dossier public_html et testez !** ✅
