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
    if (tabId === 'chart' && chartRawData) {
        renderGradeChartDetail(chartRawData, 'all');
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
let obData = { academic_year: null, program_type: null, current_semester: null, target_years: null, grades: {} };

const OB_STEPS = [
    { label: 'Bước 1 / 2', icon: '🎓', iconBg: '#f5f0e0', title: 'Chào mừng bạn!', desc: 'Hãy cho chúng tôi biết bạn đang theo học chương trình nào để hệ thống gợi ý chính xác nhất.' },
    { label: 'Bước 2 / 2', icon: '📝', iconBg: '#faf5e8', title: 'Điểm số của bạn', desc: 'Nhập điểm các môn bạn đã học. Chỉ nhập những môn đã có điểm.' },
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
        let sectionsHtml = '';
        for (const [semName, subjects] of Object.entries(SUBJECTS_BY_SEM)) {
            const rows = subjects.map(sub => {
                const g = obData.grades[sub.id];
                const cls = g === undefined ? '' : (g > 5 ? 'pass' : 'fail');
                const statusTxt = g === undefined ? '' : (g > 5 ? '✓ Pass' : '✗ Fail');
                const statusCls = g === undefined ? '' : (g > 5 ? 'pass' : 'fail');
                return `<div class="ob-subject-row ${g !== undefined ? 'has-grade' : ''}" id="ob-row-${sub.id}">
                        <div class="ob-subject-info"><div class="ob-subject-name">${sub.name}</div><div class="ob-subject-meta">${sub.credits} TC · HK chuẩn ${sub.semName}</div></div>
                        <div class="ob-grade-wrap">
                            <input type="number" class="ob-grade-input ${cls}" id="ob-grade-${sub.id}" min="0" max="10" step="0.1" placeholder="—" value="${g !== undefined ? g : ''}" oninput="obGradeChange(${sub.id},this)">
                            <span class="ob-grade-status ${statusCls}" id="ob-gstatus-${sub.id}">${statusTxt}</span>
                        </div>
                    </div>`;
            }).join('');
            sectionsHtml += `<div class="ob-semester-section"><div class="ob-semester-section-title">Học kỳ chuẩn ${semName}</div>${rows}</div>`;
        }
        body.innerHTML = `<div class="ob-warning"><span class="ob-warning-icon">⚠️</span><p><strong>Lưu ý:</strong> Chỉ nhập điểm những môn bạn <strong>đã học và có kết quả</strong>.</p></div><div class="ob-subjects-scroll">${sectionsHtml}</div>`;
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

function obGradeChange(id, input) {
    const rawVal = parseFloat(input.value);
    if (!isNaN(rawVal)) { if (rawVal > 10) input.value = 10; else if (rawVal < 0) input.value = 0; }
    const val = parseFloat(input.value);
    const status = document.getElementById(`ob-gstatus-${id}`);
    const row = document.getElementById(`ob-row-${id}`);
    input.classList.remove('pass', 'fail'); status.classList.remove('pass', 'fail'); row.classList.remove('has-grade');
    if (input.value === '' || isNaN(val)) { delete obData.grades[id]; status.textContent = ''; }
    else {
        obData.grades[id] = val; row.classList.add('has-grade');
        if (val > 5) { input.classList.add('pass'); status.classList.add('pass'); status.textContent = '✓ Pass'; }
        else { input.classList.add('fail'); status.classList.add('fail'); status.textContent = '✗ Fail'; }
    }
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
        await fetch('/preferences/save', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }, body: JSON.stringify({ academic_year: obData.academic_year, program_type: obData.program_type }) });
        const gradesToSave = Object.entries(obData.grades).map(([sid, grade]) => ({ subject_id: parseInt(sid), grade }));
        if (gradesToSave.length > 0) await fetch('/grades/save', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }, body: JSON.stringify(gradesToSave) });
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
    document.querySelectorAll('.grade-input').forEach(input => { const val = parseFloat(input.value); if (input.value === '' || isNaN(val)) empty++; else if (val > 5.0) pass++; else fail++; });
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
            const payload = { academic_year: document.getElementById('academic_year').value, program_type: document.getElementById('program_type').value, current_courses: currentCourses };
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

