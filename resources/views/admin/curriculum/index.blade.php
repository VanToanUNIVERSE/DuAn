@extends('admin.layouts.app')

@section('title', 'Phân công môn học')
@section('page-title', 'Phân công môn học theo chương trình')
@section('breadcrumb')
 / <span>Phân công môn học</span>
@endsection

@section('content')

<div class="card">
    <div class="card-header">
        <div class="card-title">🎓 Chọn chương trình đào tạo</div>
    </div>
    <div class="card-body" style="padding-bottom:0;">
        <p style="font-size:13px; color:var(--muted); margin-bottom:20px;">
            Chọn một chương trình đào tạo để xem và phân công môn học vào từng học kỳ.
        </p>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tên chương trình</th>
                    <th width="100" style="text-align:center;">Mã CT</th>
                    <th width="100" style="text-align:center;">Niên khóa</th>
                    <th width="90" style="text-align:center;">Số HK</th>
                    <th width="90" style="text-align:center;">Tổng TC</th>
                    <th width="90" style="text-align:center;">Số môn</th>
                    <th width="110" style="text-align:center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($trainingPrograms as $program)
                    @foreach($program->curriculumFrameworks as $fw)
                    <tr>
                        @if($loop->first)
                        <td rowspan="{{ $program->curriculumFrameworks->count() }}" style="border-right:2px solid var(--hairline);">
                            <div style="font-weight:700; font-size:14px;">{{ $program->program_name }}</div>
                            <div style="font-size:11px; color:var(--muted); margin-top:2px;">{{ $program->education_level }} · {{ $program->program_type }}</div>
                        </td>
                        @endif
                        <td style="text-align:center;"><code style="font-size:11px;">{{ $program->program_code }}</code></td>
                        <td style="text-align:center;"><span class="badge badge-muted">{{ $program->academic_year }}</span></td>
                        <td style="text-align:center;">{{ $fw->number_of_semesters }} HK</td>
                        <td style="text-align:center;">{{ $fw->total_credits }} TC</td>
                        <td style="text-align:center;">
                            @php $subjectCount = \App\Models\CurriculumSubject::where('curriculum_framework_id', $fw->id)->count(); @endphp
                            <span class="badge badge-{{ $subjectCount > 0 ? 'teal' : 'muted' }}">{{ $subjectCount }} môn</span>
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('admin.curriculum.show', $fw) }}" class="btn btn-primary btn-sm">
                                📋 Phân công
                            </a>
                        </td>
                    </tr>
                    @endforeach
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:30px; color:var(--muted);">
                        Chưa có chương trình đào tạo nào
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
