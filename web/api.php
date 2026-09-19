<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
require_once 'db.php';

$action = '';
if (isset($_POST['action'])) {
    $action = $_POST['action'];
} elseif (isset($_GET['action'])) {
    $action = $_GET['action'];
}

switch ($action) {
    
    // 1. تسجيل الدخول
    case 'login':
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'الرجاء إدخال البريد وكلمة المرور']);
            exit();
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            echo json_encode([
                'status' => 'success',
                'message' => 'تم تسجيل الدخول بنجاح',
                'user' => [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'branch_name' => $user['branch_name'],
                    'national_id' => $user['national_id'],
                    'balance' => isset($user['balance']) ? $user['balance'] : '0.00',
                    'address' => isset($user['address']) ? $user['address'] : '',
                    'profile_image' => isset($user['profile_image']) ? $user['profile_image'] : ''
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة']);
        }
        break;

    // 2. جلب بيانات المستخدم المحدثة (الرصيد اللحظي)
    case 'get_user_info':
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        if ($user_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
            exit();
        }
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode([
                'status' => 'success',
                'user' => [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'branch_name' => $user['branch_name'],
                    'national_id' => $user['national_id'],
                    'balance' => isset($user['balance']) ? $user['balance'] : '0.00',
                    'address' => isset($user['address']) ? $user['address'] : '',
                    'profile_image' => isset($user['profile_image']) ? $user['profile_image'] : ''
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
        }
        break;

    // 3. البحث عن المستلم
    case 'get_receiver':
        $account = '';
        if (isset($_GET['account'])) {
            $account = trim($_GET['account']);
        } elseif (isset($_POST['account'])) {
            $account = trim($_POST['account']);
        }

        if (empty($account)) {
            echo json_encode(['status' => 'not_found', 'message' => 'رقم الحساب مطلوب']);
            exit();
        }

        $stmt = $pdo->prepare("SELECT id, full_name, branch_name, email FROM users WHERE email = ? OR national_id = ? OR id = ?");
        $stmt->execute([$account, $account, $account]);
        $receiver = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($receiver) {
            echo json_encode([
                'status' => 'success',
                'id' => $receiver['id'],
                'name' => $receiver['full_name'],
                'branch' => $receiver['branch_name'],
                'email' => $receiver['email']
            ]);
        } else {
            echo json_encode(['status' => 'not_found', 'message' => 'الحساب غير موجود']);
        }
        break;

    // 4. تنفيذ التحويل البنكي (مع خصم الرصيد الصارم وإضافته للمستلم)
    case 'transfer':
        $sender_id = isset($_POST['sender_id']) ? intval($_POST['sender_id']) : null;
        $receiver_account = isset($_POST['receiver_account']) ? trim($_POST['receiver_account']) : '';
        $receiver_name = isset($_POST['receiver_name']) ? trim($_POST['receiver_name']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $transfer_type = isset($_POST['transfer_type']) ? $_POST['transfer_type'] : 'internal';
        $transfer_note = isset($_POST['transfer_note']) ? trim($_POST['transfer_note']) : '';

        if (!$sender_id || empty($receiver_account) || $amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'بيانات الحوالة غير مكتملة أو المبلغ غير صالح']);
            exit();
        }

        try {
            $pdo->beginTransaction();

            // أ. فحص رصيد المرسل وقفله لمنع التداخل (Race Condition)
            $stmtSender = $pdo->prepare("SELECT balance, full_name, email FROM users WHERE id = ? FOR UPDATE");
            $stmtSender->execute([$sender_id]);
            $sender = $stmtSender->fetch(PDO::FETCH_ASSOC);

            if (!$sender) {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'حساب المرسل غير موجود']);
                exit();
            }

            // التأكد التام من كفاية الرصيد
            if (floatval($sender['balance']) < $amount) {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'عذراً، رصيدك المتاح غير كافٍ لإتمام هذه الحوالة']);
                exit();
            }

            // ب. التحقق من المستلم الداخلي وخصم/إضافة الأرصدة
            $receiver_user_id = null;
            if ($transfer_type == 'internal') {
                $stmtReceiver = $pdo->prepare("SELECT id, balance FROM users WHERE email = ? OR national_id = ? OR id = ? FOR UPDATE");
                $stmtReceiver->execute([$receiver_account, $receiver_account, $receiver_account]);
                $receiver = $stmtReceiver->fetch(PDO::FETCH_ASSOC);

                if (!$receiver) {
                    $pdo->rollBack();
                    echo json_encode(['status' => 'error', 'message' => 'حساب المستلم الداخلي غير موجود بالنظام']);
                    exit();
                }
                $receiver_user_id = $receiver['id'];

                if ($receiver_user_id == $sender_id) {
                    $pdo->rollBack();
                    echo json_encode(['status' => 'error', 'message' => 'لا يمكنك التحويل لنفس الحساب الشخصي']);
                    exit();
                }

                // إضافة المبلغ للمستلم (الوارد)
                $new_receiver_balance = floatval($receiver['balance']) + $amount;
                $updateReceiver = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
                $updateReceiver->execute([$new_receiver_balance, $receiver_user_id]);
            }

            // ج. خصم المبلغ من المرسل (الصادر)
            $new_sender_balance = floatval($sender['balance']) - $amount;
            $updateSender = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $updateSender->execute([$new_sender_balance, $sender_id]);

            // د. تسجيل المعاملة في جدول transactions
            $transaction_ref = 'TXN-' . strtoupper(uniqid());
            $stmtTx = $pdo->prepare("INSERT INTO transactions (transaction_ref, sender_id, receiver_name, receiver_account, amount, transfer_type, transfer_note, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed')");
            $stmtTx->execute([$transaction_ref, $sender_id, $receiver_name, $receiver_account, $amount, $transfer_type, $transfer_note]);
            $transaction_id = $pdo->lastInsertId();

            // هـ. توليد الإيصال وبصمة الأمان
            $receipt_code = 'REC-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
            $secure_hash = hash_hmac('sha256', $transaction_ref . $receiver_account . $amount, 'BankSecretKey_2026');

            $stmtReceipt = $pdo->prepare("INSERT INTO transfer_receipts (transaction_id, receipt_code, secure_hash) VALUES (?, ?, ?)");
            $stmtReceipt->execute([$transaction_id, $receipt_code, $secure_hash]);

            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'message' => 'تمت الحوالة بنجاح وخصم المبلغ من رصيدك',
                'transaction_ref' => $transaction_ref,
                'receipt_code' => $receipt_code,
                'new_balance' => $new_sender_balance
            ]);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء المعالجة: ' . $e->getMessage()]);
        }
        break;

    // 5. جلب الحركات المالية (الصادر والوارد)
    case 'get_transactions':
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        if ($user_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
            exit();
        }

        // جلب المعاملات التي قام بإرسالها المستخدم
        $stmtSent = $pdo->prepare("SELECT *, 'outgoing' as tx_direction FROM transactions WHERE sender_id = ?");
        $stmtSent->execute([$user_id]);
        $sentList = $stmtSent->fetchAll(PDO::FETCH_ASSOC);

        // جلب المعاملات الواردة للمستخدم
        $stmtUser = $pdo->prepare("SELECT email, national_id, id FROM users WHERE id = ?");
        $stmtUser->execute([$user_id]);
        $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

        $receivedList = [];
        if ($currentUser) {
            $stmtReceived = $pdo->prepare("
                SELECT t.*, 'incoming' as tx_direction 
                FROM transactions t 
                WHERE t.transfer_type = 'internal' AND t.sender_id != ? 
                AND (t.receiver_account = ? OR t.receiver_account = ? OR t.receiver_account = ?)
            ");
            $stmtReceived->execute([$user_id, $currentUser['id'], $currentUser['email'], $currentUser['national_id']]);
            $receivedList = $stmtReceived->fetchAll(PDO::FETCH_ASSOC);
        }

        // دمج الصادر والوارد وترتيبها تنازلياً حسب التاريخ
        $allTransactions = array_merge($sentList, $receivedList);
        usort($allTransactions, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // حساب إجمالي الصادر وإجمالي الوارد
        $totalSent = 0.0;
        $totalReceived = 0.0;
        foreach ($allTransactions as $tx) {
            if (isset($tx['tx_direction']) && $tx['tx_direction'] == 'outgoing') {
                $totalSent += floatval($tx['amount']);
            } else {
                $totalReceived += floatval($tx['amount']);
            }
        }

        echo json_encode([
            'status' => 'success',
            'transactions' => $allTransactions,
            'total_sent' => $totalSent,
            'total_received' => $totalReceived
        ]);
        break;

    // 6. التحقق من الإيصال
    case 'verify_receipt':
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        $stmt = $pdo->prepare("
            SELECT t.*, r.receipt_code, r.secure_hash, u.full_name as sender_name 
            FROM transfer_receipts r 
            JOIN transactions t ON r.transaction_id = t.id 
            JOIN users u ON t.sender_id = u.id 
            WHERE r.receipt_code = ?
        ");
        $stmt->execute([$code]);
        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($receipt) {
            echo json_encode(['status' => 'success', 'receipt' => $receipt]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid receipt code']);
        }
        break;

    // 7. تحديث الملف الشخصي
    case 'update_profile':
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $branch_name = isset($_POST['branch_name']) ? trim($_POST['branch_name']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';

        if ($user_id <= 0 || empty($full_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, branch_name = ?, address = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $branch_name, $address, $user_id]);
            echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    // 8. إنشاء طلب دفع عبر NFC (من قبل الطالب)
    case 'create_nfc_request':
        $requester_id = isset($_POST['requester_id']) ? intval($_POST['requester_id']) : 0;
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $note = isset($_POST['note']) ? trim($_POST['note']) : 'طلب دفع NFC';

        if ($requester_id <= 0 || $amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'بيانات الطلب غير صالحة']);
            exit();
        }

        $request_code = 'NFC-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

        $stmt = $pdo->prepare("INSERT INTO nfc_requests (requester_id, amount, note, request_code, status) VALUES (?, ?, ?, ?, 'pending')");
        if ($stmt->execute([$requester_id, $amount, $note, $request_code])) {
            echo json_encode([
                'status' => 'success',
                'request_code' => $request_code,
                'message' => 'تم إنشاء طلب الـ NFC بنجاح، بانتظار مسح الجهاز الآخر'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'فشل في إنشاء طلب الـ NFC']);
        }
        break;

    // 9. جلب تفاصيل طلب NFC بواسطة الكود
    case 'get_nfc_request':
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        if (empty($code)) {
            echo json_encode(['status' => 'error', 'message' => 'كود الطلب مطلوب']);
            exit();
        }

        $stmt = $pdo->prepare("
            SELECT r.*, u.full_name as requester_name, u.email as requester_email, u.id as requester_db_id 
            FROM nfc_requests r 
            JOIN users u ON r.requester_id = u.id 
            WHERE r.request_code = ? AND r.status = 'pending'
        ");
        $stmt->execute([$code]);
        $nfcReq = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($nfcReq) {
            echo json_encode(['status' => 'success', 'request' => $nfcReq]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'طلب الـ NFC غير موجود أو تم إتمامه مسبقاً']);
        }
        break;

    // 10. قبول ودفع طلب الـ NFC (من قبل الطرف الثالث/الدافع)
    case 'accept_nfc_request':
        $payer_id = isset($_POST['payer_id']) ? intval($_POST['payer_id']) : 0;
        $request_code = isset($_POST['request_code']) ? trim($_POST['request_code']) : '';

        if ($payer_id <= 0 || empty($request_code)) {
            echo json_encode(['status' => 'error', 'message' => 'بيانات الدافع أو كود الطلب غير صالحة']);
            exit();
        }

        try {
            $pdo->beginTransaction();

            $stmtReq = $pdo->prepare("SELECT * FROM nfc_requests WHERE request_code = ? AND status = 'pending' FOR UPDATE");
            $stmtReq->execute([$request_code]);
            $nfcReq = $stmtReq->fetch(PDO::FETCH_ASSOC);

            if (!$nfcReq) {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'الطلب غير صالح أو انتهت صلاحيته']);
                exit();
            }

            $requester_id = $nfcReq['requester_id'];
            $amount = floatval($nfcReq['amount']);
            $note = $nfcReq['note'];

            if ($payer_id == $requester_id) {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'لا يمكنك قبول الدفع لنفسك']);
                exit();
            }

            // فحص رصيد الدافع
            $stmtPayer = $pdo->prepare("SELECT balance, full_name FROM users WHERE id = ? FOR UPDATE");
            $stmtPayer->execute([$payer_id]);
            $payer = $stmtPayer->fetch(PDO::FETCH_ASSOC);

            if (!$payer || floatval($payer['balance']) < $amount) {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'عذراً، رصيدك غير كافٍ لإتمام عملية الدفع هذه']);
                exit();
            }

            // فحص طالب الدفع
            $stmtRequester = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
            $stmtRequester->execute([$requester_id]);
            $requester = $stmtRequester->fetch(PDO::FETCH_ASSOC);

            if (!$requester) {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'حساب المستلم غير موجود']);
                exit();
            }

            // خصم وإضافة الأرصدة
            $new_payer_balance = floatval($payer['balance']) - $amount;
            $updatePayer = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $updatePayer->execute([$new_payer_balance, $payer_id]);

            $new_requester_balance = floatval($requester['balance']) + $amount;
            $updateRequester = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $updateRequester->execute([$new_requester_balance, $requester_id]);

            // تحديث حالة الطلب
            $updateReqStatus = $pdo->prepare("UPDATE nfc_requests SET status = 'completed' WHERE id = ?");
            $updateReqStatus->execute([$nfcReq['id']]);

            // تسجيل المعاملة
            $transaction_ref = 'NFC-TXN-' . strtoupper(uniqid());
            $stmtTx = $pdo->prepare("INSERT INTO transactions (transaction_ref, sender_id, receiver_name, receiver_account, amount, transfer_type, transfer_note, status) VALUES (?, ?, ?, ?, ?, 'internal', ?, 'completed')");
            $stmtTx->execute([$transaction_ref, $payer_id, 'NFC Payment: Requester #' . $requester_id, $requester_id, $amount, $note]);
            $transaction_id = $pdo->lastInsertId();

            // إنشاء الإيصال
            $receipt_code = 'REC-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
            $secure_hash = hash_hmac('sha256', $transaction_ref . $requester_id . $amount, 'BankSecretKey_2026');
            $stmtReceipt = $pdo->prepare("INSERT INTO transfer_receipts (transaction_id, receipt_code, secure_hash) VALUES (?, ?, ?)");
            $stmtReceipt->execute([$transaction_id, $receipt_code, $secure_hash]);

            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'message' => 'تم قبول طلب الـ NFC ودفع المبلغ بنجاح',
                'new_balance' => $new_payer_balance,
                'receipt_code' => $receipt_code
            ]);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => 'خطأ أثناء معالجة الدفع: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'إجراء غير معروف']);
        break;
}
?>