<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcademiaLink — Smart Student Planner</title>
    <meta name="description"
        content="Hệ thống gợi ý môn học thông minh, theo dõi tiến độ và phân tích kết quả học tập của bạn.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <link href="{{ asset('css/student-planner.css') }}" rel="stylesheet">
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
                <div class="srm-subtitle" id="srm-subtitle">Phân tích kết quả và gợi ý lộ trình tín chỉ kỳ tiếp theo
                </div>
            </div>
            <div class="srm-kpi-row" id="srm-kpi-row">
                <div class="srm-kpi">
                    <div class="srm-kpi-val" id="srm-gpa">--</div>
                    <div class="srm-kpi-label">GPA học kỳ</div>
                </div>
                <div class="srm-kpi">
                    <div class="srm-kpi-val green" id="srm-pass-count">0</div>
                    <div class="srm-kpi-label">Môn pass</div>
                </div>
                <div class="srm-kpi">
                    <div class="srm-kpi-val red" id="srm-fail-count">0</div>
                    <div class="srm-kpi-label">Môn fail</div>
                </div>
                <div class="srm-kpi">
                    <div class="srm-kpi-val blue" id="srm-credits-done">0</div>
                    <div class="srm-kpi-label">TC tích lũy</div>
                </div>
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
                <button class="srm-btn-apply" id="srm-btn-apply" onclick="applyCreditRecommendation()">✓ Áp dụng gợi
                    ý</button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
    ONBOARDING WIZARD
    ══════════════════════════════════════════════════════════════════ --}}
    @include('components.onboarding-modal')

    {{-- ══════════════════════════════════════════════════════════════════
    CONFIG PANEL
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="config-panel-overlay" id="config-overlay" onclick="closeConfigPanel()"></div>
    <div class="config-panel" id="config-panel">
        <div class="config-panel-header">
            <div class="config-panel-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24"
                    stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                </svg>
                Cấu Hình Chương Trình
            </div>
            <button class="config-panel-close" onclick="closeConfigPanel()">✕</button>
        </div>
        <div class="config-form-grid">
            <div class="input-group">
                <label class="input-label" for="academic_year">Niên khóa</label>
                <select id="academic_year" class="clay-select" onchange="savePreferences()">
                    @foreach($academicYears as $year)
                        <option value="{{ $year }}" {{ Auth::user()->pref_academic_year == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="input-group">
                <label class="input-label" for="program_type">Hệ đào tạo</label>
                <select id="program_type" class="clay-select" onchange="savePreferences()">
                    @foreach($programTypes as $type)
                        <option value="{{ $type }}" {{ Auth::user()->pref_program_type == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
        </div>
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



    {{-- ── SIDEBAR ── --}}
    @include('components.sidebar')


    {{-- ── MAIN CONTENT ── --}}
    <div class="main-content">

        {{-- Topbar --}}
        @include('components.topbar')


        {{-- ════════════════════════════════════════════════════════
        TAB: DASHBOARD
        ════════════════════════════════════════════════════════ --}}
        <div class="page-content tab-panel active" id="tab-dashboard">

            {{-- Welcome band --}}
            <div style="margin-bottom:var(--sp-xl);">
                <h1
                    style="font-family:'Sora',sans-serif;font-size:1.75rem;font-weight:800;color:var(--ink);letter-spacing:-0.5px;margin-bottom:4px;">
                    Chào mừng, {{ Auth::user()->fullName ?? Auth::user()->username }} 👋
                </h1>
                <p style="color:var(--muted);font-size:0.9rem;">Theo dõi tiến độ học tập và nhận gợi ý thông minh.</p>
            </div>

            {{-- 4 KPI feature cards --}}
            @include('components.dashboard-stat')
            {{-- Dashboard 3-col mini cards --}}
            <div id="dash-global-warning"></div>
            <div class="dash-panel" id="dash-panel">

                {{-- Card 1: Tiến độ tín chỉ --}}
                <div class="dash-card">
                    <div class="dash-credit-header">
                        <div class="dash-credit-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.902 59.902 0 0 1 10.399 5.84 50.53 50.53 0 0 0-2.658.814m-15.482 0A50.699 50.699 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                            </svg>
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
                        <div class="dash-sub-item">
                            <div class="dash-sub-val green" id="dash-pass-credits">0</div>
                            <div class="dash-sub-label">TC pass</div>
                        </div>
                        <div class="dash-sub-item">
                            <div class="dash-sub-val amber" id="dash-needed-per-sem">--</div>
                            <div class="dash-sub-label">TC/kỳ cần</div>
                        </div>
                        <div class="dash-sub-item">
                            <div class="dash-sub-val blue" id="dash-current-sem">--</div>
                            <div class="dash-sub-label">HK này</div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Thế mạnh --}}
                <div class="dash-card">
                    <div class="dash-strength-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                        </svg>
                        Thế Mạnh & Điểm Yếu
                    </div>
                    <div id="dash-strength-content">
                        <div class="dash-no-data">
                            <div class="dash-no-data-icon">⭐</div>
                            <div>Nhập điểm để xem</div>
                        </div>
                    </div>
                </div>

                {{-- Card 3: Gợi ý tín chỉ --}}
                <div class="dash-card">
                    <div class="dash-advice-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
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
                    <button class="btn-primary" onclick="switchTab('analysis', document.getElementById('nav-analysis'))"
                        style="height:36px;font-size:0.78rem;padding:0 14px;">Xem đầy đủ →</button>
                </div>
                <div class="chart-sem-filter" id="chart-sem-filter-dash">
                    <button class="chart-sem-btn active" data-sem="all" onclick="filterChartSem('all', this)">Tất cả
                        HK</button>
                </div>
                <div class="chart-wrapper" style="min-height:240px;">
                    <div class="chart-empty" id="chart-empty-dash">
                        <span class="chart-empty-icon">📊</span>
                        <p>Nhập điểm môn học để xem biểu đồ so sánh với sinh viên cùng khóa</p>
                    </div>
                    <canvas id="gradeChart" style="display:none;"></canvas>
                </div>
                <div class="chart-legend" id="chart-legend" style="display:none;">
                    <div class="chart-legend-item">
                        <div class="chart-legend-dot" style="background:var(--ink);"></div>Điểm của bạn
                    </div>
                    <div class="chart-legend-item">
                        <div class="chart-legend-dot" style="background:var(--brand-ochre);border-radius:50%;"></div>
                        Điểm TB cùng khóa
                    </div>
                    <div class="chart-legend-item">
                        <div class="chart-legend-dot" style="background:var(--brand-coral);border-radius:50%;"></div>
                        Ngưỡng Pass (5.0)
                    </div>
                </div>
            </div>
        </div>



        {{-- ════════════════════════════════════════════════════════
        TAB: PHÂN TÍCH & BIỂU ĐỒ
        ════════════════════════════════════════════════════════ --}}
        <div class="page-content tab-panel" id="tab-analysis">
            <div style="margin-bottom:var(--sp-xl);">
                <h2
                    style="font-family:'Sora',sans-serif;font-size:1.4rem;font-weight:800;color:var(--ink);letter-spacing:-0.4px;margin-bottom:4px;">
                    Phân Tích & Biểu Đồ</h2>
                <p style="color:var(--muted);font-size:0.88rem;">Xem biểu đồ so sánh điểm số, nhận phân tích và cảnh báo theo nhóm kỹ năng hoặc khối kiến thức.</p>
            </div>

            {{-- Summary stats for chart tab --}}
            <div class="content-grid" style="margin-bottom:var(--sp-xl);">
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

            <div class="clay-card" style="margin-bottom:var(--sp-xl);">
                <div class="card-title-row">
                    <div class="card-heading">
                        📊 Điểm Cá Nhân vs. Trung Bình Khóa
                        <span class="chart-peer-info" id="chart-peer-label-detail"></span>
                    </div>
                </div>
                <div class="chart-sem-filter" id="chart-sem-filter">
                    <button class="chart-sem-btn active" data-sem="all" onclick="filterChartSem('all', this)">Tất cả
                        HK</button>
                </div>
                <div class="chart-wrapper" style="min-height:320px;">
                    <div class="chart-empty" id="chart-empty">
                        <span class="chart-empty-icon">📊</span>
                        <p>Nhập điểm môn học để xem biểu đồ so sánh với sinh viên cùng khóa</p>
                    </div>
                    <canvas id="gradeChartDetail" style="display:none;"></canvas>
                </div>
                <div class="chart-legend" id="chart-legend-detail" style="display:none;">
                    <div class="chart-legend-item">
                        <div class="chart-legend-dot" style="background:var(--ink);"></div>Điểm của bạn
                    </div>
                    <div class="chart-legend-item">
                        <div class="chart-legend-dot" style="background:var(--brand-ochre);border-radius:50%;"></div>
                        Điểm TB cùng khóa
                    </div>
                    <div class="chart-legend-item">
                        <div class="chart-legend-dot" style="background:var(--brand-coral);border-radius:50%;"></div>
                        Ngưỡng Pass (5.0)
                    </div>
                </div>
            </div>

            <div class="analysis-toggle-group"
                style="display:inline-flex;background:var(--surface-soft);border:1px solid var(--hairline);border-radius:10px;padding:4px;margin-bottom:var(--sp-lg);gap:4px;">
                <button class="toggle-btn active" onclick="setAnalysisType('skill')" id="toggle-analysis-skill"
                    style="border:none;background:var(--surface);color:var(--ink);padding:8px 16px;border-radius:8px;font-size:0.88rem;font-weight:600;cursor:pointer;box-shadow:var(--shadow-sm);transition:all 0.2s;">Phân
                    tích theo Skill Groups</button>
                <button class="toggle-btn" onclick="setAnalysisType('program')" id="toggle-analysis-program"
                    style="border:none;background:transparent;color:var(--muted);padding:8px 16px;border-radius:8px;font-size:0.88rem;font-weight:600;cursor:pointer;transition:all 0.2s;">Phân
                    tích theo Program Groups</button>
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
        TAB: LẬP KẾ HOẠCH ĐA HỌC KỲ (NEW FEATURE)
        ════════════════════════════════════════════════════════ --}}
        <div class="page-content tab-panel" id="tab-planner">
            <div style="margin-bottom:var(--sp-xl);">
                <h2
                    style="font-family:'Sora',sans-serif;font-size:1.4rem;font-weight:800;color:var(--ink);letter-spacing:-0.4px;margin-bottom:4px;">
                    Lập Kế Hoạch Đa Học Kỳ</h2>
                <p style="color:var(--muted);font-size:0.88rem;">Hệ thống sẽ tự động rải môn học cho tất cả các học kỳ
                    còn lại đến khi tốt nghiệp.</p>
            </div>

            <div id="planner-selection-view" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:var(--sp-xl);">
                <!-- Cột Tạo Kế Hoạch Mới -->
                <div class="clay-card">
                    <div class="card-title-row">
                        <div class="card-heading">
                            ⚙️ Tạo Kế Hoạch Mới
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:16px;">
                        <div>
                            <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Chế độ học (Mode)</label>
                            <select id="planner-mode" class="clay-select" style="width: 100%;">
                                <option value="normal" selected>Bình thường (8 kỳ - 4 năm)</option>
                                <option value="fast">Nhanh (6 kỳ - 3 năm)</option>
                                <option value="slow">Chậm (10 kỳ - 5 năm)</option>
                            </select>
                            <div id="planner-target-credits" style="font-size:0.82rem; color:var(--primary); margin-top:8px; font-weight:500; display:none;"></div>
                        </div>
                        <div>
                            <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Tên kế hoạch <span style="color:var(--red);">*</span></label>
                            <input type="text" id="planner-name" class="ob-grade-input"
                                placeholder="Ví dụ: Kế hoạch ra trường sớm"
                                style="width: 100%; text-align:left; padding:0 12px; height:42px;">
                        </div>
                        <button class="btn-primary" onclick="generateStudyPlan()" style="height: 42px; width:100%; justify-content:center; margin-top:8px;">✨ Tạo kế hoạch</button>
                    </div>
                    <div id="planner-loader" class="loader" style="display:none; text-align:center; padding:20px;">
                        <div class="spinner" style="margin:0 auto;"></div>
                        <p style="color:var(--muted);font-size:0.9rem;margin-top:10px;">Hệ thống đang chạy thuật toán Tham lam (Greedy)...</p>
                    </div>
                </div>

                <!-- Cột Danh Sách Kế Hoạch Đã Lưu -->
                <div class="clay-card">
                    <div class="card-title-row">
                        <div class="card-heading">
                            📂 Kế Hoạch Của Bạn
                        </div>
                    </div>
                    <div id="inline-saved-plans-list" style="display:flex; flex-direction:column; gap:12px; max-height:260px; overflow-y:auto; padding-right:8px;">
                        <!-- Danh sách kế hoạch load bằng JS -->
                        <p style="color:var(--muted); text-align:center; padding:20px;">Đang tải danh sách...</p>
                    </div>
                </div>
            </div>

            <!-- Floating Action Button for Suggestions -->
            <button class="btn-primary suggestion-fab" onclick="toggleSuggestionDrawer()" title="Xem môn học gợi ý"
                style="position: fixed; right: -5px; top: 50%; transform: translateY(-50%); background: var(--brand-mint); color: var(--ink); font-size: 1.5rem; padding: 12px 14px 12px 18px; border-radius: 24px 0 0 24px; display: flex; align-items: center; justify-content: center; box-shadow: -4px 0 16px rgba(0,0,0,0.1); z-index: 998; transition: all 0.2s; border: 1px solid var(--hairline); border-right: none; cursor: pointer;"
                onmouseover="this.style.right='0px'" onmouseout="this.style.right='-5px'">
                ✨
            </button>

            <div id="suggestion-drawer"
                style="position: fixed; top: 0; right: -450px; width: 450px; max-width: 100vw; height: 100vh; background: var(--surface); box-shadow: -4px 0 24px rgba(0,0,0,0.12); z-index: 9999; transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column;">
                <div
                    style="padding: 20px; border-bottom: 1px solid var(--hairline); display: flex; justify-content: space-between; align-items: center; background: var(--brand-mint);">
                    <div style="display:flex; align-items:center; gap: 8px;">
                        <h3 style="margin: 0; font-size: 1.1rem; color: var(--ink); font-weight: 700; display: flex; align-items: center;">
                            ✨ Môn học gợi ý cho kỳ tới
                            <span id="suggested-total-credits" style="font-size: 0.95rem; font-weight: 500; color: var(--muted); margin-left: 8px;"></span>
                        </h3>
                        <span class="btn-info-clay" onclick="openScoreInfoModal()" title="Cách tính điểm"
                            style="cursor:pointer; display:flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:50%; background:rgba(255,255,255,0.5); color:var(--ink); border:1px solid rgba(0,0,0,0.1);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" style="width:16px;height:16px;">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                            </svg>
                        </span>
                    </div>
                    <button onclick="toggleSuggestionDrawer()"
                        style="background: none; border: none; font-size: 1.8rem; cursor: pointer; color: var(--ink); padding: 0; line-height: 1;">&times;</button>
                </div>
                <div style="padding: 16px; border-bottom: 1px solid var(--hairline); background: var(--surface-soft);">
                    <button class="btn-primary" id="btn-apply-suggestions" onclick="applySuggestionsToPlan()"
                        style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" style="width:18px;height:18px;">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Áp dụng ngay vào kế hoạch
                    </button>
                    <p style="margin: 8px 0 0 0; font-size: 0.85rem; color: var(--muted); text-align: center;">Môn học
                        sẽ được tự động thêm vào học kỳ tiếp theo</p>
                </div>
                <div style="padding: 20px; overflow-y: auto; flex: 1;">
                    <div class="loader" id="loader">
                        <div class="spinner"></div>
                        <p style="color:var(--muted);font-size:0.9rem;">Hệ thống đang phân tích các môn học...</p>
                    </div>
                    <div id="suggestions-list" class="suggestion-list"></div>
                </div>
            </div>

            <script>
                function toggleSuggestionDrawer() {
                    const drawer = document.getElementById('suggestion-drawer');
                    if (drawer.style.right === '0px') {
                        drawer.style.right = '-450px';
                    } else {
                        drawer.style.right = '0px';
                    }
                }
            </script>

            <div id="study-plan-results" style="display:none;">
                <div class="empty-state">
                    <p>Chưa có kế hoạch nào được tạo. Chọn chế độ và nhấn nút tạo ở trên.</p>
                </div>
            </div>
        </div>

    </div>{{-- end main-content --}}
    </div>{{-- end app-shell --}}

    <div class="save-indicator" id="save-indicator"></div>

    @php
        $subjectsBySem = $subjects->map(function ($group) {
            return $group->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'name' => $sub->name,
                    'credits' => $sub->credits,
                    'semName' => $sub->semester?->name ?? '?',
                    'typeName' => $sub->subjectType?->name ?? '',
                    'skillGroupName' => $sub->skillGroup?->name ?? 'Khác',
                    'programGroupName' => $sub->programGroup?->name ?? 'Khác',
                ];
            })->values();
        });
    @endphp

    <script>
        // ─── Server data ──────────────────────────────────────────────────────────
        const ACADEMIC_YEARS = @json($academicYears);
        const PROGRAM_TYPES = @json($programTypes);
        const SUBJECTS_BY_SEM = @json($subjectsBySem);
        const TOTAL_CREDITS = {{ $totalCredits }};
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // ─── App State ────────────────────────────────────────────────────────────
    </script>
    <script src="{{ asset('js/student-planner.js') }}"></script>

    {{-- Modal Môn Tiên Quyết --}}
    <div class="prereq-modal-overlay hidden" id="prereq-modal-overlay">
        <div class="prereq-modal">
            <div class="prereq-header">
                <h3 class="prereq-title">Môn tiên quyết</h3>
                <button class="prereq-close" onclick="closePrereqModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" style="width:20px;height:20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" style="width:20px;height:20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="prereq-body" style="line-height:1.6; font-size:0.95rem; color:var(--ink);">
                <p style="margin-bottom:12px;">Hệ thống Đề xuất Môn học sử dụng thuật toán tính điểm thông minh để giúp
                    bạn ưu tiên môn học hợp lý nhất:</p>
                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:16px;">
                    <li style="display:flex; align-items:start; gap:12px;">
                        <span style="font-size:1.3rem; line-height:1;">💯</span>
                        <div><strong style="color:var(--ink);display:block;margin-bottom:2px;">Điểm Gốc (100đ):</strong>
                            Mọi môn học luôn bắt đầu với 100 điểm.</div>
                    </li>
                    <li style="display:flex; align-items:start; gap:12px;">
                        <span style="font-size:1.3rem; line-height:1;">⏳</span>
                        <div><strong style="color:var(--ink);display:block;margin-bottom:2px;">Lệch Tiến Độ
                                (-10đ/kỳ):</strong> Nếu môn học nằm ngoài học kỳ hiện tại của bạn, hệ thống trừ 10đ cho
                            mỗi kỳ chênh lệch để giữ lộ trình chuẩn.</div>
                    </li>
                    <li style="display:flex; align-items:start; gap:12px;">
                        <span style="font-size:1.3rem; line-height:1;">🌟</span>
                        <div><strong style="color:var(--ink);display:block;margin-bottom:2px;">Năng Lực Cá Nhân
                                (±15đ):</strong> Hệ thống phân tích lịch sử điểm để xác định Thế mạnh và Điểm yếu. Môn
                            thuộc thế mạnh được thưởng (tối đa +15đ), môn điểm yếu sẽ bị phạt (tối đa -15đ).</div>
                    </li>
                    <li style="display:flex; align-items:start; gap:12px;">
                        <span style="font-size:1.3rem; line-height:1;">🔄</span>
                        <div><strong style="color:var(--red);display:block;margin-bottom:2px;">Học Lại (+50đ):</strong>
                            Những môn bạn đã thi rớt sẽ được tự động cộng 50 điểm tuyệt đối để ưu tiên học lại sớm nhất
                            có thể!</div>
                    </li>
                    <li
                        style="display:flex; align-items:start; gap:12px; margin-top:8px; padding-top:12px; border-top:1px dashed var(--hairline);">
                        <span style="font-size:1.3rem; line-height:1;">🔒</span>
                        <div><strong style="color:#b91c1c;display:block;margin-bottom:2px;">Điều Kiện Tiên Quyết (Bộ
                                lọc):</strong> Các môn học chưa thỏa mãn điều kiện tiên quyết sẽ bị <b>khóa hoàn
                                toàn</b> khỏi danh sách gợi ý đăng ký, bất kể điểm ưu tiên của nó là bao nhiêu.</div>
                    </li>
                </ul>
            </div>
            <div class="prereq-footer"
                style="padding:16px 20px; border-top:1px solid var(--hairline); display:flex; justify-content:flex-end;">
                <button class="prereq-btn-ok" onclick="closeScoreInfoModal()"
                    style="background:var(--primary); color:white; border:none; padding:8px 20px; border-radius:6px; cursor:pointer; font-weight:600;">Đã
                    hiểu</button>
            </div>
        </div>
    </div>
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    </script>
    <script src="{{ asset('js/student-planner.js') }}"></script>
</body>

</html>