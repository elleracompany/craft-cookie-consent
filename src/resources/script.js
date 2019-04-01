window.onload = function () {
    /*
     * addEventListner: For all major browsers, except IE 8 and earlier
     * attachEvent:     For IE 8 and earlier versions
     */
    let detailLink = document.getElementById("elc-detail-link");
    if (detailLink.addEventListener) {
        detailLink.addEventListener("click", toggleExpand);
    } else if (detailLink.attachEvent) {
        detailLink.attachEvent("onclick", toggleExpand);
    }

    let hideDetailLink = document.getElementById("elc-hide-detail-link");
    if (hideDetailLink.addEventListener) {
        hideDetailLink.addEventListener("click", toggleExpand);
    } else if (hideDetailLink.attachEvent) {
        hideDetailLink.attachEvent("onclick", toggleExpand);
    }

    let form = document.getElementById("elc-cookie-consent-form");
    if (form.addEventListener) {
        form.addEventListener("submit", submitConsent);
    } else if (form.attachEvent) {
        form.attachEvent("onsubmit", submitConsent);
    }
};

function submitConsent(event) {

    event.preventDefault();

    let form = document.querySelector('#elc-cookie-consent-form');
    let data = serialize(form);

    let xhr = new XMLHttpRequest();
    xhr.open('POST', '/');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send(data);

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                document.querySelector("#elc-cookie-consent").remove();
            } else {
                console.log('Error: ' + xhr.status);
            }
        }
    }
}

function toggleExpand(event)
{
    event.preventDefault();
    document.getElementById("elc-cookie-consent-settings").classList.toggle('elc-is-display-none');
    document.getElementById("elc-accept-link").classList.toggle('elc-is-display-none');
    document.getElementById("elc-save-link").classList.toggle('elc-is-display-none');
    document.getElementById("elc-hide-detail-link").classList.toggle('elc-is-display-none');
    document.getElementById("elc-detail-link").classList.toggle('elc-is-display-none');
}

/*!
 * Serialize all form data into a query string
 * (c) 2018 Chris Ferdinandi, MIT License, https://gomakethings.com
 * @param  {Node}   form The form to serialize
 * @return {String}      The serialized form data
 */
var serialize = function (form) {

    // Setup our serialized data
    var serialized = [];

    // Loop through each field in the form
    for (var i = 0; i < form.elements.length; i++) {

        var field = form.elements[i];

        // Don't serialize fields without a name, submits, buttons, file and reset inputs, and disabled fields
        if (!field.name || field.disabled || field.type === 'file' || field.type === 'reset' || field.type === 'submit' || field.type === 'button') continue;

        // If a multi-select, get all selections
        if (field.type === 'select-multiple') {
            for (var n = 0; n < field.options.length; n++) {
                if (!field.options[n].selected) continue;
                serialized.push(encodeURIComponent(field.name) + "=" + encodeURIComponent(field.options[n].value));
            }
        }

        // Convert field data to a query string
        else if ((field.type !== 'checkbox' && field.type !== 'radio') || field.checked) {
            serialized.push(encodeURIComponent(field.name) + "=" + encodeURIComponent(field.value));
        }
    }

    return serialized.join('&');

};