import 'package:flutter/foundation.dart';

// Main Settings Model
class AppSettingsModel {
  final OrganizationSettings organization;
  final LocalizationSettings localization;
  final PaymentSettings payments;
  final GatewaySettings gateways;
  final FeatureSettings features;
  final AppInfo app;

  AppSettingsModel({
    required this.organization,
    required this.localization,
    required this.payments,
    required this.gateways,
    required this.features,
    required this.app,
  });

  factory AppSettingsModel.fromJson(Map<String, dynamic> json) {
    return AppSettingsModel(
      organization: OrganizationSettings.fromJson(json['organization'] as Map<String, dynamic>),
      localization: LocalizationSettings.fromJson(json['localization'] as Map<String, dynamic>),
      payments: PaymentSettings.fromJson(json['payments'] as Map<String, dynamic>),
      gateways: GatewaySettings.fromJson(json['gateways'] as Map<String, dynamic>),
      features: FeatureSettings.fromJson(json['features'] as Map<String, dynamic>),
      app: AppInfo.fromJson(json['app'] as Map<String, dynamic>),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'organization': organization.toJson(),
      'localization': localization.toJson(),
      'payments': payments.toJson(),
      'gateways': gateways.toJson(),
      'features': features.toJson(),
      'app': app.toJson(),
    };
  }
}

// Sub-models for each section

class OrganizationSettings {
  final String name;
  final String? logo;
  final String? address;
  final SupportInfo support;

  OrganizationSettings({required this.name, this.logo, this.address, required this.support});

  factory OrganizationSettings.fromJson(Map<String, dynamic> json) {
    return OrganizationSettings(
      name: json['name'] as String,
      logo: json['logo'] as String?,
      address: json['address'] as String?,
      support: SupportInfo.fromJson(json['support'] as Map<String, dynamic>),
    );
  }

  Map<String, dynamic> toJson() => {'name': name, 'logo': logo, 'address': address, 'support': support.toJson()};
}

class SupportInfo {
  final String? email;
  final String? phone;

  SupportInfo({this.email, this.phone});

  factory SupportInfo.fromJson(Map<String, dynamic> json) {
    return SupportInfo(email: json['email'] as String?, phone: json['phone'] as String?);
  }

  Map<String, dynamic> toJson() => {'email': email, 'phone': phone};
}

class LocalizationSettings {
  final String defaultCurrency;
  final String dateFormat;
  final String locale;

  LocalizationSettings({required this.defaultCurrency, required this.dateFormat, required this.locale});

  factory LocalizationSettings.fromJson(Map<String, dynamic> json) {
    return LocalizationSettings(
      defaultCurrency: json['defaultCurrency'] as String,
      dateFormat: json['dateFormat'] as String,
      locale: json['locale'] as String,
    );
  }

  Map<String, dynamic> toJson() => {'defaultCurrency': defaultCurrency, 'dateFormat': dateFormat, 'locale': locale};
}

class PaymentSettings {
  final bool allowPartialPayments;
  final int minimumPaymentAmount;

  PaymentSettings({required this.allowPartialPayments, required this.minimumPaymentAmount});

  factory PaymentSettings.fromJson(Map<String, dynamic> json) {
    return PaymentSettings(
      allowPartialPayments: json['allowPartialPayments'] as bool,
      minimumPaymentAmount: (json['minimumPaymentAmount'] as num).toInt(),
    );
  }

  Map<String, dynamic> toJson() => {'allowPartialPayments': allowPartialPayments, 'minimumPaymentAmount': minimumPaymentAmount};
}

class GatewaySettings {
  final CinetpaySettings? cinetpay;

  GatewaySettings({this.cinetpay});

  factory GatewaySettings.fromJson(Map<String, dynamic> json) {
    return GatewaySettings(
      cinetpay: json['cinetpay'] != null ? CinetpaySettings.fromJson(json['cinetpay'] as Map<String, dynamic>) : null,
    );
  }

  Map<String, dynamic> toJson() => {'cinetpay': cinetpay?.toJson()};
}

class CinetpaySettings {
  final bool enabled;
  final String currency;

  CinetpaySettings({required this.enabled, required this.currency});

  factory CinetpaySettings.fromJson(Map<String, dynamic> json) {
    return CinetpaySettings(enabled: json['enabled'] as bool, currency: json['currency'] as String);
  }

  Map<String, dynamic> toJson() => {'enabled': enabled, 'currency': currency};
}

class FeatureSettings {
  final bool emailNotifications;

  FeatureSettings({required this.emailNotifications});

  factory FeatureSettings.fromJson(Map<String, dynamic> json) {
    return FeatureSettings(emailNotifications: json['emailNotifications'] as bool);
  }

  Map<String, dynamic> toJson() => {'emailNotifications': emailNotifications};
}

class AppInfo {
  final String name;
  final String version;

  AppInfo({required this.name, required this.version});

  factory AppInfo.fromJson(Map<String, dynamic> json) {
    return AppInfo(name: json['name'] as String, version: json['version'] as String);
  }

  Map<String, dynamic> toJson() => {'name': name, 'version': version};
}
