<?php
require_once 'db.php';
require_once 'lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sender_id = $_SESSION['user_id'];
$error = '';
$step = 'form'; // form أو confirm

// استقبال البيانات عند الضغط على زر التحويل الأولي
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'preview') {
    $receiver_account = trim($_POST['receiver_account']);
    $amount = floatval($_POST['amount']);
    $transfer_type = $_POST['transfer_type'];
    $transfer_note = trim($_POST['transfer_note']);

    // التحقق من وجود الحساب
    $checkAccount = $pdo->prepare("SELECT id, full_name, branch_name FROM users WHERE email = ? OR national_id = ? OR id = ?");
    $checkAccount->execute([$receiver_account, $receiver_account, $receiver_account]);
    $receiverUser = $checkAccount->fetch(PDO::FETCH_ASSOC);

    $manual_name = isset($_POST['manual_receiver_name']) ? $_POST['manual_receiver_name'] : 'حساب خارجي';
    $receiver_name = $receiverUser ? $receiverUser['full_name'] . ' (' . $receiverUser['branch_name'] . ')' : $manual_name;

    if (!$receiverUser && $transfer_type == 'internal') {
        $error = ($lang == 'ar' ? 'رقم حساب المستلم غير موجود في النظام!' : 'Receiver account number does not exist!');
    } else {
        // الانتقال لخطوة التأكيد
        $step = 'confirm';
    }
}

