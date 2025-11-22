jQuery(document).ready(function ($) {
    // Add body class for padding
    if ($('.ltp-sticky-bar').length) {
        $('body').addClass('ltp-bar-active');
    }

    // Handle close button click
    $('.ltp-close-button').on('click', function () {
        var $bar = $(this).closest('.ltp-sticky-bar');

        // Add dismissing class for animation
        $bar.addClass('ltp-dismissing');

        // Remove body padding class
        $('body').removeClass('ltp-bar-active');

        // Set cookie for 15 minutes
        setCookie('ltp_dismissed', '1', 15);

        // Remove bar after animation
        setTimeout(function () {
            $bar.remove();
        }, 300);
    });

    /**
     * Set a cookie
     * 
     * @param {string} name Cookie name
     * @param {string} value Cookie value
     * @param {number} minutes Expiration in minutes
     */
    function setCookie(name, value, minutes) {
        var expires = "";
        if (minutes) {
            var date = new Date();
            date.setTime(date.getTime() + (minutes * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }
});
