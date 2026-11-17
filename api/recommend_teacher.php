<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

use App\Services\ScheduleOptimizer;

$subject = trim($_GET['subject_name'] ?? $_POST['subject_name'] ?? '');
$day = trim($_GET['day_of_week'] ?? $_POST['day_of_week'] ?? '');
$shift = trim($_GET['shift_or_periods'] ?? $_POST['shift_or_periods'] ?? '');
$branchId = (int)($_GET['branch_id'] ?? $_POST['branch_id'] ?? 0);

if (empty($day) || empty($shift)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Vui lòng chọn Thứ và Buổi/Tiết để hệ thống gợi ý.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$optimizer = new ScheduleOptimizer();
$recommendations = $optimizer->recommendTeachers($subject, $day, $shift, $branchId);

echo json_encode([
    'status' => 'success',
    'total' => count($recommendations),
    'data' => $recommendations
], JSON_UNESCAPED_UNICODE);
