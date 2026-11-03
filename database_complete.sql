USE nehiloth_voting;

-- Ensure members table has required columns for phone auth & roles
ALTER TABLE members ADD COLUMN IF NOT EXISTS password VARCHAR(255) DEFAULT '$2y$10$eE4aQ.Y.4vL2l29L5BfCme2yM04eBv5Z06Z7W4X0Q8L5K7X9Y0Z.';
ALTER TABLE members ADD COLUMN IF NOT EXISTS gender ENUM('male','female','other') DEFAULT 'male';
ALTER TABLE members ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300';
ALTER TABLE members ADD COLUMN IF NOT EXISTS member_id_no VARCHAR(50) NULL;
ALTER TABLE members ADD COLUMN IF NOT EXISTS group_dept VARCHAR(100) DEFAULT 'Worship Choir';
ALTER TABLE members ADD COLUMN IF NOT EXISTS role ENUM('member','admin','president','secretary','schedule_coordinator','election_manager') DEFAULT 'member';

-- Ensure positions table has election_id
ALTER TABLE positions ADD COLUMN IF NOT EXISTS election_id INT NULL;

-- Ensure candidates table has vision_statement, manifesto, photo_url, member_id
ALTER TABLE candidates ADD COLUMN IF NOT EXISTS vision_statement TEXT NULL;
ALTER TABLE candidates ADD COLUMN IF NOT EXISTS manifesto TEXT NULL;
ALTER TABLE candidates ADD COLUMN IF NOT EXISTS photo_url VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300';
ALTER TABLE candidates ADD COLUMN IF NOT EXISTS member_id INT NULL;

-- OTP Password Reset Verifications
CREATE TABLE IF NOT EXISTS otp_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    phone VARCHAR(20) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Announcements Table
CREATE TABLE IF NOT EXISTS announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author_name VARCHAR(100) DEFAULT 'NELILOTH Leadership',
    image_url VARCHAR(255) NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    is_important BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- System Notifications & Member Read Status
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    recipient_group VARCHAR(50) DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS member_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    notification_id INT NULL,
    message VARCHAR(255) NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Weekly Worship Schedule
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Schedule Assignments
CREATE TABLE IF NOT EXISTS schedule_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_id INT NOT NULL,
    member_id INT NOT NULL,
    role_assigned VARCHAR(100) NOT NULL,
    status ENUM('assigned','confirmed','absent') DEFAULT 'assigned',
    FOREIGN KEY (schedule_id) REFERENCES weekly_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Leadership Directory (Current & History)
CREATE TABLE IF NOT EXISTS leadership (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(100) NOT NULL,
    bio TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    photo_url VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300',
    term_years VARCHAR(50) DEFAULT '2025-2026',
    status ENUM('active','historical') DEFAULT 'active',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Elections Engine
CREATE TABLE IF NOT EXISTS elections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('upcoming','open','closed','cancelled') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Secret Ballots & Voter Eligibility (Anonymized)
CREATE TABLE IF NOT EXISTS ballots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    position_id INT NOT NULL,
    candidate_id INT NOT NULL,
    anonymous_hash VARCHAR(255) NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
);

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

-- Gallery & Events
CREATE TABLE IF NOT EXISTS gallery (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    category ENUM('photos','videos') DEFAULT 'photos',
    media_url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255) NULL,
    description TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_title VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(255) DEFAULT 'RP Tumba College Campus',
    description TEXT,
    image_url VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=600',
    category VARCHAR(50) DEFAULT 'Worship Concert',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Sample Announcements
INSERT INTO announcements (title, content, author_name, is_pinned, is_important)
SELECT * FROM (SELECT 'Welcome to NELILOTH WORSHIP Digital Platform' AS a, 'Welcome to the official digital platform of NELILOTH WORSHIP (CEP Family - Rwanda Polytechnic, Tumba College). Stay connected with weekly schedules, announcements, and leadership elections.' AS b, 'CEP Leadership' AS c, 1 AS d, 1 AS e) AS tmp
WHERE NOT EXISTS (SELECT id FROM announcements WHERE title LIKE '%Welcome to NELILOTH%') LIMIT 1;

-- Seed Sample Schedule
INSERT INTO weekly_schedules (activity_title, day_of_week, start_time, end_time, location, responsible_leader, description, date_scheduled)
SELECT * FROM (SELECT 'Sunday Main Worship Service' AS a, 'Sunday' AS b, '08:30:00' AS c, '11:30:00' AS d, 'RP Tumba Main Chapel' AS e, 'Pastor David Nshimiyimana' AS f, 'Full congregation praise and worship service with choir and sermon.' AS g, '2026-10-25' AS h) AS tmp
WHERE NOT EXISTS (SELECT id FROM weekly_schedules WHERE activity_title = 'Sunday Main Worship Service') LIMIT 1;

