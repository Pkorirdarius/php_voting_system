# VoteSecure — PHP Voting Application

A full-featured, secure voting application built in **pure PHP** (no framework) with MySQL.

---

## Features

| Portal | Capabilities |
|---|---|
| **Voter** | Register with National ID · Login · Vote once per seat · Track progress |
| **Candidate** | Register candidacy · Login · View approval status · Edit manifesto · See live standings |
| **Admin** | Approve / Reject candidates · Voter registry · Live results dashboard · Vote feed |
| **Public** | Live results page · Leading candidates · Real-time ticker (auto-refreshes every 10 s) |

---

## Requirements

- PHP 7.4+ (8.x recommended)
- MySQL 5.7+ / MariaDB 10.3+
- Apache (mod_rewrite) or Nginx
- PDO with MySQL driver enabled

---

## Installation

### 1 — Clone / copy files

```
/var/www/html/          ← document root (or wherever your server points)
├── index.php
├── .htaccess
├── database.sql
├── assets/
│   └── css/main.css
├── includes/
│   ├── db.php
│   ├── auth.php
│   └── layout.php
├── voter/
│   ├── register.php
│   ├── login.php
│   ├── vote.php
│   └── logout.php
├── candidate/
│   ├── register.php
│   ├── login.php
│   ├── dashboard.php
│   └── logout.php
└── admin/
    ├── login.php
    ├── dashboard.php
    ├── candidates.php
    ├── voters.php
    ├── live.php
    ├── api_results.php
    └── logout.php
```

### 2 — Create the database

```bash
mysql -u root -p < database.sql
```

### 3 — Configure the database connection

Edit `includes/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'voting_app');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

### 4 — Set up your web server

**Apache** — enable `mod_rewrite` and set `AllowOverride All` for the document root.  
The included `.htaccess` handles directory protection.

**Nginx** — example block:
```nginx
server {
    root /var/www/html;
    index index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location /includes/ { deny all; }
}
```

### 5 — Set file permissions

```bash
chmod -R 755 /var/www/html
chmod -R 644 /var/www/html/assets
```

### 6 — Change the default admin password

The default admin credentials are:

| Field | Value |
|---|---|
| Username | `admin` |
| Password | `Admin@1234` |

**Change immediately** by running:
```bash
php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT);"
```
Then update the hash in the `admins` table:
```sql
USE voting_app;
UPDATE admins SET password_hash='<new_hash>' WHERE username='admin';
```

---

## User Flows

### Voter
1. Go to **/** → click **Register to Vote**
2. Fill in Full Name, National ID, Email, Password
3. Login → **Vote Now** page
4. Select one candidate per seat → **Cast Vote**
5. Progress bar tracks seats remaining

### Candidate
1. Go to **/** → click **I'm a Candidate**
2. Submit application (seat, party, manifesto)
3. Wait for admin approval
4. Login to dashboard → see vote tally and seat rankings

### Admin
1. Go to **/admin/login.php**
2. Approve / reject candidates from **Candidate Applications**
3. Monitor **Live Results** — auto-refreshes every 10 seconds
4. Browse **Voter Registry** for all registrations

---

## Security Features

- Passwords hashed with `password_hash()` (bcrypt)
- CSRF tokens on all forms
- PDO prepared statements (no SQL injection)
- Session-based authentication per role
- `includes/` directory blocked from direct web access
- `X-Frame-Options`, `X-XSS-Protection`, `X-Content-Type-Options` headers
- One vote enforced per voter per seat via database `UNIQUE KEY`

---

## Customising

- **Add electoral seats**: Candidates self-register with any seat name; no config needed
- **Styling**: Edit `assets/css/main.css` — uses CSS custom properties for easy theming
- **Auto-refresh interval**: Change `setInterval(fetchResults, 10000)` in `admin/live.php`
- **Add more admins**: Insert rows into the `admins` table with a bcrypt hash

---

## License

MIT — see `LICENSE`
