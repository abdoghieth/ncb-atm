<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDBConnection();

    switch($method) {
        case 'GET':
            $part_id = $_GET['part_id'] ?? null;
            if($part_id) {
                $stmt = $pdo->prepare("SELECT * FROM quantity_history WHERE part_id = ? ORDER BY id DESC");
                $stmt->execute([$part_id]);
            } else {
                $stmt = $pdo->query("SELECT * FROM quantity_history ORDER BY id DESC");
            }
            $history = $stmt->fetchAll();
            jsonResponse(true, $history);
            break;

        case 'POST':
            $required = ['part_id', 'quantity_added', 'add_date', 'previous_quantity', 'new_quantity'];
            foreach($required as $field) {
                if(!isset($input[$field])) {
                    jsonResponse(false, null, "حقل {$field} مطلوب");
                }
            }

            $part_id = intval($input['part_id']);
            $quantity_added = intval($input['quantity_added']);
            $add_date = $input['add_date'];
            $previous_quantity = intval($input['previous_quantity']);
            $new_quantity = intval($input['new_quantity']);

            $stmt = $pdo->prepare("
                INSERT INTO quantity_history (part_id, quantity_added, add_date, previous_quantity, new_quantity) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$part_id, $quantity_added, $add_date, $previous_quantity, $new_quantity]);
            
            jsonResponse(true, ['id' => $pdo->lastInsertId()], 'تم إضافة سجل الكمية بنجاح');
            break;

        default:
            jsonResponse(false, null, 'طريقة غير مدعومة');
    }

} catch(PDOException $e) {
    jsonResponse(false, null, 'خطأ في قاعدة البيانات: ' . $e->getMessage());
}
?>