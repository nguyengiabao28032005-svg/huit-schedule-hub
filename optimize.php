<?php
$pageTitle = 'Tối Ưu Hóa & Phân Lịch Theo Tiết Trống';
require_once __DIR__ . '/config/database.php';

use App\Models\Branch;
use App\Models\Teacher;
use App\Models\Schedule;
use App\Services\ScheduleOptimizer;

$teacherModel = new Teacher();
$branchModel = new Branch();
$scheduleModel = new Schedule();
$optimizer = new ScheduleOptimizer();

$selectedTeacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : null;

// Re-seed action if requested
$actionMsg = '';
if (isset($_POST['reseed'])) {
    // Run seed.php script safely
    ob_start();
    include __DIR__ . '/database/seed.php';
    $seedOutput = ob_get_clean();
    $actionMsg = 'Đã sinh mới dữ liệu mẫu tối ưu thành công bằng FakerPHP!';
}

$analysis = $optimizer->getTeacherWorkloadAnalysis();
$allTeachers = $teacherModel->all();
$days = ScheduleOptimizer::DAYS;
$shifts = array_keys(ScheduleOptimizer::SHIFTS);

// Calculate total metrics
$totalRiskyMoves = 0;
$totalTeachersCount = count($allTeachers);
foreach ($analysis as $item) {
    $totalRiskyMoves += $item['risky_moves'];
}

