<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gợi Ý Đăng Ký Môn Học - Hỗ Trợ Học Tập</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* CSS Reset & Variable Tokens */
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --panel-bg: rgba(30, 41, 59, 0.7);
            --panel-border: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.3);
            --secondary: #a855f7;
            --accent-success: #10b981;
            --accent-success-glow: rgba(16, 185, 129, 0.2);
            --accent-warning: #f59e0b;
            --radius-lg: 16px;
            --radius-md: 10px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 2rem 1rem;
            overflow-x: hidden;
            background-attachment: fixed;
        }

        h1, h2, h3, h4 {
            font-family: 'Outfit', sans-serif;
        }

        /* Container & Layout */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        header::after {
            content: '';
            position: absolute;
            top: -100px;
            left: 50%;
            transform: translateX(-50%);
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
            z-index: -1;
            pointer-events: none;
        }

        .logo-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #818cf8;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            animation: pulse 2s infinite;
        }

        header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(to right, #ffffff, #c7d2fe, #f472b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
        }

        header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Main Grid */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Cards & Glassmorphism */
        .glass-card {
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: var(--radius-lg);
            padding: 2rem;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            margin-bottom: 2rem;
            transition: var(--transition);
        }

        .glass-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 15px 35px rgba(99, 102, 241, 0.05);
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding-bottom: 1rem;
            color: #fff;
        }

        .card-title svg {
            color: var(--primary);
            width: 24px;
            height: 24px;
        }

        /* Dropdowns & Forms */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .input-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .form-select {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md);
            color: #fff;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            font-weight: 500;
            outline: none;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
        }

        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px var(--primary-glow);
        }

        /* Checklist Subjects */
        .semester-group {
            margin-bottom: 1.5rem;
        }

        .semester-header {
            font-size: 1rem;
            font-weight: 700;
            color: #818cf8;
            margin-bottom: 0.75rem;
            background: rgba(99, 102, 241, 0.05);
            padding: 0.4rem 0.8rem;
            border-radius: var(--radius-md);
            display: inline-block;
        }

        .subjects-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        @media (max-width: 640px) {
            .subjects-list {
                grid-template-columns: 1fr;
            }
        }

        .subject-checkbox-card {
            position: relative;
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            user-select: none;
            transition: var(--transition);
        }

        .subject-checkbox-card:hover {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .subject-checkbox-card input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: transparent;
            cursor: pointer;
            position: relative;
            transition: var(--transition);
            flex-shrink: 0;
        }

        .subject-checkbox-card input[type="checkbox"]:checked {
            background: var(--accent-success);
            border-color: var(--accent-success);
        }

        .subject-checkbox-card input[type="checkbox"]:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 900;
        }

        .subject-checkbox-card.checked {
            border-color: var(--accent-success);
            background: rgba(16, 185, 129, 0.08);
            box-shadow: 0 0 10px var(--accent-success-glow);
        }

        .subject-info {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .subject-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: #fff;
        }

        .subject-meta {
            font-size: 0.75rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .subject-badge {
            background: rgba(255, 255, 255, 0.08);
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
            font-size: 0.7rem;
        }

        /* Right Column Results */
        .results-container {
            position: sticky;
            top: 2rem;
        }

        .loader {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 0;
            gap: 1.5rem;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s linear infinite;
        }

        .suggestions-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .suggestion-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
            animation: fadeIn 0.4s ease-out;
        }

        .suggestion-card:hover {
            transform: translateX(5px);
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: -4px 0 0 var(--primary);
        }

        .suggestion-details {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .suggestion-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
        }

        .suggestion-tags {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .tag {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
            border-radius: 50px;
            text-transform: uppercase;
        }

        .tag-credits {
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .tag-type {
            background: rgba(168, 85, 247, 0.15);
            color: #d8b4fe;
            border: 1px solid rgba(168, 85, 247, 0.3);
        }

        .tag-group {
            background: rgba(245, 158, 11, 0.1);
            color: #fde047;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .suggestion-right {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.3rem;
            flex-shrink: 0;
        }

        .semester-badge {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff;
            padding: 0.35rem 0.85rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        }

        .distance-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .empty-state svg {
            width: 60px;
            height: 60px;
            color: rgba(255, 255, 255, 0.1);
        }

        .empty-state h3 {
            color: #fff;
            font-size: 1.2rem;
            font-weight: 600;
        }

        /* Animations */
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div class="logo-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5c0 .538-.012 1.05-.034 1.536a3 3 0 1 1-1.133 5.89c-.011.121-.011.234-.011.33v3.244c0 .852-.149 1.658-.439 2.4H3.083c-.29-.742-.439-1.548-.439-2.4V7.926c0-.096 0-.21-.012-.33a3 3 0 1 1-1.132-5.89A35 35 0 0 1 2.5.5zm0 1.25C2.41 2.347 2.33 3.012 2.3 3.652a2 2 0 1 0 1.95 0c-.03-.64-.11-1.305-.182-1.902h-1.57zm11 0h-1.57c.072.597.152 1.262.182 1.902A2 2 0 1 0 13.7 3.652c-.03-.64-.11-1.305-.2-2.402z"/>
            </svg>
            Smart Planner
        </div>
        <h1>Gợi Ý Học Tập Thông Minh</h1>
        <p>Chọn chương trình, học kỳ mong muốn và tích chọn các môn học bạn đã thi đỗ để nhận ngay lộ trình đề xuất tối ưu nhất thời gian thực.</p>
    </header>

    <div class="main-grid">
        <!-- Cột Bên Trái: Cấu Hình và Bộ Chọn Môn Đã Đỗ -->
        <div>
            <!-- Card 1: Chọn Khung Chương Trình -->
            <div class="glass-card">
                <h2 class="card-title">
                    <!-- Icon: Adjustments -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                    Cấu Hình Chương Trình
                </h2>
                <div class="form-grid">
                    <!-- Niên khóa -->
                    <div class="input-group">
                        <label for="academic_year">Niên khóa</label>
                        <select id="academic_year" class="form-select">
                            @foreach($academicYears as $year)
                                <option value="{{ $year }}" {{ $year == '2022-2026' ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Loại chương trình -->
                    <div class="input-group">
                        <label for="program_type">Hệ đào tạo</label>
                        <select id="program_type" class="form-select">
                            @foreach($programTypes as $type)
                                <option value="{{ $type }}" {{ $type == 'Chính quy' ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Học kỳ mới -->
                    <div class="input-group">
                        <label for="target_semester">Học kỳ mong muốn</label>
                        <select id="target_semester" class="form-select">
                            @for($i = 1; $i <= 8; $i++)
                                <option value="{{ $i }}" {{ $i == 3 ? 'selected' : '' }}>Học kỳ {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>

            <!-- Card 2: Checklist Các Môn Đã Hoàn Thành -->
            <div class="glass-card">
                <h2 class="card-title">
                    <!-- Icon: Badge Check -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                    </svg>
                    Tích Chọn Môn Học Đã Đỗ (Passed)
                </h2>

                <div id="checklist-container">
                    @foreach($subjects as $semName => $semSubjects)
                        <div class="semester-group">
                            <span class="semester-header">Học kỳ chuẩn {{ $semName }}</span>
                            <div class="subjects-list">
                                @foreach($semSubjects as $sub)
                                    <label class="subject-checkbox-card" id="lbl-sub-{{ $sub->id }}">
                                        <input type="checkbox" class="passed-subject-checkbox" value="{{ $sub->id }}" onchange="toggleSubjectCard({{ $sub->id }}, this)">
                                        <div class="subject-info">
                                            <span class="subject-name">{{ $sub->name }}</span>
                                            <div class="subject-meta">
                                                <span class="subject-badge">{{ $sub->credits }} tín</span>
                                                <span class="subject-badge">{{ $sub->subjectType?->name }}</span>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Cột Bên Phải: Danh Sách Môn Đề Xuất -->
        <div class="results-container">
            <div class="glass-card">
                <h2 class="card-title">
                    <!-- Icon: Academic Cap -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.228-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84a50.53 50.53 0 0 0-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.512A5.985 5.985 0 0 0 6 18v-3m12 3a5.985 5.985 0 0 0 1.007-3.045m-4.257-2.625A55.385 55.385 0 0 1 12 8.443M12 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                    </svg>
                    Môn Học Đề Xuất Học Kỳ Mới
                </h2>

                <!-- Loader -->
                <div class="loader" id="loader">
                    <div class="spinner"></div>
                    <p style="color: var(--text-secondary); font-size: 0.95rem;">Hệ thống đang phân tích và lập lộ trình...</p>
                </div>

                <!-- Suggested List -->
                <div id="suggestions-list" class="suggestions-grid">
                    <!-- Dữ liệu render động qua JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Hàm toggle hiệu ứng visual khi check checkbox
    function toggleSubjectCard(id, checkbox) {
        const card = document.getElementById(`lbl-sub-${id}`);
        if (checkbox.checked) {
            card.classList.add('checked');
        } else {
            card.classList.remove('checked');
        }
        // Gọi lại đề xuất tự động
        fetchSuggestions();
    }

    // Hàm gọi API lấy dữ liệu đề xuất
    async function fetchSuggestions() {
        const academicYear = document.getElementById('academic_year').value;
        const programType = document.getElementById('program_type').value;
        const semester = document.getElementById('target_semester').value;
        
        // Lấy tất cả check_box được tích chọn
        const checkboxes = document.querySelectorAll('.passed-subject-checkbox:checked');
        const passedSubjects = Array.from(checkboxes).map(cb => cb.value).join(',');

        // Hiển thị Loader
        const loader = document.getElementById('loader');
        const suggestionsContainer = document.getElementById('suggestions-list');
        loader.style.display = 'flex';
        suggestionsContainer.style.opacity = '0.3';

        try {
            // Gọi api suggestions
            const url = `/api/suggestions?academic_year=${academicYear}&program_type=${programType}&passed_subjects=${passedSubjects}&semester=${semester}`;
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error('Không thể tải dữ liệu đề xuất');
            }

            const data = await response.json();
            
            // Render dữ liệu gợi ý ra màn hình
            renderSuggestions(data, semester);
        } catch (error) {
            console.error('Lỗi khi tải đề xuất:', error);
            suggestionsContainer.innerHTML = `
                <div class="empty-state">
                    <p style="color: #ef4444; font-weight: 600;">⚠️ Đã có lỗi xảy ra khi phân tích dữ liệu.</p>
                </div>
            `;
        } finally {
            loader.style.display = 'none';
            suggestionsContainer.style.opacity = '1';
        }
    }

    // Hàm Render danh sách kết quả đề xuất
    function renderSuggestions(subjects, targetSemester) {
        const container = document.getElementById('suggestions-list');
        
        if (subjects.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <h3>Không có môn học đề xuất nào!</h3>
                    <p>Hãy thử đổi niên khóa, loại chương trình hoặc học kỳ mong muốn phù hợp hơn.</p>
                </div>
            `;
            return;
        }

        let html = '';
        subjects.forEach(subject => {
            const subjectSemester = parseInt(subject.semester?.name || 1);
            const targetSemInt = parseInt(targetSemester);
            
            // Tính toán nhãn độ lệch học kỳ
            let distanceLabel = '';
            if (subjectSemester === targetSemInt) {
                distanceLabel = '<span style="color: var(--accent-success); font-weight: 600;">Đúng tiến độ 🎯</span>';
            } else if (subjectSemester < targetSemInt) {
                distanceLabel = `<span style="color: var(--accent-warning); font-weight: 600;">Học bù (Chậm ${targetSemInt - subjectSemester} kỳ) ⏳</span>`;
            } else {
                distanceLabel = `<span style="color: #a5b4fc; font-weight: 600;">Học vượt (Nhanh ${subjectSemester - targetSemInt} kỳ) ⚡</span>`;
            }

            html += `
                <div class="suggestion-card">
                    <div class="suggestion-details">
                        <span class="suggestion-title">${subject.name}</span>
                        <div class="suggestion-tags">
                            <span class="tag tag-credits">${subject.credits} tín chỉ</span>
                            <span class="tag tag-type">${subject.subject_type?.name || 'Môn học'}</span>
                            <span class="tag tag-group">${subject.subject_group?.name || 'Nhóm'}</span>
                        </div>
                    </div>
                    <div class="suggestion-right">
                        <span class="semester-badge">Học kỳ chuẩn ${subject.semester?.name || '1'}</span>
                        <span class="distance-label">${distanceLabel}</span>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // Lắng nghe sự thay đổi các dropdown cấu hình để reload đề xuất
    document.getElementById('academic_year').addEventListener('change', fetchSuggestions);
    document.getElementById('program_type').addEventListener('change', fetchSuggestions);
    document.getElementById('target_semester').addEventListener('change', fetchSuggestions);

    // Tự động tải danh sách đề xuất lần đầu tiên khi tải trang
    document.addEventListener('DOMContentLoaded', fetchSuggestions);
</script>

</body>
</html>
