function sendNewMessageNotification($conn, $sender_id, $receiver_id, $product_id, $message) {
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, sender_id, product_id, type, message, is_read)
        VALUES (?, ?, ?, 'message', ?, 0)
    ");
    $stmt->bind_param("iiis", $receiver_id, $sender_id, $product_id, $message);
    return $stmt->execute();
}