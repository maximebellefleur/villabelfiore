# Rooted

> Land Management System — v1

Rooted is a self-hostable web application for managing a productive land: trees, orchards, edible gardens, prep zones, mobile assets, animal modules, reminders, activity logs, and basic finance tracking. Built with PHP + MySQL + jQuery. No frameworks required.

---

## Features

- **Item management** — track trees (olive, almond, vine), gardens, beds, lines, prep zones, buildings, water points, and more
- **GPS-assisted** — capture coordinates in the field, confirm on desktop
- **Activity log** — every action recorded with timestamp and user
- **Attachments** — upload photos and documents per item (4-directional yearly photo refresh for trees)
- **Reminders** — due-date reminders with recurring support, linked to items
- **Harvest tracking** — log harvests by quantity/unit per item
- **Finance tracking** — costs and revenues per item (enabled for olive and almond trees by default)
- **Settings** — land identity, timezone, currency, integrations
- **PWA-ready** — installable to home screen, offline-capable with draft/sync queue
- **Installer** — guided 6-step web installer, no manual SQL needed
- **Portable** — runs on shared hosting, VPS, or a Raspberry Pi on local network

---

## Requirements

| Requirement | Minimum |
|-------------|---------|
| PHP | 8.2+ |
| MySQL | 8.0+ |
| Extensions | `pdo_mysql`, `json`, `mbstring`, `fileinfo` |
| Web server | Apache (with `mod_rewrite`) or Nginx |
| Disk space | ~50 MB for application, plus storage for uploads |

---

## Installation

### 1. Deploy the files

Upload the contents of this repository to your server. The web root must point to the **`public/`** directory.

**Apache example** (`VirtualHost` or `.htaccess` at project root):
```
DocumentRoot /path/to/rooted/public
```

**Nginx example:**
```nginx
root /path/to/rooted/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_filename;
}
```

The `public/` directory contains `index.php` — all other directories must stay **above** the web root.

### 2. Set storage permissions

```bash
chmod -R 775 storage/
chown -R www-data:www-data storage/   # adjust user to your web server user
```

### 3. Copy the environment file

```bash
cp .env.example .env
```

You do **not** need to edit `.env` manually — the installer will write the database credentials for you.

### 4. Run the installer

Visit your site in a browser:

```
http://your-domain.com/
```

You will be redirected to `/install`. Follow the 6-step installer:

| Step | Purpose |
|------|---------|
| 1 | Environment check (PHP version, extensions, write permissions) |
| 2 | Database credentials — connects and runs the schema |
| 3 | Land identity (name, timezone, language, currency) |
| 4 | Storage setup (local by default) |
| 5 | Optional integrations (Google Calendar, weather API) |
| Finish | Create admin account → write `.env` → mark installed |

After finishing, you will be redirected to `/login`. Sign in with the admin account you created.

---

## Self-hosted / Raspberry Pi

Rooted is designed to run without internet access once installed.

1. Install PHP 8.2 and MySQL 8 on the Pi:
   ```bash
   sudo apt install php8.2 php8.2-mysql php8.2-mbstring php8.2-json php8.2-fileinfo mysql-server
   ```
2. Clone or copy the project to `/var/www/rooted`
3. Set up a local domain or use the Pi's IP address
4. Point the web root to `/var/www/rooted/public`
5. Follow the same installer steps above

---

## Directory Structure

```
rooted/
├── app/
│   ├── Controllers/     # Request handlers (thin — delegate to services)
│   ├── Models/          # Data models
│   ├── Services/        # Business logic
│   ├── Repositories/    # Database queries
│   ├── Validators/      # Input validation rules
│   ├── Policies/        # Authorization checks
│   └── Support/         # Core: Router, DB, CSRF, Env, Logger, etc.
├── bootstrap/
│   └── init.php         # App bootstrap (autoload, env, session, error handler)
├── config/
│   ├── app.php          # Application config
│   ├── routes.php       # All route definitions
│   ├── item_types.php   # Item type definitions (types, allowed meta, etc.)
│   └── defaults.php     # Default values for settings
├── database/
│   ├── schema.sql       # Full MySQL schema (run by installer)
│   ├── migrations/      # Numbered migration files
│   └── seeds/           # Seed data (action types, default settings)
├── docs/                # Documentation
├── public/              # ← WEB ROOT — point your server here
│   ├── index.php        # Application entry point
│   ├── manifest.json    # PWA manifest
│   ├── sw.js            # Service worker
│   └── assets/          # CSS, JS, images
├── resources/
│   └── views/           # PHP templates (layouts, partials, pages)
└── storage/
    ├── logs/            # Application and error logs
    ├── uploads/         # Uploaded files (items, generated icons)
    ├── backups/         # Database and file backups
    └── cache/           # Cache files
```

