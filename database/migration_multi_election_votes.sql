-- Multi-Election Voter Dashboard - Database Updates
-- Add election_id to votes table for tracking votes per election

-- Step 1: Add election_id column to votes table
ALTER TABLE votes 
ADD COLUMN election_id INT NULL AFTER candidate_id;

-- Step 2: Add foreign key constraint
ALTER TABLE votes
ADD CONSTRAINT fk_votes_election
FOREIGN KEY (election_id) REFERENCES election_settings(id)
ON DELETE SET NULL;

-- Step 3: Add index for performance
ALTER TABLE votes
ADD INDEX idx_voter_election (voter_id, election_id);

-- Step 4: Add unique constraint to prevent duplicate votes per election
ALTER TABLE votes
ADD UNIQUE KEY unique_voter_election (voter_id, election_id);

-- Step 5: Update existing votes to reference their election
-- This assumes candidates have election_id set
UPDATE votes v
JOIN users u ON v.candidate_id = u.id
SET v.election_id = u.election_id
WHERE u.role = 'candidate' AND u.election_id IS NOT NULL;

-- Verification queries
SELECT 'Votes table structure:' as info;
DESCRIBE votes;

SELECT 'Sample votes with election_id:' as info;
SELECT v.id, v.voter_id, v.candidate_id, v.election_id, v.created_at
FROM votes v
LIMIT 10;

SELECT 'Vote count per election:' as info;
SELECT 
    e.election_name,
    e.position_name,
    COUNT(v.id) as vote_count
FROM election_settings e
LEFT JOIN votes v ON e.id = v.election_id
GROUP BY e.id, e.election_name, e.position_name
ORDER BY e.created_at DESC;
