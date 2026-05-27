<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gợi Ý Đăng Ký Môn Học - Hỗ Trợ Học Tập</title>
    {{-- CSRF token cho các request POST từ JavaScript (fetch API) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ═══════════════════════════════════════════════════════════
           CSS RESET & DESIGN TOKENS
        ═══════════════════════════════════════════════════════════ */
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

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 2rem 1rem;
            overflow-x: hidden;
            background-attachment: fixed;
        }

        h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; }

        /* ═══════════════════════════════════════════════════════════
           LAYOUT
        ═══════════════════════════════════════════════════════════ */
        .container { max-width: 1400px; margin: 0 auto; width: 100%; }

        header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }
        header::after {
            content: '';
            position: absolute;
            top: -100px; left: 50%;
            transform: translateX(-50%);
            width: 300px; height: 300px;
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

        .main-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            align-items: start;
            max-width: 860px;
            margin: 0 auto;
        }

        /* ═══════════════════════════════════════════════════════════
           GLASS CARDS
        ═══════════════════════════════════════════════════════════ */
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
        .card-title svg { color: var(--primary); width: 24px; height: 24px; }

        /* ═══════════════════════════════════════════════════════════
           NAVBAR
        ═══════════════════════════════════════════════════════════ */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: .65rem 1.25rem;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(12px);
            gap: 1rem;
        }
        .navbar-left { display: flex; align-items: center; gap: .65rem; }
        .navbar-avatar {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #7c6af7, #a855f7);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            box-shadow: 0 2px 10px rgba(124,106,247,.4);
        }
        .navbar-info .name { font-size:.88rem; font-weight:600; color:#f1f1f5; }
        .navbar-info .meta { font-size:.75rem; color:rgba(241,241,245,.5); }
        .navbar-right { display: flex; align-items: center; gap: .6rem; }

        /* ── Nút Cấu Hình ── */
        .btn-config {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(99,102,241,.12);
            border: 1px solid rgba(99,102,241,.35);
            color: #a5b4fc;
            border-radius: 8px;
            padding: .45rem .9rem;
            font-family: 'Inter', sans-serif;
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-config:hover { background: rgba(99,102,241,.25); color: #fff; }
        .btn-config .config-dot {
            width: 7px; height: 7px;
            background: var(--accent-warning);
            border-radius: 50%;
            position: absolute;
            top: -2px; right: -2px;
            animation: pulse 1.5s infinite;
        }

        /* ── Nút Nhập Điểm ── */
        .btn-grades {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(16,185,129,.1);
            border: 1px solid rgba(16,185,129,.3);
            color: #6ee7b7;
            border-radius: 8px;
            padding: .45rem .9rem;
            font-family: 'Inter', sans-serif;
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-grades:hover { background: rgba(16,185,129,.22); color: #fff; }
        .btn-grades .grade-badge {
            background: var(--accent-success);
            color: #fff;
            border-radius: 50px;
            font-size: .65rem;
            font-weight: 800;
            padding: .05rem .4rem;
            min-width: 18px;
            text-align: center;
            display: none;
        }
        .btn-grades .grade-badge.visible { display: inline-block; }

        /* ── Nút Lịch sử ── */
        .btn-history {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(251,191,36,.1);
            border: 1px solid rgba(251,191,36,.3);
            color: #fcd34d;
            border-radius: 8px;
            padding: .45rem .9rem;
            font-family: 'Inter', sans-serif;
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-history:hover { background: rgba(251,191,36,.2); color: #fff; }
        .btn-history .history-badge {
            background: #f59e0b;
            color: #000;
            border-radius: 50px;
            font-size: .65rem;
            font-weight: 800;
            padding: .05rem .4rem;
            min-width: 18px;
            text-align: center;
            display: none;
        }
        .btn-history .history-badge.visible { display: inline-block; }

        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.3);
            color: #f87171;
            border-radius: 8px;
            padding: .45rem .9rem;
            font-family: 'Inter', sans-serif;
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-logout:hover { background: rgba(239,68,68,.22); }

        /* ═══════════════════════════════════════════════════════════
           HISTORY DRAWER (slide-in từ bên phải)
        ═══════════════════════════════════════════════════════════ */
        .history-drawer-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 600;
            background: rgba(7,11,26,0.6);
            backdrop-filter: blur(4px);
        }
        .history-drawer-overlay.open { display: block; }

        .history-drawer {
            position: fixed;
            top: 0; right: 0;
            width: 520px;
            max-width: 96vw;
            height: 100vh;
            background: linear-gradient(160deg, rgba(13,19,38,0.99), rgba(26,18,50,0.99));
            border-left: 1px solid rgba(251,191,36,.2);
            z-index: 700;
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.35s cubic-bezier(0.4,0,0.2,1);
            box-shadow: -6px 0 40px rgba(0,0,0,.5);
        }
        .history-drawer.open { transform: translateX(0); }

        .history-drawer-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,.07);
            display: flex; align-items: center; justify-content: space-between;
            flex-shrink: 0;
            background: rgba(251,191,36,.05);
        }
        .history-drawer-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.1rem; font-weight: 700; color: #fff;
            display: flex; align-items: center; gap: .6rem;
        }
        .history-drawer-subtitle {
            font-size: .75rem; color: var(--text-secondary); margin-top: .15rem;
        }
        .history-drawer-close {
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.12);
            color: var(--text-secondary);
            border-radius: 8px; width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 1.1rem; transition: var(--transition); flex-shrink: 0;
        }
        .history-drawer-close:hover { background: rgba(239,68,68,.15); border-color: rgba(239,68,68,.3); color: #f87171; }

        .history-drawer-body {
            flex: 1; overflow-y: auto; padding: 1.25rem 1.5rem;
        }
        .history-drawer-body::-webkit-scrollbar { width: 4px; }
        .history-drawer-body::-webkit-scrollbar-track { background: transparent; }
        .history-drawer-body::-webkit-scrollbar-thumb { background: rgba(251,191,36,.3); border-radius: 2px; }

        .history-empty {
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 4rem 1rem; gap: .75rem;
            color: var(--text-secondary); font-size: .9rem; text-align: center;
        }
        .history-empty-icon { font-size: 2.8rem; }

        /* — Từng học kỳ — */
        .history-sem-block {
            margin-bottom: 1.5rem;
            border: 1px solid rgba(251,191,36,.15);
            border-radius: 12px;
            overflow: hidden;
        }
        .history-sem-header {
            background: rgba(251,191,36,.08);
            padding: .8rem 1.1rem;
            display: flex; align-items: center; justify-content: space-between;
            gap: .75rem;
            cursor: pointer;
            transition: var(--transition);
        }
        .history-sem-header:hover { background: rgba(251,191,36,.14); }
        .history-sem-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1rem; font-weight: 700; color: #fcd34d;
            display: flex; align-items: center; gap: .5rem;
        }
        .history-sem-meta {
            display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
        }
        .history-sem-pill {
            font-size: .7rem; font-weight: 700;
            padding: .2rem .55rem; border-radius: 50px;
            border: 1px solid;
        }
        .history-sem-pill.gpa {
            color: #a5f3fc; border-color: rgba(34,211,238,.4); background: rgba(34,211,238,.08);
        }
        .history-sem-pill.credits {
            color: #bbf7d0; border-color: rgba(74,222,128,.35); background: rgba(74,222,128,.07);
        }
        .history-sem-pill.date {
            color: var(--text-secondary); border-color: rgba(255,255,255,.1); background: transparent;
        }
        .history-sem-chevron {
            color: var(--text-secondary); transition: transform .25s; flex-shrink: 0; font-size: 1rem;
        }
        .history-sem-block.open .history-sem-chevron { transform: rotate(180deg); }

        .history-sem-body {
            display: none; border-top: 1px solid rgba(255,255,255,.05);
        }
        .history-sem-block.open .history-sem-body { display: block; }

        .history-subject-list { display: flex; flex-direction: column; gap: 0; }
        .history-subject-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: .6rem 1.1rem; gap: .75rem;
            border-bottom: 1px solid rgba(255,255,255,.04);
            transition: var(--transition);
        }
        .history-subject-row:last-child { border-bottom: none; }
        .history-subject-row:hover { background: rgba(255,255,255,.025); }
        .history-subject-row.pass { border-left: 3px solid rgba(52,211,153,.5); }
        .history-subject-row.fail { border-left: 3px solid rgba(239,68,68,.45); }
        .history-subject-name { font-size: .85rem; font-weight: 500; color: #e2e8f0; flex: 1; }
        .history-subject-credits { font-size: .72rem; color: var(--text-secondary); min-width: 42px; text-align: right; }
        .history-subject-grade {
            min-width: 48px; text-align: center;
            font-size: .9rem; font-weight: 800;
            font-family: 'Outfit', sans-serif;
        }
        .history-subject-grade.pass { color: #6ee7b7; }
        .history-subject-grade.fail { color: #fca5a5; }
        .history-subject-grade.empty { color: var(--text-secondary); }

        /* ═══════════════════════════════════════════════════════════
           CONFIG PANEL (dropdown từ nút menu)
        ═══════════════════════════════════════════════════════════ */
        .config-panel-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 400;
            background: transparent;
        }
        .config-panel-overlay.open { display: block; }

        .config-panel {
            position: fixed;
            top: 70px; right: 20px;
            width: 400px;
            max-width: calc(100vw - 40px);
            background: rgba(15, 23, 42, 0.97);
            border: 1px solid rgba(99,102,241,.3);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            z-index: 500;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0,0,0,.5), 0 0 0 1px rgba(99,102,241,.1);
            transform: translateY(-10px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .config-panel.open {
            transform: translateY(0);
            opacity: 1;
            pointer-events: all;
        }

        /* ═══════════════════════════════════════════════════════════
           GRADE DRAWER (slide-in từ bên trái)
        ═══════════════════════════════════════════════════════════ */
        .grade-drawer-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 600;
            background: rgba(7, 11, 26, 0.65);
            backdrop-filter: blur(4px);
        }
        .grade-drawer-overlay.open { display: block; }

        .grade-drawer {
            position: fixed;
            top: 0; left: 0;
            width: 480px;
            max-width: 95vw;
            height: 100vh;
            background: linear-gradient(160deg, rgba(13,19,38,0.99), rgba(20,14,45,0.99));
            border-right: 1px solid rgba(99,102,241,.25);
            z-index: 700;
            display: flex;
            flex-direction: column;
            transform: translateX(-100%);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 6px 0 40px rgba(0,0,0,.5);
        }
        .grade-drawer.open { transform: translateX(0); }

        .grade-drawer-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,.07);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            background: rgba(99,102,241,.06);
        }
        .grade-drawer-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            display: flex; align-items: center; gap: .6rem;
        }
        .grade-drawer-subtitle {
            font-size: .75rem;
            color: var(--text-secondary);
            margin-top: .15rem;
        }
        .grade-drawer-close {
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.12);
            color: var(--text-secondary);
            border-radius: 8px;
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 1.1rem; transition: var(--transition);
            flex-shrink: 0;
        }
        .grade-drawer-close:hover { background: rgba(239,68,68,.15); border-color: rgba(239,68,68,.3); color: #f87171; }

        .grade-drawer-search {
            padding: .85rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,.05);
            flex-shrink: 0;
        }
        .grade-drawer-search input {
            width: 100%;
            background: rgba(15,23,42,.7);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 8px;
            color: #fff;
            padding: .6rem 1rem;
            font-size: .88rem;
            outline: none;
            transition: var(--transition);
        }
        .grade-drawer-search input::placeholder { color: var(--text-secondary); }
        .grade-drawer-search input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }

        .grade-drawer-stats {
            padding: .65rem 1.5rem;
            display: flex;
            align-items: center;
            gap: .85rem;
            border-bottom: 1px solid rgba(255,255,255,.05);
            flex-shrink: 0;
        }
        .grade-drawer-stat {
            display: flex; align-items: center; gap: .4rem;
            font-size: .78rem; color: var(--text-secondary);
        }
        .grade-drawer-stat strong { color: #fff; }
        .grade-drawer-stat.pass strong { color: var(--accent-success); }
        .grade-drawer-stat.fail strong { color: #f87171; }

        .grade-drawer-body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 1.5rem;
        }
        .grade-drawer-body::-webkit-scrollbar { width: 4px; }
        .grade-drawer-body::-webkit-scrollbar-track { background: transparent; }
        .grade-drawer-body::-webkit-scrollbar-thumb { background: rgba(99,102,241,.4); border-radius: 2px; }

        /* Subject cards bên trong drawer — compact hơn */
        .drawer-semester-group { margin-bottom: 1.25rem; }
        .drawer-semester-header {
            font-size: .78rem; font-weight: 700; color: #818cf8;
            text-transform: uppercase; letter-spacing: .05em;
            margin-bottom: .6rem;
            display: flex; align-items: center; gap: .5rem;
        }
        .drawer-semester-header::after { content:''; flex:1; height:1px; background:rgba(99,102,241,.15); }

        .drawer-subjects-list { display: flex; flex-direction: column; gap: .45rem; }

        .drawer-subject-card {
            background: rgba(15,23,42,.45);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 9px;
            padding: .6rem .9rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            transition: var(--transition);
        }
        .drawer-subject-card:hover { background: rgba(255,255,255,.03); border-color: rgba(255,255,255,.12); }
        .drawer-subject-card.pass { border-color: rgba(16,185,129,.4); background: rgba(16,185,129,.06); }
        .drawer-subject-card.fail { border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.05); }
        .drawer-subject-card.hidden-search { display: none; }

        .drawer-subject-info { flex: 1; min-width: 0; }
        .drawer-subject-name { font-size: .88rem; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .drawer-subject-meta { font-size: .7rem; color: var(--text-secondary); margin-top: .1rem; }

        .drawer-grade-wrap { display: flex; align-items: center; gap: .35rem; flex-shrink: 0; }
        .drawer-grade-input {
            width: 58px;
            background: rgba(255,255,255,.06);
            border: 1.5px solid rgba(255,255,255,.15);
            border-radius: 7px;
            color: #fff; font-size: .95rem; font-weight: 700;
            text-align: center; padding: .28rem .2rem;
            outline: none; transition: var(--transition);
        }
        .drawer-grade-input:focus { border-color: var(--primary); box-shadow: 0 0 6px var(--primary-glow); }
        .drawer-grade-input.is-pass { border-color: var(--accent-success); color: #6ee7b7; }
        .drawer-grade-input.is-fail { border-color: #ef4444; color: #fca5a5; }
        .drawer-grade-status { font-size: .62rem; font-weight: 700; min-width: 32px; text-align: center; }
        .drawer-grade-status.pass { color: var(--accent-success); }
        .drawer-grade-status.fail { color: #f87171; }
        .drawer-grade-status.empty { color: var(--text-secondary); }

        .config-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .config-panel-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: #fff;
            display: flex; align-items: center; gap: .5rem;
        }
        .config-panel-close {
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.12);
            color: var(--text-secondary);
            border-radius: 6px;
            width: 28px; height: 28px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 1rem; transition: var(--transition);
        }
        .config-panel-close:hover { background: rgba(255,255,255,.14); color: #fff; }

        .config-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.85rem;
            margin-bottom: 1.1rem;
        }

        /* ── Stats mini ── */
        .config-stats {
            display: grid;
            grid-template-columns: repeat(4,1fr);
            gap: .5rem;
            margin-top: .85rem;
            padding-top: .85rem;
            border-top: 1px solid rgba(255,255,255,.06);
        }
        .config-stat {
            background: rgba(15,23,42,.5);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: var(--radius-md);
            padding: .6rem .5rem;
            text-align: center;
        }
        .config-stat-val {
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, #c7d2fe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .config-stat-val.highlight {
            background: linear-gradient(135deg, var(--accent-success), #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .config-stat-label {
            font-size: .6rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-top: .2rem;
        }

        /* ═══════════════════════════════════════════════════════════
           FORMS & INPUTS
        ═══════════════════════════════════════════════════════════ */
        .input-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .input-group label {
            font-size: 0.82rem;
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

        /* ═══════════════════════════════════════════════════════════
           CHECKLIST SUBJECTS
        ═══════════════════════════════════════════════════════════ */
        .semester-group { margin-bottom: 1.5rem; }
        .semester-header {
            font-size: 1rem; font-weight: 700; color: #818cf8;
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
        @media (max-width: 640px) { .subjects-list { grid-template-columns: 1fr; } }

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
        .subject-grade-card:hover { background: rgba(255,255,255,.03); border-color: rgba(255,255,255,.15); }
        .subject-grade-card.pass { border-color: var(--accent-success); background: rgba(16,185,129,.08); box-shadow: 0 0 10px rgba(16,185,129,.15); }
        .subject-grade-card.fail { border-color: #ef4444; background: rgba(239,68,68,.07); }

        .grade-input-wrap { display: flex; flex-direction: column; align-items: center; gap: 0.2rem; flex-shrink: 0; }
        .grade-input {
            width: 56px;
            background: rgba(255,255,255,.06);
            border: 1.5px solid rgba(255,255,255,.15);
            border-radius: 8px;
            color: #fff; font-size: 1rem; font-weight: 700;
            text-align: center; padding: .3rem .2rem;
            outline: none; transition: var(--transition);
        }
        .grade-input:focus { border-color: var(--primary); box-shadow: 0 0 8px var(--primary-glow); }
        .grade-input.is-pass { border-color: var(--accent-success); color: #6ee7b7; }
        .grade-input.is-fail { border-color: #ef4444; color: #fca5a5; }

        .grade-status-label { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
        .grade-status-label.pass { color: var(--accent-success); }
        .grade-status-label.fail { color: #f87171; }
        .grade-status-label.empty { color: var(--text-secondary); }

        .subject-info { display: flex; flex-direction: column; gap: .15rem; }
        .subject-name { font-size: 0.9rem; font-weight: 600; color: #fff; }
        .subject-meta { font-size: 0.75rem; color: var(--text-secondary); display: flex; align-items: center; gap: .5rem; }
        .subject-badge { background: rgba(255,255,255,.08); padding: .1rem .4rem; border-radius: 4px; font-size: .7rem; }

        /* ═══════════════════════════════════════════════════════════
           RIGHT COLUMN (suggestions)
        ═══════════════════════════════════════════════════════════ */
        .results-container { position: sticky; top: 2rem; }
        .loader { display: none; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 0; gap: 1.5rem; }
        .spinner { width: 50px; height: 50px; border: 4px solid rgba(255,255,255,.05); border-radius: 50%; border-top-color: var(--primary); animation: spin 1s linear infinite; }
        .suggestions-grid { display: flex; flex-direction: column; gap: 1rem; }

        .suggestion-card {
            background: rgba(255,255,255,.02);
            border: 1px solid rgba(255,255,255,.05);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            display: flex; justify-content: space-between; align-items: center; gap: 1rem;
            transition: var(--transition);
            animation: fadeIn .4s ease-out;
        }
        .suggestion-card:hover { transform: translateX(5px); background: rgba(255,255,255,.04); border-color: rgba(99,102,241,.3); box-shadow: -4px 0 0 var(--primary); }

        .suggestion-details { display: flex; flex-direction: column; gap: .4rem; }
        .suggestion-title { font-size: 1.1rem; font-weight: 700; color: #fff; }
        .suggestion-tags { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }

        .tag { font-size: .75rem; font-weight: 600; padding: .2rem .6rem; border-radius: 50px; text-transform: uppercase; }
        .tag-credits { background: rgba(99,102,241,.15); color: #a5b4fc; border: 1px solid rgba(99,102,241,.3); }
        .tag-type { background: rgba(168,85,247,.15); color: #d8b4fe; border: 1px solid rgba(168,85,247,.3); }
        .tag-group { background: rgba(245,158,11,.1); color: #fde047; border: 1px solid rgba(245,158,11,.2); }

        .suggestion-right { text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: .5rem; flex-shrink: 0; }
        .btn-add {
            display: inline-flex; align-items: center; gap: .3rem;
            background: rgba(99,102,241,.15); border: 1px solid rgba(99,102,241,.4);
            color: #a5b4fc; border-radius: 50px; padding: .3rem .75rem;
            font-size: .75rem; font-weight: 700; cursor: pointer; transition: var(--transition); white-space: nowrap;
        }
        .btn-add:hover { background: rgba(99,102,241,.3); color: #fff; border-color: var(--primary); }
        .btn-add.added { background: rgba(16,185,129,.15); border-color: var(--accent-success); color: #6ee7b7; pointer-events: none; }

        /* ═══════════════════════════════════════════════════════════
           CURRENT COURSES PANEL
        ═══════════════════════════════════════════════════════════ */
        .current-courses-empty { text-align: center; padding: 2rem; color: var(--text-secondary); font-size: .9rem; }
        .current-course-item {
            background: rgba(15,23,42,.5); border: 1px solid rgba(255,255,255,.07);
            border-radius: var(--radius-md); padding: .85rem 1rem;
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            transition: var(--transition); animation: fadeIn .3s ease-out;
        }
        .current-course-item.cc-pass { border-color: var(--accent-success); background: rgba(16,185,129,.07); }
        .current-course-item.cc-fail { border-color: #ef4444; background: rgba(239,68,68,.06); }
        .current-course-info { display:flex; flex-direction:column; gap:.2rem; flex:1; min-width:0; }
        .current-course-name { font-weight:700; font-size:.95rem; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .current-course-meta { font-size:.75rem; color:var(--text-secondary); }
        .current-course-right { display:flex; align-items:center; gap:.6rem; flex-shrink:0; }
        .cc-grade-input {
            width:60px; background:rgba(255,255,255,.06); border:1.5px solid rgba(255,255,255,.15);
            border-radius:8px; color:#fff; font-size:.95rem; font-weight:700; text-align:center;
            padding:.3rem .2rem; outline:none; transition:var(--transition);
        }
        .cc-grade-input:focus { border-color:var(--primary); box-shadow:0 0 6px var(--primary-glow); }
        .cc-grade-input.is-pass { border-color:var(--accent-success); color:#6ee7b7; }
        .cc-grade-input.is-fail { border-color:#ef4444; color:#fca5a5; }
        .cc-status { font-size:.7rem; font-weight:700; text-transform:uppercase; min-width:40px; text-align:center; }
        .cc-status.pass { color:var(--accent-success); }
        .cc-status.fail { color:#f87171; }
        .cc-status.empty { color:var(--text-secondary); }
        .btn-remove {
            background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.3); color:#f87171;
            border-radius:6px; width:28px; height:28px;
            display:flex; align-items:center; justify-content:center;
            cursor:pointer; font-size:1rem; line-height:1; transition:var(--transition); flex-shrink:0;
        }
        .btn-remove:hover { background:rgba(239,68,68,.25); color:#fff; }

        /* ── Trạng thái Đang học ── */
        .grade-input.is-studying { opacity:0; pointer-events:none; position:absolute; }
        .studying-label {
            display:inline-flex; align-items:center; gap:.3rem;
            font-size:.72rem; font-weight:700; color:#818cf8;
            background:rgba(99,102,241,.12); border:1px solid rgba(99,102,241,.3);
            border-radius:6px; padding:.25rem .6rem; white-space:nowrap;
            animation: pulse-studying 2s ease-in-out infinite;
        }
        @keyframes pulse-studying { 0%,100%{opacity:1} 50%{opacity:.6} }
        .grade-input-wrap.is-locked { position:relative; display:flex; align-items:center; }

        .btn-complete {
            display:inline-flex; align-items:center; gap:.4rem;
            background: linear-gradient(135deg, var(--accent-success), #059669);
            border:none; color:#fff; border-radius:50px; padding:.45rem 1rem;
            font-size:.8rem; font-weight:700; cursor:pointer; transition:var(--transition);
            margin-left:auto; box-shadow:0 4px 12px rgba(16,185,129,.3); white-space:nowrap;
        }
        .btn-complete:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(16,185,129,.45); }
        .btn-complete:disabled { background:rgba(255,255,255,.08); color:var(--text-secondary); box-shadow:none; cursor:not-allowed; transform:none; }

        .counter-badge {
            display:inline-flex; align-items:center; justify-content:center;
            background:var(--primary); color:#fff; border-radius:50%;
            width:20px; height:20px; font-size:.7rem; font-weight:800; margin-left:.4rem;
        }

        .semester-badge {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color:#fff; padding:.35rem .85rem; border-radius:50px;
            font-size:.85rem; font-weight:700; box-shadow:0 4px 10px rgba(99,102,241,.3);
        }
        .distance-label { font-size:.75rem; color:var(--text-secondary); font-weight:500; }

        /* ── Empty State ── */
        .empty-state { text-align:center; padding:4rem 2rem; color:var(--text-secondary); display:flex; flex-direction:column; align-items:center; gap:1rem; }
        .empty-state svg { width:60px; height:60px; color:rgba(255,255,255,.1); }
        .empty-state h3 { color:#fff; font-size:1.2rem; font-weight:600; }

        /* ═══════════════════════════════════════════════════════════
           TOAST & SAVE INDICATOR
        ═══════════════════════════════════════════════════════════ */
        .toast {
            position:fixed; bottom:2rem; right:2rem; z-index:9999;
            padding:.9rem 1.4rem; border-radius:var(--radius-md);
            font-weight:600; font-size:.9rem; animation:slideUp .3s ease-out;
            backdrop-filter:blur(12px); max-width:340px;
        }
        .toast.success { background:rgba(16,185,129,.15); border:1px solid var(--accent-success); color:#6ee7b7; }
        .toast.error { background:rgba(239,68,68,.12); border:1px solid #ef4444; color:#fca5a5; }
        @keyframes slideUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

        .save-indicator {
            position:fixed; top:1rem; right:1.5rem; z-index:9998;
            font-size:.8rem; font-weight:600; padding:.4rem .9rem; border-radius:8px;
            display:none; align-items:center; gap:.4rem;
            backdrop-filter:blur(10px); transition:opacity .3s;
        }
        .save-indicator.saving { display:flex; background:rgba(99,102,241,.18); border:1px solid rgba(99,102,241,.4); color:#a5b4fc; }
        .save-indicator.saved  { display:flex; background:rgba(16,185,129,.15); border:1px solid rgba(16,185,129,.35); color:#6ee7b7; }
        .save-indicator.error  { display:flex; background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.3); color:#fca5a5; }

        /* ═══════════════════════════════════════════════════════════
           ✨ ONBOARDING WIZARD
        ═══════════════════════════════════════════════════════════ */
        .ob-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000;
            background: rgba(7, 11, 26, 0.85);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .ob-overlay.hidden { display: none; }

        .ob-modal {
            background: linear-gradient(145deg, rgba(20, 28, 58, 0.98), rgba(30, 20, 60, 0.98));
            border: 1px solid rgba(99,102,241,.35);
            border-radius: 24px;
            width: 100%;
            max-width: 620px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 30px 80px rgba(0,0,0,.6), 0 0 0 1px rgba(99,102,241,.1), inset 0 1px 0 rgba(255,255,255,.05);
            animation: obSlideIn .5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes obSlideIn {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .ob-header {
            padding: 2rem 2rem 0;
            text-align: center;
        }

        .ob-step-dots {
            display: flex;
            justify-content: center;
            gap: .5rem;
            margin-bottom: 1.5rem;
        }
        .ob-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,.2);
            transition: all .3s;
        }
        .ob-dot.active { background: var(--primary); width: 24px; border-radius: 4px; }
        .ob-dot.done   { background: var(--accent-success); }

        .ob-step-label {
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #818cf8;
            margin-bottom: .5rem;
        }

        .ob-icon {
            width: 72px; height: 72px;
            margin: 0 auto 1rem;
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            position: relative;
        }
        .ob-icon::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 24px;
            background: conic-gradient(from 0deg, var(--primary), var(--secondary), var(--primary));
            z-index: -1;
            opacity: .4;
            animation: spin 4s linear infinite;
        }

        .ob-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(to right, #ffffff, #c7d2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: .5rem;
        }

        .ob-desc {
            color: var(--text-secondary);
            font-size: .95rem;
            line-height: 1.6;
            max-width: 420px;
            margin: 0 auto;
        }

        .ob-body { padding: 1.5rem 2rem; }

        .ob-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 500px) { .ob-form-grid { grid-template-columns: 1fr; } }

        .ob-input-group { display: flex; flex-direction: column; gap: .5rem; }
        .ob-input-group label {
            font-size: .82rem; font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase; letter-spacing: .04em;
        }
        .ob-select {
            background: rgba(15,23,42,.7);
            border: 1.5px solid rgba(99,102,241,.25);
            border-radius: var(--radius-md);
            color: #fff;
            padding: .8rem 1rem;
            font-size: .95rem; font-weight: 500;
            outline: none; cursor: pointer; transition: var(--transition); width: 100%;
        }
        .ob-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }

        /* ── Semester picker ── */
        .ob-semester-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: .65rem;
        }
        .ob-sem-btn {
            background: rgba(15,23,42,.6);
            border: 1.5px solid rgba(255,255,255,.1);
            border-radius: 10px;
            color: var(--text-secondary);
            padding: .75rem .5rem;
            font-family: 'Outfit', sans-serif;
            font-size: .9rem; font-weight: 700;
            cursor: pointer; transition: var(--transition);
            text-align: center;
        }
        .ob-sem-btn:hover { border-color: rgba(99,102,241,.5); color: #a5b4fc; background: rgba(99,102,241,.1); }
        .ob-sem-btn.selected { border-color: var(--primary); background: rgba(99,102,241,.2); color: #fff; box-shadow: 0 0 12px var(--primary-glow); }

        /* ── Target year picker ── */
        .ob-year-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: .65rem;
        }
        .ob-year-btn {
            background: rgba(15,23,42,.6);
            border: 1.5px solid rgba(255,255,255,.1);
            border-radius: 10px;
            color: var(--text-secondary);
            padding: 1rem .5rem;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem; font-weight: 700;
            cursor: pointer; transition: var(--transition);
            text-align: center; display: flex; flex-direction: column; gap: .2rem; align-items: center;
        }
        .ob-year-btn:hover { border-color: rgba(16,185,129,.5); color: #6ee7b7; background: rgba(16,185,129,.08); }
        .ob-year-btn.selected { border-color: var(--accent-success); background: rgba(16,185,129,.15); color: #fff; box-shadow: 0 0 12px var(--accent-success-glow); }
        .ob-year-btn small { font-size: .65rem; color: var(--text-secondary); font-weight: 500; }

        /* ── Grade warning ── */
        .ob-warning {
            background: rgba(245,158,11,.08);
            border: 1px solid rgba(245,158,11,.3);
            border-radius: var(--radius-md);
            padding: .9rem 1.1rem;
            display: flex; align-items: flex-start; gap: .75rem;
            margin-bottom: 1.25rem;
        }
        .ob-warning-icon { font-size: 1.2rem; flex-shrink: 0; margin-top: .05rem; }
        .ob-warning p { font-size: .88rem; color: #fde68a; line-height: 1.5; font-weight: 500; }
        .ob-warning strong { color: #fbbf24; }

        /* ── Scrollable subject list in wizard ── */
        .ob-subjects-scroll {
            max-height: 320px;
            overflow-y: auto;
            padding-right: .25rem;
        }
        .ob-subjects-scroll::-webkit-scrollbar { width: 4px; }
        .ob-subjects-scroll::-webkit-scrollbar-track { background: transparent; }
        .ob-subjects-scroll::-webkit-scrollbar-thumb { background: rgba(99,102,241,.4); border-radius: 2px; }

        .ob-semester-section { margin-bottom: 1rem; }
        .ob-semester-section-title {
            font-size: .78rem; font-weight: 700; color: #818cf8;
            text-transform: uppercase; letter-spacing: .05em;
            margin-bottom: .5rem;
            display: flex; align-items: center; gap: .5rem;
        }
        .ob-semester-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(99,102,241,.2);
        }

        .ob-subject-row {
            display: flex; align-items: center;
            justify-content: space-between;
            background: rgba(15,23,42,.4);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 8px;
            padding: .6rem .85rem;
            margin-bottom: .4rem;
            transition: var(--transition);
        }
        .ob-subject-row:hover { background: rgba(255,255,255,.03); }
        .ob-subject-row.has-grade { border-color: rgba(99,102,241,.3); }
        .ob-subject-info { flex: 1; min-width: 0; }
        .ob-subject-name { font-size: .88rem; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ob-subject-meta { font-size: .72rem; color: var(--text-secondary); }
        .ob-grade-wrap { display: flex; align-items: center; gap: .4rem; flex-shrink: 0; }
        .ob-grade-input {
            width: 60px;
            background: rgba(255,255,255,.06);
            border: 1.5px solid rgba(255,255,255,.15);
            border-radius: 8px;
            color: #fff; font-size: .9rem; font-weight: 700;
            text-align: center; padding: .3rem .25rem;
            outline: none; transition: var(--transition);
        }
        .ob-grade-input:focus { border-color: var(--primary); box-shadow: 0 0 6px var(--primary-glow); }
        .ob-grade-input.pass { border-color: var(--accent-success); color: #6ee7b7; }
        .ob-grade-input.fail { border-color: #ef4444; color: #fca5a5; }
        .ob-grade-status { font-size: .65rem; font-weight: 700; min-width: 34px; text-align: center; }
        .ob-grade-status.pass { color: var(--accent-success); }
        .ob-grade-status.fail { color: #f87171; }

        /* ── Footer nav ── */
        .ob-footer {
            padding: 1.25rem 2rem 2rem;
            display: flex; align-items: center; justify-content: space-between;
            border-top: 1px solid rgba(255,255,255,.06);
        }

        .ob-btn-back {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            color: var(--text-secondary);
            border-radius: 10px;
            padding: .65rem 1.25rem;
            font-size: .88rem; font-weight: 600;
            cursor: pointer; transition: var(--transition);
        }
        .ob-btn-back:hover { background: rgba(255,255,255,.1); color: #fff; }
        .ob-btn-back:disabled { opacity: .3; cursor: not-allowed; }

        .ob-btn-next {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: #fff;
            border-radius: 10px;
            padding: .65rem 1.75rem;
            font-size: .92rem; font-weight: 700;
            cursor: pointer; transition: var(--transition);
            box-shadow: 0 4px 15px var(--primary-glow);
            display: flex; align-items: center; gap: .5rem;
        }
        .ob-btn-next:hover { transform: translateY(-1px); box-shadow: 0 6px 20px var(--primary-glow); }
        .ob-btn-next.finish {
            background: linear-gradient(135deg, var(--accent-success), #059669);
            box-shadow: 0 4px 15px var(--accent-success-glow);
        }
        .ob-btn-next.finish:hover { box-shadow: 0 6px 20px rgba(16,185,129,.5); }

        .ob-progress-text {
            font-size: .78rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* ═══════════════════════════════════════════════════════════
           ANIMATIONS
        ═══════════════════════════════════════════════════════════ */
        @keyframes spin     { 100% { transform: rotate(360deg); } }
        @keyframes fadeIn   { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
        @keyframes pulse    { 0%,100%{opacity:1} 50%{opacity:.7} }
        @keyframes obFadeStep {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        .ob-step-anim { animation: obFadeStep .3s ease-out; }

        /* ═══════════════════════════════════════════════════════════
           GRADE CHART CARD
        ═══════════════════════════════════════════════════════════ */
        .chart-wrapper {
            position: relative;
            width: 100%;
            min-height: 320px;
        }
        .chart-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            color: var(--text-secondary);
            font-size: .92rem;
            gap: .75rem;
            min-height: 200px;
        }
        .chart-empty-icon { font-size: 2.5rem; }
        .chart-legend {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: wrap;
            margin-top: .75rem;
            padding-top: .75rem;
            border-top: 1px solid rgba(255,255,255,.06);
        }
        .chart-legend-item {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .78rem;
            color: var(--text-secondary);
        }
        .chart-legend-dot {
            width: 12px; height: 12px;
            border-radius: 3px;
            flex-shrink: 0;
        }
        .chart-sem-filter {
            display: flex;
            align-items: center;
            gap: .4rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .chart-sem-btn {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            color: var(--text-secondary);
            border-radius: 6px;
            padding: .25rem .65rem;
            font-size: .75rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        .chart-sem-btn:hover { background: rgba(99,102,241,.15); color: #a5b4fc; border-color: rgba(99,102,241,.4); }
        .chart-sem-btn.active { background: rgba(99,102,241,.2); color: #fff; border-color: var(--primary); }
        .chart-peer-info {
            font-size: .75rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: .3rem;
            margin-left: auto;
        }

        /* ═══════════════════════════════════════════════════════════
           SUBJECT GROUP ANALYSIS CARD
        ═══════════════════════════════════════════════════════════ */
        .group-analysis-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            align-items: start;
        }
        @media (max-width: 700px) { .group-analysis-grid { grid-template-columns: 1fr; } }

        /* Radar chart container */
        .radar-wrapper {
            position: relative;
            width: 100%;
            aspect-ratio: 1;
            max-width: 300px;
            margin: 0 auto;
        }

        /* Group table */
        .group-table {
            width: 100%;
            border-collapse: collapse;
        }
        .group-table th {
            font-size: .72rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: .5rem .75rem;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }
        .group-table th:last-child { text-align: right; }
        .group-table td {
            padding: .55rem .75rem;
            font-size: .88rem;
            border-bottom: 1px solid rgba(255,255,255,.04);
            vertical-align: middle;
        }
        .group-table tr:last-child td { border-bottom: none; }
        .group-table tr:hover td { background: rgba(255,255,255,.02); }

        .group-name-cell {
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .group-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .group-name-text { font-weight: 600; color: #e2e8f0; }

        .group-avg-cell { text-align: right; }
        .group-avg-val {
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 800;
        }
        .group-avg-val.excellent { color: #6ee7b7; }
        .group-avg-val.good      { color: #a5f3fc; }
        .group-avg-val.warning   { color: #fcd34d; }
        .group-avg-val.danger    { color: #fca5a5; }
        .group-avg-val.na        { color: var(--text-secondary); }

        /* Progress bar row */
        .group-bar-cell { min-width: 100px; }
        .group-bar-track {
            height: 6px;
            background: rgba(255,255,255,.08);
            border-radius: 3px;
            overflow: hidden;
        }
        .group-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width .8s cubic-bezier(.4,0,.2,1);
        }

        /* Weak alert badge */
        .group-weak-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .65rem;
            font-weight: 700;
            padding: .2rem .5rem;
            border-radius: 50px;
            background: rgba(239,68,68,.12);
            border: 1px solid rgba(239,68,68,.35);
            color: #fca5a5;
            white-space: nowrap;
        }
        .group-ok-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .65rem;
            font-weight: 700;
            padding: .2rem .5rem;
            border-radius: 50px;
            background: rgba(16,185,129,.1);
            border: 1px solid rgba(16,185,129,.3);
            color: #6ee7b7;
            white-space: nowrap;
        }
        .group-na-badge {
            display: inline-flex;
            align-items: center;
            font-size: .65rem;
            font-weight: 700;
            padding: .2rem .5rem;
            border-radius: 50px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            color: var(--text-secondary);
        }

        /* Summary alert boxes */
        .group-summary-alerts {
            margin-top: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: .65rem;
        }
        .group-alert {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            padding: .75rem 1rem;
            border-radius: 10px;
            font-size: .84rem;
            line-height: 1.5;
        }
        .group-alert.danger {
            background: rgba(239,68,68,.07);
            border: 1px solid rgba(239,68,68,.25);
            color: #fca5a5;
        }
        .group-alert.warning {
            background: rgba(245,158,11,.07);
            border: 1px solid rgba(245,158,11,.25);
            color: #fde68a;
        }
        .group-alert.success {
            background: rgba(16,185,129,.07);
            border: 1px solid rgba(16,185,129,.25);
            color: #6ee7b7;
        }
        .group-alert-icon { font-size: 1.1rem; flex-shrink: 0; }
        .group-analysis-empty {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--text-secondary);
            font-size: .9rem;
        }
        .group-analysis-empty-icon { font-size: 2.5rem; margin-bottom: .5rem; }
    </style>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
</head>
<body>

{{-- ══════════════════════════════════════════════════════════════════
     ONBOARDING WIZARD OVERLAY
══════════════════════════════════════════════════════════════════ --}}
<div class="ob-overlay hidden" id="ob-overlay">
    <div class="ob-modal" id="ob-modal">

        {{-- Header --}}
        <div class="ob-header" id="ob-header">
            <div class="ob-step-dots" id="ob-dots">
                <div class="ob-dot active" data-step="0"></div>
                <div class="ob-dot" data-step="1"></div>
                <div class="ob-dot" data-step="2"></div>
                <div class="ob-dot" data-step="3"></div>
            </div>
            {{-- Step label, icon, title, desc được render bởi JS --}}
            <div id="ob-header-content"></div>
        </div>

        {{-- Body --}}
        <div class="ob-body" id="ob-body-content"></div>

        {{-- Footer --}}
        <div class="ob-footer">
            <button class="ob-btn-back" id="ob-btn-back" onclick="obPrev()">← Quay lại</button>
            <span class="ob-progress-text" id="ob-progress-text">Bước 1 / 4</span>
            <button class="ob-btn-next" id="ob-btn-next" onclick="obNext()">
                Tiếp theo →
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     CONFIG PANEL OVERLAY (click ngoài để đóng)
══════════════════════════════════════════════════════════════════ --}}
<div class="config-panel-overlay" id="config-overlay" onclick="closeConfigPanel()"></div>

{{-- Config Panel --}}
<div class="config-panel" id="config-panel">
    <div class="config-panel-header">
        <div class="config-panel-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
            </svg>
            Cấu Hình Chương Trình
        </div>
        <button class="config-panel-close" onclick="closeConfigPanel()">✕</button>
    </div>

    <div class="config-form-grid">
        <div class="input-group">
            <label for="academic_year">Niên khóa</label>
            <select id="academic_year" class="form-select">
                @foreach($academicYears as $year)
                    <option value="{{ $year }}" {{ $year == '2022-2026' ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div class="input-group">
            <label for="program_type">Hệ đào tạo</label>
            <select id="program_type" class="form-select">
                @foreach($programTypes as $type)
                    <option value="{{ $type }}" {{ $type == 'Chính quy' ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="input-group">
            <label for="target_semester">Học kỳ hiện tại</label>
            <select id="target_semester" class="form-select">
                @for($i = 1; $i <= 8; $i++)
                    <option value="{{ $i }}" {{ $i == 3 ? 'selected' : '' }}>Học kỳ {{ $i }}</option>
                @endfor
            </select>
        </div>
        <div class="input-group">
            <label for="target_years">Mục tiêu tốt nghiệp</label>
            <select id="target_years" class="form-select" onchange="updateCreditStats()">
                @for($y = 3; $y <= 6; $y++)
                    <option value="{{ $y }}" {{ $y == 4 ? 'selected' : '' }}>{{ $y }} năm</option>
                @endfor
            </select>
        </div>
    </div>

    {{-- Mini stats --}}
    <div class="config-stats">
        <div class="config-stat">
            <div class="config-stat-val" id="stat-total-credits">{{ $totalCredits }}</div>
            <div class="config-stat-label">Tổng TC</div>
        </div>
        <div class="config-stat">
            <div class="config-stat-val" id="stat-total-semesters">8</div>
            <div class="config-stat-label">Số kỳ</div>
        </div>
        <div class="config-stat">
            <div class="config-stat-val highlight" id="stat-credits-per-sem">—</div>
            <div class="config-stat-label">TC/kỳ</div>
        </div>
        <div class="config-stat">
            <div class="config-stat-val" id="stat-earned-credits">0</div>
            <div class="config-stat-label">Đã tích lũy</div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     HISTORY DRAWER — Slide-in từ bên phải
══════════════════════════════════════════════════════════════════ --}}
<div class="history-drawer-overlay" id="history-drawer-overlay" onclick="closeHistoryDrawer()"></div>
<div class="history-drawer" id="history-drawer">
    <div class="history-drawer-header">
        <div>
            <div class="history-drawer-title">
                📚 Lịch Sử Học Kỳ
            </div>
            <div class="history-drawer-subtitle">Các học kỳ bạn đã hoàn tất</div>
        </div>
        <button class="history-drawer-close" onclick="closeHistoryDrawer()">✕</button>
    </div>
    <div class="history-drawer-body" id="history-drawer-body">
        <div class="history-empty" id="history-empty">
            <span class="history-empty-icon">📖</span>
            <p>Chưa có học kỳ nào được hoàn tất.<br>Ấn <strong>✓ Hoàn tất học kỳ</strong> sau khi kết thúc mỗi kỳ học.</p>
        </div>
        <div id="history-list"></div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     GRADE DRAWER — Slide-in từ bên trái
══════════════════════════════════════════════════════════════════ --}}
<div class="grade-drawer-overlay" id="grade-drawer-overlay" onclick="closeGradeDrawer()"></div>
<div class="grade-drawer" id="grade-drawer">
    <div class="grade-drawer-header">
        <div>
            <div class="grade-drawer-title">
                📝 Nhập Điểm Môn Học
            </div>
            <div class="grade-drawer-subtitle">Điểm &gt; 5.0 được tính là Pass ✅</div>
        </div>
        <button class="grade-drawer-close" onclick="closeGradeDrawer()">✕</button>
    </div>

    <div class="grade-drawer-search">
        <input type="text" id="grade-search" placeholder="🔍 Tìm kiếm môn học..." oninput="filterGradeSearch(this.value)">
    </div>

    <div class="grade-drawer-stats">
        <div class="grade-drawer-stat pass">✓ Pass: <strong id="drawer-pass-count">0</strong></div>
        <div class="grade-drawer-stat fail">✗ Fail: <strong id="drawer-fail-count">0</strong></div>
        <div class="grade-drawer-stat">Chưa nhập: <strong id="drawer-empty-count">0</strong></div>
    </div>

    <div class="grade-drawer-body" id="grade-drawer-body">
        @foreach($subjects as $semName => $semSubjects)
            <div class="drawer-semester-group">
                <div class="drawer-semester-header">Học kỳ chuẩn {{ $semName }}</div>
                <div class="drawer-subjects-list">
                    @foreach($semSubjects as $sub)
                        <div class="drawer-subject-card" id="lbl-sub-{{ $sub->id }}" data-name="{{ strtolower($sub->name) }}">
                            <div class="drawer-subject-info">
                                <div class="drawer-subject-name">{{ $sub->name }}</div>
                                <div class="drawer-subject-meta">{{ $sub->credits }} tín chỉ · {{ $sub->subjectType?->name }}</div>
                            </div>
                            <div class="drawer-grade-wrap">
                                <input type="number"
                                       class="drawer-grade-input grade-input"
                                       id="grade-{{ $sub->id }}"
                                       data-subject-id="{{ $sub->id }}"
                                       data-credits="{{ $sub->credits }}"
                                       min="0" max="10" step="0.1"
                                       placeholder="—"
                                       oninput="onGradeChange({{ $sub->id }}, this)">
                                <span class="drawer-grade-status empty" id="status-{{ $sub->id }}">—</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     MAIN APP
══════════════════════════════════════════════════════════════════ --}}
<div class="container">

    {{-- Navbar --}}
    <div class="navbar">
        <div class="navbar-left">
            <div class="navbar-avatar">👤</div>
            <div>
                <div class="navbar-info name">{{ Auth::user()->fullName ?? Auth::user()->username }}</div>
                <div class="navbar-info meta">MSSV: {{ Auth::user()->student_code ?? '—' }} &nbsp;|&nbsp; {{ Auth::user()->email }}</div>
            </div>
        </div>
        <div class="navbar-right">
            {{-- Nút nhập điểm --}}
            <button class="btn-grades" id="btn-grades" onclick="toggleGradeDrawer()">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                </svg>
                📝 Nhập điểm
                <span class="grade-badge" id="grade-count-badge">0</span>
            </button>

            {{-- Nút cấu hình --}}
            <button class="btn-config" id="btn-config" onclick="toggleConfigPanel()">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                </svg>
                ⚙ Cấu hình
                <span class="config-dot" id="config-dot"></span>
            </button>

            {{-- Nút lịch sử học kỳ --}}
            <button class="btn-history" id="btn-history" onclick="toggleHistoryDrawer()">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                📚 Lịch sử
                <span class="history-badge" id="history-count-badge"></span>
            </button>

            {{-- Đăng xuất --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout">🚪 Đăng xuất</button>
            </form>
        </div>
    </div>

    <header>
        <div class="logo-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5c0 .538-.012 1.05-.034 1.536a3 3 0 1 1-1.133 5.89c-.011.121-.011.234-.011.33v3.244c0 .852-.149 1.658-.439 2.4H3.083c-.29-.742-.439-1.548-.439-2.4V7.926c0-.096 0-.21-.012-.33a3 3 0 1 1-1.132-5.89A35 35 0 0 1 2.5.5zm0 1.25C2.41 2.347 2.33 3.012 2.3 3.652a2 2 0 1 0 1.95 0c-.03-.64-.11-1.305-.182-1.902h-1.57zm11 0h-1.57c.072.597.152 1.262.182 1.902A2 2 0 1 0 13.7 3.652c-.03-.64-.11-1.305-.2-2.402z"/>
            </svg>
            Smart Planner
        </div>
        <h1>Gợi Ý Học Tập Thông Minh</h1>
        <p>Nhập điểm các môn đã học và nhận ngay lộ trình đề xuất tối ưu theo tiến độ của bạn.</p>
    </header>

    <div class="main-grid">
        {{-- Cột phải: Gợi ý --}}
        <div class="results-container">
            <div class="glass-card">
                <h2 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.228-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84a50.53 50.53 0 0 0-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.512A5.985 5.985 0 0 0 6 18v-3m12 3a5.985 5.985 0 0 0 1.007-3.045m-4.257-2.625A55.385 55.385 0 0 1 12 8.443M12 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                    </svg>
                    Môn Học Đề Xuất Học Kỳ Mới
                </h2>

                <div class="loader" id="loader">
                    <div class="spinner"></div>
                    <p style="color: var(--text-secondary); font-size: 0.95rem;">Hệ thống đang phân tích và lập lộ trình...</p>
                </div>

                <div id="suggestions-list" class="suggestions-grid"></div>
            </div>

            {{-- Panel: Môn Đang Học --}}
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

            {{-- Card: Biểu đồ điểm --}}
            <div class="glass-card" id="chart-card">
                <h2 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                    Biểu Đồ Điểm Cá Nhân
                    <span class="chart-peer-info" id="chart-peer-label"></span>
                </h2>

                <div class="chart-sem-filter" id="chart-sem-filter">
                    <button class="chart-sem-btn active" data-sem="all" onclick="filterChartSem('all', this)">Tất cả HK</button>
                </div>

                <div class="chart-wrapper">
                    <div class="chart-empty" id="chart-empty">
                        <span class="chart-empty-icon">📊</span>
                        <p>Nhập điểm môn học để xem biểu đồ so sánh với sinh viên cùng khóa</p>
                    </div>
                    <canvas id="gradeChart" style="display:none;"></canvas>
                </div>

                <div class="chart-legend" id="chart-legend" style="display:none;">
                    <div class="chart-legend-item">
                        <div class="chart-legend-dot" style="background:linear-gradient(135deg,#6366f1,#a855f7);"></div>
                        Điểm của bạn
                    </div>
                    <div class="chart-legend-item">
                        <div class="chart-legend-dot" style="background:rgba(245,158,11,.85);border-radius:50%;"></div>
                        Điểm TB cùng khóa
                    </div>
                    <div class="chart-legend-item">
                        <div class="chart-legend-dot" style="background:#ef4444;border-radius:50%;"></div>
                        Ngưỡng Pass (5.0)
                    </div>
                </div>
            </div>

            {{-- Card: Phân Tích Điểm Theo Nhóm Môn --}}
            <div class="glass-card" id="group-analysis-card">
                <h2 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
                    </svg>
                    Phân Tích Điểm Theo Nhóm Môn
                </h2>

                <div id="group-analysis-content">
                    <div class="group-analysis-empty">
                        <div class="group-analysis-empty-icon">📊</div>
                        <p>Nhập điểm các môn học để xem phân tích điểm theo nhóm</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@php
    $subjectsBySem = $subjects->map(function($group) {
        return $group->map(function($sub) {
            return [
                'id'        => $sub->id,
                'name'      => $sub->name,
                'credits'   => $sub->credits,
                'semName'   => $sub->semester?->name ?? '?',
                'typeName'  => $sub->subjectType?->name ?? '',
                'groupName' => $sub->subjectGroup?->name ?? 'Khác',
            ];
        })->values();
    });
@endphp

{{-- Save indicator --}}
<div class="save-indicator" id="save-indicator"></div>

{{-- Data cho onboarding wizard (từ Blade) --}}
<script>
    // ─── Dữ liệu truyền từ server ────────────────────────────────────────────────
    const ACADEMIC_YEARS  = @json($academicYears);
    const PROGRAM_TYPES   = @json($programTypes);
    const SUBJECTS_BY_SEM = @json($subjectsBySem);
    const TOTAL_CREDITS   = {{ $totalCredits }};
    const CSRF_TOKEN      = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // ─── State chính ─────────────────────────────────────────────────────────────
    let fetchTimer    = null;
    let saveTimer     = null;
    let prefTimer     = null;
    let currentCourses = [];
    let syncLock      = false;

    // ─── State Onboarding ─────────────────────────────────────────────────────────
    let obStep       = 0;          // bước hiện tại (0-3)
    let obData = {
        academic_year:    null,
        program_type:     null,
        current_semester: null,
        target_years:     null,
        grades: {}   // { subject_id: grade_value }
    };

    // ═══════════════════════════════════════════════════════════════
    // ONBOARDING WIZARD
    // ═══════════════════════════════════════════════════════════════
    const OB_STEPS = [
        {
            label: 'Bước 1 / 4',
            icon: '🎓',
            iconBg: 'rgba(99,102,241,.2)',
            title: 'Chào mừng bạn!',
            desc: 'Hãy cho chúng tôi biết bạn đang theo học chương trình nào để hệ thống gợi ý chính xác nhất.',
        },
        {
            label: 'Bước 2 / 4',
            icon: '📅',
            iconBg: 'rgba(168,85,247,.2)',
            title: 'Bạn đang học kỳ nào?',
            desc: 'Chọn học kỳ hiện tại của bạn để hệ thống xác định các môn phù hợp với tiến độ.',
        },
        {
            label: 'Bước 3 / 4',
            icon: '📝',
            iconBg: 'rgba(245,158,11,.15)',
            title: 'Điểm số của bạn',
            desc: 'Nhập điểm các môn bạn đã học. Chỉ nhập những môn đã có điểm.',
        },
        {
            label: 'Bước 4 / 4',
            icon: '🏆',
            iconBg: 'rgba(16,185,129,.15)',
            title: 'Mục tiêu tốt nghiệp',
            desc: 'Bạn muốn hoàn thành chương trình trong bao nhiêu năm?',
        },
    ];

    function renderObDots() {
        document.querySelectorAll('.ob-dot').forEach((dot, i) => {
            dot.classList.remove('active', 'done');
            if (i < obStep)      dot.classList.add('done');
            else if (i === obStep) dot.classList.add('active');
        });
    }

    function renderObHeader() {
        const s = OB_STEPS[obStep];
        document.getElementById('ob-header-content').innerHTML = `
            <div class="ob-step-label">${s.label}</div>
            <div class="ob-icon" style="background:${s.iconBg}">${s.icon}</div>
            <div class="ob-title">${s.title}</div>
            <p class="ob-desc">${s.desc}</p>
        `;
    }

    function renderObBody() {
        const body = document.getElementById('ob-body-content');
        body.classList.remove('ob-step-anim');
        void body.offsetWidth; // reflow để restart animation
        body.classList.add('ob-step-anim');

        if (obStep === 0) {
            // ── Bước 1: Niên khóa + Hệ đào tạo ──
            const yearOpts  = ACADEMIC_YEARS.map(y =>
                `<option value="${y}" ${obData.academic_year === y ? 'selected' : ''}>${y}</option>`
            ).join('');
            const typeOpts  = PROGRAM_TYPES.map(t =>
                `<option value="${t}" ${obData.program_type === t ? 'selected' : ''}>${t}</option>`
            ).join('');
            body.innerHTML = `
                <div class="ob-form-grid">
                    <div class="ob-input-group">
                        <label>Niên khóa</label>
                        <select class="ob-select" id="ob-academic-year" onchange="obData.academic_year=this.value">
                            <option value="">-- Chọn niên khóa --</option>
                            ${yearOpts}
                        </select>
                    </div>
                    <div class="ob-input-group">
                        <label>Hệ đào tạo</label>
                        <select class="ob-select" id="ob-program-type" onchange="obData.program_type=this.value">
                            <option value="">-- Chọn hệ đào tạo --</option>
                            ${typeOpts}
                        </select>
                    </div>
                </div>`;

        } else if (obStep === 1) {
            // ── Bước 2: Chọn học kỳ ──
            const btns = Array.from({length: 8}, (_, i) => i+1).map(i => `
                <button class="ob-sem-btn ${obData.current_semester === i ? 'selected' : ''}"
                        onclick="obSelectSem(${i}, this)">
                    Học kỳ ${i}
                </button>`).join('');
            body.innerHTML = `<div class="ob-semester-grid">${btns}</div>`;

        } else if (obStep === 2) {
            // ── Bước 3: Nhập điểm ──
            let sectionsHtml = '';
            for (const [semName, subjects] of Object.entries(SUBJECTS_BY_SEM)) {
                const rows = subjects.map(sub => {
                    const g = obData.grades[sub.id];
                    const cls = g === undefined ? '' : (g > 5 ? 'pass' : 'fail');
                    const statusTxt = g === undefined ? '' : (g > 5 ? '✓ Pass' : '✗ Fail');
                    const statusCls = g === undefined ? '' : (g > 5 ? 'pass' : 'fail');
                    return `
                        <div class="ob-subject-row ${g !== undefined ? 'has-grade' : ''}" id="ob-row-${sub.id}">
                            <div class="ob-subject-info">
                                <div class="ob-subject-name">${sub.name}</div>
                                <div class="ob-subject-meta">${sub.credits} tín chỉ · HK chuẩn ${sub.semName}</div>
                            </div>
                            <div class="ob-grade-wrap">
                                <input type="number" class="ob-grade-input ${cls}"
                                       id="ob-grade-${sub.id}"
                                       min="0" max="10" step="0.1" placeholder="—"
                                       value="${g !== undefined ? g : ''}"
                                       oninput="obGradeChange(${sub.id}, this)">
                                <span class="ob-grade-status ${statusCls}" id="ob-gstatus-${sub.id}">${statusTxt}</span>
                            </div>
                        </div>`;
                }).join('');
                sectionsHtml += `
                    <div class="ob-semester-section">
                        <div class="ob-semester-section-title">Học kỳ chuẩn ${semName}</div>
                        ${rows}
                    </div>`;
            }
            body.innerHTML = `
                <div class="ob-warning">
                    <span class="ob-warning-icon">⚠️</span>
                    <p><strong>Lưu ý quan trọng:</strong> Chỉ nhập điểm những môn bạn <strong>đã học và có kết quả</strong>. Dữ liệu này ảnh hưởng trực tiếp đến độ chính xác của các đề xuất môn học.</p>
                </div>
                <div class="ob-subjects-scroll">${sectionsHtml}</div>`;

        } else if (obStep === 3) {
            // ── Bước 4: Mục tiêu tốt nghiệp ──
            const years = [3, 4, 5, 6];
            const descs = { 3: 'Rất nhanh', 4: 'Tiêu chuẩn', 5: 'Bình thường', 6: 'Linh hoạt' };
            const btns = years.map(y => `
                <button class="ob-year-btn ${obData.target_years === y ? 'selected' : ''}"
                        onclick="obSelectYear(${y}, this)">
                    ${y} năm
                    <small>${descs[y]}</small>
                </button>`).join('');
            body.innerHTML = `
                <div class="ob-year-grid">${btns}</div>
                <p style="margin-top:1rem; font-size:.82rem; color:var(--text-secondary); text-align:center;">
                    Thông thường chương trình Đại học 4 năm gồm 8 học kỳ.
                </p>`;
        }

        // Footer buttons
        const btnBack = document.getElementById('ob-btn-back');
        const btnNext = document.getElementById('ob-btn-next');
        const progText = document.getElementById('ob-progress-text');
        btnBack.disabled = obStep === 0;
        progText.textContent = `Bước ${obStep + 1} / 4`;

        if (obStep === 3) {
            btnNext.textContent = '🎉 Hoàn thành!';
            btnNext.className = 'ob-btn-next finish';
        } else {
            btnNext.innerHTML = 'Tiếp theo →';
            btnNext.className = 'ob-btn-next';
        }
    }

    function obSelectSem(i, el) {
        obData.current_semester = i;
        document.querySelectorAll('.ob-sem-btn').forEach(b => b.classList.remove('selected'));
        el.classList.add('selected');
    }

    function obSelectYear(y, el) {
        obData.target_years = y;
        document.querySelectorAll('.ob-year-btn').forEach(b => b.classList.remove('selected'));
        el.classList.add('selected');
    }

    function obGradeChange(id, input) {
        const val = parseFloat(input.value);
        const status = document.getElementById(`ob-gstatus-${id}`);
        const row = document.getElementById(`ob-row-${id}`);
        input.classList.remove('pass', 'fail');
        status.classList.remove('pass', 'fail');
        row.classList.remove('has-grade');

        if (input.value === '' || isNaN(val)) {
            delete obData.grades[id];
            status.textContent = '';
        } else {
            obData.grades[id] = val;
            row.classList.add('has-grade');
            if (val > 5) {
                input.classList.add('pass');
                status.classList.add('pass');
                status.textContent = '✓ Pass';
            } else {
                input.classList.add('fail');
                status.classList.add('fail');
                status.textContent = '✗ Fail';
            }
        }
    }

    function obNext() {
        // Validate từng bước
        if (obStep === 0) {
            const yr = document.getElementById('ob-academic-year')?.value;
            const pt = document.getElementById('ob-program-type')?.value;
            if (!yr || !pt) { showToast('Vui lòng chọn đầy đủ niên khóa và hệ đào tạo!', 'error'); return; }
            obData.academic_year = yr;
            obData.program_type  = pt;
        }
        if (obStep === 1 && !obData.current_semester) {
            showToast('Vui lòng chọn học kỳ hiện tại!', 'error'); return;
        }
        if (obStep === 3) {
            if (!obData.target_years) { showToast('Vui lòng chọn mục tiêu tốt nghiệp!', 'error'); return; }
            obFinish();
            return;
        }
        obStep++;
        renderObDots();
        renderObHeader();
        renderObBody();
    }

    function obPrev() {
        if (obStep === 0) return;
        obStep--;
        renderObDots();
        renderObHeader();
        renderObBody();
    }

    async function obFinish() {
        const btnNext = document.getElementById('ob-btn-next');
        btnNext.disabled = true;
        btnNext.textContent = '⏳ Đang lưu...';

        try {
            // 1. Lưu preferences
            await fetch('/preferences/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: JSON.stringify({
                    academic_year:    obData.academic_year,
                    program_type:     obData.program_type,
                    current_semester: obData.current_semester,
                    target_years:     obData.target_years,
                }),
            });

            // 2. Lưu điểm số nếu có
            const gradesToSave = Object.entries(obData.grades).map(([sid, grade]) => ({
                subject_id: parseInt(sid),
                grade: grade,
            }));
            if (gradesToSave.length > 0) {
                await fetch('/grades/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify(gradesToSave),
                });
            }

            // 3. Đóng wizard và áp dụng lên UI chính
            closeOnboarding();
            applyPreferencesToUI(obData);
            showToast('Chào mừng! Đã thiết lập chương trình của bạn 🎉', 'success');

        } catch(err) {
            showToast('Có lỗi xảy ra, vui lòng thử lại!', 'error');
            btnNext.disabled = false;
            btnNext.textContent = '🎉 Hoàn thành!';
        }
    }

    function openOnboarding() {
        obStep = 0;
        const overlay = document.getElementById('ob-overlay');
        overlay.classList.remove('hidden');
        renderObDots();
        renderObHeader();
        renderObBody();
    }

    function closeOnboarding() {
        document.getElementById('ob-overlay').classList.add('hidden');
    }

    function applyPreferencesToUI(data) {
        if (data.academic_year)    document.getElementById('academic_year').value    = data.academic_year;
        if (data.program_type)     document.getElementById('program_type').value     = data.program_type;
        if (data.current_semester) document.getElementById('target_semester').value  = data.current_semester;
        if (data.target_years)     document.getElementById('target_years').value     = data.target_years;

        // Áp dụng điểm từ wizard vào UI chính
        Object.entries(data.grades).forEach(([sid, grade]) => {
            const input = document.getElementById(`grade-${sid}`);
            if (input) { input.value = grade; onGradeChange(parseInt(sid), input, true); }
        });

        // Ẩn dot "chưa cấu hình"
        document.getElementById('config-dot')?.remove();

        updateCreditStats();
        fetchSuggestions();
    }

    // ═══════════════════════════════════════════════════════════════
    // CONFIG PANEL
    // ═══════════════════════════════════════════════════════════════
    function toggleConfigPanel() {
        const panel   = document.getElementById('config-panel');
        const overlay = document.getElementById('config-overlay');
        const isOpen  = panel.classList.contains('open');
        if (isOpen) {
            panel.classList.remove('open');
            overlay.classList.remove('open');
        } else {
            panel.classList.add('open');
            overlay.classList.add('open');
        }
    }

    function closeConfigPanel() {
        document.getElementById('config-panel').classList.remove('open');
        document.getElementById('config-overlay').classList.remove('open');
    }

    // ═══════════════════════════════════════════════════════════════
    // GRADE DRAWER
    // ═══════════════════════════════════════════════════════════════
    function toggleGradeDrawer() {
        const drawer  = document.getElementById('grade-drawer');
        const overlay = document.getElementById('grade-drawer-overlay');
        const isOpen  = drawer.classList.contains('open');
        if (isOpen) {
            drawer.classList.remove('open');
            overlay.classList.remove('open');
        } else {
            drawer.classList.add('open');
            overlay.classList.add('open');
            // Reset search khi mở
            const searchEl = document.getElementById('grade-search');
            if (searchEl) { searchEl.value = ''; filterGradeSearch(''); }
        }
    }

    function closeGradeDrawer() {
        document.getElementById('grade-drawer').classList.remove('open');
        document.getElementById('grade-drawer-overlay').classList.remove('open');
    }

    function filterGradeSearch(query) {
        const q = query.toLowerCase().trim();
        document.querySelectorAll('.drawer-subject-card').forEach(card => {
            const name = card.dataset.name || '';
            card.classList.toggle('hidden-search', q !== '' && !name.includes(q));
        });
        // Ẩn/hiện tiêu đề nhóm nếu không còn môn nào hiển thị
        document.querySelectorAll('.drawer-semester-group').forEach(group => {
            const visible = group.querySelectorAll('.drawer-subject-card:not(.hidden-search)').length > 0;
            group.style.display = visible ? '' : 'none';
        });
    }

    function updateDrawerStats() {
        let pass = 0, fail = 0, empty = 0;
        document.querySelectorAll('.grade-input').forEach(input => {
            const val = parseFloat(input.value);
            if (input.value === '' || isNaN(val)) empty++;
            else if (val > 5.0) pass++;
            else fail++;
        });
        const passEl  = document.getElementById('drawer-pass-count');
        const failEl  = document.getElementById('drawer-fail-count');
        const emptyEl = document.getElementById('drawer-empty-count');
        if (passEl)  passEl.textContent  = pass;
        if (failEl)  failEl.textContent  = fail;
        if (emptyEl) emptyEl.textContent = empty;

        // Badge trên nút navbar
        const badge = document.getElementById('grade-count-badge');
        if (badge) {
            const filled = pass + fail;
            badge.textContent = filled;
            badge.classList.toggle('visible', filled > 0);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // PREFERENCES (lưu & tải)
    // ═══════════════════════════════════════════════════════════════
    function savePreferences() {
        clearTimeout(prefTimer);
        prefTimer = setTimeout(async () => {
            try {
                const payload = {
                    academic_year:    document.getElementById('academic_year').value,
                    program_type:     document.getElementById('program_type').value,
                    current_semester: parseInt(document.getElementById('target_semester').value),
                    target_years:     parseInt(document.getElementById('target_years').value),
                };
                const res = await fetch('/preferences/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify(payload),
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                showSaveIndicator('saved', 'Đã lưu cấu hình ✓');
            } catch (err) {
                showSaveIndicator('error', 'Lưu cấu hình thất bại');
            }
        }, 500);
    }

    async function loadPreferences() {
        try {
            const res = await fetch('/preferences', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return null;
            const prefs = await res.json();
            return prefs;
        } catch (err) {
            console.warn('[Preference load error]', err);
            return null;
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // SAVE INDICATOR
    // ═══════════════════════════════════════════════════════════════
    function showSaveIndicator(state, msg) {
        const el = document.getElementById('save-indicator');
        if (!el) return;
        el.className = 'save-indicator';
        if (state === 'hide') { el.style.display = 'none'; return; }
        const icons = { saving: '💾', saved: '✓', error: '⚠️' };
        const texts = { saving: 'Đang lưu...', saved: 'Đã lưu', error: 'Lưu thất bại' };
        el.classList.add(state);
        el.textContent = `${icons[state]} ${msg || texts[state]}`;
        if (state === 'saved') setTimeout(() => showSaveIndicator('hide'), 2500);
    }

    // ═══════════════════════════════════════════════════════════════
    // GRADE SAVE / LOAD
    // ═══════════════════════════════════════════════════════════════
    function autoSaveGrade(subjectId, grade) {
        clearTimeout(saveTimer);
        showSaveIndicator('saving');
        saveTimer = setTimeout(async () => {
            try {
                const res = await fetch('/grades/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify([{ subject_id: subjectId, grade: grade }]),
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                showSaveIndicator('saved');
                scheduleChartRefresh(); // Cập nhật biểu đồ sau 2s
            } catch (err) { showSaveIndicator('error'); }
        }, 800);
    }

    async function saveMultipleGrades(grades) {
        if (!grades || grades.length === 0) return;
        showSaveIndicator('saving', `Đang lưu ${grades.length} môn...`);
        try {
            const res = await fetch('/grades/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: JSON.stringify(grades),
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            showSaveIndicator('saved', `Đã lưu ${grades.length} môn ✓`);
        } catch (err) { showSaveIndicator('error', 'Lưu điểm thất bại'); }
    }

    async function loadGradesFromDB() {
        try {
            const res = await fetch('/grades', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const grades = await res.json();
            grades.forEach(({ subject_id, grade }) => {
                const input = document.getElementById(`grade-${subject_id}`);
                if (!input) return;
                if (grade !== null && grade !== undefined) {
                    input.value = grade;
                    onGradeChange(subject_id, input, true);
                }
            });
            updateEarnedCredits();
            updateDrawerStats();
        } catch (err) { console.warn('[Grade load error]', err); }
    }

    // ═══════════════════════════════════════════════════════════════
    // CREDIT STATS
    // ═══════════════════════════════════════════════════════════════
    function updateCreditStats() {
        const years   = parseInt(document.getElementById('target_years').value);
        const totalSem = years * 2;
        document.getElementById('stat-total-semesters').textContent = totalSem;
        updateEarnedCredits();
    }

    function updateEarnedCredits() {
        let earned = 0;
        document.querySelectorAll('.grade-input').forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val) && val > 5.0) earned += parseInt(input.dataset.credits || 0);
        });
        currentCourses.forEach(c => { if (c.grade !== null && c.grade > 5.0) earned += (c.credits || 0); });
        document.getElementById('stat-earned-credits').textContent = earned;

        const years      = parseInt(document.getElementById('target_years').value);
        const totalSem   = years * 2;
        const currentSem = parseInt(document.getElementById('target_semester').value);
        const remaining  = Math.max(0, TOTAL_CREDITS - earned);
        const remSem     = Math.max(1, totalSem - (currentSem - 1));
        const perSem     = remaining === 0 ? 0 : Math.ceil(remaining / remSem);
        document.getElementById('stat-credits-per-sem').textContent = perSem;
    }

    // ═══════════════════════════════════════════════════════════════
    // GRADE CHANGE HANDLERS
    // ═══════════════════════════════════════════════════════════════
    function onGradeChange(id, input, skipSave = false) {
        const card   = document.getElementById(`lbl-sub-${id}`);
        const status = document.getElementById(`status-${id}`);
        const val    = parseFloat(input.value);
        const isInDrawer = input.classList.contains('drawer-grade-input');

        // ── Cập nhật card wrapper ─────────────────────────────────────
        if (card) {
            card.classList.remove('pass', 'fail');
            if (!isNaN(val) && val > 5.0) card.classList.add('pass');
            else if (!isNaN(val) && val <= 5.0 && input.value !== '') card.classList.add('fail');
        }

        // ── Cập nhật input styling ────────────────────────────────────
        input.classList.remove('is-pass', 'is-fail');

        // ── Cập nhật status label ─────────────────────────────────────
        if (status) {
            status.classList.remove('pass', 'fail', 'empty');
            if (input.value === '' || isNaN(val)) {
                status.textContent = isInDrawer ? '—' : 'Chưa nhập';
                status.classList.add('empty');
            } else if (val > 5.0) {
                input.classList.add('is-pass');
                status.textContent = '✓ Pass'; status.classList.add('pass');
            } else {
                input.classList.add('is-fail');
                status.textContent = '✗ Fail'; status.classList.add('fail');
            }
        }

        // ── Sync sang panel Môn Đang Học ─────────────────────────────
        if (!syncLock) {
            syncLock = true;
            const ccInput = document.getElementById(`cc-grade-${id}`);
            if (ccInput && ccInput.value !== input.value) { ccInput.value = input.value; onCCGradeChange(id, ccInput); }
            syncLock = false;
        }

        if (!skipSave) { const gradeValue = isNaN(val) ? null : val; autoSaveGrade(id, gradeValue); }
        clearTimeout(fetchTimer);
        fetchTimer = setTimeout(fetchSuggestions, 400);
        updateEarnedCredits();
        updateDrawerStats();
    }

    function onCCGradeChange(id, input) {
        const val    = parseFloat(input.value);
        const item   = document.getElementById(`cc-item-${id}`);
        const status = document.getElementById(`cc-status-${id}`);
        item.classList.remove('cc-pass', 'cc-fail');
        input.classList.remove('is-pass', 'is-fail');
        status.classList.remove('pass', 'fail', 'empty');
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
        updateCompleteButton();
        clearTimeout(fetchTimer);
        fetchTimer = setTimeout(fetchSuggestions, 400);
        updateEarnedCredits();
    }

    // ═══════════════════════════════════════════════════════════════
    // CURRENT COURSES
    // ═══════════════════════════════════════════════════════════════
    function updateCompleteButton() {
        const btn = document.getElementById('btn-complete');
        if (!btn) return;
        const allFilled = currentCourses.length > 0 && currentCourses.every(c => c.grade !== null && c.grade !== undefined);
        btn.disabled = !allFilled;
    }

    function addToCurrentCourses(subject) {
        if (currentCourses.find(c => c.id == subject.id)) return;
        currentCourses.push({ id: subject.id, name: subject.name, credits: subject.credits, semesterName: subject.semester?.name || '?', grade: null });
        renderCurrentCourses();
        const btn = document.getElementById(`btn-add-${subject.id}`);
        if (btn) { btn.textContent = '✓ Đã thêm'; btn.classList.add('added'); }
        lockLeftInput(subject.id);
    }

    function removeCourse(id) {
        currentCourses = currentCourses.filter(c => c.id != id);
        renderCurrentCourses();
        const btn = document.getElementById(`btn-add-${id}`);
        if (btn) { btn.innerHTML = '+ Thêm'; btn.classList.remove('added'); }
        unlockLeftInput(id);
        clearTimeout(fetchTimer);
        fetchTimer = setTimeout(fetchSuggestions, 400);
        updateEarnedCredits();
    }

    function lockLeftInput(id) {
        const input = document.getElementById(`grade-${id}`);
        if (!input) return;
        input.classList.add('is-studying');
        const wrap = input.parentElement;
        if (wrap) {
            wrap.classList.add('is-locked');
            if (!wrap.querySelector('.studying-label')) {
                const badge = document.createElement('span');
                badge.className = 'studying-label';
                badge.innerHTML = '📖 Đang học';
                wrap.appendChild(badge);
            }
        }
        const status = document.getElementById(`status-${id}`);
        if (status) { status.dataset.prevText = status.textContent; status.textContent = ''; }
    }

    function unlockLeftInput(id) {
        const input = document.getElementById(`grade-${id}`);
        if (!input) return;
        input.classList.remove('is-studying');
        const wrap = input.parentElement;
        if (wrap) { wrap.classList.remove('is-locked'); const badge = wrap.querySelector('.studying-label'); if (badge) badge.remove(); }
        onGradeChange(id, input);
    }

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
                           id="cc-grade-${c.id}" min="0" max="10" step="0.1" placeholder="Điểm"
                           value="${c.grade !== null ? c.grade : ''}"
                           oninput="onCCGradeChange(${c.id}, this)">
                    <span class="cc-status ${c.grade !== null && c.grade > 5 ? 'pass' : c.grade !== null ? 'fail' : 'empty'}" id="cc-status-${c.id}">${c.grade !== null && c.grade > 5 ? 'Pass' : c.grade !== null ? 'Fail' : '—'}</span>
                    <button class="btn-remove" onclick="removeCourse(${c.id})" title="Xóa">✕</button>
                </div>
            </div>
        `).join('');
    }

    // ═══════════════════════════════════════════════════════════════
    // SUGGESTIONS
    // ═══════════════════════════════════════════════════════════════
    function getPassedSubjectIds() {
        const passed = new Set();
        document.querySelectorAll('.grade-input').forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val) && val > 5.0) passed.add(input.dataset.subjectId);
        });
        currentCourses.forEach(c => { if (c.grade !== null && c.grade > 5.0) passed.add(String(c.id)); });
        return [...passed].join(',');
    }

    function getFailedSubjectIds() {
        const failed = new Set();
        document.querySelectorAll('.grade-input').forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val) && val > 0 && val <= 5.0) failed.add(parseInt(input.dataset.subjectId));
        });
        currentCourses.forEach(c => { if (c.grade !== null && c.grade > 0 && c.grade <= 5.0) failed.add(c.id); });
        return failed;
    }

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

    // ═══════════════════════════════════════════════════════════════
    // COMPLETE SEMESTER
    // ═══════════════════════════════════════════════════════════════
    function completeSemester() {
        const unfilled = currentCourses.filter(c => c.grade === null || c.grade === undefined);
        if (unfilled.length > 0) { showToast(`Còn ${unfilled.length} môn chưa điền điểm!`, 'error'); return; }
        if (currentCourses.length === 0) { showToast('Chưa có môn nào trong danh sách!', 'error'); return; }

        const snapshot = currentCourses.map(c => ({ id: c.id, grade: c.grade }));
        const sel = document.getElementById('target_semester');
        const cur = parseInt(sel.value);

        // Lưu lịch sử học kỳ vào DB
        saveSemesterHistory(cur, snapshot);

        currentCourses = [];
        renderCurrentCourses();
        snapshot.forEach(({ id, grade }) => {
            const input = document.getElementById(`grade-${id}`);
            if (!input) return;
            input.classList.remove('is-studying');
            const wrap = input.parentElement;
            if (wrap) { wrap.classList.remove('is-locked'); const badge = wrap.querySelector('.studying-label'); if (badge) badge.remove(); }
            input.value = grade;
            onGradeChange(id, input, true);
        });
        const gradesToSave = snapshot.map(({ id, grade }) => ({ subject_id: id, grade: grade }));
        saveMultipleGrades(gradesToSave);
        if (cur < 8) { sel.value = cur + 1; } else { showToast('Đã hoàn thành toàn bộ chương trình! 🎓', 'success'); }
        savePreferences();
        fetchSuggestions();
        updateEarnedCredits();
        scheduleChartRefresh();
        showToast(`Hoàn tất học kỳ ${cur}! Đã chuyển sang học kỳ ${Math.min(cur + 1, 8)}.`, 'success');
    }

    async function saveSemesterHistory(semesterNumber, snapshot) {
        try {
            const courses = snapshot.map(({ id, grade }) => ({ subject_id: id, grade: grade }));
            const payload = {
                semester_number: semesterNumber,
                academic_year: document.getElementById('academic_year')?.value || null,
                program_type:  document.getElementById('program_type')?.value  || null,
                courses,
            };
            const res = await fetch('/semester-history/complete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            // Reload panel lịch sử
            loadSemesterHistory();
        } catch (err) {
            console.warn('[Lưu lịch sử thất bại]', err);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // TOAST
    // ═══════════════════════════════════════════════════════════════
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

    // ═══════════════════════════════════════════════════════════════
    // EVENT LISTENERS (config panel dropdowns)
    // ═══════════════════════════════════════════════════════════════
    document.getElementById('academic_year').addEventListener('change', () => {
        clearTimeout(saveTimer); showSaveIndicator('hide'); savePreferences(); fetchSuggestions();
    });
    document.getElementById('program_type').addEventListener('change', () => {
        clearTimeout(saveTimer); showSaveIndicator('hide'); savePreferences(); fetchSuggestions();
    });
    document.getElementById('target_semester').addEventListener('change', () => {
        clearTimeout(saveTimer); showSaveIndicator('hide'); savePreferences(); updateEarnedCredits(); fetchSuggestions();
    });
    document.getElementById('target_years').addEventListener('change', () => {
        clearTimeout(saveTimer); showSaveIndicator('hide'); savePreferences(); updateCreditStats();
    });

    // ═══════════════════════════════════════════════════════════════
    // INIT
    // ═══════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', async () => {
        updateCreditStats();
        const prefs = await loadPreferences();

        // Kiểm tra xem user đã từng cấu hình chưa (có ít nhất academic_year)
        const hasConfig = prefs && prefs.academic_year;

        if (!hasConfig) {
            // Lần đầu vào → mở onboarding wizard
            openOnboarding();
        } else {
            // Đã có config → áp dụng preferences và tải điểm
            if (prefs.academic_year)    document.getElementById('academic_year').value    = prefs.academic_year;
            if (prefs.program_type)     document.getElementById('program_type').value     = prefs.program_type;
            if (prefs.current_semester) document.getElementById('target_semester').value  = prefs.current_semester;
            if (prefs.target_years)     document.getElementById('target_years').value     = prefs.target_years;

            // Ẩn dot "chưa cấu hình"
            document.getElementById('config-dot')?.remove();

            updateCreditStats();
            await loadGradesFromDB();
            fetchSuggestions();
            fetchChartData();   // Tải biểu đồ sau khi có điểm
        }
    });
</script>

<script>
// ═══════════════════════════════════════════════════════════════
// GRADE CHART (Chart.js)
// ═══════════════════════════════════════════════════════════════
let gradeChartInstance = null;
let chartRawData = null;    // lưu data gốc để filter theo HK
let chartTimer = null;

async function fetchChartData() {
    try {
        const res = await fetch('/grades/chart-data', { headers: { 'Accept': 'application/json' } });
        if (!res.ok) return;
        chartRawData = await res.json();
        buildChartSemFilter(chartRawData.semesters);
        renderGradeChart(chartRawData, 'all');
    } catch (err) {
        console.warn('[Chart error]', err);
    }
}

function buildChartSemFilter(semesters) {
    const uniqueSems = [...new Set(semesters)].sort((a, b) => parseInt(a) - parseInt(b));
    const container = document.getElementById('chart-sem-filter');
    if (!container) return;
    // Xóa cũ (chỉ giữ nút "Tất cả")
    container.innerHTML = '<button class="chart-sem-btn active" data-sem="all" onclick="filterChartSem(\'all\', this)">Tất cả HK</button>';
    uniqueSems.forEach(sem => {
        const btn = document.createElement('button');
        btn.className = 'chart-sem-btn';
        btn.dataset.sem = sem;
        btn.textContent = `HK ${sem}`;
        btn.onclick = function() { filterChartSem(sem, this); };
        container.appendChild(btn);
    });
}

function filterChartSem(sem, btn) {
    document.querySelectorAll('.chart-sem-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    if (chartRawData) renderGradeChart(chartRawData, sem);
}

function renderGradeChart(data, semFilter = 'all') {
    const { labels, my_grades, avg_grades, semesters, academic_year, peer_count } = data;

    // Filter theo học kỳ nếu cần
    let idxs = labels.map((_, i) => i);
    if (semFilter !== 'all') {
        idxs = idxs.filter(i => String(semesters[i]) === String(semFilter));
    }

    const filteredLabels   = idxs.map(i => labels[i]);
    const filteredMy       = idxs.map(i => my_grades[i]);
    const filteredAvg      = idxs.map(i => avg_grades[i]);

    const emptyEl  = document.getElementById('chart-empty');
    const canvas   = document.getElementById('gradeChart');
    const legendEl = document.getElementById('chart-legend');
    const peerEl   = document.getElementById('chart-peer-label');

    if (!filteredLabels.length) {
        emptyEl.style.display = 'flex';
        canvas.style.display  = 'none';
        if (legendEl) legendEl.style.display = 'none';
        return;
    }

    emptyEl.style.display  = 'none';
    canvas.style.display   = 'block';
    if (legendEl) legendEl.style.display = 'flex';

    // Peer info label
    if (peerEl) {
        if (peer_count > 1) {
            peerEl.innerHTML = `👥 So sánh với <strong style="color:#fff">${peer_count}</strong> SV cùng khóa ${academic_year || ''}`;
        } else {
            peerEl.innerHTML = '<span style="color:rgba(255,255,255,.3)">Chưa có dữ liệu khóa khác</span>';
        }
    }

    // Màu cột theo pass/fail
    const barColors = filteredMy.map(v =>
        v === null ? 'rgba(255,255,255,0.1)'
        : v > 5.0  ? 'rgba(99,102,241,0.85)'
        :            'rgba(239,68,68,0.8)'
    );
    const borderColors = filteredMy.map(v =>
        v === null ? 'rgba(255,255,255,0.2)'
        : v > 5.0  ? 'rgba(167,139,250,1)'
        :            'rgba(252,165,165,1)'
    );

    // Destroy cũ trước khi render mới
    if (gradeChartInstance) { gradeChartInstance.destroy(); gradeChartInstance = null; }

    const ctx = canvas.getContext('2d');

    // Gradient cho cột pass
    const gradPass = ctx.createLinearGradient(0, 0, 0, 400);
    gradPass.addColorStop(0, 'rgba(139,92,246,0.95)');
    gradPass.addColorStop(1, 'rgba(99,102,241,0.6)');

    const gradFail = ctx.createLinearGradient(0, 0, 0, 400);
    gradFail.addColorStop(0, 'rgba(239,68,68,0.9)');
    gradFail.addColorStop(1, 'rgba(239,68,68,0.4)');

    const barBg = filteredMy.map(v => v > 5.0 ? gradPass : gradFail);

    gradeChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: filteredLabels.map(l => l.length > 20 ? l.substring(0, 18) + '…' : l),
            datasets: [
                {
                    label: 'Điểm của bạn',
                    data: filteredMy,
                    backgroundColor: barBg,
                    borderColor: borderColors,
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false,
                    order: 2,
                },
                {
                    label: 'Điểm TB cùng khóa',
                    data: filteredAvg,
                    type: 'line',
                    borderColor: 'rgba(245,158,11,0.9)',
                    backgroundColor: 'rgba(245,158,11,0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(245,158,11,1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    tension: 0.3,
                    fill: false,
                    order: 1,
                    spanGaps: true,
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 600, easing: 'easeInOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(10,14,30,0.95)',
                    borderColor: 'rgba(99,102,241,0.4)',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        title: (items) => filteredLabels[items[0].dataIndex],
                        label: (item) => {
                            if (item.dataset.label === 'Điểm của bạn') {
                                const v = item.raw;
                                return v === null ? '  Chưa nhập' : `  Của bạn: ${v} ${v > 5 ? '✓ Pass' : '✗ Fail'}`;
                            }
                            return item.raw !== null ? `  TB khóa: ${item.raw}` : '  Chưa có dữ liệu TB';
                        },
                        afterBody: (items) => {
                            const idx = items[0].dataIndex;
                            const sem = semFilter === 'all' ? semesters[idxs[idx]] : semFilter;
                            return [`  Học kỳ chuẩn: ${sem}`];
                        },
                    }
                },
                // Đường ngang ngưỡng Pass 5.0
                annotation: undefined,
            },
            scales: {
                x: {
                    ticks: {
                        color: 'rgba(255,255,255,0.5)',
                        font: { size: 10 },
                        maxRotation: 40,
                    },
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    border: { color: 'rgba(255,255,255,0.08)' },
                },
                y: {
                    min: 0, max: 10,
                    ticks: {
                        color: 'rgba(255,255,255,0.5)',
                        font: { size: 11 },
                        stepSize: 1,
                        callback: (v) => v === 5 ? '5 ⚡' : v,
                    },
                    grid: {
                        color: (ctx) => ctx.tick.value === 5
                            ? 'rgba(239,68,68,0.5)'
                            : 'rgba(255,255,255,0.05)',
                        lineWidth: (ctx) => ctx.tick.value === 5 ? 2 : 1,
                    },
                    border: { color: 'rgba(255,255,255,0.08)' },
                }
            }
        }
    });
}

// Re-fetch chart sau khi nhập điểm xong (debounce 2s để không spam)
let chartFetchTimer = null;
function scheduleChartRefresh() {
    clearTimeout(chartFetchTimer);
    chartFetchTimer = setTimeout(fetchChartData, 2000);
}
</script>

<script>
// ═══════════════════════════════════════════════════════════════
// SUBJECT GROUP ANALYSIS
// ═══════════════════════════════════════════════════════════════

// Màu sắc cho từng nhóm (tối đa 10 nhóm)
const GROUP_COLORS = [
    '#6366f1', '#a855f7', '#10b981', '#f59e0b',
    '#3b82f6', '#ec4899', '#14b8a6', '#f97316',
    '#84cc16', '#8b5cf6'
];

// Phân loại mức độ điểm
function gradeLevel(avg) {
    if (avg === null) return 'na';
    if (avg >= 8.0)   return 'excellent';
    if (avg >= 6.5)   return 'good';
    if (avg >= 5.0)   return 'warning';
    return 'danger';
}

function gradeLevelLabel(avg) {
    if (avg === null) return { cls: 'group-na-badge', text: '— Chưa có dữ liệu' };
    if (avg >= 8.0)   return { cls: 'group-ok-badge', text: '🌟 Xuất sắc' };
    if (avg >= 6.5)   return { cls: 'group-ok-badge', text: '✓ Tốt' };
    if (avg >= 5.0)   return { cls: 'group-weak-badge', text: '⚠ Cần cải thiện' };
    return { cls: 'group-weak-badge', text: '⛔ Điểm yếu' };
}

function buildGroupAnalysis() {
    // Thu thập tất cả môn học và điểm hiện tại
    const allSubjects = []; // { id, name, credits, groupName }
    for (const [semName, subs] of Object.entries(SUBJECTS_BY_SEM)) {
        subs.forEach(sub => allSubjects.push(sub));
    }

    // Lấy điểm từ các input
    const grades = {};
    document.querySelectorAll('.grade-input').forEach(input => {
        const sid = parseInt(input.dataset.subjectId);
        const val = parseFloat(input.value);
        if (!isNaN(val) && input.value !== '') grades[sid] = val;
    });

    // Nhóm theo groupName
    const groups = {}; // { groupName: { subjects: [], grades: [] } }
    allSubjects.forEach(sub => {
        const gName = sub.groupName || 'Khác';
        if (!groups[gName]) groups[gName] = { subjects: [], gradedSubjects: [] };
        groups[gName].subjects.push(sub);
        if (grades[sub.id] !== undefined) {
            groups[gName].gradedSubjects.push({ ...sub, grade: grades[sub.id] });
        }
    });

    // Tính trung bình cho mỗi nhóm
    const groupStats = Object.entries(groups).map(([name, data], idx) => {
        const graded = data.gradedSubjects;
        let avg = null;
        if (graded.length > 0) {
            const sum = graded.reduce((s, s2) => s + s2.grade, 0);
            avg = Math.round((sum / graded.length) * 10) / 10;
        }
        return {
            name,
            total: data.subjects.length,
            graded: graded.length,
            avg,
            color: GROUP_COLORS[idx % GROUP_COLORS.length],
            subjects: data.subjects,
            gradedSubjects: graded,
        };
    }).sort((a, b) => {
        // Sắp xếp: có điểm trước, sau đó theo avg tăng dần (điểm yếu lên đầu)
        if (a.avg === null && b.avg === null) return 0;
        if (a.avg === null) return 1;
        if (b.avg === null) return -1;
        return a.avg - b.avg;
    });

    return groupStats;
}

function renderGroupAnalysis() {
    const container = document.getElementById('group-analysis-content');
    if (!container) return;

    const groupStats = buildGroupAnalysis();
    const hasAny = groupStats.some(g => g.avg !== null);

    if (!hasAny) {
        container.innerHTML = `
            <div class="group-analysis-empty">
                <div class="group-analysis-empty-icon">📊</div>
                <p>Nhập điểm các môn học để xem phân tích điểm theo nhóm</p>
            </div>`;
        return;
    }

    // Nhóm yếu (< 6.5)
    const weakGroups   = groupStats.filter(g => g.avg !== null && g.avg < 6.5);
    const dangerGroups = groupStats.filter(g => g.avg !== null && g.avg < 5.0);
    const strongGroups = groupStats.filter(g => g.avg !== null && g.avg >= 8.0);

    // ── Bảng nhóm môn ──
    const tableRows = groupStats.map(g => {
        const pct    = g.avg !== null ? Math.round((g.avg / 10) * 100) : 0;
        const lvl    = gradeLevel(g.avg);
        const badge  = gradeLevelLabel(g.avg);
        const barColor = lvl === 'excellent' ? '#10b981'
                       : lvl === 'good'      ? '#6366f1'
                       : lvl === 'warning'   ? '#f59e0b'
                       : lvl === 'danger'    ? '#ef4444'
                       : 'rgba(255,255,255,.15)';
        return `
        <tr>
            <td>
                <div class="group-name-cell">
                    <span class="group-dot" style="background:${g.color};"></span>
                    <span class="group-name-text">${g.name}</span>
                </div>
            </td>
            <td style="color:var(--text-secondary);font-size:.8rem;">${g.graded}/${g.total}</td>
            <td class="group-bar-cell">
                <div class="group-bar-track">
                    <div class="group-bar-fill" style="width:${pct}%;background:${barColor};"></div>
                </div>
            </td>
            <td class="group-avg-cell">
                <span class="group-avg-val ${lvl}">${g.avg !== null ? g.avg : '—'}</span>
            </td>
            <td style="text-align:right;">
                <span class="${badge.cls}">${badge.text}</span>
            </td>
        </tr>`;
    }).join('');

    // ── Radar chart ──
    const radarLabels = groupStats.filter(g => g.avg !== null).map(g => g.name);
    const radarData   = groupStats.filter(g => g.avg !== null).map(g => g.avg);
    const radarColors = groupStats.filter(g => g.avg !== null).map(g => g.color);

    // ── Alerts ──
    let alertsHtml = '';
    if (dangerGroups.length > 0) {
        const names = dangerGroups.map(g => `<strong>${g.name}</strong>`).join(', ');
        alertsHtml += `
        <div class="group-alert danger">
            <span class="group-alert-icon">⛔</span>
            <div>Bạn đang <strong>rất yếu</strong> ở nhóm: ${names} (điểm TB < 5.0). Cần ưu tiên ôn luyện và học lại ngay!</div>
        </div>`;
    } else if (weakGroups.length > 0) {
        const names = weakGroups.map(g => `<strong>${g.name}</strong> (${g.avg})`).join(', ');
        alertsHtml += `
        <div class="group-alert warning">
            <span class="group-alert-icon">⚠️</span>
            <div>Nhóm môn cần cải thiện: ${names}. Hãy tập trung ôn luyện thêm để nâng điểm!</div>
        </div>`;
    }
    if (strongGroups.length > 0) {
        const names = strongGroups.map(g => `<strong>${g.name}</strong>`).join(', ');
        alertsHtml += `
        <div class="group-alert success">
            <span class="group-alert-icon">🌟</span>
            <div>Bạn đang làm rất tốt ở nhóm: ${names}. Tiếp tục phát huy!</div>
        </div>`;
    }

    container.innerHTML = `
        <div class="group-analysis-grid">
            <div>
                <div class="radar-wrapper">
                    <canvas id="groupRadarChart"></canvas>
                </div>
            </div>
            <div>
                <table class="group-table">
                    <thead>
                        <tr>
                            <th>Nhóm môn</th>
                            <th>Môn có điểm</th>
                            <th>Tỷ lệ</th>
                            <th style="text-align:right;">Điểm TB</th>
                            <th style="text-align:right;">Đánh giá</th>
                        </tr>
                    </thead>
                    <tbody>${tableRows}</tbody>
                </table>

                <div class="group-summary-alerts">${alertsHtml}</div>
            </div>
        </div>`;

    // Render Radar Chart
    renderGroupRadar(radarLabels, radarData, radarColors);
}

let groupRadarInstance = null;
function renderGroupRadar(labels, data, colors) {
    const canvas = document.getElementById('groupRadarChart');
    if (!canvas || labels.length === 0) return;

    if (groupRadarInstance) { groupRadarInstance.destroy(); groupRadarInstance = null; }

    // Gradient fill
    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(99,102,241,0.45)');
    gradient.addColorStop(1, 'rgba(168,85,247,0.15)');

    groupRadarInstance = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels.map(l => l.length > 14 ? l.substring(0, 12) + '…' : l),
            datasets: [{
                label: 'Điểm TB nhóm môn',
                data,
                backgroundColor: gradient,
                borderColor: 'rgba(99,102,241,0.9)',
                borderWidth: 2,
                pointBackgroundColor: colors,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            animation: { duration: 700, easing: 'easeInOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(10,14,30,0.95)',
                    borderColor: 'rgba(99,102,241,0.4)',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: item => `  Điểm TB: ${item.raw}`,
                    }
                }
            },
            scales: {
                r: {
                    min: 0,
                    max: 10,
                    ticks: {
                        stepSize: 2,
                        color: 'rgba(255,255,255,0.4)',
                        font: { size: 9 },
                        backdropColor: 'transparent',
                        callback: v => v === 5 ? '5⚡' : v,
                    },
                    grid: {
                        color: (ctx) => ctx.tick.value === 5
                            ? 'rgba(239,68,68,0.4)'
                            : 'rgba(255,255,255,0.07)',
                        lineWidth: (ctx) => ctx.tick.value === 5 ? 1.5 : 1,
                    },
                    pointLabels: {
                        color: 'rgba(255,255,255,0.75)',
                        font: { size: 10, weight: '600' },
                    },
                    angleLines: { color: 'rgba(255,255,255,0.07)' },
                }
            }
        }
    });
}

// Hook vào onGradeChange để cập nhật phân tích
const _origOnGradeChange = onGradeChange;
window.onGradeChange = function(id, input, skipSave = false) {
    _origOnGradeChange(id, input, skipSave);
    clearTimeout(window._groupAnalysisTimer);
    window._groupAnalysisTimer = setTimeout(renderGroupAnalysis, 600);
};

// Cũng update sau khi load grades từ DB
const _origLoadGrades = loadGradesFromDB;
window.loadGradesFromDB = async function() {
    await _origLoadGrades();
    setTimeout(renderGroupAnalysis, 300);
};

document.addEventListener('DOMContentLoaded', () => {
    // Sẽ được trigger tự động khi loadGradesFromDB chạy xong
});
</script>

<script>
// ═══════════════════════════════════════════════════════════════
// SEMESTER HISTORY DRAWER
// ═══════════════════════════════════════════════════════════════

function toggleHistoryDrawer() {
    const drawer  = document.getElementById('history-drawer');
    const overlay = document.getElementById('history-drawer-overlay');
    const isOpen  = drawer.classList.contains('open');
    if (isOpen) {
        drawer.classList.remove('open');
        overlay.classList.remove('open');
    } else {
        drawer.classList.add('open');
        overlay.classList.add('open');
        loadSemesterHistory(); // Luôn reload khi mở
    }
}

function closeHistoryDrawer() {
    document.getElementById('history-drawer').classList.remove('open');
    document.getElementById('history-drawer-overlay').classList.remove('open');
}

async function loadSemesterHistory() {
    try {
        const res = await fetch('/semester-history', {
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) return;
        const data = await res.json();
        renderHistoryDrawer(data);
        updateHistoryBadge(data.length);
    } catch (err) {
        console.warn('[History load error]', err);
    }
}

function updateHistoryBadge(count) {
    const badge = document.getElementById('history-count-badge');
    if (!badge) return;
    if (count > 0) {
        badge.textContent = count;
        badge.classList.add('visible');
    } else {
        badge.classList.remove('visible');
    }
}

function renderHistoryDrawer(histories) {
    const emptyEl = document.getElementById('history-empty');
    const listEl  = document.getElementById('history-list');
    if (!listEl) return;

    if (!histories || histories.length === 0) {
        emptyEl.style.display = 'flex';
        listEl.innerHTML = '';
        return;
    }

    emptyEl.style.display = 'none';

    // Sắp xếp mới nhất trước
    const sorted = [...histories].sort((a, b) => b.semester_number - a.semester_number);

    listEl.innerHTML = sorted.map((h, idx) => {
        const gpaColor = h.gpa >= 8 ? '#6ee7b7' : h.gpa >= 6.5 ? '#a5f3fc' : h.gpa >= 5 ? '#fcd34d' : '#fca5a5';
        const passRate = h.total_credits > 0
            ? Math.round((h.passed_credits / h.total_credits) * 100)
            : 0;

        const itemsHtml = (h.items || []).map(item => {
            const gradeClass = item.grade === null ? 'empty'
                : item.status === 'pass' ? 'pass' : 'fail';
            const gradeText = item.grade !== null ? item.grade : '—';
            return `
                <div class="history-subject-row ${item.status || ''}">
                    <span class="history-subject-name">${item.subject_name || '?'}</span>
                    <span class="history-subject-credits">${item.credits ?? '?'} TC</span>
                    <span class="history-subject-grade ${gradeClass}">${gradeText}</span>
                </div>`;
        }).join('');

        return `
        <div class="history-sem-block" id="history-block-${h.id}">
            <div class="history-sem-header" onclick="toggleSemBlock(${h.id})">
                <div>
                    <div class="history-sem-title">
                        🎓 Học kỳ ${h.semester_number}
                        ${h.academic_year ? `<span style="font-size:.75rem;font-weight:500;color:rgba(255,255,255,.4);">(${h.academic_year})</span>` : ''}
                    </div>
                    <div class="history-sem-meta" style="margin-top:.3rem;">
                        ${h.gpa !== null ? `<span class="history-sem-pill gpa">GPA: ${h.gpa}</span>` : ''}
                        <span class="history-sem-pill credits">✓ ${h.passed_credits}/${h.total_credits} TC</span>
                        ${h.completed_at ? `<span class="history-sem-pill date">📅 ${h.completed_at}</span>` : ''}
                    </div>
                </div>
                <span class="history-sem-chevron">▼</span>
            </div>
            <div class="history-sem-body">
                <div class="history-subject-list">
                    <div class="history-subject-row" style="background:rgba(255,255,255,.03);border-bottom:1px solid rgba(255,255,255,.07);">
                        <span style="font-size:.72rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.04em;">Môn học</span>
                        <span style="font-size:.72rem;font-weight:700;color:var(--text-secondary);min-width:42px;text-align:right;">TC</span>
                        <span style="font-size:.72rem;font-weight:700;color:var(--text-secondary);min-width:48px;text-align:center;">Điểm</span>
                    </div>
                    ${itemsHtml || '<div style="padding:.8rem 1.1rem;color:var(--text-secondary);font-size:.85rem;">Không có dữ liệu môn học</div>'}
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:.65rem 1.1rem;background:rgba(255,255,255,.03);border-top:1px solid rgba(255,255,255,.07);">
                        <span style="font-size:.75rem;color:var(--text-secondary);">Tỷ lệ pass</span>
                        <div style="display:flex;align-items:center;gap:.75rem;">
                            <div style="width:80px;height:6px;background:rgba(255,255,255,.08);border-radius:3px;overflow:hidden;">
                                <div style="height:100%;width:${passRate}%;background:linear-gradient(90deg,#10b981,#34d399);border-radius:3px;"></div>
                            </div>
                            <span style="font-size:.8rem;font-weight:700;color:${gpaColor};">${passRate}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');
}

function toggleSemBlock(id) {
    const block = document.getElementById(`history-block-${id}`);
    if (block) block.classList.toggle('open');
}

// Tải lịch sử khi page load (để cập nhật badge)
document.addEventListener('DOMContentLoaded', () => {
    loadSemesterHistory();
});
</script>

</body>
</html>
