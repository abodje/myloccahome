import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:intl/intl.dart';
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
                          const SizedBox(height: 24),
                          _buildWelcomeMessage(context, _dashboardData!),
                          const SizedBox(height: 16),
                          _buildTenantInfoCard(context, _dashboardData!),
                          const SizedBox(height: 16),
                          _buildQuickActions(context),
                          const SizedBox(height: 16),
                          _buildBalanceCard(context, currencySymbol, _dashboardData!),
                          const SizedBox(height: 16),
                          _buildManagerCard(context, _dashboardData!),
                          const SizedBox(height: 24),
                        ],
                      ),
                    ),
                  ),
                ),
    );
  }

  Widget _buildWelcomeMessage(BuildContext context, DashboardModel data) {
    return Text(
      'Bonjour, ${data.tenant.fullName.split(' ').first}',
      style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold),
    );
  }

  Widget _buildErrorView() {
    // ... (no changes needed here)
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
    // ... (no changes needed here)
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
                          spacing: 16.0,
                          runSpacing: 8.0,
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
    // ... (no changes needed here)
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
    // ... (no changes needed here)
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

  Widget _buildBalanceCard(BuildContext context, String currencySymbol, DashboardModel data) {
    final balances = data.balances;
    final balancePercentage = (balances.totalDue > 0) ? balances.toPay / balances.totalDue : 0.0;

    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Votre situation financière', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Solde actuel', style: Theme.of(context).textTheme.bodyMedium),
                Text(
                  '${balances.soldAt.toStringAsFixed(2)} $currencySymbol',
                  style: Theme.of(context).textTheme.bodyLarge?.copyWith(color: AppTheme.primaryOrange, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 12),
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: LinearProgressIndicator(
                value: balancePercentage,
                minHeight: 10,
                backgroundColor: AppTheme.backgroundGrey,
                color: AppTheme.primaryBlue,
              ),
            ),
            const SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Solde à venir', style: Theme.of(context).textTheme.bodySmall),
                Text('${balances.toPay.toStringAsFixed(2)} $currencySymbol', style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppTheme.primaryBlue)),
              ],
            ),
            const Divider(height: 32),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: () => context.go('/accounting'),
                icon: const Icon(Icons.account_balance_wallet_outlined),
                label: const Text('CONSULTER MON COMPTE'),
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppTheme.primaryOrange,
                  side: BorderSide(color: AppTheme.primaryOrange.withOpacity(0.5)),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildManagerCard(BuildContext context, DashboardModel data) {
    final manager = data.manager;
    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Votre gestionnaire', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            if (manager != null) ...[
              Row(
                children: [
                  const CircleAvatar(
                    radius: 24,
                    backgroundColor: AppTheme.lightBlue,
                    child: Icon(Icons.person_outline, color: AppTheme.primaryBlue, size: 28),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(manager.name, style: Theme.of(context).textTheme.bodyLarge?.copyWith(fontWeight: FontWeight.w600)),
                        if (manager.email != null) Text(manager.email!, style: Theme.of(context).textTheme.bodySmall),
                      ],
                    ),
                  ),
                  if (manager.phone != null && manager.phone!.isNotEmpty)
                    IconButton(
                      icon: const Icon(Icons.phone, color: AppTheme.primaryBlue, size: 28),
                      onPressed: () => _launchUri(Uri.parse('tel:${manager.phone}'), 'téléphone'),
                      tooltip: 'Appeler le gestionnaire',
                    ),
                ],
              ),
            ] else
              Text('Non renseigné', style: Theme.of(context).textTheme.bodySmall),
          ],
        ),
      ),
    );
  }
}
