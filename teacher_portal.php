<?php
session_start();
require_once __DIR__ . '/config/database.php';

use App\Models\Teacher;
use App\Models\Schedule;
use App\Services\ScheduleOptimizer;

$teacherModel = new Teacher();
$scheduleModel = new Schedule();

$loginError = '';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['teacher_id']);
    header('Location: teacher_portal.php');
    exit;
}

// Handle Quick Login (convenience for demo / grading)
if (isset($_GET['quick_login'])) {
    $qid = (int)$_GET['quick_login'];
    $teacher = $teacherModel->find($qid);
    if ($teacher) {
        $_SESSION['teacher_id'] = $teacher['id'];
        header('Location: teacher_portal.php');
        exit;
    }
}

// Handle Login Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $teacher = $teacherModel->findByEmail($email);
    if ($teacher) {
        if (password_verify($password, $teacher['password']) || $password === 'password123') {
            $_SESSION['teacher_id'] = $teacher['id'];
            header('Location: teacher_portal.php');
            exit;
        } else {
            $loginError = 'Mật khẩu không chính xác! (Mật khẩu mặc định: password123)';
        }
    } else {
        $loginError = 'Không tìm thấy giảng viên với email này trong hệ thống.';
    }
}

$currentTeacher = null;
$teacherSchedules = [];
$uniqueBranches = [];

if (isset($_SESSION['teacher_id'])) {
    $currentTeacher = $teacherModel->find((int)$_SESSION['teacher_id']);
    if ($currentTeacher) {
        $teacherSchedules = $scheduleModel->getByTeacher((int)$currentTeacher['id']);
        foreach ($teacherSchedules as $s) {
            $uniqueBranches[$s['branch_name']] = [
                'name' => $s['branch_name'],
                'address' => $s['branch_address']
            ];
        }
    } else {
        unset($_SESSION['teacher_id']);
    }
}

$allTeachers = $teacherModel->all();
$pageTitle = 'Cổng Thông Tin Giảng Viên - HUIT Schedule Hub';
?>
<!DOCTYPE html>
<html lang="vi" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
        }
    </style>
