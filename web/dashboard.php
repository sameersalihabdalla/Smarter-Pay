<?php
require_once 'db.php';
require_once 'lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// جلب آخر الحركات (تأكد من وجود عمود receiver_id في القاعدة، أو اقتصر على sender_id إن لم تضفه)
try {
    $transStmt = $pdo->prepare("
        SELECT * FROM transactions 
        WHERE sender_id = ? OR (receiver_id = ?) 
        ORDER BY id DESC LIMIT 5
    ");
    $transStmt->execute([$user_id, $user_id]);
    $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // في حال عدم وجود عمود receiver_id بعد
    $transStmt = $pdo->prepare("SELECT * FROM transactions WHERE sender_id = ? ORDER BY id DESC LIMIT 5");
    $transStmt->execute([$user_id]);
    $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);
}

$bank_title_val = isset($t['bank_title']) ? $t['bank_title'] : 'نظام البنك';
$transfer_val   = isset($t['transfer']) ? $t['transfer'] : ($lang == 'ar' ? 'تحويل' : 'Transfer');
$verify_val     = isset($t['verify']) ? $t['verify'] : ($lang == 'ar' ? 'تحقق' : 'Verify');

$ui = [
    'ar' => [
        'title' => $bank_title_val,
        'statement' => 'كشف الحساب',
        'nfc_pay' => 'دفع NFC',
        'recent_tx' => 'آخر الحركات المالية',
        'no_tx' => 'لا توجد حركات سابقة',
        'recipient' => 'مستلم',
        'sender' => 'مرسل',
        'scanning' => 'جارٍ البحث عن جهاز NFC قربك...'
    ],
    'en' => [
        'title' => $bank_title_val,
        'statement' => 'Statement',
        'nfc_pay' => 'NFC Pay',
        'recent_tx' => 'Recent Transactions',
        'no_tx' => 'No previous transactions',
        'recipient' => 'Recipient',
        'sender' => 'Sender',
        'scanning' => 'Scanning for NFC device...'
    ]
];
$text = isset($ui[$lang]) ? $ui[$lang] : $ui['ar'];
$html_lang = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
$html_dir  = htmlspecialchars($current_dir, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="<?= $html_lang; ?>" dir="<?= $html_dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($text['title'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f8fafc; font-family: 'Cairo', 'Inter', sans-serif; color: #1e293b; }
        .glass-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .btn-primary-custom { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border: none; color: white; border-radius: 10px; }
        .btn-primary-custom:hover { background: linear-gradient(135deg, #0369a1 0%, #075985 100%); color: white; }
        .avatar-circle { width: 75px; height: 75px; background: #e0f2fe; color: #0284c7; font-size: 28px; font-weight: bold; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; }
    </style>
    <?php include 'full.php'; ?>
</head>
<body>

    <nav class="navbar navbar-expand bg-white border-bottom px-3 py-2">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold text-primary fs-6"><i class="bi bi-shield-lock-fill"></i> S-Bank</span>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        🌐 <?= strtoupper($html_lang); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="?lang=ar">العربية (Arabic)</a></li>
                        <li><a class="dropdown-item" href="?lang=en">English</a></li>
                    </ul>
                </div>
                <a href="logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-power"></i></a>
            </div>
        </div>
    </nav>

    <div class="container py-4" style="max-width: 480px;">
        
        <div class="glass-card p-4 text-center mb-4">
            <?php if (!empty($user['profile_image'])): ?>
                <img src="<?= htmlspecialchars($user['profile_image'], ENT_QUOTES, 'UTF-8'); ?>" class="rounded-circle mb-3 border shadow-sm" width="80" height="80" style="object-fit: cover;">
            <?php else: ?>
                <div class="avatar-circle"><?= mb_substr($user['full_name'], 0, 1); ?></div>
            <?php endif; ?>
            <h5 class="fw-bold mb-1"><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></h5>
            <p class="text-muted small mb-2"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
            <span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill"><?= htmlspecialchars($user['branch_name'], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6">
                <a href="transfer.php" class="btn btn-primary-custom w-100 py-3 shadow-sm text-decoration-none">
                    <i class="bi bi-arrow-left-right d-block fs-3 mb-1"></i> <?= htmlspecialchars($transfer_val, ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
            <div class="col-6">
                <a href="verify.php" class="btn btn-outline-dark w-100 py-3 shadow-sm text-decoration-none bg-white">
                    <i class="bi bi-qr-code-scan d-block fs-3 mb-1 text-primary"></i> <?= htmlspecialchars($verify_val, ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </div>

        <div class="row g-2 mb-4">
            <div class="col-6">
                <a href="transactions.php" class="btn w-100 py-3 shadow-sm text-decoration-none text-white bg-success rounded-3">
                    <i class="bi bi-file-earmark-text d-block fs-4 mb-1"></i> <span style="font-size: 11px;"><?= htmlspecialchars($text['statement'], ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            </div>
            <div class="col-6">
                <a href="nfc_pay.php" class="btn w-100 py-3 shadow-sm text-decoration-none text-white bg-danger rounded-3" id="nfcPayBtn">
                    <i class="bi bi-broadcast d-block fs-4 mb-1"></i> <span style="font-size: 11px;"><?= htmlspecialchars($text['nfc_pay'], ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            </div>
        </div>

        <div class="glass-card p-3">
            <h6 class="fw-bold mb-3 text-secondary border-bottom pb-2"><?= htmlspecialchars($text['recent_tx'], ENT_QUOTES, 'UTF-8'); ?></h6>
            <?php if (empty($transactions)): ?>
                <p class="text-center text-muted small py-3"><?= htmlspecialchars($text['no_tx'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach($transactions as $tx): 
                        $isOutgoing = (isset($tx['sender_id']) && $tx['sender_id'] == $user_id);
                        $defaultName = $isOutgoing ? $text['recipient'] : $text['sender'];
                        $rawName = $isOutgoing 
                            ? (isset($tx['receiver_name']) ? $tx['receiver_name'] : $defaultName) 
                            : (isset($tx['sender_name']) ? $tx['sender_name'] : $defaultName);
                        $amountFormatted = number_format($tx['amount'], 2);
                    ?>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($isOutgoing): ?>
                                    <span class="text-danger fs-5"><i class="bi bi-arrow-up-circle-fill"></i></span>
                                <?php else: ?>
                                    <span class="text-success fs-5"><i class="bi bi-arrow-down-circle-fill"></i></span>
                                <?php endif; ?>
                                <div>
                                    <strong class="small d-block text-dark"><?= htmlspecialchars($rawName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($tx['created_at'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>
                            <span class="<?= $isOutgoing ? 'text-danger' : 'text-success'; ?> fw-bold small">
                                <?= ($isOutgoing ? '-' : '+') . $amountFormatted; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('nfcPayBtn').addEventListener('click', async function(e) {
            if ('NDEFReader' in window) {
                try {
                    const ndef = new NDEFReader();
                    await ndef.scan();
                    alert(<?= json_encode($text['scanning']); ?>);
                } catch (error) {}
            }
        });
    </script>
</body>
</html>