import './tenant_model.dart';

// Main model for the dashboard data
class DashboardModel {
  final TenantModel tenant;
  final CurrentLease? currentLease;
  final Property? property;
  final Balances balances;
  final Manager? manager;
  // Assuming recentRequests is still handled separately or is part of another model

  DashboardModel({
    required this.tenant,
    this.currentLease,
    this.property,
    required this.balances,
    this.manager,
  });

  factory DashboardModel.fromJson(Map<String, dynamic> json) {
    return DashboardModel(
      tenant: TenantModel.fromJson(json['tenant'] as Map<String, dynamic>),
      currentLease: json['currentLease'] != null
          ? CurrentLease.fromJson(json['currentLease'] as Map<String, dynamic>)
          : null,
      property: json['property'] != null
          ? Property.fromJson(json['property'] as Map<String, dynamic>)
          : null,
      balances: Balances.fromJson(json['balances'] as Map<String, dynamic>),
      manager: json['manager'] != null
          ? Manager.fromJson(json['manager'] as Map<String, dynamic>)
          : null,
    );
  }
}

// Sub-models for each nested object

class CurrentLease {
  final int id;
  final String startDate;
  final String endDate;
  final double monthlyRent;
  final String status;

  CurrentLease({
    required this.id,
    required this.startDate,
    required this.endDate,
    required this.monthlyRent,
    required this.status,
  });

  factory CurrentLease.fromJson(Map<String, dynamic> json) {
    return CurrentLease(
      id: json['id'] as int,
      startDate: json['startDate'] as String,
      endDate: json['endDate'] as String,
      // Safely parse the monthlyRent from a string
      monthlyRent: double.tryParse(json['monthlyRent']?.toString() ?? '0') ?? 0.0,
      status: json['status'] as String,
    );
  }
}

class Property {
  final int id;
  final int reference;
  final String name;
  final String fullAddress;
  final int? rooms;
  final int? surface;
  final String? type;

  Property({
    required this.id,
    required this.reference,
    required this.name,
    required this.fullAddress,
    this.rooms,
    this.surface,
    this.type,
  });

  factory Property.fromJson(Map<String, dynamic> json) {
    return Property(
      id: json['id'] as int,
      reference: json['reference'] as int,
      name: json['name'] as String,
      fullAddress: json['fullAddress'] as String,
      rooms: json['rooms'] as int?,
      surface: json['surface'] as int?,
      type: json['type'] as String?,
    );
  }
}

class Balances {
  final double soldAt;
  final double toPay;
  final double totalPaid;
  final double totalDue;

  Balances({
    required this.soldAt,
    required this.toPay,
    required this.totalPaid,
    required this.totalDue,
  });

  factory Balances.fromJson(Map<String, dynamic> json) {
    return Balances(
      // Safely parse all balance fields, converting from num to double
      soldAt: (json['soldAt'] as num? ?? 0).toDouble(),
      toPay: (json['toPay'] as num? ?? 0).toDouble(),
      totalPaid: (json['totalPaid'] as num? ?? 0).toDouble(),
      totalDue: (json['totalDue'] as num? ?? 0).toDouble(),
    );
  }
}

class Manager {
  final String name;
  final String? phone;
  final String? email;

  Manager({required this.name, this.phone, this.email});

  factory Manager.fromJson(Map<String, dynamic> json) {
    return Manager(
      name: json['name'] as String,
      phone: json['phone'] as String?,
      email: json['email'] as String?,
    );
  }
}
