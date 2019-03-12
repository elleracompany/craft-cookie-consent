window.onload = function () {
    $("#detail-link").click(function (event) {
        event.preventDefault();
        toggleExpand();
    });
    $("#hide-detail-link").click(function (event) {
        event.preventDefault();
        toggleExpand();
    });
    $("#accept-link").click(function (event) {
        event.preventDefault();
        saveSettings();
    });
    $("#save-link").click(function (event) {
        event.preventDefault();
        saveSettings();
    });
    function toggleExpand()
    {
        $("#cookie-consent-settings").toggleClass("is-display-none");
        $("#accept-link").toggleClass("is-display-none");
        $("#save-link").toggleClass("is-display-none");
        $("#hide-detail-link").toggleClass("is-display-none");
        $("#detail-link").toggleClass("is-display-none");
    }

    function saveSettings()
    {
        document.cookie = "cookieConsent=" + JSON.stringify({
            "functional": $('#checkbox-functional').is(":checked") ? 'on' : 'off',
            "statistical": $('#checkbox-statistical').is(":checked") ? 'on' : 'off',
            "marketing": $('#checkbox-marketing').is(":checked") ? 'on' : 'off',
        }) + ";path=/";
        $("#cookie-consent").remove();
    }
}