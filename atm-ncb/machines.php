<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDBConnection();

    switch($method) {
        case 'GET':
            $stmt = $pdo->query("
                SELECT m.*, b.name as branch_name, b.bank_key 
                FROM machines m 
                JOIN banks b ON m.branch_id = b.id 
                ORDER BY m.id DESC
            ");
            $machines = $stmt->fetchAll();
            jsonResponse(true, $machines);
            break;

        case 'POST':
            if(!isset($input['machine_number']) || !isset($input['branch_id'])) {
                jsonResponse(false, null, 'بيانات ناقصة');
            }

            $machine_number = trim($input['machine_number']);
            $branch_id = intval($input['branch_id']);

            $stmt = $pdo->prepare("INSERT INTO machines (machine_number, branch_id) VALUES (?, ?)");
            $stmt->execute([$machine_number, $branch_id]);
            
            jsonResponse(true, ['id' => $pdo->lastInsertId()], 'تم إضافة الآلة بنجاح');
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if(!$id) {
                jsonResponse(false, null, 'معرف الآلة مطلوب');
            }

            $stmt = $pdo->prepare("DELETE FROM machines WHERE id = ?");
            $stmt->execute([$id]);

            jsonResponse(true, null, 'تم حذف الآلة بنجاح');
            break;

        default:
            jsonResponse(false, null, 'طريقة غير مدعومة');
    }

} catch(PDOException $e) {
    jsonResponse(false, null, 'خطأ في قاعدة البيانات: ' . $e->getMessage());
}
?>