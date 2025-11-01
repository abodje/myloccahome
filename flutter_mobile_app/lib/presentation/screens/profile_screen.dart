import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../models/profile_model.dart';
import '../../services/auth_service.dart';
import '../../services/tenant_data_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/app_drawer.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({Key? key}) : super(key: key);

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  ProfileModel? _profile;
  bool _isLoading = true;
  bool _isUpdatingProfile = false;
  bool _isChangingPassword = false;
  String? _error;

  // Controllers
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  final _cityController = TextEditingController();
  final _postalCodeController = TextEditingController();
  final _professionController = TextEditingController();
  final _emergencyNameController = TextEditingController();
  final _emergencyPhoneController = TextEditingController();
  final _currentPasswordController = TextEditingController();
  final _newPasswordController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  @override
  void dispose() {
    _phoneController.dispose();
    _addressController.dispose();
    _cityController.dispose();
    _postalCodeController.dispose();
    _professionController.dispose();
    _emergencyNameController.dispose();
    _emergencyPhoneController.dispose();
    _currentPasswordController.dispose();
    _newPasswordController.dispose();
    super.dispose();
  }

  Future<void> _loadProfile() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final dataService = TenantDataService(Provider.of<AuthService>(context, listen: false));
      final profileData = await dataService.getProfile();
      if (!mounted) return;
      setState(() {
        _profile = profileData;
        _updateControllers(profileData);
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

  void _updateControllers(ProfileModel profile) {
    _phoneController.text = profile.phone ?? '';
    _addressController.text = profile.address ?? '';
    _cityController.text = profile.city ?? '';
    _postalCodeController.text = profile.postalCode ?? '';
    _professionController.text = profile.profession ?? '';
    _emergencyNameController.text = profile.emergencyContactName ?? '';
    _emergencyPhoneController.text = profile.emergencyContactPhone ?? '';
  }

  Future<void> _updateProfile() async {
    if (_formKey.currentState!.validate()) {
      _formKey.currentState!.save();
      setState(() => _isUpdatingProfile = true);
      try {
        final dataService = TenantDataService(Provider.of<AuthService>(context, listen: false));
        await dataService.updateProfile(_profile!);
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Profil mis à jour avec succès !'), backgroundColor: Colors.green),
        );
      } catch (e) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
        );
      } finally {
        if (mounted) setState(() => _isUpdatingProfile = false);
      }
    }
  }

  Future<void> _changePassword() async {
    if (_currentPasswordController.text.isEmpty || _newPasswordController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez remplir les deux champs de mot de passe.'), backgroundColor: Colors.red),
      );
      return;
    }
    setState(() => _isChangingPassword = true);
    try {
      final dataService = TenantDataService(Provider.of<AuthService>(context, listen: false));
      await dataService.changePassword(_currentPasswordController.text, _newPasswordController.text);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Mot de passe changé avec succès !'), backgroundColor: Colors.green),
      );
      _currentPasswordController.clear();
      _newPasswordController.clear();
      FocusScope.of(context).unfocus(); // Dismiss keyboard
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) setState(() => _isChangingPassword = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mon profil'), backgroundColor: AppTheme.primaryBlue, foregroundColor: Colors.white, elevation: 0),
      drawer: const AppDrawer(),
      backgroundColor: AppTheme.backgroundGrey,
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryBlue))
          : _error != null
              ? Center(child: Text('Erreur: $_error'))
              : _buildForm(),
    );
  }

  Widget _buildForm() {
    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 80), // Add padding for floating button
        children: [
          _buildSectionCard(
            icon: Icons.person_outline,
            title: 'Informations personnelles',
            children: [
              _buildTextField(_phoneController, 'Téléphone', icon: Icons.phone_outlined, onSaved: (val) => _profile!.phone = val),
              _buildTextField(_addressController, 'Adresse', icon: Icons.home_outlined, onSaved: (val) => _profile!.address = val),
              _buildTextField(_cityController, 'Ville', icon: Icons.location_city_outlined, onSaved: (val) => _profile!.city = val),
              _buildTextField(_postalCodeController, 'Code Postal', icon: Icons.local_post_office_outlined, onSaved: (val) => _profile!.postalCode = val),
              _buildTextField(_professionController, 'Profession', icon: Icons.work_outline, onSaved: (val) => _profile!.profession = val),
            ],
          ),
          _buildSectionCard(
            icon: Icons.contact_emergency_outlined,
            title: 'Contact d\'urgence',
            children: [
              _buildTextField(_emergencyNameController, 'Nom du contact', icon: Icons.person_outline, onSaved: (val) => _profile!.emergencyContactName = val),
              _buildTextField(_emergencyPhoneController, 'Téléphone du contact', icon: Icons.phone_outlined, onSaved: (val) => _profile!.emergencyContactPhone = val),
            ],
          ),
          _buildSectionCard(
            icon: Icons.notifications_outlined,
            title: 'Notifications',
            children: [
              _buildSwitchTile('Notifications par e-mail', _profile!.notifications.emailNotifications, (val) => setState(() => _profile!.notifications.emailNotifications = val)),
              _buildSwitchTile('Rappels de paiement', _profile!.notifications.paymentReminders, (val) => setState(() => _profile!.notifications.paymentReminders = val)),
              _buildSwitchTile('Suivi des demandes', _profile!.notifications.maintenanceUpdates, (val) => setState(() => _profile!.notifications.maintenanceUpdates = val)),
              _buildSwitchTile('Alertes de documents', _profile!.notifications.documentAlerts, (val) => setState(() => _profile!.notifications.documentAlerts = val)),
            ],
          ),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 16.0),
            child: _buildElevatedButton(
              onPressed: _updateProfile,
              label: 'ENREGISTRER LES MODIFICATIONS',
              isLoading: _isUpdatingProfile,
              icon: Icons.save_outlined,
            ),
          ),
          const Divider(height: 20),
          _buildSectionCard(
            icon: Icons.lock_outline,
            title: 'Changer le mot de passe',
            children: [
              _buildTextField(_currentPasswordController, 'Mot de passe actuel', icon: Icons.password_outlined, isPassword: true),
              _buildTextField(_newPasswordController, 'Nouveau mot de passe', icon: Icons.password_outlined, isPassword: true),
            ],
          ),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 16.0),
            child: _buildElevatedButton(
              onPressed: _changePassword,
              label: 'CHANGER LE MOT DE PASSE',
              isLoading: _isChangingPassword,
              icon: Icons.lock_reset_outlined,
              color: AppTheme.primaryOrange,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionCard({required IconData icon, required String title, required List<Widget> children}) {
    return Card(
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.05),
      margin: const EdgeInsets.only(bottom: 20),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ListTile(
              contentPadding: EdgeInsets.zero,
              leading: Icon(icon, color: AppTheme.primaryBlue, size: 28),
              title: Text(title, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)),
            ),
            const Divider(),
            const SizedBox(height: 16),
            ...children,
          ],
        ),
      ),
    );
  }

  Widget _buildTextField(TextEditingController controller, String label, {required IconData icon, bool isPassword = false, void Function(String?)? onSaved}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: controller,
        obscureText: isPassword,
        decoration: InputDecoration(
          labelText: label,
          prefixIcon: Icon(icon, color: AppTheme.primaryBlue.withOpacity(0.7)),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
          filled: true,
          fillColor: AppTheme.backgroundGrey.withOpacity(0.5),
        ),
        onSaved: onSaved,
      ),
    );
  }

  Widget _buildSwitchTile(String title, bool value, ValueChanged<bool> onChanged) {
    return SwitchListTile(
      contentPadding: EdgeInsets.zero,
      title: Text(title),
      value: value,
      onChanged: onChanged,
      activeColor: AppTheme.primaryBlue,
    );
  }

  Widget _buildElevatedButton({
    required VoidCallback onPressed,
    required String label,
    required bool isLoading,
    required IconData icon,
    Color color = AppTheme.primaryBlue,
  }) {
    return ElevatedButton.icon(
      onPressed: isLoading ? null : onPressed,
      icon: isLoading ? Container() : Icon(icon, color: Colors.white),
      label: isLoading
          ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 3, color: Colors.white))
          : Text(label, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
      style: ElevatedButton.styleFrom(
        backgroundColor: color,
        padding: const EdgeInsets.symmetric(vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        minimumSize: const Size(double.infinity, 50),
      ),
    );
  }
}
