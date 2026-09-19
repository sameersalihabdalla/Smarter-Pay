<?php
require_once 'db.php';
session_start();

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $national_id = trim($_POST['national_id']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $branch_name = trim($_POST['branch_name']);
    $address = trim($_POST['address']);
    $bank_account_info = trim($_POST['bank_account_info']);

    // التحقق من عدم تكرار البريد الإلكتروني أو الرقم الوطني
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR national_id = ?");
    $checkStmt->execute([$email, $national_id]);
    if ($checkStmt->rowCount() > 0) {
        $error_msg = "البريد الإلكتروني أو الرقم الوطني مستخدم مسبقاً في النظام!";
    } else {
        // تشفير كلمة المرور
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // مجلد رفع الملفات
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $profile_image_path = null;
        $id_card_path = null;

        // رفع الصورة الشخصية
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $profile_image_path = $upload_dir . 'profile_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_image_path);
        }

        // رفع صورة الهوية / الرقم الوطني
        if (isset($_FILES['id_card_image']) && $_FILES['id_card_image']['error'] == 0) {
            $ext = pathinfo($_FILES['id_card_image']['name'], PATHINFO_EXTENSION);
            $id_card_path = $upload_dir . 'idcard_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            move_uploaded_file($_FILES['id_card_image']['tmp_name'], $id_card_path);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, national_id, phone, email, password, branch_name, address, bank_account_info, profile_image, id_card_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $full_name, $national_id, $phone, $email, $hashed_password, 
                $branch_name, $address, $bank_account_info, $profile_image_path, $id_card_path
            ]);

            $success_msg = "تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول.";
        } catch (PDOException $e) {
            $error_msg = "حدث خطأ أثناء حفظ البيانات: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فتح حساب جديد - البنك الرقمي</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <?php include 'full.php'; ?>
    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <div class="container py-4" style="max-width: 500px;">
        <div class="text-center mb-4">
            <a href="index.php" class="text-decoration-none text-dark">
                <i class="bi bi-arrow-right fs-4"></i> العودة للرئيسية
            </a>
            <h3 class="fw-bold text-primary mt-2">فتح حساب وملف شخصي جديد</h3>
            <p class="text-muted small">أدخل بياناتك الشخصية والبنكية بدقة</p>
        </div>

        <div class="card register-card bg-white p-4">
            
            <?php if ($error_msg): ?>
                <div class="alert alert-danger"><?= $error_msg; ?></div>
            <?php endif; ?>

            <?php if ($success_msg): ?>
                <div class="alert alert-success">
                    <?= $success_msg; ?>
                    <div class="mt-2">
                        <a href="login.php" class="btn btn-sm btn-success w-100">انتقل لتسجيل الدخول</a>
                    </div>
                </div>
            <?php else: ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">الاسم الكامل</label>
                        <input type="text" name="full_name" class="form-control" required placeholder="الاسم رباعي">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">الرقم الوطني</label>
                        <input type="text" name="national_id" class="form-control" required placeholder="رقم الهوية / الرقم الوطني">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">رقم الهاتف الجوال</label>
                        <input type="text" name="phone" class="form-control" required placeholder="مثال: 0912345678">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control" required placeholder="name@example.com">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">كلمة المرور</label>
                        <input type="password" name="password" class="form-control" required placeholder="أدخل كلمة مرور قوية">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">اسم الفرع المصرفي</label>
                        <input type="text" name="branch_name" class="form-control" required placeholder="مثال: فرع الخرطوم الرئيسي">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">العنوان الخاص</label>
                        <textarea name="address" class="form-control" rows="2" required placeholder="المدينة، الحي، الشارع"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">أي بيانات بنكية أخرى (اختياري)</label>
                        <textarea name="bank_account_info" class="form-control" rows="2" placeholder="تفاصيل حسابات أخرى أو IBAN"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">صورة الملف الشخصي</label>
                        <input type="file" name="profile_image" class="form-control" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">صورة الهوية أو الرقم الوطني</label>
                        <input type="file" name="id_card_image" class="form-control" accept="image/*" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">إنشاء الحساب الآن</button>
                </form>

            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 
</body>
</html>