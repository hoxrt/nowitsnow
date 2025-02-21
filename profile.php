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

// Get user's products
$stmt = $conn->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's messages
$stmt = $conn->prepare("SELECT m.*, p.title as product_title, 
                              u1.username as sender_name, 
                              u2.username as receiver_name 
                       FROM messages m 
                       JOIN products p ON m.product_id = p.id 
                       JOIN users u1 ON m.sender_id = u1.id 
                       JOIN users u2 ON m.receiver_id = u2.id 
                       WHERE m.sender_id = ? OR m.receiver_id = ? 
                       ORDER BY m.created_at DESC");
$stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['delete_product'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $message = 'تم حذف المنتج بنجاح';
    } else {
        $error = 'حدث خطأ أثناء حذف المنتج';
    }
}

// Mark product as sold
if (isset($_POST['mark_sold'])) {
    $product_id = (int)$_POST['mark_sold'];
    $stmt = $conn->prepare("UPDATE products SET status = 'sold' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $message = 'تم تحديث حالة المنتج بنجاح';
    } else {
        $error = 'حدث خطأ أثناء تحديث حالة المنتج';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - سوق القرطاسية الجامعي</title>
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
                    <a href="add-product.php" class="px-3 py-2 rounded hover:bg-blue-700">إضافة منتج</a>
                    <a href="logout.php" class="px-3 py-2 rounded hover:bg-blue-700">تسجيل خروج</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4">
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Products Section -->
            <div>
                <h2 class="text-2xl font-bold mb-4">منتجاتي</h2>
                <div class="space-y-4">
                    <?php foreach ($products as $product): ?>
                        <div class="bg-white rounded-lg shadow-md p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-bold"><?php echo htmlspecialchars($product['title']); ?></h3>
                                    <p class="text-gray-600">
                                        <?php echo number_format($product['price'], 2); ?> ريال
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        الحالة: <?php echo $product['status'] === 'available' ? 'متاح' : 'تم البيع'; ?>
                                    </p>
                                </div>
                                <div class="space-x-2">
                                    <?php if ($product['status'] === 'available'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="mark_sold" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">
                                                تم البيع
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المنتج؟');">
                                        <input type="hidden" name="delete_product" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Messages Section -->
            <div>
                <h2 class="text-2xl font-bold mb-4">الرسائل</h2>
                <div class="space-y-4">
                    <?php foreach ($messages as $msg): ?>
                        <div class="bg-white rounded-lg shadow-md p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-bold">
                                        <?php echo htmlspecialchars($msg['product_title']); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        من: <?php echo htmlspecialchars($msg['sender_name']); ?>
                                        إلى: <?php echo htmlspecialchars($msg['receiver_name']); ?>
                                    </p>
                                    <p class="mt-2"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                </div>
                                <span class="text-sm text-gray-500">
                                    <?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>