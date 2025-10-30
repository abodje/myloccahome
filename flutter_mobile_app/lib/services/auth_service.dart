import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user_model.dart';
import '../models/tenant_model.dart';
import '../models/app_settings_model.dart';
import 'api_service.dart';

class AuthService extends ChangeNotifier {
  final ApiService _apiService;
  final SharedPreferences _prefs;

  UserModel? _user;
  TenantModel? _tenant;
  AppSettingsModel? _settings;
  String? _token;
  bool _isAuthenticated = false;

  // Keys for SharedPreferences
  static const String _tokenKey = 'auth_token';
  static const String _userKey = 'auth_user';
  static const String _tenantKey = 'auth_tenant';
  static const String _settingsKey = 'app_settings';

  AuthService(this._apiService, this._prefs) {
    _loadAuthData();
  }

  // Getters
  UserModel? get user => _user;
  TenantModel? get tenant => _tenant;
  AppSettingsModel? get settings => _settings;
  String? get token => _token;
  bool get isAuthenticated => _isAuthenticated;

  Future<void> _loadAuthData() async {
    _token = _prefs.getString(_tokenKey);

    if (_token != null && _token!.isNotEmpty) {
      try {
        final userJson = _prefs.getString(_userKey);
        if (userJson != null) {
          _user = UserModel.fromJson(jsonDecode(userJson));
        }

        final tenantJson = _prefs.getString(_tenantKey);
        if (tenantJson != null) {
          _tenant = TenantModel.fromJson(jsonDecode(tenantJson));
        }

        final settingsJson = _prefs.getString(_settingsKey);
        if (settingsJson != null) {
          _settings = AppSettingsModel.fromJson(jsonDecode(settingsJson));
        }

        if (_user != null && _tenant != null && _settings != null) {
          _isAuthenticated = true;
        }
      } catch (e) {
        // If parsing fails, clear the stored data
        debugPrint('Failed to load auth data from cache: $e');
        await logout();
      }
    }
    notifyListeners();
  }

  Future<bool> login(String email, String password) async {
    try {
      final response = await _apiService.post('/login', {
        'email': email,
        'password': password,
      });

      if (response['success'] == true) {
        // Parse and store data
        _token = response['token'];
        _user = UserModel.fromJson(response['user']);
        _tenant = TenantModel.fromJson(response['tenant']);
        _settings = AppSettingsModel.fromJson(response['settings']);
        _isAuthenticated = true;

        // Save to SharedPreferences
        await _prefs.setString(_tokenKey, _token!);
        await _prefs.setString(_userKey, jsonEncode(_user!.toJson()));
        await _prefs.setString(_tenantKey, jsonEncode(_tenant!.toJson()));
        await _prefs.setString(_settingsKey, jsonEncode(_settings!.toJson()));

        notifyListeners();
        return true;
      } else {
        final errorMessage = response['message'] ?? 'Erreur de connexion';
        throw Exception(errorMessage);
      }
    } catch (e) {
      debugPrint('Login error: $e');
      rethrow;
    }
  }

  Future<void> logout() async {
    _token = null;
    _user = null;
    _tenant = null;
    _settings = null;
    _isAuthenticated = false;

    // Clear all related keys from SharedPreferences
    await _prefs.remove(_tokenKey);
    await _prefs.remove(_userKey);
    await _prefs.remove(_tenantKey);
    await _prefs.remove(_settingsKey);

    notifyListeners();
  }

  // API helper methods
  Future<Map<String, dynamic>> get(String endpoint) async {
    if (!_isAuthenticated || _token == null) {
      throw Exception('Not authenticated');
    }
    try {
      return await _apiService.get(endpoint, token: _token);
    } on UnauthorizedException {
      await logout();
      rethrow;
    }
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
      throw Exception('Not authenticated');
    }
    try {
      return await _apiService.put(endpoint, data, token: _token);
    } on UnauthorizedException {
      await logout();
      rethrow;
    }
  }
}
