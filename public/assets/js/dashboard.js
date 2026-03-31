/* Rooted — Dashboard JS */
$(function () {

    // Load summary data asynchronously
    $.getJSON('/api/dashboard/summary', function (res) {
        if (res.success && res.data) {
            // Update any real-time stat widgets that have a data attribute
            $('[data-stat="total_items"]').text(res.data.total_items || 0);
            $('[data-stat="overdue_reminders"]').text(res.data.overdue_reminders || 0);
        }
    });

});
