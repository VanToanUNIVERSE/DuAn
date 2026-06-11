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
                <div style="font-size:15px; font-weight:700;">{{ $curriculumFramework->total_credits }} TC</div>
            </div>
            <div style="margin-left:auto; display:flex; gap:12px;">
                <button class="btn btn-primary" style="background:var(--brand-teal); border-color:var(--brand-teal);" onclick="openAutoAssignModal()">✨ Phân công tự động</button>
                <a href="{{ route('admin.curriculum.index') }}" class="btn btn-secondary">← Quay lại</a>
            </div>
        </div>
    </div>
</div>

{{-- Semester cards grid --}}
<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap:20px;">
    @foreach($semestersWithSubjects as $semester)
    @php $assignedIds = $semester->assignedSubjects->pluck('subject.id')->filter()->values(); @endphp
    <div class="card">
        <div class="card-header" style="background: var(--surface-soft);">
            <div class="card-title">
                📅 Học kỳ {{ $semester->name }}
                <span class="badge badge-{{ $semester->assignedSubjects->count() > 0 ? 'teal' : 'muted' }}" style="margin-left:8px;">
                    {{ $semester->assignedSubjects->count() }} môn
                    @php $totalTc = $semester->assignedSubjects->sum(fn($cs) => $cs->subject->credits ?? 0); @endphp
                    @if($totalTc > 0) · {{ $totalTc }} TC @endif
                </span>
            </div>
            {{-- Nút + mở modal --}}
            <button class="btn-add-subject"
                    onclick="openModal({{ $semester->id }}, 'Học kỳ {{ $semester->name }}', {{ $assignedIds->toJson() }})"
                    title="Thêm môn học">
                ＋
            </button>
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

function renderList(query, pgId) {
    const list = document.getElementById('subject-list');
    const filtered = ALL_SUBJECTS.filter(s => {
        const matchText = !query ||
            s.name.toLowerCase().includes(query) ||
            s.code.toLowerCase().includes(query) ||
            s.sg.toLowerCase().includes(query);
        const matchPg = !pgId || s.pg_id === pgId;
        return matchText && matchPg;
    });

    if (filtered.length === 0) {
        list.innerHTML = '<div class="no-results">Không tìm thấy môn học nào</div>';
        document.getElementById('visible-count').textContent = '';
        return;
    }

    document.getElementById('visible-count').textContent = filtered.length + ' môn';

    list.innerHTML = filtered.map(s => {
        const isAssigned = assignedIds.includes(s.id);
        const isChecked  = selectedIds.has(s.id);
        return `
        <label class="subject-item${isAssigned ? ' already-assigned' : ''}${isChecked ? ' selected' : ''}"
               data-id="${s.id}" ${isAssigned ? 'title="Đã được phân công"' : ''}>
            <input type="checkbox"
                   value="${s.id}"
                   ${isChecked  ? 'checked' : ''}
                   ${isAssigned ? 'disabled' : ''}
                   onchange="toggleSubject(${s.id}, this)">
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
    }).join('');
}

function toggleSubject(id, cb) {
    if (cb.checked) {
        selectedIds.add(id);
        cb.closest('.subject-item').classList.add('selected');
    } else {
        selectedIds.delete(id);
        cb.closest('.subject-item').classList.remove('selected');
    }
    updateSelCount();
}

function toggleSelectAll(cb) {
    const visibleCbs = document.querySelectorAll('#subject-list .subject-item:not(.already-assigned) input[type=checkbox]');
    visibleCbs.forEach(input => {
        const id = parseInt(input.value);
        if (cb.checked) {
            input.checked = true;
            selectedIds.add(id);
            input.closest('.subject-item').classList.add('selected');
        } else {
            input.checked = false;
            selectedIds.delete(id);
            input.closest('.subject-item').classList.remove('selected');
        }
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
