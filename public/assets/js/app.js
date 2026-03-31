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
