<?php
require_once 'config/database.php';

if ($conn) {
    echo "✓ تم الاتصال بقاعدة البيانات بنجاح<br>";
    
    // التحقق من وجود الجداول
    $tables = ['users', 'products', 'messages', 'college_prices'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "✓ جدول $table موجود<br>";
        } else {
            echo "✗ جدول $table غير موجود<br>";
        }
    }
    
    // عرض معلومات إضافية عن قاعدة البيانات
    $result = $conn->query("SELECT DATABASE()");
    $dbname = $result->fetch_array()[0];
    echo "<br>اسم قاعدة البيانات الحالية: " . $dbname . "<br>";
    
    // عرض إعدادات الترميز
    $result = $conn->query("SHOW VARIABLES LIKE 'character_set%'");
    echo "<br>إعدادات الترميز:<br>";
    while ($row = $result->fetch_assoc()) {
        echo $row['Variable_name'] . ": " . $row['Value'] . "<br>";
    }
} else {
    echo "✗ فشل الاتصال بقاعدة البيانات";
}
?>