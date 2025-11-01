class ProfileModel {
  int id;
  String firstName;
  String lastName;
  String email;
  String? phone;
  String? address;
  String? city;
  String? postalCode;
  String? birthDate;
  String? profession;
  String? emergencyContactName;
  String? emergencyContactPhone;
  NotificationsModel notifications;

  ProfileModel({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.email,
    this.phone,
    this.address,
    this.city,
    this.postalCode,
    this.birthDate,
    this.profession,
    this.emergencyContactName,
    this.emergencyContactPhone,
    required this.notifications,
  });

  factory ProfileModel.fromJson(Map<String, dynamic> json) {
    return ProfileModel(
      id: json['id'] as int,
      firstName: json['firstName'] as String,
      lastName: json['lastName'] as String,
      email: json['email'] as String,
      phone: json['phone'] as String?,
      address: json['address'] as String?,
      city: json['city'] as String?,
      postalCode: json['postalCode'] as String?,
      birthDate: json['birthDate'] as String?,
      profession: json['profession'] as String?,
      emergencyContactName: json['emergencyContactName'] as String?,
      emergencyContactPhone: json['emergencyContactPhone'] as String?,
      notifications: NotificationsModel.fromJson(json['notifications'] as Map<String, dynamic>),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'phone': phone,
      'address': address,
      'city': city,
      'postalCode': postalCode,
      'profession': profession,
      'emergencyContactName': emergencyContactName,
      'emergencyContactPhone': emergencyContactPhone,
      'birthDate': birthDate,
      'notifications': notifications.toJson(),
    };
  }
}

class NotificationsModel {
  bool emailNotifications;
  bool paymentReminders;
  bool maintenanceUpdates;
  bool documentAlerts;

  NotificationsModel({
    required this.emailNotifications,
    required this.paymentReminders,
    required this.maintenanceUpdates,
    required this.documentAlerts,
  });

  factory NotificationsModel.fromJson(Map<String, dynamic> json) {
    return NotificationsModel(
      emailNotifications: json['emailNotifications'] as bool,
      paymentReminders: json['paymentReminders'] as bool,
      maintenanceUpdates: json['maintenanceUpdates'] as bool,
      documentAlerts: json['documentAlerts'] as bool,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'emailNotifications': emailNotifications,
      'paymentReminders': paymentReminders,
      'maintenanceUpdates': maintenanceUpdates,
      'documentAlerts': documentAlerts,
    };
  }
}
