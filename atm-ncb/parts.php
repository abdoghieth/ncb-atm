<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDBConnection();

    switch($method) {
        case 'GET':
            $stmt = $pdo->query("SELECT * FROM parts ORDER BY id DESC");
            $parts = $stmt->fetchAll();
            jsonResponse(true, $parts);
            break;

        case 'POST':
            if(!isset($input['code']) || !isset($input['name'])) {
                jsonResponse(false, null, 'بيانات ناقصة');
            }

            $code = trim($input['code']);
            $name = trim($input['name']);
            $quantity = isset($input['quantity']) ? intval($input['quantity']) : 0;

            $stmt = $pdo->prepare("INSERT INTO parts (code, name, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$code, $name, $quantity]);
            
            jsonResponse(true, ['id' => $pdo->lastInsertId()], 'تم إضافة القطعة بنجاح');
            break;

        case 'PUT':
            $id = $_GET['id'] ?? null;
            if(!$id) {
                jsonResponse(false, null, 'معرف القطعة مطلوب');
            }

            $updates = [];
            $params = [];

            if(isset($input['code'])) {
                $updates[] = "code = ?";
                $params[] = $input['code'];
            }

            if(isset($input['name'])) {
                $updates[] = "name = ?";
                $params[] = $input['name'];
            }

            if(isset($input['quantity'])) {
                $updates[] = "quantity = ?";
                $params[] = intval($input['quantity']);
            }

            if(empty($updates)) {
                jsonResponse(false, null, 'لا توجد بيانات للتحديث');
            }

            $params[] = $id;
            $sql = "UPDATE parts SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            jsonResponse(true, null, 'تم تحديث القطعة بنجاح');
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if(!$id) {
                jsonResponse(false, null, 'معرف القطعة مطلوب');
            }

            $stmt = $pdo->prepare("DELETE FROM parts WHERE id = ?");
            $stmt->execute([$id]);

            jsonResponse(true, null, 'تم حذف القطعة بنجاح');
            break;

        default:
            jsonResponse(false, null, 'طريقة غير مدعومة');
    }

} catch(PDOException $e) {
    jsonResponse(false, null, 'خطأ في قاعدة البيانات: ' . $e->getMessage());
}
?>