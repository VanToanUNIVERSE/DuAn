@extends('admin.layouts.app')

@section('title', 'Phân công môn học — ' . $curriculumFramework->trainingProgram->program_name)
@section('page-title', 'Phân công môn học')
@section('breadcrumb')
 / <a href="{{ route('admin.curriculum.index') }}">Chương trình</a>
 / <span>{{ $curriculumFramework->trainingProgram->program_name }} ({{ $curriculumFramework->trainingProgram->academic_year }})</span>
@endsection

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
                <div style="font-size:15px; font-weight:700;">{{ $curriculumFramework->number_of_semesters }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--muted); text-transform:uppercase; font-weight:700;">Tổng tín chỉ</div>
                <div style="font-size:15px; font-weight:700;">{{ $curriculumFramework->total_credits }} TC</div>
            </div>
            <div style="margin-left:auto;">
                <a href="{{ route('admin.curriculum.index') }}" class="btn btn-secondary">← Quay lại</a>
            </div>
        </div>
    </div>
</div>

{{-- Form thêm môn vào học kỳ --}}
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <div class="card-title">➕ Phân công môn học vào học kỳ</div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.curriculum.assign', $curriculumFramework) }}"
              style="display:grid; grid-template-columns:1fr 1fr auto; gap:12px; align-items:end;">
            @csrf
            <div class="form-group" style="margin:0;">
                <label>Học kỳ</label>
                <select name="semester_id" required>
                    <option value="">— Chọn học kỳ —</option>
                    @foreach($semestersWithSubjects as $semester)
                        <option value="{{ $semester->id }}">Học kỳ {{ $semester->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label>Môn học</label>
                <select name="subject_id" required id="subject-select">
                    <option value="">— Tìm và chọn môn —</option>
                    @foreach($allSubjects as $subj)
                        <option value="{{ $subj->id }}"
                                data-code="{{ $subj->subject_code }}"
                                data-credits="{{ $subj->credits }}">
                            {{ $subj->subject_code ? "[{$subj->subject_code}] " : '' }}{{ $subj->name }}
                            {{ $subj->credits ? "({$subj->credits} TC)" : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">✅ Thêm</button>
        </form>
    </div>
</div>

{{-- Danh sách học kỳ với môn học --}}
<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); gap:20px;">
    @foreach($semestersWithSubjects as $semester)
    <div class="card">
        <div class="card-header" style="background: var(--surface-soft);">
            <div class="card-title">
                📅 Học kỳ {{ $semester->name }}
            </div>
            <span class="badge badge-{{ $semester->assignedSubjects->count() > 0 ? 'teal' : 'muted' }}">
                {{ $semester->assignedSubjects->count() }} môn
                @php $totalTc = $semester->assignedSubjects->sum(fn($cs) => $cs->subject->credits ?? 0); @endphp
                @if($totalTc > 0) · {{ $totalTc }} TC @endif
            </span>
        </div>

        @if($semester->assignedSubjects->isEmpty())
        <div style="padding:20px; text-align:center; color:var(--muted); font-size:13px;">
            Chưa có môn học nào
        </div>
        @else
        <div class="table-wrap">
            <table style="font-size:12px;">
                <thead>
                    <tr>
                        <th>Môn học</th>
                        <th width="50" style="text-align:center;">TC</th>
                        <th width="120">Nhóm</th>
                        <th width="44" style="text-align:center;">Xóa</th>
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
                                <span style="color:var(--error);">— Môn đã bị xóa —</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <span class="badge badge-mint">{{ $cs->subject->credits ?? '—' }}</span>
                        </td>
                        <td>
                            @if($cs->subject?->skillGroup)
                                <span style="font-size:10px; color:var(--muted);">{{ Str::limit($cs->subject->skillGroup->name, 20) }}</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <form method="POST"
                                  action="{{ route('admin.curriculum.remove', [$curriculumFramework, $cs->id]) }}"
                                  onsubmit="return confirm('Xóa môn này khỏi học kỳ?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Xóa">✕</button>
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

@push('scripts')
<script>
// Tìm kiếm nhanh trong danh sách môn học
const select = document.getElementById('subject-select');
if (select && window.TomSelect) {
    new TomSelect(select, { maxOptions: 300, searchField: ['text'] });
}
</script>
@endpush
@endsection
