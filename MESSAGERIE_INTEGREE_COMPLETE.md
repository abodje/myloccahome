# 💬 Messagerie Intégrée Complète

## 📋 Vue d'ensemble

Système de messagerie intégrée permettant la communication entre admin, gestionnaires et locataires avec interface moderne et fonctionnalités avancées.

---

## ✨ Fonctionnalités Principales

### **1. Communication Multi-Rôles**
- ✅ **Admin ↔ Gestionnaires** : Communication administrative
- ✅ **Gestionnaires ↔ Locataires** : Communication opérationnelle
- ✅ **Admin ↔ Locataires** : Communication directe
- ✅ **Gestionnaires ↔ Gestionnaires** : Collaboration entre pairs

### **2. Interface Utilisateur**
- ✅ **Liste des conversations** : Vue d'ensemble avec statuts
- ✅ **Chat en temps réel** : Interface de messagerie moderne
- ✅ **Notifications visuelles** : Badges pour messages non lus
- ✅ **Recherche et filtres** : Trouver rapidement les conversations
- ✅ **Responsive design** : Compatible mobile et desktop

### **3. Fonctionnalités Avancées**
- ✅ **Messages non lus** : Compteur et marquage automatique
- ✅ **Statuts des messages** : Vu/Non vu avec timestamps
- ✅ **Participants multiples** : Conversations de groupe
- ✅ **Historique complet** : Conservation des échanges
- ✅ **Sécurité** : Accès restreint aux participants

---

## 🏗️ Architecture Technique

### **1. Entités Principales**

#### **Conversation Entity :**
```php
- id: Identifiant unique
- subject: Sujet de la conversation
- initiator: Utilisateur initiateur
- participants: Collection d'utilisateurs
- messages: Collection de messages
- isActive: Statut actif/inactif
- createdAt/updatedAt: Timestamps
- lastMessageAt: Dernier message
```

#### **Message Entity :**
```php
- id: Identifiant unique
- conversation: Conversation parente
- sender: Utilisateur expéditeur
- content: Contenu du message
- isRead: Statut lu/non lu
- sentAt/readAt: Timestamps
- createdAt/updatedAt: Timestamps
```

### **2. Contrôleur MessageController**

#### **Routes Principales :**
```php
GET  /messagerie/                    # Liste des conversations
GET  /messagerie/nouvelle            # Créer une conversation
GET  /messagerie/{id}                # Afficher une conversation
POST /messagerie/{id}                # Envoyer un message
POST /messagerie/{id}/marquer-lu     # Marquer comme lu
GET  /messagerie/contact/{id}        # Contacter un utilisateur
GET  /messagerie/api/unread-count    # API compteur non lus
GET  /messagerie/api/recent          # API conversations récentes
```

#### **Fonctionnalités du Contrôleur :**
- ✅ **Filtrage par rôle** : Conversations selon les permissions
- ✅ **Validation d'accès** : Vérification des participants
- ✅ **Marquage automatique** : Messages lus automatiquement
- ✅ **API endpoints** : Données pour notifications

### **3. Repositories Spécialisés**

#### **ConversationRepository :**
```php
findByUser(User $user)                    # Conversations d'un utilisateur
findWithUnreadMessages(User $user)        # Conversations avec messages non lus
findBetweenUsers(User $user1, User $user2) # Conversation entre 2 utilisateurs
findByRole(User $user)                    # Conversations selon le rôle
findWithFilters(User $user, $search, $status) # Recherche avec filtres
getStatisticsForUser(User $user)          # Statistiques utilisateur
```

#### **MessageRepository :**
```php
findByConversation(Conversation $conversation) # Messages d'une conversation
findUnreadByUser(User $user)                   # Messages non lus d'un utilisateur
markAsReadByConversationAndUser()              # Marquage en lot
getStatisticsForUser(User $user)               # Statistiques messages
findRecentByUser(User $user, $limit)           # Messages récents
```

---

## 🎨 Interface Utilisateur

### **1. Page Principale (/messagerie/)**

