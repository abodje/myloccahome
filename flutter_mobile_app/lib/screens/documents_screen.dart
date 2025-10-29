import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import '../services/tenant_data_service.dart';
import '../theme/app_theme.dart';
import '../widgets/app_drawer.dart';
import '../models/document_model.dart';

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
    final authService = Provider.of<AuthService>(context, listen: false);
    final dataService = TenantDataService(authService);

    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final data = await dataService.getDocuments();
      setState(() {
        _documents = (data['documents'] as List?)
                ?.map((d) => DocumentModel.fromJson(d))
                .toList() ??
            [];
        _isLoading = false;
      });
    } catch (e) {
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
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(title: const Text('Mes documents')),
        drawer: const AppDrawer(),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (_error != null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Mes documents')),
        drawer: const AppDrawer(),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text('Erreur: $_error'),
              ElevatedButton(
                onPressed: _loadDocuments,
                child: const Text('Réessayer'),
              ),
            ],
          ),
        ),
      );
    }

    final categories = _groupByCategory();
    final categoryList = categories.keys.toList();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes documents'),
        actions: [
          IconButton(
            icon: const Icon(Icons.chat_bubble_outline),
            onPressed: () {},
          ),
        ],
      ),
      drawer: const AppDrawer(),
      body: RefreshIndicator(
        onRefresh: _loadDocuments,
        child: Container(
          color: AppTheme.lightBlue.withOpacity(0.05),
          child: categories.isEmpty
              ? Center(
                  child: Text(
                    'Aucun document',
                    style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                          color: AppTheme.textLight,
                        ),
                  ),
                )
              : GridView.builder(
                  padding: const EdgeInsets.all(16),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    crossAxisSpacing: 16,
                    mainAxisSpacing: 16,
                  ),
                  itemCount: categoryList.length,
                  itemBuilder: (context, index) {
                    final category = categoryList[index];
                    final docs = categories[category]!;
                    final latestDoc = docs.first;
                    return _buildDocumentCategory(
                        context, category, docs, latestDoc);
                  },
                ),
        ),
      ),
    );
  }

  Widget _buildDocumentCategory(
    BuildContext context,
    String category,
    List<DocumentModel> documents,
    DocumentModel latestDoc,
  ) {
    IconData iconData;
    Color color;

    switch (category) {
      case 'Assurance':
        iconData = Icons.home;
        color = Colors.blue;
        break;
      case 'Avis d\'échéance':
        iconData = Icons.campaign;
        color = Colors.orange;
        break;
      case 'Bail':
        iconData = Icons.description;
        color = Colors.green;
        break;
      case 'Diagnostics':
        iconData = Icons.medical_services;
        color = Colors.red;
        break;
      default:
        iconData = Icons.lightbulb;
        color = Colors.amber;
    }

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
      child: InkWell(
        onTap: () {
          // TODO: Naviguer vers la liste des documents de cette catégorie
        },
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Badge avec nombre
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '${documents.length} document${documents.length > 1 ? 's' : ''}',
                  style: TextStyle(
                    color: color,
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              const SizedBox(height: 8),
              // Date
              Text(
                'Actualisé le ${latestDoc.uploadDate.split(' ')[0]}',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: AppTheme.textLight,
                    ),
              ),
              const SizedBox(height: 16),
              // Icône
              Icon(iconData, size: 50, color: color),
              const SizedBox(height: 12),
              // Titre
              Text(
                category,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
