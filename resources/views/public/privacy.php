<?php $layout = 'public'; ?>

<style>
    .privacy-hero {
        background: var(--color-primary);
        color: #fff;
        border-radius: var(--radius-lg);
        padding: var(--spacing-8) var(--spacing-6);
        margin-bottom: var(--spacing-6);
        text-align: center;
    }
    .privacy-hero h1 { font-size: 2rem; margin-bottom: var(--spacing-3); }
    .privacy-hero p  { font-size: 1.05rem; opacity: .88; max-width: 560px; margin: 0 auto; }
    .privacy-features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--spacing-4);
        margin-bottom: var(--spacing-6);
    }
    .privacy-feature {
        background: #fff;
        border: 1px solid var(--color-border);
        border-radius: var(--radius);
        padding: var(--spacing-4);
    }
    .privacy-feature-icon { font-size: 1.8rem; margin-bottom: var(--spacing-2); }
    .privacy-feature h3   { font-size: .95rem; margin-bottom: 4px; }
    .privacy-feature p    { font-size: .8rem; color: var(--color-text-muted); margin: 0; }
    .privacy-section { margin-bottom: var(--spacing-6); }
    .privacy-section h2 {
        font-size: 1.2rem;
        color: var(--color-primary);
        border-bottom: 2px solid var(--color-border);
        padding-bottom: var(--spacing-2);
        margin-bottom: var(--spacing-4);
    }
    .privacy-section p, .privacy-section li {
        font-size: .9rem;
        color: #444;
        line-height: 1.75;
    }
    .privacy-section ul { padding-left: var(--spacing-5); }
    .privacy-section li { margin-bottom: 4px; }
    .privacy-updated {
        font-size: .8rem;
        color: var(--color-text-muted);
        margin-bottom: var(--spacing-6);
    }
    .privacy-contact {
        background: #f0f7f0;
        border: 1px solid #b6d9b6;
        border-radius: var(--radius);
        padding: var(--spacing-5);
        font-size: .9rem;
        color: #444;
    }
</style>

<!-- Hero: what is Rooted -->
<div class="privacy-hero">
    <div style="font-size:3rem;margin-bottom:var(--spacing-3)">🌿</div>
    <h1>Rooted</h1>
    <p>A private farm &amp; land management app. Track your plants, trees, harvests, tasks, and finances — all in one place, on your own server.</p>
</div>

<!-- Feature highlights -->
<div class="privacy-features">
    <div class="privacy-feature">
        <div class="privacy-feature-icon">🗺️</div>
        <h3>Interactive Map</h3>
        <p>Place items on a satellite map of your land with custom boundaries and zones.</p>
    </div>
    <div class="privacy-feature">
        <div class="privacy-feature-icon">🌱</div>
        <h3>Item Tracking</h3>
        <p>Log trees, plants, animals, infrastructure — with notes, photos, and actions.</p>
    </div>
    <div class="privacy-feature">
        <div class="privacy-feature-icon">📅</div>
        <h3>Reminders</h3>
        <p>Schedule tasks and pruning dates. Sync to Google Calendar.</p>
    </div>
    <div class="privacy-feature">
        <div class="privacy-feature-icon">🌾</div>
        <h3>Harvests</h3>
        <p>Record harvest dates, quantities, and yields over time.</p>
    </div>
    <div class="privacy-feature">
        <div class="privacy-feature-icon">💰</div>
        <h3>Finance Log</h3>
        <p>Track income and expenses per item or across the whole farm.</p>
    </div>
    <div class="privacy-feature">
        <div class="privacy-feature-icon">🔒</div>
        <h3>Self-Hosted</h3>
        <p>Runs on your own server or cPanel hosting. Your data never leaves your control.</p>
    </div>
</div>

<p class="privacy-updated">Privacy Policy &mdash; Last updated: April 2026</p>

<!-- Privacy policy sections -->
<div class="privacy-section">
    <h2>1. About this application</h2>
    <p>Rooted is a self-hosted web application for managing farms, homesteads, and land. It is installed and operated by the site owner on their own server. Anthropic / the Rooted project does not operate any shared cloud service — all data is stored in the database configured during installation.</p>
</div>

<div class="privacy-section">
    <h2>2. Who operates this instance</h2>
    <p>This installation of Rooted is operated by the site owner (the person who installed and configured this software). If you have questions about how your data is used on this specific site, contact the site owner directly.</p>
</div>

<div class="privacy-section">
    <h2>3. What data is collected</h2>
    <ul>
        <li><strong>Account credentials</strong> — a username and hashed password used to log in.</li>
        <li><strong>Farm data</strong> — items, harvests, reminders, finance records, and map boundaries you enter.</li>
        <li><strong>GPS coordinates</strong> — if you use the "Detect my GPS" feature, your browser shares your location only for placing items or boundary points on the map. Coordinates are stored as item metadata in the local database.</li>
        <li><strong>Google Calendar tokens</strong> — if you connect Google Calendar, an OAuth access token and refresh token are stored in the local database. These are used only to create and update calendar events on your behalf.</li>
        <li><strong>Error logs</strong> — PHP error and activity logs are written to local files to help diagnose problems. They are not transmitted anywhere.</li>
    </ul>
</div>

<div class="privacy-section">
    <h2>4. How data is used</h2>
    <ul>
        <li>All data is used solely to operate the features of this application.</li>
        <li>No data is sold, shared with third parties, or sent to any analytics service.</li>
        <li>No advertising is displayed or tracked.</li>
        <li>Map tiles are loaded from OpenStreetMap and Esri (standard tile CDN requests — your IP is visible to those tile servers as with any map application).</li>
    </ul>
</div>

<div class="privacy-section">
    <h2>5. Google Calendar integration</h2>
    <p>When you connect Google Calendar, Rooted uses the Google Calendar API to create and update events for your reminders. Rooted requests only the following OAuth scopes:</p>
    <ul>
        <li><code>https://www.googleapis.com/auth/calendar.events</code> — to create and update calendar events.</li>
        <li><code>https://www.googleapis.com/auth/userinfo.email</code> — to display which Google account is connected.</li>
    </ul>
    <p>Rooted does not read, list, or delete your existing calendar events. OAuth tokens are stored only in your local database and are never transmitted to any third party other than Google's OAuth servers.</p>
    <p>You can disconnect Google Calendar at any time from Settings → Google Calendar, which deletes all stored tokens from the database.</p>
</div>

<div class="privacy-section">
    <h2>6. Cookies &amp; sessions</h2>
    <p>Rooted uses a single session cookie to keep you logged in. No tracking cookies, advertising cookies, or third-party cookies are set. The session data is stored server-side.</p>
</div>

<div class="privacy-section">
    <h2>7. Data retention</h2>
    <p>Data is retained for as long as the application is in use. You can delete any item, reminder, or record from within the application. To remove all data, the site owner can delete the database.</p>
</div>

<div class="privacy-section">
    <h2>8. Security</h2>
    <ul>
        <li>Passwords are hashed using PHP's <code>password_hash()</code> (bcrypt).</li>
        <li>All forms use CSRF tokens.</li>
        <li>The application does not expose raw database errors to end users.</li>
        <li>Security of the server itself is the responsibility of the site owner.</li>
    </ul>
</div>

<div class="privacy-section">
    <h2>9. Changes to this policy</h2>
    <p>The site owner may update this privacy policy at any time. Continued use of the application after changes constitutes acceptance of the updated policy.</p>
</div>

<div class="privacy-contact">
    <strong>Questions?</strong> Contact the operator of this Rooted installation directly. This is a self-hosted application — there is no central company managing your data.
</div>
