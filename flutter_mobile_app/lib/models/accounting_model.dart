class AccountingDataModel {
  final AccountingSummaryModel summary;
  final List<AccountingEntryModel> entries;

  AccountingDataModel({required this.summary, required this.entries});

  factory AccountingDataModel.fromJson(Map<String, dynamic> json) {
    return AccountingDataModel(
      summary: AccountingSummaryModel.fromJson(json['accounting'] as Map<String, dynamic>),
      entries: (json['entries'] as List)
          .map((e) => AccountingEntryModel.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

class AccountingSummaryModel {
  final double balance;
  final double totalPaid;
  final double totalDue;
  final double toPay;
  final String? lastPaymentDate;
  final String? nextPaymentDate;

  AccountingSummaryModel({
    required this.balance,
    required this.totalPaid,
    required this.totalDue,
    required this.toPay,
    this.lastPaymentDate,
    this.nextPaymentDate,
  });

  factory AccountingSummaryModel.fromJson(Map<String, dynamic> json) {
    return AccountingSummaryModel(
      balance: (json['balance'] as num? ?? 0).toDouble(),
      totalPaid: (json['totalPaid'] as num? ?? 0).toDouble(),
      totalDue: (json['totalDue'] as num? ?? 0).toDouble(),
      toPay: (json['toPay'] as num? ?? 0).toDouble(),
      lastPaymentDate: json['lastPaymentDate'] as String?,
      nextPaymentDate: json['nextPaymentDate'] as String?,
    );
  }
}

class AccountingEntryModel {
  final int id;
  final String date;
  final String type; // CREDIT or DEBIT
  final String category;
  final double amount;
  final String reference;
  final String description;
  final double runningBalance;

  AccountingEntryModel({
    required this.id,
    required this.date,
    required this.type,
    required this.category,
    required this.amount,
    required this.reference,
    required this.description,
    required this.runningBalance,
  });

  bool get isCredit => type.toUpperCase() == 'CREDIT';
  bool get isDebit => type.toUpperCase() == 'DEBIT';

  factory AccountingEntryModel.fromJson(Map<String, dynamic> json) {
    return AccountingEntryModel(
      id: json['id'] as int,
      date: json['date'] as String,
      type: json['type'] as String,
      category: json['category'] as String,
      amount: (json['amount'] as num? ?? 0).toDouble(),
      reference: json['reference'] as String,
      description: json['description'] as String,
      runningBalance: (json['runningBalance'] as num? ?? 0).toDouble(),
    );
  }
}
