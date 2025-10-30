import 'package:flutter/material.dart';
import '../../theme/app_theme.dart';

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
        title: Text('Détail du lot'),
        backgroundColor: AppTheme.primaryBlue,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      backgroundColor: AppTheme.backgroundGrey,
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildSection(
            context,
            title: 'Informations sur le bien',
            items: [
              _InfoItem(label: 'Adresse', value: propertyData['fullAddress'] ?? propertyData['address'] ?? 'N/A'),
              _InfoItem(label: 'Type de bien', value: propertyData['type'] ?? 'N/A'),
              if (propertyData['rooms'] != null) _InfoItem(label: 'Pièces', value: '${propertyData['rooms']}'),
              if (propertyData['surface'] != null) _InfoItem(label: 'Surface', value: '${propertyData['surface']} m²'),
            ],
          ),
          const SizedBox(height: 16),
          _buildSection(
            context,
            title: 'Descriptif',
            items: [
              _InfoItem(label: 'Numéro du lot', value: '${propertyData['id'] ?? ''}'),
              if (propertyData['description'] != null) _InfoItem(label: 'Description', value: propertyData['description']),
            ],
          ),
          // Add more sections for leaseData if needed
        ],
      ),
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
