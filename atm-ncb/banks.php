<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDBConnection();

    switch($method) {
        case 'GET':
            $stmt = $pdo->query("SELECT * FROM banks ORDER BY id DESC");
            $banks = $stmt->fetchAll();
            jsonResponse(true, $banks);
            break;

        case 'POST':
            if(!isset($input['name']) || !isset($input['key'])) {
                jsonResponse(false, null, 'بيانات ناقصة');
            }

            $name = trim($input['name']);
            $key = trim($input['key']);

            $stmt = $pdo->prepare("INSERT INTO banks (name, bank_key) VALUES (?, ?)");
            $stmt->execute([$name, $key]);
            
            jsonResponse(true, ['id' => $pdo->lastInsertId()], 'تم إضافة المصرف بنجاح');
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if(!$id) {
                jsonResponse(false, null, 'معرف المصرف مطلوب');
            }

            $stmt = $pdo->prepare("DELETE FROM banks WHERE id = ?");
            $stmt->execute([$id]);

            jsonResponse(true, null, 'تم حذف المصرف بنجاح');
            break;

        default:
            jsonResponse(false, null, 'طريقة غير مدعومة');
    }

} catch(PDOException $e) {
    jsonResponse(false, null, 'خطأ في قاعدة البيانات: ' . $e->getMessage());
}
?>