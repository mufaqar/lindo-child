jQuery(document).ready(function ($) {
    $('#serial-verification-form').on('submit', function (e) {
        e.preventDefault();

        var serialNumber = $('#serial_number').val().trim();
        var resultBox = $('#serial-verification-result');

        resultBox.html('<p>Searching...</p>');

        $.ajax({
            url: verification_ajax_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'serial_verification_search',
                nonce: verification_ajax_obj.nonce,
                serial_number: serialNumber
            },
            success: function (response) {
                if (response.success) {
                    resultBox.html(
                        '<p class="success">' + response.data.message + '</p>' +
                        '<a class="certificate-link" href="' + response.data.url + '" target="_blank">Open Certificate</a>'
                    );
                } else {
                    resultBox.html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function () {
                resultBox.html('<p class="error">Something went wrong. Please try again.</p>');
            }
        });
    });
});