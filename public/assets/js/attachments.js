/* Rooted — Attachments JS */
$(function () {
    // Basic file size validation before upload
    $('input[type="file"]').on('change', function () {
        var maxBytes = 10 * 1024 * 1024; // 10 MB
        var file = this.files[0];
        if (file && file.size > maxBytes) {
            alert('File is too large. Maximum allowed size is 10 MB.');
            this.value = '';
        }
    });
});
