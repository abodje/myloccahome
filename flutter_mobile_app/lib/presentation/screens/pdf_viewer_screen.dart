import 'package:flutter/material.dart';
import 'package:syncfusion_flutter_pdfviewer/pdfviewer.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../theme/app_theme.dart';

class PdfViewerScreen extends StatefulWidget {
  final String documentUrl;
  final String documentTitle;

  const PdfViewerScreen({
    Key? key,
    required this.documentUrl,
    required this.documentTitle,
  }) : super(key: key);

  @override
  State<PdfViewerScreen> createState() => _PdfViewerScreenState();
}

class _PdfViewerScreenState extends State<PdfViewerScreen> {
  late PdfViewerController _pdfViewerController;

  @override
  void initState() {
    super.initState();
    _pdfViewerController = PdfViewerController();
  }

  Future<void> _downloadDocument() async {
    final uri = Uri.parse(widget.documentUrl);
    try {
      if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
        throw Exception('Impossible de lancer l\'URL: ${widget.documentUrl}');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur lors du téléchargement: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.documentTitle),
        backgroundColor: AppTheme.primaryBlue,
        foregroundColor: Colors.white,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.download_outlined),
            tooltip: 'Télécharger',
            onPressed: _downloadDocument,
          ),
        ],
      ),
      body: SfPdfViewer.network(
        widget.documentUrl,
        controller: _pdfViewerController,
      ),
      floatingActionButton: FloatingActionButton(
        backgroundColor: AppTheme.primaryBlue,
        onPressed: () {
          _pdfViewerController.zoomLevel = _pdfViewerController.zoomLevel + 1.0;
        },
        tooltip: 'Zoom avant',
        child: const Icon(Icons.zoom_in, color: Colors.white),
      ),
    );
  }
}
