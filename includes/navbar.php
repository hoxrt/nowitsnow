<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth($conn);
?>
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
                            <?php if ($auth->isLibraryAdmin()): ?>
                                <a href="admin-stats.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-cog ml-1"></i>لوحة التحكم
                                </a>
                            <?php endif; ?>
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
            <?php if ($auth->isLibraryAdmin()): ?>
                <a href="admin-stats.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">لوحة التحكم</a>
            <?php endif; ?>
            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100">تسجيل الخروج</a>
        <?php else: ?>
            <a href="login.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">تسجيل الدخول</a>
            <a href="register.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">التسجيل</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Add padding for fixed navbar -->
<div class="h-16"></div>

<!-- Mobile menu script -->
<script>
const mobileMenuButton = document.querySelector('.mobile-menu-button');
const mobileMenu = document.querySelector('.mobile-menu');
mobileMenuButton.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden');
});
</script>