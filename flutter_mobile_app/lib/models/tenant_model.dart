class TenantModel {
  final int id;
  final String firstName;
  final String lastName;
  final String email;
  final String? phone;

  TenantModel({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.email,
    this.phone,
  });

  factory TenantModel.fromJson(Map<String, dynamic> json) {
    return TenantModel(
      id: json['id'] ?? 0,
      firstName: json['firstName'] ?? '',
      lastName: json['lastName'] ?? '',
      email: json['email'] ?? '',
      phone: json['phone'],
    );
  }

  String get fullName => '$firstName $lastName';
}
