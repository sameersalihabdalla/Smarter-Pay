import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:screenshot/screenshot.dart';
import 'package:share_plus/share_plus.dart';
import 'package:audioplayers/audioplayers.dart';
import 'package:nfc_manager/nfc_manager.dart';

void main() {
  runApp(const SmarterApp());
}

// رابط الـ API الموحد
const String apiUrl = "http://10.92.50.114/smoney/api.php";

class SmarterApp extends StatefulWidget {
  const SmarterApp({super.key});

  @override
  State<SmarterApp> createState() => _SmarterAppState();

  static _SmarterAppState? of(BuildContext context) =>
      context.findAncestorStateOfType<_SmarterAppState>();
}

class _SmarterAppState extends State<SmarterApp> {
  Locale _locale = const Locale('ar');
  Map<String, dynamic>? loggedInUser;
  bool _isBalanceHidden = false;

  void setLocale(Locale locale) {
    setState(() {
      _locale = locale;
    });
  }

  void setUser(Map<String, dynamic> user) {
    setState(() {
      loggedInUser = user;
    });
  }

  void toggleBalanceVisibility() {
    setState(() {
      _isBalanceHidden = !_isBalanceHidden;
    });
  }

  bool get isBalanceHidden => _isBalanceHidden;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Smarter - Digital Banking & Payments',
      locale: _locale,
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      supportedLocales: const [
        Locale('ar', ''),
        Locale('en', ''),
      ],
      builder: (context, child) {
        return Directionality(
          textDirection: _locale.languageCode == 'ar' ? TextDirection.rtl : TextDirection.ltr,
          child: child!,
        );
      },
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF0284c7)),
        fontFamily: 'Cairo',
      ),
      home: const LoginScreen(),
    );
  }
}

// مكون الفوتر الثابت
class AppFooter extends StatelessWidget {
  const AppFooter({super.key});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 24.0, horizontal: 8.0),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Image.asset(
            'logo.png',
            height: 32,
            fit: BoxFit.contain,
          ),
          const SizedBox(height: 8),
          const Text(
            'Produced by Smart Digital Solutions - SUDAN - Sameer Salih',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w300,
              color: Colors.grey,
            ),
          ),
        ],
      ),
    );
  }
}

