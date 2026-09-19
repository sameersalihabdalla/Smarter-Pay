<?php
require_once 'db.php';
require_once 'lang.php';

$receipt_code = isset($_GET['code']) ? $_GET['code'] : '';

$stmt = $pdo->prepare("
    SELECT t.*, r.receipt_code, r.secure_hash, u.full_name as sender_name, u.branch_name 
    FROM transfer_receipts r 
    JOIN transactions t ON r.transaction_id = t.id 
    JOIN users u ON t.sender_id = u.id 
    WHERE r.receipt_code = ?
");
$stmt->execute([$receipt_code]);
$receipt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$receipt) {
    die("<div style='text-align:center; margin-top:50px; font-family:Cairo;'>إيصال التحويل غير موجود أو غير صالح.</div>");
}

// رابط التحقق داخل الـ QR Code
$verify_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php?code=" . $receipt['receipt_code'];
$qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verify_url);
?>
<!DOCTYPE html>
<html lang="<?= $lang; ?>" dir="<?= $current_dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إيصال تحويل مالي معتمد</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <?php include 'full.php'; ?>
    <style>
        body { background: #e2e8f0; font-family: 'Cairo', sans-serif; }
        .receipt-container { max-width: 420px; background: #ffffff; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); overflow: hidden; border-top: 6px solid #10b981; }
        .receipt-header { background: #f8fafc; border-bottom: 1px dashed #cbd5e1; padding: 25px 20px; text-align: center; }
        .hash-box { font-size: 10px; background: #f1f5f9; padding: 8px; border-radius: 8px; word-break: break-all; color: #64748b; }
    </style>
</head>
<body class="py-4">
    <div class="container receipt-container p-4">
        <div class="receipt-header">
            <div class="text-success display-6 mb-2"><i class="bi bi-check-circle-fill"></i></div>
            <h5 class="fw-bold text-dark mb-1">إيصال تحويل مالي معتمد</h5>
            <span class="text-muted small">Digital Bank Secure Transfer Voucher</span>
        </div>

        <div class="py-3">
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">رقم العملية (Ref):</span>
                    <strong><?= $receipt['transaction_ref']; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">التاريخ والوقت:</span>
                    <strong><?= $receipt['created_at']; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">الراسل:</span>
                    <strong><?= $receipt['sender_name']; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">المستلم:</span>
                    <strong><?= $receipt['receiver_name']; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">الحساب المستلم:</span>
                    <strong>000<?= $receipt['receiver_account']; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0 bg-light px-2 py-2 rounded">
                    <span class="fw-bold text-dark">المبلغ المحول:</span>
                    <strong class="text-success fs-6"><?= number_format($receipt['amount'], 2); ?> ج.س</strong>
                </li>
                <?php if(!empty($receipt['transfer_note'])): ?>
                <li class="list-group-item px-0 text-muted">
                    <span>التعليق:</span> <em><?= htmlspecialchars($receipt['transfer_note']); ?></em>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- قسم الـ QR Code للتحقق الفوري -->
        <div class="text-center bg-light p-3 rounded-3 my-3">
            <img src="<?= $qr_image_url; ?>" alt="QR Code" class="img-fluid mb-2" style="width: 120px; height: 120px;">
            <div class="fw-bold text-primary small"><?= $receipt['receipt_code']; ?></div>
            <div class="text-muted" style="font-size: 10px;">امسح الكود للتأكد من صحة الإيصال عبر الإنترنت</div>
        </div>

        <div class="mb-3">
            <label class="form-label text-muted" style="font-size: 11px;">بصمة الأمان المشفرة (Hash):</label>
            <div class="hash-box"><?= $receipt['secure_hash']; ?></div>
        </div>

        <div class="d-grid gap-2">
            <button onclick="window.print();" class="btn btn-outline-dark btn-sm"><i class="bi bi-printer"></i> طباعة الإيصال / حفظ PDF</button>
            <a href="dashboard.php" class="btn btn-primary btn-sm" style="background:#0284c7; border:none;">العودة للرئيسية</a>
        </div>
    </div>
   
</body>
</html>