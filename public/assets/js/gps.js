/* Rooted — Unified GPS Service
 * Single watchPosition runs on every page. All features share the same
 * continuously-improving cached fix. No more waiting 10s for each feature.
 * Usage:  RootedGPS.get(function(pos) { pos.lat, pos.lng, pos.accuracy });
 *         RootedGPS.last()  → last known pos or null
 *         var unsub = RootedGPS.subscribe(fn)  → fn({lat,lng,accuracy,timestamp}) on every update; call unsub() to stop
 */
window.RootedGPS = (function () {
    'use strict';

    var _pos              = null;      // { lat, lng, accuracy, timestamp }
    var _watchId          = null;
    var _pending          = [];        // waiting callbacks (first-fix)
    var _accuracyWatchers = [];        // callbacks that fire when accuracy improves
    var _subscribers      = [];        // continuous subscribers — fire on every update
    var _bestAccuracy     = Infinity;  // best accuracy seen so far (m)

    function _store(pos) {
        var newAcc = pos.coords.accuracy;
        _pos = {
            lat:       pos.coords.latitude,
            lng:       pos.coords.longitude,
            accuracy:  newAcc,
            timestamp: Date.now(),
        };
        // Flush first-fix waiters
        if (_pending.length) {
            var cbs = _pending.slice(); _pending = [];
            cbs.forEach(function (fn) { fn(_pos); });
        }
        // Fire accuracy-improve watchers when signal gets meaningfully better
        // (first fix counts as infinite improvement; subsequent: ≥30% gain)
        if (newAcc < _bestAccuracy * 0.7) {
            _bestAccuracy = newAcc;
            _accuracyWatchers.forEach(function (fn) { fn(_pos); });
        }
        // Fire continuous subscribers on every update
        if (_subscribers.length) {
            var subs = _subscribers.slice();
            subs.forEach(function (fn) { fn(_pos); });
        }
    }

    function _watchError(err) {
        if (err.code === 1 /* PERMISSION_DENIED */) {
            // Tell one-shot waiters there's nothing coming
            var cbs = _pending.slice(); _pending = [];
            cbs.forEach(function (fn) { fn(null); });
            // Tell continuous subscribers too — so walk mode can show an error
            // instead of waiting forever
            if (_subscribers.length) {
                var subs = _subscribers.slice();
                subs.forEach(function (fn) { try { fn(null); } catch (e) {} });
            }
        }
        // For timeout/unavailable, keep watching — might recover
    }

    function start() {
        if (_watchId !== null || !navigator.geolocation) return;
        _watchId = navigator.geolocation.watchPosition(_store, _watchError, {
            enableHighAccuracy: true,
            maximumAge:         0,       // always fresh — important for walk mode
            timeout:            20000,
        });
    }

    /**
     * Subscribe to every GPS update (continuous stream).
     * Returns an unsubscribe function — call it to stop receiving updates.
     * cb({ lat, lng, accuracy, timestamp })
     */
    function subscribe(cb) {
        _subscribers.push(cb);
        start(); // ensure watch is running
        // If we already have a recent fix, fire immediately
        if (_pos && (Date.now() - _pos.timestamp) < 5000) {
            try { cb(_pos); } catch (e) {}
        }
        return function unsubscribe() {
            var idx = _subscribers.indexOf(cb);
            if (idx > -1) _subscribers.splice(idx, 1);
        };
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

    /**
     * Register a callback that fires whenever GPS accuracy meaningfully improves
     * (≥30% gain over the best seen so far, including the very first fix).
     * cb(pos) receives { lat, lng, accuracy, timestamp }.
     */
    function onAccuracyImprove(cb) {
        _accuracyWatchers.push(cb);
    }

    // Boot immediately — gives the browser time to warm up the GPS chip
    // before the user taps any button
    if (navigator.geolocation) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            setTimeout(start, 0);
        }
    }

    return { start: start, get: get, last: last, onAccuracyImprove: onAccuracyImprove, subscribe: subscribe };
}());
