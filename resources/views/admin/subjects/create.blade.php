@extends('admin.layouts.app')

@section('title', 'Thêm môn học')
@section('page-title', 'Thêm môn học mới')
@section('breadcrumb')
 / <a href="{{ route('admin.subjects.index') }}">Môn học</a> / <span>Thêm mới</span>
@endsection

@section('content')
<div style="max-width:800px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">📝 Thông tin môn học</div>
            <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary btn-sm">← Quay lại</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.subjects.store') }}">
                @csrf

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Mã môn học</label>
                        <input type="text" name="subject_code" value="{{ old('subject_code') }}" placeholder="VD: IT001, MATH101">
                        @error('subject_code')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Số tín chỉ</label>
                        <input type="number" name="credits" value="{{ old('credits') }}" min="1" max="20" placeholder="VD: 3">
                        @error('credits')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Tên môn học <span style="color:var(--error);">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="VD: Lập trình hướng đối tượng" required>
                    @error('name')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-grid-3">
                    <div class="form-group">
                        <label>Loại môn</label>
                        <select name="subject_type_id">
                            <option value="">— Chọn loại môn —</option>
                            @foreach($subjectTypes as $st)
                                <option value="{{ $st->id }}" {{ old('subject_type_id') == $st->id ? 'selected' : '' }}>
                                    {{ $st->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subject_type_id')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Skill Group</label>
                        <select name="skill_group_id">
                            <option value="">— Chọn nhóm kỹ năng —</option>
                            @foreach($skillGroups as $sg)
                                <option value="{{ $sg->id }}" {{ old('skill_group_id') == $sg->id ? 'selected' : '' }}>
                                    {{ $sg->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('skill_group_id')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Program Group</label>
                        <select name="program_group_id">
                            <option value="">— Chọn nhóm chương trình —</option>
                            @foreach($programGroups as $pg)
                                <option value="{{ $pg->id }}" {{ old('program_group_id') == $pg->id ? 'selected' : '' }}>
                                    {{ $pg->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('program_group_id')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                </div>


                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="note" placeholder="Ghi chú thêm về môn học...">{{ old('note') }}</textarea>
                    @error('note')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Yêu cầu điều kiện (Requirement Type)</label>
                    <select name="requirement_type">
                        @foreach(App\Models\Subject::REQUIREMENT_TYPES as $value => $label)
                            <option value="{{ $value }}" {{ old('requirement_type', 'none') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <div style="font-size:11px; color:var(--muted); margin-top:4px;">
                        Điều kiện sinh viên cần đáp ứng trước khi đăng ký môn này.
                    </div>
                    @error('requirement_type')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="submit" class="btn btn-primary">💾 Lưu môn học</button>
                    <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
