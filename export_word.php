<?php

use Illuminate\Support\Facades\DB;

$database = config('database.connections.mysql.database');
$tables = DB::select("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ?", [$database]);

$html = '
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="utf-8">
<title>Từ điển dữ liệu (Data Dictionary)</title>
<style>
    body { font-family: "Times New Roman", Times, serif; font-size: 12pt; }
    h3 { font-family: "Times New Roman", Times, serif; font-size: 14pt; font-weight: bold; margin-top: 20px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid black; padding: 5px; text-align: left; vertical-align: top; }
    th { font-weight: bold; background-color: #f2f2f2; }
</style>
</head>
<body>
<h1>Từ Điển Dữ Liệu</h1>
';

foreach ($tables as $t) {
    $table = $t->TABLE_NAME;
    if (in_array($table, ['migrations', 'failed_jobs', 'password_reset_tokens', 'personal_access_tokens'])) continue;

    $html .= "<h3>Bảng: $table</h3>\n";
    $html .= "<table>\n";
    $html .= "<tr><th>STT</th><th>Thuộc tính</th><th>Kiểu dữ liệu</th><th>Ràng buộc</th><th>Diễn giải</th></tr>\n";
    
    $columns = DB::select("
        SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT 
        FROM information_schema.columns 
        WHERE table_schema = ? AND table_name = ?
        ORDER BY ORDINAL_POSITION
    ", [$database, $table]);
    
    $stt = 1;
    foreach ($columns as $column) {
        $name = $column->COLUMN_NAME;
        $type = $column->COLUMN_TYPE;
        $isNullable = $column->IS_NULLABLE === 'YES';
        $isPk = $column->COLUMN_KEY === 'PRI';
        $isUnique = $column->COLUMN_KEY === 'UNI';
        $default = $column->COLUMN_DEFAULT;
        $extra = $column->EXTRA;
        $comment = $column->COLUMN_COMMENT;
        
        $constraints = [];
        if ($isPk) {
            $constraints[] = "PK";
        }
        if ($isUnique) {
            $constraints[] = "Unique";
        }
        if ($isNullable) {
            $constraints[] = "Nullable";
        } else if (!$isPk) {
            $constraints[] = "Not null";
        }
        if ($default !== null) {
            $constraints[] = "Default $default";
        }
        
        $constraintStr = empty($constraints) ? "" : implode(", ", $constraints);
        
        // Diễn giải (try to translate or infer based on name)
        $description = $comment;
        if (empty($description)) {
            if ($isPk) $description = "Khóa chính";
            else if ($table === 'users') {
                $userMap = [
                    'id' => 'Mã định danh người dùng',
                    'username' => 'Tên đăng nhập',
                    'email' => 'Email',
                    'password' => 'Mật khẩu đã mã hóa',
                    'is_admin' => 'Xác định tài khoản admin',
                    'fullName' => 'Họ tên người dùng',
                    'student_code' => 'Mã số sinh viên',
                    'pref_academic_year' => 'Niên khóa',
                    'pref_program_type' => 'Hệ đào tạo',
                    'pref_current_semester' => 'Học kỳ hiện tại',
                    'pref_target_years' => 'Mục tiêu tốt nghiệp',
                    'pref_current_courses' => 'Danh sách môn đang học',
                    'remember_token' => 'Token ghi nhớ đăng nhập',
                    'created_at' => 'Thời gian tạo',
                    'updated_at' => 'Thời gian cập nhật'
                ];
                if (isset($userMap[$name])) $description = $userMap[$name];
            }
            else if (str_ends_with($name, '_id')) {
                $refTable = str_replace('_id', '', $name);
                if ($refTable == 'user') $refTable = 'người dùng';
                else if ($refTable == 'subject') $refTable = 'môn học';
                else if ($refTable == 'study_plan') $refTable = 'kế hoạch học tập';
                else if ($refTable == 'semester') $refTable = 'học kỳ';
                $description = "Khóa ngoại tham chiếu bảng $refTable";
            }
            else if ($name === 'created_at') $description = "Thời gian tạo";
            else if ($name === 'updated_at') $description = "Thời gian cập nhật";
            else if ($name === 'deleted_at') $description = "Thời gian xóa (Soft delete)";
            else if (strpos($name, 'is_') === 0) $description = "Trạng thái " . str_replace('is_', '', $name);
        }
        
        $html .= "<tr><td>$stt</td><td>$name</td><td>$type</td><td>$constraintStr</td><td>$description</td></tr>\n";
        $stt++;
    }
    $html .= "</table>\n";
}

$html .= "</body></html>";

file_put_contents('e:/LuanVan/DuAn/public/DataDictionary.doc', $html);
echo "Word document generated!\n";
