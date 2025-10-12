# 🔧 Guide - Gestion automatique des demandes de maintenance

## 📋 Vue d'ensemble

MYLOCCA dispose d'un système complet de **gestion automatique des demandes de maintenance** qui permet :

1. ✅ **Attribution automatique** des demandes aux intervenants disponibles
2. ⚠️ **Notifications urgentes** pour les demandes prioritaires
3. 🔴 **Alertes de retard** pour les demandes non traitées à temps

---

## 🎯 FONCTIONNALITÉS

### 1. Attribution automatique des demandes

**Principe** : Les nouvelles demandes de maintenance sont automatiquement attribuées à un intervenant selon des critères intelligents.

#### Critères d'attribution (par ordre de priorité)

1. **Manager de la propriété** : Si la propriété a un propriétaire avec compte Manager
2. **Manager disponible** : Manager avec la charge de travail la plus faible
3. **Administrateur** : En dernier recours, attribution à un admin

#### Processus

```
Nouvelle demande créée
    ↓
Statut = "Nouvelle"
    ↓
Attribution automatique
    ↓
Statut = "En cours"
    ↓
Notification envoyée à l'intervenant
```

---

### 2. Notifications urgentes

**Principe** : Les demandes marquées comme "Urgent" déclenchent automatiquement des notifications à tous les administrateurs.

#### Critères de déclenchement

- Priorité = "Urgent"
- Statut ≠ "Terminée"

#### Destinataires

- ✅ Tous les utilisateurs avec rôle `ROLE_ADMIN`
- ✅ Email avec sujet : "⚠️ Demande de maintenance URGENTE"

#### Informations incluses

- Titre et description de la demande
- Propriété et locataire concernés
- Date de création et date prévue
- Lien direct vers la demande

---

### 3. Alertes de retard

**Principe** : Les demandes dont la date prévue est dépassée sont automatiquement détectées et des alertes sont envoyées.

#### Critères de déclenchement

- Date prévue < Date actuelle
- Statut ≠ "Terminée"

#### Actions automatiques

1. **Changement de statut** : La demande passe en statut "En retard"
2. **Notification admin** : Email envoyé à tous les admins
3. **Calcul du retard** : Nombre de jours de retard affiché

#### Email d'alerte

- Sujet : "🔴 Demande de maintenance EN RETARD"
- Nombre de jours de retard
- Toutes les informations de la demande

---

## 🖥️ UTILISATION

### Commande console

#### Syntaxe complète

```bash
php bin/console app:maintenance:manage [OPTIONS]
```

#### Options disponibles

| Option | Raccourci | Description |
|--------|-----------|-------------|
| `--assign` | `-a` | Attribuer automatiquement les demandes non assignées |
| `--urgent` | `-u` | Envoyer les notifications pour les demandes urgentes |
| `--overdue` | `-o` | Vérifier et notifier les demandes en retard |
| `--all` | | Exécuter toutes les actions (par défaut) |

#### Exemples d'utilisation

**Exécuter toutes les actions** :
```bash
php bin/console app:maintenance:manage
```

**Uniquement l'attribution** :
```bash
php bin/console app:maintenance:manage --assign
```

**Attribution + notifications urgentes** :
```bash
php bin/console app:maintenance:manage -a -u
```

**Vérifier les retards uniquement** :
```bash
php bin/console app:maintenance:manage --overdue
```

---

### Configuration CRON (automatisation)

Pour une gestion entièrement automatisée, configurez ces tâches CRON :

#### Recommandations

```cron
# Attribution automatique - toutes les heures
0 * * * * cd /path/to/mylocca && php bin/console app:maintenance:manage --assign

# Notifications urgentes - toutes les 2 heures
0 */2 * * * cd /path/to/mylocca && php bin/console app:maintenance:manage --urgent

# Alertes de retard - une fois par jour à 9h
0 9 * * * cd /path/to/mylocca && php bin/console app:maintenance:manage --overdue

# Ou tout en un - une fois par heure
0 * * * * cd /path/to/mylocca && php bin/console app:maintenance:manage
```

---

## 📧 TEMPLATES D'EMAILS

### 1. Notification d'attribution

**Template** : `templates/emails/maintenance_assignment.html.twig`

**Variables disponibles** :
- `user` : Intervenant assigné
- `request` : Demande de maintenance
- `company` : Informations de l'entreprise

**Personnalisation** :
- Modifier le template pour adapter le design
- Ajouter des informations supplémentaires
- Personnaliser les couleurs et styles

---

### 2. Alerte urgente

**Template** : `templates/emails/urgent_maintenance.html.twig`

