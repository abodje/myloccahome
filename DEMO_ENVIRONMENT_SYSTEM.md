# 🌐 Système d'Environnements de Démo avec Sous-domaines - MYLOCCA

## ✅ Fonctionnalités Implémentées

### **🎯 Système Complet**
- **Environnement de démo automatique** à chaque inscription
- **Sous-domaines dynamiques** (ex: `jean-dupont-a1b2.demo.mylocca.local`)
- **Données de démo réalistes** (5 propriétés, 5 locataires, 4 baux, 12 paiements)
- **Configuration Apache automatique** pour les sous-domaines
- **Interface d'administration** pour gérer les environnements

---

## 🚀 Installation et Configuration

### **1. Fichiers Créés**
```
src/Service/DemoEnvironmentService.php           ← Service principal
src/Controller/Admin/DemoEnvironmentController.php ← Contrôleur admin
templates/admin/demo/index.html.twig             ← Interface admin
```

### **2. Modifications Apportées**
- **RegistrationController** : Intégration automatique de la création d'environnement
- **MenuService** : Ajout du menu "Environnements Démo"
- **Organization** : Ajout des champs `isDemo` et `subdomain`

### **3. Variables d'Environnement**
Ajoutez dans votre `.env` :
```env
DEMO_BASE_URL=demo.mylocca.local
```

---

## 🔧 Configuration Apache

### **1. Configuration Principale**
Le service génère automatiquement des fichiers de configuration Apache dans :
```
C:/wamp64/bin/apache/apache2.4.54/conf/extra/{subdomain}.conf
```

### **2. Exemple de Configuration Générée**
```apache
<VirtualHost *:80>
    ServerName jean-dupont-a1b2.demo.mylocca.local
    DocumentRoot "C:/wamp64/mylocca/public"
    
    <Directory "C:/wamp64/mylocca/public">
        AllowOverride All
        Require all granted
    </Directory>
    
    # Configuration spécifique pour l'environnement de démo
    SetEnv APP_ENV demo
    SetEnv DEMO_SUBDOMAIN jean-dupont-a1b2
    
    # Logs spécifiques
    ErrorLog "C:/wamp64/logs/jean-dupont-a1b2_error.log"
    CustomLog "C:/wamp64/logs/jean-dupont-a1b2_access.log" combined
</VirtualHost>
```

### **3. Activation des Sous-domaines**
Le service ajoute automatiquement l'include dans `httpd.conf` :
```apache
# Configuration sous-domaine de démo
Include conf/extra/jean-dupont-a1b2.conf
```

---

## 🌐 Configuration DNS Local

### **1. Fichier Hosts Windows**
Le service ajoute automatiquement des entrées dans :
```
C:/Windows/System32/drivers/etc/hosts
```

### **2. Exemple d'Entrée Ajoutée**
```
# MYLOCCA Demo Environment
127.0.0.1 jean-dupont-a1b2.demo.mylocca.local
```

### **3. Redémarrage Requis**
Après modification du fichier hosts, redémarrez votre navigateur ou exécutez :
```cmd
ipconfig /flushdns
```

---

## 📊 Données de Démo Créées

### **1. Propriétés (5)**
- **Adresses réalistes** : Rue de la Paix, Avenue des Champs, etc.
- **Loyers variés** : 1200€ à 2200€
- **Surfaces différentes** : 45m² à 110m²
- **Type** : Appartement

### **2. Locataires (5)**
- **Noms réalistes** : Jean Dupont, Marie Martin, etc.
- **Emails de démo** : `jean.dupont@demo.com`
- **Téléphones** : Numéros de test
- **Statut** : Actif

### **3. Baux (4)**
- **Durée** : 12 mois
- **Début** : Date actuelle
- **Fin** : +12 mois
- **Caution** : 2 mois de loyer
- **Statut** : Actif

### **4. Paiements (12)**
- **3 paiements par bail** (mois précédents)
- **Statut** : Payé
- **Méthode** : Virement
- **Référence** : DEMO-001, DEMO-002, etc.

---

## 🎯 Utilisation

### **1. Inscription Automatique**
Quand un utilisateur s'inscrit :
1. ✅ **Compte créé** normalement
2. ✅ **Environnement de démo** créé automatiquement
3. ✅ **Sous-domaine** généré (ex: `jean-dupont-a1b2`)
4. ✅ **Données de démo** ajoutées
5. ✅ **Configuration Apache** générée
6. ✅ **DNS local** configuré

### **2. Accès à l'Environnement**
L'utilisateur reçoit :
- **URL de démo** : `https://jean-dupont-a1b2.demo.mylocca.local`
- **Données pré-remplies** pour tester immédiatement
- **Accès admin** à son environnement

### **3. Interface d'Administration**
Menu **Administration** → **🌐 Environnements Démo** :
- **Liste** de tous les environnements
- **Création manuelle** d'environnements
- **Rafraîchissement** des données
- **Suppression** d'environnements

---

## 🔍 Fonctionnalités du Service

