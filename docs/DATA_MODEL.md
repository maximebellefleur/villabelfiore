# Rooted v1 — Data Model

## Architecture

Rooted uses a **generic item architecture**: one central `items` table with flexible metadata in `item_meta`. Behavior is driven by `type` + config from `config/item_types.php`.

## Tables

### users
One user per land instance in v1. Fields: id, email, password_hash, display_name, is_active, created_at, updated_at, last_login_at.

### items
Core entity table. Every tree, garden, bed, orchard, zone, etc. is a row here.

Key fields:
- `type` — item type key (olive_tree, garden, bed, etc.)
- `parent_id` — self-referential, e.g. bed → garden
- `status` — active | archived | trashed | draft
- `gps_lat`, `gps_lng` — GPS coordinates (nullable)
- `gps_source` — device | manual | corrected
- `is_finance_enabled` — enables finance module
- `is_mobile_asset` — enables location history tracking

### item_meta
Key-value metadata per item. Keys are documented per type in `config/item_types.php`.

Common keys: `variety`, `latin_name`, `estimated_age_years`, `purpose`, `sun_exposure`, `soil_type`, `irrigation_type`, `bed_length_m`, `bed_width_m`, `garden_area_m2`, `line_crop_mix`, `cover_crop_type`.

### item_relationships
Extra linking beyond `parent_id`. Used for mobile coop ↔ prep_zone, orchard ↔ trees, etc.

### attachments
File registry. Files are stored in `storage/uploads/items/` by default. DB holds metadata only (not file blobs).

Categories: `identification_photo`, `yearly_refresh_north/south/east/west`, `generated_icon`, `harvest_photo`, `general_attachment`, `invoice`, `treatment_document`.

### activity_log
Append-only log of successful actions. Never stores failures (those go to error_logs).

### error_logs
All errors, warnings, exceptions. Severity: info | warning | error | critical.

### action_types
Lookup table for action keys and labels. System types are seeded; users can add custom types.

### reminders
Reminders linked optionally to items. Supports recurrence. Status: pending | completed | dismissed | archived.

### harvest_entries
Harvest records per item. Supports multiple units (kg, L, wheelbarrow, etc.).

### finance_entries
Cost/revenue/market_reference entries per item. Used for olive and almond financial tracking.

### drafts
Incomplete form saves. Created when validation fails or user saves mid-form. Promoted to real records when valid.

### sync_queue
Offline operations queued for sync. Status: queued | processing | synced | failed.

### settings
Key-value store for all operational settings. Keys use dot notation: `app.name`, `gps.accuracy_threshold`, etc.

### storage_targets
Configurable file storage destinations (local, FTP, SFTP).

### item_location_history
Movement history for mobile assets. Append-only.

## Item Type Hierarchy

```
orchard
  └── tree / olive_tree / almond_tree / vine

garden
  └── bed
       └── line

prep_zone (can convert to garden)
mobile_coop (linked to prep_zone optionally)
building
water_point
zone
```
