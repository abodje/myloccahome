import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:go_router/go_router.dart';
import 'services/auth_service.dart';
import 'services/api_service.dart';
import 'theme/app_theme.dart';
import 'presentation/screens/login_screen.dart';
import 'presentation/screens/dashboard_screen.dart';
import 'presentation/screens/documents_screen.dart';
import 'presentation/screens/payments_screen.dart';
import 'presentation/screens/requests_screen.dart';
import 'presentation/screens/property_screen.dart';
import 'presentation/screens/profile_screen.dart';
import 'presentation/screens/accounting_screen.dart';

// Future pour l'initialisation des services
final Future<AuthService> _initialization = _initServices();

Future<AuthService> _initServices() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('fr_FR', null);
  final prefs = await SharedPreferences.getInstance();
  final apiService = ApiService();
  return AuthService(apiService, prefs);
}

void main() {
  runApp(const AppInitializer());
}

class AppInitializer extends StatelessWidget {
  const AppInitializer({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<AuthService>(
      future: _initialization,
      builder: (context, snapshot) {
        if (snapshot.hasData) {
          return MyApp(authService: snapshot.data!);
        } else if (snapshot.hasError) {
          return MaterialApp(
            home: Scaffold(
              body: Center(
                child: Text('Erreur d\'initialisation: ${snapshot.error}'),
              ),
            ),
          );
        } else {
          return const MaterialApp(
            home: Scaffold(
              body: Center(child: CircularProgressIndicator()),
            ),
          );
        }
      },
    );
  }
}

class MyApp extends StatelessWidget {
  final AuthService authService;

  const MyApp({Key? key, required this.authService}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider<AuthService>.value(
      value: authService,
      child: MaterialApp.router(
        title: 'Lokapro - Locataire',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.lightTheme,
        routerConfig: _router,
      ),
    );
  }
}

final GoRouter _router = GoRouter(
  initialLocation: '/login',
  routes: [
    GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
    GoRoute(
      path: '/dashboard',
      builder: (context, state) => const DashboardScreen(),
    ),
    GoRoute(
      path: '/profile',
      builder: (context, state) => const ProfileScreen(),
    ),
    GoRoute(
      path: '/documents',
      builder: (context, state) => const DocumentsScreen(),
    ),
    GoRoute(
      path: '/payments',
      builder: (context, state) => const PaymentsScreen(),
    ),
    GoRoute(
      path: '/requests',
      builder: (context, state) => const RequestsScreen(),
    ),
    GoRoute(
      path: '/property',
      builder: (context, state) => const PropertyScreen(),
    ),
    GoRoute(
      path: '/accounting',
      builder: (context, state) => const AccountingScreen(),
    ),
  ],
);
