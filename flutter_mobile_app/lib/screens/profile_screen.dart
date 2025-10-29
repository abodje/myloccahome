import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import '../widgets/app_drawer.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mon profil'),
        actions: [
          IconButton(
            icon: const Icon(Icons.chat_bubble_outline),
            onPressed: () {},
          ),
        ],
      ),
      drawer: const AppDrawer(),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildProfileOption(
            context,
            icon: Icons.person_outline,
            title: 'Mes informations',
            onTap: () {},
          ),
          _buildProfileOption(
            context,
            icon: Icons.lock_outline,
            title: 'Mes identifiants',
            onTap: () {},
          ),
          _buildProfileOption(
            context,
            icon: Icons.credit_card_outlined,
            title: 'Mon mode de paiement',
            onTap: () {},
          ),
          _buildProfileOption(
            context,
            icon: Icons.notifications_outlined,
            title: 'Mes notifications',
            onTap: () {},
          ),
          _buildProfileOption(
            context,
            icon: Icons.shield_outlined,
            title: 'Confidentialité',
            onTap: () {},
          ),
          const SizedBox(height: 24),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Version principale : 2.90.0',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: AppTheme.textLight,
                      ),
                ),
                Text(
                  'Mise à jour : 72b0d7f0-cd96-486d-bb4f-d48c152937f2',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: AppTheme.textLight,
                      ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: TextButton(
              onPressed: () {},
              child: Row(
                children: [
                  Text(
                    'Mes données personnelles',
                    style: TextStyle(color: AppTheme.primaryBlue),
                  ),
                  const SizedBox(width: 8),
                  Icon(Icons.arrow_forward,
                      color: AppTheme.primaryBlue, size: 16),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildProfileOption(
    BuildContext context, {
    required IconData icon,
    required String title,
    required VoidCallback onTap,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: ListTile(
        leading: Icon(icon, color: AppTheme.lightBlue),
        title: Text(
          title,
          style: Theme.of(context).textTheme.bodyLarge,
        ),
        trailing: Icon(Icons.chevron_right, color: AppTheme.textLight),
        onTap: onTap,
      ),
    );
  }
}
