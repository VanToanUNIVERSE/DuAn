@extends('admin.layouts.app')

@section('title', 'Import môn học')
@section('page-title', 'Import môn học từ Excel')
@section('breadcrumb')
 / <a href="{{ route('admin.subjects.index') }}">Môn học</a> / <span>Import</span>
@endsection

@section('content')
<div style="max-width:800px;">

    {{-- Download mẫu --}}
    <div class="card" style="border-left: 4px solid var(--brand-ochre);">
        <div class="card-header" style="background: #fffbeb;">
            <div class="card-title">📥 Tải file mẫu Excel</div>
        </div>
        <div class="card-body" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div style="font-size:13px; color:var(--body);">
                Tải về file mẫu <code>.xlsx</code> đã có sẵn header và dữ liệu ví dụ.<br>
                <span style="color:var(--muted); font-size:12px;">Điền dữ liệu vào file mẫu rồi upload lên bên dưới.</span>
            </div>
            <a href="{{ route('admin.subjects.template') }}" class="btn btn-primary" style="white-space:nowrap;">
                ⬇️ Tải file mẫu (.xlsx)
            </a>
        </div>
    </div>

    {{-- Upload Form --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">📤 Upload file Excel / CSV</div>
            <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary btn-sm">← Quay lại</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.subjects.import') }}" enctype="multipart/form-data" id="import-form">
                @csrf

                <div class="form-group">
                    <label>Chọn file (.xlsx, .xls, .csv) — tối đa 10MB</label>
                    <div id="drop-zone" style="border: 2px dashed var(--hairline); padding: 30px; text-align:center; cursor:pointer; transition: all 0.2s; background: var(--surface-soft);">
                        <div style="font-size:32px; margin-bottom:8px;">📂</div>
                        <div style="font-size:14px; font-weight:600; color:var(--body-strong);">Kéo thả file vào đây hoặc click để chọn</div>
                        <div style="font-size:12px; color:var(--muted); margin-top:4px;">Hỗ trợ: .xlsx, .xls, .csv</div>
                        <input type="file" name="file" id="file-input" accept=".xlsx,.xls,.csv" required
                               style="display:none;">
                    </div>
                    <div id="file-preview" style="display:none; margin-top:10px; padding:10px 14px; background:var(--surface-strong); border:2px solid var(--hairline); display:none; align-items:center; gap:10px;">
                        <span style="font-size:20px;">📄</span>
                        <div>
                            <div id="file-name" style="font-weight:600; font-size:13px;"></div>
                            <div id="file-size" style="font-size:11px; color:var(--muted);"></div>
                        </div>
                        <button type="button" onclick="clearFile()" style="margin-left:auto; background:none; border:none; cursor:pointer; font-size:16px; color:var(--muted);">✕</button>
                    </div>
                    @error('file')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-primary" id="submit-btn">📤 Bắt đầu Import</button>
            </form>
        </div>
    </div>

    {{-- File Format Guide --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">📋 Định dạng cột trong file Excel</div>
        </div>
        <div class="card-body">
            <p style="font-size:13px; color:var(--body); margin-bottom:16px;">
                Dòng đầu tiên phải là <strong>tiêu đề cột</strong> (header). Tên cột phải đúng như bảng bên dưới:
            </p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th width="40" style="text-align:center;">Cột</th>
                            <th>Tên cột (header)</th>
                            <th>Ý nghĩa</th>
                            <th width="90" style="text-align:center;">Bắt buộc?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background:#fffbeb;">
                            <td style="text-align:center; font-weight:700;">A</td>
                            <td><code style="background:#ffe082; padding:2px 6px; font-weight:700;">subject_code</code></td>
                            <td><strong>Mã môn học</strong> — dùng làm khoá nhận diện (upsert). Nếu mã đã tồn tại sẽ cập nhật, nếu chưa sẽ tạo mới.</td>
                            <td style="text-align:center;"><span class="badge badge-coral">Có</span></td>
                        </tr>
                        <tr>
                            <td style="text-align:center; color:var(--muted);">B</td>
                            <td><code style="background:var(--surface-strong); padding:2px 6px;">subjects</code></td>
                            <td><strong>Tên môn học</strong></td>
                            <td style="text-align:center;"><span class="badge badge-coral">Có</span></td>
                        </tr>
                        <tr>
                            <td style="text-align:center; color:var(--muted);">C</td>
                            <td><code style="background:var(--surface-strong); padding:2px 6px;">credits</code></td>
                            <td>Số tín chỉ (số nguyên)</td>
                            <td style="text-align:center;"><span class="badge badge-muted">Không</span></td>
                        </tr>
                        <tr>
                            <td style="text-align:center; color:var(--muted);">D</td>
                            <td><code style="background:var(--surface-strong); padding:2px 6px;">program_groups</code></td>
                            <td>Tên Program Group (Đại cương, Cơ sở ngành…)</td>
                            <td style="text-align:center;"><span class="badge badge-muted">Không</span></td>
                        </tr>
                        <tr>
                            <td style="text-align:center; color:var(--muted);">E</td>
                            <td><code style="background:var(--surface-strong); padding:2px 6px;">skill_groups</code></td>
                            <td>Tên Skill Group (Lập trình, Toán – Khoa học…)</td>
                            <td style="text-align:center;"><span class="badge badge-muted">Không</span></td>
                        </tr>
                        <tr style="background:#fffff3;">
                            <td style="text-align:center; color:var(--muted);">F</td>
                            <td><code style="background:#fff3cd; padding:2px 6px;">prerequisite</code></td>
                            <td>Mã môn tiên quyết (phải là mã môn đã có trong file)</td>
                            <td style="text-align:center;"><span class="badge badge-muted">Không</span></td>
                        </tr>
                        <tr style="background:#f0fff4;">
                            <td style="text-align:center; color:var(--muted);">G</td>
                            <td><code style="background:#d1f5e0; padding:2px 6px;">corequisite</code></td>
                            <td>Mã môn song hành (học đồng thời)</td>
                            <td style="text-align:center;"><span class="badge badge-muted">Không</span></td>
                        </tr>
                        <tr>
                            <td style="text-align:center; color:var(--muted);">H</td>
                            <td><code style="background:var(--surface-strong); padding:2px 6px;">requirement_type</code></td>
                            <td>Điều kiện đăng ký: <code>none</code> / <code>completed_basic</code> / <code>completed_major</code> / <code>completed_all</code> / <code>min_credits</code></td>
                            <td style="text-align:center;"><span class="badge badge-muted">Không</span></td>
                        </tr>
                        <tr style="background:#eff6ff;">
                            <td style="text-align:center; font-weight:700; color:#1d4ed8;">I</td>
                            <td><code style="background:#dbeafe; padding:2px 6px; font-weight:700; color:#1d4ed8;">is_elective</code></td>
                            <td>Loại môn học: <code>required</code> (bắt buộc) hoặc <code>elective</code> (tự chọn)</td>
                            <td style="text-align:center;"><span class="badge badge-muted">Không</span></td>
                        </tr>
                        <tr style="background:#eff6ff;">
                            <td style="text-align:center; color:#1d4ed8;">J</td>
                            <td><code style="background:#dbeafe; padding:2px 6px; color:#1d4ed8;">elective_group</code></td>
                            <td>Tên nhóm tự chọn (ví dụ: <code>Web Electives</code>). Chỉ điền khi <code>is_elective = elective</code>. Các môn cùng tên nhóm sẽ được gộp vào một nhóm.</td>
                            <td style="text-align:center;"><span class="badge badge-muted">Không</span></td>
                        </tr>
                        <tr style="background:#eff6ff;">
                            <td style="text-align:center; color:#1d4ed8;">K</td>
                            <td><code style="background:#dbeafe; padding:2px 6px; color:#1d4ed8;">required_credits</code></td>
                            <td>Số TC cần tích lũy để thỏa nhóm tự chọn (ví dụ: <code>4</code>). Chỉ cần điền trên 1 dòng của nhóm, các dòng còn lại để trống.</td>
                            <td style="text-align:center;"><span class="badge badge-muted">Không</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:16px; padding:12px 16px; background:var(--surface-soft); border:2px solid var(--hairline); font-size:13px; line-height:1.8;">
                <strong>💡 Lưu ý quan trọng:</strong>
                <ul style="margin: 6px 0 0 18px;">
                    <li>Cột <code>subject_code</code> là <strong>bắt buộc</strong> và là khoá nhận diện: nếu mã đã tồn tại → <strong>cập nhật</strong>, chưa có → <strong>tạo mới</strong>.</li>
                    <li>Dòng thiếu <code>subject_code</code> sẽ bị <strong>bỏ qua</strong> và ghi vào danh sách lỗi.</li>
                    <li>Cột <code>prerequisite</code> và <code>corequisite</code> ghi <strong>mã môn học</strong> (subject_code, không phải tên).</li>
                    <li>Program Group / Skill Group chưa có sẽ được <strong>tạo tự động</strong>.</li>
                    <li>File CSV phải dùng <strong>encoding UTF-8</strong>.</li>
                </ul>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
const dropZone  = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');
const preview   = document.getElementById('file-preview');
const fileName  = document.getElementById('file-name');
const fileSize  = document.getElementById('file-size');

dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', e => {
    e.preventDefault();
    dropZone.style.borderColor = 'var(--ink)';
    dropZone.style.background  = 'var(--surface-strong)';
});
dropZone.addEventListener('dragleave', () => {
    dropZone.style.borderColor = 'var(--hairline)';
    dropZone.style.background  = 'var(--surface-soft)';
});
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.style.borderColor = 'var(--hairline)';
    dropZone.style.background  = 'var(--surface-soft)';
    const files = e.dataTransfer.files;
    if (files.length) showFile(files[0]);
});

fileInput.addEventListener('change', () => {
    if (fileInput.files.length) showFile(fileInput.files[0]);
});

function showFile(file) {
    fileName.textContent = file.name;
    fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
    preview.style.display = 'flex';
    dropZone.style.display = 'none';

    // Gắn file vào input
    const dt = new DataTransfer();
    dt.items.add(file);
    fileInput.files = dt.files;
}

function clearFile() {
    fileInput.value = '';
    preview.style.display = 'none';
    dropZone.style.display = 'block';
}

document.getElementById('import-form').addEventListener('submit', function() {
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.textContent = '⏳ Đang xử lý...';
});
</script>
@endpush
@endsection