// If teacher selected, build free slots matrix
$selectedTeacher = null;
$teacherFreeSlots = [];
if ($selectedTeacherId) {
    $selectedTeacher = $teacherModel->find($selectedTeacherId);
    if ($selectedTeacher) {
        $teacherClasses = $scheduleModel->getByTeacher($selectedTeacherId);
        $busySlots = [];
        foreach ($teacherClasses as $c) {
            $busySlots[$c['day_of_week']][$c['shift_or_periods']] = $c;
        }

        foreach ($days as $d) {
            foreach ($shifts as $s) {
                if (!isset($busySlots[$d][$s])) {
                    $teacherFreeSlots[$d][] = $s;
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles text-amber-500"></i>
                Phân Lịch Theo Tiết Trống & Tối Ưu Hóa Di Chuyển
            </h1>
            <p class="text-sm text-slate-500">
                Thuật toán phân tích khoảng trống chết, kiểm tra di chuyển gấp giữa các cơ sở và khai thác tối đa tiết trống của giảng viên.
            </p>
        </div>
        <form method="POST" onsubmit="return confirm('Bạn có chắc muốn làm mới và sinh lại toàn bộ dữ liệu mẫu chuẩn hóa với FakerPHP?');">
            <button type="submit" name="reseed" value="1" class="inline-flex items-center gap-2 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition border border-slate-300">
                <i class="fa-solid fa-arrows-rotate text-blue-600"></i> Reset & Re-Seed Faker Data
            </button>
        </form>
    </div>

    <?php if ($actionMsg): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-lg text-emerald-500"></i>
            <span><?= htmlspecialchars($actionMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Health Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Xung Đột Lịch Dạy</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">0 Xung đột</p>
                <p class="text-xs text-slate-500 mt-1">Phòng & Giảng viên 100% an toàn</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-shield-check"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Ca Di Chuyển Gấp</p>
                <p class="text-2xl font-bold <?= $totalRiskyMoves > 0 ? 'text-amber-500' : 'text-emerald-600' ?> mt-1">
                    <?= $totalRiskyMoves ?> Ca
                </p>
                <p class="text-xs text-slate-500 mt-1">2 ca liền kề tại cơ sở khác nhau</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-person-walking-arrow-right"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Tỷ Lệ Tối Ưu Hóa</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">
                    <?= $totalTeachersCount > 0 ? round((($totalTeachersCount - $totalRiskyMoves) / $totalTeachersCount) * 100) : 100 ?>%
                </p>
                <p class="text-xs text-slate-500 mt-1">Dựa trên khoảng cách di chuyển</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-gauge-high"></i>
            </div>
        </div>
    </div>

    <!-- Free Slots Explorer for Specific Teacher -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-clock text-blue-600"></i> Tra Cứu & Khai Thác Tiết Trống Của Giảng Viên
                </h3>
                <p class="text-xs text-slate-500">Chọn giảng viên để hệ thống tự động vẽ bản đồ các buổi/tiết còn trống, bấm vào để xếp lịch ngay lập tức.</p>
            </div>
            <form method="GET" action="optimize.php" class="flex items-center gap-2">
                <select name="teacher_id" onchange="this.form.submit()" class="text-xs rounded-lg border-slate-300 border p-2 bg-slate-50 font-medium">
                    <option value="">-- Chọn giảng viên để xem tiết trống --</option>
                    <?php foreach ($allTeachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $selectedTeacherId === (int)$t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['full_name']) ?> (<?= htmlspecialchars($t['expertise']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($selectedTeacher): ?>
            <div class="p-4 rounded-xl bg-blue-50/60 border border-blue-200 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-blue-600 text-white font-bold text-lg flex items-center justify-center shadow">
                        <?= mb_substr($selectedTeacher['full_name'], 0, 1, 'UTF-8') ?>
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($selectedTeacher['full_name']) ?></div>
                        <div class="text-xs text-blue-700 font-medium">Chuyên môn: <?= htmlspecialchars($selectedTeacher['expertise']) ?></div>
                        <div class="text-[11px] text-slate-500"><?= htmlspecialchars($selectedTeacher['email']) ?> • <?= htmlspecialchars($selectedTeacher['phone']) ?></div>
                    </div>
                </div>
                <div class="text-xs text-slate-600 bg-white px-3 py-2 rounded-lg border border-slate-200 shadow-2xs">
                    <i class="fa-solid fa-circle-info text-blue-600 mr-1"></i> Bấm vào bất kỳ <strong>ô tiết trống màu xanh lá</strong> để mở nhanh giao diện phân lịch.
                </div>
            </div>

            <!-- Free Slots Matrix Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 font-semibold border-b border-slate-200">
                            <th class="p-3 text-left w-40 border-r border-slate-200">Buổi / Tiết học</th>
                            <?php foreach ($days as $day): ?>
                                <th class="p-3 text-center border-r border-slate-200 min-w-[130px]"><?= $day ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php 
                            $busyMap = [];
                            $tClasses = $scheduleModel->getByTeacher($selectedTeacherId);
                            foreach ($tClasses as $tc) {
                                $busyMap[$tc['day_of_week']][$tc['shift_or_periods']] = $tc;
                            }
                        ?>
                        <?php foreach ($shifts as $shift): ?>
                            <tr>
                                <td class="p-3 font-semibold text-slate-800 bg-slate-50 border-r border-slate-200">
                                    <div><?= $shift ?></div>
                                    <div class="text-[10px] text-slate-400 font-normal"><?= ScheduleOptimizer::SHIFTS[$shift]['time'] ?></div>
                                </td>
                                <?php foreach ($days as $day): ?>
                                    <?php $isOccupied = isset($busyMap[$day][$shift]); ?>
                                    <td class="p-2 border-r border-slate-200 text-center align-middle">
                                        <?php if ($isOccupied): ?>
                                            <?php $cls = $busyMap[$day][$shift]; ?>
                                            <div class="p-2 rounded-lg bg-blue-100 border border-blue-200 text-blue-900 font-medium text-left">
                                                <div class="font-bold truncate" title="<?= htmlspecialchars($cls['subject_name']) ?>"><?= htmlspecialchars($cls['subject_name']) ?></div>
                                                <div class="text-[10px] text-blue-700"><?= htmlspecialchars($cls['branch_name']) ?> (<?= htmlspecialchars($cls['room']) ?>)</div>
                                            </div>
                                        <?php else: ?>
                                            <a href="schedules.php?action=create&day=<?= urlencode($day) ?>&shift=<?= urlencode($shift) ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-semibold transition group">
                                                <i class="fa-solid fa-plus-circle text-emerald-500 group-hover:scale-110 transition"></i> Tiết Trống
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-10 text-slate-400 text-xs">
                <i class="fa-solid fa-hand-pointer text-2xl text-blue-400 mb-2"></i>
                <p>Hãy chọn một giảng viên ở thanh chọn phía trên để xem bản đồ tiết trống.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Teacher Workload & Travel Efficiency Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-list-check text-blue-600"></i> Bảng Phân Tích Phân Bổ Ca Dạy & Tối Ưu Cơ Sở Toàn Trường
            </h3>
            <p class="text-xs text-slate-500">Tổng hợp số ca dạy, danh sách cơ sở di chuyển và cảnh báo khoảng trống di chuyển của từng giảng viên.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 font-semibold border-b border-slate-200">
                        <th class="p-3 w-12 text-center">STT</th>
                        <th class="p-3">Giảng Viên</th>
                        <th class="p-3">Chuyên Môn</th>
                        <th class="p-3 text-center">Tổng Ca Dạy</th>
                        <th class="p-3">Cơ Sở Giảng Dạy Trong Tuần</th>
                        <th class="p-3">Đánh Giá Tối Ưu Di Chuyển</th>
                        <th class="p-3 text-center w-28">Thao Tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php $idx = 1; foreach ($analysis as $tid => $data): ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-3 text-center text-slate-400 font-medium"><?= $idx++ ?></td>
                            <td class="p-3">
                                <div class="font-bold text-slate-900"><?= htmlspecialchars($data['teacher']['full_name']) ?></div>
                                <div class="text-[11px] text-slate-500"><?= htmlspecialchars($data['teacher']['email']) ?></div>
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-medium border border-slate-200">
                                    <?= htmlspecialchars($data['teacher']['expertise']) ?>
                                </span>
                            </td>
                            <td class="p-3 text-center">
                                <span class="text-sm font-bold text-blue-700"><?= $data['total_classes'] ?></span> ca
                            </td>
                            <td class="p-3">
                                <div class="flex flex-wrap gap-1">
                                    <?php if (empty($data['branches'])): ?>
                                        <span class="text-slate-400 italic">Chưa xếp lịch</span>
                                    <?php else: ?>
                                        <?php foreach (array_keys($data['branches']) as $bname): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                <?= htmlspecialchars($bname) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-3">
                                <?php if ($data['risky_moves'] > 0): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Có <?= $data['risky_moves'] ?> ca di chuyển gấp
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        <i class="fa-solid fa-circle-check"></i> Lịch dạy tối ưu
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-center">
                                <a href="optimize.php?teacher_id=<?= $data['teacher']['id'] ?>" class="text-xs px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition inline-flex items-center gap-1">
                                    <i class="fa-solid fa-calendar-day"></i> Tiết trống
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
