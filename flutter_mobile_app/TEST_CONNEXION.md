# Guide de test de connexion Flutter → Backend

## ✅ Vérification que Flutter appelle bien le backend

### 1. Les logs dans Flutter

Lorsque vous essayez de vous connecter, regardez la console Flutter. Vous devriez voir :

```
📤 POST http://localhost:8000/api/tenant/login
📦 Data: {"email":"locataire@test.com","password":"***"}
📥 Response status: 200
📥 Response body: {"success":true,"token":"...","user":{...},"tenant":{...}}
```

Si vous ne voyez **aucun log**, le problème est dans l'envoi de la requête.

Si vous voyez une **erreur réseau**, le problème est la connexion entre Flutter et le backend.

### 2. Configuration de l'URL selon votre environnement

#### Sur émulateur Android
```dart
static const String baseUrl = 'http://10.0.2.2:8000/api/tenant';
```
> `10.0.2.2` est l'adresse spéciale pour accéder à localhost de la machine hôte depuis l'émulateur Android

#### Sur simulateur iOS
```dart
static const String baseUrl = 'http://localhost:8000/api/tenant';
```
> localhost fonctionne directement sur iOS

#### Sur appareil physique (Android/iOS)
```dart
static const String baseUrl = 'http://192.168.1.XXX:8000/api/tenant';
```
> Remplacez XXX par l'IP locale de votre PC (voir avec `ipconfig` sur Windows)

### 3. Vérifier que le backend est démarré

Avant de tester Flutter, vérifiez que le backend répond :

```bash
# Test avec curl
curl -X POST http://localhost:8000/api/tenant/login \
  -H "Content-Type: application/json" \
  -d '{"email":"locataire@test.com","password":"password123"}'

# Ou avec le script PHP
php test_flutter_backend_connection.php
```

### 4. Erreurs courantes

#### "Erreur réseau: SocketException"
→ Le serveur Symfony n'est pas démarré ou l'URL est incorrecte

#### "Erreur réseau: TimeoutException"
→ Le serveur ne répond pas (vérifiez le firewall, l'IP, le port)

#### "Identifiants invalides"
→ L'API répond bien, mais :
  - L'utilisateur n'existe pas
  - Le mot de passe est incorrect
  - L'utilisateur n'a pas le rôle ROLE_TENANT
  - Pas de profil Tenant associé

### 5. Test pas à pas

1. **Démarrer le serveur Symfony** :
   ```bash
   symfony server:start
   # Ou
   php -S localhost:8000 -t public
   ```

2. **Tester avec le script PHP** :
   ```bash
   php test_flutter_backend_connection.php
   ```
   → Doit afficher "✅ Login réussi !"

3. **Lancer Flutter en mode debug** :
   ```bash
   cd flutter_mobile_app
   flutter run
   ```

4. **Essayer de se connecter** :
   - Email: `locataire@test.com`
   - Mot de passe: `password123`

5. **Regarder les logs** dans la console Flutter

### 6. Vérification manuelle dans le code

Le code actuel dans `auth_service.dart` ligne 37 :

```dart
final response = await _apiService.post('/login', {
  'email': email,
  'password': password,
});
```

Cette ligne fait bien un appel HTTP POST vers `/api/tenant/login`.

La requête est envoyée dans `api_service.dart` ligne 27-34.

### 7. Dépannage

Si rien ne fonctionne, ajoutez ceci temporairement dans `login_screen.dart` :

```dart
// Dans _handleLogin, avant l'appel à authService.login
print('🔍 Tentative de connexion avec: ${_emailController.text}');
print('🔍 URL API: ${ApiService.baseUrl}/login');
```

Et regardez les logs dans la console Flutter.


