<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth($conn);
if (!$auth->isLibraryAdmin()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// إحصائيات عامة
$stats = [
    'total_products' => $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0],
    'available_products' => $conn->query("SELECT COUNT(*) FROM products WHERE status = 'available'")->fetch_row()[0],
    'total_users' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetch_row()[0],
    'total_items' => $conn->query("SELECT COUNT(*) FROM college_prices")->fetch_row()[0]
];

// التحقق من الأسعار المحدثة مؤخراً
$recent_updates = $conn->query("
    SELECT item_name, price, category, updated_at 
    FROM college_prices 
    ORDER BY updated_at DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// إضافة/تحديث سعر
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $item_name = $_POST['item_name'] ?? '';
        $price = $_POST['price'] ?? '';
        $category = $_POST['category'] ?? '';
        $availability = $_POST['availability'] ?? 'available';

        if ($_POST['action'] === 'add') {
            $stmt = $conn->prepare("INSERT INTO college_prices (item_name, price, category, availability) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sdss", $item_name, $price, $category, $availability);
            if ($stmt->execute()) {
                $success = 'تمت إضافة المنتج بنجاح';
            } else {
                $error = 'حدث خطأ أثناء إضافة المنتج';
            }
        } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
            $stmt = $conn->prepare("UPDATE college_prices SET item_name = ?, price = ?, category = ?, availability = ? WHERE id = ?");
            $stmt->bind_param("sdssi", $item_name, $price, $category, $availability, $_POST['id']);
            if ($stmt->execute()) {
                $success = 'تم تحديث المنتج بنجاح';
            } else {
                $error = 'حدث خطأ أثناء تحديث المنتج';
            }
        }
    }
}