// قاموس الترجمات الثنائية
class AppStrings {
  static const Map<String, Map<String, String>> localizedValues = {
    'ar': {
      'loginTitle': 'Smarter Pay SDN',
      'welcome': 'مرحباً بك في نظام Smarter المصرفي',
      'email': 'البريد الإلكتروني',
      'password': 'كلمة المرور',
      'loginBtn': 'تسجيل الدخول',
      'dashboard': 'لوحة التحكم',
      'transfer': 'تحويل أموال',
      'statement': 'كشف الحساب',
      'nfcPay': 'دفع NFC',
      'bills': 'سداد الفواتير',
      'electricity': 'شراء الكهرباء',
      'telecom': 'اتصالات الإنترنت',
      'profile': 'حسابي',
      'branch': 'الفرع',
      'searchAccount': 'رقم الحساب / البريد / الرقم الوطني',
      'receiverName': 'اسم المستلم',
      'amount': 'المبلغ المراد تحويله',
      'note': 'تعليق للمستلم (اختياري)',
      'sendTransfer': 'إتمام الحوالة الفورية',
      'logout': 'تسجيل الخروج',
      'required': 'حقل مطلوب',
      'successLogin': 'تم تسجيل الدخول بنجاح',
      'successTransfer': 'تمت الحوالة بنجاح',
      'recentTransactions': 'آخر الحركات المالية',
      'noTransactions': 'لا توجد حركات سابقة',
      'totalSent': 'إجمالي الصادر',
      'totalReceived': 'إجمالي الوارد',
      'currentBalance': 'الرصيد المتاح',
      'bbanLabel': 'رقم الحساب المصرفي  (BBAN): سيتوفر قريباً',
      'recipient': 'مستلم',
      'sender': 'مرسل',
      'scanningNfc': 'جارٍ التقاط إشارة الـ NFC الفيزيائية أو التفاعلية...',
      'fullName': 'الاسم الكامل',
      'nationalId': 'الرقم الوطني',
      'phone': 'رقم الهاتف',
      'address': 'العنوان',
      'saveChanges': 'حفظ التعديلات',
      'profileUpdated': 'بيانات الحساب محمية (عرض فقط ولا يمكن تعديلها)',
      'receiptTitle': 'إيصال تحويل مالي معتمد',
      'receiptSubtitle': 'Smarter Digital Bank Secure Voucher',
      'transactionRef': 'رقم العملية (Ref):',
      'dateTime': 'التاريخ والوقت:',
      'receiverAccount': 'الحساب المستلم:',
      'transferredAmount': 'المبلغ المحول:',
      'qrHint': 'امسح الكود للتأكد من صحة الإيصال عبر الإنترنت',
      'secureHash': 'بصمة الأمان المشفرة (Hash):',
      'backToHome': 'العودة للرئيسية',
      'shareReceipt': 'مشاركة الإيصال كصورة',
      'serviceComingSoon': 'هذه الخدمة ستتوفر قريباً',
      'nfcTitle': 'نظام الطلب والدفع التفاعلي عبر NFC',
      'requestTab': 'طلب مبلغ (بث تلامسي)',
      'scanTab': 'التقاط ذكي (تلقائي/فيزيائي)',
      'nfcInstruction1': 'أنشئ طلباً بمبلغ محدد لبثه عبر التلامس أو كود موجي:',
      'nfcInstruction2': 'قرب الهاتف المقابل لالتقاط الفاتورة وقراءتها فيزيائياً بشكل تلقائي:',
      'startNfcBroadcast': 'بدء بث طلب الـ NFC',
      'nfcCodeLabel': 'أو أدخل كود الطلب يدوياً:',
      'confirmAndPayNfc': 'تأكيد وقبول ودفع المبلغ الفوري',
      'cantLogin': 'لا يمكنني الدخول؟',
      'createNewAccount': 'إنشاء حساب جديد',
    },
    'en': {
      'loginTitle': 'Smarter - Digital Bank',
      'welcome': 'Welcome to Smarter Banking System',
      'email': 'Email Address',
      'password': 'Password',
      'loginBtn': 'Login',
      'dashboard': 'Dashboard',
      'transfer': 'Transfer',
      'statement': 'Account Statement',
      'nfcPay': 'NFC Pay',
      'bills': 'Bill Payment',
      'electricity': 'Electricity',
      'telecom': 'Telecom & Internet',
      'profile': 'My Profile',
      'branch': 'Branch',
      'searchAccount': 'Account Number / Email / ID',
      'receiverName': 'Receiver Name',
      'amount': 'Transfer Amount',
      'note': 'Note for Receiver (Optional)',
      'sendTransfer': 'Complete Instant Transfer',
      'logout': 'Logout',
      'required': 'Required field',
      'successLogin': 'Login Successful',
      'successTransfer': 'Transfer Completed Successfully',
      'recentTransactions': 'Recent Transactions',
      'noTransactions': 'No previous transactions',
      'totalSent': 'Total Sent',
      'totalReceived': 'Total Received',
      'currentBalance': 'Available Balance',
      'bbanLabel': 'BBAN Account Number: Coming Soon',
      'recipient': 'Recipient',
      'sender': 'Sender',
      'scanningNfc': 'Capturing physical or interactive NFC waves...',
      'fullName': 'Full Name',
      'nationalId': 'National ID',
      'phone': 'Phone Number',
      'address': 'Address',
      'saveChanges': 'Save Changes',
      'profileUpdated': 'Profile data is secure (Read Only)',
      'receiptTitle': 'Verified Financial Transfer Receipt',
      'receiptSubtitle': 'Smarter Digital Bank Secure Voucher',
      'transactionRef': 'Transaction Ref:',
      'dateTime': 'Date & Time:',
      'receiverAccount': 'Receiver Account:',
      'transferredAmount': 'Transferred Amount:',
      'qrHint': 'Scan the code to verify receipt online',
      'secureHash': 'Secure Hash Fingerprint:',
      'backToHome': 'Back to Home',
      'shareReceipt': 'Share Receipt as Image',
      'serviceComingSoon': 'This service will be available soon',
      'nfcTitle': 'Interactive NFC Request & Payment',
      'requestTab': 'Request Amount',
      'scanTab': 'Smart Auto-Capture',
      'nfcInstruction1': 'Create a request with a specific amount to broadcast:',
      'nfcInstruction2': 'Bring the other device close to auto-capture the incoming request:',
      'startNfcBroadcast': 'Start NFC Broadcast',
      'nfcCodeLabel': 'Or enter NFC Request Code manually:',
      'confirmAndPayNfc': 'Confirm, Accept & Pay Amount',
      'cantLogin': 'Can\'t login?',
      'createNewAccount': 'Create new account',
    },
  };

