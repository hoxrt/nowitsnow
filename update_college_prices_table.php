<?php
require_once 'config/database.php';

// Add availability column if it doesn't exist
$sql = "SHOW COLUMNS FROM college_prices LIKE 'availability'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    $sql = "ALTER TABLE college_prices 
            ADD COLUMN availability ENUM('available', 'unavailable') DEFAULT 'available' AFTER category";
    
    if ($conn->query($sql)) {
        echo "✓ تم إضافة عمود حالة التوفر بنجاح";
    } else {
        echo "✗ حدث خطأ أثناء إضافة عمود حالة التوفر: " . $conn->error;
    }
} else {
    echo "✓ عمود حالة التوفر موجود بالفعل";
}
?>