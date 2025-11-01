import 'package:flutter/foundation.dart';

class RequestListModel {
  final List<RequestModel> requests;
  final RequestStatisticsModel statistics;

  RequestListModel({required this.requests, required this.statistics});

  factory RequestListModel.fromJson(Map<String, dynamic> json) {
    var requestList = json['requests'] as List;
    List<RequestModel> requests = requestList.map((i) => RequestModel.fromJson(i)).toList();

    return RequestListModel(
      requests: requests,
      statistics: RequestStatisticsModel.fromJson(json['statistics'] as Map<String, dynamic>),
    );
  }
}

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
  final PropertyInfoModel property;

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
    required this.property,
  });

  factory RequestModel.fromJson(Map<String, dynamic> json) {
    return RequestModel(
      id: json['id'] as int,
      reference: json['reference'] as String,
      title: json['title'] as String,
      category: json['category'] as String,
      description: json['description'] as String,
      status: json['status'] as String,
      priority: json['priority'] as String,
      reportedDate: json['reportedDate'] as String,
      scheduledDate: json['scheduledDate'] as String?,
      completedDate: json['completedDate'] as String?,
      property: PropertyInfoModel.fromJson(json['property'] as Map<String, dynamic>),
    );
  }
}

class PropertyInfoModel {
  final String address;

  PropertyInfoModel({required this.address});

  factory PropertyInfoModel.fromJson(Map<String, dynamic> json) {
    return PropertyInfoModel(address: json['address'] as String);
  }
}

class RequestStatisticsModel {
  final int total;
  final int pending;
  final int inProgress;
  final int completed;

  RequestStatisticsModel({
    required this.total,
    required this.pending,
    required this.inProgress,
    required this.completed,
  });

  factory RequestStatisticsModel.fromJson(Map<String, dynamic> json) {
    return RequestStatisticsModel(
      total: json['total'] as int,
      pending: json['pending'] as int,
      inProgress: json['inProgress'] as int,
      completed: json['completed'] as int,
    );
  }
}
