let fetchTimer = null;
let saveTimer = null;
let prefTimer = null;
let currentCourses = [];
try { const saved = localStorage.getItem('current_courses'); if (saved) currentCourses = JSON.parse(saved); } catch (e) { }
let syncLock = false;

// ─── Tab Switching ────────────────────────────────────────────────────────
const TAB_TITLES = {
    dashboard: { title: 'Dashboard', sub: 'Tổng quan tiến độ học tập của bạn' },
    suggestions: { title: 'Đề Xuất Môn Học', sub: 'Gợi ý môn học phù hợp với tiến độ của bạn' },
    analysis: { title: 'Phân Tích & Biểu Đồ', sub: 'So sánh điểm và phân tích theo nhóm kỹ năng' },
    courses: { title: 'Môn Đang Học', sub: 'Quản lý môn học trong học kỳ hiện tại' },
    planner: { title: 'Lập Kế Hoạch Đa Học Kỳ', sub: 'Hệ thống tự động rải môn học cho các học kỳ' },
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
    if (tabId === 'analysis') {
        if (chartRawData) { try { renderGradeChartDetail(chartRawData, 'all'); } catch (e) {} }
        fetchGpaTrend();
        fetchSkillFocusProgress();
    }
    if (tabId === 'planner') {
        clearTimeout(fetchTimer);
        fetchTimer = setTimeout(fetchSuggestions, 100);
        fetchSavedPlansList();
    }
}

// ─── Onboarding State ────────────────────────────────────────────────────

function getCurrentSemester() {
    let maxSem = 0;
    const grades = {};
    document.querySelectorAll('.grade-input').forEach(input => {
        if (input.value !== '') {
            grades[parseInt(input.dataset.subjectId)] = parseFloat(input.value);
        }
    });
    if (typeof currentCourses !== 'undefined') {
        currentCourses.forEach(c => {
            if (c.grade !== null) grades[c.id] = c.grade;
        });
    }

    if (typeof SUBJECTS_BY_SEM !== 'undefined') {
        for (const [semName, subjects] of Object.entries(SUBJECTS_BY_SEM)) {
            const semNum = parseInt(semName);
            for (const sub of subjects) {
                if (grades[sub.id] !== undefined) {
                    if (!isNaN(semNum) && semNum > maxSem) {
                        maxSem = semNum;
                    }
                }
            }
        }
    }
    return maxSem + 1;
}
let obStep = 0;
let obData = { academic_year: null, program_type: null, target_semesters: 8, grades: {} };

const OB_STEPS = [
    { label: 'Bước 1 / 2', icon: '🎓', iconBg: '#f5f0e0', title: 'Chào mừng bạn!', desc: 'Hãy cho chúng tôi biết bạn đang theo học chương trình nào để hệ thống gợi ý chính xác nhất.' },
    { label: 'Bước 2 / 2', icon: '🏁', iconBg: '#f0fdf4', title: 'Mục tiêu tốt nghiệp', desc: 'Bạn muốn hoàn thành chương trình trong bao nhiêu năm? Hệ thống sẽ phân bổ tín chỉ phù hợp.' },
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
        const yearOpts = ACADEMIC_YEARS.map(y => `<option value="${y}" ${obData.academic_year === y ? 'selected' : ''}>${y}</option>`).join('');
        const typeOpts = PROGRAM_TYPES.map(t => `<option value="${t}" ${obData.program_type === t ? 'selected' : ''}>${t}</option>`).join('');
        body.innerHTML = `<div class="ob-form-grid">
                <div class="ob-input-group"><label>Niên khóa</label><select class="ob-select" id="ob-academic-year" onchange="obData.academic_year=this.value"><option value="">-- Chọn niên khóa --</option>${yearOpts}</select></div>
                <div class="ob-input-group"><label>Hệ đào tạo</label><select class="ob-select" id="ob-program-type" onchange="obData.program_type=this.value"><option value="">-- Chọn hệ đào tạo --</option>${typeOpts}</select></div>
            </div>`;
    } else if (obStep === 1) {
        const goals = [
            { sems: 6,  label: '3 Năm',   sub: '6 học kỳ · Rút ngắn tối đa' },
            { sems: 7,  label: '3.5 Năm', sub: '7 học kỳ · Nhanh hơn chuẩn' },
            { sems: 8,  label: '4 Năm',   sub: '8 học kỳ · Chuẩn chương trình', recommended: true },
            { sems: 9,  label: '4.5 Năm', sub: '9 học kỳ · Nhẹ nhàng hơn' },
            { sems: 10, label: '5 Năm',   sub: '10 học kỳ · Thoải mái nhất' },
        ];
        body.innerHTML = `<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; padding:4px 0;">
            ${goals.map(g => {
                const tcEst = Math.ceil(130 / g.sems);
                const isSel = obData.target_semesters === g.sems;
                return `<div onclick="obSelectGoal(${g.sems},this)"
                    style="padding:14px 12px; border-radius:10px; border:2px solid ${isSel ? 'var(--ink)' : 'var(--hairline)'}; background:${isSel ? 'var(--surface-card)' : 'var(--canvas)'}; cursor:pointer; text-align:center; transition:all 0.15s; user-select:none;"
                    onmouseover="if(!this.classList.contains('ob-goal-sel'))this.style.background='var(--surface-soft)'"
                    onmouseout="if(!this.classList.contains('ob-goal-sel'))this.style.background='var(--canvas)'"
                    class="ob-goal-card ${isSel ? 'ob-goal-sel' : ''}">
                    <div style="font-weight:700; font-size:0.95rem; color:var(--ink);">${g.label}</div>
                    <div style="font-size:0.75rem; color:var(--muted); margin-top:3px;">${g.sub}</div>
                    ${g.recommended ? '<div style="font-size:0.7rem; color:#16a34a; font-weight:700; margin-top:5px;">✓ Khuyến nghị</div>' : ''}
                    <div style="font-size:0.78rem; color:var(--muted); margin-top:6px; padding-top:6px; border-top:1px solid var(--hairline);">~${tcEst} TC/kỳ</div>
                </div>`;
            }).join('')}
        </div>`;
    }

    const btnBack = document.getElementById('ob-btn-back');
    const btnNext = document.getElementById('ob-btn-next');
    const progText = document.getElementById('ob-progress-text');
    btnBack.disabled = obStep === 0;
    progText.textContent = `Bước ${obStep + 1} / 2`;
    if (obStep === 1) { btnNext.textContent = '🎉 Hoàn thành!'; btnNext.className = 'ob-btn-next finish'; }
    else { btnNext.innerHTML = 'Tiếp theo →'; btnNext.className = 'ob-btn-next'; }
}

function obSelectSem(i, el) { obData.current_semester = i; document.querySelectorAll('.ob-sem-btn').forEach(b => b.classList.remove('selected')); el.classList.add('selected'); }
function obSelectYear(y, el) { obData.target_years = y; document.querySelectorAll('.ob-year-btn').forEach(b => b.classList.remove('selected')); el.classList.add('selected'); }
function obSelectGoal(sems, el) {
    obData.target_semesters = sems;
    document.querySelectorAll('.ob-goal-card').forEach(c => {
        c.classList.remove('ob-goal-sel');
        c.style.border = '2px solid var(--hairline)';
        c.style.background = 'var(--canvas)';
    });
    el.classList.add('ob-goal-sel');
    el.style.border = '2px solid var(--ink)';
    el.style.background = 'var(--surface-card)';
}

function obNext() {
    if (obStep === 0) { const yr = document.getElementById('ob-academic-year')?.value; const pt = document.getElementById('ob-program-type')?.value; if (!yr || !pt) { showToast('Vui lòng chọn đầy đủ niên khóa và hệ đào tạo!', 'error'); return; } obData.academic_year = yr; obData.program_type = pt; }
    if (obStep === 1) { obFinish(); return; }
    obStep++; renderObDots(); renderObHeader(); renderObBody();
}

function obPrev() { if (obStep === 0) return; obStep--; renderObDots(); renderObHeader(); renderObBody(); }

async function obFinish() {
    const btnNext = document.getElementById('ob-btn-next');
    btnNext.disabled = true; btnNext.textContent = '⏳ Đang lưu...';
    try {
        await fetch('/preferences/save', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }, body: JSON.stringify({ academic_year: obData.academic_year, program_type: obData.program_type, target_semesters: obData.target_semesters }) });
        closeOnboarding(); applyPreferencesToUI(obData);
        showToast('Chào mừng! Đã thiết lập chương trình của bạn 🎉', 'success');
    } catch (err) { showToast('Có lỗi xảy ra, vui lòng thử lại!', 'error'); btnNext.disabled = false; btnNext.textContent = '🎉 Hoàn thành!'; }
}

function openOnboarding() { obStep = 0; document.getElementById('ob-overlay').classList.remove('hidden'); renderObDots(); renderObHeader(); renderObBody(); }
function closeOnboarding() { document.getElementById('ob-overlay').classList.add('hidden'); }

function applyPreferencesToUI(data) {
    if (data.academic_year) document.getElementById('academic_year').value = data.academic_year;
    if (data.program_type) document.getElementById('program_type').value = data.program_type;

    Object.entries(data.grades).forEach(([sid, grade]) => { const input = document.getElementById(`grade-${sid}`); if (input) { input.value = grade; onGradeChange(parseInt(sid), input, true); } });
    document.getElementById('config-dot')?.remove();
    updateCreditStats(); fetchSuggestions();
}

// ═══════════════════════════════════════════════════════════════
// CONFIG PANEL
// ═══════════════════════════════════════════════════════════════
function toggleConfigPanel() {
    const panel = document.getElementById('config-panel'); const overlay = document.getElementById('config-overlay');
    const isOpen = panel.classList.contains('open');
    if (isOpen) { panel.classList.remove('open'); overlay.classList.remove('open'); }
    else { panel.classList.add('open'); overlay.classList.add('open'); }
}
function closeConfigPanel() { document.getElementById('config-panel').classList.remove('open'); document.getElementById('config-overlay').classList.remove('open'); }

// ═══════════════════════════════════════════════════════════════
// GRADE DRAWER
// ═══════════════════════════════════════════════════════════════
function toggleGradeDrawer() {
    const drawer = document.getElementById('grade-drawer'); const overlay = document.getElementById('grade-drawer-overlay');
    const isOpen = drawer.classList.contains('open');
    if (isOpen) { drawer.classList.remove('open'); overlay.classList.remove('open'); }
    else { drawer.classList.add('open'); overlay.classList.add('open'); const s = document.getElementById('grade-search'); if (s) { s.value = ''; filterGradeSearch(''); } }
}
function closeGradeDrawer() {
    document.getElementById('grade-drawer').classList.remove('open');
    document.getElementById('grade-drawer-overlay').classList.remove('open');
    clearTimeout(fetchTimer); fetchTimer = setTimeout(fetchSuggestions, 300);
}
function filterGradeSearch(query) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll('.drawer-subject-card').forEach(card => { card.classList.toggle('hidden-search', q !== '' && !card.dataset.name?.includes(q)); });
    document.querySelectorAll('.drawer-sem-group').forEach(group => { const visible = group.querySelectorAll('.drawer-subject-card:not(.hidden-search)').length > 0; group.style.display = visible ? '' : 'none'; });
}

function updateDrawerStats() {
    let pass = 0, fail = 0, empty = 0;
    document.querySelectorAll('.grade-input').forEach(input => { const val = parseFloat(input.value); if (input.value === '' || isNaN(val)) empty++; else if (val >= 5.0) pass++; else fail++; });
    const passEl = document.getElementById('drawer-pass-count'); const failEl = document.getElementById('drawer-fail-count'); const emptyEl = document.getElementById('drawer-empty-count');
    if (passEl) passEl.textContent = pass; if (failEl) failEl.textContent = fail; if (emptyEl) emptyEl.textContent = empty;
    const badge = document.getElementById('grade-count-badge');
    if (badge) { const filled = pass + fail; badge.textContent = filled; badge.classList.toggle('visible', filled > 0); }
}

// ═══════════════════════════════════════════════════════════════
// PREFERENCES
// ═══════════════════════════════════════════════════════════════
function savePreferences() {
    clearTimeout(prefTimer);
    prefTimer = setTimeout(async () => {
        try {
            const skillFocusEl = document.getElementById('skill_focus');
            const payload = { academic_year: document.getElementById('academic_year').value, program_type: document.getElementById('program_type').value, current_courses: currentCourses, skill_focus: skillFocusEl ? skillFocusEl.value : null };
            const res = await fetch('/preferences/save', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }, body: JSON.stringify(payload) });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            showSaveIndicator('saved', 'Đã lưu cấu hình ✓');
        } catch (err) { showSaveIndicator('error', 'Lưu cấu hình thất bại'); }
    }, 500);
}

async function loadPreferences() {
    try { const res = await fetch('/preferences', { headers: { 'Accept': 'application/json' } }); if (!res.ok) return null; return await res.json(); }
    catch (err) { console.warn('[Preference load error]', err); return null; }
}

// ═══════════════════════════════════════════════════════════════
// SAVE INDICATOR
// ═══════════════════════════════════════════════════════════════
function showSaveIndicator(state, msg) {
    const el = document.getElementById('save-indicator'); if (!el) return;
    el.className = 'save-indicator';
    if (state === 'hide') { el.style.display = 'none'; return; }
    const icons = { saving: '💾', saved: '✓', error: '⚠️' };
    const texts = { saving: 'Đang lưu...', saved: 'Đã lưu', error: 'Lưu thất bại' };
    el.classList.add(state); el.textContent = `${icons[state]} ${msg || texts[state]}`;
    if (state === 'saved') setTimeout(() => showSaveIndicator('hide'), 2500);
}

// ═══════════════════════════════════════════════════════════════
// GRADE SAVE / LOAD
// ═══════════════════════════════════════════════════════════════
function autoSaveGrade(subjectId, grade) {
    clearTimeout(saveTimer); showSaveIndicator('saving');
    saveTimer = setTimeout(async () => {
        try {
            const res = await fetch('/grades/save', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }, body: JSON.stringify([{ subject_id: subjectId, grade }]) });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            showSaveIndicator('saved'); scheduleChartRefresh();
            fetchWarnings(); // Cập nhật cảnh báo sau khi lưu điểm

            // Nếu rớt môn → phân tích hiệu ứng dây chuyền
            if (grade !== null && grade < 5.0) {
                const card = document.getElementById(`lbl-sub-${subjectId}`);
                const nameEl = card?.querySelector('.drawer-subject-name');
                const subjectName = nameEl?.textContent?.trim() || `Môn #${subjectId}`;
                // Hiển thị sau 0.5s để không chồng lên toast
                setTimeout(() => openCascadeModal(subjectId, subjectName), 500);
            }
        } catch (err) { showSaveIndicator('error'); }
    }, 800);
}

async function saveMultipleGrades(grades) {
    if (!grades || grades.length === 0) return;
    showSaveIndicator('saving', `Đang lưu ${grades.length} môn...`);
    try {
        const res = await fetch('/grades/save', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }, body: JSON.stringify(grades) });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        showSaveIndicator('saved', `Đã lưu ${grades.length} môn ✓`);
    } catch (err) { showSaveIndicator('error', 'Lưu điểm thất bại'); }
}

let globalUserGrades = {};

async function loadGradesFromDB() {
    try {
        const res = await fetch('/grades', { headers: { 'Accept': 'application/json' } }); if (!res.ok) return;
        const grades = await res.json();
        globalUserGrades = {};
        grades.forEach(({ subject_id, grade }) => { 
            if (grade !== null && grade !== undefined) {
                globalUserGrades[subject_id] = grade;
            }
            const input = document.getElementById(`grade-${subject_id}`); 
            if (!input) return; 
            if (grade !== null && grade !== undefined) { 
                input.value = grade; 
                onGradeChange(subject_id, input, true); 
            } 
        });
        updateEarnedCredits(); updateDrawerStats();
    } catch (err) { console.warn('[Grade load error]', err); }
}

// ═══════════════════════════════════════════════════════════════
// CREDIT STATS
// ═══════════════════════════════════════════════════════════════
function updateCreditStats() {
    document.getElementById('stat-total-semesters').textContent = 8;
    updateEarnedCredits();

    // Update KPI card
    const curSem = getCurrentSemester();
    if (curSem) document.getElementById('kpi-semester').textContent = `HK ${curSem}`;
}

