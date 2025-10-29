import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

class ApiService {
  // ============================================
  // ⚠️ CONFIGURATION IMPORTANTE ⚠️
  // ============================================
  // Pour APPAREIL PHYSIQUE, décommentez la ligne ci-dessous
  // et remplacez 192.168.1.54 par VOTRE IP LOCALE (voir ipconfig)
  // ============================================
  // static const String baseUrlOverride = 'http://192.168.1.54:8000/api/tenant';

  // Configuration automatique selon la plateforme
  static String get baseUrl {
    // Si une URL personnalisée est définie (pour appareil physique), l'utiliser
    // Décommentez la ligne ci-dessus si vous testez sur un appareil physique

    if (kIsWeb) {
      return 'http://localhost:8000/api/tenant';
    }

    // Pour Android émulateur
    if (Platform.isAndroid) {
      // 10.0.2.2 est l'adresse spéciale pour accéder à localhost de la machine hôte
      // C'est automatique, mais si vous utilisez un appareil physique, décommentez
      // la baseUrlOverride ci-dessus et mettez votre IP locale
      return 'http://192.168.1.54:8000/api/tenant';
    }

    // Pour iOS simulateur, localhost fonctionne
    if (Platform.isIOS) {
      return 'http://192.168.1.54:8000/api/tenant';
    }

    // Par défaut (Linux, Windows desktop, etc.)
    return 'http://192.168.1.54:8000/api/tenant';
  }

  Future<Map<String, dynamic>> post(
    String endpoint,
    Map<String, dynamic> data,
  ) async {
    try {
      final url = Uri.parse('$baseUrl$endpoint');

      // Debug: Afficher les données envoyées (sans le mot de passe)
      final debugData = Map<String, dynamic>.from(data);
      debugData['password'] = '***';
      if (kDebugMode) {
        debugPrint('📤 POST $url');
        debugPrint('📦 Data: ${jsonEncode(debugData)}');
      }

      final response = await http.post(
        url,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
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
    String? email,
  }) async {
    try {
      final headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
        // Utiliser l'email fourni ou extraire du token
        headers['X-User-Email'] = email ?? _getEmailFromToken(token);
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
    String? email,
  }) async {
    try {
      final headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
        headers['X-User-Email'] = email ?? _getEmailFromToken(token);
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
      // Extraire le message d'erreur de l'API
      final errorMessage = body['message'] ?? 'Erreur serveur: $statusCode';
      throw Exception(errorMessage);
    }
  }

  String _getEmailFromToken(String token) {
    // Décoder le token base64 (temporaire - à améliorer avec JWT)
    try {
      final decoded = utf8.decode(base64Decode(token));
      final parts = decoded.split(':');
      if (parts.isNotEmpty) {
        return parts[0];
      }
    } catch (e) {
      // Ignorer les erreurs de décodage
    }
    return '';
  }
}
