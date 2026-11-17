<?php
$pageTitle = 'Trang Tổng Quan & Biểu Đồ Phân Bổ';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

use App\Models\Branch;
use App\Models\Teacher;
use App\Models\Schedule;
use App\Services\ScheduleOptimizer;

$branchModel = new Branch();
$teacherModel = new Teacher();
$scheduleModel = new Schedule();
$optimizer = new ScheduleOptimizer();

// Get filter parameters
$filterBranch = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null;
$filterTeacher = isset($_GET['teacher_id']) && $_GET['teacher_id'] !== '' ? (int)$_GET['teacher_id'] : null;
$filterDay = isset($_GET['day_of_week']) && $_GET['day_of_week'] !== '' ? $_GET['day_of_week'] : null;

// Fetch filtered schedules
$filters = [];
if ($filterBranch) $filters['branch_id'] = $filterBranch;
if ($filterTeacher) $filters['teacher_id'] = $filterTeacher;
if ($filterDay) $filters['day_of_week'] = $filterDay;

$schedules = $scheduleModel->all($filters);
$allBranches = $branchModel->all();
$allTeachers = $teacherModel->all();

// Metrics
$totalBranches = $branchModel->count();
$totalTeachers = $teacherModel->count();
$totalSchedules = $scheduleModel->count();
$avgPerTeacher = $totalTeachers > 0 ? round($totalSchedules / $totalTeachers, 1) : 0;

// Prepare data for Timetable Matrix View
$days = ScheduleOptimizer::DAYS;
$shifts = array_keys(ScheduleOptimizer::SHIFTS);

$matrix = [];
foreach ($days as $d) {
    foreach ($shifts as $s) {
        $matrix[$d][$s] = [];
    }
}
foreach ($schedules as $item) {
    if (isset($matrix[$item['day_of_week']][$item['shift_or_periods']])) {
        $matrix[$item['day_of_week']][$item['shift_or_periods']][] = $item;
    }
}
?>