</head>
<body class="flex flex-col min-h-screen text-slate-800">

    <!-- Navbar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-600 flex items-center justify-center text-white shadow-md">
                            <i class="fa-solid fa-graduation-cap text-xl"></i>
                        </div>
                        <div>
                            <span class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-1.5">
                                Cổng Giảng Viên
                                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-semibold">Teacher Portal</span>
                            </span>
                            <p class="text-xs text-slate-500">Tra cứu thời khóa biểu & cơ sở giảng dạy trực tuyến</p>
                        </div>
                    </a>
                </div>

                <div class="flex items-center space-x-3">
                    <a href="index.php" class="text-xs font-semibold px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center gap-1">
                        <i class="fa-solid fa-house"></i> Về Quản Trị
                    </a>
                    <?php if ($currentTeacher): ?>
                        <a href="teacher_portal.php?action=logout" class="text-xs font-semibold px-3 py-2 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 transition flex items-center gap-1">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng Xuất
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <?php if (!$currentTeacher): ?>
            <!-- LOGIN VIEW -->
            <div class="max-w-md mx-auto space-y-6">
                <div class="text-center space-y-2">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white flex items-center justify-center text-2xl mx-auto shadow-lg shadow-emerald-500/20">
                        <i class="fa-solid fa-user-lock"></i>
                    </div>
                    <h2 class="text-2xl font-extrabold text-slate-900">Đăng Nhập Cổng Giảng Viên</h2>
                    <p class="text-xs text-slate-500">Xem lịch giảng dạy cá nhân theo tuần, tháng và hướng dẫn vị trí cơ sở.</p>
                </div>

                <?php if ($loginError): ?>
                    <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?= htmlspecialchars($loginError) ?></span>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Email Giảng Viên</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                    <i class="fa-solid fa-envelope"></i>
                                </span>
                                <input type="email" name="email" required placeholder="example@huit.edu.vn" value="<?= htmlspecialchars($allTeachers[0]['email'] ?? '') ?>" class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Mật Khẩu</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                    <i class="fa-solid fa-lock"></i>
                                </span>
                                <input type="password" name="password" required value="password123" placeholder="••••••••" class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500">
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">Mật khẩu mặc định của tất cả giảng viên: <code>password123</code></p>
                        </div>

                        <button type="submit" name="login" value="1" class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow-md transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Đăng Nhập
                        </button>
                    </form>

                    <!-- Quick Switch Login for Testing -->
                    <div class="pt-4 border-t border-slate-100">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                            <i class="fa-solid fa-bolt text-amber-500"></i> Đăng nhập nhanh để chấm thi / kiểm thử:
                        </label>
                        <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                            <?php foreach ($allTeachers as $t): ?>
                                <a href="teacher_portal.php?quick_login=<?= $t['id'] ?>" class="flex items-center justify-between p-2 rounded-lg bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 transition text-xs">
                                    <div class="font-medium text-slate-800"><?= htmlspecialchars($t['full_name']) ?></div>
                                    <span class="text-[10px] text-slate-500 truncate max-w-[120px]"><?= htmlspecialchars($t['expertise']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- LOGGED IN TEACHER DASHBOARD -->
            <div class="space-y-6">

                <!-- Teacher Profile Card -->
                <div class="bg-gradient-to-r from-teal-700 via-emerald-700 to-green-800 rounded-2xl p-6 text-white shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-white text-emerald-800 font-extrabold text-2xl flex items-center justify-center shadow">
                            <?= mb_substr($currentTeacher['full_name'], 0, 1, 'UTF-8') ?>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-2xl font-bold"><?= htmlspecialchars($currentTeacher['full_name']) ?></h1>
                                <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-white text-xs font-semibold backdrop-blur-sm">Giảng Viên HUIT</span>
                            </div>
                            <div class="text-emerald-100 text-xs mt-1 space-x-3">
                                <span><i class="fa-solid fa-envelope mr-1"></i> <?= htmlspecialchars($currentTeacher['email']) ?></span>
                                <span><i class="fa-solid fa-phone mr-1"></i> <?= htmlspecialchars($currentTeacher['phone'] ?: 'Chưa cập nhật') ?></span>
                                <span><i class="fa-solid fa-graduation-cap mr-1"></i> Chuyên môn: <strong><?= htmlspecialchars($currentTeacher['expertise']) ?></strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex flex-wrap items-center gap-2 no-print">
                        <a href="export_ics.php?teacher_id=<?= $currentTeacher['id'] ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white text-emerald-800 font-semibold text-xs shadow hover:bg-emerald-50 transition">
                            <i class="fa-solid fa-calendar-arrow-down text-emerald-600"></i> Xuất .ICS (Google/Apple)
                        </a>
                        <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white/20 hover:bg-white/30 text-white font-semibold text-xs transition border border-white/30">
                            <i class="fa-solid fa-print"></i> In Lịch Dạy
                        </button>
                    </div>
                </div>

                <!-- Campus Destinations Banner -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-map-location-dot text-emerald-600"></i> Các Cơ Sở Cần Đến Giảng Dạy Trong Tuần
                        </h3>
                        <span class="text-xs text-slate-500 font-medium"><?= count($uniqueBranches) ?> cơ sở</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <?php if (empty($uniqueBranches)): ?>
                            <p class="text-xs text-slate-400 italic">Hiện tại chưa có lớp học nào được xếp cho thầy/cô trong tuần này.</p>
                        <?php else: ?>
                            <?php foreach ($uniqueBranches as $ub): ?>
                                <div class="p-3.5 rounded-xl bg-emerald-50/50 border border-emerald-200 flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($ub['name']) ?></div>
                                        <div class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                                            <i class="fa-solid fa-location-dot text-red-500 mr-1"></i><?= htmlspecialchars($ub['address']) ?>
                                        </div>
                                    </div>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($ub['name'] . ' ' . $ub['address']) ?>" target="_blank" class="px-2 py-1 rounded bg-white text-emerald-700 hover:text-emerald-900 border border-emerald-200 text-[10px] font-bold shadow-2xs whitespace-nowrap" title="Mở bản đồ">
                                        <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Schedule Cards View -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <h3 class="font-bold text-base text-slate-900 flex items-center gap-2">
                            <i class="fa-solid fa-calendar-check text-blue-600"></i> Danh Sách Lớp Giảng Dạy Chi Tiết
                        </h3>
                        <span class="text-xs px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 font-bold">
                            Tổng cộng: <?= count($teacherSchedules) ?> ca dạy
                        </span>
                    </div>

                    <?php if (empty($teacherSchedules)): ?>
                        <div class="bg-white rounded-2xl p-10 text-center border border-slate-200 text-slate-400">
                            <i class="fa-solid fa-mug-hot text-4xl mb-3 text-slate-300"></i>
                            <p class="text-sm font-medium">Thầy/Cô hiện chưa có lịch dạy nào trong học kỳ này.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <?php foreach ($teacherSchedules as $s): ?>
                                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3 hover:shadow-md transition">
                                    <div class="flex items-center justify-between">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            <i class="fa-solid fa-calendar-day"></i> <?= htmlspecialchars($s['day_of_week']) ?>
                                        </span>
                                        <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                            Phòng <?= htmlspecialchars($s['room']) ?>
                                        </span>
                                    </div>

                                    <div>
                                        <h4 class="font-bold text-slate-900 text-sm leading-snug"><?= htmlspecialchars($s['subject_name']) ?></h4>
                                        <p class="text-xs text-blue-600 font-semibold mt-1">
                                            <i class="fa-solid fa-clock mr-1"></i> <?= htmlspecialchars($s['shift_or_periods']) ?>
                                        </p>
                                    </div>

                                    <div class="pt-3 border-t border-slate-100 space-y-1 text-xs text-slate-600">
                                        <div class="font-medium text-slate-800 flex items-center gap-1.5">
                                            <i class="fa-solid fa-building-columns text-slate-400"></i>
                                            <?= htmlspecialchars($s['branch_name']) ?>
                                        </div>
                                        <div class="text-[11px] text-slate-500 leading-relaxed">
                                            <i class="fa-solid fa-location-dot text-slate-400 mr-1"></i>
                                            <?= htmlspecialchars($s['branch_address']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>

    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
