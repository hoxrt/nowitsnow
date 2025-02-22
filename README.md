# متجر القرطاسية الجامعي
نظام لتبادل وبيع المستلزمات الدراسية بين طلاب الجامعة

## متطلبات النظام
- PHP 8.2 أو أحدث
- MySQL 5.7 أو أحدث
- WAMP Server أو XAMPP (موصى به)
- متصفح حديث يدعم JavaScript و Tailwind CSS

## خطوات التثبيت

### 1. إعداد بيئة التطوير
1. قم بتثبيت [WAMP Server](https://www.wampserver.com/en/) أو [XAMPP](https://www.apachefriends.org/)
2. تأكد من تشغيل خدمات Apache و MySQL

### 2. إعداد قاعدة البيانات
1. انسخ محتويات المجلد إلى المسار التالي:
   ```
   c:\wamp64\www\nowitsnow\
   ```
   أو في حالة استخدام XAMPP:
   ```
   c:\xampp\htdocs\nowitsnow\
   ```

2. قم بتعديل ملف الإعدادات `config/database.php` إذا كنت تستخدم إعدادات مختلفة:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'stationery_marketplace');
   ```

3. قم بزيارة الروابط التالية بالترتيب لإعداد قاعدة البيانات:
   - http://localhost/nowitsnow/create_database.php
   - http://localhost/nowitsnow/create_comments_table.php
   - http://localhost/nowitsnow/add_is_read_column.php
   - http://localhost/nowitsnow/test_database.php (للتأكد من نجاح الإعداد)

### 3. إعداد مجلد الصور
1. تأكد من وجود مجلد `uploads` في المشروع وأن لديه صلاحيات الكتابة:
   ```
   c:\wamp64\www\nowitsnow\uploads\
   ```
   
2. امنح صلاحيات الكتابة للمجلد:
   - في Windows: انقر بزر الماوس الأيمن على المجلد > Properties > Security
   - في Linux: `chmod 777 uploads`

### 4. إعداد حساب المسؤول
1. قم بزيارة:
   - http://localhost/nowitsnow/setup_admin.php (لإنشاء حساب المسؤول)
   - http://localhost/nowitsnow/setup_library_admin.php (لإنشاء حساب مسؤول المكتبة)

### 5. الوصول للنظام
- قم بزيارة: http://localhost/nowitsnow/
- سجل دخول باستخدام:
  - المسؤول: admin@admin.com / password
  - مسؤول المكتبة: library@admin.com / password

## الميزات الرئيسية
1. **نظام المستخدمين**
   - تسجيل حساب جديد
   - تسجيل الدخول/الخروج
   - الملف الشخصي

2. **المنتجات**
   - عرض المنتجات المتاحة
   - إضافة منتج جديد
   - البحث والتصفية حسب الفئة والحالة

3. **التواصل**
   - نظام التعليقات العامة
   - نظام المراسلة الخاص
   - إشعارات الرسائل غير المقروءة

4. **أسعار المكتبة**
   - عرض أسعار المكتبة
   - إدارة الأسعار (لمسؤول المكتبة)

5. **لوحة التحكم**
   - إدارة المستخدمين
   - إدارة المنتجات
   - إحصائيات النظام

## الأمان
- حماية من SQL Injection
- تشفير كلمات المرور
- معالجة XSS
- التحقق من صحة الملفات المرفوعة

## الدعم
في حال واجهتك أي مشكلة:
1. تأكد من تثبيت جميع المتطلبات
2. تحقق من صلاحيات المجلدات
3. راجع سجلات PHP و MySQL للأخطاء

## التخصيص
- يمكن تعديل الألوان والتصميم من خلال Tailwind CSS
- يمكن تعديل النصوص والترجمات في ملفات PHP
- يمكن إضافة فئات جديدة للمنتجات في ملف `config/database.php`

## الترخيص
جميع الحقوق محفوظة © <?php echo date('Y'); ?>
