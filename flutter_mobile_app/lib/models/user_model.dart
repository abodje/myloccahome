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

  // Helper getter to easily get the full name
  String get fullName => '$firstName $lastName';

  // Factory constructor to create a UserModel from a JSON object
  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] as int,
      email: json['email'] as String,
      firstName: json['firstName'] as String,
      lastName: json['lastName'] as String,
      roles: List<String>.from(json['roles'] as List),
    );
  }

  // Method to convert a UserModel instance to a JSON object
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'email': email,
      'firstName': firstName,
      'lastName': lastName,
      'roles': roles,
    };
  }
}
