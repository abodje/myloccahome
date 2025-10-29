import 'package:flutter/material.dart';
import '../widgets/app_drawer.dart';

class AccountingScreen extends StatelessWidget {
  const AccountingScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Ma comptabilité'),
      ),
      drawer: const AppDrawer(),
      body: const Center(
        child: Text('À venir'),
      ),
    );
  }
}
