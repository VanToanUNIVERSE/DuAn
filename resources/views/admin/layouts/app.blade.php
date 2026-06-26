<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — AcademiaLink</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ═══════════════════════════════════════════════════════════
           CLAY DESIGN TOKENS — NOTEBOOK EDITION (Admin)
        ═══════════════════════════════════════════════════════════ */
        :root {
            --canvas:          #fffef7;
            --surface-soft:    #faf8ee;
            --surface-card:    #f5f2e3;
            --surface-strong:  #ebe7d2;
            --surface-dark:    #0f1f1f;
            --surface-dark-el: #182a2a;

            --ink:             #0d0d0d;
            --body-strong:     #1a1a1a;
            --body:            #363636;
            --muted:           #686868;
            --muted-soft:      #9a9a9a;

            --hairline:        #c2bba4;
            --hairline-soft:   #d8d2bc;
            --rule-line:       #cdc7b0;
            --rule-strong:     #b5ae98;

            --brand-pink:      #ff4d8b;
            --brand-teal:      #1a3a3a;
            --brand-ochre:     #e8b94a;
            --brand-mint:      #a4d4c5;
            --brand-coral:     #ff6b5a;

            --success:         #16a34a;
            --warning:         #d97706;
            --error:           #dc2626;

            --sidebar-w: 240px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--canvas);
            color: var(--body);
            min-height: 100vh;
            display: flex;
        }

        /* ── Sidebar ─────────────────────────────────────────────── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--surface-dark);
            color: #e0e0d0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            border-right: 3px solid var(--ink);
        }

        .sidebar-brand {
            padding: 24px 20px 20px;
            border-bottom: 2px solid #2a3a3a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand .logo-mark {
            width: 36px; height: 36px;
            background: var(--brand-pink);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Sora', sans-serif;
            font-weight: 800;
            font-size: 14px;
            color: white;
            border: 2px solid var(--ink);
        }

        .sidebar-brand .brand-text {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 14px;
            color: #f0ede0;
        }

        .sidebar-brand .brand-sub {
            font-size: 10px;
            color: var(--brand-ochre);
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 0;
            overflow-y: auto;
        }

        .nav-section-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--muted-soft);
            padding: 16px 20px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: #b0b09a;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.15s;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.05);
            color: #f0ede0;
            border-left-color: var(--brand-ochre);
        }

        .nav-item.active {
            background: rgba(255,255,255,0.08);
            color: #f0ede0;
            border-left-color: var(--brand-pink);
        }

        .nav-item .nav-icon { font-size: 16px; width: 20px; text-align: center; }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 2px solid #2a3a3a;
            font-size: 12px;
            color: var(--muted-soft);
        }

        .sidebar-footer a {
            color: var(--brand-coral);
            text-decoration: none;
            font-weight: 600;
        }

        /* ── Main Content ─────────────────────────────────────────── */
        .main-wrap {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .top-bar {
            background: var(--surface-card);
            border-bottom: 2px solid var(--hairline);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .top-bar-title {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 16px;
            color: var(--ink);
        }

        .top-bar-breadcrumb {
            font-size: 12px;
            color: var(--muted);
        }

        .top-bar-breadcrumb a { color: var(--brand-teal); text-decoration: none; }

        .top-bar-user {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--body);
        }

        .user-avatar {
            width: 30px; height: 30px;
            background: var(--brand-pink);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 700;
            border: 2px solid var(--ink);
        }

        .content {
            padding: 28px;
            flex: 1;
        }

        /* ── Alert Messages ──────────────────────────────────────── */
        .alert {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-left: 4px solid;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-success { background: #f0fdf4; border-color: var(--success); color: #15803d; }
        .alert-error   { background: #fef2f2; border-color: var(--error);   color: #b91c1c; }

        /* ── Cards ───────────────────────────────────────────────── */
        .card {
            background: var(--surface-card);
            border: 2px solid var(--hairline);
            margin-bottom: 24px;
        }

        .card-header {
            padding: 14px 20px;
            border-bottom: 2px solid var(--hairline);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 14px;
            color: var(--ink);
        }

        .card-body { padding: 20px; }

        /* ── Buttons ─────────────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            border: 2px solid var(--ink);
            text-decoration: none;
            transition: all 0.15s;
        }

        .btn-primary { background: var(--ink); color: white; }
        .btn-primary:hover { background: #333; }

        .btn-secondary { background: var(--surface-card); color: var(--ink); }
        .btn-secondary:hover { background: var(--surface-strong); }

        .btn-danger { background: var(--error); color: white; border-color: var(--error); }
        .btn-danger:hover { background: #b91c1c; }

        .btn-sm { padding: 5px 10px; font-size: 12px; }

        /* ── Tables ──────────────────────────────────────────────── */
        .table-wrap { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            background: var(--surface-strong);
            padding: 10px 14px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--muted);
            border-bottom: 2px solid var(--hairline);
        }

        tbody tr { border-bottom: 1px solid var(--hairline-soft); transition: background 0.1s; }
        tbody tr:hover { background: var(--surface-soft); }

        tbody td {
            padding: 10px 14px;
            font-size: 13px;
            color: var(--body-strong);
            vertical-align: middle;
        }

        /* ── Forms ───────────────────────────────────────────────── */
        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--body-strong);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input[type="text"], input[type="number"], input[type="email"],
        select, textarea {
            width: 100%;
            padding: 9px 12px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: var(--ink);
            background: var(--canvas);
            border: 2px solid var(--hairline);
            outline: none;
            transition: border-color 0.15s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--ink);
        }

        textarea { min-height: 80px; resize: vertical; }

        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }

        .field-error {
            font-size: 12px;
            color: var(--error);
            margin-top: 4px;
        }

        /* ── Badge ───────────────────────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
            border: 1.5px solid currentColor;
        }
        .badge-teal   { color: #0f766e; background: #f0fdfa; }
        .badge-pink   { color: #be185d; background: #fdf2f8; }
        .badge-ochre  { color: #92400e; background: #fffbeb; }
        .badge-mint   { color: #065f46; background: #ecfdf5; }
        .badge-coral  { color: #9f1239; background: #fff1f2; }
        .badge-muted  { color: var(--muted); background: var(--surface-card); }

        /* ── Pagination ───────────────────────────────────────────── */
        .pagination { display: flex; gap: 4px; margin-top: 20px; flex-wrap: wrap; }
        .pagination a, .pagination span {
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 600;
            border: 2px solid var(--hairline);
            text-decoration: none;
            color: var(--body);
        }
        .pagination a:hover { background: var(--surface-strong); }
        .pagination .active span { background: var(--ink); color: white; border-color: var(--ink); }

        /* ── Search/Filter Bar ───────────────────────────────────── */
        .filter-bar {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding: 16px;
            background: var(--surface-soft);
            border: 2px solid var(--hairline);
        }

        .filter-bar .form-group { margin-bottom: 0; flex: 1; min-width: 160px; }

        /* ── Stat Cards ──────────────────────────────────────────── */
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }

        .stat-card {
            background: var(--surface-card);
            border: 2px solid var(--hairline);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
        }

        .stat-card.pink::before  { background: var(--brand-pink); }
        .stat-card.teal::before  { background: var(--brand-teal); }
        .stat-card.ochre::before { background: var(--brand-ochre); }
        .stat-card.mint::before  { background: var(--brand-mint); }

        .stat-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
        }

        .stat-value {
            font-family: 'Sora', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: var(--ink);
            line-height: 1;
        }

        .stat-icon {
            position: absolute;
            right: 16px; bottom: 16px;
            font-size: 28px;
            opacity: 0.15;
        }

        /* ── Responsive ───────────────────────────────────────────── */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-wrap { margin-left: 0; }
            .stat-grid { grid-template-columns: 1fr 1fr; }
            .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- SIDEBAR --}}
