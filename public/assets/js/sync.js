/* Rooted — Sync JS */
$(function () {

    // Check sync queue status
    function checkSyncStatus() {
        $.getJSON('/sync/status', function (res) {
            if (res.success && res.data && res.data.pending > 0) {
                $('#syncStatus').text(res.data.pending + ' item(s) pending sync').show();
            } else {
                $('#syncStatus').hide();
            }
        });
    }

    // Check on load if online
    if (navigator.onLine) {
        checkSyncStatus();
    }

    window.addEventListener('online', checkSyncStatus);

});
