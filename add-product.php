<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $category = $_POST['category'] ?? '';
    $condition = $_POST['condition'] ?? '';
    
    // Handle file upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = $target_path;
            } else {
                $error = 'فشل في رفع الصورة';
            }
        } else {
            $error = 'نوع الملف غير مسموح به';
        }
    }
    
    if (empty($error)) {
        $stmt = $conn->prepare("INSERT INTO products (user_id, title, description, price, category, condition_status, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdsss", $_SESSION['user_id'], $title, $description, $price, $category, $condition, $image_path);
        
        if ($stmt->execute()) {
            $message = 'تم إضافة المنتج بنجاح';
            header('Location: index.php');
            exit;
        } else {
            $error = 'حدث خطأ أثناء إضافة المنتج';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة منتج جديد - سوق القرطاسية الجامعي</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg mb-8">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-xl font-bold">سوق القرطاسية</a>
                <div class="space-x-4">
                    <a href="index.php" class="px-3 py-2 rounded hover:bg-blue-700">الرئيسية</a>
                    <a href="profile.php" class="px-3 py-2 rounded hover:bg-blue-700">حسابي</a>
                    <a href="logout.php" class="px-3 py-2 rounded hover:bg-blue-700">تسجيل خروج</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-8">إضافة منتج جديد</h1>

            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">عنوان المنتج</label>
                    <input type="text" name="title" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">الوصف</label>
                    <textarea name="description" rows="4" required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">السعر</label>
                    <input type="number" step="0.01" name="price" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">الفئة</label>
                    <select name="category" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="notebooks">مذكرات</option>
                        <option value="books">كتب</option>
                        <option value="stationery">قرطاسية</option>
                        <option value="electronics">إلكترونيات</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">الحالة</label>
                    <select name="condition" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="new">جديد</option>
                        <option value="used">مستعمل</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">صورة المنتج</label>
                    <input type="file" name="image" accept="image/*"
                           class="mt-1 block w-full">
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700">
                    إضافة المنتج
                </button>
            </form>
        </div>
    </div>
</body>
</html>