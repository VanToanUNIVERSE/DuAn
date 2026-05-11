?<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gợi Ý Đăng Ký Môn Học - Hỗ Trợ Học Tập</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* CSS Reset & Variable Tokens */
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --panel-bg: rgba(30, 41, 59, 0.7);
            --panel-border: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.3);
            --secondary: #a855f7;
            --accent-success: #10b981;
            --accent-success-glow: rgba(16, 185, 129, 0.2);
            --accent-warning: #f59e0b;
            --radius-lg: 16px;
            --radius-md: 10px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 2rem 1rem;
            overflow-x: hidden;
            background-attachment: fixed;
        }

        h1, h2, h3, h4 {
            font-family: 'Outfit', sans-serif;
        }

        /* Container & Layout */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        header::after {
            content: '';
            position: absolute;
            top: -100px;
            left: 50%;
            transform: translateX(-50%);
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
            z-index: -1;
            pointer-events: none;
        }

        .logo-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #818cf8;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            animation: pulse 2s infinite;
        }

        header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(to right, #ffffff, #c7d2fe, #f472b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
        }

        header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Main Grid */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Cards & Glassmorphism */
        .glass-card {
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: var(--radius-lg);
            padding: 2rem;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            margin-bottom: 2rem;
            transition: var(--transition);
        }

        .glass-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 15px 35px rgba(99, 102, 241, 0.05);
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding-bottom: 1rem;
            color: #fff;
        }

        .card-title svg {
            color: var(--primary);
            width: 24px;
            height: 24px;
        }

        /* Dropdowns & Forms */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .input-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .form-select {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md);
            color: #fff;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            font-weight: 500;
            outline: none;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
        }

        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px var(--primary-glow);
        }

        /* Checklist Subjects */
        .semester-group {
            margin-bottom: 1.5rem;
        }

        .semester-header {
            font-size: 1rem;
            font-weight: 700;
            color: #818cf8;
            margin-bottom: 0.75rem;
            background: rgba(99, 102, 241, 0.05);
            padding: 0.4rem 0.8rem;
            border-radius: var(--radius-md);
            display: inline-block;
        }

        .subjects-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        @media (max-width: 640px) {
            .subjects-list {
                grid-template-columns: 1fr;
            }
        }

        .subject-grade-card {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            transition: var(--transition);
        }

        .subject-grade-card:hover {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .subject-grade-card.pass {
            border-color: var(--accent-success);
            background: rgba(16, 185, 129, 0.08);
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.15);
        }

        .subject-grade-card.fail {
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.07);
        }

        .grade-input-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.2rem;
            flex-shrink: 0;
        }

        .grade-input {
            width: 56px;
            background: rgba(255,255,255,0.06);
            border: 1.5px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            text-align: center;
            padding: 0.3rem 0.2rem;
            outline: none;
            transition: var(--transition);
        }

        .grade-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 8px var(--primary-glow);
        }

        .grade-input.is-pass { border-color: var(--accent-success); color: #6ee7b7; }
        .grade-input.is-fail { border-color: #ef4444; color: #fca5a5; }

        .grade-status-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .grade-status-label.pass { color: var(--accent-success); }
        .grade-status-label.fail { color: #f87171; }
        .grade-status-label.empty { color: var(--text-secondary); }

        .subject-info {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .subject-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: #fff;
        }

        .subject-meta {
            font-size: 0.75rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .subject-badge {
            background: rgba(255, 255, 255, 0.08);
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
            font-size: 0.7rem;
        }

        /* Right Column Results */
        .results-container {
            position: sticky;
            top: 2rem;
        }

        .loader {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 0;
            gap: 1.5rem;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s linear infinite;
        }

        .suggestions-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .suggestion-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
            animation: fadeIn 0.4s ease-out;
        }

        .suggestion-card:hover {
            transform: translateX(5px);
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: -4px 0 0 var(--primary);
        }

        .suggestion-details {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .suggestion-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
        }

        .suggestion-tags {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .tag {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
            border-radius: 50px;
            text-transform: uppercase;
        }

        .tag-credits {
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .tag-type {
            background: rgba(168, 85, 247, 0.15);
            color: #d8b4fe;
            border: 1px solid rgba(168, 85, 247, 0.3);
        }

        .tag-group {
            background: rgba(245, 158, 11, 0.1);
            color: #fde047;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .suggestion-right {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.4);
            color: #a5b4fc;
            border-radius: 50px;
            padding: 0.3rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
        }
        .btn-add:hover {
            background: rgba(99, 102, 241, 0.3);
            color: #fff;
            border-color: var(--primary);
        }
        .btn-add.added {
            background: rgba(16, 185, 129, 0.15);
            border-color: var(--accent-success);
            color: #6ee7b7;
            pointer-events: none;
        }

        /* Panel Môn Đang Học */
        .current-courses-empty {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .current-course-item {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: var(--radius-md);
            padding: 0.85rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            transition: var(--transition);
            animation: fadeIn 0.3s ease-out;
        }
        .current-course-item.cc-pass {
            border-color: var(--accent-success);
            background: rgba(16,185,129,0.07);
        }
        .current-course-item.cc-fail {
            border-color: #ef4444;
            background: rgba(239,68,68,0.06);
        }
        .current-course-info { display:flex; flex-direction:column; gap:0.2rem; flex:1; min-width:0; }
        .current-course-name { font-weight:700; font-size:0.95rem; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .current-course-meta { font-size:0.75rem; color:var(--text-secondary); }
        .current-course-right { display:flex; align-items:center; gap:0.6rem; flex-shrink:0; }
        .cc-grade-input {
            width: 60px;
            background: rgba(255,255,255,0.06);
            border: 1.5px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            color:#fff;
            font-size:0.95rem;
            font-weight:700;
            text-align:center;
            padding: 0.3rem 0.2rem;
            outline:none;
            transition: var(--transition);
        }
        .cc-grade-input:focus { border-color:var(--primary); box-shadow:0 0 6px var(--primary-glow); }
        .cc-grade-input.is-pass { border-color:var(--accent-success); color:#6ee7b7; }
        .cc-grade-input.is-fail { border-color:#ef4444; color:#fca5a5; }
        .cc-status { font-size:0.7rem; font-weight:700; text-transform:uppercase; min-width:40px; text-align:center; }
        .cc-status.pass { color:var(--accent-success); }
        .cc-status.fail { color:#f87171; }
        .cc-status.empty { color:var(--text-secondary); }
        .btn-remove {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #f87171;
            border-radius: 6px;
            width:28px; height:28px;
            display:flex; align-items:center; justify-content:center;
            cursor:pointer;
            font-size:1rem;
            line-height:1;
            transition: var(--transition);
            flex-shrink:0;
        }
        .btn-remove:hover { background:rgba(239,68,68,0.25); color:#fff; }

        /* Trạng thái Đang học — khóa ô nhập điểm bên trái */
        .grade-input.is-studying {
            opacity: 0;
            pointer-events: none;
            position: absolute;
        }
        .studying-label {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.72rem;
            font-weight: 700;
            color: #818cf8;
            background: rgba(99,102,241,0.12);
            border: 1px solid rgba(99,102,241,0.3);
            border-radius: 6px;
            padding: 0.25rem 0.6rem;
            white-space: nowrap;
            animation: pulse-studying 2s ease-in-out infinite;
        }
        @keyframes pulse-studying {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.6; }
        }
        .grade-input-wrap.is-locked {
            position: relative;
            display: flex;
            align-items: center;
        }

        .btn-complete {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: linear-gradient(135deg, var(--accent-success), #059669);
            border: none;
            color: #fff;
            border-radius: 50px;
            padding: 0.45rem 1rem;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            margin-left: auto;
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
            white-space: nowrap;
        }
        .btn-complete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(16,185,129,0.45);
        }
        .btn-complete:disabled {
            background: rgba(255,255,255,0.08);
            color: var(--text-secondary);
            box-shadow: none;
            cursor: not-allowed;
            transform: none;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 9999;
            padding: 0.9rem 1.4rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.9rem;
            animation: slideUp 0.3s ease-out;
            backdrop-filter: blur(12px);
            max-width: 340px;
        }
        .toast.success {
            background: rgba(16,185,129,0.15);
            border: 1px solid var(--accent-success);
            color: #6ee7b7;
        }
        .toast.error {
            background: rgba(239,68,68,0.12);
            border: 1px solid #ef4444;
            color: #fca5a5;
        }
        @keyframes slideUp {
            from { opacity:0; transform: translateY(20px); }
            to   { opacity:1; transform: translateY(0); }
        }

        .counter-badge {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            background: var(--primary);
            color:#fff;
            border-radius:50%;
            width:20px; height:20px;
            font-size:0.7rem;
            font-weight:800;
            margin-left:0.4rem;
        }

        /* Stats Card */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-top: 1.25rem;
        }
        @media (max-width: 640px) { .stats-grid { grid-template-columns: 1fr 1fr; } }

        .stat-item {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: var(--radius-md);
            padding: 0.85rem 1rem;
            text-align: center;
            transition: var(--transition);
        }
        .stat-item:hover { border-color: rgba(255,255,255,0.15); }
        .stat-value {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1.1;
            background: linear-gradient(135deg, #fff 0%, #c7d2fe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-value.highlight {
            background: linear-gradient(135deg, var(--accent-success), #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-label {
            font-size: 0.72rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 0.25rem;
        }

        .semester-badge {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff;
            padding: 0.35rem 0.85rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        }

        .distance-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .empty-state svg {
            width: 60px;
            height: 60px;
            color: rgba(255, 255, 255, 0.1);
        }

        .empty-state h3 {
            color: #fff;
            font-size: 1.2rem;
            font-weight: 600;
        }

        /* Animations */
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div class="logo-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5c0 .538-.012 1.05-.034 1.536a3 3 0 1 1-1.133 5.89c-.011.121-.011.234-.011.33v3.244c0 .852-.149 1.658-.439 2.4H3.083c-.29-.742-.439-1.548-.439-2.4V7.926c0-.096 0-.21-.012-.33a3 3 0 1 1-1.132-5.89A35 35 0 0 1 2.5.5zm0 1.25C2.41 2.347 2.33 3.012 2.3 3.652a2 2 0 1 0 1.95 0c-.03-.64-.11-1.305-.182-1.902h-1.57zm11 0h-1.57c.072.597.152 1.262.182 1.902A2 2 0 1 0 13.7 3.652c-.03-.64-.11-1.305-.2-2.402z"/>
            </svg>
            Smart Planner
        </div>
        <h1>Gợi Ý Học Tập Thông Minh</h1>
        <p>Chọn chương trình, học kỳ mong muốn và tích chọn các môn học bạn đã thi đỗ để nhận ngay lộ trình đề xuất tối ưu nhất thời gian thực.</p>
    </header>

    <div class="main-grid">
        <!-- Cột Bên Trái: Cấu Hình và Bộ Chọn Môn Đã Đỗ -->
        <div>
            <!-- Card 1: Chọn Khung Chương Trình -->
            <div class="glass-card">
                <h2 class="card-title">
                    <!-- Icon: Adjustments -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                    Cấu Hình Chương Trình
                </h2>
                <div class="form-grid">
                    <!-- Niên khóa -->
                    <div class="input-group">
                        <label for="academic_year">Niên khóa</label>
                        <select id="academic_year" class="form-select">
                            @foreach($academicYears as $year)
                                <option value="{{ $year }}" {{ $year == '2022-2026' ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Loại chương trình -->
                    <div class="input-group">
                        <label for="program_type">Hệ đào tạo</label>
                        <select id="program_type" class="form-select">
                            @foreach($programTypes as $type)
                                <option value="{{ $type }}" {{ $type == 'Chính quy' ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Học kỳ mong muốn -->
                    <div class="input-group">
                        <label for="target_semester">Học kỳ hiện tại</label>
                        <select id="target_semester" class="form-select">
                            @for($i = 1; $i <= 8; $i++)
                                <option value="{{ $i }}" {{ $i == 3 ? 'selected' : '' }}>Học kỳ {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <!-- Số năm học mục tiêu -->
                    <div class="input-group">
                        <label for="target_years">Mục tiêu tốt nghiệp</label>
                        <select id="target_years" class="form-select" onchange="updateCreditStats()">
                            @for($y = 3; $y <= 6; $y++)
                                <option value="{{ $y }}" {{ $y == 4 ? 'selected' : '' }}>{{ $y }} năm</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <!-- Stats: Tín chỉ cần đạt -->
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value" id="stat-total-credits">{{ $totalCredits }}</div>
                        <div class="stat-label">Tổng tín chỉ</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="stat-total-semesters">8</div>
                        <div class="stat-label">Số học kỳ</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value highlight" id="stat-credits-per-sem">—</div>
                        <div class="stat-label">Tín chỉ / kỳ cần đạt</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="stat-earned-credits">0</div>
                        <div class="stat-label">Tín chỉ đã tích lũy</div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Checklist Các Môn Đã Hoàn Thành -->
            <div class="glass-card">
                <h2 class="card-title">
                    <!-- Icon: Pencil Square -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    Nhập Điểm Môn Học
                    <span style="font-size:0.75rem;font-weight:500;color:var(--text-secondary);margin-left:auto;">Điểm &gt; 5.0 = Pass ✅</span>
                </h2>

                <div id="checklist-container">
                    @foreach($subjects as $semName => $semSubjects)
                        <div class="semester-group">
                            <span class="semester-header">Học kỳ chuẩn {{ $semName }}</span>
                            <div class="subjects-list">
                                @foreach($semSubjects as $sub)
                                    <div class="subject-grade-card" id="lbl-sub-{{ $sub->id }}">
                                        <div class="subject-info">
                                            <span class="subject-name">{{ $sub->name }}</span>
                                            <div class="subject-meta">
                                                <span class="subject-badge">{{ $sub->credits }} tín</span>
                                                <span class="subject-badge">{{ $sub->subjectType?->name }}</span>
                                            </div>
                                        </div>
                                        <div class="grade-input-wrap">
                                            <input type="number"
                                                   class="grade-input"
                                                   id="grade-{{ $sub->id }}"
                                                   data-subject-id="{{ $sub->id }}"
                                                   data-credits="{{ $sub->credits }}"
                                                   min="0" max="10" step="0.1"
                                                   placeholder="—"
                                                   oninput="onGradeChange({{ $sub->id }}, this)">
                                            <span class="grade-status-label empty" id="status-{{ $sub->id }}">Chưa nhập</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Cột Bên Phải: Danh Sách Môn Đề Xuất -->
        <div class="results-container">
            <div class="glass-card">
                <h2 class="card-title">
                    <!-- Icon: Academic Cap -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.228-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84a50.53 50.53 0 0 0-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.512A5.985 5.985 0 0 0 6 18v-3m12 3a5.985 5.985 0 0 0 1.007-3.045m-4.257-2.625A55.385 55.385 0 0 1 12 8.443M12 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                    </svg>
                    Môn Học Đề Xuất Học Kỳ Mới
                </h2>

                <!-- Loader -->
                <div class="loader" id="loader">
                    <div class="spinner"></div>
                    <p style="color: var(--text-secondary); font-size: 0.95rem;">Hệ thống đang phân tích và lập lộ trình...</p>
                </div>

                <!-- Suggested List -->
                <div id="suggestions-list" class="suggestions-grid">
                    <!-- Dữ liệu render động qua JS -->
                </div>
            </div>

            <!-- Panel: Môn Đang Học -->
            <div class="glass-card" id="current-courses-card">
                <h2 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                    Môn Đang Học Kỳ Này
                    <span class="counter-badge" id="cc-count">0</span>
                    <button class="btn-complete" id="btn-complete" onclick="completeSemester()" disabled>
                        ✓ Hoàn tất học kỳ
                    </button>
                </h2>
                <div id="current-courses-list">
                    <div class="current-courses-empty">Chưa có môn nào — nhấn <strong>+ Thêm</strong> trên các môn gợi ý bên trên.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ─── State ──────────────────────────────────────────────────────────────────
    let fetchTimer = null;
    let currentCourses = [];
    let syncLock = false; // Tránh vòng lặp sync vô hạn
    const TOTAL_CREDITS = {{ $totalCredits }}; // Tổng tín chỉ toàn chương trình

    // ─── Tính toán và cập nhật thống kê tín chỉ ─────────────────────────────────
    function updateCreditStats() {
        const years    = parseInt(document.getElementById('target_years').value);
        const totalSem = years * 2;
        document.getElementById('stat-total-semesters').textContent = totalSem;
        // Cập nhật tín chỉ còn lại / kỳ còn lại (sẽ gọi trong updateEarnedCredits)
        updateEarnedCredits();
    }

    // ─── Tính tổng tín chỉ đã tích lũy + cập nhật tín chỉ/kỳ cần đạt ───────────
    function updateEarnedCredits() {
        let earned = 0;
        // Từ bảng nhập điểm lịch sử (cột trái)
        document.querySelectorAll('.grade-input').forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val) && val > 5.0) {
                const credits = parseInt(input.dataset.credits || 0);
                earned += credits;
            }
        });
        // Từ bảng môn đang học
        currentCourses.forEach(c => {
            if (c.grade !== null && c.grade > 5.0) earned += (c.credits || 0);
        });
        document.getElementById('stat-earned-credits').textContent = earned;

        // ── Tín chỉ / kỳ cần đạt (động) ─────────────────────────────────────────
        // Công thức: ceil( tín_chỉ_còn_lại / học_kỳ_còn_lại )
        const years       = parseInt(document.getElementById('target_years').value);
        const totalSem    = years * 2;
        const currentSem  = parseInt(document.getElementById('target_semester').value);
        const remaining   = Math.max(0, TOTAL_CREDITS - earned);
        // Học kỳ còn lại = tổng kỳ - (kỳ hiện tại - 1), tối thiểu 1
        const remSem      = Math.max(1, totalSem - (currentSem - 1));
        const perSem      = remaining === 0 ? 0 : Math.ceil(remaining / remSem);
        document.getElementById('stat-credits-per-sem').textContent = perSem;
    }

    // ─── Nhập điểm ở bảng LỊCH SỬ (cột trái) ───────────────────────────────────
    function onGradeChange(id, input) {
        const card   = document.getElementById(`lbl-sub-${id}`);
        const status = document.getElementById(`status-${id}`);
        const val    = parseFloat(input.value);
        card.classList.remove('pass','fail');
        input.classList.remove('is-pass','is-fail');
        status.classList.remove('pass','fail','empty');
        if (input.value === '' || isNaN(val)) {
            status.textContent = 'Chưa nhập'; status.classList.add('empty');
        } else if (val > 5.0) {
            card.classList.add('pass'); input.classList.add('is-pass');
            status.textContent = '✓ Pass'; status.classList.add('pass');
        } else {
            card.classList.add('fail'); input.classList.add('is-fail');
            status.textContent = '✗ Fail'; status.classList.add('fail');
        }
        // Đồng bộ sang panel Môn Đang Học nếu môn đó có mặt
        if (!syncLock) {
            syncLock = true;
            const ccInput = document.getElementById(`cc-grade-${id}`);
            if (ccInput && ccInput.value !== input.value) {
                ccInput.value = input.value;
                onCCGradeChange(id, ccInput);
            }
            syncLock = false;
        }
        clearTimeout(fetchTimer);
        fetchTimer = setTimeout(fetchSuggestions, 400);
        updateEarnedCredits();
    }

    // ─── Nhập điểm ở bảng MÔN ĐANG HỌC ────────────────────────────────────────
    function onCCGradeChange(id, input) {
        const val = parseFloat(input.value);
        const item = document.getElementById(`cc-item-${id}`);
        const status = document.getElementById(`cc-status-${id}`);
        item.classList.remove('cc-pass','cc-fail');
        input.classList.remove('is-pass','is-fail');
        status.classList.remove('pass','fail','empty');
        const course = currentCourses.find(c => c.id == id);
        if (course) course.grade = isNaN(val) ? null : val;
        if (input.value === '' || isNaN(val)) {
            status.textContent = '—'; status.classList.add('empty');
        } else if (val > 5.0) {
            item.classList.add('cc-pass'); input.classList.add('is-pass');
            status.textContent = 'Pass'; status.classList.add('pass');
        } else {
            item.classList.add('cc-fail'); input.classList.add('is-fail');
            status.textContent = 'Fail'; status.classList.add('fail');
        }
        // ⚠️ KHÔNG sync sang cột trái ngay — chỉ chuyển khi bấm "Hoàn tất học kỳ"
        // Cập nhật trạng thái nút Hoàn tất (bật khi tất cả môn đã có điểm)
        updateCompleteButton();
        clearTimeout(fetchTimer);
        fetchTimer = setTimeout(fetchSuggestions, 400);
        updateEarnedCredits();
    }

    // ─── Cập nhật trạng thái nút Hoàn tất học kỳ ────────────────────────────────
    function updateCompleteButton() {
        const btn = document.getElementById('btn-complete');
        if (!btn) return;
        // Bật nút khi: có ít nhất 1 môn VÀ tất cả đã điền điểm (kể cả 0 hay fail)
        const allFilled = currentCourses.length > 0
            && currentCourses.every(c => c.grade !== null && c.grade !== undefined);
        btn.disabled = !allFilled;
    }

    // ─── Thêm môn vào danh sách đang học ────────────────────────────────────────
    function addToCurrentCourses(subject) {
        if (currentCourses.find(c => c.id == subject.id)) return;
        currentCourses.push({ id: subject.id, name: subject.name, credits: subject.credits, semesterName: subject.semester?.name || '?', grade: null });
        renderCurrentCourses();
        // Đổi nút thành "Đã thêm"
        const btn = document.getElementById(`btn-add-${subject.id}`);
        if (btn) { btn.textContent = '✓ Đã thêm'; btn.classList.add('added'); }
        // Khóa ô nhập điểm bên trái
        lockLeftInput(subject.id);
    }

    // ─── Xóa môn khỏi danh sách đang học ───────────────────────────────────────
    function removeCourse(id) {
        currentCourses = currentCourses.filter(c => c.id != id);
        renderCurrentCourses();
        // Khôi phục nút + trên suggestion card nếu có
        const btn = document.getElementById(`btn-add-${id}`);
        if (btn) { btn.innerHTML = '+ Thêm'; btn.classList.remove('added'); }
        // Mở lại ô nhập điểm bên trái
        unlockLeftInput(id);
        clearTimeout(fetchTimer);
        fetchTimer = setTimeout(fetchSuggestions, 400);
        updateEarnedCredits();
    }

    // ─── Khóa / Mở ô nhập điểm bên trái khi môn đang được thêm vào panel phải ─────────
    function lockLeftInput(id) {
        const input = document.getElementById(`grade-${id}`);
        if (!input) return;
        input.classList.add('is-studying');
        const wrap = input.parentElement;
        if (wrap) {
            wrap.classList.add('is-locked');
            // Thêm badge nếu chưa có
            if (!wrap.querySelector('.studying-label')) {
                const badge = document.createElement('span');
                badge.className = 'studying-label';
                badge.innerHTML = '📖 Đang học';
                wrap.appendChild(badge);
            }
        }
        // Ẩn status cũ
        const status = document.getElementById(`status-${id}`);
        if (status) { status.dataset.prevText = status.textContent; status.textContent = ''; }
    }

    function unlockLeftInput(id) {
        const input = document.getElementById(`grade-${id}`);
        if (!input) return;
        input.classList.remove('is-studying');
        const wrap = input.parentElement;
        if (wrap) {
            wrap.classList.remove('is-locked');
            const badge = wrap.querySelector('.studying-label');
            if (badge) badge.remove();
        }
        // Khôi phục status dựa theo giá trị hiện tại của input
        onGradeChange(id, input);
    }

    // ─── Render bảng Môn Đang Học ────────────────────────────────────────────────
    function renderCurrentCourses() {
        const container = document.getElementById('current-courses-list');
        const counter   = document.getElementById('cc-count');
        counter.textContent = currentCourses.length;
        updateCompleteButton();
        if (currentCourses.length === 0) {
            container.innerHTML = '<div class="current-courses-empty">Chưa có môn nào — nhấn <strong>+ Thêm</strong> trên các môn gợi ý bên trên.</div>';
            return;
        }
        container.innerHTML = currentCourses.map(c => `
            <div class="current-course-item${c.grade !== null && c.grade > 5 ? ' cc-pass' : c.grade !== null ? ' cc-fail' : ''}" id="cc-item-${c.id}">
                <div class="current-course-info">
                    <span class="current-course-name">${c.name}</span>
                    <span class="current-course-meta">${c.credits} tín chỉ &nbsp;·&nbsp; Học kỳ chuẩn ${c.semesterName}</span>
                </div>
                <div class="current-course-right">
                    <input type="number" class="cc-grade-input${c.grade !== null && c.grade > 5 ? ' is-pass' : c.grade !== null ? ' is-fail' : ''}"
                           id="cc-grade-${c.id}"
                           min="0" max="10" step="0.1"
                           placeholder="Điểm"
                           value="${c.grade !== null ? c.grade : ''}"
                           oninput="onCCGradeChange(${c.id}, this)">
                    <span class="cc-status ${c.grade !== null && c.grade > 5 ? 'pass' : c.grade !== null ? 'fail' : 'empty'}" id="cc-status-${c.id}">${c.grade !== null && c.grade > 5 ? 'Pass' : c.grade !== null ? 'Fail' : '—'}</span>
                    <button class="btn-remove" onclick="removeCourse(${c.id})" title="Xóa">✕</button>
                </div>
            </div>
        `).join('');
    }

    // ─── Lấy tất cả môn học đã Pass (từ cả 2 bảng) ──────────────────────────────
    function getPassedSubjectIds() {
        const passed = new Set();
        // Từ bảng lịch sử điểm (cột trái)
        document.querySelectorAll('.grade-input').forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val) && val > 5.0) passed.add(input.dataset.subjectId);
        });
        // Từ bảng môn đang học
        currentCourses.forEach(c => {
            if (c.grade !== null && c.grade > 5.0) passed.add(String(c.id));
        });
        return [...passed].join(',');
    }

    // ─── Gọi API lấy dữ liệu đề xuất ───────────────────────────────────────────
    async function fetchSuggestions() {
        const academicYear   = document.getElementById('academic_year').value;
        const programType    = document.getElementById('program_type').value;
        const semester       = document.getElementById('target_semester').value;
        const passedSubjects = getPassedSubjectIds();
        const loader               = document.getElementById('loader');
        const suggestionsContainer = document.getElementById('suggestions-list');
        loader.style.display       = 'flex';
        suggestionsContainer.style.opacity = '0.3';
        try {
            const url = `/api/suggestions?academic_year=${encodeURIComponent(academicYear)}&program_type=${encodeURIComponent(programType)}&passed_subjects=${passedSubjects}&semester=${semester}`;
            const response = await fetch(url);
            if (!response.ok) throw new Error('API error');
            const data = await response.json();
            renderSuggestions(data, semester);
        } catch (error) {
            suggestionsContainer.innerHTML = `<div class="empty-state"><p style="color:#ef4444;font-weight:600;">⚠️ Đã có lỗi xảy ra khi phân tích dữ liệu.</p></div>`;
        } finally {
            loader.style.display = 'none';
            suggestionsContainer.style.opacity = '1';
        }
    }

    // ─── Lấy danh sách môn đã thi Fail (có điểm ≤ 5.0) ──────────────────────────────────
    function getFailedSubjectIds() {
        const failed = new Set();
        // Từ cột trái
        document.querySelectorAll('.grade-input').forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val) && val > 0 && val <= 5.0)
                failed.add(parseInt(input.dataset.subjectId));
        });
        // Từ panel Môn Đang Học
        currentCourses.forEach(c => {
            if (c.grade !== null && c.grade > 0 && c.grade <= 5.0)
                failed.add(c.id);
        });
        return failed;
    }

    // ─── Render danh sách gợi ý ─────────────────────────────────────────────────
    function renderSuggestions(subjects, targetSemester) {
        const container = document.getElementById('suggestions-list');
        if (subjects.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <h3>Không có môn học đề xuất nào!</h3>
                    <p>Hãy thử đổi niên khóa, loại chương trình hoặc học kỳ mong muốn phù hợp hơn.</p>
                </div>`;
            return;
        }
        container.innerHTML = subjects.map(subject => {
            const subSem    = parseInt(subject.semester?.name || 1);
            const targetSem = parseInt(targetSemester);
            const isAdded   = currentCourses.find(c => c.id == subject.id);
            const failedIds = getFailedSubjectIds();
            const isFailed  = failedIds.has(subject.id);

            let distLabel = '';
            if (isFailed) {
                // Môn đã thi nhưng trượt → cần học lại
                distLabel = '<span style="color:#f87171;font-weight:600;">Học lại 🔄</span>';
            } else if (subSem === targetSem) {
                distLabel = '<span style="color:var(--accent-success);font-weight:600;">Đúng tiến độ 🎯</span>';
            } else if (subSem < targetSem) {
                distLabel = `<span style="color:var(--accent-warning);font-weight:600;">Học bù (Chậm ${targetSem - subSem} kỳ) ⏳</span>`;
            } else {
                distLabel = `<span style="color:#a5b4fc;font-weight:600;">Học vượt (Nhanh ${subSem - targetSem} kỳ) ⚡</span>`;
            }
            return `
                <div class="suggestion-card">
                    <div class="suggestion-details">
                        <span class="suggestion-title">${subject.name}</span>
                        <div class="suggestion-tags">
                            <span class="tag tag-credits">${subject.credits} tín chỉ</span>
                            <span class="tag tag-type">${subject.subject_type?.name || 'Môn học'}</span>
                            <span class="tag tag-group">${subject.subject_group?.name || 'Nhóm'}</span>
                        </div>
                    </div>
                    <div class="suggestion-right">
                        <span class="semester-badge">Học kỳ chuẩn ${subject.semester?.name || '1'}</span>
                        <span class="distance-label">${distLabel}</span>
                        <button id="btn-add-${subject.id}" class="btn-add${isAdded ? ' added' : ''}" onclick="addToCurrentCourses(${JSON.stringify(subject).replace(/"/g, '&quot;')})">
                            ${isAdded ? '✓ Đã thêm' : '+ Thêm'}
                        </button>
                    </div>
                </div>`;
        }).join('');
    }

    // ─── Hoàn tất học kỳ: tăng học kỳ +1, chuyển điểm sang lịch sử ────────────────────────
    function completeSemester() {
        // Kiểm tra tất cả đã có điểm
        const unfilled = currentCourses.filter(c => c.grade === null || c.grade === undefined);
        if (unfilled.length > 0) {
            showToast(`Còn ${unfilled.length} môn chưa điền điểm!`, 'error');
            return;
        }
        if (currentCourses.length === 0) {
            showToast('Chưa có môn nào trong danh sách!', 'error');
            return;
        }

        // 1. Chụp lại danh sách điểm TRƯỚC khi xóa (tránh mất dữ liệu)
        const snapshot = currentCourses.map(c => ({ id: c.id, grade: c.grade }));

        // 2. Xóa currentCourses và re-render NGAY — điều này loại cc-grade-${id} khỏi DOM
        //    → khi ghi điểm sang cột trái, sync ngược không còn nơi để ghi đè về null
        currentCourses = [];
        renderCurrentCourses();

        // 3. Ghi điểm vào cột trái (cc-grade không còn tồn tại → không có sync ngược)
        snapshot.forEach(({ id, grade }) => {
            // Mở khóa badge "📖 Đang học" (không trigger sync vì cc-grade đã bị xóa)
            const input = document.getElementById(`grade-${id}`);
            if (!input) return;
            input.classList.remove('is-studying');
            const wrap = input.parentElement;
            if (wrap) {
                wrap.classList.remove('is-locked');
                const badge = wrap.querySelector('.studying-label');
                if (badge) badge.remove();
            }
            // Ghi điểm và cập nhật UI cột trái
            input.value = grade;
            onGradeChange(id, input);
        });

        // 4. Tăng học kỳ hiện tại +1
        const sel = document.getElementById('target_semester');
        const cur = parseInt(sel.value);
        if (cur < 8) {
            sel.value = cur + 1;
        } else {
            showToast('Đã hoàn thành toàn bộ chương trình! 🎓', 'success');
        }

        fetchSuggestions();
        updateEarnedCredits();
        showToast(`Hoàn tất học kỳ ${cur}! Đã chuyển sang học kỳ ${Math.min(cur + 1, 8)}.`, 'success');
    }

    // ─── Hiển thị thông báo toast ────────────────────────────────────────────────────
    function showToast(msg, type = 'success') {
        const existing = document.getElementById('app-toast');
        if (existing) existing.remove();
        const t = document.createElement('div');
        t.id = 'app-toast';
        t.className = `toast ${type}`;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3500);
    }

    // ─── Event Listeners ────────────────────────────────────────────────────────
    document.getElementById('academic_year').addEventListener('change', fetchSuggestions);
    document.getElementById('program_type').addEventListener('change', fetchSuggestions);
    document.getElementById('target_semester').addEventListener('change', () => {
        updateEarnedCredits();
        fetchSuggestions();
    });
    document.addEventListener('DOMContentLoaded', () => {
        updateCreditStats();
        fetchSuggestions();
    });
</script>

</body>
</html>