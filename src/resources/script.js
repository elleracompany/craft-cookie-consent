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

    let tabLink = document.getElementById("elc-tab-link");
    if (tabLink.addEventListener) {
        tabLink.addEventListener("click", toggleTab);
    } else if (tabLink.attachEvent) {
        tabLink.attachEvent("onclick", toggleTab);
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
                document.getElementById("elc-cookie-consent").classList.toggle('elc-hidden');
                document.getElementById("elc-cookie-tab").classList.toggle('elc-hidden');
            } else {
                console.log('Error: ' + xhr.status);
            }
        }
    }
}

function toggleExpand(event)
{
    event.preventDefault();
    document.getElementById("elc-cookie-consent").classList.toggle('elc-fullwidth');
    document.getElementById("elc-cookie-consent").classList.toggle('elc-small');
}

function toggleTab(event)
{
    event.preventDefault();
    document.getElementById("elc-cookie-consent").classList.toggle('elc-hidden');
    document.getElementById("elc-cookie-tab").classList.toggle('elc-hidden');
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