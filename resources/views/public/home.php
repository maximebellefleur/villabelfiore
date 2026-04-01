<?php $layout = 'public'; ?>

<style>
/* ---- Hero ---- */
.home-hero {
    background: linear-gradient(135deg, var(--color-primary-dark,#1e3d1b) 0%, var(--color-primary,#2d5a27) 60%, #3d7a35 100%);
    color: #fff;
    border-radius: var(--radius-lg);
    padding: 64px var(--spacing-6) 56px;
    text-align: center;
    margin-bottom: var(--spacing-6);
    position: relative;
    overflow: hidden;
}
.home-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
.home-hero-icon { font-size: 4rem; line-height: 1; margin-bottom: var(--spacing-4); }
.home-hero h1  { font-size: clamp(2rem, 5vw, 3rem); font-weight: 800; margin-bottom: var(--spacing-3); }
.home-hero p   { font-size: 1.15rem; opacity: .88; max-width: 540px; margin: 0 auto var(--spacing-6); line-height: 1.6; }
.home-hero-actions { display: flex; gap: var(--spacing-3); justify-content: center; flex-wrap: wrap; }
.btn-hero-primary {
    background: #fff;
    color: var(--color-primary-dark, #1e3d1b);
    font-weight: 700;
    padding: 14px 32px;
    border-radius: var(--radius);
    font-size: 1rem;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}
.btn-hero-primary:hover { background: #f0faf0; text-decoration: none; }
.btn-hero-secondary {
    background: transparent;
    color: rgba(255,255,255,.9);
    font-weight: 600;
    padding: 14px 28px;
    border-radius: var(--radius);
    font-size: 1rem;
    border: 2px solid rgba(255,255,255,.4);
    text-decoration: none;
    display: inline-block;
}
.btn-hero-secondary:hover { background: rgba(255,255,255,.1); text-decoration: none; color: #fff; }

/* ---- Features grid ---- */
.home-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-6);
}
.home-feature {
    background: #fff;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--spacing-5);
    transition: box-shadow .2s;
}
.home-feature:hover { box-shadow: var(--shadow); }
.home-feature-icon { font-size: 2rem; margin-bottom: var(--spacing-3); }
.home-feature h3   { font-size: 1rem; font-weight: 700; margin-bottom: 6px; }
.home-feature p    { font-size: .85rem; color: var(--color-text-muted); margin: 0; line-height: 1.65; }

/* ---- Self-hosted callout ---- */
.home-callout {
    background: #f0f7f0;
    border: 1px solid #b6d9b6;
    border-radius: var(--radius-lg);
    padding: var(--spacing-6);
    display: flex;
    gap: var(--spacing-5);
    align-items: flex-start;
    margin-bottom: var(--spacing-6);
}
.home-callout-icon { font-size: 2.5rem; flex-shrink: 0; }
.home-callout h2   { font-size: 1.15rem; font-weight: 700; margin-bottom: var(--spacing-2); color: var(--color-primary); }
.home-callout p    { font-size: .9rem; color: #444; margin: 0; line-height: 1.7; }

/* ---- Screenshots / how it looks ---- */
.home-steps {
    margin-bottom: var(--spacing-6);
}
.home-steps h2 {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: var(--spacing-4);
    color: var(--color-primary);
    text-align: center;
}
.home-steps-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-4);
    list-style: none;
    padding: 0;
    margin: 0;
}
.home-step {
    background: #fff;
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: var(--spacing-4);
    display: flex;
    gap: var(--spacing-3);
    align-items: flex-start;
}
.home-step-num {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--color-primary);
    color: #fff;
    font-weight: 800;
    font-size: .85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.home-step h3 { font-size: .9rem; font-weight: 700; margin-bottom: 3px; }
.home-step p  { font-size: .8rem; color: var(--color-text-muted); margin: 0; }

/* ---- CTA bottom ---- */
.home-cta {
    text-align: center;
    padding: var(--spacing-6) 0 var(--spacing-4);
}
.home-cta h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: var(--spacing-3); }
.home-cta p  { color: var(--color-text-muted); margin-bottom: var(--spacing-4); }

@media (max-width: 600px) {
    .home-hero { padding: 48px var(--spacing-4) 40px; }
    .home-callout { flex-direction: column; gap: var(--spacing-3); }
}
</style>

<!-- Hero -->
<div class="home-hero">
    <div class="home-hero-icon">🌿</div>
    <h1>Rooted</h1>
    <p>The private farm &amp; land management app. Track your trees, harvests, tasks, and finances — from your phone, on your own land.</p>
    <div class="home-hero-actions">
        <a href="<?= url('/login') ?>" class="btn-hero-primary">Sign In →</a>
        <a href="<?= url('/privacy') ?>" class="btn-hero-secondary">Privacy &amp; About</a>
    </div>
</div>

<!-- Features -->
<div class="home-features">
    <div class="home-feature">
        <div class="home-feature-icon">🗺️</div>
        <h3>Interactive Land Map</h3>
        <p>Satellite view of your property. Place trees, plants, structures, and animals with a tap. Draw land and zone boundaries.</p>
    </div>
    <div class="home-feature">
        <div class="home-feature-icon">🌱</div>
        <h3>Item Tracking</h3>
        <p>Log every tree, crop row, beehive, or fence post. Add notes, photos, GPS coordinates, and a complete action history.</p>
    </div>
    <div class="home-feature">
        <div class="home-feature-icon">🌾</div>
        <h3>Harvest Records</h3>
        <p>Record harvest dates, weights, and yields. See totals per season and track which trees produce the most.</p>
    </div>
    <div class="home-feature">
        <div class="home-feature-icon">🔔</div>
        <h3>Reminders &amp; Tasks</h3>
        <p>Schedule pruning, fertilising, or any recurring task. Sync to Google Calendar for notifications on your phone.</p>
    </div>
    <div class="home-feature">
        <div class="home-feature-icon">💰</div>
        <h3>Finance Log</h3>
        <p>Track income and expenses per item or across the whole farm. Know your cost per tree and return per harvest.</p>
    </div>
    <div class="home-feature">
        <div class="home-feature-icon">📱</div>
        <h3>Works Offline</h3>
        <p>Install it as a PWA on your phone. Use it in the field without internet — data syncs when you're back online.</p>
    </div>
</div>

<!-- Self-hosted callout -->
<div class="home-callout">
    <div class="home-callout-icon">🔒</div>
    <div>
        <h2>Your data stays on your server</h2>
        <p>Rooted is self-hosted — it runs on your own cPanel or web hosting. No cloud subscription, no monthly fee, no third-party storing your farm data. Install it once and it's yours. Updates are applied in one click from the settings panel.</p>
    </div>
</div>

<!-- How it works -->
<div class="home-steps">
    <h2>How it works</h2>
    <ul class="home-steps-list">
        <li class="home-step">
            <div class="home-step-num">1</div>
            <div>
                <h3>Open the map</h3>
                <p>See a satellite view of your land. Draw your boundaries and zones.</p>
            </div>
        </li>
        <li class="home-step">
            <div class="home-step-num">2</div>
            <div>
                <h3>Add items</h3>
                <p>Tap anywhere on the map to place an item. Use GPS to pin your exact location.</p>
            </div>
        </li>
        <li class="home-step">
            <div class="home-step-num">3</div>
            <div>
                <h3>Log actions</h3>
                <p>Record pruning, spraying, harvesting, or any activity against each item.</p>
            </div>
        </li>
        <li class="home-step">
            <div class="home-step-num">4</div>
            <div>
                <h3>Review &amp; plan</h3>
                <p>Check your harvest history, upcoming reminders, and finance reports at a glance.</p>
            </div>
        </li>
    </ul>
</div>

<!-- Bottom CTA -->
<div class="home-cta">
    <h2>Ready to get started?</h2>
    <p>Sign in to your Rooted installation below.</p>
    <a href="<?= url('/login') ?>" class="btn-hero-primary" style="background:var(--color-primary);color:#fff">Sign In to Rooted →</a>
</div>
