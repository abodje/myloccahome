import 'package:flutter/material.dart';
import '../../theme/app_theme.dart';
import '../../widgets/app_drawer.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mon profil'),
        backgroundColor: AppTheme.primaryBlue,
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: false,
        actions: [
          IconButton(
            icon: const Icon(Icons.chat_bubble_outline),
            onPressed: () {},
          ),
        ],
      ),
      drawer: const AppDrawer(),
      backgroundColor: AppTheme.backgroundGrey,
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            elevation: 2,
            shadowColor: Colors.black.withOpacity(0.05),
            child: Column(
              children: [
                _buildProfileOption(
                  context,
                  icon: Icons.person_outline,
                  title: 'Mes informations',
                  onTap: () {},
                ),
                const Divider(height: 1, indent: 16, endIndent: 16),
                _buildProfileOption(
                  context,
                  icon: Icons.lock_outline,
                  title: 'Mes identifiants',
                  onTap: () {},
                ),
                const Divider(height: 1, indent: 16, endIndent: 16),
                _buildProfileOption(
                  context,
                  icon: Icons.credit_card_outlined,
                  title: 'Mon mode de paiement',
                  onTap: () {},
                ),
                const Divider(height: 1, indent: 16, endIndent: 16),
                _buildProfileOption(
                  context,
                  icon: Icons.notifications_outlined,
                  title: 'Mes notifications',
                  onTap: () {},
                ),
                const Divider(height: 1, indent: 16, endIndent: 16),
                _buildProfileOption(
                  context,
                  icon: Icons.shield_outlined,
                  title: 'Confidentialité',
                  isLast: true,
                  onTap: () {},
                ),
              ],
            ),
          ),
          const SizedBox(height: 32),
          _buildVersionInfo(context),
          const SizedBox(height: 16),
          _buildPersonalDataButton(context),
        ],
      ),
    );
  }

  Widget _buildProfileOption(BuildContext context, {
    required IconData icon,
    required String title,
    required VoidCallback onTap,
    bool isLast = false,
  }) {
    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
      leading: Icon(icon, color: AppTheme.primaryBlue),
      title: Text(title, style: Theme.of(context).textTheme.bodyLarge),
      trailing: const Icon(Icons.chevron_right, color: AppTheme.textLight),
      onTap: onTap,
      shape: isLast ? null : const Border(bottom: BorderSide(color: Colors.transparent)),
    );
  }

  Widget _buildVersionInfo(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Text(
            'Version principale : 2.90.0',
            style: Theme.of(context).textTheme.bodySmall,
          ),
          const SizedBox(height: 4),
          Text(
            'Mise à jour : 72b0d7f0-cd96-486d-bb4f-d48c152937f2',
            style: Theme.of(context).textTheme.bodySmall,
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildPersonalDataButton(BuildContext context) {
    return Center(
      child: TextButton(
        onPressed: () {},
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Mes données personnelles'),
            const SizedBox(width: 8),
            Icon(Icons.arrow_forward, size: 16, color: Theme.of(context).primaryColor),
          ],
        ),
      ),
    );
  }
}
