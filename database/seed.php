<?php
/**
 * Database Seeder using FakerPHP
 * Seeds branches, teachers, and schedules
 */

require_once __DIR__ . '/../config/database.php';

use Faker\Factory as Faker;

$pdo = Database::getConnection();
$faker = Faker::create('vi_VN');

echo "--- BẮT ĐẦU SEED DỮ LIỆU MẪU (HUIT SCHEDULE HUB) ---\n";

// Disable foreign key checks to truncate clean
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
$pdo->exec("TRUNCATE TABLE schedules;");
$pdo->exec("TRUNCATE TABLE teachers;");
$pdo->exec("TRUNCATE TABLE branches;");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

// 1. Seed Branches
$branchesData = [
    [
        'branch_name' => 'Cơ sở chính (Tân Phú)',
        'address' => '140 Lê Trọng Tấn, P. Tây Thạnh, Q. Tân Phú, TP. Hồ Chí Minh'
    ],
    [
        'branch_name' => 'Cơ sở 2 (Nguyễn Thị Tú)',
        'address' => '336 Nguyễn Thị Tú, P. Bình Hưng Hòa B, Q. Bình Tân, TP. Hồ Chí Minh'
    ],
    [
        'branch_name' => 'Cơ sở Thực hành Công nghệ (Tân Kỳ Tân Quý)',
        'address' => '93 Tân Kỳ Tân Quý, P. Tân Sơn Nhì, Q. Tân Phú, TP. Hồ Chí Minh'
    ],
    [
        'branch_name' => 'Trung tâm Đào tạo Kỹ năng & CNTT',
        'address' => '73/1 Nguyễn Đỗ Cung, P. Tây Thạnh, Q. Tân Phú, TP. Hồ Chí Minh'
    ]
];

$branchStmt = $pdo->prepare("INSERT INTO branches (branch_name, address) VALUES (?, ?)");
$branchIds = [];
foreach ($branchesData as $b) {
    $branchStmt->execute([$b['branch_name'], $b['address']]);
    $branchIds[] = $pdo->lastInsertId();
}
echo "✓ Đã thêm " . count($branchIds) . " cơ sở trường học.\n";

// 2. Seed Teachers
$expertises = [
    'Lập trình Web & Mã nguồn mở',
    'Cơ sở dữ liệu & Hệ quản trị',
    'Trí tuệ nhân tạo & Máy học',
    'Mạng máy tính & An toàn thông tin',
    'Lập trình ứng dụng Di động',
    'Công nghệ phần mềm & DevOps',
    'Cấu trúc dữ liệu & Giải thuật',
    'Kiểm thử phần mềm & QA'
];

$vietnameseNames = [
    'Nguyễn Gia Bảo',
    'Trần Văn Hoàng',
    'Lê Thị Mai Hương',
    'Phạm Quốc Đạt',
    'Vũ Minh Tuấn',
    'Đặng Thu Hà',
    'Bùi Quang Dũng',
    'Đỗ Hoàng Nam',
    'Ngô Bảo Châu',
    'Hồ Thị Bích Ngọc',
    'Dương Đình Trí',
    'Lý Cẩm Tiên'
];

$teacherStmt = $pdo->prepare("INSERT INTO teachers (full_name, email, phone, expertise, password) VALUES (?, ?, ?, ?, ?)");
$teacherIds = [];
$teacherList = [];
$defaultPassHash = password_hash('password123', PASSWORD_DEFAULT);

foreach ($vietnameseNames as $index => $name) {
    // Generate clean email
    $cleanName = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name));
    $cleanName = preg_replace('/[^a-z0-9]/', '', $cleanName);
    $email = $cleanName . ($index + 1) . '@huit.edu.vn';
    $phone = '09' . $faker->numerify('########');
    $expertise = $expertises[$index % count($expertises)];

    $teacherStmt->execute([$name, $email, $phone, $expertise, $defaultPassHash]);
    $tid = $pdo->lastInsertId();
    $teacherIds[] = $tid;
    $teacherList[] = [
        'id' => $tid,
        'full_name' => $name,
        'expertise' => $expertise
    ];
}
echo "✓ Đã thêm " . count($teacherIds) . " giảng viên mẫu.\n";

