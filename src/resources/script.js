document.addEventListener('DOMContentLoaded', function () {
    bind('elc-detail-link', toggleExpand);
    bind('elc-hide-detail-link', toggleExpand);
    bind('elc-save-link', submitConsent);
    bind('elc-accept-link', submitConsent);
    bind('elc-accept-all-link', submitAllConsent);
    bind('elc-tab-link', toggleTab);

    addWindowObject();
});

function bind(id, handler) {
    const element = document.getElementById(id);
    if (element !== null) {
        element.addEventListener('click', handler);
    }
}

function submitConsent(event) {
    event.preventDefault();
    submit(false);
}

function submitAllConsent(event) {
    event.preventDefault();
    submit(true);
}

function submit(acceptAll) {
    const form = document.querySelector('#elc-cookie-consent-form');

    if (acceptAll) {
        for (const field of form.elements) {
            if (field.type === 'checkbox') {
                field.checked = true;
            }
        }
    }

    let data = new URLSearchParams(new FormData(form)).toString();
    if (acceptAll) {
        data += '&acceptAll=true';
    }

    document.getElementById('elc-cookie-consent').classList.toggle('elc-hidden');
    const cookieTab = document.getElementById('elc-cookie-tab');
    if (cookieTab !== null) {
        cookieTab.classList.toggle('elc-hidden');
    }

    withCsrf(form, data, function (payload) {
        fetch(form.dataset.url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: payload
        }).then(function (response) {
            addWindowObject();
            if (!response.ok) {
                console.error('Cookie consent: submit failed with status ' + response.status);
            } else if (form.dataset.refresh === 'true' && form.dataset.refreshtime) {
                refresh(form.dataset.refreshtime);
            }
        }).catch(function (error) {
            console.error('Cookie consent: submit failed', error);
        });
    });
}

/*
 * Fetch a fresh CSRF token and append it to the serialized form data.
 * The token is not part of the rendered banner, so cached pages stay
 * valid for every visitor; it is only requested when consent is submitted.
 */
function withCsrf(form, data, callback) {
    if (typeof form.dataset.csrf === 'undefined') {
        callback(data);
        return;
    }

    fetch(form.dataset.csrf, {headers: {'Accept': 'application/json'}})
        .then(function (response) {
            if (!response.ok) {
                throw new Error('status ' + response.status);
            }
            return response.json();
        })
        .then(function (token) {
            callback(data + '&' + encodeURIComponent(token.name) + '=' + encodeURIComponent(token.value));
        })
        .catch(function (error) {
            console.error('Cookie consent: could not fetch CSRF token', error);
            callback(data);
        });
}

function refresh(ms) {
    setTimeout(function () {
        location.reload();
    }, ms);
}

function toggleExpand(event) {
    event.preventDefault();
    document.getElementById('elc-cookie-consent').classList.toggle('elc-fullwidth');
    document.getElementById('elc-cookie-consent').classList.toggle('elc-small');
}

function toggleTab(event) {
    event.preventDefault();
    document.getElementById('elc-cookie-consent').classList.toggle('elc-hidden');
    document.getElementById('elc-cookie-tab').classList.toggle('elc-hidden');

    document.getElementById('elc-cookie-consent').classList.add('elc-fullwidth');
    document.getElementById('elc-cookie-consent').classList.remove('elc-small');
}

/*
 * Expose the current consent state as window.ccc so site scripts can
 * check what the visitor has consented to.
 */
function addWindowObject() {
    const form = document.querySelector('#elc-cookie-consent-form');
    if (form === null || typeof form.dataset.show === 'undefined') {
        return;
    }

    fetch(form.dataset.show, {headers: {'Accept': 'application/json'}})
        .then(function (response) {
            if (!response.ok) {
                throw new Error('status ' + response.status);
            }
            return response.json();
        })
        .then(function (consents) {
            window.ccc = consents === null ? {} : consents;
        })
        .catch(function (error) {
            console.error('Cookie consent: could not fetch consent state', error);
            window.ccc = {};
        });
}
