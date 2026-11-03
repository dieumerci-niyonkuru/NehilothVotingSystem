USE nehiloth_voting;

-- Song Requests Table
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

-- Song Votes Table
CREATE TABLE IF NOT EXISTS song_votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    song_id INT NOT NULL,
    member_id INT NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_song_member_vote (song_id, member_id),
    FOREIGN KEY (song_id) REFERENCES song_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Leadership Directory Table
CREATE TABLE IF NOT EXISTS leadership (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(100) NOT NULL,
    bio TEXT,
    email VARCHAR(100),
    phone VARCHAR(20),
    member_id INT NULL,
    photo_url VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE leadership ADD COLUMN IF NOT EXISTS member_id INT NULL;
ALTER TABLE leadership ADD COLUMN IF NOT EXISTS term_years VARCHAR(50) DEFAULT '2025-2026';
ALTER TABLE leadership ADD COLUMN IF NOT EXISTS status ENUM('active','historical') DEFAULT 'active';

-- Join Worship Team Applications Table
CREATE TABLE IF NOT EXISTS worship_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NULL,
    applicant_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    role_applied VARCHAR(100) NOT NULL,
    experience VARCHAR(100) NOT NULL,
    availability VARCHAR(100) NOT NULL,
    notes TEXT,
    status ENUM('pending','interview_scheduled','approved','rejected') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
);

-- Seed Initial Leadership Members if Empty
INSERT INTO leadership (name, role, bio, email, phone, display_order)
SELECT * FROM (SELECT 'Pastor David Nshimiyimana', 'Worship Director', 'Oversees Nehiloth Worship Team spiritual and musical direction.', 'david@nehiloth.com', '0788000001', 1) AS tmp
WHERE NOT EXISTS (SELECT id FROM leadership WHERE email = 'david@nehiloth.com') LIMIT 1;

INSERT INTO leadership (name, role, bio, email, phone, display_order)
SELECT * FROM (SELECT 'Grace Umutoni', 'Lead Vocalist & Worship Leader', 'Praise & worship leader with 8 years of vocal ministry experience.', 'grace@nehiloth.com', '0788000002', 2) AS tmp
WHERE NOT EXISTS (SELECT id FROM leadership WHERE email = 'grace@nehiloth.com') LIMIT 1;

INSERT INTO leadership (name, role, bio, email, phone, display_order)
SELECT * FROM (SELECT 'Dieudonne Mugisha', 'Music & Technical Director', 'Pianist, sound engineer, and worship arrangements coordinator.', 'dieudonne@nehiloth.com', '0788000003', 3) AS tmp
WHERE NOT EXISTS (SELECT id FROM leadership WHERE email = 'dieudonne@nehiloth.com') LIMIT 1;

-- Seed Sample Song Requests if Empty
INSERT INTO song_requests (requester_name, song_title, artist, genre_category, key_note, notes, status, votes_count)
SELECT * FROM (SELECT 'Grace Umutoni', 'Way Maker', 'Sinach', 'Praise & Worship', 'G Major', 'Great song for Sunday opening worship', 'approved', 12) AS tmp
WHERE NOT EXISTS (SELECT id FROM song_requests WHERE song_title = 'Way Maker') LIMIT 1;

INSERT INTO song_requests (requester_name, song_title, artist, genre_category, key_note, notes, status, votes_count)
SELECT * FROM (SELECT 'Test Member', 'What A Beautiful Name', 'Hillsong Worship', 'Worship', 'D Major', 'Powerful song for quiet reflection', 'pending', 5) AS tmp
WHERE NOT EXISTS (SELECT id FROM song_requests WHERE song_title = 'What A Beautiful Name') LIMIT 1;
