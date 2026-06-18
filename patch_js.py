import sys

filepath = r'e:\LuanVan\DuAn\public\js\student-planner.js'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add getCurrentSemester() helper at the top or after TAB_TITLES
helper_code = '''
function getCurrentSemester() {
    let maxSem = 0;
    const grades = {};
    document.querySelectorAll('.grade-input').forEach(input => {
        if (input.value !== '') {
            grades[parseInt(input.dataset.subjectId)] = parseFloat(input.value);
        }
    });
    // Add CC grades
    currentCourses.forEach(c => {
        if (c.grade !== null) grades[c.id] = c.grade;
    });

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
'''
if 'function getCurrentSemester()' not in content:
    content = content.replace('let obStep = 0;', helper_code + '\nlet obStep = 0;')

# 2. Fix OB_STEPS and renderObBody
content = content.replace('''const OB_STEPS = [
    { label: 'Bước 1 / 4', icon: '🎓', iconBg: '#f5f0e0', title: 'Chào mừng bạn!', desc: 'Hãy cho chúng tôi biết bạn đang theo học chương trình nào để hệ thống gợi ý chính xác nhất.' },
    { label: 'Bước 2 / 4', icon: '📅', iconBg: '#faf5e8', title: 'Bạn đang học kỳ nào?', desc: 'Chọn học kỳ hiện tại để hệ thống xác định các môn phù hợp với tiến độ.' },
    { label: 'Bước 3 / 4', icon: '📝', iconBg: '#faf5e8', title: 'Điểm số của bạn', desc: 'Nhập điểm các môn bạn đã học. Chỉ nhập những môn đã có điểm.' },
    { label: 'Bước 4 / 4', icon: '🏆', iconBg: '#f0fdf4', title: 'Mục tiêu tốt nghiệp', desc: 'Bạn muốn hoàn thành chương trình trong bao nhiêu năm?' },
];''', '''const OB_STEPS = [
    { label: 'Bước 1 / 2', icon: '🎓', iconBg: '#f5f0e0', title: 'Chào mừng bạn!', desc: 'Hãy cho chúng tôi biết bạn đang theo học chương trình nào để hệ thống gợi ý chính xác nhất.' },
    { label: 'Bước 2 / 2', icon: '📝', iconBg: '#faf5e8', title: 'Điểm số của bạn', desc: 'Nhập điểm các môn bạn đã học. Chỉ nhập những môn đã có điểm.' },
];''')

content = content.replace('''    } else if (obStep === 1) {
        const btns = Array.from({ length: 8 }, (_, i) => i + 1).map(i => `<button class="ob-sem-btn ${obData.current_semester === i ? 'selected' : ''}" onclick="obSelectSem(${i},this)">Học kỳ ${i}</button>`).join('');
        body.innerHTML = `<div class="ob-semester-grid">${btns}</div>`;
    } else if (obStep === 2) {''', '''    } else if (obStep === 1) {''')

content = content.replace('''    } else if (obStep === 3) {
        const years = [3, 4, 5, 6];
        const descs = { 3: 'Rất nhanh', 4: 'Tiêu chuẩn', 5: 'Bình thường', 6: 'Linh hoạt' };
        const btns = years.map(y => `<button class="ob-year-btn ${obData.target_years === y ? 'selected' : ''}" onclick="obSelectYear(${y},this)">${y} năm<small>${descs[y]}</small></button>`).join('');
        body.innerHTML = `<div class="ob-year-grid">${btns}</div><p style="margin-top:var(--sp-md);font-size:0.8rem;color:var(--muted);text-align:center;">Thông thường chương trình Đại học 4 năm gồm 8 học kỳ.</p>`;
    }''', '''''')

