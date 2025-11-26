<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['username']) || !isset($input['password'])) {
        jsonResponse(false, null, 'اسم المستخدم وكلمة المرور مطلوبان');
    }
    
    $username = trim($input['username']);
    $password = trim($input['password']);
    
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT u.*, b.name as branch_name 
            FROM users u 
            LEFT JOIN banks b ON u.branch_id = b.id 
            WHERE u.username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // كلمات مرور بسيطة للاختبار
            $simple_passwords = ['123456', 'admin', 'password', 'user'];
            
            $password_valid = false;
            
            // التحقق من كلمة المرور المشفرة
            if (password_verify($password, $user['password'])) {
                $password_valid = true;
            }
            // التحقق من كلمات المرور البسيطة
            else if (in_array($password, $simple_passwords)) {
                $password_valid = true;
            }
            // إذا كانت كلمة المرور نص عادي
            else if ($user['password'] === $password) {
                $password_valid = true;
            }
            
            if ($password_valid) {
                // إخفاء كلمة المرور قبل الإرسال
                unset($user['password']);
                jsonResponse(true, $user, 'تم تسجيل الدخول بنجاح');
            } else {
                jsonResponse(false, null, 'كلمة المرور غير صحيحة');
            }
        } else {
            jsonResponse(false, null, 'اسم المستخدم غير موجود');
        }
        
    } catch(PDOException $e) {
        jsonResponse(false, null, 'خطأ في قاعدة البيانات: ' . $e->getMessage());
    }
} else {
    jsonResponse(false, null, 'طريقة غير مدعومة');
}
?>