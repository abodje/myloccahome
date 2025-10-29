import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import '../services/tenant_data_service.dart';
import '../theme/app_theme.dart';
import '../widgets/app_drawer.dart';

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
    final authService = Provider.of<AuthService>(context, listen: false);
    final dataService = TenantDataService(authService);

    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final data = await dataService.getProperty();
      setState(() {
        _propertyData = data['property'];
        _leaseData = data['lease'];
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(title: const Text('Mes biens')),
        drawer: const AppDrawer(),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (_error != null || _propertyData == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Mes biens')),
        drawer: const AppDrawer(),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(_error ?? 'Aucune propriété trouvée'),
              ElevatedButton(
                onPressed: _loadProperty,
                child: const Text('Réessayer'),
              ),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Mes biens')),
      drawer: const AppDrawer(),
      body: RefreshIndicator(
        onRefresh: _loadProperty,
        child: ListView(
          children: [
            // Card principale
            Container(
              margin: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 10,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (_propertyData!['name'] != null &&
                                  _propertyData!['name'] != 'N/A')
                                Text(
                                  _propertyData!['name'],
                                  style: Theme.of(context)
                                      .textTheme
                                      .bodyLarge
                                      ?.copyWith(fontWeight: FontWeight.w600),
                                ),
                              const SizedBox(height: 4),
                              Text(
                                'N°${_propertyData!['reference'] ?? _propertyData!['id']}',
                                style: Theme.of(context).textTheme.bodyMedium,
                              ),
                              Text(
                                '1 lot',
                                style: Theme.of(context).textTheme.bodySmall,
                              ),
                              const Divider(height: 24),
                              Text(
                                _propertyData!['fullAddress'] ??
                                    _propertyData!['address'] ??
                                    '',
                                style: Theme.of(context).textTheme.bodyMedium,
                              ),
                            ],
                          ),
                        ),
                        Container(
                          width: 100,
                          height: 100,
                          decoration: BoxDecoration(
                            color: AppTheme.backgroundGrey,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Icon(
                            Icons.apartment,
                            size: 50,
                            color: AppTheme.primaryBlue.withOpacity(0.3),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            // Card Appartement
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 10,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: InkWell(
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
                borderRadius: BorderRadius.circular(12),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Icon(
                        Icons.home_outlined,
                        size: 32,
                        color: AppTheme.primaryBlue,
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Lot ${_propertyData!['id'] ?? ''}',
                              style: Theme.of(context)
                                  .textTheme
                                  .bodyLarge
                                  ?.copyWith(
                                    fontWeight: FontWeight.w600,
                                  ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              _propertyData!['type'] ?? 'Appartement',
                              style: Theme.of(context).textTheme.bodyMedium,
                            ),
                            if (_propertyData!['surface'] != null)
                              Text(
                                '${_propertyData!['surface']} m²',
                                style: Theme.of(context)
                                    .textTheme
                                    .bodySmall
                                    ?.copyWith(
                                      color: AppTheme.textLight,
                                    ),
                              ),
                          ],
                        ),
                      ),
                      Icon(
                        Icons.chevron_right,
                        color: AppTheme.textLight,
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class PropertyDetailScreen extends StatelessWidget {
  final Map<String, dynamic> propertyData;
  final Map<String, dynamic>? leaseData;

  const PropertyDetailScreen({
    Key? key,
    required this.propertyData,
    this.leaseData,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text('Appartement'),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildSection(
            context,
            title: 'Information immeuble',
            items: [
              _InfoItem(
                label: 'Adresse du bien',
                value: propertyData['fullAddress'] ??
                    propertyData['address'] ??
                    'N/A',
              ),
              _InfoItem(label: 'Type', value: propertyData['type'] ?? 'N/A'),
              if (propertyData['rooms'] != null)
                _InfoItem(label: 'Pièces', value: '${propertyData['rooms']}'),
              if (propertyData['surface'] != null)
                _InfoItem(
                    label: 'Surface', value: '${propertyData['surface']} m²'),
            ],
          ),
          if (propertyData['surface'] != null) ...[
            const SizedBox(height: 24),
            _buildSection(
              context,
              title: 'Superficie du bien',
              items: [
                _InfoItem(
                  label: 'Surface totale',
                  value: '${propertyData['surface']} m²',
                ),
                _InfoItem(
                  label: 'Surface (Loi Carrez)',
                  value: '${propertyData['surface']} m²',
                ),
              ],
            ),
          ],
          const SizedBox(height: 24),
          _buildSection(
            context,
            title: 'Descriptif du bien',
            items: [
              _InfoItem(
                label: 'Numéro du lot',
                value: '${propertyData['id'] ?? ''}',
              ),
              _InfoItem(
                label: 'Type',
                value: '${propertyData['type'] ?? 'N/A'}',
              ),
              if (propertyData['description'] != null)
                _InfoItem(
                  label: 'Description',
                  value: propertyData['description'],
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSection(
    BuildContext context, {
    required String title,
    required List<_InfoItem> items,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Text(
              title,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    color: AppTheme.primaryBlue,
                    fontWeight: FontWeight.bold,
                  ),
            ),
          ),
          const Divider(height: 1),
          ...items.map((item) => _buildInfoRow(context, item)),
        ],
      ),
    );
  }

  Widget _buildInfoRow(BuildContext context, _InfoItem item) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            item.label,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: AppTheme.textLight,
                ),
          ),
          const SizedBox(height: 4),
          Text(
            item.value,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w500,
                ),
          ),
        ],
      ),
    );
  }
}

class _InfoItem {
  final String label;
  final String value;

  _InfoItem({required this.label, required this.value});
}