function updateEarnedCredits() {
    let earned = 0;
    document.querySelectorAll('.grade-input').forEach(input => { const val = parseFloat(input.value); if (!isNaN(val) && val >= 5.0) earned += parseInt(input.dataset.credits || 0); });
    currentCourses.forEach(c => { if (c.grade !== null && c.grade >= 5.0) earned += (c.credits || 0); });
    document.getElementById('stat-earned-credits').textContent = earned;

    const totalSem = 8;
    const currentSem = getCurrentSemester();
    const remaining = Math.max(0, TOTAL_CREDITS - earned);
    const remSem = Math.max(1, totalSem - (currentSem - 1));
    const perSem = remaining === 0 ? 0 : Math.ceil(remaining / remSem);
    document.getElementById('stat-credits-per-sem').textContent = perSem;

    // Update KPI cards
    document.getElementById('kpi-credits').textContent = earned;
    const progPct = Math.min(100, Math.round((earned / TOTAL_CREDITS) * 100));
    document.getElementById('kpi-progress').textContent = progPct + '%';
    document.getElementById('kpi-progress-sub').textContent = `${earned} / ${TOTAL_CREDITS} TC hoàn thành`;
    
    // Cập nhật biểu đồ tròn trên Hero Section
    const heroCircle = document.getElementById('hero-progress-circle');
    if (heroCircle) {
        heroCircle.style.setProperty('--prog-deg', `${progPct * 3.6}deg`);
    }

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
function onGradeChange(id, input, skipSave = false) {
    const rawVal = parseFloat(input.value);
    if (!isNaN(rawVal)) { if (rawVal > 10) input.value = 10; else if (rawVal < 0) input.value = 0; }
    const card = document.getElementById(`lbl-sub-${id}`);
    const status = document.getElementById(`status-${id}`);
    const val = parseFloat(input.value);
    const isInDrawer = input.classList.contains('drawer-grade-input');

    if (card) { card.classList.remove('pass', 'fail'); if (!isNaN(val) && val >= 5.0) card.classList.add('pass'); else if (!isNaN(val) && val < 5.0 && input.value !== '') card.classList.add('fail'); }
    input.classList.remove('is-pass', 'is-fail');

    if (status) {
        status.classList.remove('pass', 'fail', 'empty');
        if (input.value === '' || isNaN(val)) { status.textContent = isInDrawer ? '—' : 'Chưa nhập'; status.classList.add('empty'); }
        else if (val >= 5.0) { input.classList.add('is-pass'); status.textContent = '✓ Pass'; status.classList.add('pass'); }
        else { input.classList.add('is-fail'); status.textContent = '✗ Fail'; status.classList.add('fail'); }
    }

    if (!syncLock) { syncLock = true; const ccInput = document.getElementById(`cc-grade-${id}`); if (ccInput && ccInput.value !== input.value) { ccInput.value = input.value; onCCGradeChange(id, ccInput); } syncLock = false; }
    if (!skipSave) { const gradeValue = isNaN(val) ? null : val; autoSaveGrade(id, gradeValue); }
    updateEarnedCredits(); updateDrawerStats();
}

function onCCGradeChange(id, input) {
    const rawVal = parseFloat(input.value);
    if (!isNaN(rawVal)) { if (rawVal > 10) input.value = 10; else if (rawVal < 0) input.value = 0; }
    const val = parseFloat(input.value);
    const item = document.getElementById(`cc-item-${id}`);
    const status = document.getElementById(`cc-status-${id}`);
    item.classList.remove('cc-pass', 'cc-fail'); input.classList.remove('is-pass', 'is-fail'); status.classList.remove('pass', 'fail', 'empty');
    const course = currentCourses.find(c => c.id == id); if (course) course.grade = isNaN(val) ? null : val;
    if (input.value === '' || isNaN(val)) { status.textContent = '—'; status.classList.add('empty'); }
    else if (val >= 5.0) { item.classList.add('cc-pass'); input.classList.add('is-pass'); status.textContent = 'Pass'; status.classList.add('pass'); }
    else { item.classList.add('cc-fail'); input.classList.add('is-fail'); status.textContent = 'Fail'; status.classList.add('fail'); }
    localStorage.setItem('current_courses', JSON.stringify(currentCourses));
    savePreferences();
    updateCompleteButton(); updateEarnedCredits();
}

// ═══════════════════════════════════════════════════════════════
// CURRENT COURSES
// ═══════════════════════════════════════════════════════════════
function updateCompleteButton() {
    const btn = document.getElementById('btn-complete'); if (!btn) return;
    const allFilled = currentCourses.length > 0 && currentCourses.every(c => c.grade !== null && c.grade !== undefined);
    btn.disabled = !allFilled;
}

function addToCurrentCourses(subject) {
    if (currentCourses.find(c => c.id == subject.id)) return;
    currentCourses.push({ id: subject.id, name: subject.name, credits: subject.credits, semesterName: subject.semester?.name || '?', grade: null });

    let autoAdded = [];
    if (subject.corequisites_info && subject.corequisites_info.length > 0) {
        subject.corequisites_info.forEach(coreq => {
            if (!currentCourses.find(c => c.id == coreq.id)) {
                currentCourses.push({ id: coreq.id, name: coreq.name, credits: coreq.credits, semesterName: '?', grade: null });
                autoAdded.push(coreq);
            }
        });
    }

    renderCurrentCourses();

    const btn = document.getElementById(`btn-add-${subject.id}`);
    if (btn) { btn.innerHTML = '✓ Đã thêm'; btn.classList.add('added'); }
    lockLeftInput(subject.id);

    if (autoAdded.length > 0) {
        autoAdded.forEach(coreq => {
            const cbtn = document.getElementById(`btn-add-${coreq.id}`);
            if (cbtn) { cbtn.innerHTML = '✓ Đã thêm'; cbtn.classList.add('added'); }
            lockLeftInput(coreq.id);
        });
        showToast(`Đã tự động thêm môn song hành: ${autoAdded.map(a => a.name).join(', ')}`, 'info');
    }

    const navBadge = document.getElementById('nav-cc-badge');
    if (navBadge) { navBadge.textContent = currentCourses.length; navBadge.classList.toggle('visible', currentCourses.length > 0); }

    clearTimeout(fetchTimer);
    fetchTimer = setTimeout(() => {
        saveMultipleGrades(currentCourses.map(c => ({ subject_id: c.id, grade: c.grade })));
    }, 1000);
}

function removeCourse(id) {
    currentCourses = currentCourses.filter(c => c.id != id); renderCurrentCourses();
    const btn = document.getElementById(`btn-add-${id}`); if (btn) { btn.innerHTML = '+ Thêm'; btn.classList.remove('added'); }
    unlockLeftInput(id);
    clearTimeout(fetchTimer); fetchTimer = setTimeout(fetchSuggestions, 400); updateEarnedCredits();
    const navBadge = document.getElementById('nav-cc-badge');
    if (navBadge) { navBadge.textContent = currentCourses.length; navBadge.classList.toggle('visible', currentCourses.length > 0); }
}

function lockLeftInput(id) {
    const input = document.getElementById(`grade-${id}`); if (!input) return;
    input.classList.add('is-studying');
    const wrap = input.parentElement;
    if (wrap) { wrap.classList.add('is-locked'); if (!wrap.querySelector('.studying-label')) { const badge = document.createElement('span'); badge.className = 'studying-label'; badge.innerHTML = '📖 Đang học'; wrap.appendChild(badge); } }
    const status = document.getElementById(`status-${id}`); if (status) { status.dataset.prevText = status.textContent; status.textContent = ''; }
}

function unlockLeftInput(id) {
    const input = document.getElementById(`grade-${id}`); if (!input) return;
    input.classList.remove('is-studying');
    const wrap = input.parentElement; if (wrap) { wrap.classList.remove('is-locked'); const badge = wrap.querySelector('.studying-label'); if (badge) badge.remove(); }
    onGradeChange(id, input);
}

function renderCurrentCourses() {
    localStorage.setItem('current_courses', JSON.stringify(currentCourses));
    savePreferences();
    const container = document.getElementById('current-courses-list');
    const counter = document.getElementById('cc-count'); counter.textContent = currentCourses.length;
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
    if (currentCourses.length === 0) { container.innerHTML = '<div class="current-courses-empty">Chưa có môn nào — vào <strong>Đề Xuất Môn Học</strong> và nhấn <strong>+ Thêm</strong>.</div>'; return; }
    container.innerHTML = currentCourses.map(c => `
            <div class="course-item${c.grade !== null && c.grade >= 5 ? ' cc-pass' : c.grade !== null ? ' cc-fail' : ''}" id="cc-item-${c.id}">
                <div class="course-info">
                    <span class="course-name">${c.name}</span>
                    <span class="course-meta">${c.credits} tín chỉ · Học kỳ chuẩn ${c.semesterName}</span>
                </div>
                <div class="course-right">
                    <input type="number" class="grade-input-clay${c.grade !== null && c.grade >= 5 ? ' is-pass' : c.grade !== null ? ' is-fail' : ''}"
                           id="cc-grade-${c.id}" min="0" max="10" step="0.1" placeholder="Điểm"
                           value="${c.grade !== null ? c.grade : ''}"
                           oninput="onCCGradeChange(${c.id},this)">
                    <span class="grade-status-clay ${c.grade !== null && c.grade >= 5 ? 'pass' : c.grade !== null ? 'fail' : 'empty'}" id="cc-status-${c.id}">${c.grade !== null && c.grade >= 5 ? 'Pass' : c.grade !== null ? 'Fail' : '—'}</span>
                    <button class="btn-remove-clay" onclick="removeCourse(${c.id})" title="Xóa">✕</button>
                </div>
            </div>`).join('');
}

// ═══════════════════════════════════════════════════════════════
// SUGGESTIONS
// ═══════════════════════════════════════════════════════════════
function getPassedSubjectIds() {
    const passed = new Set();
    document.querySelectorAll('.grade-input').forEach(input => { const val = parseFloat(input.value); if (!isNaN(val) && val >= 5.0) passed.add(input.dataset.subjectId); });
    currentCourses.forEach(c => { if (c.grade !== null && c.grade >= 5.0) passed.add(String(c.id)); });
    return [...passed].join(',');
}

function getFailedSubjectIds() {
    const failed = new Set();
    document.querySelectorAll('.grade-input').forEach(input => { const val = parseFloat(input.value); if (!isNaN(val) && val > 0 && val < 5.0) failed.add(parseInt(input.dataset.subjectId)); });
    currentCourses.forEach(c => { if (c.grade !== null && c.grade > 0 && c.grade < 5.0) failed.add(c.id); });
    return failed;
}

async function fetchSuggestions() {
    const semester = getCurrentSemester();
    const loader = document.getElementById('loader');
    const suggestionsContainer = document.getElementById('suggestions-list');
    loader.style.display = 'flex'; suggestionsContainer.style.opacity = '0.3';
    try {
        const url = `/api/v1/recommendations`;
        const response = await fetch(url); if (!response.ok) throw new Error('API error');
        const resData = await response.json();
        let mappedData = (resData.data || []).map(item => ({
            ...item.subject,
            suggestion_score: item.score,
            skill_evaluation: item.reasons.join(', '),
            is_failed_subject: item.is_failed || false,
        }));

        // Lấy giới hạn TC từ active plan
        const maxCredits = window.currentActivePlan?.tc_per_sem || 18;

        // Tách môn rớt ra — ưu tiên tuyệt đối, hiển thị đầu danh sách
        const retakeSubjs  = mappedData.filter(s => s.is_failed_subject);
        const regularSubjs = mappedData.filter(s => !s.is_failed_subject && s.can_study !== false);

        // Môn rớt chiếm credit trước; môn mới chỉ lấp phần còn lại trong giới hạn
        const retakeCredits  = retakeSubjs.reduce((sum, s) => sum + (parseInt(s.credits) || 3), 0);
        const remainingSlots = Math.max(0, maxCredits - retakeCredits);

        let currentTotal = 0;
        const limitedRegular = [];
        for (const subj of regularSubjs) {
            const cr = parseInt(subj.credits) || 3;
            if (currentTotal + cr <= remainingSlots) {
                limitedRegular.push(subj);
                currentTotal += cr;
            }
        }

        mappedData = [...retakeSubjs, ...limitedRegular];
        window.currentSuggestions = mappedData;
        window._suggestionMeta = { retakeCredits, regularCredits: currentTotal, maxCredits };

        renderSuggestions(mappedData, semester);

        if (window.currentActivePlan) {
            renderStudyPlan(window.currentActivePlan);
        }

        fetchProgress();
    } catch (error) {
        suggestionsContainer.innerHTML = `<div class="empty-state"><p style="color:var(--error);font-weight:600;">⚠️ Đã có lỗi xảy ra khi phân tích dữ liệu.</p></div>`;
    } finally { loader.style.display = 'none'; suggestionsContainer.style.opacity = '1'; }
}

async function fetchProgress() {
    try {
        const response = await fetch('/api/v1/progress');
        if (!response.ok) return;
        const resData = await response.json();
        if (resData.success && resData.data) {
            const prog = resData.data.progress;
            const warnings = resData.data.warnings;

            const earned   = prog.earned_credits   || 0;
            const total    = prog.total_required_credits || TOTAL_CREDITS;
            const gpa      = prog.current_gpa      || null;
            const pct      = prog.completion_percentage || Math.round((earned / total) * 100);
            const neededPS = prog.needed_credits_per_sem || 0;

            // ── KPI Hero / Dashboard ──────────────────────────────────────
            document.getElementById('kpi-progress').textContent     = pct + '%';
            document.getElementById('kpi-progress-sub').textContent = `${earned} / ${total} TC hoàn thành`;

            // TC tích lũy (stat-earned-credits tồn tại ở 2 nơi)
            document.querySelectorAll('#stat-earned-credits').forEach(el => el.textContent = earned);

            // KPI credits (hero circle)
            const kpiCreditsEl = document.getElementById('kpi-credits');
            if (kpiCreditsEl) kpiCreditsEl.textContent = earned;

            // Hero progress circle animation
            const heroCircle = document.getElementById('hero-progress-circle');
            if (heroCircle) heroCircle.style.setProperty('--prog-deg', `${pct * 3.6}deg`);

            // GPA
            const gpaEl = document.getElementById('kpi-gpa');
            if (gpaEl) gpaEl.textContent = gpa !== null ? gpa : '—';

            // TC/kỳ còn lại
            const perSemEl = document.getElementById('stat-credits-per-sem');
            if (perSemEl && neededPS > 0) perSemEl.textContent = neededPS;

            // ── Card "Gợi Ý Môn Học" trên Dashboard ────────────────────────
            // Cập nhật badge tư vấn dựa trên GPA + tiến độ từ backend
            const adviceBadge = document.getElementById('dash-advice-badge');
            const adviceNum   = document.getElementById('dash-advice-num');
            const adviceReason = document.getElementById('dash-advice-reason');

            if (adviceBadge && gpa !== null) {
                let badgeText, badgeClass, creditTarget, adviceText;

                if (gpa >= 7.5 && pct >= 20) {
                    badgeText    = '🚀 Học lực tốt — Tăng tốc';
                    badgeClass   = 'increase';
                    creditTarget = 20;
                    adviceText   = `GPA ${gpa} xuất sắc! Hãy đăng ký thêm môn để ra trường sớm hơn.`;
                } else if (gpa >= 6.0) {
                    badgeText    = '⚖️ Đang đúng tiến độ';
                    badgeClass   = 'maintain';
                    creditTarget = 18;
                    adviceText   = `GPA ${gpa} ổn định. Tiếp tục duy trì nhịp học hiện tại.`;
                } else if (gpa >= 5.0) {
                    badgeText    = '⚠️ Cần cải thiện GPA';
                    badgeClass   = 'warn';
                    creditTarget = 15;
                    adviceText   = `GPA ${gpa} cần cải thiện. Hãy ưu tiên chất lượng hơn số lượng môn.`;
                } else {
                    badgeText    = '🆘 Cảnh báo học vụ';
                    badgeClass   = 'danger';
                    creditTarget = 12;
                    adviceText   = `GPA ${gpa} ở ngưỡng nguy hiểm. Hãy tham khảo cố vấn học tập ngay.`;
                }

                adviceBadge.textContent  = badgeText;
                adviceBadge.className    = `dash-advice-badge ${badgeClass}`;
                if (adviceNum)    adviceNum.textContent    = creditTarget;
                if (adviceReason) adviceReason.textContent = adviceText;
            } else if (adviceBadge && gpa === null) {
                adviceBadge.textContent = '• Nhập điểm để nhận gợi ý';
                adviceBadge.className   = 'dash-advice-badge maintain';
            }

            // ── Cảnh báo học vụ ─────────────────────────────────────────
            const warnContainer = document.getElementById('dash-global-warning');
            if (warnContainer) {
                if (warnings && warnings.length > 0) {
                    warnContainer.innerHTML = warnings.map(w => `
                        <div style="background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                            ⚠️ <strong>Cảnh báo học vụ:</strong> ${w.message}
                        </div>
                    `).join('');
                } else {
                    warnContainer.innerHTML = '';
                }
            }
        }
    } catch (e) {
        console.error('Lỗi khi fetch progress', e);
    }

    // Gọi thêm API dự báo tốt nghiệp
    fetchGraduationForecast();
}


async function fetchGraduationForecast() {
    try {
        const response = await fetch('/api/v1/graduation-forecast');
        if (!response.ok) return;
        const resData = await response.json();
        if (resData.success && resData.data) {
            const data = resData.data;
            const widget = document.getElementById('grad-forecast-widget');
            if (!widget) return;

            // Header badge
            const badge = document.getElementById('grad-status-badge');
            const statusLabels = { 'ON_TRACK': 'Đúng tiến độ', 'AHEAD': 'Vượt tiến độ', 'BEHIND': 'Chậm tiến độ', 'GRADUATED': 'Đã tốt nghiệp' };
            badge.textContent = statusLabels[data.status] || data.status;
            badge.style.color = data.status_color;
            badge.style.backgroundColor = data.status_color + '20';

            document.getElementById('grad-message').textContent = data.message;
            document.getElementById('grad-remaining-credits').textContent = (data.remaining_credits ?? '--') + ' TC';
            const gpaEl = document.getElementById('grad-current-gpa');
            if (gpaEl) gpaEl.textContent = data.current_gpa > 0 ? data.current_gpa : '--';

            // 3 kịch bản
            if (data.scenarios) {
                const s = data.scenarios;
                const setText = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
                setText('grad-opt-label',  s.optimistic?.grad_label  ?? '--');
                setText('grad-opt-desc',   s.optimistic?.description ?? '--');
                setText('grad-opt-gpa',    s.optimistic?.projected_gpa ?? '--');
                setText('grad-avg-label',  s.average?.grad_label     ?? '--');
                setText('grad-avg-desc',   s.average?.description    ?? '--');
                setText('grad-avg-gpa',    s.average?.projected_gpa  ?? '--');
                setText('grad-pess-label', s.pessimistic?.grad_label  ?? '--');
                setText('grad-pess-desc',  s.pessimistic?.description ?? '--');
                setText('grad-pess-gpa',   s.pessimistic?.projected_gpa ?? '--');
            }

            // Cảnh báo rủi ro
            const risksContainer = document.getElementById('grad-risks-container');
            const risksList = document.getElementById('grad-risks-list');
            if (risksContainer && risksList && data.risks && data.risks.length > 0) {
                risksList.innerHTML = data.risks.map(r => {
                    const color = r.level === 'danger' ? '#ef4444' : '#f59e0b';
                    const bg    = r.level === 'danger' ? '#fef2f2' : '#fffbeb';
                    return `<div style="font-size:0.8rem; padding:8px 12px; border-radius:8px; background:${bg}; border-left:3px solid ${color}; color:#374151;">${r.message}</div>`;
                }).join('');
                risksContainer.style.display = 'block';
            } else if (risksContainer) {
                risksContainer.style.display = 'none';
            }

            widget.style.display = 'block';

            if (window._pendingModeEvaluation) {
                updateModeSuggestionBadge(window._pendingModeEvaluation);
            }
        }
    } catch (e) { console.error('Lỗi khi fetch graduation forecast', e); }
}

/**
 * Hiển thị badge gợi ý mode nhẹ nhàng trong widget Dự Báo Tốt Nghiệp.
 * KHÔNG hiện popup — người dùng có thể đọc và tự quyết định khi nào phù hợp.
 *
 * Chỉ gợi ý khi:
 *   - GPA tốt (≥ 7.5) + đúng/vượt tiến độ → gợi ý FAST
 *   - GPA trung bình (6.0-7.4) + chậm tiến độ → gợi ý giữ NORMAL hoặc xem xét
 *   - GPA yếu/nguy hiểm (< 6.0) → gợi ý SLOW
 */
function updateModeSuggestionBadge(evaluation) {
    const badgeEl = document.getElementById('grad-mode-suggestion');
    if (!badgeEl) return;

    const modeMap = {
        fast:   { icon: '🚀', label: 'Tăng Tốc',  color: '#10b981', bg: '#d1fae5' },
        normal: { icon: '⚖️', label: 'Cân Bằng',  color: '#1a3a3a', bg: '#e8f8f3' },
        slow:   { icon: '🌱', label: 'Học Nhẹ',   color: '#d97706', bg: '#fef3c7' },
    };

    const suggested = evaluation.suggested_mode || 'normal';
    const current   = window.currentActivePlan?.mode || 'normal';
    const status    = evaluation.status; // KEEP | REPLAN | SPEED_UP | REDUCE

    // Không hiện gì nếu không cần thay đổi
    if (status === 'KEEP' || suggested === current) {
        badgeEl.style.display = 'none';
        return;
    }

    const m = modeMap[suggested] || modeMap['normal'];
    const arrow = suggested === 'fast' ? '↑' : '↓';

    badgeEl.innerHTML = `
        <span style="font-size:0.78rem; color:${m.color}; background:${m.bg}; padding:4px 10px; border-radius:20px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px;"
              title="${evaluation.message}"
              onclick="document.getElementById('tab-planner') && switchTab('study-plan', document.getElementById('nav-study-plan'))">
            ${arrow} Nên chuyển sang ${m.icon} ${m.label}
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:12px;height:12px;"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
        </span>`;
    badgeEl.style.display = 'block';
}

function renderSuggestions(subjects, targetSemester) {
    const container = document.getElementById('suggestions-list');
    const creditsSpan = document.getElementById('suggested-total-credits');
    
    if (subjects.length === 0) {
        if (creditsSpan) creditsSpan.textContent = '(0 TC)';
        container.innerHTML = `<div class="empty-state"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg><h3>Không có môn học đề xuất nào!</h3><p>Hãy thử đổi niên khóa, loại chương trình hoặc học kỳ mong muốn phù hợp hơn.</p></div>`;
        return;
    }
    
    const meta = window._suggestionMeta;
    if (creditsSpan) {
        if (meta && meta.retakeCredits > 0 && meta.regularCredits > 0) {
            creditsSpan.textContent = `(${meta.retakeCredits} TC học lại · ${meta.regularCredits} TC mới / ${meta.maxCredits} TC/kỳ)`;
        } else if (meta && meta.retakeCredits > 0) {
            creditsSpan.textContent = `(${meta.retakeCredits} TC học lại)`;
        } else {
            const total = subjects.reduce((s, x) => s + (parseInt(x.credits) || 0), 0);
            creditsSpan.textContent = `(${total} TC / ${meta?.maxCredits || 18} TC/kỳ)`;
        }
    }
    container.innerHTML = subjects.map(subject => {
        const subSem = parseInt(subject.semester?.name || 1);
        const targetSem = parseInt(targetSemester);
        const isAdded = currentCourses.find(c => c.id == subject.id);
        const isFailed = subject.is_failed_subject === true;
        const isEligible = subject.can_study !== false;

        let priorityLabel = '';
        if (isFailed) {
            priorityLabel = `<span class="pill" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;font-weight:700;">🔄 Cần học lại</span>`;
        } else if (subject.suggestion_score >= 100) {
            priorityLabel = `<span class="pill pill-mint">Ưu tiên Rất Cao 🔥</span>`;
        } else if (subject.suggestion_score >= 60) {
            priorityLabel = `<span class="pill pill-lavender">Ưu tiên Cao 👍</span>`;
        } else if (subject.suggestion_score >= 30) {
            priorityLabel = `<span class="pill pill-ochre">Ưu tiên Vừa 👌</span>`;
        } else {
            priorityLabel = `<span class="pill" style="background:#f3f4f6;color:#6b7280;border:none;">Ít Ưu tiên</span>`;
        }

        // Badge bắt buộc / tự chọn
        let electiveBadge = '';
        if (subject.elective_group_name) {
            electiveBadge = `<span class="pill" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;font-size:0.68rem;padding:2px 7px;">📚 Tự chọn · ${subject.elective_group_name}</span>`;
        } else if (subject.is_elective) {
            electiveBadge = `<span class="pill" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;font-size:0.68rem;padding:2px 7px;">📝 Tự chọn</span>`;
        } else if (subject.is_elective === false) {
            electiveBadge = `<span class="pill" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;font-size:0.68rem;padding:2px 7px;">✔ Bắt buộc</span>`;
        }

        let tagHtml = `<span class="pill pill-cream" style="background:#e8f8f3;color:#1a3a3a;border:none;">${subject.credits} TC</span> ${priorityLabel} ${electiveBadge}`;
        if (subject.skill_evaluation && !isFailed) {
            let evalColor = subject.skill_evaluation.includes('+') ? '#10b981' : '#f59e0b';
            if (subject.skill_evaluation.includes('-15')) evalColor = '#ef4444';
            tagHtml += `<span class="pill" style="background:var(--surface);color:${evalColor};border:1px solid ${evalColor}40;font-size:0.68rem;padding:2px 8px;">${subject.skill_evaluation}</span>`;
        }

        const cardBorder = isFailed ? '1px solid #fca5a5' : '1px solid var(--hairline)';
        const cardBg     = isFailed ? 'rgba(254,226,226,0.3)' : '';

        return `
                <div class="suggestion-card" onclick="scrollToSubject(${subject.id})" style="cursor:pointer; transition:all 0.2s; border:${cardBorder}; background:${cardBg};" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-sm)'" onmouseout="this.style.transform='none'; this.style.boxShadow='none'">
                    <div class="suggestion-card-top" style="padding-bottom: 0;">
                        <div class="suggestion-details">
                            <span class="suggestion-title">${subject.name}</span>
                            <div class="suggestion-tags">${tagHtml}</div>
                        </div>
                    </div>
                </div>`;
    }).join('');
}

// ═══════════════════════════════════════════════════════════════
// COMPLETE SEMESTER
// ═══════════════════════════════════════════════════════════════
async function completeSemester() {
    const unfilled = currentCourses.filter(c => c.grade === null || c.grade === undefined);
    if (unfilled.length > 0) { showToast(`Còn ${unfilled.length} môn chưa điền điểm!`, 'error'); return; }
    if (currentCourses.length === 0) { showToast('Chưa có môn nào trong danh sách!', 'error'); return; }
    
    const snapshot = currentCourses.map(c => ({ ...c }));
    const cur = getCurrentSemester();
    
    // Gọi API để trigger đánh giá ở backend
    try {
        const res = await fetch('/semester-history/complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                semester_number: cur,
                courses: snapshot.map(c => ({ subject_id: c.id, grade: c.grade }))
            })
        });
        const resData = await res.json();
        _semEvaluation = resData.evaluation || null;
    } catch(e) {
        console.error('Lỗi khi lưu lịch sử', e);
    }

    // Xóa badge gợi ý cũ từ kỳ trước (nếu có) vì sắp có đánh giá mới
    window._pendingModeEvaluation = null;
    const oldBadge = document.getElementById('grad-mode-suggestion');
    if (oldBadge) oldBadge.style.display = 'none';

    currentCourses = []; renderCurrentCourses();
    snapshot.forEach(({ id, grade }) => {
        const input = document.getElementById(`grade-${id}`); if (!input) return;
        input.classList.remove('is-studying'); const wrap = input.parentElement;
        if (wrap) { wrap.classList.remove('is-locked'); const badge = wrap.querySelector('.studying-label'); if (badge) badge.remove(); }
        input.value = grade; onGradeChange(id, input, true);
    });
    saveMultipleGrades(snapshot.map(c => ({ subject_id: c.id, grade: c.grade })));
    if (cur >= 8) { showToast('Đã hoàn thành toàn bộ chương trình! 🎓', 'success'); }
    savePreferences(); fetchSuggestions(); updateEarnedCredits(); scheduleChartRefresh();
    showSemResultModal(cur, snapshot);
    // Update nav badge
    const navBadge = document.getElementById('nav-cc-badge');
    if (navBadge) { navBadge.textContent = 0; navBadge.classList.remove('visible'); }
}

