import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../models/document_model.dart';
import '../../theme/app_theme.dart';
import '../../services/auth_service.dart';
import '../../services/api_service.dart';
import './pdf_viewer_screen.dart'; // Import the new PDF viewer screen

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
  Future<void> _launchDocumentUrl(DocumentModel document, bool isViewAction) async {
    final authService = Provider.of<AuthService>(context, listen: false);
    final baseUrl = ApiService.baseUrl;
    String fullUrl = '$baseUrl${document.downloadUrl}';

    if (authService.token != null && authService.token!.isNotEmpty) {
      fullUrl = '$fullUrl?token=${authService.token}';
    }

    final uri = Uri.parse(fullUrl);
    final extension = document.fileName.split('.').last.toLowerCase();

    try {
      if (isViewAction && extension == 'pdf') {
        // If it's a view action and a PDF, open in internal PDF viewer
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => PdfViewerScreen(
              documentUrl: fullUrl,
              documentTitle: document.name,
            ),
          ),
        );
      } else {
        // Otherwise, use url_launcher for external app or in-app browser
        bool launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
        if (!launched) {
          launched = await launchUrl(uri, mode: LaunchMode.inAppBrowserView);
        }
        if (!launched) {
          throw Exception('Impossible d\'ouvrir l\'URL du document: $fullUrl');
        }
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
                  elevation: 4, // Increased elevation for a nicer look
                  shadowColor: Colors.black.withOpacity(0.1),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)), // More rounded corners
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(vertical: 8, horizontal: 16), // More padding
                    leading: Icon(_getFileIcon(document.fileName), color: AppTheme.primaryBlue, size: 36), // Larger icon
                    title: Text(document.name, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold, color: AppTheme.textDark)), // Bold title
                    subtitle: Padding(
                      padding: const EdgeInsets.only(top: 4.0),
                      child: Text(
                        '${document.fileSize != null ? '${(document.fileSize! / 1024).toStringAsFixed(1)} Mo' : 'Taille inconnue'} - ${document.uploadDate}',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppTheme.textLight),
                      ),
                    ),
                    trailing: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        IconButton(
                          icon: const Icon(Icons.visibility_outlined, color: AppTheme.primaryBlue),
                          tooltip: 'Voir le document',
                          onPressed: () => _launchDocumentUrl(document, true), // Pass true for view action
                        ),
                        IconButton(
                          icon: const Icon(Icons.download_outlined, color: AppTheme.primaryBlue),
                          tooltip: 'Télécharger le document',
                          onPressed: () => _launchDocumentUrl(document, false), // Pass false for download action
                        ),
                      ],
                    ),
                    onTap: () => _launchDocumentUrl(document, true), // Default tap to view
                  ),
                );
              },
            ),
    );
  }
}
