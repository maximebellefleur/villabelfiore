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

> **From v1.1.0 onwards**, use the in-app upgrade instead — go to **Settings → Upgrade**,
> download the ZIP and upload it there. PHP handles everything safely.
> The manual steps below are only needed for the very first update to v1.1.0.

### Step 1 — Download the update ZIP

Get `rooted-cpanel-update.zip` from the GitHub repository:
- Go to `https://github.com/maximebellefleur/villabelfiore`
- Branch: `claude/create-rooted-project-RVbog`
- Click the file → **Download**

### Step 2 — Upload and extract (no deleting needed)

The update ZIP does **not** contain `.env` or `storage/`, so you can safely extract
it over the existing folders — your config and data will not be touched.

In cPanel → **File Manager** → `public_html/`:
1. Upload `rooted-cpanel-update.zip`
2. Right-click → **Extract** (confirm overwrite if prompted — this is safe)
3. Right-click the ZIP → **Delete**

### Step 3 — Done

Visit `https://maximebellefleur.com/rooted/` — everything works with your existing data.

> **You do not need to back up or re-upload `.env`.**
> You do not need to delete the old folders.
> Just upload, extract, delete ZIP.

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
