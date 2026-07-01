@extends('admin.layouts.app')

@section('title', 'Phân công môn học — ' . $curriculumFramework->trainingProgram->program_name)
@section('page-title', 'Phân công môn học')
@section('breadcrumb')
 / <a href="{{ route('admin.curriculum.index') }}">Chương trình</a>
 / <span>{{ $curriculumFramework->trainingProgram->program_name }} ({{ $curriculumFramework->trainingProgram->academic_year }})</span>
@endsection

@push('styles')
<style>
    /* ── Modal overlay ──────────────────────────────── */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.45);
        z-index: 999;
        align-items: center;
        justify-content: center;
    }
    .modal-overlay.open { display: flex; }

    .modal-box {
        background: var(--canvas);
        border: 3px solid var(--ink);
        width: 560px;
        max-width: 95vw;
        max-height: 80vh;
        display: flex;
        flex-direction: column;
        animation: slideUp .18s ease;
    }
    @keyframes slideUp {
        from { transform: translateY(24px); opacity:0; }
        to   { transform: translateY(0);   opacity:1; }
    }

    .modal-head {
        padding: 16px 20px;
        border-bottom: 2px solid var(--hairline);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }
    .modal-head-title {
        font-family: 'Sora', sans-serif;
        font-weight: 700;
        font-size: 14px;
        color: var(--ink);
    }
    .modal-close {
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        color: var(--muted);
        line-height: 1;
        padding: 2px 6px;
    }
    .modal-close:hover { color: var(--ink); }

    .modal-search {
        padding: 12px 20px;
        border-bottom: 1px solid var(--hairline-soft);
        flex-shrink: 0;
    }
    .modal-search input {
        width: 100%;
        padding: 8px 12px;
        border: 2px solid var(--hairline);
        font-size: 13px;
        font-family: 'Inter', sans-serif;
        outline: none;
        background: var(--surface-soft);
    }
    .modal-search input:focus { border-color: var(--ink); }

    /* Subject list */
    .subject-list {
        flex: 1;
        overflow-y: auto;
        padding: 8px 0;
    }
    .subject-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 20px;
        cursor: pointer;
        transition: background .1s;
        border-bottom: 1px solid var(--hairline-soft);
    }
    .subject-item:hover { background: var(--surface-soft); }
    .subject-item.selected { background: #f0fdf4; }
    .subject-item input[type=checkbox] {
        width: 16px; height: 16px;
        accent-color: var(--ink);
        cursor: pointer;
        flex-shrink: 0;
    }
    .subject-item-name {
        flex: 1;
        font-size: 13px;
        font-weight: 600;
        color: var(--body-strong);
    }
    .subject-item-meta {
        font-size: 11px;
        color: var(--muted);
        white-space: nowrap;
    }
    .subject-item-code {
        font-size: 10px;
        color: var(--muted);
        display: block;
        font-weight: 400;
    }
    .subject-item.already-assigned {
        opacity: .45;
        cursor: not-allowed;
    }
    .no-results {
        padding: 24px;
        text-align: center;
        color: var(--muted);
        font-size: 13px;
    }

    .modal-foot {
        padding: 12px 20px;
        border-top: 2px solid var(--hairline);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
        background: var(--surface-card);
    }
    .modal-foot .selected-count {
        font-size: 13px;
        color: var(--muted);
    }
    .modal-foot .selected-count strong { color: var(--ink); }

    /* ── "+" add button on semester cards ── */
    .btn-add-subject {
        background: none;
        border: 2px dashed var(--hairline);
        color: var(--muted);
        padding: 5px 12px;
        font-size: 18px;
        cursor: pointer;
        line-height: 1;
        transition: all .15s;
    }
    .btn-add-subject:hover {
        border-color: var(--ink);
        color: var(--ink);
        background: var(--surface-strong);
    }

    /* Select all bar */
    .select-all-bar {
        padding: 6px 20px;
        background: var(--surface-soft);
        border-bottom: 1px solid var(--hairline-soft);
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--muted);
        flex-shrink: 0;
    }
    .select-all-bar label { text-transform: none; font-weight:500; letter-spacing:0; margin:0; cursor:pointer; }

    /* Elective groups in modal */
    .eg-group-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 20px;
        background: #f0f9ff;
        border-top: 2px solid #bae6fd;
        border-bottom: 1px solid #bae6fd;
        cursor: default;
    }
    .eg-group-header + .eg-group-header { border-top: 2px solid #bae6fd; margin-top: 4px; }
    .eg-subject {
        padding-left: 40px;
        border-left: 3px solid #bfdbfe;
        background: #fafcff;
    }
    .eg-subject:hover { background: #eff6ff !important; }
    .eg-subject.selected { background: #dbeafe !important; }
</style>
@endpush

@section('content')

{{-- Info header --}}
<div class="card" style="margin-bottom:20px; border-left:4px solid var(--brand-teal);">
    <div class="card-body" style="padding:14px 20px;">
        <div style="display:flex; gap:24px; flex-wrap:wrap; align-items:center;">
            <div>
                <div style="font-size:11px; color:var(--muted); text-transform:uppercase; font-weight:700;">Chương trình</div>
                <div style="font-size:15px; font-weight:700;">{{ $curriculumFramework->trainingProgram->program_name }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--muted); text-transform:uppercase; font-weight:700;">Niên khóa</div>
                <div style="font-size:15px; font-weight:700;">{{ $curriculumFramework->trainingProgram->academic_year }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--muted); text-transform:uppercase; font-weight:700;">Số học kỳ</div>
                <div style="font-size:15px; font-weight:700;">{{ $curriculumFramework->number_of_semesters }} HK</div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--muted); text-transform:uppercase; font-weight:700;">Tổng tín chỉ</div>
                @php 
                    $totalCredits = $curriculumFramework->calculatedTotalCredits();
                @endphp
                <div style="font-size:15px; font-weight:700;">{{ $totalCredits }} TC</div>
            </div>
            <div style="margin-left:auto; display:flex; gap:12px;">
                <form method="POST" action="{{ route('admin.curriculum.clear-all', $curriculumFramework) }}" onsubmit="return confirm('⚠️ CẢNH BÁO: Hành động này sẽ xóa TOÀN BỘ môn học khỏi tất cả các học kỳ của chương trình này. Bạn có chắc chắn muốn tiếp tục?');" style="margin:0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" style="background:var(--error); border-color:var(--error); color: white;">🗑️ Xóa toàn bộ</button>
                </form>
                <button class="btn btn-primary" style="background:var(--brand-teal); border-color:var(--brand-teal);" onclick="openAutoAssignModal()">✨ Phân công tự động</button>
                <a href="{{ route('admin.curriculum.index') }}" class="btn btn-secondary">← Quay lại</a>
            </div>
        </div>
    </div>
</div>

{{-- Semester cards grid --}}
<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap:20px;">
    @foreach($semestersWithSubjects as $semester)
    @php
        $assignedIds  = $semester->assignedSubjects->pluck('subject.id')->filter()->values();
        $totalTc      = $semester->assignedSubjects->sum(fn($cs) => $cs->subject->credits ?? 0);
        $mandatoryTc  = $semester->assignedSubjects->filter(fn($cs) => !($cs->subject->is_elective ?? false))->sum(fn($cs) => $cs->subject->credits ?? 0);
        $electiveTc   = $totalTc - $mandatoryTc;
    @endphp
    <div class="card">
        <div class="card-header" style="background: var(--surface-soft);">
            <div class="card-title">
                📅 Học kỳ {{ $semester->name }}
                <span class="badge badge-{{ $semester->assignedSubjects->count() > 0 ? 'teal' : 'muted' }}" style="margin-left:8px;">
                    {{ $semester->assignedSubjects->count() }} môn
                    @if($totalTc > 0) · {{ $totalTc }} TC @endif
                </span>
                @if($totalTc > 0)
                <span style="margin-left:6px;font-size:0.72rem;font-weight:500;color:var(--muted);display:inline-flex;align-items:center;gap:8px;">
                    @if($mandatoryTc > 0)
                    <span style="display:inline-flex;align-items:center;gap:3px;">
                        <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#10b981;"></span>
                        {{ $mandatoryTc }} TC bắt buộc
                    </span>
                    @endif
                    @if($electiveTc > 0)
                    <span style="display:inline-flex;align-items:center;gap:3px;">
                        <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#3b82f6;"></span>
                        {{ $electiveTc }} TC tự chọn
                    </span>
                    @endif
                </span>
                @endif
            </div>
            {{-- Actions --}}
            <div style="display: flex; gap: 8px;">
                @if($semester->assignedSubjects->count() > 0)
                <form method="POST" action="{{ route('admin.curriculum.clear-semester', [$curriculumFramework, $semester->id]) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tất cả môn học khỏi học kỳ này?');" style="margin: 0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-add-subject" style="color: var(--error); border-color: var(--error);" title="Xóa tất cả">
                        <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                    </button>
                </form>
                @endif
                <button class="btn-add-subject"
                        onclick="openModal({{ $semester->id }}, 'Học kỳ {{ $semester->name }}', {{ $assignedIds->toJson() }})"
                        title="Thêm môn học">
                    ＋
                </button>
            </div>
        </div>

        @if($semester->assignedSubjects->isEmpty())
        <div style="padding:24px; text-align:center; color:var(--muted); font-size:13px;">
            Chưa có môn học nào — nhấn <strong>＋</strong> để thêm
        </div>
        @else
        <div class="table-wrap">
            <table style="font-size:12px;">
                <thead>
                    <tr>
                        <th>Môn học</th>
                        <th width="50" style="text-align:center;">TC</th>
                        <th width="130">Nhóm kỹ năng</th>
                        <th width="44" style="text-align:center;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($semester->assignedSubjects as $cs)
                    <tr>
                        <td>
                            @if($cs->subject)
                                @if($cs->subject->subject_code)
                                    <code style="font-size:10px; color:var(--muted);">{{ $cs->subject->subject_code }}</code><br>
                                @endif
                                <span style="font-weight:600;">{{ $cs->subject->name }}</span>
                            @else
                                <span style="color:var(--error); font-size:11px;">— Môn đã bị xóa —</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <span class="badge badge-mint">{{ $cs->subject->credits ?? '—' }}</span>
                        </td>
                        <td style="font-size:11px; color:var(--muted);">
                            {{ Str::limit($cs->subject?->skillGroup?->name ?? '—', 22) }}
                        </td>
                        <td style="text-align:center;">
                            <form method="POST"
                                  action="{{ route('admin.curriculum.remove', [$curriculumFramework, $cs->id]) }}"
                                  onsubmit="return confirm('Xóa môn này khỏi học kỳ?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                        style="padding:3px 8px; font-size:12px;" title="Xóa">✕</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endforeach
</div>

{{-- ─── MODAL ─────────────────────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-overlay" onclick="closeModalOnBackdrop(event)">
    <div class="modal-box">
        <div class="modal-head">
            <div class="modal-head-title" id="modal-title">Thêm môn học</div>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>

        {{-- Search + Program Group filter --}}
        <div class="modal-search" style="display:flex; gap:8px; align-items:center;">
            <input type="text" id="modal-search-input" placeholder="🔍 Tìm theo tên hoặc mã môn..."
                   oninput="filterSubjects()" autocomplete="off" style="flex:1;">
            <select id="modal-pg-filter" onchange="filterSubjects()"
                    style="width:200px; padding:8px 10px; border:2px solid var(--hairline); font-size:12px;
                           font-family:'Inter',sans-serif; background:var(--surface-soft); outline:none; flex-shrink:0;">
                <option value="">— Tất cả nhóm —</option>
                @foreach($programGroups as $pg)
                    <option value="{{ $pg->id }}">{{ $pg->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Select all --}}
        <div class="select-all-bar">
            <input type="checkbox" id="select-all-cb" onchange="toggleSelectAll(this)">
            <label for="select-all-cb">Chọn tất cả kết quả đang hiển thị</label>
            <span id="visible-count" style="margin-left:auto;"></span>
        </div>

        {{-- Subject list --}}
        <div class="subject-list" id="subject-list"></div>

        {{-- Footer --}}
        <div class="modal-foot">
            <span class="selected-count">Đã chọn: <strong id="sel-count">0</strong> môn</span>
            <div style="display:flex; gap:8px;">
                <button class="btn btn-secondary btn-sm" onclick="closeModal()">Hủy</button>
                <button class="btn btn-primary btn-sm" id="btn-submit-modal" onclick="submitAssign()">✅ Xong</button>
            </div>
        </div>
    </div>
</div>

{{-- Hidden assign form --}}
<form id="assign-form" method="POST" action="" style="display:none;">
    @csrf
    <input type="hidden" name="semester_id" id="assign-semester-id">
    <div id="assign-subjects-inputs"></div>
</form>

@push('scripts')
<script>
const ALL_SUBJECTS = {!! $subjectsJson !!};

const BASE_ASSIGN_URL = "{{ route('admin.curriculum.assign', $curriculumFramework) }}";
const AUTO_ASSIGN_URL = "{{ route('admin.curriculum.auto-assign', $curriculumFramework) }}";

let currentSemesterId = null;
let assignedIds       = [];
let selectedIds       = new Set();
let isAutoAssignMode  = false;

function openModal(semesterId, semesterLabel, alreadyAssigned) {
    isAutoAssignMode  = false;
    currentSemesterId = semesterId;
    assignedIds       = alreadyAssigned;
    selectedIds       = new Set();

    document.getElementById('modal-title').textContent = `➕ Thêm môn — ${semesterLabel}`;
    document.getElementById('modal-search-input').value = '';
    document.getElementById('modal-pg-filter').value = '';
    document.getElementById('select-all-cb').checked = false;
    renderList('', null);
    updateSelCount();
    document.getElementById('modal-overlay').classList.add('open');
    setTimeout(() => document.getElementById('modal-search-input').focus(), 80);
}

function openAutoAssignModal() {
    isAutoAssignMode  = true;
    currentSemesterId = null;
    assignedIds       = []; // Trong chế độ tự động, có thể chọn bất kỳ môn nào
    selectedIds       = new Set();

    document.getElementById('modal-title').textContent = `✨ Phân công tự động nhiều môn học`;
    document.getElementById('modal-search-input').value = '';
    document.getElementById('modal-pg-filter').value = '';
    document.getElementById('select-all-cb').checked = false;
    renderList('', null);
    updateSelCount();
    document.getElementById('modal-overlay').classList.add('open');
    setTimeout(() => document.getElementById('modal-search-input').focus(), 80);
}

function closeModal() {
    document.getElementById('modal-overlay').classList.remove('open');
}

function closeModalOnBackdrop(e) {
    if (e.target === document.getElementById('modal-overlay')) closeModal();
}

function filterSubjects() {
    const query = (document.getElementById('modal-search-input').value || '').toLowerCase().trim();
    const pgId  = document.getElementById('modal-pg-filter').value;
    renderList(query, pgId ? parseInt(pgId) : null);
    document.getElementById('select-all-cb').checked = false;
}

function renderSubjectItem(s, egId) {
    const isAssigned = assignedIds.includes(s.id);
    const isChecked  = selectedIds.has(s.id);
    const egAttr     = egId != null ? `data-eg-id="${egId}"` : '';
    const egClass    = egId != null ? ' eg-subject' : '';
    const egArg      = egId != null ? `, ${egId}` : '';
    return `
    <label class="subject-item${egClass}${isAssigned ? ' already-assigned' : ''}${isChecked ? ' selected' : ''}"
           data-id="${s.id}" ${egAttr} ${isAssigned ? 'title="Đã được phân công"' : ''}>
        <input type="checkbox"
               value="${s.id}"
               ${isChecked  ? 'checked' : ''}
               ${isAssigned ? 'disabled' : ''}
               onchange="toggleSubject(${s.id}, this${egArg})">
        <div style="flex:1;">
            ${s.code ? `<span class="subject-item-code">${s.code}</span>` : ''}
            <span class="subject-item-name">${s.name}</span>
            ${s.pg ? `<span style="font-size:10px; color:var(--muted); display:block; margin-top:1px;">📂 ${s.pg}</span>` : ''}
        </div>
        <div class="subject-item-meta">
            ${s.credits ? `<span class="badge badge-mint">${s.credits} TC</span>` : ''}
            ${isAssigned ? '<span class="badge badge-muted" style="margin-left:4px;">Đã có</span>' : ''}
        </div>
    </label>`;
}

function renderList(query, pgId) {
    const list = document.getElementById('subject-list');
    const filtered = ALL_SUBJECTS.filter(s => {
        const matchText = !query ||
            s.name.toLowerCase().includes(query) ||
            s.code.toLowerCase().includes(query) ||
            s.sg.toLowerCase().includes(query) ||
            (s.eg_name || '').toLowerCase().includes(query);
        const matchPg = !pgId || s.pg_id === pgId;
        return matchText && matchPg;
    });

    if (filtered.length === 0) {
        list.innerHTML = '<div class="no-results">Không tìm thấy môn học nào</div>';
        document.getElementById('visible-count').textContent = '';
        return;
    }

    document.getElementById('visible-count').textContent = filtered.length + ' môn';

    // Separate grouped (elective) vs standalone subjects
    const groups    = {};   // eg_id → { name, credits, subjects[] }
    const standalone = [];
    for (const s of filtered) {
        if (s.eg_id) {
            if (!groups[s.eg_id]) groups[s.eg_id] = { name: s.eg_name, credits: s.eg_credits, subjects: [] };
            groups[s.eg_id].subjects.push(s);
        } else {
            standalone.push(s);
        }
    }

    let html = standalone.map(s => renderSubjectItem(s, null)).join('');

    for (const [egId, group] of Object.entries(groups)) {
        const egIdNum = parseInt(egId);
        const ids     = group.subjects.map(s => s.id);
        const allAssigned = ids.every(id => assignedIds.includes(id));
        const allChecked  = ids.every(id => selectedIds.has(id));
        const totalCr = group.subjects.reduce((sum, s) => sum + (parseInt(s.credits) || 0), 0);

        html += `
        <div class="eg-group-header">
            <input type="checkbox"
                   id="eg-cb-${egIdNum}"
                   ${allChecked  ? 'checked' : ''}
                   ${allAssigned ? 'disabled' : ''}
                   style="width:16px;height:16px;accent-color:var(--ink);cursor:pointer;flex-shrink:0;"
                   onchange="toggleGroupSubjects(${egIdNum}, this)">
            <div style="flex:1;">
                <div style="font-weight:700;font-size:13px;color:var(--ink);">📚 ${group.name}</div>
                <div style="font-size:11px;color:var(--muted);margin-top:2px;">
                    Nhóm tự chọn · Cần ${group.credits} TC · ${group.subjects.length} môn
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                <span class="badge badge-mint">${totalCr} TC</span>
                ${allAssigned ? '<span class="badge badge-muted">Đã có</span>' : ''}
            </div>
        </div>`;
        html += group.subjects.map(s => renderSubjectItem(s, egIdNum)).join('');
    }

    list.innerHTML = html;

    // Set indeterminate state on group checkboxes
    for (const [egId, group] of Object.entries(groups)) {
        const cb = document.getElementById(`eg-cb-${egId}`);
        if (!cb) continue;
        const ids       = group.subjects.map(s => s.id);
        const allChecked = ids.every(id => selectedIds.has(id));
        const someChecked = ids.some(id => selectedIds.has(id));
        cb.indeterminate = someChecked && !allChecked;
    }
}

function toggleGroupSubjects(egId, cb) {
    document.querySelectorAll(`#subject-list .eg-subject[data-eg-id="${egId}"]`).forEach(item => {
        const input = item.querySelector('input[type=checkbox]');
        if (!input || input.disabled) return;
        const id = parseInt(input.value);
        if (cb.checked) { input.checked = true;  selectedIds.add(id);    item.classList.add('selected'); }
        else            { input.checked = false; selectedIds.delete(id); item.classList.remove('selected'); }
    });
    updateSelCount();
}

function toggleSubject(id, cb, egId) {
    if (cb.checked) { selectedIds.add(id);    cb.closest('.subject-item').classList.add('selected'); }
    else            { selectedIds.delete(id); cb.closest('.subject-item').classList.remove('selected'); }

    if (egId != null) {
        const inputs = Array.from(document.querySelectorAll(
            `#subject-list .eg-subject[data-eg-id="${egId}"] input[type=checkbox]:not(:disabled)`));
        const allChecked  = inputs.length > 0 && inputs.every(c => c.checked);
        const someChecked = inputs.some(c => c.checked);
        const groupCb = document.getElementById(`eg-cb-${egId}`);
        if (groupCb) { groupCb.checked = allChecked; groupCb.indeterminate = someChecked && !allChecked; }
    }
    updateSelCount();
}

function toggleSelectAll(cb) {
    const visibleCbs = document.querySelectorAll('#subject-list .subject-item:not(.already-assigned) input[type=checkbox]');
    visibleCbs.forEach(input => {
        const id = parseInt(input.value);
        if (cb.checked) { input.checked = true;  selectedIds.add(id);    input.closest('.subject-item').classList.add('selected'); }
        else            { input.checked = false; selectedIds.delete(id); input.closest('.subject-item').classList.remove('selected'); }
    });
    // Sync group header checkboxes
    document.querySelectorAll('[id^="eg-cb-"]').forEach(groupCb => {
        if (!groupCb.disabled) { groupCb.checked = cb.checked; groupCb.indeterminate = false; }
    });
    updateSelCount();
}

function updateSelCount() {
    document.getElementById('sel-count').textContent = selectedIds.size;
}

function submitAssign() {
    if (selectedIds.size === 0) {
        alert('Vui lòng chọn ít nhất 1 môn học.');
        return;
    }

    const form = document.getElementById('assign-form');
    if (isAutoAssignMode) {
        form.action = AUTO_ASSIGN_URL;
        document.getElementById('assign-semester-id').value = ''; // Không truyền học kỳ
        document.getElementById('assign-semester-id').disabled = true;
    } else {
        form.action = BASE_ASSIGN_URL;
        document.getElementById('assign-semester-id').value = currentSemesterId;
        document.getElementById('assign-semester-id').disabled = false;
    }

    const container = document.getElementById('assign-subjects-inputs');
    container.innerHTML = '';
    selectedIds.forEach(id => {
        const inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = 'subject_ids[]';
        inp.value = id;
        container.appendChild(inp);
    });

    form.submit();
}

// Đóng bằng Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
});
</script>
@endpush
@endsection