**Couleur** : Rouge (#dc3545)
**Icône** : 🚨

**Caractéristiques** :
- Design d'alerte avec fond jaune
- Bouton d'action proéminent
- Informations complètes de la demande

---

### 3. Alerte de retard

**Template** : `templates/emails/overdue_maintenance.html.twig`

**Couleur** : Rouge foncé (#842029)
**Icône** : ⏰

**Caractéristiques** :
- Calcul automatique du nombre de jours de retard
- Design d'urgence
- Rappel de la date prévue dépassée

---

## 🛠️ CONFIGURATION

### Paramètres email (Admin → Paramètres → Email)

Pour que les notifications fonctionnent, configurez :

1. **SMTP** : Serveur d'envoi d'emails
2. **Email expéditeur** : `email_from` dans les paramètres
3. **Nom de l'entreprise** : Affiché dans les emails

### Test de configuration

```bash
# Tester l'envoi d'emails
php bin/console app:email:test votre@email.com
```

---

## 🎬 WORKFLOW COMPLET

### Scénario 1 : Nouvelle demande normale

```
1. Locataire crée une demande
   └─> Priorité = "Normale"
   
2. Attribution automatique (CRON ou manuelle)
   └─> Demande assignée au manager de la propriété
   └─> Email envoyé à l'intervenant
   
3. Intervenant traite la demande
   └─> Statut = "En cours" → "Terminée"
```

---

### Scénario 2 : Demande urgente

```
1. Admin crée une demande
   └─> Priorité = "Urgent"
   
2. Attribution automatique
   └─> Demande assignée
   └─> Email d'attribution envoyé
   
3. Notifications urgentes (CRON)
   └─> 🚨 Tous les admins reçoivent une alerte
   └─> Sujet : "⚠️ Demande de maintenance URGENTE"
   
4. Traitement prioritaire
   └─> Intervention rapide
```

---

### Scénario 3 : Demande en retard

```
1. Demande avec date prévue = 2025-10-05
   └─> Date actuelle = 2025-10-12
   └─> Retard = 7 jours
   
2. Vérification automatique (CRON)
   └─> Statut changé en "En retard"
   └─> 🔴 Alertes envoyées aux admins
   
3. Email d'alerte
   └─> Affiche : "Retard : 7 jour(s)"
   └─> Lien vers la demande
   
4. Action corrective
   └─> Intervention d'urgence
```

---

## 🔍 MONITORING

### Logs de l'application

Les actions automatiques sont enregistrées dans les logs :

```bash
# Consulter les logs
tail -f var/log/prod.log

# Filtrer les logs de maintenance
grep "maintenance" var/log/prod.log
```

---

### Tableau de bord admin

Le dashboard admin affiche :

- ✅ Nombre de demandes nouvelles
- ⚠️ Nombre de demandes urgentes
- 🔴 Nombre de demandes en retard
- 📊 Statistiques par statut

---

## 💡 BONNES PRATIQUES

### 1. Attribution

- Vérifier régulièrement la charge de travail des managers
- Équilibrer les attributions
- Former les intervenants aux procédures

### 2. Priorités

- **Urgent** : Danger immédiat, panne majeure
- **Haute** : Problème important à traiter rapidement
- **Normale** : Maintenance standard
- **Basse** : Amélioration, non urgent

### 3. Délais

- Urgent : Traiter dans les 24h
- Haute : Traiter dans les 48-72h
- Normale : Traiter dans la semaine
- Basse : Planifier selon disponibilité

---

## 🔧 MAINTENANCE DU SYSTÈME

### Nettoyage des anciennes demandes

```bash
# Archiver les demandes terminées de plus de 1 an
php bin/console app:maintenance:archive --older-than=1year
```

### Statistiques

```bash
# Afficher les statistiques de maintenance
php bin/console app:maintenance:stats
```

---

## 📊 INDICATEURS DE PERFORMANCE

### KPI à suivre

1. **Temps de première réponse** : Délai entre création et première action
2. **Temps de résolution** : Délai entre création et clôture
3. **Taux de retard** : % de demandes dépassant la date prévue
4. **Satisfaction** : Retours des locataires

### Rapports

Disponibles dans : **Admin → Rapports → Maintenance**

---

## 🎓 FORMATION

### Pour les managers

1. Consulter les demandes assignées
2. Mettre à jour le statut régulièrement
3. Ajouter des notes et photos
4. Clôturer avec compte-rendu

### Pour les admins

1. Configurer les paramètres email
2. Surveiller le tableau de bord
3. Intervenir sur les demandes urgentes/en retard
4. Analyser les rapports

---

## 🐛 DÉPANNAGE

### Les emails ne sont pas envoyés

1. Vérifier la configuration SMTP
2. Tester avec `php bin/console app:email:test`
3. Consulter les logs d'erreurs
4. Vérifier le pare-feu

### Les demandes ne sont pas attribuées

1. Vérifier qu'il existe des utilisateurs Manager/Admin
2. Exécuter manuellement : `php bin/console app:maintenance:manage -a`
3. Consulter les logs pour les erreurs

### Les notifications urgentes ne partent pas

1. Vérifier que les demandes sont bien marquées "Urgent"
2. Vérifier qu'elles ne sont pas "Terminée"
3. Exécuter manuellement : `php bin/console app:maintenance:manage -u`

---

## 🚀 ÉVOLUTIONS FUTURES

### Fonctionnalités prévues

- [ ] Attribution basée sur les compétences
- [ ] Système de calendrier des intervenants
- [ ] Application mobile pour les intervenants
- [ ] Géolocalisation des demandes
- [ ] Estimation automatique des coûts
- [ ] Planification préventive

---

## 📞 SUPPORT

Pour toute question ou problème :

1. Consulter les logs : `var/log/prod.log`
2. Vérifier la documentation : Ce fichier
3. Tester en mode développement
4. Contacter l'équipe technique

---

## ✅ CHECKLIST DE MISE EN PRODUCTION

- [ ] Configuration SMTP validée
- [ ] Test d'envoi d'emails réussi
- [ ] CRON configuré
- [ ] Utilisateurs Manager/Admin créés
- [ ] Templates d'emails personnalisés
- [ ] Première attribution testée
- [ ] Notification urgente testée
- [ ] Alerte de retard testée
- [ ] Documentation lue par l'équipe
- [ ] Formation des utilisateurs effectuée

---

**📅 Version** : 1.0
**📄 Date** : 12 Octobre 2025
**✨ Statut** : Opérationnel

---

**🎊 Le système de gestion automatique de maintenance est maintenant pleinement opérationnel !**

