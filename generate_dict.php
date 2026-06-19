<?php

use Illuminate\Support\Facades\DB;

$database = config('database.connections.mysql.database');
$tables = DB::select("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ?", [$database]);

$output = "# Từ điển dữ liệu (Data Dictionary)\n\n";

foreach ($tables as $t) {
    $table = $t->TABLE_NAME;
    if (in_array($table, ['migrations', 'failed_jobs', 'password_reset_tokens', 'personal_access_tokens'])) continue;

    $output .= "### Bảng `$table`\n";
    $output .= "| STT | Thuộc tính | Kiểu dữ liệu | Ràng buộc | Diễn giải |\n";
    $output .= "|---|---|---|---|---|\n";
    
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
        } else if (strpos($extra, 'auto_increment') !== false) {
            // we usually don't need to specify auto increment in constraints for this simple format
        }
        
        $constraintStr = empty($constraints) ? "" : implode(", ", $constraints);
        
        // Diễn giải (try to translate or infer based on name)
        $description = $comment;
        if (empty($description)) {
            if ($isPk) $description = "Khóa chính";
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
        
        $output .= "| $stt | $name | $type | $constraintStr | $description |\n";
        $stt++;
    }
    $output .= "\n";
}

file_put_contents('C:/Users/ACER/.gemini/antigravity-ide/brain/daba0b16-7552-4343-af93-66cdf79f27f6/data_dictionary.md', $output);
echo "Data dictionary generated successfully!\n";
