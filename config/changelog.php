<?php

/**
 * Rooted — Changelog
 *
 * Each entry: 'x.y.z' => ['date', 'title', 'new' => [], 'improved' => [], 'fixed' => []]
 * Shown in the Settings → Upgrade page after uploading an update ZIP.
 */
return [

    '2.0.6' => [
        'date'  => '2026-04-18',
        'title' => 'Multi-Photo Log & Google Satellite',
        'new'   => [
            'Log Action form now supports multiple photo attachments — select several at once, see thumbnail previews of all, and all photos are saved to the item gallery with the first one linked to the log entry.',
        ],
        'improved' => [
            'File picker for log photos now uses programmatic input.click() instead of the label+display:none trick — reliably opens the camera/gallery on iOS PWA.',
            'Service worker cache bumped to v4, forcing all PWA clients to re-fetch latest JS/CSS assets (fixes stale Esri map tiles showing on devices that had the old cache).',
        ],
        'fixed' => [
            'Main map and mini-map both now display Google Maps satellite imagery (no API key required) — previously the PWA served the old cached Esri tile source even after the code was updated.',
        ],
    ],

    '2.0.5' => [
        'date'  => '2026-04-18',
        'title' => 'Edit Meta Save & Google Mini-Map',
        'new'   => [],
        'improved' => [
            'Mini-map on item create/edit forms now uses Google Maps satellite tiles (matching the main map) — same zoom levels (native 21) and identical imagery.',
        ],
        'fixed' => [
            'Editing an existing item now correctly saves all type-specific meta fields (variety, tree type, soil, irrigation, etc.). Previously the update action ignored all meta[] POST data and values were silently discarded.',
            'tree_type field now included in both create and update meta save lists.',
        ],
    ],

    '2.0.4' => [
        'date'  => '2026-04-17',
        'title' => 'Multi-Photo Upload',
        'new'   => [
            'Photos page now accepts multiple files at once — tap "Add Photos" and select as many as you want. All selected images share the same category and caption.',
            'Sequential upload queue with per-file progress: "2 / 5 · Uploading… 64%" so you always see which file is in flight.',
        ],
        'improved' => [],
        'fixed'    => [],
    ],

    '2.0.3' => [
        'date'  => '2026-04-17',
        'title' => 'Edit Form Fields & Google Satellite',
        'new'   => [],
        'improved' => [
            'Item edit form now shows the same type-specific meta fields as the create form — with dropdowns, custom tree types, and all required/optional fields from the type config. Existing values are pre-populated.',
            'Edit form uses the shared RootedGPS service for location detection (same as create form), replacing the older direct getCurrentPosition call.',
            'Satellite tiles switched back to Google Maps (maxNativeZoom 21) — matches the desktop view the user preferred, with real tile detail up to zoom 21 vs Esri\'s zoom 19.',
        ],
        'fixed' => [
            'Edit form was missing meta fields that were never filled at creation time. Now all fields from required_meta and optional_meta are always shown.',
        ],
    ],

    '2.0.2' => [
        'date'  => '2026-04-16',
        'title' => 'Walk Mode GPS Fix',
        'new'   => [
            'Walk mode now uses the shared RootedGPS stream (gps.js) instead of creating its own watchPosition. Eliminates the competing-watcher bug that silently killed GPS callbacks on iOS/Android.',
            'Wake Lock API: the screen stays on automatically during a walk so background JS throttling does not pause GPS recording.',
            'RootedGPS.subscribe(cb) API added — fires callback on every GPS update, returns an unsubscribe function.',
        ],
        'improved' => [
            'gps.js watchPosition now uses maximumAge:0 (was 5000 ms) so walk mode always gets fresh readings, not cached ones.',
        ],
        'fixed' => [],
    ],

    '2.0.1' => [
        'date'  => '2026-04-15',
        'title' => 'Map Fixes',
        'new'   => [],
        'improved' => [
            'Satellite tiles switched to Esri World Imagery (standard REST endpoint). More reliable than Google or Esri Clarity, zoom 19+ coverage for Italy/Sicily, no API key required.',
            'Walk mode GPS accuracy threshold raised from ±12 m to ±25 m — accepts more readings in rural areas where GPS rarely gets better than 15 m.',
        ],
        'fixed' => [
            'Land boundary polygon no longer intercepts map clicks. Uses a dedicated CSS pane with pointer-events:none so tapping the land fill area passes through to items and the map beneath.',
            'Walk mode "doing nothing" bug: removed the 30-second GPS timeout from watchPosition. Previously a TIMEOUT error would silently reset the walk UI. Now it waits indefinitely for a fix, shows clear error only on permission denied or GPS unavailable.',
        ],
    ],

    '2.0.0' => [
        'date'  => '2026-04-15',
        'title' => 'Google Satellite & Gallery Fix',
        'new'   => [],
        'improved' => [
            'Satellite tiles switched from ESRI Clarity to Google Maps satellite — full zoom 20+ coverage for rural Sicily and other regions where ESRI blanked out above zoom 15.',
        ],
        'fixed' => [
            'Gallery fullscreen broken: internal function named "open" collided with window.open. Renamed to galleryOpen, moved all DOM variable declarations above the early-return guard, and assigned window.openGallery = galleryOpen directly. Tapping an image thumbnail now reliably opens the fullscreen overlay.',
        ],
    ],

    '1.9.9' => [
        'date'  => '2026-04-15',
        'title' => 'Walk Perimeter Mode',
        'new'   => [
            'Walk Perimeter mode for land boundary: tap 🚶 Walk the Perimeter, walk around your property, tap Stop. GPS records the path continuously, rejects readings worse than ±12 m, records a new point every 1.5 m of movement.',
            'Walk Boundary mode for zone/item boundaries: same continuous walk recording, available in the Draw Zone Boundary panel.',
            'Both walk modes use Ramer-Douglas-Peucker simplification on finish (0.8 m tolerance) to produce a clean, minimal polygon from the raw GPS path.',
            'Live stats while walking: "📍 42 pts · 247 m · ±4 m" updates on every GPS reading.',
            'Walk mode rejects noisy readings (>12 m accuracy) and shows a warning until signal improves.',
        ],
        'improved' => [],
        'fixed'    => [],
    ],

    '1.9.8' => [
        'date'  => '2026-04-15',
        'title' => 'Precise GPS & Better Satellite',
        'new'   => [
            'GPS boundary drawing now uses multi-sample averaging: collects up to 12 readings over 6 seconds, discards the worst half, and averages the best ones — typical accuracy improves from ±30 m to ±5–10 m in open sky',
            'Each GPS-placed boundary point shows a live accuracy ring (semi-transparent circle proportional to GPS error) so you can see the confidence of each corner',
            'GPS button shows live feedback: "📡 Sampling… 4 fixes · ±8 m" while collecting, stops early when accuracy ≤ 5 m',
        ],
        'improved' => [
            'Satellite tiles switched to ESRI World Imagery Clarity — noticeably sharper than Google at high zoom levels over agricultural land',
        ],
        'fixed' => [],
    ],

    '1.9.7' => [
        'date'  => '2026-04-15',
        'title' => 'cPanel PUBLIC_PATH Fix',
        'fixed' => [
            'Logo uploads, attachment storage and any file written to PUBLIC_PATH now land in the correct web-accessible directory on cPanel subdirectory installs (/rooted). Previously files were saved to rooted-files/public/ (not served) instead of the actual web root.',
        ],
        'new'      => [],
        'improved' => [],
    ],

    '1.9.6' => [
        'date'  => '2026-04-15',
        'title' => 'Map & Item Detail Polish',
        'new'   => [
            'Map now uses Google satellite tiles (no API key required) with native zoom up to level 21',
            'Activity log entries are now clickable — tap any row to open a full-detail popup with dark backdrop',
            'Item details page shows the boundary polygon GeoJSON when a boundary is set, with a collapsible raw JSON view',
            'Settings → General: configurable boundary types — choose which item types can draw polygon boundaries on the map',
        ],
        'improved' => [
            'Logo uploads now accept all valid PNG/JPG/WebP/SVG variants regardless of what finfo reports (image/x-png, text/xml for SVG, etc.)',
        ],
        'fixed' => [
            'Gallery "openGallery is not defined" error — function is now always exposed on window, even when an item has no images',
            'Land boundary on map is now purely visual — non-interactive, no tooltip on hover',
        ],
    ],

    '1.9.5' => [
        'date'  => '2026-04-14',
        'title' => 'AI Setup Guide & Nav Logo Pair',
        'improved' => [
            'Settings → General now shows a full Ollama setup guide: install command, model comparison table (moondream/llava:7b/13b/34b with RAM requirements), start command, and a collapsible section for importing custom Hugging Face .gguf models',
            'Nav bar displays icon + wordmark side by side when both logo-icon-light and logo-horizontal-light are uploaded',
        ],
        'new'  => [],
        'fixed' => [],
    ],

    '1.9.4' => [
        'date'  => '2026-04-14',
        'title' => 'Nav: Icon + Wordmark Side by Side',
        'improved' => [
            'Top nav now shows the icon logo and horizontal wordmark together side-by-side when both are uploaded. Falls back to whichever is available, then to "🌿 Rooted" text.',
        ],
        'new'  => [],
        'fixed' => [],
    ],

    '1.9.3' => [
        'date'  => '2026-04-14',
        'title' => '4-Slot Logos & Family Needs Units',
        'new'   => [
            'Four logo upload slots in Settings → General: Icon Light, Icon Dark, Horizontal Light, Horizontal Dark',
            'Nav bar uses the horizontal-light logo on desktop and the icon-light logo in the mobile drawer — graceful fallback to legacy logo-nav.* files and then "🌿 Rooted" text',
            'Each logo slot can be independently uploaded (PNG, JPG, WebP, SVG) or removed via a delete button',
            'Family Needs yearly quantity now has a unit selector: kg, g, units, heads, bunches, litres, jars, other',
        ],
        'improved' => [],
        'fixed'    => [],
    ],

    '1.9.1' => [
        'date'  => '2026-04-14',
        'title' => 'AI Photo Seed Identification',
        'new'   => [
            'Photo identify button on the Add / Edit Seed form — take a photo or pick from gallery and local AI (Ollama) pre-fills the entire form: name, variety, type, growing times, spacing, sun, companions, antagonists, notes',
            'AI endpoint and vision model configurable in Settings → General (supports llava, moondream, bakllava, and any Ollama vision model)',
        ],
        'improved' => [],
        'fixed'    => [],
    ],

    '1.9.0' => [
        'date'  => '2026-04-14',
        'title' => 'Seeds Catalog & Mobile Nav Update',
        'new'   => [
            'Full Seeds Catalog module: add, edit, and delete seed varieties with growing info, companion planting data, and planting/harvest month calendars',
            'Per-seed stock tracking with unit (seeds/grams/packets), low-stock threshold alerts, and quick +/− stock adjustment',
            'Family Needs planner: record yearly vegetable consumption targets with priority ranking and links to seed catalog entries',
            'Bed Row Planner: plan rows per item (bed/garden) by season, row number, seed, plant count, sowing date, and status',
            'Seeds accessible from bottom nav (🌱) on mobile — replaces the Home button (the logo tap returns to dashboard)',
            'Upload a custom logo image (PNG, JPG, WebP, or SVG) via Settings → General to replace the default "🌿 Rooted" text in the nav bar',
            '"Other…" action type in the item log form — choose Other and type a custom action label that is saved to the activity log',
        ],
        'improved' => [
            'Map on mobile now uses position:fixed pinned between top and bottom nav bars — no more overflow behind the bottom nav buttons',
            'Delete Item button added directly on the item edit page — no longer need to navigate elsewhere to remove an item',
        ],
        'fixed' => [
            'Map container overflowing behind bottom navigation buttons on mobile devices',
        ],
    ],

    '1.8.6' => [
        'date'  => '2026-04-14',
        'title' => 'Zone Polygon Toggle & Mobile Item Panel',
        'new'   => [
            'Mobile_coop and building item types can now have polygon boundaries drawn on the map (previously only garden, bed, orchard, zone, prep_zone, line)',
            'Tapping any item on the map now opens the sidebar automatically on mobile — item info is always visible',
            'Polygon status indicator in the item info panel: "Polygon saved" (green) or "No polygon yet" (grey) — draw or edit in one tap',
            'Popup and info panel polygon buttons now show context-aware labels: "➕ Draw polygon", "✏️ Edit polygon", "➕ Draw row", "✏️ Edit row"',
        ],
        'improved' => [],
        'fixed'    => [],
    ],

    '1.8.5' => [
        'date'  => '2026-04-14',
        'title' => 'Gallery Lightbox',
        'new'   => [
            'Tapping any photo on the item detail page now opens a full-screen gallery lightbox instead of navigating away',
            'Gallery supports left/right swipe on mobile, keyboard arrow keys and Escape on desktop, and tap-backdrop-to-close',
            'Photos loop continuously — swiping past the last image wraps back to the first and vice versa',
            'Live image counter (e.g. "3 / 7") and caption displayed at the bottom of the lightbox',
            'Hero/identification photo is also clickable and opens the gallery at the correct index',
        ],
        'improved' => [],
        'fixed'    => [],
    ],

    '1.8.4' => [
        'date'  => '2026-04-14',
        'title' => 'Map Polygon Drawing Overhaul',
        'new'   => [],
        'improved' => [
            'Polygon drawing now has explicit "Finish Polygon" and "Save" buttons — no more relying on double-click to close a shape',
            'Both land boundary and zone boundary drawing now auto-open the sidebar on mobile so the controls are always visible when draw mode is activated',
            'Live closing-line preview: while drawing, a faint dashed line connects the last point back to the first so you can see the shape forming before finishing',
            'Drawing is locked after Finish — no accidental point additions after closing the polygon; Clear resets everything for a fresh start',
            'GPS-assisted mode for mobile: walk to each corner, tap "Add GPS Point", repeat, then "Finish Polygon" — works for both land boundary and zone boundaries',
            'Status messages updated to guide through both desktop click mode and mobile GPS mode step by step',
        ],
        'fixed' => [],
    ],

    '1.8.3' => [
        'date'  => '2026-04-14',
        'title' => 'Icon Alpha Fix, Auto-Sync, Distance Sort & Caption UI',
        'new'   => [],
        'improved' => [
            'Google Calendar sync is now fully automatic — visiting the Reminders page silently pushes any pending reminders not yet synced, no manual sync button needed',
            'Items list now defaults to distance sort — GPS location is requested on page load and items are immediately sorted nearest-first; falls back to name order if GPS is unavailable',
            'Caption editor on photo cards is now stacked (button below input) with larger text and touch targets, matching the rest of the mobile UI',
            'Static assets (CSS, JS, images) now get 1-year browser cache headers, significantly reducing repeat page load times',
        ],
        'fixed' => [
            'PWA icon upload corrupted transparent logos — imagealphablending was set to true before imagecopyresampled causing alpha compositing instead of direct pixel copy; fixed by keeping blending off throughout the resize pipeline',
        ],
    ],

    '1.8.2' => [
        'date'  => '2026-04-14',
        'title' => 'PWA Manifest Fix, Dynamic Paths & Install Prompt',
        'new'   => [],
        'improved' => [
            'manifest.json is now served dynamically by PHP so icon and start_url paths always include the correct subdirectory prefix (e.g. /rooted/), fixing a long-standing bug where icons 404\'d and the install prompt never appeared on subdirectory installs',
            'Service worker (sw.js) now derives its base path from its own URL so cache paths work correctly on subdirectory installs; bumped cache to v3 to force reinstall',
            'Install App section now detects platform: iOS users see step-by-step share-sheet instructions, Chrome/Edge/Android users see the install button when the browser fires beforeinstallprompt, and a fallback message appears for unsupported browsers',
            'Icon upload now uses multi-step downsampling and center-crops non-square sources, producing sharper results for large source images',
        ],
        'fixed' => [
            'PWA install button was permanently hidden because beforeinstallprompt never fired — root cause was broken icon paths and start_url in manifest.json without /rooted prefix',
        ],
    ],

    '1.8.1' => [
        'date'  => '2026-04-14',
        'title' => 'Settings CSS Fix & PWA Icon Upload Fix',
        'new'   => [],
        'improved' => [],
        'fixed' => [
            'PWA, Weather, Harvest and Action Types settings tabs were unstyled — shared settings CSS was only in General settings inline style block; moved to admin.css so all tabs load it',
            'PWA icon upload crashed with "Something went wrong" — replaced mime_content_type() with finfo (PHP 8.1+ safe), added directory writability check, guarded imagecreatefromwebp() availability, wrapped in try/catch so real error is shown as flash message',
        ],
    ],

    '1.8.0' => [
        'date'  => '2026-04-14',
        'title' => 'Log Photos & AI Prompt Image URLs',
        'new' => [
            'Attach a photo directly to a log entry: tick "📷 Attach a photo to this log" when logging an action to upload an image that is permanently linked to that log entry',
            'Log thumbnails: photos linked to log entries appear as small thumbnails in the Activity Log table and in the Recent Activity feed on the item page',
            'AI Prompt now includes full image URLs: all item photos listed under a PHOTOS section with category, caption, and direct download URL; log entries with linked photos include the photo URL inline',
        ],
        'improved' => [
            'AI Prompt photo section lets any AI (ChatGPT, Claude, Gemini) fetch and analyze the actual images alongside the text history',
            'activity_log table gains attachment_id column (lazy migration — added automatically on first use, no manual SQL needed)',
        ],
        'fixed' => [],
    ],

    '1.7.0' => [
        'date'  => '2026-04-14',
        'title' => 'Fused Log+Reminder, Photo Captions & Google Calendar Auto-Push',
        'new' => [
            'Item detail: Log Action and Reminder forms merged into a single "Log Action" form — log anything, then tick "Set a reminder for this log" to optionally attach a reminder inline',
            'Inline calendar picker for reminders — full month-grid calendar widget (no native date input) with Mo–Su header, past-day dimming, today highlight, and selected-day highlight; defaults to today + 7 days',
            'Photo captions: each photo now has an optional text legend/caption — editable at upload time and inline-editable in the gallery (same tap-to-edit pattern as category)',
            'Photo category "Other…" option: select Other… to reveal a free-text input and store any custom category name; custom categories are listed dynamically in the dropdown from existing DB values',
            'Google Calendar auto-push: reminders created from the Log Action form or from the Reminders page are immediately pushed to Google Calendar — no manual Sync Now needed',
        ],
        'improved' => [
            'Bottom nav: Photos (📷) replaced with Items (🌿) — Photos always required selecting an item first; Items is more useful as a quick-nav destination',
            'Photo upload form: caption field always visible below category (dashed placeholder style)',
            'Quick Photos and Item Photos both support custom categories and captions',
            'ReminderController::store() and ItemController::addAction() both auto-push to Google Calendar via shared CalendarController::pushReminderById() — consistent across all creation paths',
        ],
        'fixed' => [],
    ],

    '1.6.0' => [
        'date'  => '2026-04-10',
        'title' => 'PWA, Brand Identity & Offline Support',
        'new' => [
            'Full PWA support: installable app with manifest, service worker v2, and branded home-screen icons',
            'PWA Settings page (Settings → PWA): enable/disable PWA, set app name/short name/description, theme & background color pickers, start URL, display mode, orientation',
            'Icon upload in PWA settings: upload a PNG/JPG source image and Rooted auto-generates all required sizes (512px, 192px, 180px Apple touch, 32px favicon) using GD',
            'In-page install prompt on PWA settings page (works on Chrome, Edge, Samsung Internet; iOS instructions included)',
            'Dedicated offline fallback page (/offline): branded "You\'re Offline" screen with retry button — replaces plain /dashboard cache fallback',
            'Service worker bumped to v2: old cache evicted on activate; offline page pre-cached during install; non-GET requests explicitly excluded from caching',
            'Apple PWA meta tags in main layout: apple-touch-icon, apple-mobile-web-app-capable, apple-mobile-web-app-status-bar-style, apple-mobile-web-app-title',
        ],
        'improved' => [
            'New brand palette applied to CSS custom properties: Deep Moss (#29402B) primary, Umber Earth (#A66141) accent, Slate Grey (#637380) muted text, warm cream (#F5F0EA) surfaces',
            'Manifest theme_color and background_color updated to match brand palette (#29402B / #F5F0EA)',
            'PWA tab added to all settings tab navs (General, Harvest, Action Types, Weather)',
            'Generated icons: Deep Moss rounded-square background + hexagon geometric frame + white tree silhouette (trunk, roots, 3-circle canopy with leaf detail)',
        ],
        'fixed' => [],
    ],

    '1.5.3' => [
        'date'  => '2026-04-10',
        'title' => 'Dashboard Section Order & 3-Day Forecast',
        'new' => [],
        'improved' => [
            'Weather forecast strip now shows 3 days ahead (today+3) — one extra daily pill added',
            'Upcoming Reminders moved directly under the Lunar Calendar — visible before the Nearest to You section',
            'Recent Activity is now a standalone full-width section at the bottom (no longer paired in a two-column layout)',
        ],
        'fixed' => [],
    ],

    '1.5.2' => [
        'date'  => '2026-04-10',
        'title' => 'Dashboard Layout Refinements',
        'new' => [],
        'improved' => [
            'Weather widget: removed dark card background — now flows seamlessly below the welcome greeting as a transparent section separated by a light border',
            'Nearby card action row: photo badge now sits to the left of all three action buttons (📷🌾➕) — camera icon always visible regardless of whether a photo exists',
            'Nearby card: removed emoji placeholder when no photo — buttons are right-aligned cleanly with no gap filler',
            'Dashboard order: Lunar Calendar moved directly after weather/welcome, Nearest to You section follows below it',
        ],
        'fixed' => [],
    ],

    '1.5.1' => [
        'date'  => '2026-04-10',
        'title' => 'Welcome Greeting, Quote of the Day & Weather City',
        'new' => [
            'Dashboard welcome header: "Ciao, [Name]!" greeting with a daily inspirational quote below in smaller italic text (max 2 lines)',
            'Quote of the Day powered by ZenQuotes API (free, no key required) — cached 24 hours in the database to avoid redundant requests',
            'Weather widget shows city/location name (e.g. "📍 Rosolini") configurable in Settings → Weather',
            'Weather widget: tomorrow and day-after-tomorrow daily forecast shown in the same horizontal row as hourly items, with a distinct background shade, max/min temperatures',
        ],
        'improved' => [
            'Dashboard layout: Add Item button removed from top (already in bottom nav) — weather widget now takes full width for better readability',
            'Weather widget: description and city name appear together in the center column; meta details (humidity, pressure, sunset) stacked on the right',
            'General settings: "Your Name" field drives the dashboard greeting; "Quote API URL" field for customising the quote source',
            'Open-Meteo request now fetches 3-day forecast data for the daily strip',
        ],
        'fixed' => [],
    ],

    '1.5.0' => [
        'date'  => '2026-04-10',
        'title' => 'Weather Widget & Nearby Card Photo Badge',
        'new' => [
            'Dashboard: live weather widget showing current temperature, icon, description, humidity, atmospheric pressure, sunset time, and next 4-hour forecast strip',
            'Dashboard: Add Item button and weather widget displayed side-by-side in a 50/50 top strip',
            'Weather settings page (Settings → Weather): enable/disable widget, configure coordinates for Open-Meteo, link a personal weather station JSON URL, and set external forecast link',
            'Weather data cached for 30 minutes in the database to avoid excessive API calls',
            'Support for custom weather station JSON feeds (EcoWitt-compatible and generic formats)',
        ],
        'improved' => [
            'Dashboard nearest-to-you cards: circular ID photo badge moved into the action buttons row as the leftmost item, size increased to 48px — aligns with action icons at the same height',
            'Nearby card action buttons grouped on the right with photo badge on the far left for a cleaner layout',
            'Settings tab navigation updated to include Weather tab across all settings pages',
        ],
        'fixed' => [],
    ],

    '1.4.27' => [
        'date'  => '2026-04-10',
        'title' => 'Dashboard Card Photo Badge Bottom-Left of Card',
        'new' => [],
        'improved' => [
            'Dashboard nearest cards: circular ID photo badge repositioned to absolute bottom-left of the card (10px margin) — no longer pinned to the emoji icon corner',
            'Badge size increased to 36px with stronger border and shadow for better visibility over the photo background',
        ],
        'fixed' => [],
    ],

    '1.4.26' => [
        'date'  => '2026-04-09',
        'title' => 'Map Attribution Z-Index, Fullscreen Restored & Lunar Spacing',
        'new' => [],
        'improved' => [
            'Lunar calendar week strip: 10px margin between moon phase emoji and day-type emoji — no more touching icons',
        ],
        'fixed' => [
            'Map page: Leaflet attribution no longer overlaps the bottom nav — main-content--map is now a flex column so #mapWrap fills exact remaining height (no overflow behind nav)',
            'Map page: fullscreen button restored on mobile (was accidentally hidden in v1.4.25)',
        ],
    ],

    '1.4.25' => [
        'date'  => '2026-04-09',
        'title' => 'Map Overflow Fix & Layers Toggle',
        'new' => [
            'Map page: Layers toggle button — tap ☰ Layers to collapse/expand the sidebar panel; sidebar starts collapsed on mobile',
        ],
        'improved' => [
            'Map page header on mobile: button labels hidden (icon-only), fullscreen button hidden — all 4 buttons now fit without overflowing the screen width',
            'body: overflow-x:hidden prevents any page-level horizontal scroll across the app',
        ],
        'fixed' => [
            'Map page: horizontal overflow on mobile — header buttons caused page to scroll sideways',
        ],
    ],

    '1.4.24' => [
        'date'  => '2026-04-09',
        'title' => 'Lunar Calendar Dark Mode & Spacing',
        'new' => [],
        'improved' => [
            'Lunar calendar section redesigned in dark mode (#1a2e1f background) — visually separated from the rest of the dashboard',
            'More spacing between moon phase icon and text (gap increased), larger emoji sizes',
            'Week strip: day cells use dark glass styling with element colours (fire/earth/air/water) adapted for dark background',
            'Today cell highlighted with a soft green border on the dark background',
        ],
        'fixed' => [],
    ],

    '1.4.23' => [
        'date'  => '2026-04-09',
        'title' => 'Dashboard Card Photo BG & Badge Position Fix',
        'new' => [],
        'improved' => [
            'Dashboard nearest cards: ID photo used as full card background (cover) when available — the card itself IS the photo',
            'Dashboard nearest cards: circular photo badge on the emoji icon moved to bottom-left',
        ],
        'fixed' => [],
    ],

    '1.4.22' => [
        'date'  => '2026-04-09',
        'title' => 'Photo Icons, Hero Cover, Dashboard Badge & Lunar Calendar',
        'new' => [
            'Lunar biodynamic calendar on dashboard — shows moon phase, zodiac sign, and day type (Root/Leaf/Flower/Fruit) for today + 7-day week strip; fully calculated in PHP, no API key required',
        ],
        'improved' => [
            'Items list: left type icon now shows the actual ID photo (object-fit:cover) when one exists, instantly recognizable — no more raw emoji for photo-tagged items',
            'Item detail hero: switched back to full-cover background (object-fit:cover, 300px height) — no more black letterbox bars; small circular avatar added above the type badge for extra quick ID',
            'Dashboard nearest cards: emoji icon always visible in the avatar circle; ID photo shown as a small circular badge (26px) overlaid at bottom-right — both type and photo visible at a glance',
            'Map page: mapWrap height on mobile now subtracts bottom nav height — map no longer extends behind the nav bar',
            'Item detail mini map: isolation:isolate contains all Leaflet z-indexes within the map — no pane elements can render above the fixed bottom nav',
        ],
        'fixed' => [],
    ],

    '1.4.21' => [
        'date'  => '2026-04-09',
        'title' => 'Map Nav Fix, List Photo Fit & Dashboard Avatar Photos',
        'new' => [],
        'improved' => [
            'Items list photo: switched to auto 100% background-size — image scales to row height so 40–50% of the full photo is visible in the thumbnail, not a zoomed-in crop',
            'Dashboard nearest cards: photo shown as circular avatar icon (52×52 object-fit:cover) instead of full-card background — easy to identify at a glance without overwhelming the card',
        ],
        'fixed' => [
            'Map page: bottom navigation bar now sits above all Leaflet layers (z-index raised from 150 to 1000) — map controls and tiles no longer overlap the nav bar',
        ],
    ],

    '1.4.20' => [
        'date'  => '2026-04-09',
        'title' => 'ID Photos in Items List & Zoom Fix Everywhere',
        'new' => [
            'Items list now shows the identification photo as a background on each row — gradient overlay keeps the name and type text readable',
        ],
        'improved' => [
            'Dashboard nearest cards: photo no longer crops — uses full-width scale (100% wide, natural height) anchored top-center; card taller at 180px',
            'Item detail hero photo: switched to object-fit:contain with dark background — shows the full tree, no lateral cropping',
            'Items list photo query uses a single IN() for the whole page — no N+1 queries',
        ],
        'fixed' => [
            'Photos with status=NULL (old uploads) excluded from identification photo lookup in dashboard and items list — now included',
        ],
    ],

    '1.4.19' => [
        'date'  => '2026-04-08',
        'title' => 'Photos Fix, Custom Tree Types, Log Delete & Dashboard Photo',
        'new' => [
            'Custom tree type: selecting "Other" on the Add Item form reveals a text field — type a name, click Save, and it persists as a new option for all future items',
            'Activity log: delete any log entry directly from the item detail page — inline Yes/No confirm, no window.confirm needed',
            'Photo category: added Treatment (💊) option to the photo category selector on the item photos page',
        ],
        'improved' => [
            'Dashboard nearest-card: identification photo now shows at 170px height (was 110px) and anchored top-center — shows significantly more of the tree rather than a zoomed-in crop',
            'Activity log: long filenames and descriptions now word-wrap instead of overflowing the table',
        ],
        'fixed' => [
            'Photos page still showed "No photos yet" — ItemController::photos() was passing the wrong variable ($byCategory instead of $attachments) to the view',
            'Photos uploaded before the status column existed (status = NULL) were excluded by the active filter — all queries now include status IS NULL as fallback',
        ],
    ],

    '1.4.18' => [
        'date'  => '2026-04-06',
        'title' => 'Harvest Settings, Photos Gallery Fix & Action Types Mobile',
        'new' => [
            'Harvest Settings page at Settings → Harvest — configure which item types are harvest-enabled, max harvests per year, unit label, slider max and step per type',
            'HarvestConfig helper class drives both the Settings form and Quick Harvest page — single source of truth for all harvest configuration',
        ],
        'improved' => [
            'Quick Harvest slider range and unit labels now come from Settings instead of being hardcoded — changes take effect immediately after saving',
            'Action Types settings page now has the full tab nav, horizontal scroll for the table on mobile, and a clear description of what the page is for',
            'Settings tab nav updated — Harvest tab added for direct access from General settings',
        ],
        'fixed' => [
            'Photos page (/items/{id}/photos) was showing "No photos yet" even for items with existing photos — controller was rendering the wrong legacy view',
        ],
    ],

    '1.4.17' => [
        'date'  => '2026-04-06',
        'title' => 'Photo Edit & Delete — Inline Category Change, PWA-Safe Confirm',
        'new' => [
            'Tap a photo\'s category label to edit it inline — select from the dropdown, saves instantly via AJAX',
            'New POST /attachments/{id}/category route and AttachmentController::updateCategory() method',
        ],
        'improved' => [
            'Photo delete no longer uses window.confirm() (broken in PWA/standalone mode) — replaced with inline ✓ Yes / ✕ No buttons, same pattern as harvest delete',
        ],
        'fixed' => [],
    ],

    '1.4.16' => [
        'date'  => '2026-04-05',
        'title' => 'Reminder Item Association — GPS-Sorted Picker',
        'new' => [
            'Reminder form on /reminders now has a GPS-sorted item picker — items sorted nearest first as soon as location is available; re-sorts as accuracy improves',
            'Item detail reminder form shows the item name badge so it\'s clear which item the reminder is for',
        ],
        'improved' => [
            'Reminders page completely redesigned — clean card layout, ✓ Done and ✕ Dismiss buttons per reminder, linked item name on each entry',
        ],
        'fixed' => [],
    ],

    '1.4.15' => [
        'date'  => '2026-04-05',
        'title' => 'Item Detail Redesign — Quick Actions, Photo Preview & Activity Feed',
        'new' => [
            'Quick-action strip on every item page — 4 one-tap buttons: Add Photo, Reminder, Note, AI (scroll to matching form)',
            'Photo preview masonry strip — shows last 4 photos with "See all X →" link',
            'Recent activity feed — last 3 log entries and pending reminders shown inline with one-tap Done',
        ],
        'improved' => [
            'Item page layout reordered: quick actions → photos → activity → details → location → harvests → finance → full log',
            'Hero simplified — back button and edit button both in top bar, no embedded action strip',
            'Reminder and Note forms always visible as anchored sections (no more hidden in tabs)',
            'Harvest and Finance show inline tables without tabs',
        ],
        'fixed' => [],
    ],

    '1.4.14' => [
        'date'  => '2026-04-05',
        'title' => 'Photo Upload Polish, Harvest Scale & Reminder Fix',
        'new' => [],
        'improved' => [
            'Photo upload: single "Add Photo" button (native OS picker — camera + gallery + files) replaces split camera/gallery buttons — no more double-upload',
            'Photo upload: fill progress bar shows real percentage (5% → 95% during XHR upload) instead of spinning circle',
            'Quick Photos success state shows "Photo saved! See all photos →" link and stays visible',
            'Item Photos success flashes "✅ Photo saved!" briefly before reloading gallery',
            'Harvest slider scale is now defined per item type: olive trees 0–2 baskets, almond 0–5 wheelbarrows, vine 0–50 kg, tree 0–20 kg',
            'Wheelbarrow SVG redrawn — larger canvas (72×48), bigger spoked wheel, cleaner tray trapezoid with rim, parallel handles and grip bar',
        ],
        'fixed' => [
            'Reminder quick-action button on dashboard returned 404 — route /reminders/create does not exist; link now correctly goes to /reminders',
        ],
    ],

    '1.4.13' => [
        'date'  => '2026-04-05',
        'title' => 'GPS Accuracy Auto-Refresh — Nearest, Harvest & Items',
        'new' => [
            'GPS now automatically re-sorts nearest items as signal improves — no manual refresh needed',
            'Dashboard "Nearest to You" re-renders with updated distances whenever GPS accuracy gains ≥30%',
            'Quick Harvest cards re-sort by distance automatically as location sharpens',
            'Items list re-sorts by distance automatically when distance sort is active and accuracy improves',
        ],
        'improved' => [
            'RootedGPS now exposes onAccuracyImprove(cb) — fires callback on every meaningful accuracy gain (≥30% improvement over best seen so far)',
        ],
        'fixed' => [],
    ],

    '1.4.12' => [
        'date'  => '2026-04-05',
        'title' => 'Better Wheelbarrow, Camera Fix, Photos Split Buttons',
        'fixed' => [
            'Wheelbarrow SVG redrawn — colored filled tray (amber/brown), gray spoked wheel, wood handles, support leg',
            'Camera capture change event not firing on Android after photo confirm — window focus fallback now triggers upload correctly',
            'Item Photos page now has Camera + Gallery split buttons matching Quick Photos',
        ],
    ],

    '1.4.11' => [
        'date'  => '2026-04-05',
        'title' => 'Photo Upload Fix & Camera+Gallery Buttons',
        'new' => [],
        'improved' => [
            'Quick Photos now has separate Camera and Gallery buttons — Camera uses device camera directly, Gallery opens photo library',
            'Upload error messages now show the real server error (DB error, folder not writable, file too large, etc.) instead of generic "server error"',
        ],
        'fixed' => [
            'AttachmentController wrapped in try/catch — finfo extension failure and DB errors now return a readable error instead of crashing',
            'MIME detection falls back to file extension if finfo PHP extension is unavailable on the host',
            'post_max_size overflow now detected and returns a clear "photo too large" message',
            'Added .user.ini to set upload_max_filesize=25M for PHP-FPM hosts (htaccess php_value is ignored on PHP-FPM)',
        ],
    ],

    '1.4.10' => [
        'date'  => '2026-04-04',
        'title' => 'Nav Drawer Fix & Wheelbarrow Icon',
        'new' => [],
        'improved' => [],
        'fixed' => [
            'Mobile nav drawer completely rewritten — moved outside <nav> element so it is no longer trapped inside a CSS stacking context. Drawer now uses z-index:9999 in the root stacking context and is always visible above the overlay.',
            'Wheelbarrow SVG icon added for almond tree harvest — replaces the bucket emoji since no wheelbarrow emoji exists in Unicode.',
        ],
    ],

    '1.4.9' => [
        'date'  => '2026-04-04',
        'title' => 'jQuery Load Order Fix — Locate Me & Upload',
        'new' => [],
        'improved' => [],
        'fixed' => [
            'jQuery was loaded AFTER page content in the layout — all inline scripts using $() (Locate Me button, GPS detection on create/edit item) crashed silently and never registered their click handlers. jQuery is now loaded before the page content.',
        ],
    ],

    '1.4.8' => [
        'date'  => '2026-04-04',
        'title' => 'AJAX Error Handling & Upload Diagnostics',
        'new' => [],
        'improved' => [
            'Global exception handler now returns JSON for AJAX requests — upload errors show real message instead of "Upload failed — try again."',
            'Router 404/405 handlers also return JSON for AJAX requests',
            'Almond tree harvest unit changed from "wheelbarrows" to "buckets" to match the 🪣 icon',
        ],
        'fixed' => [],
    ],

    '1.4.7' => [
        'date'  => '2026-04-04',
        'title' => 'Harvest Delete Inline Confirm',
        'new' => [],
        'improved' => [],
        'fixed' => [
            'Harvest delete ✕ button now uses inline Yes/No confirm instead of window.confirm() — works correctly in PWA/standalone mode where native dialogs are suppressed',
        ],
    ],

    '1.4.6' => [
        'date'  => '2026-04-04',
        'title' => 'Nav Fix, Photo Upload & CSRF JSON',
        'new' => [],
        'improved' => [
            'CSRF validation now returns JSON (not HTML) when request is AJAX — photo upload error messages now display correctly instead of "Something went wrong"',
            'Photo upload XHR now sends X-Requested-With header — AJAX detection is reliable even when PHP post_max_size is exceeded',
        ],
        'fixed' => [
            'Nav drawer (menu items) was invisible when hamburger was opened — nav z-index raised above overlay so links are visible',
            'Item Photos page: removed capture="environment" from file input — change event now fires reliably on all mobile browsers',
            'Quick Photos: added X-Requested-With header to upload XHR — same fix as item photos',
        ],
    ],

    '1.4.5' => [
        'date'  => '2026-04-04',
        'title' => 'GPS Reliability, Photos & Harvest Overhaul',
        'new' => [
            'Harvest quick page shows year count per item (e.g. 1/1 this year) and locks out further harvests once the annual max is reached',
            'Harvest entries listed per item with year history and one-tap delete',
            'harvest_max_per_year setting per item type (default 1) — overridable via settings table',
        ],
        'improved' => [
            'GPS: all Locate Me / Detect GPS / Nearest to You use cached position instantly — no more 15-27 second waits',
            'Dashboard ↺ refresh shows cached cards immediately then updates silently in background',
            'Quick Photos: removed capture="environment" — uses native media picker for reliable change event; auto-uploads on selection',
            'Harvest quick page redirects back to itself after save/delete instead of item detail page',
            'Map Locate Me button icon (SVG) now correctly restored after GPS resolves',
        ],
        'fixed' => [
            'Dashboard Nearest to You never rendered because RootedGPS was called before gps.js loaded (now in <head>)',
            'Locate Me button became blank after first use (textContent wiped SVG; now uses innerHTML)',
            'Quick Photos upload button never appeared — change event unreliable with capture="environment"',
        ],
    ],

    '1.4.4' => [
        'date'  => '2026-04-04',
        'title' => 'AI Prompt, Map Fix & UX Polish',
        'new' => [
            'AI Prompt button on every item page — one tap builds a full history prompt (details, actions, harvests, finance, reminders) and copies it to clipboard, ready to paste into any AI chatbot',
        ],
        'improved' => [
            'Map now goes full-screen on all screens up to 900px wide (was 768px) — fixes white sidebar column on many phones',
            'Quick Photos redesigned as two-step: choose photo first, then tap Upload — no more silent auto-upload confusion',
            'Nearest to You section always visible on dashboard — shows helpful message if no GPS items or location unavailable',
        ],
        'fixed' => [
            'Mobile nav hamburger menu — backdrop-filter blur on overlay created CSS stacking context that hid the drawer',
        ],
    ],

    '1.4.3' => [
        'date'  => '2026-04-03',
        'title' => 'Bottom Nav, Quick Photos & Bug Fixes',
        'new' => [
            'Bottom nav now: Home · Map · + · Harvest · Photos — harvest and photos both in quick mode',
            'Quick Photos page (/photos/quick) — nearest trees first, one tap to upload with category select',
            'Photos page redesigned — one big + Add Photo button, category dropdown, gallery with fullscreen lightbox and delete',
        ],
        'improved' => [
            'GPS Locate Me button: longer patience window (15s) and direct high-accuracy fallback shot — more reliable first fix',
        ],
        'fixed' => [
            'Dashboard crash — attachments subquery used deleted_at (column does not exist), now uses status',
        ],
    ],

    '1.4.2' => [
        'date'  => '2026-04-03',
        'title' => 'GPS Service, Harvest Slider & UI Overhaul',
        'new' => [
            'Unified GPS service (gps.js) — warms up on every page load so location is instant when you need it',
            'Dashboard: Nearest to You is now the hero section (80vh mobile) with photo backgrounds and action buttons',
            'Quick Harvest: distance-sorted trees, basket/wheelbarrow slider 0-5 in 0.25 steps, tap Harvest it!',
            'Map: fullscreen button (⛶), no more accidental item creation on map tap',
            'Image upload: client-side compression to ~400KB before sending — phone photos no longer fail',
        ],
        'improved' => [
            'Items page: clean full-width list layout with sort-by-distance button — grid split bug fixed',
            'Mobile nav menu: z-index fix — drawer now appears above the blur overlay',
            'Dashboard quick-actions strip: wraps to second row instead of scrolling off-screen',
            'Roadmap: all features (including released) now individually toggleable (○ → ✅ → ❌)',
            'GPS on Add Item page: uses shared service, no HTTPS block, map pin moves correctly',
        ],
        'fixed' => [
            'Stray ?> at top of item detail page',
            'Upload errors now stay visible until dismissed — no more 8-second auto-hide',
            'Items grid rendered card content and actions in separate cells on desktop — now a proper list',
        ],
    ],

    '1.4.1' => [
        'date'     => '2026-04-03',
        'title'    => 'Nearby Items & GPS Fixes',
        'new' => [
            'Dashboard: "Nearest to You" — auto-detects your location and shows the 5 closest items with distance, Photos and Harvest quick-action buttons',
        ],
        'improved' => [
            'Upgrade page: one big "Update Now" button — no more choosing between two options',
        ],
        'fixed' => [
            'GPS on Add Item page: removed false HTTPS block that prevented location detection',
            'GPS on Add Item page: map pin now moves correctly when GPS fires (native event dispatch fix)',
        ],
    ],

    '1.3.0' => [
        'date'     => '2026-04-02',
        'title'    => 'Mobile UX, Map Improvements & UI Revamp',
        'new' => [
            'Tree type is now a meta field on the generic Tree item type — select from 19 Sicily-native species',
            'Quick harvest entry page — record harvest from dashboard without navigating to the item',
            'Photo management page per item — dedicated mobile-friendly upload grid for N/S/E/W/ID photos',
            'Roadmap: three-state toggle (none → done → problem) — manage your own progress list',
            'Dashboard: harvest chart, quick action strip, lively design with emojis per type',
            'Map: fullscreen on mobile with compact floating toolbar',
            'Map: direct tap-to-add with safeguard against accidental triggers near controls',
            'Map: layer Show/Hide All toggle working correctly',
        ],
        'improved' => [
            'Map icons: smaller, cleaner, pinhead anchor — less visual clutter',
            'Mobile navigation: overlay backdrop + correct z-index layering',
            'GPS detect: HTTPS check with clear messaging, better error descriptions',
            'Settings page: tab layout fixed (was broken — tabs and form content were side-by-side)',
            'Item detail view: mobile-first hero card, photo gallery grouped by category',
        ],
        'fixed' => [
            'Settings page: admin.css .tabs { display:flex } was placing tab nav and content side by side',
            'Mobile nav menu: was invisible behind content (z-index fix)',
            'Map "Show all" layer toggle was not affecting individual type checkboxes',
            'Tree type dropdown now renders as select in Add Item form for known option lists',
        ],
    ],

    '1.2.0' => [
        'date'     => '2026-04-01',
        'title'    => 'Photos, Actions & Calendar',
        'new' => [
            'Google Calendar sync — connect your Google account and push all pending reminders as calendar events',
            'Settings → Roadmap page: full feature list with version tracking and status (released / in progress / planned)',
            'Settings → Calendar tab: step-by-step OAuth setup with redirect URI helper and copy button',
            'Map item icons rebuilt as CSS div icons (reliable emoji rendering on iOS and Android)',
            'GPS detect button in Add Item sheet — tap to place pin at your current location',
            'GPS "Add GPS Point" button in land boundary and zone boundary draw panels',
        ],
        'improved' => [
            'Map: item boundary polygons now save and load using the correct DB column (meta_value_text)',
            'Map: items with no GPS but with a boundary polygon now render correctly',
            'Settings navigation updated with Calendar and Roadmap tabs',
        ],
        'fixed' => [
            'Map item icons using SVG text were invisible on iOS Safari and some Android browsers — replaced with div-based icons',
            'Item boundary save/load was silently failing due to wrong DB column name (meta_value vs meta_value_text)',
            'GPS detect on boundary panels used alert() — replaced with inline status messages',
        ],
    ],

    '1.1.0' => [
        'date'     => '2026-04-01',
        'title'    => 'Interactive Land Map',
        'new' => [
            'Full-screen interactive map at Dashboard → Map (Leaflet.js)',
            'Satellite imagery enabled by default (Esri World Imagery + OSM labels)',
            'All items with GPS coordinates shown as type-colored emoji pins',
            'Boundary drawing: click to place polygon, double-click to finish, saved per item',
            'Drawn boundaries rendered as semi-transparent polygons with tooltips',
            'Layer toggles to show/hide each item type independently',
            '"My Location" button: centers map on your device GPS with accuracy circle',
            'Satellite ↔ Map toggle button in top-right corner',
            'Mini-map on item create and edit forms — click or drag to set coordinates',
            'Map link added to the navigation bar',
            'Settings → Upgrade page (you are here)',
        ],
        'improved' => [
            'Item create/edit: "Detect Location" button now labeled with 📍 icon',
            'Item edit: location fields grouped into a proper fieldset like create form',
        ],
        'fixed' => [
            'Homepage redirected to /install even after installation was complete',
            'All form actions and links were missing the /rooted/ subdirectory prefix',
            'Installer writeEnv() silently failed if .env.example was missing',
        ],
    ],

    '1.0.0' => [
        'date'     => '2026-03-31',
        'title'    => 'Initial Release',
        'new' => [
            '6-step web installer (env check, DB setup, land identity, storage, integrations, admin)',
            '13 item types: tree, olive tree, almond tree, vine, garden, bed, orchard, zone, prep zone, water point, tool, mobile coop, building',
            'Generic item architecture with EAV metadata per type',
            'Parent/child item relationships',
            'GPS coordinate storage with accuracy and source tracking',
            'Nearby items search using Haversine formula',
            'Harvest tracking with quantity, unit, quality grade',
            'Finance entries (income and expense) per item',
            'Reminder system with due dates, completion, dismissal',
            'File and photo attachments per item',
            'Activity log for all item actions',
            'Dashboard with item counts, reminders, recent activity',
            'Reports page with harvest and finance summaries',
            'PWA support: manifest, service worker, offline drafts',
            'Sync queue for offline-first mobile use',
            'CSRF protection on all forms',
            'cPanel subdirectory deployment (public_html/rooted/)',
        ],
        'improved' => [],
        'fixed'    => [],
    ],

];
