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
$product = null;

// التحقق من وجود معرف المنتج
if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        header('Location: profile.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $category = $_POST['category'] ?? '';
    $condition = $_POST['condition'] ?? '';
    
    // معالجة تحميل الصورة الجديدة
    $image_path = $product['image_path'] ?? '';
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
                // حذف الصورة القديمة إذا وجدت
                if (!empty($product['image_path']) && file_exists($product['image_path'])) {
                    unlink($product['image_path']);
                }
                $image_path = $target_path;
            } else {
                $error = 'فشل في رفع الصورة';
            }
        } else {
            $error = 'نوع الملف غير مسموح به';
        }
    }
    
    if (empty($error)) {
        $stmt = $conn->prepare("UPDATE products SET title = ?, description = ?, price = ?, category = ?, condition_status = ?, image_path = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssdsssii", $title, $description, $price, $category, $condition, $image_path, $product_id, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $message = 'تم تحديث المنتج بنجاح';
            header('Location: profile.php');
            exit;
        } else {
            $error = 'حدث خطأ أثناء تحديث المنتج';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المنتج - سوق القرطاسية الجامعي</title>
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
            <h1 class="text-3xl font-bold mb-8">تعديل المنتج</h1>

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
                           value="<?php echo htmlspecialchars($product['title']); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">الوصف</label>
                    <textarea name="description" rows="4" required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">السعر</label>
                    <input type="number" step="0.01" name="price" required
                           value="<?php echo htmlspecialchars($product['price']); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">الفئة</label>
                    <select name="category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="notebooks" <?php echo $product['category'] === 'notebooks' ? 'selected' : ''; ?>>مذكرات</option>
                        <option value="books" <?php echo $product['category'] === 'books' ? 'selected' : ''; ?>>كتب</option>
                        <option value="stationery" <?php echo $product['category'] === 'stationery' ? 'selected' : ''; ?>>قرطاسية</option>
                        <option value="electronics" <?php echo $product['category'] === 'electronics' ? 'selected' : ''; ?>>إلكترونيات</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">الحالة</label>
                    <select name="condition" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="new" <?php echo $product['condition_status'] === 'new' ? 'selected' : ''; ?>>جديد</option>
                        <option value="used" <?php echo $product['condition_status'] === 'used' ? 'selected' : ''; ?>>مستعمل</option>
                    </select>
                </div>

                <?php if ($product['image_path']): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">الصورة الحالية</label>
                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                             alt="صورة المنتج" 
                             class="mt-2 w-48 h-48 object-cover rounded">
                    </div>
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700">تغيير الصورة</label>
                    <input type="file" name="image" accept="image/*"
                           class="mt-1 block w-full">
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700">
                    حفظ التغييرات
                </button>
            </form>
        </div>
    </div>
</body>
</html>