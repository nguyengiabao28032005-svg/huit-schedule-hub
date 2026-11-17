<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();

// 1. Classes per teacher
$teacherStats = $pdo->query("SELECT t.full_name, COUNT(s.id) as class_count 
                             FROM teachers t 
                             LEFT JOIN schedules s ON t.id = s.teacher_id 
                             GROUP BY t.id, t.full_name 
                             ORDER BY class_count DESC")->fetchAll();

// 2. Classes per branch
$branchStats = $pdo->query("SELECT b.branch_name, COUNT(s.id) as class_count 
                            FROM branches b 
                            LEFT JOIN schedules s ON b.id = s.branch_id 
                            GROUP BY b.id, b.branch_name 
                            ORDER BY class_count DESC")->fetchAll();

// 3. Classes per Day of Week
$dayStats = $pdo->query("SELECT day_of_week, COUNT(*) as class_count 
                         FROM schedules 
                         GROUP BY day_of_week 
                         ORDER BY FIELD(day_of_week, 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ Nhật')")->fetchAll();

echo json_encode([
    'teachers' => $teacherStats,
    'branches' => $branchStats,
    'days' => $dayStats
], JSON_UNESCAPED_UNICODE);
