/* Rooted — Main application JS */
$(function () {

    // -------------------------------------------------------------------
    // CSRF: inject token header on all AJAX requests
    // -------------------------------------------------------------------
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    if (csrfToken) {
        $.ajaxSetup({ headers: { 'X-CSRF-Token': csrfToken } });
    }

    // -------------------------------------------------------------------
    // Flash message auto-dismiss
    // -------------------------------------------------------------------
    $('.alert').each(function () {
        var $alert = $(this);
        setTimeout(function () { $alert.fadeOut(400); }, 6000);
    });

    $('.alert-close').on('click', function () {
        $(this).closest('.alert').fadeOut(300);
    });

    // -------------------------------------------------------------------
    // Tab switching — handles [data-tab] buttons within .tabs containers
    // -------------------------------------------------------------------
    $(document).on('click', '.tab-btn[data-tab]', function () {
        var tabId   = $(this).data('tab');
        var $tabs   = $(this).closest('.tabs');
        var $wrap   = $tabs.parent();        // tab panels are siblings of .tabs
        $tabs.find('.tab-btn').removeClass('tab-btn--active');
        $(this).addClass('tab-btn--active');
        $wrap.find('.tab-panel').removeClass('tab-panel--active');
        $wrap.find('#tab-' + tabId).addClass('tab-panel--active');
    });

    // -------------------------------------------------------------------
    // Mobile nav toggle
    // -------------------------------------------------------------------
    $('#navToggle').on('click', function () {
        $('#navLinks').toggleClass('open');
    });

    // Close nav when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.nav').length) {
            $('#navLinks').removeClass('open');
        }
    });

    // -------------------------------------------------------------------
    // Confirm destructive actions
    // -------------------------------------------------------------------
    $('form[data-confirm]').on('submit', function (e) {
        if (!window.confirm($(this).data('confirm'))) {
            e.preventDefault();
        }
    });

});
