<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDBConnection();

    switch($method) {
        case 'GET':
            $stmt = $pdo->query("
                SELECT i.*, u.username, m.machine_number, b.name as branch_name
                FROM invoices i 
                JOIN users u ON i.user_id = u.id 
                JOIN machines m ON i.machine_id = m.id 
                JOIN banks b ON m.branch_id = b.id 
                ORDER BY i.id DESC
            ");
            $invoices = $stmt->fetchAll();
            jsonResponse(true, $invoices);
            break;

        case 'POST':
            $required = ['part_code', 'part_name', 'quantity', 'date', 'machine_id', 'user_id'];
            foreach($required as $field) {
                if(!isset($input[$field])) {
                    jsonResponse(false, null, "حقل {$field} مطلوب");
                }
            }

            $part_code = trim($input['part_code']);
            $part_name = trim($input['part_name']);
            $quantity = intval($input['quantity']);
            $date = $input['date'];
            $machine_id = intval($input['machine_id']);
            $user_id = intval($input['user_id']);
            $status = $input['status'] ?? 'pending';

            $stmt = $pdo->prepare("
                INSERT INTO invoices (part_code, part_name, quantity, date, machine_id, user_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$part_code, $part_name, $quantity, $date, $machine_id, $user_id, $status]);
            
            jsonResponse(true, ['id' => $pdo->lastInsertId()], 'تم إضافة الفاتورة بنجاح');
            break;

        case 'PUT':
            $id = $_GET['id'] ?? null;
            if(!$id) {
                jsonResponse(false, null, 'معرف الفاتورة مطلوب');
            }

            if(!isset($input['status'])) {
                jsonResponse(false, null, 'حقل الحالة مطلوب');
            }

            $status = $input['status'];

            $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);

            jsonResponse(true, null, 'تم تحديث الفاتورة بنجاح');
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if(!$id) {
                jsonResponse(false, null, 'معرف الفاتورة مطلوب');
            }

            $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
            $stmt->execute([$id]);

            jsonResponse(true, null, 'تم حذف الفاتورة بنجاح');
            break;

        default:
            jsonResponse(false, null, 'طريقة غير مدعومة');
    }

} catch(PDOException $e) {
    jsonResponse(false, null, 'خطأ في قاعدة البيانات: ' . $e->getMessage());
}
?>