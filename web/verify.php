<?php
require_once 'db.php';
require_once 'lang.php';

$result_data = null;
$error = null;

if (isset($_GET['code']) && !empty(trim($_GET['code']))) {
    $search_code = trim($_GET['code']);
    
    $stmt = $pdo->prepare("
        SELECT t.*, r.receipt_code, r.secure_hash, u.full_name as sender_name 
        FROM transfer_receipts r 
        JOIN transactions t ON r.transaction_id = t.id 
        JOIN users u ON t.sender_id = u.id 
        WHERE r.receipt_code = ?
    ");
    $stmt->execute([$search_code]);
    $result_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result_data) {
        $error = ($lang == 'ar' ? 'عذراً، رقم الإشعار غير موجود أو غير صالح!' : 'Sorry, the receipt code is invalid or not found!');
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang; ?>" dir="<?= $current_dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['verify']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <?php include 'full.php'; ?>
    <style>
        body { background: #f8fafc; font-family: 'Cairo', sans-serif; }
        .glass-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="py-5">
    <div class="container" style="max-width: 440px;">
        <div class="text-center mb-4">
            <a href="index.php" class="text-decoration-none text-dark small"><i class="bi bi-arrow-right"></i> <?= $lang == 'ar' ? 'الرئيسية' : 'Home'; ?></a>
            <h4 class="fw-bold text-primary mt-2"><?= $t['verify']; ?></h4>
            <p class="text-muted small"><?= $lang == 'ar' ? 'تحقق من صحة الفاتورة أو إشعار التحويل المالي' : 'Verify authenticity of receipt or invoice'; ?></p>
        </div>

        <div class="glass-card p-4">
            <form method="GET" class="mb-4">
                <div class="input-group">
                    <input type="text" name="code" class="form-control" placeholder="REC-XXXXXXXXXX" value="<?= htmlspecialchars(isset($_GET['code']) ? $_GET['code'] : ''); ?>" required>
                    <button class="btn btn-success" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center small"><?= $error; ?></div>
            <?php endif; ?>

            <?php if ($result_data): ?>
                <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success">
                    <h6 class="fw-bold mb-2"><i class="bi bi-shield-fill-check"></i> <?= $lang == 'ar' ? 'إشعار صحيح وموثق رسمياً' : 'Valid & Verified Receipt'; ?></h6>
                    <hr>
                    <p class="mb-1 small"><strong><?= $lang == 'ar' ? 'الراسل:' : 'Sender:'; ?></strong> <?= htmlspecialchars($result_data['sender_name']); ?></p>
                    <p class="mb-1 small"><strong><?= $lang == 'ar' ? 'المستلم:' : 'Receiver:'; ?></strong> <?= htmlspecialchars($result_data['receiver_name']); ?></p>
                    <p class="mb-1 small"><strong><?= $lang == 'ar' ? 'المبلغ:' : 'Amount:'; ?></strong> <?= number_format($result_data['amount'], 2); ?> ج.س</p>
                    <p class="mb-0 small"><strong><?= $lang == 'ar' ? 'التاريخ:' : 'Date:'; ?></strong> <?= $result_data['created_at']; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
  
</body>
</html>