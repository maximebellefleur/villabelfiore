# Rooted v1 — Update Guide

Updates in Rooted v1 are **manual and reviewed**. There are no automatic updates.

## Before Updating

1. **Back up your database** — see BACKUP_RESTORE.md
2. **Back up your files** — especially `storage/uploads/` and `.env`
3. Note your current version from Settings

## Update Process

1. Pull or extract the new version to a staging folder
2. Review the CHANGELOG for breaking changes and migration notes
3. Compare `.env.example` with your `.env` — add any new keys
4. Replace application files (everything except `.env`, `storage/`, and `public/assets/images/` if you have custom icons)
5. Run any new migration SQL files from `database/migrations/` in order
6. Clear cache if applicable: `rm -f storage/cache/*`
7. Test the application

## Rollback

To roll back:
1. Restore the previous code package
2. Restore the database backup from before the update
3. Restore the `storage/uploads/` backup

## Migration Files

Each update includes numbered SQL files in `database/migrations/`. Run only the ones added since your last update, in numerical order.