// ═══════════════════════════════════════════════════════════════
// SEMESTER RESULT MODAL
// ═══════════════════════════════════════════════════════════════
let _semRecCredits = 0;
let _semEvaluation = null;

function showSemResultModal(semNumber, snapshot) {
    const graded = snapshot.filter(c => c.grade !== null && c.grade !== undefined);
    const passSubjects = graded.filter(c => c.grade >= 5.0);
    const failSubjects = graded.filter(c => c.grade < 5.0);
    const gpa = graded.length > 0 ? Math.round(graded.reduce((s, c) => s + c.grade, 0) / graded.length * 10) / 10 : null;
    const creditsThisSem = snapshot.reduce((s, c) => s + (c.credits || 0), 0);
    const passedCredits = passSubjects.reduce((s, c) => s + (c.credits || 0), 0);
    let totalEarned = 0;
    
    // Nếu có evaluation từ backend (gọi từ completeSemester)
    if (_semEvaluation) {
        const evalData = _semEvaluation;
        const modeDisplayMap = {
            fast:   { icon: '🚀', label: 'Tăng Tốc',  color: '#10b981' },
            normal: { icon: '⚖️', label: 'Cân Bằng',  color: '#1a3a3a' },
            slow:   { icon: '🌱', label: 'Học Nhẹ',   color: '#d97706' },
        };
        const modeInfo = modeDisplayMap[evalData.suggested_mode] || modeDisplayMap['normal'];
        const currentMode = window.currentActivePlan?.mode || 'normal';
        const currentInfo = modeDisplayMap[currentMode] || modeDisplayMap['normal'];
        const needsChange = evalData.status !== 'KEEP' && evalData.suggested_mode !== currentMode;

        document.getElementById('srm-advisor-section').style.display = 'block';
        document.getElementById('srm-advisor-message').innerHTML = `
            <p style="margin:0 0 10px 0;">${evalData.message}</p>
            <div style="display:flex; align-items:center; gap:12px; background:#f8fafc; border-radius:8px; padding:10px 14px;">
                <div style="font-size:0.85rem; color:var(--muted);">Mode hiện tại:</div>
                <span style="font-weight:700; color:${currentInfo.color}">${currentInfo.icon} ${currentInfo.label}</span>
                ${needsChange ? `<span style="color:var(--muted); font-size:1rem;">→</span>
                <div style="font-size:0.85rem; color:var(--muted);">Gợi ý:</div>
                <span style="font-weight:700; color:${modeInfo.color}">${modeInfo.icon} ${modeInfo.label}</span>` : ''}
            </div>
        `;

        if (needsChange) {
            document.getElementById('srm-adjustment-prompt').style.display = 'flex';
        } else {
            document.getElementById('srm-adjustment-prompt').style.display = 'none';
        }
    } else {
        document.getElementById('srm-advisor-section').style.display = 'none';
    }

    if (window.currentActivePlan && window.currentActivePlan.semesters) {
        window.currentActivePlan.semesters.forEach(sem => {
            if (sem.subjects) {
                sem.subjects.forEach(ss => {
                    if (ss.grade !== null && ss.grade >= 5.0 && ss.subject) {
                        totalEarned += parseInt(ss.subject.credits || 0);
                    }
                });
            }
        });
    }
    const planMode = window.currentActivePlan ? window.currentActivePlan.mode : 'normal';
    const totalSem = planMode === 'fast' ? 6 : (planMode === 'slow' ? 10 : 8);
    const nextSem = Math.min(semNumber + 1, totalSem);
    const remSem = Math.max(1, totalSem - semNumber);

    // Ưu tiên số liệu từ đánh giá backend (AcademicEvaluationService) để modal NHẤT QUÁN
    // với phần "Gợi Ý Cường Độ Học" — tránh hiện 2 con số TC/kỳ mâu thuẫn (vd: tư vấn 13
    // nhưng nút lại 21). Backend dùng TC chương trình yêu cầu + số kỳ mục tiêu thật;
    // FE trước đây dùng tổng TC mọi môn (kể cả tự chọn dư) + số kỳ theo mode → lệch.
    const ev = _semEvaluation || {};
    if (ev.earned_credits != null) totalEarned = ev.earned_credits;
    const totalReq     = ev.total_required_credits || TOTAL_CREDITS;
    const remCredits   = (ev.remaining_credits != null) ? ev.remaining_credits : Math.max(0, totalReq - totalEarned);
    const neededPerSem = (ev.projected_tc_per_sem != null) ? ev.projected_tc_per_sem : (remSem > 0 ? Math.ceil(remCredits / remSem) : 0);
    const progPct = Math.min(100, Math.round((totalEarned / totalReq) * 100));
    const passRate = graded.length > 0 ? passSubjects.length / graded.length : 1;
    const avgPerSem = creditsThisSem;
    let recType, recIcon, recTag, recHeadline, recDesc, recDelta;
    const reasons = [];
    if (gpa === null) { recType = 'maintain'; recIcon = '📊'; recTag = 'Giữ nguyên'; recHeadline = 'Tiếp tục theo kế hoạch'; recDelta = 0; recDesc = 'Nhập điểm để nhận gợi ý chính xác hơn.'; }
    else if (gpa >= 7.5 && passRate >= 0.85) {
        recType = 'increase'; recIcon = '🌟'; recTag = 'Học lực xuất sắc';
        if (neededPerSem > 20) {
            recHeadline = 'Năng lực tốt, hãy tăng tốc!';
            recDelta = Math.min(6, Math.max(2, neededPerSem - avgPerSem));
            recDesc = `Thành tích kỳ này rất tốt (GPA ${gpa}). Hãy tăng số tín chỉ kỳ sau để sớm bắt kịp tiến độ nhé.`;
            reasons.push({ icon: '⏳', text: `Cần <strong>${neededPerSem} TC/kỳ</strong> để không bị trễ hạn.` });
        } else {
            recHeadline = 'Cơ hội ra trường sớm!';
            recDelta = Math.max(2, Math.min(6, 22 - avgPerSem));
            recDesc = `Thành tích của bạn rất xuất sắc (GPA ${gpa}). Nếu tăng mức tải, bạn hoàn toàn có thể ra trường sớm hơn kế hoạch!`;
            reasons.push({ icon: '🎯', text: `Có thể đăng ký vượt rào để rút ngắn thời gian học.` });
        }
        reasons.push({ icon: '🌟', text: `GPA học kỳ <strong>${gpa}</strong> — kết quả xuất sắc!` });
    }
    else if (gpa >= 6.5 && passRate >= 0.8 && neededPerSem <= avgPerSem + 2) { recType = 'maintain'; recIcon = '✅'; recTag = 'Giữ nguyên tiến độ'; recHeadline = 'Tiếp tục theo kế hoạch!'; recDelta = 0; recDesc = `GPA ${gpa} và tiến độ đúng hướng.`; reasons.push({ icon: '✔️', text: `GPA <strong>${gpa}</strong> — đang đi đúng hướng!` }); }
    else if ((gpa < 5.5 || passRate < 0.6) && failSubjects.length > 0) {
        recType = 'decrease'; recIcon = '📉'; recTag = 'Gợi ý giảm tín chỉ';
        if (neededPerSem > 20) {
            recHeadline = 'Cảnh báo học vụ & Tiến độ';
            recDelta = 15 - avgPerSem; // Hướng tới mức 15 TC an toàn
            recDesc = `GPA ${gpa} thấp nhưng tiến độ đang rất chậm. Đề xuất học khoảng 15 TC để cân bằng.`;
        } else {
            recHeadline = 'Cần giảm tải để tập trung';
            recDelta = -Math.min(6, Math.ceil(failSubjects.length * 1.5));
            recDesc = `GPA ${gpa} thấp, ${failSubjects.length} môn fail.`;
        }
        reasons.push({ icon: '⚠️', text: `<strong>${failSubjects.length} môn fail</strong> cần học lại kỳ sau.` });
    }
    else if (neededPerSem > avgPerSem + 4) { recType = 'increase'; recIcon = '📈'; recTag = 'Cần tăng tiến độ'; recHeadline = 'Tăng tín chỉ để kịp tiến độ'; recDelta = Math.min(5, neededPerSem - avgPerSem); recDesc = `Cần <strong>${neededPerSem} TC/kỳ</strong> nhưng kỳ này chỉ ${avgPerSem} TC.`; }
    else { recType = 'maintain'; recIcon = '✨'; recTag = 'Giữ nguyên tiến độ'; recHeadline = 'Tiếp tục theo kế hoạch!'; recDelta = 0; recDesc = 'Bạn đang đi đúng hướng.'; if (gpa) reasons.push({ icon: '⭐', text: `GPA <strong>${gpa}</strong> - kết quả ổn.` }); }

    // Ensure suggestion is bounded safely, but allow 15 if forced
    let suggestedCredits = Math.max(10, Math.min(25, avgPerSem + recDelta));
    if ((gpa < 5.5 || passRate < 0.6) && neededPerSem > 20) suggestedCredits = 15;

    // Recalculate true delta to ensure consistency
    recDelta = suggestedCredits - avgPerSem;

    // Synchronize visual tags with the actual math
    if (recDelta > 0) {
        recType = 'increase';
        recIcon = '📈';
        if (gpa < 5.5 || passRate < 0.6) recTag = 'Cần nỗ lực hơn';
        else if (recTag.includes('giảm') || recTag.includes('Giữ')) recTag = 'Gợi ý tăng tín chỉ';
    } else if (recDelta < 0) {
        recType = 'decrease';
        recIcon = '📉';
        if (recTag.includes('tăng') || recTag.includes('Giữ')) recTag = 'Gợi ý giảm tín chỉ';
    } else {
        recType = 'maintain';
        recIcon = '✨';
        if (gpa < 5.5 || passRate < 0.6) {
            recTag = 'Giữ mức an toàn';
            recIcon = '⚠️';
        } else {
            recTag = 'Giữ nguyên tiến độ';
        }
    }

    _semRecCredits = suggestedCredits;
    const gpaClass = gpa === null ? '' : gpa >= 8.0 ? 'gpa-ex' : gpa >= 7.0 ? 'gpa-good' : gpa >= 5.5 ? 'gpa-ok' : 'gpa-bad';
    document.getElementById('srm-sem-label').textContent = `Kết quả Học Kỳ ${semNumber}`;
    document.getElementById('srm-title').textContent = semNumber < 8 ? `Hoàn tất Học Kỳ ${semNumber} 🎉` : `Tốt nghiệp chương trình! 🎓`;
    document.getElementById('srm-subtitle').textContent = `Phân tích kết quả và gợi ý tín chỉ cho học kỳ ${nextSem}`;
    const gpaEl = document.getElementById('srm-gpa'); gpaEl.textContent = gpa !== null ? gpa : '—'; gpaEl.className = `srm-kpi-val ${gpaClass}`;
    document.getElementById('srm-pass-count').textContent = passSubjects.length;
    document.getElementById('srm-fail-count').textContent = failSubjects.length;
    document.getElementById('srm-credits-done').textContent = totalEarned;
    document.getElementById('srm-prog-pct').textContent = `${progPct}%`;
    const fill = document.getElementById('srm-prog-fill'); fill.style.width = '0%';
    setTimeout(() => { fill.style.width = `${progPct}%`; }, 100);
    fill.style.background = progPct >= 75 ? 'var(--success)' : progPct >= 40 ? 'var(--ink)' : 'var(--brand-ochre)';
    document.getElementById('srm-prog-left').textContent = `Còn lại: ${remCredits} TC`;
    document.getElementById('srm-prog-pace').textContent = `Cần ${neededPerSem} TC/kỳ`;
    const subjEl = document.getElementById('srm-subj-section');
    const subjectData = snapshot.map(c => { const input = document.getElementById(`grade-${c.id}`); const credits = parseInt(input?.dataset.credits || c.credits || 0); return { ...c, credits }; });
    const passHtml = subjectData.filter(c => c.grade >= 5.0).map(c => `<div class="srm-subj-row pass"><span class="srm-subj-name">${c.name}</span><span class="srm-subj-credits">${c.credits} TC</span><span class="srm-subj-grade pass">${c.grade}</span></div>`).join('');
    const failHtml = subjectData.filter(c => c.grade < 5.0).map(c => `<div class="srm-subj-row fail"><span class="srm-subj-name">${c.name}</span><span class="srm-subj-credits">${c.credits} TC</span><span class="srm-subj-grade fail">${c.grade}</span></div>`).join('');
    subjEl.innerHTML = `${passHtml ? `<div class="srm-subj-title">✓ Môn đạt (${passSubjects.length})</div><div class="srm-subj-list">${passHtml}</div>` : ''}${failHtml ? `<div class="srm-subj-title" style="color:var(--error);">✗ Môn chưa đạt (${failSubjects.length})</div><div class="srm-subj-list">${failHtml}</div>` : ''}`;
    const applyBtn = document.getElementById('srm-btn-apply');
    const hasModeSuggestion = _semEvaluation && _semEvaluation.status !== 'KEEP' &&
        _semEvaluation.suggested_mode !== (window.currentActivePlan?.mode || 'normal');

    // Backend đã khuyên GIỮ NGUYÊN (KEEP) hoặc chưa có đánh giá → không hiện nút áp dụng
    // (sẽ mâu thuẫn với "duy trì nhịp học hiện tại"). Chỉ hiện khi backend thật sự khuyên đổi.
    const backendRecommendsChange = !!(_semEvaluation && _semEvaluation.status && _semEvaluation.status !== 'KEEP');
    if (applyBtn) {
        if (hasModeSuggestion || !backendRecommendsChange || recDelta === 0) {
            applyBtn.style.display = 'none';
        } else {
            applyBtn.style.display = '';
            applyBtn.innerHTML = `✨ Áp dụng gợi ý kỳ tiếp (${suggestedCredits} TC)`;
        }
    }
    document.getElementById('sem-result-overlay').classList.add('open');
}

function closeSemResultModal() { document.getElementById('sem-result-overlay').classList.remove('open'); }

function applyAutoAdjustment() {
    if (window.currentActivePlan && _semEvaluation) {
        closeSemResultModal();
        // Xóa badge gợi ý vì user đã chấp nhận điều chỉnh
        window._pendingModeEvaluation = null;
        const badgeEl = document.getElementById('grad-mode-suggestion');
        if (badgeEl) badgeEl.style.display = 'none';
        adjustStudyPlan(window.currentActivePlan.id, _semEvaluation);
    }
}

let _pendingEvaluation = null;
let _pendingPlanId = null;

function showDynamicModeModal(planId, evaluation) {
    _pendingEvaluation = evaluation;
    _pendingPlanId = planId;
    
    document.getElementById('dyn-modal-message').innerHTML = evaluation.message;
    
    const pros = document.getElementById('dyn-modal-pros');
    const cons = document.getElementById('dyn-modal-cons');
    const title = document.getElementById('dyn-modal-title');
    const header = document.querySelector('#dynamic-mode-overlay .srm-header');
    
    if (evaluation.status === 'SPEED_UP') {
        title.textContent = 'Thành tích xuất sắc! 🚀';
        header.style.background = 'var(--success)';
        pros.innerHTML = '+ Ra trường sớm hơn dự kiến, tiết kiệm thời gian.<br>+ Giảm chi phí sinh hoạt dài hạn.';
        cons.innerHTML = '− Khối lượng học tập mỗi kỳ khá nặng (lên đến 25 TC).<br>− Yêu cầu duy trì sự tập trung cao độ.';
    } else {
        title.textContent = 'Cảnh báo học thuật ⚠️';
        header.style.background = 'var(--brand-ochre)';
        pros.innerHTML = '+ Giảm tải áp lực học tập xuống mức an toàn (khoảng 15 TC/kỳ).<br>+ Có thời gian học lại các môn nợ và cải thiện điểm số.';
        cons.innerHTML = '− Kéo dài thời gian ra trường (thêm học kỳ).<br>− Phát sinh thêm chi phí sinh hoạt cho các học kỳ phụ.';
    }
    
    document.getElementById('dynamic-mode-overlay').classList.add('open');
}

function closeDynamicModeModal() {
    document.getElementById('dynamic-mode-overlay').classList.remove('open');
    _pendingEvaluation = null;
    _pendingPlanId = null;
    fetchStudyPlans(); // Reload just in case
}

function confirmDynamicMode() {
    if (_pendingPlanId && _pendingEvaluation) {
        document.getElementById('dynamic-mode-overlay').classList.remove('open');
        adjustStudyPlan(_pendingPlanId, _pendingEvaluation);
    }
}

function applyCreditRecommendation() {
    localStorage.setItem('recommended_credits_per_sem', _semRecCredits);
    closeSemResultModal();
    // Cập nhật hiển thị nếu có element thống kê
    const statEl = document.getElementById('stat-credits-per-sem');
    if (statEl) statEl.textContent = _semRecCredits;
    showToast(
        `✅ Đã đặt mục tiêu ${_semRecCredits} TC cho kỳ tiếp — Không ảnh hưởng lộ trình tổng thể`,
        'success'
    );
}

document.getElementById('sem-result-overlay').addEventListener('click', function (e) { if (e.target === this) closeSemResultModal(); });


// ═══════════════════════════════════════════════════════════════
// TOAST
// ═══════════════════════════════════════════════════════════════
function showToast(msg, type = 'success') {
    const existing = document.getElementById('app-toast'); if (existing) existing.remove();
    const t = document.createElement('div'); t.id = 'app-toast'; t.className = `toast ${type}`; t.textContent = msg;
    document.body.appendChild(t); setTimeout(() => t.remove(), 3500);
}

// ═══════════════════════════════════════════════════════════════
// EVENT LISTENERS
// ═══════════════════════════════════════════════════════════════
document.getElementById('academic_year').addEventListener('change', () => { clearTimeout(saveTimer); showSaveIndicator('hide'); savePreferences(); fetchSuggestions(); });
document.getElementById('program_type').addEventListener('change', () => { clearTimeout(saveTimer); showSaveIndicator('hide'); savePreferences(); fetchSuggestions(); });



// ═══════════════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', async () => {
    updateCreditStats();
    const prefs = await loadPreferences();
    const hasConfig = prefs && prefs.academic_year;
    if (!hasConfig) { openOnboarding(); }
    else {
        if (prefs.academic_year) document.getElementById('academic_year').value = prefs.academic_year;
        if (prefs.program_type) document.getElementById('program_type').value = prefs.program_type;
        const sfEl = document.getElementById('skill_focus');
        if (sfEl && prefs.skill_focus) sfEl.value = prefs.skill_focus;

        if (prefs.current_courses && prefs.current_courses.length > 0) {
            currentCourses = prefs.current_courses;
            localStorage.setItem('current_courses', JSON.stringify(currentCourses));
        }
        document.getElementById('config-dot')?.remove();
        updateCreditStats();
        await loadGradesFromDB();
        renderCurrentCourses();
        fetchSuggestions();
        fetchChartData();
        fetchWarnings();
    }
    
    // Tính TC/kỳ preview cho config goal cards
    setTimeout(() => _updateConfigGoalPreview(window._selectedGoalSems || 8), 400);
});

// ── Graduation goal selection (unified — lives in config panel) ─
window._selectedGoalSems = 8;

function selectConfigGoal(sems, applyNow = false) {
    window._selectedGoalSems = sems;
    document.querySelectorAll('#config-goal-cards .goal-card.cfg').forEach(el => {
        const isThis = +el.dataset.sems === sems;
        el.style.border = isThis ? '2px solid var(--brand-mint)' : '2px solid var(--hairline)';
        el.style.background = isThis ? 'rgba(155,217,177,0.15)' : '';
        if (isThis) el.classList.add('selected'); else el.classList.remove('selected');
    });
    _updateConfigGoalPreview(sems);
    if (applyNow && window.currentActivePlan) {
        applyTargetAdjustment();
    }
}

function _updateConfigGoalPreview(sems) {
    const el = document.getElementById('config-goal-preview');
    if (!el) return;
    let earned = 0;
    if (window.obData && window.obData.grades) {
        earned = Object.values(window.obData.grades)
            .filter(g => g.grade >= 5.0 || ['passed', 'pass'].includes(g.status))
            .reduce((sum, g) => sum + (parseInt((window.subjectMap || {})[g.subject_id]?.credits || 0)), 0);
    }
    const remaining = Math.max(0, (window.TOTAL_CREDITS || 130) - earned);
    const completedSems = (window.currentActivePlan?.semesters?.filter(s => s.subjects.every(ss => ss.is_completed))?.length) || 0;
    const remSems = Math.max(1, sems - completedSems);
    const tcPerSem = Math.min(22, Math.max(12, Math.ceil(remaining / remSems)));
    el.innerHTML = `Cần đạt <strong>~${tcPerSem} TC/kỳ</strong> trong <strong>${remSems}</strong> kỳ còn lại (${remaining} TC chưa học).`;
}

// Aliases for backward compat
function selectGoal(sems) { selectConfigGoal(sems); }
function selectAdjGoal(sems) { selectConfigGoal(sems); }

