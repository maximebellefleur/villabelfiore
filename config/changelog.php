<?php

/**
 * Rooted — Changelog
 *
 * Each entry: 'x.y.z' => ['date', 'title', 'new' => [], 'improved' => [], 'fixed' => []]
 * Shown in the Settings → Upgrade page after uploading an update ZIP.
 */
return [

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
