<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth($conn);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        if ($auth->login($email, $password)) {
            // تسجيل معلومات الجلسة للتأكد من صحتها
            error_log("Login successful for email: " . $email);
            error_log("Session data: " . print_r($_SESSION, true));
            
            header('Location: index.php');
            exit;
        } else {
            error_log("Login failed for email: " . $email);
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error = 'حدث خطأ أثناء تسجيل الدخول';
    }
}

// التحقق من حالة الجلسة الحالية
if (isset($_SESSION['user_id'])) {
    error_log("Current session user_id: " . $_SESSION['user_id']);
    error_log("Current session data: " . print_r($_SESSION, true));
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - سوق القرطاسية الجامعي</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
            <h2 class="text-2xl font-bold mb-6 text-center">تسجيل الدخول</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        البريد الإلكتروني
                    </label>
                    <input type="email" name="email" id="email" required
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        كلمة المرور
                    </label>
                    <input type="password" name="password" id="password" required
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700">
                    دخول
                </button>
            </form>

            <div class="mt-4 text-center">
                <p>ليس لديك حساب؟ <a href="register.php" class="text-blue-600 hover:underline">سجل الآن</a></p>
            </div>
        </div>
    </div>
</body>
</html>