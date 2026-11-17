<?php
$pageTitle = 'Quản Lý Giảng Viên';
require_once __DIR__ . '/config/database.php';

use App\Models\Teacher;
use App\Models\Schedule;

$teacherModel = new Teacher();
$scheduleModel = new Schedule();

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle Delete
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id && $teacherModel->delete($id)) {
        header('Location: teachers.php?msg=deleted');
        exit;
    } else {
        $error = 'Không thể xóa giảng viên này.';
    }
}

// Handle Form Submit (Create / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $expertise = trim($_POST['expertise'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($fullName) || empty($email) || empty($expertise)) {
        $error = 'Vui lòng nhập đầy đủ họ tên, email và chuyên môn chính!';
    } else {
        try {
            if ($id) {
                $teacherModel->update($id, $fullName, $email, $phone, $expertise, !empty($password) ? $password : null);
                header('Location: teachers.php?msg=updated');
                exit;
            } else {
                $teacherModel->create($fullName, $email, $phone, $expertise, !empty($password) ? $password : 'password123');
                header('Location: teachers.php?msg=created');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Lỗi lưu thông tin: Email có thể đã tồn tại trong hệ thống.';
        }
    }
}

// Flash messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') $success = 'Đã thêm giảng viên mới thành công!';
    if ($_GET['msg'] === 'updated') $success = 'Đã cập nhật thông tin giảng viên thành công!';
    if ($_GET['msg'] === 'deleted') $success = 'Đã xóa giảng viên khỏi hệ thống!';
}

$teachers = $teacherModel->all();

// Pre-fill for edit
$editTeacher = null;
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $editTeacher = $teacherModel->find($id);
    if (!$editTeacher) {
        header('Location: teachers.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-chalkboard-user text-blue-600"></i>
                <?= ($action === 'create' || $action === 'edit') ? ($action === 'create' ? 'Thêm Giảng Viên Mới' : 'Cập Nhật Giảng Viên') : 'Danh Sách Giảng Viên' ?>
            </h1>
            <p class="text-sm text-slate-500">Quản lý đội ngũ giảng viên, chuyên môn giảng dạy và tài khoản đăng nhập cổng cá nhân.</p>
        </div>
        <div>
            <?php if ($action === 'list'): ?>
                <a href="teachers.php?action=create" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                    <i class="fa-solid fa-user-plus"></i> Thêm Giảng Viên Mới
                </a>
            <?php else: ?>
                <a href="teachers.php" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-semibold transition">
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
        <div class="max-w-2xl mx-auto bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-lg font-bold text-slate-900 pb-3 border-b border-slate-100">
                <?= $editTeacher ? 'Chỉnh Sửa Giảng Viên' : 'Thông Tin Giảng Viên Mới' ?>
            </h2>

            <form method="POST" class="space-y-4">
                <?php if ($editTeacher): ?>
                    <input type="hidden" name="id" value="<?= $editTeacher['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Họ và Tên Giảng Viên <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($editTeacher['full_name'] ?? '') ?>" placeholder="Ví dụ: ThS. Nguyễn Gia Bảo" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($editTeacher['email'] ?? '') ?>" placeholder="baong@huit.edu.vn" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Số Điện Thoại
                        </label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($editTeacher['phone'] ?? '') ?>" placeholder="0912345678" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Chuyên Môn Giảng Dạy Chính <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="expertise" required value="<?= htmlspecialchars($editTeacher['expertise'] ?? '') ?>" placeholder="Ví dụ: Lập trình Web & PHP, Cơ sở dữ liệu & Hệ quản trị, Trí tuệ nhân tạo, v.v." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500">
                    <p class="text-[11px] text-slate-500 mt-1">Chuyên môn sẽ được AI Matching so khớp khi phân lịch học phần mới.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Mật Khẩu Đăng Nhập Cổng Giảng Viên
                    </label>
                    <input type="password" name="password" placeholder="<?= $editTeacher ? 'Để trống nếu không muốn đổi mật khẩu' : 'Mặc định: password123' ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="teachers.php" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">Hủy</a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-md transition flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Lưu Thông Tin
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Grid of Teacher Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($teachers as $t): ?>
                <?php 
                    $tSchedules = $scheduleModel->getByTeacher((int)$t['id']);
                    $classCount = count($tSchedules);
                ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                                    <?= mb_substr($t['full_name'], 0, 1, 'UTF-8') ?>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 text-base"><?= htmlspecialchars($t['full_name']) ?></h3>
                                    <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-semibold border border-blue-200 mt-0.5">
                                        <?= htmlspecialchars($t['expertise']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <a href="teachers.php?action=edit&id=<?= $t['id'] ?>" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-slate-100 rounded-lg transition" title="Sửa">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <button onclick="confirmDelete(<?= $t['id'] ?>)" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-slate-100 rounded-lg transition" title="Xóa">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-envelope text-slate-400 w-4"></i>
                                <span class="truncate"><?= htmlspecialchars($t['email']) ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-phone text-slate-400 w-4"></i>
                                <span><?= htmlspecialchars($t['phone'] ?: 'Chưa cập nhật') ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-calendar-check text-slate-400 w-4"></i>
                                <span class="font-semibold text-slate-800"><?= $classCount ?> ca giảng dạy</span> trong tuần
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <a href="index.php?teacher_id=<?= $t['id'] ?>" class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1">
                            <i class="fa-solid fa-eye"></i> Xem lịch dạy
                        </a>
                        <a href="teacher_portal.php?quick_login=<?= $t['id'] ?>" class="text-xs px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200 hover:bg-emerald-100 transition flex items-center gap-1">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Đăng nhập Cổng
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Xóa giảng viên?',
                text: 'Các lịch giảng dạy gắn với giảng viên này cũng sẽ bị xóa tương ứng!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Đồng ý xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `teachers.php?action=delete&id=${id}`;
                }
            });
        }
        </script>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
