@extends('admin.layouts.app')

@section('title', 'Thống kê đăng ký học phần')
@section('page-title', 'Thống kê đăng ký học phần')
@section('breadcrumb')
 / <span>Thống kê đăng ký học phần</span>
@endsection

@section('content')

{{-- ── Summary cards ─────────────────────────────────────────────────── --}}
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px;">
    <div class="card" style="padding:20px;">
        <div style="font-size:0.75rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Kế hoạch đang hoạt động</div>
        <div style="font-size:2rem;font-weight:800;color:var(--body-strong);margin-top:6px;">{{ number_format($totalActivePlans) }}</div>
    </div>
    <div class="card" style="padding:20px;">
        <div style="font-size:0.75rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Sinh viên có kế hoạch</div>
        <div style="font-size:2rem;font-weight:800;color:var(--body-strong);margin-top:6px;">{{ number_format($totalStudentsWithPlan) }}</div>
    </div>
    <div class="card" style="padding:20px;">
        <div style="font-size:0.75rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Học phần đang thống kê</div>
        <div style="font-size:2rem;font-weight:800;color:var(--body-strong);margin-top:6px;">{{ $rows->count() }}</div>
    </div>
</div>

{{-- ── Filter form ───────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title">🔍 Bộ lọc</div>
        @if($rows->count() > 0)
        <a href="{{ route('admin.enrollment-stats.export', request()->query()) }}"
           class="btn btn-secondary btn-sm" style="gap:6px;">
            ⬇ Xuất CSV
        </a>
        @endif
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.enrollment-stats.index') }}"
              style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
            <div class="form-group" style="flex:2; min-width:200px; margin:0;">
                <label>Chương trình đào tạo</label>
                <select name="program_id">
                    <option value="">— Tất cả —</option>
                    @foreach($programs as $p)
                    <option value="{{ $p->id }}" {{ $selectedProgramId == $p->id ? 'selected' : '' }}>
                        {{ $p->program_name }} ({{ $p->academic_year }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="flex:1; min-width:140px; margin:0;">
                <label>Học kỳ</label>
                <select name="semester">
                    <option value="">— Tất cả —</option>
                    @foreach($availableSemesters as $s)
                    <option value="{{ $s }}" {{ $selectedSemester == $s ? 'selected' : '' }}>Học kỳ {{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="flex:1; min-width:140px; margin:0;">
                <label>SV tối thiểu</label>
                <input type="number" name="min_count" value="{{ $minCount }}" min="1" placeholder="1">
            </div>
            <button type="submit" class="btn btn-primary" style="white-space:nowrap;">🔍 Lọc</button>
            <a href="{{ route('admin.enrollment-stats.index') }}" class="btn btn-secondary" style="white-space:nowrap;">↺ Xóa lọc</a>
        </form>
    </div>
</div>

{{-- ── Main content ──────────────────────────────────────────────────── --}}
@if($grouped->isEmpty())
<div class="card" style="padding:48px; text-align:center; color:var(--muted);">
    <div style="font-size:2.5rem; margin-bottom:12px;">📋</div>
    <div style="font-size:1rem; font-weight:600;">Chưa có dữ liệu kế hoạch học tập</div>
    <div style="font-size:0.85rem; margin-top:6px;">Sinh viên cần lập và lưu kế hoạch học tập để dữ liệu xuất hiện ở đây.</div>
</div>
@else

@foreach($grouped as $programId => $programData)
@php
    $programSemesters = $programData['semesters'];
    // Tìm max student_count để scale progress bar
    $maxCount = $programSemesters->flatten()->max('student_count') ?: 1;
@endphp

<div class="card" style="margin-bottom:24px;">
    <div class="card-header" style="background:var(--surface-dark); color:#e0e0d0;">
        <div>
            <div style="font-weight:700; font-size:1rem;">🎓 {{ $programData['program_name'] }}</div>
            <div style="font-size:0.78rem; opacity:0.7; margin-top:2px;">
                {{ $programData['academic_year'] }} · {{ $programData['program_type'] }}
            </div>
        </div>
        @php $totalSubjectRows = $programSemesters->flatten()->count(); @endphp
        <div style="text-align:right;">
            <div style="font-size:1.4rem; font-weight:800; color:var(--brand-mint);">{{ $totalSubjectRows }}</div>
            <div style="font-size:0.72rem; opacity:0.7;">học phần có đăng ký</div>
        </div>
    </div>

    @foreach($programSemesters as $semIdx => $subjects)
    @php
        $totalSV  = $subjects->sum('student_count');
        $mandatorySubjects = $subjects->where('is_elective', false);
        $electiveSubjects  = $subjects->where('is_elective', true);
    @endphp

    <div style="border-bottom:1px solid var(--hairline);">
        {{-- Semester header --}}
        <div style="display:flex; align-items:center; gap:12px; padding:12px 20px;
                    background:var(--surface-soft); border-bottom:1px solid var(--hairline);">
            <span style="font-weight:700; font-size:0.95rem;">📅 Học kỳ {{ $semIdx }}</span>
            <span class="badge badge-teal">{{ $subjects->count() }} học phần</span>
            @if($electiveSubjects->count() > 0)
            <span style="font-size:0.72rem; color:#2563eb;">
                <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#3b82f6;margin-right:3px;vertical-align:middle;"></span>
                {{ $electiveSubjects->count() }} tự chọn
            </span>
            @endif
            <span style="margin-left:auto; font-size:0.78rem; color:var(--muted);">
                Tổng lượt đăng ký: <strong style="color:var(--body-strong);">{{ number_format($totalSV) }}</strong>
            </span>
        </div>

        {{-- Subject table --}}
        <div class="table-wrap" style="margin:0;">
            <table style="font-size:12px;">
                <thead>
                    <tr>
                        <th style="width:90px;">Mã môn</th>
                        <th>Tên học phần</th>
                        <th style="width:45px; text-align:center;">TC</th>
                        <th style="width:80px; text-align:center;">Loại</th>
                        <th style="width:70px; text-align:center;">SV đăng ký</th>
                        <th style="min-width:160px;">Tỷ lệ nhu cầu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subjects->sortByDesc('student_count') as $row)
                    @php
                        $pct     = round(($row->student_count / $maxCount) * 100);
                        $barColor = $row->student_count >= ($maxCount * 0.7) ? '#10b981'
                                  : ($row->student_count >= ($maxCount * 0.4) ? '#f59e0b' : '#94a3b8');
                    @endphp
                    <tr>
                        <td>
                            <span class="badge badge-muted" style="font-size:11px;">{{ $row->subject_code }}</span>
                        </td>
                        <td style="font-weight:500; color:var(--body-strong);">{{ $row->subject_name }}</td>
                        <td style="text-align:center;">{{ $row->credits }}</td>
                        <td style="text-align:center;">
                            @if($row->is_elective)
                            <span style="font-size:0.68rem; background:#eff6ff; color:#1d4ed8; padding:2px 7px; border-radius:4px; font-weight:600; border:1px solid #bfdbfe;">Tự chọn</span>
                            @else
                            <span style="font-size:0.68rem; background:#f0fdf4; color:#15803d; padding:2px 7px; border-radius:4px; font-weight:600; border:1px solid #bbf7d0;">Bắt buộc</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <strong style="font-size:1rem; color:var(--body-strong);">{{ $row->student_count }}</strong>
                            <div style="font-size:0.65rem; color:var(--muted);">sinh viên</div>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="flex:1; height:8px; background:var(--hairline); border-radius:99px; overflow:hidden;">
                                    <div style="height:100%; width:{{ $pct }}%; background:{{ $barColor }}; border-radius:99px; transition:width .4s;"></div>
                                </div>
                                <span style="font-size:0.7rem; color:var(--muted); width:32px; text-align:right;">{{ $pct }}%</span>
                            </div>
                            @if($row->student_count >= ($maxCount * 0.7))
                            <div style="font-size:0.65rem; color:#10b981; margin-top:2px; font-weight:600;">↑ Nhu cầu cao</div>
                            @elseif($row->student_count <= 2)
                            <div style="font-size:0.65rem; color:#94a3b8; margin-top:2px;">Ít đăng ký</div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>
@endforeach

@endif

@endsection
