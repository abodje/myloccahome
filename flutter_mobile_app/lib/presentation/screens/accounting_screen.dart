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
                  child: _buildContent(currencySymbol),
                ),
    );
  }

  Widget _buildErrorView() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text(_error ?? 'Impossible de charger les données comptables.', style: Theme.of(context).textTheme.bodyMedium),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: _loadAccountingData,
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
        _buildSummaryCard(context, _accountingData!.summary, currencySymbol),
        const SizedBox(height: 24),
        _buildEntriesList(context, _accountingData!.entries, currencySymbol),
      ],
    );
  }

  Widget _buildSummaryCard(BuildContext context, AccountingSummaryModel summary, String currencySymbol) {
    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Solde actuel', style: Theme.of(context).textTheme.titleMedium),
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
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _buildSummaryItem(context, 'Total dû', summary.totalDue, currencySymbol),
                _buildSummaryItem(context, 'Total payé', summary.totalPaid, currencySymbol, isPositive: true),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryItem(BuildContext context, String label, double amount, String currencySymbol, {bool isPositive = false}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: Theme.of(context).textTheme.bodySmall),
        const SizedBox(height: 4),
        Text(
          _formatAmount(amount, currencySymbol),
          style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                color: isPositive ? Colors.green.shade700 : AppTheme.textDark,
                fontWeight: FontWeight.w600,
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

  Widget _buildEntryItem(BuildContext context, AccountingEntryModel entry, String currencySymbol) {
    final isCredit = entry.isCredit;
    final color = isCredit ? Colors.green.shade700 : AppTheme.primaryOrange;

    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    DateFormat.yMMMd('fr_FR').format(DateTime.parse(entry.date)),
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    entry.description,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            const SizedBox(width: 16),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  _formatAmount(entry.amount, currencySymbol),
                  style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                        color: color,
                      ),
                ),
                const SizedBox(height: 4),
                Text(
                  isCredit ? 'Crédit' : 'Débit',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(color: color),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