INSERT INTO weekly_schedules (activity_title, day_of_week, start_time, end_time, location, responsible_leader, description, date_scheduled)
SELECT * FROM (SELECT 'Midweek Worship & Choir Rehearsal' AS a, 'Wednesday' AS b, '17:00:00' AS c, '19:00:00' AS d, 'RP Tumba Chapel Hall' AS e, 'Grace Umutoni' AS f, 'Choir vocal practice and instrumental arrangements for Sunday.' AS g, '2026-10-21' AS h) AS tmp
WHERE NOT EXISTS (SELECT id FROM weekly_schedules WHERE activity_title = 'Midweek Worship & Choir Rehearsal') LIMIT 1;

-- Seed Sample Active Election
INSERT INTO elections (title, description, start_time, end_time, status)
SELECT * FROM (SELECT 'NELILOTH Leadership Committee Election 2026' AS a, 'Official election for President, Vice President, Secretary, Treasurer, and Worship Leaders.' AS b, '2026-10-20 00:00:00' AS c, '2026-10-28 23:59:59' AS d, 'open' AS e) AS tmp
WHERE NOT EXISTS (SELECT id FROM elections WHERE title LIKE '%Leadership Committee Election%') LIMIT 1;

-- Seed Sample Positions
INSERT INTO positions (election_id, title, description)
SELECT e.id, 'President / Chairperson', 'Leads the NELILOTH Worship Organization spiritual & administrative affairs.'
FROM elections e WHERE e.title LIKE '%Leadership Committee Election%' AND NOT EXISTS (SELECT id FROM positions WHERE title = 'President / Chairperson') LIMIT 1;

INSERT INTO positions (election_id, title, description)
SELECT e.id, 'Worship Leader / Choir Director', 'Coordinates choir arrangements, rehearsals, and worship service leading.'
FROM elections e WHERE e.title LIKE '%Leadership Committee Election%' AND NOT EXISTS (SELECT id FROM positions WHERE title = 'Worship Leader / Choir Director') LIMIT 1;

-- Seed Sample Candidates
INSERT INTO candidates (position_id, name, vision_statement, manifesto, bio)
SELECT p.id, 'Pastor David Nshimiyimana', 'To build a united, spiritually grounded, and musicially excellent worship team.', '1. Expand choir training\n2. Introduce digital scheduling\n3. Enhance prayer meetings', 'Experienced worship leader and spiritual mentor at RP Tumba College.'
FROM positions p WHERE p.title = 'President / Chairperson' AND NOT EXISTS (SELECT id FROM candidates WHERE name = 'Pastor David Nshimiyimana') LIMIT 1;

INSERT INTO candidates (position_id, name, vision_statement, manifesto, bio)
SELECT p.id, 'Grace Umutoni', 'Raising passionate vocalists dedicated to genuine praise and worship.', '1. Weekly vocal coaching\n2. Song repertoire expansion\n3. Rehearsal discipline', 'Lead vocalist with 8 years of worship ministry experience.'
FROM positions p WHERE p.title = 'Worship Leader / Choir Director' AND NOT EXISTS (SELECT id FROM candidates WHERE name = 'Grace Umutoni') LIMIT 1;

-- Seed Sample Gallery & Events
INSERT INTO events (event_title, event_date, event_time, location, description, category)
SELECT * FROM (SELECT 'Annual RP Tumba Praise Concert' AS a, '2026-11-15' AS b, '14:00:00' AS c, 'Tumba College Auditorium' AS d, 'A powerful evening of praise and worship featuring CEP Family choirs and guest ministers.' AS e, 'Concert' AS f) AS tmp
WHERE NOT EXISTS (SELECT id FROM events WHERE event_title = 'Annual RP Tumba Praise Concert') LIMIT 1;

INSERT INTO gallery (title, category, media_url, description)
SELECT * FROM (SELECT 'Sunday Praise Service Highlights' AS a, 'photos' AS b, 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=800' AS c, 'Moments from our Sunday congregation worship service.' AS d) AS tmp
WHERE NOT EXISTS (SELECT id FROM gallery WHERE title = 'Sunday Praise Service Highlights') LIMIT 1;
