import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

class ApiService {
  // ============================================
  // ⚠️ CONFIGURATION IMPORTANTE ⚠️
  // ============================================
  // Pour APPAREIL PHYSIQUE, décommentez la ligne ci-dessous
  // et remplacez 192.168.1.54 par VOTRE IP LOCALE (voir ipconfig)
  // ============================================
  // static const String baseUrlOverride = 'http://192.168.1.54:8000'; // Changed to just the base URL

  // Configuration automatique selon la plateforme
  static String get baseUrl {
    return 'https://app.lokapro.tech'; // Changed to just the base URL
  }

  Future<Map<String, dynamic>> post(
    String endpoint,
    Map<String, dynamic> data, {
    String? token,
  }) async {
    try {
      final url = Uri.parse('$baseUrl$endpoint');

      final headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
      }

      // Debug: Afficher les données envoyées (sans le mot de passe)
      final debugData = Map<String, dynamic>.from(data);
      if (debugData.containsKey('password')) {
        debugData['password'] = '***';
      }
      if (kDebugMode) {
        debugPrint('📤 POST $url');
        debugPrint('📦 Data: ${jsonEncode(debugData)}');
      }

      final response = await http.post(
        url,
        headers: headers,
        body: jsonEncode(data),
      );

      // Debug: Afficher la réponse
      if (kDebugMode) {
        debugPrint('📥 Response status: ${response.statusCode}');
        debugPrint('📥 Response body: ${response.body}');
      }

      return _handleResponse(response);
    } catch (e) {
      if (kDebugMode) {
        debugPrint('❌ Erreur réseau: $e');
        debugPrint('❌ URL tentée: $baseUrl$endpoint');
        debugPrint('');
        debugPrint('💡 AIDE:');
        debugPrint(
            '   - Si vous êtes sur APPAREIL PHYSIQUE, utilisez votre IP locale');
        debugPrint('   - Votre IP locale: 192.168.1.54');
        debugPrint('   - Décommentez baseUrlOverride dans api_service.dart');
      }
      throw Exception('Erreur réseau: $e');
    }
  }

  Future<Map<String, dynamic>> get(
    String endpoint, {
    String? token,
  }) async {
    try {
      final headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
      }

      final response = await http.get(
        Uri.parse('$baseUrl$endpoint'),
        headers: headers,
      );

      return _handleResponse(response);
    } catch (e) {
      throw Exception('Erreur réseau: $e');
    }
  }

  Future<Map<String, dynamic>> put(
    String endpoint,
    Map<String, dynamic> data, {
    String? token,
  }) async {
    try {
      final headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
      }

      final response = await http.put(
        Uri.parse('$baseUrl$endpoint'),
        headers: headers,
        body: jsonEncode(data),
      );

      return _handleResponse(response);
    } catch (e) {
      throw Exception('Erreur réseau: $e');
    }
  }

  Map<String, dynamic> _handleResponse(http.Response response) {
    final statusCode = response.statusCode;
    Map<String, dynamic> body;

    try {
      body = jsonDecode(response.body) as Map<String, dynamic>;
    } catch (e) {
      throw Exception('Réponse invalide du serveur: ${response.body}');
    }

    if (statusCode >= 200 && statusCode < 300) {
      return body;
    } else {
      // Gérer les erreurs d'authentification spécifiquement
      if (statusCode == 401) {
        final errorMessage =
            body['message'] ?? 'Session expirée. Veuillez vous reconnecter.';
        throw UnauthorizedException(errorMessage);
      }

      // Extraire le message d'erreur de l'API
      final errorMessage = body['message'] ?? 'Erreur serveur: $statusCode';
      throw Exception(errorMessage);
    }
  }
}

/// Exception personnalisée pour les erreurs d'authentification
class UnauthorizedException implements Exception {
  final String message;
  UnauthorizedException(this.message);

  @override
  String toString() => message;
}