async function loadGradesFromDB() {
    try {
        const res = await fetch('/grades', { headers: { 'Accept': 'application/json' } }); if (!res.ok) return;
        const grades = await res.json();
        grades.forEach(({ subject_id, grade }) => { const input = document.getElementById(`grade-${subject_id}`); if (!input) return; if (grade !== null && grade !== undefined) { input.value = grade; onGradeChange(subject_id, input, true); } });
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
    document.querySelectorAll('.grade-input').forEach(input => { const val = parseFloat(input.value); if (!isNaN(val) && val > 5.0) earned += parseInt(input.dataset.credits || 0); });
    currentCourses.forEach(c => { if (c.grade !== null && c.grade > 5.0) earned += (c.credits || 0); });
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

    if (card) { card.classList.remove('pass', 'fail'); if (!isNaN(val) && val > 5.0) card.classList.add('pass'); else if (!isNaN(val) && val <= 5.0 && input.value !== '') card.classList.add('fail'); }
    input.classList.remove('is-pass', 'is-fail');

    if (status) {
        status.classList.remove('pass', 'fail', 'empty');
        if (input.value === '' || isNaN(val)) { status.textContent = isInDrawer ? '—' : 'Chưa nhập'; status.classList.add('empty'); }
        else if (val > 5.0) { input.classList.add('is-pass'); status.textContent = '✓ Pass'; status.classList.add('pass'); }
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
    else if (val > 5.0) { item.classList.add('cc-pass'); input.classList.add('is-pass'); status.textContent = 'Pass'; status.classList.add('pass'); }
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
            <div class="course-item${c.grade !== null && c.grade > 5 ? ' cc-pass' : c.grade !== null ? ' cc-fail' : ''}" id="cc-item-${c.id}">
                <div class="course-info">
                    <span class="course-name">${c.name}</span>
                    <span class="course-meta">${c.credits} tín chỉ · Học kỳ chuẩn ${c.semesterName}</span>
                </div>
                <div class="course-right">
                    <input type="number" class="grade-input-clay${c.grade !== null && c.grade > 5 ? ' is-pass' : c.grade !== null ? ' is-fail' : ''}"
                           id="cc-grade-${c.id}" min="0" max="10" step="0.1" placeholder="Điểm"
                           value="${c.grade !== null ? c.grade : ''}"
                           oninput="onCCGradeChange(${c.id},this)">
                    <span class="grade-status-clay ${c.grade !== null && c.grade > 5 ? 'pass' : c.grade !== null ? 'fail' : 'empty'}" id="cc-status-${c.id}">${c.grade !== null && c.grade > 5 ? 'Pass' : c.grade !== null ? 'Fail' : '—'}</span>
                    <button class="btn-remove-clay" onclick="removeCourse(${c.id})" title="Xóa">✕</button>
                </div>
            </div>`).join('');
}

// ═══════════════════════════════════════════════════════════════
// SUGGESTIONS
// ═══════════════════════════════════════════════════════════════
function getPassedSubjectIds() {
    const passed = new Set();
    document.querySelectorAll('.grade-input').forEach(input => { const val = parseFloat(input.value); if (!isNaN(val) && val > 5.0) passed.add(input.dataset.subjectId); });
    currentCourses.forEach(c => { if (c.grade !== null && c.grade > 5.0) passed.add(String(c.id)); });
    return [...passed].join(',');
}

function getFailedSubjectIds() {
    const failed = new Set();
    document.querySelectorAll('.grade-input').forEach(input => { const val = parseFloat(input.value); if (!isNaN(val) && val > 0 && val <= 5.0) failed.add(parseInt(input.dataset.subjectId)); });
    currentCourses.forEach(c => { if (c.grade !== null && c.grade > 0 && c.grade <= 5.0) failed.add(c.id); });
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
            skill_evaluation: item.reasons.join(', ')
        }));

        // Limit subjects by max credits of active plan mode or personalized recommendation
        if (window.currentActivePlan && window.currentActivePlan.mode) {
            const mode = window.currentActivePlan.mode;
            
            // Check if there's a personalized recommendation from completing a semester
            const savedRec = localStorage.getItem('recommended_credits_per_sem');
            let maxCredits = savedRec ? parseInt(savedRec) : 18; 
            
            if (!savedRec) {
                if (mode === 'fast') maxCredits = 22;
                else if (mode === 'slow') maxCredits = 14;
            }

            let currentTotal = 0;
            let limitedData = [];
            for (let subj of mappedData) {
                if (subj.can_study !== false) {
                    if (currentTotal + subj.credits <= maxCredits) {
                        limitedData.push(subj);
                        currentTotal += subj.credits;
                    }
                } else {
                    limitedData.push(subj); // Keep locked subjects for reference if wanted, or just skip. We'll skip them to be clean, wait, no, the old UI showed them at the bottom.
                    // Actually let's just keep the ones we can study for the target semester.
                }
            }
            // Also append the locked ones at the bottom if needed, but let's just show the eligible ones up to maxCredits.
            mappedData = limitedData;
        }
        window.currentSuggestions = mappedData.filter(s => s.can_study !== false); // Save for apply button

        renderSuggestions(mappedData, semester);

        // Re-render study plan to sync the "Gợi ý cho bạn" badges
        if (window.currentActivePlan) {
            renderStudyPlan(window.currentActivePlan);
        }

        fetchProgress(); // Update progress and warnings
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

            // Update UI KPI
            document.getElementById('kpi-progress').textContent = prog.completion_percentage + '%';
            document.getElementById('kpi-progress-sub').textContent = `${prog.earned_credits} / ${prog.total_required_credits} TC hoàn thành`;

            const gpaEl = document.getElementById('kpi-gpa');
            if (gpaEl) gpaEl.textContent = prog.current_gpa || '—';

            // Update Warnings
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
}

function renderSuggestions(subjects, targetSemester) {
    const container = document.getElementById('suggestions-list');
    if (subjects.length === 0) {
        container.innerHTML = `<div class="empty-state"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg><h3>Không có môn học đề xuất nào!</h3><p>Hãy thử đổi niên khóa, loại chương trình hoặc học kỳ mong muốn phù hợp hơn.</p></div>`;
        return;
    }
    container.innerHTML = subjects.map(subject => {
        const subSem = parseInt(subject.semester?.name || 1);
        const targetSem = parseInt(targetSemester);
        const isAdded = currentCourses.find(c => c.id == subject.id);
        const failedIds = getFailedSubjectIds();
        const isFailed = failedIds.has(subject.id);
        let priorityLabel = '';
        let scoreText = `<span style="opacity:0.85;margin-left:4px;font-size:0.9em;">(${subject.suggestion_score}đ)</span>`;

        if (isFailed) priorityLabel = `<span class="pill pill-red">Học lại 🔄 ${scoreText}</span>`;
        else if (subject.suggestion_score >= 105) priorityLabel = `<span class="pill pill-mint">Ưu tiên Rất Cao 🔥 ${scoreText}</span>`;
        else if (subject.suggestion_score >= 95) priorityLabel = `<span class="pill pill-lavender">Ưu tiên Cao 👍 ${scoreText}</span>`;
        else if (subject.suggestion_score >= 80) priorityLabel = `<span class="pill pill-ochre">Ưu tiên Vừa 👌 ${scoreText}</span>`;
        else priorityLabel = `<span class="pill pill-red" style="background:#fee2e2;color:#b91c1c;border:none;">Ít Ưu tiên ⬇️ ${scoreText}</span>`;
        const isEligible = subject.can_study !== false;

        let tagHtml = '';
        let actionHtml = '';
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
        } else {
            tagHtml = `
                    <span class="pill pill-cream" style="background:transparent;color:#dc2626;border:none;padding:0;font-size:0.75rem;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;margin-right:2px;vertical-align:-3px;"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg> Thiếu môn tiên quyết</span>
                `;
        }

        return `
                <div class="suggestion-card${isFailed ? ' is-locked' : (!isEligible ? ' is-locked' : '')}" onclick="scrollToSubject(${subject.id})" style="cursor:pointer; transition: all 0.2s; border:1px solid var(--hairline);" onmouseover="this.style.transform='translateY(-2px)'; this.style.borderColor='var(--brand-mint)'; this.style.boxShadow='var(--shadow-sm)'" onmouseout="this.style.transform='none'; this.style.borderColor='var(--hairline)'; this.style.boxShadow='none'">
                    <div class="suggestion-card-top" style="padding-bottom: 0;">
                        <div class="suggestion-details">
                            <span class="suggestion-title">${subject.name}</span>
                            <div class="suggestion-tags">
                                ${tagHtml}
                            </div>
                        </div>
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
    const snapshot = currentCourses.map(c => ({ ...c }));
    const cur = getCurrentSemester();
    saveSemesterHistory(cur, snapshot.map(c => ({ id: c.id, grade: c.grade })));
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

function showSemResultModal(semNumber, snapshot) {
    const graded = snapshot.filter(c => c.grade !== null && c.grade !== undefined);
    const passSubjects = graded.filter(c => c.grade > 5.0);
    const failSubjects = graded.filter(c => c.grade <= 5.0);
    const gpa = graded.length > 0 ? Math.round(graded.reduce((s, c) => s + c.grade, 0) / graded.length * 10) / 10 : null;
    const creditsThisSem = snapshot.reduce((s, c) => s + (c.credits || 0), 0);
    const passedCredits = passSubjects.reduce((s, c) => s + (c.credits || 0), 0);
    let totalEarned = 0;
    if (typeof obData !== 'undefined' && obData && obData.grades && typeof subjectMap !== 'undefined') {
        totalEarned = Object.values(obData.grades)
            .filter(g => g.grade > 5.0 || ['passed', 'pass'].includes(g.status))
            .reduce((sum, g) => sum + (parseInt(subjectMap[g.subject_id]?.credits || 0)), 0);
    } else {
        document.querySelectorAll('.grade-input').forEach(input => { const val = parseFloat(input.value); if (!isNaN(val) && val > 5.0) totalEarned += parseInt(input.dataset.credits || 0); });
    }
    const planMode = window.currentActivePlan ? window.currentActivePlan.mode : (document.getElementById('planner-mode')?.value || 'normal');
    const totalSem = planMode === 'fast' ? 6 : (planMode === 'slow' ? 10 : 8);
    const nextSem = Math.min(semNumber + 1, totalSem);
    const remSem = Math.max(1, totalSem - semNumber);
    const remCredits = Math.max(0, TOTAL_CREDITS - totalEarned);
    const neededPerSem = remSem > 0 ? Math.ceil(remCredits / remSem) : 0;
    const progPct = Math.min(100, Math.round((totalEarned / TOTAL_CREDITS) * 100));
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
    const recEl = document.getElementById('srm-recommend'); recEl.className = `srm-recommend ${recType}`;
    document.getElementById('srm-rec-icon').textContent = recIcon;
    document.getElementById('srm-rec-tag').textContent = recTag;
    document.getElementById('srm-rec-headline').textContent = recHeadline;
    document.getElementById('srm-rec-desc').innerHTML = recDesc;
    const changeEl = document.getElementById('srm-credit-change');
    if (recDelta > 0) { changeEl.className = 'srm-credit-change up'; changeEl.innerHTML = `↑ ${suggestedCredits} <small style="font-size:.72rem;font-weight:500;color:var(--muted);">TC/kỳ (tăng +${recDelta})</small>`; }
    else if (recDelta < 0) { changeEl.className = 'srm-credit-change down'; changeEl.innerHTML = `↓ ${suggestedCredits} <small style="font-size:.72rem;font-weight:500;color:var(--muted);">TC/kỳ (giảm ${recDelta})</small>`; }
    else { changeEl.className = 'srm-credit-change same'; changeEl.innerHTML = `= ${suggestedCredits} <small style="font-size:.72rem;font-weight:500;color:var(--muted);">TC/kỳ (giữ nguyên)</small>`; }
    const reasonsEl = document.getElementById('srm-reasons');
    reasonsEl.innerHTML = reasons.map(r => `<div class="srm-reason-item"><span class="srm-reason-icon">${r.icon}</span><span>${r.text}</span></div>`).join('');
    const subjEl = document.getElementById('srm-subj-section');
    const subjectData = snapshot.map(c => { const input = document.getElementById(`grade-${c.id}`); const credits = parseInt(input?.dataset.credits || c.credits || 0); return { ...c, credits }; });
    const passHtml = subjectData.filter(c => c.grade > 5.0).map(c => `<div class="srm-subj-row pass"><span class="srm-subj-name">${c.name}</span><span class="srm-subj-credits">${c.credits} TC</span><span class="srm-subj-grade pass">${c.grade}</span></div>`).join('');
    const failHtml = subjectData.filter(c => c.grade <= 5.0).map(c => `<div class="srm-subj-row fail"><span class="srm-subj-name">${c.name}</span><span class="srm-subj-credits">${c.credits} TC</span><span class="srm-subj-grade fail">${c.grade}</span></div>`).join('');
    subjEl.innerHTML = `${passHtml ? `<div class="srm-subj-title">✓ Môn đạt (${passSubjects.length})</div><div class="srm-subj-list">${passHtml}</div>` : ''}${failHtml ? `<div class="srm-subj-title" style="color:var(--error);">✗ Môn chưa đạt (${failSubjects.length})</div><div class="srm-subj-list">${failHtml}</div>` : ''}`;
    const applyBtn = document.getElementById('srm-btn-apply');
    if (recDelta !== 0) { applyBtn.style.display = ''; applyBtn.innerHTML = `✨ Áp dụng gợi ý (${suggestedCredits} TC)`; }
    else { applyBtn.style.display = 'none'; }
    document.getElementById('sem-result-overlay').classList.add('open');
}

function closeSemResultModal() { document.getElementById('sem-result-overlay').classList.remove('open'); }

function applyCreditRecommendation() {
    localStorage.setItem('recommended_credits_per_sem', _semRecCredits);
    showToast(`Đã ghi nhớ gợi ý: ${_semRecCredits} TC/kỳ 📌`, 'success');
    closeSemResultModal();
    document.getElementById('stat-credits-per-sem').textContent = _semRecCredits;
}

document.getElementById('sem-result-overlay').addEventListener('click', function (e) { if (e.target === this) closeSemResultModal(); });

async function saveSemesterHistory(semesterNumber, snapshot) {
    try {
        const courses = snapshot.map(({ id, grade }) => ({ subject_id: id, grade }));
        const payload = { semester_number: semesterNumber, academic_year: document.getElementById('academic_year')?.value || null, program_type: document.getElementById('program_type')?.value || null, courses };
        const res = await fetch('/semester-history/complete', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }, body: JSON.stringify(payload) });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        loadSemesterHistory();
    } catch (err) { console.warn('[Lưu lịch sử thất bại]', err); }
}

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
    }
    
    // Khởi tạo và lắng nghe thay đổi chế độ học (Mode) để tính toán số tín chỉ mục tiêu
    const modeSelect = document.getElementById('planner-mode');
    if (modeSelect) {
        modeSelect.addEventListener('change', updateTargetCreditsUI);
        // Đợi load xong điểm để tính cho chính xác
        setTimeout(updateTargetCreditsUI, 500); 
    }
});

