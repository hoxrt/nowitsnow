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

// الحصول على قائمة المحادثات الفريدة
$stmt = $conn->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id 
        END as other_user_id,
        u.username as other_username,
        p.title as last_product_title,
        MAX(m.created_at) as last_message_time
    FROM messages m
    JOIN users u ON (
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id = u.id
            ELSE m.sender_id = u.id
        END
    )
    JOIN products p ON m.product_id = p.id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY other_user_id, other_username, p.title
    ORDER BY last_message_time DESC
");

$stmt->bind_param("iiii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// الحصول على رسائل محادثة معينة
$current_chat = null;
$messages = [];
if (isset($_GET['user_id'])) {
    $other_user_id = (int)$_GET['user_id'];
    
    // التحقق من وجود المستخدم
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->bind_param("i", $other_user_id);
    $stmt->execute();
    $current_chat = $stmt->get_result()->fetch_assoc();
    
    if ($current_chat) {
        $stmt = $conn->prepare("
            SELECT m.*, u.username as sender_name, p.title as product_title 
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            JOIN products p ON m.product_id = p.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->bind_param("iiii", $_SESSION['user_id'], $other_user_id, $other_user_id, $_SESSION['user_id']);
        $stmt->execute();
        $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// إرسال رسالة جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['receiver_id']) && isset($_POST['product_id'])) {
    $message_text = trim($_POST['message']);
    $receiver_id = (int)$_POST['receiver_id'];
    $product_id = (int)$_POST['product_id'];
    
    if (!empty($message_text)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, product_id, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $_SESSION['user_id'], $receiver_id, $product_id, $message_text);
        if ($stmt->execute()) {
            header("Location: messages.php?user_id=" . $receiver_id);
            exit;
        }
    }
}

// الحصول على المنتجات المتاحة للمناقشة
if ($current_chat) {
    $stmt = $conn->prepare("
        SELECT DISTINCT p.* 
        FROM products p
        LEFT JOIN messages m ON m.product_id = p.id
        WHERE (p.user_id = ? OR p.user_id = ?) 
        AND p.status = 'available'
        ORDER BY p.created_at DESC
    ");
    $stmt->bind_param("ii", $_SESSION['user_id'], $other_user_id);
    $stmt->execute();
    $available_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المحادثات - سوق القرطاسية الجامعي</title>
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
                    <a href="profile.php" class="px-3 py-2 rounded hover:bg-blue-700">حسابي</a>
                    <a href="logout.php" class="px-3 py-2 rounded hover:bg-blue-700">تسجيل خروج</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4">
        <div class="flex gap-6">
            <!-- قائمة المحادثات -->
            <div class="w-1/4 bg-white rounded-lg shadow-md p-4">
                <h2 class="text-xl font-bold mb-4">المحادثات</h2>
                <div class="space-y-2">
                    <?php foreach ($conversations as $conv): ?>
                        <a href="?user_id=<?php echo $conv['other_user_id']; ?>" 
                           class="block p-3 rounded hover:bg-gray-100 <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $conv['other_user_id']) ? 'bg-blue-50' : ''; ?>">
                            <div class="font-bold"><?php echo htmlspecialchars($conv['other_username']); ?></div>
                            <div class="text-sm text-gray-600">
                                <?php echo htmlspecialchars($conv['last_product_title']); ?>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?php echo date('Y-m-d H:i', strtotime($conv['last_message_time'])); ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- منطقة المحادثة -->
            <div class="flex-1 bg-white rounded-lg shadow-md p-4">
                <?php if ($current_chat): ?>
                    <div class="flex flex-col h-[600px]">
                        <div class="border-b pb-4 mb-4">
                            <h2 class="text-xl font-bold">
                                محادثة مع <?php echo htmlspecialchars($current_chat['username']); ?>
                            </h2>
                        </div>

                        <!-- الرسائل -->
                        <div class="flex-1 overflow-y-auto mb-4 space-y-4">
                            <?php foreach ($messages as $msg): ?>
                                <div class="flex <?php echo $msg['sender_id'] == $_SESSION['user_id'] ? 'justify-end' : 'justify-start'; ?>">
                                    <div class="max-w-[70%] <?php echo $msg['sender_id'] == $_SESSION['user_id'] ? 'bg-blue-100' : 'bg-gray-100'; ?> rounded-lg p-3">
                                        <div class="text-sm text-gray-600 mb-1">
                                            بخصوص: <?php echo htmlspecialchars($msg['product_title']); ?>
                                        </div>
                                        <div class="text-gray-900">
                                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- نموذج إرسال رسالة -->
                        <form method="POST" class="mt-4">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">المنتج</label>
                                    <select name="product_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <?php foreach ($available_products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>">
                                                <?php echo htmlspecialchars($product['title']); ?>
                                                (<?php echo number_format($product['price'], 2); ?> ريال)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="flex gap-4">
                                    <input type="hidden" name="receiver_id" value="<?php echo $current_chat['id']; ?>">
                                    <textarea name="message" rows="2" required
                                              class="flex-1 rounded-md border-gray-300 shadow-sm"
                                              placeholder="اكتب رسالتك هنا..."></textarea>
                                    <button type="submit" 
                                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                        إرسال
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="text-center text-gray-500 py-8">
                        اختر محادثة من القائمة أو ابدأ محادثة جديدة من صفحة المنتج
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // تمرير إلى آخر رسالة عند فتح المحادثة
    const messagesContainer = document.querySelector('.overflow-y-auto');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    </script>
</body>
</html>