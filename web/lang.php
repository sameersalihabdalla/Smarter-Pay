<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// تحديد اللغة الافتراضية (عربي)
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'ar';
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}

$translations = [
    'ar' => [
        'dir' => 'rtl',
        'bank_title' => 'smarter Pay',
        'welcome' => 'مرحباً بك في نظام Smarter Pay المصرفي',
        'login' => 'تسجيل الدخول',
        'register' => 'فتح حساب جديد',
        'transfer' => 'تحويل أموال سريع',
        'verify' => 'التحقق من الإشعار',
        'profile' => 'الملف الشخصي',
        'logout' => 'خروج',
        'receiver_name' => 'اسم المستلم',
        'receiver_account' => 'رقم الحساب أو الايبان (IBAN)',
        'amount' => 'المبلغ المراد تحويله',
        'note' => 'تعليق للمستلم',
        'submit_transfer' => 'إتمام التحويل وتوليد الإيصال مع QR',
        'account_not_found' => 'خطأ: رقم حساب المستلم غير موجود في قاعدة بيانات Smarter Pay!',
        'success_transfer' => 'تمت عملية التحويل وتوليد الإيصال المعتمد بنجاح',
        'statement' => 'كشف الحساب والسجل المالي',
        'total_sent' => 'إجمالي الصادر',
        'total_received' => 'إجمالي الوارد',
        'transaction_history' => 'سجل المعاملات السابقة',
        'view_receipt' => 'عرض الإيصال / QR',
        'no_transactions' => 'لا توجد معاملات مالية مسجلة حتى الآن'
    ],
    'en' => [
        'dir' => 'ltr',
        'bank_title' => 'Smarter Pay ',
        'welcome' => 'Welcome to Smarter Pay Banking System',
        'login' => 'Login',
        'register' => 'Open Account',
        'transfer' => 'Fast Money Transfer',
        'verify' => 'Verify Receipt',
        'profile' => 'Profile',
        'logout' => 'Logout',
        'receiver_name' => 'Receiver Name',
        'receiver_account' => 'Receiver Account or IBAN',
        'amount' => 'Transfer Amount',
        'note' => 'Note for Receiver',
        'submit_transfer' => 'Complete Transfer & Generate QR Voucher',
        'account_not_found' => 'Error: Receiver account does not exist in Smarter Pay database!',
        'success_transfer' => 'Transfer completed successfully with verified QR receipt',
        'statement' => 'Account Statement & History',
        'total_sent' => 'Total Sent',
        'total_received' => 'Total Received',
        'transaction_history' => 'Previous Transactions',
        'view_receipt' => 'View Receipt / QR',
        'no_transactions' => 'No financial transactions recorded yet'
    ]
];

$lang = $_SESSION['lang'];
$t = $translations[$lang];
$current_dir = $t['dir'];
?>