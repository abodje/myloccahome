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
            _buildStatItem(context, 'Total', stats.total, AppTheme.primaryBlue, Icons.list_alt_outlined),
            _buildStatItem(context, 'En attente', stats.pending, Colors.orange.shade700, Icons.hourglass_top_outlined),
            _buildStatItem(context, 'En cours', stats.inProgress, Colors.blue.shade700, Icons.sync_outlined),
            _buildStatItem(context, 'Terminé', stats.completed, Colors.green.shade700, Icons.check_circle_outline),
          ],
        ),
      ),
    );
  }

  Widget _buildStatItem(BuildContext context, String label, int count, Color color, IconData icon) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, color: color, size: 28),
        const SizedBox(height: 8),
        Text(
          count.toString(),
          style: Theme.of(context).textTheme.titleLarge?.copyWith(color: color, fontWeight: FontWeight.bold),
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
    final priorityInfo = _getPriorityInfo(request.priority);

    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      child: InkWell(
        onTap: () { /* TODO: Navigate to request details screen */ },
        borderRadius: BorderRadius.circular(10),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    request.reference,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppTheme.textLight),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: statusInfo.color.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      request.status.toUpperCase(),
                      style: TextStyle(color: statusInfo.color, fontSize: 12, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                request.title,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Icon(priorityInfo.icon, color: priorityInfo.color, size: 16),
                  const SizedBox(width: 4),
                  Text(request.priority, style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: priorityInfo.color)),
                  const SizedBox(width: 12),
                  const Icon(Icons.calendar_today_outlined, color: AppTheme.textLight, size: 16),
                  const SizedBox(width: 4),
                  Text(request.reportedDate, style: Theme.of(context).textTheme.bodyMedium),
                ],
              ),
              const Divider(height: 24),
              Row(
                children: [
                  const Icon(Icons.location_on_outlined, color: AppTheme.textLight, size: 16),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      request.property.address,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppTheme.textLight),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ],
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

  ({IconData icon, Color color}) _getPriorityInfo(String priority) {
    switch (priority.toLowerCase()) {
      case 'haute':
        return (icon: Icons.keyboard_arrow_up_outlined, color: AppTheme.primaryOrange);
      case 'normale':
        return (icon: Icons.remove_outlined, color: Colors.blue.shade700);
      case 'basse':
        return (icon: Icons.keyboard_arrow_down_outlined, color: Colors.grey.shade600);
      default:
        return (icon: Icons.remove_outlined, color: AppTheme.textLight);
    }
  }
}
