<?php
$pageTitle = 'Quản Lý Lịch Dạy & Phân Công Thông Minh';
require_once __DIR__ . '/config/database.php';

use App\Models\Branch;
use App\Models\Teacher;
use App\Models\Schedule;
use App\Services\ScheduleOptimizer;

$branchModel = new Branch();
$teacherModel = new Teacher();
$scheduleModel = new Schedule();
$optimizer = new ScheduleOptimizer();

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle Delete
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id && $scheduleModel->delete($id)) {
        header('Location: schedules.php?msg=deleted');
        exit;
    } else {
        $error = 'Không thể xóa lịch dạy này.';
    }
}

// Handle Form Submit (Create or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $branchId = (int)($_POST['branch_id'] ?? 0);
    $subjectName = trim($_POST['subject_name'] ?? '');
    $room = trim($_POST['room'] ?? '');
    $dayOfWeek = trim($_POST['day_of_week'] ?? '');
    $shiftOrPeriods = trim($_POST['shift_or_periods'] ?? '');

    if (empty($teacherId) || empty($branchId) || empty($subjectName) || empty($room) || empty($dayOfWeek) || empty($shiftOrPeriods)) {
        $error = 'Vui lòng điền đầy đủ tất cả các trường thông tin!';
    } else {
        // Validate conflicts
        $validation = $optimizer->validateSchedule($teacherId, $branchId, $room, $dayOfWeek, $shiftOrPeriods, $id);
        if (!$validation['valid']) {
            $error = implode('<br>', $validation['errors']);
        } else {
            $data = [
                'teacher_id' => $teacherId,
                'branch_id' => $branchId,
                'subject_name' => $subjectName,
                'room' => $room,
                'day_of_week' => $dayOfWeek,
                'shift_or_periods' => $shiftOrPeriods
            ];

            if ($id) {
                $scheduleModel->update($id, $data);
                header('Location: schedules.php?msg=updated');
                exit;
            } else {
                $scheduleModel->create($data);
                header('Location: schedules.php?msg=created');
                exit;
            }
        }
    }
}

// Flash messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') $success = 'Đã thêm lịch giảng dạy mới thành công!';
    if ($_GET['msg'] === 'updated') $success = 'Đã cập nhật lịch giảng dạy thành công!';
    if ($_GET['msg'] === 'deleted') $success = 'Đã xóa lịch giảng dạy khỏi hệ thống!';
}

$allBranches = $branchModel->all();
$allTeachers = $teacherModel->all();
$shifts = array_keys(ScheduleOptimizer::SHIFTS);
$days = ScheduleOptimizer::DAYS;

// Pre-fill for edit
$editItem = null;
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $editItem = $scheduleModel->find($id);
    if (!$editItem) {
        header('Location: schedules.php');
        exit;
    }
}

