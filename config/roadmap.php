<?php

/**
 * Rooted — Feature roadmap.
 * Drives the Settings → Upcoming Features page.
 *
 * Statuses: released | in_progress | planned | future
 */
return [

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
