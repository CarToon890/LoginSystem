<?php

declare(strict_types=1);
require __DIR__ . '/config_mysqli.php';
require __DIR__ . '/csrf.php';

// helper: redirect + flash
function go(string $to, ?string $msg = null): never
{
    if ($msg) {
        $_SESSION['flash'] = $msg;
    }
    header("Location: {$to}");
    exit;
}

// 1) ตรวจ method + CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register2.php');
    exit;
}
if (!csrf_check($_POST['csrf'] ?? '')) {
    $_SESSION['flash'] = 'Invalid request. Please try again.';
    header('Location: register2.php');
    exit;
}
// 2) รับและทำความสะอาด input
$name      = trim((string)($_POST['name'] ?? ''));
$email     = strtolower(trim((string)($_POST['email'] ?? '')));
$password  = (string)($_POST['password'] ?? '');
$password2 = (string)($_POST['password2'] ?? '');

// 3) ตรวจความถูกต้องเบื้องต้น
if ($name === '' || $email === '' || $password === '' || $password2 === '') {
    go('register2.php', 'Please fill in all fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    go('register2.php', 'Email format is invalid.');
}
if (strlen($password) < 4) {
    go('register2.php', 'Password must be at least 4 characters.');
}
if (!hash_equals($password, $password2)) {
    go('register2.php', 'Passwords do not match.');
}

// 4) ตรวจซ้ำ email
$chk = $mysqli->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
$chk->bind_param("s", $email);
$chk->execute();
$exists = $chk->get_result()->num_rows > 0;
$chk->close();
if ($exists) {
    go('register2.php', 'This email is already registered.');
}

// 5) แฮ็ชรหัสผ่าน + บันทึก
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $mysqli->prepare("INSERT INTO users (email, display_name, password_hash) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $name, $hash);

try {
    $stmt->execute();
} catch (Throwable $e) {
    // เผื่อ unique constraint หรือ error อื่น
    go('register2.php', 'Unable to register. Please try again.');
} finally {
    $stmt->close();
}

// 6) สำเร็จ → ส่งไปหน้า login พร้อมข้อความ
go('login.php', 'Account created. Please sign in.');