// Parameters from query string for prefilling (e.g. from empty cell click)
$prefillDay = $_GET['day'] ?? ($editItem['day_of_week'] ?? 'Thứ 2');
$prefillShift = $_GET['shift'] ?? ($editItem['shift_or_periods'] ?? $shifts[0]);
$prefillBranch = $_GET['branch_id'] ?? ($editItem['branch_id'] ?? ($allBranches[0]['id'] ?? ''));

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">

    <!-- Header bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-calendar-days text-blue-600"></i>
                <?= ($action === 'create' || $action === 'edit') ? ($action === 'create' ? 'Phân Lịch Giảng Dạy Mới' : 'Cập Nhật Lịch Giảng Dạy') : 'Danh Sách Lịch Giảng Dạy' ?>
            </h1>
            <p class="text-sm text-slate-500">
                Quản lý lịch học, áp dụng thuật toán kiểm tra trùng lịch và gợi ý giảng viên chuyên môn thông minh.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($action === 'list'): ?>
                <a href="schedules.php?action=create" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                    <i class="fa-solid fa-plus"></i> Thêm Lịch Mới
                </a>
                <a href="optimize.php" class="inline-flex items-center gap-2 px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-sm font-semibold transition">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Phân Tích Tiết Trống
                </a>
            <?php else: ?>
                <a href="schedules.php" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-semibold transition">
                    <i class="fa-solid fa-arrow-left"></i> Quay Lại Danh Sách
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert notifications -->
    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-start gap-3 shadow-sm">
            <i class="fa-solid fa-triangle-exclamation text-lg text-red-500 mt-0.5"></i>
            <div>
                <p class="font-bold">Không thể lưu lịch dạy:</p>
                <p class="mt-1 leading-relaxed"><?= $error ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3 shadow-sm">
            <i class="fa-solid fa-circle-check text-lg text-emerald-500"></i>
            <span class="font-medium"><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($action === 'create' || $action === 'edit'): ?>
        <!-- Form Create / Edit with Smart Suggestion Engine -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Left: Form inputs -->
            <div class="lg:col-span-7 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                <h2 class="text-lg font-bold text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                    <i class="fa-solid fa-pen-nib text-blue-600"></i> Thông Tin Lịch Giảng Dạy
                </h2>

                <form method="POST" id="scheduleForm" class="space-y-4">
                    <?php if ($editItem): ?>
                        <input type="hidden" name="id" id="schedule_id" value="<?= $editItem['id'] ?>">
                    <?php endif; ?>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Tên Học Phần / Môn Học <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="subject_name" id="subject_name" required
                               value="<?= htmlspecialchars($editItem['subject_name'] ?? ($_GET['subject'] ?? '')) ?>"
                               placeholder="Ví dụ: Lập trình Web với PHP & Laravel, Hệ quản trị CSDL MySQL, v.v."
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <p class="text-[11px] text-slate-500 mt-1">
                            <i class="fa-solid fa-lightbulb text-amber-500 mr-1"></i> Hệ thống sẽ phân tích từ khóa tên môn để gợi ý giảng viên có chuyên môn tương ứng.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Cơ Sở Đào Tạo <span class="text-red-500">*</span>
                            </label>
                            <select name="branch_id" id="branch_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <?php foreach ($allBranches as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= (int)$prefillBranch === (int)$b['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['branch_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Phòng Học <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="room" id="room" required
                                   value="<?= htmlspecialchars($editItem['room'] ?? 'A201') ?>"
                                   placeholder="Ví dụ: A201, PM-02, LAB-03"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Thứ Giảng Dạy <span class="text-red-500">*</span>
                            </label>
                            <select name="day_of_week" id="day_of_week" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <?php foreach ($days as $d): ?>
                                    <option value="<?= $d ?>" <?= $prefillDay === $d ? 'selected' : '' ?>><?= $d ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Buổi / Ca Học <span class="text-red-500">*</span>
                            </label>
                            <select name="shift_or_periods" id="shift_or_periods" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <?php foreach ($shifts as $s): ?>
                                    <option value="<?= $s ?>" <?= $prefillShift === $s ? 'selected' : '' ?>>
                                        <?= $s ?> (<?= ScheduleOptimizer::SHIFTS[$s]['time'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Giảng Viên Phụ Trách <span class="text-red-500">*</span>
                            </label>
                            <span id="conflictStatus" class="text-xs font-semibold"></span>
                        </div>
                        <select name="teacher_id" id="teacher_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Chọn giảng viên hoặc chọn nhanh từ khung gợi ý bên phải --</option>
                            <?php foreach ($allTeachers as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= (isset($editItem) && (int)$editItem['teacher_id'] === (int)$t['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['full_name']) ?> — Chuyên môn: <?= htmlspecialchars($t['expertise']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="conflictAlertBox" class="hidden p-3.5 rounded-xl text-xs space-y-1"></div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <a href="schedules.php" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">
                            Hủy
                        </a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-md transition flex items-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <?= $editItem ? 'Lưu Thay Đổi' : 'Xác Nhận Phân Lịch' ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right: Smart Recommendation Engine Panel -->
            <div class="lg:col-span-5 space-y-4">
                <div class="bg-gradient-to-br from-indigo-900 to-blue-900 text-white p-5 rounded-2xl shadow-md">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-base flex items-center gap-2">
                            <i class="fa-solid fa-brain text-amber-300"></i> AI Smart Suggester
                        </h3>
                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-blue-800/80 border border-blue-600 text-blue-200">
                            Gợi ý thông minh
                        </span>
                    </div>
                    <p class="text-xs text-blue-100 mt-1">
                        Hệ thống tự động lọc giảng viên phù hợp chuyên môn, trống lịch, và tối ưu quãng đường di chuyển giữa các cơ sở.
                    </p>
                </div>

                <!-- Recommendation Cards Container -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3 min-h-[380px]">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Danh Sách Giảng Viên Gợi Ý</span>
                        <span id="recommendCount" class="text-xs font-semibold text-blue-600">Đang quét...</span>
                    </div>

                    <div id="recommendationList" class="space-y-2.5 max-h-[500px] overflow-y-auto pr-1">
                        <div class="text-center py-10 text-slate-400 text-xs">
                            <i class="fa-solid fa-spinner fa-spin text-xl mb-2 text-blue-500"></i>
                            <p>Đang tìm kiếm giảng viên tối ưu nhất...</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <script>
        // Real-time recommendation and conflict check
        function updateRecommendations() {
            const subject = document.getElementById('subject_name').value;
            const day = document.getElementById('day_of_week').value;
            const shift = document.getElementById('shift_or_periods').value;
            const branchId = document.getElementById('branch_id').value;
            const recList = document.getElementById('recommendationList');
            const recCount = document.getElementById('recommendCount');

            recCount.innerText = 'Đang quét...';

            fetch(`api/recommend_teacher.php?subject_name=${encodeURIComponent(subject)}&day_of_week=${encodeURIComponent(day)}&shift_or_periods=${encodeURIComponent(shift)}&branch_id=${branchId}`)
                .then(res => res.json())
                .then(resp => {
                    if (resp.status !== 'success') {
                        recList.innerHTML = `<div class="p-3 text-xs text-amber-700 bg-amber-50 rounded-lg">${resp.message}</div>`;
                        return;
                    }

                    recCount.innerText = `${resp.data.length} ứng viên`;

                    let html = '';
                    resp.data.forEach(t => {
                        const isAvailable = t.is_available;
                        const cardBg = isAvailable ? 'bg-slate-50 hover:bg-blue-50/60 border-slate-200' : 'bg-red-50/40 border-red-200 opacity-60';
                        const badgeColor = t.score >= 80 ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : (t.score >= 60 ? 'bg-blue-100 text-blue-800 border-blue-300' : 'bg-slate-200 text-slate-700');

                        html += `
                            <div class="p-3 rounded-xl border ${cardBg} transition text-xs space-y-1.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                                            ${t.full_name}
                                            ${isAvailable ? `<span class="text-[10px] px-1.5 py-0.5 rounded-full border font-semibold ${badgeColor}">${t.status_badge}</span>` : `<span class="text-[10px] px-1.5 py-0.5 rounded-full border font-semibold bg-red-100 text-red-800 border-red-300">Đã bận</span>`}
                                        </div>
                                        <div class="text-slate-500 text-[11px] mt-0.5">
                                            <i class="fa-solid fa-graduation-cap text-slate-400"></i> ${t.expertise}
                                        </div>
                                    </div>
                                    ${isAvailable ? `
                                        <button type="button" onclick="selectTeacher(${t.teacher_id})" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                                            Chọn
                                        </button>
                                    ` : `
                                        <button type="button" disabled class="px-2.5 py-1 bg-slate-200 text-slate-400 rounded-lg text-xs cursor-not-allowed">
                                            Bận
                                        </button>
                                    `}
                                </div>

                                <div class="text-[11px] ${t.travel_status === 'optimal' ? 'text-emerald-700 font-medium' : (t.travel_status === 'warning' ? 'text-amber-700 font-medium' : 'text-slate-500')}">
                                    ${t.travel_note}
                                </div>

                                ${!isAvailable ? `
                                    <div class="text-[11px] text-red-600 font-medium">
                                        <i class="fa-solid fa-circle-xmark mr-1"></i> ${t.status_message}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    });

                    recList.innerHTML = html;
                })
                .catch(err => {
                    recList.innerHTML = `<div class="p-3 text-xs text-red-600">Lỗi kết nối bộ lọc gợi ý.</div>`;
                });
        }

        function selectTeacher(id) {
            const select = document.getElementById('teacher_id');
            select.value = id;
            checkConflicts();
        }

        function checkConflicts() {
            const teacherId = document.getElementById('teacher_id').value;
            const branchId = document.getElementById('branch_id').value;
            const room = document.getElementById('room').value;
            const day = document.getElementById('day_of_week').value;
            const shift = document.getElementById('shift_or_periods').value;
            const excludeElem = document.getElementById('schedule_id');
            const excludeId = excludeElem ? excludeElem.value : '';

            const statusElem = document.getElementById('conflictStatus');
            const alertBox = document.getElementById('conflictAlertBox');

            if (!teacherId || !room) {
                statusElem.innerHTML = '';
                alertBox.classList.add('hidden');
                return;
            }

            fetch(`api/check_conflict.php?teacher_id=${teacherId}&branch_id=${branchId}&room=${encodeURIComponent(room)}&day_of_week=${encodeURIComponent(day)}&shift_or_periods=${encodeURIComponent(shift)}&exclude_id=${excludeId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.valid) {
                        statusElem.innerHTML = '<span class="text-emerald-600 font-bold"><i class="fa-solid fa-circle-check"></i> Hợp lệ, không trùng lịch</span>';
                        if (data.warnings && data.warnings.length > 0) {
                            alertBox.className = 'p-3 rounded-xl text-xs bg-amber-50 border border-amber-200 text-amber-800';
                            alertBox.innerHTML = data.warnings.map(w => `<p>${w}</p>`).join('');
                            alertBox.classList.remove('hidden');
                        } else {
                            alertBox.classList.add('hidden');
                        }
                    } else {
                        statusElem.innerHTML = '<span class="text-red-600 font-bold"><i class="fa-solid fa-circle-xmark"></i> Xung đột lịch dạy!</span>';
                        alertBox.className = 'p-3 rounded-xl text-xs bg-red-50 border border-red-200 text-red-800';
                        alertBox.innerHTML = data.errors.map(e => `<p class="font-medium">${e}</p>`).join('');
                        alertBox.classList.remove('hidden');
                    }
                });
        }

        // Attach listeners
        ['subject_name', 'day_of_week', 'shift_or_periods', 'branch_id'].forEach(id => {
            document.getElementById(id).addEventListener('change', () => {
                updateRecommendations();
                checkConflicts();
            });
        });
        document.getElementById('subject_name').addEventListener('input', updateRecommendations);
        document.getElementById('teacher_id').addEventListener('change', checkConflicts);
        document.getElementById('room').addEventListener('input', checkConflicts);

        // Initial trigger
        document.addEventListener('DOMContentLoaded', () => {
            updateRecommendations();
            checkConflicts();
        });
        </script>

    <?php else: ?>
        <!-- List View of Schedules with Quick Filters -->
        <?php
            $filterBranch = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null;
            $filterTeacher = isset($_GET['teacher_id']) && $_GET['teacher_id'] !== '' ? (int)$_GET['teacher_id'] : null;
            $filterDay = isset($_GET['day_of_week']) && $_GET['day_of_week'] !== '' ? $_GET['day_of_week'] : null;
            $search = trim($_GET['search'] ?? '');

            $filters = [];
            if ($filterBranch) $filters['branch_id'] = $filterBranch;
            if ($filterTeacher) $filters['teacher_id'] = $filterTeacher;
            if ($filterDay) $filters['day_of_week'] = $filterDay;
            if ($search) $filters['search'] = $search;

            $schedulesList = $scheduleModel->all($filters);
        ?>

        <!-- Filter and Search Header -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <form method="GET" action="schedules.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Tìm kiếm:</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Môn học, phòng, GV..." class="w-full text-xs rounded-lg border-slate-300 border p-2 bg-slate-50">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Cơ sở:</label>
                    <select name="branch_id" class="w-full text-xs rounded-lg border-slate-300 border p-2 bg-slate-50">
                        <option value="">-- Tất cả cơ sở --</option>
                        <?php foreach ($allBranches as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= $filterBranch === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['branch_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Giảng viên:</label>
                    <select name="teacher_id" class="w-full text-xs rounded-lg border-slate-300 border p-2 bg-slate-50">
                        <option value="">-- Tất cả giảng viên --</option>
                        <?php foreach ($allTeachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $filterTeacher === (int)$t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Thứ:</label>
                    <select name="day_of_week" class="w-full text-xs rounded-lg border-slate-300 border p-2 bg-slate-50">
                        <option value="">-- Tất cả các ngày --</option>
                        <?php foreach ($days as $d): ?>
                            <option value="<?= $d ?>" <?= $filterDay === $d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs py-2 px-3 rounded-lg shadow-sm transition flex items-center justify-center gap-1">
                        <i class="fa-solid fa-filter"></i> Lọc
                    </button>
                    <a href="schedules.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs py-2 px-3 rounded-lg transition flex items-center justify-center">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Table View -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 font-semibold border-b border-slate-200">
                            <th class="p-3 w-12 text-center">STT</th>
                            <th class="p-3">Học Phần / Môn Học</th>
                            <th class="p-3">Giảng Viên</th>
                            <th class="p-3">Cơ Sở Đào Tạo</th>
                            <th class="p-3">Phòng</th>
                            <th class="p-3">Thời Gian</th>
                            <th class="p-3 text-center w-28">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($schedulesList)): ?>
                            <tr>
                                <td colspan="7" class="p-8 text-center text-slate-400">
                                    <i class="fa-solid fa-calendar-xmark text-3xl mb-2 text-slate-300"></i>
                                    <p>Không tìm thấy lịch giảng dạy nào phù hợp với bộ lọc.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($schedulesList as $idx => $s): ?>
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="p-3 text-center text-slate-400 font-medium"><?= $idx + 1 ?></td>
                                    <td class="p-3">
                                        <div class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($s['subject_name']) ?></div>
                                        <div class="text-[11px] text-slate-400">Mã lịch: #<?= $s['id'] ?></div>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-semibold text-slate-800"><?= htmlspecialchars($s['teacher_name']) ?></div>
                                        <div class="text-[11px] text-slate-500"><i class="fa-solid fa-envelope text-slate-400 mr-1"></i><?= htmlspecialchars($s['teacher_email']) ?></div>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-medium text-slate-800"><?= htmlspecialchars($s['branch_name']) ?></div>
                                        <div class="text-[10px] text-slate-400 truncate max-w-[200px]" title="<?= htmlspecialchars($s['branch_address']) ?>">
                                            <?= htmlspecialchars($s['branch_address']) ?>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            <i class="fa-solid fa-door-closed"></i> <?= htmlspecialchars($s['room']) ?>
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-bold text-indigo-700"><?= htmlspecialchars($s['day_of_week']) ?></div>
                                        <div class="text-[11px] text-slate-600"><?= htmlspecialchars($s['shift_or_periods']) ?></div>
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <a href="schedules.php?action=edit&id=<?= $s['id'] ?>" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition" title="Sửa">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?= $s['id'] ?>)" class="p-1.5 text-red-600 hover:bg-red-50 rounded transition" title="Xóa">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Xóa lịch giảng dạy?',
                text: 'Hành động này sẽ xóa lịch học khỏi hệ thống và không thể khôi phục lại.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Đồng ý xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `schedules.php?action=delete&id=${id}`;
                }
            });
        }
        </script>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
