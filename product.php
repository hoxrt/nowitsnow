<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth($conn);
$message = '';
$error = '';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$product_id = (int)$_GET['id'];
$query = "SELECT p.*, u.username, u.email FROM products p 
          JOIN users u ON p.user_id = u.id 
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header('Location: index.php');
    exit;
}

$pageTitle = $product['title'] . ' - سوق القرطاسية الجامعي';

// Handle sending messages
if ($auth->isLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['message']) && !empty($_POST['message'])) {
        $message_text = $_POST['message'];
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, product_id, message) 
                              VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $_SESSION['user_id'], $product['user_id'], $product_id, $message_text);
        
        if ($stmt->execute()) {
            $message = 'تم إرسال الرسالة بنجاح';
        } else {
            $error = 'حدث خطأ أثناء إرسال الرسالة';
        }
    }
}

// Get messages for this product (if user is seller or buyer)
$messages = [];
if ($auth->isLoggedIn()) {
    $stmt = $conn->prepare("SELECT m.*, u.username as sender_name 
                          FROM messages m 
                          JOIN users u ON m.sender_id = u.id 
                          WHERE m.product_id = ? AND 
                                (m.sender_id = ? OR m.receiver_id = ?)
                          ORDER BY m.created_at DESC");
    $stmt->bind_param("iii", $product_id, $_SESSION['user_id'], $_SESSION['user_id']);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> - سوق القرطاسية الجامعي</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
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

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Back Button -->
        <a href="javascript:history.back()" class="inline-flex items-center text-blue-600 mb-6 hover:text-blue-800">
            <i class="fas fa-arrow-right ml-2"></i>
            العودة للصفحة السابقة
        </a>

        <!-- Product Details -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="md:flex">
                <!-- Product Image -->
                <div class="md:w-1/2">
                    <div class="relative pb-[75%]">
                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($product['title']); ?>"
                             class="absolute top-0 left-0 w-full h-full object-contain bg-gray-100">
                    </div>
                </div>

                <!-- Product Info -->
                <div class="md:w-1/2 p-6">
                    <div class="flex justify-between items-start">
                        <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($product['title']); ?></h1>
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                            <?php echo $product['condition_status'] === 'new' ? 'جديد' : 'مستعمل'; ?>
                        </span>
                    </div>

                    <div class="text-2xl font-bold text-blue-600 mb-4">
                        <?php echo number_format($product['price'], 2); ?> ريال
                    </div>

                    <div class="mb-6">
                        <h2 class="font-bold text-lg mb-2">الوصف</h2>
                        <p class="text-gray-600 whitespace-pre-line">
                            <?php echo htmlspecialchars($product['description']); ?>
                        </p>
                    </div>

                    <div class="border-t pt-4 mb-6">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-user-circle text-gray-400 text-xl ml-2"></i>
                            <span class="font-bold"><?php echo htmlspecialchars($product['username']); ?></span>
                        </div>
                        <div class="flex items-center text-gray-500 text-sm">
                            <i class="fas fa-clock ml-2"></i>
                            <span>تم النشر: <?php echo (new DateTime($product['created_at']))->format('Y/m/d'); ?></span>
                        </div>
                    </div>

                    <?php if ($auth->isLoggedIn() && $_SESSION['user_id'] !== $product['user_id']): ?>
                        <!-- Contact Seller Form -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-bold mb-4">تواصل مع البائع</h3>
                            <form action="send_message.php" method="POST" class="space-y-4">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <input type="hidden" name="receiver_id" value="<?php echo $product['user_id']; ?>">
                                
                                <textarea name="message" rows="3" required
                                    placeholder="اكتب رسالتك هنا..."
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                                
                                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-paper-plane ml-2"></i>
                                    إرسال رسالة
                                </button>
                            </form>
                        </div>
                    <?php elseif (!$auth->isLoggedIn()): ?>
                        <div class="bg-blue-50 text-blue-600 p-4 rounded-lg text-center">
                            <p class="mb-2">يجب تسجيل الدخول للتواصل مع البائع</p>
                            <a href="login.php" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                                تسجيل الدخول
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Similar Products -->
        <div class="mt-12">
            <h2 class="text-xl font-bold mb-6">منتجات مشابهة</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <?php
                $similar_query = "SELECT p.*, u.username FROM products p 
                                JOIN users u ON p.user_id = u.id 
                                WHERE p.category = ? AND p.id != ? AND p.status = 'available'
                                LIMIT 4";
                $stmt = $conn->prepare($similar_query);
                $stmt->bind_param("si", $product['category'], $product_id);
                $stmt->execute();
                $similar_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                foreach ($similar_products as $similar): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
                        <div class="relative pb-[75%]">
                            <img src="<?php echo htmlspecialchars($similar['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($similar['title']); ?>"
                                 class="absolute top-0 left-0 w-full h-full object-cover">
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold mb-2 truncate"><?php echo htmlspecialchars($similar['title']); ?></h3>
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-blue-600">
                                    <?php echo number_format($similar['price'], 2); ?> ريال
                                </span>
                                <a href="product.php?id=<?php echo $similar['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-800">
                                    عرض التفاصيل
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>