  static String get(BuildContext context, String key) {
    String lang = Localizations.localeOf(context).languageCode;
    return localizedValues[lang]?[key] ?? localizedValues['ar']![key]!;
  }
}

Widget buildLanguageDropdown(BuildContext context) {
  String currentLang = Localizations.localeOf(context).languageCode;
  return Padding(
    padding: const EdgeInsets.symmetric(horizontal: 12.0),
    child: DropdownButton<String>(
      value: currentLang,
      underline: const SizedBox(),
      items: const [
        DropdownMenuItem(value: 'ar', child: Text('🌐 العربية', style: TextStyle(fontSize: 13))),
        DropdownMenuItem(value: 'en', child: Text('🌐 English', style: TextStyle(fontSize: 13))),
      ],
      onChanged: (val) {
        if (val != null) {
          SmarterApp.of(context)?.setLocale(Locale(val));
        }
      },
    ),
  );
}

// شاشة تسجيل الدخول
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  bool _isLoading = false;

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isLoading = true);

    try {
      final response = await http.post(
        Uri.parse(apiUrl),
        body: {
          'action': 'login',
          'email': _emailController.text.trim(),
          'password': _passwordController.text,
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          SmarterApp.of(context)?.setUser(data['user']);
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (context) => const MainNavigationScreen()),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message']), backgroundColor: Colors.red));
        }
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red));
    } finally {
      setState(() => _isLoading = false);
    }
  }

  void _showRestrictedActionDialog(String title, String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('حسناً'))
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    bool isArabic = Localizations.localeOf(context).languageCode == 'ar';
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        actions: [buildLanguageDropdown(context)],
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24.0),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Image.asset('logo.png', height: 220, fit: BoxFit.contain),
                  const SizedBox(height: 16),
                  Text(AppStrings.get(context, 'welcome'), textAlign: TextAlign.center, style: const TextStyle(fontSize: 14, color: Colors.grey)),
                  const SizedBox(height: 32),
                  TextFormField(
                    controller: _emailController,
                    decoration: InputDecoration(labelText: AppStrings.get(context, 'email'), border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
                    validator: (v) => v!.isEmpty ? AppStrings.get(context, 'required') : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _passwordController,
                    obscureText: true,
                    decoration: InputDecoration(labelText: AppStrings.get(context, 'password'), border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
                    validator: (v) => v!.isEmpty ? AppStrings.get(context, 'required') : null,
                  ),
                  const SizedBox(height: 24),
                  _isLoading
                      ? const Center(child: CircularProgressIndicator())
                      : ElevatedButton(
                    onPressed: _handleLogin,
                    style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF0284c7), foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14)),
                    child: Text(AppStrings.get(context, 'loginBtn'), style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      TextButton(
                        onPressed: () => _showRestrictedActionDialog(
                          isArabic ? 'تنبيه أمني' : 'Security Notice',
                          isArabic ? 'لا يمكنني الدخول مباشرة؟ يرجى التواصل مع الدعم الفني بالفرع المصرفي.' : 'Cannot login? Please contact bank branch support.',
                        ),
                        child: Text(AppStrings.get(context, 'cantLogin'), style: const TextStyle(color: Colors.grey, fontSize: 13)),
                      ),
                      TextButton(
                        onPressed: () => _showRestrictedActionDialog(
                          isArabic ? 'إنشاء حساب جديد' : 'New Account',
                          isArabic ? 'عذراً، لا يمكن إنشاء حساب جديد عبر التطبيق مباشرة. يرجى زيارة أقرب فرع.' : 'Registration through app is disabled. Visit branch.',
                        ),
                        child: Text(AppStrings.get(context, 'createNewAccount'), style: const TextStyle(color: Color(0xFF0284c7), fontSize: 13, fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  const AppFooter(),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

// التنقل الرئيسي وباقي الشاشات (Dashboard, Transfer, Statement, Profile, NfcPayment, Receipt)
class MainNavigationScreen extends StatefulWidget {
  const MainNavigationScreen({super.key});

  @override
  State<MainNavigationScreen> createState() => _MainNavigationScreenState();
}

class _MainNavigationScreenState extends State<MainNavigationScreen> {
  int _currentIndex = 0;

  @override
  Widget build(BuildContext context) {
    final List<Widget> screens = [
      const DashboardScreen(),
      const TransferScreen(),
      const StatementScreen(),
      const ProfileScreen(),
    ];

    return Scaffold(
      body: screens[_currentIndex],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex,
        onDestinationSelected: (index) => setState(() => _currentIndex = index),
        destinations: [
          NavigationDestination(icon: const Icon(Icons.dashboard), label: AppStrings.get(context, 'dashboard')),
          NavigationDestination(icon: const Icon(Icons.swap_horiz), label: AppStrings.get(context, 'transfer')),
          NavigationDestination(icon: const Icon(Icons.receipt_long), label: AppStrings.get(context, 'statement')),
          NavigationDestination(icon: const Icon(Icons.person), label: AppStrings.get(context, 'profile')),
        ],
      ),
    );
  }
}

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  List<dynamic> _transactions = [];
  bool _isLoadingTransactions = true;

  @override
  void initState() {
    super.initState();
    _fetchUserDataAndTransactions();
  }

  Future<void> _fetchUserDataAndTransactions() async {
    final appState = SmarterApp.of(context);
    final user = appState?.loggedInUser;
    if (user == null) return;

    try {
      final userResponse = await http.get(Uri.parse('$apiUrl?action=get_user_info&user_id=${user['id']}'));
      if (userResponse.statusCode == 200) {
        final userData = jsonDecode(userResponse.body);
        if (userData['status'] == 'success') {
          appState?.setUser(userData['user']);
        }
      }

      final txResponse = await http.get(Uri.parse('$apiUrl?action=get_transactions&user_id=${user['id']}'));
      if (txResponse.statusCode == 200) {
        final txData = jsonDecode(txResponse.body);
        if (txData['status'] == 'success') {
          setState(() {
            _transactions = txData['transactions'];
          });
        }
      }
    } catch (e) {
      print(e);
    } finally {
      setState(() {
        _isLoadingTransactions = false;
      });
    }
  }

  void _showServiceModal(String serviceName) {
    bool isArabic = Localizations.localeOf(context).languageCode == 'ar';
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(serviceName),
        content: Text(isArabic ? 'هذه الخدمة ستتوفر بكامل مزاياها قريباً.' : 'This service will be fully available soon.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('OK'))
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final appState = SmarterApp.of(context);
    final user = appState?.loggedInUser;
    final String fullName = user?['full_name'] ?? 'User';
    final String firstLetter = fullName.isNotEmpty ? fullName.substring(0, 1) : 'U';

    final double currentBalance = double.tryParse(user?['balance']?.toString() ?? '0.0') ?? 0.0;
    final bool isHidden = appState?.isBalanceHidden ?? false;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Smarter Pay', style: TextStyle(fontWeight: FontWeight.bold)),
        centerTitle: true,
        actions: [
          buildLanguageDropdown(context),
          IconButton(
            icon: const Icon(Icons.logout, color: Colors.red),
            onPressed: () {
              Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (context) => const LoginScreen()),
              );
            },
          )
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _fetchUserDataAndTransactions,
        child: ListView(
          padding: const EdgeInsets.all(16.0),
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(colors: [Color(0xFF0284c7), Color(0xFF0369a1)]),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      user?['profile_image'] != null && user!['profile_image'].toString().isNotEmpty
                          ? CircleAvatar(radius: 28, backgroundImage: NetworkImage(user['profile_image']))
                          : CircleAvatar(radius: 28, backgroundColor: Colors.white, child: Text(firstLetter, style: const TextStyle(fontSize: 22, color: Color(0xFF0284c7), fontWeight: FontWeight.bold))),
                      const SizedBox(width: 14),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(fullName, style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
                          Text(user?['branch_name'] ?? '-', style: const TextStyle(color: Colors.white70, fontSize: 12)),
                        ],
                      ),
                    ],
                  ),
                  const Divider(height: 25, color: Colors.white24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(AppStrings.get(context, 'currentBalance'), style: const TextStyle(color: Colors.white70, fontSize: 12)),
                      IconButton(
                        icon: Icon(isHidden ? Icons.visibility_off : Icons.visibility, color: Colors.white70, size: 20),
                        onPressed: () => appState?.toggleBalanceVisibility(),
                      ),
                    ],
                  ),
                  Text(
                    isHidden ? '******** ج.س' : '${currentBalance.toStringAsFixed(2)} ج.س',
                    style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    AppStrings.get(context, 'bbanLabel'),
                    style: const TextStyle(color: Colors.amberAccent, fontSize: 10, fontStyle: FontStyle.italic),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            GridView.count(
              crossAxisCount: 4,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisSpacing: 10,
              mainAxisSpacing: 10,
              children: [
                _buildServiceIcon(Icons.swap_horiz, AppStrings.get(context, 'transfer'), const Color(0xFF0284c7), () {
                  Navigator.push(context, MaterialPageRoute(builder: (context) => const TransferScreen(isStandalone: true)));
                }),
                _buildServiceIcon(Icons.contactless, AppStrings.get(context, 'nfcPay'), Colors.red, () {
                  Navigator.push(context, MaterialPageRoute(builder: (context) => const NfcPaymentScreen()));
                }),
                _buildServiceIcon(Icons.receipt_long, AppStrings.get(context, 'bills'), Colors.orange, () {
                  _showServiceModal(AppStrings.get(context, 'bills'));
                }),
                _buildServiceIcon(Icons.bolt, AppStrings.get(context, 'electricity'), Colors.amber.shade700, () {
                  _showServiceModal(AppStrings.get(context, 'electricity'));
                }),
              ],
            ),
            const SizedBox(height: 20),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.grey.shade200),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(AppStrings.get(context, 'recentTransactions'), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  const Divider(height: 20),
                  _isLoadingTransactions
                      ? const Center(child: CircularProgressIndicator())
                      : _transactions.isEmpty
                      ? Padding(
                    padding: const EdgeInsets.symmetric(vertical: 20),
                    child: Center(child: Text(AppStrings.get(context, 'noTransactions'), style: const TextStyle(color: Colors.grey))),
                  )
                      : ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: _transactions.length > 5 ? 5 : _transactions.length,
                    itemBuilder: (context, index) {
                      final tx = _transactions[index];
                      bool isOutgoing = tx['tx_direction'] == 'outgoing';
                      return ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: Icon(
                          isOutgoing ? Icons.arrow_circle_up : Icons.arrow_circle_down,
                          color: isOutgoing ? Colors.red : Colors.green,
                        ),
                        title: Text(tx['receiver_name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                        subtitle: Text(tx['created_at'] ?? '', style: const TextStyle(fontSize: 11, color: Colors.grey)),
                        trailing: Text(
                          '${isOutgoing ? '-' : '+'}${tx['amount']} ج.س',
                          style: TextStyle(fontWeight: FontWeight.bold, color: isOutgoing ? Colors.red : Colors.green),
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
            const SizedBox(height: 10),
            const AppFooter(),
          ],
        ),
      ),
    );
  }

  Widget _buildServiceIcon(IconData icon, String label, Color color, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: color.withOpacity(0.1), shape: BoxShape.circle),
            child: Icon(icon, color: color, size: 24),
          ),
          const SizedBox(height: 4),
          Text(label, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold), textAlign: TextAlign.center, maxLines: 1),
        ],
      ),
    );
  }
}

