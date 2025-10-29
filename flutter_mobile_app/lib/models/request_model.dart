class RequestModel {
  final int id;
  final String reference;
  final String title;
  final String category;
  final String description;
  final String status;
  final String priority;
  final String reportedDate;
  final String? scheduledDate;
  final String? completedDate;
  final Map<String, dynamic>? property;

  RequestModel({
    required this.id,
    required this.reference,
    required this.title,
    required this.category,
    required this.description,
    required this.status,
    required this.priority,
    required this.reportedDate,
    this.scheduledDate,
    this.completedDate,
    this.property,
  });

  factory RequestModel.fromJson(Map<String, dynamic> json) {
    return RequestModel(
      id: json['id'] ?? 0,
      reference: json['reference'] ?? '',
      title: json['title'] ?? '',
      category: json['category'] ?? '',
      description: json['description'] ?? '',
      status: json['status'] ?? '',
      priority: json['priority'] ?? '',
      reportedDate: json['reportedDate'] ?? '',
      scheduledDate: json['scheduledDate'],
      completedDate: json['completedDate'],
      property: json['property'],
    );
  }

  bool get isInProgress =>
      status == 'En cours' || status == 'En attente' || status == 'Nouvelle';
  bool get isClosed =>
      status == 'Terminé' || status == 'Clôturée' || status == 'Clos';
}
