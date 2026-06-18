<?php

$filepath = 'e:\\LuanVan\\DuAn\\public\\js\\student-planner.js';
$content = file_get_contents($filepath);

$helperCode = <<<EOT

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

EOT;

if (strpos($content, 'function getCurrentSemester()') === false) {
    $content = str_replace('let obStep = 0;', $helperCode . 'let obStep = 0;', $content);
}

$target1 = <<<EOT
const OB_STEPS = [
    { label: 'Bước 1 / 4', icon: '🎓', iconBg: '#f5f0e0', title: 'Chào mừng bạn!', desc: 'Hãy cho chúng tôi biết bạn đang theo học chương trình nào để hệ thống gợi ý chính xác nhất.' },
    { label: 'Bước 2 / 4', icon: '📅', iconBg: '#faf5e8', title: 'Bạn đang học kỳ nào?', desc: 'Chọn học kỳ hiện tại để hệ thống xác định các môn phù hợp với tiến độ.' },
    { label: 'Bước 3 / 4', icon: '📝', iconBg: '#faf5e8', title: 'Điểm số của bạn', desc: 'Nhập điểm các môn bạn đã học. Chỉ nhập những môn đã có điểm.' },
    { label: 'Bước 4 / 4', icon: '🏆', iconBg: '#f0fdf4', title: 'Mục tiêu tốt nghiệp', desc: 'Bạn muốn hoàn thành chương trình trong bao nhiêu năm?' },
];
EOT;

$rep1 = <<<EOT
const OB_STEPS = [
    { label: 'Bước 1 / 2', icon: '🎓', iconBg: '#f5f0e0', title: 'Chào mừng bạn!', desc: 'Hãy cho chúng tôi biết bạn đang theo học chương trình nào để hệ thống gợi ý chính xác nhất.' },
    { label: 'Bước 2 / 2', icon: '📝', iconBg: '#faf5e8', title: 'Điểm số của bạn', desc: 'Nhập điểm các môn bạn đã học. Chỉ nhập những môn đã có điểm.' },
];
EOT;
$content = str_replace($target1, $rep1, $content);

$target2 = <<<EOT
    } else if (obStep === 1) {
        const btns = Array.from({ length: 8 }, (_, i) => i + 1).map(i => `<button class="ob-sem-btn \${obData.current_semester === i ? 'selected' : ''}" onclick="obSelectSem(\${i},this)">Học kỳ \${i}</button>`).join('');
        body.innerHTML = `<div class="ob-semester-grid">\${btns}</div>`;
    } else if (obStep === 2) {
EOT;
$rep2 = <<<EOT
    } else if (obStep === 1) {
EOT;
$content = str_replace($target2, $rep2, $content);

$target3 = <<<EOT
    } else if (obStep === 3) {
        const years = [3, 4, 5, 6];
        const descs = { 3: 'Rất nhanh', 4: 'Tiêu chuẩn', 5: 'Bình thường', 6: 'Linh hoạt' };
        const btns = years.map(y => `<button class="ob-year-btn \${obData.target_years === y ? 'selected' : ''}" onclick="obSelectYear(\${y},this)">\${y} năm<small>\${descs[y]}</small></button>`).join('');
        body.innerHTML = `<div class="ob-year-grid">\${btns}</div><p style="margin-top:var(--sp-md);font-size:0.8rem;color:var(--muted);text-align:center;">Thông thường chương trình Đại học 4 năm gồm 8 học kỳ.</p>`;
    }
EOT;
$rep3 = <<<EOT

EOT;
$content = str_replace($target3, $rep3, $content);

$target4 = <<<EOT
    progText.textContent = `Bước \${obStep + 1} / 4`;
    if (obStep === 3) { btnNext.textContent = '🎉 Hoàn thành!'; btnNext.className = 'ob-btn-next finish'; }
EOT;
$rep4 = <<<EOT
    progText.textContent = `Bước \${obStep + 1} / 2`;
    if (obStep === 1) { btnNext.textContent = '🎉 Hoàn thành!'; btnNext.className = 'ob-btn-next finish'; }
EOT;
$content = str_replace($target4, $rep4, $content);

