# Nehiloth Worship & Voting System

A complete web platform for **Nehiloth Worship Team** managing:
- 🎵 **Song Requests & Upvoting System**: Members and visitors can request praise & worship songs, upvote favorites for Sunday services, and track preparation statuses.
- 🤝 **Join Worship Team Applications**: Online application portal for choir singers, band musicians (keyboard, guitar, bass, drums), sound engineers, and media team members.
- 👥 **Leadership & Committee Directory**: Public directory showcasing worship directors, lead vocalists, and technical team coordinators.
- 🗳️ **Democratic Leadership Elections**: Member voting for worship committee positions with verified, published results.
- 🗳️ **Election Lifecycle & Ballots**: Draft, publish, schedule, pause, resume, close, and publish verified election results. Approved members are snapshotted at publication and submit one complete ballot per election.
- 🛡️ **Admin Control Panel**: Centralized management for members, song request approvals, audition scheduling, and election controls.
- 🎧 **Song Library & Rehearsal Setlists**: Members submit public song links or reviewed audio recordings; leaders schedule songs for rehearsals.
- 📱 **Mobile-ready experience**: Responsive web pages, installable PWA shell, and JSON endpoints for mobile clients.
- 👥 **Role-based leadership**: President, secretary, schedule coordinator, and election manager dashboards are separate from full administrator access.
- 🔗 **Social Channels**: Direct links to Nehiloth Worship Team Instagram, YouTube, and TikTok.

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

## Worship gallery media

Open **Admin → Gallery** to manage public media. When the gallery manager is opened, supported files already in `photo/` and `video/` are added to the gallery automatically. Administrators can upload JPEG, PNG, GIF, WebP, MP4, WebM, and OGV files, edit titles and descriptions, and publish or hide items without deleting the original media file. Existing external HTTP(S) media links can also be added.

The public gallery and gallery API show only published items and render videos with native playback controls. For an existing installation, run `upgrade.sql` to add the gallery visibility field before deploying these changes.

## Songs, rehearsals, and mobile

Members can submit a public song link or upload an MP3, M4A, WAV, OGG, or WebM recording (up to 32 MB). Song requests remain private until reviewed and approved. Approved recordings play in the song library and can be placed in an ordered rehearsal setlist from **Admin → Worship Schedule**. Members can remove their own pending requests.

Assign leadership portal roles through **Admin → Member Management** or link a member in **Admin → Leadership**. Presidents, secretaries, schedule coordinators, and election managers receive role-scoped dashboards. Full administrator access is a separate explicit role grant and requires an approved member account.

The website can be installed from a mobile browser as a PWA. Mobile clients can use:

- `GET /api/songs.php` for approved and scheduled songs.
- `POST /api/songs.php` with the signed-in member session, CSRF token from the API login response (`X-CSRF-Token`), and song metadata (or `audio_file` multipart upload) to submit a song.
- `GET /api/schedules.php` for upcoming schedule entries with their ordered song setlists.
- `GET /api/dashboard.php` for home data and official social links.

This repository currently contains the PHP website and API, not a native Android application source tree. The mobile browser/PWA and API can be tested here; a native APK build requires the separate Android client project.

---

### 🔑 Default Admin Credentials
- **Email**: `admin@nehiloth.com`
- **Password**: `Admin@123`