// 3. Seed Schedules (Lịch dạy)
$days = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
$shifts = [
    'Tiết 1 - 3 (Sáng)',
    'Tiết 4 - 6 (Sáng)',
    'Tiết 7 - 9 (Chiều)',
    'Tiết 10 - 12 (Chiều)',
    'Tiết 13 - 15 (Tối)'
];

$subjectByExpertise = [
    'Lập trình Web & Mã nguồn mở' => ['Lập trình PHP & Laravel', 'Công nghệ Web tiên tiến', 'Phát triển Web Fullstack'],
    'Cơ sở dữ liệu & Hệ quản trị' => ['Hệ quản trị CSDL MySQL', 'Thiết kế CSDL quan hệ', 'Kho dữ liệu và Khai phá'],
    'Trí tuệ nhân tạo & Máy học' => ['Nhập môn Trí tuệ nhân tạo', 'Học máy nâng cao (Machine Learning)', 'Xử lý ngôn ngữ tự nhiên'],
    'Mạng máy tính & An toàn thông tin' => ['Mạng máy tính căn bản', 'An toàn và Bảo mật thông tin', 'Quản trị hệ thống Linux'],
    'Lập trình ứng dụng Di động' => ['Lập trình Android với Kotlin', 'Phát triển Flutter đa nền tảng', 'Lập trình iOS Swift'],
    'Công nghệ phần mềm & DevOps' => ['Phương pháp phát triển Agile/Scrum', 'CI/CD & DevOps căn bản', 'Kiến trúc phần mềm'],
    'Cấu trúc dữ liệu & Giải thuật' => ['Cấu trúc dữ liệu và giải thuật', 'Phân tích và thiết kế thuật toán'],
    'Kiểm thử phần mềm & QA' => ['Kiểm thử phần mềm tự động', 'Đảm bảo chất lượng dự án CNTT']
];

$rooms = ['A201', 'A202', 'B101', 'B105', 'C301', 'PM-01', 'PM-02', 'LAB-03', 'D401'];

$scheduleStmt = $pdo->prepare("INSERT INTO schedules (teacher_id, branch_id, subject_name, room, day_of_week, shift_or_periods) VALUES (?, ?, ?, ?, ?, ?)");

$occupiedSlots = []; // key: "$teacher_id|$day|$shift" or "$room|$day|$shift"
$insertedCount = 0;

// Create 20 realistic schedule entries
foreach ($teacherList as $teacher) {
    // Each teacher gets 1 to 3 classes to leave open slots (tiết trống)
    $numClasses = rand(1, 3);
    for ($i = 0; $i < $numClasses; $i++) {
        $day = $days[array_rand($days)];
        $shift = $shifts[array_rand($shifts)];
        $teacherSlotKey = "T:{$teacher['id']}|$day|$shift";

        if (isset($occupiedSlots[$teacherSlotKey])) {
            continue; // Teacher already busy in this slot
        }

        $branchId = $branchIds[array_rand($branchIds)];
        $room = $rooms[array_rand($rooms)];
        $roomSlotKey = "R:$room|$branchId|$day|$shift";

        if (isset($occupiedSlots[$roomSlotKey])) {
            continue; // Room already occupied in that branch and slot
        }

        $availableSubjects = $subjectByExpertise[$teacher['expertise']] ?? ['Tin học đại cương'];
        $subject = $availableSubjects[array_rand($availableSubjects)];

        $scheduleStmt->execute([
            $teacher['id'],
            $branchId,
            $subject,
            $room,
            $day,
            $shift
        ]);

        $occupiedSlots[$teacherSlotKey] = true;
        $occupiedSlots[$roomSlotKey] = true;
        $insertedCount++;
    }
}

echo "✓ Đã tạo thành công {$insertedCount} lịch giảng dạy mẫu tối ưu, không trùng lặp.\n";
echo "--- HOÀN THÀNH SEED DỮ LIỆU! Mật khẩu mặc định giảng viên: password123 ---\n";
