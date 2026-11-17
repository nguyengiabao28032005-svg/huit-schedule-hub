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

    <!-- Top Hero / Banner -->
    <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 rounded-2xl p-6 text-white shadow-lg">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white backdrop-blur-sm mb-2">
                    <i class="fa-solid fa-sparkles text-amber-300"></i> HUIT Schedule Hub v1.0
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Hệ Thống Quản Lý & Phân Lịch Đa Cơ Sở Thông Minh</h1>
                <p class="text-blue-100 text-sm mt-1 max-w-2xl">
                    Tối ưu hóa thời gian di chuyển giữa các cơ sở, gợi ý giảng viên phù hợp chuyên môn và hỗ trợ tra cứu lịch giảng dạy trực tuyến.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="schedules.php?action=create" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-blue-800 font-semibold text-sm shadow hover:bg-blue-50 transition transform active:scale-95">
                    <i class="fa-solid fa-plus-circle text-blue-600"></i> Phân Lịch Mới
                </a>
                <a href="optimize.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600/60 hover:bg-blue-600 border border-white/20 text-white font-semibold text-sm transition">
                    <i class="fa-solid fa-bolt text-amber-300"></i> Kiểm Tra Tối Ưu
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Tổng Cơ Sở Đào Tạo</p>
                <p class="text-2xl font-bold text-slate-900 mt-1"><?= $totalBranches ?></p>
                <p class="text-xs text-blue-600 mt-1"><i class="fa-solid fa-map-pin mr-1"></i> Cơ sở chính & vệ tinh</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-building-columns"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Tổng Giảng Viên</p>
                <p class="text-2xl font-bold text-slate-900 mt-1"><?= $totalTeachers ?></p>
                <p class="text-xs text-emerald-600 mt-1"><i class="fa-solid fa-user-check mr-1"></i> Đầy đủ chuyên môn</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Tổng Ca / Lớp Dạy</p>
                <p class="text-2xl font-bold text-slate-900 mt-1"><?= $totalSchedules ?></p>
                <p class="text-xs text-indigo-600 mt-1"><i class="fa-solid fa-calendar-check mr-1"></i> Đã xếp vào hệ thống</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-book-open"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Định Mức Trung Bình</p>
                <p class="text-2xl font-bold text-slate-900 mt-1"><?= $avgPerTeacher ?> <span class="text-sm font-normal text-slate-500">lớp/GV</span></p>
                <p class="text-xs text-amber-600 mt-1"><i class="fa-solid fa-scale-balanced mr-1"></i> Cân bằng tải dạy</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-chart-column"></i>
            </div>
        </div>
    </div>

    <!-- Filter Bar (Quản lý & Lọc chung) -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
        <form method="GET" action="index.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="branch_id" class="block text-xs font-semibold text-slate-600 uppercase mb-1">
                    <i class="fa-solid fa-building mr-1 text-slate-400"></i> Lọc theo Cơ sở:
                </label>
                <select name="branch_id" id="branch_id" class="w-full text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-slate-50 border p-2">
                    <option value="">-- Tất cả cơ sở --</option>
                    <?php foreach ($allBranches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $filterBranch === (int)$b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['branch_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="teacher_id" class="block text-xs font-semibold text-slate-600 uppercase mb-1">
                    <i class="fa-solid fa-chalkboard-user mr-1 text-slate-400"></i> Lọc theo Giảng viên:
                </label>
                <select name="teacher_id" id="teacher_id" class="w-full text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-slate-50 border p-2">
                    <option value="">-- Tất cả giảng viên --</option>
                    <?php foreach ($allTeachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $filterTeacher === (int)$t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['full_name']) ?> (<?= htmlspecialchars($t['expertise']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="day_of_week" class="block text-xs font-semibold text-slate-600 uppercase mb-1">
                    <i class="fa-solid fa-calendar mr-1 text-slate-400"></i> Lọc theo Thứ:
                </label>
                <select name="day_of_week" id="day_of_week" class="w-full text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-slate-50 border p-2">
                    <option value="">-- Tất cả các ngày trong tuần --</option>
                    <?php foreach ($days as $d): ?>
                        <option value="<?= $d ?>" <?= $filterDay === $d ? 'selected' : '' ?>><?= $d ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm py-2 px-4 rounded-lg shadow-sm transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-filter"></i> Áp Dụng Lọc
                </button>
                <?php if ($filterBranch || $filterTeacher || $filterDay): ?>
                    <a href="index.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-sm py-2 px-3 rounded-lg transition flex items-center justify-center" title="Bỏ lọc">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Charts Section (Biểu đồ phân bổ thời gian & cơ sở) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Chart 1: Teacher Teaching Load Distribution -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-chart-column text-blue-600"></i> Biểu Đồ Phân Bổ Ca Dạy Theo Giảng Viên
                    </h3>
                    <p class="text-xs text-slate-500">Giúp phòng đào tạo kiểm soát khối lượng giảng dạy đồng đều</p>
                </div>
            </div>
            <div class="flex-1 relative min-h-[280px]">
                <canvas id="teacherLoadChart"></canvas>
            </div>
        </div>

        <!-- Chart 2: Campus Utilization Distribution -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-chart-pie text-indigo-600"></i> Biểu Đồ Tỷ Lệ Lớp Học Theo Từng Cơ Sở
                    </h3>
                    <p class="text-xs text-slate-500">Tỷ trọng lớp phân bổ giữa cơ sở chính và các phân hiệu/phòng máy</p>
                </div>
            </div>
            <div class="flex-1 relative min-h-[280px] flex items-center justify-center">
                <canvas id="branchDistChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Visual Timetable Matrix Grid -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-table-cells text-blue-600"></i> Ma Trận Thời Khóa Biểu Tổng Thể
                </h3>
                <p class="text-xs text-slate-500">Hiển thị lịch phân công theo lưới Thứ & Ca học (<?= count($schedules) ?> lớp đang hiển thị)</p>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1 text-slate-600"><span class="w-3 h-3 rounded bg-blue-100 border border-blue-300"></span> Có lớp</span>
                <span class="inline-flex items-center gap-1 text-slate-600"><span class="w-3 h-3 rounded bg-slate-50 border border-dashed border-slate-300"></span> Tiết trống</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead>
                    <tr class="bg-slate-100/80 text-slate-700 font-semibold border-b border-slate-200">
                        <th class="p-3 w-40 border-r border-slate-200">Buổi / Khung giờ</th>
                        <?php foreach ($days as $day): ?>
                            <th class="p-3 min-w-[170px] border-r border-slate-200 text-center <?= $filterDay === $day ? 'bg-blue-100 text-blue-900' : '' ?>">
                                <?= $day ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($shifts as $shift): ?>
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="p-3 border-r border-slate-200 bg-slate-50 font-medium text-slate-800">
                                <div class="font-bold text-slate-900"><?= htmlspecialchars($shift) ?></div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    <?= ScheduleOptimizer::SHIFTS[$shift]['time'] ?? '' ?>
                                </div>
                            </td>
                            <?php foreach ($days as $day): ?>
                                <?php 
                                    $cellClasses = $matrix[$day][$shift]; 
                                    $hasClasses = !empty($cellClasses);
                                ?>
                                <td class="p-2 border-r border-slate-200 align-top <?= $hasClasses ? 'bg-blue-50/30' : 'bg-slate-50/20' ?>">
                                    <?php if ($hasClasses): ?>
                                        <div class="space-y-1.5">
                                            <?php foreach ($cellClasses as $cls): ?>
                                                <div class="timetable-cell p-2.5 rounded-lg bg-white border border-blue-200 shadow-xs hover:shadow-md transition cursor-pointer" onclick="showScheduleModal(<?= htmlspecialchars(json_encode($cls)) ?>)">
                                                    <div class="font-semibold text-blue-900 line-clamp-1" title="<?= htmlspecialchars($cls['subject_name']) ?>">
                                                        <?= htmlspecialchars($cls['subject_name']) ?>
                                                    </div>
                                                    <div class="text-[11px] text-slate-600 mt-1 flex items-center gap-1">
                                                        <i class="fa-solid fa-user text-slate-400"></i>
                                                        <span class="font-medium truncate"><?= htmlspecialchars($cls['teacher_name']) ?></span>
                                                    </div>
                                                    <div class="text-[10px] text-slate-500 mt-0.5 flex items-center justify-between">
                                                        <span class="font-medium text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                                            <i class="fa-solid fa-door-open"></i> <?= htmlspecialchars($cls['room']) ?>
                                                        </span>
                                                        <span class="text-slate-500 truncate max-w-[90px]" title="<?= htmlspecialchars($cls['branch_name']) ?>">
                                                            <?= htmlspecialchars($cls['branch_name']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="h-16 flex flex-col items-center justify-center rounded border border-dashed border-slate-200 text-slate-400 group hover:border-blue-400 hover:text-blue-600 transition">
                                            <a href="schedules.php?action=create&day=<?= urlencode($day) ?>&shift=<?= urlencode($shift) ?><?= $filterBranch ? '&branch_id=' . $filterBranch : '' ?>" class="text-[11px] font-medium flex items-center gap-1 py-2 px-1 text-center">
                                                <i class="fa-solid fa-plus-circle"></i> Trống (Xếp)
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
        title: `<div class="text-xl font-bold text-slate-900">${data.subject_name}</div>`,
        html: `
            <div class="text-left text-sm space-y-3 mt-3">
                <div class="p-3 bg-blue-50 rounded-lg border border-blue-100 flex items-center justify-between">
                    <span class="font-semibold text-blue-900"><i class="fa-solid fa-calendar-day mr-2"></i> ${data.day_of_week}</span>
                    <span class="text-xs bg-blue-200 text-blue-800 font-bold px-2 py-0.5 rounded">${data.shift_or_periods}</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600"><i class="fa-solid fa-chalkboard-user"></i></div>
                    <div>
                        <div class="text-xs text-slate-500">Giảng viên phụ trách</div>
                        <div class="font-medium text-slate-800">${data.teacher_name} (${data.teacher_expertise || 'N/A'})</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600"><i class="fa-solid fa-building-columns"></i></div>
                    <div>
                        <div class="text-xs text-slate-500">Cơ sở & Địa chỉ</div>
                        <div class="font-medium text-slate-800">${data.branch_name}</div>
                        <div class="text-xs text-slate-500">${data.branch_address}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600"><i class="fa-solid fa-door-open"></i></div>
                    <div>
                        <div class="text-xs text-slate-500">Phòng học</div>
                        <div class="font-bold text-blue-700">${data.room}</div>
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-pen-to-square"></i> Chỉnh sửa',
        confirmButtonColor: '#2563eb',
        cancelButtonText: 'Đóng',
        cancelButtonColor: '#64748b'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `schedules.php?action=edit&id=${data.id}`;
        }
    });
}

// Fetch stats and render Chart.js
document.addEventListener('DOMContentLoaded', function() {
    fetch('api/stats.php')
        .then(res => res.json())
        .then(data => {
            // Render Teacher Load Chart
            const teacherNames = data.teachers.slice(0, 8).map(t => t.full_name);
            const teacherCounts = data.teachers.slice(0, 8).map(t => t.class_count);

            new Chart(document.getElementById('teacherLoadChart'), {
                type: 'bar',
                data: {
                    labels: teacherNames,
                    datasets: [{
                        label: 'Số ca dạy',
                        data: teacherCounts,
                        backgroundColor: 'rgba(59, 130, 246, 0.85)',
                        borderColor: '#2563eb',
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        },
                        x: {
                            ticks: { font: { size: 11 } }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

            // Render Branch Distribution Chart
            const branchNames = data.branches.map(b => b.branch_name);
            const branchCounts = data.branches.map(b => b.class_count);

            new Chart(document.getElementById('branchDistChart'), {
                type: 'doughnut',
                data: {
                    labels: branchNames,
                    datasets: [{
                        data: branchCounts,
                        backgroundColor: [
                            '#3b82f6',
                            '#10b981',
                            '#f59e0b',
                            '#8b5cf6',
                            '#ec4899'
                        ],
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, font: { size: 11 } }
                        }
                    }
                }
            });
        });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
