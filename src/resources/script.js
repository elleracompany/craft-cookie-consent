window.onload = function () {
    $("#elc-detail-link").click(function (event) {
        event.preventDefault();
        toggleExpand();
    });
    $("#elc-hide-detail-link").click(function (event) {
        event.preventDefault();
        toggleExpand();
    });
    $("#elc-cookie-consent-form").submit( function(event) {

        event.preventDefault();

        let data = $(this).serialize();

        $.ajax({
            method: 'POST',
            url: '/',
            data: data,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            success: function(data) {
                $("#elc-cookie-consent").remove();
                console.log('success', data);
            }
        });
    });

    function toggleExpand()
    {
        $("#elc-cookie-consent-settings").toggleClass("elc-is-display-none");
        $("#elc-accept-link").toggleClass("elc-is-display-none");
        $("#elc-save-link").toggleClass("elc-is-display-none");
        $("#elc-hide-detail-link").toggleClass("elc-is-display-none");
        $("#elc-detail-link").toggleClass("elc-is-display-none");
    }
}