async function applyTargetAdjustment() {
    if (!window.currentActivePlan) return;
    const targetSemesters = window._selectedGoalSems || 8;
    const loader = document.getElementById('planner-loader');
    if (loader) loader.style.display = 'block';
    try {
        const res = await fetch(`/api/v1/study-plans/${window.currentActivePlan.id}/adjust-target`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify({ target_semesters: targetSemesters })
        });
        const resData = await res.json();
        if (resData.success && resData.data) {
            showToast(`Đã cập nhật mục tiêu ${targetSemesters} kỳ, ~${resData.data.tc_per_sem || '?'} TC/kỳ`, 'success');
            renderStudyPlan(resData.data);
            fetchSavedPlansList();
        } else {
            showToast('Lỗi khi cập nhật mục tiêu', 'error');
        }
    } catch (e) {
        showToast('Lỗi mạng', 'error');
    } finally {
        if (loader) loader.style.display = 'none';
    }
}


// ═══════════════════════════════════════════════════════════════
// GRADE CHART (Chart.js) — Clay colors
// ═══════════════════════════════════════════════════════════════
let gradeChartInstance = null;
let gradeChartDetailInstance = null;
let chartRawData = null;
let chartTimer = null;

async function fetchChartData() {
    try {
        const res = await fetch('/grades/chart-data', { headers: { 'Accept': 'application/json' } });
        if (!res.ok) return;
        chartRawData = await res.json();
        buildChartSemFilter(chartRawData.semesters);
        try { renderGradeChart(chartRawData, 'all'); } catch(e) { console.warn(e); }
        try { renderGradeChartDetail(chartRawData, 'all'); } catch(e) { console.warn(e); }
    } catch (err) { console.warn('[Chart error]', err); }
}

function buildChartSemFilter(semesters) {
    const uniqueSems = [...new Set(semesters)].sort((a, b) => parseInt(a) - parseInt(b));
    // Dashboard filter (shared buttons)
    ['chart-sem-filter', 'chart-sem-filter-dash'].forEach(filterId => {
        const container = document.getElementById(filterId);
        if (!container) return;
        container.innerHTML = '<button class="chart-sem-btn active" data-sem="all" onclick="filterChartSem(\'all\',this)">Tất cả HK</button>';
        uniqueSems.forEach(sem => {
            const btn = document.createElement('button');
            btn.className = 'chart-sem-btn'; btn.dataset.sem = sem;
            btn.textContent = `HK ${sem}`;
            btn.onclick = function () { filterChartSem(sem, this); };
            container.appendChild(btn);
        });
    });
}

function filterChartSem(sem, btn) {
    // Update all filter containers
    document.querySelectorAll('.chart-sem-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll(`.chart-sem-btn[data-sem="${sem}"]`).forEach(b => b.classList.add('active'));
    if (chartRawData) { renderGradeChart(chartRawData, sem); renderGradeChartDetail(chartRawData, sem); }
}

function renderGradeChart(data, semFilter = 'all') {
    const { labels, my_grades, avg_grades, semesters, academic_year, peer_count } = data;
    let idxs = labels.map((_, i) => i);
    if (semFilter !== 'all') idxs = idxs.filter(i => String(semesters[i]) === String(semFilter));
    const filteredLabels = idxs.map(i => labels[i]);
    const filteredMy = idxs.map(i => my_grades[i]);
    const filteredAvg = idxs.map(i => avg_grades[i]);

    const emptyEl = document.getElementById('chart-empty-dash');
    const canvas = document.getElementById('gradeChart');
    const legendEl = document.getElementById('chart-legend');
    const peerEl = document.getElementById('chart-peer-label');

    if (!filteredLabels.length) { if (emptyEl) emptyEl.style.display = 'flex'; if (canvas) canvas.style.display = 'none'; if (legendEl) legendEl.style.display = 'none'; return; }
    if (emptyEl) emptyEl.style.display = 'none';
    if (canvas) canvas.style.display = 'block';
    if (legendEl) legendEl.style.display = 'flex';

    if (peerEl) {
        if (peer_count > 1) peerEl.innerHTML = `👥 So sánh với <strong>${peer_count}</strong> SV cùng khóa ${academic_year || ''}`;
        else peerEl.innerHTML = '<span style="color:var(--muted-soft);">Chưa có dữ liệu khóa khác</span>';
    }

    // Also update detail peer label
    const peerElDetail = document.getElementById('chart-peer-label-detail');
    if (peerElDetail) {
        if (peer_count > 1) peerElDetail.innerHTML = `👥 So sánh với <strong>${peer_count}</strong> SV cùng khóa ${academic_year || ''}`;
        else peerElDetail.innerHTML = '<span style="color:var(--muted-soft);">Chưa có dữ liệu khóa khác</span>';
    }

    const barColors = filteredMy.map(v => v === null ? 'rgba(10,10,10,0.08)' : v >= 5.0 ? 'rgba(10,10,10,0.85)' : 'rgba(239,68,68,0.8)');
    const borderColors = filteredMy.map(v => v === null ? 'rgba(10,10,10,0.15)' : v >= 5.0 ? '#0a0a0a' : '#ef4444');

    if (gradeChartInstance) { gradeChartInstance.destroy(); gradeChartInstance = null; }
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    gradeChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: filteredLabels.map(l => l.length > 18 ? l.substring(0, 16) + '…' : l),
            datasets: [
                { label: 'Điểm của bạn', data: filteredMy, backgroundColor: barColors, borderColor: borderColors, borderWidth: 1.5, borderRadius: 6, borderSkipped: false, order: 2 },
                { label: 'Điểm TB cùng khóa', data: filteredAvg, type: 'line', borderColor: 'rgba(232,185,74,0.9)', backgroundColor: 'rgba(232,185,74,0.1)', borderWidth: 2.5, pointBackgroundColor: 'rgba(232,185,74,1)', pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 5, pointHoverRadius: 7, tension: 0.3, fill: false, order: 1, spanGaps: true }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false, animation: { duration: 600, easing: 'easeInOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(10,10,10,0.92)', titleColor: '#fff', bodyColor: 'rgba(255,255,255,0.8)', borderColor: 'rgba(10,10,10,0.15)', borderWidth: 1, padding: 12, callbacks: {
                        title: items => filteredLabels[items[0].dataIndex],
                        label: item => { if (item.dataset.label === 'Điểm của bạn') { const v = item.raw; return v === null ? '  Chưa nhập' : `  Của bạn: ${v} ${v >= 5 ? '✓ Pass' : '✗ Fail'}`; } return item.raw !== null ? `  TB khóa: ${item.raw}` : '  Chưa có dữ liệu TB'; },
                        afterBody: items => { const idx = items[0].dataIndex; const sem = semFilter === 'all' ? semesters[idxs[idx]] : semFilter; return [`  HK chuẩn: ${sem}`]; }
                    }
                }
            },
            scales: {
                x: { ticks: { color: 'rgba(10,10,10,0.45)', font: { size: 10 }, maxRotation: 40 }, grid: { color: 'rgba(10,10,10,0.04)' }, border: { color: 'rgba(10,10,10,0.1)' } },
                y: { min: 0, max: 10, ticks: { color: 'rgba(10,10,10,0.45)', font: { size: 11 }, stepSize: 1, callback: v => v === 5 ? '5 ⚡' : v }, grid: { color: ctx => ctx.tick.value === 5 ? 'rgba(239,68,68,0.4)' : 'rgba(10,10,10,0.04)', lineWidth: ctx => ctx.tick.value === 5 ? 2 : 1 }, border: { color: 'rgba(10,10,10,0.08)' } }
            }
        }
    });
}

function renderGradeChartDetail(data, semFilter = 'all') {
    const { labels, my_grades, avg_grades, semesters } = data;
    let idxs = labels.map((_, i) => i);
    if (semFilter !== 'all') idxs = idxs.filter(i => String(semesters[i]) === String(semFilter));
    const filteredLabels = idxs.map(i => labels[i]);
    const filteredMy = idxs.map(i => my_grades[i]);
    const filteredAvg = idxs.map(i => avg_grades[i]);

    const emptyEl = document.getElementById('chart-empty');
    const canvas = document.getElementById('gradeChartDetail');
    const legendEl = document.getElementById('chart-legend-detail');

    if (!filteredLabels.length) { if (emptyEl) emptyEl.style.display = 'flex'; if (canvas) canvas.style.display = 'none'; if (legendEl) legendEl.style.display = 'none'; return; }
    if (emptyEl) emptyEl.style.display = 'none';
    if (canvas) canvas.style.display = 'block';
    if (legendEl) legendEl.style.display = 'flex';

    const barColors = filteredMy.map(v => v === null ? 'rgba(10,10,10,0.08)' : v >= 5.0 ? 'rgba(10,10,10,0.85)' : 'rgba(239,68,68,0.8)');
    const borderColors = filteredMy.map(v => v === null ? 'rgba(10,10,10,0.15)' : v >= 5.0 ? '#0a0a0a' : '#ef4444');

    if (gradeChartDetailInstance) { gradeChartDetailInstance.destroy(); gradeChartDetailInstance = null; }
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    gradeChartDetailInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: filteredLabels.map(l => l.length > 18 ? l.substring(0, 16) + '…' : l),
            datasets: [
                { label: 'Điểm của bạn', data: filteredMy, backgroundColor: barColors, borderColor: borderColors, borderWidth: 1.5, borderRadius: 6, borderSkipped: false, order: 2 },
                { label: 'Điểm TB cùng khóa', data: filteredAvg, type: 'line', borderColor: 'rgba(232,185,74,0.9)', backgroundColor: 'rgba(232,185,74,0.1)', borderWidth: 2.5, pointBackgroundColor: 'rgba(232,185,74,1)', pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 5, pointHoverRadius: 7, tension: 0.3, fill: false, order: 1, spanGaps: true }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false, animation: { duration: 600, easing: 'easeInOutQuart' },
            plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(10,10,10,0.92)', padding: 12, callbacks: { title: items => filteredLabels[items[0].dataIndex], label: item => { if (item.dataset.label === 'Điểm của bạn') { const v = item.raw; return v === null ? '  Chưa nhập' : `  Của bạn: ${v} ${v >= 5 ? '✓ Pass' : '✗ Fail'}`; } return item.raw !== null ? `  TB khóa: ${item.raw}` : '  Chưa có dữ liệu TB'; } } } },
            scales: {
                x: { ticks: { color: 'rgba(10,10,10,0.45)', font: { size: 10 }, maxRotation: 40 }, grid: { color: 'rgba(10,10,10,0.04)' }, border: { color: 'rgba(10,10,10,0.1)' } },
                y: { min: 0, max: 10, ticks: { color: 'rgba(10,10,10,0.45)', font: { size: 11 }, stepSize: 1, callback: v => v === 5 ? '5 ⚡' : v }, grid: { color: ctx => ctx.tick.value === 5 ? 'rgba(239,68,68,0.4)' : 'rgba(10,10,10,0.04)', lineWidth: ctx => ctx.tick.value === 5 ? 2 : 1 }, border: { color: 'rgba(10,10,10,0.08)' } }
            }
        }
    });
}

let chartFetchTimer = null;
function scheduleChartRefresh() { clearTimeout(chartFetchTimer); chartFetchTimer = setTimeout(fetchChartData, 2000); }

// ═══════════════════════════════════════════════════════════════
// SUBJECT GROUP ANALYSIS
// ═══════════════════════════════════════════════════════════════
const GROUP_COLORS = ['#1a3a3a', '#ff4d8b', '#b8a4ed', '#ffb084', '#e8b94a', '#a4d4c5', '#ff6b5a', '#0a0a0a', '#3a3a3a', '#6a6a6a'];

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

function gradeLevel(avg) { if (avg === null) return 'na'; if (avg >= 8.0) return 'excellent'; if (avg >= 6.5) return 'good'; if (avg >= 5.0) return 'warning'; return 'danger'; }
function gradeLevelLabel(avg) {
    if (avg === null) return { cls: 'group-na-badge', text: '— Chưa có dữ liệu' };
    if (avg >= 8.0) return { cls: 'group-ok-badge', text: '🌟 Xuất sắc' };
    if (avg >= 6.5) return { cls: 'group-ok-badge', text: '✓ Tốt' };
    if (avg >= 5.0) return { cls: 'group-weak-badge', text: '⚠ Cần cải thiện' };
    return { cls: 'group-weak-badge', text: '⛔ Điểm yếu' };
}

function buildGroupAnalysis() {
    const allSubjects = [];
    for (const [semName, subs] of Object.entries(SUBJECTS_BY_SEM)) { subs.forEach(sub => allSubjects.push(sub)); }
    const grades = globalUserGrades || {};
    const groups = {};
    allSubjects.forEach(sub => {
        const gName = currentAnalysisType === 'skill' ? (sub.skillGroupName || 'Khác') : (sub.programGroupName || 'Khác');
        if (!groups[gName]) groups[gName] = { subjects: [], gradedSubjects: [] };
        groups[gName].subjects.push(sub);
        if (grades[sub.id] !== undefined) groups[gName].gradedSubjects.push({ ...sub, grade: grades[sub.id] });
    });
    const groupStats = Object.entries(groups).map(([name, data], idx) => { const graded = data.gradedSubjects; let avg = null; if (graded.length > 0) { const sum = graded.reduce((s, s2) => s + s2.grade, 0); avg = Math.round((sum / graded.length) * 10) / 10; } return { name, total: data.subjects.length, graded: graded.length, avg, color: GROUP_COLORS[idx % GROUP_COLORS.length], subjects: data.subjects, gradedSubjects: graded }; }).sort((a, b) => { if (a.avg === null && b.avg === null) return 0; if (a.avg === null) return 1; if (b.avg === null) return -1; return a.avg - b.avg; });
    return groupStats;
}

function renderGroupAnalysis() {
    const container = document.getElementById('group-analysis-content'); if (!container) return;
    const groupStats = buildGroupAnalysis();
    const hasAny = groupStats.some(g => g.avg !== null);
    if (!hasAny) { container.innerHTML = `<div class="group-analysis-empty"><div class="group-analysis-empty-icon">📊</div><p>Nhập điểm các môn học để xem phân tích điểm theo nhóm</p></div>`; return; }

    const weakGroups = groupStats.filter(g => g.avg !== null && g.avg < 6.5);
    const dangerGroups = groupStats.filter(g => g.avg !== null && g.avg < 5.0);
    const strongGroups = groupStats.filter(g => g.avg !== null && g.avg >= 8.0);

    const tableRows = groupStats.map(g => {
        const pct = g.avg !== null ? Math.round((g.avg / 10) * 100) : 0;
        const lvl = gradeLevel(g.avg);
        const badge = gradeLevelLabel(g.avg);
        const barColor = lvl === 'excellent' ? '#22c55e' : lvl === 'good' ? '#1a3a3a' : lvl === 'warning' ? '#e8b94a' : lvl === 'danger' ? '#ef4444' : 'rgba(10,10,10,0.1)';
        return `<tr>
            <td><div class="group-name-cell"><span class="group-dot" style="background:${g.color};"></span><span class="group-name-text">${g.name}</span></div></td>
            <td style="color:var(--muted);font-size:.78rem;">${g.graded}/${g.total}</td>
            <td class="group-bar-cell"><div class="group-bar-track"><div class="group-bar-fill" style="width:${pct}%;background:${barColor};"></div></div></td>
            <td class="group-avg-cell"><span class="group-avg-val ${lvl}">${g.avg !== null ? g.avg : '—'}</span></td>
            <td style="text-align:right;"><span class="${badge.cls}">${badge.text}</span></td>
        </tr>`;
    }).join('');

    const radarLabels = groupStats.filter(g => g.avg !== null).map(g => g.name);
    const radarData = groupStats.filter(g => g.avg !== null).map(g => g.avg);
    const radarColors = groupStats.filter(g => g.avg !== null).map(g => g.color);

    let alertsHtml = '';
    const labelTitle = currentAnalysisType === 'skill' ? 'nhóm kỹ năng' : 'khối kiến thức';
    if (dangerGroups.length > 0) { const names = dangerGroups.map(g => `<strong>${g.name}</strong>`).join(', '); alertsHtml += `<div class="group-alert danger"><span class="group-alert-icon">⛔</span><div>Bạn đang <strong>rất yếu</strong> ở ${labelTitle}: ${names} (điểm TB < 5.0).</div></div>`; }
    else if (weakGroups.length > 0) { const names = weakGroups.map(g => `<strong>${g.name}</strong> (${g.avg})`).join(', '); alertsHtml += `<div class="group-alert warning"><span class="group-alert-icon">⚠️</span><div>Cần cải thiện ${labelTitle}: ${names}.</div></div>`; }
    if (strongGroups.length > 0) { const names = strongGroups.map(g => `<strong>${g.name}</strong>`).join(', '); alertsHtml += `<div class="group-alert success"><span class="group-alert-icon">🌟</span><div>Bạn đang làm rất tốt ở ${labelTitle}: ${names}.</div></div>`; }

    const colHeader = currentAnalysisType === 'skill' ? 'Nhóm kỹ năng' : 'Khối kiến thức';
    container.innerHTML = `<div class="group-analysis-grid">
        <div><div class="radar-wrapper"><canvas id="groupRadarChart"></canvas></div></div>
        <div>
            <table class="group-table">
                <thead><tr><th>${colHeader}</th><th>Môn có điểm</th><th>Tỷ lệ</th><th style="text-align:right;">Điểm TB</th><th style="text-align:right;">Đánh giá</th></tr></thead>
                <tbody>${tableRows}</tbody>
            </table>
            <div class="group-summary-alerts">${alertsHtml}</div>
        </div>
    </div>`;
    renderGroupRadar(radarLabels, radarData, radarColors);
}

