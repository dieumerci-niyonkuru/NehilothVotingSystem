# Nehiloth Worship & Voting System

A complete web platform for **Nehiloth Worship Team** managing:
- 🎵 **Song Requests & Upvoting System**: Members and visitors can request praise & worship songs, upvote favorites for Sunday services, and track preparation statuses.
- 🤝 **Join Worship Team Applications**: Online application portal for choir singers, band musicians (keyboard, guitar, bass, drums), sound engineers, and media team members.
- 👥 **Leadership & Committee Directory**: Public directory showcasing worship directors, lead vocalists, and technical team coordinators.
- 🗳️ **Democratic Leadership Elections**: Transparent member voting system for worship committee positions with real-time reporting.
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

---

### 🔑 Default Admin Credentials
- **Email**: `admin@nehiloth.com`
- **Password**: `Admin@123`
