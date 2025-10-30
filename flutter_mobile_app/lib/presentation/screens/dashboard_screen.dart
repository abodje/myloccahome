import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
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
  Map<String, dynamic>? _dashboardData;
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
        _dashboardData = data;
        _isLoading = false;
      });
    }
    catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final authService = Provider.of<AuthService>(context);
    final tenant = authService.tenant;
    final user = authService.user;
    final currencySymbol = authService.settings?.localization.defaultCurrency ?? '€';

    final now = DateTime.now();
    final formattedDate =
        '${now.day.toString().padLeft(2, '0')}/${now.month.toString().padLeft(2, '0')}/${now.year}';

    return Scaffold(
      appBar: AppBar(
        backgroundColor: AppTheme.primaryBlue, // Changed to primaryBlue
        foregroundColor: Colors.white, // Changed to white
        elevation: 0,
        title: const Text('Tableau de bord'),
        centerTitle: false,
        actions: [
          IconButton(
            icon: const Icon(Icons.chat_bubble_outline),
            onPressed: () {},
          ),
        ],
      ),
      drawer: const AppDrawer(),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryBlue))
          : _error != null
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
                          _buildTenantInfoCard(context, tenant, user),
                          const SizedBox(height: 16),
                          _buildCardsRow(context, formattedDate, currencySymbol),
                          const SizedBox(height: 24),
                          _buildRecentRequestsSection(context),
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
          Text('Erreur: $_error', style: Theme.of(context).textTheme.bodyMedium),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: _loadDashboard,
            child: const Text('Réessayer'),
          ),
        ],
      ),
    );
  }

  Widget _buildTenantInfoCard(BuildContext context, dynamic tenant, dynamic user) {
    final property = _dashboardData!['property'];
    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              tenant?.fullName ?? user?.fullName ?? 'Locataire',
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: 8),
            Text(
              'Compte N°${_dashboardData!['tenant']?['accountNumber'] ?? tenant?.id ?? user?.id ?? ''}',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.textLight),
            ),
            Text(
              'Client N°${_dashboardData!['tenant']?['clientNumber'] ?? tenant?.id.toString().padLeft(10, '0') ?? ''}',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.textLight),
            ),
            if (property != null) ...[
              const Divider(height: 32),
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Immeuble N°${property['id'] ?? property['reference'] ?? ''}',
                          style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.textLight),
                        ),
                        const SizedBox(height: 4),
                        if (property['name'] != null && property['name'] != 'N/A')
                          Text(property['name'], style: Theme.of(context).textTheme.bodyMedium),
                        Text(
                          property['fullAddress'] ?? '${property['address'] ?? ''}, ${property['postalCode'] ?? ''} ${property['city'] ?? ''}',
                          style: Theme.of(context).textTheme.bodyMedium,
                        ),
                        const SizedBox(height: 8),
                        Text('1 lot', style: Theme.of(context).textTheme.bodySmall),
                      ],
                    ),
                  ),
                  const SizedBox(width: 16),
                  Container(
                    width: 80,
                    height: 80,
                    decoration: BoxDecoration(
                      color: AppTheme.lightBlue.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.apartment, size: 40, color: AppTheme.primaryBlue),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildCardsRow(BuildContext context, String formattedDate, String currencySymbol) {
    final manager = _dashboardData!['manager'];
    final balances = _dashboardData!['balances'] ?? {};

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Card Gestionnaire
        Expanded(
          child: Card(
            elevation: 2,
            shadowColor: Colors.black.withOpacity(0.05),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(
                          'Mon gestionnaire',
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                        ),
                      ),
                      Container(
                        width: 40,
                        height: 40,
                        decoration: BoxDecoration(
                          color: AppTheme.lightBlue.withOpacity(0.1),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.phone, color: AppTheme.primaryBlue, size: 20),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  if (manager != null) ...[
                    Text(
                      manager['name'] ?? 'N/A',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
                    ),
                    const SizedBox(height: 4),
                    Text(manager['company'] ?? '', style: Theme.of(context).textTheme.bodySmall),
                    Text(
                      manager['address'] != null ? '${manager['address']}, ${manager['city'] ?? ''}' : manager['city'] ?? '',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ] else
                    Text('Non renseigné', style: Theme.of(context).textTheme.bodySmall),
                ],
              ),
            ),
          ),
        ),
        const SizedBox(width: 16),
        // Card Solde
        Expanded(
          child: Card(
            elevation: 2,
            shadowColor: Colors.black.withOpacity(0.05),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Solde au $formattedDate',
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '${balances['soldAt'] ?? 0} $currencySymbol',
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                          color: AppTheme.primaryOrange,
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 8),
                  Text('Solde à venir', style: Theme.of(context).textTheme.bodySmall),
                  Text(
                    '${balances['toPay'] ?? 0} $currencySymbol',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.primaryBlue),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton(
                      onPressed: () {},
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppTheme.primaryOrange,
                        side: BorderSide(color: AppTheme.primaryOrange.withOpacity(0.5)),
                      ),
                      child: const Text('CONSULTER'),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildRecentRequestsSection(BuildContext context) {
    final requestsList = _dashboardData!['recentRequests'] ?? [];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'MES DERNIÈRES DEMANDES',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                color: AppTheme.primaryBlue,
                fontWeight: FontWeight.bold,
              ),
        ),
        const SizedBox(height: 16),
        if (requestsList.isEmpty)
          Center(
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 32.0),
              child: Text(
                'Aucune demande récente',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.textLight),
              ),
            ),
          )
        else
          ...requestsList.take(3).map((req) => _buildRequestCard(context, req)),
      ],
    );
  }

  Widget _buildRequestCard(BuildContext context, Map<String, dynamic> request) {
    final statusColor = request['status'] == 'CLOSED' ? Colors.grey : AppTheme.lightBlue;

    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      margin: const EdgeInsets.only(bottom: 12),
      child: IntrinsicHeight(
        child: Row(
          children: [
            Container(
              width: 5,
              decoration: BoxDecoration(
                color: statusColor,
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(12),
                  bottomLeft: Radius.circular(12),
                ),
              ),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      request['title'] ?? 'Demande',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.bold),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      request['category'] ?? '',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'N° ${request['reference'] ?? ''}',
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppTheme.primaryBlue),
                        ),
                        Text(
                          'Créée le ${request['reportedDate'] ?? ''}',
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