let groupRadarInstance = null;
function renderGroupRadar(labels, data, colors) {
    const canvas = document.getElementById('groupRadarChart'); if (!canvas || labels.length === 0) return;
    if (groupRadarInstance) { groupRadarInstance.destroy(); groupRadarInstance = null; }
    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(10,10,10,0.3)'); gradient.addColorStop(1, 'rgba(10,10,10,0.05)');
    const datasetLabel = currentAnalysisType === 'skill' ? 'Điểm TB nhóm kỹ năng' : 'Điểm TB khối kiến thức';
    groupRadarInstance = new Chart(ctx, {
        type: 'radar',
        data: { labels: labels.map(l => l.length > 14 ? l.substring(0, 12) + '…' : l), datasets: [{ label: datasetLabel, data, backgroundColor: gradient, borderColor: 'rgba(10,10,10,0.7)', borderWidth: 2, pointBackgroundColor: colors, pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 5, pointHoverRadius: 7 }] },
        options: { responsive: true, maintainAspectRatio: true, animation: { duration: 700, easing: 'easeInOutQuart' }, plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(10,10,10,0.9)', padding: 10, callbacks: { label: item => `  Điểm TB: ${item.raw}` } } }, scales: { r: { min: 0, max: 10, ticks: { stepSize: 2, color: 'rgba(10,10,10,0.35)', font: { size: 9 }, backdropColor: 'transparent', callback: v => v === 5 ? '5⚡' : v }, grid: { color: ctx => ctx.tick.value === 5 ? 'rgba(239,68,68,0.3)' : 'rgba(10,10,10,0.07)', lineWidth: ctx => ctx.tick.value === 5 ? 1.5 : 1 }, pointLabels: { color: 'rgba(10,10,10,0.65)', font: { size: 10, weight: '600' } }, angleLines: { color: 'rgba(10,10,10,0.07)' } } } }
    });
}

const _origOnGradeChange = onGradeChange;
window.onGradeChange = function (id, input, skipSave = false) { _origOnGradeChange(id, input, skipSave); clearTimeout(window._groupAnalysisTimer); window._groupAnalysisTimer = setTimeout(renderGroupAnalysis, 600); };
const _origLoadGrades = loadGradesFromDB;
window.loadGradesFromDB = async function () { await _origLoadGrades(); setTimeout(renderGroupAnalysis, 300); };

// ═══════════════════════════════════════════════════════════════
// HISTORY DRAWER
// ═══════════════════════════════════════════════════════════════
function toggleHistoryDrawer() { const drawer = document.getElementById('history-drawer'); const overlay = document.getElementById('history-drawer-overlay'); const isOpen = drawer.classList.contains('open'); if (isOpen) { drawer.classList.remove('open'); overlay.classList.remove('open'); } else { drawer.classList.add('open'); overlay.classList.add('open'); loadSemesterHistory(); } }
function closeHistoryDrawer() { document.getElementById('history-drawer').classList.remove('open'); document.getElementById('history-drawer-overlay').classList.remove('open'); }

async function loadSemesterHistory() {
    try { const res = await fetch('/semester-history', { headers: { 'Accept': 'application/json' } }); if (!res.ok) return; const data = await res.json(); renderHistoryDrawer(data); updateHistoryBadge(data.length); }
    catch (err) { console.warn('[History load error]', err); }
}

function updateHistoryBadge(count) { const badge = document.getElementById('history-count-badge'); if (!badge) return; if (count > 0) { badge.textContent = count; badge.classList.add('visible'); } else { badge.classList.remove('visible'); } }

function renderHistoryDrawer(histories) {
    const emptyEl = document.getElementById('history-empty'); const listEl = document.getElementById('history-list'); if (!listEl) return;
    if (!histories || histories.length === 0) { emptyEl.style.display = 'flex'; listEl.innerHTML = ''; return; }
    emptyEl.style.display = 'none';
    const sorted = [...histories].sort((a, b) => b.semester_number - a.semester_number);
    listEl.innerHTML = sorted.map((h, idx) => {
        const gpaColor = h.gpa >= 8 ? '#16a34a' : h.gpa >= 6.5 ? '#1a3a3a' : h.gpa >= 5 ? '#d97706' : '#dc2626';
        const passRate = h.total_credits > 0 ? Math.round((h.passed_credits / h.total_credits) * 100) : 0;
        const itemsHtml = (h.items || []).map(item => { const gradeClass = item.grade === null ? 'empty' : item.status === 'pass' ? 'pass' : 'fail'; const gradeText = item.grade !== null ? item.grade : '—'; return `<div class="history-subject-row ${item.status || ''}"><span class="history-subject-name">${item.subject_name || '?'}</span><span class="history-subject-credits">${item.credits ?? '?'} TC</span><span class="history-subject-grade ${gradeClass}">${gradeText}</span></div>`; }).join('');
        return `<div class="history-sem-block" id="history-block-${h.id}">
            <div class="history-sem-header" onclick="toggleSemBlock(${h.id})">
                <div>
                    <div class="history-sem-title">🎓 Học kỳ ${h.semester_number} ${h.academic_year ? `<span style="font-size:.72rem;font-weight:500;color:var(--muted);">(${h.academic_year})</span>` : ''}</div>
                    <div class="history-sem-meta" style="margin-top:3px;">
                        ${h.gpa !== null ? `<span class="history-sem-pill gpa">GPA: ${h.gpa}</span>` : ''}
                        <span class="history-sem-pill credits">✓ ${h.passed_credits}/${h.total_credits} TC</span>
                        ${h.completed_at ? `<span class="history-sem-pill date">📅 ${h.completed_at}</span>` : ''}
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
                    ${itemsHtml || '<div style="padding:10px var(--sp-lg);color:var(--muted);font-size:.84rem;">Không có dữ liệu môn học</div>'}
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

function toggleSemBlock(id) { const block = document.getElementById(`history-block-${id}`); if (block) block.classList.toggle('open'); }
document.addEventListener('DOMContentLoaded', () => { loadSemesterHistory(); });

// ═══════════════════════════════════════════════════════════════
// DASHBOARD OVERVIEW PANEL
// ═══════════════════════════════════════════════════════════════
function renderDashboard() {
    let totalEarned = 0, totalGraded = 0, totalFail = 0, allGrades = [];
    document.querySelectorAll('.grade-input').forEach(input => {
        const val = parseFloat(input.value); const credits = parseInt(input.dataset.credits || 0); const sid = parseInt(input.dataset.subjectId);
        if (isNaN(val) || input.value === '') return;
        totalGraded++; if (val >= 5.0) totalEarned += credits; else totalFail++;
        allGrades.push({ id: sid, grade: val, credits });
    });
    const subjectMap = {};
    for (const subs of Object.values(SUBJECTS_BY_SEM)) { subs.forEach(s => { subjectMap[s.id] = s; }); }
    allGrades = allGrades.map(g => ({ ...g, groupName: subjectMap[g.id]?.skillGroupName || 'Khác', name: subjectMap[g.id]?.name || '?' }));

    const currentSem = getCurrentSemester();
    const totalSem = 8;
    const remSem = Math.max(1, totalSem - currentSem + 1);
    const remCredits = Math.max(0, TOTAL_CREDITS - totalEarned);
    const neededPerSem = Math.ceil(remCredits / remSem);
    const progPct = Math.min(100, Math.round((totalEarned / TOTAL_CREDITS) * 100));
    const thisSemCredits = currentCourses.reduce((s, c) => s + (parseInt(subjectMap[c.id]?.credits || 0)), 0);

    // Card 1: Tiến độ
    const fill = document.getElementById('dash-prog-fill');
    const pctEl = document.getElementById('dash-prog-pct');
    const earnEl = document.getElementById('dash-credit-earned');
    const leftEl = document.getElementById('dash-prog-left');
    const remSemEl = document.getElementById('dash-prog-rem-sem');
    const passEl = document.getElementById('dash-pass-credits');
    const needEl = document.getElementById('dash-needed-per-sem');
    const thisEl = document.getElementById('dash-current-sem');

    if (earnEl) earnEl.textContent = totalEarned;
    if (leftEl) leftEl.textContent = `Còn lại: ${remCredits} TC`;
    if (remSemEl) remSemEl.textContent = `${remSem} kỳ còn`;
    if (passEl) passEl.textContent = totalEarned;
    if (needEl) needEl.textContent = neededPerSem;
    if (thisEl) thisEl.textContent = thisSemCredits || currentCourses.length > 0 ? thisSemCredits : '--';

    const progColor = progPct >= 75 ? 'var(--success)' : progPct >= 40 ? 'var(--ink)' : 'var(--brand-ochre)';
    if (fill) { fill.style.background = progColor; fill.style.width = '0%'; setTimeout(() => { fill.style.width = `${progPct}%`; }, 80); }
    const pctClass = progPct >= 75 ? 'great' : progPct >= 40 ? 'mid' : 'low';
    if (pctEl) { pctEl.textContent = `${progPct}%`; pctEl.className = `dash-prog-pct ${pctClass}`; }

    // Card 2: Thế mạnh / Điểm yếu
    const strengthEl = document.getElementById('dash-strength-content');
    if (strengthEl) {
        if (allGrades.length === 0) { strengthEl.innerHTML = `<div class="dash-no-data"><div class="dash-no-data-icon">⭐</div><div>Nhập điểm để xem</div></div>`; }
        else {
            const groups = {};
            allGrades.forEach(g => { const gn = g.groupName; if (!groups[gn]) groups[gn] = { grades: [], name: gn }; groups[gn].grades.push(g.grade); });
            const groupStats = Object.values(groups).map(g => ({ name: g.name, avg: Math.round(g.grades.reduce((s, v) => s + v, 0) / g.grades.length * 10) / 10, count: g.grades.length })).sort((a, b) => b.avg - a.avg);
            const avgCls = avg => avg >= 8 ? 'ex' : avg >= 6.5 ? 'good' : avg >= 5 ? 'ok' : 'bad';
            const barColor = avg => avg >= 8 ? '#22c55e' : avg >= 6.5 ? '#1a3a3a' : avg >= 5 ? '#e8b94a' : '#ef4444';
            const top3 = groupStats.slice(0, 3);
            const bottom2 = groupStats.length > 3 ? groupStats.slice(-2).filter(g => g.avg < 6.5) : [];
            const renderRow = g => `<div class="dash-strength-row"><span class="dash-strength-name">${g.name}</span><div class="dash-strength-bar-wrap"><div class="dash-strength-bar-track"><div class="dash-strength-bar-fill" style="width:${g.avg * 10}%;background:${barColor(g.avg)};"></div></div></div><span class="dash-strength-avg ${avgCls(g.avg)}">${g.avg}</span></div>`;
            let html = `<div class="dash-sw-grid">`;
            html += `<div class="dash-sw-col"><div style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:10px;">🌟 Thế mạnh</div><div class="dash-strength-list">${top3.map(renderRow).join('')}</div></div>`;
            if (bottom2.length > 0) {
                html += `<div class="dash-sw-col"><div style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#dc2626;margin-bottom:10px;">⚠ Cần cải thiện</div><div class="dash-strength-list">${bottom2.map(renderRow).join('')}</div></div>`;
            } else {
                html += `<div class="dash-sw-col"><div style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#dc2626;margin-bottom:10px;">⚠ Cần cải thiện</div><div style="font-size:0.75rem;color:var(--muted-soft);text-align:center;margin-top:10px;">Không có điểm yếu đáng kể</div></div>`;
            }
            html += `</div>`;
            strengthEl.innerHTML = html;
        }
    }

    // Card 3: Gợi ý tín chỉ
    const badgeEl = document.getElementById('dash-advice-badge');
    const numEl = document.getElementById('dash-advice-num');
    const reasonEl = document.getElementById('dash-advice-reason');
    if (allGrades.length === 0) {
        if (badgeEl) { badgeEl.className = 'dash-advice-badge maintain'; badgeEl.textContent = '• Chưa có dữ liệu'; }
        if (numEl) { numEl.className = 'dash-advice-num same'; numEl.textContent = '--'; }
        if (reasonEl) reasonEl.textContent = 'Nhập điểm các môn để nhận gợi ý.';
    } else {
        const overallGpa = Math.round(allGrades.reduce((s, g) => s + g.grade, 0) / allGrades.length * 10) / 10;
        const passRate = allGrades.filter(g => g.grade >= 5).length / allGrades.length;
        const avgPerSem = thisSemCredits || neededPerSem;
        const savedRec = localStorage.getItem('recommended_credits_per_sem');
        const baseCredits = savedRec ? parseInt(savedRec) : avgPerSem;
        let recType, recLabel, recCredits, recReason, numClass;
        if (savedRec) {
            recType = 'maintain'; recLabel = ''; recCredits = parseInt(savedRec); numClass = 'same';
            recReason = `Hệ thống khuyến nghị mức tải ${recCredits} TC/kỳ dựa trên đánh giá tiến độ của bạn.`;
        } else if (overallGpa >= 7.5 && passRate >= 0.85) {
            recType = 'increase';
            numClass = 'up';
            if (neededPerSem > 20) {
                recLabel = '';
                recCredits = Math.min(24, neededPerSem + 2);
                recReason = `Thành tích học tập rất tốt (GPA ${overallGpa}). Hệ thống khuyến nghị tăng mức tải lên ${recCredits} TC/kỳ để nhanh chóng bắt kịp tiến độ.`;
            } else {
                recLabel = '';
                recCredits = Math.max(neededPerSem + 3, 20); // Gợi ý mức cao để ra trường sớm
                recCredits = Math.min(24, recCredits);
                recReason = `Thành tích học tập của bạn rất xuất sắc (GPA ${overallGpa}). Hệ thống khuyến nghị mức tải ${recCredits} TC/kỳ. Bạn hoàn toàn có khả năng ra trường sớm hơn dự kiến!`;
            }
        }
        else if (overallGpa < 5.5 || passRate < 0.6) {
            recType = 'decrease';
            if (neededPerSem > 20) {
                recLabel = '';
                recCredits = 15; // Giới hạn cảnh báo học vụ
                numClass = 'same';
                recReason = `GPA thấp nhưng tiến độ rất chậm. Đề xuất học 15 TC để cân bằng chất lượng và không bị trễ hạn quá lâu.`;
            } else {
                recLabel = '↓ Nên giảm tín chỉ';
                recCredits = Math.max(10, neededPerSem - 3);
                numClass = 'down';
                recReason = `GPA ${overallGpa} thấp. Giảm tải để tập trung vào chất lượng.`;
            }
        }
        else if (neededPerSem > (baseCredits || 15) + 4) { recType = 'increase'; recLabel = '↑ Cần tăng để kịp'; recCredits = Math.min(24, neededPerSem); numClass = 'up'; recReason = `Cần ${neededPerSem} TC/kỳ để tốt nghiệp đúng hạn trong ${remSem} kỳ.`; }
        else { recType = 'maintain'; recLabel = '= Giữ nguyên'; recCredits = neededPerSem; numClass = 'same'; recReason = `Đang đúng tiến độ. Cần ~${neededPerSem} TC/kỳ trong ${remSem} kỳ còn lại.`; }
        if (badgeEl) { badgeEl.className = `dash-advice-badge ${recType}`; badgeEl.textContent = recLabel; }
        if (numEl) { numEl.className = `dash-advice-num ${numClass}`; numEl.textContent = recCredits; }
        if (reasonEl) reasonEl.innerHTML = recReason;
        const ccRec = document.getElementById('cc-recommend');
        if (ccRec) ccRec.textContent = `Khuyên dùng: ${recCredits} TC`;

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

const __origGradeChangeDash = window.onGradeChange;
window.onGradeChange = function (id, input, skipSave = false) { __origGradeChangeDash(id, input, skipSave); clearTimeout(window._dashTimer); window._dashTimer = setTimeout(renderDashboard, 500); };
const __origLoadGradesDash = window.loadGradesFromDB;
window.loadGradesFromDB = async function () { await __origLoadGradesDash(); setTimeout(renderDashboard, 300); };
['academic_year'].forEach(id => { document.getElementById(id)?.addEventListener('change', () => { clearTimeout(window._dashTimer); window._dashTimer = setTimeout(renderDashboard, 300); }); });
document.addEventListener('DOMContentLoaded', () => {
    renderDashboard();
    // Luôn load dữ liệu thực từ API cho dashboard (không chỉ dựa vào DOM inputs)
    fetchProgress();
    fetchSuggestions();
});

function openPrereqModal(subjectData) {
    let subject;
    try {
        subject = typeof subjectData === 'string' ? JSON.parse(subjectData) : subjectData;
    } catch (e) { return; }

    document.getElementById('prereq-subject-name').innerHTML = subject.name + ' <span class="pill pill-lavender" style="font-size:0.7rem;margin-left:6px;vertical-align:1px;">Học kỳ chuẩn: ' + (subject.semester?.name || '1') + '</span>';
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

// ═══════════════════════════════════════════════════════════════
// STUDY PLANNER (NEW FEATURE)
// ═══════════════════════════════════════════════════════════════
async function fetchStudyPlans() {
    try {
        const res = await fetch('/api/v1/study-plans');
        if (!res.ok) return;
        const resData = await res.json();
        if (resData.success && resData.data && resData.data.length > 0) {
            // Render the latest plan
            const latestPlan = resData.data[resData.data.length - 1];
            renderStudyPlan(latestPlan);
        }
    } catch (e) {
        console.error('Lỗi khi fetch study plans', e);
    }
}

// Initialize fetch study plans on load
window.addEventListener('DOMContentLoaded', () => {
    fetchStudyPlans();
    checkActivePlanStatus();
});

async function saveCurrentPlan(planId) {
    const name = prompt("Nhập tên để dễ nhớ cho kế hoạch này:", window.currentActivePlan.name);
    if (name === null) return;
    
    try {
        const res = await fetch(`/api/v1/study-plans/${planId}/save`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ name: name })
        });
        const resData = await res.json();
        if (resData.success) {
            showToast('Đã lưu kế hoạch thành công!', 'success');
            window.currentActivePlan.is_saved = true;
            window.currentActivePlan.name = name;
            renderStudyPlan(window.currentActivePlan);
        } else {
            showToast('Có lỗi xảy ra khi lưu kế hoạch', 'error');
        }
    } catch(e) {
        showToast('Lỗi mạng', 'error');
    }
}

async function fetchSavedPlansList() {
    const listContainer = document.getElementById('inline-saved-plans-list');
    if (!listContainer) return;
    listContainer.innerHTML = '<p style="color:var(--muted); text-align:center; padding:20px;">Đang tải danh sách...</p>';
    try {
        const res = await fetch('/api/v1/study-plans/saved');
        const resData = await res.json();
        if(resData.success && resData.data.length > 0) {
            listContainer.innerHTML = resData.data.map(plan => `
                <div class="saved-plan-item" style="padding:12px 16px; border:1px solid var(--hairline); border-radius:8px; background:var(--surface); transition:all 0.2s; display:flex; justify-content:space-between; align-items:center;">
                    <div style="flex:1; cursor:pointer;" onclick="loadSavedPlan(${plan.id})">
                        <div style="font-weight:600; margin-bottom:4px;">${plan.name} <span class="pill pill-lavender" style="font-size:0.7rem; padding:2px 6px;">${plan.target_semesters || plan.target_semester_count || 8} kỳ</span></div>
                        <div style="font-size:0.8rem; color:var(--muted);">Cập nhật: ${new Date(plan.updated_at).toLocaleString('vi-VN')}</div>
                    </div>
                    <button onclick="deleteSavedPlan(${plan.id}, event)" title="Xóa kế hoạch này" style="background:none; border:none; color:var(--red); padding:8px; cursor:pointer; border-radius:8px; display:flex; align-items:center; justify-content:center; transition:background 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='none'">
                        <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    </button>
                </div>
            `).join('');
        } else {
            listContainer.innerHTML = '<p style="color:var(--muted); text-align:center;">Chưa có kế hoạch nào được lưu.</p>';
        }
    } catch(e) {
        listContainer.innerHTML = '<p style="color:var(--error); text-align:center;">Lỗi tải dữ liệu.</p>';
    }
}

async function loadSavedPlan(planId) {
    const loader = document.getElementById('planner-loader');
    const container = document.getElementById('study-plan-results');
    loader.style.display = 'block';
    container.style.opacity = '0.3';
    try {
        const res = await fetch(`/api/v1/study-plans/${planId}/load`);
        const resData = await res.json();
        if(resData.success) {
            renderStudyPlan(resData.data);
            showToast('Đã tải kế hoạch đã lưu!', 'success');
            fetchSuggestions(); // Cập nhật lại danh sách gợi ý theo mode của kế hoạch mới tải
        }
    } catch(e) {
        showToast('Lỗi khi tải kế hoạch.', 'error');
    } finally {
        loader.style.display = 'none';
        container.style.opacity = '1';
    }
}

function backToPlannerSelection() {
    window.currentActivePlan = null;
    window._advisoryCheckedForPlan = null;
    document.getElementById('planner-selection-view').style.display = 'block';
    document.getElementById('study-plan-results').style.display = 'none';
}

async function checkActivePlanStatus() {
    try {
        const res = await fetch('/api/v1/study-plans/active');
        const resData = await res.json();
        const selectionView = document.getElementById('planner-selection-view');
        if (resData.success && resData.data) {
            window.currentActivePlan = resData.data;
            if (selectionView) selectionView.style.display = 'none';
            const curSems = resData.data.target_semesters || resData.data.target_semester_count || 8;
            selectConfigGoal(curSems);
        } else {
            if (selectionView) selectionView.style.display = 'block';
            selectConfigGoal(window._selectedGoalSems || 8);
        }
    } catch(e) {
        console.error('Error checkActivePlanStatus', e);
    }
}

async function changeActivePlanMode() {
    // Deprecated — forward to applyTargetAdjustment
    await applyTargetAdjustment();
}

async function deleteSavedPlan(planId, event) {
    if (event) event.stopPropagation(); // Ngăn sự kiện click vào item
    
    if (!confirm('Bạn có chắc chắn muốn xóa kế hoạch này không?')) return;

    try {
        const res = await fetch(`/api/v1/study-plans/${planId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            }
        });
        const resData = await res.json();
        if (resData.success) {
            showToast('Đã xóa kế hoạch thành công!', 'success');
            // Nếu kế hoạch đang mở là kế hoạch bị xóa, thì trở lại danh sách
            if (window.currentActivePlan && window.currentActivePlan.id === planId) {
                backToPlannerSelection();
            } else {
                fetchSavedPlansList();
            }
        } else {
            showToast(resData.error || 'Lỗi khi xóa kế hoạch', 'error');
        }
    } catch (e) {
        showToast('Lỗi mạng khi xóa', 'error');
    }
}

async function generateStudyPlan() {
    const targetSemesters = window._selectedGoalSems || 8;
    const name = document.getElementById('planner-name').value.trim();

    if (!name) {
        showToast('Vui lòng nhập tên kế hoạch!', 'error');
        return;
    }
    const loader = document.getElementById('planner-loader');
    const container = document.getElementById('study-plan-results');

    loader.style.display = 'block';
    container.style.opacity = '0.3';

    try {
        const res = await fetch('/api/v1/study-plans/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ target_semesters: targetSemesters, name })
        });
        if (!res.ok) throw new Error('API error');
        const resData = await res.json();
        if (resData.success && resData.data) {
            renderStudyPlan(resData.data);
            fetchSavedPlansList();
            fetchSuggestions();

            if (resData.over_semesters) {
                showOverSemestersNotice(resData.over_semesters_notice
                    || `Kế hoạch cần thêm ${resData.over_semesters_count} học kỳ so với mục tiêu ${targetSemesters} kỳ.`);
            } else {
                showToast(`Tạo kế hoạch thành công! ~${resData.tc_per_sem} TC/kỳ`, 'success');
            }
        }
    } catch (e) {
        showToast('Lỗi khi tạo kế hoạch học tập.', 'error');
    } finally {
        loader.style.display = 'none';
        container.style.opacity = '1';
    }
}

function renderStudyPlan(plan) {
    window.currentActivePlan = plan; // Store active plan for suggestions

    const container = document.getElementById('study-plan-results');
    document.getElementById('planner-selection-view').style.display = 'none';
    container.style.display = 'block';

    if (!plan.semesters || plan.semesters.length === 0) {
        container.innerHTML = `<div class="empty-state">Không có môn học nào cần học nữa. Bạn đã đủ tín chỉ!</div>`;
        return;
    }

    // ── Tính trạng thái tốt nghiệp ────────────────────────────────
    const targetSems      = plan.target_semesters || plan.target_semester_count || 8;
    const totalPlanned    = plan.semesters.length;
    const completedSems   = plan.semesters.filter(s => s.subjects.length > 0 && s.subjects.every(ss => ss.is_completed)).length;
    const delta           = totalPlanned - targetSems;

    const yearLabel = n => {
        const months = n * 6;
        if (months < 12) return `${months} tháng`;
        const y = months / 12;
        return y === Math.floor(y) ? `${y} năm` : `${y} năm`;
    };

    let statusColor, statusIcon, statusText, statusDesc;
    if (delta <= 0) {
        statusColor = '#10b981';
        statusIcon  = '✅';
        statusText  = delta === 0 ? 'Đúng hạn' : `Sớm ${Math.abs(delta)} HK`;
        statusDesc  = delta === 0 ? `Kế hoạch khớp đúng ${targetSems} kỳ mục tiêu.` : `Hoàn thành sớm hơn ${Math.abs(delta)} kỳ (${yearLabel(Math.abs(delta))}) so với mục tiêu!`;
    } else if (delta === 1) {
        statusColor = '#f59e0b';
        statusIcon  = '⚠️';
        statusText  = `Trễ hơn 1 HK (nửa năm)`;
        statusDesc  = `Dự kiến ${totalPlanned} kỳ, vượt mục tiêu ${targetSems} kỳ 1 học kỳ (6 tháng). Tăng TC/kỳ để rút ngắn.`;
    } else {
        statusColor = '#ef4444';
        statusIcon  = '🔴';
        statusText  = `Trễ hơn ${delta} HK (~${yearLabel(delta)})`;
        statusDesc  = `Dự kiến ${totalPlanned} kỳ, vượt mục tiêu ${targetSems} kỳ ${delta} học kỳ (~${yearLabel(delta)}). Cần tăng TC/kỳ đáng kể.`;
    }

    let html = `
        <div style="background:var(--surface-soft); border:1px solid var(--hairline); border-radius:14px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                <div style="display:flex; flex-direction:column; gap:2px;">
                    <span style="font-size:0.72rem; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.04em;">Đang áp dụng</span>
                    <span style="font-size:1.35rem; font-weight:800; color:var(--ink); font-family:'Sora',sans-serif; line-height:1;">${plan.tc_per_sem || 18} <span style="font-size:0.78rem; font-weight:500; color:var(--muted);">TC/kỳ</span></span>
                </div>
                <div style="width:1px; height:36px; background:var(--hairline);"></div>
                <div style="display:flex; flex-direction:column; gap:2px;">
                    <span style="font-size:0.72rem; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.04em;">Mục tiêu</span>
                    <span style="font-size:1.35rem; font-weight:800; color:var(--ink); font-family:'Sora',sans-serif; line-height:1;">${targetSems} <span style="font-size:0.78rem; font-weight:500; color:var(--muted);">học kỳ</span></span>
                </div>
                <div style="width:1px; height:36px; background:var(--hairline);"></div>
                <div style="display:flex; flex-direction:column; gap:2px;" title="${statusDesc}">
                    <span style="font-size:0.72rem; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.04em;">Tiến độ</span>
                    <span style="font-size:1.1rem; font-weight:800; color:${statusColor}; font-family:'Sora',sans-serif; line-height:1;">${statusIcon} ${statusText}</span>
                    <span style="font-size:0.75rem; color:var(--muted);">Dự kiến ${totalPlanned} kỳ · đã xong ${completedSems} kỳ</span>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <button onclick="fetchAndShowAdvisory(${plan.id})" id="advisory-btn-${plan.id}"
                    style="background:var(--surface); border:1px solid var(--hairline); padding:7px 14px; border-radius:8px; cursor:pointer; font-size:0.82rem; font-weight:600; color:var(--ink); display:flex; align-items:center; gap:6px; transition:all 0.2s; white-space:nowrap;"
                    onmouseover="this.style.borderColor='var(--brand-mint)'" onmouseout="this.style.borderColor='var(--hairline)'">
                    💡 Tư vấn điều chỉnh
                </button>
                <span style="color:#10b981; font-weight:600; font-size:0.82rem; display:flex; align-items:center; gap:4px; white-space:nowrap;" title="Mọi thay đổi được lưu tự động.">✅ Đã lưu</span>
            </div>
        </div>
        <div style="display:flex; flex-direction:column; gap:20px;">
    `;


    // Xác định học kỳ hiện tại = kỳ đầu tiên có môn chưa hoàn thành (không phải retake)
    let currentSemIdx = -1;
    for (const sem of plan.semesters) {
        const hasIncomplete = sem.subjects.some(ss => !ss.is_completed && !ss.is_retake);
        if (hasIncomplete) { currentSemIdx = sem.semester_index; break; }
    }

    const maxCr = plan.tc_per_sem || 18;

    plan.semesters.forEach(sem => {
        const isPast    = currentSemIdx > 0 && sem.semester_index < currentSemIdx;
        const isCurrent = sem.semester_index === currentSemIdx;

        // Credit utilization bar
        const usedCr  = sem.expected_credits || 0;

        // Tính TC bắt buộc / tự chọn
        const electiveCr = (sem.elective_groups || []).reduce((sum, g) => {
            return sum + g.options.filter(o => o.selected).reduce((s, o) => s + o.credits, 0);
        }, 0);
        const mandatoryCr = usedCr - electiveCr;

        const barPct  = Math.min(100, Math.round((usedCr / maxCr) * 100));
        // Kỳ đã qua: không tô đỏ (dữ liệu lịch sử, giới hạn mode hiện tại không áp dụng ngược)
        const barColor = isPast
            ? '#10b981'
            : (usedCr > maxCr ? '#ef4444' : (usedCr >= maxCr * 0.85 ? '#f59e0b' : '#10b981'));

        const lockIcon = isPast
            ? `<span title="Kỳ đã hoàn thành — không thể kéo môn vào/ra.\nBạn vẫn có thể cập nhật điểm nếu nhập sai."
                     style="cursor:help; font-size:0.85rem; opacity:0.6; margin-left:2px;">🔒</span>`
            : '';

        const semBadge = isPast
            ? `<span class="pill" style="background:#f3f4f6; color:#9ca3af; font-size:0.7rem;">Đã hoàn thành</span>`
            : isCurrent
                ? `<span class="pill" style="background:#dbeafe; color:#2563eb; font-size:0.7rem;">Hiện tại</span>`
                : '';

        // Kỳ đã qua vẫn nhận drop nhưng qua handler riêng để hiển thị cảnh báo
        const dropHandlers = isPast
            ? `ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDropToPast(event, ${plan.id}, ${sem.semester_index})"`
            : `ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event, ${plan.id}, ${sem.semester_index})"`;

        html += `
            <div class="clay-card study-plan-semester${isPast ? ' sem-past' : ''}"
                 data-semester-index="${sem.semester_index}"
                 ${dropHandlers}>
                <div class="card-title-row" style="border-bottom:1px solid var(--hairline); padding-bottom:10px; margin-bottom:12px; pointer-events:none;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <strong>Học kỳ ${sem.semester_index}</strong>
                        ${semBadge}
                        ${lockIcon}
                    </div>
                    <span class="pill" style="background:#e8f8f3; color:#10b981;">${usedCr} TC</span>
                </div>
                <div style="margin-bottom:10px; pointer-events:none;">
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.72rem; color:var(--muted); margin-bottom:4px;">
                        <span>Tải tín chỉ</span>
                        <div style="display:flex; align-items:center; gap:6px;">
                            ${mandatoryCr > 0 ? `<span style="color:#374151;"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#10b981;margin-right:3px;vertical-align:middle;"></span>${mandatoryCr} TC bắt buộc</span>` : ''}
                            ${electiveCr > 0 ? `<span style="color:#374151;"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#3b82f6;margin-right:3px;vertical-align:middle;"></span>${electiveCr} TC tự chọn</span>` : ''}
                            <span style="color:var(--muted);">${usedCr}/${maxCr} TC</span>
                        </div>
                    </div>
                    <div style="height:5px; background:var(--hairline); border-radius:99px; overflow:hidden; display:flex;">
                        ${mandatoryCr > 0 ? `<div style="height:100%; width:${Math.round((mandatoryCr/maxCr)*100)}%; background:#10b981; border-radius:99px 0 0 99px; transition:width 0.4s;"></div>` : ''}
                        ${electiveCr  > 0 ? `<div style="height:100%; width:${Math.round((electiveCr /maxCr)*100)}%; background:#3b82f6; transition:width 0.4s;"></div>` : ''}
                    </div>
                </div>
                <div class="semester-subjects-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:12px; min-height: 50px;">
        `;

        // Tập hợp subject_id thuộc nhóm tự chọn (sẽ render qua group frame, không render riêng)
        const egSubjectIds = new Set(
            (sem.elective_groups || []).flatMap(g => g.options.map(o => o.id))
        );

        sem.subjects.forEach(ss => {
            const sub = ss.subject;
            if (!sub) return;
            if (egSubjectIds.has(sub.id)) return; // handled by group frame below

            // ── Điểm riêng của row này ──────────────────────────────────────
            const grade     = ss.subject_grade ?? ss.grade;
            const hasGrade  = grade !== undefined && grade !== null;
            const isCompleted = hasGrade && grade >= 5.0;
            const isFailed    = hasGrade && grade < 5.0;

            const cardBg     = isCompleted ? '#f0fdf4' : (isFailed ? '#fef2f2' : 'var(--surface)');
            const borderColor= isCompleted ? '#86efac' : (isFailed ? '#fca5a5' : 'var(--hairline)');

            const draggableAttr = (!isCompleted && !isPast)
                ? `draggable="true" ondragstart="handleDragStart(event, ${sub.id}, ${sem.semester_index})" ondragend="handleDragEnd(event)"`
                : '';

            const isSuggested = window.currentSuggestions && window.currentSuggestions.some(s => s.id === sub.id);
            const highlyRecommendedClass = (!isCompleted && isSuggested) ? 'highly-recommended' : '';

            let statusHtml = '';
            if (isCompleted) {
                statusHtml = '<span style="color:#10b981; font-size:0.85rem; font-weight:600;">✓ Pass</span>';
            } else if (isFailed) {
                statusHtml = '<span style="color:#ef4444; font-size:0.85rem; font-weight:600;">✗ Rớt</span>';
            }

            const prereqInfo = sub.prerequisites_info || [];
            let prereqTooltipHtml = '';
            if (prereqInfo.length > 0) {
                const typeLabel = { explicit: 'Tiên quyết', corequisite: 'Song hành', group: 'Nhóm' };
                const items = prereqInfo.map(p => {
                    const icon = p.is_passed ? '✅' : (p.type === 'corequisite' ? '🔗' : '⏳');
                    const color = p.is_passed ? '#10b981' : (p.type === 'corequisite' ? '#7c3aed' : '#f59e0b');
                    const tag = typeLabel[p.type] || p.type;
                    return `<div style="display:flex;align-items:center;gap:6px;font-size:0.75rem;margin-bottom:3px;">
                        <span>${icon}</span>
                        <span style="color:${color};">${p.name}</span>
                        <span style="color:#9ca3af;font-size:0.68rem;">(${tag})</span>
                    </div>`;
                }).join('');
                prereqTooltipHtml = `
                    <div class="prereq-tooltip-wrap" style="position:relative; display:inline-block;" onmousedown="event.stopPropagation()">
                        <button type="button" title="Tiên quyết"
                                onmouseenter="this.nextElementSibling.style.display='block'"
                                onmouseleave="this.nextElementSibling.style.display='none'"
                                style="border:none; background:transparent; padding:2px 4px; cursor:pointer; color:var(--muted); font-size:0.75rem; display:flex; align-items:center; gap:3px;">
                            🔗 <span style="text-decoration:underline dotted;">Tiên quyết (${prereqInfo.length})</span>
                        </button>
                        <div style="display:none; position:absolute; bottom:calc(100% + 6px); left:0; background:var(--surface); border:1px solid var(--hairline); border-radius:8px; padding:8px 10px; min-width:200px; box-shadow:0 4px 12px rgba(0,0,0,0.12); z-index:50;">
                            <div style="font-size:0.7rem; font-weight:600; color:var(--muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:.04em;">Tiên quyết</div>
                            ${items}
                        </div>
                    </div>`;
            }

            const cascadeBtn = isFailed
                ? `<button type="button" title="Xem ảnh hưởng chuỗi"
                           style="border:1px solid #fca5a5; background:#fef2f2; color:#ef4444; border-radius:6px; padding:3px 8px; font-size:0.72rem; cursor:pointer; margin-top:6px; display:flex; align-items:center; gap:4px;"
                           onmousedown="event.stopPropagation()"
                           onclick="openCascadeModal(${sub.id}, ${JSON.stringify(sub.name).replace(/'/g, "&#39;")})">
                         ⚠ Xem ảnh hưởng
                       </button>`
                : '';

            let retakeBadge = '';
            if (ss.is_retake) {
                if (hasGrade && isFailed) {
                    retakeBadge = `<div style="position:absolute; top:0; left:0; right:0; background:#fef3c7; color:#92400e; font-size:0.68rem; font-weight:700; padding:3px 8px; border-radius:8px 8px 0 0; display:flex; align-items:center; gap:4px;">
                        📌 Đã thử – Rớt lại (điểm ${grade}) — lịch sử lần học này</div>`;
                } else if (!hasGrade) {
                    retakeBadge = `<div style="position:absolute; top:0; left:0; right:0; background:#fee2e2; color:#991b1b; font-size:0.68rem; font-weight:700; padding:3px 8px; border-radius:8px 8px 0 0; display:flex; align-items:center; gap:4px;">
                        🔄 Cần học lại kỳ này</div>`;
                }
            }
            const retakePadTop = retakeBadge ? 'padding-top:28px;' : '';

            html += `
                <div class="study-plan-subject ${highlyRecommendedClass}"
                     id="plan-subject-${sub.id}-${ss.id}"
                     ${draggableAttr}
                     style="padding:12px; ${retakePadTop} border:1px solid ${borderColor}; border-radius:8px; background:${cardBg}; position:relative; scroll-margin-top:100px;">

                    ${retakeBadge}

                    <button type="button" class="icon-btn"
                            style="position:absolute; top:${retakeBadge ? '30px' : '8px'}; right:8px; padding:4px; border:none; background:transparent; color:var(--muted); cursor:pointer; z-index:2;"
                            onclick='openPrereqModal(${JSON.stringify(sub).replace(/'/g, "&#39;")})'
                            title="Xem chi tiết môn"
                            onmousedown="event.stopPropagation()">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:20px;height:20px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                    </button>

                    <div style="font-weight:600; font-size:0.95rem; margin-bottom:4px; padding-right:24px;">${sub.name}</div>
                    <div style="font-size:0.8rem; color:var(--muted); margin-bottom:6px;">${sub.credits} TC | Nhóm: ${sub.skill_group_id || 'Chung'}</div>
                    ${sub.is_elective === false
                        ? `<span style="display:inline-block;font-size:0.68rem;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:4px;padding:1px 6px;margin-bottom:6px;">✔ Bắt buộc</span>`
                        : ''
                    }

                    ${prereqTooltipHtml}

                    <div style="display:flex; align-items:center; gap:8px; margin-top:8px;" onmousedown="event.stopPropagation()">
                        <input type="number"
                               class="ob-grade-input"
                               placeholder="Điểm..."
                               min="0" max="10" step="0.1"
                               value="${hasGrade ? grade : ''}"
                               data-plan-subject-id="${ss.id}"
                               style="width:80px; height:32px; font-size:0.85rem;"
                               onchange="updatePlanGrade(${plan.id}, ${sub.id}, this)">
                        ${statusHtml}
                    </div>
                    ${cascadeBtn}
                </div>
            `;
        });

        // ── Elective group frames ────────────────────────────────────────────
        // ── Elective group frames (card-based) ─────────────────────────────
        (sem.elective_groups || []).forEach(group => {
            const frameId    = `eg-frame-${plan.id}-${sem.semester_index}-${group.id}`;
            const needCr     = group.required_credits;

            // Map plan subjects (selected=true) by id for grade data
            const planSsMap  = {};
            sem.subjects.forEach(ss => {
                if (ss.subject && group.options.some(o => o.id === ss.subject.id)) {
                    planSsMap[ss.subject.id] = ss;
                }
            });

            // How many TC selected (in plan) and how many graded
            let selectedCr = 0;
            let gradedCr   = 0;
            group.options.forEach(opt => {
                const ss = planSsMap[opt.id];
                if (ss && opt.selected) {
                    selectedCr += opt.credits;
                    const g = ss.subject_grade ?? ss.grade;
                    if (g !== null && g !== undefined) gradedCr += opt.credits;
                }
            });

            // Plan subject ids (for group drag header)
            const planIds = group.options.filter(o => o.selected).map(o => o.id);

            let cardsHtml = '';
            group.options.forEach(opt => {
                const ss        = planSsMap[opt.id];
                const isInPlan  = !!ss && opt.selected;

                if (isInPlan) {
                    const grade       = ss.subject_grade ?? ss.grade;
                    const hasGrade    = grade !== null && grade !== undefined;
                    const isCompleted = hasGrade && grade >= 5.0;
                    const isFailed    = hasGrade && grade < 5.0;
                    const cardBg      = isCompleted ? '#f0fdf4' : (isFailed ? '#fef2f2' : 'var(--surface)');
                    const borderColor = isCompleted ? '#86efac' : (isFailed ? '#fca5a5' : '#93c5fd');
                    const limitReached = !hasGrade && gradedCr >= needCr; // gradedCr: already-graded TC
                    let statusHtml = '';
                    if (isCompleted) statusHtml = '<span style="color:#10b981;font-size:0.85rem;font-weight:600;">✓ Pass</span>';
                    else if (isFailed) statusHtml = '<span style="color:#ef4444;font-size:0.85rem;font-weight:600;">✗ Rớt</span>';

                    const subJson = JSON.stringify(ss.subject ?? {}).replace(/'/g, "&#39;");
                    const canDeselect = !hasGrade && !isPast;

                    cardsHtml += `
                    <div class="study-plan-subject"
                         id="plan-subject-${opt.id}-${ss.id}"
                         style="padding:12px;border:1.5px solid ${borderColor};border-radius:8px;background:${cardBg};position:relative;scroll-margin-top:100px;">
                        <button type="button" style="position:absolute;top:8px;right:8px;padding:4px;border:none;background:transparent;color:var(--muted);cursor:pointer;z-index:2;"
                                onclick='openPrereqModal(${subJson})' title="Xem chi tiết" onmousedown="event.stopPropagation()">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:20px;height:20px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        </button>
                        <span style="position:absolute;top:7px;left:10px;font-size:0.62rem;background:#dbeafe;color:#1d4ed8;padding:1px 7px;border-radius:4px;font-weight:700;">Đang học</span>
                        <div style="font-weight:600;font-size:0.95rem;margin-bottom:4px;padding-top:18px;padding-right:24px;">${opt.name}</div>
                        <div style="font-size:0.8rem;color:var(--muted);margin-bottom:6px;">${opt.credits} TC | ${opt.code || 'Chung'}</div>
                        <div style="display:flex;align-items:center;gap:8px;margin-top:8px;" onmousedown="event.stopPropagation()">
                            <input type="number" class="ob-grade-input"
                                   placeholder="Điểm..."
                                   min="0" max="10" step="0.1"
                                   value="${hasGrade ? grade : ''}"
                                   data-plan-subject-id="${ss.id}"
                                   data-eg-frame="${frameId}"
                                   data-eg-credits="${opt.credits}"
                                   data-eg-need="${needCr}"
                                   style="width:80px;height:32px;font-size:0.85rem;${limitReached ? 'opacity:0.45;cursor:not-allowed;' : ''}"
                                   ${limitReached ? 'disabled title="Đã đủ số tín chỉ yêu cầu của nhóm"' : ''}
                                   onchange="updatePlanGrade(${plan.id}, ${opt.id}, this)">
                            ${statusHtml}
                            ${canDeselect ? `<button type="button"
                                onclick="toggleElectiveSubject(${plan.id},${opt.id},${sem.semester_index},'remove')"
                                style="margin-left:auto;font-size:0.72rem;padding:3px 9px;border-radius:6px;border:1px solid #fca5a5;color:#ef4444;background:transparent;cursor:pointer;"
                                title="Bỏ chọn môn này">Bỏ chọn</button>` : ''}
                        </div>
                    </div>`;
                } else {
                    // Alternative — same card look, dashed border, "Chọn học" button
                    const atLimit = selectedCr >= needCr;
                    const canSelect = !isPast && !atLimit;
                    cardsHtml += `
                    <div class="study-plan-subject"
                         style="padding:12px;border:1.5px dashed ${atLimit ? '#d1d5db' : '#93c5fd'};border-radius:8px;background:${atLimit ? 'var(--surface-soft,#f9fafb)' : '#f0f7ff'};position:relative;opacity:${atLimit ? '0.5' : '0.85'};">
                        <span style="position:absolute;top:7px;left:10px;font-size:0.62rem;background:#f1f5f9;color:#64748b;padding:1px 7px;border-radius:4px;font-weight:700;">Phương án</span>
                        <div style="font-weight:600;font-size:0.95rem;margin-bottom:4px;padding-top:18px;padding-right:8px;">${opt.name}</div>
                        <div style="font-size:0.8rem;color:var(--muted);margin-bottom:10px;">${opt.credits} TC | ${opt.code || 'Chung'}</div>
                        ${canSelect
                            ? `<button type="button"
                                onclick="toggleElectiveSubject(${plan.id},${opt.id},${sem.semester_index},'add')"
                                style="font-size:0.75rem;padding:4px 12px;border-radius:6px;border:1.5px solid #2563eb;color:#2563eb;background:transparent;cursor:pointer;font-weight:600;">
                                + Chọn học môn này
                              </button>`
                            : `<div style="font-size:0.75rem;color:var(--muted);font-style:italic;">${atLimit ? 'Nhóm đã đủ TC' : 'Chưa được chọn'}</div>`
                        }
                    </div>`;
                }
            });

            html += `
            <div class="eg-group-frame" id="${frameId}"
                 style="grid-column:1/-1;border:2px solid #93c5fd;border-radius:12px;overflow:hidden;background:#eef5ff;">
                <div draggable="${!isPast}"
                     ondragstart="handleGroupDragStart(event,[${planIds.join(',')}],${sem.semester_index})"
                     ondragend="handleDragEnd(event)"
                     style="display:flex;align-items:center;gap:8px;padding:8px 14px;background:#1d4ed8;color:#fff;cursor:${isPast ? 'default' : 'grab'};user-select:none;">
                    <span style="font-size:1rem;">📚</span>
                    <div style="flex:1;">
                        <div style="font-weight:700;font-size:0.85rem;">Nhóm tự chọn "${group.name}"</div>
                        <div style="font-size:0.72rem;opacity:0.85;">Chọn đủ ${needCr} TC · ${isPast ? '' : 'Kéo tiêu đề để di chuyển cả nhóm · '}${group.options.length} môn phương án</div>
                    </div>
                    <span id="eg-counter-${frameId}"
                          style="font-weight:700;font-size:0.88rem;background:rgba(255,255,255,0.18);padding:3px 12px;border-radius:6px;color:${selectedCr >= needCr ? '#86efac' : '#fde68a'};">
                        ${selectedCr}/${needCr} TC
                    </span>
                </div>
                <div style="padding:12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px;">
                    ${cardsHtml}
                </div>
            </div>`;
        });

        html += '</div></div>';
    });

    html += '</div>';
    container.innerHTML = html;

    // Advisory panel được cập nhật bởi fetchSuggestions() — không auto-popup modal nữa
}

// ─── Drag and Drop Handlers ───
let draggedSubjectId      = null;
let draggedGroupSubjectIds = null;
let draggedSourceSemester = null;

function handleDragStart(event, subjectId, semesterIndex) {
    draggedGroupSubjectIds = null;
    draggedSubjectId = subjectId;
    draggedSourceSemester = semesterIndex;
    event.dataTransfer.effectAllowed = 'move';
    setTimeout(() => event.target.classList.add('is-dragging'), 0);
}

function handleGroupDragStart(event, subjectIds, semesterIndex) {
    draggedSubjectId = null;
    draggedGroupSubjectIds = subjectIds;
    draggedSourceSemester = semesterIndex;
    event.dataTransfer.effectAllowed = 'move';
    setTimeout(() => event.target.closest('.eg-group-frame')?.classList.add('is-dragging'), 0);
}

function handleDragEnd(event) {
    event.target.classList.remove('is-dragging');
    event.target.closest('.eg-group-frame')?.classList.remove('is-dragging');
    document.querySelectorAll('.study-plan-semester').forEach(el => el.classList.remove('drag-over'));
}

function handleDragOver(event) {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
    const semesterCard = event.target.closest('.study-plan-semester');
    if (semesterCard) {
        semesterCard.classList.add('drag-over');
    }
}

function handleDragLeave(event) {
    const semesterCard = event.target.closest('.study-plan-semester');
    if (semesterCard) {
        semesterCard.classList.remove('drag-over');
    }
}

async function handleDrop(event, planId, targetSemesterIndex) {
    event.preventDefault();
    event.target.closest('.study-plan-semester')?.classList.remove('drag-over');
    if (draggedSourceSemester === targetSemesterIndex) return;

    if (draggedGroupSubjectIds && draggedGroupSubjectIds.length > 0) {
        await executeGroupMove(planId, targetSemesterIndex);
    } else if (draggedSubjectId) {
        await executeSubjectMove(planId, targetSemesterIndex);
    }
}

// Drop vào kỳ đã hoàn thành → hiển thị cảnh báo trước khi thực hiện
function handleDropToPast(event, planId, targetSemesterIndex) {
    event.preventDefault();
    event.target.closest('.study-plan-semester')?.classList.remove('drag-over');
    if (draggedSourceSemester === targetSemesterIndex) return;
    if (!draggedSubjectId && (!draggedGroupSubjectIds || !draggedGroupSubjectIds.length)) return;

    // Lưu lại thông tin drag vì user cần thời gian xác nhận
    const subjectId   = draggedSubjectId;
    const groupIds    = draggedGroupSubjectIds ? [...draggedGroupSubjectIds] : null;
    const sourceSem   = draggedSourceSemester;

    document.getElementById('past-drop-confirm-modal')?.remove();
    const modal = document.createElement('div');
    modal.id = 'past-drop-confirm-modal';
    modal.innerHTML = `
        <div style="position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:10000;display:flex;align-items:center;justify-content:center;animation:fadeIn .15s ease;">
            <div style="background:var(--surface,#fff);border-radius:16px;padding:28px 24px;max-width:420px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
                <div style="font-size:1.6rem;margin-bottom:10px;">⚠️</div>
                <div style="font-weight:700;font-size:1rem;margin-bottom:8px;">Thêm môn vào kỳ đã hoàn thành?</div>
                <div style="font-size:0.875rem;color:var(--muted,#6b7280);line-height:1.65;margin-bottom:22px;">
                    Hành động này có thể:<br>
                    <ul style="margin:8px 0 0 16px;padding:0;">
                        <li>Thay đổi <strong>tổng tín chỉ và GPA</strong> của học kỳ đó</li>
                        <li>Ảnh hưởng đến <strong>gợi ý và đánh giá học lực</strong> của hệ thống</li>
                        <li>Làm sai lệch <strong>lộ trình</strong> so với thực tế</li>
                    </ul>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button id="past-drop-cancel"
                            style="padding:9px 18px;border-radius:8px;border:1px solid var(--hairline,#e5e7eb);background:transparent;cursor:pointer;font-size:0.875rem;">
                        Hủy
                    </button>
                    <button id="past-drop-confirm"
                            style="padding:9px 18px;border-radius:8px;border:none;background:#f59e0b;color:#fff;cursor:pointer;font-size:0.875rem;font-weight:600;">
                        Vẫn tiếp tục
                    </button>
                </div>
            </div>
        </div>`;
    document.body.appendChild(modal);

    modal.querySelector('#past-drop-cancel').onclick = () => {
        modal.remove();
        draggedSubjectId      = null;
        draggedGroupSubjectIds = null;
        draggedSourceSemester = null;
    };
    modal.querySelector('#past-drop-confirm').onclick = async () => {
        modal.remove();
        draggedSourceSemester = sourceSem;
        if (groupIds && groupIds.length) {
            draggedGroupSubjectIds = groupIds;
            draggedSubjectId = null;
            await executeGroupMove(planId, targetSemesterIndex);
        } else {
            draggedSubjectId = subjectId;
            draggedGroupSubjectIds = null;
            await executeSubjectMove(planId, targetSemesterIndex);
        }
    };
}

async function executeSubjectMove(planId, targetSemesterIndex) {
    const loader = document.getElementById('planner-loader');
    const container = document.getElementById('study-plan-results');
    loader.style.display = 'block';
    container.style.opacity = '0.3';

    try {
        const res = await fetch('/api/v1/study-plans/move-subject', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                study_plan_id: planId,
                subject_id: draggedSubjectId,
                target_semester_index: targetSemesterIndex
            })
        });

        const resData = await res.json();
        if (!res.ok) throw new Error(resData.error || 'Lỗi hệ thống');

        if (resData.success && resData.data) {
            renderStudyPlan(resData.data);
            const coreqsMoved = resData.coreqs_moved || [];
            if (coreqsMoved.length > 0) {
                showToast(`Đã di chuyển kèm môn song hành: ${coreqsMoved.join(', ')}`, 'success');
            } else {
                showToast('Di chuyển môn học thành công!', 'success');
            }
        }
    } catch (e) {
        showToast(e.message || 'Lỗi khi di chuyển môn học', 'error');
    } finally {
        loader.style.display = 'none';
        container.style.opacity = '1';
        draggedSubjectId = null;
        draggedSourceSemester = null;
    }
}

async function executeGroupMove(planId, targetSemesterIndex) {
    const loader = document.getElementById('planner-loader');
    const container = document.getElementById('study-plan-results');
    loader.style.display = 'block';
    container.style.opacity = '0.3';

    const ids = [...draggedGroupSubjectIds];
    let lastData = null;

    try {
        for (const subjectId of ids) {
            draggedSubjectId = subjectId;
            const res = await fetch('/api/v1/study-plans/move-subject', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    study_plan_id: planId,
                    subject_id: subjectId,
                    target_semester_index: targetSemesterIndex
                })
            });
            const resData = await res.json();
            if (!res.ok) throw new Error(resData.error || 'Lỗi khi di chuyển nhóm');
            if (resData.success && resData.data) lastData = resData.data;
        }
        if (lastData) renderStudyPlan(lastData);
        showToast('Đã di chuyển cả nhóm tự chọn!', 'success');
    } catch (e) {
        showToast(e.message || 'Lỗi khi di chuyển nhóm môn học', 'error');
    } finally {
        loader.style.display = 'none';
        container.style.opacity = '1';
        draggedSubjectId = null;
        draggedGroupSubjectIds = null;
        draggedSourceSemester = null;
    }
}

async function toggleElectiveSubject(planId, subjectId, semesterIndex, action) {
    const loader = document.getElementById('planner-loader');
    const container = document.getElementById('study-plan-results');
    loader.style.display = 'block';
    container.style.opacity = '0.3';

    try {
        const res = await fetch('/api/v1/study-plans/toggle-elective', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                study_plan_id:  planId,
                subject_id:     subjectId,
                semester_index: semesterIndex,
                action:         action,
            })
        });
        const resData = await res.json();
        if (!res.ok) {
            showToast(resData.error || 'Lỗi hệ thống', 'error');
            return;
        }
        if (resData.success && resData.data) {
            renderStudyPlan(resData.data);
            showToast(action === 'add' ? 'Đã thêm môn vào kế hoạch!' : 'Đã bỏ chọn môn.', 'success');
        }
    } catch (e) {
        showToast('Lỗi kết nối', 'error');
    } finally {
        loader.style.display = 'none';
        container.style.opacity = '1';
    }
}

async function updatePlanGrade(planId, subjectId, inputEl) {
    let grade = null;
    let status = null;

    if (inputEl.value.trim() !== '') {
        grade = parseFloat(inputEl.value);
        if (isNaN(grade) || grade < 0 || grade > 10) {
            showToast('Điểm không hợp lệ', 'error');
            return;
        }
        status = grade >= 5.0 ? 'passed' : 'failed';

        // TC constraint for elective group frames
        const egFrameId = inputEl.dataset.egFrame;
        if (egFrameId) {
            const egFrame = document.getElementById(egFrameId);
            if (egFrame) {
                const needCr  = parseInt(inputEl.dataset.egNeed  || 0);
                const thisCr  = parseInt(inputEl.dataset.egCredits || 0);
                let otherGradedCr = 0;
                egFrame.querySelectorAll('.ob-grade-input[data-eg-frame]').forEach(inp => {
                    if (inp === inputEl) return;
                    if (inp.value.trim() !== '') otherGradedCr += parseInt(inp.dataset.egCredits || 0);
                });
                if (otherGradedCr + thisCr > needCr) {
                    showToast(`Nhóm tự chọn chỉ cần ${needCr} TC. Hãy xóa điểm một môn khác trước.`, 'error');
                    inputEl.value = '';
                    return;
                }
            }
        }
    }

    inputEl.disabled = true;
    showSaveIndicator('saving', grade === null ? 'Đang xóa điểm...' : 'Đang cập nhật điểm...');

    try {
        const res = await fetch('/api/v1/study-plans/update-grade', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                study_plan_id:   planId,
                subject_id:      subjectId,
                grade:           grade,
                status:          status,
                plan_subject_id: inputEl.dataset.planSubjectId ? parseInt(inputEl.dataset.planSubjectId) : null
            })
        });

        if (!res.ok) throw new Error('API error');
        const resData = await res.json();

        inputEl.disabled = false;
        showSaveIndicator('saved', grade === null ? 'Đã xóa điểm' : 'Đã lưu điểm');

        // API trả về plan đã cập nhật (có retake auto-create/delete)
        if (resData.data) {
            window.currentActivePlan = resData.data;
            renderStudyPlan(resData.data);
        }

        if (grade === null) {
            // Xóa điểm → sync toàn bộ
            fetchProgress();
            fetchSuggestions();
            updateEarnedCredits();
            updateCreditStats();
        } else {
            // Kiểm tra kỳ học đã điền đủ để lưu lịch sử
            let allFilled = false;
            let snapshot  = [];
            let semIndex  = 1;
            const semesterCard = inputEl.closest('.study-plan-semester');
            if (semesterCard) {
                semIndex = parseInt(semesterCard.dataset.semesterIndex);
                const inputs = semesterCard.querySelectorAll('.ob-grade-input:not([readonly])');
                allFilled = inputs.length > 0;
                inputs.forEach(inp => {
                    if (inp.value.trim() === '') { allFilled = false; return; }
                    const subjCard = inp.closest('.study-plan-subject');
                    if (subjCard && subjCard.id) {
                        // id format: plan-subject-{subjectId}-{ssId}
                        const parts = subjCard.id.split('-');
                        const sid   = parseInt(parts[2]);
                        if (sid) snapshot.push({ id: sid, grade: parseFloat(inp.value) });
                    }
                });
            }

            if (allFilled && snapshot.length > 0 && window.currentActivePlan) {
                const semData = window.currentActivePlan.semesters.find(s => s.semester_index === semIndex);
                if (semData && semData.subjects) {
                    snapshot.forEach(c => {
                        const subData = semData.subjects.find(s => s.subject_id === c.id);
                        c.credits = subData && subData.subject ? subData.subject.credits : 0;
                        c.name    = subData && subData.subject ? subData.subject.name   : '';
                    });
                }

                // Lưu/cập nhật lịch sử học kỳ + lấy đánh giá cường độ học
                try {
                    const completeRes = await fetch('/semester-history/complete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                        body: JSON.stringify({
                            semester_number: semIndex,
                            courses: snapshot.map(c => ({ subject_id: c.id, grade: c.grade }))
                        })
                    });
                    const completeData = await completeRes.json();
                    // Gán biến GLOBAL (không khai báo lại bằng const) để showSemResultModal đọc được
                    _semEvaluation = completeData.evaluation || null;

                    if (_semEvaluation && _semEvaluation.status !== 'KEEP') {
                        window._pendingModeEvaluation = _semEvaluation;
                        window._pendingModeEvaluation.plan_id = planId;
                        updateModeSuggestionBadge(_semEvaluation);
                    }
                } catch (e) { console.warn('Lỗi lưu lịch sử học kỳ', e); }

                fetchGraduationForecast();
                fetchSuggestions();
                updateEarnedCredits();
                updateCreditStats();
                loadSemesterHistory();

                // Luôn hiện bảng thống kê kết quả học kỳ + tư vấn điều chỉnh kế hoạch
                // (kể cả khi đã lưu lịch sử trước đó — để sinh viên xem lại sau mỗi lần sửa điểm).
                showSemResultModal(semIndex, snapshot);
                setTimeout(() => {
                    if (window.currentActivePlan) fetchAndShowAdvisory(window.currentActivePlan.id);
                }, 1800);
            } else if (resData.evaluation) {
                if (resData.evaluation.status !== 'KEEP') {
                    window._pendingModeEvaluation = resData.evaluation;
                    window._pendingModeEvaluation.plan_id = planId;
                    updateModeSuggestionBadge(resData.evaluation);
                }
                updateEarnedCredits();
                updateCreditStats();
                fetchProgress();
            } else {
                updateEarnedCredits();
                updateCreditStats();
            }
        }
    } catch (e) {
        inputEl.disabled = false;
        showSaveIndicator('error', 'Lỗi lưu điểm');
    }
}

// ═══════════════════════════════════════════════════════════════
// THÊM MÔN HỌC LẠI (RETAKE)
// ═══════════════════════════════════════════════════════════════
async function addRetakeSubject(planId, subjectId, fromSemester, originalGrade) {
    if (!planId || !subjectId) return;

    try {
        showSaveIndicator('saving', 'Đang thêm học lại...');

        const res = await fetch('/api/v1/study-plans/add-retake', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                study_plan_id:  planId,
                subject_id:     subjectId,
                from_semester:  fromSemester,
                original_grade: originalGrade
            })
        });

        const resData = await res.json();

        if (!res.ok || !resData.success) {
            showToast(resData.message || 'Không thể thêm môn học lại.', 'error');
            showSaveIndicator('error', 'Lỗi');
            return;
        }

        // Cập nhật plan từ response
        window.currentActivePlan = resData.data;
        renderStudyPlan(resData.data);

        const retakeSem = resData.retake_semester;
        showToast(`✅ Đã thêm học lại vào Học kỳ ${retakeSem}. Điểm sẽ lấy cao hơn giữa 2 lần thi.`, 'success');
        showSaveIndicator('saved', 'Đã lưu');

    } catch (e) {
        console.error('Lỗi addRetakeSubject:', e);
        showToast('Đã xảy ra lỗi khi thêm môn học lại.', 'error');
    }
}

async function adjustStudyPlan(planId, evaluation) {
    const loader = document.getElementById('planner-loader');
    const container = document.getElementById('study-plan-results');
    loader.style.display = 'block';
    container.style.opacity = '0.3';

    try {
        const res = await fetch('/api/v1/study-plans/adjust', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ study_plan_id: planId, evaluation: evaluation })
        });
        if (!res.ok) throw new Error('API error');
        const resData = await res.json();
        if (resData.success && resData.data) {
            renderStudyPlan(resData.data);
            showToast('Đã tự động cập nhật lại lộ trình! ✨', 'success');
            fetchSavedPlansList();
            fetchSuggestions();
        }
    } catch (e) {
        showToast('Lỗi khi điều chỉnh kế hoạch', 'error');
    } finally {
        loader.style.display = 'none';
        container.style.opacity = '1';
    }
}

// Initialize fetch study plans on load
window.addEventListener('DOMContentLoaded', () => {
    fetchStudyPlans();
});

function scrollToSubject(id) {
    const el = document.getElementById('plan-subject-' + id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.style.transition = 'box-shadow 0.3s';
        el.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.4)';
        setTimeout(() => el.style.boxShadow = 'none', 2000);

        // Close the drawer if it exists
        const drawer = document.getElementById('suggestion-drawer');
        if (drawer) {
            drawer.style.right = '-450px';
            const ov = document.getElementById('suggestion-drawer-overlay');
            if (ov) { ov.style.opacity = '0'; ov.style.pointerEvents = 'none'; }
        }
    } else {
        showToast('Môn học này chưa có trong lộ trình.', 'warning');
    }
}

async function applySuggestionsToPlan() {
    if (!window.currentActivePlan || !window.currentSuggestions || window.currentSuggestions.length === 0) {
        showToast('Không có gợi ý nào hợp lệ để áp dụng.', 'warning');
        return;
    }

    const plan = window.currentActivePlan;
    const subjectIds = window.currentSuggestions.map(s => s.id);

    // Tìm học kỳ mục tiêu: học kỳ đầu tiên có môn học nhưng chưa có điểm (grade === null)
    // Hoặc nếu tất cả đều có điểm, lấy học kỳ tiếp theo
    let targetSemesterIndex = 1;
    let found = false;
    if (plan.semesters && plan.semesters.length > 0) {
        for (let sem of plan.semesters) {
            if (sem.subjects && sem.subjects.length > 0) {
                let hasNullGrade = sem.subjects.some(ss => ss.grade === null || ss.grade === undefined || ss.grade === '');
                if (hasNullGrade) {
                    targetSemesterIndex = sem.semester_index;
                    found = true;
                    break;
                }
            }
        }
        if (!found) {
            targetSemesterIndex = plan.semesters[plan.semesters.length - 1].semester_index + 1;
        }
    }

    const confirmed = await showConfirm(
        `Áp dụng gợi ý cho Học kỳ ${targetSemesterIndex}`,
        `Các môn tương lai sẽ được sắp xếp lại tự động. Môn học lại sẽ được chuyển sang học kỳ này. Bạn có chắc chắn muốn tiếp tục không?`
    );
    if (!confirmed) return;

    const btn = document.getElementById('btn-apply-suggestions');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<div class="spinner" style="width:18px;height:18px;border-width:2px;border-top-color:#fff;"></div> Đang xử lý...';
    btn.disabled = true;

    try {
        const res = await fetch('/api/v1/study-plans/apply-suggestions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                study_plan_id: plan.id,
                subject_ids: subjectIds,
                target_semester_index: targetSemesterIndex
            })
        });

        const resData = await res.json();

        if (!res.ok) {
            throw new Error(resData.error || 'Lỗi hệ thống');
        }

        if (resData.success && resData.data) {
            renderStudyPlan(resData.data);
            showToast('Đã áp dụng môn học vào học kỳ ' + targetSemesterIndex + '!', 'success');

            const drawer = document.getElementById('suggestion-drawer');
            if (drawer) {
                drawer.style.right = '-450px';
                const ov = document.getElementById('suggestion-drawer-overlay');
                if (ov) { ov.style.opacity = '0'; ov.style.pointerEvents = 'none'; }
            }
        }
    } catch (e) {
        showToast(e.message || 'Lỗi khi áp dụng gợi ý.', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// ═══════════════════════════════════════════════════════════════
// SPRINT 3: GPA TREND LINE CHART
// ═══════════════════════════════════════════════════════════════
let gpaTrendChartInstance = null;

async function fetchGpaTrend() {
    try {
        const res = await fetch('/api/v1/gpa-trend', { headers: { 'Accept': 'application/json' } });
        if (!res.ok) return;
        const data = await res.json();
        renderGpaTrendChart(data);
    } catch (e) { console.warn('[GPA trend error]', e); }
}

function renderGpaTrendChart(data) {
    const empty = document.getElementById('gpa-trend-empty');
    const wrap  = document.getElementById('gpa-trend-chart-wrap');
    const badge = document.getElementById('gpa-trend-badge');
    const msg   = document.getElementById('gpa-trend-message');

    if (!data || !data.gpas || data.gpas.length === 0) {
        if (empty) empty.style.display = 'block';
        if (wrap)  wrap.style.display  = 'none';
        if (badge) { badge.textContent = 'Chưa có dữ liệu'; badge.style.background = 'var(--surface-soft)'; badge.style.color = 'var(--muted)'; }
        return;
    }

    if (empty) empty.style.display = 'none';
    if (wrap)  wrap.style.display  = 'block';
    if (msg && data.message) msg.textContent = data.message;

    const trendConfig = {
        improving: { label: 'Đang cải thiện ↑', bg: '#d1fae5', color: '#065f46' },
        declining:  { label: 'Đang giảm ↓',     bg: '#fee2e2', color: '#991b1b' },
        stable:     { label: 'Ổn định →',        bg: '#dbeafe', color: '#1e40af' },
        no_data:    { label: 'Chưa đủ dữ liệu',  bg: 'var(--surface-soft)', color: 'var(--muted)' },
    };
    const tCfg = trendConfig[data.trend] || trendConfig.no_data;
    if (badge) { badge.textContent = tCfg.label; badge.style.background = tCfg.bg; badge.style.color = tCfg.color; }

    const canvas = document.getElementById('gpaTrendChart');
    if (!canvas) return;

    if (gpaTrendChartInstance) { gpaTrendChartInstance.destroy(); gpaTrendChartInstance = null; }

    const ctx = canvas.getContext('2d');
    const lineColor = data.trend === 'improving' ? '#10b981' : data.trend === 'declining' ? '#ef4444' : '#3b82f6';

    gpaTrendChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.semesters,
            datasets: [
                {
                    label: 'GPA học kỳ',
                    data: data.gpas,
                    borderColor: lineColor,
                    backgroundColor: lineColor + '22',
                    borderWidth: 2.5,
                    pointBackgroundColor: lineColor,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.35,
                },
                {
                    label: 'Ngưỡng yếu (5.0)',
                    data: data.semesters.map(() => 5.0),
                    borderColor: '#ef4444',
                    borderDash: [6, 3],
                    borderWidth: 1.5,
                    pointRadius: 0,
                    fill: false,
                },
                {
                    label: 'Ngưỡng khá (7.5)',
                    data: data.semesters.map(() => 7.5),
                    borderColor: '#10b981',
                    borderDash: [6, 3],
                    borderWidth: 1.5,
                    pointRadius: 0,
                    fill: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'bottom', labels: { font: { size: 12 }, usePointStyle: true } },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            if (ctx.datasetIndex === 0) return `GPA: ${ctx.parsed.y.toFixed(2)}`;
                            return null;
                        },
                        afterBody: (items) => {
                            const idx = items[0]?.dataIndex;
                            if (idx !== undefined && data.credits && data.credits[idx] !== undefined)
                                return [`TC tích lũy: ${data.credits[idx]}`];
                            return [];
                        }
                    }
                },
            },
            scales: {
                y: { min: 0, max: 10, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.04)' } },
                x: { grid: { display: false } },
            },
        },
    });
}

// ═══════════════════════════════════════════════════════════════
// SPRINT 3: WARNINGS PANEL
// ═══════════════════════════════════════════════════════════════
async function fetchWarnings() {
    try {
        const res = await fetch('/api/v1/warnings', { headers: { 'Accept': 'application/json' } });
        if (!res.ok) return;
        const data = await res.json();
        renderWarningsPanel(data.live_warnings || []);
    } catch (e) { console.warn('[Warnings error]', e); }
}

function renderWarningsPanel(warnings) {
    const panel = document.getElementById('warnings-panel');
    const list  = document.getElementById('warnings-list');
    const badge = document.getElementById('warnings-count-badge');
    if (!panel || !list) return;

    if (!warnings || warnings.length === 0) {
        panel.style.display = 'none';
        return;
    }

    panel.style.display = 'block';
    if (badge) badge.textContent = warnings.length;

    const severityConfig = {
        critical: { bg: '#fef2f2', border: '#fca5a5', color: '#991b1b', icon: '🚨' },
        warning:  { bg: '#fffbeb', border: '#fde68a', color: '#92400e', icon: '⚠️' },
        info:     { bg: '#eff6ff', border: '#bfdbfe', color: '#1e40af', icon: 'ℹ️' },
    };

    list.innerHTML = warnings.map(w => {
        const cfg = severityConfig[w.severity] || severityConfig.info;
        return `<div style="background:${cfg.bg}; border:1px solid ${cfg.border}; border-radius:8px; padding:10px 14px; display:flex; align-items:flex-start; gap:10px;">
            <span style="font-size:1.1rem; flex-shrink:0;">${cfg.icon}</span>
            <div>
                <div style="font-size:0.85rem; font-weight:700; color:${cfg.color}; margin-bottom:2px;">${w.title || 'Cảnh báo'}</div>
                <div style="font-size:0.82rem; color:#374151; line-height:1.5;">${w.message}</div>
            </div>
        </div>`;
    }).join('');
}

// ═══════════════════════════════════════════════════════════════
// SPRINT 3: CASCADE ANALYSIS MODAL
// ═══════════════════════════════════════════════════════════════
function closeCascadeModal() {
    const overlay = document.getElementById('cascade-modal-overlay');
    if (overlay) overlay.style.display = 'none';
}

async function openCascadeModal(subjectId, subjectName) {
    const overlay = document.getElementById('cascade-modal-overlay');
    if (!overlay) return;

    // Reset state
    document.getElementById('cascade-modal-subject').textContent = subjectName || 'Đang tải...';
    document.getElementById('cascade-summary-box').textContent = 'Đang phân tích hiệu ứng dây chuyền...';
    document.getElementById('cascade-kpi-total').textContent = '...';
    document.getElementById('cascade-kpi-credits').textContent = '...';
    document.getElementById('cascade-kpi-delay').textContent = '...';
    document.getElementById('cascade-direct-section').style.display = 'none';
    document.getElementById('cascade-indirect-section').style.display = 'none';
    document.getElementById('cascade-no-impact').style.display = 'none';
    overlay.style.display = 'flex';

    try {
        const res = await fetch(`/api/v1/cascade-impact/${subjectId}`, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('Lỗi hệ thống');
        const json = await res.json();
        const d = json.data;

        document.getElementById('cascade-summary-box').textContent = d.summary;
        document.getElementById('cascade-kpi-total').textContent   = d.total_blocked;
        document.getElementById('cascade-kpi-credits').textContent = d.total_blocked_credits;
        document.getElementById('cascade-kpi-delay').textContent   = d.estimated_delay_sems;
        document.getElementById('cascade-modal-title').textContent = d.total_blocked > 0 ? 'Cảnh Báo Dây Chuyền' : 'Không Có Ảnh Hưởng';

        if (d.total_blocked === 0) {
            document.getElementById('cascade-no-impact').style.display = 'block';
            return;
        }

        const renderSubjectChips = (subjects, containerId) => {
            const el = document.getElementById(containerId);
            if (!el) return;
            el.innerHTML = subjects.map(s =>
                `<span style="display:inline-block; background:var(--surface-soft); border:1px solid var(--hairline); border-radius:6px; padding:4px 10px; font-size:0.78rem; font-weight:600; color:var(--ink);" title="${s.credits} TC — ${s.program_group || ''}">${s.code ? s.code + ' · ' : ''}${s.name} <span style="color:var(--muted); font-weight:400;">(${s.credits}TC)</span></span>`
            ).join('');
        };

        if (d.direct_blocked.length > 0) {
            document.getElementById('cascade-direct-section').style.display = 'block';
            document.getElementById('cascade-direct-count').textContent = d.direct_blocked.length + ' môn';
            renderSubjectChips(d.direct_blocked, 'cascade-direct-list');
        }
        if (d.indirect_blocked.length > 0) {
            document.getElementById('cascade-indirect-section').style.display = 'block';
            document.getElementById('cascade-indirect-count').textContent = d.indirect_blocked.length + ' môn';
            renderSubjectChips(d.indirect_blocked, 'cascade-indirect-list');
        }
    } catch (e) {
        document.getElementById('cascade-summary-box').textContent = 'Không thể phân tích hiệu ứng dây chuyền. Vui lòng thử lại.';
    }
}

// ═══════════════════════════════════════════════════════════════
// SPRINT 3: SKILL FOCUS CARD (in Analysis tab)
// ═══════════════════════════════════════════════════════════════
async function fetchSkillFocusProgress() {
    try {
        const res = await fetch('/api/v1/progress', { headers: { 'Accept': 'application/json' } });
        if (!res.ok) return;
        const data = await res.json();
        const evaluation = data?.data?.progress?.evaluation;
        if (!evaluation || !evaluation.skill_analysis) {
            document.getElementById('skill-focus-card')?.setAttribute('style', 'display:none');
            return;
        }
        const skill = evaluation.skill_analysis;
        const card  = document.getElementById('skill-focus-card');
        if (!card) return;
        card.style.display = 'block';

        const focusLabel = evaluation.skill_focus
            ? ({ backend:'Backend Development', frontend:'Frontend Development', ai:'AI / Machine Learning', data:'Data Science', mobile:'Mobile Development', devops:'DevOps / Cloud', testing:'Testing / QA', security:'Cybersecurity', core:'Core' }[evaluation.skill_focus] || evaluation.skill_focus)
            : 'Chưa chọn';

        const badge = document.getElementById('skill-focus-label-badge');
        if (badge) badge.textContent = focusLabel;

        const pct = skill.completion_pct || 0;
        const bar = document.getElementById('skill-focus-bar');
        if (bar) bar.style.width = pct + '%';

        const passedEl = document.getElementById('skill-focus-passed');
        if (passedEl) passedEl.textContent = `${skill.passed_subjects || 0} / ${skill.total_subjects || 0} môn`;

        const pctEl = document.getElementById('skill-focus-pct');
        if (pctEl) pctEl.textContent = pct.toFixed(0) + '%';

        const gpaEl = document.getElementById('skill-focus-gpa');
        if (gpaEl) gpaEl.textContent = skill.avg_grade ? skill.avg_grade.toFixed(2) : '--';

        const msgEl = document.getElementById('skill-focus-message');
        if (msgEl) msgEl.textContent = evaluation.skill_message || 'Chưa có dữ liệu đủ để phân tích.';
    } catch (e) { console.warn('[Skill focus error]', e); }
}

// ═══════════════════════════════════════════════════════════════
// MODE DOWNGRADE NOTICE (forced_slow từ generate API)
// ═══════════════════════════════════════════════════════════════
function showModeDowngradeNotice(message) {
    // Xóa banner cũ nếu có
    document.getElementById('mode-downgrade-notice')?.remove();

    const banner = document.createElement('div');
    banner.id = 'mode-downgrade-notice';
    banner.style.cssText = [
        'position:fixed; top:72px; left:50%; transform:translateX(-50%);',
        'background:#fef3c7; border:1px solid #fbbf24; border-radius:12px;',
        'padding:14px 20px; max-width:520px; width:90%; z-index:9999;',
        'display:flex; align-items:flex-start; gap:12px;',
        'box-shadow:0 4px 16px rgba(0,0,0,0.12); animation:slideDown 0.3s ease;'
    ].join('');

    banner.innerHTML = `
        <span style="font-size:1.4rem; flex-shrink:0; margin-top:1px;">⚠️</span>
        <div style="flex:1;">
            <div style="font-weight:700; color:#92400e; margin-bottom:4px; font-size:0.9rem;">Chế độ học đã được điều chỉnh</div>
            <div style="font-size:0.85rem; color:#78350f; line-height:1.5;">${message}</div>
        </div>
        <button onclick="this.closest('#mode-downgrade-notice').remove()"
                style="border:none; background:transparent; color:#92400e; font-size:1.1rem; cursor:pointer; padding:2px; flex-shrink:0; line-height:1;">&times;</button>
    `;

    document.body.appendChild(banner);
    // Tự đóng sau 8 giây
    setTimeout(() => banner?.remove(), 8000);
}

// ═══════════════════════════════════════════════════════════════
// OVER-SEMESTERS NOTICE (kế hoạch vượt số kỳ mục tiêu)
// ═══════════════════════════════════════════════════════════════
function showOverSemestersNotice(message) {
    document.getElementById('over-semesters-notice')?.remove();

    const banner = document.createElement('div');
    banner.id = 'over-semesters-notice';
    banner.style.cssText = [
        'position:fixed; top:72px; left:50%; transform:translateX(-50%);',
        'background:#eff6ff; border:1px solid #60a5fa; border-radius:12px;',
        'padding:14px 20px; max-width:560px; width:90%; z-index:9999;',
        'display:flex; align-items:flex-start; gap:12px;',
        'box-shadow:0 4px 16px rgba(0,0,0,0.12); animation:slideDown 0.3s ease;'
    ].join('');

    banner.innerHTML = `
        <span style="font-size:1.4rem; flex-shrink:0; margin-top:1px;">📋</span>
        <div style="flex:1;">
            <div style="font-weight:700; color:#1e40af; margin-bottom:4px; font-size:0.9rem;">Kế hoạch vượt số học kỳ mục tiêu</div>
            <div style="font-size:0.85rem; color:#1e3a8a; line-height:1.5;">${message}</div>
        </div>
        <button onclick="this.closest('#over-semesters-notice').remove()"
                style="border:none; background:transparent; color:#1e40af; font-size:1.1rem; cursor:pointer; padding:2px; flex-shrink:0; line-height:1;">&times;</button>
    `;

    document.body.appendChild(banner);
    setTimeout(() => banner?.remove(), 10000);
}

// ═══════════════════════════════════════════════════════════════
// ADVISORY MODAL (tư vấn sau khi nhập điểm học kỳ)
// ═══════════════════════════════════════════════════════════════
window._advisoryData = null;

// Fetch advisory và render vào panel trong drawer (không popup modal)
async function fetchAdvisoryForDrawer(planId) {
    try {
        const res = await fetch(`/api/v1/study-plans/${planId}/advisory`, { headers: { 'Accept': 'application/json' } });
        const resData = await res.json();
        if (!resData.success) return;
        window._advisoryData = resData.data;
        renderAdvisoryPanel(resData.data);
    } catch (e) { /* bỏ qua */ }
}

function renderAdvisoryPanel(data) {
    const panel = document.getElementById('advisory-panel');
    if (!panel) return;

    const plan = window.currentActivePlan;
    const targetSems  = plan?.target_semesters || plan?.target_semester_count || 8;
    const currentTc   = plan?.tc_per_sem || 18;

    const icons = { increase: '🚀', decrease: '🌱', maintain: '⚖️' };
    const colors = {
        increase: { bg: '#f0fdf4', border: '#86efac', text: '#15803d', badge: '#dcfce7', badgeText: '#15803d' },
        decrease: { bg: '#fffbeb', border: '#fcd34d', text: '#92400e', badge: '#fef3c7', badgeText: '#92400e' },
        maintain: { bg: '#f8fafc', border: '#e2e8f0', text: '#475569', badge: '#f1f5f9', badgeText: '#475569' },
    };
    const c = colors[data.recommend] || colors.maintain;
    const icon = icons[data.recommend] || '💡';

    // Row trạng thái kế hoạch hiện tại
    let statsHtml = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <span style="font-size:0.8rem; color:#64748b;">Mục tiêu tốt nghiệp</span>
            <strong style="font-size:0.82rem;">${targetSems} học kỳ</strong>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <span style="font-size:0.8rem; color:#64748b;">TC/kỳ đang áp dụng</span>
            <strong style="font-size:0.82rem;">${currentTc} TC</strong>
        </div>`;

    if (data.recommend !== 'maintain') {
        const dir  = data.recommend === 'increase' ? '▲' : '▼';
        const grad = data.new_graduation_estimate;
        statsHtml += `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <span style="font-size:0.8rem; color:#64748b;">TC/kỳ đề xuất</span>
            <strong style="font-size:0.82rem; color:${c.text};">${dir} ${data.recommended_tc_per_sem} TC</strong>
        </div>`;
        if (grad) {
            const delta = Math.abs(data.semesters_delta);
            statsHtml += `
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:0.8rem; color:#64748b;">Tốt nghiệp dự kiến</span>
            <strong style="font-size:0.82rem; color:${data.recommend==='increase'&&delta>0?'#059669':data.recommend==='decrease'&&delta>0?'#d97706':'inherit'};">HK ${grad} ${delta > 0 ? `(${data.recommend==='increase'?'sớm hơn':'trễ hơn'} ${delta} kỳ)` : '(đúng mục tiêu)'}</strong>
        </div>`;
        }
    }

    let actionHtml = '';
    if (data.recommend !== 'maintain') {
        actionHtml = `
        <div style="display:flex; gap:6px; margin-top:10px;">
            <button onclick="applyAdvisoryAction(true)" style="flex:1; background:${c.text}; color:#fff; border:none; padding:7px 0; border-radius:8px; font-size:0.8rem; font-weight:700; cursor:pointer;">
                Rải lại tự động
            </button>
            <button onclick="applyAdvisoryAction(false)" style="flex:1; background:${c.badge}; color:${c.text}; border:1px solid ${c.border}; padding:7px 0; border-radius:8px; font-size:0.8rem; font-weight:600; cursor:pointer;">
                Chỉ đổi giới hạn TC
            </button>
        </div>`;
    }

    panel.style.display = 'block';
    panel.innerHTML = `
        <div style="margin:12px 16px; background:${c.bg}; border:1px solid ${c.border}; border-radius:12px; padding:12px 14px;">
            <div style="display:flex; align-items:center; gap:6px; margin-bottom:8px;">
                <span style="font-size:1.05rem;">${icon}</span>
                <span style="font-size:0.82rem; font-weight:700; color:${c.text};">
                    ${data.recommend === 'increase' ? 'Nên tăng tải' : data.recommend === 'decrease' ? 'Nên giảm tải' : 'Tiếp tục duy trì'}
                </span>
                <span style="margin-left:auto; font-size:0.75rem; background:${c.badge}; color:${c.badgeText}; padding:2px 8px; border-radius:99px; border:1px solid ${c.border};">Tư vấn</span>
            </div>
            <p style="font-size:0.8rem; color:${c.text}; margin:0 0 10px 0; line-height:1.45;">${data.reason}</p>
            <div style="background:rgba(255,255,255,0.6); border-radius:8px; padding:10px 12px;">
                ${statsHtml}
            </div>
            ${actionHtml}
        </div>`;
}