<div class="space-y-6">

    <!-- 1. Modern SaaS Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 border border-slate-800 shadow-elevated">
        <!-- Ambient decorative glow -->
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="max-w-2xl space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/15 text-indigo-300 border border-indigo-500/25 backdrop-blur-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-ping"></span>
                    <i class="fa-solid fa-wand-magic-sparkles text-amber-300 text-[11px]"></i>
                    <span>HUIT Schedule Hub Engine 2026</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-tight">
                    Hệ Thống Phân Lịch & Tối Ưu Giảng Dạy Đa Cơ Sở
                </h1>
                <p class="text-slate-300 text-xs sm:text-sm leading-relaxed max-w-xl">
                    Giải pháp thông minh giúp phòng đào tạo tối ưu hóa lộ trình di chuyển giữa các cơ sở, loại bỏ xung đột trùng ca và cung cấp cổng tra cứu thời khóa biểu cá nhân trực tuyến.
                </p>
            </div>

            <!-- Action buttons -->
            <div class="flex flex-wrap items-center gap-3 self-start lg:self-center">
                <a href="schedules.php?action=create" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-slate-900 font-semibold text-xs shadow-sm hover:bg-slate-100 hover:shadow-md transition-all duration-150 transform active:scale-95">
                    <i class="fa-solid fa-plus text-indigo-600"></i>
                    <span>Phân Lịch Mới</span>
                </a>
                <a href="optimize.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-white font-semibold text-xs border border-white/15 backdrop-blur-sm transition-all duration-150">
                    <i class="fa-solid fa-bolt text-amber-300"></i>
                    <span>Kiểm Tra Tối Ưu</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 2. Refined Stats Cards (SaaS Metric Grid) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <!-- Card 1: Branches -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:shadow-md hover:border-slate-300 transition-all duration-200 group">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Cơ Sở Đào Tạo</span>
                    <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1"><?= $totalBranches ?></div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white transition-all duration-200">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-1.5 text-xs text-blue-600 font-medium">
                <i class="fa-solid fa-location-dot text-[11px]"></i>
                <span>Cơ sở chính & vệ tinh HUIT</span>
            </div>
        </div>

        <!-- Card 2: Teachers -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:shadow-md hover:border-slate-300 transition-all duration-200 group">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Tổng Giảng Viên</span>
                    <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1"><?= $totalTeachers ?></div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-200">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-1.5 text-xs text-emerald-600 font-medium">
                <i class="fa-solid fa-circle-check text-[11px]"></i>
                <span>Đã nạp hồ sơ & chuyên môn</span>
            </div>
        </div>

        <!-- Card 3: Schedules -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:shadow-md hover:border-slate-300 transition-all duration-200 group">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Tổng Ca / Lớp Dạy</span>
                    <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1"><?= $totalSchedules ?></div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base group-hover:scale-110 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-200">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-1.5 text-xs text-indigo-600 font-medium">
                <i class="fa-solid fa-book-open text-[11px]"></i>
                <span>Phân bố trong tuần</span>
            </div>
        </div>

        <!-- Card 4: Average Workload -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:shadow-md hover:border-slate-300 transition-all duration-200 group">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Định Mức Giảng Dạy</span>
                    <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                        <?= $avgPerTeacher ?> <span class="text-xs font-semibold text-slate-400">ca/GV</span>
                    </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base group-hover:scale-110 group-hover:bg-amber-600 group-hover:text-white transition-all duration-200">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-1.5 text-xs text-amber-600 font-medium">
                <i class="fa-solid fa-chart-line text-[11px]"></i>
                <span>Cân đối khối lượng công việc</span>
            </div>
        </div>
    </div>

    <!-- 3. Modernized Filter Bar (shadcn/ui style) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-xs">
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Bộ Lọc Lịch Giảng Dạy Nhanh</h3>
            </div>
            <span class="text-[11px] text-slate-400">Hiển thị kết quả khớp với tiêu chí lựa chọn</span>
        </div>

        <form method="GET" action="index.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 items-end">
            <!-- Filter 1: Branch -->
            <div>
                <label for="branch_id" class="block text-xs font-semibold text-slate-600 mb-1.5">
                    <i class="fa-solid fa-building text-slate-400 mr-1"></i> Cơ sở đào tạo
                </label>
                <div class="relative">
                    <select name="branch_id" id="branch_id" class="w-full text-xs font-medium rounded-xl border-slate-300/80 bg-slate-50/50 hover:bg-white focus:bg-white shadow-xs focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all border p-2.5 text-slate-700">
                        <option value="">-- Tất cả cơ sở --</option>
                        <?php foreach ($allBranches as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= $filterBranch === (int)$b['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['branch_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Filter 2: Teacher -->
            <div>
                <label for="teacher_id" class="block text-xs font-semibold text-slate-600 mb-1.5">
                    <i class="fa-solid fa-chalkboard-user text-slate-400 mr-1"></i> Giảng viên
                </label>
                <div class="relative">
                    <select name="teacher_id" id="teacher_id" class="w-full text-xs font-medium rounded-xl border-slate-300/80 bg-slate-50/50 hover:bg-white focus:bg-white shadow-xs focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all border p-2.5 text-slate-700">
                        <option value="">-- Tất cả giảng viên --</option>
                        <?php foreach ($allTeachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $filterTeacher === (int)$t['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['full_name']) ?> (<?= htmlspecialchars($t['expertise']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Filter 3: Day of Week -->
            <div>
                <label for="day_of_week" class="block text-xs font-semibold text-slate-600 mb-1.5">
                    <i class="fa-solid fa-calendar-day text-slate-400 mr-1"></i> Thứ trong tuần
                </label>
                <div class="relative">
                    <select name="day_of_week" id="day_of_week" class="w-full text-xs font-medium rounded-xl border-slate-300/80 bg-slate-50/50 hover:bg-white focus:bg-white shadow-xs focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all border p-2.5 text-slate-700">
                        <option value="">-- Tất cả các ngày --</option>
                        <?php foreach ($days as $d): ?>
                            <option value="<?= $d ?>" <?= $filterDay === $d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 bg-slate-900 hover:bg-indigo-600 text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-xs transition-all duration-150 flex items-center justify-center gap-2 active:scale-95">
                    <i class="fa-solid fa-filter text-[10px]"></i>
                    <span>Áp Dụng Lọc</span>
                </button>
                <?php if ($filterBranch || $filterTeacher || $filterDay): ?>
                    <a href="index.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs py-2.5 px-3.5 rounded-xl transition-all duration-150 flex items-center justify-center" title="Bỏ lọc">
                        <i class="fa-solid fa-arrow-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- 4. Clean Minimalist Charts Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Chart 1: Teacher Workload Distribution -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        Phân Bổ Ca Dạy Theo Giảng Viên
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Kiểm soát khối lượng giảng dạy giữa các thầy/cô trong khoa</p>
                </div>
                <span class="text-[10px] px-2.5 py-1 rounded-full font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                    Cân đối tải dạy
                </span>
            </div>
            <div class="flex-1 relative min-h-[290px]">
                <canvas id="teacherLoadChart"></canvas>
            </div>
        </div>

        <!-- Chart 2: Branch Campus Utilization -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Tỷ Trọng Lớp Học Giữa Các Cơ Sở
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Tỷ lệ sử dụng phòng học tại cơ sở chính và các phân hiệu</p>
                </div>
                <span class="text-[10px] px-2.5 py-1 rounded-full font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                    Mức độ phân bổ
                </span>
            </div>
            <div class="flex-1 relative min-h-[290px] flex items-center justify-center">
                <canvas id="branchDistChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 5. Visual Timetable Matrix Grid -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-table-cells text-indigo-600"></i>
                    <span>Ma Trận Thời Khóa Biểu Tổng Thể</span>
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    Hiển thị các lớp đang diễn ra theo lưới Thứ & Ca học (<strong><?= count($schedules) ?></strong> lớp đang hiển thị)
                </p>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="inline-flex items-center gap-1.5 text-slate-600 font-medium">
                    <span class="w-2.5 h-2.5 rounded-md bg-indigo-100 border border-indigo-300"></span> Đã có lớp
                </span>
                <span class="inline-flex items-center gap-1.5 text-slate-600 font-medium">
                    <span class="w-2.5 h-2.5 rounded-md bg-slate-50 border border-dashed border-slate-300"></span> Tiết trống
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead>
                    <tr class="bg-slate-100/70 text-slate-600 font-semibold border-b border-slate-200/80">
                        <th class="p-3 w-44 border-r border-slate-200/80 text-xs">Buổi / Khung giờ</th>
                        <?php foreach ($days as $day): ?>
                            <th class="p-3 min-w-[170px] border-r border-slate-200/80 text-center <?= $filterDay === $day ? 'bg-indigo-100/60 text-indigo-900 font-bold' : '' ?>">
                                <?= $day ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80">
                    <?php foreach ($shifts as $shift): ?>
                        <tr class="hover:bg-slate-50/40 transition">
                            <td class="p-3 border-r border-slate-200/80 bg-slate-50/50 font-medium text-slate-800">
                                <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($shift) ?></div>
                                <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1">
                                    <i class="fa-regular fa-clock text-[10px]"></i>
                                    <?= ScheduleOptimizer::SHIFTS[$shift]['time'] ?? '' ?>
                                </div>
                            </td>
                            <?php foreach ($days as $day): ?>
                                <?php 
                                    $cellClasses = $matrix[$day][$shift]; 
                                    $hasClasses = !empty($cellClasses);
                                ?>
                                <td class="p-2 border-r border-slate-200/80 align-top <?= $hasClasses ? 'bg-indigo-50/20' : 'bg-slate-50/10' ?>">
                                    <?php if ($hasClasses): ?>
                                        <div class="space-y-1.5">
                                            <?php foreach ($cellClasses as $cls): ?>
                                                <div class="timetable-cell p-2.5 rounded-xl bg-white border border-indigo-100 shadow-xs hover:border-indigo-300 transition-all duration-150 cursor-pointer" onclick="showScheduleModal(<?= htmlspecialchars(json_encode($cls)) ?>)">
                                                    <div class="font-bold text-slate-900 text-xs line-clamp-1" title="<?= htmlspecialchars($cls['subject_name']) ?>">
                                                        <?= htmlspecialchars($cls['subject_name']) ?>
                                                    </div>
                                                    <div class="text-[11px] text-slate-600 mt-1 flex items-center gap-1.5">
                                                        <i class="fa-solid fa-chalkboard-user text-slate-400 text-[10px]"></i>
                                                        <span class="font-medium truncate"><?= htmlspecialchars($cls['teacher_name']) ?></span>
                                                    </div>
                                                    <div class="text-[10px] text-slate-500 mt-1 flex items-center justify-between gap-1">
                                                        <span class="font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                                            <i class="fa-solid fa-door-open"></i> <?= htmlspecialchars($cls['room']) ?>
                                                        </span>
                                                        <span class="text-slate-400 truncate max-w-[90px]" title="<?= htmlspecialchars($cls['branch_name']) ?>">
                                                            <?= htmlspecialchars($cls['branch_name']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="h-16 flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200/80 text-slate-400 group hover:border-indigo-400 hover:text-indigo-600 hover:bg-indigo-50/30 transition-all duration-150">
                                            <a href="schedules.php?action=create&day=<?= urlencode($day) ?>&shift=<?= urlencode($shift) ?><?= $filterBranch ? '&branch_id=' . $filterBranch : '' ?>" class="text-[11px] font-semibold flex items-center gap-1 py-2 px-1 text-center">
                                                <i class="fa-solid fa-plus-circle text-xs"></i> Trống (Xếp)
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Schedule Detail Modal (Interactive SweetAlert Popup) -->
<script>
function showScheduleModal(data) {
    Swal.fire({
        title: `<div class="text-lg font-bold text-slate-900">${data.subject_name}</div>`,
        html: `
            <div class="text-left text-xs space-y-3 mt-3">
                <div class="p-3 bg-indigo-50/70 rounded-xl border border-indigo-100 flex items-center justify-between">
                    <span class="font-semibold text-indigo-900"><i class="fa-solid fa-calendar-day mr-1.5"></i> ${data.day_of_week}</span>
                    <span class="text-xs bg-indigo-200/70 text-indigo-800 font-bold px-2.5 py-0.5 rounded-full">${data.shift_or_periods}</span>
                </div>
                <div class="flex items-center gap-3 p-2 rounded-lg bg-slate-50 border border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-white shadow-xs flex items-center justify-center text-indigo-600"><i class="fa-solid fa-chalkboard-user"></i></div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Giảng viên phụ trách</div>
                        <div class="font-bold text-slate-800 text-xs">${data.teacher_name} <span class="text-slate-400 font-normal">(${data.teacher_expertise || 'N/A'})</span></div>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-2 rounded-lg bg-slate-50 border border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-white shadow-xs flex items-center justify-center text-blue-600"><i class="fa-solid fa-building-columns"></i></div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Cơ sở & Địa chỉ</div>
                        <div class="font-bold text-slate-800 text-xs">${data.branch_name}</div>
                        <div class="text-[11px] text-slate-500">${data.branch_address}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-2 rounded-lg bg-slate-50 border border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-white shadow-xs flex items-center justify-center text-emerald-600"><i class="fa-solid fa-door-open"></i></div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Phòng học chỉ định</div>
                        <div class="font-extrabold text-emerald-700 text-sm">${data.room}</div>
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-pen-to-square"></i> Chỉnh sửa',
        confirmButtonColor: '#4f46e5',
        cancelButtonText: 'Đóng',
        cancelButtonColor: '#64748b',
        customClass: {
            popup: 'rounded-2xl',
            confirmButton: 'rounded-xl text-xs px-4 py-2 font-semibold',
            cancelButton: 'rounded-xl text-xs px-4 py-2 font-semibold'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `schedules.php?action=edit&id=${data.id}`;
        }
    });
}

// Fetch stats and render Chart.js with modern aesthetics
document.addEventListener('DOMContentLoaded', function() {
    fetch('api/stats.php')
        .then(res => res.json())
        .then(data => {
            // Chart 1: Teacher Load Chart (Rounded modern bars)
            const teacherNames = data.teachers.slice(0, 8).map(t => t.full_name);
            const teacherCounts = data.teachers.slice(0, 8).map(t => t.class_count);

            new Chart(document.getElementById('teacherLoadChart'), {
                type: 'bar',
                data: {
                    labels: teacherNames,
                    datasets: [{
                        label: 'Số ca dạy trong tuần',
                        data: teacherCounts,
                        backgroundColor: '#6366f1',
                        hoverBackgroundColor: '#4f46e5',
                        borderRadius: 8,
                        borderSkipped: false,
                        barThickness: 28,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' },
                            bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                            padding: 10,
                            cornerRadius: 8,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(226, 232, 240, 0.6)',
                                drawBorder: false
                            },
                            ticks: {
                                stepSize: 1,
                                font: { family: 'Plus Jakarta Sans', size: 11 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { family: 'Plus Jakarta Sans', size: 11 }
                            }
                        }
                    }
                }
            });

            // Chart 2: Branch Distribution Doughnut (Sophisticated palette)
            const branchNames = data.branches.map(b => b.branch_name);
            const branchCounts = data.branches.map(b => b.class_count);

            new Chart(document.getElementById('branchDistChart'), {
                type: 'doughnut',
                data: {
                    labels: branchNames,
                    datasets: [{
                        data: branchCounts,
                        backgroundColor: [
                            '#6366f1', // Indigo
                            '#10b981', // Emerald
                            '#f59e0b', // Amber
                            '#8b5cf6', // Violet
                            '#06b6d4'  // Cyan
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                usePointStyle: true,
                                font: { family: 'Plus Jakarta Sans', size: 11, weight: '500' },
                                padding: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' },
                            bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                            padding: 10,
                            cornerRadius: 8
                        }
                    }
                }
            });
        });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
