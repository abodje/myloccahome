import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../models/document_model.dart';
import '../../theme/app_theme.dart';
import '../../services/auth_service.dart';
import '../../services/api_service.dart';

class DocumentListByCategoryScreen extends StatefulWidget {
  final String category;
  final List<DocumentModel> documents;

  const DocumentListByCategoryScreen({
    Key? key,
    required this.category,
    required this.documents,
  }) : super(key: key);

  @override
  State<DocumentListByCategoryScreen> createState() => _DocumentListByCategoryScreenState();
}

class _DocumentListByCategoryScreenState extends State<DocumentListByCategoryScreen> {
  Future<void> _launchDocumentUrl(String relativeUrl) async {
    final authService = Provider.of<AuthService>(context, listen: false);
    final baseUrl = ApiService.baseUrl;
    String fullUrl = '$baseUrl$relativeUrl';

    if (authService.token != null && authService.token!.isNotEmpty) {
      fullUrl = '$fullUrl?token=${authService.token}';
    }

    final uri = Uri.parse(fullUrl);

    try {
      bool launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!launched) {
        launched = await launchUrl(uri, mode: LaunchMode.inAppBrowserView);
      }
      if (!launched) {
        throw Exception('Impossible d\'ouvrir l\'URL du document: $fullUrl');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur lors de l\'ouverture du document: $e')),
      );
    }
  }

  IconData _getFileIcon(String fileName) {
    final extension = fileName.split('.').last.toLowerCase();
    switch (extension) {
      case 'pdf':
        return Icons.picture_as_pdf;
      case 'doc':
      case 'docx':
        return Icons.description;
      case 'xls':
      case 'xlsx':
        return Icons.table_chart;
      case 'jpg':
      case 'jpeg':
      case 'png':
      case 'gif':
        return Icons.image;
      case 'txt':
        return Icons.text_snippet;
      default:
        return Icons.insert_drive_file_outlined;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.category),
        backgroundColor: AppTheme.primaryBlue,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: widget.documents.isEmpty
          ? const Center(
              child: Text('Aucun document dans cette catégorie.'),
            )
          : ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: widget.documents.length,
              itemBuilder: (context, index) {
                final document = widget.documents[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  elevation: 2,
                  shadowColor: Colors.black.withOpacity(0.05),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)), // Rounded corners for cards
                  child: ListTile(
                    leading: Icon(_getFileIcon(document.fileName), color: AppTheme.primaryBlue, size: 30), // Dynamic icon
                    title: Text(document.name, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600)),
                    subtitle: Text(
                      '${document.fileSize != null ? '${document.fileSize} KB' : 'Taille inconnue'} - ${document.uploadDate}',
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppTheme.textLight),
                    ),
                    trailing: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        IconButton(
                          icon: const Icon(Icons.visibility_outlined, color: AppTheme.primaryBlue),
                          tooltip: 'Voir le document',
                          onPressed: () => _launchDocumentUrl(document.downloadUrl),
                        ),
                        IconButton(
                          icon: const Icon(Icons.download_outlined, color: AppTheme.primaryBlue),
                          tooltip: 'Télécharger le document',
                          onPressed: () => _launchDocumentUrl(document.downloadUrl),
                        ),
                      ],
                    ),
                    onTap: () => _launchDocumentUrl(document.downloadUrl),
                  ),
                );
              },
            ),
    );
  }
}
