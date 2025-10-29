# Application Mobile Flutter - MyLocca (Espace Locataire)

Application mobile Flutter permettant aux locataires de se connecter et accéder à leurs informations.

## Prérequis

- Flutter SDK (>=3.0.0)
- Dart SDK
- Un serveur Symfony avec l'API disponible (voir `/api/tenant/login`)

## Installation

1. **Installer les dépendances Flutter**:
   ```bash
   cd flutter_mobile_app
   flutter pub get
   ```

2. **Configurer l'URL de l'API**:
   
   Modifiez le fichier `lib/services/api_service.dart` pour définir l'URL de base de votre API:
   
   ```dart
   static const String baseUrl = 'http://localhost:8000/api/tenant';
   ```
   
   **Important**: Pour tester sur un appareil physique:
   - Remplacez `localhost` par l'IP locale de votre machine (ex: `192.168.1.100`)
   - Assurez-vous que le port 8000 est accessible depuis votre réseau local
   - Pour Android, vous pouvez aussi utiliser `10.0.2.2` (émulateur Android)

## API Backend

L'application utilise l'API Symfony disponible dans `src/Controller/Api/TenantApiController.php`.

### Endpoint de connexion

**POST** `/api/tenant/login`

Body:
```json
{
  "email": "user@example.com",
  "password": "password"
}
```

Response (succès):
```json
{
  "success": true,
  "token": "base64_encoded_token",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "roles": ["ROLE_TENANT", "ROLE_USER"]
  },
  "tenant": {
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "email": "user@example.com",
    "phone": "+33123456789"
  }
}
```

## Structure du projet

```
flutter_mobile_app/
├── lib/
│   ├── main.dart                    # Point d'entrée de l'application
│   ├── models/                      # Modèles de données
│   │   ├── user_model.dart
│   │   └── tenant_model.dart
│   ├── services/                    # Services (API, Auth)
│   │   ├── api_service.dart
│   │   └── auth_service.dart
│   └── screens/                     # Écrans de l'application
│       ├── login_screen.dart
│       └── dashboard_screen.dart
├── pubspec.yaml                     # Dépendances Flutter
└── README.md
```

## Exécution

1. **Démarrer le serveur Symfony**:
   ```bash
   symfony server:start
   # ou
   php -S localhost:8000 -t public
   ```

2. **Lancer l'application Flutter**:
   ```bash
   flutter run
   ```

## Fonctionnalités implémentées

- ✅ Authentification (connexion)
- ✅ Écran de connexion avec validation
- ✅ Stockage du token localement
- ✅ Dashboard de base
- ⏳ Gestion du profil locataire
- ⏳ Liste des paiements
- ⏳ Liste des demandes d'intervention
- ⏳ Gestion des documents

## Prochaines étapes

1. Améliorer l'authentification avec JWT
2. Implémenter les autres écrans (paiements, demandes, documents)
3. Ajouter la gestion des erreurs réseau
4. Implémenter le rafraîchissement des données
5. Ajouter des tests unitaires

## Notes

- L'authentification utilise actuellement un token base64 temporaire. Il est recommandé d'implémenter JWT pour la production.
- Pour les tests, vous pouvez créer un utilisateur locataire via la commande Symfony ou directement en base de données.

