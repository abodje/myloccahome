class DocumentModel {
  final int id;
  final String name;
  final String type;
  final String fileName;
  final int? fileSize;
  final String uploadDate;
  final String? description;
  final String downloadUrl;

  DocumentModel({
    required this.id,
    required this.name,
    required this.type,
    required this.fileName,
    this.fileSize,
    required this.uploadDate,
    this.description,
    required this.downloadUrl,
  });

  factory DocumentModel.fromJson(Map<String, dynamic> json) {
    return DocumentModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      type: json['type'] ?? '',
      fileName: json['fileName'] ?? '',
      fileSize: json['fileSize'],
      uploadDate: json['uploadDate'] ?? '',
      description: json['description'],
      downloadUrl: json['downloadUrl'] ?? '',
    );
  }

  // Grouper les documents par type pour l'affichage
  static String getCategoryFromType(String type) {
    final lowerType = type.toLowerCase();
    if (lowerType.contains('assurance')) return 'Assurance';
    if (lowerType.contains('échéance') || lowerType.contains('avis')) {
      return 'Avis d\'échéance';
    }
    if (lowerType.contains('bail')) return 'Bail';
    if (lowerType.contains('diagnostic')) return 'Diagnostics';
    return 'Autres';
  }
}
