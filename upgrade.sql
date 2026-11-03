ALTER TABLE settings ADD COLUMN IF NOT EXISTS last_notification TIMESTAMP NULL;
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    message VARCHAR(255) NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS title VARCHAR(255) NULL;
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS recipient_group VARCHAR(50) DEFAULT 'all';

CREATE TABLE IF NOT EXISTS elections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('draft','upcoming','open','paused','closed','cancelled') DEFAULT 'draft',
    results_published BOOLEAN NOT NULL DEFAULT FALSE,
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE elections MODIFY status ENUM('draft','upcoming','open','paused','closed','cancelled') DEFAULT 'draft';
ALTER TABLE elections ADD COLUMN IF NOT EXISTS results_published BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE elections ADD COLUMN IF NOT EXISTS published_at DATETIME NULL;

ALTER TABLE positions ADD COLUMN IF NOT EXISTS election_id INT NULL;
ALTER TABLE candidates ADD COLUMN IF NOT EXISTS vision_statement TEXT NULL;
ALTER TABLE candidates ADD COLUMN IF NOT EXISTS manifesto TEXT NULL;
ALTER TABLE candidates ADD COLUMN IF NOT EXISTS photo_url VARCHAR(255) NULL;

CREATE TABLE IF NOT EXISTS ballots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    position_id INT NOT NULL,
    candidate_id INT NOT NULL,
    anonymous_hash VARCHAR(255) NOT NULL,
    ballot_group VARCHAR(64) NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ballot_position (ballot_group, position_id),
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
);
ALTER TABLE ballots ADD COLUMN IF NOT EXISTS ballot_group VARCHAR(64) NULL;
SET @ballot_position_index_exists = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'ballots' AND index_name = 'unique_ballot_position'
);
SET @ballot_position_index_sql = IF(
    @ballot_position_index_exists = 0,
    'ALTER TABLE ballots ADD UNIQUE KEY unique_ballot_position (ballot_group, position_id)',
    'SELECT 1'
);
PREPARE ballot_position_index_stmt FROM @ballot_position_index_sql;
EXECUTE ballot_position_index_stmt;
DEALLOCATE PREPARE ballot_position_index_stmt;

CREATE TABLE IF NOT EXISTS election_eligible_voters (
    election_id INT NOT NULL,
    member_id INT NOT NULL,
    eligible_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (election_id, member_id),
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS election_vote_receipts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    member_id INT NOT NULL,
    idempotency_hash CHAR(64) NOT NULL,
    receipt_code CHAR(32) NULL,
    receipt_hash CHAR(64) NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_election_voter (election_id, member_id),
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);
ALTER TABLE election_vote_receipts ADD COLUMN IF NOT EXISTS receipt_code CHAR(32) NULL;
UPDATE election_vote_receipts
SET receipt_code = LOWER(SUBSTRING(SHA2(CONCAT('existing-receipt:', id), 256), 1, 32)),
    receipt_hash = SHA2(LOWER(SUBSTRING(SHA2(CONCAT('existing-receipt:', id), 256), 1, 32)), 256)
WHERE receipt_code IS NULL;

CREATE TABLE IF NOT EXISTS voting_eligibility (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    member_id INT NOT NULL,
    position_id INT NOT NULL,
    has_voted BOOLEAN DEFAULT TRUE,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_voter_position (election_id, member_id, position_id),
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE
);

INSERT IGNORE INTO election_eligible_voters (election_id, member_id)
SELECT e.id, m.id
FROM elections e
CROSS JOIN members m
WHERE e.status IN ('upcoming','open','paused','closed')
  AND m.status = 'approved';

INSERT IGNORE INTO election_vote_receipts (election_id, member_id, idempotency_hash, receipt_code, receipt_hash, submitted_at)
SELECT election_id,
       member_id,
       SHA2(CONCAT('legacy-idempotency:', election_id, ':', member_id), 256),
       LOWER(SUBSTRING(SHA2(CONCAT('legacy-code:', election_id, ':', member_id), 256), 1, 32)),
       SHA2(LOWER(SUBSTRING(SHA2(CONCAT('legacy-code:', election_id, ':', member_id), 256), 1, 32)), 256),
       MIN(voted_at)
FROM voting_eligibility
GROUP BY election_id, member_id;