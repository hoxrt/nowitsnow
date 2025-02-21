<?php
require_once 'config/database.php';

// Check if library admin already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE role = 'library_admin' LIMIT 1");
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    die("حساب مسؤول المكتبة موجود بالفعل!");
}

// Create library admin account
$username = "library_admin";
$email = "library@college.edu"; // Replace with actual email
$password = "LibraryAdmin123!"; // Replace with secure password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'library_admin')");
$stmt->bind_param("sss", $username, $email, $hashed_password);

if ($stmt->execute()) {
    echo "تم إنشاء حساب مسؤول المكتبة بنجاح!<br>";
    echo "اسم المستخدم: " . htmlspecialchars($username) . "<br>";
    echo "كلمة المرور: " . htmlspecialchars($password) . "<br>";
    echo "يرجى تغيير كلمة المرور فور تسجيل الدخول لأول مرة.";
} else {
    echo "حدث خطأ أثناء إنشاء حساب مسؤول المكتبة: " . $conn->error;
}
?>