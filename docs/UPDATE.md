# Rooted — Update Guide

Updates are done by replacing the code files with a new ZIP. Your database,
uploaded files, and configuration (`.env`) are **never touched** by an update.

---

## What is safe / what is preserved

| Safe to overwrite | Never overwrite |
|-------------------|-----------------|
| `rooted/` (all web files) | `rooted-files/.env` |
| `rooted-files/app/` | `rooted-files/storage/` |
| `rooted-files/bootstrap/` | Your database |
| `rooted-files/config/` | |
| `rooted-files/database/` | |
| `rooted-files/resources/` | |

---

## Update Process (cPanel)

### Step 1 — Back up first (takes 2 minutes)

In cPanel → **File Manager**:
1. Right-click `rooted-files/.env` → **Download** (save it somewhere safe)
2. In cPanel → **phpMyAdmin**: select your database → **Export** → Quick → Go (saves a `.sql` file)

### Step 2 — Download the update ZIP

Get `rooted-cpanel-update.zip` from the GitHub repository:
- Go to `https://github.com/maximebellefleur/villabelfiore`
- Branch: `claude/create-rooted-project-RVbog`
- Click the file → **Download**

> The update ZIP contains only code files. It does **not** contain `storage/` or `.env.example`,
> so your data is safe even if you forget to back up.

### Step 3 — Delete the old code folders in cPanel

In **File Manager** → `public_html/`:
1. Delete the `rooted/` folder
2. Delete the `rooted-files/` folder

> Do **not** touch anything else in `public_html/`.

### Step 4 — Upload and extract the update ZIP

1. Upload `rooted-cpanel-update.zip` to `public_html/`
2. Right-click → **Extract**
3. Delete the ZIP after extracting

### Step 5 — Restore your .env

The update ZIP does not include `.env`, so you need to put it back:

1. In File Manager, navigate to `public_html/rooted-files/`
2. Click **Upload** and upload the `.env` file you saved in Step 1

### Step 6 — Done

Visit `https://maximebellefleur.com/rooted/` — the app loads with all your data intact.

---

## If the update includes database changes

Occasionally an update adds new tables or columns. When this happens, it will be
noted in the commit message or changelog. You will need to run the migration SQL:

1. In cPanel → **phpMyAdmin**, select your database
2. Click the **SQL** tab
3. Paste and run the migration SQL provided in the update notes

---

## Rollback

If something goes wrong after an update:

1. Delete the new `rooted/` and `rooted-files/` folders
2. Re-upload and extract the previous version's ZIP
3. Restore your `.env`
4. If you ran a DB migration, restore your database backup from Step 1

---

## Quick reference

| ZIP file | When to use |
|----------|-------------|
| `rooted-cpanel-deploy.zip` | First-time install on a blank server |
| `rooted-cpanel-update.zip` | Updating an existing install (preserves your data) |
