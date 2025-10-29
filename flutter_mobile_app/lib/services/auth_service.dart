import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user_model.dart';
import '../models/tenant_model.dart';
import 'api_service.dart';

class AuthService extends ChangeNotifier {
  final ApiService _apiService;
  final SharedPreferences _prefs;

  UserModel? _user;
  TenantModel? _tenant;
  String? _token;
  bool _isAuthenticated = false;

  AuthService(this._apiService, this._prefs) {
    _loadAuthData();
  }

  UserModel? get user => _user;
  TenantModel? get tenant => _tenant;
  String? get token => _token;
  String? get userEmail => _user?.email ?? _tenant?.email;
  bool get isAuthenticated => _isAuthenticated;

  Future<void> _loadAuthData() async {
    _token = _prefs.getString('auth_token');
    if (_token != null && _token!.isNotEmpty) {
      _isAuthenticated = true;
      // Charger les données utilisateur depuis les préférences ou l'API
      notifyListeners();
    }
  }

  Future<bool> login(String email, String password) async {
    try {
      final response = await _apiService.post('/login', {
        'email': email,
        'password': password,
      });

      if (response['success'] == true) {
        _token = response['token'];
        _user = UserModel.fromJson(response['user']);
        _tenant = TenantModel.fromJson(response['tenant']);
        _isAuthenticated = true;

        // Sauvegarder le token
        await _prefs.setString('auth_token', _token!);
        await _prefs.setString('user_email', email);

        notifyListeners();
        return true;
      } else {
        // Afficher le message d'erreur de l'API
        final errorMessage = response['message'] ?? 'Erreur de connexion';
        debugPrint('Erreur API: $errorMessage');
        throw Exception(errorMessage);
      }
    } catch (e) {
      debugPrint('Erreur de connexion: $e');
      rethrow; // Relancer l'erreur pour que l'écran puisse l'afficher
    }
  }

  Future<void> logout() async {
    _token = null;
    _user = null;
    _tenant = null;
    _isAuthenticated = false;

    await _prefs.remove('auth_token');
    await _prefs.remove('user_email');

    notifyListeners();
  }

  // Méthode helper pour les requêtes API authentifiées
  Future<Map<String, dynamic>> get(String endpoint) async {
    if (!_isAuthenticated || _token == null) {
      throw Exception('Non authentifié');
    }
    return _apiService.get(endpoint, token: _token, email: userEmail);
  }

  Future<Map<String, dynamic>> post(
    String endpoint,
    Map<String, dynamic> data,
  ) async {
    return _apiService.post(endpoint, data);
  }

  Future<Map<String, dynamic>> put(
    String endpoint,
    Map<String, dynamic> data,
  ) async {
    if (!_isAuthenticated || _token == null) {
      throw Exception('Non authentifié');
    }
    return _apiService.put(endpoint, data, token: _token, email: userEmail);
  }
}