async function fetchAndShowAdvisory(planId) {
    try {
        const res = await fetch(`/api/v1/study-plans/${planId}/advisory`, {
            headers: { 'Accept': 'application/json' }
        });
        const resData = await res.json();
        if (!resData.success) return;
        showAdvisoryModal(resData.data);
    } catch (e) {
        console.error('Advisory fetch error', e);
    }
}

function showAdvisoryModal(data) {
    window._advisoryData = data;
    const overlay   = document.getElementById('advisory-modal-overlay');
    const bodyEl    = document.getElementById('advisory-body');
    const titleEl   = document.getElementById('advisory-title');
    const btnManual = document.getElementById('advisory-apply-manual');
    const btnRedist = document.getElementById('advisory-apply-redistribute');
    if (!overlay || !bodyEl) return;

    const icons = { increase: '🚀', decrease: '🌱', maintain: '⚖️' };
    const icon  = icons[data.recommend] || '💡';

    titleEl.textContent = `${icon} Tư Vấn Điều Chỉnh Kế Hoạch`;

    let body = `<p style="margin-bottom:12px;">${data.reason}</p>`;

    if (data.recommend !== 'maintain') {
        const dir = data.recommend === 'increase' ? 'tăng' : 'giảm';
        const delta = Math.abs(data.semesters_delta);
        const grad  = data.new_graduation_estimate;
        body += `
            <div style="background:var(--surface-soft); border-radius:10px; padding:14px 16px; margin-bottom:14px; border:1px solid var(--hairline);">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <span style="color:var(--muted); font-size:0.85rem;">TC/kỳ hiện tại</span>
                    <strong>${data.current_tc_per_sem} TC</strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <span style="color:var(--muted); font-size:0.85rem;">TC/kỳ đề xuất</span>
                    <strong style="color:${data.recommend==='increase'?'#059669':'#d97706'};">${data.recommended_tc_per_sem} TC</strong>
                </div>
                ${grad ? `<div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--muted); font-size:0.85rem;">Tốt nghiệp dự kiến</span>
                    <strong style="color:${data.recommend==='increase'&&delta>0?'#059669':data.recommend==='decrease'&&delta>0?'#d97706':'inherit'};">
                        Học kỳ ${grad} ${delta > 0 ? `(${data.recommend==='increase'?'sớm hơn':'trễ hơn'} ${delta} kỳ)` : '(đúng mục tiêu)'}</strong>
                </div>` : ''}
            </div>
            <p style="font-size:0.85rem; color:var(--muted);">Chọn cách áp dụng đề xuất:</p>
            <ul style="font-size:0.88rem; margin:8px 0 0 0; padding-left:18px; display:flex; flex-direction:column; gap:6px;">
                <li><strong>Tự động rải lại:</strong> Hệ thống tính lại và phân bổ môn học theo tải mới.</li>
                <li><strong>Chỉ đổi giới hạn TC:</strong> Cập nhật giới hạn tín chỉ/kỳ, bạn tự kéo thả điều chỉnh.</li>
            </ul>`;
        btnManual.style.display  = 'inline-block';
        btnRedist.style.display  = 'inline-block';
        btnManual.textContent    = `Chỉ đổi giới hạn (${data.recommended_tc_per_sem} TC/kỳ)`;
        btnRedist.textContent    = `Tự động rải lại`;
    } else {
        body += `<p style="color:var(--muted); font-size:0.88rem;">Không cần thay đổi gì. Tiếp tục theo kế hoạch hiện tại.</p>`;
        btnManual.style.display = 'none';
        btnRedist.style.display = 'none';
    }

    bodyEl.innerHTML = body;
    overlay.classList.remove('hidden');
}

