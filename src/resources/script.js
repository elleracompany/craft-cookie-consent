window.onload = function () {
    $("#detail-link").click(function (event) {
        event.preventDefault();
        toggleExpand();
    });
    $("#hide-detail-link").click(function (event) {
        event.preventDefault();
        toggleExpand();
    });
    $("#cookie-consent-form").submit( function(event) {

        event.preventDefault();

        let data = $(this).serialize();

        $.ajax({
            method: 'POST',
            url: '/',
            data: data,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            success: function(data) {
                $("#cookie-consent").remove();
                console.log('success', data);
            }
        });
    });

    function toggleExpand()
    {
        $("#cookie-consent-settings").toggleClass("is-display-none");
        $("#accept-link").toggleClass("is-display-none");
        $("#save-link").toggleClass("is-display-none");
        $("#hide-detail-link").toggleClass("is-display-none");
        $("#detail-link").toggleClass("is-display-none");
    }
}