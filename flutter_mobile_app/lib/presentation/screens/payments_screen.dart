import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';
import '../../services/tenant_data_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/app_drawer.dart';
import '../../models/payment_model.dart';

class PaymentsScreen extends StatefulWidget {
  const PaymentsScreen({Key? key}) : super(key: key);

  @override
  State<PaymentsScreen> createState() => _PaymentsScreenState();
}

class _PaymentsScreenState extends State<PaymentsScreen> {
  List<PaymentModel> _payments = [];
  Map<String, dynamic>? _statistics;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadPayments();
  }

  Future<void> _loadPayments() async {
    if (!mounted) return;
    final authService = Provider.of<AuthService>(context, listen: false);
    final dataService = TenantDataService(authService);

    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final data = await dataService.getPayments();
      if (!mounted) return;
      setState(() {
        _payments = (data['payments'] as List?)
                ?.map((p) => PaymentModel.fromJson(p))
                .toList() ??
            [];
        _statistics = data['statistics'];
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  String _formatAmount(double amount, String currencySymbol) {
    return '${amount.toStringAsFixed(2).replaceAll('.', ',')} $currencySymbol';
  }

  @override
  Widget build(BuildContext context) {
    final authService = Provider.of<AuthService>(context);
    final currencySymbol = authService.settings?.localization.defaultCurrency ?? '€';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes paiements'),
        backgroundColor: AppTheme.primaryBlue,
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: false,
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () {},
          ),
        ],
      ),
      drawer: const AppDrawer(),
      backgroundColor: AppTheme.backgroundGrey,
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryBlue))
          : _error != null
              ? _buildErrorView()
              : RefreshIndicator(
                  onRefresh: _loadPayments,
                  child: _buildContent(currencySymbol),
                ),
    );
  }

  Widget _buildErrorView() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text('Erreur: $_error', style: Theme.of(context).textTheme.bodyMedium),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: _loadPayments,
            child: const Text('Réessayer'),
          ),
        ],
      ),
    );
  }

  Widget _buildContent(String currencySymbol) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _buildBalanceCard(context, currencySymbol),
        const SizedBox(height: 24),
        _buildPaymentsHistory(context, currencySymbol),
      ],
    );
  }

  Widget _buildBalanceCard(BuildContext context, String currencySymbol) {
    final balance = double.tryParse(_statistics?['balance']?.toString() ?? '0') ?? 0.0;
    final toPay = double.tryParse(_statistics?['pending']?.toString() ?? '0') ?? 0.0;

    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Solde en cours',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            Text(
              _formatAmount(balance, currencySymbol),
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    color: AppTheme.primaryBlue,
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const Divider(height: 32),
            Text(
              'Solde à venir',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 4),
            Text(
              _formatAmount(toPay, currencySymbol),
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                    color: AppTheme.textDark,
                    fontWeight: FontWeight.w600,
                  ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPaymentsHistory(BuildContext context, String currencySymbol) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'HISTORIQUE DES PAIEMENTS',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                color: AppTheme.primaryBlue,
                fontWeight: FontWeight.bold,
              ),
        ),
        const SizedBox(height: 16),
        if (_payments.isEmpty)
          Center(
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 40.0),
              child: Text(
                'Aucun paiement pour le moment',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.textLight),
              ),
            ),
          )
        else
          ListView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: _payments.length,
            itemBuilder: (context, index) {
              return _buildPaymentItem(context, _payments[index], currencySymbol);
            },
          ),
      ],
    );
  }

  Widget _buildPaymentItem(BuildContext context, PaymentModel payment, String currencySymbol) {
    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        payment.dueDate,
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        payment.type,
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
                      ),
                      if (payment.paidDate != null) ...[
                        const SizedBox(height: 4),
                        Text(
                          'Prélèvement le ${payment.paidDate}',
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(width: 16),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      _formatAmount(payment.amount, currencySymbol),
                      style: Theme.of(context).textTheme.bodyLarge?.copyWith(fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 4),
                    _buildStatusBadge(context, payment),
                  ],
                ),
              ],
            ),
            if (!payment.isValidated && !payment.isError) ...[
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => _showPaymentDialog(context, payment, currencySymbol),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primaryOrange,
                    foregroundColor: Colors.white,
                  ),
                  child: const Text('PAYER'),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  void _showPaymentDialog(BuildContext context, PaymentModel payment, String currencySymbol) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text('Confirmer le paiement'),
          content: Text('Voulez-vous vraiment payer ${payment.type} d\'un montant de ${_formatAmount(payment.amount, currencySymbol)} ?'),
          actions: <Widget>[
            TextButton(
              child: const Text('Annuler'),
              onPressed: () {
                Navigator.of(context).pop();
              },
            ),
            TextButton(
              child: const Text('Payer'),
              onPressed: () {
                // TODO: Implement payment logic
                Navigator.of(context).pop();
              },
            ),
          ],
        );
      },
    );
  }

  Widget _buildStatusBadge(BuildContext context, PaymentModel payment) {
    Color statusColor;
    String statusText;

    if (payment.isValidated) {
      statusColor = AppTheme.primaryBlue; // Changed from green
      statusText = 'Validé';
    } else if (payment.isError) {
      statusColor = AppTheme.primaryOrange; // Changed from red
      statusText = 'Erreur';
    } else {
      statusColor = AppTheme.textLight;
      statusText = 'En attente';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: statusColor.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        statusText,
        style: TextStyle(
          color: statusColor,
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}
