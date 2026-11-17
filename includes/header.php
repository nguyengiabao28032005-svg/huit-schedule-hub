<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'HUIT Schedule Hub' ?> - Quản lý Lịch Giảng dạy Thông minh</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        huit: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            600: '#1d4ed8',
                            700: '#1e40af',
                            800: '#1e3a8a',
                            900: '#172554'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .timetable-cell:hover { transform: translateY(-2px); transition: all 0.15s ease-in-out; }
    </style>
</head>
<body class="flex flex-col min-h-screen text-slate-800">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Left: Brand -->
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-700 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform">
                            <i class="fa-solid fa-graduation-cap text-xl"></i>
                        </div>
                        <div>
                            <span class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-1.5">
                                HUIT Schedule Hub
                                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-semibold uppercase tracking-wider">Multi-Campus</span>
                            </span>
                            <p class="text-xs text-slate-500">Phân lịch & Tối ưu di chuyển thông minh</p>
                        </div>
                    </a>
                </div>

                <!-- Center: Navigation Links -->
                <nav class="hidden md:flex items-center space-x-1 lg:space-x-2">
                    <a href="index.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $currentPage === 'index.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-chart-pie mr-1.5 text-slate-400 group-hover:text-blue-600"></i> Tổng quan
                    </a>
                    <a href="schedules.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $currentPage === 'schedules.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-calendar-days mr-1.5 text-slate-400"></i> Lịch giảng dạy
                    </a>
                    <a href="optimize.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $currentPage === 'optimize.php' ? 'bg-amber-50 text-amber-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-wand-magic-sparkles mr-1.5 text-amber-500"></i> Tối ưu & Tiết trống
                    </a>
                    <a href="teachers.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $currentPage === 'teachers.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-chalkboard-user mr-1.5 text-slate-400"></i> Giảng viên
                    </a>
                    <a href="branches.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $currentPage === 'branches.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-building-columns mr-1.5 text-slate-400"></i> Cơ sở
                    </a>
                </nav>

                <!-- Right: Quick Actions -->
                <div class="flex items-center space-x-3">
                    <a href="teacher_portal.php" class="inline-flex items-center gap-2 px-3.5 py-1.5 text-sm font-medium rounded-lg text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition shadow-sm">
                        <i class="fa-solid fa-id-card"></i>
                        <span>Cổng Giảng viên</span>
                    </a>
                    <a href="schedules.php?action=create" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition">
                        <i class="fa-solid fa-plus"></i>
                        <span>Phân lịch mới</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