#### **Sidebar Gauche :**
- 📋 **Liste des conversations** avec statuts
- 🔍 **Barre de recherche** et filtres
- 📊 **Statistiques** (total, actives)
- ➕ **Bouton nouvelle conversation**

#### **Zone Principale :**
- 💬 **Interface de chat** moderne
- 📝 **Formulaire d'envoi** de messages
- 👥 **Informations participants**
- 📱 **Design responsive**

### **2. Interface de Conversation**

#### **En-tête :**
- 📌 **Sujet de la conversation**
- 👥 **Liste des participants**
- 🏷️ **Badge nombre de participants**

#### **Zone Messages :**
- 💬 **Bulles de messages** différenciées par expéditeur
- ⏰ **Timestamps** de chaque message
- ✅ **Statuts de lecture** (vu/non vu)
- 🎨 **Couleurs différenciées** (expéditeur vs destinataire)

#### **Formulaire d'envoi :**
- 📝 **Zone de texte** avec placeholder
- 🚀 **Bouton d'envoi** avec icône
- ⚡ **Soumission instantanée**

### **3. Création de Conversation**

#### **Formulaire :**
- 📌 **Champ sujet** obligatoire
- 👥 **Sélection participants** (multi-sélection)
- 💡 **Conseils d'utilisation**
- ✅ **Validation** côté client et serveur

---

## 🔒 Sécurité et Permissions

### **1. Contrôle d'Accès**
```php
// Vérification des participants
if (!$conversation->getParticipants()->contains($user)) {
    throw $this->createAccessDeniedException('Accès refusé');
}

// Filtrage par rôle
if (in_array('ROLE_ADMIN', $user->getRoles())) {
    // Accès à toutes les conversations
} elseif (in_array('ROLE_MANAGER', $user->getRoles())) {
    // Accès aux conversations avec ses locataires
} else {
    // Accès uniquement à ses propres conversations
}
```

### **2. Validation des Données**
- ✅ **Sanitisation** du contenu des messages
- ✅ **Limitation** de la taille des messages
- ✅ **Vérification** des participants autorisés
- ✅ **Protection CSRF** sur tous les formulaires

### **3. Confidentialité**
- ✅ **Conversations privées** : Seuls les participants y ont accès
- ✅ **Pas d'historique** visible par des tiers
- ✅ **Messages chiffrés** en base de données
- ✅ **Suppression sécurisée** des conversations

---

## 📊 Gestion des Rôles

### **1. Administrateurs (ROLE_ADMIN)**
- ✅ **Accès total** : Toutes les conversations
- ✅ **Création libre** : Conversations avec n'importe qui
- ✅ **Modération** : Gestion des conversations
- ✅ **Statistiques** : Vue d'ensemble du système

### **2. Gestionnaires (ROLE_MANAGER)**
- ✅ **Conversations limitées** : Avec leurs locataires uniquement
- ✅ **Communication directe** : Avec les locataires de leurs propriétés
- ✅ **Collaboration** : Entre gestionnaires
- ✅ **Reporting** : Statistiques de leurs conversations

### **3. Locataires (ROLE_TENANT)**
- ✅ **Conversations personnelles** : Uniquement leurs conversations
- ✅ **Contact gestionnaire** : Communication avec leur gestionnaire
- ✅ **Support technique** : Contact admin si nécessaire
- ✅ **Historique personnel** : Conservation de leurs échanges

---

## 🔧 Intégration Système

### **1. Menu Principal**
```php
// Ajout dans MenuService.php
'messages' => [
    'label' => 'Messagerie',
    'icon' => 'bi-chat-dots',
    'route' => 'app_message_index',
    'roles' => ['ROLE_USER', 'ROLE_TENANT', 'ROLE_MANAGER', 'ROLE_ADMIN'],
    'order' => 9,
    'badge_type' => 'danger',
    'badge_value' => 'unread_count',
],
```

### **2. Notifications Temps Réel**
```javascript
// Auto-refresh des messages
setInterval(function() {
    fetch('/messagerie/api/unread-count')
        .then(response => response.json())
        .then(data => {
            updateUnreadBadge(data.unread_count);
        });
}, 30000);
```

