# Nehiloth Worship & Voting System

A complete web platform for **Nehiloth Worship Team** managing:
- 🎵 **Song Requests & Upvoting System**: Members and visitors can request praise & worship songs, upvote favorites for Sunday services, and track preparation statuses.
- 🤝 **Join Worship Team Applications**: Online application portal for choir singers, band musicians (keyboard, guitar, bass, drums), sound engineers, and media team members.
- 👥 **Leadership & Committee Directory**: Public directory showcasing worship directors, lead vocalists, and technical team coordinators.
- 🗳️ **Democratic Leadership Elections**: Member voting for worship committee positions with verified, published results.
- 🗳️ **Election Lifecycle & Ballots**: Draft, publish, schedule, pause, resume, close, and publish verified election results. Approved members are snapshotted at publication and submit one complete ballot per election.
- 🛡️ **Admin Control Panel**: Centralized management for members, song request approvals, audition scheduling, and election controls.

---

## 🛠 Tech Stack
- **Backend**: PHP 8.2 (PDO, prepared statements, session security)
- **Database**: MySQL / MariaDB (`nehiloth_voting`)
- **Frontend**: Bootstrap 5, FontAwesome 6, CSS3, HTML5
- **Server**: Apache / PHP Built-in Web Server

---

## 🚀 Quick Start Guide

1. **Start MySQL & PHP Web Server**:
   Ensure MySQL/MariaDB server is running on `localhost:3306`.
   Start the web server from the project directory:
   ```bash
   php -S localhost:8000
   ```

2. **Database Setup**:
   Import the SQL schemas:
   ```bash
   mysql -u root nehiloth_voting < database.sql
   mysql -u root nehiloth_voting < upgrade.sql
   mysql -u root nehiloth_voting < add_member_notifications.sql
   mysql -u root nehiloth_voting < add_songs_and_leadership.sql
   ```

3. **Access the Portal**:
   - **Home Page**: [http://localhost:8000](http://localhost:8000)
   - **Song Requests**: [http://localhost:8000/songs.php](http://localhost:8000/songs.php)
   - **Join Worship Team**: [http://localhost:8000/join_team.php](http://localhost:8000/join_team.php)
   - **Leadership Directory**: [http://localhost:8000/leadership.php](http://localhost:8000/leadership.php)
   - **Admin Login**: [http://localhost:8000/admin/login.php](http://localhost:8000/admin/login.php)

## Election workflow

1. In **Admin → Manage Elections**, create an election. New elections start as drafts.
2. Add every position and candidate, then publish the election. Publishing freezes the eligible-voter list to members approved at that time.
3. Voting opens automatically at the scheduled opening time (Africa/Kigali); administrators can pause, resume, or close an election. Closed elections cannot be reopened.
4. Review the results as an administrator, then explicitly publish them for members and the voting API.
5. Members submit one ballot containing one selection for every position. The server validates eligibility, election dates, candidates, and duplicate submissions inside a database transaction.
6. Mobile clients use `api/voting.php?action=cast_vote` with the authenticated member session, a stable `Idempotency-Key` header, and the complete `selections` map. Members can retrieve their participation receipt with `api/voting.php?action=get_receipt&election_id=<id>`.

The standard database setup above applies `upgrade.sql`, which creates or upgrades the election tables and constraints. Run that migration once for an existing installation before using the updated election workflows.

**Privacy limitation:** voter eligibility and ballot selections are stored in separate tables, but this application does not implement a reviewed cryptographic unlinkability protocol. Do not describe the current database implementation as cryptographically anonymous or independently verifiable.

---

### 🔑 Default Admin Credentials
- **Email**: `admin@nehiloth.com`
- **Password**: `Admin@123`
