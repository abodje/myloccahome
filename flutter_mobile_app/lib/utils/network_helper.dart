import 'dart:io';
import 'package:flutter/foundation.dart';

class NetworkHelper {
  /// Détermine l'URL de base selon la plateforme
  static String getBaseUrl() {
    // En mode debug, on peut détecter automatiquement
    if (kDebugMode) {
      // Pour Android émulateur
      if (Platform.isAndroid) {
        // Retourner 10.0.2.2 pour l'émulateur Android (localhost de la machine hôte)
        return 'http://10.0.2.2:8000/api/tenant';
      }
      // Pour iOS simulateur, localhost fonctionne
      if (Platform.isIOS) {
        return 'http://localhost:8000/api/tenant';
      }
    }

    // Par défaut (ou pour appareils physiques, à configurer manuellement)
    return 'http://localhost:8000/api/tenant';
  }

  /// Vérifie la connectivité réseau
  static Future<bool> checkConnectivity(String url) async {
    try {
      final uri = Uri.parse(url);
      final client = HttpClient();
      final request = await client.getUrl(uri).timeout(
            const Duration(seconds: 5),
          );
      final response = await request.close().timeout(
            const Duration(seconds: 5),
          );
      client.close();
      return response.statusCode < 500;
    } catch (e) {
      if (kDebugMode) {
        debugPrint('❌ Erreur de connectivité: $e');
      }
      return false;
    }
  }

  /// Affiche un message d'aide pour configurer l'URL
  static String getSetupInstructions() {
    return '''
Pour configurer l'URL du backend:

1. Sur émulateur Android:
   → Utilisez: http://10.0.2.2:8000/api/tenant

2. Sur appareil physique:
   → Trouvez l'IP locale de votre PC (ipconfig sur Windows)
   → Utilisez: http://VOTRE_IP:8000/api/tenant
   → Exemple: http://192.168.1.100:8000/api/tenant

3. Modifiez api_service.dart ligne 7:
   static const String baseUrl = 'http://VOTRE_URL/api/tenant';
''';
  }
}