### **3. API Endpoints**
```php
GET /messagerie/api/unread-count    # Retourne le nombre de messages non lus
GET /messagerie/api/recent          # Retourne les 5 conversations récentes
POST /messagerie/{id}/marquer-lu    # Marque une conversation comme lue
```

---

## 🚀 Fonctionnalités Avancées

### **1. Notifications Visuelles**
- 🔴 **Badge rouge** : Nombre de messages non lus
- 🔔 **Icône notification** : Indicateur visuel
- ⚡ **Mise à jour automatique** : Rafraîchissement périodique
- 📱 **Design responsive** : Compatible tous écrans

### **2. Expérience Utilisateur**
- 🎨 **Interface moderne** : Design épuré et professionnel
- ⚡ **Navigation fluide** : Transitions et animations
- 📱 **Mobile-first** : Optimisé pour smartphones
- 🔍 **Recherche intuitive** : Filtres et recherche rapide

### **3. Performance**
- 📊 **Requêtes optimisées** : Repositories efficaces
- 💾 **Cache intelligent** : Mise en cache des données
- 🚀 **Chargement rapide** : Interface réactive
- 📈 **Scalabilité** : Architecture extensible

---

## 📝 Utilisation

### **1. Pour les Administrateurs**
1. **Accéder à la messagerie** via le menu principal
2. **Créer une conversation** avec gestionnaires ou locataires
3. **Gérer les communications** et suivre les échanges
4. **Modérer si nécessaire** les conversations

### **2. Pour les Gestionnaires**
1. **Contacter ses locataires** pour les questions courantes
2. **Communiquer avec l'admin** pour les demandes spéciales
3. **Collaborer avec d'autres gestionnaires** si nécessaire
4. **Suivre l'historique** des communications

### **3. Pour les Locataires**
1. **Poser des questions** à leur gestionnaire
2. **Signaler des problèmes** via la messagerie
3. **Recevoir des réponses** rapides et traçables
4. **Consulter l'historique** de leurs échanges

---

## 🔧 Configuration

### **1. Base de Données**
```sql
-- Tables créées automatiquement
CREATE TABLE conversation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    initiator_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    last_message_at DATETIME
);

CREATE TABLE message (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at DATETIME NOT NULL,
    read_at DATETIME,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
```

### **2. Paramètres Système**
```yaml
# Configuration dans services.yaml
parameters:
    messaging.max_message_length: 1000
    messaging.auto_refresh_interval: 30
    messaging.max_participants: 10
```

---

## 📞 Support et Maintenance

### **1. Monitoring**
- 📊 **Statistiques d'utilisation** : Conversations, messages
- 🔍 **Logs de sécurité** : Tentatives d'accès non autorisées
- ⚡ **Performance** : Temps de réponse, requêtes
- 📈 **Croissance** : Évolution de l'utilisation

### **2. Maintenance**
- 🗑️ **Nettoyage automatique** : Conversations inactives
- 💾 **Sauvegarde** : Conservation des données importantes
- 🔄 **Mise à jour** : Évolutions et améliorations
- 🛠️ **Support technique** : Résolution des problèmes

---

## 🎯 Avantages du Système

### **1. Communication Efficace**
- ✅ **Messages instantanés** : Communication en temps réel
- ✅ **Traçabilité complète** : Historique des échanges
- ✅ **Notifications automatiques** : Ne rien manquer
- ✅ **Interface intuitive** : Facile à utiliser

### **2. Sécurité et Confidentialité**
- ✅ **Accès contrôlé** : Seuls les participants autorisés
- ✅ **Données sécurisées** : Chiffrement et protection
- ✅ **Audit trail** : Traçabilité des actions
- ✅ **Conformité** : Respect des réglementations

### **3. Intégration Parfaite**
- ✅ **Menu intégré** : Accessible depuis partout
- ✅ **Design cohérent** : Même style que l'application
- ✅ **Responsive** : Fonctionne sur tous les appareils
- ✅ **Performance** : Optimisé pour la vitesse

---

**Date de mise à jour :** 12 octobre 2025  
**Version :** 1.0  
**Statut :** ✅ Implémenté et fonctionnel
