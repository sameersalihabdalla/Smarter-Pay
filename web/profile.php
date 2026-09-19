<?php
require_once 'db.php';
require_once 'lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $branch_name = trim($_POST['branch_name']);
    $address = trim($_POST['address']);
    $bank_account_info = trim($_POST['bank_account_info']);

    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $profile_sql = "";
    $params = [$full_name, $phone, $branch_name, $address, $bank_account_info];

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $p_path = $upload_dir . 'profile_' . $user_id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $p_path)) {
            $profile_sql = ", profile_image = ?";
            $params[] = $p_path;
        }
    }

    $params[] = $user_id;
    $sql = "UPDATE users SET full_name = ?, phone = ?, branch_name = ?, address = ?, bank_account_info = ?" . $profile_sql . " WHERE id = ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $success_msg = ($lang == 'ar' ? 'تم تحديث الملف الشخصي بنجاح!' : 'Profile updated successfully!');
    } catch (PDOException $e) {
        $error_msg = "Error: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= $lang; ?>" dir="<?= $current_dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['profile']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <?php include 'full.php'; ?>
    <style>
        body { background: #f8fafc; font-family: 'Cairo', sans-serif; }
        .glass-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="py-4">
    <div class="container" style="max-width: 480px;">
        <div class="d-flex align-items-center mb-3">
            <a href="dashboard.php" class="text-dark fs-4 text-decoration-none"><i class="bi bi-arrow-right-short"></i></a>
            <h5 class="fw-bold mb-0 ms-2"><?= $t['profile']; ?></h5>
        </div>

        <div class="glass-card p-4">
            <?php if ($success_msg): ?><div class="alert alert-success small"><?= $success_msg; ?></div><?php endif; ?>
            <?php if ($error_msg): ?><div class="alert alert-danger small"><?= $error_msg; ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="text-center mb-3">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?= $user['profile_image']; ?>" class="rounded-circle border mb-2" width="70" height="70" style="object-fit:cover;">
                    <?php else: ?>
                        <div class="bg-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center fw-bold fs-4 mb-2" style="width:70px; height:70px;">
                            <?= mb_substr($user['full_name'], 0, 1); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold"><?= $lang == 'ar' ? 'الاسم الكامل' : 'Full Name'; ?></label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold"><?= $lang == 'ar' ? 'الرقم الوطني' : 'National ID'; ?></label>
                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['national_id']); ?>" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold"><?= $lang == 'ar' ? 'رقم الهاتف' : 'Phone Number'; ?></label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold"><?= $lang == 'ar' ? 'اسم الفرع' : 'Branch Name'; ?></label>
                    <input type="text" name="branch_name" class="form-control" value="<?= htmlspecialchars($user['branch_name']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold"><?= $lang == 'ar' ? 'العنوان' : 'Address'; ?></label>
                    <textarea name="address" class="form-control" rows="2" required><?= htmlspecialchars($user['address']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold"><?= $lang == 'ar' ? 'تحديث صورة الملف الشخصي' : 'Update Profile Picture'; ?></label>
                    <input type="file" name="profile_image" class="form-control" accept="image/*">
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="background:#0284c7; border:none;"><?= $lang == 'ar' ? 'حفظ التعديلات' : 'Save Changes'; ?></button>
            </form>
        </div>
    </div>
   
</body>
</html>