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
        'status'   => 'in_progress',
        'released' => null,
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
        'status'   => 'planned',
        'released' => null,
        'title'    => 'Intelligence & Offline',
        'features' => [
            ['title' => 'AI-generated item icons',                    'detail' => 'Generate a unique icon for each item based on its type, variety, and age using AI image generation.'],
            ['title' => 'Offline / PWA mode',                         'detail' => 'Full offline support via service worker. Changes are queued and synced when back online.'],
            ['title' => 'Yearly directional photo reminders',         'detail' => 'Auto-generate annual reminders to photograph each tree from N/S/E/W for visual health tracking.'],
            ['title' => 'Mobile coop location history',               'detail' => 'Track the movement history of mobile coops on the map with a timeline and route overlay.'],
            ['title' => 'Export to CSV / PDF',                        'detail' => 'Export item lists, harvest totals, finance summaries, and activity logs.'],
            ['title' => 'Item QR code labels',                        'detail' => 'Print a QR code sticker per item that opens its detail page on scan — identify trees in the field.'],
            ['title' => 'Draft form auto-save',                       'detail' => 'Partially filled forms are saved as drafts and restored on return to prevent data loss.'],
        ],
    ],

    '1.4.0' => [
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
