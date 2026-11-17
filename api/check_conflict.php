<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

use App\Services\ScheduleOptimizer;

$teacherId = (int)($_POST['teacher_id'] ?? $_GET['teacher_id'] ?? 0);
$branchId = (int)($_POST['branch_id'] ?? $_GET['branch_id'] ?? 0);
$room = trim($_POST['room'] ?? $_GET['room'] ?? '');
$day = trim($_POST['day_of_week'] ?? $_GET['day_of_week'] ?? '');
$shift = trim($_POST['shift_or_periods'] ?? $_GET['shift_or_periods'] ?? '');
$excludeId = isset($_POST['exclude_id']) ? (int)$_POST['exclude_id'] : (isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null);

if (!$teacherId || !$branchId || empty($room) || empty($day) || empty($shift)) {
    echo json_encode([
        'valid' => true,
        'errors' => [],
        'warnings' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$optimizer = new ScheduleOptimizer();
$result = $optimizer->validateSchedule($teacherId, $branchId, $room, $day, $shift, $excludeId);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
