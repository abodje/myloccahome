class PaymentModel {
  final int id;
  final String type;
  final double amount;
  final String dueDate;
  final String? paidDate;
  final String status;
  final String? paymentMethod;
  final String? reference;
  final Map<String, dynamic>? property;

  PaymentModel({
    required this.id,
    required this.type,
    required this.amount,
    required this.dueDate,
    this.paidDate,
    required this.status,
    this.paymentMethod,
    this.reference,
    this.property,
  });

  factory PaymentModel.fromJson(Map<String, dynamic> json) {
    return PaymentModel(
      id: json['id'] ?? 0,
      type: json['type'] ?? '',
      amount: double.tryParse(json['amount']?.toString() ?? '0') ?? 0.0,
      dueDate: json['dueDate'] ?? '',
      paidDate: json['paidDate'],
      status: json['status'] ?? '',
      paymentMethod: json['paymentMethod'],
      reference: json['reference'],
      property: json['property'],
    );
  }

  bool get isValidated => status.toLowerCase() == 'payé' || status == 'Validé';
  bool get isError => status.toLowerCase() == 'erreur' || status == 'Error';
  bool get isPending => !isValidated && !isError;
}
