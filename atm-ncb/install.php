<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // إنشاء قاعدة البيانات إذا لم تكن موجودة
    $pdo->exec("CREATE DATABASE IF NOT EXISTS atm_system");
    $pdo->exec("USE atm_system");
    
    // إنشاء الجداول
    $tables = [
        "CREATE TABLE IF NOT EXISTS banks (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            bank_key VARCHAR(50) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            user_type ENUM('admin', 'user') NOT NULL DEFAULT 'user',
            branch_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS machines (
            id INT PRIMARY KEY AUTO_INCREMENT,
            machine_number VARCHAR(50) NOT NULL,
            branch_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (branch_id) REFERENCES banks(id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS parts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            code VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            quantity INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS invoices (
            id INT PRIMARY KEY AUTO_INCREMENT,
            part_code VARCHAR(50) NOT NULL,
            part_name VARCHAR(100) NOT NULL,
            quantity INT NOT NULL,
            date DATE NOT NULL,
            machine_id INT NOT NULL,
            user_id INT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            user_type ENUM('all', 'admin', 'user') DEFAULT 'all',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables as $table) {
        $pdo->exec($table);
    }
    
    // إضافة البيانات الأولية
    $initialData = [
        // إضافة مستخدم مسؤول
        "INSERT IGNORE INTO users (username, password, user_type) VALUES 
        ('admin', '123456', 'admin')",
        
        // إضافة مستخدم عادي
        "INSERT IGNORE INTO users (username, password, user_type, branch_id) VALUES 
        ('user', '123456', 'user', 1)",
        
        // إضافة مصارف
        "INSERT IGNORE INTO banks (name, bank_key) VALUES 
        ('فرع غريان', '057'),
        ('فرع الاصابعة', '086'),
        ('وكالة جامعة غريان', '129')",
        
        // إضافة آلات سحب
        "INSERT IGNORE INTO machines (machine_number, branch_id) VALUES 
        ('10000259', 1),
        ('10000260', 1),
        ('10000255', 2),
        ('10000346', 3)",
        
        // إضافة قطع غيار
        "INSERT IGNORE INTO parts (code, name, quantity) VALUES 
        ('P001', 'شاشة لمس', 10),
        ('P002', 'لوحة مفاتيح', 15),
        ('P003', 'طابعة إيصالات', 8),
        ('P004', 'قارئ البطاقات', 5)",
        
        // إضافة إشعارات
        "INSERT IGNORE INTO notifications (title, message, user_type) VALUES 
        ('مرحباً بكم في النظام', 'تم تشغيل منظومة آلات السحب الذاتي بنجاح', 'all'),
        ('تحديث النظام', 'تم إضافة ميزات جديدة للإدارة والتقارير', 'all')"
    ];
    
    foreach ($initialData as $data) {
        try {
            $pdo->exec($data);
        } catch (Exception $e) {
            // تجاهل الأخطاء في الإدخال المكرر
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تثبيت النظام بنجاح!',
        'login_info' => [
            'admin' => ['username' => 'admin', 'password' => '123456'],
            'user' => ['username' => 'user', 'password' => '123456']
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في التثبيت: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>