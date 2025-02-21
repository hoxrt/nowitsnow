<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth($conn);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سوق القرطاسية الجامعي</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-xl font-bold">سوق القرطاسية</a>
                <div class="space-x-4">
                    <a href="index.php" class="px-3 py-2 rounded hover:bg-blue-700">الرئيسية</a>
                    <?php if ($auth->isLoggedIn()): ?>
                        <a href="messages.php" class="px-3 py-2 rounded hover:bg-blue-700">المحادثات</a>
                        <a href="profile.php" class="px-3 py-2 rounded hover:bg-blue-700">حسابي</a>
                        <a href="add-product.php" class="px-3 py-2 rounded hover:bg-blue-700">إضافة منتج</a>
                        <a href="college-prices.php" class="px-3 py-2 rounded hover:bg-blue-700">أسعار المكتبة</a>
                        <?php if ($auth->isAnyAdmin()): ?>
                            <a href="admin-stats.php" class="px-3 py-2 rounded hover:bg-blue-700">الإحصائيات</a>
                        <?php endif; ?>
                        <a href="logout.php" class="px-3 py-2 rounded hover:bg-blue-700">تسجيل خروج</a>
                    <?php else: ?>
                        <a href="login.php" class="px-3 py-2 rounded hover:bg-blue-700">تسجيل دخول</a>
                        <a href="register.php" class="px-3 py-2 rounded hover:bg-blue-700">تسجيل جديد</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Search and Categories -->
    <div class="container mx-auto mt-8 px-4">
        <div class="bg-white p-4 rounded-lg shadow">
            <form action="index.php" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <input type="text" name="search" placeholder="ابحث عن منتج..." 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                               class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <select name="category" class="w-full p-2 border rounded">
                            <option value="">كل الفئات</option>
                            <option value="notebooks" <?php echo ($_GET['category'] ?? '') === 'notebooks' ? 'selected' : ''; ?>>مذكرات</option>
                            <option value="books" <?php echo ($_GET['category'] ?? '') === 'books' ? 'selected' : ''; ?>>كتب</option>
                            <option value="stationery" <?php echo ($_GET['category'] ?? '') === 'stationery' ? 'selected' : ''; ?>>قرطاسية</option>
                            <option value="electronics" <?php echo ($_GET['category'] ?? '') === 'electronics' ? 'selected' : ''; ?>>إلكترونيات</option>
                        </select>
                    </div>
                    <div>
                        <select name="condition" class="w-full p-2 border rounded">
                            <option value="">جميع الحالات</option>
                            <option value="new" <?php echo ($_GET['condition'] ?? '') === 'new' ? 'selected' : ''; ?>>جديد</option>
                            <option value="used" <?php echo ($_GET['condition'] ?? '') === 'used' ? 'selected' : ''; ?>>مستعمل</option>
                        </select>
                    </div>
                    <div>
                        <select name="price_range" class="w-full p-2 border rounded">
                            <option value="">كل الأسعار</option>
                            <option value="0-50" <?php echo ($_GET['price_range'] ?? '') === '0-50' ? 'selected' : ''; ?>>أقل من 50 ريال</option>
                            <option value="50-100" <?php echo ($_GET['price_range'] ?? '') === '50-100' ? 'selected' : ''; ?>>50 - 100 ريال</option>
                            <option value="100-200" <?php echo ($_GET['price_range'] ?? '') === '100-200' ? 'selected' : ''; ?>>100 - 200 ريال</option>
                            <option value="200+" <?php echo ($_GET['price_range'] ?? '') === '200+' ? 'selected' : ''; ?>>أكثر من 200 ريال</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-center">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                        بحث
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="container mx-auto mt-8 px-4">
        <?php
        $query = "SELECT p.*, u.username FROM products p 
                  JOIN users u ON p.user_id = u.id 
                  WHERE p.status = 'available'";
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $conn->real_escape_string($_GET['search']);
            $query .= " AND (p.title LIKE '%$search%' OR p.description LIKE '%$search%')";
        }
        
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $category = $conn->real_escape_string($_GET['category']);
            $query .= " AND p.category = '$category'";
        }

        if (isset($_GET['condition']) && !empty($_GET['condition'])) {
            $condition = $conn->real_escape_string($_GET['condition']);
            $query .= " AND p.condition_status = '$condition'";
        }

        if (isset($_GET['price_range']) && !empty($_GET['price_range'])) {
            $price_range = $_GET['price_range'];
            switch ($price_range) {
                case '0-50':
                    $query .= " AND p.price < 50";
                    break;
                case '50-100':
                    $query .= " AND p.price BETWEEN 50 AND 100";
                    break;
                case '100-200':
                    $query .= " AND p.price BETWEEN 100 AND 200";
                    break;
                case '200+':
                    $query .= " AND p.price > 200";
                    break;
            }
        }
        
        $query .= " ORDER BY p.created_at DESC";
        $result = $conn->query($query);
        $total_products = $result->num_rows;
        ?>

        <div class="mb-4 text-gray-600">
            تم العثور على <?php echo $total_products; ?> منتج
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            while ($product = $result->fetch_assoc()):
            ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <?php if ($product['image_path']): ?>
                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                         class="w-full h-48 object-cover">
                <?php endif; ?>
                <div class="p-4">
                    <h3 class="text-lg font-semibold">
                        <?php echo htmlspecialchars($product['title']); ?>
                    </h3>
                    <p class="text-gray-600 mt-2">
                        <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                    </p>
                    <div class="mt-4 flex justify-between items-center">
                        <span class="text-blue-600 font-bold">
                            <?php echo number_format($product['price'], 2); ?> ريال
                        </span>
                        <span class="text-gray-500 text-sm">
                            <?php echo htmlspecialchars($product['username']); ?>
                        </span>
                    </div>
                    <a href="product.php?id=<?php echo $product['id']; ?>" 
                       class="mt-4 block text-center bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                        عرض التفاصيل
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <footer class="bg-gray-800 text-white mt-12 py-8">
        <div class="container mx-auto px-4 text-center">
            <p>جميع الحقوق محفوظة © سوق القرطاسية الجامعي <?php echo date('Y'); ?></p>
        </div>
    </footer>
</body>
</html>