function updateTargetCreditsUI() {
    const targetEl = document.getElementById('planner-target-credits');
    const modeSelect = document.getElementById('planner-mode');
    if (!targetEl || !modeSelect) return;

    // TOTAL_CREDITS có sẵn từ HTML render
    let earned = 0;
    if (obData && obData.grades) {
        earned = Object.values(obData.grades)
            .filter(g => g.grade > 5.0 || ['passed', 'pass'].includes(g.status))
            .reduce((sum, g) => sum + (parseInt(subjectMap[g.subject_id]?.credits || 0)), 0);
    }

    const unpassed = Math.max(0, TOTAL_CREDITS - earned);
    const currentSem = getCurrentSemester();
    
    let targetTotalSems = 8;
    if (modeSelect.value === 'fast') targetTotalSems = 6;
    if (modeSelect.value === 'slow') targetTotalSems = 10;
    
    const remainingSems = Math.max(1, targetTotalSems - currentSem + 1);
    const targetPerSem = Math.ceil(unpassed / remainingSems);

    targetEl.style.display = 'block';
    targetEl.innerHTML = `Mục tiêu cần đạt: <strong>~${targetPerSem} tín chỉ/kỳ</strong> (để ra trường đúng tiến độ)`;
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
        renderGradeChart(chartRawData, 'all');
        renderGradeChartDetail(chartRawData, 'all');
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

    const barColors = filteredMy.map(v => v === null ? 'rgba(10,10,10,0.08)' : v > 5.0 ? 'rgba(10,10,10,0.85)' : 'rgba(239,68,68,0.8)');
    const borderColors = filteredMy.map(v => v === null ? 'rgba(10,10,10,0.15)' : v > 5.0 ? '#0a0a0a' : '#ef4444');

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
                        label: item => { if (item.dataset.label === 'Điểm của bạn') { const v = item.raw; return v === null ? '  Chưa nhập' : `  Của bạn: ${v} ${v > 5 ? '✓ Pass' : '✗ Fail'}`; } return item.raw !== null ? `  TB khóa: ${item.raw}` : '  Chưa có dữ liệu TB'; },
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

    const barColors = filteredMy.map(v => v === null ? 'rgba(10,10,10,0.08)' : v > 5.0 ? 'rgba(10,10,10,0.85)' : 'rgba(239,68,68,0.8)');
    const borderColors = filteredMy.map(v => v === null ? 'rgba(10,10,10,0.15)' : v > 5.0 ? '#0a0a0a' : '#ef4444');

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
            plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(10,10,10,0.92)', padding: 12, callbacks: { title: items => filteredLabels[items[0].dataIndex], label: item => { if (item.dataset.label === 'Điểm của bạn') { const v = item.raw; return v === null ? '  Chưa nhập' : `  Của bạn: ${v} ${v > 5 ? '✓ Pass' : '✗ Fail'}`; } return item.raw !== null ? `  TB khóa: ${item.raw}` : '  Chưa có dữ liệu TB'; } } } },
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
    const grades = {};
    document.querySelectorAll('.grade-input').forEach(input => { const sid = parseInt(input.dataset.subjectId); const val = parseFloat(input.value); if (!isNaN(val) && input.value !== '') grades[sid] = val; });
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
        totalGraded++; if (val > 5.0) totalEarned += credits; else totalFail++;
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
        const passRate = allGrades.filter(g => g.grade > 5).length / allGrades.length;
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
document.addEventListener('DOMContentLoaded', () => { renderDashboard(); });

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
                        <div style="font-weight:600; margin-bottom:4px;">${plan.name} <span class="pill pill-lavender" style="font-size:0.7rem; padding:2px 6px;">${plan.mode.toUpperCase()}</span></div>
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
    document.getElementById('planner-selection-view').style.display = 'grid';
    document.getElementById('study-plan-results').style.display = 'none';
    fetchSavedPlansList();
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
    const mode = document.getElementById('planner-mode').value;
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
            body: JSON.stringify({ mode, name })
        });
        if (!res.ok) throw new Error('API error');
        const resData = await res.json();
        if (resData.success && resData.data) {
            renderStudyPlan(resData.data);
            showToast('Tạo kế hoạch học tập thành công! 🎉', 'success');
            fetchSavedPlansList(); // Cập nhật lại danh sách kế hoạch bên cạnh
            fetchSuggestions(); // Cập nhật lại danh sách gợi ý theo mode vừa tạo
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
        container.innerHTML = `
            <button onclick="backToPlannerSelection()" class="btn-secondary" style="margin-bottom:16px;">⬅ Trở lại</button>
            <div class="empty-state">Không có môn học nào cần học nữa. Bạn đã đủ tín chỉ!</div>`;
        return;
    }

    let html = `
        <div style="background:var(--surface-soft); padding:16px; border-radius:12px; margin-bottom:20px; border:1px solid var(--hairline); display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:12px;">
                <button onclick="backToPlannerSelection()" style="background:var(--surface); border:1px solid var(--hairline); padding:6px 12px; border-radius:8px; cursor:pointer; font-size:0.85rem; font-weight:600; display:flex; align-items:center; gap:6px; color:var(--ink); transition:all 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='var(--surface)'">
                    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    Trở lại
                </button>
                <div style="border-left:1px solid var(--hairline); padding-left:12px;">
                    <h3 style="margin:0 0 4px 0;">${plan.name} <span class="pill pill-lavender">${plan.mode.toUpperCase()}</span></h3>
                    <p style="margin:0; color:var(--muted); font-size:0.9rem;">Dự kiến hoàn thành trong <strong>${plan.target_semester_count}</strong> học kỳ.</p>
                </div>
            </div>
            <span style="color:#10b981; font-weight:600; font-size:0.85rem; display:flex; align-items:center; gap:4px;" title="Mọi chỉnh sửa kéo thả của bạn đều được hệ thống tự động lưu ngay lập tức.">✅ Đã lưu <span style="font-size:0.7rem; font-weight:400; color:var(--muted);">(Auto-save)</span></span>
        </div>
        <div style="display:flex; flex-direction:column; gap:20px;">
    `;

    plan.semesters.forEach(sem => {
        html += `
            <div class="clay-card study-plan-semester" 
                 data-semester-index="${sem.semester_index}"
                 ondragover="handleDragOver(event)" 
                 ondragleave="handleDragLeave(event)" 
                 ondrop="handleDrop(event, ${plan.id}, ${sem.semester_index})">
                <div class="card-title-row" style="border-bottom:1px solid var(--hairline); padding-bottom:12px; margin-bottom:12px; pointer-events:none;">
                    <strong>Học kỳ ${sem.semester_index}</strong>
                    <span class="pill" style="background:#e8f8f3; color:#10b981;">${sem.expected_credits} Tín chỉ</span>
                </div>
                <div class="semester-subjects-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:12px; min-height: 50px;">
        `;

        sem.subjects.forEach(ss => {
            const sub = ss.subject;
            if (sub) {
                const hasGrade = ss.grade !== undefined && ss.grade !== null;
                const isCompleted = hasGrade && ss.grade > 5.0;
                const isFailed = hasGrade && ss.grade <= 5.0;

                const cardBg = isCompleted ? '#f0fdf4' : (isFailed ? '#fef2f2' : 'var(--surface)');
                const borderColor = isCompleted ? '#86efac' : (isFailed ? '#fca5a5' : 'var(--hairline)');
                const draggableAttr = !isCompleted ? 'draggable="true" ondragstart="handleDragStart(event, ' + sub.id + ', ' + sem.semester_index + ')" ondragend="handleDragEnd(event)"' : '';

                // Đồng bộ nhãn Gợi ý với danh sách gợi ý hiện tại
                const isSuggested = window.currentSuggestions && window.currentSuggestions.some(s => s.id === sub.id);
                const highlyRecommendedClass = (!isCompleted && isSuggested) ? 'highly-recommended' : '';

                let statusHtml = '';
                if (isCompleted) {
                    statusHtml = '<span style="color:#10b981; font-size:0.85rem; font-weight:600;">✓ Pass</span>';
                } else if (isFailed) {
                    statusHtml = '<span style="color:#ef4444; font-size:0.85rem; font-weight:600;">✗ Rớt</span>';
                }

                html += `
                    <div class="study-plan-subject ${highlyRecommendedClass}" 
                         id="plan-subject-${sub.id}"
                         ${draggableAttr}
                         style="padding:12px; border:1px solid ${borderColor}; border-radius:8px; background:${cardBg}; position:relative; scroll-margin-top: 100px;">
                        
                        <button type="button" class="icon-btn" 
                                style="position:absolute; top:8px; right:8px; padding:4px; border:none; background:transparent; color:var(--muted); cursor:pointer; z-index:2;"
                                onclick='openPrereqModal(${JSON.stringify(sub).replace(/'/g, "&#39;")})'
                                title="Xem môn tiên quyết"
                                onmousedown="event.stopPropagation()">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:20px;height:20px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        </button>

                        <div style="font-weight:600; font-size:0.95rem; margin-bottom:8px; padding-right:24px;">${sub.name}</div>
                        <div style="font-size:0.8rem; color:var(--muted); margin-bottom:12px;">${sub.credits} TC | Nhóm: ${sub.skill_group_id || 'Chung'}</div>
                        
                        <div style="display:flex; align-items:center; gap:8px;" onmousedown="event.stopPropagation()">
                            <input type="number" 
                                   class="ob-grade-input" 
                                   placeholder="Điểm..." 
                                   min="0" max="10" step="0.1" 
                                   value="${hasGrade ? ss.grade : ''}"
                                   style="width: 80px; height: 32px; font-size: 0.85rem;"
                                   onchange="updatePlanGrade(${plan.id}, ${sub.id}, this)">
                            ${statusHtml}
                        </div>
                    </div>
                `;
            }
        });

        html += '</div></div>';
    });

    html += '</div>';
    container.innerHTML = html;
}

