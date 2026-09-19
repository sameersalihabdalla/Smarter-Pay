<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];

        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'];

        // تسجيل الدخول في السجلات
        $logStmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent, status) VALUES (?, ?, ?, 'Success')");
        $logStmt->execute([$user['id'], $ip, $ua]);

        // تنبيه فوري أمني عند الدخول
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $notifStmt->execute([
            $user['id'], 
            ($lang == 'ar' ? 'تنبيه أمني: تسجيل دخول جديد' : 'Security Alert: New Login'), 
            ($lang == 'ar' ? 'تم تسجيل الدخول بنجاح من العنوان IP: ' : 'Successfully logged in from IP: ') . $ip
        ]);

        header("Location: dashboard.php");
        exit();
    } else {
        $error = ($lang == 'ar' ? 'البريد الإلكتروني أو كلمة المرور غير صحيحة.' : 'Invalid email or password.');
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang; ?>" dir="<?= $current_dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $t['login']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <?php include 'full.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { 
            background: #f8fafc; 
            font-family: 'Cairo', sans-serif; 
        }
        .glass-card { 
            background: #ffffff; 
            border: 1px solid #e2e8f0; 
            border-radius: 16px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); 
        }
        .app-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="d-flex flex-column align-items-center justify-content-center min-vh-100 py-4 px-3">
    <div class="container w-100" style="max-width: 420px;">
        <div class="text-center mb-3">
          
            <!-- لوجو التطبيق -->
            <div>
                <img src="logo.png" alt="sMoney Logo" class="">
            </div>
            
        </div>

        <div class="glass-card p-4 mb-3">
            <?php if ($error): ?>
                <div class="alert alert-danger small"><?= $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold"><?= $lang == 'ar' ? 'البريد الإلكتروني' : 'Email Address'; ?></label>
                    <input type="email" name="email" class="form-control py-2" required placeholder="name@bank.com">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold"><?= $lang == 'ar' ? 'كلمة المرور' : 'Password'; ?></label>
                    <input type="password" name="password" class="form-control py-2" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="background:#0284c7; border:none;"><?= $t['login']; ?></button>
            </form>
            <div class="text-center mt-3 small">
                <a href="register.php" class="text-decoration-none text-muted"><?= $lang == 'ar' ? 'ليس لديك حساب؟ افتح حساباً جديداً' : "Don't have an account? Open one"; ?></a>
            </div>
        </div>

        
    </div>
</body>
</html>