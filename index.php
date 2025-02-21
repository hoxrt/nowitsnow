<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth($conn);
$pageTitle = 'الرئيسية - سوق القرطاسية الجامعي';

// Get products with user information
$query = "SELECT p.*, u.username FROM products p 
          JOIN users u ON p.user_id = u.id 
          WHERE p.status = 'available' 
          ORDER BY p.created_at DESC";
$result = $conn->query($query);
$products = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'سوق القرطاسية الجامعي'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4 space-x-reverse">
                    <a href="index.php" class="flex items-center">
                        <i class="fas fa-book-open text-2xl text-blue-600 ml-2"></i>
                        <span class="font-bold text-xl">سوق القرطاسية</span>
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-4 space-x-reverse">
                    <a href="index.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md">الرئيسية</a>
                    <a href="college-prices.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md">أسعار المكتبة</a>
                    <?php if ($auth->isLoggedIn()): ?>
                        <a href="messages.php" class="relative text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md">
                            <i class="fas fa-envelope"></i>
                            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">2</span>
                        </a>
                        <a href="add-product.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-plus ml-1"></i>إضافة منتج
                        </a>
                        <div class="relative group">
                            <button class="flex items-center text-gray-700 hover:text-blue-600 px-3 py-2">
                                <i class="fas fa-user-circle text-xl ml-1"></i>
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </button>
                            <div class="absolute left-0 w-48 py-2 bg-white rounded-md shadow-xl hidden group-hover:block">
                                <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-user ml-1"></i>الملف الشخصي
                                </a>
                                <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt ml-1"></i>تسجيل الخروج
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md">تسجيل الدخول</a>
                        <a href="register.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">التسجيل</a>
                    <?php endif; ?>
                </div>
                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button class="outline-none mobile-menu-button">
                        <i class="fas fa-bars text-gray-700 text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
        <!-- Mobile menu -->
        <div class="hidden mobile-menu md:hidden">
            <a href="index.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">الرئيسية</a>
            <a href="college-prices.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">أسعار المكتبة</a>
            <?php if ($auth->isLoggedIn()): ?>
                <a href="messages.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">الرسائل</a>
                <a href="add-product.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">إضافة منتج</a>
                <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">الملف الشخصي</a>
                <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100">تسجيل الخروج</a>
            <?php else: ?>
                <a href="login.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">تسجيل الدخول</a>
                <a href="register.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">التسجيل</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="bg-blue-600 text-white py-16">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center">
                <h1 class="text-4xl font-bold mb-4">مرحباً بك في سوق القرطاسية الجامعي</h1>
                <p class="text-xl mb-8">المكان الأمثل لتبادل وشراء المستلزمات الدراسية</p>
                <?php if (!$auth->isLoggedIn()): ?>
                    <a href="register.php" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-bold hover:bg-gray-100 transition duration-300">
                        انضم إلينا الآن
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" placeholder="ابحث عن منتج..." 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div class="w-full md:w-auto">
                    <select class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">جميع الفئات</option>
                        <option value="books">كتب</option>
                        <option value="stationery">قرطاسية</option>
                        <option value="electronics">إلكترونيات</option>
                    </select>
                </div>
                <div class="w-full md:w-auto">
                    <select class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">الحالة</option>
                        <option value="new">جديد</option>
                        <option value="used">مستعمل</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-search ml-2"></i>بحث
                </button>
            </form>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($products as $product): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
                <div class="relative pb-[75%]">
                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                         class="absolute top-0 left-0 w-full h-full object-cover">
                </div>
                <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="font-bold text-lg truncate">
                            <?php echo htmlspecialchars($product['title']); ?>
                        </h3>
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                            <?php echo $product['condition_status'] === 'new' ? 'جديد' : 'مستعمل'; ?>
                        </span>
                    </div>
                    <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                        <?php echo htmlspecialchars($product['description']); ?>
                    </p>
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-lg text-blue-600">
                            <?php echo number_format($product['price'], 2); ?> ريال
                        </span>
                        <a href="product.php?id=<?php echo $product['id']; ?>" 
                           class="text-blue-600 hover:text-blue-800">
                            عرض التفاصيل
                        </a>
                    </div>
                    <div class="mt-3 pt-3 border-t text-sm text-gray-500">
                        <i class="fas fa-user ml-1"></i>
                        <?php echo htmlspecialchars($product['username']); ?>
                        <span class="mx-2">•</span>
                        <i class="fas fa-clock ml-1"></i>
                        <?php 
                        $date = new DateTime($product['created_at']);
                        echo $date->format('Y/m/d'); 
                        ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16">
        <div class="max-w-6xl mx-auto px-4 py-8">
            <div class="flex flex-wrap justify-between">
                <div class="w-full md:w-1/3 mb-8 md:mb-0">
                    <h3 class="text-xl font-bold mb-4">سوق القرطاسية الجامعي</h3>
                    <p class="text-gray-400">
                        منصة لتبادل وشراء المستلزمات الدراسية بين طلاب الجامعة
                    </p>
                </div>
                <div class="w-full md:w-1/3 mb-8 md:mb-0">
                    <h3 class="text-xl font-bold mb-4">روابط سريعة</h3>
                    <ul class="space-y-2">
                        <li><a href="about.php" class="text-gray-400 hover:text-white">من نحن</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-white">اتصل بنا</a></li>
                        <li><a href="terms.php" class="text-gray-400 hover:text-white">الشروط والأحكام</a></li>
                    </ul>
                </div>
                <div class="w-full md:w-1/3">
                    <h3 class="text-xl font-bold mb-4">تواصل معنا</h3>
                    <div class="flex space-x-4 space-x-reverse">
                        <a href="#" class="text-gray-400 hover:text-white text-2xl">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white text-2xl">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white text-2xl">
                            <i class="fab fa-telegram"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>جميع الحقوق محفوظة © <?php echo date('Y'); ?></p>
            </div>
        </div>
    </footer>

    <!-- Add mobile menu script -->
    <script>
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mobileMenu = document.querySelector('.mobile-menu');
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>
</html>