import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../models/lease_model.dart';
import '../../models/property_model.dart';
import '../../services/auth_service.dart';
import '../../services/tenant_data_service.dart';
import '../../theme/app_theme.dart';

class PropertyDetailScreen extends StatefulWidget {
  const PropertyDetailScreen({Key? key}) : super(key: key);

  @override
  State<PropertyDetailScreen> createState() => _PropertyDetailScreenState();
}

class _PropertyDetailScreenState extends State<PropertyDetailScreen> {
  PropertyModel? _property;
  LeaseModel? _lease;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadPropertyDetails();
  }

  Future<void> _loadPropertyDetails() async {
    final authService = Provider.of<AuthService>(context, listen: false);
    final dataService = TenantDataService(authService);

    if (!mounted) return;
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final data = await dataService.getPropertyDetails();
      if (!mounted) return;
      setState(() {
        _property = data['property'];
        _lease = data['lease'];
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
    final currencySymbol = Provider.of<AuthService>(context).settings?.localization.defaultCurrency ?? '€';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Détail du lot'),
        backgroundColor: AppTheme.primaryBlue,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      backgroundColor: AppTheme.backgroundGrey,
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryBlue))
          : _error != null || _property == null
              ? _buildErrorView()
              : RefreshIndicator(
                  onRefresh: _loadPropertyDetails,
                  child: _buildContent(currencySymbol),
                ),
    );
  }

  Widget _buildErrorView() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text(_error ?? 'Aucun détail de propriété trouvé', style: Theme.of(context).textTheme.bodyMedium),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: _loadPropertyDetails,
            child: const Text('Réessayer'),
          ),
        ],
      ),
    );
  }

  Widget _buildContent(String currencySymbol) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _buildSection(
          context,
          title: 'Informations sur le bien',
          items: [
            _InfoItem(label: 'Adresse', value: _property!.fullAddress),
            _InfoItem(label: 'Type de bien', value: _property!.type),
            if (_property!.rooms != null) _InfoItem(label: 'Pièces', value: '${_property!.rooms}'),
            if (_property!.surface != null) _InfoItem(label: 'Surface', value: '${_property!.surface} m²'),
          ],
        ),
        const SizedBox(height: 16),
        if (_lease != null)
          _buildSection(
            context,
            title: 'Mon bail',
            items: [
              _InfoItem(label: 'Loyer mensuel', value: '${_lease!.monthlyRent.toStringAsFixed(2)} $currencySymbol'),
              _InfoItem(label: 'Charges', value: '${_lease!.charges.toStringAsFixed(2)} $currencySymbol'),
              _InfoItem(label: 'Dépôt de garantie', value: '${_lease!.deposit.toStringAsFixed(2)} $currencySymbol'),
              _InfoItem(label: 'Date de début', value: _lease!.startDate),
              _InfoItem(label: 'Date de fin', value: _lease!.endDate),
              _InfoItem(label: 'Statut', value: _lease!.status),
            ],
          ),
        const SizedBox(height: 16),
        if (_property!.equipmentList != null && _property!.equipmentList!.isNotEmpty)
          _buildSection(
            context,
            title: 'Équipements',
            items: _property!.equipmentList!.map((e) => _InfoItem(label: e, value: '')).toList(),
          ),
      ],
    );
  }

  Widget _buildSection(BuildContext context, { required String title, required List<_InfoItem> items }) {
    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
            child: Text(
              title,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    color: AppTheme.primaryBlue,
                    fontWeight: FontWeight.bold,
                  ),
            ),
          ),
          const Divider(height: 1),
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: items.length,
            itemBuilder: (context, index) => _buildInfoRow(context, items[index]),
            separatorBuilder: (context, index) => const Divider(height: 1, indent: 16, endIndent: 16),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoRow(BuildContext context, _InfoItem item) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(item.label, style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.textLight)),
          Flexible(
            child: Text(
              item.value,
              textAlign: TextAlign.end,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w500),
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
