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
