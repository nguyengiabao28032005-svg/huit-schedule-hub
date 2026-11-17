<?php
namespace App\Services;

use Database;
use PDO;

class ScheduleOptimizer {
    private PDO $db;

    public const SHIFTS = [
        'Tiết 1 - 3 (Sáng)'   => ['order' => 1, 'session' => 'morning',   'time' => '07:00 - 09:30'],
        'Tiết 4 - 6 (Sáng)'   => ['order' => 2, 'session' => 'morning',   'time' => '09:45 - 12:15'],
        'Tiết 7 - 9 (Chiều)'  => ['order' => 3, 'session' => 'afternoon', 'time' => '12:45 - 15:15'],
        'Tiết 10 - 12 (Chiều)'=> ['order' => 4, 'session' => 'afternoon', 'time' => '15:30 - 18:00'],
        'Tiết 13 - 15 (Tối)'  => ['order' => 5, 'session' => 'evening',   'time' => '18:15 - 20:45']
    ];

    public const DAYS = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ Nhật'];

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Check if a proposed schedule causes teacher or room conflict
     */
    public function validateSchedule(int $teacherId, int $branchId, string $room, string $day, string $shift, ?int $excludeId = null): array {
        $errors = [];
        $warnings = [];

        // 1. Check Teacher Conflict
        $sqlTeacher = "SELECT s.*, b.branch_name FROM schedules s 
                       JOIN branches b ON s.branch_id = b.id 
                       WHERE s.teacher_id = ? AND s.day_of_week = ? AND s.shift_or_periods = ?";
        $paramsTeacher = [$teacherId, $day, $shift];
        if ($excludeId) {
            $sqlTeacher .= " AND s.id != ?";
            $paramsTeacher[] = $excludeId;
        }
        $stmtT = $this->db->prepare($sqlTeacher);
        $stmtT->execute($paramsTeacher);
        $tConflict = $stmtT->fetch();

        if ($tConflict) {
            $errors[] = "Giảng viên đã có lịch dạy môn \"{$tConflict['subject_name']}\" tại {$tConflict['branch_name']} (Phòng {$tConflict['room']}) vào {$day}, {$shift}!";
        }

        // 2. Check Room Conflict
        $sqlRoom = "SELECT s.*, t.full_name FROM schedules s 
                    JOIN teachers t ON s.teacher_id = t.id 
                    WHERE s.branch_id = ? AND s.room = ? AND s.day_of_week = ? AND s.shift_or_periods = ?";
        $paramsRoom = [$branchId, $room, $day, $shift];
        if ($excludeId) {
            $sqlRoom .= " AND s.id != ?";
            $paramsRoom[] = $excludeId;
        }
        $stmtR = $this->db->prepare($sqlRoom);
        $stmtR->execute($paramsRoom);
        $rConflict = $stmtR->fetch();

        if ($rConflict) {
            $errors[] = "Phòng {$room} tại cơ sở này đã được giảng viên {$rConflict['full_name']} sử dụng để dạy môn \"{$rConflict['subject_name']}\" vào {$day}, {$shift}!";
        }

        // 3. Smart Travel Warning: Check if teacher teaches at a DIFFERENT branch on the same day
        $sqlDay = "SELECT s.*, b.branch_name, b.id as b_id FROM schedules s
                   JOIN branches b ON s.branch_id = b.id
                   WHERE s.teacher_id = ? AND s.day_of_week = ?";
        $paramsDay = [$teacherId, $day];
        if ($excludeId) {
            $sqlDay .= " AND s.id != ?";
            $paramsDay[] = $excludeId;
        }
        $stmtD = $this->db->prepare($sqlDay);
        $stmtD->execute($paramsDay);
        $dayClasses = $stmtD->fetchAll();

        $proposedOrder = self::SHIFTS[$shift]['order'] ?? 0;
        foreach ($dayClasses as $cls) {
            $clsOrder = self::SHIFTS[$cls['shift_or_periods']]['order'] ?? 0;
            $isAdjacent = abs($proposedOrder - $clsOrder) === 1;

            if ($cls['b_id'] != $branchId && $isAdjacent) {
                $warnings[] = "⚠️ Cảnh báo di chuyển: Giảng viên có ca liền kề ({$cls['shift_or_periods']}) tại \"{$cls['branch_name']}\". Hãy đảm bảo thời gian di chuyển giữa 2 cơ sở (khoảng 30 phút).";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Smart recommendation engine for teachers based on subject expertise,
     * time availability, and campus travel optimization
     */
    public function recommendTeachers(string $subjectName, string $day, string $shift, int $branchId): array {
        // Fetch all teachers
        $teachers = $this->db->query("SELECT * FROM teachers ORDER BY full_name ASC")->fetchAll();

        // Fetch all busy schedules for this day and shift
        $stmtBusy = $this->db->prepare("SELECT s.*, b.branch_name FROM schedules s 
                                        JOIN branches b ON s.branch_id = b.id 
                                        WHERE s.day_of_week = ? AND s.shift_or_periods = ?");
        $stmtBusy->execute([$day, $shift]);
        $busyMap = [];
        foreach ($stmtBusy->fetchAll() as $b) {
            $busyMap[$b['teacher_id']] = $b;
        }

        // Fetch teachers' other schedules on this same day to evaluate travel optimization
        $stmtSameDay = $this->db->prepare("SELECT s.*, b.branch_name FROM schedules s 
                                           JOIN branches b ON s.branch_id = b.id 
                                           WHERE s.day_of_week = ?");
        $stmtSameDay->execute([$day]);
        $sameDayMap = [];
        foreach ($stmtSameDay->fetchAll() as $sd) {
            $sameDayMap[$sd['teacher_id']][] = $sd;
        }

        $results = [];
        $targetOrder = self::SHIFTS[$shift]['order'] ?? 3;

        foreach ($teachers as $t) {
            $tid = (int)$t['id'];
            $isBusy = isset($busyMap[$tid]);
            $busyInfo = $isBusy ? $busyMap[$tid] : null;

            // 1. Calculate Expertise Match Score
            $score = 40;
            $matchReasons = [];
            $expertise = mb_strtolower($t['expertise'], 'UTF-8');
            $subject = mb_strtolower($subjectName, 'UTF-8');

            $keywords = [
                'web' => ['web', 'php', 'laravel', 'frontend', 'backend', 'mã nguồn mở'],
                'data' => ['dữ liệu', 'database', 'csdl', 'sql', 'mysql', 'khai phá', 'kho dữ liệu'],
                'ai' => ['trí tuệ nhân tạo', 'ai', 'máy học', 'machine learning', 'ngôn ngữ tự nhiên', 'deep learning'],
                'network' => ['mạng', 'bảo mật', 'an toàn', 'security', 'linux', 'truyền thông'],
                'mobile' => ['di động', 'android', 'ios', 'flutter', 'mobile', 'kotlin', 'swift'],
                'software' => ['công nghệ phần mềm', 'phần mềm', 'devops', 'scrum', 'agile', 'kiến trúc'],
                'algo' => ['thuật toán', 'giải thuật', 'cấu trúc dữ liệu'],
                'test' => ['kiểm thử', 'qa', 'test', 'chất lượng']
            ];

            $matchedCategory = false;
            foreach ($keywords as $group => $words) {
                $subMatch = false;
                $expMatch = false;
                foreach ($words as $w) {
                    if (str_contains($subject, $w)) { $subMatch = true; }
                    if (str_contains($expertise, $w)) { $expMatch = true; }
                }
                if ($subMatch && $expMatch) {
                    $score += 45;
                    $matchedCategory = true;
                    $matchReasons[] = "Chuyên môn khớp chuẩn xác với học phần: \"{$t['expertise']}\"";
                    break;
                }
            }

            if (!$matchedCategory) {
                // Generic string similarity
                similar_text($subject, $expertise, $percent);
                if ($percent > 30) {
                    $score += 25;
                    $matchReasons[] = "Chuyên môn liên quan gần: \"{$t['expertise']}\"";
                } else {
                    $score += 10;
                    $matchReasons[] = "Chuyên môn khác: \"{$t['expertise']}\"";
                }
            }

            // 2. Travel & Campus Optimization Factor
            $travelStatus = 'neutral';
            $travelNote = 'Chưa có lịch khác trong ngày này.';
            if (isset($sameDayMap[$tid])) {
                $hasSameBranchClass = false;
                $hasAdjacentOtherBranch = false;

                foreach ($sameDayMap[$tid] as $otherClass) {
                    $otherOrder = self::SHIFTS[$otherClass['shift_or_periods']]['order'] ?? 0;
                    $isAdjacent = abs($targetOrder - $otherOrder) === 1;

                    if ($otherClass['branch_id'] == $branchId) {
                        $hasSameBranchClass = true;
                    } elseif ($isAdjacent) {
                        $hasAdjacentOtherBranch = true;
                    }
                }

                if ($hasSameBranchClass) {
                    $score += 20;
                    $travelStatus = 'optimal';
                    $travelNote = '⭐ Tuyệt vời: Giảng viên đã có lịch tại cùng cơ sở trong ngày, không cần di chuyển!';
                } elseif ($hasAdjacentOtherBranch) {
                    $score -= 20;
                    $travelStatus = 'warning';
                    $travelNote = '⚠️ Chú ý: Giảng viên có ca liền kề tại cơ sở khác, cần thời gian di chuyển.';
                } else {
                    $travelStatus = 'acceptable';
                    $travelNote = 'Giảng viên có ca tại cơ sở khác nhưng không liền kề (có thời gian nghỉ di chuyển).';
                }
            }

            // Availability status
            if ($isBusy) {
                $score = -100;
                $availabilityStatus = 'busy';
                $statusBadge = 'Bận';
                $statusMessage = "Đã có lịch dạy môn \"{$busyInfo['subject_name']}\" ({$busyInfo['room']} - {$busyInfo['branch_name']})";
            } else {
                $availabilityStatus = 'free';
                if ($score >= 80) {
                    $statusBadge = 'Ưu tiên hàng đầu (Tối ưu)';
                } elseif ($score >= 60) {
                    $statusBadge = 'Phù hợp cao';
                } else {
                    $statusBadge = 'Có thể phân công';
                }
                $statusMessage = "Trống lịch trong ca này.";
            }

            $results[] = [
                'teacher_id' => $tid,
                'full_name' => $t['full_name'],
                'email' => $t['email'],
                'phone' => $t['phone'],
                'expertise' => $t['expertise'],
                'score' => $score,
                'is_available' => !$isBusy,
                'availability_status' => $availabilityStatus,
                'status_badge' => $statusBadge,
                'status_message' => $statusMessage,
                'travel_status' => $travelStatus,
                'travel_note' => $travelNote,
                'match_reasons' => $matchReasons
            ];
        }

        // Sort: Available first, then by score descending
        usort($results, function($a, $b) {
            if ($a['is_available'] !== $b['is_available']) {
                return $b['is_available'] ? 1 : -1;
            }
            return $b['score'] <=> $a['score'];
        });

        return $results;
    }

    /**
     * Calculate teacher weekly workload and free slots
     */
    public function getTeacherWorkloadAnalysis(): array {
        $teachers = $this->db->query("SELECT * FROM teachers ORDER BY full_name ASC")->fetchAll();
        $schedules = $this->db->query("SELECT s.*, b.branch_name FROM schedules s JOIN branches b ON s.branch_id = b.id")->fetchAll();

        $matrix = [];
        foreach ($teachers as $t) {
            $matrix[$t['id']] = [
                'teacher' => $t,
                'total_classes' => 0,
                'branches' => [],
                'schedule_by_day' => [],
                'risky_moves' => 0
            ];
            foreach (self::DAYS as $d) {
                $matrix[$t['id']]['schedule_by_day'][$d] = [];
            }
        }

        foreach ($schedules as $s) {
            $tid = $s['teacher_id'];
            if (isset($matrix[$tid])) {
                $matrix[$tid]['total_classes']++;
                $matrix[$tid]['branches'][$s['branch_name']] = true;
                $matrix[$tid]['schedule_by_day'][$s['day_of_week']][] = $s;
            }
        }

        // Detect inter-branch rush moves for each teacher
        foreach ($matrix as $tid => &$item) {
            foreach ($item['schedule_by_day'] as $day => $dayClasses) {
                if (count($dayClasses) >= 2) {
                    for ($i = 0; $i < count($dayClasses) - 1; $i++) {
                        $c1 = $dayClasses[$i];
                        $c2 = $dayClasses[$i + 1];
                        $ord1 = self::SHIFTS[$c1['shift_or_periods']]['order'] ?? 0;
                        $ord2 = self::SHIFTS[$c2['shift_or_periods']]['order'] ?? 0;
                        if (abs($ord1 - $ord2) === 1 && $c1['branch_id'] != $c2['branch_id']) {
                            $item['risky_moves']++;
                        }
                    }
                }
            }
        }

        return $matrix;
    }
}
