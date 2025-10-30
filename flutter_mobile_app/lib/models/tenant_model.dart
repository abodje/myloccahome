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

  // Helper getter to easily get the full name
  String get fullName => '$firstName $lastName';

  // Factory constructor to create a TenantModel from a JSON object
  factory TenantModel.fromJson(Map<String, dynamic> json) {
    return TenantModel(
      id: json['id'] as int,
      firstName: json['firstName'] as String,
      lastName: json['lastName'] as String,
      email: json['email'] as String,
      phone: json['phone'] as String?,
    );
  }

  // Method to convert a TenantModel instance to a JSON object
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'firstName': firstName,
      'lastName': lastName,
      'email': email,
      'phone': phone,
    };
  }
}
