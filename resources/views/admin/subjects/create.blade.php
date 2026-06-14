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
                        <label>Mã môn học <span style="color:var(--error);">*</span></label>
                        <input type="text" name="subject_code" value="{{ old('subject_code') }}" placeholder="VD: IT001, MATH101" required>
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

                <div class="form-grid-2">
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

                <div class="form-grid-2" style="margin-top:16px; margin-bottom:16px;">
                    <div class="form-group">
                        <label>Môn tiên quyết</label>
                        <select name="prerequisites[]" multiple class="form-control" style="height:120px;">
                            @foreach($allSubjects as $sub)
                                <option value="{{ $sub->id }}" {{ in_array($sub->id, old('prerequisites', [])) ? 'selected' : '' }}>
                                    {{ $sub->subject_code }} - {{ $sub->name }}
                                </option>
                            @endforeach
                        </select>
                        <div style="font-size:11px; color:var(--muted); margin-top:4px;">Nhấn giữ Ctrl (hoặc Cmd) để chọn nhiều môn.</div>
                    </div>
                    <div class="form-group">
                        <label>Môn song hành</label>
                        <select name="corequisites[]" multiple class="form-control" style="height:120px;">
                            @foreach($allSubjects as $sub)
                                <option value="{{ $sub->id }}" {{ in_array($sub->id, old('corequisites', [])) ? 'selected' : '' }}>
                                    {{ $sub->subject_code }} - {{ $sub->name }}
                                </option>
                            @endforeach
                        </select>
                        <div style="font-size:11px; color:var(--muted); margin-top:4px;">Nhấn giữ Ctrl (hoặc Cmd) để chọn nhiều môn.</div>
                    </div>
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

@push('styles')
<style>
.vanilla-multi-select {
    position: relative;
    font-family: inherit;
}
.vms-header {
    min-height: 38px;
    padding: 4px 8px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
}
.vms-tag {
    background: var(--brand-teal);
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.vms-tag-remove {
    cursor: pointer;
    font-weight: bold;
}
.vms-tag-remove:hover { color: #ffe3e3; }
.vms-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 6px;
    margin-top: 4px;
    max-height: 250px;
    overflow-y: auto;
    z-index: 100;
    display: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.vanilla-multi-select.open .vms-dropdown {
    display: block;
}
.vms-search {
    padding: 8px;
    border-bottom: 1px solid var(--border);
    position: sticky;
    top: 0;
    background: #fff;
}
.vms-search input {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid var(--border);
    border-radius: 4px;
    outline: none;
}
.vms-options {
    list-style: none;
    padding: 0;
    margin: 0;
}
.vms-option {
    padding: 8px 12px;
    cursor: pointer;
    font-size: 13px;
    border-bottom: 1px solid #f9f9f9;
}
.vms-option:hover { background: #f8f9fa; }
.vms-option.selected {
    background: #e6fcf5;
    color: var(--brand-teal);
    font-weight: 600;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function initMultiSelect(selectElement) {
        selectElement.style.display = 'none';

        const wrapper = document.createElement('div');
        wrapper.className = 'vanilla-multi-select';
        selectElement.parentNode.insertBefore(wrapper, selectElement);
        wrapper.appendChild(selectElement);

        const header = document.createElement('div');
        header.className = 'vms-header';
        wrapper.appendChild(header);

        const dropdown = document.createElement('div');
        dropdown.className = 'vms-dropdown';
        wrapper.appendChild(dropdown);

        const searchWrap = document.createElement('div');
        searchWrap.className = 'vms-search';
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Tìm kiếm môn học...';
        searchWrap.appendChild(searchInput);
        dropdown.appendChild(searchWrap);

        const optionsList = document.createElement('ul');
        optionsList.className = 'vms-options';
        dropdown.appendChild(optionsList);

        const options = Array.from(selectElement.options);

        function renderHeader() {
            header.innerHTML = '';
            const selectedOptions = options.filter(o => o.selected);
            if (selectedOptions.length === 0) {
                header.innerHTML = '<span style="color:#aaa; font-size:14px;">— Nhấp để chọn môn —</span>';
            } else {
                selectedOptions.forEach(opt => {
                    const tag = document.createElement('div');
                    tag.className = 'vms-tag';
                    tag.innerHTML = `<span>${opt.text}</span><span class="vms-tag-remove" data-value="${opt.value}">×</span>`;
                    header.appendChild(tag);
                });
            }
        }

        function renderOptions(filter = '') {
            optionsList.innerHTML = '';
            options.forEach(opt => {
                if (opt.text.toLowerCase().includes(filter.toLowerCase())) {
                    const li = document.createElement('li');
                    li.className = 'vms-option' + (opt.selected ? ' selected' : '');
                    li.textContent = opt.text;
                    li.dataset.value = opt.value;
                    optionsList.appendChild(li);
                }
            });
        }

        header.addEventListener('click', (e) => {
            if (e.target.classList.contains('vms-tag-remove')) {
                const val = e.target.dataset.value;
                const opt = options.find(o => o.value === val);
                if (opt) {
                    opt.selected = false;
                    renderHeader();
                    renderOptions(searchInput.value);
                }
                return;
            }
            wrapper.classList.toggle('open');
            if (wrapper.classList.contains('open')) {
                searchInput.focus();
            }
        });

        searchInput.addEventListener('input', (e) => {
            renderOptions(e.target.value);
        });

        optionsList.addEventListener('click', (e) => {
            const li = e.target.closest('.vms-option');
            if (li) {
                const val = li.dataset.value;
                const opt = options.find(o => o.value === val);
                if (opt) {
                    opt.selected = !opt.selected;
                    renderHeader();
                    renderOptions(searchInput.value);
                }
            }
        });

        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                wrapper.classList.remove('open');
            }
        });

        renderHeader();
        renderOptions();
    }

    document.querySelectorAll('select[multiple]').forEach(select => {
        initMultiSelect(select);
    });
});
</script>
@endpush
