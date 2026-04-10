<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline — Rooted</title>
    <meta name="theme-color" content="#29402B">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #F5F0EA;
            color: #1E2E20;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
        }
        .badge {
            width: 96px;
            height: 96px;
            background: #29402B;
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 4px 20px rgba(41,64,43,0.3);
        }
        /* Simple SVG tree silhouette */
        .badge svg { width: 56px; height: 56px; }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #29402B;
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }
        p {
            font-size: 0.95rem;
            color: #637380;
            line-height: 1.55;
            max-width: 320px;
            margin: 0 auto 28px;
        }
        .btn {
            display: inline-block;
            padding: 13px 28px;
            background: #29402B;
            color: #fff;
            border: none;
            border-radius: 999px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }
        .btn:hover { background: #3D6642; }
        .hint {
            margin-top: 18px;
            font-size: 0.78rem;
            color: #96A3AB;
        }
    </style>
</head>
<body>
    <div class="badge">
        <svg viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Tree: trunk -->
            <rect x="25" y="32" width="6" height="14" rx="1" fill="white"/>
            <!-- Roots -->
            <ellipse cx="19" cy="46" rx="7" ry="3.5" fill="white"/>
            <ellipse cx="37" cy="46" rx="7" ry="3.5" fill="white"/>
            <!-- Mask root tops -->
            <rect x="12" y="38" width="32" height="8" fill="#29402B"/>
            <!-- Redraw trunk over mask -->
            <rect x="25" y="32" width="6" height="14" rx="1" fill="white"/>
            <!-- Canopy circles -->
            <circle cx="21" cy="24" r="8" fill="white"/>
            <circle cx="35" cy="24" r="8" fill="white"/>
            <circle cx="28" cy="20" r="10" fill="white"/>
            <!-- Leaf details -->
            <circle cx="28" cy="11" r="3.5" fill="white"/>
            <circle cx="22" cy="13" r="3.5" fill="white"/>
            <circle cx="34" cy="13" r="3.5" fill="white"/>
        </svg>
    </div>

    <h1>You're Offline</h1>
    <p>Rooted can't reach the server right now. Check your connection and try again.</p>

    <button class="btn" onclick="window.location.reload()">Try Again</button>
    <p class="hint">Previously viewed pages may still be available in the navigation.</p>
</body>
</html>