content = content.replace('''    progText.textContent = `Bước ${obStep + 1} / 4`;
    if (obStep === 3) { btnNext.textContent = '🎉 Hoàn thành!'; btnNext.className = 'ob-btn-next finish'; }''', '''    progText.textContent = `Bước ${obStep + 1} / 2`;
    if (obStep === 1) { btnNext.textContent = '🎉 Hoàn thành!'; btnNext.className = 'ob-btn-next finish'; }''')

content = content.replace('''    if (obStep === 1 && !obData.current_semester) { showToast('Vui lòng chọn học kỳ hiện tại!', 'error'); return; }
    if (obStep === 3) { if (!obData.target_years) { showToast('Vui lòng chọn mục tiêu tốt nghiệp!', 'error'); return; } obFinish(); return; }''', '''    if (obStep === 1) { obFinish(); return; }''')

# 3. Fix save preferences payload & apply UI
content = content.replace('''JSON.stringify({ academic_year: obData.academic_year, program_type: obData.program_type, current_semester: obData.current_semester, target_years: obData.target_years })''', '''JSON.stringify({ academic_year: obData.academic_year, program_type: obData.program_type })''')

content = content.replace('''    if (data.current_semester) document.getElementById('target_semester').value = data.current_semester;
    if (data.target_years) document.getElementById('target_years').value = data.target_years;''', '''''')

content = content.replace('''current_semester: parseInt(document.getElementById('target_semester').value), target_years: parseInt(document.getElementById('target_years').value), ''', '''''')
content = content.replace('''current_semester: parseInt(document.getElementById('target_semester').value), target_years: parseInt(document.getElementById('target_years').value)''', '''''')

# 4. Remove UI event listeners
content = content.replace('''document.getElementById('target_semester').addEventListener('change', () => { clearTimeout(saveTimer); showSaveIndicator('hide'); savePreferences(); updateEarnedCredits(); fetchSuggestions(); });''', '''''')
content = content.replace('''document.getElementById('target_years').addEventListener('change', () => { clearTimeout(saveTimer); showSaveIndicator('hide'); savePreferences(); updateCreditStats(); });''', '''''')

content = content.replace('''        if (prefs.current_semester) document.getElementById('target_semester').value = prefs.current_semester;
        if (prefs.target_years) document.getElementById('target_years').value = prefs.target_years;''', '''''')

# 5. Fix updateCreditStats
content = content.replace('''    const years = parseInt(document.getElementById('target_years').value);
    const totalSem = years * 2;
    document.getElementById('stat-total-semesters').textContent = totalSem;
    updateEarnedCredits();

    // Update KPI card
    const curSem = document.getElementById('target_semester')?.value;''', '''    document.getElementById('stat-total-semesters').textContent = 8;
    updateEarnedCredits();

    // Update KPI card
    const curSem = getCurrentSemester();''')

content = content.replace('''    const years = parseInt(document.getElementById('target_years').value);
    const totalSem = years * 3;
    const currentSem = parseInt(document.getElementById('target_semester').value);''', '''    const totalSem = 8;
    const currentSem = getCurrentSemester();''')

# 6. Fix fetchSuggestions
content = content.replace('''const semester = document.getElementById('target_semester').value;''', '''const semester = getCurrentSemester();''')

# 7. Fix completeSemester
content = content.replace('''    const sel = document.getElementById('target_semester');
    const cur = parseInt(sel.value);''', '''    const cur = getCurrentSemester();''')
content = content.replace('''    if (cur < 8) { sel.value = cur + 1; } else { showToast('Đã hoàn thành toàn bộ chương trình! 🎓', 'success'); }''', '''    if (cur >= 8) { showToast('Đã hoàn thành toàn bộ chương trình! 🎓', 'success'); }''')

# 8. Fix renderDashboard
content = content.replace('''    const targetYears = parseInt(document.getElementById('target_years')?.value || 4);
    const currentSem = parseInt(document.getElementById('target_semester')?.value || 1);
    const totalSem = targetYears * 3;''', '''    const currentSem = getCurrentSemester();
    const totalSem = 8;''')


with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)
print('Done modifying student-planner.js')
