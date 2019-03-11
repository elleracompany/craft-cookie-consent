window.onload = function () {
    $("#detail-link").click(function (event) {
        event.preventDefault();
        let settingsLinkText = $('#detail-link').text();
        $("#cookie-consent-settings").toggleClass("is-display-none");
        $('#detail-link').text(
            settingsLinkText == "Detaljer" ? "Skjul detaljer" : "Detaljer"
        );
        $('#accept-link').text(
            settingsLinkText == "Detaljer" ? "Lagre" : "OK"
        );
    });
    $("#accept-link").click(function (event) {
        event.preventDefault();
        document.cookie = "cookieConsent=" + JSON.stringify({
            "functional": $('#checkbox-functional').is(":checked") ? 'on' : 'off',
            "statistical": $('#checkbox-statistical').is(":checked") ? 'on' : 'off',
            "marketing": $('#checkbox-marketing').is(":checked") ? 'on' : 'off',
        }) + ";path=/";
        $("#cookie-consent").remove();
    });
}