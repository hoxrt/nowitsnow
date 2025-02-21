<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth($conn);
if (!$auth->isAnyAdmin()) {
    header('Location: index.php');
    exit;
}

// إحصائيات عامة
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetch_row()[0],
    'total_products' => $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0],
    'available_products' => $conn->query("SELECT COUNT(*) FROM products WHERE status = 'available'")->fetch_row()[0],
    'sold_products' => $conn->query("SELECT COUNT(*) FROM products WHERE status = 'sold'")->fetch_row()[0],
    'total_messages' => $conn->query("SELECT COUNT(*) FROM messages")->fetch_row()[0]
];

// إحصائيات حسب الفئة
$category_stats = $conn->query("
    SELECT category, 
           COUNT(*) as total,
           SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold
    FROM products 
    GROUP BY category
");

// إحصائيات أسعار المكتبة
$library_stats = $conn->query("
    SELECT category, 
           COUNT(*) as total,
           AVG(price) as avg_price,
           SUM(CASE WHEN availability = 'available' THEN 1 ELSE 0 END) as available
    FROM college_prices 
    GROUP BY category
");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الإحصائيات - سوق القرطاسية الجامعي</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white shadow-lg mb-8">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-xl font-bold">سوق القرطاسية</a>
                <div class="space-x-4">
                    <a href="index.php" class="px-3 py-2 rounded hover:bg-blue-700">الرئيسية</a>
                    <a href="college-prices.php" class="px-3 py-2 rounded hover:bg-blue-700">أسعار المكتبة</a>
                    <a href="logout.php" class="px-3 py-2 rounded hover:bg-blue-700">تسجيل خروج</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold mb-8">لوحة الإحصائيات</h1>

        <!-- الإحصائيات العامة -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold mb-4">إحصائيات المستخدمين والمنتجات</h3>
                <div class="space-y-2">
                    <p>عدد المستخدمين: <span class="font-bold"><?php echo $stats['total_users']; ?></span></p>
                    <p>إجمالي المنتجات: <span class="font-bold"><?php echo $stats['total_products']; ?></span></p>
                    <p>المنتجات المتاحة: <span class="font-bold"><?php echo $stats['available_products']; ?></span></p>
                    <p>المنتجات المباعة: <span class="font-bold"><?php echo $stats['sold_products']; ?></span></p>
                    <p>عدد الرسائل: <span class="font-bold"><?php echo $stats['total_messages']; ?></span></p>
                </div>
            </div>

            <!-- إحصائيات الفئات -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold mb-4">إحصائيات حسب الفئة</h3>
                <div class="space-y-2">
                    <?php while ($row = $category_stats->fetch_assoc()): ?>
                        <div class="flex justify-between items-center">
                            <span><?php echo getArabicCategory($row['category']); ?></span>
                            <span>
                                المتوفر: <?php echo $row['total'] - $row['sold']; ?> |
                                المباع: <?php echo $row['sold']; ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- إحصائيات المكتبة -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold mb-4">إحصائيات المكتبة</h3>
                <div class="space-y-2">
                    <?php while ($row = $library_stats->fetch_assoc()): ?>
                        <div class="flex justify-between items-center">
                            <span><?php echo getArabicCategory($row['category']); ?></span>
                            <span>
                                المتوفر: <?php echo $row['available']; ?> |
                                متوسط السعر: <?php echo number_format($row['avg_price'], 2); ?> ريال
                            </span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- الرسوم البيانية -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <canvas id="productsChart"></canvas>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <canvas id="libraryChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        function getArabicCategory(category) {
            const categories = {
                'notebooks': 'مذكرات',
                'books': 'كتب',
                'stationery': 'قرطاسية',
                'electronics': 'إلكترونيات'
            };
            return categories[category] || category;
        }

        // إعداد بيانات الرسوم البيانية
        const productsCtx = document.getElementById('productsChart').getContext('2d');
        new Chart(productsCtx, {
            type: 'pie',
            data: {
                labels: ['متاح', 'تم البيع'],
                datasets: [{
                    data: [<?php echo $stats['available_products']; ?>, <?php echo $stats['sold_products']; ?>],
                    backgroundColor: ['#60A5FA', '#34D399']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'توزيع حالة المنتجات'
                    }
                }
            }
        });

        // رسم بياني لأسعار المكتبة
        const libraryCtx = document.getElementById('libraryChart').getContext('2d');
        new Chart(libraryCtx, {
            type: 'bar',
            data: {
                labels: <?php 
                    $library_stats->data_seek(0);
                    echo json_encode(array_map(function($row) {
                        return getArabicCategory($row['category']);
                    }, $library_stats->fetch_all(MYSQLI_ASSOC)));
                ?>,
                datasets: [{
                    label: 'متوسط السعر',
                    data: <?php 
                        $library_stats->data_seek(0);
                        echo json_encode(array_map(function($row) {
                            return $row['avg_price'];
                        }, $library_stats->fetch_all(MYSQLI_ASSOC)));
                    ?>,
                    backgroundColor: '#60A5FA'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'متوسط أسعار المنتجات حسب الفئة'
                    }
                }
            }
        });
    </script>

<?php
function getArabicCategory($category) {
    $categories = [
        'notebooks' => 'مذكرات',
        'books' => 'كتب',
        'stationery' => 'قرطاسية',
        'electronics' => 'إلكترونيات'
    ];
    return $categories[$category] ?? $category;
}
?>
</body>
</html>