/* Rooted — Unified GPS Service
 * Single watchPosition runs on every page. All features share the same
 * continuously-improving cached fix. No more waiting 10s for each feature.
 * Usage:  RootedGPS.get(function(pos) { pos.lat, pos.lng, pos.accuracy });
 *         RootedGPS.last()  → last known pos or null
 */
window.RootedGPS = (function () {
    'use strict';

    var _pos     = null;   // { lat, lng, accuracy, timestamp }
    var _watchId = null;
    var _pending = [];     // waiting callbacks

    function _store(pos) {
        _pos = {
            lat:       pos.coords.latitude,
            lng:       pos.coords.longitude,
            accuracy:  pos.coords.accuracy,
            timestamp: Date.now(),
        };
        // Flush first-fix waiters
        if (_pending.length) {
            var cbs = _pending.slice(); _pending = [];
            cbs.forEach(function (fn) { fn(_pos); });
        }
    }

    function _watchError(err) {
        if (err.code === 1 /* PERMISSION_DENIED */) {
            // Tell waiters there's nothing coming
            var cbs = _pending.slice(); _pending = [];
            cbs.forEach(function (fn) { fn(null); });
        }
        // For timeout/unavailable, keep watching — might recover
    }

    function start() {
        if (_watchId !== null || !navigator.geolocation) return;
        _watchId = navigator.geolocation.watchPosition(_store, _watchError, {
            enableHighAccuracy: true,
            maximumAge:         5000,
            timeout:            20000,
        });
    }

    /**
     * Request the current position.
     *  - Returns immediately if cache is fresh (< maxAgeMs, default 30s).
     *  - Otherwise waits for the next watchPosition update (up to 10s).
     *  - Falls back to a single getCurrentPosition with lower accuracy.
     *  - Calls cb(null) if GPS is unavailable or permission denied.
     */
    function get(cb, maxAgeMs) {
        maxAgeMs = (typeof maxAgeMs === 'number') ? maxAgeMs : 30000;

        // Fresh cache → immediate
        if (_pos && (Date.now() - _pos.timestamp) < maxAgeMs) {
            cb(_pos);
            return;
        }

        if (!navigator.geolocation) { cb(null); return; }

        var done  = false;
        var timer = null;

        function resolve(p) {
            if (done) return;
            done = true;
            clearTimeout(timer);
            var idx = _pending.indexOf(resolve);
            if (idx > -1) _pending.splice(idx, 1);
            cb(p);
        }

        _pending.push(resolve);
        start(); // ensure watch is running

        // 15-second patience window for watchPosition, then force a direct shot
        timer = setTimeout(function () {
            navigator.geolocation.getCurrentPosition(
                function (pos) { _store(pos); resolve(_pos); },
                function (err) {
                    if (err.code === 1) { resolve(null); return; } // denied
                    // timeout/unavailable — return whatever we have
                    resolve(_pos || null);
                },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
            );
        }, 15000);
    }

    /** Last known position, may be null or stale */
    function last() { return _pos; }

    // Boot immediately — gives the browser time to warm up the GPS chip
    // before the user taps any button
    if (navigator.geolocation) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            setTimeout(start, 0);
        }
    }

    return { start: start, get: get, last: last };
}());