$target5 = <<<EOT
    if (obStep === 1 && !obData.current_semester) { showToast('Vui lòng chọn học kỳ hiện tại!', 'error'); return; }
    if (obStep === 3) { if (!obData.target_years) { showToast('Vui lòng chọn mục tiêu tốt nghiệp!', 'error'); return; } obFinish(); return; }
EOT;
$rep5 = <<<EOT
    if (obStep === 1) { obFinish(); return; }
EOT;
$content = str_replace($target5, $rep5, $content);

$content = str_replace("JSON.stringify({ academic_year: obData.academic_year, program_type: obData.program_type, current_semester: obData.current_semester, target_years: obData.target_years })", "JSON.stringify({ academic_year: obData.academic_year, program_type: obData.program_type })", $content);

$target6 = <<<EOT
    if (data.current_semester) document.getElementById('target_semester').value = data.current_semester;
    if (data.target_years) document.getElementById('target_years').value = data.target_years;
EOT;
$content = str_replace($target6, "", $content);

$content = str_replace("current_semester: parseInt(document.getElementById('target_semester').value), target_years: parseInt(document.getElementById('target_years').value), ", "", $content);
$content = str_replace("current_semester: parseInt(document.getElementById('target_semester').value), target_years: parseInt(document.getElementById('target_years').value)", "", $content);

$content = str_replace("document.getElementById('target_semester').addEventListener('change', () => { clearTimeout(saveTimer); showSaveIndicator('hide'); savePreferences(); updateEarnedCredits(); fetchSuggestions(); });", "", $content);
$content = str_replace("document.getElementById('target_years').addEventListener('change', () => { clearTimeout(saveTimer); showSaveIndicator('hide'); savePreferences(); updateCreditStats(); });", "", $content);

$target7 = <<<EOT
        if (prefs.current_semester) document.getElementById('target_semester').value = prefs.current_semester;
        if (prefs.target_years) document.getElementById('target_years').value = prefs.target_years;
EOT;
$content = str_replace($target7, "", $content);

$target8 = <<<EOT
    const years = parseInt(document.getElementById('target_years').value);
    const totalSem = years * 2;
    document.getElementById('stat-total-semesters').textContent = totalSem;
    updateEarnedCredits();

    // Update KPI card
    const curSem = document.getElementById('target_semester')?.value;
EOT;
$rep8 = <<<EOT
    document.getElementById('stat-total-semesters').textContent = 8;
    updateEarnedCredits();

    // Update KPI card
    const curSem = getCurrentSemester();
EOT;
$content = str_replace($target8, $rep8, $content);

$target9 = <<<EOT
    const years = parseInt(document.getElementById('target_years').value);
    const totalSem = years * 3;
    const currentSem = parseInt(document.getElementById('target_semester').value);
EOT;
$rep9 = <<<EOT
    const totalSem = 8;
    const currentSem = getCurrentSemester();
EOT;
$content = str_replace($target9, $rep9, $content);

$content = str_replace("const semester = document.getElementById('target_semester').value;", "const semester = getCurrentSemester();", $content);

$target10 = <<<EOT
    const sel = document.getElementById('target_semester');
    const cur = parseInt(sel.value);
EOT;
$rep10 = <<<EOT
    const cur = getCurrentSemester();
EOT;
$content = str_replace($target10, $rep10, $content);
$content = str_replace("if (cur < 8) { sel.value = cur + 1; } else { showToast('Đã hoàn thành toàn bộ chương trình! 🎓', 'success'); }", "if (cur >= 8) { showToast('Đã hoàn thành toàn bộ chương trình! 🎓', 'success'); }", $content);

$target11 = <<<EOT
    const targetYears = parseInt(document.getElementById('target_years')?.value || 4);
    const currentSem = parseInt(document.getElementById('target_semester')?.value || 1);
    const totalSem = targetYears * 3;
EOT;
$rep11 = <<<EOT
    const currentSem = getCurrentSemester();
    const totalSem = 8;
EOT;
$content = str_replace($target11, $rep11, $content);


file_put_contents($filepath, $content);
echo "JS modified successfully.\n";

?>
