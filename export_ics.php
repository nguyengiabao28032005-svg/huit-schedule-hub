<?php
require_once __DIR__ . '/config/database.php';

use App\Models\Teacher;
use App\Models\Schedule;
use App\Services\ScheduleOptimizer;

$teacherId = (int)($_GET['teacher_id'] ?? 0);
$teacherModel = new Teacher();
$scheduleModel = new Schedule();

$teacher = $teacherModel->find($teacherId);
if (!$teacher) {
    die("Không tìm thấy thông tin giảng viên.");
}

$schedules = $scheduleModel->getByTeacher($teacherId);

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="HUIT_LichDay_' . urlencode($teacher['full_name']) . '.ics"');

$byDayMap = [
    'Thứ 2' => 'MO',
    'Thứ 3' => 'TU',
    'Thứ 4' => 'WE',
    'Thứ 5' => 'TH',
    'Thứ 6' => 'FR',
    'Thứ 7' => 'SA',
    'Chủ Nhật' => 'SU'
];

$shiftTimeMap = [
    'Tiết 1 - 3 (Sáng)'    => ['start' => '070000', 'end' => '093000'],
    'Tiết 4 - 6 (Sáng)'    => ['start' => '094500', 'end' => '121500'],
    'Tiết 7 - 9 (Chiều)'   => ['start' => '124500', 'end' => '151500'],
    'Tiết 10 - 12 (Chiều)' => ['start' => '153000', 'end' => '180000'],
    'Tiết 13 - 15 (Tối)'   => ['start' => '181500', 'end' => '204500']
];

$today = date('Ymd');
$now = gmdate('Ymd\THis\Z');

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//HUIT Schedule Hub//VN\r\n";
echo "CALSCALE:GREGORIAN\r\n";
echo "METHOD:PUBLISH\r\n";
echo "X-WR-CALNAME:Lịch Giảng Dạy - " . $teacher['full_name'] . "\r\n";
echo "X-WR-TIMEZONE:Asia/Ho_Chi_Minh\r\n";

foreach ($schedules as $s) {
    $uid = "huit-schedule-{$s['id']}@" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $dayCode = $byDayMap[$s['day_of_week']] ?? 'MO';
    $time = $shiftTimeMap[$s['shift_or_periods']] ?? ['start' => '070000', 'end' => '093000'];

    $summary = $s['subject_name'] . " (" . $s['room'] . ")";
    $location = $s['branch_name'] . " - " . $s['branch_address'] . " (Phòng " . $s['room'] . ")";
    $description = "Lịch giảng dạy HUIT\\nGiảng viên: " . $teacher['full_name'] . "\\nCa học: " . $s['shift_or_periods'] . "\\nĐịa điểm: " . $location;

    echo "BEGIN:VEVENT\r\n";
    echo "UID:{$uid}\r\n";
    echo "DTSTAMP:{$now}\r\n";
    echo "DTSTART;TZID=Asia/Ho_Chi_Minh:{$today}T{$time['start']}\r\n";
    echo "DTEND;TZID=Asia/Ho_Chi_Minh:{$today}T{$time['end']}\r\n";
    echo "RRULE:FREQ=WEEKLY;BYDAY={$dayCode}\r\n";
    echo "SUMMARY:{$summary}\r\n";
    echo "LOCATION:{$location}\r\n";
    echo "DESCRIPTION:{$description}\r\n";
    echo "STATUS:CONFIRMED\r\n";
    echo "END:VEVENT\r\n";
}

echo "END:VCALENDAR\r\n";
