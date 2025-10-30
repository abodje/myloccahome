import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';
import '../../services/tenant_data_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/app_drawer.dart';
import './property_detail_screen.dart'; // Import for the detail screen

class PropertyScreen extends StatefulWidget {
  const PropertyScreen({Key? key}) : super(key: key);

  @override
  State<PropertyScreen> createState() => _PropertyScreenState();
}

class _PropertyScreenState extends State<PropertyScreen> {
  Map<String, dynamic>? _propertyData;
  Map<String, dynamic>? _leaseData;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadProperty();
  }

  Future<void> _loadProperty() async {
    if (!mounted) return;
    final authService = Provider.of<AuthService>(context, listen: false);
    final dataService = TenantDataService(authService);

    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final data = await dataService.getProperty();
      if (!mounted) return;
      setState(() {
        _propertyData = data['property'];
        _leaseData = data['lease'];
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes biens'),
        backgroundColor: AppTheme.primaryBlue,
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: false,
      ),
      drawer: const AppDrawer(),
      backgroundColor: AppTheme.backgroundGrey,
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryBlue))
          : _error != null || _propertyData == null
              ? _buildErrorView()
              : RefreshIndicator(
                  onRefresh: _loadProperty,
                  child: _buildContent(context),
                ),
    );
  }

  Widget _buildErrorView() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text(_error ?? 'Aucune propriété trouvée', style: Theme.of(context).textTheme.bodyMedium),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: _loadProperty,
            child: const Text('Réessayer'),
          ),
        ],
      ),
    );
  }

  Widget _buildContent(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _buildPropertyInfoCard(context),
        const SizedBox(height: 16),
        _buildLotNavigationCard(context),
      ],
    );
  }

  Widget _buildPropertyInfoCard(BuildContext context) {
    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (_propertyData!['name'] != null && _propertyData!['name'] != 'N/A') ...[
                    Text(
                      _propertyData!['name'],
                      style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 4),
                  ],
                  Text(
                    'Immeuble N°${_propertyData!['reference'] ?? _propertyData!['id']}',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.textLight),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    _propertyData!['fullAddress'] ?? _propertyData!['address'] ?? '',
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
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
      ),
    );
  }

  Widget _buildLotNavigationCard(BuildContext context) {
    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        leading: const Icon(Icons.home_work_outlined, color: AppTheme.primaryBlue, size: 32),
        title: Text(
          'Lot ${_propertyData!['id'] ?? ''}',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
        ),
        subtitle: Text(
          '${_propertyData!['type'] ?? 'Appartement'} - ${_propertyData!['surface'] ?? '--'} m²',
          style: Theme.of(context).textTheme.bodyMedium,
        ),
        trailing: const Icon(Icons.chevron_right, color: AppTheme.textLight),
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => PropertyDetailScreen(
                propertyData: _propertyData!,
                leaseData: _leaseData,
              ),
            ),
          );
        },
      ),
    );
  }
}
