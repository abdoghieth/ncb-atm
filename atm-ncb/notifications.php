<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDBConnection();

    switch($method) {
        case 'GET':
            $stmt = $pdo->query("SELECT * FROM notifications ORDER BY id DESC");
            $notifications = $stmt->fetchAll();
            jsonResponse(true, $notifications);
            break;

        case 'POST':
            if(!isset($input['title']) || !isset($input['message'])) {
                jsonResponse(false, null, 'بيانات ناقصة');
            }

            $title = trim($input['title']);
            $message = trim($input['message']);
            $user_type = $input['user_type'] ?? 'all';

            $stmt = $pdo->prepare("INSERT INTO notifications (title, message, user_type) VALUES (?, ?, ?)");
            $stmt->execute([$title, $message, $user_type]);
            
            jsonResponse(true, ['id' => $pdo->lastInsertId()], 'تم إضافة الإشعار بنجاح');
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if(!$id) {
                jsonResponse(false, null, 'معرف الإشعار مطلوب');
            }

            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
            $stmt->execute([$id]);

            jsonResponse(true, null, 'تم حذف الإشعار بنجاح');
            break;

        default:
            jsonResponse(false, null, 'طريقة غير مدعومة');
    }

} catch(PDOException $e) {
    jsonResponse(false, null, 'خطأ في قاعدة البيانات: ' . $e->getMessage());
}
?>