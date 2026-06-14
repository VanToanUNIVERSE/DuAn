<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcademiaLink — Smart Student Planner</title>
    <meta name="description" content="Hệ thống gợi ý môn học thông minh, theo dõi tiến độ và phân tích kết quả học tập của bạn.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <style>
        /* ═══════════════════════════════════════════════════════════
           CLAY DESIGN TOKENS — NOTEBOOK EDITION
        ═══════════════════════════════════════════════════════════ */
        :root {
            /* Canvas & Surfaces — cream giấy vở */
            --canvas:          #fffef7;
            --surface-soft:    #faf8ee;
            --surface-card:    #f5f2e3;
            --surface-strong:  #ebe7d2;
            --surface-dark:    #0f1f1f;
            --surface-dark-el: #182a2a;

            /* Ink & Text */
            --ink:             #0d0d0d;
            --body-strong:     #1a1a1a;
            --body:            #363636;
            --muted:           #686868;
            --muted-soft:      #9a9a9a;

            /* Notebook lines — đường kẻ vở */
            --hairline:        #c2bba4;
            --hairline-soft:   #d8d2bc;
            --rule-line:       #cdc7b0;
            --rule-strong:     #b5ae98;
            --rule-margin:     #dba0a0;   /* đỏ nhạt — đường margin sổ tay */

            /* Brand Palette */
            --brand-pink:      #ff4d8b;
            --brand-teal:      #1a3a3a;
            --brand-lavender:  #b8a4ed;
            --brand-peach:     #ffb084;
            --brand-ochre:     #e8b94a;
            --brand-mint:      #a4d4c5;
            --brand-coral:     #ff6b5a;

            /* Semantic */
            --success:         #16a34a;
            --warning:         #d97706;
            --error:           #dc2626;

            /* Primary CTA */
            --primary:         #0d0d0d;
            --on-primary:      #ffffff;

            /* Radius — NOTEBOOK: hoàn toàn vuông, không tròn */
            --r-xs:   0px;
            --r-sm:   0px;
            --r-md:   0px;
            --r-lg:   0px;
            --r-xl:   0px;
            --r-pill: 0px;

            /* Border thickness */
            --border:         1.5px solid var(--hairline);
            --border-strong:  2px solid var(--rule-strong);
            --border-heavy:   3px solid var(--ink);
            --border-left-accent: 4px solid var(--ink);

            /* Spacing */
            --sp-xs:  8px;
            --sp-sm:  12px;
            --sp-md:  16px;
            --sp-lg:  24px;
            --sp-xl:  32px;
            --sp-xxl: 48px;
            --sp-sec: 64px;

            /* Transitions */
            --transition: all 0.18s ease;
        }

        /* ═══════════════════════════════════════════════════════════
           BASE RESET
        ═══════════════════════════════════════════════════════════ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--canvas);
            color: var(--body);
            min-height: 100vh;
            overflow-x: hidden;
            line-height: 1.55;
        }

        h1, h2, h3, h4, h5 {
            font-family: 'Sora', 'Inter', sans-serif;
            color: var(--ink);
            line-height: 1.2;
            letter-spacing: -0.3px;
        }

        /* ═══════════════════════════════════════════════════════════
           APP SHELL — SIDEBAR LAYOUT
        ═══════════════════════════════════════════════════════════ */
        .app-shell {
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: 240px;
            background: var(--surface-dark);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 200;
            padding: 0 0 var(--sp-lg);
            overflow-y: auto;
            flex-shrink: 0;
        }

        .sidebar-logo {
            padding: var(--sp-lg) var(--sp-xl);
            border-bottom: 1px solid rgba(255,255,255,0.07);
            flex-shrink: 0;
        }
        .sidebar-logo-name {
            font-family: 'Sora', sans-serif;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--canvas);
            letter-spacing: -0.5px;
        }
        .sidebar-logo-sub {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.45);
            font-weight: 500;
            margin-top: 2px;
        }

        .sidebar-nav {
            flex: 1;
            padding: var(--sp-lg) var(--sp-sm);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sidebar-nav-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,0.3);
            padding: var(--sp-xs) var(--sp-sm) 4px;
            margin-top: var(--sp-xs);
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px var(--sp-sm);
            border-radius: var(--r-md);
            color: rgba(255,255,255,0.55);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
        }
        .nav-item:hover {
            background: rgba(255,255,255,0.07);
            color: rgba(255,255,255,0.85);
        }
        .nav-item.active {
            background: rgba(255,255,255,0.12);
            color: var(--canvas);
            font-weight: 600;
        }
        .nav-item-icon {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            opacity: 0.7;
        }
        .nav-item.active .nav-item-icon { opacity: 1; }

        .nav-badge {
            margin-left: auto;
            background: var(--brand-pink);
            color: white;
            font-size: 0.6rem;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: var(--r-pill);
            min-width: 18px;
            text-align: center;
            display: none;
        }
        .nav-badge.visible { display: block; }

        .sidebar-actions {
            padding: 0 var(--sp-sm);
            display: flex;
            flex-direction: column;
            gap: 6px;
            border-top: 1px solid rgba(255,255,255,0.07);
            padding-top: var(--sp-md);
            margin: 0 var(--sp-sm);
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: var(--sp-sm);
            background: rgba(255,255,255,0.06);
            border-radius: var(--r-md);
            margin: var(--sp-md) var(--sp-sm) 0;
        }
        .sidebar-avatar {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--brand-lavender), var(--brand-pink));
            border-radius: var(--r-pill);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .sidebar-user-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--canvas);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-user-meta {
            font-size: 0.68rem;
            color: rgba(255,255,255,0.4);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        .btn-sidebar-action {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px var(--sp-sm);
            border-radius: var(--r-sm);
            font-family: 'Inter', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            width: 100%;
            text-align: left;
        }
        .btn-grades-sb {
            background: rgba(34,197,94,0.12);
            color: #4ade80;
            border: 1px solid rgba(34,197,94,0.25);
        }
        .btn-grades-sb:hover { background: rgba(34,197,94,0.2); }

        .btn-history-sb {
            background: rgba(232,185,74,0.12);
            color: #fbbf24;
            border: 1px solid rgba(232,185,74,0.25);
        }
        .btn-history-sb:hover { background: rgba(232,185,74,0.2); }

        .btn-config-sb {
            background: rgba(184,164,237,0.12);
            color: var(--brand-lavender);
            border: 1px solid rgba(184,164,237,0.25);
            position: relative;
        }
        .btn-config-sb:hover { background: rgba(184,164,237,0.2); }
        .config-dot-sb {
            position: absolute;
            top: 6px; right: 6px;
            width: 6px; height: 6px;
            background: var(--brand-peach);
            border-radius: 50%;
            animation: pulseDot 1.5s infinite;
        }
        @keyframes pulseDot { 0%,100%{opacity:1} 50%{opacity:0.4} }

        .btn-logout-sb {
            background: rgba(239,68,68,0.08);
            color: #f87171;
            border: 1px solid rgba(239,68,68,0.2);
        }
        .btn-logout-sb:hover { background: rgba(239,68,68,0.15); }

        /* ── Main Content Area ── */
        .main-content {
            margin-left: 240px;
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Topbar ── */
        .topbar {
            background: var(--canvas);
            border-bottom: 1px solid var(--hairline);
            padding: 0 var(--sp-xl);
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-title {
            font-family: 'Sora', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -0.3px;
        }
        .topbar-subtitle {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 1px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: var(--sp-sm);
        }

        /* ── Page Content ── */
        .page-content {
            flex: 1;
            padding: var(--sp-xl);
            max-width: 1200px;
            width: 100%;
        }

        /* ═══════════════════════════════════════════════════════════
           CLAY CARDS
        ═══════════════════════════════════════════════════════════ */
        .clay-card {
            background: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: var(--sp-xl);
            transition: var(--transition);
        }
        .clay-card:hover {
            border-color: rgba(10,10,10,0.15);
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }

        .clay-card-sm {
            background: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: var(--sp-lg);
        }

        /* Feature cards — Clay saturated colors */
        .feat-card {
            border-radius: var(--r-xl);
            padding: var(--sp-xl);
            position: relative;
            overflow: hidden;
        }
        .feat-card-pink    { background: var(--brand-pink);    color: white; }
        .feat-card-teal    { background: var(--brand-teal);    color: white; }
        .feat-card-lavender{ background: var(--brand-lavender); color: var(--ink); }
        .feat-card-peach   { background: var(--brand-peach);   color: var(--ink); }
        .feat-card-ochre   { background: var(--brand-ochre);   color: var(--ink); }
        .feat-card-cream   { background: var(--surface-card);  color: var(--ink); }
        .feat-card-mint    { background: var(--brand-mint);    color: var(--ink); }

        .feat-card-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.7;
            margin-bottom: var(--sp-xs);
        }
        .feat-card-value {
            font-family: 'Sora', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -1px;
        }
        .feat-card-value-sm {
            font-family: 'Sora', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1;
        }
        .feat-card-sub {
            font-size: 0.8rem;
            margin-top: var(--sp-xs);
            opacity: 0.75;
        }

        /* ═══════════════════════════════════════════════════════════
           SECTION HEADINGS
        ═══════════════════════════════════════════════════════════ */
        .section-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--muted);
            margin-bottom: var(--sp-sm);
        }

        .section-title {
            font-family: 'Sora', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -0.4px;
            margin-bottom: var(--sp-lg);
            display: flex;
            align-items: center;
            gap: var(--sp-sm);
        }
        .section-title-icon {
            width: 28px; height: 28px;
            background: var(--surface-strong);
            border-radius: var(--r-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        /* ═══════════════════════════════════════════════════════════
           CLAY BUTTONS
        ═══════════════════════════════════════════════════════════ */
        .btn-primary {
            background: var(--ink);
            color: var(--on-primary);
            border: none;
            border-radius: var(--r-md);
            padding: 11px 20px;
            height: 44px;
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .btn-primary:hover { background: var(--body-strong); transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }

        .btn-secondary {
            background: var(--canvas);
            color: var(--ink);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            padding: 11px 20px;
            height: 44px;
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .btn-secondary:hover { background: var(--surface-soft); border-color: rgba(10,10,10,0.2); }

        .btn-on-color {
            background: white;
            color: var(--ink);
            border: none;
            border-radius: var(--r-md);
            padding: 10px 18px;
            font-family: 'Inter', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-on-color:hover { background: var(--surface-soft); transform: translateY(-1px); }

        .btn-text {
            background: transparent;
            color: var(--ink);
            border: none;
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
            text-underline-offset: 2px;
            padding: 0;
        }

        /* ═══════════════════════════════════════════════════════════
           BADGE PILLS
        ═══════════════════════════════════════════════════════════ */
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: var(--r-pill);
            font-size: 0.72rem;
            font-weight: 600;
        }
        .pill-cream  { background: var(--surface-card); color: var(--body-strong); border: 1px solid var(--hairline); }
        .pill-ink    { background: var(--ink); color: white; }
        .pill-pink   { background: var(--brand-pink); color: white; }
        .pill-teal   { background: var(--brand-teal); color: white; }
        .pill-ochre  { background: var(--brand-ochre); color: var(--ink); }
        .pill-mint   { background: var(--brand-mint); color: var(--ink); }
        .pill-lavender { background: var(--brand-lavender); color: var(--ink); }
        .pill-peach  { background: var(--brand-peach); color: var(--ink); }
        .pill-green  { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .pill-red    { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* ═══════════════════════════════════════════════════════════
           FORM INPUTS (Clay style)
        ═══════════════════════════════════════════════════════════ */
        .clay-input {
            background: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            color: var(--ink);
            padding: 11px 14px;
            height: 44px;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: var(--transition);
            width: 100%;
        }
        .clay-input:focus {
            border-color: var(--ink);
            box-shadow: 0 0 0 3px rgba(10,10,10,0.08);
        }
        .clay-input::placeholder { color: var(--muted-soft); }

        .clay-select {
            background: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            color: var(--ink);
            padding: 11px 14px;
            height: 44px;
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            outline: none;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            -webkit-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' viewBox='0 0 24 24' stroke='%236a6a6a' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }
        .clay-select:focus { border-color: var(--ink); box-shadow: 0 0 0 3px rgba(10,10,10,0.08); }

        .input-group { display: flex; flex-direction: column; gap: 6px; }
        .input-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--body-strong);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* ═══════════════════════════════════════════════════════════
           PROGRESS BAR (Clay style)
        ═══════════════════════════════════════════════════════════ */
        .prog-track {
            height: 8px;
            background: var(--surface-strong);
            border-radius: var(--r-pill);
            overflow: hidden;
        }
        .prog-fill {
            height: 100%;
            border-radius: var(--r-pill);
            transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .prog-fill-ink    { background: var(--ink); }
        .prog-fill-pink   { background: var(--brand-pink); }
        .prog-fill-teal   { background: linear-gradient(90deg, var(--brand-teal), #2d6a6a); }
        .prog-fill-mint   { background: linear-gradient(90deg, var(--brand-mint), #7dd3c0); }
        .prog-fill-ochre  { background: var(--brand-ochre); }

        /* ═══════════════════════════════════════════════════════════
           TABS / TAB PANELS
        ═══════════════════════════════════════════════════════════ */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ═══════════════════════════════════════════════════════════
           TOPBAR CATEGORY TABS
        ═══════════════════════════════════════════════════════════ */
        .cat-tabs {
            display: flex;
            align-items: center;
            gap: 4px;
            background: var(--surface-card);
            border-radius: var(--r-pill);
            padding: 4px;
        }
        .cat-tab {
            padding: 6px 14px;
            border-radius: var(--r-pill);
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--muted);
            cursor: pointer;
            transition: var(--transition);
            border: none;
            background: transparent;
            font-family: 'Inter', sans-serif;
            white-space: nowrap;
        }
        .cat-tab.active { background: var(--canvas); color: var(--ink); font-weight: 600; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .cat-tab:hover:not(.active) { color: var(--body-strong); }

        /* ═══════════════════════════════════════════════════════════
           DASHBOARD — STAT CARDS GRID
        ═══════════════════════════════════════════════════════════ */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: var(--sp-md);
            margin-bottom: var(--sp-xl);
        }
        @media (max-width: 900px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .stat-grid { grid-template-columns: 1fr; } }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: var(--sp-xl);
            margin-bottom: var(--sp-xl);
        }
        @media (max-width: 900px) { .dashboard-grid { grid-template-columns: 1fr; } }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--sp-xl);
            margin-bottom: var(--sp-xl);
        }
        @media (max-width: 900px) { .content-grid { grid-template-columns: 1fr; } }

        /* ═══════════════════════════════════════════════════════════
           SUGGESTION CARDS
        ═══════════════════════════════════════════════════════════ */
        .suggestion-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: var(--sp-md);
        }

        .suggestion-card {
            background: var(--canvas);
            border: 1px solid var(--hairline);
            border-left: 4px solid var(--brand-mint);
            border-radius: 0;
            padding: var(--sp-lg);
            display: flex;
            flex-direction: column;
            gap: var(--sp-md);
            transition: var(--transition);
            animation: fadeUp 0.3s ease-out;
            position: relative;
        }
        .suggestion-card.is-locked {
            border-left-color: var(--hairline);
            background: var(--surface-soft);
        }
        .suggestion-card:hover {
            border-color: var(--ink);
            border-left-color: var(--brand-mint);
            box-shadow: 4px 4px 0px rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }

        .suggestion-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .suggestion-details { display: flex; flex-direction: column; gap: 8px; flex: 1; min-width: 0; }
        .suggestion-title { font-size: 1.05rem; font-weight: 700; color: var(--ink); }
        .suggestion-tags { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        
        .suggestion-icon {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: #eef2ff;
            color: #4f46e5;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .suggestion-icon.locked { background: var(--surface-strong); color: var(--muted); }

        .suggestion-desc {
            font-size: 0.85rem;
            color: var(--muted);
            line-height: 1.5;
            margin-top: 4px;
        }

        .suggestion-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 4px;
        }

        .btn-add-clay {
            flex: 1;
            background: #004ecc;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 14px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            text-align: center;
        }
        .btn-add-clay:hover { background: #003db3; }
        .btn-add-clay.added {
            background: #dcfce7;
            color: #166534;
            pointer-events: none;
        }

        .btn-info-clay {
            width: 40px; height: 40px;
            border: 1px solid var(--hairline);
            background: transparent;
            color: var(--muted);
            border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: var(--transition);
            flex-shrink: 0; font-size: 1.1rem;
        }
        .btn-info-clay:hover { border-color: var(--ink); color: var(--ink); }

        .semester-pill {
            background: var(--surface-strong);
            color: var(--body-strong);
            padding: 3px 10px;
            border-radius: var(--r-pill);
            font-size: 0.72rem;
            font-weight: 700;
        }

        /* ═══════════════════════════════════════════════════════════
           CURRENT COURSE ITEMS
        ═══════════════════════════════════════════════════════════ */
        .course-item {
            background: var(--surface-soft);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: var(--sp-md) var(--sp-lg);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--sp-md);
            transition: var(--transition);
            animation: fadeUp 0.3s ease-out;
        }
        .course-item.cc-pass { border-color: #bbf7d0; background: #f0fdf4; }
        .course-item.cc-fail { border-color: #fecaca; background: #fff5f5; }
        .course-info { display: flex; flex-direction: column; gap: 3px; flex: 1; min-width: 0; }
        .course-name { font-weight: 700; font-size: 0.9rem; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .course-meta { font-size: 0.75rem; color: var(--muted); }
        .course-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

        .grade-input-clay {
            width: 64px;
            background: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            color: var(--ink);
            font-size: 0.95rem;
            font-weight: 700;
            text-align: center;
            padding: 5px 4px;
            outline: none;
            transition: var(--transition);
            font-family: 'Sora', sans-serif;
        }
        .grade-input-clay:focus { border-color: var(--ink); box-shadow: 0 0 0 3px rgba(10,10,10,0.08); }
        .grade-input-clay.is-pass { border-color: #22c55e; color: #16a34a; background: #f0fdf4; }
        .grade-input-clay.is-fail { border-color: #ef4444; color: #dc2626; background: #fff5f5; }

        .grade-status-clay { font-size: 0.7rem; font-weight: 700; min-width: 40px; text-align: center; }
        .grade-status-clay.pass { color: #16a34a; }
        .grade-status-clay.fail { color: #dc2626; }
        .grade-status-clay.empty { color: var(--muted-soft); }

        .btn-remove-clay {
            background: var(--surface-card);
            border: 1px solid var(--hairline);
            color: var(--muted);
            border-radius: var(--r-sm);
            width: 28px; height: 28px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 0.9rem; transition: var(--transition); flex-shrink: 0;
        }
        .btn-remove-clay:hover { background: #fee2e2; border-color: #fecaca; color: #dc2626; }

        .studying-label {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 0.7rem; font-weight: 700; color: var(--body-strong);
            background: var(--brand-ochre);
            border-radius: var(--r-sm); padding: 3px 8px; white-space: nowrap;
            animation: studyingPulse 2s ease-in-out infinite;
        }
        @keyframes studyingPulse { 0%,100%{opacity:1} 50%{opacity:0.65} }
        .grade-input-wrap { display: flex; align-items: center; gap: 4px; }
        .grade-input.is-studying { opacity:0; pointer-events:none; position:absolute; }
        .grade-input-wrap.is-locked { position:relative; display:flex; align-items:center; }

        /* ═══════════════════════════════════════════════════════════
           CHART CARD
        ═══════════════════════════════════════════════════════════ */
        .chart-wrapper {
            position: relative;
            width: 100%;
            min-height: 280px;
        }
        .chart-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            color: var(--muted);
            font-size: 0.88rem;
            gap: 8px;
            text-align: center;
            min-height: 200px;
        }
        .chart-empty-icon { font-size: 2.2rem; opacity: 0.5; }
        .chart-legend {
            display: flex;
            align-items: center;
            gap: var(--sp-lg);
            flex-wrap: wrap;
            margin-top: var(--sp-md);
            padding-top: var(--sp-md);
            border-top: 1px solid var(--hairline);
        }
        .chart-legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: var(--muted);
        }
        .chart-legend-dot {
            width: 10px; height: 10px;
            border-radius: 3px;
            flex-shrink: 0;
        }
        .chart-sem-filter {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
            margin-bottom: var(--sp-md);
        }
        .chart-sem-btn {
            background: var(--surface-card);
            border: 1px solid var(--hairline);
            color: var(--muted);
            border-radius: var(--r-pill);
            padding: 4px 12px;
            font-size: 0.73rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }
        .chart-sem-btn:hover { border-color: var(--ink); color: var(--ink); }
        .chart-sem-btn.active { background: var(--ink); color: white; border-color: var(--ink); }

        /* ═══════════════════════════════════════════════════════════
           GROUP ANALYSIS
        ═══════════════════════════════════════════════════════════ */
        .group-analysis-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: var(--sp-xl);
            align-items: start;
        }
        @media (max-width: 700px) { .group-analysis-grid { grid-template-columns: 1fr; } }

        .radar-wrapper {
            position: relative;
            width: 100%;
            aspect-ratio: 1;
            max-width: 260px;
            margin: 0 auto;
        }

        .group-table { width: 100%; border-collapse: collapse; }
        .group-table th {
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid var(--hairline);
        }
        .group-table th:last-child { text-align: right; }
        .group-table td {
            padding: 8px 8px;
            font-size: 0.84rem;
            border-bottom: 1px solid var(--hairline-soft);
            vertical-align: middle;
            color: var(--body);
        }
        .group-table tr:last-child td { border-bottom: none; }
        .group-table tr:hover td { background: var(--surface-soft); }

        .group-name-cell { display: flex; align-items: center; gap: 8px; }
        .group-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .group-name-text { font-weight: 600; color: var(--body-strong); font-size: 0.82rem; }

        .group-avg-cell { text-align: right; }
        .group-avg-val { font-family: 'Sora', sans-serif; font-size: 0.95rem; font-weight: 800; }
        .group-avg-val.excellent { color: #16a34a; }
        .group-avg-val.good      { color: #1a3a3a; }
        .group-avg-val.warning   { color: #d97706; }
        .group-avg-val.danger    { color: #dc2626; }
        .group-avg-val.na        { color: var(--muted-soft); }

        .group-bar-cell { min-width: 80px; }
        .group-bar-track { height: 5px; background: var(--surface-strong); border-radius: var(--r-pill); overflow: hidden; }
        .group-bar-fill { height: 100%; border-radius: var(--r-pill); transition: width 0.8s cubic-bezier(0.4,0,0.2,1); }

        .group-weak-badge, .group-ok-badge, .group-na-badge {
            display: inline-flex; align-items: center; gap: 3px;
            font-size: 0.62rem; font-weight: 700;
            padding: 2px 7px; border-radius: var(--r-pill);
            white-space: nowrap;
        }
        .group-weak-badge { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .group-ok-badge   { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .group-na-badge   { background: var(--surface-card); color: var(--muted); border: 1px solid var(--hairline); }

        .group-summary-alerts { margin-top: var(--sp-lg); display: flex; flex-direction: column; gap: 8px; }
        .group-alert {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 10px var(--sp-md); border-radius: var(--r-md);
            font-size: 0.82rem; line-height: 1.5;
        }
        .group-alert.danger  { background: #fff5f5; border: 1px solid #fecaca; color: #991b1b; }
        .group-alert.warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
        .group-alert.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .group-alert-icon { font-size: 1rem; flex-shrink: 0; }

        .group-analysis-empty { text-align: center; padding: 3rem 1rem; color: var(--muted); font-size: 0.88rem; }
        .group-analysis-empty-icon { font-size: 2rem; margin-bottom: 6px; opacity: 0.5; }

        /* ═══════════════════════════════════════════════════════════
           DRAWERS (Grade & History)
        ═══════════════════════════════════════════════════════════ */
        .drawer-overlay {
            display: none;
            position: fixed; inset: 0;
            z-index: 600;
            background: rgba(10,10,10,0.4);
            backdrop-filter: blur(4px);
        }
        .drawer-overlay.open { display: block; }

        .grade-drawer {
            position: fixed;
            top: 0; left: 0;
            width: 480px; max-width: 95vw;
            height: 100vh;
            background: var(--canvas);
            border-right: 1px solid var(--hairline);
            z-index: 700;
            display: flex; flex-direction: column;
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 4px 0 32px rgba(0,0,0,0.1);
        }
        .grade-drawer.open { transform: translateX(0); }

        .history-drawer {
            position: fixed;
            top: 0; right: 0;
            width: 520px; max-width: 96vw;
            height: 100vh;
            background: var(--canvas);
            border-left: 1px solid var(--hairline);
            z-index: 700;
            display: flex; flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: -4px 0 32px rgba(0,0,0,0.1);
        }
        .history-drawer.open { transform: translateX(0); }

        .drawer-header {
            padding: var(--sp-lg) var(--sp-xl);
            border-bottom: 1px solid var(--hairline);
            display: flex; align-items: center; justify-content: space-between;
            flex-shrink: 0;
            background: var(--surface-soft);
        }
        .drawer-title {
            font-family: 'Sora', sans-serif;
            font-size: 1rem; font-weight: 700; color: var(--ink);
            display: flex; align-items: center; gap: 8px;
        }
        .drawer-subtitle { font-size: 0.72rem; color: var(--muted); margin-top: 2px; }
        .drawer-close {
            background: var(--surface-card);
            border: 1px solid var(--hairline);
            color: var(--muted);
            border-radius: var(--r-sm);
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 1rem; transition: var(--transition); flex-shrink: 0;
        }
        .drawer-close:hover { background: #fee2e2; border-color: #fecaca; color: #dc2626; }

        .drawer-search {
            padding: var(--sp-sm) var(--sp-xl);
            border-bottom: 1px solid var(--hairline);
            flex-shrink: 0;
        }

        .drawer-stats {
            padding: var(--sp-sm) var(--sp-xl);
            display: flex; align-items: center; gap: var(--sp-md);
            border-bottom: 1px solid var(--hairline);
            flex-shrink: 0;
        }
        .drawer-stat { display: flex; align-items: center; gap: 5px; font-size: 0.75rem; color: var(--muted); }
        .drawer-stat strong { color: var(--body-strong); }
        .drawer-stat.pass strong { color: #16a34a; }
        .drawer-stat.fail strong { color: #dc2626; }

        .drawer-body {
            flex: 1; overflow-y: auto;
            padding: var(--sp-md) var(--sp-xl);
        }
        .drawer-body::-webkit-scrollbar { width: 4px; }
        .drawer-body::-webkit-scrollbar-track { background: transparent; }
        .drawer-body::-webkit-scrollbar-thumb { background: var(--hairline); border-radius: 2px; }

        /* Subject cards in grade drawer */
        .drawer-sem-group { margin-bottom: var(--sp-lg); }
        .drawer-sem-header {
            font-size: 0.7rem; font-weight: 700; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.05em;
            margin-bottom: var(--sp-sm);
            display: flex; align-items: center; gap: 6px;
        }
        .drawer-sem-header::after { content:''; flex:1; height:1px; background:var(--hairline); }
        .drawer-subjects-list { display: flex; flex-direction: column; gap: 4px; }

        .drawer-subject-card {
            background: var(--surface-soft);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            padding: 8px var(--sp-md);
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            transition: var(--transition);
        }
        .drawer-subject-card:hover { background: var(--surface-card); }
        .drawer-subject-card.pass { border-color: #bbf7d0; background: #f0fdf4; }
        .drawer-subject-card.fail { border-color: #fecaca; background: #fff5f5; }
        .drawer-subject-card.hidden-search { display: none; }

        .drawer-subject-info { flex: 1; min-width: 0; }
        .drawer-subject-name { font-size: 0.84rem; font-weight: 600; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .drawer-subject-meta { font-size: 0.68rem; color: var(--muted); margin-top: 1px; }

        .drawer-grade-wrap { display: flex; align-items: center; gap: 5px; flex-shrink: 0; }
        .drawer-grade-input {
            width: 56px;
            background: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            color: var(--ink); font-size: 0.9rem; font-weight: 700;
            text-align: center; padding: 5px 3px;
            outline: none; transition: var(--transition);
            font-family: 'Sora', sans-serif;
        }
        .drawer-grade-input:focus { border-color: var(--ink); box-shadow: 0 0 0 3px rgba(10,10,10,0.08); }
        .drawer-grade-input.is-pass { border-color: #22c55e; color: #16a34a; }
        .drawer-grade-input.is-fail { border-color: #ef4444; color: #dc2626; }
        .drawer-grade-status { font-size: 0.62rem; font-weight: 700; min-width: 32px; text-align: center; }
        .drawer-grade-status.pass { color: #16a34a; }
        .drawer-grade-status.fail { color: #dc2626; }
        .drawer-grade-status.empty { color: var(--muted-soft); }

        /* ── History Drawer specific ── */
        .history-empty {
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 4rem 1rem; gap: 8px;
            color: var(--muted); font-size: 0.88rem; text-align: center;
        }
        .history-empty-icon { font-size: 2.5rem; opacity: 0.4; }

        .history-sem-block {
            margin-bottom: var(--sp-md);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            overflow: hidden;
        }
        .history-sem-header {
            background: var(--surface-soft);
            padding: var(--sp-sm) var(--sp-lg);
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            cursor: pointer; transition: var(--transition);
        }
        .history-sem-header:hover { background: var(--surface-card); }
        .history-sem-title {
            font-family: 'Sora', sans-serif;
            font-size: 0.9rem; font-weight: 700; color: var(--ink);
            display: flex; align-items: center; gap: 6px;
        }
        .history-sem-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 3px; }
        .history-sem-pill {
            font-size: 0.68rem; font-weight: 700;
            padding: 2px 8px; border-radius: var(--r-pill); border: 1px solid;
        }
        .history-sem-pill.gpa { color: #1a3a3a; border-color: #a4d4c5; background: #e8f8f3; }
        .history-sem-pill.credits { color: #166534; border-color: #bbf7d0; background: #f0fdf4; }
        .history-sem-pill.date { color: var(--muted); border-color: var(--hairline); background: transparent; }
        .history-sem-chevron { color: var(--muted); transition: transform 0.25s; flex-shrink: 0; font-size: 0.85rem; }
        .history-sem-block.open .history-sem-chevron { transform: rotate(180deg); }

        .history-sem-body { display: none; border-top: 1px solid var(--hairline); }
        .history-sem-block.open .history-sem-body { display: block; }
        .history-subject-list { display: flex; flex-direction: column; gap: 0; }
        .history-subject-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 7px var(--sp-lg); gap: 10px;
            border-bottom: 1px solid var(--hairline-soft);
            transition: var(--transition);
        }
        .history-subject-row:last-child { border-bottom: none; }
        .history-subject-row:hover { background: var(--surface-soft); }
        .history-subject-row.pass { border-left: 3px solid #22c55e; }
        .history-subject-row.fail { border-left: 3px solid #ef4444; }
        .history-subject-name { font-size: 0.82rem; font-weight: 500; color: var(--body-strong); flex: 1; }
        .history-subject-credits { font-size: 0.7rem; color: var(--muted); min-width: 40px; text-align: right; }
        .history-subject-grade { min-width: 44px; text-align: center; font-size: 0.88rem; font-weight: 800; font-family: 'Sora', sans-serif; }
        .history-subject-grade.pass { color: #16a34a; }
        .history-subject-grade.fail { color: #dc2626; }
        .history-subject-grade.empty { color: var(--muted-soft); }

        /* ═══════════════════════════════════════════════════════════
           CONFIG PANEL
        ═══════════════════════════════════════════════════════════ */
        .config-panel-overlay {
            display: none; position: fixed; inset: 0; z-index: 400; background: transparent;
        }
        .config-panel-overlay.open { display: block; }

        .config-panel {
            position: fixed;
            top: 64px; right: 20px;
            width: 380px; max-width: calc(100vw - 40px);
            background: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--r-xl);
            padding: var(--sp-xl);
            z-index: 500;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            transform: translateY(-8px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .config-panel.open { transform: translateY(0); opacity: 1; pointer-events: all; }

        .config-panel-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: var(--sp-lg); padding-bottom: var(--sp-md);
            border-bottom: 1px solid var(--hairline);
        }
        .config-panel-title {
            font-family: 'Sora', sans-serif;
            font-size: 1rem; font-weight: 700; color: var(--ink);
            display: flex; align-items: center; gap: 6px;
        }
        .config-panel-close {
            background: var(--surface-card); border: 1px solid var(--hairline);
            color: var(--muted); border-radius: var(--r-sm);
            width: 28px; height: 28px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 0.9rem; transition: var(--transition);
        }
        .config-panel-close:hover { background: var(--surface-strong); color: var(--ink); }

        .config-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--sp-md); margin-bottom: var(--sp-lg); }

        .config-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 8px; margin-top: var(--sp-md); padding-top: var(--sp-md); border-top: 1px solid var(--hairline); }
        .config-stat { background: var(--surface-soft); border: 1px solid var(--hairline); border-radius: var(--r-md); padding: 10px 8px; text-align: center; }
        .config-stat-val { font-family: 'Sora', sans-serif; font-size: 1.1rem; font-weight: 800; color: var(--ink); }
        .config-stat-val.highlight { color: #16a34a; }
        .config-stat-label { font-size: 0.58rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 2px; }

        /* ═══════════════════════════════════════════════════════════
           ONBOARDING WIZARD
        ═══════════════════════════════════════════════════════════ */
        .ob-overlay {
            position: fixed; inset: 0; z-index: 1000;
            background: rgba(10,10,10,0.6);
            backdrop-filter: blur(8px);
            display: flex; align-items: center; justify-content: center;
            padding: var(--sp-md);
        }
        .ob-overlay.hidden { display: none; }

        .ob-modal {
            background: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--r-xl);
            width: 100%; max-width: 580px;
            max-height: 90vh; overflow-y: auto;
            box-shadow: 0 40px 100px rgba(0,0,0,0.2);
            animation: obIn 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes obIn { from{opacity:0;transform:translateY(40px) scale(0.95)} to{opacity:1;transform:none} }

        .ob-header { padding: var(--sp-xl) var(--sp-xl) 0; text-align: center; }
        .ob-step-dots { display: flex; justify-content: center; gap: 6px; margin-bottom: var(--sp-lg); }
        .ob-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--surface-strong); transition: all 0.3s; }
        .ob-dot.active { background: var(--ink); width: 24px; border-radius: var(--r-pill); }
        .ob-dot.done { background: var(--success); }

        .ob-step-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: var(--muted); margin-bottom: 6px; }
        .ob-icon { width: 64px; height: 64px; margin: 0 auto var(--sp-md); border-radius: var(--r-lg); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
        .ob-title { font-family: 'Sora', sans-serif; font-size: 1.6rem; font-weight: 800; color: var(--ink); margin-bottom: 6px; letter-spacing: -0.5px; }
        .ob-desc { color: var(--muted); font-size: 0.9rem; max-width: 400px; margin: 0 auto; line-height: 1.6; }

        .ob-body { padding: var(--sp-xl); }
        .ob-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--sp-md); }
        @media (max-width: 500px) { .ob-form-grid { grid-template-columns: 1fr; } }

        .ob-input-group { display: flex; flex-direction: column; gap: 6px; }
        .ob-input-group label { font-size: 0.78rem; font-weight: 700; color: var(--body-strong); text-transform: uppercase; letter-spacing: 0.04em; }

        .ob-semester-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
        .ob-sem-btn {
            background: var(--surface-soft); border: 1.5px solid var(--hairline);
            border-radius: var(--r-md); color: var(--muted);
            padding: 12px 8px; font-family: 'Sora', sans-serif;
            font-size: 0.88rem; font-weight: 700;
            cursor: pointer; transition: var(--transition); text-align: center;
        }
        .ob-sem-btn:hover { border-color: var(--ink); color: var(--ink); }
        .ob-sem-btn.selected { border-color: var(--ink); background: var(--ink); color: white; }

        .ob-year-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
        .ob-year-btn {
            background: var(--surface-soft); border: 1.5px solid var(--hairline);
            border-radius: var(--r-md); color: var(--muted);
            padding: var(--sp-md) 8px; font-family: 'Sora', sans-serif;
            font-size: 1rem; font-weight: 700;
            cursor: pointer; transition: var(--transition);
            text-align: center; display: flex; flex-direction: column; gap: 3px; align-items: center;
        }
        .ob-year-btn:hover { border-color: var(--ink); color: var(--ink); }
        .ob-year-btn.selected { border-color: var(--ink); background: var(--ink); color: white; }
        .ob-year-btn small { font-size: 0.62rem; opacity: 0.7; font-weight: 500; }

        .ob-warning {
            background: #fffbeb; border: 1px solid #fde68a;
            border-radius: var(--r-md); padding: var(--sp-md);
            display: flex; align-items: flex-start; gap: 10px; margin-bottom: var(--sp-lg);
        }
        .ob-warning-icon { font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }
        .ob-warning p { font-size: 0.84rem; color: #92400e; line-height: 1.55; font-weight: 500; }
        .ob-warning strong { color: #78350f; }

        .ob-subjects-scroll { max-height: 300px; overflow-y: auto; padding-right: 4px; }
        .ob-subjects-scroll::-webkit-scrollbar { width: 4px; }
        .ob-subjects-scroll::-webkit-scrollbar-thumb { background: var(--hairline); border-radius: 2px; }

        .ob-semester-section { margin-bottom: var(--sp-md); }
        .ob-semester-section-title {
            font-size: 0.7rem; font-weight: 700; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;
            display: flex; align-items: center; gap: 6px;
        }
        .ob-semester-section-title::after { content:''; flex:1; height:1px; background:var(--hairline); }

        .ob-subject-row {
            display: flex; align-items: center; justify-content: space-between;
            background: var(--surface-soft); border: 1px solid var(--hairline);
            border-radius: var(--r-sm); padding: 8px var(--sp-sm); margin-bottom: 4px;
            transition: var(--transition);
        }
        .ob-subject-row:hover { background: var(--surface-card); }
        .ob-subject-row.has-grade { border-color: rgba(10,10,10,0.2); }
        .ob-subject-info { flex: 1; min-width: 0; }
        .ob-subject-name { font-size: 0.84rem; font-weight: 600; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ob-subject-meta { font-size: 0.68rem; color: var(--muted); }
        .ob-grade-wrap { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
        .ob-grade-input {
            width: 56px; background: var(--canvas); border: 1px solid var(--hairline);
            border-radius: var(--r-sm); color: var(--ink); font-size: 0.88rem; font-weight: 700;
            text-align: center; padding: 5px 3px; outline: none; transition: var(--transition);
        }
        .ob-grade-input:focus { border-color: var(--ink); }
        .ob-grade-input.pass { border-color: #22c55e; color: #16a34a; }
        .ob-grade-input.fail { border-color: #ef4444; color: #dc2626; }
        .ob-grade-status { font-size: 0.62rem; font-weight: 700; min-width: 30px; text-align: center; }
        .ob-grade-status.pass { color: #16a34a; }
        .ob-grade-status.fail { color: #dc2626; }

        .ob-footer {
            padding: var(--sp-lg) var(--sp-xl) var(--sp-xl);
            display: flex; align-items: center; justify-content: space-between;
            border-top: 1px solid var(--hairline);
        }
        .ob-btn-back {
            background: var(--surface-card); border: 1px solid var(--hairline);
            color: var(--muted); border-radius: var(--r-md);
            padding: 10px var(--sp-lg); font-size: 0.84rem; font-weight: 600;
            cursor: pointer; transition: var(--transition); font-family: 'Inter', sans-serif;
        }
        .ob-btn-back:hover { background: var(--surface-strong); color: var(--body-strong); }
        .ob-btn-back:disabled { opacity: 0.3; cursor: not-allowed; }

        .ob-btn-next {
            background: var(--ink); border: none; color: white;
            border-radius: var(--r-md); padding: 10px var(--sp-xl);
            font-size: 0.9rem; font-weight: 700;
            cursor: pointer; transition: var(--transition);
            display: flex; align-items: center; gap: 6px;
            font-family: 'Inter', sans-serif;
        }
        .ob-btn-next:hover { background: var(--body-strong); transform: translateY(-1px); }
        .ob-btn-next.finish { background: var(--success); }
        .ob-btn-next.finish:hover { background: #16a34a; }
        .ob-progress-text { font-size: 0.75rem; color: var(--muted); }

        .ob-step-anim { animation: obFadeStep 0.3s ease-out; }
        @keyframes obFadeStep { from{opacity:0;transform:translateX(16px)} to{opacity:1;transform:none} }

        /* ═══════════════════════════════════════════════════════════
           SEMESTER RESULT MODAL
        ═══════════════════════════════════════════════════════════ */
        .sem-result-overlay {
            display: none; position: fixed; inset: 0; z-index: 1100;
            background: rgba(10,10,10,0.6); backdrop-filter: blur(8px);
            align-items: center; justify-content: center; padding: var(--sp-md);
        }
        .sem-result-overlay.open { display: flex; }

        .sem-result-modal {
            background: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--r-xl);
            width: 100%; max-width: 580px; max-height: 92vh;
            overflow-y: auto;
            box-shadow: 0 40px 100px rgba(0,0,0,0.2);
            animation: srIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .sem-result-modal::-webkit-scrollbar { width: 4px; }
        .sem-result-modal::-webkit-scrollbar-thumb { background: var(--hairline); border-radius: 2px; }
        @keyframes srIn { from{opacity:0;transform:scale(0.92) translateY(24px)} to{opacity:1;transform:none} }

        .srm-header { padding: var(--sp-xl) var(--sp-xl) var(--sp-lg); text-align: center; border-bottom: 1px solid var(--hairline); position: relative; background: var(--surface-soft); border-radius: var(--r-xl) var(--r-xl) 0 0; }
        .srm-close { position: absolute; top: var(--sp-lg); right: var(--sp-lg); background: var(--surface-card); border: 1px solid var(--hairline); color: var(--muted); border-radius: var(--r-sm); width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0.9rem; transition: var(--transition); }
        .srm-close:hover { background: #fee2e2; border-color: #fecaca; color: #dc2626; }
        .srm-semester-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: var(--muted); margin-bottom: 6px; }
        .srm-title { font-family: 'Sora', sans-serif; font-size: 1.5rem; font-weight: 800; color: var(--ink); margin-bottom: 4px; letter-spacing: -0.5px; }
        .srm-subtitle { font-size: 0.84rem; color: var(--muted); }

        .srm-kpi-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; padding: var(--sp-lg) var(--sp-xl); border-bottom: 1px solid var(--hairline); }
        @media (max-width: 480px) { .srm-kpi-row { grid-template-columns: repeat(2,1fr); } }
        .srm-kpi { background: var(--surface-soft); border: 1px solid var(--hairline); border-radius: var(--r-md); padding: var(--sp-md) 8px; text-align: center; }
        .srm-kpi-val { font-family: 'Sora', sans-serif; font-size: 1.4rem; font-weight: 800; line-height: 1; margin-bottom: 4px; color: var(--ink); }
        .srm-kpi-label { font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); }
        .srm-kpi-val.gpa-ex  { color: #16a34a; }
        .srm-kpi-val.gpa-good{ color: #1a3a3a; }
        .srm-kpi-val.gpa-ok  { color: #d97706; }
        .srm-kpi-val.gpa-bad { color: #dc2626; }
        .srm-kpi-val.green   { color: #16a34a; }
        .srm-kpi-val.red     { color: #dc2626; }
        .srm-kpi-val.blue    { color: #1a3a3a; }
        .srm-kpi-val.yellow  { color: #d97706; }

        .srm-progress-section { padding: var(--sp-lg) var(--sp-xl); border-bottom: 1px solid var(--hairline); }
        .srm-progress-label { display: flex; align-items: center; justify-content: space-between; font-size: 0.82rem; margin-bottom: 8px; }
        .srm-progress-title { color: var(--muted); font-weight: 600; }
        .srm-progress-pct { font-weight: 800; font-family: 'Sora', sans-serif; font-size: 1rem; color: var(--ink); }
        .srm-progress-track { height: 10px; background: var(--surface-strong); border-radius: var(--r-pill); overflow: hidden; }
        .srm-progress-fill { height: 100%; border-radius: var(--r-pill); transition: width 1.2s cubic-bezier(0.4,0,0.2,1); }
        .srm-progress-meta { display: flex; align-items: center; justify-content: space-between; font-size: 0.72rem; color: var(--muted); margin-top: 6px; }

        .srm-recommend { margin: var(--sp-lg) var(--sp-xl); border-radius: var(--r-lg); padding: var(--sp-lg); display: flex; align-items: flex-start; gap: var(--sp-md); }
        .srm-recommend.increase { background: var(--surface-soft); border: 1px solid var(--hairline); }
        .srm-recommend.decrease { background: #fffbeb; border: 1px solid #fde68a; }
        .srm-recommend.maintain { background: #f0fdf4; border: 1px solid #bbf7d0; }
        .srm-recommend-icon { font-size: 2rem; flex-shrink: 0; }
        .srm-recommend-body { flex: 1; }
        .srm-recommend-tag { font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px; color: var(--muted); }
        .srm-recommend.decrease .srm-recommend-tag { color: #92400e; }
        .srm-recommend.maintain .srm-recommend-tag { color: #166534; }
        .srm-recommend-headline { font-family: 'Sora', sans-serif; font-size: 1.05rem; font-weight: 700; color: var(--ink); margin-bottom: 4px; }
        .srm-recommend-desc { font-size: 0.82rem; color: var(--muted); line-height: 1.55; }
        .srm-credit-change { display: inline-flex; align-items: center; gap: 5px; font-family: 'Sora', sans-serif; font-size: 1.5rem; font-weight: 800; margin: 4px 0; }
        .srm-credit-change.up   { color: #16a34a; }
        .srm-credit-change.down { color: #dc2626; }
        .srm-credit-change.same { color: var(--ink); }

        .srm-reasons { margin: 0 var(--sp-xl) var(--sp-lg); display: flex; flex-direction: column; gap: 6px; }
        .srm-reason-item { display: flex; align-items: flex-start; gap: 8px; font-size: 0.8rem; color: var(--muted); background: var(--surface-soft); border: 1px solid var(--hairline); border-radius: var(--r-sm); padding: 8px var(--sp-md); line-height: 1.45; }
        .srm-reason-icon { flex-shrink: 0; font-size: 0.9rem; }
        .srm-reason-item strong { color: var(--body-strong); }

        .srm-subj-section { margin: 0 var(--sp-xl); }
        .srm-subj-title { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .srm-subj-title::after { content:''; flex:1; height:1px; background:var(--hairline); }
        .srm-subj-list { display: flex; flex-direction: column; gap: 4px; margin-bottom: var(--sp-md); }
        .srm-subj-row { display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; border-radius: var(--r-sm); font-size: 0.82rem; gap: 10px; }
        .srm-subj-row.pass { background: #f0fdf4; border: 1px solid #bbf7d0; }
        .srm-subj-row.fail { background: #fff5f5; border: 1px solid #fecaca; }
        .srm-subj-name { flex: 1; color: var(--body-strong); font-weight: 500; }
        .srm-subj-credits { font-size: 0.72rem; color: var(--muted); min-width: 34px; text-align: right; }
        .srm-subj-grade { min-width: 40px; text-align: center; font-family: 'Sora', sans-serif; font-size: 0.9rem; font-weight: 800; }
        .srm-subj-grade.pass { color: #16a34a; }
        .srm-subj-grade.fail { color: #dc2626; }

        .srm-footer { padding: var(--sp-lg) var(--sp-xl) var(--sp-xl); display: flex; gap: 10px; justify-content: flex-end; border-top: 1px solid var(--hairline); flex-wrap: wrap; }
        .srm-btn-apply { background: var(--ink); border: none; color: white; border-radius: var(--r-md); padding: 10px var(--sp-lg); font-size: 0.88rem; font-weight: 700; cursor: pointer; transition: var(--transition); display: flex; align-items: center; gap: 5px; font-family: 'Inter', sans-serif; }
        .srm-btn-apply:hover { background: var(--body-strong); }
        .srm-btn-close { background: var(--surface-card); border: 1px solid var(--hairline); color: var(--muted); border-radius: var(--r-md); padding: 10px var(--sp-lg); font-size: 0.84rem; font-weight: 600; cursor: pointer; transition: var(--transition); font-family: 'Inter', sans-serif; }
        .srm-btn-close:hover { background: var(--surface-strong); color: var(--body-strong); }

        /* ═══════════════════════════════════════════════════════════
           TOAST & SAVE INDICATOR
        ═══════════════════════════════════════════════════════════ */
        .toast {
            position: fixed; bottom: var(--sp-xl); right: var(--sp-xl); z-index: 9999;
            padding: 12px var(--sp-lg); border-radius: var(--r-lg);
            font-weight: 600; font-size: 0.875rem;
            animation: slideUp 0.3s ease-out;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            max-width: 340px;
            font-family: 'Inter', sans-serif;
        }
        .toast.success { background: var(--ink); color: white; }
        .toast.error   { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .toast.info    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        @keyframes slideUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:none} }

        .save-indicator {
            position: fixed; top: 70px; right: var(--sp-xl); z-index: 9998;
            font-size: 0.78rem; font-weight: 600; padding: 6px 12px; border-radius: var(--r-md);
            display: none; align-items: center; gap: 5px; font-family: 'Inter', sans-serif;
        }
        .save-indicator.saving { display: flex; background: var(--surface-card); border: 1px solid var(--hairline); color: var(--muted); }
        .save-indicator.saved  { display: flex; background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; }
        .save-indicator.error  { display: flex; background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }

        /* ═══════════════════════════════════════════════════════════
           COMPLETE SEMESTER BUTTON
        ═══════════════════════════════════════════════════════════ */
        .btn-complete {
            display: inline-flex; align-items: center; gap: 5px;
            background: var(--ink); border: none; color: white;
            border-radius: var(--r-pill); padding: 7px var(--sp-md);
            font-size: 0.78rem; font-weight: 700; cursor: pointer;
            transition: var(--transition); margin-left: auto;
            white-space: nowrap; font-family: 'Inter', sans-serif;
        }
        .btn-complete:hover { background: var(--body-strong); transform: translateY(-1px); }
        .btn-complete:disabled { background: var(--surface-strong); color: var(--muted-soft); box-shadow: none; cursor: not-allowed; transform: none; }

        .counter-badge {
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--ink); color: white; border-radius: 50%;
            width: 18px; height: 18px; font-size: 0.65rem; font-weight: 800; margin-left: 3px;
        }

        /* ═══════════════════════════════════════════════════════════
           DASHBOARD SPECIFIC — Stat cards
        ═══════════════════════════════════════════════════════════ */
        .dash-prog-fill { height: 100%; border-radius: var(--r-pill); transition: width 1.4s cubic-bezier(0.4,0,0.2,1); }
        .dash-prog-pct { font-family: 'Sora', sans-serif; font-weight: 800; font-size: 0.88rem; }
        .dash-prog-pct.great { color: #16a34a; }
        .dash-prog-pct.mid   { color: var(--ink); }
        .dash-prog-pct.low   { color: #d97706; }

        .dash-sub-val { font-family: 'Sora', sans-serif; font-size: 1rem; font-weight: 800; color: var(--ink); }
        .dash-sub-val.green { color: #16a34a; }
        .dash-sub-val.amber { color: #d97706; }
        .dash-sub-val.blue  { color: #1a3a3a; }
        .dash-sub-label { font-size: 0.6rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 2px; }

        .dash-strength-avg { font-family: 'Sora', sans-serif; font-size: 0.82rem; font-weight: 800; min-width: 28px; text-align: right; flex-shrink: 0; }
        .dash-strength-avg.ex   { color: #16a34a; }
        .dash-strength-avg.good { color: #1a3a3a; }
        .dash-strength-avg.ok   { color: #d97706; }
        .dash-strength-avg.bad  { color: #dc2626; }
        .dash-strength-avg.na   { color: var(--muted-soft); }

        .dash-advice-num { font-family: 'Sora', sans-serif; font-size: 2.2rem; font-weight: 900; line-height: 1; margin-bottom: 3px; color: var(--ink); }
        .dash-advice-num.up   { color: #16a34a; }
        .dash-advice-num.same { color: var(--ink); }
        .dash-advice-num.down { color: #dc2626; }

        .dash-no-data { text-align: center; padding: var(--sp-lg) 6px; color: var(--muted); font-size: 0.8rem; }
        .dash-no-data-icon { font-size: 1.6rem; margin-bottom: 4px; opacity: 0.5; }
        .dash-sw-grid { display: flex; gap: var(--sp-lg); position: relative; }
        .dash-sw-grid::after { content: ''; position: absolute; left: 50%; top: 0; bottom: 0; width: 1px; border-left: 1.5px dashed var(--rule-strong); transform: translateX(-50%); }
        .dash-sw-col { flex: 1; min-width: 0; padding: 0 4px; }

        /* ═══════════════════════════════════════════════════════════
           CURRENT COURSES EMPTY STATE
        ═══════════════════════════════════════════════════════════ */
        .current-courses-empty { text-align: center; padding: var(--sp-xl); color: var(--muted); font-size: 0.88rem; }

        /* ═══════════════════════════════════════════════════════════
           EMPTY STATE
        ═══════════════════════════════════════════════════════════ */
        .empty-state { text-align: center; padding: 3rem var(--sp-xl); color: var(--muted); display: flex; flex-direction: column; align-items: center; gap: var(--sp-md); }
        .empty-state svg { width: 48px; height: 48px; color: var(--surface-strong); }
        .empty-state h3 { color: var(--body-strong); font-size: 1.05rem; font-weight: 700; }

        /* ═══════════════════════════════════════════════════════════
           LOADER
        ═══════════════════════════════════════════════════════════ */
        .loader { display: none; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 0; gap: var(--sp-md); }
        .spinner { width: 40px; height: 40px; border: 3px solid var(--hairline); border-radius: 50%; border-top-color: var(--ink); animation: spin 0.8s linear infinite; }
        @keyframes spin { 100%{transform:rotate(360deg)} }

        /* ═══════════════════════════════════════════════════════════
           DIVIDERS & UTILS
        ═══════════════════════════════════════════════════════════ */
        .divider { height: 1px; background: var(--hairline); margin: var(--sp-xl) 0; }
        .card-title-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--sp-lg); }
        .card-heading { font-family: 'Sora', sans-serif; font-size: 1rem; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px; }

        /* ═══════════════════════════════════════════════════════════
           ANIMATIONS
        ═══════════════════════════════════════════════════════════ */
        @keyframes fadeUp { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }

        /* ═══════════════════════════════════════════════════════════
           CHIP GROUP (filter tabs for suggestions)
        ═══════════════════════════════════════════════════════════ */
        .chip-group { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: var(--sp-lg); }
        .chip {
            padding: 5px 14px; border-radius: var(--r-pill);
            font-size: 0.78rem; font-weight: 600;
            cursor: pointer; transition: var(--transition);
            border: 1px solid var(--hairline);
            background: var(--canvas); color: var(--muted);
            font-family: 'Inter', sans-serif;
        }
        .chip:hover { border-color: var(--ink); color: var(--ink); }
        .chip.active { background: var(--ink); color: white; border-color: var(--ink); }

        /* ═══════════════════════════════════════════════════════════
           CHART PEER INFO
        ═══════════════════════════════════════════════════════════ */
        .chart-peer-info { font-size: 0.73rem; color: var(--muted); display: flex; align-items: center; gap: 4px; margin-left: auto; }

        /* select in ob-wizard */
        .ob-select {
            background: var(--canvas); border: 1.5px solid var(--hairline);
            border-radius: var(--r-md); color: var(--ink);
            padding: 10px 14px; font-size: 0.9rem; font-weight: 500;
            outline: none; cursor: pointer; transition: var(--transition); width: 100%;
            font-family: 'Inter', sans-serif;
        }
        .ob-select:focus { border-color: var(--ink); box-shadow: 0 0 0 3px rgba(10,10,10,0.08); }

        /* Scrollbar for drawer body */
        .grade-drawer-body { flex:1; overflow-y:auto; padding: var(--sp-md) var(--sp-xl); }
        .grade-drawer-body::-webkit-scrollbar { width: 4px; }
        .grade-drawer-body::-webkit-scrollbar-thumb { background: var(--hairline); border-radius: 2px; }

        /* alias for compat */
        .grade-input { font-family: 'Sora', sans-serif; }

        /* ── Dash panel cards ── */
        .dash-panel {
            display: grid;
            grid-template-columns: 1fr 1.4fr 1.1fr;
            gap: var(--sp-md);
            margin-bottom: var(--sp-xl);
            align-items: start;
        }
        @media (max-width: 860px) { .dash-panel { grid-template-columns: 1fr; } }
        @media (min-width: 560px) and (max-width: 860px) { .dash-panel { grid-template-columns: 1fr 1fr; } }

        .dash-card {
            background: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: var(--sp-lg);
            transition: var(--transition);
        }
        .dash-card:hover { border-color: rgba(10,10,10,0.18); box-shadow: 0 4px 16px rgba(0,0,0,0.06); }

        .dash-credit-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--sp-sm); }
        .dash-credit-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); display: flex; align-items: center; gap: 5px; }

        .dash-credit-numbers { display: flex; align-items: baseline; gap: 4px; margin-bottom: var(--sp-sm); }
        .dash-credit-earned { font-family: 'Sora', sans-serif; font-size: 1.8rem; font-weight: 800; color: var(--ink); line-height: 1; }
        .dash-credit-sep { font-size: 0.95rem; color: var(--muted-soft); }
        .dash-credit-total { font-size: 0.95rem; color: var(--muted); font-weight: 600; }

        .dash-prog-track { height: 7px; background: var(--surface-strong); border-radius: var(--r-pill); overflow: hidden; margin-bottom: 6px; }
        .dash-prog-foot { display: flex; justify-content: space-between; font-size: 0.7rem; color: var(--muted); }

        .dash-credit-sub { display: flex; gap: 10px; margin-top: var(--sp-sm); padding-top: var(--sp-sm); border-top: 1px solid var(--hairline); }
        .dash-sub-item { flex: 1; text-align: center; }

        .dash-strength-title { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); margin-bottom: var(--sp-sm); display: flex; align-items: center; gap: 5px; }
        .dash-strength-list { display: flex; flex-direction: column; gap: 6px; }
        .dash-strength-row { display: flex; align-items: center; gap: 10px; }
        .dash-strength-name { font-size: 0.78rem; font-weight: 600; color: var(--body-strong); flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
        .dash-strength-bar-wrap { width: 56px; flex-shrink: 0; }
        .dash-strength-bar-track { height: 4px; background: var(--surface-strong); border-radius: 2px; overflow: hidden; }
        .dash-strength-bar-fill { height: 100%; border-radius: 2px; transition: width 0.9s ease; }

        .dash-advice-title { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); margin-bottom: var(--sp-sm); display: flex; align-items: center; gap: 5px; }
        .dash-advice-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: var(--r-pill); font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
        .dash-advice-badge.increase { background: var(--surface-card); color: var(--body-strong); border: 1px solid var(--hairline); }
        .dash-advice-badge.decrease { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .dash-advice-badge.maintain { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .dash-advice-unit { font-size: 0.75rem; color: var(--muted); margin-bottom: 6px; }
        .dash-advice-reason { font-size: 0.75rem; color: var(--muted); line-height: 1.5; border-top: 1px solid var(--hairline); padding-top: 8px; margin-top: 4px; }

        /* ── Feature card grid for dashboard ── */
        .feat-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--sp-md); }
        @media (max-width: 900px) { .feat-grid-4 { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .feat-grid-4 { grid-template-columns: 1fr 1fr; } }

        /* ── Course list ── */
        .course-list { display: flex; flex-direction: column; gap: 6px; margin-top: var(--sp-xs); }

        /* ── Prerequisite Modal ── */
        .prereq-modal-overlay {
            position: fixed; inset: 0; z-index: 2000;
            background: rgba(10,10,10,0.4);
            display: flex; align-items: center; justify-content: center;
            padding: var(--sp-md);
            opacity: 1; transition: opacity 0.3s;
        }
        .prereq-modal-overlay.hidden { display: none; opacity: 0; pointer-events: none; }
        .prereq-modal {
            background: var(--canvas);
            border-radius: var(--r-lg);
            width: 100%; max-width: 440px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            display: flex; flex-direction: column;
            animation: modalIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-family: 'Inter', sans-serif;
        }
        @keyframes modalIn { from{transform:scale(0.95);opacity:0} to{transform:scale(1);opacity:1} }
        .prereq-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 20px; border-bottom: 1px solid var(--hairline);
        }
        .prereq-title { font-size: 1.05rem; font-weight: 700; color: var(--ink); }
        .prereq-close { background: transparent; border: none; font-size: 1.2rem; cursor: pointer; color: var(--muted); padding: 4px; display: flex; align-items: center; justify-content: center; border-radius: 4px; }
        .prereq-close:hover { background: var(--surface-soft); color: var(--ink); }
        .prereq-body { padding: 20px; overflow-y: auto; }
        .prereq-desc { font-size: 0.95rem; color: var(--muted); margin-bottom: 20px; line-height: 1.5; }
        .prereq-desc strong { color: var(--ink); font-weight: 700; }
        .prereq-list { display: flex; flex-direction: column; gap: 12px; }
        .prereq-item {
            display: flex; align-items: center; gap: 14px;
            padding: 16px; border-radius: 8px; border: 1px solid;
        }
        .prereq-item.is-passed { background: #f0fdf4; border-color: #bbf7d0; }
        .prereq-item.is-failed { background: #fff5f5; border-color: #fecaca; }
        .prereq-item-icon { font-size: 1.2rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
        .prereq-item.is-passed .prereq-item-icon { color: #16a34a; }
        .prereq-item.is-failed .prereq-item-icon { color: #dc2626; }
        .prereq-item-info { display: flex; flex-direction: column; gap: 4px; }
        .prereq-item-name { font-size: 0.95rem; font-weight: 700; color: var(--ink); }
        .prereq-item-status { font-size: 0.8rem; font-weight: 700; }
        .prereq-item.is-passed .prereq-item-status { color: #16a34a; }
        .prereq-item.is-failed .prereq-item-status { color: #dc2626; }
        .prereq-footer {
            background: #f8fafc; padding: 16px 20px; border-top: 1px solid var(--hairline);
            border-radius: 0 0 var(--r-lg) var(--r-lg);
        }
        .prereq-btn-ok {
            width: 100%; background: #004ecc; color: white; border: none;
            border-radius: 6px; padding: 12px; font-size: 0.95rem; font-weight: 700;
            cursor: pointer; transition: background 0.2s;
        }
        .prereq-btn-ok:hover { background: #003db3; }

    </style>
</head>
<body>

{{-- ══════════════════════════════════════════════════════════════════
     SEMESTER RESULT MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="sem-result-overlay" id="sem-result-overlay">
    <div class="sem-result-modal">
        <div class="srm-header">
            <button class="srm-close" onclick="closeSemResultModal()">✕</button>
            <div class="srm-semester-label" id="srm-sem-label">Kết quả học kỳ</div>
            <div class="srm-title" id="srm-title">Hoàn tất Học Kỳ!</div>
            <div class="srm-subtitle" id="srm-subtitle">Phân tích kết quả và gợi ý lộ trình tín chỉ kỳ tiếp theo</div>
        </div>
        <div class="srm-kpi-row" id="srm-kpi-row">
            <div class="srm-kpi"><div class="srm-kpi-val" id="srm-gpa">--</div><div class="srm-kpi-label">GPA học kỳ</div></div>
            <div class="srm-kpi"><div class="srm-kpi-val green" id="srm-pass-count">0</div><div class="srm-kpi-label">Môn pass</div></div>
            <div class="srm-kpi"><div class="srm-kpi-val red" id="srm-fail-count">0</div><div class="srm-kpi-label">Môn fail</div></div>
            <div class="srm-kpi"><div class="srm-kpi-val blue" id="srm-credits-done">0</div><div class="srm-kpi-label">TC tích lũy</div></div>
        </div>
        <div class="srm-progress-section">
            <div class="srm-progress-label">
                <span class="srm-progress-title">🎯 Tiến độ tích lũy tín chỉ</span>
                <span class="srm-progress-pct" id="srm-prog-pct">0%</span>
            </div>
            <div class="srm-progress-track">
                <div class="srm-progress-fill" id="srm-prog-fill" style="width:0%;background:var(--ink);"></div>
            </div>
            <div class="srm-progress-meta">
                <span id="srm-prog-left">Còn lại: -- TC</span>
                <span id="srm-prog-pace">Cần -- TC/kỳ</span>
            </div>
        </div>
        <div class="srm-recommend" id="srm-recommend">
            <div class="srm-recommend-icon" id="srm-rec-icon">📈</div>
            <div class="srm-recommend-body">
                <div class="srm-recommend-tag" id="srm-rec-tag">Gợi ý</div>
                <div class="srm-recommend-headline" id="srm-rec-headline">Giữ nguyên số tín chỉ</div>
                <div class="srm-credit-change" id="srm-credit-change"><span>--</span> TC/kỳ</div>
                <div class="srm-recommend-desc" id="srm-rec-desc">--</div>
            </div>
        </div>
        <div class="srm-reasons" id="srm-reasons"></div>
        <div class="srm-subj-section" id="srm-subj-section"></div>
        <div class="srm-footer">
            <button class="srm-btn-close" onclick="closeSemResultModal()">Bỏ qua</button>
            <button class="srm-btn-apply" id="srm-btn-apply" onclick="applyCreditRecommendation()">✓ Áp dụng gợi ý</button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     ONBOARDING WIZARD
══════════════════════════════════════════════════════════════════ --}}
<div class="ob-overlay hidden" id="ob-overlay">
    <div class="ob-modal" id="ob-modal">
        <div class="ob-header" id="ob-header">
            <div class="ob-step-dots" id="ob-dots">
                <div class="ob-dot active" data-step="0"></div>
                <div class="ob-dot" data-step="1"></div>
                <div class="ob-dot" data-step="2"></div>
                <div class="ob-dot" data-step="3"></div>
            </div>
            <div id="ob-header-content"></div>
        </div>
        <div class="ob-body" id="ob-body-content"></div>
        <div class="ob-footer">
            <button class="ob-btn-back" id="ob-btn-back" onclick="obPrev()">← Quay lại</button>
            <span class="ob-progress-text" id="ob-progress-text">Bước 1 / 4</span>
            <button class="ob-btn-next" id="ob-btn-next" onclick="obNext()">Tiếp theo →</button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     CONFIG PANEL
══════════════════════════════════════════════════════════════════ --}}
<div class="config-panel-overlay" id="config-overlay" onclick="closeConfigPanel()"></div>
<div class="config-panel" id="config-panel">
    <div class="config-panel-header">
        <div class="config-panel-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg>
            Cấu Hình Chương Trình
        </div>
        <button class="config-panel-close" onclick="closeConfigPanel()">✕</button>
    </div>
    <div class="config-form-grid">
        <div class="input-group">
            <label class="input-label" for="academic_year">Niên khóa</label>
            <select id="academic_year" class="clay-select">
                @foreach($academicYears as $year)
                    <option value="{{ $year }}" {{ $year == '2022-2026' ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div class="input-group">
            <label class="input-label" for="program_type">Hệ đào tạo</label>
            <select id="program_type" class="clay-select">
                @foreach($programTypes as $type)
                    <option value="{{ $type }}" {{ $type == 'Chính quy' ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="input-group">
            <label class="input-label" for="target_semester">Học kỳ hiện tại</label>
            <select id="target_semester" class="clay-select">
                @for($i = 1; $i <= 8; $i++)
                    <option value="{{ $i }}" {{ $i == 3 ? 'selected' : '' }}>Học kỳ {{ $i }}</option>
                @endfor
            </select>
        </div>
        <div class="input-group">
            <label class="input-label" for="target_years">Mục tiêu tốt nghiệp</label>
            <select id="target_years" class="clay-select" onchange="updateCreditStats()">
                @for($y = 3; $y <= 6; $y++)
                    <option value="{{ $y }}" {{ $y == 4 ? 'selected' : '' }}>{{ $y }} năm</option>
                @endfor
            </select>
        </div>
    </div>
    <div class="config-stats">
        <div class="config-stat"><div class="config-stat-val" id="stat-total-credits">{{ $totalCredits }}</div><div class="config-stat-label">Tổng TC</div></div>
        <div class="config-stat"><div class="config-stat-val" id="stat-total-semesters">8</div><div class="config-stat-label">Số kỳ</div></div>
        <div class="config-stat"><div class="config-stat-val highlight" id="stat-credits-per-sem">—</div><div class="config-stat-label">TC/kỳ</div></div>
        <div class="config-stat"><div class="config-stat-val" id="stat-earned-credits">0</div><div class="config-stat-label">Đã tích lũy</div></div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     GRADE DRAWER (slide-in từ trái)
══════════════════════════════════════════════════════════════════ --}}
<div class="drawer-overlay" id="grade-drawer-overlay" onclick="closeGradeDrawer()"></div>
<div class="grade-drawer" id="grade-drawer">
    <div class="drawer-header">
        <div>
            <div class="drawer-title">📝 Nhập Điểm Môn Học</div>
            <div class="drawer-subtitle">Điểm > 5.0 được tính là Pass ✅</div>
        </div>
        <button class="drawer-close" onclick="closeGradeDrawer()">✕</button>
    </div>
    <div class="drawer-search">
        <input type="text" id="grade-search" class="clay-input" placeholder="🔍 Tìm kiếm môn học..." oninput="filterGradeSearch(this.value)" style="height:38px;font-size:0.84rem;">
    </div>
    <div class="drawer-stats">
        <div class="drawer-stat pass">✓ Pass: <strong id="drawer-pass-count">0</strong></div>
        <div class="drawer-stat fail">✗ Fail: <strong id="drawer-fail-count">0</strong></div>
        <div class="drawer-stat">Chưa nhập: <strong id="drawer-empty-count">0</strong></div>
    </div>
    <div class="grade-drawer-body" id="grade-drawer-body">
        @foreach($subjects as $semName => $semSubjects)
            <div class="drawer-sem-group">
                <div class="drawer-sem-header">Học kỳ chuẩn {{ $semName }}</div>
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
     HISTORY DRAWER (slide-in từ phải)
══════════════════════════════════════════════════════════════════ --}}
<div class="drawer-overlay" id="history-drawer-overlay" onclick="closeHistoryDrawer()"></div>
<div class="history-drawer" id="history-drawer">
    <div class="drawer-header">
        <div>
            <div class="drawer-title">📚 Lịch Sử Học Kỳ</div>
            <div class="drawer-subtitle">Các học kỳ bạn đã hoàn tất</div>
        </div>
        <button class="drawer-close" onclick="closeHistoryDrawer()">✕</button>
    </div>
    <div class="drawer-body" id="history-drawer-body">
        <div class="history-empty" id="history-empty">
            <span class="history-empty-icon">📖</span>
            <p>Chưa có học kỳ nào được hoàn tất.<br>Ấn <strong>✓ Hoàn tất học kỳ</strong> sau khi kết thúc mỗi kỳ học.</p>
        </div>
        <div id="history-list"></div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     APP SHELL
══════════════════════════════════════════════════════════════════ --}}
<div class="app-shell">

    {{-- ── SIDEBAR ── --}}
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-name">AcademiaLink</div>
            <div class="sidebar-logo-sub">Smart Student Planner</div>
        </div>

        <div class="sidebar-user">
            <div class="sidebar-avatar">👤</div>
            <div style="min-width:0;flex:1;">
                <div class="sidebar-user-name">{{ Auth::user()->fullName ?? Auth::user()->username }}</div>
                <div class="sidebar-user-meta">MSSV: {{ Auth::user()->student_code ?? '—' }}</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <span class="sidebar-nav-label">Điều hướng</span>

            <button class="nav-item active" onclick="switchTab('dashboard', this)" id="nav-dashboard">
                <svg class="nav-item-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>
                Dashboard
            </button>

            <button class="nav-item" onclick="switchTab('suggestions', this)" id="nav-suggestions">
                <svg class="nav-item-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                Đề Xuất Môn Học
            </button>

            <button class="nav-item" onclick="switchTab('chart', this)" id="nav-chart">
                <svg class="nav-item-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                Biểu Đồ Điểm
            </button>

            <button class="nav-item" onclick="switchTab('analysis', this)" id="nav-analysis">
                <svg class="nav-item-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" /></svg>
                Phân Tích
            </button>

            <button class="nav-item" onclick="switchTab('courses', this)" id="nav-courses">
                <svg class="nav-item-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                Môn Đang Học
                <span class="nav-badge" id="nav-cc-badge">0</span>
            </button>
        </nav>

        <div class="sidebar-actions">
            <span class="sidebar-nav-label" style="padding:0 var(--sp-xs) 4px;">Công cụ</span>
            <button class="btn-sidebar-action btn-grades-sb" onclick="toggleGradeDrawer()">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                Nhập điểm
                <span class="nav-badge" id="grade-count-badge" style="position:static;margin-left:auto;">0</span>
            </button>
            <button class="btn-sidebar-action btn-history-sb" onclick="toggleHistoryDrawer()">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                Lịch sử HK
                <span class="nav-badge" id="history-count-badge" style="position:static;margin-left:auto;"></span>
            </button>
            <button class="btn-sidebar-action btn-config-sb" id="btn-config" onclick="toggleConfigPanel()">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg>
                Cấu hình
                <span class="config-dot-sb" id="config-dot"></span>
            </button>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn-sidebar-action btn-logout-sb">
                    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" /></svg>
                    Đăng xuất
                </button>
            </form>
        </div>
    </aside>

    {{-- ── MAIN CONTENT ── --}}
    <div class="main-content">

        {{-- Topbar --}}
        <header class="topbar">
            <div>
                <div class="topbar-title" id="topbar-title">Dashboard</div>
                <div class="topbar-subtitle" id="topbar-subtitle">Tổng quan tiến độ học tập của bạn</div>
            </div>
            <div class="topbar-right">
                <div style="font-size:0.8rem;color:var(--muted);">{{ Auth::user()->email }}</div>
            </div>
        </header>

        {{-- ════════════════════════════════════════════════════════
             TAB: DASHBOARD
        ════════════════════════════════════════════════════════ --}}
        <div class="page-content tab-panel active" id="tab-dashboard">

            {{-- Welcome band --}}
            <div style="margin-bottom:var(--sp-xl);">
                <h1 style="font-family:'Sora',sans-serif;font-size:1.75rem;font-weight:800;color:var(--ink);letter-spacing:-0.5px;margin-bottom:4px;">
                    Chào mừng, {{ Auth::user()->fullName ?? Auth::user()->username }} 👋
                </h1>
                <p style="color:var(--muted);font-size:0.9rem;">Theo dõi tiến độ học tập và nhận gợi ý thông minh.</p>
            </div>

            {{-- 4 KPI feature cards --}}
            <div class="feat-grid-4" style="margin-bottom:var(--sp-xl);">
                <div class="feat-card feat-card-teal">
                    <div class="feat-card-label">GPA tích lũy</div>
                    <div class="feat-card-value" id="kpi-gpa">—</div>
                    <div class="feat-card-sub">/ 10.0 điểm</div>
                </div>
                <div class="feat-card feat-card-lavender">
                    <div class="feat-card-label">Tín chỉ tích lũy</div>
                    <div class="feat-card-value" id="kpi-credits">0</div>
                    <div class="feat-card-sub" id="kpi-credits-sub">/ {{ $totalCredits }} TC</div>
                </div>
                <div class="feat-card feat-card-peach">
                    <div class="feat-card-label">Học kỳ hiện tại</div>
                    <div class="feat-card-value" id="kpi-semester">—</div>
                    <div class="feat-card-sub">/ 8 học kỳ</div>
                </div>
                <div class="feat-card feat-card-ochre">
                    <div class="feat-card-label">Tiến độ</div>
                    <div class="feat-card-value" id="kpi-progress">0%</div>
                    <div class="feat-card-sub" id="kpi-progress-sub">Hoàn thành chương trình</div>
                </div>
            </div>

            {{-- Dashboard 3-col mini cards --}}
            <div id="dash-global-warning"></div>
            <div class="dash-panel" id="dash-panel">

                {{-- Card 1: Tiến độ tín chỉ --}}
                <div class="dash-card">
                    <div class="dash-credit-header">
                        <div class="dash-credit-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.902 59.902 0 0 1 10.399 5.84 50.53 50.53 0 0 0-2.658.814m-15.482 0A50.699 50.699 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" /></svg>
                            Tiến độ tín chỉ
                        </div>
                        <span class="dash-prog-pct" id="dash-prog-pct">0%</span>
                    </div>
                    <div class="dash-credit-numbers">
                        <span class="dash-credit-earned" id="dash-credit-earned">0</span>
                        <span class="dash-credit-sep">/</span>
                        <span class="dash-credit-total" id="dash-credit-total">{{ $totalCredits }}</span>
                        <span class="dash-credit-sep" style="font-size:.68rem;margin-left:2px;">TC</span>
                    </div>
                    <div class="dash-prog-track" style="margin-bottom:6px;">
                        <div class="dash-prog-fill" id="dash-prog-fill" style="width:0%;background:var(--ink);"></div>
                    </div>
                    <div class="dash-prog-foot">
                        <span id="dash-prog-left">Còn lại: --</span>
                        <span id="dash-prog-rem-sem">-- kỳ còn</span>
                    </div>
                    <div class="dash-credit-sub">
                        <div class="dash-sub-item"><div class="dash-sub-val green" id="dash-pass-credits">0</div><div class="dash-sub-label">TC pass</div></div>
                        <div class="dash-sub-item"><div class="dash-sub-val amber" id="dash-needed-per-sem">--</div><div class="dash-sub-label">TC/kỳ cần</div></div>
                        <div class="dash-sub-item"><div class="dash-sub-val blue" id="dash-current-sem">--</div><div class="dash-sub-label">HK này</div></div>
                    </div>
                </div>

                {{-- Card 2: Thế mạnh --}}
                <div class="dash-card">
                    <div class="dash-strength-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                        Thế Mạnh & Điểm Yếu
                    </div>
                    <div id="dash-strength-content">
                        <div class="dash-no-data"><div class="dash-no-data-icon">⭐</div><div>Nhập điểm để xem</div></div>
                    </div>
                </div>

                {{-- Card 3: Gợi ý tín chỉ --}}
                <div class="dash-card">
                    <div class="dash-advice-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                        Gợi Ý Kỳ Tiếp Theo
                    </div>
                    <div id="dash-advice-badge-wrap">
                        <span class="dash-advice-badge maintain" id="dash-advice-badge">• Phân tích...</span>
                    </div>
                    <div class="dash-advice-num same" id="dash-advice-num">--</div>
                    <div class="dash-advice-unit">tín chỉ / học kỳ</div>
                    <div class="dash-advice-reason" id="dash-advice-reason">Nhập điểm các môn để nhận gợi ý.</div>
                </div>
            </div>

            {{-- Score Comparison Chart trên Dashboard --}}
            <div class="clay-card" style="margin-bottom:var(--sp-xl);">
                <div class="card-title-row">
                    <div class="card-heading">
                        📊 Biểu Đồ So Sánh Điểm
                        <span class="chart-peer-info" id="chart-peer-label"></span>
                    </div>
                    <button class="btn-primary" onclick="switchTab('chart', document.getElementById('nav-chart'))" style="height:36px;font-size:0.78rem;padding:0 14px;">Xem đầy đủ →</button>
                </div>
                <div class="chart-sem-filter" id="chart-sem-filter-dash">
                    <button class="chart-sem-btn active" data-sem="all" onclick="filterChartSem('all', this)">Tất cả HK</button>
                </div>
                <div class="chart-wrapper" style="min-height:240px;">
                    <div class="chart-empty" id="chart-empty-dash">
                        <span class="chart-empty-icon">📊</span>
                        <p>Nhập điểm môn học để xem biểu đồ so sánh với sinh viên cùng khóa</p>
                    </div>
                    <canvas id="gradeChart" style="display:none;"></canvas>
                </div>
                <div class="chart-legend" id="chart-legend" style="display:none;">
                    <div class="chart-legend-item"><div class="chart-legend-dot" style="background:var(--ink);"></div>Điểm của bạn</div>
                    <div class="chart-legend-item"><div class="chart-legend-dot" style="background:var(--brand-ochre);border-radius:50%;"></div>Điểm TB cùng khóa</div>
                    <div class="chart-legend-item"><div class="chart-legend-dot" style="background:var(--brand-coral);border-radius:50%;"></div>Ngưỡng Pass (5.0)</div>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════
             TAB: ĐỀ XUẤT MÔN HỌC
        ════════════════════════════════════════════════════════ --}}
        <div class="page-content tab-panel" id="tab-suggestions">
            <div style="margin-bottom:var(--sp-xl);">
                <h2 style="font-family:'Sora',sans-serif;font-size:1.4rem;font-weight:800;color:var(--ink);letter-spacing:-0.4px;margin-bottom:4px;">Môn Học Đề Xuất</h2>
                <p style="color:var(--muted);font-size:0.88rem;">Dựa trên tiến độ và điểm số của bạn, hệ thống gợi ý các môn phù hợp.</p>
            </div>

            <div class="clay-card">
                <div class="card-title-row">
                    <div class="card-heading" style="display:flex; align-items:center;">
                        ✨ Gợi Ý Học Kỳ Mới
                        <button class="btn-info-clay" onclick="openScoreInfoModal()" title="Cách tính điểm" style="margin-left:12px; width:36px; height:36px; border-radius:50%; background:var(--canvas); color:var(--primary); box-shadow: 0 4px 12px rgba(0,0,0,0.06); border:1px solid var(--hairline);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:20px;height:20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" /></svg>
                        </button>
                    </div>
                </div>
                <div class="loader" id="loader">
                    <div class="spinner"></div>
                    <p style="color:var(--muted);font-size:0.9rem;">Hệ thống đang phân tích và lập lộ trình...</p>
                </div>
                <div id="suggestions-list" class="suggestion-list"></div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════
             TAB: BIỂU ĐỒ ĐIỂM (chi tiết)
        ════════════════════════════════════════════════════════ --}}
        <div class="page-content tab-panel" id="tab-chart">
            <div style="margin-bottom:var(--sp-xl);">
                <h2 style="font-family:'Sora',sans-serif;font-size:1.4rem;font-weight:800;color:var(--ink);letter-spacing:-0.4px;margin-bottom:4px;">Biểu Đồ So Sánh Điểm</h2>
                <p style="color:var(--muted);font-size:0.88rem;">So sánh điểm số của bạn với sinh viên cùng khóa theo từng môn học.</p>
            </div>

            <div class="clay-card" style="margin-bottom:var(--sp-xl);">
                <div class="card-title-row">
                    <div class="card-heading">
                        📊 Điểm Cá Nhân vs. Trung Bình Khóa
                        <span class="chart-peer-info" id="chart-peer-label-detail"></span>
                    </div>
                </div>
                <div class="chart-sem-filter" id="chart-sem-filter">
                    <button class="chart-sem-btn active" data-sem="all" onclick="filterChartSem('all', this)">Tất cả HK</button>
                </div>
                <div class="chart-wrapper" style="min-height:320px;">
                    <div class="chart-empty" id="chart-empty">
                        <span class="chart-empty-icon">📊</span>
                        <p>Nhập điểm môn học để xem biểu đồ so sánh với sinh viên cùng khóa</p>
                    </div>
                    <canvas id="gradeChartDetail" style="display:none;"></canvas>
                </div>
                <div class="chart-legend" id="chart-legend-detail" style="display:none;">
                    <div class="chart-legend-item"><div class="chart-legend-dot" style="background:var(--ink);"></div>Điểm của bạn</div>
                    <div class="chart-legend-item"><div class="chart-legend-dot" style="background:var(--brand-ochre);border-radius:50%;"></div>Điểm TB cùng khóa</div>
                    <div class="chart-legend-item"><div class="chart-legend-dot" style="background:var(--brand-coral);border-radius:50%;"></div>Ngưỡng Pass (5.0)</div>
                </div>
            </div>

            {{-- Summary stats for chart tab --}}
            <div class="content-grid">
                <div class="feat-card feat-card-mint">
                    <div class="feat-card-label">Môn đã nhập điểm</div>
                    <div class="feat-card-value-sm" id="chart-stat-graded">0 môn</div>
                    <div class="feat-card-sub">trong tổng số các môn</div>
                </div>
                <div class="feat-card feat-card-peach">
                    <div class="feat-card-label">GPA tích lũy hiện tại</div>
                    <div class="feat-card-value-sm" id="chart-stat-gpa">—</div>
                    <div class="feat-card-sub">Điểm trung bình tất cả môn</div>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════
             TAB: PHÂN TÍCH
        ════════════════════════════════════════════════════════ --}}
        <div class="page-content tab-panel" id="tab-analysis">
            <div style="margin-bottom:var(--sp-xl);">
                <h2 style="font-family:'Sora',sans-serif;font-size:1.4rem;font-weight:800;color:var(--ink);letter-spacing:-0.4px;margin-bottom:4px;">Phân Tích Điểm</h2>
                <p style="color:var(--muted);font-size:0.88rem;">Xem điểm trung bình và nhận cảnh báo theo nhóm kỹ năng hoặc khối kiến thức.</p>
            </div>

            <div class="analysis-toggle-group" style="display:inline-flex;background:var(--surface-soft);border:1px solid var(--hairline);border-radius:10px;padding:4px;margin-bottom:var(--sp-lg);gap:4px;">
                <button class="toggle-btn active" onclick="setAnalysisType('skill')" id="toggle-analysis-skill" style="border:none;background:var(--surface);color:var(--ink);padding:8px 16px;border-radius:8px;font-size:0.88rem;font-weight:600;cursor:pointer;box-shadow:var(--shadow-sm);transition:all 0.2s;">Phân tích theo Skill Groups</button>
                <button class="toggle-btn" onclick="setAnalysisType('program')" id="toggle-analysis-program" style="border:none;background:transparent;color:var(--muted);padding:8px 16px;border-radius:8px;font-size:0.88rem;font-weight:600;cursor:pointer;transition:all 0.2s;">Phân tích theo Program Groups</button>
            </div>

            <div class="clay-card" id="group-analysis-card">
                <div id="group-analysis-content">
                    <div class="group-analysis-empty">
                        <div class="group-analysis-empty-icon">📊</div>
                        <p>Nhập điểm các môn học để xem phân tích điểm theo nhóm</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════
             TAB: MÔN ĐANG HỌC
        ════════════════════════════════════════════════════════ --}}
        <div class="page-content tab-panel" id="tab-courses">
            <div style="margin-bottom:var(--sp-xl);">
                <h2 style="font-family:'Sora',sans-serif;font-size:1.4rem;font-weight:800;color:var(--ink);letter-spacing:-0.4px;margin-bottom:4px;">Môn Đang Học Kỳ Này</h2>
                <p style="color:var(--muted);font-size:0.88rem;">Thêm môn từ phần Đề Xuất, nhập điểm và hoàn tất học kỳ khi kết thúc.</p>
            </div>

            <div class="clay-card" id="current-courses-card">
                <div class="card-title-row">
                    <div class="card-heading" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                        📖 Danh Sách Môn
                        <span class="counter-badge" id="cc-count">0</span>
                        <span class="pill" id="cc-credits" style="background:var(--surface-soft);color:var(--muted);border:1px solid var(--hairline);font-size:0.75rem;">0 TC</span>
                        <span class="pill" id="cc-recommend" style="background:#eef2ff;color:#4f46e5;border:1px solid #c7d2fe;font-size:0.75rem;">Khuyên dùng: -- TC</span>
                    </div>
                    <button class="btn-complete btn-primary" id="btn-complete" onclick="completeSemester()" disabled>
                        ✓ Hoàn tất học kỳ
                    </button>
                </div>
                <div id="current-courses-list" class="course-list">
                    <div class="current-courses-empty">Chưa có môn nào — vào <strong>Đề Xuất Môn Học</strong> và nhấn <strong>+ Thêm</strong>.</div>
                </div>
            </div>
        </div>

    </div>{{-- end main-content --}}
</div>{{-- end app-shell --}}

<div class="save-indicator" id="save-indicator"></div>

@php
    $subjectsBySem = $subjects->map(function($group) {
        return $group->map(function($sub) {
            return [
                'id'               => $sub->id,
                'name'             => $sub->name,
                'credits'          => $sub->credits,
                'semName'          => $sub->semester?->name ?? '?',
                'typeName'         => $sub->subjectType?->name ?? '',
                'skillGroupName'   => $sub->skillGroup?->name ?? 'Khác',
                'programGroupName' => $sub->programGroup?->name ?? 'Khác',
            ];
        })->values();
    });
@endphp

<script>
    // ─── Server data ──────────────────────────────────────────────────────────
    const ACADEMIC_YEARS  = @json($academicYears);
    const PROGRAM_TYPES   = @json($programTypes);
    const SUBJECTS_BY_SEM = @json($subjectsBySem);
    const TOTAL_CREDITS   = {{ $totalCredits }};
    const CSRF_TOKEN      = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // ─── App State ────────────────────────────────────────────────────────────
    let fetchTimer    = null;
    let saveTimer     = null;
    let prefTimer     = null;
    let currentCourses = [];
    try { const saved = localStorage.getItem('current_courses'); if(saved) currentCourses = JSON.parse(saved); } catch(e) {}
    let syncLock      = false;

    // ─── Tab Switching ────────────────────────────────────────────────────────
    const TAB_TITLES = {
        dashboard:   { title: 'Dashboard', sub: 'Tổng quan tiến độ học tập của bạn' },
        suggestions: { title: 'Đề Xuất Môn Học', sub: 'Gợi ý môn học phù hợp với tiến độ của bạn' },
        chart:       { title: 'Biểu Đồ So Sánh Điểm', sub: 'So sánh điểm của bạn với sinh viên cùng khóa' },
        analysis:    { title: 'Phân Tích', sub: 'Điểm trung bình theo nhóm kỹ năng hoặc khối kiến thức' },
        courses:     { title: 'Môn Đang Học', sub: 'Quản lý môn học trong học kỳ hiện tại' },
    };

    function switchTab(tabId, navEl) {
        // Hide all tab panels
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        // Remove active from all nav items
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        // Show selected panel
        const panel = document.getElementById('tab-' + tabId);
        if (panel) panel.classList.add('active');
        // Activate nav item
        if (navEl) navEl.classList.add('active');

        // Update topbar
        const meta = TAB_TITLES[tabId];
        if (meta) {
            document.getElementById('topbar-title').textContent = meta.title;
            document.getElementById('topbar-subtitle').textContent = meta.sub;
        }

        // Trigger chart reload when switching to chart tab
        if (tabId === 'chart' && chartRawData) {
            renderGradeChartDetail(chartRawData, 'all');
        }
        if (tabId === 'suggestions') {
            clearTimeout(fetchTimer);
            fetchTimer = setTimeout(fetchSuggestions, 100);
        }
    }

    // ─── Onboarding State ────────────────────────────────────────────────────
    let obStep = 0;
    let obData = { academic_year: null, program_type: null, current_semester: null, target_years: null, grades: {} };

    const OB_STEPS = [
        { label:'Bước 1 / 4', icon:'🎓', iconBg:'#f5f0e0', title:'Chào mừng bạn!', desc:'Hãy cho chúng tôi biết bạn đang theo học chương trình nào để hệ thống gợi ý chính xác nhất.' },
        { label:'Bước 2 / 4', icon:'📅', iconBg:'#faf5e8', title:'Bạn đang học kỳ nào?', desc:'Chọn học kỳ hiện tại để hệ thống xác định các môn phù hợp với tiến độ.' },
        { label:'Bước 3 / 4', icon:'📝', iconBg:'#faf5e8', title:'Điểm số của bạn', desc:'Nhập điểm các môn bạn đã học. Chỉ nhập những môn đã có điểm.' },
        { label:'Bước 4 / 4', icon:'🏆', iconBg:'#f0fdf4', title:'Mục tiêu tốt nghiệp', desc:'Bạn muốn hoàn thành chương trình trong bao nhiêu năm?' },
    ];

    function renderObDots() {
        document.querySelectorAll('.ob-dot').forEach((dot, i) => {
            dot.classList.remove('active', 'done');
            if (i < obStep) dot.classList.add('done');
            else if (i === obStep) dot.classList.add('active');
        });
    }

    function renderObHeader() {
        const s = OB_STEPS[obStep];
        document.getElementById('ob-header-content').innerHTML = `
            <div class="ob-step-label">${s.label}</div>
            <div class="ob-icon" style="background:${s.iconBg}">${s.icon}</div>
            <div class="ob-title">${s.title}</div>
            <p class="ob-desc">${s.desc}</p>`;
    }

    function renderObBody() {
        const body = document.getElementById('ob-body-content');
        body.classList.remove('ob-step-anim');
        void body.offsetWidth;
        body.classList.add('ob-step-anim');

        if (obStep === 0) {
            const yearOpts = ACADEMIC_YEARS.map(y => `<option value="${y}" ${obData.academic_year===y?'selected':''}>${y}</option>`).join('');
            const typeOpts = PROGRAM_TYPES.map(t => `<option value="${t}" ${obData.program_type===t?'selected':''}>${t}</option>`).join('');
            body.innerHTML = `<div class="ob-form-grid">
                <div class="ob-input-group"><label>Niên khóa</label><select class="ob-select" id="ob-academic-year" onchange="obData.academic_year=this.value"><option value="">-- Chọn niên khóa --</option>${yearOpts}</select></div>
                <div class="ob-input-group"><label>Hệ đào tạo</label><select class="ob-select" id="ob-program-type" onchange="obData.program_type=this.value"><option value="">-- Chọn hệ đào tạo --</option>${typeOpts}</select></div>
            </div>`;
        } else if (obStep === 1) {
            const btns = Array.from({length:8},(_,i)=>i+1).map(i=>`<button class="ob-sem-btn ${obData.current_semester===i?'selected':''}" onclick="obSelectSem(${i},this)">Học kỳ ${i}</button>`).join('');
            body.innerHTML = `<div class="ob-semester-grid">${btns}</div>`;
        } else if (obStep === 2) {
            let sectionsHtml = '';
            for (const [semName, subjects] of Object.entries(SUBJECTS_BY_SEM)) {
                const rows = subjects.map(sub => {
                    const g = obData.grades[sub.id];
                    const cls = g===undefined?'':(g>5?'pass':'fail');
                    const statusTxt = g===undefined?'':(g>5?'✓ Pass':'✗ Fail');
                    const statusCls = g===undefined?'':(g>5?'pass':'fail');
                    return `<div class="ob-subject-row ${g!==undefined?'has-grade':''}" id="ob-row-${sub.id}">
                        <div class="ob-subject-info"><div class="ob-subject-name">${sub.name}</div><div class="ob-subject-meta">${sub.credits} TC · HK chuẩn ${sub.semName}</div></div>
                        <div class="ob-grade-wrap">
                            <input type="number" class="ob-grade-input ${cls}" id="ob-grade-${sub.id}" min="0" max="10" step="0.1" placeholder="—" value="${g!==undefined?g:''}" oninput="obGradeChange(${sub.id},this)">
                            <span class="ob-grade-status ${statusCls}" id="ob-gstatus-${sub.id}">${statusTxt}</span>
                        </div>
                    </div>`;
                }).join('');
                sectionsHtml += `<div class="ob-semester-section"><div class="ob-semester-section-title">Học kỳ chuẩn ${semName}</div>${rows}</div>`;
            }
            body.innerHTML = `<div class="ob-warning"><span class="ob-warning-icon">⚠️</span><p><strong>Lưu ý:</strong> Chỉ nhập điểm những môn bạn <strong>đã học và có kết quả</strong>.</p></div><div class="ob-subjects-scroll">${sectionsHtml}</div>`;
        } else if (obStep === 3) {
            const years = [3,4,5,6];
            const descs = {3:'Rất nhanh',4:'Tiêu chuẩn',5:'Bình thường',6:'Linh hoạt'};
            const btns = years.map(y=>`<button class="ob-year-btn ${obData.target_years===y?'selected':''}" onclick="obSelectYear(${y},this)">${y} năm<small>${descs[y]}</small></button>`).join('');
            body.innerHTML = `<div class="ob-year-grid">${btns}</div><p style="margin-top:var(--sp-md);font-size:0.8rem;color:var(--muted);text-align:center;">Thông thường chương trình Đại học 4 năm gồm 8 học kỳ.</p>`;
        }

        const btnBack = document.getElementById('ob-btn-back');
        const btnNext = document.getElementById('ob-btn-next');
        const progText = document.getElementById('ob-progress-text');
        btnBack.disabled = obStep === 0;
        progText.textContent = `Bước ${obStep + 1} / 4`;
        if (obStep === 3) { btnNext.textContent = '🎉 Hoàn thành!'; btnNext.className = 'ob-btn-next finish'; }
        else { btnNext.innerHTML = 'Tiếp theo →'; btnNext.className = 'ob-btn-next'; }
    }

    function obSelectSem(i, el) { obData.current_semester=i; document.querySelectorAll('.ob-sem-btn').forEach(b=>b.classList.remove('selected')); el.classList.add('selected'); }
    function obSelectYear(y, el) { obData.target_years=y; document.querySelectorAll('.ob-year-btn').forEach(b=>b.classList.remove('selected')); el.classList.add('selected'); }

    function obGradeChange(id, input) {
        const rawVal = parseFloat(input.value);
        if (!isNaN(rawVal)) { if (rawVal>10) input.value=10; else if (rawVal<0) input.value=0; }
        const val = parseFloat(input.value);
        const status = document.getElementById(`ob-gstatus-${id}`);
        const row = document.getElementById(`ob-row-${id}`);
        input.classList.remove('pass','fail'); status.classList.remove('pass','fail'); row.classList.remove('has-grade');
        if (input.value===''||isNaN(val)) { delete obData.grades[id]; status.textContent=''; }
        else {
            obData.grades[id]=val; row.classList.add('has-grade');
            if (val>5) { input.classList.add('pass'); status.classList.add('pass'); status.textContent='✓ Pass'; }
            else { input.classList.add('fail'); status.classList.add('fail'); status.textContent='✗ Fail'; }
        }
    }

    function obNext() {
        if (obStep===0) { const yr=document.getElementById('ob-academic-year')?.value; const pt=document.getElementById('ob-program-type')?.value; if (!yr||!pt){showToast('Vui lòng chọn đầy đủ niên khóa và hệ đào tạo!','error');return;} obData.academic_year=yr; obData.program_type=pt; }
        if (obStep===1&&!obData.current_semester) { showToast('Vui lòng chọn học kỳ hiện tại!','error');return; }
        if (obStep===3) { if (!obData.target_years){showToast('Vui lòng chọn mục tiêu tốt nghiệp!','error');return;} obFinish(); return; }
        obStep++; renderObDots(); renderObHeader(); renderObBody();
    }

    function obPrev() { if (obStep===0) return; obStep--; renderObDots(); renderObHeader(); renderObBody(); }

    async function obFinish() {
        const btnNext = document.getElementById('ob-btn-next');
        btnNext.disabled=true; btnNext.textContent='⏳ Đang lưu...';
        try {
            await fetch('/preferences/save',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN,'Accept':'application/json'},body:JSON.stringify({academic_year:obData.academic_year,program_type:obData.program_type,current_semester:obData.current_semester,target_years:obData.target_years})});
            const gradesToSave = Object.entries(obData.grades).map(([sid,grade])=>({subject_id:parseInt(sid),grade}));
            if (gradesToSave.length>0) await fetch('/grades/save',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN,'Accept':'application/json'},body:JSON.stringify(gradesToSave)});
            closeOnboarding(); applyPreferencesToUI(obData);
            showToast('Chào mừng! Đã thiết lập chương trình của bạn 🎉','success');
        } catch(err) { showToast('Có lỗi xảy ra, vui lòng thử lại!','error'); btnNext.disabled=false; btnNext.textContent='🎉 Hoàn thành!'; }
    }

    function openOnboarding() { obStep=0; document.getElementById('ob-overlay').classList.remove('hidden'); renderObDots(); renderObHeader(); renderObBody(); }
    function closeOnboarding() { document.getElementById('ob-overlay').classList.add('hidden'); }

    function applyPreferencesToUI(data) {
        if (data.academic_year)    document.getElementById('academic_year').value   = data.academic_year;
        if (data.program_type)     document.getElementById('program_type').value    = data.program_type;
        if (data.current_semester) document.getElementById('target_semester').value = data.current_semester;
        if (data.target_years)     document.getElementById('target_years').value    = data.target_years;
        Object.entries(data.grades).forEach(([sid,grade])=>{ const input=document.getElementById(`grade-${sid}`); if(input){input.value=grade;onGradeChange(parseInt(sid),input,true);} });
        document.getElementById('config-dot')?.remove();
        updateCreditStats(); fetchSuggestions();
    }

    // ═══════════════════════════════════════════════════════════════
    // CONFIG PANEL
    // ═══════════════════════════════════════════════════════════════
    function toggleConfigPanel() {
        const panel=document.getElementById('config-panel'); const overlay=document.getElementById('config-overlay');
        const isOpen=panel.classList.contains('open');
        if(isOpen){panel.classList.remove('open');overlay.classList.remove('open');}
        else{panel.classList.add('open');overlay.classList.add('open');}
    }
    function closeConfigPanel() { document.getElementById('config-panel').classList.remove('open'); document.getElementById('config-overlay').classList.remove('open'); }

    // ═══════════════════════════════════════════════════════════════
    // GRADE DRAWER
    // ═══════════════════════════════════════════════════════════════
    function toggleGradeDrawer() {
        const drawer=document.getElementById('grade-drawer'); const overlay=document.getElementById('grade-drawer-overlay');
        const isOpen=drawer.classList.contains('open');
        if(isOpen){drawer.classList.remove('open');overlay.classList.remove('open');}
        else{drawer.classList.add('open');overlay.classList.add('open'); const s=document.getElementById('grade-search'); if(s){s.value='';filterGradeSearch('');}}
    }
    function closeGradeDrawer() {
        document.getElementById('grade-drawer').classList.remove('open');
        document.getElementById('grade-drawer-overlay').classList.remove('open');
        clearTimeout(fetchTimer); fetchTimer=setTimeout(fetchSuggestions,300);
    }
    function filterGradeSearch(query) {
        const q=query.toLowerCase().trim();
        document.querySelectorAll('.drawer-subject-card').forEach(card=>{ card.classList.toggle('hidden-search',q!==''&&!card.dataset.name?.includes(q)); });
        document.querySelectorAll('.drawer-sem-group').forEach(group=>{ const visible=group.querySelectorAll('.drawer-subject-card:not(.hidden-search)').length>0; group.style.display=visible?'':'none'; });
    }

    function updateDrawerStats() {
        let pass=0,fail=0,empty=0;
        document.querySelectorAll('.grade-input').forEach(input=>{ const val=parseFloat(input.value); if(input.value===''||isNaN(val))empty++; else if(val>5.0)pass++; else fail++; });
        const passEl=document.getElementById('drawer-pass-count'); const failEl=document.getElementById('drawer-fail-count'); const emptyEl=document.getElementById('drawer-empty-count');
        if(passEl)passEl.textContent=pass; if(failEl)failEl.textContent=fail; if(emptyEl)emptyEl.textContent=empty;
        const badge=document.getElementById('grade-count-badge');
        if(badge){ const filled=pass+fail; badge.textContent=filled; badge.classList.toggle('visible',filled>0); }
    }

    // ═══════════════════════════════════════════════════════════════
    // PREFERENCES
    // ═══════════════════════════════════════════════════════════════
    function savePreferences() {
        clearTimeout(prefTimer);
        prefTimer=setTimeout(async()=>{
            try {
                const payload={academic_year:document.getElementById('academic_year').value,program_type:document.getElementById('program_type').value,current_semester:parseInt(document.getElementById('target_semester').value),target_years:parseInt(document.getElementById('target_years').value),current_courses:currentCourses};
                const res=await fetch('/preferences/save',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN,'Accept':'application/json'},body:JSON.stringify(payload)});
                if(!res.ok)throw new Error(`HTTP ${res.status}`);
                showSaveIndicator('saved','Đã lưu cấu hình ✓');
            } catch(err){showSaveIndicator('error','Lưu cấu hình thất bại');}
        },500);
    }

    async function loadPreferences() {
        try { const res=await fetch('/preferences',{headers:{'Accept':'application/json'}}); if(!res.ok)return null; return await res.json(); }
        catch(err){ console.warn('[Preference load error]',err); return null; }
    }

    // ═══════════════════════════════════════════════════════════════
    // SAVE INDICATOR
    // ═══════════════════════════════════════════════════════════════
    function showSaveIndicator(state,msg) {
        const el=document.getElementById('save-indicator'); if(!el)return;
        el.className='save-indicator';
        if(state==='hide'){el.style.display='none';return;}
        const icons={saving:'💾',saved:'✓',error:'⚠️'};
        const texts={saving:'Đang lưu...',saved:'Đã lưu',error:'Lưu thất bại'};
        el.classList.add(state); el.textContent=`${icons[state]} ${msg||texts[state]}`;
        if(state==='saved')setTimeout(()=>showSaveIndicator('hide'),2500);
    }

    // ═══════════════════════════════════════════════════════════════
    // GRADE SAVE / LOAD
    // ═══════════════════════════════════════════════════════════════
    function autoSaveGrade(subjectId,grade) {
        clearTimeout(saveTimer); showSaveIndicator('saving');
        saveTimer=setTimeout(async()=>{
            try {
                const res=await fetch('/grades/save',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN,'Accept':'application/json'},body:JSON.stringify([{subject_id:subjectId,grade}])});
                if(!res.ok)throw new Error(`HTTP ${res.status}`);
                showSaveIndicator('saved'); scheduleChartRefresh();
            } catch(err){showSaveIndicator('error');}
        },800);
    }

    async function saveMultipleGrades(grades) {
        if(!grades||grades.length===0)return;
        showSaveIndicator('saving',`Đang lưu ${grades.length} môn...`);
        try {
            const res=await fetch('/grades/save',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN,'Accept':'application/json'},body:JSON.stringify(grades)});
            if(!res.ok)throw new Error(`HTTP ${res.status}`);
            showSaveIndicator('saved',`Đã lưu ${grades.length} môn ✓`);
        } catch(err){showSaveIndicator('error','Lưu điểm thất bại');}
    }

    async function loadGradesFromDB() {
        try {
            const res=await fetch('/grades',{headers:{'Accept':'application/json'}}); if(!res.ok)return;
            const grades=await res.json();
            grades.forEach(({subject_id,grade})=>{ const input=document.getElementById(`grade-${subject_id}`); if(!input)return; if(grade!==null&&grade!==undefined){input.value=grade;onGradeChange(subject_id,input,true);} });
            updateEarnedCredits(); updateDrawerStats();
        } catch(err){console.warn('[Grade load error]',err);}
    }

    // ═══════════════════════════════════════════════════════════════
    // CREDIT STATS
    // ═══════════════════════════════════════════════════════════════
    function updateCreditStats() {
        const years=parseInt(document.getElementById('target_years').value);
        const totalSem=years*2;
        document.getElementById('stat-total-semesters').textContent=totalSem;
        updateEarnedCredits();

        // Update KPI card
        const curSem = document.getElementById('target_semester')?.value;
        if (curSem) document.getElementById('kpi-semester').textContent = `HK ${curSem}`;
    }

    function updateEarnedCredits() {
        let earned=0;
        document.querySelectorAll('.grade-input').forEach(input=>{ const val=parseFloat(input.value); if(!isNaN(val)&&val>5.0)earned+=parseInt(input.dataset.credits||0); });
        currentCourses.forEach(c=>{if(c.grade!==null&&c.grade>5.0)earned+=(c.credits||0);});
        document.getElementById('stat-earned-credits').textContent=earned;

        const years=parseInt(document.getElementById('target_years').value);
        const totalSem=years*3;
        const currentSem=parseInt(document.getElementById('target_semester').value);
        const remaining=Math.max(0,TOTAL_CREDITS-earned);
        const remSem=Math.max(1,totalSem-(currentSem-1));
        const perSem=remaining===0?0:Math.ceil(remaining/remSem);
        document.getElementById('stat-credits-per-sem').textContent=perSem;

        // Update KPI cards
        document.getElementById('kpi-credits').textContent = earned;
        const progPct = Math.min(100, Math.round((earned / TOTAL_CREDITS) * 100));
        document.getElementById('kpi-progress').textContent = progPct + '%';
        document.getElementById('kpi-progress-sub').textContent = `${earned} / ${TOTAL_CREDITS} TC hoàn thành`;

        // Update GPA KPI
        let allGrades = [];
        document.querySelectorAll('.grade-input').forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val) && input.value !== '') allGrades.push(val);
        });
        if (allGrades.length > 0) {
            const gpa = (allGrades.reduce((s, v) => s + v, 0) / allGrades.length).toFixed(2);
            document.getElementById('kpi-gpa').textContent = gpa;
            // Update chart tab stats
            const gradedEl = document.getElementById('chart-stat-graded');
            if (gradedEl) gradedEl.textContent = allGrades.length + ' môn';
            const gpaEl = document.getElementById('chart-stat-gpa');
            if (gpaEl) gpaEl.textContent = gpa;
        } else {
            document.getElementById('kpi-gpa').textContent = '—';
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // GRADE CHANGE HANDLERS
    // ═══════════════════════════════════════════════════════════════
    function onGradeChange(id,input,skipSave=false) {
        const rawVal=parseFloat(input.value);
        if(!isNaN(rawVal)){if(rawVal>10)input.value=10; else if(rawVal<0)input.value=0;}
        const card=document.getElementById(`lbl-sub-${id}`);
        const status=document.getElementById(`status-${id}`);
        const val=parseFloat(input.value);
        const isInDrawer=input.classList.contains('drawer-grade-input');

        if(card){card.classList.remove('pass','fail'); if(!isNaN(val)&&val>5.0)card.classList.add('pass'); else if(!isNaN(val)&&val<=5.0&&input.value!=='')card.classList.add('fail');}
        input.classList.remove('is-pass','is-fail');

        if(status){
            status.classList.remove('pass','fail','empty');
            if(input.value===''||isNaN(val)){status.textContent=isInDrawer?'—':'Chưa nhập';status.classList.add('empty');}
            else if(val>5.0){input.classList.add('is-pass');status.textContent='✓ Pass';status.classList.add('pass');}
            else{input.classList.add('is-fail');status.textContent='✗ Fail';status.classList.add('fail');}
        }

        if(!syncLock){syncLock=true; const ccInput=document.getElementById(`cc-grade-${id}`); if(ccInput&&ccInput.value!==input.value){ccInput.value=input.value;onCCGradeChange(id,ccInput);} syncLock=false;}
        if(!skipSave){const gradeValue=isNaN(val)?null:val;autoSaveGrade(id,gradeValue);}
        updateEarnedCredits(); updateDrawerStats();
    }

    function onCCGradeChange(id,input) {
        const rawVal=parseFloat(input.value);
        if(!isNaN(rawVal)){if(rawVal>10)input.value=10; else if(rawVal<0)input.value=0;}
        const val=parseFloat(input.value);
        const item=document.getElementById(`cc-item-${id}`);
        const status=document.getElementById(`cc-status-${id}`);
        item.classList.remove('cc-pass','cc-fail'); input.classList.remove('is-pass','is-fail'); status.classList.remove('pass','fail','empty');
        const course=currentCourses.find(c=>c.id==id); if(course)course.grade=isNaN(val)?null:val;
        if(input.value===''||isNaN(val)){status.textContent='—';status.classList.add('empty');}
        else if(val>5.0){item.classList.add('cc-pass');input.classList.add('is-pass');status.textContent='Pass';status.classList.add('pass');}
        else{item.classList.add('cc-fail');input.classList.add('is-fail');status.textContent='Fail';status.classList.add('fail');}
        localStorage.setItem('current_courses', JSON.stringify(currentCourses));
        savePreferences();
        updateCompleteButton(); updateEarnedCredits();
    }

    // ═══════════════════════════════════════════════════════════════
    // CURRENT COURSES
    // ═══════════════════════════════════════════════════════════════
    function updateCompleteButton() {
        const btn=document.getElementById('btn-complete'); if(!btn)return;
        const allFilled=currentCourses.length>0&&currentCourses.every(c=>c.grade!==null&&c.grade!==undefined);
        btn.disabled=!allFilled;
    }

    function addToCurrentCourses(subject) {
        if(currentCourses.find(c=>c.id==subject.id))return;
        currentCourses.push({id:subject.id,name:subject.name,credits:subject.credits,semesterName:subject.semester?.name||'?',grade:null});
        
        let autoAdded = [];
        if (subject.corequisites_info && subject.corequisites_info.length > 0) {
            subject.corequisites_info.forEach(coreq => {
                if(!currentCourses.find(c=>c.id==coreq.id)) {
                    currentCourses.push({id:coreq.id,name:coreq.name,credits:coreq.credits,semesterName:'?',grade:null});
                    autoAdded.push(coreq);
                }
            });
        }

        renderCurrentCourses();
        
        const btn=document.getElementById(`btn-add-${subject.id}`); 
        if(btn){btn.innerHTML='✓ Đã thêm';btn.classList.add('added');}
        lockLeftInput(subject.id);

        if (autoAdded.length > 0) {
            autoAdded.forEach(coreq => {
                const cbtn=document.getElementById(`btn-add-${coreq.id}`); 
                if(cbtn){cbtn.innerHTML='✓ Đã thêm';cbtn.classList.add('added');}
                lockLeftInput(coreq.id);
            });
            showToast(`Đã tự động thêm môn song hành: ${autoAdded.map(a=>a.name).join(', ')}`, 'info');
        }

        const navBadge = document.getElementById('nav-cc-badge');
        if (navBadge) { navBadge.textContent = currentCourses.length; navBadge.classList.toggle('visible', currentCourses.length > 0); }
        
        clearTimeout(fetchTimer); 
        fetchTimer=setTimeout(()=>{
            saveMultipleGrades(currentCourses.map(c=>({subject_id:c.id,grade:c.grade})));
        }, 1000);
    }

    function removeCourse(id) {
        currentCourses=currentCourses.filter(c=>c.id!=id); renderCurrentCourses();
        const btn=document.getElementById(`btn-add-${id}`); if(btn){btn.innerHTML='+ Thêm';btn.classList.remove('added');}
        unlockLeftInput(id);
        clearTimeout(fetchTimer); fetchTimer=setTimeout(fetchSuggestions,400); updateEarnedCredits();
        const navBadge = document.getElementById('nav-cc-badge');
        if (navBadge) { navBadge.textContent = currentCourses.length; navBadge.classList.toggle('visible', currentCourses.length > 0); }
    }

    function lockLeftInput(id) {
        const input=document.getElementById(`grade-${id}`); if(!input)return;
        input.classList.add('is-studying');
        const wrap=input.parentElement;
        if(wrap){wrap.classList.add('is-locked'); if(!wrap.querySelector('.studying-label')){const badge=document.createElement('span');badge.className='studying-label';badge.innerHTML='📖 Đang học';wrap.appendChild(badge);}}
        const status=document.getElementById(`status-${id}`); if(status){status.dataset.prevText=status.textContent;status.textContent='';}
    }

    function unlockLeftInput(id) {
        const input=document.getElementById(`grade-${id}`); if(!input)return;
        input.classList.remove('is-studying');
        const wrap=input.parentElement; if(wrap){wrap.classList.remove('is-locked');const badge=wrap.querySelector('.studying-label');if(badge)badge.remove();}
        onGradeChange(id,input);
    }

    function renderCurrentCourses() {
        localStorage.setItem('current_courses', JSON.stringify(currentCourses));
        savePreferences();
        const container=document.getElementById('current-courses-list');
        const counter=document.getElementById('cc-count'); counter.textContent=currentCourses.length;
        const currentCredits = currentCourses.reduce((sum, c) => sum + (parseInt(c.credits) || 0), 0);
        const ccCredits = document.getElementById('cc-credits');
        if (ccCredits) {
            ccCredits.textContent = currentCredits + ' TC';
            if (currentCredits > 0) {
                ccCredits.style.background = 'var(--ink)';
                ccCredits.style.color = 'white';
                ccCredits.style.borderColor = 'var(--ink)';
            } else {
                ccCredits.style.background = 'var(--surface-soft)';
                ccCredits.style.color = 'var(--muted)';
                ccCredits.style.borderColor = 'var(--hairline)';
            }
        }
        updateCompleteButton();
        if(currentCourses.length===0){container.innerHTML='<div class="current-courses-empty">Chưa có môn nào — vào <strong>Đề Xuất Môn Học</strong> và nhấn <strong>+ Thêm</strong>.</div>';return;}
        container.innerHTML=currentCourses.map(c=>`
            <div class="course-item${c.grade!==null&&c.grade>5?' cc-pass':c.grade!==null?' cc-fail':''}" id="cc-item-${c.id}">
                <div class="course-info">
                    <span class="course-name">${c.name}</span>
                    <span class="course-meta">${c.credits} tín chỉ · Học kỳ chuẩn ${c.semesterName}</span>
                </div>
                <div class="course-right">
                    <input type="number" class="grade-input-clay${c.grade!==null&&c.grade>5?' is-pass':c.grade!==null?' is-fail':''}"
                           id="cc-grade-${c.id}" min="0" max="10" step="0.1" placeholder="Điểm"
                           value="${c.grade!==null?c.grade:''}"
                           oninput="onCCGradeChange(${c.id},this)">
                    <span class="grade-status-clay ${c.grade!==null&&c.grade>5?'pass':c.grade!==null?'fail':'empty'}" id="cc-status-${c.id}">${c.grade!==null&&c.grade>5?'Pass':c.grade!==null?'Fail':'—'}</span>
                    <button class="btn-remove-clay" onclick="removeCourse(${c.id})" title="Xóa">✕</button>
                </div>
            </div>`).join('');
    }

    // ═══════════════════════════════════════════════════════════════
    // SUGGESTIONS
    // ═══════════════════════════════════════════════════════════════
    function getPassedSubjectIds() {
        const passed=new Set();
        document.querySelectorAll('.grade-input').forEach(input=>{const val=parseFloat(input.value);if(!isNaN(val)&&val>5.0)passed.add(input.dataset.subjectId);});
        currentCourses.forEach(c=>{if(c.grade!==null&&c.grade>5.0)passed.add(String(c.id));});
        return [...passed].join(',');
    }

    function getFailedSubjectIds() {
        const failed=new Set();
        document.querySelectorAll('.grade-input').forEach(input=>{const val=parseFloat(input.value);if(!isNaN(val)&&val>0&&val<=5.0)failed.add(parseInt(input.dataset.subjectId));});
        currentCourses.forEach(c=>{if(c.grade!==null&&c.grade>0&&c.grade<=5.0)failed.add(c.id);});
        return failed;
    }

    async function fetchSuggestions() {
        const academicYear=document.getElementById('academic_year').value;
        const programType=document.getElementById('program_type').value;
        const semester=document.getElementById('target_semester').value;
        const passedSubjects=getPassedSubjectIds();
        const loader=document.getElementById('loader');
        const suggestionsContainer=document.getElementById('suggestions-list');
        loader.style.display='flex'; suggestionsContainer.style.opacity='0.3';
        try {
            const url=`/api/suggestions?academic_year=${encodeURIComponent(academicYear)}&program_type=${encodeURIComponent(programType)}&passed_subjects=${passedSubjects}&semester=${semester}&t=${new Date().getTime()}`;
            const response=await fetch(url); if(!response.ok)throw new Error('API error');
            const data=await response.json(); renderSuggestions(data,semester);
        } catch(error) {
            suggestionsContainer.innerHTML=`<div class="empty-state"><p style="color:var(--error);font-weight:600;">⚠️ Đã có lỗi xảy ra khi phân tích dữ liệu.</p></div>`;
        } finally { loader.style.display='none'; suggestionsContainer.style.opacity='1'; }
    }

    function renderSuggestions(subjects,targetSemester) {
        const container=document.getElementById('suggestions-list');
        if(subjects.length===0){
            container.innerHTML=`<div class="empty-state"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg><h3>Không có môn học đề xuất nào!</h3><p>Hãy thử đổi niên khóa, loại chương trình hoặc học kỳ mong muốn phù hợp hơn.</p></div>`;
            return;
        }
        container.innerHTML=subjects.map(subject=>{
            const subSem=parseInt(subject.semester?.name||1);
            const targetSem=parseInt(targetSemester);
            const isAdded=currentCourses.find(c=>c.id==subject.id);
            const failedIds=getFailedSubjectIds();
            const isFailed=failedIds.has(subject.id);
            let priorityLabel='';
            let scoreText = `<span style="opacity:0.85;margin-left:4px;font-size:0.9em;">(${subject.suggestion_score}đ)</span>`;

            if(isFailed) priorityLabel=`<span class="pill pill-red">Học lại 🔄 ${scoreText}</span>`;
            else if(subject.suggestion_score >= 105) priorityLabel=`<span class="pill pill-mint">Ưu tiên Rất Cao 🔥 ${scoreText}</span>`;
            else if(subject.suggestion_score >= 95) priorityLabel=`<span class="pill pill-lavender">Ưu tiên Cao 👍 ${scoreText}</span>`;
            else if(subject.suggestion_score >= 80) priorityLabel=`<span class="pill pill-ochre">Ưu tiên Vừa 👌 ${scoreText}</span>`;
            else priorityLabel=`<span class="pill pill-red" style="background:#fee2e2;color:#b91c1c;border:none;">Ít Ưu tiên ⬇️ ${scoreText}</span>`;
            const isEligible = subject.can_study !== false;
            
            let tagHtml = '';
            let actionHtml = '';
            let jsonSubject = JSON.stringify(subject).replace(/"/g,'&quot;');
            if (isEligible) {
                tagHtml = `
                    <span class="pill pill-cream" style="background:#e8f8f3;color:#1a3a3a;border:none;">${subject.credits} Tín chỉ</span>
                    ${priorityLabel}
                `;
                if (subject.skill_evaluation) {
                    let evalColor = subject.skill_evaluation.includes('+') ? '#10b981' : '#f59e0b';
                    if (subject.skill_evaluation.includes('-15')) evalColor = '#ef4444';
                    let evalIcon = subject.skill_evaluation.includes('+') ? '⭐' : (subject.skill_evaluation.includes('-15') ? '⚠️' : '📊');
                    tagHtml += `<span class="pill" style="background:var(--surface);color:${evalColor};border:1px solid ${evalColor}40;font-size:0.68rem;padding:2px 8px;">${evalIcon} ${subject.skill_evaluation}</span>`;
                }
                actionHtml = `
                    <button id="btn-add-${subject.id}" class="btn-add-clay${isAdded?' added':''}" onclick="addToCurrentCourses(${jsonSubject})">
                        ${isAdded?'✓ Đã thêm vào kế hoạch':'Thêm vào kế hoạch'}
                    </button>
                    <button class="btn-info-clay" title="Thông tin chi tiết" onclick="openPrereqModal(${jsonSubject})"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:20px;height:20px;"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg></button>
                `;
            } else {
                tagHtml = `
                    <span class="pill pill-cream" style="background:transparent;color:#dc2626;border:none;padding:0;font-size:0.75rem;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;margin-right:2px;vertical-align:-3px;"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg> Thiếu môn tiên quyết</span>
                `;
                actionHtml = `
                    <button class="btn-add-clay" style="background:var(--surface-soft);color:var(--muted);border:1px solid var(--hairline);" onclick="openPrereqModal(${jsonSubject})">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;vertical-align:-3px;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg> Xem danh sách môn tiên quyết
                    </button>
                `;
            }

            return `
                <div class="suggestion-card${isFailed ? ' is-locked' : (!isEligible ? ' is-locked' : '')}">
                    <div class="suggestion-card-top">
                        <div class="suggestion-details">
                            <span class="suggestion-title">${subject.name}</span>
                            <div class="suggestion-tags">
                                ${tagHtml}
                            </div>
                        </div>
                        <div class="suggestion-icon ${isFailed||!isEligible ? 'locked' : ''}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px;"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" /></svg></div>
                    </div>
                    <div class="suggestion-desc">Môn học thuộc khối kiến thức ${subject.skill_group?.name||'chuyên ngành'}. Đây là môn học cung cấp các nền tảng thiết yếu.</div>
                    <div class="suggestion-actions">
                        ${actionHtml}
                    </div>
                </div>`;
        }).join('');
    }

    // ═══════════════════════════════════════════════════════════════
    // COMPLETE SEMESTER
    // ═══════════════════════════════════════════════════════════════
    function completeSemester() {
        const unfilled=currentCourses.filter(c=>c.grade===null||c.grade===undefined);
        if(unfilled.length>0){showToast(`Còn ${unfilled.length} môn chưa điền điểm!`,'error');return;}
        if(currentCourses.length===0){showToast('Chưa có môn nào trong danh sách!','error');return;}
        const snapshot=currentCourses.map(c=>({...c}));
        const sel=document.getElementById('target_semester');
        const cur=parseInt(sel.value);
        saveSemesterHistory(cur,snapshot.map(c=>({id:c.id,grade:c.grade})));
        currentCourses=[]; renderCurrentCourses();
        snapshot.forEach(({id,grade})=>{
            const input=document.getElementById(`grade-${id}`); if(!input)return;
            input.classList.remove('is-studying'); const wrap=input.parentElement;
            if(wrap){wrap.classList.remove('is-locked');const badge=wrap.querySelector('.studying-label');if(badge)badge.remove();}
            input.value=grade; onGradeChange(id,input,true);
        });
        saveMultipleGrades(snapshot.map(c=>({subject_id:c.id,grade:c.grade})));
        if(cur<8){sel.value=cur+1;}else{showToast('Đã hoàn thành toàn bộ chương trình! 🎓','success');}
        savePreferences(); fetchSuggestions(); updateEarnedCredits(); scheduleChartRefresh();
        showSemResultModal(cur,snapshot);
        // Update nav badge
        const navBadge = document.getElementById('nav-cc-badge');
        if (navBadge) { navBadge.textContent = 0; navBadge.classList.remove('visible'); }
    }

    // ═══════════════════════════════════════════════════════════════
    // SEMESTER RESULT MODAL
    // ═══════════════════════════════════════════════════════════════
    let _semRecCredits=0;

    function showSemResultModal(semNumber,snapshot) {
        const graded=snapshot.filter(c=>c.grade!==null&&c.grade!==undefined);
        const passSubjects=graded.filter(c=>c.grade>5.0);
        const failSubjects=graded.filter(c=>c.grade<=5.0);
        const gpa=graded.length>0?Math.round(graded.reduce((s,c)=>s+c.grade,0)/graded.length*10)/10:null;
        const creditsThisSem=snapshot.reduce((s,c)=>s+(c.credits||0),0);
        const passedCredits=passSubjects.reduce((s,c)=>s+(c.credits||0),0);
        let totalEarned=0;
        document.querySelectorAll('.grade-input').forEach(input=>{const val=parseFloat(input.value);if(!isNaN(val)&&val>5.0)totalEarned+=parseInt(input.dataset.credits||0);});
        const targetYears=parseInt(document.getElementById('target_years').value)||4;
        const totalSem=targetYears*2;
        const nextSem=Math.min(semNumber+1,8);
        const remSem=Math.max(1,totalSem-semNumber);
        const remCredits=Math.max(0,TOTAL_CREDITS-totalEarned);
        const neededPerSem=remSem>0?Math.ceil(remCredits/remSem):0;
        const progPct=Math.min(100,Math.round((totalEarned/TOTAL_CREDITS)*100));
        const passRate=graded.length>0?passSubjects.length/graded.length:1;
        const avgPerSem=creditsThisSem;
        let recType,recIcon,recTag,recHeadline,recDesc,recDelta;
        const reasons=[];
        if(gpa===null){recType='maintain';recIcon='📊';recTag='Giữ nguyên';recHeadline='Tiếp tục theo kế hoạch';recDelta=0;recDesc='Nhập điểm để nhận gợi ý chính xác hơn.';}
        else if(gpa>=7.5&&passRate>=0.85){
            recType='increase';recIcon='🌟';recTag='Học lực xuất sắc';
            if (neededPerSem > 20) {
                recHeadline='Năng lực tốt, hãy tăng tốc!';
                recDelta = Math.min(6, Math.max(2, neededPerSem - avgPerSem));
                recDesc=`Thành tích kỳ này rất tốt (GPA ${gpa}). Hãy tăng số tín chỉ kỳ sau để sớm bắt kịp tiến độ nhé.`;
                reasons.push({icon:'⏳',text:`Cần <strong>${neededPerSem} TC/kỳ</strong> để không bị trễ hạn.`});
            } else {
                recHeadline='Cơ hội ra trường sớm!';
                recDelta = Math.max(2, Math.min(6, 22 - avgPerSem));
                recDesc=`Thành tích của bạn rất xuất sắc (GPA ${gpa}). Nếu tăng mức tải, bạn hoàn toàn có thể ra trường sớm hơn kế hoạch!`;
                reasons.push({icon:'🎯',text:`Có thể đăng ký vượt rào để rút ngắn thời gian học.`});
            }
            reasons.push({icon:'🌟',text:`GPA học kỳ <strong>${gpa}</strong> — kết quả xuất sắc!`});
        }
        else if(gpa>=6.5&&passRate>=0.8&&neededPerSem<=avgPerSem+2){recType='maintain';recIcon='✅';recTag='Giữ nguyên tiến độ';recHeadline='Tiếp tục theo kế hoạch!';recDelta=0;recDesc=`GPA ${gpa} và tiến độ đúng hướng.`;reasons.push({icon:'✔️',text:`GPA <strong>${gpa}</strong> — đang đi đúng hướng!`});}
        else if((gpa<5.5||passRate<0.6)&&failSubjects.length>0){
            recType='decrease';recIcon='📉';recTag='Gợi ý giảm tín chỉ';
            if(neededPerSem > 20) {
                recHeadline='Cảnh báo học vụ & Tiến độ';
                recDelta = 15 - avgPerSem; // Hướng tới mức 15 TC an toàn
                recDesc=`GPA ${gpa} thấp nhưng tiến độ đang rất chậm. Đề xuất học khoảng 15 TC để cân bằng.`;
            } else {
                recHeadline='Cần giảm tải để tập trung';
                recDelta=-Math.min(6,Math.ceil(failSubjects.length*1.5));
                recDesc=`GPA ${gpa} thấp, ${failSubjects.length} môn fail.`;
            }
            reasons.push({icon:'⚠️',text:`<strong>${failSubjects.length} môn fail</strong> cần học lại kỳ sau.`});
        }
        else if(neededPerSem>avgPerSem+4){recType='increase';recIcon='📈';recTag='Cần tăng tiến độ';recHeadline='Tăng tín chỉ để kịp tiến độ';recDelta=Math.min(5,neededPerSem-avgPerSem);recDesc=`Cần <strong>${neededPerSem} TC/kỳ</strong> nhưng kỳ này chỉ ${avgPerSem} TC.`;}
        else{recType='maintain';recIcon='✨';recTag='Giữ nguyên tiến độ';recHeadline='Tiếp tục theo kế hoạch!';recDelta=0;recDesc='Bạn đang đi đúng hướng.';if(gpa)reasons.push({icon:'⭐',text:`GPA <strong>${gpa}</strong> - kết quả ổn.`});}
        
        // Ensure suggestion is bounded safely, but allow 15 if forced
        let suggestedCredits=Math.max(10,Math.min(25,avgPerSem+recDelta));
        if ((gpa<5.5||passRate<0.6) && neededPerSem > 20) suggestedCredits = 15;
        
        _semRecCredits=suggestedCredits;
        const gpaClass=gpa===null?'':gpa>=8.0?'gpa-ex':gpa>=7.0?'gpa-good':gpa>=5.5?'gpa-ok':'gpa-bad';
        document.getElementById('srm-sem-label').textContent=`Kết quả Học Kỳ ${semNumber}`;
        document.getElementById('srm-title').textContent=semNumber<8?`Hoàn tất Học Kỳ ${semNumber} 🎉`:`Tốt nghiệp chương trình! 🎓`;
        document.getElementById('srm-subtitle').textContent=`Phân tích kết quả và gợi ý tín chỉ cho học kỳ ${nextSem}`;
        const gpaEl=document.getElementById('srm-gpa'); gpaEl.textContent=gpa!==null?gpa:'—'; gpaEl.className=`srm-kpi-val ${gpaClass}`;
        document.getElementById('srm-pass-count').textContent=passSubjects.length;
        document.getElementById('srm-fail-count').textContent=failSubjects.length;
        document.getElementById('srm-credits-done').textContent=totalEarned;
        document.getElementById('srm-prog-pct').textContent=`${progPct}%`;
        const fill=document.getElementById('srm-prog-fill'); fill.style.width='0%';
        setTimeout(()=>{fill.style.width=`${progPct}%`;},100);
        fill.style.background=progPct>=75?'var(--success)':progPct>=40?'var(--ink)':'var(--brand-ochre)';
        document.getElementById('srm-prog-left').textContent=`Còn lại: ${remCredits} TC`;
        document.getElementById('srm-prog-pace').textContent=`Cần ${neededPerSem} TC/kỳ`;
        const recEl=document.getElementById('srm-recommend'); recEl.className=`srm-recommend ${recType}`;
        document.getElementById('srm-rec-icon').textContent=recIcon;
        document.getElementById('srm-rec-tag').textContent=recTag;
        document.getElementById('srm-rec-headline').textContent=recHeadline;
        document.getElementById('srm-rec-desc').innerHTML=recDesc;
        const changeEl=document.getElementById('srm-credit-change');
        if(recDelta>0){changeEl.className='srm-credit-change up';changeEl.innerHTML=`↑ ${suggestedCredits} <small style="font-size:.72rem;font-weight:500;color:var(--muted);">TC/kỳ (tăng +${recDelta})</small>`;}
        else if(recDelta<0){changeEl.className='srm-credit-change down';changeEl.innerHTML=`↓ ${suggestedCredits} <small style="font-size:.72rem;font-weight:500;color:var(--muted);">TC/kỳ (giảm ${recDelta})</small>`;}
        else{changeEl.className='srm-credit-change same';changeEl.innerHTML=`= ${suggestedCredits} <small style="font-size:.72rem;font-weight:500;color:var(--muted);">TC/kỳ (giữ nguyên)</small>`;}
        const reasonsEl=document.getElementById('srm-reasons');
        reasonsEl.innerHTML=reasons.map(r=>`<div class="srm-reason-item"><span class="srm-reason-icon">${r.icon}</span><span>${r.text}</span></div>`).join('');
        const subjEl=document.getElementById('srm-subj-section');
        const subjectData=snapshot.map(c=>{const input=document.getElementById(`grade-${c.id}`);const credits=parseInt(input?.dataset.credits||c.credits||0);return{...c,credits};});
        const passHtml=subjectData.filter(c=>c.grade>5.0).map(c=>`<div class="srm-subj-row pass"><span class="srm-subj-name">${c.name}</span><span class="srm-subj-credits">${c.credits} TC</span><span class="srm-subj-grade pass">${c.grade}</span></div>`).join('');
        const failHtml=subjectData.filter(c=>c.grade<=5.0).map(c=>`<div class="srm-subj-row fail"><span class="srm-subj-name">${c.name}</span><span class="srm-subj-credits">${c.credits} TC</span><span class="srm-subj-grade fail">${c.grade}</span></div>`).join('');
        subjEl.innerHTML=`${passHtml?`<div class="srm-subj-title">✓ Môn đạt (${passSubjects.length})</div><div class="srm-subj-list">${passHtml}</div>`:''}${failHtml?`<div class="srm-subj-title" style="color:var(--error);">✗ Môn chưa đạt (${failSubjects.length})</div><div class="srm-subj-list">${failHtml}</div>`:''}`;
        const applyBtn=document.getElementById('srm-btn-apply');
        if(recDelta!==0){applyBtn.style.display='';applyBtn.innerHTML=`✨ Áp dụng gợi ý (${suggestedCredits} TC)`;}
        else{applyBtn.style.display='none';}
        document.getElementById('sem-result-overlay').classList.add('open');
    }

    function closeSemResultModal() { document.getElementById('sem-result-overlay').classList.remove('open'); }

    function applyCreditRecommendation() {
        localStorage.setItem('recommended_credits_per_sem',_semRecCredits);
        showToast(`Đã ghi nhớ gợi ý: ${_semRecCredits} TC/kỳ 📌`,'success');
        closeSemResultModal();
        document.getElementById('stat-credits-per-sem').textContent=_semRecCredits;
    }

    document.getElementById('sem-result-overlay').addEventListener('click',function(e){if(e.target===this)closeSemResultModal();});

    async function saveSemesterHistory(semesterNumber,snapshot) {
        try {
            const courses=snapshot.map(({id,grade})=>({subject_id:id,grade}));
            const payload={semester_number:semesterNumber,academic_year:document.getElementById('academic_year')?.value||null,program_type:document.getElementById('program_type')?.value||null,courses};
            const res=await fetch('/semester-history/complete',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN,'Accept':'application/json'},body:JSON.stringify(payload)});
            if(!res.ok)throw new Error(`HTTP ${res.status}`);
            loadSemesterHistory();
        } catch(err){console.warn('[Lưu lịch sử thất bại]',err);}
    }

    // ═══════════════════════════════════════════════════════════════
    // TOAST
    // ═══════════════════════════════════════════════════════════════
    function showToast(msg,type='success') {
        const existing=document.getElementById('app-toast'); if(existing)existing.remove();
        const t=document.createElement('div'); t.id='app-toast'; t.className=`toast ${type}`; t.textContent=msg;
        document.body.appendChild(t); setTimeout(()=>t.remove(),3500);
    }

    // ═══════════════════════════════════════════════════════════════
    // EVENT LISTENERS
    // ═══════════════════════════════════════════════════════════════
    document.getElementById('academic_year').addEventListener('change',()=>{clearTimeout(saveTimer);showSaveIndicator('hide');savePreferences();fetchSuggestions();});
    document.getElementById('program_type').addEventListener('change',()=>{clearTimeout(saveTimer);showSaveIndicator('hide');savePreferences();fetchSuggestions();});
    document.getElementById('target_semester').addEventListener('change',()=>{clearTimeout(saveTimer);showSaveIndicator('hide');savePreferences();updateEarnedCredits();fetchSuggestions();});
    document.getElementById('target_years').addEventListener('change',()=>{clearTimeout(saveTimer);showSaveIndicator('hide');savePreferences();updateCreditStats();});

    // ═══════════════════════════════════════════════════════════════
    // INIT
    // ═══════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded',async()=>{
        updateCreditStats();
        const prefs=await loadPreferences();
        const hasConfig=prefs&&prefs.academic_year;
        if(!hasConfig){openOnboarding();}
        else {
            if(prefs.academic_year)    document.getElementById('academic_year').value   =prefs.academic_year;
            if(prefs.program_type)     document.getElementById('program_type').value    =prefs.program_type;
            if(prefs.current_semester) document.getElementById('target_semester').value =prefs.current_semester;
            if(prefs.target_years)     document.getElementById('target_years').value    =prefs.target_years;
            if(prefs.current_courses && prefs.current_courses.length > 0) {
                currentCourses = prefs.current_courses;
                localStorage.setItem('current_courses', JSON.stringify(currentCourses));
            }
            document.getElementById('config-dot')?.remove();
            updateCreditStats();
            await loadGradesFromDB();
            renderCurrentCourses();
            fetchSuggestions();
            fetchChartData();
        }
    });
</script>

<script>
// ═══════════════════════════════════════════════════════════════
// GRADE CHART (Chart.js) — Clay colors
// ═══════════════════════════════════════════════════════════════
let gradeChartInstance = null;
let gradeChartDetailInstance = null;
let chartRawData = null;
let chartTimer = null;

async function fetchChartData() {
    try {
        const res=await fetch('/grades/chart-data',{headers:{'Accept':'application/json'}});
        if(!res.ok)return;
        chartRawData=await res.json();
        buildChartSemFilter(chartRawData.semesters);
        renderGradeChart(chartRawData,'all');
        renderGradeChartDetail(chartRawData,'all');
    } catch(err){console.warn('[Chart error]',err);}
}

function buildChartSemFilter(semesters) {
    const uniqueSems=[...new Set(semesters)].sort((a,b)=>parseInt(a)-parseInt(b));
    // Dashboard filter (shared buttons)
    ['chart-sem-filter','chart-sem-filter-dash'].forEach(filterId => {
        const container=document.getElementById(filterId);
        if(!container)return;
        container.innerHTML='<button class="chart-sem-btn active" data-sem="all" onclick="filterChartSem(\'all\',this)">Tất cả HK</button>';
        uniqueSems.forEach(sem=>{
            const btn=document.createElement('button');
            btn.className='chart-sem-btn'; btn.dataset.sem=sem;
            btn.textContent=`HK ${sem}`;
            btn.onclick=function(){filterChartSem(sem,this);};
            container.appendChild(btn);
        });
    });
}

function filterChartSem(sem,btn) {
    // Update all filter containers
    document.querySelectorAll('.chart-sem-btn').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll(`.chart-sem-btn[data-sem="${sem}"]`).forEach(b=>b.classList.add('active'));
    if(chartRawData){renderGradeChart(chartRawData,sem); renderGradeChartDetail(chartRawData,sem);}
}

function renderGradeChart(data,semFilter='all') {
    const{labels,my_grades,avg_grades,semesters,academic_year,peer_count}=data;
    let idxs=labels.map((_,i)=>i);
    if(semFilter!=='all')idxs=idxs.filter(i=>String(semesters[i])===String(semFilter));
    const filteredLabels=idxs.map(i=>labels[i]);
    const filteredMy=idxs.map(i=>my_grades[i]);
    const filteredAvg=idxs.map(i=>avg_grades[i]);

    const emptyEl=document.getElementById('chart-empty-dash');
    const canvas=document.getElementById('gradeChart');
    const legendEl=document.getElementById('chart-legend');
    const peerEl=document.getElementById('chart-peer-label');

    if(!filteredLabels.length){if(emptyEl)emptyEl.style.display='flex';if(canvas)canvas.style.display='none';if(legendEl)legendEl.style.display='none';return;}
    if(emptyEl)emptyEl.style.display='none';
    if(canvas)canvas.style.display='block';
    if(legendEl)legendEl.style.display='flex';

    if(peerEl){
        if(peer_count>1)peerEl.innerHTML=`👥 So sánh với <strong>${peer_count}</strong> SV cùng khóa ${academic_year||''}`;
        else peerEl.innerHTML='<span style="color:var(--muted-soft);">Chưa có dữ liệu khóa khác</span>';
    }

    // Also update detail peer label
    const peerElDetail=document.getElementById('chart-peer-label-detail');
    if(peerElDetail){
        if(peer_count>1)peerElDetail.innerHTML=`👥 So sánh với <strong>${peer_count}</strong> SV cùng khóa ${academic_year||''}`;
        else peerElDetail.innerHTML='<span style="color:var(--muted-soft);">Chưa có dữ liệu khóa khác</span>';
    }

    const barColors=filteredMy.map(v=>v===null?'rgba(10,10,10,0.08)':v>5.0?'rgba(10,10,10,0.85)':'rgba(239,68,68,0.8)');
    const borderColors=filteredMy.map(v=>v===null?'rgba(10,10,10,0.15)':v>5.0?'#0a0a0a':'#ef4444');

    if(gradeChartInstance){gradeChartInstance.destroy();gradeChartInstance=null;}
    if(!canvas)return;
    const ctx=canvas.getContext('2d');

    gradeChartInstance=new Chart(ctx,{
        type:'bar',
        data:{
            labels:filteredLabels.map(l=>l.length>18?l.substring(0,16)+'…':l),
            datasets:[
                {label:'Điểm của bạn',data:filteredMy,backgroundColor:barColors,borderColor:borderColors,borderWidth:1.5,borderRadius:6,borderSkipped:false,order:2},
                {label:'Điểm TB cùng khóa',data:filteredAvg,type:'line',borderColor:'rgba(232,185,74,0.9)',backgroundColor:'rgba(232,185,74,0.1)',borderWidth:2.5,pointBackgroundColor:'rgba(232,185,74,1)',pointBorderColor:'#fff',pointBorderWidth:2,pointRadius:5,pointHoverRadius:7,tension:0.3,fill:false,order:1,spanGaps:true}
            ]
        },
        options:{
            responsive:true,maintainAspectRatio:false,animation:{duration:600,easing:'easeInOutQuart'},
            plugins:{
                legend:{display:false},
                tooltip:{backgroundColor:'rgba(10,10,10,0.92)',titleColor:'#fff',bodyColor:'rgba(255,255,255,0.8)',borderColor:'rgba(10,10,10,0.15)',borderWidth:1,padding:12,callbacks:{
                    title:items=>filteredLabels[items[0].dataIndex],
                    label:item=>{if(item.dataset.label==='Điểm của bạn'){const v=item.raw;return v===null?'  Chưa nhập':`  Của bạn: ${v} ${v>5?'✓ Pass':'✗ Fail'}`;}return item.raw!==null?`  TB khóa: ${item.raw}`:'  Chưa có dữ liệu TB';},
                    afterBody:items=>{const idx=items[0].dataIndex;const sem=semFilter==='all'?semesters[idxs[idx]]:semFilter;return[`  HK chuẩn: ${sem}`];}
                }}
            },
            scales:{
                x:{ticks:{color:'rgba(10,10,10,0.45)',font:{size:10},maxRotation:40},grid:{color:'rgba(10,10,10,0.04)'},border:{color:'rgba(10,10,10,0.1)'}},
                y:{min:0,max:10,ticks:{color:'rgba(10,10,10,0.45)',font:{size:11},stepSize:1,callback:v=>v===5?'5 ⚡':v},grid:{color:ctx=>ctx.tick.value===5?'rgba(239,68,68,0.4)':'rgba(10,10,10,0.04)',lineWidth:ctx=>ctx.tick.value===5?2:1},border:{color:'rgba(10,10,10,0.08)'}}
            }
        }
    });
}

function renderGradeChartDetail(data,semFilter='all') {
    const{labels,my_grades,avg_grades,semesters}=data;
    let idxs=labels.map((_,i)=>i);
    if(semFilter!=='all')idxs=idxs.filter(i=>String(semesters[i])===String(semFilter));
    const filteredLabels=idxs.map(i=>labels[i]);
    const filteredMy=idxs.map(i=>my_grades[i]);
    const filteredAvg=idxs.map(i=>avg_grades[i]);

    const emptyEl=document.getElementById('chart-empty');
    const canvas=document.getElementById('gradeChartDetail');
    const legendEl=document.getElementById('chart-legend-detail');

    if(!filteredLabels.length){if(emptyEl)emptyEl.style.display='flex';if(canvas)canvas.style.display='none';if(legendEl)legendEl.style.display='none';return;}
    if(emptyEl)emptyEl.style.display='none';
    if(canvas)canvas.style.display='block';
    if(legendEl)legendEl.style.display='flex';

    const barColors=filteredMy.map(v=>v===null?'rgba(10,10,10,0.08)':v>5.0?'rgba(10,10,10,0.85)':'rgba(239,68,68,0.8)');
    const borderColors=filteredMy.map(v=>v===null?'rgba(10,10,10,0.15)':v>5.0?'#0a0a0a':'#ef4444');

    if(gradeChartDetailInstance){gradeChartDetailInstance.destroy();gradeChartDetailInstance=null;}
    if(!canvas)return;
    const ctx=canvas.getContext('2d');

    gradeChartDetailInstance=new Chart(ctx,{
        type:'bar',
        data:{
            labels:filteredLabels.map(l=>l.length>18?l.substring(0,16)+'…':l),
            datasets:[
                {label:'Điểm của bạn',data:filteredMy,backgroundColor:barColors,borderColor:borderColors,borderWidth:1.5,borderRadius:6,borderSkipped:false,order:2},
                {label:'Điểm TB cùng khóa',data:filteredAvg,type:'line',borderColor:'rgba(232,185,74,0.9)',backgroundColor:'rgba(232,185,74,0.1)',borderWidth:2.5,pointBackgroundColor:'rgba(232,185,74,1)',pointBorderColor:'#fff',pointBorderWidth:2,pointRadius:5,pointHoverRadius:7,tension:0.3,fill:false,order:1,spanGaps:true}
            ]
        },
        options:{
            responsive:true,maintainAspectRatio:false,animation:{duration:600,easing:'easeInOutQuart'},
            plugins:{legend:{display:false},tooltip:{backgroundColor:'rgba(10,10,10,0.92)',padding:12,callbacks:{title:items=>filteredLabels[items[0].dataIndex],label:item=>{if(item.dataset.label==='Điểm của bạn'){const v=item.raw;return v===null?'  Chưa nhập':`  Của bạn: ${v} ${v>5?'✓ Pass':'✗ Fail'}`;}return item.raw!==null?`  TB khóa: ${item.raw}`:'  Chưa có dữ liệu TB';}}}},
            scales:{
                x:{ticks:{color:'rgba(10,10,10,0.45)',font:{size:10},maxRotation:40},grid:{color:'rgba(10,10,10,0.04)'},border:{color:'rgba(10,10,10,0.1)'}},
                y:{min:0,max:10,ticks:{color:'rgba(10,10,10,0.45)',font:{size:11},stepSize:1,callback:v=>v===5?'5 ⚡':v},grid:{color:ctx=>ctx.tick.value===5?'rgba(239,68,68,0.4)':'rgba(10,10,10,0.04)',lineWidth:ctx=>ctx.tick.value===5?2:1},border:{color:'rgba(10,10,10,0.08)'}}
            }
        }
    });
}

let chartFetchTimer=null;
function scheduleChartRefresh(){clearTimeout(chartFetchTimer);chartFetchTimer=setTimeout(fetchChartData,2000);}
</script>

<script>
// ═══════════════════════════════════════════════════════════════
// SUBJECT GROUP ANALYSIS
// ═══════════════════════════════════════════════════════════════
const GROUP_COLORS=['#1a3a3a','#ff4d8b','#b8a4ed','#ffb084','#e8b94a','#a4d4c5','#ff6b5a','#0a0a0a','#3a3a3a','#6a6a6a'];

let currentAnalysisType = 'skill';

function setAnalysisType(type) {
    currentAnalysisType = type;
    const skillBtn = document.getElementById('toggle-analysis-skill');
    const programBtn = document.getElementById('toggle-analysis-program');
    if (!skillBtn || !programBtn) return;
    
    if (type === 'skill') {
        skillBtn.classList.add('active');
        skillBtn.style.background = 'var(--surface)';
        skillBtn.style.color = 'var(--ink)';
        skillBtn.style.boxShadow = 'var(--shadow-sm)';
        
        programBtn.classList.remove('active');
        programBtn.style.background = 'transparent';
        programBtn.style.color = 'var(--muted)';
        programBtn.style.boxShadow = 'none';
    } else {
        programBtn.classList.add('active');
        programBtn.style.background = 'var(--surface)';
        programBtn.style.color = 'var(--ink)';
        programBtn.style.boxShadow = 'var(--shadow-sm)';
        
        skillBtn.classList.remove('active');
        skillBtn.style.background = 'transparent';
        skillBtn.style.color = 'var(--muted)';
        skillBtn.style.boxShadow = 'none';
    }
    renderGroupAnalysis();
}

function gradeLevel(avg){if(avg===null)return'na';if(avg>=8.0)return'excellent';if(avg>=6.5)return'good';if(avg>=5.0)return'warning';return'danger';}
function gradeLevelLabel(avg){
    if(avg===null)return{cls:'group-na-badge',text:'— Chưa có dữ liệu'};
    if(avg>=8.0)return{cls:'group-ok-badge',text:'🌟 Xuất sắc'};
    if(avg>=6.5)return{cls:'group-ok-badge',text:'✓ Tốt'};
    if(avg>=5.0)return{cls:'group-weak-badge',text:'⚠ Cần cải thiện'};
    return{cls:'group-weak-badge',text:'⛔ Điểm yếu'};
}

function buildGroupAnalysis(){
    const allSubjects=[];
    for(const[semName,subs]of Object.entries(SUBJECTS_BY_SEM)){subs.forEach(sub=>allSubjects.push(sub));}
    const grades={};
    document.querySelectorAll('.grade-input').forEach(input=>{const sid=parseInt(input.dataset.subjectId);const val=parseFloat(input.value);if(!isNaN(val)&&input.value!=='')grades[sid]=val;});
    const groups={};
    allSubjects.forEach(sub=>{
        const gName = currentAnalysisType === 'skill' ? (sub.skillGroupName || 'Khác') : (sub.programGroupName || 'Khác');
        if(!groups[gName])groups[gName]={subjects:[],gradedSubjects:[]};
        groups[gName].subjects.push(sub);
        if(grades[sub.id]!==undefined)groups[gName].gradedSubjects.push({...sub,grade:grades[sub.id]});
    });
    const groupStats=Object.entries(groups).map(([name,data],idx)=>{const graded=data.gradedSubjects;let avg=null;if(graded.length>0){const sum=graded.reduce((s,s2)=>s+s2.grade,0);avg=Math.round((sum/graded.length)*10)/10;}return{name,total:data.subjects.length,graded:graded.length,avg,color:GROUP_COLORS[idx%GROUP_COLORS.length],subjects:data.subjects,gradedSubjects:graded};}).sort((a,b)=>{if(a.avg===null&&b.avg===null)return 0;if(a.avg===null)return 1;if(b.avg===null)return-1;return a.avg-b.avg;});
    return groupStats;
}

function renderGroupAnalysis(){
    const container=document.getElementById('group-analysis-content'); if(!container)return;
    const groupStats=buildGroupAnalysis();
    const hasAny=groupStats.some(g=>g.avg!==null);
    if(!hasAny){container.innerHTML=`<div class="group-analysis-empty"><div class="group-analysis-empty-icon">📊</div><p>Nhập điểm các môn học để xem phân tích điểm theo nhóm</p></div>`;return;}

    const weakGroups=groupStats.filter(g=>g.avg!==null&&g.avg<6.5);
    const dangerGroups=groupStats.filter(g=>g.avg!==null&&g.avg<5.0);
    const strongGroups=groupStats.filter(g=>g.avg!==null&&g.avg>=8.0);

    const tableRows=groupStats.map(g=>{
        const pct=g.avg!==null?Math.round((g.avg/10)*100):0;
        const lvl=gradeLevel(g.avg);
        const badge=gradeLevelLabel(g.avg);
        const barColor=lvl==='excellent'?'#22c55e':lvl==='good'?'#1a3a3a':lvl==='warning'?'#e8b94a':lvl==='danger'?'#ef4444':'rgba(10,10,10,0.1)';
        return `<tr>
            <td><div class="group-name-cell"><span class="group-dot" style="background:${g.color};"></span><span class="group-name-text">${g.name}</span></div></td>
            <td style="color:var(--muted);font-size:.78rem;">${g.graded}/${g.total}</td>
            <td class="group-bar-cell"><div class="group-bar-track"><div class="group-bar-fill" style="width:${pct}%;background:${barColor};"></div></div></td>
            <td class="group-avg-cell"><span class="group-avg-val ${lvl}">${g.avg!==null?g.avg:'—'}</span></td>
            <td style="text-align:right;"><span class="${badge.cls}">${badge.text}</span></td>
        </tr>`;
    }).join('');

    const radarLabels=groupStats.filter(g=>g.avg!==null).map(g=>g.name);
    const radarData=groupStats.filter(g=>g.avg!==null).map(g=>g.avg);
    const radarColors=groupStats.filter(g=>g.avg!==null).map(g=>g.color);

    let alertsHtml='';
    const labelTitle = currentAnalysisType === 'skill' ? 'nhóm kỹ năng' : 'khối kiến thức';
    if(dangerGroups.length>0){const names=dangerGroups.map(g=>`<strong>${g.name}</strong>`).join(', ');alertsHtml+=`<div class="group-alert danger"><span class="group-alert-icon">⛔</span><div>Bạn đang <strong>rất yếu</strong> ở ${labelTitle}: ${names} (điểm TB < 5.0).</div></div>`;}
    else if(weakGroups.length>0){const names=weakGroups.map(g=>`<strong>${g.name}</strong> (${g.avg})`).join(', ');alertsHtml+=`<div class="group-alert warning"><span class="group-alert-icon">⚠️</span><div>Cần cải thiện ${labelTitle}: ${names}.</div></div>`;}
    if(strongGroups.length>0){const names=strongGroups.map(g=>`<strong>${g.name}</strong>`).join(', ');alertsHtml+=`<div class="group-alert success"><span class="group-alert-icon">🌟</span><div>Bạn đang làm rất tốt ở ${labelTitle}: ${names}.</div></div>`;}

    const colHeader = currentAnalysisType === 'skill' ? 'Nhóm kỹ năng' : 'Khối kiến thức';
    container.innerHTML=`<div class="group-analysis-grid">
        <div><div class="radar-wrapper"><canvas id="groupRadarChart"></canvas></div></div>
        <div>
            <table class="group-table">
                <thead><tr><th>${colHeader}</th><th>Môn có điểm</th><th>Tỷ lệ</th><th style="text-align:right;">Điểm TB</th><th style="text-align:right;">Đánh giá</th></tr></thead>
                <tbody>${tableRows}</tbody>
            </table>
            <div class="group-summary-alerts">${alertsHtml}</div>
        </div>
    </div>`;
    renderGroupRadar(radarLabels,radarData,radarColors);
}

let groupRadarInstance=null;
function renderGroupRadar(labels,data,colors){
    const canvas=document.getElementById('groupRadarChart'); if(!canvas||labels.length===0)return;
    if(groupRadarInstance){groupRadarInstance.destroy();groupRadarInstance=null;}
    const ctx=canvas.getContext('2d');
    const gradient=ctx.createLinearGradient(0,0,0,300);
    gradient.addColorStop(0,'rgba(10,10,10,0.3)'); gradient.addColorStop(1,'rgba(10,10,10,0.05)');
    const datasetLabel = currentAnalysisType === 'skill' ? 'Điểm TB nhóm kỹ năng' : 'Điểm TB khối kiến thức';
    groupRadarInstance=new Chart(ctx,{
        type:'radar',
        data:{labels:labels.map(l=>l.length>14?l.substring(0,12)+'…':l),datasets:[{label:datasetLabel,data,backgroundColor:gradient,borderColor:'rgba(10,10,10,0.7)',borderWidth:2,pointBackgroundColor:colors,pointBorderColor:'#fff',pointBorderWidth:2,pointRadius:5,pointHoverRadius:7}]},
        options:{responsive:true,maintainAspectRatio:true,animation:{duration:700,easing:'easeInOutQuart'},plugins:{legend:{display:false},tooltip:{backgroundColor:'rgba(10,10,10,0.9)',padding:10,callbacks:{label:item=>`  Điểm TB: ${item.raw}`}}},scales:{r:{min:0,max:10,ticks:{stepSize:2,color:'rgba(10,10,10,0.35)',font:{size:9},backdropColor:'transparent',callback:v=>v===5?'5⚡':v},grid:{color:ctx=>ctx.tick.value===5?'rgba(239,68,68,0.3)':'rgba(10,10,10,0.07)',lineWidth:ctx=>ctx.tick.value===5?1.5:1},pointLabels:{color:'rgba(10,10,10,0.65)',font:{size:10,weight:'600'}},angleLines:{color:'rgba(10,10,10,0.07)'}}}}
    });
}

const _origOnGradeChange=onGradeChange;
window.onGradeChange=function(id,input,skipSave=false){_origOnGradeChange(id,input,skipSave);clearTimeout(window._groupAnalysisTimer);window._groupAnalysisTimer=setTimeout(renderGroupAnalysis,600);};
const _origLoadGrades=loadGradesFromDB;
window.loadGradesFromDB=async function(){await _origLoadGrades();setTimeout(renderGroupAnalysis,300);};
</script>

<script>
// ═══════════════════════════════════════════════════════════════
// HISTORY DRAWER
// ═══════════════════════════════════════════════════════════════
function toggleHistoryDrawer(){const drawer=document.getElementById('history-drawer');const overlay=document.getElementById('history-drawer-overlay');const isOpen=drawer.classList.contains('open');if(isOpen){drawer.classList.remove('open');overlay.classList.remove('open');}else{drawer.classList.add('open');overlay.classList.add('open');loadSemesterHistory();}}
function closeHistoryDrawer(){document.getElementById('history-drawer').classList.remove('open');document.getElementById('history-drawer-overlay').classList.remove('open');}

async function loadSemesterHistory(){
    try{const res=await fetch('/semester-history',{headers:{'Accept':'application/json'}});if(!res.ok)return;const data=await res.json();renderHistoryDrawer(data);updateHistoryBadge(data.length);}
    catch(err){console.warn('[History load error]',err);}
}

function updateHistoryBadge(count){const badge=document.getElementById('history-count-badge');if(!badge)return;if(count>0){badge.textContent=count;badge.classList.add('visible');}else{badge.classList.remove('visible');}}

function renderHistoryDrawer(histories){
    const emptyEl=document.getElementById('history-empty');const listEl=document.getElementById('history-list');if(!listEl)return;
    if(!histories||histories.length===0){emptyEl.style.display='flex';listEl.innerHTML='';return;}
    emptyEl.style.display='none';
    const sorted=[...histories].sort((a,b)=>b.semester_number-a.semester_number);
    listEl.innerHTML=sorted.map((h,idx)=>{
        const gpaColor=h.gpa>=8?'#16a34a':h.gpa>=6.5?'#1a3a3a':h.gpa>=5?'#d97706':'#dc2626';
        const passRate=h.total_credits>0?Math.round((h.passed_credits/h.total_credits)*100):0;
        const itemsHtml=(h.items||[]).map(item=>{const gradeClass=item.grade===null?'empty':item.status==='pass'?'pass':'fail';const gradeText=item.grade!==null?item.grade:'—';return`<div class="history-subject-row ${item.status||''}"><span class="history-subject-name">${item.subject_name||'?'}</span><span class="history-subject-credits">${item.credits??'?'} TC</span><span class="history-subject-grade ${gradeClass}">${gradeText}</span></div>`;}).join('');
        return `<div class="history-sem-block" id="history-block-${h.id}">
            <div class="history-sem-header" onclick="toggleSemBlock(${h.id})">
                <div>
                    <div class="history-sem-title">🎓 Học kỳ ${h.semester_number} ${h.academic_year?`<span style="font-size:.72rem;font-weight:500;color:var(--muted);">(${h.academic_year})</span>`:''}</div>
                    <div class="history-sem-meta" style="margin-top:3px;">
                        ${h.gpa!==null?`<span class="history-sem-pill gpa">GPA: ${h.gpa}</span>`:''}
                        <span class="history-sem-pill credits">✓ ${h.passed_credits}/${h.total_credits} TC</span>
                        ${h.completed_at?`<span class="history-sem-pill date">📅 ${h.completed_at}</span>`:''}
                    </div>
                </div>
                <span class="history-sem-chevron">▼</span>
            </div>
            <div class="history-sem-body">
                <div class="history-subject-list">
                    <div class="history-subject-row" style="background:var(--surface-soft);">
                        <span style="font-size:.68rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Môn học</span>
                        <span style="font-size:.68rem;font-weight:700;color:var(--muted);min-width:40px;text-align:right;">TC</span>
                        <span style="font-size:.68rem;font-weight:700;color:var(--muted);min-width:44px;text-align:center;">Điểm</span>
                    </div>
                    ${itemsHtml||'<div style="padding:10px var(--sp-lg);color:var(--muted);font-size:.84rem;">Không có dữ liệu môn học</div>'}
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px var(--sp-lg);background:var(--surface-soft);border-top:1px solid var(--hairline);">
                        <span style="font-size:.72rem;color:var(--muted);">Tỷ lệ pass</span>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:72px;height:5px;background:var(--surface-strong);border-radius:var(--r-pill);overflow:hidden;"><div style="height:100%;width:${passRate}%;background:var(--success);border-radius:var(--r-pill);"></div></div>
                            <span style="font-size:.78rem;font-weight:700;color:${gpaColor};">${passRate}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');
}

function toggleSemBlock(id){const block=document.getElementById(`history-block-${id}`);if(block)block.classList.toggle('open');}
document.addEventListener('DOMContentLoaded',()=>{loadSemesterHistory();});
</script>

<script>
// ═══════════════════════════════════════════════════════════════
// DASHBOARD OVERVIEW PANEL
// ═══════════════════════════════════════════════════════════════
function renderDashboard(){
    let totalEarned=0,totalGraded=0,totalFail=0,allGrades=[];
    document.querySelectorAll('.grade-input').forEach(input=>{
        const val=parseFloat(input.value); const credits=parseInt(input.dataset.credits||0); const sid=parseInt(input.dataset.subjectId);
        if(isNaN(val)||input.value==='')return;
        totalGraded++; if(val>5.0)totalEarned+=credits; else totalFail++;
        allGrades.push({id:sid,grade:val,credits});
    });
    const subjectMap={};
    for(const subs of Object.values(SUBJECTS_BY_SEM)){subs.forEach(s=>{subjectMap[s.id]=s;});}
    allGrades=allGrades.map(g=>({...g,groupName:subjectMap[g.id]?.skillGroupName||'Khác',name:subjectMap[g.id]?.name||'?'}));

    const targetYears=parseInt(document.getElementById('target_years')?.value||4);
    const currentSem=parseInt(document.getElementById('target_semester')?.value||1);
    const totalSem=targetYears*3;
    const remSem=Math.max(1,totalSem-currentSem+1);
    const remCredits=Math.max(0,TOTAL_CREDITS-totalEarned);
    const neededPerSem=Math.ceil(remCredits/remSem);
    const progPct=Math.min(100,Math.round((totalEarned/TOTAL_CREDITS)*100));
    const thisSemCredits=currentCourses.reduce((s,c)=>s+(parseInt(subjectMap[c.id]?.credits||0)),0);

    // Card 1: Tiến độ
    const fill=document.getElementById('dash-prog-fill');
    const pctEl=document.getElementById('dash-prog-pct');
    const earnEl=document.getElementById('dash-credit-earned');
    const leftEl=document.getElementById('dash-prog-left');
    const remSemEl=document.getElementById('dash-prog-rem-sem');
    const passEl=document.getElementById('dash-pass-credits');
    const needEl=document.getElementById('dash-needed-per-sem');
    const thisEl=document.getElementById('dash-current-sem');

    if(earnEl)earnEl.textContent=totalEarned;
    if(leftEl)leftEl.textContent=`Còn lại: ${remCredits} TC`;
    if(remSemEl)remSemEl.textContent=`${remSem} kỳ còn`;
    if(passEl)passEl.textContent=totalEarned;
    if(needEl)needEl.textContent=neededPerSem;
    if(thisEl)thisEl.textContent=thisSemCredits||currentCourses.length>0?thisSemCredits:'--';

    const progColor=progPct>=75?'var(--success)':progPct>=40?'var(--ink)':'var(--brand-ochre)';
    if(fill){fill.style.background=progColor;fill.style.width='0%';setTimeout(()=>{fill.style.width=`${progPct}%`;},80);}
    const pctClass=progPct>=75?'great':progPct>=40?'mid':'low';
    if(pctEl){pctEl.textContent=`${progPct}%`;pctEl.className=`dash-prog-pct ${pctClass}`;}

    // Card 2: Thế mạnh / Điểm yếu
    const strengthEl=document.getElementById('dash-strength-content');
    if(strengthEl){
        if(allGrades.length===0){strengthEl.innerHTML=`<div class="dash-no-data"><div class="dash-no-data-icon">⭐</div><div>Nhập điểm để xem</div></div>`;}
        else{
            const groups={};
            allGrades.forEach(g=>{const gn=g.groupName;if(!groups[gn])groups[gn]={grades:[],name:gn};groups[gn].grades.push(g.grade);});
            const groupStats=Object.values(groups).map(g=>({name:g.name,avg:Math.round(g.grades.reduce((s,v)=>s+v,0)/g.grades.length*10)/10,count:g.grades.length})).sort((a,b)=>b.avg-a.avg);
            const avgCls=avg=>avg>=8?'ex':avg>=6.5?'good':avg>=5?'ok':'bad';
            const barColor=avg=>avg>=8?'#22c55e':avg>=6.5?'#1a3a3a':avg>=5?'#e8b94a':'#ef4444';
            const top3=groupStats.slice(0,3);
            const bottom2=groupStats.length>3?groupStats.slice(-2).filter(g=>g.avg<6.5):[];
            const renderRow=g=>`<div class="dash-strength-row"><span class="dash-strength-name">${g.name}</span><div class="dash-strength-bar-wrap"><div class="dash-strength-bar-track"><div class="dash-strength-bar-fill" style="width:${g.avg*10}%;background:${barColor(g.avg)};"></div></div></div><span class="dash-strength-avg ${avgCls(g.avg)}">${g.avg}</span></div>`;
            let html = `<div class="dash-sw-grid">`;
            html += `<div class="dash-sw-col"><div style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:10px;">🌟 Thế mạnh</div><div class="dash-strength-list">${top3.map(renderRow).join('')}</div></div>`;
            if (bottom2.length > 0) {
                html += `<div class="dash-sw-col"><div style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#dc2626;margin-bottom:10px;">⚠ Cần cải thiện</div><div class="dash-strength-list">${bottom2.map(renderRow).join('')}</div></div>`;
            } else {
                html += `<div class="dash-sw-col"><div style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#dc2626;margin-bottom:10px;">⚠ Cần cải thiện</div><div style="font-size:0.75rem;color:var(--muted-soft);text-align:center;margin-top:10px;">Không có điểm yếu đáng kể</div></div>`;
            }
            html += `</div>`;
            strengthEl.innerHTML=html;
        }
    }

    // Card 3: Gợi ý tín chỉ
    const badgeEl=document.getElementById('dash-advice-badge');
    const numEl=document.getElementById('dash-advice-num');
    const reasonEl=document.getElementById('dash-advice-reason');
    if(allGrades.length===0){
        if(badgeEl){badgeEl.className='dash-advice-badge maintain';badgeEl.textContent='• Chưa có dữ liệu';}
        if(numEl){numEl.className='dash-advice-num same';numEl.textContent='--';}
        if(reasonEl)reasonEl.textContent='Nhập điểm các môn để nhận gợi ý.';
    } else {
        const overallGpa=Math.round(allGrades.reduce((s,g)=>s+g.grade,0)/allGrades.length*10)/10;
        const passRate=allGrades.filter(g=>g.grade>5).length/allGrades.length;
        const avgPerSem=thisSemCredits||neededPerSem;
        const savedRec=localStorage.getItem('recommended_credits_per_sem');
        const baseCredits=savedRec?parseInt(savedRec):avgPerSem;
        let recType,recLabel,recCredits,recReason,numClass;
        if (savedRec) {
            recType = 'maintain'; recLabel = ''; recCredits = parseInt(savedRec); numClass = 'same';
            recReason = `Hệ thống khuyến nghị mức tải ${recCredits} TC/kỳ dựa trên đánh giá tiến độ của bạn.`;
        } else if(overallGpa>=7.5&&passRate>=0.85){
            recType='increase';
            numClass='up';
            if (neededPerSem > 20) {
                recLabel='';
                recCredits=Math.min(24,neededPerSem+2);
                recReason=`Thành tích học tập rất tốt (GPA ${overallGpa}). Hệ thống khuyến nghị tăng mức tải lên ${recCredits} TC/kỳ để nhanh chóng bắt kịp tiến độ.`;
            } else {
                recLabel='';
                recCredits=Math.max(neededPerSem + 3, 20); // Gợi ý mức cao để ra trường sớm
                recCredits=Math.min(24, recCredits);
                recReason=`Thành tích học tập của bạn rất xuất sắc (GPA ${overallGpa}). Hệ thống khuyến nghị mức tải ${recCredits} TC/kỳ. Bạn hoàn toàn có khả năng ra trường sớm hơn dự kiến!`;
            }
        }
        else if(overallGpa<5.5||passRate<0.6){
            recType='decrease';
            if (neededPerSem > 20) {
                recLabel='';
                recCredits=15; // Giới hạn cảnh báo học vụ
                numClass='same';
                recReason=`GPA thấp nhưng tiến độ rất chậm. Đề xuất học 15 TC để cân bằng chất lượng và không bị trễ hạn quá lâu.`;
            } else {
                recLabel='↓ Nên giảm tín chỉ';
                recCredits=Math.max(10,neededPerSem-3);
                numClass='down';
                recReason=`GPA ${overallGpa} thấp. Giảm tải để tập trung vào chất lượng.`;
            }
        }
        else if(neededPerSem>(baseCredits||15)+4){recType='increase';recLabel='↑ Cần tăng để kịp';recCredits=Math.min(24,neededPerSem);numClass='up';recReason=`Cần ${neededPerSem} TC/kỳ để tốt nghiệp đúng hạn trong ${remSem} kỳ.`;}
        else{recType='maintain';recLabel='= Giữ nguyên';recCredits=neededPerSem;numClass='same';recReason=`Đang đúng tiến độ. Cần ~${neededPerSem} TC/kỳ trong ${remSem} kỳ còn lại.`;}
        if(badgeEl){badgeEl.className=`dash-advice-badge ${recType}`;badgeEl.textContent=recLabel;}
        if(numEl){numEl.className=`dash-advice-num ${numClass}`;numEl.textContent=recCredits;}
        if(reasonEl)reasonEl.innerHTML=recReason;
        const ccRec = document.getElementById('cc-recommend');
        if(ccRec) ccRec.textContent = `Khuyên dùng: ${recCredits} TC`;

        const globalWarnEl = document.getElementById('dash-global-warning');
        if (globalWarnEl) {
            if (neededPerSem > recCredits + 3 && neededPerSem < 100) {
                globalWarnEl.innerHTML = `<div style="margin-bottom: 24px; padding: 16px 20px; background: #fff1f2; border-left: 5px solid #e11d48; border-radius: 8px; color: #881337; font-size: 0.9rem; line-height: 1.5; display: flex; gap: 14px; align-items: flex-start; box-shadow: 0 4px 12px rgba(225,29,72,0.08);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0; margin-top:2px; color:#e11d48;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>
                        <strong style="display:block; font-size:1rem; margin-bottom:6px; color:#be123c; font-weight:800; letter-spacing:0.02em;">Cảnh báo: Nguy cơ trễ hạn tốt nghiệp!</strong>
                        Tiến độ hiện tại đòi hỏi bạn phải hoàn thành <strong>~${neededPerSem} TC/kỳ</strong> để ra trường đúng mục tiêu. Mức tải <strong>${recCredits} TC/kỳ</strong> là quá thấp. Bạn nên vào mục <b>Cấu hình</b> (thanh menu bên trái) để điều chỉnh nới lỏng thời gian tốt nghiệp cho phù hợp.
                    </div>
                </div>`;
            } else {
                globalWarnEl.innerHTML = '';
            }
        }
    }
}

const __origGradeChangeDash=window.onGradeChange;
window.onGradeChange=function(id,input,skipSave=false){__origGradeChangeDash(id,input,skipSave);clearTimeout(window._dashTimer);window._dashTimer=setTimeout(renderDashboard,500);};
const __origLoadGradesDash=window.loadGradesFromDB;
window.loadGradesFromDB=async function(){await __origLoadGradesDash();setTimeout(renderDashboard,300);};
['target_semester','target_years','academic_year'].forEach(id=>{document.getElementById(id)?.addEventListener('change',()=>{clearTimeout(window._dashTimer);window._dashTimer=setTimeout(renderDashboard,300);});});
document.addEventListener('DOMContentLoaded',()=>{renderDashboard();});
</script>
{{-- Modal Môn Tiên Quyết --}}
<div class="prereq-modal-overlay hidden" id="prereq-modal-overlay">
    <div class="prereq-modal">
        <div class="prereq-header">
            <h3 class="prereq-title">Môn tiên quyết</h3>
            <button class="prereq-close" onclick="closePrereqModal()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:20px;height:20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="prereq-body">
            <p class="prereq-desc">Môn <strong id="prereq-subject-name"></strong> yêu cầu hoàn thành:</p>
            <div class="prereq-list" id="prereq-list"></div>
        </div>
        <div class="prereq-footer">
            <button class="prereq-btn-ok" onclick="closePrereqModal()">Đã hiểu</button>
        </div>
    </div>
</div>

<div class="prereq-modal-overlay hidden" id="score-info-modal-overlay">
    <div class="prereq-modal" style="max-width: 500px;">
        <div class="prereq-header">
            <h3 class="prereq-title">Cách Tính Điểm Ưu Tiên</h3>
            <button class="prereq-close" onclick="closeScoreInfoModal()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:20px;height:20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="prereq-body" style="line-height:1.6; font-size:0.95rem; color:var(--ink);">
            <p style="margin-bottom:12px;">Hệ thống Đề xuất Môn học sử dụng thuật toán tính điểm thông minh để giúp bạn ưu tiên môn học hợp lý nhất:</p>
            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:16px;">
                <li style="display:flex; align-items:start; gap:12px;">
                    <span style="font-size:1.3rem; line-height:1;">💯</span>
                    <div><strong style="color:var(--ink);display:block;margin-bottom:2px;">Điểm Gốc (100đ):</strong> Mọi môn học luôn bắt đầu với 100 điểm.</div>
                </li>
                <li style="display:flex; align-items:start; gap:12px;">
                    <span style="font-size:1.3rem; line-height:1;">⏳</span>
                    <div><strong style="color:var(--ink);display:block;margin-bottom:2px;">Lệch Tiến Độ (-10đ/kỳ):</strong> Nếu môn học nằm ngoài học kỳ hiện tại của bạn, hệ thống trừ 10đ cho mỗi kỳ chênh lệch để giữ lộ trình chuẩn.</div>
                </li>
                <li style="display:flex; align-items:start; gap:12px;">
                    <span style="font-size:1.3rem; line-height:1;">🌟</span>
                    <div><strong style="color:var(--ink);display:block;margin-bottom:2px;">Năng Lực Cá Nhân (±15đ):</strong> Hệ thống phân tích lịch sử điểm để xác định Thế mạnh và Điểm yếu. Môn thuộc thế mạnh được thưởng (tối đa +15đ), môn điểm yếu sẽ bị phạt (tối đa -15đ).</div>
                </li>
                <li style="display:flex; align-items:start; gap:12px;">
                    <span style="font-size:1.3rem; line-height:1;">🔄</span>
                    <div><strong style="color:var(--red);display:block;margin-bottom:2px;">Học Lại (+50đ):</strong> Những môn bạn đã thi rớt sẽ được tự động cộng 50 điểm tuyệt đối để ưu tiên học lại sớm nhất có thể!</div>
                </li>
                <li style="display:flex; align-items:start; gap:12px; margin-top:8px; padding-top:12px; border-top:1px dashed var(--hairline);">
                    <span style="font-size:1.3rem; line-height:1;">🔒</span>
                    <div><strong style="color:#b91c1c;display:block;margin-bottom:2px;">Điều Kiện Tiên Quyết (Bộ lọc):</strong> Các môn học chưa thỏa mãn điều kiện tiên quyết sẽ bị <b>khóa hoàn toàn</b> khỏi danh sách gợi ý đăng ký, bất kể điểm ưu tiên của nó là bao nhiêu.</div>
                </li>
            </ul>
        </div>
        <div class="prereq-footer" style="padding:16px 20px; border-top:1px solid var(--hairline); display:flex; justify-content:flex-end;">
            <button class="prereq-btn-ok" onclick="closeScoreInfoModal()" style="background:var(--primary); color:white; border:none; padding:8px 20px; border-radius:6px; cursor:pointer; font-weight:600;">Đã hiểu</button>
        </div>
    </div>
</div>

<script>
function openPrereqModal(subjectData) {
    let subject;
    try {
        subject = typeof subjectData === 'string' ? JSON.parse(subjectData) : subjectData;
    } catch(e) { return; }
    
    document.getElementById('prereq-subject-name').innerHTML = subject.name + ' <span class="pill pill-lavender" style="font-size:0.7rem;margin-left:6px;vertical-align:1px;">Học kỳ chuẩn: ' + (subject.semester?.name||'1') + '</span>';
    const list = document.getElementById('prereq-list');
    
    if (!subject.prerequisites_info || subject.prerequisites_info.length === 0) {
        list.innerHTML = `<div style="text-align:center;padding:20px;color:var(--muted);">Môn học này không yêu cầu môn tiên quyết.</div>`;
    } else {
        list.innerHTML = subject.prerequisites_info.map(p => `
            <div class="prereq-item ${p.is_passed ? 'is-passed' : 'is-failed'}">
                <div class="prereq-item-icon">
                    ${p.is_passed 
                        ? '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:28px;height:28px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" /></svg>'
                        : '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:28px;height:28px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" /></svg>'
                    }
                </div>
                <div class="prereq-item-info">
                    <span class="prereq-item-name">${p.name}</span>
                    <span class="prereq-item-status">${p.is_passed ? 'Đã hoàn thành' : 'Chưa hoàn thành'}</span>
                </div>
            </div>
        `).join('');
    }
    document.getElementById('prereq-modal-overlay').classList.remove('hidden');
}
function closePrereqModal() {
    document.getElementById('prereq-modal-overlay').classList.add('hidden');
}

function openScoreInfoModal() {
    document.getElementById('score-info-modal-overlay').classList.remove('hidden');
}
function closeScoreInfoModal() {
    document.getElementById('score-info-modal-overlay').classList.add('hidden');
}
</script>

</body>
</html>
