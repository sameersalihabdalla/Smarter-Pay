<?php
require_once 'db.php';
require_once 'lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// جلب بيانات المستخدم لمعرفة معلومات الحساب
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// جلب كافة معاملات الحساب (الصادرة والواردة إن وجدت)
// المعاملات الصادرة التي قام بها المستخدم
$transStmt = $pdo->prepare("
    SELECT t.*, r.receipt_code 
    FROM transactions t 
    LEFT JOIN transfer_receipts r ON t.id = r.transaction_id 
    WHERE t.sender_id = ? 
    ORDER BY t.created_at DESC
");
$transStmt->execute([$user_id]);
$transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);

// حساب إجمالي المبالغ المحولة (الصادرة)
$sumStmt = $pdo->prepare("SELECT SUM(amount) as total_amount, COUNT(*) as total_count FROM transactions WHERE sender_id = ?");
$sumStmt->execute([$user_id]);
$stats = $sumStmt->fetch(PDO::FETCH_ASSOC);

$total_sent = isset($stats['total_amount']) ? $stats['total_amount'] : 0;
$total_count = isset($stats['total_count']) ? $stats['total_count'] : 0;
?>
<!DOCTYPE html>
<html lang="<?= $lang; ?>" dir="<?= $current_dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['statement']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <?php include 'full.php'; ?>
    <style>
        body { background: #f8fafc; font-family: 'Cairo', sans-serif; color: #1e293b; }
        .glass-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .stat-box { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: white; border-radius: 14px; padding: 15px; }
    </style>
</head>
<body class="py-4">
    <div class="container" style="max-width: 500px;">
        
        <!-- الترويسة -->
        <div class="d-flex align-items-center mb-3">
            <a href="dashboard.php" class="text-dark fs-4 text-decoration-none"><i class="bi bi-arrow-right-short"></i></a>
            <h5 class="fw-bold mb-0 ms-2"><?= $t['statement']; ?></h5>
        </div>

        <!-- ملخص الحساب والإحصائيات -->
        <div class="stat-box mb-4 shadow-sm">
            <div class="row text-center">
                <div class="col-6 border-end border-light border-opacity-25">
                    <span class="small text-white-50 d-block mb-1"><?= $t['total_sent']; ?></span>
                    <h5 class="fw-bold mb-0"><?= number_format($total_sent, 2); ?> <small style="font-size:11px;">ج.س</small></h5>
                </div>
                <div class="col-6">
                    <span class="small text-white-50 d-block mb-1"><?= $lang == 'ar' ? 'عدد العمليات' : 'Transactions'; ?></span>
                    <h5 class="fw-bold mb-0"><?= $total_count; ?></h5>
                </div>
            </div>
        </div>

        <!-- سجل المعاملات السابقة -->
        <div class="glass-card p-3 mb-4">
            <h6 class="fw-bold mb-3 text-secondary border-bottom pb-2"><?= $t['transaction_history']; ?></h6>

            <?php if (empty($transactions)): ?>
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-receipt display-6 d-block mb-2 opacity-50"></i>
                    <?= $t['no_transactions']; ?>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($transactions as $tx): ?>
                        <div class="list-group-item px-0 py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <span class="badge bg-primary bg-opacity-10 text-primary mb-1" style="font-size:10px;"><?= $tx['transaction_ref']; ?></span>
                                    <h6 class="fw-bold mb-0 text-dark" style="font-size: 14px;"><?= htmlspecialchars($tx['receiver_name']); ?></h6>
                                </div>
                                <span class="text-danger fw-bold" style="font-size: 14px;">-<?= number_format($tx['amount'], 2); ?> ج.س</span>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="text-muted" style="font-size: 11px;"><i class="bi bi-clock"></i> <?= $tx['created_at']; ?></span>
                                <?php if (!empty($tx['receipt_code'])): ?>
                                    <a href="receipt.php?code=<?= $tx['receipt_code']; ?>" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 11px;">
                                        <i class="bi bi-qr-code"></i> <?= $t['view_receipt']; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($tx['transfer_note'])): ?>
                                <div class="mt-2 bg-light p-1.5 rounded text-muted small" style="font-size: 11px;">
                                    <strong><?= $lang == 'ar' ? 'التعليق:' : 'Note:'; ?></strong> <?= htmlspecialchars($tx['transfer_note']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center">
            <button onclick="window.print();" class="btn btn-outline-secondary btn-sm w-100 py-2">
                <i class="bi bi-printer"></i> <?= $lang == 'ar' ? 'طباعة كشف الحساب / حفظ PDF' : 'Print / Save Statement PDF'; ?>
            </button>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  
</body>
</html>