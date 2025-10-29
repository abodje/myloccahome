import '../models/user_model.dart';
import '../models/tenant_model.dart';
import 'auth_service.dart';

class TenantDataService {
  final AuthService _authService;

  TenantDataService(this._authService);

  // Dashboard
  Future<Map<String, dynamic>> getDashboard() async {
    return await _authService.get('/dashboard');
  }

  // Paiements
  Future<Map<String, dynamic>> getPayments() async {
    return await _authService.get('/payments');
  }

  // Demandes
  Future<Map<String, dynamic>> getRequests() async {
    return await _authService.get('/requests');
  }

  // Créer une demande
  Future<Map<String, dynamic>> createRequest({
    required String title,
    required String category,
    String? description,
    String? priority,
  }) async {
    return await _authService.post('/requests', {
      'title': title,
      'category': category,
      'description': description ?? '',
      'priority': priority ?? 'Normale',
    });
  }

  // Documents
  Future<Map<String, dynamic>> getDocuments() async {
    return await _authService.get('/documents');
  }

  // Propriété
  Future<Map<String, dynamic>> getProperty() async {
    return await _authService.get('/property');
  }

  // Comptabilité
  Future<Map<String, dynamic>> getAccounting() async {
    return await _authService.get('/accounting');
  }

  // Profil
  Future<Map<String, dynamic>> getProfile() async {
    return await _authService.get('/profile');
  }

  // Mettre à jour le profil
  Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> data) async {
    return await _authService.put('/profile', data);
  }
}