<nav class="sidebar">
    <div class="sidebar-brand">
        <div class="logo-mark">AL</div>
        <div>
            <div class="brand-text">AcademiaLink</div>
            <div class="brand-sub">Admin Panel</div>
        </div>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section-label">Tổng quan</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <span class="nav-icon">📊</span> Dashboard
        </a>

        <div class="nav-section-label">Quản lý</div>
        <a href="{{ route('admin.subjects.index') }}" class="nav-item {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}">
            <span class="nav-icon">📚</span> Môn học
        </a>
        <a href="{{ route('admin.skill-groups.index') }}" class="nav-item {{ request()->routeIs('admin.skill-groups.*') ? 'active' : '' }}">
            <span class="nav-icon">🎯</span> Skill Groups
        </a>
        <a href="{{ route('admin.program-groups.index') }}" class="nav-item {{ request()->routeIs('admin.program-groups.*') ? 'active' : '' }}">
            <span class="nav-icon">🗂️</span> Program Groups
        </a>
        <a href="{{ route('admin.training-programs.index') }}" class="nav-item {{ request()->routeIs('admin.training-programs.*') ? 'active' : '' }}">
            <span class="nav-icon">🎓</span> Chương trình đào tạo
        </a>
        <a href="{{ route('admin.curriculum.index') }}" class="nav-item {{ request()->routeIs('admin.curriculum.*') ? 'active' : '' }}">
            <span class="nav-icon">📅</span> Phân công môn học
        </a>

        <div class="nav-section-label">Báo cáo</div>
        <a href="{{ route('admin.enrollment-stats.index') }}" class="nav-item {{ request()->routeIs('admin.enrollment-stats.*') ? 'active' : '' }}">
            <span class="nav-icon">📈</span> Thống kê đăng ký HP
        </a>

        <div class="nav-section-label">Hệ thống</div>
        <a href="{{ route('suggest') }}" class="nav-item">
            <span class="nav-icon">↩️</span> Về trang chính
        </a>
    </div>

    <div class="sidebar-footer">
        Đăng nhập: <strong>{{ auth()->user()->fullName ?? auth()->user()->username }}</strong><br>
        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
            @csrf
            <a href="#" onclick="this.closest('form').submit()">Đăng xuất</a>
        </form>
    </div>
</nav>

{{-- MAIN CONTENT --}}
<div class="main-wrap">
    <div class="top-bar">
        <div>
            <div class="top-bar-title">@yield('page-title', 'Admin')</div>
            <div class="top-bar-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Admin</a>
                @yield('breadcrumb')
            </div>
        </div>
        <div class="top-bar-user">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->fullName ?? auth()->user()->username, 0, 1)) }}</div>
            {{ auth()->user()->fullName ?? auth()->user()->username }}
        </div>
    </div>

    <div class="content">
        @if(session('success'))
            <div class="alert alert-success">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">❌ {{ session('error') }}</div>
        @endif

        @yield('content')
    </div>
</div>

@stack('scripts')
</body>
</html>