// التأكيد النهائي وحفظ العملية في قاعدة البيانات
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'execute') {
    $receiver_account = trim($_POST['receiver_account']);
    $receiver_name = trim($_POST['receiver_name']);
    $amount = floatval($_POST['amount']);
    $transfer_type = $_POST['transfer_type'];
    $transfer_note = trim($_POST['transfer_note']);

    $transaction_ref = 'TXN-' . strtoupper(uniqid());

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO transactions (transaction_ref, sender_id, receiver_name, receiver_account, amount, transfer_type, transfer_note) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$transaction_ref, $sender_id, $receiver_name, $receiver_account, $amount, $transfer_type, $transfer_note]);
        $transaction_id = $pdo->lastInsertId();

        $receipt_code = 'REC-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        $secure_hash = hash_hmac('sha256', $transaction_ref . $receiver_account . $amount, 'BankSecretKey_2026');

        $stmtReceipt = $pdo->prepare("INSERT INTO transfer_receipts (transaction_id, receipt_code, secure_hash) VALUES (?, ?, ?)");
        $stmtReceipt->execute([$transaction_id, $receipt_code, $secure_hash]);

        $pdo->commit();

        header("Location: receipt.php?code=" . $receipt_code);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang; ?>" dir="<?= $current_dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['transfer']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <?php include 'full.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f8fafc; font-family: 'Cairo', sans-serif; }
        .glass-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="py-4">
    <div class="container" style="max-width: 480px;">
        <div class="d-flex align-items-center mb-3">
            <a href="dashboard.php" class="text-dark fs-4 text-decoration-none"><i class="bi bi-arrow-right-short"></i></a>
            <h5 class="fw-bold mb-0 ms-2"><?= $t['transfer']; ?></h5>
        </div>

        <div class="glass-card p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger small"><?= $error; ?></div>
            <?php endif; ?>

            <?php if ($step == 'form'): ?>
                <!-- نموذج إدخال بيانات التحويل -->
                <form method="POST">
                    <input type="hidden" name="action" value="preview">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= $lang == 'ar' ? 'نوع التحويل' : 'Transfer Type'; ?></label>
                        <select name="transfer_type" class="form-select">
                            <option value="internal"><?= $lang == 'ar' ? 'داخل البنك (تحقق تلقائي)' : 'Internal (Auto Verify)'; ?></option>
                            <option value="external"><?= $lang == 'ar' ? 'بنوك أخرى' : 'External Banks'; ?></option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= $lang == 'ar' ? 'رقم حساب المستلم / البريد / الرقم الوطني' : 'Receiver Account / Email / ID'; ?></label>
                        <input type="text" name="receiver_account" id="receiver_account" class="form-control" required placeholder="<?= $lang == 'ar' ? 'أدخل رقم الحساب هنا...' : 'Enter account number...'; ?>">
                        <div id="accountStatus" class="form-text small mt-1"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= $t['receiver_name']; ?></label>
                        <input type="text" name="manual_receiver_name" id="receiver_name" class="form-control bg-light fw-bold text-success" readonly required placeholder="<?= $lang == 'ar' ? 'سيظهر اسم المستلم تلقائياً...' : 'Receiver name will appear automatically...'; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= $t['amount']; ?></label>
                        <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= $t['note']; ?></label>
                        <input type="text" name="transfer_note" class="form-control" placeholder="<?= $lang == 'ar' ? 'مثال: قيمة فاتورة...' : 'e.g., Invoice payment...'; ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="background:#0284c7; border:none;"><?= $lang == 'ar' ? 'متابعة إلى شاشة التأكيد' : 'Proceed to Confirmation'; ?></button>
                </form>

            <?php elseif ($step == 'confirm'): ?>
                <!-- شاشة تأكيد التحويل المالي (مع إمكانية التراجع) -->
                <div class="text-center mb-3">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px; font-size: 28px;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <h5 class="fw-bold"><?= $lang == 'ar' ? 'تأكيد تفاصيل الحوالة' : 'Confirm Transfer Details'; ?></h5>
                    <p class="text-muted small"><?= $lang == 'ar' ? 'يرجى مراجعة البيانات بعناية قبل التأكيد النهائي' : 'Please review details carefully before final confirmation'; ?></p>
                </div>

                <ul class="list-group list-group-flush mb-4 small">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted"><?= $lang == 'ar' ? 'نوع التحويل:' : 'Type:'; ?></span>
                        <strong><?= $transfer_type == 'internal' ? ($lang == 'ar' ? 'تحويل أموال داخل البنك' : 'Internal') : ($lang == 'ar' ? 'بنوك أخرى' : 'External'); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted"><?= $lang == 'ar' ? 'حساب المستلم:' : 'Account:'; ?></span>
                        <strong>0000<?= htmlspecialchars($receiver_account); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted"><?= $lang == 'ar' ? 'اسم المستلم:' : 'Receiver Name:'; ?></span>
                        <strong class="text-success"><?= htmlspecialchars($receiver_name); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 bg-light px-2 py-2 rounded">
                        <span class="fw-bold"><?= $lang == 'ar' ? 'المبلغ الإجمالي:' : 'Total Amount:'; ?></span>
                        <strong class="text-primary fs-6"><?= number_format($amount, 2); ?> ج.س</strong>
                    </li>
                    <?php if (!empty($transfer_note)): ?>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted"><?= $lang == 'ar' ? 'التعليق:' : 'Note:'; ?></span>
                        <span><?= htmlspecialchars($transfer_note); ?></span>
                    </li>
                    <?php endif; ?>
                </ul>

                <form method="POST">
                    <input type="hidden" name="action" value="execute">
                    <input type="hidden" name="transfer_type" value="<?= htmlspecialchars($transfer_type); ?>">
                    <input type="hidden" name="receiver_account" value="<?= htmlspecialchars($receiver_account); ?>">
                    <input type="hidden" name="receiver_name" value="<?= htmlspecialchars($receiver_name); ?>">
                    <input type="hidden" name="amount" value="<?= htmlspecialchars($amount); ?>">
                    <input type="hidden" name="transfer_note" value="<?= htmlspecialchars($transfer_note); ?>">

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success py-2 fw-bold"><i class="bi bi-check-circle"></i> <?= $lang == 'ar' ? 'تأكيد وإرسال الحوالة' : 'Confirm & Send'; ?></button>
                        <a href="transfer.php" class="btn btn-outline-secondary py-2"><i class="bi bi-arrow-right"></i> <?= $lang == 'ar' ? 'تراجع وتعديل البيانات' : 'Cancel & Edit'; ?></a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- كود الـ JavaScript للبحث التلقائي لاسم المستلم -->
    <script>
        <?php if ($step == 'form'): ?>
        document.getElementById('receiver_account').addEventListener('keyup', function() {
            let accountVal = this.value.trim();
            let nameField = document.getElementById('receiver_name');
            let statusDiv = document.getElementById('accountStatus');

            if (accountVal.length > 1) {
                fetch('get_receiver.php?account=' + encodeURIComponent(accountVal))
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            nameField.value = data.name + ' (فرع: ' + data.branch + ')';
                            statusDiv.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> <?= $lang == 'ar' ? 'تم العثور على الحساب بنجاح' : 'Account found successfully'; ?></span>';
                        } else {
                            nameField.value = '';
                            statusDiv.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle-fill"></i> <?= $lang == 'ar' ? 'الحساب غير موجود' : 'Account not found'; ?></span>';
                        }
                    })
                    .catch(error => { console.error('Error:', error); });
            } else {
                nameField.value = '';
                statusDiv.innerHTML = '';
            }
        });
        <?php endif; ?>
    </script>
   
</body>
</html>