const fs = require('fs');
const file = 'public/js/student-planner.js';
let content = fs.readFileSync(file, 'utf8');

const target1 = `    let html = \`
        <div style="background:var(--surface-soft); padding:16px; border-radius:12px; margin-bottom:20px; border:1px solid var(--hairline);">
            <h3 style="margin:0 0 8px 0;">\${plan.name} <span class="pill pill-lavender">\${plan.mode.toUpperCase()}</span></h3>
            <p style="margin:0; color:var(--muted); font-size:0.9rem;">Dự kiến hoàn thành trong <strong>\${plan.target_semester_count}</strong> học kỳ.</p>
        </div>`;

const replace1 = `    let html = \`
        <div style="background:var(--surface-soft); padding:16px; border-radius:12px; margin-bottom:20px; border:1px solid var(--hairline); display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0 0 8px 0;">\${plan.name} <span class="pill pill-lavender">\${plan.mode.toUpperCase()}</span></h3>
                <p style="margin:0; color:var(--muted); font-size:0.9rem;">Dự kiến hoàn thành trong <strong>\${plan.target_semester_count}</strong> học kỳ.</p>
            </div>
            \${!plan.is_saved ? \`<button class="btn-primary" onclick="saveCurrentPlan(\${plan.id})" style="height:34px; padding:0 16px; font-size:0.85rem; background:var(--brand-mint); color:var(--ink); border:none; border-radius:6px; font-weight:600; cursor:pointer;">💾 Lưu kế hoạch</button>\` : \`<span style="color:#10b981; font-weight:600; font-size:0.85rem;">✅ Đã lưu</span>\`}
        </div>`;

const target2 = `async function generateStudyPlan() {`;

const replace2 = `async function saveCurrentPlan(planId) {
    const name = prompt("Nhập tên để dễ nhớ cho kế hoạch này:", window.currentActivePlan.name);
    if (name === null) return;
    
    try {
        const res = await fetch(\`/api/v1/study-plans/\${planId}/save\`, {
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

function openSavedPlansModal() {
    const modal = document.getElementById('saved-plans-modal-overlay');
    modal.style.display = 'flex';
    fetchSavedPlansList();
}

function closeSavedPlansModal() {
    const modal = document.getElementById('saved-plans-modal-overlay');
    modal.style.display = 'none';
}

async function fetchSavedPlansList() {
    const listContainer = document.getElementById('saved-plans-list');
    listContainer.innerHTML = '<p style="color:var(--muted); text-align:center;">Đang tải...</p>';
    try {
        const res = await fetch('/api/v1/study-plans/saved');
        const resData = await res.json();
        if(resData.success && resData.data.length > 0) {
            listContainer.innerHTML = resData.data.map(plan => \`
                <div class="saved-plan-item" onclick="loadSavedPlan(\${plan.id})">
                    <div style="font-weight:600; margin-bottom:4px;">\${plan.name} <span class="pill pill-lavender" style="font-size:0.7rem; padding:2px 6px;">\${plan.mode.toUpperCase()}</span></div>
                    <div style="font-size:0.8rem; color:var(--muted);">Cập nhật: \${new Date(plan.updated_at).toLocaleString('vi-VN')}</div>
                </div>
            \`).join('');
        } else {
            listContainer.innerHTML = '<p style="color:var(--muted); text-align:center;">Chưa có kế hoạch nào được lưu.</p>';
        }
    } catch(e) {
        listContainer.innerHTML = '<p style="color:var(--error); text-align:center;">Lỗi tải dữ liệu.</p>';
    }
}

async function loadSavedPlan(planId) {
    closeSavedPlansModal();
    const loader = document.getElementById('planner-loader');
    const container = document.getElementById('study-plan-results');
    loader.style.display = 'block';
    container.style.opacity = '0.3';
    try {
        const res = await fetch(\`/api/v1/study-plans/\${planId}/load\`);
        const resData = await res.json();
        if(resData.success) {
            renderStudyPlan(resData.data);
            showToast('Đã tải kế hoạch đã lưu!', 'success');
        }
    } catch(e) {
        showToast('Lỗi khi tải kế hoạch.', 'error');
    } finally {
        loader.style.display = 'none';
        container.style.opacity = '1';
    }
}

async function generateStudyPlan() {`;

// Normalize line endings to help match
const contentNorm = content.replace(/\r\n/g, '\n');
const target1Norm = target1.replace(/\r\n/g, '\n');

if (contentNorm.includes(target1Norm)) {
    content = contentNorm.replace(target1Norm, replace1);
    content = content.replace(target2, replace2);
    fs.writeFileSync(file, content, 'utf8');
    console.log("Success");
} else {
    console.log("Failed to find target1");
}
