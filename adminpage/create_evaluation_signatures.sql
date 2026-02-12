-- Create table for storing evaluation e-signatures
CREATE TABLE IF NOT EXISTS evaluation_signatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    evaluator_id INT NOT NULL COMMENT 'ID of the admin/staff who evaluated',
    year_semester VARCHAR(10) NOT NULL COMMENT 'Format: 1-1, 1-2, 2-1, etc.',
    signature_filename VARCHAR(255) DEFAULT NULL COMMENT 'E-signature filename',
    evaluation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    comments TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_evaluation (student_id, evaluator_id, year_semester),
    INDEX idx_student_id (student_id),
    INDEX idx_evaluator_id (evaluator_id),
    INDEX idx_year_semester (year_semester)
);

-- Add foreign key constraints if needed
-- ALTER TABLE evaluation_signatures ADD CONSTRAINT fk_eval_signatures_student 
-- FOREIGN KEY (student_id) REFERENCES signin_db(student_id);
-- ALTER TABLE evaluation_signatures ADD CONSTRAINT fk_eval_signatures_evaluator 
-- FOREIGN KEY (evaluator_id) REFERENCES signin_db(id);
