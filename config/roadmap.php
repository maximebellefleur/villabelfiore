<?php

/**
 * Rooted — Feature roadmap.
 * Drives the Settings → Upcoming Features page.
 *
 * Statuses: released | in_progress | planned | future
 */
return [

    '2.4.3' => [
        'status'   => 'released',
        'released' => '2026-04-21',
        'title'    => 'Polish & Favicon',
        'features' => [
            ['title' => 'Favicon upload', 'detail' => 'Upload a custom favicon (ICO/PNG/SVG) from Settings. Live previews at 16, 32, and 48 px with size requirements guide.'],
            ['title' => 'Task inline tags', 'detail' => 'Type (TAG) at the start of a task to auto-categorize it. Tag shown as a colored pill badge — same tag always same color.'],
            ['title' => 'SVG nav icons', 'detail' => 'All navigation icons replaced with clean Lucide-style SVG icons — white on transparent, scales perfectly.'],
            ['title' => 'Calendar sync fixes', 'detail' => 'Snooze, complete, and dismiss all properly update or remove Google Calendar events.'],
        ],
    ],

    '2.4.2' => [
        'status'   => 'released',
        'released' => '2026-04-21',
        'title'    => 'Calendar Sync Fix',
        'features' => [
            ['title' => 'Snooze syncs to Google Calendar', 'detail' => '+1 Day and +1 Week now update the event time in Google Calendar immediately.'],
            ['title' => 'Complete/dismiss removes GCal event', 'detail' => 'Marking a reminder done or dismissed deletes the orphaned event from your calendar.'],
            ['title' => 'Full sync updates existing events', 'detail' => 'Settings → Calendar sync now PUTs updated data to all existing events, not just creates new ones.'],
        ],
    ],

    '2.4.1' => [
        'status'   => 'released',
        'released' => '2026-04-21',
        'title'    => 'Smart Reminders',
        'features' => [
            ['title' => 'Snooze reminders (+1 Day / +1 Week)', 'detail' => 'Push any reminder forward by one day or one week with a single tap — available everywhere reminders appear.'],
            ['title' => 'Merged dashboard reminder widget', 'detail' => 'Overdue and upcoming reminders combined in one widget, overdue shown in red, item name shown below title.'],
            ['title' => 'Reminders at top of item page', 'detail' => "Pending reminders are now the first section when you open an item — you can't miss them."],
            ['title' => 'AJAX reminder actions', 'detail' => 'Done, Dismiss, +1d, and +1w all update instantly without reloading the page.'],
            ['title' => '"All Reminders" popup modal', 'detail' => 'Dashboard shows up to 5 reminders inline; a popup lists all of them with full actions.'],
        ],
    ],

    '2.3.0' => [
        'status'   => 'released',
        'released' => '2026-04-20',
        'title'    => 'Smart Irrigation',
        'features' => [
            ['title' => 'Multiple irrigation plans per item', 'detail' => 'Add as many plans as needed per item — no more one-plan restriction.'],
            ['title' => 'New intervals (3/5/10/20 days)', 'detail' => 'More granular watering schedules for different crops and seasons.'],
            ['title' => 'Custom end date + quantity (litres)', 'detail' => 'Set an exact end date and record the volume per session, shown in Google Calendar.'],
            ['title' => 'Time preset for Calendar events', 'detail' => 'Sunrise, Midday, Sunset, Night, or custom hour — sets the event time in Google Calendar.'],
            ['title' => '"Irrigate Today" dashboard widget', 'detail' => 'See what needs watering today. Tap Done to log it in Google Calendar.'],
            ['title' => 'Biodynamic "Right Now" advice', 'detail' => 'Dashboard shows specific actionable advice: what to sow, harvest, or avoid based on the current biodynamic moment.'],
            ['title' => 'Nav icons + basket for Garden', 'detail' => 'All navigation links now show emoji icons. Garden uses the 🧺 basket icon.'],
        ],
    ],

    '2.2.0' => [
        'status'   => 'released',
        'released' => '2026-04-20',
        'title'    => 'Biodynamic Garden',
        'features' => [
            ['title' => 'Maria Thun Biodynamic Calendar', 'detail' => 'Full hourly calendar using Jean Meeus sidereal astronomy. Root/Leaf/Flower/Fruit days, descending moon planting windows, anomaly periods. Crop search highlights the right days.'],
            ['title' => 'Climate-based planting suggestions', 'detail' => 'Garden Hub now shows what to plant this month based on your climate zone (Mediterranean Sicily default). Configure in Settings → General.'],
            ['title' => 'Dashboard biodynamic widget', 'detail' => "Today's organ type, ascending/descending moon, anomaly warning, and 7-day biodynamic week strip — all powered by real sidereal astronomy."],
            ['title' => 'Nav icon logo styling', 'detail' => 'Circular badge style for the nav icon with border and background.'],
            ['title' => 'Modal null-error fixes', 'detail' => 'Log modal and AI modal no longer crash with null addEventListener.'],
        ],
    ],

    '2.1.9' => [
        'status'   => 'released',
        'released' => '2026-04-20',
        'title'    => 'Garden Hub',
        'features' => [
            ['title' => 'Garden Hub page', 'detail' => 'New /garden assistant-style dashboard: Plant This Month, Harvest Window, Active Bed Rows, Family Needs, Low Stock, and Recent Garden Activity — all in one place.'],
            ['title' => 'Navigation updated', 'detail' => '"Seeds" in the top menu and bottom nav replaced with "Garden" linking to the hub. Seeds catalog still reachable from within.'],
            ['title' => 'Copy AI definitively fixed', 'detail' => 'Modal HTML moved above the script block; getElementById now always finds elements; no more null addEventListener crash.'],
        ],
    ],

    '2.1.8' => [
        'status'   => 'released',
        'released' => '2026-04-20',
        'title'    => 'Bug Fixes',
        'features' => [
            ['title' => 'Copy AI fixed', 'detail' => 'Fixed null addEventListener crash — modal elements are now looked up lazily on first click instead of at script load time.'],
            ['title' => 'Remember Me layout', 'detail' => 'Checkbox and label are now on the same line, left-aligned, standard form style.'],
            ['title' => 'Dashboard 4-per-row grid', 'detail' => 'Quick action buttons now use a strict CSS grid — always 4 columns, no irregular wrapping.'],
        ],
    ],

    '2.1.7' => [
        'status'   => 'released',
        'released' => '2026-04-20',
        'title'    => 'Remember Me & Dashboard Links',
        'features' => [
            ['title' => 'Remember Me login', 'detail' => 'Checkbox on the sign-in form stores a secure 30-day token. On return visits the session is restored automatically. Signing out revokes the token immediately.'],
            ['title' => 'Seeds, Family Needs, Irrigation on dashboard', 'detail' => 'Three new quick-action buttons on the dashboard link directly to Seeds catalog, Family Needs planner, and the new Irrigation Plans overview page.'],
            ['title' => 'Irrigation Plans overview', 'detail' => 'New /irrigation page lists all active plans (interval, duration, start date) and shows irrigatable items that have no plan yet — each links to the item page to add one.'],
        ],
    ],

    '2.1.6' => [
        'status'   => 'released',
        'released' => '2026-04-20',
        'title'    => 'Map & UX Fixes',
        'features' => [
            ['title' => 'Map items restored', 'detail' => 'Fixed a SQL column name bug (meta_value vs meta_value_text) that crashed the /api/map/items endpoint and made all items disappear from the Land Map.'],
            ['title' => 'Copy AI via bottom sheet', 'detail' => 'The AI prompt is now shown in a modal sheet. Clipboard copy is triggered by a direct button tap — fixes iOS PWA where the async fetch broke the user-activation window required by the clipboard API.'],
            ['title' => 'Compact action pill buttons', 'detail' => 'Item page quick actions redesigned from tall grid cards to horizontal scrollable pills — same actions, much less vertical space.'],
            ['title' => 'PWA maskable icons', 'detail' => 'Icon upload now generates separate maskable variants (padded to safe zone) so Android adaptive icons show the full logo without clipping.'],
        ],
    ],

    '2.1.5' => [
        'status'   => 'released',
        'released' => '2026-04-20',
        'title'    => 'Irrigation Plan',
        'features' => [
            ['title' => 'Per-item irrigation schedule', 'detail' => 'Add an irrigation plan to any tree, vine, garden, or bed. Choose interval (twice daily to monthly), duration in months, start date, and optional notes. View, edit, and delete from the item detail page.'],
            ['title' => 'Google Calendar recurring event sync', 'detail' => 'When Google Calendar is connected, saving an irrigation plan creates a recurring calendar event "💧 Water — {name}" at 7:00 AM for the full duration. Edit syncs the series; delete removes the entire series.'],
        ],
    ],

    '2.1.4' => [
        'status'   => 'released',
        'released' => '2026-04-20',
        'title'    => 'GPS Accuracy Visual & Bed Corner+Size Mode',
        'features' => [
            ['title' => 'Color-coded GPS accuracy indicator', 'detail' => 'During GPS sampling (boundary points, corner capture) the status shows 🟢 ≤5 m / 🟡 5–20 m / 🔴 >20 m with matching background color so you instantly know signal quality.'],
            ['title' => 'Corner + Size bed/garden boundary mode', 'detail' => 'Stand at any corner of a bed or garden, get a GPS fix, select NE/NW/SE/SW, enter N-S length and E-W width in meters. The app computes the rectangle and previews it on the mini-map. Saves boundary + dimensions to item_meta.'],
            ['title' => 'Planting rows on map', 'detail' => 'If a bed has a bed_rows value stored (from Corner+Size mode), the main land map renders that many dashed parallel horizontal lines inside the polygon — one per planting row.'],
        ],
    ],

    '2.1.3' => [
        'status'   => 'released',
        'released' => '2026-04-19',
        'title'    => 'Family Needs Mobile Fix & Copy AI Fix',
        'features' => [
            ['title' => 'Family Needs card layout', 'detail' => 'Replaced the table with mobile-friendly cards. Each card shows priority badge, vegetable name, qty/unit, linked seed, and notes. Edit expands an inline form. Delete shows inline Yes/Cancel confirm.'],
            ['title' => 'Copy AI clipboard fallback', 'detail' => 'Added execCommand fallback for navigator.clipboard.writeText — fixes the iOS PWA bug where the Clipboard API silently fails when a capture="environment" file input exists on the page.'],
        ],
    ],

    '2.1.2' => [
        'status'   => 'released',
        'released' => '2026-04-18',
        'title'    => 'Family Needs Edit & Delete',
        'features' => [
            ['title' => 'Inline edit for family needs', 'detail' => 'Each row in the Family Needs list has an Edit button that expands an inline form pre-filled with the existing values. Save posts to the existing update route.'],
            ['title' => 'PWA-safe inline delete confirm', 'detail' => 'Delete uses inline "Remove? Yes / No" buttons instead of window.confirm() — works in PWA standalone mode.'],
        ],
    ],

    '2.1.1' => [
        'status'   => 'released',
        'released' => '2026-04-18',
        'title'    => 'CSRF Login Fix',
        'features' => [
            ['title' => 'Service worker network-first for HTML pages', 'detail' => 'Navigation requests (HTML pages) now bypass the service worker cache entirely and always fetch from the network. This ensures CSRF tokens embedded in forms are always fresh and match the PHP session — fixes the "403 Invalid or missing CSRF token" login error on PWA installs.'],
        ],
    ],

    '2.1.0' => [
        'status'   => 'released',
        'released' => '2026-04-18',
        'title'    => 'Walk Mode Live Feedback',
        'features' => [
            ['title' => 'Recording overlay on map', 'detail' => 'A red pill banner "⏺ REC · 12 pts · 45 m · ±3 m" appears on the map canvas itself while GPS walk is active — no need to look at the sidebar.'],
            ['title' => 'Live GPS position dot', 'detail' => 'A red dot follows your position on the map during walk mode so you can see the path being traced behind you in real time.'],
            ['title' => 'Toast on GPS point save', 'detail' => '"📍 Point N saved · ±X m" toast pops on the map each time a manual GPS corner is added.'],
        ],
    ],

    '2.0.9' => [
        'status'   => 'released',
        'released' => '2026-04-18',
        'title'    => 'Yearly Compass Survey',
        'features' => [
            ['title' => 'Guided 4-direction photo survey', 'detail' => 'Tap "🧭 Survey" on any item to capture S/E/N/W photos in sequence. Uses rear camera directly, shows thumbnail after each capture, then uploads all 4 to the item gallery in one batch.'],
            ['title' => '5-button quick action bar', 'detail' => 'Item show page quick actions now include Survey alongside Add Photo, Reminder, Log, and AI Copy.'],
        ],
    ],

    '2.0.8' => [
        'status'   => 'released',
        'released' => '2026-04-18',
        'title'    => 'Fix APP_BASE URL Bug',
        'features' => [
            ['title' => 'APP_BASE slash fix', 'detail' => 'All AJAX URLs that concatenated window.APP_BASE + path now correctly include a leading slash on the path segment, preventing broken URLs like /rootedpath instead of /rooted/path.'],
        ],
    ],

    '2.0.7' => [
        'status'   => 'released',
        'released' => '2026-04-18',
        'title'    => 'Boundary Walk in Edit Item',
        'features' => [
            ['title' => 'Walk boundary from edit page', 'detail' => 'Item edit page now has a Boundary section for boundary-able types. Walk, preview on mini-map, save via API, or delete. No map page required.'],
            ['title' => 'GPS denied error in walk mode', 'detail' => 'When GPS permission is denied, walk mode now immediately shows an error instead of waiting forever for a fix that never comes.'],
        ],
    ],

    '2.0.6' => [
        'status'   => 'released',
        'released' => '2026-04-18',
        'title'    => 'Multi-Photo Log & Google Satellite',
        'features' => [
            ['title' => 'Multiple log photos', 'detail' => 'Attach several photos to one log entry. All are saved to the item gallery; the first is linked to the log for the feed thumbnail.'],
            ['title' => 'PWA cache bust (v4)', 'detail' => 'Service worker cache version bumped so all installed PWAs re-fetch the latest JS/CSS on next visit — eliminates the stale Esri tile issue.'],
        ],
    ],

    '2.0.5' => [
        'status'   => 'released',
        'released' => '2026-04-18',
        'title'    => 'Edit Meta Save & Google Mini-Map',
        'features' => [
            ['title' => 'Edit form saves meta fields', 'detail' => 'The update action now persists all type-specific meta fields (variety, tree_type, soil, irrigation, sun, etc.) to item_meta. Empty fields are deleted. Previously edit silently discarded all meta changes.'],
            ['title' => 'Google satellite on mini-map', 'detail' => 'Item create/edit forms now use Google Maps satellite (maxNativeZoom 21) instead of Esri (max 19) — same tiles and zoom as the main land map.'],
        ],
    ],

    '2.0.4' => [
        'status'   => 'released',
        'released' => '2026-04-17',
        'title'    => 'Multi-Photo Upload',
        'features' => [
            ['title' => 'Multiple photo upload', 'detail' => 'File input now has the multiple attribute. Files are uploaded sequentially with shared caption and category. Progress shows "2 / 5 · 64%".'],
        ],
    ],

    '2.0.3' => [
        'status'   => 'released',
        'released' => '2026-04-17',
        'title'    => 'Edit Form Fields & Google Satellite',
        'features' => [
            ['title' => 'Edit form: full meta fields', 'detail' => 'Item edit now shows all type-specific fields (required + optional) with dropdowns and custom tree type support. Values pre-populated from DB.'],
            ['title' => 'Google satellite tiles',       'detail' => 'Switched back to Google Maps satellite (maxNativeZoom 21). Two extra zoom levels of real imagery vs Esri standard (19).'],
        ],
    ],

    '2.0.2' => [
        'status'   => 'released',
        'released' => '2026-04-16',
        'title'    => 'Walk Mode GPS Fix',
        'features' => [
            ['title' => 'RootedGPS.subscribe API', 'detail' => 'Shared GPS stream now supports continuous subscribers. Walk mode subscribes to this instead of opening a competing watchPosition.'],
            ['title' => 'Wake Lock during walk', 'detail' => 'Screen stays on while walking so iOS/Android does not throttle background JavaScript and pause GPS callbacks.'],
        ],
    ],

    '2.0.1' => [
        'status'   => 'released',
        'released' => '2026-04-15',
        'title'    => 'Map Fixes',
        'features' => [
            ['title' => 'Esri World Imagery satellite', 'detail' => 'Switched from Google to Esri World Imagery standard REST tiles. Free, no API key, zoom 19+ for Italy.'],
            ['title' => 'Land boundary non-interactive', 'detail' => 'Land perimeter polygon placed in a dedicated CSS pane with pointer-events:none. Clicks on the land fill area no longer intercept item taps.'],
            ['title' => 'Walk mode GPS fix',             'detail' => 'Removed 30s GPS timeout from watchPosition. Walk now waits as long as needed for a fix. Accuracy threshold raised to 25 m for better rural coverage.'],
        ],
    ],

    '2.0.0' => [
        'status'   => 'released',
        'released' => '2026-04-15',
        'title'    => 'Google Satellite & Gallery Fix',
        'features' => [
            ['title' => 'Google satellite tiles', 'detail' => 'Switched from ESRI Clarity to Google Maps satellite. Provides zoom 20+ coverage for rural Sicily and regions where ESRI imagery was unavailable above zoom 15.'],
            ['title' => 'Gallery fullscreen fix',  'detail' => 'Image tap to fullscreen was broken due to window.open name collision. Restructured the gallery IIFE: all DOM vars declared first, internal function renamed to galleryOpen, window.openGallery assigned directly.'],
        ],
    ],

    '1.9.9' => [
        'status'   => 'released',
        'released' => '2026-04-15',
        'title'    => 'Walk Perimeter Mode',
        'features' => [
            ['title' => 'Walk Perimeter — land boundary', 'detail' => 'Tap 🚶 Walk the Perimeter and walk your land. GPS records continuously, filtering noise >12m and recording every 1.5m of movement. RDP simplification on finish produces a clean polygon.'],
            ['title' => 'Walk Boundary — zone/item',      'detail' => 'Same continuous walk mode available in the Draw Zone Boundary panel for gardens, beds, orchards, etc.'],
        ],
    ],

    '1.9.8' => [
        'status'   => 'released',
        'released' => '2026-04-15',
        'title'    => 'Precise GPS & Better Satellite',
        'features' => [
            ['title' => 'Multi-sample GPS averaging', 'detail' => 'Boundary GPS collects up to 12 readings over 6 seconds, averages the best half. Each placed point shows an accuracy ring. Stops early at ≤5 m accuracy.'],
            ['title' => 'ESRI Clarity satellite tiles', 'detail' => 'Switched from Google to ESRI World Imagery Clarity — sharper imagery at high zoom for rural/agricultural land.'],
        ],
    ],

    '1.9.7' => [
        'status'   => 'released',
        'released' => '2026-04-15',
        'title'    => 'cPanel PUBLIC_PATH Fix',
        'features' => [
            ['title' => 'cPanel upload path fix', 'detail' => 'index.php now sets PUBLIC_PATH = __DIR__ in cPanel deployments so uploaded files (logos, attachments) are saved to the actual web root, not to the unserved rooted-files/public/ directory.'],
        ],
    ],

    '1.9.6' => [
        'status'   => 'released',
        'released' => '2026-04-15',
        'title'    => 'Map & Item Detail Polish',
        'features' => [
            ['title' => 'Google satellite tiles',         'detail' => 'Map switched from Esri to Google satellite tiles — sharper, higher zoom, no API key needed.'],
            ['title' => 'Land boundary non-interactive',  'detail' => 'Land boundary outline is visual-only: no hover tooltip, no click events.'],
            ['title' => 'Gallery bug fix',                'detail' => 'openGallery is now always defined on window — no more "not defined" error when an item has no images.'],
            ['title' => 'Boundary polygon in details',    'detail' => 'Item details page shows a "Boundary" row with a link to the map and a collapsible raw GeoJSON view.'],
            ['title' => 'Logo MIME detection fix',        'detail' => 'Logo uploads now accept image/x-png, text/xml (SVG), and other finfo variants — uploads no longer rejected for valid files.'],
            ['title' => 'Configurable boundary types',    'detail' => 'Settings → General lets you choose which item types can draw polygon boundaries on the map.'],
            ['title' => 'Activity log popup',             'detail' => 'Click any activity log row to open a full-detail overlay with dark backdrop. Shows action, date, full description, and attached photo.'],
        ],
    ],

    '1.9.5' => [
        'status'   => 'released',
        'released' => '2026-04-14',
        'title'    => 'AI Setup Guide & Nav Logo Pair',
        'features' => [
            ['title' => 'Ollama setup guide in Settings', 'detail' => 'Step-by-step instructions: install Ollama, pull vision model, start server, plus collapsible guide for importing custom Hugging Face .gguf models.'],
            ['title' => 'Nav icon + wordmark pair', 'detail' => 'Top nav shows icon and horizontal wordmark side by side when both are uploaded.'],
        ],
    ],

    '1.9.4' => [
        'status'   => 'released',
        'released' => '2026-04-14',
        'title'    => 'Nav: Icon + Wordmark Side by Side',
        'features' => [
            ['title' => 'Icon + wordmark in nav', 'detail' => 'When both icon-light and horizontal-light logos are uploaded, they are displayed side by side in the top nav bar.'],
        ],
    ],

    '1.9.3' => [
        'status'   => 'released',
        'released' => '2026-04-14',
        'title'    => '4-Slot Logos & Family Needs Units',
        'features' => [
            ['title' => '4-slot logo uploads',       'detail' => 'Upload icon (square) and horizontal (wide) logos each in light and dark variants. Nav automatically picks the right one.'],
            ['title' => 'Family Needs unit selector', 'detail' => 'Choose the unit for yearly family needs: kg, g, units, heads, bunches, litres, jars, or other.'],
        ],
    ],

    '1.9.1' => [
        'status'   => 'released',
        'released' => '2026-04-14',
        'title'    => 'AI Photo Seed Identification',
        'features' => [
            ['title' => 'AI photo seed ID', 'detail' => 'Take or pick a photo of seeds or a seed packet — Ollama vision model identifies the plant and pre-fills the entire Add Seed form. Configurable endpoint and model in Settings.'],
        ],
    ],

    '1.9.0' => [
        'status'   => 'released',
        'released' => '2026-04-14',
        'title'    => 'Seeds Catalog & Mobile Nav Update',
        'features' => [
            ['title' => 'Seeds Catalog',         'detail' => 'Full CRUD for seed varieties with growing info, companion planting, planting/harvest month calendars, and per-variety stock tracking.'],
            ['title' => 'Bed Row Planner',        'detail' => 'Plan rows per item (bed/garden) by season with seed, plant count, sowing date, spacing, and status tracking.'],
            ['title' => 'Family Needs Planner',   'detail' => 'Record yearly vegetable/food consumption targets with priority ranking linked to the seed catalog.'],
            ['title' => 'Seeds in bottom nav',    'detail' => '🌱 Seeds replaces Home in the mobile bottom navigation bar. Dashboard reachable by tapping the logo.'],
            ['title' => 'Custom nav logo upload', 'detail' => 'Upload a PNG/JPG/WebP/SVG logo via Settings → General to replace the default "🌿 Rooted" branding.'],
            ['title' => '"Other" log action type','detail' => 'Choose "Other…" in the item activity log form and type a custom action label — fully dynamic, no page reload.'],
            ['title' => 'Delete from edit page',  'detail' => 'A danger-zone delete form is now shown at the bottom of every item edit page.'],
            ['title' => 'Map mobile height fix',  'detail' => 'Map now uses position:fixed to fill the viewport between top and bottom navbars — no more overflow on mobile.'],
        ],
    ],

    '1.0.0' => [
        'status'   => 'released',
        'released' => '2026-03-31',
        'title'    => 'Initial Release',
        'features' => [
            ['title' => 'Install wizard & one-click setup',           'detail' => 'Five-step installer with environment checks, DB creation, and admin account.'],
            ['title' => 'Item management (13 types)',                 'detail' => 'Create, edit, archive olive trees, almond trees, vines, trees, gardens, beds, lines, orchards, prep zones, mobile coops, buildings, water points, zones.'],
            ['title' => 'GPS coordinate per item',                    'detail' => 'Latitude / longitude stored per item with source tracking (device, manual, corrected).'],
            ['title' => 'Item metadata',                              'detail' => 'Variety, latin name, estimated age, soil type, irrigation type, sun exposure, bed dimensions, garden area, etc.'],
            ['title' => 'Activity log',                               'detail' => 'Append-only log of all actions performed across all items.'],
            ['title' => 'Reminders',                                  'detail' => 'Date-based reminders linked optionally to items. Pending / completed / dismissed states.'],
            ['title' => 'Harvest entries',                            'detail' => 'Record harvest quantities with unit, grade, type, and notes.'],
            ['title' => 'Finance entries',                            'detail' => 'Track costs, revenues, and market references per item.'],
            ['title' => 'Error & application logging',                'detail' => 'All PHP errors and exceptions captured with severity levels and stack traces.'],
            ['title' => 'Settings (land name, currency, timezone)',   'detail' => 'General preferences stored in the settings table.'],
            ['title' => 'In-browser upgrade system',                  'detail' => 'Upload a ZIP to update the application without server access.'],
        ],
    ],

    '1.1.0' => [
        'status'   => 'released',
        'released' => '2026-04-01',
        'title'    => 'Interactive Land Map',
        'features' => [
            ['title' => 'Interactive satellite map (Leaflet + Esri)', 'detail' => 'Full-screen Leaflet map with Esri World Imagery satellite tiles and OSM road label overlay.'],
            ['title' => 'Item pins with type icons',                  'detail' => 'Each item type has a colour-coded emoji pin placed at its GPS coordinates.'],
            ['title' => 'GPS accuracy circles',                       'detail' => 'Faint halo around each pin showing the GPS measurement precision radius.'],
            ['title' => 'Land boundary polygon',                      'detail' => 'Draw the outer limit of your property once and it renders as a dashed green border on every map load.'],
            ['title' => 'Item boundary polygons',                     'detail' => 'Draw precise boundaries for zones, orchards, gardens, and beds directly on the map.'],
            ['title' => 'Boundary point-click correction',            'detail' => 'While drawing, click any existing point to remove it and all subsequent points — undo mid-draw.'],
            ['title' => 'Add item from map',                          'detail' => 'Tap the map to place a pin, fill in type + name in a slide-up sheet, save directly — no page reload.'],
            ['title' => 'GPS detect on item forms',                   'detail' => 'One-tap GPS detection on the create/edit form, with inline status and accuracy feedback.'],
            ['title' => 'GPS detect on boundary draw & add-item',     'detail' => 'Walk your land and tap "Add GPS Point" to record your position as a boundary vertex.'],
            ['title' => 'Mobile-first item form',                     'detail' => 'Map first, large touch targets, GPS coords pill, full-width submit button.'],
            ['title' => 'Full-height mobile navigation drawer',       'detail' => 'Slide-in overlay nav with large touch targets, backdrop close, and keyboard dismiss.'],
            ['title' => 'Changelog & upgrade history',                'detail' => 'Settings → Upgrade shows current version, full changelog, and past upgrade dates.'],
        ],
    ],

    '1.2.0' => [
        'status'   => 'released',
        'released' => '2026-04-01',
        'title'    => 'Photos, Actions & Calendar',
        'features' => [
            ['title' => 'Photo uploads per item',                     'detail' => 'Upload identification photos, N/S/E/W yearly directional shots, harvest photos, and general attachments. Stored in storage/uploads/.'],
            ['title' => 'Action logging UI',                          'detail' => 'Log pruning, treatment, amendment, planting, harvest, move, maintenance, and note actions per item with date and description.'],
            ['title' => 'Item action history timeline',               'detail' => 'Per-item timeline view of all logged actions, filterable by type.'],
            ['title' => 'Harvest log UI',                             'detail' => 'Full harvest entry form with quantity, unit (kg/L/wheelbarrow), quality grade, type, and notes.'],
            ['title' => 'Finance dashboard per item',                 'detail' => 'Per-item income/expense breakdown with totals and year filter.'],
            ['title' => 'Item parent/child hierarchy',                'detail' => 'Assign a tree to an orchard, a bed to a garden — breadcrumb navigation and grouped list views.'],
            ['title' => 'Item show page mini-map',                    'detail' => 'Embedded mini satellite map on each item\'s detail page showing its pin and boundary.'],
            ['title' => 'Nearby items feature',                       'detail' => 'From any item, list all other items within a configurable radius. Useful for linked prep zones.'],
            ['title' => 'Reminder detail & edit',                     'detail' => 'View, edit, reschedule, or dismiss a reminder from its own page.'],
            ['title' => 'Google Calendar sync',                       'detail' => 'Connect your Google account to automatically create calendar events for every reminder.'],
            ['title' => 'Map item clustering',                        'detail' => 'Cluster dense item pins at low zoom levels to keep the map readable on large properties.'],
        ],
    ],

    '1.3.0' => [
        'status'   => 'released',
        'released' => '2026-04-02',
        'title'    => 'Mobile UX, Map & UI Revamp',
        'features' => [
            ['title' => 'Tree type as meta field',                    'detail' => 'Select from 19 Sicily-native tree species (fig, carob, lemon, orange, mandarin, pistachio, pomegranate, cherry, prickly pear, peach, apricot, mulberry, hazelnut, walnut, medlar, olive, almond, vine, other) as a meta field on the generic Tree type.'],
            ['title' => 'Quick harvest entry from dashboard',         'detail' => 'Record harvests for any tree directly from the dashboard without navigating to the item page.'],
            ['title' => 'Per-item photo management page',             'detail' => 'Dedicated mobile-friendly grid for uploading and viewing directional photos (N/S/E/W + ID + harvest).'],
            ['title' => 'Roadmap 3-state toggle',                     'detail' => 'Track your own roadmap progress: none → done (✅) → problem (❌) — state persisted in browser localStorage.'],
            ['title' => 'Dashboard revamp with harvest chart',        'detail' => 'Monthly harvest bar chart, quick action strip, emoji icons per item type, lively card design.'],
            ['title' => 'Fullscreen map on mobile',                   'detail' => 'Map fills the full screen on mobile with compact floating toolbar. Sidebar collapses to a scrollable strip.'],
            ['title' => 'Map direct-tap safeguard',                   'detail' => 'Tapping near controls or when sheet is open no longer triggers accidental item creation.'],
            ['title' => 'Map layer toggle fix',                       'detail' => 'Show/Hide All layer toggle now correctly controls all individual type checkboxes.'],
            ['title' => 'Settings page layout fix',                   'detail' => 'Tab navigation and form content were side by side — now correctly stacked.'],
            ['title' => 'Mobile navigation fix',                      'detail' => 'Nav overlay backdrop added, z-index corrected — menu now appears above page content.'],
            ['title' => 'GPS HTTPS detection',                        'detail' => 'GPS detect button now checks for HTTPS and shows a clear message if secure connection is required.'],
        ],
    ],

    '1.8.6' => [
        'status'   => 'released',
        'released' => '2026-04-14',
        'title'    => 'Zone Polygon Toggle & Mobile Item Panel',
        'features' => [
            ['title' => 'mobile_coop and building now drawable',  'detail' => 'Mobile coop and building type items can now have polygon boundaries on the map, same as garden, bed, zone, etc.'],
            ['title' => 'Auto-open sidebar on item tap (mobile)', 'detail' => 'Tapping any item marker on mobile now automatically opens the sidebar so the item info panel is always readable.'],
            ['title' => 'Polygon status indicator',               'detail' => '"⬡ Polygon saved" or "No polygon yet" label in the item info panel, with context-aware draw/edit button.'],
        ],
    ],

    '1.8.5' => [
        'status'   => 'released',
        'released' => '2026-04-14',
        'title'    => 'Gallery Lightbox',
        'features' => [
            ['title' => 'Full-screen photo gallery',           'detail' => 'Tapping any image on the item detail page opens a full-screen lightbox showing all item photos in sequence.'],
            ['title' => 'Swipe and keyboard navigation',       'detail' => 'Navigate photos by swiping left/right on mobile or using the arrow keys on desktop. Escape closes the gallery.'],
            ['title' => 'Looping navigation',                  'detail' => 'Gallery loops continuously — going past the last photo wraps back to the first and vice versa.'],
            ['title' => 'Photo counter and caption',           'detail' => 'Counter (e.g. "3 / 7") and the photo caption are displayed at the bottom of the lightbox.'],
            ['title' => 'Hero image clickable',                'detail' => 'The large hero/identification photo at the top of the item page is also clickable and opens the gallery at the correct index.'],
        ],
    ],

    '1.8.4' => [
        'status'   => 'released',
        'released' => '2026-04-14',
        'title'    => 'Map Polygon Drawing Overhaul',
        'features' => [
            ['title' => 'Finish Polygon button',           'detail' => 'Explicit "Finish Polygon" button closes the shape without relying on double-click, which is unreliable on mobile.'],
            ['title' => 'Auto-open sidebar on draw mode',  'detail' => 'Activating draw mode on mobile now opens the sidebar automatically so GPS, Finish, Save, and Cancel buttons are visible.'],
            ['title' => 'Live closing-line preview',       'detail' => 'A faint dashed line connects the last placed point back to the first as you draw, showing the shape before it is closed.'],
            ['title' => 'GPS-assisted polygon on mobile',  'detail' => 'Walk to each corner of a zone or your land boundary, tap Add GPS Point, move, repeat, then tap Finish Polygon.'],
        ],
    ],

    '1.8.3' => [
        'status'   => 'released',
        'released' => '2026-04-14',
        'title'    => 'Icon Alpha Fix, Auto-Sync, Distance Sort & Caption UI',
        'features' => [
            ['title' => 'Transparent logo support for PWA icons', 'detail' => 'Fixed alpha channel corruption when uploading logos with transparent backgrounds — images are now resized with blending off throughout the pipeline.'],
            ['title' => 'Automatic Google Calendar sync',         'detail' => 'Reminders are pushed to Google Calendar automatically when the Reminders page is opened — no need to visit Settings → Calendar to sync.'],
            ['title' => 'Distance sort as default on items list', 'detail' => 'Items page requests GPS on load and sorts items nearest-first automatically. Falls back to name order if location is unavailable.'],
            ['title' => 'Improved caption editor',               'detail' => 'Save/Cancel buttons now appear below the caption input with larger touch targets, making it easier to edit captions on mobile.'],
            ['title' => 'Browser asset caching',                 'detail' => 'CSS, JS, and image files now get 1-year browser cache headers, reducing page load time on repeat visits.'],
        ],
    ],

    '1.8.2' => [
        'status'   => 'released',
        'released' => '2026-04-14',
        'title'    => 'PWA Manifest Fix, Dynamic Paths & Install Prompt',
        'features' => [
            ['title' => 'Dynamic manifest.json with correct paths',  'detail' => 'manifest.json is now served by PHP at runtime so start_url and icon paths always include the correct subdirectory prefix — fixes install prompt on subdirectory installs like /rooted/.'],
            ['title' => 'Subdirectory-aware service worker',         'detail' => 'Service worker derives its base path from its own URL, so asset caching and offline mode work correctly on all installs.'],
            ['title' => 'Platform-aware install prompt',             'detail' => 'Install App section detects iOS (shows share-sheet steps), Chrome/Edge/Android (shows install button), and unsupported browsers (shows browser menu guidance).'],
            ['title' => 'Better icon quality',                       'detail' => 'Icon resize uses multi-step downsampling and center-crops non-square sources for sharper results.'],
        ],
    ],

    '1.8.1' => [
        'status'   => 'released',
        'released' => '2026-04-14',
        'title'    => 'Settings CSS Fix & PWA Icon Upload Fix',
        'features' => [
            ['title' => 'Settings tabs all properly styled', 'detail' => 'Shared settings CSS moved to admin.css — PWA, Weather, Harvest, Action Types tabs now render correctly.'],
            ['title' => 'PWA icon upload fixed',             'detail' => 'Icon upload no longer crashes. Uses finfo for MIME detection, checks directory writability, guards WebP support, and shows real error messages on failure.'],
        ],
    ],

    '1.8.0' => [
        'status'   => 'released',
        'released' => '2026-04-14',
        'title'    => 'Log Photos & AI Prompt Image URLs',
        'features' => [
            ['title' => 'Attach photo to log entry',         'detail' => 'When logging an action, tick "Attach a photo to this log" to upload an image that is permanently linked to that log entry. Preview shown before submit.'],
            ['title' => 'Log photo thumbnails',              'detail' => 'Photos linked to log entries appear as thumbnails in the Activity Log table and in the Recent Activity feed on the item detail page.'],
            ['title' => 'AI Prompt with full image URLs',    'detail' => 'The Copy AI Prompt button now includes a PHOTOS section with direct download URLs for all item photos (with category and caption), and appends [photo: URL] inline on any log entry that has a linked image. Any AI model that supports image URLs can fetch and analyze the photos.'],
        ],
    ],

    '1.7.0' => [
        'status'   => 'released',
        'released' => '2026-04-14',
        'title'    => 'Fused Log+Reminder, Photo Captions & Google Calendar Auto-Push',
        'features' => [
            ['title' => 'Fused Log + Reminder form',                    'detail' => 'Log Action and Reminder merged into one form on the item detail page. Tick "Set a reminder for this log" to attach a reminder inline without leaving the form.'],
            ['title' => 'Inline calendar picker',                       'detail' => 'Full month-grid calendar widget for selecting reminder dates — no native datetime input. Mo–Su headers, today/selected/past highlighting. Default = today + 7 days.'],
            ['title' => 'Photo captions',                               'detail' => 'Each photo has an optional caption/legend. Editable at upload and inline in the gallery (tap-to-edit, same UX as category). Stored in a new caption column on the attachments table.'],
            ['title' => 'Dynamic "Other…" photo category',              'detail' => 'Selecting Other… in the category dropdown reveals a free-text input. Any custom category name is stored and listed dynamically from existing DB values.'],
            ['title' => 'Google Calendar auto-push',                    'detail' => 'Reminders are pushed to Google Calendar immediately on creation — from the Log Action form or the Reminders page. No manual Sync Now needed.'],
            ['title' => 'Bottom nav: Items replaces Photos',            'detail' => 'Bottom nav now shows Items (🌿) instead of Photos (📷) — Photos always required an item selection first; Items is a more useful quick-nav destination.'],
        ],
    ],

    '1.6.0' => [
        'status'   => 'released',
        'released' => '2026-04-10',
        'title'    => 'PWA, Brand Identity & Offline Support',
        'features' => [
            ['title' => 'Full PWA — installable home-screen app',         'detail' => 'Service worker v2, manifest.json, install prompt. Works on Android (Chrome/Edge/Samsung) and iOS (Add to Home Screen).'],
            ['title' => 'PWA Settings page',                               'detail' => 'Settings → PWA: enable/disable, app name, short name, description, theme color, background color, display mode, orientation, start URL. Saves and regenerates manifest.json on the fly.'],
            ['title' => 'Icon upload & auto-generation',                   'detail' => 'Upload any PNG/JPG source image (≥512px); Rooted resizes it to 512, 192, 180 (Apple touch), and 32px using PHP GD.'],
            ['title' => 'Default branded icons (Deep Moss + tree)',        'detail' => 'Pre-generated icons using the Rooted brand: Deep Moss rounded-square background, hexagon geometric frame, white tree silhouette with roots and leaf detail.'],
            ['title' => 'Dedicated offline page',                          'detail' => 'Branded /offline page with retry button. Pre-cached by service worker. Shown when navigation fails offline instead of showing a stale /dashboard.'],
            ['title' => 'New brand palette — Minimalist Earth',            'detail' => 'CSS custom properties updated: Deep Moss (#29402B) primary, Umber Earth (#A66141) accent, Slate Grey (#637380) muted, warm cream (#F5F0EA) surfaces.'],
            ['title' => 'Apple PWA meta tags',                             'detail' => 'apple-touch-icon, apple-mobile-web-app-capable, apple-mobile-web-app-status-bar-style, apple-mobile-web-app-title added to the main layout for iOS add-to-home-screen support.'],
        ],
    ],

    '1.5.3' => [
        'status'   => 'released',
        'released' => '2026-04-10',
        'title'    => 'Dashboard Section Order & 3-Day Forecast',
        'features' => [
            ['title' => '3-day weather forecast',          'detail' => 'Daily forecast strip now shows tomorrow + 2 more days (4 forecast days total fetched from Open-Meteo).'],
            ['title' => 'Upcoming Reminders repositioned', 'detail' => 'Upcoming Reminders section moved directly under the Lunar Calendar, above Nearest to You.'],
        ],
    ],

    '1.5.2' => [
        'status'   => 'released',
        'released' => '2026-04-10',
        'title'    => 'Dashboard Layout Refinements',
        'features' => [
            ['title' => 'Weather widget no background card',        'detail' => 'Weather info now renders transparently below the greeting — no dark box, feels like one unified section with the welcome header.'],
            ['title' => 'Nearby card: photo badge beside 📷 button','detail' => 'When an ID photo exists, the round badge appears to the left of the three action buttons. Camera icon is always visible and tappable. No emoji placeholder when no photo.'],
            ['title' => 'Lunar calendar moved up',                  'detail' => 'Lunar Garden Calendar now appears immediately after the welcome/weather section, before Nearest to You.'],
        ],
    ],

    '1.5.1' => [
        'status'   => 'released',
        'released' => '2026-04-10',
        'title'    => 'Welcome Greeting, Quote of the Day & Weather City',
        'features' => [
            ['title' => 'Welcome greeting with owner name',    'detail' => '"Ciao, Max!" personalised header at the top of the dashboard, configurable in General Settings.'],
            ['title' => 'Quote of the day',                    'detail' => 'Inspirational quote displayed below the greeting (2-line max, italic). Powered by ZenQuotes API, 24-hour DB cache. API URL configurable in settings.'],
            ['title' => 'Weather city name',                   'detail' => 'City/location label shown inside the weather widget. Set in Settings → Weather.'],
            ['title' => '2-day daily forecast in weather strip','detail' => 'Tomorrow and the day after shown in the hourly row with a different background shade, max and min temperatures.'],
            ['title' => 'Add Item removed from dashboard top', 'detail' => 'Removed the large Add Item button — it duplicates the bottom nav shortcut. Weather widget now spans full width.'],
        ],
    ],

    '1.5.0' => [
        'status'   => 'released',
        'released' => '2026-04-10',
        'title'    => 'Weather Widget & Nearby Card Photo Badge',
        'features' => [
            ['title' => 'Live weather widget on dashboard',   'detail' => 'Current temperature, weather icon, description, humidity, atmospheric pressure, sunset time, and 4-hour forecast strip displayed in the dashboard top strip. Powered by Open-Meteo (free, no API key) or your own weather station JSON feed.'],
            ['title' => 'Dashboard top strip layout',         'detail' => 'Add Item button and weather widget share a 50/50 grid in the dashboard header — visible immediately when you open the app.'],
            ['title' => 'Weather settings page',              'detail' => 'Settings → Weather: enable/disable widget, configure latitude/longitude, link a personal weather station, and set the external forecast URL. Weather cache cleared on save.'],
            ['title' => 'Nearby card photo badge repositioned','detail' => 'ID photo badge (48px) moved from absolute corner into the action button row as the leftmost item — visually aligned with the 📷 🌾 ➕ buttons at the same height.'],
        ],
    ],

    '1.4.27' => [
        'status'   => 'released',
        'released' => '2026-04-10',
        'title'    => 'Dashboard Card Photo Badge Bottom-Left of Card',
        'features' => [
            ['title' => 'Photo badge repositioned', 'detail' => 'Circular ID photo badge (36px) now sits at the absolute bottom-left corner of the nearest-item card with 10px margin — easy to see over the full-photo card background.'],
        ],
    ],

    '1.4.26' => [
        'status'   => 'released',
        'released' => '2026-04-09',
        'title'    => 'Map Attribution Z-Index, Fullscreen Restored & Lunar Spacing',
        'features' => [
            ['title' => 'Map attribution z-index fix', 'detail' => 'main-content--map is now a flex column; #mapWrap uses flex:1 so its height is exactly the remaining space — Leaflet attribution stays inside the map, never over the nav.'],
            ['title' => 'Fullscreen button restored',  'detail' => 'Fullscreen button visible again on mobile.'],
            ['title' => 'Lunar week spacing',          'detail' => '10px margin between moon emoji and day-type emoji in the week strip.'],
        ],
    ],

    '1.4.25' => [
        'status'   => 'released',
        'released' => '2026-04-09',
        'title'    => 'Map Overflow Fix & Layers Toggle',
        'features' => [
            ['title' => 'Layers/filter panel toggle', 'detail' => 'New ☰ Layers button in the map header collapses or expands the sidebar. Sidebar starts collapsed on mobile to give the map more space.'],
            ['title' => 'Map header overflow fix',    'detail' => 'Mobile map header now shows icon-only buttons — no more horizontal page scroll. body overflow-x:hidden added as global guard.'],
        ],
    ],

    '1.4.24' => [
        'status'   => 'released',
        'released' => '2026-04-09',
        'title'    => 'Lunar Calendar Dark Mode & Spacing',
        'features' => [
            ['title' => 'Lunar section dark mode', 'detail' => 'Deep green-black (#1a2e1f) background with frosted-glass today card and dark week strip — clearly separated from the light dashboard.'],
        ],
    ],

    '1.4.23' => [
        'status'   => 'released',
        'released' => '2026-04-09',
        'title'    => 'Dashboard Card Photo BG & Badge Position Fix',
        'features' => [
            ['title' => 'Dashboard card photo background', 'detail' => 'ID photo used as full cover background on nearest cards when available. Emoji icon + gradient overlay still legible on top.'],
            ['title' => 'Badge moved to bottom-left',      'detail' => 'Circular photo badge on the emoji avatar repositioned to bottom-left corner.'],
        ],
    ],

    '1.4.22' => [
        'status'   => 'released',
        'released' => '2026-04-09',
        'title'    => 'Photo Icons, Hero Cover, Dashboard Badge & Lunar Calendar',
        'features' => [
            ['title' => 'Lunar biodynamic calendar',         'detail' => 'Moon phase, zodiac sign, and day type (Root/Leaf/Flower/Fruit) for today plus a 7-day week strip. Calculated in PHP using astronomical algorithms — no external API.'],
            ['title' => 'Items list photo icons',            'detail' => 'Left type icon replaced with the actual ID photo (cover-fit rounded square) when one exists — each row is instantly recognizable.'],
            ['title' => 'Item detail hero full-cover',       'detail' => 'Hero image reverted to object-fit:cover (300px fixed height). Small circular avatar above the type badge for quick ID alongside the title.'],
            ['title' => 'Dashboard nearest card photo badge','detail' => 'Emoji always visible in the 52px avatar; ID photo shown as a 26px circular badge at bottom-right corner — type and photo both visible.'],
            ['title' => 'Map/mini-map nav overlap fixes',    'detail' => 'mapWrap height on mobile accounts for bottom nav; mini map uses isolation:isolate to contain Leaflet z-indexes.'],
        ],
    ],

    '1.4.21' => [
        'status'   => 'released',
        'released' => '2026-04-09',
        'title'    => 'Map Nav Fix, List Photo Fit & Dashboard Avatar Photos',
        'features' => [
            ['title' => 'Map navigation z-index fix',           'detail' => 'Bottom nav bar z-index raised to 1000 — sits above all Leaflet panes and controls, no more map overlapping the menu.'],
            ['title' => 'Items list photo fit',                 'detail' => 'Background-size changed to auto 100% — scales image to row height so 40–50% of the photo is visible as a recognizable thumbnail on the right.'],
            ['title' => 'Dashboard nearest cards avatar photo', 'detail' => 'Photo shown as circular avatar (52×52, object-fit:cover) in the icon circle instead of a zoomed full-card background — instantly recognizable, not decorative.'],
        ],
    ],

    '1.4.20' => [
        'status'   => 'released',
        'released' => '2026-04-09',
        'title'    => 'ID Photos in Items List & Zoom Fix Everywhere',
        'features' => [
            ['title' => 'Items list ID photo backgrounds',      'detail' => 'Each item row shows its identification photo on the right with a gradient overlay so name/type remain readable.'],
            ['title' => 'Photo zoom fix — dashboard cards',     'detail' => 'background-size: 100% auto anchored top — full width visible, no lateral crop. Cards now 180px tall.'],
            ['title' => 'Photo zoom fix — item detail hero',    'detail' => 'Hero switches to object-fit: contain with dark background — complete tree visible, no crop.'],
        ],
    ],

    '1.4.19' => [
        'status'   => 'released',
        'released' => '2026-04-08',
        'title'    => 'Photos Fix, Custom Tree Types, Log Delete & Dashboard Photo',
        'features' => [
            ['title' => 'Custom tree types',               'detail' => 'Select Other on Add Item, enter a name, save via AJAX. Persists for all future items in the tree_type dropdown.'],
            ['title' => 'Activity log delete',             'detail' => 'Tap ✕ on any log entry to remove it with inline Yes/No confirm.'],
            ['title' => 'Photos page fully fixed',         'detail' => 'Fixed wrong variable passed to view and status=NULL exclusion — all existing photos now show correctly.'],
            ['title' => 'Dashboard photo improvement',     'detail' => 'Taller cards (170px), top-center anchor — shows much more of the tree, not just a zoomed-in crop.'],
            ['title' => 'Treatment photo category',        'detail' => 'New 💊 Treatment category in the photo upload and category-change dropdowns.'],
        ],
    ],

    '1.4.18' => [
        'status'   => 'released',
        'released' => '2026-04-06',
        'title'    => 'Harvest Settings, Photos Gallery Fix & Action Types Mobile',
        'features' => [
            ['title' => 'Harvest Settings page',                    'detail' => 'Settings → Harvest: enable/disable harvest per type, set max per year, unit label, slider max and step. Drives Quick Harvest immediately.'],
            ['title' => 'Photos gallery fixed',                     'detail' => 'Fixed a bug where the photos page showed "No photos yet" even when the item had existing photos.'],
            ['title' => 'Action Types mobile scroll fix',           'detail' => 'Action types table was overflowing on mobile — now horizontally scrollable with a clear page description.'],
        ],
    ],

    '1.4.17' => [
        'status'   => 'released',
        'released' => '2026-04-06',
        'title'    => 'Photo Edit & Delete',
        'features' => [
            ['title' => 'Inline category edit on photos',       'detail' => 'Tap the category label on any photo to change it. Saves via AJAX with no page reload.'],
            ['title' => 'PWA-safe photo delete confirm',        'detail' => 'Replaced window.confirm() with inline Yes/No buttons — works in standalone PWA mode.'],
        ],
    ],

    '1.4.16' => [
        'status'   => 'released',
        'released' => '2026-04-05',
        'title'    => 'Reminder Item Association',
        'features' => [
            ['title' => 'GPS-sorted item picker on reminder form', 'detail' => 'Items sorted nearest first as GPS fixes arrive. Re-sorts on accuracy improvement.'],
            ['title' => 'Item name badge on item-detail reminder form', 'detail' => 'Visually confirms which item the reminder is being added to.'],
        ],
    ],

    '1.4.15' => [
        'status'   => 'released',
        'released' => '2026-04-05',
        'title'    => 'Item Detail Redesign',
        'features' => [
            ['title' => '4-button quick action strip',  'detail' => 'Add Photo, Reminder, Note, and AI prompt — always one tap away at the top of every item.'],
            ['title' => 'Photo preview masonry strip',  'detail' => 'Latest photos shown inline with See all link. No tab-switching needed.'],
            ['title' => 'Recent activity feed',         'detail' => 'Last 3 log entries and pending reminders with inline Done button.'],
        ],
    ],

    '1.4.14' => [
        'status'   => 'released',
        'released' => '2026-04-05',
        'title'    => 'Photo Upload Polish, Harvest Scale & Reminder Fix',
        'features' => [
            ['title' => 'Single "Add Photo" button — no double upload', 'detail' => 'Native OS picker gives camera + gallery + files in one tap. Removes window.focus hack and the double-upload race condition.'],
            ['title' => 'Real progress bar on photo upload',             'detail' => 'Fill bar shows XHR upload percentage. No more spinning circle with no feedback.'],
            ['title' => 'Per-type harvest slider range',                 'detail' => 'Olive 0–2 baskets, almond 0–5 wheelbarrows, vine 0–50 kg, tree 0–20 kg.'],
            ['title' => 'Reminder quick-action 404 fixed',              'detail' => '/reminders/create did not exist; link now goes to /reminders.'],
        ],
    ],

    '1.4.13' => [
        'status'   => 'released',
        'released' => '2026-04-05',
        'title'    => 'GPS Accuracy Auto-Refresh',
        'features' => [
            ['title' => 'Auto-refresh nearest items as GPS improves',  'detail' => 'Dashboard, Quick Harvest, and Items list all re-sort automatically when location accuracy gains ≥30%.'],
            ['title' => 'RootedGPS.onAccuracyImprove(cb)',             'detail' => 'New GPS service method — register any callback to run whenever the device GPS signal meaningfully sharpens.'],
        ],
    ],

    '1.4.12' => [
        'status'   => 'released',
        'released' => '2026-04-05',
        'title'    => 'Better Wheelbarrow, Camera Fix, Photos Split Buttons',
        'features' => [
            ['title' => 'Wheelbarrow SVG redrawn',                      'detail' => 'Colored filled tray (amber/brown), gray spoked wheel, wood handles, support leg.'],
            ['title' => 'Android camera change event fix',               'detail' => 'window.focus fallback triggers upload when Android does not fire change event after camera confirm.'],
            ['title' => 'Camera + Gallery split buttons on item Photos', 'detail' => 'Item Photos page now matches Quick Photos with dedicated Camera and Gallery inputs.'],
        ],
    ],

    '1.4.11' => [
        'status'   => 'released',
        'released' => '2026-04-05',
        'title'    => 'Photo Upload Fix & Camera+Gallery Buttons',
        'features' => [
            ['title' => 'Camera + Gallery buttons on Quick Photos', 'detail' => 'Two explicit buttons instead of one ambiguous tap zone.'],
            ['title' => 'Real upload error messages',               'detail' => 'DB errors, missing finfo extension, unwritable folder now surface the actual error text.'],
            ['title' => '.user.ini for PHP-FPM upload limits',      'detail' => 'htaccess php_value is silently ignored on PHP-FPM. .user.ini sets 25M upload limit correctly.'],
        ],
    ],

    '1.4.10' => [
        'status'   => 'released',
        'released' => '2026-04-04',
        'title'    => 'Nav Drawer Fix & Wheelbarrow Icon',
        'features' => [
            ['title' => 'Mobile nav drawer — complete rewrite', 'detail' => 'Drawer moved outside <nav> element. z-index:9999 in root stacking context. No more CSS stacking context trap.'],
            ['title' => 'Wheelbarrow SVG icon for almond harvest', 'detail' => 'Custom SVG wheelbarrow replaces bucket emoji.'],
        ],
    ],

    '1.4.9' => [
        'status'   => 'released',
        'released' => '2026-04-04',
        'title'    => 'jQuery Load Order Fix',
        'features' => [
            ['title' => 'Locate Me button fixed on Add/Edit Item', 'detail' => 'jQuery was loaded after page content — $() calls in inline scripts failed silently. Moved jQuery before content so all GPS buttons work correctly.'],
        ],
    ],

    '1.4.8' => [
        'status'   => 'released',
        'released' => '2026-04-04',
        'title'    => 'AJAX Error Handling & Upload Diagnostics',
        'features' => [
            ['title' => 'AJAX-aware error responses', 'detail' => 'Exception handler and Router return JSON instead of HTML for AJAX requests — upload errors now show real messages.'],
            ['title' => 'Almond harvest unit fix',    'detail' => 'Changed "wheelbarrows" → "buckets" to match the 🪣 bucket icon.'],
        ],
    ],

    '1.4.7' => [
        'status'   => 'released',
        'released' => '2026-04-04',
        'title'    => 'Harvest Delete Inline Confirm',
        'features' => [
            ['title' => 'Harvest delete confirmation', 'detail' => 'Replaced window.confirm() with inline Yes/No buttons — works in all contexts including PWA standalone mode.'],
        ],
    ],

    '1.4.6' => [
        'status'   => 'released',
        'released' => '2026-04-04',
        'title'    => 'Nav Fix, Photo Upload & CSRF JSON',
        'features' => [
            ['title' => 'Nav menu drawer visible again',       'detail' => 'Nav z-index raised above overlay — menu items no longer hidden behind the backdrop when hamburger is opened.'],
            ['title' => 'Photo upload AJAX error messages',    'detail' => 'CSRF validation returns JSON for AJAX requests — upload errors now display the real message instead of "Something went wrong".'],
            ['title' => 'Reliable file input on all phones',   'detail' => 'Removed capture="environment" from file inputs on both Quick Photos and item Photos pages — change event fires correctly on all mobile browsers.'],
        ],
    ],

    '1.4.5' => [
        'status'   => 'released',
        'released' => '2026-04-04',
        'title'    => 'GPS Reliability, Photos & Harvest Overhaul',
        'features' => [
            ['title' => 'GPS instant response everywhere',    'detail' => 'All GPS buttons (Locate Me, Detect GPS, Nearest to You, Harvest/Photo sort) use cached position immediately. No waiting.'],
            ['title' => 'Harvest year limit enforcement',     'detail' => 'Each item type has a max harvests per year (default 1). Quick harvest page shows count badge and locks the button when maxed.'],
            ['title' => 'Harvest year entries with delete',   'detail' => 'Quick harvest page lists all this-year entries per item. One-tap delete per entry.'],
            ['title' => 'Quick Photos reliable upload',       'detail' => 'Removed capture="environment" — native media picker fires change event reliably on all devices. Auto-uploads on selection.'],
            ['title' => 'Harvest page stays on harvest page', 'detail' => 'After saving or deleting a harvest, page redirects back to Quick Harvest instead of the item detail page.'],
        ],
    ],

    '1.4.4' => [
        'status'   => 'released',
        'released' => '2026-04-04',
        'title'    => 'AI Prompt, Map Fix & UX Polish',
        'features' => [
            ['title' => 'AI Prompt button on item pages',    'detail' => 'Tap 🤖 AI Prompt on any item to copy a full history context block to clipboard — paste into any AI chatbot for instant agronomic advice.'],
            ['title' => 'Map full-screen fix on phones',     'detail' => 'Mobile breakpoint raised from 768px to 900px — sidebar now correctly stacks below the map on all phones.'],
            ['title' => 'Quick Photos two-step upload',      'detail' => 'Choose photo first, then tap Upload — removes silent auto-upload confusion.'],
            ['title' => 'Nearest to You always visible',     'detail' => 'Dashboard section always shows, with helpful guidance when GPS or item coordinates are missing.'],
            ['title' => 'Nav hamburger blur fix',            'detail' => 'Removed backdrop-filter from overlay — CSS stacking context was hiding the drawer behind the blur.'],
        ],
    ],

    '1.4.3' => [
        'status'   => 'released',
        'released' => '2026-04-03',
        'title'    => 'Bottom Nav, Quick Photos & Bug Fixes',
        'features' => [
            ['title' => 'Bottom nav: Harvest + Photos quick access', 'detail' => 'Home · Map · + · Harvest · Photos. Both Harvest and Photos open quick-mode pages with nearest trees first.'],
            ['title' => 'Quick Photos page',                         'detail' => 'New /photos/quick page — GPS-sorted tree list, one tap to upload, category select per card.'],
            ['title' => 'Photos page redesign',                      'detail' => 'Single big + Add Photo button, category dropdown, full gallery with tap-to-fullscreen lightbox and delete.'],
            ['title' => 'GPS Locate Me reliability',                 'detail' => 'Patience window extended to 15s; fallback shot uses maximumAge:0 for a fresh fix instead of potentially stale cached data.'],
            ['title' => 'Dashboard SQL crash fix',                   'detail' => 'Subquery used deleted_at on attachments table which has no such column. Fixed to use status = active.'],
        ],
    ],

    '1.4.2' => [
        'status'   => 'released',
        'released' => '2026-04-03',
        'title'    => 'GPS Service, Harvest Slider & UI Overhaul',
        'features' => [
            ['title' => 'Unified GPS service',           'detail' => 'Single watchPosition singleton (gps.js) shared across all pages. Position warms up on page load — no more waiting when you tap a GPS button.'],
            ['title' => 'Dashboard Nearest to You hero', 'detail' => '80vh hero section on mobile, photo backgrounds on cards, action buttons (📷 🌾 ➕).'],
            ['title' => 'Quick Harvest slider',          'detail' => '0-5 basket/wheelbarrow slider in 0.25 increments. Auto-records current time. Tap Harvest it!'],
            ['title' => 'Map fullscreen',                'detail' => '⛶ button in map header. No more accidental item-add on casual map tap.'],
            ['title' => 'Image compression',             'detail' => 'Canvas-based resize to 1920px max / 82% JPEG before upload. Phone photos no longer fail.'],
            ['title' => 'Items list layout',             'detail' => 'Clean full-width rows with sort-by-distance. Grid split bug eliminated.'],
            ['title' => 'Mobile nav fix',                'detail' => 'Drawer z-index raised above blur overlay — menu is now visible when opened.'],
        ],
    ],

    '1.4.1' => [
        'status'   => 'released',
        'released' => '2026-04-03',
        'title'    => 'Nearby Items & GPS Fixes',
        'features' => [
            ['title' => 'Dashboard nearest items widget',    'detail' => 'Auto-detects location on dashboard load and lists the 5 closest items with distance and quick-action buttons.'],
            ['title' => 'GPS fix on Add Item page',          'detail' => 'Removed false HTTPS block; map pin now moves when GPS fires via correct native event dispatch.'],
            ['title' => 'Simplified upgrade page',           'detail' => 'One big Update Now button — no more choosing between GitHub and manual upload.'],
        ],
    ],

    '1.4.0' => [
        'status'   => 'released',
        'released' => '2026-04-02',
        'title'    => 'Design Overhaul, GPS & Photo Upload',
        'features' => [
            ['title' => 'Full UI redesign — modern 2026 design system',  'detail' => 'Complete rewrite of CSS with modern design tokens, pill buttons, shadow-only cards, smooth animations, and richer green palette.'],
            ['title' => 'Bottom navigation bar (mobile)',                 'detail' => 'Instagram-style bottom nav on mobile with Dashboard, Map, Add (FAB), Items, Settings. Replaces hamburger-only navigation.'],
            ['title' => 'Items list — visual card grid',                  'detail' => 'Items now display as a 2-4 column card grid with type color strip, emoji badge, quick action buttons per card.'],
            ['title' => 'Item detail — photo hero',                       'detail' => 'Item detail page shows identification photo as a full hero image with name overlay and large action strip.'],
            ['title' => 'Settings page layout fix',                       'detail' => 'Horizontal scrollable pill tabs + iOS-style grouped form fields. The side-by-side layout bug is fully resolved.'],
            ['title' => 'GPS reliability — retry logic',                  'detail' => 'GPS now retries automatically (high-accuracy → low-accuracy fallback). Up to 2 retries before showing actionable error.'],
            ['title' => 'Floating Locate Me button on map',               'detail' => 'Always-visible 📍 button bottom-right of the map. Shows accuracy halo and green dot on success, pulses while detecting.'],
            ['title' => 'Photo upload — AJAX with progress',              'detail' => 'Photos page rebuilt: tap any card to upload instantly via AJAX. Shows spinner, progress %, and live photo preview without reload.'],
            ['title' => 'Image upload bug fixes',                         'detail' => 'Fixed ORDER BY created_at bug (column is uploaded_at), raised PHP upload limit to 25MB, clear per-error messages.'],
        ],
    ],

    '1.5.0' => [
        'status'   => 'future',
        'released' => null,
        'title'    => 'Scale & Integrations',
        'features' => [
            ['title' => 'Bulk CSV item import',                       'detail' => 'Import a spreadsheet of items with GPS, type, variety, and metadata in one go.'],
            ['title' => 'Remote storage targets (FTP / SFTP)',        'detail' => 'Store uploaded photos on a remote FTP or SFTP server instead of local storage.'],
            ['title' => 'Multi-user / team access',                   'detail' => 'Invite collaborators with role-based permissions (viewer, editor, admin).'],
            ['title' => 'Weather integration',                        'detail' => 'Pull local weather data to correlate with action logs and harvest quality.'],
            ['title' => 'Advanced reporting & charts',                'detail' => 'Harvest trends over years, finance summaries, action frequency heatmaps.'],
        ],
    ],

];
