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
    DYNAMIC MODE MODAL
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="sem-result-overlay" id="dynamic-mode-overlay" style="z-index: 100000;">
        <div class="sem-result-modal" style="max-width: 500px;">
            <div class="srm-header" style="background: var(--brand-ochre); color: white;">
                <button class="srm-close" style="color: white; opacity: 0.8;" onclick="closeDynamicModeModal()">✕</button>
                <div class="srm-semester-label" style="color: rgba(255,255,255,0.8);">Đánh giá tiến độ 30%</div>
                <div class="srm-title" id="dyn-modal-title">Cảnh báo học thuật</div>
                <div class="srm-subtitle" style="color: rgba(255,255,255,0.9);">Hệ thống đề xuất điều chỉnh lộ trình học</div>
            </div>
            
            <div style="padding: 24px;">
                <div id="dyn-modal-message" style="font-size: 0.95rem; color: var(--ink); line-height: 1.6; margin-bottom: 20px; padding: 16px; background: #fffbeb; border-radius: 8px; border: 1px solid #fde68a;">
                    Message goes here...
                </div>

                <div style="background: var(--surface); border: 1px solid var(--hairline); border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                    <h4 style="margin: 0 0 12px 0; font-size: 0.9rem; display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="color: var(--brand-ochre);"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        Lợi ích & Bất lợi
                    </h4>
                    <div id="dyn-modal-pros" style="font-size: 0.85rem; color: var(--success-dark); margin-bottom: 8px;">+ Giảm tải áp lực học tập xuống mức an toàn.</div>
                    <div id="dyn-modal-cons" style="font-size: 0.85rem; color: var(--error);">− Ra trường trễ hơn dự kiến.</div>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button class="btn-secondary" onclick="closeDynamicModeModal()">Từ chối (Giữ nguyên)</button>
                    <button class="btn-primary" id="dyn-modal-confirm-btn" onclick="confirmDynamicMode()">Đồng ý điều chỉnh</button>
                </div>
            </div>
        </div>
    </div>

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
                    {{-- Badge phạm vi: chỉ ảnh hưởng kỳ tiếp --}}
                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <div class="srm-recommend-tag" id="srm-rec-tag">Gợi ý chung</div>
                        <span style="font-size: 0.68rem; background: #e0f2fe; color: #0369a1; padding: 1px 6px; border-radius: 4px; font-weight: 700; letter-spacing: 0.03em;">CHỈ KỲ TIẾP</span>
                    </div>
                    <div class="srm-recommend-headline" id="srm-rec-headline">Giữ nguyên số tín chỉ</div>
                    <div class="srm-credit-change" id="srm-credit-change"><span>--</span> TC/kỳ</div>
                    <div class="srm-recommend-desc" id="srm-rec-desc">--</div>
                </div>
            </div>
            <div class="srm-reasons" id="srm-reasons"></div>
            
            {{-- Advisor Section: Gợi ý điều chỉnh mode lộ trình --}}
            <div class="srm-advisor-section" id="srm-advisor-section"
                 style="display:none; background: linear-gradient(135deg, #fefce8 0%, #f0fdf4 100%); border: 1px solid #d1fae5; border-radius: 12px; margin: 0 24px 4px; overflow: hidden;">

                {{-- Header phân biệt với phần gợi ý TC bên trên --}}
                <div style="padding: 12px 16px 0; display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 1rem;">🧠</span>
                    <div>
                        <div style="font-size: 0.88rem; font-weight: 700; color: var(--ink);">Gợi Ý Thay Đổi Cường Độ Học</div>
                        <div style="font-size: 0.75rem; color: var(--muted); margin-top: 1px;">Áp dụng cho <strong>toàn bộ lộ trình</strong> còn lại — khác với gợi ý TC/kỳ ở trên</div>
                    </div>
                </div>

                <div id="srm-advisor-message" style="font-size: 0.88rem; color: var(--muted); padding: 10px 16px 0; line-height: 1.6;">
                    <!-- Message goes here -->
                </div>

                <div id="srm-adjustment-prompt"
                     style="display:none; padding: 12px 16px; margin-top: 10px; background: rgba(255,255,255,0.7); border-top: 1px solid #d1fae5; gap: 8px; justify-content: space-between; align-items: center;">
                    {{-- Impact warning --}}
                    <div style="font-size: 0.75rem; color: #6b7280; display: flex; align-items: center; gap: 5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px;color:#d97706;flex-shrink:0;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
                        </svg>
                        Sẽ tái phân bổ lại toàn bộ môn học còn lại
                    </div>
                    <div style="display: flex; gap: 8px; flex-shrink: 0;">
                        <button class="btn-secondary" onclick="closeSemResultModal()"
                                style="padding: 6px 14px; font-size: 0.82rem; border-radius: 8px;">
                            Bỏ qua
                        </button>
                        <button class="btn-primary" onclick="applyAutoAdjustment()"
                                style="padding: 6px 14px; font-size: 0.82rem; background: #10b981; border-radius: 8px; display: flex; align-items: center; gap: 5px;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            Áp dụng & Tái lộ trình
                        </button>
                    </div>
                </div>
            </div>

            <div class="srm-subj-section" id="srm-subj-section"></div>

            <div class="srm-footer" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 24px; border-top: 1px solid var(--hairline);">
                <button class="srm-btn-close" onclick="closeSemResultModal()">Đóng</button>

                {{-- Nút này CHỈ đặt mục tiêu TC cho kỳ tiếp, không thay đổi lộ trình tổng thể --}}
                <button class="srm-btn-apply" id="srm-btn-apply" onclick="applyCreditRecommendation()" style="display:none;"
                        title="Lưu mục tiêu số tín chỉ cho kỳ học tiếp theo. Không thay đổi cấu trúc lộ trình.">
                    <span style="font-size: 0.75rem; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; margin-right: 5px; font-weight: 700;">KỲ TIẾP</span>
                    ✨ Áp dụng gợi ý
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
    CASCADE ANALYSIS MODAL
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="sem-result-overlay" id="cascade-modal-overlay" style="z-index: 100001; display:none;" onclick="if(event.target===this) closeCascadeModal()">
        <div class="sem-result-modal" style="max-width:600px; max-height:85vh; display:flex; flex-direction:column;">
            <div class="srm-header" style="background:linear-gradient(135deg,#7c3aed,#4f46e5); flex-shrink:0;">
                <button class="srm-close" style="color:white; opacity:0.8;" onclick="closeCascadeModal()">✕</button>
                <div class="srm-semester-label" style="color:rgba(255,255,255,0.8);">Phân tích hiệu ứng dây chuyền</div>
                <div class="srm-title" id="cascade-modal-title">Ảnh Hưởng Khi Rớt Môn</div>
                <div class="srm-subtitle" style="color:rgba(255,255,255,0.85);" id="cascade-modal-subject">--</div>
            </div>

            <div style="padding:20px 24px; overflow-y:auto; flex:1;">
                {{-- Summary --}}
                <div id="cascade-summary-box" style="background:#fef3c7; border:1px solid #fde68a; border-radius:10px; padding:14px 16px; margin-bottom:16px; font-size:0.9rem; color:#78350f; line-height:1.6;"></div>

                {{-- KPI row --}}
                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px;">
                    <div style="text-align:center; background:var(--surface-soft); border-radius:10px; padding:14px 8px;">
                        <div style="font-family:'Sora',sans-serif; font-size:1.6rem; font-weight:800; color:#7c3aed;" id="cascade-kpi-total">0</div>
                        <div style="font-size:0.75rem; color:var(--muted); font-weight:600; text-transform:uppercase;">Môn bị ảnh hưởng</div>
                    </div>
                    <div style="text-align:center; background:var(--surface-soft); border-radius:10px; padding:14px 8px;">
                        <div style="font-family:'Sora',sans-serif; font-size:1.6rem; font-weight:800; color:#ef4444;" id="cascade-kpi-credits">0</div>
                        <div style="font-size:0.75rem; color:var(--muted); font-weight:600; text-transform:uppercase;">TC bị khoá</div>
                    </div>
                    <div style="text-align:center; background:var(--surface-soft); border-radius:10px; padding:14px 8px;">
                        <div style="font-family:'Sora',sans-serif; font-size:1.6rem; font-weight:800; color:#f59e0b;" id="cascade-kpi-delay">0</div>
                        <div style="font-size:0.75rem; color:var(--muted); font-weight:600; text-transform:uppercase;">Kỳ có thể trễ</div>
                    </div>
                </div>

                {{-- Direct blocked --}}
                <div id="cascade-direct-section" style="display:none; margin-bottom:16px;">
                    <div style="font-size:0.85rem; font-weight:700; color:#dc2626; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                        🔒 Môn bị khoá trực tiếp <span id="cascade-direct-count" style="background:#fee2e2; color:#dc2626; border-radius:20px; padding:1px 8px; font-size:0.72rem;"></span>
                    </div>
                    <div id="cascade-direct-list" style="display:flex; flex-wrap:wrap; gap:6px;"></div>
                </div>

                {{-- Indirect blocked --}}
                <div id="cascade-indirect-section" style="display:none;">
                    <div style="font-size:0.85rem; font-weight:700; color:#d97706; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                        🔗 Môn bị ảnh hưởng gián tiếp <span id="cascade-indirect-count" style="background:#fef3c7; color:#92400e; border-radius:20px; padding:1px 8px; font-size:0.72rem;"></span>
                    </div>
                    <div id="cascade-indirect-list" style="display:flex; flex-wrap:wrap; gap:6px;"></div>
                </div>

                <div id="cascade-no-impact" style="display:none; text-align:center; padding:24px; color:var(--muted);">
                    <div style="font-size:2rem; margin-bottom:8px;">✅</div>
                    <p>Môn này không là tiên quyết của môn nào khác.<br>Không có ảnh hưởng dây chuyền.</p>
                </div>
            </div>

            <div style="padding:14px 24px; border-top:1px solid var(--hairline); display:flex; justify-content:flex-end; flex-shrink:0;">
                <button class="btn-secondary" onclick="closeCascadeModal()">Đóng</button>
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

        {{-- Định hướng kỹ năng --}}
        <div class="input-group" style="margin-top: 16px;">
            <label class="input-label" for="skill_focus" style="display:flex; align-items:center; gap:6px;">
                🎯 Định hướng kỹ năng
                <span style="font-size:0.75rem; color:var(--muted); font-weight:400;">(ảnh hưởng đến thứ tự gợi ý môn)</span>
            </label>
            <select id="skill_focus" class="clay-select" onchange="savePreferences()">
                <option value="">— Chưa chọn —</option>
                <option value="backend"  {{ Auth::user()->pref_skill_focus === 'backend'  ? 'selected' : '' }}>🖥️ Backend Development</option>
                <option value="frontend" {{ Auth::user()->pref_skill_focus === 'frontend' ? 'selected' : '' }}>🎨 Frontend Development</option>
                <option value="ai"       {{ Auth::user()->pref_skill_focus === 'ai'       ? 'selected' : '' }}>🤖 AI / Machine Learning</option>
                <option value="data"     {{ Auth::user()->pref_skill_focus === 'data'     ? 'selected' : '' }}>📊 Data Science / Analytics</option>
                <option value="mobile"   {{ Auth::user()->pref_skill_focus === 'mobile'   ? 'selected' : '' }}>📱 Mobile Development</option>
                <option value="devops"   {{ Auth::user()->pref_skill_focus === 'devops'   ? 'selected' : '' }}>⚙️ DevOps / Cloud</option>
                <option value="testing"  {{ Auth::user()->pref_skill_focus === 'testing'  ? 'selected' : '' }}>🧪 Testing / QA</option>
                <option value="security" {{ Auth::user()->pref_skill_focus === 'security' ? 'selected' : '' }}>🔒 Cybersecurity</option>
            </select>
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



    @include('components.history-drawer')

    {{-- ══════════════════════════════════════════════════════════════════
         APP SHELL
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="app-shell">

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

            {{-- Glassmorphism Hero Section --}}
            <style>
                /* New Dashboard Styles */
                .hero-glass {
                    background: rgba(255, 255, 255, 0.7);
                    backdrop-filter: blur(16px);
                    -webkit-backdrop-filter: blur(16px);
                    border: 1px solid rgba(255, 255, 255, 0.5);
                    border-radius: 20px;
                    padding: 32px;
                    margin-bottom: 24px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 24px;
                    position: relative;
                    overflow: hidden;
                }
                .hero-glass::before {
                    content: '';
                    position: absolute;
                    top: -50%; left: -50%;
                    width: 200%; height: 200%;
                    background: radial-gradient(circle, rgba(16,185,129,0.05) 0%, rgba(255,255,255,0) 70%);
                    z-index: 0;
                    pointer-events: none;
                }
                .hero-content {
                    position: relative;
                    z-index: 1;
                    flex: 1;
                }
                .hero-title {
                    font-family: 'Sora', sans-serif;
                    font-size: 2rem;
                    font-weight: 800;
                    color: var(--ink);
                    letter-spacing: -0.5px;
                    margin-bottom: 8px;
                }
                .hero-subtitle {
                    color: var(--muted);
                    font-size: 1rem;
                    margin-bottom: 24px;
                }
                .hero-stats {
                    display: flex;
                    gap: 32px;
                }
                .hero-stat-item {
                    display: flex;
                    flex-direction: column;
                }
                .hero-stat-value {
                    font-size: 2rem;
                    font-weight: 800;
                    font-family: 'Sora', sans-serif;
                    color: var(--ink);
                    display: flex;
                    align-items: baseline;
                    gap: 4px;
                }
                .hero-stat-value span {
                    font-size: 0.9rem;
                    color: var(--muted);
                    font-weight: 500;
                }
                .hero-stat-label {
                    font-size: 0.85rem;
                    color: var(--muted);
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    font-weight: 600;
                }
                .hero-progress-circle {
                    position: relative;
                    z-index: 1;
                    width: 140px;
                    height: 140px;
                    flex-shrink: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    background: conic-gradient(var(--brand-mint) var(--prog-deg, 0deg), var(--surface-soft) 0deg);
                    box-shadow: inset 0 0 0 12px rgba(255,255,255,0.8);
                }
                .hero-progress-inner {
                    width: 116px;
                    height: 116px;
                    background: #fff;
                    border-radius: 50%;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
                }
                .hero-progress-inner .pct {
                    font-family: 'Sora', sans-serif;
                    font-size: 1.5rem;
                    font-weight: 800;
                    color: var(--ink);
                }
                .hero-progress-inner .lbl {
                    font-size: 0.75rem;
                    color: var(--muted);
                    font-weight: 600;
                }

                /* BENTO GRID */
                .bento-grid {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    grid-template-rows: auto auto;
                    gap: 20px;
                    margin-bottom: 32px;
                }
                .bento-item {
                    background: #fff;
                    border: 1px solid var(--hairline);
                    border-radius: 20px;
                    padding: 24px;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    position: relative;
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                    cursor: pointer;
                }
                .bento-item:hover {
                    transform: translateY(-4px);
                    box-shadow: 0 12px 24px rgba(0,0,0,0.08);
                    border-color: rgba(0,0,0,0.1);
                }
                .bento-main {
                    grid-column: span 2;
                    background: linear-gradient(135deg, var(--ink) 0%, #374151 100%);
                    color: #fff;
                    border: none;
                }
                .bento-main:hover {
                    box-shadow: 0 16px 32px rgba(17, 24, 39, 0.2);
                }
                .bento-main .bento-icon {
                    background: rgba(255,255,255,0.1);
                    color: #fff;
                }
                .bento-icon {
                    width: 48px;
                    height: 48px;
                    border-radius: 14px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.5rem;
                    margin-bottom: 16px;
                    background: var(--surface-soft);
                    color: var(--ink);
                }
                .bento-title {
                    font-family: 'Sora', sans-serif;
                    font-size: 1.25rem;
                    font-weight: 700;
                    margin-bottom: 8px;
                }
                .bento-main .bento-title {
                    font-size: 1.75rem;
                }
                .bento-desc {
                    font-size: 0.9rem;
                    color: var(--muted);
                    line-height: 1.5;
                    flex: 1;
                }
                .bento-main .bento-desc {
                    color: rgba(255,255,255,0.8);
                    font-size: 1rem;
                }
                .bento-action {
                    margin-top: 20px;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    font-weight: 600;
                    font-size: 0.9rem;
                }
                .bento-main .bento-action {
                    color: var(--brand-mint);
                }
                .bento-arrow {
                    transition: transform 0.2s;
                }
                .bento-item:hover .bento-arrow {
                    transform: translateX(4px);
                }

                /* Suggestion card specific */
                .bento-suggestion {
                    grid-column: span 1;
                    grid-row: span 2;
                    background: linear-gradient(to bottom, #fff, var(--surface-soft));
                }
                .bento-suggestion .advice-wrap {
                    margin-top: auto;
                    background: #fff;
                    border-radius: 12px;
                    padding: 16px;
                    border: 1px solid var(--hairline);
                    text-align: center;
                }
                .bento-suggestion .advice-num {
                    font-family: 'Sora', sans-serif;
                    font-size: 2.5rem;
                    font-weight: 800;
                    color: var(--brand-ochre);
                    line-height: 1;
                    margin: 8px 0 4px;
                }
                
                .bento-small {
                    grid-column: span 1;
                }
            </style>

            <div id="dash-global-warning"></div>

            <div class="hero-glass">
                <div class="hero-content">
                    <h1 class="hero-title">Chào mừng, {{ Auth::user()->fullName ?? Auth::user()->username }} 👋</h1>
                    <p class="hero-subtitle">Hôm nay là một ngày tuyệt vời để tiếp tục hành trình học tập của bạn.</p>
                    
                    <div class="hero-stats">
                        <div class="hero-stat-item">
                            <div class="hero-stat-value">
                                <span id="stat-earned-credits" style="font-size: 2rem; color: var(--ink); font-weight: 800; font-family: 'Sora', sans-serif;">0</span>
                                <span>/ <span id="dash-credit-total">{{ $totalCredits }}</span> TC</span>
                            </div>
                            <div class="hero-stat-label">Đã Tích Lũy</div>
                        </div>
                        <div class="hero-stat-item">
                            <div class="hero-stat-value">
                                <span id="kpi-gpa">--</span>
                            </div>
                            <div class="hero-stat-label">GPA Hiện Tại</div>
                        </div>
                        <div class="hero-stat-item">
                            <div class="hero-stat-value" id="stat-credits-per-sem" style="font-size: 1.5rem; margin-top: 6px;">--</div>
                            <div class="hero-stat-label">TC / Kỳ Còn Lại</div>
                        </div>
                    </div>
                </div>
                
                <div class="hero-progress-circle" id="hero-progress-circle" style="--prog-deg: 0deg;">
                    <div class="hero-progress-inner">
                        <div class="pct" id="kpi-progress">0%</div>
                        <div class="lbl">Hoàn thành</div>
                    </div>
                </div>
            </div>

            {{-- Widget Dự Báo Tốt Nghiệp — 3 Kịch Bản --}}
            <div id="grad-forecast-widget" style="display:none; background:#fff; border-radius:16px; border:1px solid var(--hairline); padding:20px 24px; margin-bottom:24px; box-shadow:0 4px 12px rgba(0,0,0,0.03);">

                {{-- Header --}}
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
                    <div>
                        <h3 style="font-family:'Sora',sans-serif; font-size:1.05rem; margin:0 0 4px 0; color:var(--ink); display:flex; align-items:center; gap:8px;">
                            🎓 Dự Báo Tốt Nghiệp
                            <span id="grad-status-badge" style="font-size:0.72rem; padding:3px 8px; border-radius:20px; font-weight:600; background:var(--surface-soft); color:var(--muted);">Đang tải...</span>
                        </h3>
                        <p id="grad-message" style="color:var(--muted); font-size:0.85rem; margin:0; line-height:1.5; max-width:560px;">...</p>
                    </div>
                    <div style="display:flex; gap:20px; text-align:right; flex-shrink:0;">
                        <div>
                            <div style="font-size:0.72rem; color:var(--muted); text-transform:uppercase; font-weight:600; letter-spacing:0.04em;">TC còn lại</div>
                            <div style="font-size:1.2rem; font-weight:700; color:var(--ink); font-family:'Sora',sans-serif;" id="grad-remaining-credits">-</div>
                        </div>
                        <div>
                            <div style="font-size:0.72rem; color:var(--muted); text-transform:uppercase; font-weight:600; letter-spacing:0.04em;">GPA hiện tại</div>
                            <div style="font-size:1.2rem; font-weight:700; color:var(--ink); font-family:'Sora',sans-serif;" id="grad-current-gpa">-</div>
                        </div>
                    </div>
                </div>

                {{-- 3 Kịch bản --}}
                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px;" id="grad-scenarios-grid">
                    {{-- Lạc quan --}}
                    <div style="border-radius:12px; border:2px solid #10b981; padding:14px; background:#f0fdf4;">
                        <div style="font-size:0.72rem; font-weight:700; color:#10b981; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:6px;">✅ Lạc quan</div>
                        <div style="font-family:'Sora',sans-serif; font-size:1.1rem; font-weight:800; color:var(--ink);" id="grad-opt-label">--</div>
                        <div style="font-size:0.78rem; color:var(--muted); margin-top:4px;" id="grad-opt-desc">--</div>
                        <div style="font-size:0.75rem; margin-top:8px; color:#059669;">GPA dự kiến: <strong id="grad-opt-gpa">--</strong></div>
                    </div>
                    {{-- Trung bình --}}
                    <div style="border-radius:12px; border:2px solid #3b82f6; padding:14px; background:#eff6ff;">
                        <div style="font-size:0.72rem; font-weight:700; color:#3b82f6; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:6px;">📊 Trung bình</div>
                        <div style="font-family:'Sora',sans-serif; font-size:1.1rem; font-weight:800; color:var(--ink);" id="grad-avg-label">--</div>
                        <div style="font-size:0.78rem; color:var(--muted); margin-top:4px;" id="grad-avg-desc">--</div>
                        <div style="font-size:0.75rem; margin-top:8px; color:#2563eb;">GPA dự kiến: <strong id="grad-avg-gpa">--</strong></div>
                    </div>
                    {{-- Rủi ro --}}
                    <div style="border-radius:12px; border:2px solid #ef4444; padding:14px; background:#fef2f2;">
                        <div style="font-size:0.72rem; font-weight:700; color:#ef4444; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:6px;">⚠️ Rủi ro</div>
                        <div style="font-family:'Sora',sans-serif; font-size:1.1rem; font-weight:800; color:var(--ink);" id="grad-pess-label">--</div>
                        <div style="font-size:0.78rem; color:var(--muted); margin-top:4px;" id="grad-pess-desc">--</div>
                        <div style="font-size:0.75rem; margin-top:8px; color:#dc2626;">GPA dự kiến: <strong id="grad-pess-gpa">--</strong></div>
                    </div>
                </div>

                {{-- Cảnh báo rủi ro --}}
                <div id="grad-risks-container" style="display:none; padding-top:12px; border-top:1px solid var(--hairline);">
                    <div style="font-size:0.78rem; font-weight:700; color:var(--ink); margin-bottom:8px;">⚠️ Cảnh báo học vụ</div>
                    <div id="grad-risks-list" style="display:flex; flex-direction:column; gap:6px;"></div>
                </div>

                {{-- Badge gợi ý đổi mode --}}
                <div id="grad-mode-suggestion" style="display:none; padding-top:10px; border-top:1px solid var(--hairline);"></div>
            </div>

            {{-- Warnings Panel --}}
            <div id="warnings-panel" style="display:none; margin-bottom:20px; background:#fff; border-radius:16px; border:1px solid #fde68a; padding:20px 24px; box-shadow:0 4px 12px rgba(234,179,8,0.08);">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span style="font-size:1.25rem;">⚠️</span>
                        <h3 style="font-family:'Sora',sans-serif; font-size:1rem; margin:0; color:var(--ink);">Cảnh Báo Học Vụ</h3>
                        <span id="warnings-count-badge" style="background:#fef3c7; color:#92400e; font-size:0.72rem; font-weight:700; padding:2px 8px; border-radius:20px;">0</span>
                    </div>
                    <button onclick="document.getElementById('warnings-panel').style.display='none'" style="background:none; border:none; color:var(--muted); cursor:pointer; font-size:1.1rem; line-height:1;">✕</button>
                </div>
                <div id="warnings-list" style="display:flex; flex-direction:column; gap:8px;"></div>
            </div>

            {{-- Bento Grid --}}
            <div class="bento-grid">
                
                {{-- Card Chính: Lập Kế Hoạch --}}
                <div class="bento-item bento-main" onclick="switchTab('study-plan', document.getElementById('nav-study-plan'))">
                    <div class="bento-icon" style="background: rgba(255,255,255,0.2);">🚀</div>
                    <div class="bento-title">Lập Kế Hoạch Đa Học Kỳ</div>
                    <div class="bento-desc">Trái tim của hệ thống. Xây dựng lộ trình học tập cá nhân hóa, dự báo điểm số và tự động rải môn thông minh để tốt nghiệp đúng hạn.</div>
                    <div class="bento-action">
                        Bắt đầu lập kế hoạch <span class="bento-arrow">→</span>
                    </div>
                </div>

                {{-- Card Gợi Ý (Chính phụ) --}}
                <div class="bento-item bento-suggestion" onclick="switchTab('study-plan', document.getElementById('nav-study-plan'))">
                    <div class="bento-icon">💡</div>
                    <div class="bento-title">Gợi Ý Môn Học</div>
                    <div class="bento-desc">Dựa trên thành tích và năng lực hiện tại của bạn.</div>
                    
                    <div class="advice-wrap">
                        <div id="dash-advice-badge-wrap">
                            <span class="dash-advice-badge maintain" id="dash-advice-badge"
                                  style="font-size: 0.75rem; display: flex; align-items: center; gap: 5px;">
                                <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:currentColor;opacity:.6;animation:pulse 1.5s infinite;"></span>
                                Đang phân tích học lực...
                            </span>
                        </div>
                        <div class="advice-num" id="dash-advice-num" style="min-height: 2.5rem; display:flex; align-items:center;">--</div>
                        <div style="font-size: 0.8rem; color: var(--muted); font-weight: 600; text-transform: uppercase;">tín chỉ / học kỳ</div>
                        <div style="font-size: 0.85rem; color: var(--ink); margin-top: 8px; line-height: 1.4;" id="dash-advice-reason">
                            Đang tải dữ liệu từ hệ thống...
                        </div>
                    </div>
                </div>

                {{-- Card Phụ: Phân Tích --}}
                <div class="bento-item bento-small" onclick="switchTab('analysis', document.getElementById('nav-analysis'))">
                    <div class="bento-icon">📊</div>
                    <div class="bento-title">Phân Tích & Biểu Đồ</div>
                    <div class="bento-desc">Xem đánh giá chuyên sâu theo kỹ năng và biểu đồ điểm.</div>
                    <div class="bento-action" style="color: var(--ink);">Xem chi tiết <span class="bento-arrow">→</span></div>
                </div>

                {{-- Card Phụ: Lịch Sử --}}
                <div class="bento-item bento-small" onclick="toggleHistoryDrawer()">
                    <div class="bento-icon">📚</div>
                    <div class="bento-title">Lịch Sử Học Tập</div>
                    <div class="bento-desc">Quản lý và xem lại các học kỳ đã hoàn tất.</div>
                    <div class="bento-action" style="color: var(--ink);">Mở lịch sử <span class="bento-arrow">→</span></div>
                </div>

            </div>
            
            <div style="display:none;">
                {{-- Elements needed for student-planner.js that are hidden but JS expects them to exist to read/write values --}}
                <span id="kpi-progress-sub"></span>
                <span id="kpi-semester"></span>
                <span id="kpi-credits"></span>
                <span id="dash-prog-pct"></span>
                <span id="dash-credit-earned"></span>
                <span id="dash-prog-fill"></span>
                <span id="dash-prog-left"></span>
                <span id="dash-prog-rem-sem"></span>
                <span id="dash-pass-credits"></span>
                <span id="dash-needed-per-sem"></span>
                <span id="dash-current-sem"></span>
                <span id="dash-strength-content"></span>
                <canvas id="gradeChart"></canvas>
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

            {{-- GPA Trend Line Chart --}}
            <div class="clay-card" style="margin-top:var(--sp-xl);">
                <div class="card-title-row">
                    <div class="card-heading">📈 Xu Hướng GPA Qua Các Học Kỳ</div>
                    <span id="gpa-trend-badge" style="font-size:0.75rem; padding:3px 10px; border-radius:20px; font-weight:600; background:var(--surface-soft); color:var(--muted);">Đang tải...</span>
                </div>
                <p id="gpa-trend-message" style="color:var(--muted); font-size:0.85rem; margin:0 0 16px 0;"></p>
                <div id="gpa-trend-empty" style="text-align:center; padding:40px; color:var(--muted);">
                    <div style="font-size:2rem; margin-bottom:8px;">📉</div>
                    <p>Chưa có lịch sử học kỳ nào. Hoàn thành một học kỳ để xem biểu đồ xu hướng GPA.</p>
                </div>
                <div style="position:relative; height:260px; display:none;" id="gpa-trend-chart-wrap">
                    <canvas id="gpaTrendChart"></canvas>
                </div>
            </div>

            {{-- Skill Focus Progress Card --}}
            <div id="skill-focus-card" style="display:none; margin-top:var(--sp-xl);">
                <div class="clay-card">
                    <div class="card-title-row">
                        <div class="card-heading">🎯 Tiến Độ Định Hướng Kỹ Năng</div>
                        <span id="skill-focus-label-badge" style="font-size:0.75rem; padding:3px 10px; border-radius:20px; font-weight:600; background:#dbeafe; color:#1d4ed8;"></span>
                    </div>
                    <div id="skill-focus-content" style="display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:center;">
                        <div>
                            <div style="margin-bottom:12px;">
                                <div style="font-size:0.8rem; color:var(--muted); margin-bottom:4px;">Tiến độ hoàn thành</div>
                                <div style="height:8px; background:var(--surface-soft); border-radius:4px; overflow:hidden;">
                                    <div id="skill-focus-bar" style="height:100%; background:var(--brand-mint); border-radius:4px; width:0%; transition:width 0.6s ease;"></div>
                                </div>
                                <div style="display:flex; justify-content:space-between; margin-top:4px;">
                                    <span style="font-size:0.78rem; color:var(--muted);" id="skill-focus-passed">0 / 0 môn</span>
                                    <span style="font-size:0.78rem; font-weight:700; color:var(--ink);" id="skill-focus-pct">0%</span>
                                </div>
                            </div>
                            <div>
                                <div style="font-size:0.8rem; color:var(--muted); margin-bottom:4px;">GPA trung bình nhóm định hướng</div>
                                <div style="font-family:'Sora',sans-serif; font-size:2rem; font-weight:800; color:var(--ink);" id="skill-focus-gpa">--</div>
                            </div>
                        </div>
                        <div id="skill-focus-message" style="font-size:0.88rem; color:var(--muted); line-height:1.7; background:var(--surface-soft); border-radius:12px; padding:16px;">
                            Đang phân tích...
                        </div>
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
                
                <!-- Cột 1: Quản Lý Chế Độ / Tạo Kế Hoạch -->
                <div class="clay-card">
                    <!-- Khối: Tạo Kế Hoạch Mới (Khi chưa có kế hoạch) -->
                    <div id="plan-creation-wizard">
                        <div class="card-title-row">
                            <div class="card-heading">⚙️ Tạo Kế Hoạch Mới</div>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:16px;">
                            <div>
                                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Chế độ học (Mode)</label>
                                <select id="planner-mode" class="clay-select" style="width: 100%;">
                                    <option value="normal" selected>Bình thường (8 kỳ - 4 năm)</option>
                                    <option value="fast">Nhanh (6 kỳ - 3 năm)</option>
                                    <option value="slow">Chậm (10 kỳ - 5 năm)</option>
                                </select>
                            </div>
                            <div>
                                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Tên kế hoạch <span style="color:var(--red);">*</span></label>
                                <input type="text" id="planner-name" class="ob-grade-input"
                                    placeholder="Ví dụ: Kế hoạch ra trường sớm"
                                    style="width: 100%; text-align:left; padding:0 12px; height:42px;">
                            </div>
                            <button class="btn-primary" onclick="generateStudyPlan()" style="height: 42px; width:100%; justify-content:center; margin-top:8px;">✨ Tạo kế hoạch</button>
                        </div>
                    </div>

                    <!-- Khối: Đổi Chế Độ (Khi đã có kế hoạch active) -->
                    <div id="plan-mode-switcher" style="display:none;">
                        <div class="card-title-row">
                            <div class="card-heading">🎛️ Điều Chỉnh Cường Độ Học</div>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 16px;">Bạn đã có kế hoạch đang hoạt động. Bạn có thể đổi cường độ học để hệ thống tự động rải lại môn.</p>
                        
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <label style="display:flex; align-items:center; gap:12px; padding: 12px; border: 1px solid var(--hairline); border-radius: 8px; cursor: pointer;" class="mode-radio-label">
                                <input type="radio" name="change_mode" value="slow" style="width: 18px; height: 18px;">
                                <div>
                                    <div style="font-weight: 600; color: var(--ink);">🌱 Học Nhẹ (~12-14 TC/kỳ)</div>
                                    <div style="font-size: 0.8rem; color: var(--muted);">Giảm áp lực, kéo dài thời gian</div>
                                </div>
                            </label>
                            
                            <label style="display:flex; align-items:center; gap:12px; padding: 12px; border: 1px solid var(--hairline); border-radius: 8px; cursor: pointer;" class="mode-radio-label">
                                <input type="radio" name="change_mode" value="normal" style="width: 18px; height: 18px;">
                                <div>
                                    <div style="font-weight: 600; color: var(--ink);">⚖️ Cân Bằng (~15-18 TC/kỳ)</div>
                                    <div style="font-size: 0.8rem; color: var(--muted);">Tốt nghiệp đúng hạn chuẩn</div>
                                </div>
                            </label>
                            
                            <label style="display:flex; align-items:center; gap:12px; padding: 12px; border: 1px solid var(--hairline); border-radius: 8px; cursor: pointer;" class="mode-radio-label">
                                <input type="radio" name="change_mode" value="fast" style="width: 18px; height: 18px;">
                                <div>
                                    <div style="font-weight: 600; color: var(--ink);">🚀 Tăng Tốc (~20-25 TC/kỳ)</div>
                                    <div style="font-size: 0.8rem; color: var(--muted);">Tốt nghiệp sớm hơn</div>
                                </div>
                            </label>
                        </div>
                        
                        <button class="btn-primary" onclick="changeActivePlanMode()" style="height: 42px; width:100%; justify-content:center; margin-top:16px;">🔄 Cập nhật lộ trình</button>
                    </div>

                    <div id="planner-loader" class="loader" style="display:none; text-align:center; padding:20px;">
                        <div class="spinner" style="margin:0 auto;"></div>
                        <p style="color:var(--muted);font-size:0.9rem;margin-top:10px;">Hệ thống đang chạy thuật toán tái phân bổ...</p>
                    </div>
                </div>

                <!-- Cột Danh Sách Kế Hoạch -->
                <div class="clay-card">
                    <div class="card-title-row">
                        <div class="card-heading">
                            📂 Kế Hoạch Của Bạn
                        </div>
                    </div>
                    <div id="inline-saved-plans-list" style="display:flex; flex-direction:column; gap:12px; max-height:400px; overflow-y:auto; padding-right:8px;">
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