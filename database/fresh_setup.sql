-- E-Voting System Database Schema - RESTORED VERSION
-- Drop existing database if exists and create new one
DROP DATABASE IF EXISTS evoting;
CREATE DATABASE evoting CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE evoting;

-- Users table (for both voters and candidates)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    mobile VARCHAR(15) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('voter', 'candidate') NOT NULL,
    photo VARCHAR(255),
    address TEXT,
    student_id VARCHAR(50) UNIQUE,
    department VARCHAR(100),
    year VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_mobile (mobile),
    INDEX idx_role (role),
    INDEX idx_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Votes table
CREATE TABLE votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    voter_id INT NOT NULL,
    candidate_id INT NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (voter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (voter_id),
    INDEX idx_candidate (candidate_id),
    INDEX idx_voted_at (voted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- OTP verification table
CREATE TABLE otp_verification (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    email VARCHAR(255),
    mobile VARCHAR(15),
    otp VARCHAR(6) NOT NULL,
    purpose ENUM('login', 'registration', 'password_reset', 'vote_verification') NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_mobile (mobile),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin users table
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Election settings table
CREATE TABLE election_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_name VARCHAR(255) NOT NULL,
    election_status ENUM('not_started', 'active', 'paused', 'completed') DEFAULT 'not_started',
    start_date TIMESTAMP NULL,
    end_date TIMESTAMP NULL,
    description TEXT,
    allow_registration BOOLEAN DEFAULT TRUE,
    show_results BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit log table
CREATE TABLE audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    user_type ENUM('voter', 'candidate', 'admin') NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id, user_type),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO admin_users (username, email, password, full_name, role, status) VALUES
('admin', 'admin@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', 'active');

-- Insert default election
INSERT INTO election_settings (election_name, election_status, description, allow_registration, show_results) VALUES
('College Student Council Election 2026', 'not_started', 'Annual student council election for the academic year 2026-2027', TRUE, FALSE);

-- Create views for easy data retrieval
CREATE VIEW vote_summary AS
SELECT 
    u.id,
    u.name,
    u.email,
    u.department,
    u.photo,
    COUNT(v.id) as vote_count
FROM users u
LEFT JOIN votes v ON u.id = v.candidate_id
WHERE u.role = 'candidate' AND u.status = 'active'
GROUP BY u.id, u.name, u.email, u.department, u.photo
ORDER BY vote_count DESC;

CREATE VIEW voter_turnout AS
SELECT 
    COUNT(DISTINCT v.voter_id) as voted_count,
    (SELECT COUNT(*) FROM users WHERE role = 'voter' AND status = 'active') as total_voters,
    ROUND((COUNT(DISTINCT v.voter_id) / (SELECT COUNT(*) FROM users WHERE role = 'voter' AND status = 'active')) * 100, 2) as turnout_percentage
FROM votes v;
