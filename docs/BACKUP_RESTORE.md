# Rooted v1 — Backup & Restore Guide

## What to Back Up

| Component | Location |
|-----------|----------|
| Database  | MySQL dump |
| Uploaded files | `storage/uploads/` |
| Application config | `.env` |
| Generated icons | `storage/uploads/generated-icons/` |

## Database Backup

```bash
mysqldump -u YOUR_DB_USER -p YOUR_DB_NAME > rooted_backup_$(date +%Y%m%d).sql
```

## File Backup

```bash
tar -czf rooted_uploads_$(date +%Y%m%d).tar.gz storage/uploads/
```

## Full Backup Script

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M)
mysqldump -u root -p rooted > /backups/db_$DATE.sql
tar -czf /backups/files_$DATE.tar.gz /var/www/rooted/storage/uploads/
echo "Backup complete: $DATE"
```

## Restore

### Restore Database

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < rooted_backup_20260101.sql
```

### Restore Files

```bash
tar -xzf rooted_uploads_20260101.tar.gz -C /var/www/rooted/
```

### Restore to a New Server

1. Deploy fresh Rooted installation (same or newer version)
2. Restore database backup
3. Restore `storage/uploads/` backup
4. Copy `.env` from backup (update server-specific values)
5. Run installer if `.env` flag `INSTALL_LOCK` is not set (or set it manually in `.env`)

## JSON Export

A JSON export of all operational data can be generated from the admin interface (Settings → Export) in future versions. In v1, use SQL dump as the primary backup method.
