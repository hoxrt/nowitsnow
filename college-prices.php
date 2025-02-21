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

// التحقق من الصلاحيات: المسؤول العام أو مسؤول المكتبة
if (($auth->isAdmin() || $auth->isLibraryAdmin()) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = $_POST['item_name'] ?? '';
    $price = $_POST['price'] ?? '';
    $category = $_POST['category'] ?? '';
    $availability = $_POST['availability'] ?? 'available';
    
    // فقط مسؤول المكتبة يمكنه التعديل على المنتجات الموجودة
    if (isset($_POST['edit_item']) && !$auth->isLibraryAdmin()) {
        $error = 'عذراً، فقط مسؤول المكتبة يمكنه تعديل المنتجات';
    } else {
        if (!empty($item_name) && !empty($price) && !empty($category)) {
            if (isset($_POST['edit_item'])) {
                // تحديث منتج موجود - فقط مسؤول المكتبة
                $item_id = (int)$_POST['edit_item'];
                $stmt = $conn->prepare("UPDATE college_prices SET price = ?, availability = ? WHERE id = ?");
                $stmt->bind_param("dsi", $price, $availability, $item_id);
            } else {
                // إضافة منتج جديد - المسؤول العام أو مسؤول المكتبة
                $stmt = $conn->prepare("INSERT INTO college_prices (item_name, price, category, availability) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sdss", $item_name, $price, $category, $availability);
            }
            
            if ($stmt->execute()) {
                $message = isset($_POST['edit_item']) ? 'تم تحديث المنتج بنجاح' : 'تم إضافة المنتج بنجاح';
            } else {
                $error = isset($_POST['edit_item']) ? 'حدث خطأ أثناء تحديث المنتج' : 'حدث خطأ أثناء إضافة المنتج';
            }
        }
    }
}

// حذف منتج - فقط مسؤول المكتبة
if (isset($_POST['delete_item'])) {
    if (!$auth->isLibraryAdmin()) {
        $error = 'عذراً، فقط مسؤول المكتبة يمكنه حذف المنتجات';
    } else {
        $item_id = (int)$_POST['delete_item'];
        $stmt = $conn->prepare("DELETE FROM college_prices WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        
        if ($stmt->execute()) {
            $message = 'تم حذف المنتج بنجاح';
        } else {
            $error = 'حدث خطأ أثناء حذف المنتج';
        }
    }
}

// الحصول على جميع الأسعار مرتبة حسب الفئة
$prices = [];
$result = $conn->query("SELECT * FROM college_prices ORDER BY category, item_name");
while ($row = $result->fetch_assoc()) {
    $prices[$row['category']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أسعار مكتبة الكلية - سوق القرطاسية الجامعي</title>
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
                    <?php if ($auth->isLoggedIn()): ?>
                        <a href="profile.php" class="px-3 py-2 rounded hover:bg-blue-700">حسابي</a>
                        <a href="logout.php" class="px-3 py-2 rounded hover:bg-blue-700">تسجيل خروج</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold mb-8">أسعار مكتبة الكلية</h1>

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

        <?php if ($auth->isAdmin() || $auth->isLibraryAdmin()): ?>
            <!-- نموذج إضافة/تحديث المنتجات -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h2 class="text-xl font-bold mb-4">
                    <?php echo $auth->isLibraryAdmin() ? 'إضافة/تحديث منتج' : 'إضافة منتج جديد'; ?>
                </h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">اسم المنتج</label>
                        <input type="text" name="item_name" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
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
                        <label class="block text-sm font-medium text-gray-700">حالة التوفر</label>
                        <select name="availability" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="available">متوفر</option>
                            <option value="unavailable">غير متوفر</option>
                        </select>
                    </div>
                    <button type="submit" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <?php echo $auth->isLibraryAdmin() ? 'حفظ' : 'إضافة'; ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- عرض الأسعار -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($prices as $category => $items): ?>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-bold mb-4">
                        <?php 
                        $category_names = [
                            'notebooks' => 'مذكرات',
                            'books' => 'كتب',
                            'stationery' => 'قرطاسية',
                            'electronics' => 'إلكترونيات'
                        ];
                        echo htmlspecialchars($category_names[$category] ?? ucfirst($category)); 
                        ?>
                    </h2>
                    <div class="space-y-2">
                        <?php foreach ($items as $item): ?>
                            <div class="flex justify-between items-center border-b pb-2">
                                <div>
                                    <span class="<?php echo $item['availability'] === 'unavailable' ? 'text-gray-400' : ''; ?>">
                                        <?php echo htmlspecialchars($item['item_name']); ?>
                                        <?php if ($item['availability'] === 'unavailable'): ?>
                                            <span class="text-red-500 text-sm">(غير متوفر)</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold <?php echo $item['availability'] === 'unavailable' ? 'text-gray-400' : 'text-blue-600'; ?>">
                                        <?php echo number_format($item['price'], 2); ?> ريال
                                    </span>
                                    <?php if ($auth->isLibraryAdmin()): ?>
                                        <button onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)"
                                                class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المنتج؟');">
                                            <input type="hidden" name="delete_item" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($auth->isLibraryAdmin()): ?>
    <script>
    function editItem(item) {
        if (!item) return;
        
        const form = document.querySelector('form');
        const itemNameInput = form.querySelector('[name="item_name"]');
        const priceInput = form.querySelector('[name="price"]');
        const categorySelect = form.querySelector('[name="category"]');
        const availabilitySelect = form.querySelector('[name="availability"]');
        
        itemNameInput.value = item.item_name;
        priceInput.value = item.price;
        categorySelect.value = item.category;
        availabilitySelect.value = item.availability;
        
        // إضافة حقل مخفي لتحديد أن هذا تعديل
        let editInput = form.querySelector('[name="edit_item"]');
        if (!editInput) {
            editInput = document.createElement('input');
            editInput.type = 'hidden';
            editInput.name = 'edit_item';
            form.appendChild(editInput);
        }
        editInput.value = item.id;
        
        // تغيير نص الزر
        form.querySelector('button[type="submit"]').textContent = 'تحديث';
        
        // التمرير إلى النموذج
        form.scrollIntoView({ behavior: 'smooth' });
    }
    </script>
    <?php endif; ?>
</body>
</html>