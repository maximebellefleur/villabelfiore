# Rooted — Installation Guide

## Your Setup

- **Server**: cPanel hosting (maximebellefleur.com)
- **App URL**: `https://maximebellefleur.com/rooted/`
- **PHP**: 8.2+
- **Database**: MySQL / MariaDB (created manually in cPanel)

---

## What the ZIP Contains

The deploy ZIP (`rooted-cpanel-deploy.zip`) creates two folders inside `public_html/`:

```
public_html/
├── rooted/               ← web-accessible files (PHP entry point, assets, .htaccess)
│   ├── index.php
│   ├── .htaccess
│   ├── assets/
│   ├── manifest.json
│   └── sw.js
└── rooted-files/         ← protected app files (never directly accessible)
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── resources/
    ├── storage/
    ├── .env.example
    └── .htaccess         ← blocks all direct web access
```

`rooted/index.php` automatically detects the `rooted-files/` folder next to it and loads the app from there. Nothing in `rooted-files/` is reachable from a browser URL.

---

## First-Time Installation

### Step 1 — Create the MySQL database in cPanel

1. Go to **cPanel → MySQL Databases**
2. Create a database (e.g. `maxime_rooted`)
3. Create a database user with a strong password
4. Add that user to the database with **All Privileges**
5. Note down: host (`localhost`), database name, username, password

### Step 2 — Upload the deploy ZIP

1. Go to **cPanel → File Manager**
2. Navigate to `public_html/`
3. Click **Upload** and upload `rooted-cpanel-deploy.zip`
4. Right-click the ZIP → **Extract** — this creates `rooted/` and `rooted-files/`
5. Delete the ZIP file after extracting

### Step 3 — Set storage permissions

1. In File Manager, right-click `rooted-files/storage/` → **Change Permissions**
2. Set to `755` (owner read/write/execute, others read/execute)
3. Apply recursively to all subfolders

> If the installer shows "storage/ not writable", set it to `775` instead.

### Step 4 — Run the installer

Visit: **https://maximebellefleur.com/rooted/**

You will be redirected to `/rooted/install`. Follow the 6 steps:

| Step | What it does |
|------|-------------|
| 1 — Environment check | Verifies PHP version, extensions, storage is writable |
| 2 — Database setup | Enter your DB credentials; tables are created automatically |
| 3 — Land identity | Name your land, set timezone and currency |
| 4 — Storage | Choose **Local Filesystem** (recommended for cPanel) |
| 5 — Integrations | Skip for now (Google Calendar etc.) |
| 6 — Admin account | Create your login email and password |

### Step 5 — Log in

After installation you are redirected to `/rooted/login`. Sign in with the credentials you just created.

The homepage (`/rooted/`) will now redirect to your dashboard automatically.

---

## Requirements

| Requirement | Minimum |
|-------------|---------|
| PHP | 8.2+ |
| MySQL / MariaDB | 8.0+ / 10.5+ |
| PHP extensions | `pdo_mysql`, `json`, `mbstring`, `fileinfo` |
| Apache mod_rewrite | Must be enabled |
| `storage/` writable | `chmod 755` or `775` |
