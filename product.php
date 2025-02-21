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
$stmt = $conn->prepare("SELECT p.*, u.username, u.email FROM products p 
                       JOIN users u ON p.user_id = u.id 
                       WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: index.php');
    exit;
}

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
        <div class="max-w-4xl mx-auto">
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

            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="md:flex">
                    <?php if ($product['image_path']): ?>
                        <div class="md:flex-shrink-0">
                            <img class="h-48 w-full object-cover md:w-48" 
                                 src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['title']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="p-8">
                        <h1 class="text-2xl font-bold mb-2">
                            <?php echo htmlspecialchars($product['title']); ?>
                        </h1>
                        <p class="text-gray-600 mb-4">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </p>
                        <div class="mb-4">
                            <span class="font-bold text-lg text-blue-600">
                                <?php echo number_format($product['price'], 2); ?> ريال
                            </span>
                        </div>
                        <div class="text-sm text-gray-600">
                            <p>البائع: <?php echo htmlspecialchars($product['username']); ?></p>
                            <p>الحالة: <?php echo $product['condition_status'] === 'new' ? 'جديد' : 'مستعمل'; ?></p>
                            <p>تاريخ النشر: <?php echo date('Y-m-d', strtotime($product['created_at'])); ?></p>
                        </div>

                        <?php if ($auth->isLoggedIn() && $_SESSION['user_id'] !== $product['user_id']): ?>
                            <div class="mt-4 flex space-x-4">
                                <a href="messages.php?user_id=<?php echo $product['user_id']; ?>" 
                                   class="flex-1 bg-green-600 text-white text-center py-2 px-4 rounded-lg hover:bg-green-700">
                                    محادثة مباشرة مع البائع
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($auth->isLoggedIn() && $_SESSION['user_id'] !== $product['user_id']): ?>
                <!-- Message Form -->
                <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4">راسل البائع</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <textarea name="message" rows="4" required
                                      class="w-full rounded-md border-gray-300 shadow-sm"
                                      placeholder="اكتب رسالتك هنا..."></textarea>
                        </div>
                        <button type="submit" 
                                class="bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700">
                            إرسال
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (!empty($messages)): ?>
                <!-- Messages -->
                <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4">المحادثات</h2>
                    <div class="space-y-4">
                        <?php foreach ($messages as $msg): ?>
                            <div class="border-b pb-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="font-bold"><?php echo htmlspecialchars($msg['sender_name']); ?></span>
                                        <p class="mt-1"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                    </div>
                                    <span class="text-sm text-gray-500">
                                        <?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>