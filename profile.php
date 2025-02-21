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

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user's products
$stmt = $conn->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's messages
$stmt = $conn->prepare("SELECT m.*, u.username as other_user, p.title as product_title 
                       FROM messages m 
                       JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id) AND u.id != ?
                       JOIN products p ON m.product_id = p.id
                       WHERE m.sender_id = ? OR m.receiver_id = ?
                       ORDER BY m.created_at DESC LIMIT 5");
$stmt->bind_param("iii", $userId, $userId, $userId);
$stmt->execute();
$recent_messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - <?php echo htmlspecialchars($user['username']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-blue-600 text-white shadow-lg mb-8">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-xl font-bold">سوق القرطاسية</a>
                <div class="space-x-4">
                    <a href="index.php" class="px-3 py-2 rounded hover:bg-blue-700">الرئيسية</a>
                    <a href="add-product.php" class="px-3 py-2 rounded hover:bg-blue-700">إضافة منتج</a>
                    <a href="logout.php" class="px-3 py-2 rounded hover:bg-blue-700">تسجيل خروج</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Profile Header -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
            <div class="bg-blue-600 h-32"></div>
            <div class="px-6 py-4 relative">
                <div class="absolute -top-16 left-6">
                    <div class="w-32 h-32 bg-white rounded-full border-4 border-white shadow-lg flex items-center justify-center">
                        <i class="fas fa-user-circle text-gray-400 text-6xl"></i>
                    </div>
                </div>
                <div class="pt-16">
                    <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="mt-4 flex gap-4">
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                            <?php echo $user['role'] === 'library_admin' ? 'مسؤول المكتبة' : 'مستخدم'; ?>
                        </span>
                        <span class="text-gray-500 text-sm">
                            <i class="fas fa-calendar ml-1"></i>
                            عضو منذ <?php echo (new DateTime($user['created_at']))->format('Y/m/d'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Products Section -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold">منتجاتي</h2>
                        <a href="add-product.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            <i class="fas fa-plus ml-1"></i>إضافة منتج
                        </a>
                    </div>
                    
                    <?php if (empty($products)): ?>
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-box-open text-4xl mb-4"></i>
                            <p>لا توجد منتجات معروضة</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($products as $product): ?>
                            <div class="border rounded-lg overflow-hidden hover:shadow-md transition duration-300">
                                <div class="relative pb-[60%]">
                                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                                         class="absolute top-0 left-0 w-full h-full object-cover">
                                </div>
                                <div class="p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="font-bold truncate">
                                            <?php echo htmlspecialchars($product['title']); ?>
                                        </h3>
                                        <span class="bg-<?php echo $product['status'] === 'available' ? 'green' : 'red'; ?>-100 
                                                     text-<?php echo $product['status'] === 'available' ? 'green' : 'red'; ?>-800 
                                                     text-xs px-2 py-1 rounded">
                                            <?php echo $product['status'] === 'available' ? 'متاح' : 'تم البيع'; ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-600 text-sm mb-3">
                                        <?php echo number_format($product['price'], 2); ?> ريال
                                    </p>
                                    <div class="flex justify-end space-x-2 space-x-reverse">
                                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteProduct(<?php echo $product['id']; ?>)" 
                                                class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Recent Messages -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-4">آخر المحادثات</h2>
                    <?php if (empty($recent_messages)): ?>
                        <div class="text-center text-gray-500 py-4">
                            <i class="fas fa-comments text-4xl mb-2"></i>
                            <p>لا توجد محادثات</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_messages as $msg): ?>
                            <a href="messages.php?with=<?php echo $msg['other_user']; ?>" 
                               class="block p-4 rounded-lg hover:bg-gray-50 transition duration-300">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="font-bold"><?php echo htmlspecialchars($msg['other_user']); ?></span>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <?php echo htmlspecialchars(substr($msg['message'], 0, 50)) . '...'; ?>
                                        </p>
                                    </div>
                                    <span class="text-xs text-gray-500">
                                        <?php echo (new DateTime($msg['created_at']))->format('Y/m/d'); ?>
                                    </span>
                                </div>
                                <div class="mt-2 text-xs text-gray-500">
                                    <i class="fas fa-tag ml-1"></i>
                                    <?php echo htmlspecialchars($msg['product_title']); ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <a href="messages.php" class="block text-center text-blue-600 hover:text-blue-800 mt-4">
                            عرض كل المحادثات
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Statistics -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-4">إحصائيات</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">إجمالي المنتجات</span>
                            <span class="font-bold"><?php echo count($products); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">المنتجات المتاحة</span>
                            <span class="font-bold"><?php 
                                echo count(array_filter($products, fn($p) => $p['status'] === 'available')); 
                            ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">المنتجات المباعة</span>
                            <span class="font-bold"><?php 
                                echo count(array_filter($products, fn($p) => $p['status'] === 'sold')); 
                            ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function deleteProduct(productId) {
        if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
            fetch('delete-product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + productId
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
    </script>
</body>
</html>