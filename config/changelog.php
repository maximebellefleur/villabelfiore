<?php

/**
 * Rooted — Changelog
 *
 * Each entry: 'x.y.z' => ['date', 'title', 'new' => [], 'improved' => [], 'fixed' => []]
 * Shown in the Settings → Upgrade page after uploading an update ZIP.
 */
return [

    '3.0.4' => [
        'date'  => '2026-04-28',
        'title' => 'Prep Bed restriction + hero overlay buttons + nav shortcuts',
        'new' => [
            'Edit and Position buttons are now overlaid directly on the item hero photo (bottom-right corner, glassmorphism pill buttons) — no need to scroll to find them.',
            'Prep Beds (unassigned beds) now show a yellow banner on the planting page and block the seed/planting UI. Logs, treatments, and harvests can still be recorded.',
        ],
        'improved' => [
            'Harvest and Irrigation shortcuts added to the main nav drawer (mobile and desktop), positioned between Garden and Tasks for faster access.',
        ],
    ],

    '3.0.3' => [
        'date'  => '2026-04-28',
        'title' => 'Edit UX overhaul — position modal, Active Gardens, bed assignment popup',
        'new' => [
            'Full-screen "Position on Map" modal for bed/garden boundaries: tap to open, nudge N/S/E/W, rotate ±5°, Save button — replaces the tiny inline nudge section that was below the mini-map.',
            'Garden hub renamed to "Active Gardens"; unassigned beds now shown as "Prep Beds (unassigned)" in the Bed Overview section.',
            '"Assign existing beds" button on empty gardens opens a multi-select popup listing all unassigned beds — tap checkboxes, hit Save, instantly associates beds with the garden.',
            '"+ New bed" from a garden hub row pre-fills the garden association so the new bed is automatically linked.',
            'Position button (📍) added to item show hero bar for all boundary-capable item types.',
            'Yellow CTA banner shown above the page for beds and gardens that have no boundary set yet.',
            '"Adjust Position on Map" button shown in the boundary card when a boundary already exists.',
        ],
        'improved' => [
            'Type field in Edit form is now a full editable select (excludes "line" type which is managed internally).',
            'Parent/garden field in Edit form is a smart select showing all gardens (for bed type) instead of a useless raw ID input. Hidden for all other item types.',
            'Corner + Size boundary mode no longer duplicates the Height/Width/Lines/Direction fields — it reads them from the Garden Bed Layout card above.',
            '"Remove Boundary" is now clearly styled as a danger button in a "Danger zone" sub-section, not red text that looked like a notification.',
            'Save/remove boundary feedback is now a full-width colored toast banner (green for success, red for errors) instead of inline text next to a button.',
            'Create Item form now pre-fills parent garden from URL (fixing the flow from "Add a bed" on a specific garden).',
            'Create Item form shows garden select for bed type, hidden for other types.',
        ],
    ],

    '3.0.2' => [
        'date'  => '2026-04-28',
        'title' => 'Log photo compression — compress to ~800 KB before upload so it never hangs',
        'fixed' => [
            'Log Action form with a photo attached showed "Logging…" indefinitely. Root cause: raw iPhone/Android photos (5–15 MB HEIC/JPEG) were sent as-is, making the upload slow on mobile connections and occasionally causing the server to time out reading the multipart body. Photos are now compressed to max 1600 px / 80 % JPEG (~300–800 KB) using an in-browser canvas step (same pattern as the survey flow) before the fetch request is sent. The button shows "Compressing…" for 1–2 s then "Logging…" while uploading.',
            'Added a 60-second AbortController timeout on the fetch; if the server does not respond in time the button re-enables and an "Upload timed out" alert is shown instead of hanging forever.',
        ],
    ],

    '3.0.1' => [
        'date'  => '2026-04-28',
        'title' => 'Fix photo attach log — AJAX submit ensures image is always sent',
        'fixed' => [
            'Log Action form with a photo attached did nothing on submit (iOS Safari and some Android browsers silently dropped the file when the native form POST used a width:0/height:0 hidden file input). The form now submits via fetch + FormData so the file is always explicitly included, and an error alert is shown if the request fails.',
            'File input hiding changed from width:0/height:0 to left:-9999px/1px×1px so iOS webkit includes the input in the form payload even in fallback scenarios.',
        ],
    ],

    '3.0.0' => [
        'date'  => '2026-04-28',
        'title' => 'Garden Module Redesign — action-first hub, tap-to-plant beds, succession planning',
        'new' => [
            'Garden hub redesigned as an action-first list: a "This week" summary strip aggregates urgency counts (ready to harvest, water today, sow scheduled, beds to plan, thin seedlings) across the property, then each garden expands into a clickable bed list with a per-row urgency callout pill, orientation badge (cardinal direction relative to garden centroid), and crop chips.',
            'Bed view rewritten as the merged tap-to-plant interface: per-line stripe bar showing proportional planting segments + dot grid (one dot per 5 cm, tap empty dot to plant the active crop), stepper chips for each crop on the line, and a sticky active-crop palette at the bottom for tap selection.',
            'New inline planning view (/items/{id}/planting/inline): per-line maturity stripe with elapsed-progress tick, sown→harvest dates, succession card with "Pick what comes next" picker, rotation warnings for same-family follow-ons, and rotation-history pills.',
            'New 4-month timeline view (/items/{id}/planting/timeline, desktop-only): 16-week grid with TODAY accent line, current plantings rendered as gradient bars (solid → 33% alpha at maturity), successions rendered with diagonal stripes + dashed border, plus a property-wide rotation memory panel grouped by year.',
            'Per-line state table (garden_bed_lines): persists succession queue (crop + start date), empty_since, last_watered_at, length_cm override, and rotation_history JSON array. Auto-hydrated when a bed is opened.',
            'Seed catalog gains four redesign fields: family (root/leaf/fruit/herb/allium/legume), season (cool/warm), emoji (1-glyph), color (hex). Existing seeds default to "other"/"any" until tagged.',
            'Pure server helpers in App\\Support\\GardenHelpers: lineHarvestDate, daysToLineHarvest, maturity, rotationWarning, getSuggestions, bedActions, bedOrientation, propertySummary — fully unit-testable.',
            'Tap-to-plant endpoint: POST /items/{id}/plant-tap adds 1 plant of cropId to (line_number), creating the planting row if absent and clearing empty_since on that line.',
            'Succession set/clear endpoints: POST /items/{id}/lines/succession/set and /items/{id}/lines/succession/clear.',
            'Harvest workflow: harvesting a line now appends the cleared cropIds to that line\'s rotation_history (year + season + cropId) so future succession pickers can warn on same-family clashes.',
            'Active-crop palette persists last selected crop in localStorage per bed (key rooted.activeCrop.{bedId}); garden hub section collapse state persists in localStorage (key rooted.garden.collapsed).',
        ],
        'improved' => [
            'Urgency palette tokens added to app.css (--urg-{high,med,low,water,sow,plan}-bg/bdr/text) for consistent action coloring across the hub, succession warnings, and modal alerts.',
            'Bed dimensions display switched to compact mono format (4×1.2m) with tabular numerals.',
            'Mode tabs at the top of every bed view (Plant / Plan / Timeline) for quick switching between the three workflows on the same bed.',
            'GardenSchema::ensure() — single idempotent migration entrypoint called from every garden controller; safe to run repeatedly, adds columns/tables only when missing.',
        ],
        'fixed' => [
            'Bed-line view no longer assumes a single planting per line — multiple plantings render side-by-side in the dot grid based on each crop\'s in-row spacing.',
            'Rotation-history queries no longer fail silently when garden_bed_lines is missing — the schema bootstrap creates it on first access.',
        ],
    ],

    '2.8.9' => [
        'date'  => '2026-04-27',
        'title' => 'Fix fill bar reset to 0 after +/- click',
        'fixed' => [
            'The qty span was missing the data-qty attribute that updateLineFill() uses to read the current plant count — caused the fill bar to always recalculate as 0cm after any +/- click.',
            'Fill calculation confirmed correct: used_cm = plant_count × spacing_cm (inline within-row spacing, not row_spacing_cm).',
        ],
    ],

    '2.8.8' => [
        'date'  => '2026-04-27',
        'title' => 'Multi-crop lines, proportional fill, GPS masonry map',
        'new' => [
            'A bed line can now hold multiple crops simultaneously — each planting is a separate row under the line header. Use ＋ Add on any line to add another crop without replacing what is already there.',
            'Companion quick-add and the full seed dropdown now plant on the SAME line as the open companion panel, not a new empty line.',
            'Bed SVG shows proportional fill: the coloured stripe = (plant count × spacing) ÷ bed length. Overflow shows a ⚠ marker.',
            'Line header has a green/red fill bar (used cm / total cm) that updates live with +/− buttons.',
        ],
        'improved' => [
            'GPS masonry map: beds sorted north→south (rows) and west→east (within row), rendered as tight flex shelves — no isolated beds in empty corners.',
            'Bed thumbnail: width_m = E-W screen width, length_m = N-S screen height. EW-row beds show horizontal bands; NS-row beds show vertical stripes.',
            'Edit forms are per-planting (keyed by planting ID): editing one crop never overwrites other crops on the same line.',
        ],
        'fixed' => [
            'storeLine() now UPDATEs by planting_id when editing, and INSERTs when adding — previously always UPSERTed by line_number, overwriting any other planting on that line.',
        ],
    ],

    '2.8.7' => [
        'date'  => '2026-04-27',
        'title' => 'Companions: variety-aware quick-add + GPS bed map',
        'fixed' => [
            'quickAddCompanion() now posts crop_name and line_number (was posting wrong field names "crop" and "line"), fixing companion quick-add silently failing.',
            'variety is now passed in FormData when quick-adding a companion from the panel.',
        ],
        'improved' => [
            'rankCompanions() stores the variety of the best-scoring seed so companion suggestions now show specific variety names (e.g. "Carotte — Nantes" instead of just "Carotte").',
            'Companions panel passes seed_id of the current line to the API so the server can detect other varieties of the same crop.',
            'Companion variants (other varieties of the same plant) are attached to each companion entry and shown in the "Add any seed" dropdown.',
            '"Other varieties" chip row appears at the top of the companions panel when the current line has sibling seeds with different varieties.',
            'Each companion now shows its specific variety in the panel name.',
            '"Add any seed" full-catalog dropdown added to the companions panel, grouped by name with optgroups for multi-variety plants, antagonist options disabled.',
        ],
        'new' => [
            'GPS-relative bed positioning on the garden index: when ≥2 beds in a group have gps_lat/gps_lng, they are rendered on a 360×260px canvas positioned by their GPS coordinates. Falls back to the existing flex grid when fewer than 2 beds have GPS data.',
        ],
    ],

    '2.8.6' => [
        'date'  => '2026-04-27',
        'title' => 'Buy list merged into Tasks → Achats',
        'improved' => [
            'Seeds marked as "Out of seed" now appear directly in Tasks → Achats under a 🌱 GRAINES group — no separate Buy List page or nav entry.',
            'Checking a seed row in Achats marks it as back in stock instantly (AJAX, no reload). Row fades out on success.',
            'The "Buy List" nav link is removed. /seeds/buy-list redirects to /tasks?tab=achats.',
            'SeedController::markBought() now returns JSON when called with _ajax=1, matching the tasks pattern.',
        ],
    ],

    '2.8.5' => [
        'date'  => '2026-04-27',
        'title' => 'Companions: dedupe + quick-add + over-capacity warning',
        'improved' => [
            'Companion suggestions now deduplicate by plant name — one entry per vegetable regardless of how many varieties exist in the seed bank, keeping the highest-scoring match.',
            'Each companion suggestion has a "+ Add" button that immediately plants it on the first empty bed line (status: planned, qty: 1) and reloads.',
            'seed_id is now included in companion response so the quick-add wires up to the correct seed.',
            'Companions button is now always shown for planted lines (no longer requires an API key setting).',
        ],
        'new' => [
            'Over-capacity warning on bed line cards: red border + "⚠ over capacity" label appears when plant count exceeds the calculated capacity (bed length ÷ seed spacing). Updates live when using +/− buttons without a full page reload.',
        ],
    ],

    '2.8.4' => [
        'date'  => '2026-04-27',
        'title' => 'Seed form: manual AI prompt copy/paste pre-fill',
        'new' => [
            'Manual AI prompt panel on Add/Edit Seed: click "Generate with external AI" to expand a collapsible panel with a ready-to-copy prompt including the full JSON schema.',
            'Paste the AI JSON response into the second box and click "Pre-fill form" — the form fills automatically and the panel closes.',
            'Handles markdown code fences and surrounding text automatically; shows a clear error if the JSON is invalid.',
        ],
    ],

    '2.8.3' => [
        'date'  => '2026-04-27',
        'title' => 'Companion planting: data-driven suggestions from seed bank',
        'improved' => [
            'Companions panel no longer calls an external AI API — suggestions are now derived purely from the companions & avoid lists stored in your own seed bank.',
            'Returns up to 6 ranked companions scored by mutual companion mentions across seeds.',
            'Other crops already in the same bed are passed automatically; any that conflict with the suggested companion are flagged with a warning in the reason text.',
            'Bed conflicts with the target crop itself appear in a separate "Conflicts in this bed" section.',
            'Added reusable GardenBedController::rankCompanions() static helper usable from any other controller.',
            'Helpful message shown when no companion data exists yet instead of an opaque API error.',
        ],
    ],

    '2.8.2' => [
        'date'  => '2026-04-27',
        'title' => 'Fix save network error + harvest modal + qty +/- + auto-fill from seed',
        'fixed' => [
            'storeLine() and trashLine() now detect AJAX via _ajax POST field instead of X-Requested-With header — resolves the "Network error" alert when saving or clearing a bed line.',
        ],
        'new' => [
            'Harvest modal: Planned/Growing lines now show a Harvest button that opens a modal for recording quantity, unit, and notes before marking the line harvested.',
            'Plant count +/− buttons on each line for quick quantity adjustments without opening the edit form.',
            'Route and controller method for POST /garden/plantings/{id}/harvest-line and /garden/plantings/{id}/adjust-qty.',
        ],
        'improved' => [
            'Seed chip auto-fills Notes with sowing depth, spacing, row spacing, and seed notes when the field is empty.',
            'Selecting a seed chip auto-calculates harvest date from planted date + days_to_maturity.',
            'Capacity warning shown when plant count exceeds what fits in the line at the given spacing.',
            '$allSeeds query now fetches row_spacing_cm, sowing_depth_mm, days_to_maturity, and notes for richer auto-fill.',
        ],
    ],

    '2.8.1' => [
        'date'  => '2026-04-27',
        'title' => 'Fix: bed planting page crash on first visit',
        'fixed' => [
            'Opening /items/{id}/planting now works even if the Seeds section has never been visited. The seeds.needs_restock column is created on demand; if anything fails the suggestions panel gracefully shows empty rather than crashing the page.',
        ],
    ],

    '2.8.0' => [
        'date'  => '2026-04-27',
        'title' => 'Smart Garden — Buy List, Duplicate Detection, Gemini Model Picker, Bed Suggestions',
        'new' => [
            'Seeds → Buy List: mark any seed as "Out of seed" from its detail page — it immediately appears on the /seeds/buy-list page with planting-month urgency indicators and a "Bought" button to remove it.',
            'Duplicate seed name detection: typing a name in the Add/Edit Seed form triggers a live check; a yellow warning with a direct link appears if the exact name already exists.',
            'Gemini model priority picker: Settings → AI → Cloud API now shows a "Fetch from API" button that retrieves all models available to your key. You select and order your own top-5 fallback list — the system no longer auto-picks models.',
            'Bed line smart suggestions: clicking Edit on a garden bed line shows tap-to-fill suggestion chips from your family needs, ordered by in-season first. Plant count is auto-calculated from bed length ÷ seed spacing.',
            'Planting backlog calendar: a 6-month forward view at the bottom of every garden bed page shows what family-needs crops should be planted each month, with "in bed" / "BUY FIRST" indicators.',
        ],
        'improved' => [
            'Garden bed edit form now includes a Plants count field and a seed datalist for autocomplete.',
            'Buy List nav link added to the main navigation.',
            'garden_plantings table now tracks seed_id and plant_count per line.',
            'AiController uses the user-saved Gemini model list; falls back to auto-selection only when no list is configured.',
            'Seeds show page: fixed a duplicate grid rendering bug.',
        ],
    ],

    '2.7.9' => [
        'date'  => '2026-04-27',
        'title' => 'AI — Codex Tab (session token, no API billing)',
        'improved' => [
            'Third AI tab renamed from "OpenAI" to "Codex" — uses a session token from Codex CLI or a compatible tool (OpenClaw etc.) instead of a paid API key.',
            'Setup guide covers two paths: Codex CLI (codex auth login → copy access_token from ~/.codex/auth.json) and browser tools like OpenClaw (copy the JWT from their settings page).',
            'No per-token billing — works through your existing ChatGPT Plus/Pro subscription.',
        ],
    ],

    '2.7.8' => [
        'date'  => '2026-04-27',
        'title' => 'AI — OpenAI Tab (GPT-4o / GPT-4o-mini)',
        'new' => [
            'Settings → AI now has three independent tabs: Local (Ollama), Cloud API (Gemini / HuggingFace), and OpenAI (GPT-4o).',
            'OpenAI tab includes a 4-step setup guide: create an API key on platform.openai.com, paste it in, done.',
            'Default OpenAI model is gpt-4o-mini (cheapest, excellent quality); gpt-4o and o4-mini are also supported.',
        ],
    ],

    '2.7.7' => [
        'date'  => '2026-04-27',
        'title' => 'AI — Bulletproof Gemini Fallback (5-model cap, watchdog, full recap)',
        'improved' => [
            'Model loop is now capped at 5 to avoid excessive API calls; only confirmed-available models are tried.',
            'When all models fail, the debug log shows the full available-model list again so you know exactly what\'s on your key and can update Settings → AI.',
            'SSE output buffering is fully disabled at the PHP level so events flush to the browser immediately, even on cPanel with proxy buffering.',
            'A 90-second client-side watchdog re-enables the Identify button and shows "try again" if the server goes silent — the UI can never get stuck.',
            'All error messages now end with "click Identify to try again" so the recovery action is always obvious.',
        ],
    ],

    '2.7.6' => [
        'date'  => '2026-04-27',
        'title' => 'AI — Real-Time Gemini Model Fallback via SSE',
        'improved' => [
            'The AI identify endpoint now streams Server-Sent Events so every step (fetching model list, trying each model, parsing response) appears in the debug panel in real time instead of all at once at the end.',
            'Available Gemini models are fetched live from Google\'s API before any scan — the app only ever calls models confirmed to be accessible with your API key.',
            'If the primary model is not in the confirmed list, a warning is shown in the log and only available models are tried.',
            'On 503/429 (overloaded/rate-limited), the app silently skips to the next confirmed model and shows the skip reason live in the log.',
        ],
    ],

    '2.7.5' => [
        'date'  => '2026-04-27',
        'title' => 'AI — Gemini Model Fallback on 503/429',
        'improved' => [
            'When the configured Gemini model returns 503 (overloaded) or 429 (rate-limited), the AI scanner now automatically retries with the next model in a prioritized fallback chain (gemini-2.5-flash → gemini-2.0-flash → gemini-1.5-flash → gemini-1.5-flash-8b) instead of returning an error.',
            'Debug output now shows which model was ultimately used and which ones were skipped.',
        ],
    ],

    '2.7.4' => [
        'date'  => '2026-04-27',
        'title' => 'Seeds — Stay on Add Form After Save',
        'improved' => [
            'After saving a new seed, the app now redirects back to the Add Seed form (instead of the seed detail page) with a success notice showing the seed name — makes it fast to add several seeds in a row.',
        ],
    ],

    '2.7.3' => [
        'date'  => '2026-04-27',
        'title' => 'Seed Detail — Mobile Layout Fix',
        'fixed' => [
            'Seed detail page: two-column layout was squeezing the calendar into 50% width on mobile — 12 month labels became unreadable. Now stacks to a single column on screens ≤640 px, with the calendar card promoted to the top so it is the first thing visible.',
        ],
    ],

    '2.7.2' => [
        'date'  => '2026-04-27',
        'title' => 'AI Seed Scan — Working',
        'fixed' => [
            'JSON truncated mid-response: maxOutputTokens was 1024 — too small for the full seed packet JSON. Raised to 4096 for both Gemini native and OpenAI-compatible paths.',
        ],
    ],

    '2.7.1' => [
        'date'  => '2026-04-27',
        'title' => 'AI Seed Scan — Gemini Model Discovery',
        'improved' => [
            'When Gemini returns 404 model-not-found, the controller now calls the ListModels API and shows every available Gemini model name for your key in the debug panel — no more guessing model names.',
        ],
    ],

    '2.7.0' => [
        'date'  => '2026-04-27',
        'title' => 'AI Seed Scan — Native Gemini API',
        'fixed' => [
            'Google Gemini OpenAI-compatible endpoint returned 404 for all model names — the translation layer does not accept standard model IDs. AiController now detects generativelanguage.googleapis.com and calls the native generateContent API directly, bypassing the broken OpenAI compat layer.',
        ],
        'improved' => [
            'Native Gemini API uses inline_data format for images (faster and more reliable than base64 data URLs). Model and API key are combined into the direct API URL. Response parsed from candidates[0].content.parts[0].text.',
        ],
    ],

    '2.6.9' => [
        'date'  => '2026-04-27',
        'title' => 'AI Seed Scan — Gemini 1.5 Flash + Error Parsing Fix',
        'fixed' => [
            'gemini-2.0-flash-exp does not support the OpenAI-compatible endpoint. Switched recommended model to gemini-1.5-flash which is stable and fully supported.',
            'Gemini API wraps error responses in a JSON array ([{"error":...}]) instead of an object — error parser now unwraps the array before extracting the message, so proper error text is shown in the debug panel instead of "HTTP 404".',
        ],
    ],

    '2.6.8' => [
        'date'  => '2026-04-27',
        'title' => 'AI Seed Scan — Google Gemini Free Tier',
        'improved' => [
            'Switched recommended cloud AI provider to Google Gemini API (aistudio.google.com) — permanent free tier (15 req/min, 1500/day), stable model names, and Gemini 2.0 Flash excels at OCR and seed packet reading.',
            'Endpoint URL: https://generativelanguage.googleapis.com/v1beta/openai/chat/completions — uses Google\'s official OpenAI-compatible interface, no API changes needed.',
            'Settings → AI quickstart updated with 3-step Google AI Studio setup (sign in, create key, paste values).',
        ],
    ],

    '2.6.7' => [
        'date'  => '2026-04-27',
        'title' => 'AI Seed Scan — OpenRouter Model Fix',
        'fixed' => [
            'OpenRouter model ID qwen/qwen2.5-vl-7b-instruct:free does not exist on OpenRouter. Default recommendation switched to google/gemini-2.0-flash-exp:free which is a verified free vision model on OpenRouter.',
            'PHP fatal error when OpenRouter returns error as a JSON object instead of a plain string — jsonError() now extracts message from nested error objects.',
        ],
        'improved' => [
            'Settings → AI: quickstart now shows google/gemini-2.0-flash-exp:free as the recommended model with meta-llama/llama-3.2-11b-vision-instruct:free and microsoft/phi-4-vision-instruct:free as alternatives.',
        ],
    ],

    '2.6.6' => [
        'date'  => '2026-04-27',
        'title' => 'AI Seed Scan — 500 Fix',
        'fixed' => [
            'HTTP 500 on all requests after v2.6.2 introduced LimitRequestBody in .htaccess. Apache on cPanel requires AllowOverride Limit to honour this directive in .htaccess — without it, Apache returns 500 for the entire directory. Directive removed; images are now small enough (800px/72%) that the existing post_max_size 30M is sufficient.',
            'AI controller now wraps the entire request in try/catch and returns JSON on any unhandled exception, instead of falling through to the HTML error page.',
        ],
    ],

    '2.6.5' => [
        'date'  => '2026-04-27',
        'title' => 'AI Seed Scan — Switch to OpenRouter',
        'fixed' => [
            'HuggingFace free serverless API does not host Qwen2.5-VL-7B-Instruct — all API attempts returned 404. Switched the recommended free cloud AI provider to OpenRouter (openrouter.ai), which hosts the same model (and Gemini Flash, Llama 3.2 Vision) with a simple OpenAI-compatible API and no server setup.',
            'Simplified endpoint URL normalisation in AiController — removed incorrect HuggingFace-specific URL path building that was causing confusion.',
        ],
        'improved' => [
            'Settings → AI → Cloud: quickstart now shows OpenRouter steps (3 minutes, no credit card, free tier). Free models listed: qwen/qwen2.5-vl-7b-instruct:free, google/gemini-2.0-flash-exp:free, meta-llama/llama-3.2-11b-vision-instruct:free.',
            'AiController now sends HTTP-Referer and X-Title headers when the endpoint is openrouter.ai — required for free-tier access on some models.',
        ],
    ],

    '2.6.4' => [
        'date'  => '2026-04-27',
        'title' => 'AI Seed Scan — HuggingFace URL Fix',
        'fixed' => [
            'HuggingFace serverless API returned 404 "Cannot POST /v1/chat/completions". The api-inference.huggingface.co endpoint requires the model ID in the URL path (/models/{model}/v1/chat/completions). AiController now detects this host and auto-inserts the model into the URL, so the old generic endpoint URL no longer causes a 404.',
            'Settings → AI: corrected all displayed example URLs and the endpoint input placeholder to show the model-specific path.',
        ],
    ],

    '2.6.3' => [
        'date'  => '2026-04-27',
        'title' => 'AI Seed Scan — HuggingFace Payload Fix',
        'fixed' => [
            'HuggingFace free-tier API returns 413 "request entity too large" when two seed packet photos are sent together. The client-side compression target is now 800 px max dimension at JPEG 72% quality (down from 1280 px / 82%) — each image lands at 80–150 KB, well within HuggingFace\'s ~1 MB JSON body limit.',
        ],
    ],

    '2.6.2' => [
        'date'  => '2026-04-27',
        'title' => 'AI Seed Scan — Image Compression Fix',
        'fixed' => [
            'HTTP 413 "Request Entity Too Large" when uploading seed packet photos — phone camera images (4–8 MB) were sent as raw base64, exceeding the server\'s request body limit before PHP could run. Images are now resized to a max of 1280 px and recompressed to JPEG at 82% quality client-side before upload (typically 150–400 KB each). The status line shows the compressed size after loading each photo.',
            'Added LimitRequestBody 50 MB to .htaccess and a mod_php8.c block so PHP upload limits are honoured on both PHP 7 and PHP 8 cPanel installs.',
        ],
        'improved' => [
            'AI pipeline: smaller images reach HuggingFace faster and consume fewer tokens — identification is noticeably quicker on slow connections.',
        ],
    ],

    '2.6.1' => [
        'date'  => '2026-04-27',
        'title' => 'AI Seed Scan — Month Icons & Editable Prompt',
        'new' => [
            'AI prompt now reads calendar icon rows (Semis/Récolte, Sowing/Harvest, etc.) and auto-ticks the planting and harvest month checkboxes on the seed form.',
            'Seed form: "✏️ Extra instructions" collapsible textarea in the AI panel lets you tweak the prompt per-upload without changing the saved setting.',
            'Settings → AI → HuggingFace: step-by-step 3-minute Qwen quickstart guide (no SSH, no server installation, free tier).',
        ],
        'improved' => [
            'AI prompt enriched with detailed icon-reading instructions: sun/shade, spacing arrows, depth markers, germination clocks, and harvest day ranges (midpoint calculated automatically).',
            'Recommended model updated to Qwen/Qwen2.5-VL-7B-Instruct — better at OCR and structured label reading than Llama-Vision on the free tier.',
            'extra_prompt_override POST field: the per-upload textarea value is sent alongside images and overrides the saved extra_prompt for that request only.',
        ],
    ],

    '2.6.0' => [
        'date'  => '2026-04-27',
        'title' => 'AI Seed Scan — HuggingFace & Dual-Photo',
        'new' => [
            'Settings → AI: two-mode selector — "🖥 Local (Ollama / Raspberry Pi)" or "🤗 HuggingFace". Selecting one mode hides the other\'s config. Each mode has a full setup guide.',
            'HuggingFace mode: paste any HF Inference API endpoint URL (serverless or dedicated) + API token + model ID. Supports OpenAI-compatible chat/completions format used by Llama-3.2-Vision, Qwen-VL, etc.',
            'Seed form: new "Back of packet" photo slot alongside the existing front photo — both images are sent to the AI together for richer identification.',
            'Settings → AI: "Extra Prompt Instructions" textarea — append custom text to every AI prompt (e.g. "Answer in French", "Focus on Mediterranean varieties").',
            'Seed form: temporary debug status panel (yellow, collapsible) shows every step of the AI pipeline: mode selected, images sent, HTTP status, raw AI text, JSON parse result, fields filled.',
        ],
        'improved' => [
            'AI endpoint now normalises the response from both Ollama (/api/generate) and HuggingFace (OpenAI chat completions) through a single parseAndRespond() function.',
            'Identify button only appears after at least one photo is loaded, replacing the old auto-send-on-file-select behaviour.',
            'Progress bar shows accurate step-by-step progress (10 → 20 → 75 → 100%) instead of guessed percentages.',
        ],
    ],

    '2.5.14' => [
        'date'  => '2026-04-26',
        'title' => 'This Week Tasks',
        'new' => [
            'Tasks: new "📅 This Week" section between Today and Backlog — plan tasks for the week without marking them as urgent for today.',
            'Each task row now has ☀️ (Today) and 📅 (This Week) toggle buttons so you can move tasks between sections with one tap.',
            'Dashboard widget: added "📅 Week" tab alongside Today, Tasks, and Achats.',
        ],
        'fixed' => [
            'PWA reopen bug: every time the app was brought back from background, the quick-add input stopped working until the page was refreshed. A CSRF token refresh now runs automatically on visibility change, keeping the session alive.',
        ],
    ],

    '2.5.13' => [
        'date'  => '2026-04-25',
        'title' => 'Achats Bulk Add',
        'new' => [
            'Achats tab: "📋 Bulk" button below the input opens a multi-line textarea. Type (CATEGORY) on the first line then one item per line, press Enter for a new line, then "Done" to save them all at once as individual achat items.',
        ],
    ],

    '2.5.12' => [
        'date'  => '2026-04-24',
        'title' => 'Fix Upgrade URL (slash branch)',
        'fixed' => [
            'CRITICAL: "Update Now" returned 404 — the raw.githubusercontent.com URL regex did not handle branch names containing slashes (e.g. claude/feature-xyz). Both update_zip_url and update_version_url now use the GitHub API URL format directly, bypassing the regex entirely.',
        ],
    ],

    '2.5.11' => [
        'date'  => '2026-04-24',
        'title' => 'Garden Bed UX & GPS Zoom',
        'new' => [
            'Garden Bed edit page now shows a layout card at the top — set width, length, lines, and direction with a live SVG preview that updates as you type.',
            'GPS Detect Zoom Level setting in Settings → GPS & Photos — choose how far to zoom in after tapping "Locate Me" (default: 18).',
        ],
        'improved' => [
            '"Garden" and "Line" item types are now hidden from the + New Item dropdown — beds are created directly, lines are managed inside beds.',
            'Renamed item type "Bed" to "Garden Bed" throughout the interface.',
            'Map preview (corner wizard and walk boundary) now stays zoomed in (maxZoom 20) after drawing a polygon.',
            '"Locate Me" button now zooms the map to the configured GPS detect zoom level after getting a fix.',
        ],
    ],

    '2.5.10' => [
        'date'  => '2026-04-24',
        'title' => 'PWA & CSRF Fixes',
        'fixed' => [
            'Service worker (v8) no longer caches authenticated HTML pages — prevents stale CSRF tokens when using the installed PWA.',
            'CSRF failure now redirects back to the previous page with a clear error message instead of showing a raw 403 page.',
        ],
    ],

    '2.5.9' => [
        'date'  => '2026-04-24',
        'title' => 'Upgrade via GitHub API',
        'improved' => [
            '"Update Now" now downloads via the GitHub API instead of raw CDN — always fetches the latest version, no more stale cache.',
        ],
        'fixed' => [
            'PHP syntax error in GardenBedController (orphaned try block removed).',
        ],
    ],

    '2.5.8' => [
        'date'  => '2026-04-24',
        'title' => 'Bed Lines & Garden Workflow',
        'new'   => [
            'Garden hub: "+ New Garden" and "+ New Bed" buttons create the right item type directly.',
            'Bed planting page: Configure Lines panel — set number of lines, direction (N–S / E–W), width and length, with live spacing calculation.',
        ],
        'fixed' => [
            'CRITICAL: Bed planting page crashed with 500 — wrong column name (meta_value vs meta_value_text) in the meta query.',
            'Items create: URL ?type=garden or ?type=bed pre-selects the type in the dropdown.',
        ],
    ],

    '2.5.6' => [
        'date'  => '2026-04-24',
        'title' => 'Instant Version Check',
        'improved' => [
            'Upgrade panel now checks for new versions via a tiny version.json file instead of downloading the full 400 KB ZIP — instant and always current.',
        ],
        'fixed' => [
            'Upgrade: added no-cache request headers so GitHub CDN always serves the latest ZIP.',
            'Garden bed page: $currentMonth now has a safe fallback so the page never crashes if the variable is missing.',
        ],
    ],

    '2.5.5' => [
        'date'  => '2026-04-24',
        'title' => 'Hardening & Cache Fix',
        'fixed' => [
            'Upgrade: added no-cache request headers so GitHub CDN always serves the latest ZIP.',
            'Garden bed page: $currentMonth now has a safe fallback so the page never crashes if the variable is missing.',
        ],
    ],

    '2.5.4' => [
        'date'  => '2026-04-24',
        'title' => 'Bed Page & Edit Fixes',
        'fixed' => [
            'Garden bed planting page crashed with "Something went wrong" — missing $currentMonth variable passed to view.',
            'Task inline edit input now stretches to full row width when double-clicking to rename.',
        ],
    ],

    '2.5.3' => [
        'date'  => '2026-04-24',
        'title' => 'Garden Beds & Task Fixes',
        'new'   => [
            'Garden schematic: proportional bed rectangles on the Garden hub, grouped by garden, coloured by crop status (green=growing, amber=planned, blue=harvested, grey=empty). Click any bed to open its planting view.',
            'Bed planting view (/items/{id}/planting): manage what is planted per line, with status, planted/harvest dates, and inline editing.',
            'Companion planting AI: ☘ Companions button on each line fetches suggestions (companions, antagonists, succession tip) from a configurable OpenAI/Anthropic/custom endpoint.',
            'Companion API settings: provider, API key, model, and custom URL configurable in Settings.',
        ],
        'improved' => [
            'Today tasks can now be dragged to reorder within the Today section.',
            '☀️ Today button now correctly moves tasks between Today and Backlog sections after toggling.',
        ],
        'fixed' => [
            'Finance delete button returned 404 due to hardcoded /finance/ path — now uses BASE URL.',
        ],
    ],

    '2.5.2' => [
        'date'  => '2026-04-23',
        'title' => 'Today Tab',
        'new'   => [],
        'improved' => [
            'Dashboard: "Today" tab shown first — all today-planned tasks in a dedicated green panel. Defaults to Today when tasks are planned.',
            'Dashboard "Tasks" tab now shows only backlog tasks (non-today), up to 5.',
            'Tasks page: today tasks rendered in a green card section above the backlog with a task count. Backlog label appears below.',
            'Group-by-tag merges today and backlog rows together so tags span both sections.',
        ],
        'fixed' => [],
    ],

    '2.5.1' => [
        'date'  => '2026-04-23',
        'title' => 'Today Focus',
        'new'   => [],
        'improved' => [
            '"Important" renamed to "Today" — mark tasks the night before to build your focus list for the next day.',
            'Today tasks show a green left border and "☀️ Today" section header; backlog tasks appear below a "— Backlog" divider.',
            'Dashboard task list highlights today tasks in green with a ☀️ badge.',
            'Group-by-tag toggle hides the Today/Backlog section headers to avoid conflicts.',
        ],
        'fixed' => [],
    ],

    '2.5.0' => [
        'date'  => '2026-04-23',
        'title' => 'Survey & Quick Actions',
        'new'   => [
            'Photo survey flow: staged multi-step survey (General 4 photos → Budding 3 photos → Health 2 photos) accessible from every item.',
            'Budding scale 1–10 slider in the survey for olive trees and almond trees, logged as an activity entry.',
            'Four quick action buttons on every item row: Irrigation, Photo, Survey, Log.',
            'Garden bed positioning: anchor GPS point + corner selector + Height N–S / Width E–W dimensions + line direction (NS/EW).',
            'Polygon nudge controls: move a saved bed boundary ←→↑↓ by 0.5 m / 1 m / 5 m without re-entering GPS.',
        ],
        'improved' => [
            'Tasks: group-by-tag toggle to re-order tasks under tag headers; auto-suggest tags on ( + 1 letter.',
            'Session lifetime extended to 30 days; remember_me enabled by default — no more surprise logouts after updates.',
            'Bottom nav: Photos entry replaced with Items for faster item list access.',
        ],
        'fixed' => [],
    ],

    '2.4.9' => [
        'date'  => '2026-04-22',
        'title' => 'Task Inline Edit',
        'new'   => [
            'Double-click (or double-tap) any task or achat title to rename it inline — works on both the Tasks page and the Dashboard widget.',
            '"Clear done" button at the top of each list removes all completed tasks/achats in one tap.',
        ],
        'improved' => [],
        'fixed' => [],
    ],

    '2.4.8' => [
        'date'  => '2026-04-22',
        'title' => 'Nav & Cache',
        'new'   => [
            'Bottom nav: 7 items — Home, Photos, Map, +, Garden, Harvest, Tasks.',
            'Image caching: proper Cache-Control + ETag headers on attachment downloads; browsers cache images for 1 year.',
            'Settings: "Clear Image Cache" button forces all browsers to reload fresh images.',
        ],
        'improved' => [
            'Attachment images served inline (not as download) for faster display in the browser.',
        ],
        'fixed' => [],
    ],

    '2.4.7' => [
        'date'  => '2026-04-22',
        'title' => 'Infinite Scroll Items',
        'new'   => [
            'Items list — infinite scroll: rows load automatically as you scroll, with staggered fade-up animation.',
        ],
        'improved' => [],
        'fixed' => [],
    ],

    '2.4.6' => [
        'date'  => '2026-04-22',
        'title' => 'Tasks & Finance',
        'new'   => [],
        'improved' => [],
        'fixed' => [
            'Achats quick-add: first item now saves correctly when the list was empty (parent node was lost after removing empty state).',
        ],
    ],

    '2.4.5' => [
        'date'  => '2026-04-22',
        'title' => 'Tasks & Finance',
        'new'   => [
            'Tasks — Achats tab: dedicated shopping list with (STORE) tag grouping by store.',
            'Tasks — Drag-to-reorder: grab the ⠿ handle to reorder your to-do list manually.',
            'Tasks — Important flag: ⭐ button per task; important tasks get a gold border and float to the top.',
            'Tasks widget on Dashboard: tabbed To-Do / Achats panel with AJAX toggle, right after reminders.',
            'Finance — year navigation: browse entries by year with a select box.',
            'Finance — inline edit & delete on every entry row.',
            'Finance — association by item type (Olive Tree, Almond Tree, Vine…) or general land.',
            'Finance — CSV export per year and all-time.',
            'Settings › Harvest — financial tracking toggle per crop type with optional cost/revenue rule note.',
        ],
        'improved' => [
            'Finance form no longer requires picking a specific item — scope can be General, Item Type, or specific Item.',
        ],
        'fixed' => [],
    ],

    '2.4.4' => [
        'date'  => '2026-04-21',
        'title' => 'Nav & Tasks Upgrade',
        'new'   => [
            'Bottom nav restructured: Home (far left) and Tasks (far right) replace Harvest and Items for faster one-thumb access.',
            'Tasks: important flag — mark any task as important with a ⭐ button; important tasks float to the top.',
            'Tasks: drag-to-reorder — grab the handle to reorder your to-do list.',
            'Achats tab in Tasks — a dedicated shopping list with (STORE) category tags, grouped by store.',
        ],
        'improved' => [
            'FAB (+ button) made smaller and raised higher above the footer bar, freeing space for 5 nav items.',
            'Nav icon image enlarged to 84 × 84 px with tighter negative margin for a more prominent brand presence.',
            'Bottom nav items use tighter padding and smaller icons to fit 5 items comfortably.',
        ],
        'fixed' => [],
    ],

    '2.4.3' => [
        'date'  => '2026-04-21',
        'title' => 'Polish & Favicon',
        'new'   => [
            'Favicon upload in Settings — supports ICO, PNG, SVG with live 16/32/48 px previews and size guide.',
            'Task inline tags: type (TAG) at the start of a task to assign a category — shown as a colored pill badge. Same tag always gets the same color across all tasks.',
            'Live tag preview in the quick-add bar as you type the (TAG) prefix.',
            'Tasks item name shown as subtitle in the Reminders tab.',
        ],
        'improved' => [
            'Tasks quick-add: Enter to save instantly via AJAX, no form or submit button needed.',
            'Nearest to You cards: text no longer overflows in 2-column grid — name wraps, type/distance stack vertically.',
            'Google Calendar sync: snooze (+1d/+1w) now immediately updates the event time in Google Calendar.',
            'Completing or dismissing a reminder now deletes its Google Calendar event.',
            'Full calendar sync now updates all existing events, not only creates missing ones.',
            'Navigation icons replaced with clean SVG line icons (Lucide-style, white on transparent).',
            'Garden nav icon updated to basket shape.',
            'Reminder item name shown under title throughout the app.',
        ],
        'fixed'    => [
            'Dashboard reminder widget overdue items showing in red correctly.',
            'Snooze AJAX correctly updates the displayed date without page reload.',
        ],
    ],

    '2.4.2' => [
        'date'  => '2026-04-21',
        'title' => 'Calendar Sync Fix',
        'new'   => [],
        'improved' => [
            'Tasks quick-add: always-visible input bar, press Enter to save — no form, no button, instant AJAX row.',
            'Nearest to You cards: name now wraps 2 lines instead of clipping, type/distance stack vertically, smaller buttons.',
        ],
        'fixed' => [
            'Google Calendar events now update immediately when you snooze (+1d/+1w) a reminder.',
            'Completing or dismissing a reminder now deletes its Google Calendar event instead of leaving it orphaned.',
            'Full calendar sync (Settings → Calendar) now updates ALL existing events, not only creates missing ones.',
            'syncPendingReminders (auto-sync on reminders page load) now upserts all pending future reminders.',
        ],
    ],

    '2.4.1' => [
        'date'  => '2026-04-21',
        'title' => 'Smart Reminders',
        'new'   => [
            '+1 Day / +1 Week snooze buttons on every reminder row — push the due date forward without opening any form.',
            'Dashboard reminder widget now merges overdue and upcoming into one section, sorted by date.',
            '"All Reminders" popup modal on the dashboard when there are more than 5 pending reminders.',
            'Reminders section moved to the top of each item page — the first thing you see when opening an item.',
            'Item page reminders: full list of pending reminders with Done, +1d, +1w, and Dismiss quick-action buttons.',
            'Overdue reminders highlighted in red throughout the app (dashboard widget, item page, reminders page).',
            'Item name shown under reminder title on the dashboard widget.',
        ],
        'improved' => [
            'All reminder action buttons are now AJAX — no page reload needed.',
            'Reminders page action buttons restyled as compact pill buttons matching the rest of the app.',
        ],
        'fixed' => [],
    ],

    '2.4.0' => [
        'date'  => '2026-04-20',
        'title' => 'Tasks Hub',
        'new'   => [
            'Tasks section (/tasks) — a unified to-do hub with three tabs: To-Do, Reminders, and Irrigation.',
            'General To-Do list: add tasks with open categories (Building, Planting, Pruning, Maintenance, etc.), optional due date, and notes.',
            'Tick/untick tasks instantly via AJAX — no page reload.',
            'Archive tasks (📦) — removed from main list, kept in /tasks/archive with restore option.',
            'Show/hide completed tasks toggle.',
            'Overdue tasks flagged in red with ⚠ indicator.',
            'Slide-out animation when archiving or deleting tasks.',
            'Tasks added to top navigation (desktop + mobile drawer).',
        ],
        'improved' => [],
        'fixed'    => [],
    ],

    '2.3.1' => [
        'date'  => '2026-04-20',
        'title' => 'Dashboard Polish',
        'new'   => [
            '"What to do in the Garden" dashboard widget — 7-day scroll strip showing organ type and sow/harvest action for each day.',
            'Biodynamic "Right Now" section shows next 6 hours grouped by organ period — if the same organ runs for multiple hours, shown as one block; if multiple types, shown side by side.',
            'Garden page biodynamic widget — rich dark card showing today\'s action, crop suggestions, ascending/descending, and a 7-day mini strip.',
        ],
        'improved' => [
            '"Full Calendar" link now styled as a pill button (white on dark background) aligned to the far right of the header.',
            'Nearest to You cards now show 2 per row on all screen sizes.',
            'Weather hourly forecast fixed: now picks next 4 hours from the current moment (not just within today), so it always shows regardless of time of day.',
            'Biodynamic calendar hourly grid: organ emoji shown at each transition point, organ initial letter in each cell, bold hour labels at 0/6/12/18, taller cells (36px), left border on organ change for clear visual segmentation.',
        ],
        'fixed' => [
            'Weather hourly strip was empty when viewed after ~20:00 — fixed by comparing full timestamps instead of just the hour number.',
        ],
    ],

    '2.3.0' => [
        'date'  => '2026-04-20',
        'title' => 'Smart Irrigation',
        'new'   => [
            'Multiple irrigation plans per item — add overlapping or sequential plans to cover the full season.',
            'New interval options: every 3, 5, 10, and 20 days.',
            'Custom end date picker replaces fixed duration selector.',
            'Quantity in litres field — displayed in Google Calendar event title.',
            'Time preset for Google Calendar: Sunrise (6AM), Midday (12PM), Sunset (7PM), Night (9PM), or a custom hour. Leaves it as an all-day event if no time is set.',
            '"Irrigate Today" dashboard widget: shows all items due for watering today. Click ✓ Done to mark complete and log a Google Calendar event.',
            'Basket icon (🧺) for Garden in bottom and mobile navigation.',
            'All drawer/top navigation links now show emoji icons for quicker scanning.',
        ],
        'improved' => [
            'Biodynamic "Garden — Right Now" panel on dashboard: actionable advice (what to sow, harvest, or avoid) based on current organ type, ascending/descending moon, and anomaly detection. Replaces generic data display.',
            'Dashboard biodynamic section is now focused on the current moment with specific crop suggestions.',
        ],
        'fixed' => [],
    ],

    '2.2.0' => [
        'date'  => '2026-04-20',
        'title' => 'Biodynamic Garden',
        'new'   => [
            'Maria Thun Biodynamic Calendar (/garden/biodynamic): full hourly grid using Jean Meeus sidereal astronomy (Fagan-Bradley ayanamsa). Shows Root/Leaf/Flower/Fruit days, descending moon planting windows, and anomaly periods (lunar nodes, apogee/perigee ±6h).',
            'Crop search in biodynamic calendar: type a crop name (e.g. "tomatoes", "carrots") to highlight the correct organ days.',
            'Best planting days summary: automatically lists best Root/Leaf/Flower/Fruit days each month with descending moon.',
            'Climate-based planting suggestions in Garden Hub: built-in seasonal calendar for Mediterranean Sicily (default), N. Italy, Temperate Oceanic, and more. Shows what to plant this month based on your configured zone.',
            'Climate Zone setting added to Settings → General.',
            'Dashboard lunar section enhanced with biodynamic data: today\'s organ type, ascending/descending status, anomaly warning, and 7-day biodynamic week strip.',
        ],
        'improved' => [
            'Nav icon logo now shows circular badge style (border, padding, background) matching user\'s design.',
            'Nav has overflow:hidden to prevent layout overflow.',
            'Log entry detail modal fixed: DOMContentLoaded ensures modal elements are available before event listeners attach.',
            'Copy AI button fixed: lazy-init removed; direct getElementById used since modal HTML is before the script block.',
        ],
        'fixed' => [
            'Log modal closeBtn null addEventListener crash fixed.',
            'AI prompt modal null addEventListener crash fixed.',
        ],
    ],

    '2.1.9' => [
        'date'  => '2026-04-20',
        'title' => 'Garden Hub',
        'new'   => [
            'Garden Hub page (/garden): smart assistant-style mini-dashboard showing Plant This Month, Harvest Window, Active Bed Rows, Family Needs, Low Stock, and Recent Garden Activity.',
            'Navigation: top menu and bottom nav "Seeds" replaced with "Garden" linking to the new Garden Hub; Seeds catalog still accessible from within.',
            'Dashboard quick action "Seeds" button updated to link to the Garden Hub.',
        ],
        'improved' => [
            'Garden Hub stats bar gives an at-a-glance count of seeds, planting and harvest opportunities, active beds, and family needs.',
        ],
        'fixed' => [
            'Copy AI button: replaced lazy initModal() approach with direct getElementById calls now that modal HTML is guaranteed above the script block — resolves "Cannot read properties of null (reading addEventListener)" on every iOS and desktop browser.',
        ],
    ],

    '2.1.8' => [
        'date'  => '2026-04-20',
        'title' => 'Bug Fixes',
        'new'   => [],
        'improved' => [],
        'fixed' => [
            'Copy AI button: "Cannot read properties of null (reading addEventListener)" — modal HTML is placed after the script tag so getElementById returned null at load time; now looked up lazily on first click.',
            'Remember Me checkbox: moved into a proper left-aligned label element, checkbox and text on the same line.',
            'Dashboard quick actions: changed from flex-wrap to a strict 4-column grid so buttons always appear 4 per row.',
        ],
    ],

    '2.1.7' => [
        'date'  => '2026-04-20',
        'title' => 'Remember Me & Dashboard Links',
        'new'   => [
            'Remember Me on login — check the box to stay signed in for 30 days. Uses a secure hashed token stored in the database; signing out immediately revokes it.',
            'Dashboard quick actions: new Seeds, Family Needs, and Irrigation Plan buttons linking directly to those sections.',
            'New Irrigation Plans overview page (/irrigation) — lists all active plans with interval, duration, and start date; also shows irrigatable items that have no plan yet.',
        ],
        'improved' => [],
        'fixed'    => [],
    ],

    '2.1.6' => [
        'date'  => '2026-04-20',
        'title' => 'Map & UX Fixes',
        'new'   => [],
        'improved' => [
            'Quick action buttons (Add Photo, Reminder, Log, Copy AI, Survey) are now compact horizontal pills in a scrollable row — much less vertical space, easier to tap on mobile.',
            'Copy AI now shows the generated prompt in a bottom sheet. Tap "Copy to Clipboard" to copy it with a direct gesture — this fixes silent clipboard failures on iOS PWA where the async fetch broke the user-activation window.',
            'PWA icons now use separate "any" and "maskable" manifest entries. Uploading an icon generates properly padded maskable variants (logo at 72% of canvas with background fill) so Android adaptive icons no longer clip the edges. Icon URLs include a version stamp so the service worker cache is busted on each upload.',
            'Service worker bumped to v7 to clear cached icons for all installed PWA clients.',
        ],
        'fixed' => [
            'All items disappeared from the Land Map. The bed-rows query in /api/map/items used a non-existent "meta_value" column (the table only has "meta_value_text"), causing a SQL error that crashed the entire API endpoint and returned no features.',
            'Saving a boundary in Corner+Size mode also used the wrong "meta_value" column for bed_rows/bed_length_m/bed_width_m — those values were never actually saved to the database.',
        ],
    ],

    '2.1.5' => [
        'date'  => '2026-04-20',
        'title' => 'Irrigation Plan',
        'new'   => [
            'Per-item irrigation plans: set interval (twice daily / daily / every 2 days / weekly / biweekly / monthly), duration in months, start date, and optional notes. Available for trees, vines, gardens, and beds.',
            'If Google Calendar is connected, creating or updating an irrigation plan automatically adds a recurring calendar event titled "💧 Water — {item name}" at 7:00 AM for the full plan duration. Editing or deleting the plan removes and re-creates (or deletes) the calendar event.',
        ],
        'improved' => [],
        'fixed' => [],
    ],

    '2.1.4' => [
        'date'  => '2026-04-20',
        'title' => 'GPS Accuracy Visual & Bed Corner+Size Mode',
        'new'   => [
            'GPS accuracy is now color-coded in real time during boundary point sampling: 🟢 ≤5 m (excellent), 🟡 5–20 m (good), 🔴 >20 m (weak). Shows in the status bar during preciseSample() and in the corner GPS capture.',
            'New "📐 Corner + Size" mode on the edit page for beds and gardens: stand at one corner, tap Get Position to get a GPS fix, select which corner you are at (NE/NW/SE/SW), enter N-S length and E-W width in meters, and optionally enter the number of planting rows. Tap Preview to see the computed rectangle on the mini-map, then Save.',
            'Planting rows from Corner+Size mode are rendered as dashed parallel lines inside the bed polygon on the main land map.',
        ],
        'improved' => [
            'Bed boundary Save now also persists bed_length_m, bed_width_m, and bed_rows to item_meta so the map always has the latest dimensions.',
        ],
        'fixed' => [],
    ],

    '2.1.3' => [
        'date'  => '2026-04-19',
        'title' => 'Family Needs Mobile Fix & Copy AI Fix',
        'new'   => [],
        'improved' => [
            'Family Needs redesigned as cards instead of a table — works on all screen sizes. Edit button expands inline form, delete shows inline "Yes / Cancel" confirm. Notes now use a textarea and wrap properly.',
            'Copy AI button now works on iOS PWA even when a capture="environment" file input is present on the page. Falls back to execCommand if the Clipboard API is unavailable or denied.',
        ],
        'fixed' => [
            'Family Needs edit and delete buttons were cut off on mobile because the table overflowed the screen width.',
            'Copy AI button was stuck on "Building…" on iOS PWA — navigator.clipboard.writeText() was silently failing due to an iOS bug triggered by the compass survey capture input.',
        ],
    ],

    '2.1.2' => [
        'date'  => '2026-04-18',
        'title' => 'Family Needs Edit & Delete',
        'new'   => [],
        'improved' => [
            'Each family need row now has an ✏️ Edit button that expands an inline edit form — change vegetable name, linked seed, quantity, unit, priority, or notes without leaving the page.',
            'Delete confirmation replaced with inline "Remove? Yes / No" buttons — no window.confirm(), works correctly in PWA standalone mode.',
        ],
        'fixed' => [],
    ],

    '2.1.1' => [
        'date'  => '2026-04-18',
        'title' => 'CSRF Login Fix',
        'new'   => [],
        'improved' => [
            'Service worker bumped to v6 to force all PWA clients to re-register with the new fetch strategy.',
        ],
        'fixed' => [
            'Login page returned "403 — Invalid or missing CSRF token" on PWA installs. The service worker was caching HTML pages containing embedded CSRF tokens; on subsequent visits it served the stale cached page whose token no longer matched the PHP session. Navigation requests (HTML pages) now always bypass the cache and go direct to the network, so the CSRF token is always fresh.',
        ],
    ],

    '2.1.0' => [
        'date'  => '2026-04-18',
        'title' => 'Walk Mode Live Feedback',
        'new'   => [
            'Red "⏺ REC · X pts · Y m · ±Z m" overlay banner on the map itself — visible while GPS walk is recording, impossible to miss.',
            'Live position dot (red circle) follows your GPS position on the map during walk mode, so you can see the trail being drawn behind you.',
        ],
        'improved' => [
            '"Add GPS Point" now shows a toast notification on the map ("📍 Point 3 saved · ±4 m") in addition to the sidebar status text.',
            'Service worker bumped to v5 to push updated map.js and map.css to all PWA installs.',
        ],
        'fixed' => [],
    ],

    '2.0.9' => [
        'date'  => '2026-04-18',
        'title' => 'Yearly Compass Survey',
        'new'   => [
            'Annual compass survey: tap "🧭 Survey" on any item\'s page to start a guided 4-photo capture flow (South → East → North → West). One tap per direction, thumbnail preview after each, then upload all 4 at once with a single button. Photos are saved directly to the item gallery as yearly_refresh_* categories.',
        ],
        'improved' => [
            'Quick action bar on item page now has 5 buttons (added Survey alongside Add Photo, Reminder, Log, AI).',
            'Survey uses rear camera directly via capture="environment" — no gallery picker, no extra taps.',
        ],
        'fixed' => [],
    ],

    '2.0.8' => [
        'date'  => '2026-04-18',
        'title' => 'Fix APP_BASE URL Bug',
        'new'   => [],
        'improved' => [],
        'fixed' => [
            'Tree-type "Save as new type" now works correctly — the AJAX POST URL was malformed due to APP_BASE having no trailing slash, causing /rootedpath instead of /rooted/path. Fixed in create, edit, and photos views.',
            'Photo category and caption AJAX calls in the photos gallery view were also affected by the same APP_BASE slash bug and are now fixed.',
        ],
    ],

    '2.0.7' => [
        'date'  => '2026-04-18',
        'title' => 'Boundary Walk in Edit Item',
        'new'   => [
            'Walk boundary mode is now available directly on the item edit page — no need to go to the map. Works for all boundary-able types (garden, bed, orchard, zone, etc.).',
            'Edit item boundary section: walk the path, preview the polygon on the mini-map, then save or discard. Existing boundary can also be deleted inline.',
        ],
        'improved' => [
            'minimap.js exposes window.miniMapLeaflet so the boundary polygon can be drawn on the edit-form mini-map.',
        ],
        'fixed' => [
            'GPS permission denied no longer leaves walk mode stuck on "Waiting for GPS fix…" forever. RootedGPS now notifies continuous subscribers (walk mode) with null when permission is denied, so they can show the error immediately.',
        ],
    ],

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
