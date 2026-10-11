-- Demo accounts (password for all: password)
-- Hash is PHP password_hash compatible: password_verify('password', hash)

USE nurses_leave_system;

INSERT INTO users (email, password_hash, role, status) VALUES
('admin@hospital.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
('matron@hospital.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'matron', 'active'),
('nurse@hospital.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nurse', 'active'),
('anura@hospital.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nurse', 'active');

INSERT INTO matrons (user_id, full_name, phone, department)
SELECT id, 'Matron Kumari Perera', '0771234567', 'Nursing Administration' FROM users WHERE email = 'matron@hospital.local';

INSERT INTO nurses (user_id, employee_no, full_name, nic, gender, phone, ward, department, designation, address, date_of_joining)
SELECT id, 'NLS-2024-0001', 'Nimali Silva', '905432123V', 'female', '0712345678', 'ICU', 'Critical Care', 'Staff Nurse', '12 Hospital Road, Colombo', '2020-03-15'
FROM users WHERE email = 'nurse@hospital.local';

INSERT INTO nurses (user_id, employee_no, full_name, nic, gender, phone, ward, department, designation, address, date_of_joining)
SELECT id, 'NLS-2024-0002', 'Anura Fernando', '882211334V', 'male', '0729876543', 'Ward 3', 'General Medicine', 'Senior Nurse', '45 Lake Drive, Kandy', '2018-07-01'
FROM users WHERE email = 'anura@hospital.local';

INSERT INTO leave_balances (nurse_id, year, annual_entitled, annual_used, casual_entitled, casual_used, medical_entitled, medical_used)
SELECT id, YEAR(CURDATE()), 21, 0, 7, 1, 14, 0 FROM nurses WHERE employee_no = 'NLS-2024-0001';

INSERT INTO leave_balances (nurse_id, year, annual_entitled, annual_used, casual_entitled, casual_used, medical_entitled, medical_used)
SELECT id, YEAR(CURDATE()), 21, 0, 7, 0, 14, 0 FROM nurses WHERE employee_no = 'NLS-2024-0002';

INSERT INTO leave_applications (nurse_id, leave_type, start_date, end_date, days, reason, status)
SELECT id, 'annual', DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), 3, 'Family function', 'pending'
FROM nurses WHERE employee_no = 'NLS-2024-0001';

INSERT INTO leave_applications (nurse_id, leave_type, start_date, end_date, days, reason, status, reviewed_by, reviewed_at, review_comment)
SELECT n.id, 'casual', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 20 DAY), 1, 'Personal errand', 'approved', u.id, NOW(), 'Approved'
FROM nurses n
JOIN users u ON u.email = 'matron@hospital.local'
WHERE n.employee_no = 'NLS-2024-0001';
