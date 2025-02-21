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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $category = $_POST['category'] ?? '';
    $condition = $_POST['condition'] ?? '';
    
    // التحقق من وجود الصورة
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = 'يرجى اختيار صورة للمنتج';
    } else {
        $file = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $error = 'نوع الملف غير مدعوم. يجب أن تكون الصورة من نوع JPG أو PNG';
        } elseif ($file['size'] > $maxSize) {
            $error = 'حجم الصورة كبير جداً. الحد الأقصى هو 5 ميجابايت';
        } else {
            // إنشاء اسم فريد للصورة
            $imageExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $imageName = uniqid() . '.' . $imageExtension;
            $imagePath = 'uploads/' . $imageName;
            
            if (move_uploaded_file($file['tmp_name'], $imagePath)) {
                $stmt = $conn->prepare("INSERT INTO products (user_id, title, description, price, category, condition_status, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issdsis", $_SESSION['user_id'], $title, $description, $price, $category, $condition, $imagePath);
                
                if ($stmt->execute()) {
                    $success = 'تم إضافة المنتج بنجاح';
                    // إعادة التوجيه بعد ثانيتين
                    header("refresh:2;url=profile.php");
                } else {
                    $error = 'حدث خطأ أثناء إضافة المنتج';
                    // حذف الصورة في حالة فشل إضافة المنتج
                    unlink($imagePath);
                }
            } else {
                $error = 'حدث خطأ أثناء رفع الصورة';
            }
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
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

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Back Button -->
        <a href="javascript:history.back()" class="inline-flex items-center text-blue-600 mb-6 hover:text-blue-800">
            <i class="fas fa-arrow-right ml-2"></i>
            العودة للصفحة السابقة
        </a>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">إضافة منتج جديد</h1>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Image Upload -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">صورة المنتج</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg relative">
                        <div class="space-y-1 text-center" id="upload-area">
                            <i class="fas fa-image text-gray-400 text-3xl mb-3"></i>
                            <div class="flex text-sm text-gray-600">
                                <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                    <span>اختر صورة</span>
                                    <input type="file" name="image" class="sr-only" id="image-input" accept="image/*" required>
                                </label>
                                <p class="pr-1">أو اسحب وأفلت</p>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG حتى 5MB</p>
                        </div>
                        <div id="image-preview" class="absolute inset-0 hidden">
                            <img src="" alt="معاينة" class="w-full h-full object-contain">
                            <button type="button" id="remove-image" class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                        عنوان المنتج
                    </label>
                    <input type="text" name="title" id="title" required
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                           placeholder="مثال: كتاب الرياضيات للمستوى الأول">
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                        وصف المنتج
                    </label>
                    <textarea name="description" id="description" rows="4" required
                              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                              placeholder="اكتب وصفاً تفصيلياً للمنتج..."></textarea>
                </div>

                <!-- Price and Category -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="price">
                            السعر (ريال)
                        </label>
                        <input type="number" name="price" id="price" required min="0" step="0.01"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="category">
                            الفئة
                        </label>
                        <select name="category" id="category" required
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="">اختر الفئة</option>
                            <option value="books">كتب</option>
                            <option value="stationery">قرطاسية</option>
                            <option value="electronics">إلكترونيات</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                </div>

                <!-- Condition -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">حالة المنتج</label>
                    <div class="flex gap-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="condition" value="new" required
                                   class="text-blue-600 focus:ring-blue-500">
                            <span class="mr-2">جديد</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="condition" value="used" required
                                   class="text-blue-600 focus:ring-blue-500">
                            <span class="mr-2">مستعمل</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700">
                    إضافة المنتج
                </button>
            </form>
        </div>
    </div>

    <script>
    const uploadArea = document.getElementById('upload-area');
    const imageInput = document.getElementById('image-input');
    const imagePreview = document.getElementById('image-preview');
    const previewImage = imagePreview.querySelector('img');
    const removeButton = document.getElementById('remove-image');

    // Drag and drop functionality
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    uploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }

    // File input change
    imageInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        if (files.length > 0) {
            const file = files[0];
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    uploadArea.classList.add('hidden');
                    imagePreview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        }
    }

    // Remove image
    removeButton.addEventListener('click', function() {
        imageInput.value = '';
        uploadArea.classList.remove('hidden');
        imagePreview.classList.add('hidden');
    });
    </script>
</body>
</html>