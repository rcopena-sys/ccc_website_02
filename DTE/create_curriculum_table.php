<?php
// create_curriculum_table.php
require_once 'db.php';

    // Create curriculum table for CSV uploads
    $sql = "CREATE TABLE IF NOT EXISTS curriculum (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fiscal_year VARCHAR(20) NOT NULL,
        program VARCHAR(10) NOT NULL,
        year_semester VARCHAR(10) NOT NULL,
        subject_code VARCHAR(20) NOT NULL,
        course_title VARCHAR(200) NOT NULL,
        lec_units DECIMAL(3,1) NOT NULL,
        lab_units DECIMAL(3,1) NOT NULL,
        total_units DECIMAL(3,1) NOT NULL,
        prerequisites TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_curriculum (fiscal_year, program, year_semester, subject_code)
    )";

if ($conn->query($sql) === TRUE) {
    echo "Curriculum table created successfully<br>";
    
    // Create indexes for better performance (only if they don't exist)
    $indexes = [
        "idx_curriculum_program" => "CREATE INDEX idx_curriculum_program ON curriculum(program)",
        "idx_curriculum_fiscal_year" => "CREATE INDEX idx_curriculum_fiscal_year ON curriculum(fiscal_year)",
        "idx_curriculum_year_semester" => "CREATE INDEX idx_curriculum_year_semester ON curriculum(year_semester)",
        "idx_curriculum_subject_code" => "CREATE INDEX idx_curriculum_subject_code ON curriculum(subject_code)"
    ];
    
    foreach ($indexes as $index_name => $index_sql) {
        // Check if index already exists
        $check_index = $conn->query("SHOW INDEX FROM curriculum WHERE Key_name = '$index_name'");
        if ($check_index->num_rows === 0) {
            if ($conn->query($index_sql) === TRUE) {
                echo "Index '$index_name' created successfully<br>";
            } else {
                echo "Error creating index '$index_name': " . $conn->error . "<br>";
            }
        } else {
            echo "Index '$index_name' already exists<br>";
        }
    }
    
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$conn->close();
echo "<br><a href='upload_curriculum_csv.php'>Go to CSV Upload</a>";
?> 