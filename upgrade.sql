ALTER TABLE admin ADD COLUMN IF NOT EXISTS member_id INT NULL;
SET @admin_member_index_exists = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'admin' AND index_name = 'unique_member_admin'
);
SET @admin_member_index_sql = IF(
    @admin_member_index_exists = 0,
    'ALTER TABLE admin ADD UNIQUE KEY unique_member_admin (member_id)',
    'SELECT 1'
);
PREPARE admin_member_index_stmt FROM @admin_member_index_sql;
EXECUTE admin_member_index_stmt;
DEALLOCATE PREPARE admin_member_index_stmt;
ALTER TABLE members ADD COLUMN IF NOT EXISTS role ENUM('member','admin','president','secretary','schedule_coordinator','election_manager') DEFAULT 'member';
ALTER TABLE members MODIFY role ENUM('member','admin','president','secretary','schedule_coordinator','election_manager') DEFAULT 'member';
CREATE TABLE IF NOT EXISTS weekly_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    activity_title VARCHAR(255) NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(255) DEFAULT 'RP Tumba College Chapel',
    responsible_leader VARCHAR(100) NULL,
    description TEXT NULL,
    date_scheduled DATE NOT NULL,
    rehearsal_type VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE weekly_schedules ADD COLUMN IF NOT EXISTS rehearsal_type VARCHAR(50) NULL;
CREATE TABLE IF NOT EXISTS schedule_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_id INT NOT NULL,
    member_id INT NOT NULL,
    role_assigned VARCHAR(100) NOT NULL,
    status ENUM('assigned','confirmed','absent') DEFAULT 'assigned',
    FOREIGN KEY (schedule_id) REFERENCES weekly_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS leadership (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(100) NOT NULL,
    bio TEXT NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    member_id INT NULL,
    photo_url VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300',
    display_order INT DEFAULT 0,
    term_years VARCHAR(50) DEFAULT '2025-2026',
    status ENUM('active','historical') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE leadership ADD COLUMN IF NOT EXISTS member_id INT NULL;
ALTER TABLE leadership ADD COLUMN IF NOT EXISTS term_years VARCHAR(50) DEFAULT '2025-2026';
ALTER TABLE leadership ADD COLUMN IF NOT EXISTS status ENUM('active','historical') DEFAULT 'active';
CREATE TABLE IF NOT EXISTS song_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NULL,
    requester_name VARCHAR(100) NOT NULL,
    song_title VARCHAR(255) NOT NULL,
    artist VARCHAR(100) DEFAULT 'Unknown / Traditional',
    genre_category VARCHAR(100) DEFAULT 'Worship',
    key_note VARCHAR(20) DEFAULT 'C',
    lyrics_link TEXT NULL,
    media_url TEXT NULL,
    notes TEXT NULL,
    status ENUM('pending','approved','scheduled','rejected') DEFAULT 'pending',
    votes_count INT DEFAULT 0,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
);
ALTER TABLE song_requests ADD COLUMN IF NOT EXISTS media_url TEXT NULL;
CREATE TABLE IF NOT EXISTS song_votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    song_id INT NOT NULL,
    member_id INT NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_song_member_vote (song_id, member_id),
    FOREIGN KEY (song_id) REFERENCES song_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS rehearsal_songs (
    schedule_id INT NOT NULL,
    song_id INT NOT NULL,
    sequence_no INT NOT NULL DEFAULT 1,
    rehearsal_notes VARCHAR(500) NULL,
    PRIMARY KEY (schedule_id, song_id),
    FOREIGN KEY (schedule_id) REFERENCES weekly_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES song_requests(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS leadership_activity (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_title VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(255) DEFAULT 'RP Tumba College Campus',
    description TEXT NULL,
    image_url VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=600',
    category VARCHAR(50) DEFAULT 'Worship Concert',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE settings ADD COLUMN IF NOT EXISTS last_notification TIMESTAMP NULL;
CREATE TABLE IF NOT EXISTS announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author_name VARCHAR(100) DEFAULT 'Nehiloth Leadership',
    image_url VARCHAR(255) NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    is_important BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
UPDATE announcements
SET title = 'Welcome to Nehiloth Worship Team',
    content = 'Welcome to the official digital home of Nehiloth Worship Team at Rwanda Polytechnic, Tumba College. Stay connected with our songs, rehearsals, announcements, and leadership.',
    author_name = 'Nehiloth Leadership'
WHERE title = 'Welcome to NELILOTH WORSHIP Digital Platform'
  AND content LIKE '%CEP Family%';
CREATE TABLE IF NOT EXISTS otp_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    phone VARCHAR(20) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    message VARCHAR(255) NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS title VARCHAR(255) NULL;
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS recipient_group VARCHAR(50) DEFAULT 'all';

CREATE TABLE IF NOT EXISTS gallery (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    category ENUM('photos','videos') DEFAULT 'photos',
    media_url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255) NULL,
    description TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE gallery ADD COLUMN IF NOT EXISTS is_published BOOLEAN NOT NULL DEFAULT TRUE;

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