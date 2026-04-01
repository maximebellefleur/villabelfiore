<?php

/**
 * Rooted — Changelog
 *
 * Each entry: 'x.y.z' => ['date', 'title', 'new' => [], 'improved' => [], 'fixed' => []]
 * Shown in the Settings → Upgrade page after uploading an update ZIP.
 */
return [

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