function closeAdvisoryModal() {
    document.getElementById('advisory-modal-overlay')?.classList.add('hidden');
    window._advisoryData = null;
}

async function applyAdvisoryAction(redistribute) {
    if (!window._advisoryData || !window.currentActivePlan) return;
    const tc = window._advisoryData.recommended_tc_per_sem;
    const loader = document.getElementById('planner-loader');
    if (loader) loader.style.display = 'block';
    closeAdvisoryModal();
    const panel = document.getElementById('advisory-panel');
    if (panel) panel.style.display = 'none';
    try {
        const res = await fetch(`/api/v1/study-plans/${window.currentActivePlan.id}/apply-advisory`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify({ tc_per_sem: tc, redistribute })
        });
        const resData = await res.json();
        if (resData.success && resData.data) {
            renderStudyPlan(resData.data);
            showToast(redistribute ? `Đã rải lại lộ trình với ${tc} TC/kỳ` : `Đã cập nhật giới hạn ${tc} TC/kỳ`, 'success');
        } else {
            showToast('Lỗi khi áp dụng tư vấn', 'error');
        }
    } catch (e) {
        showToast('Lỗi mạng', 'error');
    } finally {
        if (loader) loader.style.display = 'none';
    }
}

// Inject CSS cho sem-past và animation nếu chưa có
(function injectPlannerStyles() {
    if (document.getElementById('planner-dynamic-styles')) return;
    const style = document.createElement('style');
    style.id = 'planner-dynamic-styles';
    style.textContent = `
        .goal-card:hover { border-color:var(--brand-mint) !important; background:rgba(155,217,177,0.08) !important; }
        .goal-card.selected { border-color:var(--brand-mint) !important; background:rgba(155,217,177,0.15) !important; }
        .sem-past { opacity: 0.88; }
        /* Drag-drop bị chặn bởi HTML (không có draggable attr + không có ondrop),
           không dùng pointer-events: none để vẫn cho phép sửa điểm */
        @keyframes slideDown {
            from { opacity:0; transform:translateX(-50%) translateY(-10px); }
            to   { opacity:1; transform:translateX(-50%) translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity:0; }
            to   { opacity:1; }
        }
        .prereq-tooltip-wrap:hover > div { display:block !important; }
    `;
    document.head.appendChild(style);
})();
