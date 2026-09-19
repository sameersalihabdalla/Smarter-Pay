<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if (isset($_GET['account'])) {
    $account = trim($_GET['account']);

    // البحث في جدول المستخدمين عن طريق البريد الإلكتروني، الرقم الوطني، أو الـ ID
    $stmt = $pdo->prepare("SELECT full_name, branch_name FROM users WHERE email = ? OR national_id = ? OR id = ?");
    $stmt->execute([$account, $account, $account]);
    $receiver = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($receiver) {
        echo json_encode([
            'status' => 'success',
            'name' => $receiver['full_name'],
            'branch' => $receiver['branch_name']
        ]);
    } else {
        echo json_encode([
            'status' => 'not_found'
        ]);
    }
}
?>