// ─── Drag and Drop Handlers ───
let draggedSubjectId = null;
let draggedSourceSemester = null;

function handleDragStart(event, subjectId, semesterIndex) {
    draggedSubjectId = subjectId;
    draggedSourceSemester = semesterIndex;
    event.dataTransfer.effectAllowed = 'move';
    setTimeout(() => {
        event.target.classList.add('is-dragging');
    }, 0);
}

function handleDragEnd(event) {
    event.target.classList.remove('is-dragging');
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
    const semesterCard = event.target.closest('.study-plan-semester');
    if (semesterCard) {
        semesterCard.classList.remove('drag-over');
    }

    if (!draggedSubjectId || draggedSourceSemester === targetSemesterIndex) {
        return;
    }

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

        if (!res.ok) {
            throw new Error(resData.error || 'Lỗi hệ thống');
        }

        if (resData.success && resData.data) {
            renderStudyPlan(resData.data);
            showToast('Di chuyển môn học thành công!', 'success');
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
            body: JSON.stringify({ study_plan_id: planId, subject_id: subjectId, grade: grade, status: status })
        });

        if (!res.ok) throw new Error('API error');
        const resData = await res.json();

        inputEl.disabled = false;
        showSaveIndicator('saved', grade === null ? 'Đã xóa điểm' : 'Đã lưu điểm');

        if (grade === null) {
            // Khi xóa điểm, cấu trúc kế hoạch bị thay đổi (môn chưa học ở quá khứ), 
            // nên ta tự động tạo lại kế hoạch với mode hiện tại.
            if (window.currentActivePlan && window.currentActivePlan.mode) {
                showToast('Đang cập nhật lại lộ trình...', 'info');
                adjustStudyPlan(planId, { suggested_mode: window.currentActivePlan.mode, message: 'Người dùng xóa điểm', gpa: 0 });
            } else {
                fetchStudyPlans();
            }
        } else {
            // Check if all grades in this semester card are filled to trigger suggestion modal automatically
            let allFilled = false;
            let snapshot = [];
            let semIndex = 1;
            const semesterCard = inputEl.closest('.study-plan-semester');
            if (semesterCard) {
                semIndex = parseInt(semesterCard.dataset.semesterIndex);
                const inputs = semesterCard.querySelectorAll('.ob-grade-input');
                allFilled = inputs.length > 0;
                inputs.forEach(inp => {
                    if (inp.value.trim() === '') allFilled = false;
                    else {
                        const subjCard = inp.closest('.study-plan-subject');
                        if (subjCard && subjCard.id) {
                            const idStr = subjCard.id.split('-')[2];
                            if (idStr) {
                                const sid = parseInt(idStr);
                                const gradeVal = parseFloat(inp.value);
                                snapshot.push({ id: sid, grade: gradeVal });
                            }
                        }
                    }
                });
            }

            if (allFilled && snapshot.length > 0 && window.currentActivePlan) {
                // Populate credits and names for the snapshot
                const semData = window.currentActivePlan.semesters.find(s => s.semester_index === semIndex);
                if (semData && semData.subjects) {
                    snapshot.forEach(c => {
                        const subData = semData.subjects.find(s => s.subject_id === c.id);
                        c.credits = subData && subData.subject ? subData.subject.credits : 0;
                        c.name = subData && subData.subject ? subData.subject.name : '';
                    });
                }
                
                // Show modal immediately
                fetchStudyPlans().then(() => {
                    showSemResultModal(semIndex, snapshot);
                });
            } else if (resData.success && resData.evaluation) {
                const evaluation = resData.evaluation;
                if (evaluation.status !== 'KEEP') {
                    if (confirm(`Hệ thống nhận thấy tiến độ thay đổi:\n"${evaluation.message}"\n\nBạn có muốn hệ thống tự động điều chỉnh kế hoạch học tập sang chế độ "${evaluation.suggested_mode}" không?`)) {
                        adjustStudyPlan(planId, evaluation);
                    } else {
                        fetchStudyPlans(); // Reload to show passed/failed state
                    }
                } else {
                    fetchStudyPlans();
                }
            } else {
                fetchStudyPlans();
            }
        }
    } catch (e) {
        inputEl.disabled = false;
        showSaveIndicator('error', 'Lỗi lưu điểm');
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
        if (drawer) drawer.style.right = '-450px';
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

    const finalConfirmMsg = `Bạn sắp áp dụng gợi ý cho Học kỳ ${targetSemesterIndex}.\nLưu ý: Các môn tương lai sẽ được sắp xếp lại. Bạn có chắc chắn muốn tiếp tục không?`;
    if (!window.confirm(finalConfirmMsg)) {
        return;
    }

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
            if (drawer) drawer.style.right = '-450px';
        }
    } catch (e) {
        showToast(e.message || 'Lỗi khi áp dụng gợi ý.', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
