<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi" class="h-full bg-slate-50/70 antialiased selection:bg-indigo-500 selection:text-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'HUIT Schedule Hub' ?> - Quản lý Lịch Giảng dạy Thông minh</title>
    
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', '"Inter"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                            950: '#1e1b4b',
                        }
                    },
                    boxShadow: {
                        'xs': '0 1px 2px 0 rgba(0, 0, 0, 0.04)',
                        'card': '0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.03)',
                        'elevated': '0 10px 25px -5px rgba(15, 23, 42, 0.06), 0 8px 10px -6px rgba(15, 23, 42, 0.04)',
                        'glow': '0 0 20px -5px rgba(99, 102, 241, 0.25)',
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif;
            background-color: #f8fafc;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        .timetable-cell:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.08);
        }
    </style>
</head>
<body class="flex flex-col min-h-screen text-slate-800 bg-slate-50/70">

    <!-- Modern SaaS Header (Sticky Glassmorphic Navbar) -->
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-200/80 transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <!-- Left: Brand Logo & Identity -->
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-slate-900 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 group-hover:shadow-indigo-500/30 transition-all duration-200">
                            <i class="fa-solid fa-graduation-cap text-lg"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-base sm:text-lg font-bold text-slate-900 tracking-tight group-hover:text-indigo-600 transition-colors">
                                    HUIT Schedule Hub
                                </span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> v1.0
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-400 font-medium">Hệ thống phân lịch đa cơ sở thông minh</p>
                        </div>
                    </a>
                </div>

                <!-- Center: Navigation Links -->
                <nav class="hidden md:flex items-center space-x-1 lg:space-x-1.5 bg-slate-100/70 p-1 rounded-xl border border-slate-200/60">
                    <a href="index.php" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-1.5 <?= $currentPage === 'index.php' ? 'bg-white text-indigo-700 shadow-xs border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' ?>">
                        <i class="fa-solid fa-chart-pie text-xs <?= $currentPage === 'index.php' ? 'text-indigo-600' : 'text-slate-400' ?>"></i>
                        <span>Tổng quan</span>
                    </a>
                    <a href="schedules.php" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-1.5 <?= $currentPage === 'schedules.php' ? 'bg-white text-indigo-700 shadow-xs border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' ?>">
                        <i class="fa-solid fa-calendar-days text-xs <?= $currentPage === 'schedules.php' ? 'text-indigo-600' : 'text-slate-400' ?>"></i>
                        <span>Lịch giảng dạy</span>
                    </a>
                    <a href="optimize.php" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-1.5 <?= $currentPage === 'optimize.php' ? 'bg-white text-amber-700 shadow-xs border border-amber-200/60' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' ?>">
                        <i class="fa-solid fa-wand-magic-sparkles text-xs text-amber-500"></i>
                        <span>Tối ưu & Tiết trống</span>
                    </a>
                    <a href="teachers.php" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-1.5 <?= $currentPage === 'teachers.php' ? 'bg-white text-indigo-700 shadow-xs border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' ?>">
                        <i class="fa-solid fa-chalkboard-user text-xs <?= $currentPage === 'teachers.php' ? 'text-indigo-600' : 'text-slate-400' ?>"></i>
                        <span>Giảng viên</span>
                    </a>
                    <a href="branches.php" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-1.5 <?= $currentPage === 'branches.php' ? 'bg-white text-indigo-700 shadow-xs border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' ?>">
                        <i class="fa-solid fa-building-columns text-xs <?= $currentPage === 'branches.php' ? 'text-indigo-600' : 'text-slate-400' ?>"></i>
                        <span>Cơ sở</span>
                    </a>
                </nav>

                <!-- Right: Quick Actions (SaaS style) -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <a href="teacher_portal.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-all shadow-xs">
                        <i class="fa-solid fa-id-card text-emerald-600"></i>
                        <span class="hidden sm:inline">Cổng Giảng viên</span>
                    </a>
                    <a href="schedules.php?action=create" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-lg text-white bg-slate-900 hover:bg-indigo-600 shadow-xs hover:shadow-indigo-500/20 active:scale-95 transition-all">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                        <span>Phân lịch mới</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
