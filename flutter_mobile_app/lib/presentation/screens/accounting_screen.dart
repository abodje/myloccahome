import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import '../../models/accounting_model.dart';
import '../../services/auth_service.dart';
import '../../services/tenant_data_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/app_drawer.dart';

class AccountingScreen extends StatefulWidget {
  const AccountingScreen({Key? key}) : super(key: key);

  @override
  State<AccountingScreen> createState() => _AccountingScreenState();
}

class _AccountingScreenState extends State<AccountingScreen> {
  AccountingDataModel? _accountingData;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadAccountingData();
  }

  Future<void> _loadAccountingData() async {
    final authService = Provider.of<AuthService>(context, listen: false);
    final dataService = TenantDataService(authService);

    if (!mounted) return;
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final data = await dataService.getAccounting();
      if (kDebugMode) {
        print('Nombre d\'entrées comptables reçues: ${data.entries.length}');
      }
      if (!mounted) return;
      setState(() {
        _accountingData = data;
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
    return '${NumberFormat.currency(locale: 'fr_FR', symbol: currencySymbol).format(amount)}';
  }

  @override
  Widget build(BuildContext context) {
    final currencySymbol = Provider.of<AuthService>(context).settings?.localization.defaultCurrency ?? '€';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Ma comptabilité'),
        backgroundColor: AppTheme.primaryBlue,
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: false,
      ),
      drawer: const AppDrawer(),
      backgroundColor: AppTheme.backgroundGrey,
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryBlue))
          : _error != null || _accountingData == null
              ? _buildErrorView()
              : RefreshIndicator(
                  onRefresh: _loadAccountingData,
                  color: AppTheme.primaryBlue,
                  child: _buildContent(currencySymbol),
                ),
    );
  }

  Widget _buildErrorView() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, color: Colors.red, size: 60),
          const SizedBox(height: 16),
          Text('Erreur de chargement', style: Theme.of(context).textTheme.headlineSmall),
          const SizedBox(height: 8),
          Text(_error ?? 'Impossible de charger les données comptables.', textAlign: TextAlign.center),
          const SizedBox(height: 16),
          ElevatedButton(onPressed: _loadAccountingData, child: const Text('Réessayer')),
        ],
      ),
    );
  }

  Widget _buildContent(String currencySymbol) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _buildSummaryCard(context, _accountingData!.summary, currencySymbol),
        const SizedBox(height: 24),
        _buildEntriesList(context, _accountingData!.entries, currencySymbol),
      ],
    );
  }

  Widget _buildSummaryCard(BuildContext context, AccountingSummaryModel summary, String currencySymbol) {
    return Card(
      elevation: 4,
      shadowColor: Colors.black.withOpacity(0.1),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Solde actuel', style: Theme.of(context).textTheme.titleMedium?.copyWith(color: AppTheme.textLight)),
            const SizedBox(height: 8),
            Text(
              _formatAmount(summary.balance, currencySymbol),
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    color: summary.balance >= 0 ? AppTheme.primaryBlue : AppTheme.primaryOrange,
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const Divider(height: 32),
            Row(
              children: [
                Expanded(
                  child: _buildSummaryItem(context, 'Total dû', summary.totalDue, currencySymbol, icon: Icons.arrow_downward, color: AppTheme.primaryOrange),
                ),
                Expanded(
                  child: _buildSummaryItem(context, 'Total payé', summary.totalPaid, currencySymbol, icon: Icons.arrow_upward, color: Colors.green.shade700),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryItem(BuildContext context, String label, double amount, String currencySymbol, {required IconData icon, required Color color}) {
    return Row(
      children: [
        Icon(icon, color: color, size: 20),
        const SizedBox(width: 8),
        Expanded( // This will make the Column take the remaining space
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: Theme.of(context).textTheme.bodySmall),
              const SizedBox(height: 4),
              Text(
                _formatAmount(amount, currencySymbol),
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: AppTheme.textDark,
                      fontWeight: FontWeight.w600,
                    ),
                softWrap: false, // Prevent wrapping and let it overflow with ellipsis if needed
                overflow: TextOverflow.ellipsis, // Handle long text gracefully
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildEntriesList(BuildContext context, List<AccountingEntryModel> entries, String currencySymbol) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'DERNIÈRES ÉCRITURES',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                color: AppTheme.primaryBlue,
                fontWeight: FontWeight.bold,
              ),
        ),
        const SizedBox(height: 16),
        if (entries.isEmpty)
          Center(
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 40.0),
              child: Text(
                'Aucune écriture comptable pour le moment',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.textLight),
              ),
            ),
          )
        else
          ListView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: entries.length,
            itemBuilder: (context, index) {
              return _buildEntryItem(context, entries[index], currencySymbol);
            },
          ),
      ],
    );
  }

  IconData _getEntryIcon(String description) {
    final desc = description.toLowerCase();
    if (desc.contains('loyer')) return Icons.home_work_outlined;
    if (desc.contains('paiement')) return Icons.payment;
    if (desc.contains('charge')) return Icons.receipt_long;
    if (desc.contains('facture')) return Icons.receipt;
    return Icons.request_page_outlined;
  }

  Widget _buildEntryItem(BuildContext context, AccountingEntryModel entry, String currencySymbol) {
    final isCredit = entry.isCredit;
    final color = isCredit ? Colors.green.shade700 : AppTheme.primaryOrange;
    final icon = isCredit ? Icons.arrow_upward : Icons.arrow_downward;

    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: color.withOpacity(0.1),
          child: Icon(icon, color: color, size: 20),
        ),
        title: Text(
          entry.description,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
        ),
        subtitle: Text(
          DateFormat.yMMMd('fr_FR').format(DateTime.parse(entry.date)),
          style: Theme.of(context).textTheme.bodySmall,
        ),
        trailing: Text(
          _formatAmount(entry.amount, currencySymbol),
          style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                fontWeight: FontWeight.bold,
                color: color,
              ),
        ),
      ),
    );
  }
}