// شاشة NFC
class NfcPaymentScreen extends StatefulWidget {
  const NfcPaymentScreen({super.key});

  @override
  State<NfcPaymentScreen> createState() => _NfcPaymentScreenState();
}

class _NfcPaymentScreenState extends State<NfcPaymentScreen> with SingleTickerProviderStateMixin {
  final TextEditingController _amountController = TextEditingController();
  final TextEditingController _noteController = TextEditingController();
  final TextEditingController _scanCodeController = TextEditingController();
  bool _isLoading = false;
  String? _generatedNfcCode;
  Map<String, dynamic>? _scannedRequest;
  late AnimationController _radarController;
  final AudioPlayer _audioPlayer = AudioPlayer();

  @override
  void initState() {
    super.initState();
    _radarController = AnimationController(vsync: this, duration: const Duration(seconds: 2))..repeat();
    _initPhysicalNfcListener();
  }

  @override
  void dispose() {
    _radarController.dispose();
    _audioPlayer.dispose();
    NfcManager.instance.stopSession();
    super.dispose();
  }

  Future<void> _playSuccessSound() async {
    try {
      await _audioPlayer.play(AssetSource('success_beep.mp3'));
    } catch (_) {}
  }

  void _initPhysicalNfcListener() async {
    bool isAvailable = await NfcManager.instance.isAvailable();
    if (!isAvailable) return;

    NfcManager.instance.startSession(
      onDiscovered: (NfcTag tag) async {
        try {
          var ndef = Ndef.from(tag);
          if (ndef != null && ndef.cachedMessage != null) {
            for (var record in ndef.cachedMessage!.records) {
              String payload = String.fromCharCodes(record.payload);
              if (payload.contains('NFC-')) {
                String code = payload.substring(payload.indexOf('NFC-'));
                _scanCodeController.text = code;
                await _fetchNfcRequest(code);
                break;
              }
            }
          }
        } catch (e) {
          print('NFC Physical Read Error: $e');
        }
      },
    );
  }

