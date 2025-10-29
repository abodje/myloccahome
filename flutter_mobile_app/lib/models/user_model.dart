class UserModel {
  final int id;
  final String email;
  final String firstName;
  final String lastName;
  final List<String> roles;

  UserModel({
    required this.id,
    required this.email,
    required this.firstName,
    required this.lastName,
    required this.roles,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] ?? 0,
      email: json['email'] ?? '',
      firstName: json['firstName'] ?? '',
      lastName: json['lastName'] ?? '',
      roles: List<String>.from(json['roles'] ?? []),
    );
  }

  String get fullName => '$firstName $lastName';
}
