import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../models/request_model.dart';
import '../../services/auth_service.dart';
import '../../services/tenant_data_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/app_drawer.dart';

class RequestsScreen extends StatefulWidget {
  const RequestsScreen({Key? key}) : super(key: key);

  @override
  State<RequestsScreen> createState() => _RequestsScreenState();
}

class _RequestsScreenState extends State<RequestsScreen> {
  RequestListModel? _requestData;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadRequests();
  }

  Future<void> _loadRequests() async {
    final authService = Provider.of<AuthService>(context, listen: false);
    final dataService = TenantDataService(authService);

    if (!mounted) return;
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final data = await dataService.getRequests();
      if (!mounted) return;
      setState(() {
        _requestData = RequestListModel.fromJson(data);
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

  void _navigateToCreateRequest() {
    context.push('/create-request').then((_) {
      // After returning from create screen, refresh the list
      _loadRequests();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes demandes'),
        backgroundColor: AppTheme.primaryBlue,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      drawer: const AppDrawer(),
      backgroundColor: AppTheme.backgroundGrey,
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryBlue))
          : _error != null || _requestData == null
              ? _buildErrorView()
              : RefreshIndicator(
                  onRefresh: _loadRequests,
                  color: AppTheme.primaryBlue,
                  child: _buildContent(),
                ),
      floatingActionButton: FloatingActionButton(
        onPressed: _navigateToCreateRequest,
        backgroundColor: AppTheme.primaryOrange,
        child: const Icon(Icons.add, color: Colors.white),
        tooltip: 'Nouvelle demande',
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
          Text(_error ?? 'Impossible de charger les demandes.', textAlign: TextAlign.center),
          const SizedBox(height: 16),
          ElevatedButton(onPressed: _loadRequests, child: const Text('Réessayer')),
        ],
      ),
    );
  }

  Widget _buildContent() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _buildStatisticsCard(_requestData!.statistics),
        const SizedBox(height: 24),
        _buildRequestsList(_requestData!.requests),
      ],
    );
  }

  Widget _buildStatisticsCard(RequestStatisticsModel stats) {
    return Card(
      elevation: 4,
      shadowColor: Colors.black.withOpacity(0.1),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 16.0),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          children: [
            _buildStatItem(context, 'Total', stats.total, AppTheme.primaryBlue),
            _buildStatItem(context, 'En attente', stats.pending, Colors.orange.shade700),
            _buildStatItem(context, 'En cours', stats.inProgress, Colors.blue.shade700),
            _buildStatItem(context, 'Terminé', stats.completed, Colors.green.shade700),
          ],
        ),
      ),
    );
  }

  Widget _buildStatItem(BuildContext context, String label, int count, Color color) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          count.toString(),
          style: Theme.of(context).textTheme.headlineSmall?.copyWith(color: color, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 4),
        Text(label, style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppTheme.textLight)),
      ],
    );
  }

  Widget _buildRequestsList(List<RequestModel> requests) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'LISTE DES DEMANDES',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                color: AppTheme.primaryBlue,
                fontWeight: FontWeight.bold,
              ),
        ),
        const SizedBox(height: 16),
        if (requests.isEmpty)
          Center(
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 40.0),
              child: Text(
                'Aucune demande pour le moment',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.textLight),
              ),
            ),
          )
        else
          ListView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: requests.length,
            itemBuilder: (context, index) {
              return _buildRequestItem(requests[index]);
            },
          ),
      ],
    );
  }

  Widget _buildRequestItem(RequestModel request) {
    final statusInfo = _getStatusInfo(request.status);

    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(vertical: 10, horizontal: 16),
        leading: CircleAvatar(
          backgroundColor: statusInfo.color.withOpacity(0.1),
          child: Icon(statusInfo.icon, color: statusInfo.color, size: 24),
        ),
        title: Text(
          request.title,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 4.0),
          child: Text(
            '${request.reference} - ${request.reportedDate}',
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
          decoration: BoxDecoration(
            color: statusInfo.color,
            borderRadius: BorderRadius.circular(20),
          ),
          child: Text(
            request.status,
            style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
          ),
        ),
      ),
    );
  }

  ({IconData icon, Color color}) _getStatusInfo(String status) {
    switch (status.toLowerCase()) {
      case 'en attente':
        return (icon: Icons.hourglass_top_outlined, color: Colors.orange.shade700);
      case 'en cours':
        return (icon: Icons.sync_outlined, color: Colors.blue.shade700);
      case 'terminé':
        return (icon: Icons.check_circle_outline, color: Colors.green.shade700);
      default:
        return (icon: Icons.help_outline, color: AppTheme.textLight);
    }
  }
}