---

## Configuration

After installation all settings are managed in **Admin → Settings**. The `.env` file only holds infrastructure secrets (DB credentials, app key). Everything else — timezone, currency, GPS thresholds, reminder lead times, integrations — is editable in the UI.

### Key `.env` variables

| Variable | Default | Purpose |
|----------|---------|---------|
| `APP_URL` | `http://localhost` | Public base URL |
| `APP_DEBUG` | `false` | Enable verbose error output |
| `DB_HOST` | `localhost` | MySQL host |
| `DB_NAME` | `rooted` | Database name |
| `SESSION_NAME` | `rooted_session` | Session cookie name |
| `STORAGE_DRIVER` | `local` | File storage driver |
| `LOG_LEVEL` | `error` | Minimum log level |

---

## Item Types

Rooted uses a generic item architecture. Every item has a type that controls which fields, meta keys, finance module, harvest module, and action presets are available.

| Type | Description | Finance | Harvest |
|------|-------------|---------|---------|
| `olive_tree` | Olive tree | ✓ | ✓ |
| `almond_tree` | Almond tree | ✓ | ✓ |
| `tree` | Generic tree | — | — |
| `vine` | Vine / grape | — | — |
| `orchard` | Orchard (groups trees) | — | — |
| `garden` | Garden (contains beds) | — | — |
| `bed` | Garden bed (contains lines) | — | — |
| `line` | Planting line | — | — |
| `prep_zone` | Area being prepared | — | — |
| `mobile_coop` | Mobile chicken coop | — | — |
| `building` | Structure | — | — |
| `water_point` | Water source | — | — |
| `zone` | General land zone | — | — |

Item types are defined in `config/item_types.php` and can be extended without modifying core code.

---

## Backup and Restore

See [docs/BACKUP_RESTORE.md](docs/BACKUP_RESTORE.md) for full instructions.

**Quick backup:**
```bash
mysqldump -u USER -p DBNAME > backup_$(date +%Y%m%d).sql
tar -czf uploads_$(date +%Y%m%d).tar.gz storage/uploads/
```

**Restore to a new server:**
1. Deploy files
2. Create database and import the SQL dump
3. Copy `.env` from old server
4. Extract uploads archive to `storage/uploads/`
5. Skip the installer — the app will detect it is already installed

---

## Updating

See [docs/UPDATE.md](docs/UPDATE.md) for full instructions.

1. Back up database and uploads
2. Replace application files (keep `.env` and `storage/`)
3. Run any new migration files in `database/migrations/`
4. Clear `storage/cache/`

---

## Security Notes

- The installer is automatically locked after completion (`.env` flag + DB setting)
- All POST routes require a CSRF token
- Passwords are hashed with `password_hash(..., PASSWORD_BCRYPT)`
- File uploads are validated by MIME type (via `finfo`), not file extension
- Uploaded files are stored with generated names, not original filenames
- `storage/`, `config/`, `database/`, `app/`, `bootstrap/` must **not** be accessible from the web — only `public/` should be the web root

---

## PWA / Offline Use

Rooted is installable as a Progressive Web App. On mobile:
1. Open in the browser
2. Use "Add to Home Screen" (iOS Safari) or the install prompt (Android Chrome)
3. The app shell is cached for offline use
4. Forms can be saved as drafts while offline
5. Changes sync automatically when the connection returns

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2+ (no framework) |
| Database | MySQL 8+ |
| Frontend | HTML5, CSS3 (custom), jQuery 3.7 |
| PWA | Web App Manifest + Service Worker |
| Architecture | MVC-like, modular, no Composer required |

---

## License

Private / proprietary. Not for redistribution.

---

## Docs

- [Installation Guide](docs/INSTALL.md)
- [Update Guide](docs/UPDATE.md)
- [Backup & Restore](docs/BACKUP_RESTORE.md)
- [API Reference](docs/API.md)
- [Data Model](docs/DATA_MODEL.md)
