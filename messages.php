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
$selectedChat = isset($_GET['with']) ? $_GET['with'] : null;

// Get all user's conversations
$conversations_query = "SELECT 
    DISTINCT IF(m.sender_id = ?, m.receiver_id, m.sender_id) as other_user_id,
    u.username as other_username,
    p.id as last_product_id,
    p.title as last_product_title,
    p.image_path as product_image,
    MAX(m.created_at) as last_message_time,
    (SELECT message FROM messages 
     WHERE (sender_id = ? AND receiver_id = other_user_id) 
     OR (sender_id = other_user_id AND receiver_id = ?)
     ORDER BY created_at DESC LIMIT 1) as last_message
FROM messages m
JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id) AND u.id != ?
JOIN products p ON m.product_id = p.id
WHERE m.sender_id = ? OR m.receiver_id = ?
GROUP BY IF(m.sender_id = ?, m.receiver_id, m.sender_id)
ORDER BY last_message_time DESC";

$stmt = $conn->prepare($conversations_query);
$stmt->bind_param("iiiiiii", $userId, $userId, $userId, $userId, $userId, $userId, $userId);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get messages for selected conversation
$messages = [];
if ($selectedChat) {
    $messages_query = "SELECT m.*, 
                             u.username as sender_name,
                             p.title as product_title,
                             p.image_path as product_image
                      FROM messages m
                      JOIN users u ON m.sender_id = u.id
                      JOIN products p ON m.product_id = p.id
                      WHERE (m.sender_id = ? AND m.receiver_id = ?)
                      OR (m.sender_id = ? AND m.receiver_id = ?)
                      ORDER BY m.created_at ASC";
    
    $stmt = $conn->prepare($messages_query);
    $stmt->bind_param("iiii", $userId, $selectedChat, $selectedChat, $userId);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المحادثات - سوق القرطاسية الجامعي</title>
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
                    <a href="profile.php" class="px-3 py-2 rounded hover:bg-blue-700">حسابي</a>
                    <a href="logout.php" class="px-3 py-2 rounded hover:bg-blue-700">تسجيل خروج</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="grid grid-cols-12">
                <!-- Conversations List -->
                <div class="col-span-12 md:col-span-4 border-l">
                    <div class="h-[600px] flex flex-col">
                        <div class="p-4 border-b bg-gray-50">
                            <h2 class="text-xl font-bold">المحادثات</h2>
                        </div>
                        <div class="flex-1 overflow-y-auto">
                            <?php if (empty($conversations)): ?>
                                <div class="text-center text-gray-500 p-8">
                                    <i class="fas fa-comments text-4xl mb-4"></i>
                                    <p>لا توجد محادثات</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($conversations as $conv): ?>
                                    <a href="?with=<?php echo $conv['other_user_id']; ?>" 
                                       class="block p-4 border-b hover:bg-gray-50 transition duration-300
                                              <?php echo $selectedChat == $conv['other_user_id'] ? 'bg-blue-50' : ''; ?>">
                                        <div class="flex gap-4">
                                            <div class="w-12 h-12 rounded-full bg-gray-200 overflow-hidden">
                                                <img src="<?php echo htmlspecialchars($conv['product_image']); ?>" 
                                                     alt="" class="w-full h-full object-cover">
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex justify-between">
                                                    <span class="font-bold">
                                                        <?php echo htmlspecialchars($conv['other_username']); ?>
                                                    </span>
                                                    <span class="text-xs text-gray-500">
                                                        <?php 
                                                        $date = new DateTime($conv['last_message_time']);
                                                        echo $date->format('Y/m/d'); 
                                                        ?>
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600 truncate mt-1">
                                                    <?php echo htmlspecialchars($conv['last_message']); ?>
                                                </p>
                                                <div class="mt-1 text-xs text-gray-500">
                                                    <i class="fas fa-tag ml-1"></i>
                                                    <?php echo htmlspecialchars($conv['last_product_title']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div class="col-span-12 md:col-span-8">
                    <div class="h-[600px] flex flex-col">
                        <?php if ($selectedChat && !empty($messages)): ?>
                            <!-- Chat Header -->
                            <div class="p-4 border-b bg-gray-50">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden">
                                        <img src="<?php echo htmlspecialchars($messages[0]['product_image']); ?>" 
                                             alt="" class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <h3 class="font-bold">
                                            <?php echo htmlspecialchars($messages[0]['sender_name']); ?>
                                        </h3>
                                        <p class="text-sm text-gray-600">
                                            <?php echo htmlspecialchars($messages[0]['product_title']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Messages -->
                            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="messages-container">
                                <?php foreach ($messages as $msg): ?>
                                    <div class="flex <?php echo $msg['sender_id'] == $userId ? 'justify-end' : 'justify-start'; ?>">
                                        <div class="max-w-[70%] <?php echo $msg['sender_id'] == $userId ? 'bg-blue-500 text-white' : 'bg-gray-100'; ?> 
                                                     rounded-lg p-3 shadow">
                                            <p><?php echo htmlspecialchars($msg['message']); ?></p>
                                            <span class="text-xs <?php echo $msg['sender_id'] == $userId ? 'text-blue-100' : 'text-gray-500'; ?> mt-1 block">
                                                <?php echo (new DateTime($msg['created_at']))->format('h:i A'); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Message Input -->
                            <div class="p-4 border-t">
                                <form action="send_message.php" method="POST" class="flex gap-2">
                                    <input type="hidden" name="receiver_id" value="<?php echo $selectedChat; ?>">
                                    <input type="hidden" name="product_id" value="<?php echo $messages[0]['product_id']; ?>">
                                    <input type="text" name="message" placeholder="اكتب رسالتك هنا..." required
                                           class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="flex-1 flex items-center justify-center text-gray-500">
                                <div class="text-center">
                                    <i class="fas fa-comments text-6xl mb-4"></i>
                                    <p>اختر محادثة للبدء</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Scroll to bottom of messages on load
    const messagesContainer = document.getElementById('messages-container');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    </script>
</body>
</html>