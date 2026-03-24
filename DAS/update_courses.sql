-- Update curriculum_subjects table with new course data
-- First, clear existing data
DELETE FROM curriculum_subjects;

-- Insert First Year - First Semester courses
INSERT INTO curriculum_subjects (subject_code, subject_title, lecture_units, lab_units, units, prerequisites) VALUES
('IT 101', 'Introduction to Computing with Laboratory', 2.0, 3.0, 3.0, '-'),
('CS 101', 'Fundamentals of Programming with Laboratory', 2.0, 3.0, 3.0, '-'),
('MATH 101', 'Mathematics in the Modern World', 3.0, 0.0, 3.0, '-'),
('US 101', 'Understanding the Self', 3.0, 0.0, 3.0, '-'),
('IE 101', 'Interactive English', 3.0, 0.0, 3.0, '-'),
('SEC 101', 'Security Awareness', 3.0, 0.0, 3.0, '-'),
('ALG 101', 'Linear Algebra', 3.0, 0.0, 3.0, '-'),
('PE 101', 'Physical Fitness, Gymnastics and Aerobics', 2.0, 0.0, 2.0, '-'),
('NSTP 101', 'National Service Training Program 1', 3.0, 0.0, 3.0, '-');

-- Insert First Year - Second Semester courses
INSERT INTO curriculum_subjects (subject_code, subject_title, lecture_units, lab_units, units, prerequisites) VALUES
('CS 102', 'Oriented Programming', 2.0, 3.0, 3.0, 'CS101'),
('IT 102', 'Information Management', 3.0, 0.0, 3.0, 'IT101'),
('NET 102', 'Computer Networking 1', 2.0, 3.0, 3.0, 'IT101'),
('IT 201', 'Data Structures and Algorithms with laboratory', 2.0, 3.0, 3.0, 'CS 101'),
('PCOM 102', 'Purposive Communication', 3.0, 0.0, 3.0, 'IE101'),
('IT 231', 'Computer Networking 3', 2.0, 3.0, 3.0, 'IT101, CS101'),
('CALC 102', 'Mechanics', 3.0, 0.0, 3.0, 'Math101'),
('PE 102', 'Team Sports', 2.0, 0.0, 2.0, 'PATHfit 1'),
('NSTP 102', 'Team Sports', 3.0, 0.0, 3.0, '-'); 