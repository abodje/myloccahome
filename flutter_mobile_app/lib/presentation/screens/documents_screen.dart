import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';
import '../../services/tenant_data_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/app_drawer.dart';
import '../../models/document_model.dart';

class DocumentsScreen extends StatefulWidget {
  const DocumentsScreen({Key? key}) : super(key: key);

  @override
  State<DocumentsScreen> createState() => _DocumentsScreenState();
}

class _DocumentsScreenState extends State<DocumentsScreen> {
  List<DocumentModel> _documents = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadDocuments();
  }

  Future<void> _loadDocuments() async {
    if (!mounted) return;
    final authService = Provider.of<AuthService>(context, listen: false);
    final dataService = TenantDataService(authService);

    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final data = await dataService.getDocuments();
      if (!mounted) return;
      setState(() {
        _documents = (data['documents'] as List?)
                ?.map((d) => DocumentModel.fromJson(d))
                .toList() ??
            [];
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

  Map<String, List<DocumentModel>> _groupByCategory() {
    final grouped = <String, List<DocumentModel>>{};
    for (var doc in _documents) {
      final category = DocumentModel.getCategoryFromType(doc.type);
      if (!grouped.containsKey(category)) {
        grouped[category] = [];
      }
      grouped[category]!.add(doc);
    }
    return grouped;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes documents'),
        backgroundColor: AppTheme.primaryBlue,
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: false,
      ),
      drawer: const AppDrawer(),
      backgroundColor: AppTheme.backgroundGrey,
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryBlue))
          : _error != null
              ? _buildErrorView()
              : RefreshIndicator(
                  onRefresh: _loadDocuments,
                  child: _buildContent(),
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
            onPressed: _loadDocuments,
            child: const Text('Réessayer'),
          ),
        ],
      ),
    );
  }

  Widget _buildContent() {
    final categories = _groupByCategory();
    final categoryList = categories.keys.toList();

    if (categories.isEmpty) {
      return Center(
        child: Text(
          'Aucun document disponible',
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.textLight),
        ),
      );
    }

    return GridView.builder(
      padding: const EdgeInsets.all(16),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 16,
        mainAxisSpacing: 16,
        childAspectRatio: 0.8, // Make cards taller than they are wide
      ),
      itemCount: categoryList.length,
      itemBuilder: (context, index) {
        final category = categoryList[index];
        final docs = categories[category]!;
        return _buildDocumentCategoryCard(context, category, docs);
      },
    );
  }

  Widget _buildDocumentCategoryCard(BuildContext context, String category, List<DocumentModel> documents) {
    final latestDoc = documents.isNotEmpty ? documents.first : null;
    final categoryStyle = _getCategoryStyle(category);

    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      child: InkWell(
        onTap: () {
          // TODO: Navigate to document list for this category
        },
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Icon(categoryStyle.icon, size: 32, color: categoryStyle.color),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: categoryStyle.color.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      '${documents.length}',
                      style: TextStyle(
                        color: categoryStyle.color,
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    category,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (latestDoc != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 4.0),
                    child: Text(
                      'Màj ${latestDoc.uploadDate.split(' ')[0]}',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ),
                ],
              )
            ],
          ),
        ),
      ),
    );
  }

  _CategoryStyle _getCategoryStyle(String category) {
    switch (category) {
      case 'Assurance':
        return _CategoryStyle(icon: Icons.shield_outlined, color: AppTheme.primaryBlue);
      case 'Avis d\'échéance':
        return _CategoryStyle(icon: Icons.receipt_long_outlined, color: AppTheme.primaryOrange);
      case 'Bail':
        return _CategoryStyle(icon: Icons.description_outlined, color: Colors.green.shade600);
      case 'Diagnostics':
        return _CategoryStyle(icon: Icons.science_outlined, color: Colors.purple.shade400);
      default:
        return _CategoryStyle(icon: Icons.folder_outlined, color: AppTheme.textLight);
    }
  }
}

class _CategoryStyle {
  final IconData icon;
  final Color color;
  _CategoryStyle({required this.icon, required this.color});
}
