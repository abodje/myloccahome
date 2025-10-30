import '../models/accounting_model.dart';
import '../models/user_model.dart';
import '../models/tenant_model.dart';
import '../models/property_model.dart';
import '../models/lease_model.dart';
import 'auth_service.dart';

class TenantDataService {
  final AuthService _authService;

  TenantDataService(this._authService);

  // Dashboard
  Future<Map<String, dynamic>> getDashboard() async {
    return await _authService.get('/api/tenant/dashboard');
  }

  // Paiements
  Future<Map<String, dynamic>> getPayments() async {
    return await _authService.get('/api/tenant/payments');
  }

  Future<String> initiatePayment(int paymentId) async {
    final response = await _authService.post('/api/tenant/payments/$paymentId/pay', {});
    return response['transaction']['paymentUrl'];
  }

  // Demandes
  Future<Map<String, dynamic>> getRequests() async {
    return await _authService.get('/api/tenant/requests');
  }

  // Créer une demande
  Future<Map<String, dynamic>> createRequest({
    required String title,
    required String category,
    String? description,
    String? priority,
  }) async {
    return await _authService.post('/api/tenant/requests', {
      'title': title,
      'category': category,
      'description': description ?? '',
      'priority': priority ?? 'Normale',
    });
  }

  // Documents
  Future<Map<String, dynamic>> getDocuments() async {
    return await _authService.get('/api/tenant/documents');
  }

  // Propriété
  Future<Map<String, dynamic>> getProperty() async {
    return await _authService.get('/api/tenant/property');
  }

  // Détail de la propriété
  Future<Map<String, dynamic>> getPropertyDetails() async {
    final response = await _authService.get('/api/tenant/property');
    return {
      'property': PropertyModel.fromJson(response['property']),
      'lease': LeaseModel.fromJson(response['lease']),
    };
  }

  // Comptabilité
  Future<AccountingDataModel> getAccounting() async {
    final response = await _authService.get('/api/tenant/accounting?entries=1&entriesLimit=20');
    return AccountingDataModel.fromJson(response);
  }

  // Profil
  Future<Map<String, dynamic>> getProfile() async {
    return await _authService.get('/api/tenant/profile');
  }

  // Mettre à jour le profil
  Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> data) async {
    return await _authService.put('/api/tenant/profile', data);
  }
}
