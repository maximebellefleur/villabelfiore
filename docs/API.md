# Rooted v1 — API Reference

All API endpoints return JSON in the standard envelope format:

```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "errors": {}
}
```

Authentication is via session cookie (same as web UI). All endpoints require an active login session.

---

## Items

### GET /api/items/nearby
Returns items within a given radius of a GPS coordinate.

**Query params:**
- `lat` (float) — latitude
- `lng` (float) — longitude
- `radius` (float, default 1.0) — radius in km

**Response:**
```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "Olive Tree #12", "type": "olive_tree", "distance_km": 0.043 }
  ]
}
```

### GET /api/items/{id}
Returns a single item by ID.

### POST /api/items
Create a new item.

**Body (JSON or form):**
- `type` (string, required)
- `name` (string, required)
- `gps_lat`, `gps_lng`, `gps_accuracy`, `gps_source` (optional)
- `parent_id` (optional)

### POST /api/items/{id}/actions
Log an action for an item.

**Body:**
- `action_type` (string, required)
- `description` (string, required)

---

## Reminders

### GET /api/reminders
Returns pending reminders (upcoming and overdue), max 20.

---

## Dashboard

### GET /api/dashboard/summary
Returns high-level counts for the dashboard.

**Response:**
```json
{
  "success": true,
  "data": {
    "total_items": 42,
    "overdue_reminders": 3
  }
}
```

---

## Sync

### POST /api/sync/push
Push a queued sync operation.

**Body:**
- `entity_type` (string)
- `operation_type` (string: create|update|delete)
- `payload` (JSON string)

### GET /sync/status
Returns count of pending sync queue items.

---

## Harvests

### POST /api/items/{id}/harvests
Record a harvest for an item.

**Body:**
- `quantity` (float, required)
- `unit` (string, required)
- `recorded_at` (datetime, required)
- `harvest_type` (string, optional)
- `quality_grade` (string, optional)
- `notes` (string, optional)
