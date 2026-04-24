-- Delete existing admin user if any
DELETE FROM admin_users WHERE username = 'admin';

-- Insert new admin user with username: admin, password: admin@Apsit
INSERT INTO admin_users (username, email, password, full_name, role, status) VALUES
('admin', 'admin@college.edu.in', '$2y$10$a0mEufNrXCPFIYQW7c8bKewjygtStaxUgAcIgmj1zwuCwtILWHJXK', 'System Administrator', 'super_admin', 'active');

-- Verify the insert
SELECT id, username, email, full_name, role, status FROM admin_users WHERE username = 'admin';
