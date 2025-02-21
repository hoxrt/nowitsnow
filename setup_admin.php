<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// تحقق من أنه لا يوجد مسؤول عادي
$stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    die("حساب المسؤول موجود بالفعل!");
}

// إنشاء حساب المسؤول العادي
$username = "admin";
$email = "admin@college.edu"; // قم بتغيير البريد الإلكتروني حسب الحاجة
$password = "AdminCollege123!"; // قم بتغيير كلمة المرور حسب الحاجة

$auth = new Auth($conn);
if ($auth->createAdmin($username, $email, $password, 'admin')) {
    echo "تم إنشاء حساب المسؤول بنجاح!<br>";
    echo "اسم المستخدم: " . htmlspecialchars($username) . "<br>";
    echo "كلمة المرور: " . htmlspecialchars($password) . "<br>";
    echo "يرجى تغيير كلمة المرور فور تسجيل الدخول لأول مرة.";
} else {
    echo "حدث خطأ أثناء إنشاء حساب المسؤول.";
}
?>