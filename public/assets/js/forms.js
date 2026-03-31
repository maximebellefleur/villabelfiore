/* Rooted — Form helpers */
$(function () {

    // Mark form as dirty on change
    $('form.form').each(function () {
        var $form = $(this);
        var dirty = false;

        $form.find('input, select, textarea').on('change input', function () {
            dirty = true;
        });

        $form.on('submit', function () {
            dirty = false;
        });

        $(window).on('beforeunload', function () {
            if (dirty) {
                return 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
    });

});