### **1. Méthodes Principales**
```php
// Créer un environnement complet
$result = $demoEnvironmentService->createDemoEnvironment($user);

// Lister tous les environnements
$environments = $demoEnvironmentService->listDemoEnvironments();

// Supprimer un environnement
$success = $demoEnvironmentService->deleteDemoEnvironment($subdomain);
```

### **2. Génération de Sous-domaines**
```php
// Format : {email_clean}-{hash}
// Exemple : jean.dupont@example.com → jeandupont-a1b2
$subdomain = $this->generateSubdomain($user);
```

### **3. Isolation Complète**
- **Organisation séparée** pour chaque démo
- **Société dédiée** à l'organisation
- **Données isolées** (isDemo = true)
- **Configuration Apache** individuelle

---

## 🧪 Test du Système

### **1. Test d'Inscription**
1. **Allez sur** : `/inscription/freemium`
2. **Remplissez** le formulaire d'inscription
3. **Vérifiez** les messages de succès
4. **Notez** l'URL de démo fournie

### **2. Test d'Accès**
1. **Ouvrez** l'URL de démo dans un nouvel onglet
2. **Connectez-vous** avec les identifiants créés
3. **Vérifiez** que les données de démo sont présentes
4. **Testez** les fonctionnalités (propriétés, locataires, etc.)

### **3. Test d'Administration**
1. **Connectez-vous** en tant que Super Admin
2. **Allez sur** : Menu Administration → 🌐 Environnements Démo
3. **Vérifiez** que l'environnement apparaît dans la liste
4. **Testez** les actions (rafraîchir, supprimer)

---

## 🔧 Configuration Avancée

### **1. Personnalisation des Données**
Modifiez `DemoEnvironmentService.php` pour :
- **Changer les adresses** de démo
- **Modifier les noms** des locataires
- **Ajuster les montants** des loyers
- **Personnaliser les types** de propriétés

### **2. Configuration Apache Avancée**
```apache
# Ajouter SSL pour les environnements de démo
<VirtualHost *:443>
    ServerName jean-dupont-a1b2.demo.mylocca.local
    SSLEngine on
    SSLCertificateFile "path/to/cert.pem"
    SSLCertificateKeyFile "path/to/key.pem"
    # ... reste de la configuration
</VirtualHost>
```

### **3. Configuration DNS Externe**
Pour la production, configurez un DNS générique :
```
*.demo.mylocca.com → IP_SERVEUR
```

---

## 🚀 Déploiement en Production

### **1. Configuration Serveur**
```bash
# Installer Apache avec mod_vhost_alias
sudo apt-get install apache2
sudo a2enmod vhost_alias

# Configuration générique
<VirtualHost *:80>
    ServerName demo.mylocca.com
    ServerAlias *.demo.mylocca.com
    
    VirtualDocumentRoot /var/www/mylocca/public
    
    <Directory "/var/www/mylocca/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### **2. Configuration DNS**
```
*.demo.mylocca.com → IP_SERVEUR
```

### **3. Variables d'Environnement**
```env
DEMO_BASE_URL=demo.mylocca.com
```

---

## 💡 Avantages du Système

### **1. Pour les Utilisateurs**
- **Test immédiat** sans configuration
- **Données réalistes** pour comprendre le système
- **Environnement isolé** et sécurisé
- **Accès direct** via sous-domaine

### **2. Pour l'Administration**
- **Gestion centralisée** des environnements
- **Monitoring** des utilisations
- **Nettoyage automatique** possible
- **Scalabilité** horizontale

### **3. Pour le Business**
- **Conversion améliorée** (test immédiat)
- **Réduction du support** (données pré-remplies)
- **Démonstration facile** pour les prospects
- **Onboarding accéléré**

---

## 🔍 Monitoring et Maintenance

### **1. Logs de Création**
```
demo_data/demo_environments.log
```

### **2. Configuration Proxy**
```
demo_data/proxy_config_{subdomain}.json
```

### **3. Nettoyage Automatique**
```php
// Supprimer les environnements anciens
$oldEnvironments = $this->entityManager->getRepository(Organization::class)
    ->findBy(['isDemo' => true, 'createdAt' => ['<', new \DateTime('-30 days')]]);

foreach ($oldEnvironments as $org) {
    $this->demoEnvironmentService->deleteDemoEnvironment($org->getSubdomain());
}
```

---

## 🎯 Prochaines Améliorations

### **1. Fonctionnalités Avancées**
- **Limite de temps** pour les environnements de démo
- **Données personnalisées** selon le secteur d'activité
- **Intégration CRM** pour le suivi des prospects
- **Analytics** des environnements de démo

### **2. Optimisations Techniques**
- **Cache Redis** pour les configurations
- **Load Balancer** pour la scalabilité
- **CDN** pour les ressources statiques
- **Monitoring** en temps réel

---

**Le système d'environnements de démo est maintenant opérationnel ! 🚀**

**Chaque nouvelle inscription génère automatiquement un environnement complet avec sous-domaine !** ✅