  Future<void> _createNfcRequest() async {
    final user = SmarterApp.of(context)?.loggedInUser;
    if (user == null || _amountController.text.isEmpty) return;

    setState(() => _isLoading = true);
    try {
      final response = await http.post(
        Uri.parse(apiUrl),
        body: {
          'action': 'create_nfc_request',
          'requester_id': user['id'].toString(),
          'amount': _amountController.text.trim(),
          'note': _noteController.text.trim().isEmpty ? 'طلب دفع NFC' : _noteController.text.trim(),
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          setState(() {
            _generatedNfcCode = data['request_code'];
          });
        }
      }
    } catch (e) {
      print(e);
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _fetchNfcRequest(String code) async {
    if (code.trim().isEmpty) return;
    setState(() => _isLoading = true);
    try {
      final response = await http.get(Uri.parse('$apiUrl?action=get_nfc_request&code=${code.trim()}'));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          setState(() {
            _scannedRequest = data['request'];
          });
          await _playSuccessSound();
        }
      }
    } catch (e) {
      print(e);
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _acceptAndPay() async {
    final user = SmarterApp.of(context)?.loggedInUser;
    if (user == null || _scannedRequest == null) return;

    setState(() => _isLoading = true);
    try {
      final response = await http.post(
        Uri.parse(apiUrl),
        body: {
          'action': 'accept_nfc_request',
          'payer_id': user['id'].toString(),
          'request_code': _scannedRequest!['request_code'],
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          if (data.containsKey('new_balance')) {
            user['balance'] = data['new_balance'];
            SmarterApp.of(context)?.setUser(user);
          }
          await _playSuccessSound();
          setState(() {
            _scannedRequest = null;
            _scanCodeController.clear();
          });
        }
      }
    } catch (e) {
      print(e);
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: Text(AppStrings.get(context, 'nfcTitle')),
          centerTitle: true,
          actions: [buildLanguageDropdown(context)],
          bottom: TabBar(
            tabs: [
              Tab(text: AppStrings.get(context, 'requestTab'), icon: const Icon(Icons.wifi_tethering)),
              Tab(text: AppStrings.get(context, 'scanTab'), icon: const Icon(Icons.radar)),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: ListView(
                children: [
                  Text(AppStrings.get(context, 'nfcInstruction1'), style: const TextStyle(color: Colors.grey)),
                  const SizedBox(height: 20),
                  TextField(controller: _amountController, keyboardType: TextInputType.number, decoration: InputDecoration(labelText: AppStrings.get(context, 'amount'), border: const OutlineInputBorder())),
                  const SizedBox(height: 16),
                  TextField(controller: _noteController, decoration: InputDecoration(labelText: AppStrings.get(context, 'note'), border: const OutlineInputBorder())),
                  const SizedBox(height: 24),
                  _isLoading ? const Center(child: CircularProgressIndicator()) : ElevatedButton.icon(
                    onPressed: _createNfcRequest,
                    icon: const Icon(Icons.waves, size: 24),
                    label: Text(AppStrings.get(context, 'startNfcBroadcast')),
                    style: ElevatedButton.styleFrom(minimumSize: const Size(double.infinity, 52), backgroundColor: const Color(0xFF0284c7), foregroundColor: Colors.white),
                  ),
                  if (_generatedNfcCode != null) ...[
                    const SizedBox(height: 30),
                    Center(child: RotationTransition(turns: _radarController, child: const Icon(Icons.radar, size: 80, color: Color(0xFF0284c7)))),
                    const SizedBox(height: 15),
                    Container(
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(color: Colors.blue.shade50, borderRadius: BorderRadius.circular(12), border: Border.all(color: Colors.blue.shade200)),
                      child: Column(
                        children: [
                          const Text('الموجات تبث بنجاح عبر تلامس الأجهزة', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF0284c7))),
                          const SizedBox(height: 6),
                          Text('كود البث: $_generatedNfcCode', style: const TextStyle(color: Colors.black87, fontSize: 15, fontWeight: FontWeight.bold)),
                        ],
                      ),
                    ),
                  ],
                  const SizedBox(height: 20),
                  const AppFooter(),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: ListView(
                children: [
                  Text(AppStrings.get(context, 'nfcInstruction2'), style: const TextStyle(color: Colors.grey)),
                  const SizedBox(height: 15),
                  Center(
                    child: ScaleTransition(
                      scale: Tween(begin: 0.95, end: 1.05).animate(_radarController),
                      child: Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.red.withOpacity(0.1), border: Border.all(color: Colors.red.shade300, width: 2)),
                        child: const Icon(Icons.contactless, size: 50, color: Colors.red),
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),
                  Row(
                    children: [
                      Expanded(child: TextField(controller: _scanCodeController, decoration: InputDecoration(labelText: AppStrings.get(context, 'nfcCodeLabel'), border: const OutlineInputBorder()))),
                      const SizedBox(width: 10),
                      ElevatedButton(
                        onPressed: () => _fetchNfcRequest(_scanCodeController.text),
                        style: ElevatedButton.styleFrom(minimumSize: const Size(80, 56), backgroundColor: const Color(0xFF0284c7), foregroundColor: Colors.white),
                        child: const Icon(Icons.search),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                  if (_scannedRequest != null)
                    Card(
                      elevation: 4,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      child: Padding(
                        padding: const EdgeInsets.all(20.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Row(children: [Icon(Icons.verified, color: Colors.green), SizedBox(width: 8), Text('تم التقاط الفاتورة بنجاح', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.green))]),
                            const Divider(height: 20),
                            Text('الطالب: ${_scannedRequest!['requester_name']}'),
                            const SizedBox(height: 6),
                            Text('المبلغ المستحق: ${_scannedRequest!['amount']} ج.س', style: const TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 16)),
                            const SizedBox(height: 20),
                            _isLoading ? const Center(child: CircularProgressIndicator()) : ElevatedButton.icon(
                              onPressed: _acceptAndPay,
                              icon: const Icon(Icons.check_circle),
                              label: Text(AppStrings.get(context, 'confirmAndPayNfc')),
                              style: ElevatedButton.styleFrom(minimumSize: const Size(double.infinity, 50), backgroundColor: Colors.green, foregroundColor: Colors.white),
                            ),
                          ],
                        ),
                      ),
                    ),
                  const SizedBox(height: 20),
                  const AppFooter(),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// كشف الحساب والتحويلات والملف الشخصي والإيصالات
class StatementScreen extends StatefulWidget {
  const StatementScreen({super.key});

  @override
  State<StatementScreen> createState() => _StatementScreenState();
}

class _StatementScreenState extends State<StatementScreen> {
  Map<String, List<dynamic>> _groupedTransactions = {};
  bool _isLoading = true;
  double _totalSent = 0.0;
  double _totalReceived = 0.0;

  @override
  void initState() {
    super.initState();
    _fetchStatement();
  }

  Future<void> _fetchStatement() async {
    final user = SmarterApp.of(context)?.loggedInUser;
    if (user == null) return;
    try {
      final response = await http.get(Uri.parse('$apiUrl?action=get_transactions&user_id=${user['id']}'));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          List transactions = data['transactions'];
          Map<String, List<dynamic>> grouped = {};
          for (var tx in transactions) {
            String fullDate = tx['created_at'] ?? 'أخرى';
            String dateOnly = fullDate.contains(' ') ? fullDate.split(' ')[0] : fullDate;
            grouped.putIfAbsent(dateOnly, () => []).add(tx);
          }
          setState(() {
            _groupedTransactions = grouped;
            _totalSent = double.tryParse(data['total_sent'].toString()) ?? 0.0;
            _totalReceived = double.tryParse(data['total_received'].toString()) ?? 0.0;
          });
        }
      }
    } catch (_) {} finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(AppStrings.get(context, 'statement')), centerTitle: true, actions: [buildLanguageDropdown(context)]),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: ListView(
          children: [
            Text(AppStrings.get(context, 'recentTransactions'), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const Divider(height: 20),
            _isLoading ? const Center(child: CircularProgressIndicator()) : _groupedTransactions.isEmpty ? const Center(child: Text('لا توجد حركات سابقة')) : ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _groupedTransactions.keys.length,
              itemBuilder: (context, index) {
                String dateKey = _groupedTransactions.keys.elementAt(index);
                List txs = _groupedTransactions[dateKey]!;
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(dateKey, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF0284c7))),
                    ...txs.map((tx) => ListTile(
                      title: Text(tx['receiver_name'] ?? ''),
                      trailing: Text('${tx['amount']} ج.س'),
                    )),
                  ],
                );
              },
            ),
            const AppFooter(),
          ],
        ),
      ),
    );
  }
}

class TransferScreen extends StatelessWidget {
  final bool isStandalone;
  const TransferScreen({super.key, this.isStandalone = false});
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(AppStrings.get(context, 'transfer')), centerTitle: true),
      body: const Center(child: Text('شاشة تحويل الأموال النشطة')),
    );
  }
}

class ReceiptScreen extends StatelessWidget {
  final String receiptCode;
  const ReceiptScreen({super.key, required this.receiptCode});
  @override
  Widget build(BuildContext context) {
    return Scaffold(appBar: AppBar(title: const Text('الإيصال')), body: Center(child: Text('رقم الإيصال: $receiptCode')));
  }
}

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});
  @override
  Widget build(BuildContext context) {
    return Scaffold(appBar: AppBar(title: const Text('الملف الشخصي')), body: const Center(child: Text('بيانات المستخدم')));
  }
}