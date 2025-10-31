import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:intl/intl.dart'; // For date formatting
import '../../models/dashboard_model.dart';
import '../../services/auth_service.dart';
import '../../services/tenant_data_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/app_drawer.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({Key? key}) : super(key: key);

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  DashboardModel? _dashboardData;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  Future<void> _loadDashboard() async {
    final authService = Provider.of<AuthService>(context, listen: false);
    final dataService = TenantDataService(authService);

    if (!mounted) return;
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final data = await dataService.getDashboard();
      if (!mounted) return;
      setState(() {
        _dashboardData = DashboardModel.fromJson(data);
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _launchUri(Uri uri, String type) async {
    try {
      if (!await launchUrl(uri)) {
        throw Exception('Impossible de lancer l\'action: $uri');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur lors de l\'action $type: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final authService = Provider.of<AuthService>(context);
    final currencySymbol = authService.settings?.localization.defaultCurrency ?? '€';
    final formattedDate = DateFormat('dd/MM/yyyy').format(DateTime.now());

    return Scaffold(
      appBar: AppBar(
        backgroundColor: AppTheme.primaryBlue,
        foregroundColor: Colors.white,
        elevation: 0,
        title: const Text('Tableau de bord'),
        centerTitle: false,
      ),
      drawer: const AppDrawer(),
      backgroundColor: AppTheme.backgroundGrey,
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryBlue))
          : _error != null || _dashboardData == null
              ? _buildErrorView()
              : RefreshIndicator(
                  onRefresh: _loadDashboard,
                  color: AppTheme.primaryBlue,
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const SizedBox(height: 16),
                          _buildTenantInfoCard(context, _dashboardData!),
                          const SizedBox(height: 16),
                          _buildQuickActions(context),
                          const SizedBox(height: 16),
                          _buildCardsRow(context, formattedDate, currencySymbol, _dashboardData!),
                          const SizedBox(height: 24),
                        ],
                      ),
                    ),
                  ),
                ),
    );
  }

  Widget _buildErrorView() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, color: Colors.red, size: 60),
          const SizedBox(height: 16),
          Text('Erreur de chargement', style: Theme.of(context).textTheme.headlineSmall),
          const SizedBox(height: 8),
          Text(_error ?? 'Une erreur inconnue est survenue.', textAlign: TextAlign.center),
          const SizedBox(height: 16),
          ElevatedButton(onPressed: _loadDashboard, child: const Text('Réessayer')),
        ],
      ),
    );
  }

  Widget _buildTenantInfoCard(BuildContext context, DashboardModel data) {
    final tenant = data.tenant;
    final property = data.property;
    final lease = data.currentLease;

    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(tenant.fullName, style: Theme.of(context).textTheme.headlineSmall),
            const SizedBox(height: 8),
            if (tenant.id != null) Text('Compte N°${tenant.id}', style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.textLight)),
            if (property != null) ...[
              const Divider(height: 32),
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(property.fullAddress, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 16.0, // Horizontal space between items
                          runSpacing: 8.0,  // Vertical space between lines
                          children: [
                            if (property.surface != null)
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.square_foot, size: 16, color: AppTheme.textLight),
                                  const SizedBox(width: 4),
                                  Text('${property.surface} m²', style: Theme.of(context).textTheme.bodyMedium),
                                ],
                              ),
                            if (property.type != null)
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.home_work_outlined, size: 16, color: AppTheme.textLight),
                                  const SizedBox(width: 4),
                                  Text(property.type!, style: Theme.of(context).textTheme.bodyMedium),
                                ],
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 16),
                  const Icon(Icons.apartment, size: 50, color: AppTheme.primaryBlue),
                ],
              ),
            ],
            if (lease != null) ...[
              const Divider(height: 32),
              Row(
                children: [
                  const Icon(Icons.event_available_outlined, color: AppTheme.textLight),
                  const SizedBox(width: 8),
                  Text('Fin du bail le', style: Theme.of(context).textTheme.bodyMedium),
                  const Spacer(),
                  Text(lease.endDate, style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.bold)),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildQuickActions(BuildContext context) {
    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8.0),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          children: [
            _buildQuickActionButton(context, icon: Icons.description_outlined, label: 'Documents', onTap: () => context.go('/documents')),
            _buildQuickActionButton(context, icon: Icons.request_page_outlined, label: 'Demandes', onTap: () => context.go('/requests')),
            _buildQuickActionButton(context, icon: Icons.payment_outlined, label: 'Paiements', onTap: () => context.go('/payments')),
          ],
        ),
      ),
    );
  }

  Widget _buildQuickActionButton(BuildContext context, {required IconData icon, required String label, required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: AppTheme.primaryBlue, size: 28),
            const SizedBox(height: 6),
            Text(label, style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppTheme.primaryBlue, fontWeight: FontWeight.w600)),
          ],
        ),
      ),
    );
  }

  Widget _buildCardsRow(BuildContext context, String formattedDate, String currencySymbol, DashboardModel data) {
    final manager = data.manager;
    final balances = data.balances;

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: Card(
            elevation: 2,
            shadowColor: Colors.black.withOpacity(0.05),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Mon gestionnaire', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  if (manager != null) ...[
                    Text(manager.name, style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        if (manager.phone != null && manager.phone!.isNotEmpty) ...[
                          InkWell(
                            onTap: () => _launchUri(Uri.parse('tel:${manager.phone}'), 'téléphone'),
                            borderRadius: BorderRadius.circular(20),
                            child: const Icon(Icons.phone, color: AppTheme.primaryBlue, size: 24),
                          ),
                          const SizedBox(width: 16),
                        ],
                        if (manager.email != null && manager.email!.isNotEmpty) ...[
                          InkWell(
                            onTap: () => _launchUri(Uri.parse('mailto:${manager.email}'), 'e-mail'),
                            borderRadius: BorderRadius.circular(20),
                            child: const Icon(Icons.email_outlined, color: AppTheme.primaryBlue, size: 24),
                          ),
                        ],
                      ],
                    ),
                  ] else
                    Text('Non renseigné', style: Theme.of(context).textTheme.bodySmall),
                ],
              ),
            ),
          ),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Card(
            elevation: 2,
            shadowColor: Colors.black.withOpacity(0.05),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Solde au $formattedDate', style: Theme.of(context).textTheme.bodySmall),
                  const SizedBox(height: 8),
                  Text('${balances.soldAt.toStringAsFixed(2)} $currencySymbol', style: Theme.of(context).textTheme.headlineSmall?.copyWith(color: AppTheme.primaryOrange, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  Text('Solde à venir', style: Theme.of(context).textTheme.bodySmall),
                  Text('${balances.toPay.toStringAsFixed(2)} $currencySymbol', style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.primaryBlue)),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton(onPressed: () => context.go('/accounting'), child: const Text('CONSULTER')),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}
