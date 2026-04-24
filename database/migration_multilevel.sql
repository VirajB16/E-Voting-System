-- Multi-Level Election System - Database Migration Script
-- This script updates the database schema to support class-level and institute-level elections
-- Created: February 10, 2026

-- ============================================
-- STEP 1: Add fields to users table for voter eligibility
-- ============================================

-- Add department and year fields to users table
ALTER TABLE users 
ADD COLUMN department VARCHAR(100) NULL AFTER email,
ADD COLUMN year VARCHAR(20) NULL AFTER department;

-- ============================================
-- STEP 2: Update election_settings table
-- ============================================

-- Add election scope and targeting fields
ALTER TABLE election_settings 
ADD COLUMN election_scope ENUM('class', 'institute') DEFAULT 'institute' AFTER election_status,
ADD COLUMN target_department VARCHAR(100) NULL AFTER election_scope,
ADD COLUMN target_year VARCHAR(20) NULL AFTER target_department,
ADD COLUMN position_name VARCHAR(100) NULL AFTER target_year;

-- ============================================
-- STEP 3: Link candidates to specific elections
-- ============================================

-- Add election_id to candidates table
ALTER TABLE candidates 
ADD COLUMN election_id INT NULL AFTER id,
ADD CONSTRAINT fk_candidate_election 
    FOREIGN KEY (election_id) REFERENCES election_settings(id) ON DELETE SET NULL;

-- ============================================
-- STEP 4: Link votes to specific elections
-- ============================================

-- Add election_id to votes table
ALTER TABLE votes 
ADD COLUMN election_id INT NULL AFTER id,
ADD CONSTRAINT fk_vote_election 
    FOREIGN KEY (election_id) REFERENCES election_settings(id) ON DELETE CASCADE;

-- ============================================
-- STEP 5: Create indexes for performance
-- ============================================

-- Index for filtering elections by scope and target
CREATE INDEX idx_election_scope ON election_settings(election_scope, target_department, target_year);

-- Index for filtering candidates by election
CREATE INDEX idx_candidate_election ON candidates(election_id);

-- Index for filtering votes by election
CREATE INDEX idx_vote_election ON votes(election_id);

-- Index for filtering users by department and year
CREATE INDEX idx_user_dept_year ON users(department, year);

-- ============================================
-- STEP 6: Migrate existing data
-- ============================================

-- Set existing election as institute-level
UPDATE election_settings 
SET election_scope = 'institute', 
    position_name = 'Student Council' 
WHERE id = (SELECT MIN(id) FROM (SELECT id FROM election_settings) AS temp);

-- Link all existing candidates to the first election
UPDATE candidates 
SET election_id = (SELECT MIN(id) FROM election_settings) 
WHERE election_id IS NULL;

-- Link all existing votes to the first election
UPDATE votes 
SET election_id = (SELECT MIN(id) FROM election_settings) 
WHERE election_id IS NULL;

-- ============================================
-- STEP 7: Update sample voter data (optional)
-- ============================================

-- Update some voters with department and year for testing
-- You can customize this based on your actual data

UPDATE users SET department = 'Computer Science', year = '3rd Year' 
WHERE email LIKE '%24106073%' OR email LIKE '%priya%';

UPDATE users SET department = 'Computer Science', year = '2nd Year' 
WHERE email LIKE '%amit%';

UPDATE users SET department = 'Electronics', year = '3rd Year' 
WHERE email LIKE '%anjali%';

UPDATE users SET department = 'Electronics', year = '2nd Year' 
WHERE email LIKE '%sneha%';

UPDATE users SET department = 'Mechanical', year = '3rd Year' 
WHERE email LIKE '%abhishek%';

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Check election_settings structure
-- SELECT * FROM election_settings;

-- Check candidates linked to elections
-- SELECT c.*, e.election_name, e.election_scope FROM candidates c 
-- LEFT JOIN election_settings e ON c.election_id = e.id;

-- Check votes linked to elections
-- SELECT COUNT(*) as vote_count, election_id FROM votes GROUP BY election_id;

-- Check users with department and year
-- SELECT id, full_name, email, department, year FROM users WHERE role = 'voter';

-- ============================================
-- ROLLBACK SCRIPT (if needed)
-- ============================================

/*
-- To rollback these changes, run:

ALTER TABLE votes DROP FOREIGN KEY fk_vote_election;
ALTER TABLE votes DROP COLUMN election_id;

ALTER TABLE candidates DROP FOREIGN KEY fk_candidate_election;
ALTER TABLE candidates DROP COLUMN election_id;

ALTER TABLE election_settings DROP COLUMN position_name;
ALTER TABLE election_settings DROP COLUMN target_year;
ALTER TABLE election_settings DROP COLUMN target_department;
ALTER TABLE election_settings DROP COLUMN election_scope;

ALTER TABLE users DROP COLUMN year;
ALTER TABLE users DROP COLUMN department;

DROP INDEX idx_election_scope ON election_settings;
DROP INDEX idx_candidate_election ON candidates;
DROP INDEX idx_vote_election ON votes;
DROP INDEX idx_user_dept_year ON users;
*/
