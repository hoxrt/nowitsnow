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

// جلب كل المنتجات المتوفرة من المكتبة
$stmt = $conn->prepare("SELECT * FROM college_prices WHERE availability = 'available' ORDER BY category, item_name");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// تنظيم المنتجات حسب الفئات
$categories = [];
foreach ($products as $product) {
    $categories[$product['category']][] = $product;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أسعار المكتبة - سوق القرطاسية الجامعي</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Hero Section -->
        <div class="bg-blue-600 rounded-lg text-white p-8 mb-8">
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-3xl font-bold mb-4">أسعار المكتبة الجامعية</h1>
                <p class="text-lg mb-6">تعرف على أسعار الكتب والقرطاسية في المكتبة الجامعية</p>
                <div class="flex justify-center gap-4">
                    <div class="bg-white/10 px-6 py-3 rounded-lg">
                        <span class="block text-2xl font-bold"><?php echo count($products); ?></span>
                        <span class="text-sm">منتج متوفر</span>
                    </div>
                    <div class="bg-white/10 px-6 py-3 rounded-lg">
                        <span class="block text-2xl font-bold"><?php echo count($categories); ?></span>
                        <span class="text-sm">فئة مختلفة</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" id="searchInput" placeholder="ابحث عن منتج..." 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <select id="categoryFilter" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">جميع الفئات</option>
                        <option value="books">كتب</option>
                        <option value="stationery">قرطاسية</option>
                        <option value="electronics">إلكترونيات</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Products by Category -->
        <?php foreach ($categories as $category => $categoryProducts): ?>
        <div class="category-section mb-8" data-category="<?php echo htmlspecialchars($category); ?>">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas <?php 
                    echo match($category) {
                        'books' => 'fa-book',
                        'stationery' => 'fa-pencil',
                        'electronics' => 'fa-laptop',
                        default => 'fa-box'
                    }; 
                ?> text-blue-600 ml-2"></i>
                <?php 
                    echo match($category) {
                        'books' => 'الكتب',
                        'stationery' => 'القرطاسية',
                        'electronics' => 'الإلكترونيات',
                        default => 'منتجات أخرى'
                    }; 
                ?>
                <span class="text-gray-500 text-base mr-2">(<?php echo count($categoryProducts); ?> منتج)</span>
            </h2>

            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 text-right">
                                <th class="px-6 py-3 text-gray-600">المنتج</th>
                                <th class="px-6 py-3 text-gray-600">السعر</th>
                                <th class="px-6 py-3 text-gray-600">آخر تحديث</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryProducts as $product): ?>
                            <tr class="border-t hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4">
                                    <div class="font-medium"><?php echo htmlspecialchars($product['item_name']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-blue-600">
                                        <?php echo number_format($product['price'], 2); ?> ريال
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    <?php echo (new DateTime($product['updated_at']))->format('Y/m/d'); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Compare Prices Info -->
        <div class="bg-gray-100 rounded-lg p-6 mt-8">
            <div class="flex items-start gap-6">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-600 text-4xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-2">مقارنة الأسعار</h3>
                    <p class="text-gray-600">
                        يمكنك مقارنة أسعار المكتبة مع المنتجات المعروضة في السوق للحصول على أفضل قيمة.
                        تأكد من مراجعة الأسعار بشكل دوري حيث يتم تحديثها من قبل إدارة المكتبة.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const categorySections = document.querySelectorAll('.category-section');

    function filterProducts() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value;

        categorySections.forEach(section => {
            const sectionCategory = section.dataset.category;
            const rows = section.querySelectorAll('tbody tr');
            let visibleRows = 0;

            // إذا تم اختيار فئة معينة، أخفِ الأقسام الأخرى
            if (selectedCategory && selectedCategory !== sectionCategory) {
                section.classList.add('hidden');
                return;
            }
            section.classList.remove('hidden');

            // تصفية المنتجات حسب البحث
            rows.forEach(row => {
                const productName = row.querySelector('td').textContent.toLowerCase();
                if (productName.includes(searchTerm)) {
                    row.classList.remove('hidden');
                    visibleRows++;
                } else {
                    row.classList.add('hidden');
                }
            });

            // إخفاء القسم بالكامل إذا لم تكن هناك نتائج
            if (visibleRows === 0) {
                section.classList.add('hidden');
            }
        });
    }

    searchInput.addEventListener('input', filterProducts);
    categoryFilter.addEventListener('change', filterProducts);
    </script>
</body>
</html>