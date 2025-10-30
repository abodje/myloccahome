class LeaseModel {
  final int id;
  final String startDate;
  final String endDate;
  final double monthlyRent;
  final double charges;
  final double deposit;
  final String status;

  LeaseModel({
    required this.id,
    required this.startDate,
    required this.endDate,
    required this.monthlyRent,
    required this.charges,
    required this.deposit,
    required this.status,
  });

  factory LeaseModel.fromJson(Map<String, dynamic> json) {
    return LeaseModel(
      id: json['id'] as int,
      startDate: json['startDate'] as String,
      endDate: json['endDate'] as String,
      monthlyRent: double.tryParse(json['monthlyRent']?.toString() ?? '0') ?? 0.0,
      charges: double.tryParse(json['charges']?.toString() ?? '0') ?? 0.0,
      deposit: double.tryParse(json['deposit']?.toString() ?? '0') ?? 0.0,
      status: json['status'] as String,
    );
  }
}
