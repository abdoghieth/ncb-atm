<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDBConnection();

    switch($method) {
        case 'GET':
            $stmt = $pdo->query("
                SELECT u.*, b.name as branch_name 
                FROM users u 
                LEFT JOIN banks b ON u.branch_id = b.id 
                ORDER BY u.id DESC
            ");
            $users = $stmt->fetchAll();
            jsonResponse(true, $users);
            break;

        case 'POST':
            if(!isset($input['username']) || !isset($input['password']) || !isset($input['user_type'])) {
                jsonResponse(false, null, 'بيانات ناقصة');
            }

            $username = trim($input['username']);
            $password = trim($input['password']); // حفظ كنص عادي للتبسيط
            $user_type = $input['user_type'];
            $branch_id = ($user_type === 'admin') ? null : ($input['branch_id'] ?? null);

            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, user_type, branch_id) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$username, $password, $user_type, $branch_id]);
            
            jsonResponse(true, ['id' => $pdo->lastInsertId()], 'تم إضافة المستخدم بنجاح');
            break;

        case 'PUT':
            $id = $_GET['id'] ?? null;
            if(!$id) {
                jsonResponse(false, null, 'معرف المستخدم مطلوب');
            }

            $updates = [];
            $params = [];

            if(isset($input['username'])) {
                $updates[] = "username = ?";
                $params[] = $input['username'];
            }

            if(isset($input['password']) && !empty($input['password'])) {
                $updates[] = "password = ?";
                $params[] = $input['password'];
            }

            if(isset($input['user_type'])) {
                $updates[] = "user_type = ?";
                $params[] = $input['user_type'];
                
                if($input['user_type'] === 'admin') {
                    $updates[] = "branch_id = NULL";
                } else if(isset($input['branch_id'])) {
                    $updates[] = "branch_id = ?";
                    $params[] = $input['branch_id'];
                }
            }

            if(empty($updates)) {
                jsonResponse(false, null, 'لا توجد بيانات للتحديث');
            }

            $params[] = $id;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            jsonResponse(true, null, 'تم تحديث المستخدم بنجاح');
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if(!$id) {
                jsonResponse(false, null, 'معرف المستخدم مطلوب');
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            jsonResponse(true, null, 'تم حذف المستخدم بنجاح');
            break;

        default:
            jsonResponse(false, null, 'طريقة غير مدعومة');
    }

} catch(PDOException $e) {
    jsonResponse(false, null, 'خطأ في قاعدة البيانات: ' . $e->getMessage());
}
?>