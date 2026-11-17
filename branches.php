<?php
$pageTitle = 'Quản Lý Cơ Sở Đào Tạo';
require_once __DIR__ . '/config/database.php';

use App\Models\Branch;
use App\Models\Schedule;

$branchModel = new Branch();
$scheduleModel = new Schedule();

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle Delete
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id && $branchModel->delete($id)) {
        header('Location: branches.php?msg=deleted');
        exit;
    } else {
        $error = 'Không thể xóa cơ sở này.';
    }
}

// Handle Form Submit (Create / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $branchName = trim($_POST['branch_name'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($branchName) || empty($address)) {
        $error = 'Vui lòng nhập đầy đủ tên cơ sở và địa chỉ cụ thể!';
    } else {
        if ($id) {
            $branchModel->update($id, $branchName, $address);
            header('Location: branches.php?msg=updated');
            exit;
        } else {
            $branchModel->create($branchName, $address);
            header('Location: branches.php?msg=created');
            exit;
        }
    }
}

// Flash messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') $success = 'Đã thêm cơ sở mới thành công!';
    if ($_GET['msg'] === 'updated') $success = 'Đã cập nhật cơ sở thành công!';
    if ($_GET['msg'] === 'deleted') $success = 'Đã xóa cơ sở khỏi hệ thống!';
}

$branches = $branchModel->all();

// Pre-fill for edit
$editBranch = null;
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $editBranch = $branchModel->find($id);
    if (!$editBranch) {
        header('Location: branches.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-building-columns text-blue-600"></i>
                <?= ($action === 'create' || $action === 'edit') ? ($action === 'create' ? 'Thêm Cơ Sở Mới' : 'Cập Nhật Cơ Sở') : 'Danh Sách Cơ Sở Đào Tạo' ?>
            </h1>
            <p class="text-sm text-slate-500">Quản lý mạng lưới cơ sở chính, các phân hiệu và trung tâm thực hành thí nghiệm của trường.</p>
        </div>
        <div>
            <?php if ($action === 'list'): ?>
                <a href="branches.php?action=create" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                    <i class="fa-solid fa-plus"></i> Thêm Cơ Sở Mới
                </a>
            <?php else: ?>
                <a href="branches.php" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-semibold transition">
                    <i class="fa-solid fa-arrow-left"></i> Quay Lại Danh Sách
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notifications -->
    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-lg text-red-500"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-lg text-emerald-500"></i>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($action === 'create' || $action === 'edit'): ?>
        <!-- Form Create / Edit -->
        <div class="max-w-xl mx-auto bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-lg font-bold text-slate-900 pb-3 border-b border-slate-100">
                <?= $editBranch ? 'Chỉnh Sửa Cơ Sở' : 'Thông Tin Cơ Sở Mới' ?>
            </h2>

            <form method="POST" class="space-y-4">
                <?php if ($editBranch): ?>
                    <input type="hidden" name="id" value="<?= $editBranch['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Tên Cơ Sở Đào Tạo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="branch_name" required value="<?= htmlspecialchars($editBranch['branch_name'] ?? '') ?>" placeholder="Ví dụ: Cơ sở chính (Tân Phú)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Địa Chỉ Cụ Thể <span class="text-red-500">*</span>
                    </label>
                    <textarea name="address" rows="3" required placeholder="Ví dụ: 140 Lê Trọng Tấn, P. Tây Thạnh, Q. Tân Phú, TP. Hồ Chí Minh" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($editBranch['address'] ?? '') ?></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="branches.php" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">Hủy</a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-md transition flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Lưu Cơ Sở
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Grid of Branches -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <?php foreach ($branches as $b): ?>
                <?php 
                    $bSchedules = $scheduleModel->getByBranch((int)$b['id']);
                    $classCount = count($bSchedules);
                ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-xl font-bold border border-indigo-100">
                                    <i class="fa-solid fa-school-flag"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 text-lg"><?= htmlspecialchars($b['branch_name']) ?></h3>
                                    <span class="inline-flex items-center gap-1 text-xs text-indigo-600 font-semibold mt-0.5">
                                        <i class="fa-solid fa-check-circle"></i> Đang hoạt động
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <a href="branches.php?action=edit&id=<?= $b['id'] ?>" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-slate-100 rounded-lg transition" title="Sửa">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <button onclick="confirmDelete(<?= $b['id'] ?>)" class="p-2 text-slate-400 hover:text-red-600 hover:bg-slate-100 rounded-lg transition" title="Xóa">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-sm text-slate-600">
                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-location-dot text-red-500 mt-1 flex-shrink-0"></i>
                                <span class="leading-relaxed"><?= htmlspecialchars($b['address']) ?></span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <i class="fa-solid fa-calendar-check text-slate-400"></i>
                                <span>Hiện có <strong><?= $classCount ?> lớp học</strong> được xếp lịch tại đây</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <a href="index.php?branch_id=<?= $b['id'] ?>" class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1">
                            <i class="fa-solid fa-filter"></i> Lọc thời khóa biểu cơ sở này
                        </a>
                        <a href="schedules.php?action=create&branch_id=<?= $b['id'] ?>" class="text-xs px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition flex items-center gap-1">
                            <i class="fa-solid fa-plus"></i> Xếp lịch tại đây
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Xóa cơ sở đào tạo?',
                text: 'Các lịch học liên quan đến cơ sở này cũng sẽ bị xóa khỏi hệ thống!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Đồng ý xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `branches.php?action=delete&id=${id}`;
                }
            });
        }
        </script>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