// جلب جميع المنتجات
$products = $conn->query("SELECT * FROM college_prices ORDER BY category, item_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المكتبة - سوق القرطاسية الجامعي</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 right-0 w-64 bg-blue-800 text-white">
            <div class="p-4">
                <div class="text-xl font-bold mb-8">لوحة تحكم المكتبة</div>
                <nav class="space-y-2">
                    <a href="#dashboard" class="block px-4 py-2 rounded hover:bg-blue-700 transition">
                        <i class="fas fa-chart-line ml-2"></i>لوحة المعلومات
                    </a>
                    <a href="#prices" class="block px-4 py-2 rounded hover:bg-blue-700 transition">
                        <i class="fas fa-tags ml-2"></i>إدارة الأسعار
                    </a>
                    <a href="index.php" class="block px-4 py-2 rounded hover:bg-blue-700 transition">
                        <i class="fas fa-home ml-2"></i>الرئيسية
                    </a>
                    <a href="logout.php" class="block px-4 py-2 rounded hover:bg-red-600 transition">
                        <i class="fas fa-sign-out-alt ml-2"></i>تسجيل الخروج
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="mr-64 p-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">إجمالي المنتجات</p>
                            <h3 class="text-2xl font-bold"><?php echo $stats['total_products']; ?></h3>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-box text-blue-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">المنتجات المتاحة</p>
                            <h3 class="text-2xl font-bold"><?php echo $stats['available_products']; ?></h3>
                        </div>
                        <div class="bg-green-100 p-3 rounded-lg">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">عدد المستخدمين</p>
                            <h3 class="text-2xl font-bold"><?php echo $stats['total_users']; ?></h3>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-lg">
                            <i class="fas fa-users text-purple-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">منتجات المكتبة</p>
                            <h3 class="text-2xl font-bold"><?php echo $stats['total_items']; ?></h3>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-lg">
                            <i class="fas fa-book text-yellow-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Updates -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">آخر التحديثات</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-right bg-gray-50">
                                <th class="p-3 text-gray-600">المنتج</th>
                                <th class="p-3 text-gray-600">السعر</th>
                                <th class="p-3 text-gray-600">الفئة</th>
                                <th class="p-3 text-gray-600">تاريخ التحديث</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_updates as $update): ?>
                            <tr class="border-t">
                                <td class="p-3"><?php echo htmlspecialchars($update['item_name']); ?></td>
                                <td class="p-3"><?php echo number_format($update['price'], 2); ?> ريال</td>
                                <td class="p-3"><?php echo htmlspecialchars($update['category']); ?></td>
                                <td class="p-3"><?php echo (new DateTime($update['updated_at']))->format('Y/m/d H:i'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add/Edit Product Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">إضافة/تعديل منتج</h2>
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
                <form method="POST" class="space-y-4" id="productForm">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id" id="productId">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">اسم المنتج</label>
                            <input type="text" name="item_name" required
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">السعر (ريال)</label>
                            <input type="number" name="price" required min="0" step="0.01"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">الفئة</label>
                            <select name="category" required
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="">اختر الفئة</option>
                                <option value="books">كتب</option>
                                <option value="stationery">قرطاسية</option>
                                <option value="electronics">إلكترونيات</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                            <select name="availability" required
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="available">متوفر</option>
                                <option value="unavailable">غير متوفر</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="resetForm()" 
                                class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                            إلغاء
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            حفظ
                        </button>
                    </div>
                </form>
            </div>

            <!-- Products Table -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">قائمة المنتجات</h2>
                    <div class="flex gap-2">
                        <input type="text" id="searchInput" placeholder="بحث..." 
                               class="px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                        <select id="categoryFilter" 
                                class="px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="">جميع الفئات</option>
                            <option value="books">كتب</option>
                            <option value="stationery">قرطاسية</option>
                            <option value="electronics">إلكترونيات</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full" id="productsTable">
                        <thead>
                            <tr class="text-right bg-gray-50">
                                <th class="p-3 text-gray-600">المنتج</th>
                                <th class="p-3 text-gray-600">السعر</th>
                                <th class="p-3 text-gray-600">الفئة</th>
                                <th class="p-3 text-gray-600">الحالة</th>
                                <th class="p-3 text-gray-600">آخر تحديث</th>
                                <th class="p-3 text-gray-600">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr class="border-t" data-category="<?php echo htmlspecialchars($product['category']); ?>">
                                <td class="p-3"><?php echo htmlspecialchars($product['item_name']); ?></td>
                                <td class="p-3"><?php echo number_format($product['price'], 2); ?> ريال</td>
                                <td class="p-3"><?php echo htmlspecialchars($product['category']); ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-1 rounded-full text-xs <?php echo $product['availability'] === 'available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $product['availability'] === 'available' ? 'متوفر' : 'غير متوفر'; ?>
                                    </span>
                                </td>
                                <td class="p-3"><?php echo (new DateTime($product['updated_at']))->format('Y/m/d H:i'); ?></td>
                                <td class="p-3">
                                    <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                            class="text-blue-600 hover:text-blue-800 ml-2">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteProduct(<?php echo $product['id']; ?>)"
                                            class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    // تحرير منتج
    function editProduct(product) {
        document.querySelector('[name="action"]').value = 'update';
        document.querySelector('[name="id"]').value = product.id;
        document.querySelector('[name="item_name"]').value = product.item_name;
        document.querySelector('[name="price"]').value = product.price;
        document.querySelector('[name="category"]').value = product.category;
        document.querySelector('[name="availability"]').value = product.availability;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // إعادة تعيين النموذج
    function resetForm() {
        document.querySelector('[name="action"]').value = 'add';
        document.querySelector('[name="id"]').value = '';
        document.getElementById('productForm').reset();
    }

    // حذف منتج
    function deleteProduct(id) {
        if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
            fetch('delete_college_price.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('حدث خطأ أثناء حذف المنتج');
                }
            });
        }
    }

    // البحث والتصفية
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const productsTable = document.getElementById('productsTable');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const category = categoryFilter.value;
        const rows = productsTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        for (let row of rows) {
            const name = row.cells[0].textContent.toLowerCase();
            const rowCategory = row.dataset.category;
            const matchesSearch = name.includes(searchTerm);
            const matchesCategory = !category || rowCategory === category;
            
            row.style.display = matchesSearch && matchesCategory ? '' : 'none';
        }
    }

    searchInput.addEventListener('input', filterTable);
    categoryFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>