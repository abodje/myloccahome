import 'package:flutter/material.dart';
import 'package:syncfusion_flutter_pdfviewer/pdfviewer.dart';
import '../../theme/app_theme.dart';

class PdfViewerScreen extends StatelessWidget {
  final String documentUrl;
  final String documentTitle;

  const PdfViewerScreen({
    Key? key,
    required this.documentUrl,
    required this.documentTitle,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(documentTitle),
        backgroundColor: AppTheme.primaryBlue,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: SfPdfViewer.network(
        documentUrl,
        // Vous pouvez ajouter des contrôles ou des fonctionnalités ici si nécessaire
        // par exemple, controller: PdfViewerController(),
      ),
    );
  }
}
