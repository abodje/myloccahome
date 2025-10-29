import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../services/auth_service.dart';
import '../theme/app_theme.dart';

class AppDrawer extends StatelessWidget {
  const AppDrawer({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final authService = Provider.of<AuthService>(context);
    final tenant = authService.tenant;
    final user = authService.user;

    return Drawer(
      backgroundColor: AppTheme.primaryBlue,
      child: SafeArea(
        child: Column(
          children: [
            // Header avec informations utilisateur
            Container(
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  CircleAvatar(
                    radius: 30,
                    backgroundColor: Colors.white.withOpacity(0.2),
                    child: Text(
                      (tenant?.firstName ?? user?.firstName ?? 'U')
                          .substring(0, 1)
                          .toUpperCase(),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    tenant?.fullName ?? user?.fullName ?? 'Locataire',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    tenant?.email ?? user?.email ?? '',
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.8),
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            ),
            const Divider(color: Colors.white24, height: 1),
            // Menu items
            Expanded(
              child: ListView(
                padding: EdgeInsets.zero,
                children: [
                  _DrawerItem(
                    icon: Icons.dashboard,
                    title: 'Tableau de bord',
                    onTap: () {
                      Navigator.pop(context);
                      context.go('/dashboard');
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.person,
                    title: 'Mon profil',
                    onTap: () {
                      Navigator.pop(context);
                      context.go('/profile');
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.message,
                    title: 'Mes demandes',
                    onTap: () {
                      Navigator.pop(context);
                      context.go('/requests');
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.account_balance,
                    title: 'Ma comptabilité',
                    onTap: () {
                      Navigator.pop(context);
                      context.go('/accounting');
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.payment,
                    title: 'Mes paiements',
                    onTap: () {
                      Navigator.pop(context);
                      context.go('/payments');
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.handshake,
                    title: 'Offres et services',
                    onTap: () {
                      Navigator.pop(context);
                      // TODO: Naviguer vers offres
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.home,
                    title: 'Mes biens',
                    onTap: () {
                      Navigator.pop(context);
                      context.go('/property');
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.description,
                    title: 'Mes documents',
                    onTap: () {
                      Navigator.pop(context);
                      context.go('/documents');
                    },
                  ),
                ],
              ),
            ),
            const Divider(color: Colors.white24, height: 1),
            // Déconnexion
            _DrawerItem(
              icon: Icons.logout,
              title: 'Déconnexion',
              onTap: () async {
                await authService.logout();
                if (context.mounted) {
                  context.go('/login');
                }
              },
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }
}

class _DrawerItem extends StatelessWidget {
  final IconData icon;
  final String title;
  final VoidCallback onTap;

  const _DrawerItem({
    required this.icon,
    required this.title,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(icon, color: Colors.white),
      title: Text(
        title,
        style: const TextStyle(color: Colors.white, fontSize: 16),
      ),
      onTap: onTap,
      contentPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 4),
    );
  }
}
