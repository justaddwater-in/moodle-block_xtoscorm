define([
    'core/ajax',
    'core/notification',
    'core/str',
    'core/templates'
], function(Ajax, Notification, Str, Templates) {

    let strings = {};

    /**
     * Preload language strings
     * @returns {Promise}
     */
    function loadStrings() {
        return Str.get_strings([
            {key: 'limitreached', component: 'block_xtoscorm'},
            {key: 'conversionfailed', component: 'block_xtoscorm'},
            {key: 'convertbtn', component: 'block_xtoscorm'},
            {key: 'processing', component: 'block_xtoscorm'},
            {key: 'filenotselected', component: 'block_xtoscorm'},
            {key: 'nodownloadurl', component: 'block_xtoscorm'},
            {key: 'error', component: 'core'}
        ]).then(function(results) {
            strings.limitreached = results[0];
            strings.conversionfailed = results[1];
            strings.convertbtn = results[2];
            strings.processing = results[3];
            strings.filenotselected = results[4];
            strings.nodownloadurl = results[5];
            strings.error = results[6];

            return null;
        });
    }

    /**
     * Get selected radio value
     * @param {string} name
     * @returns {string}
     */
    function getRadioValue(name) {
        const el = document.querySelector('input[name="' + name + '"]:checked');
        return el ? el.value : '';
    }

    /**
     * Show notification
     * @param {string} type
     * @param {string} message
     * @returns {Promise}
     */
    function showAlert(type, message) {
        const container = document.getElementById('moodleNotification');

        if (!container) {
            return Promise.resolve(null);
        }

        return Templates.render('block_xtoscorm/notification', {
            type: type,
            message: message
        }).then(function(html, js) {
            Templates.replaceNodeContents(container, html, js);

            const alertEl = container.querySelector('.alert');

            if (alertEl) {
                setTimeout(function() {
                    alertEl.remove();
                }, 5000);
            }

            return null;
        }).catch(Notification.exception);
    }

    /**
     * API call
     * @param {Object} args
     * @returns {Promise}
     */
    function callConvertAPI(args) {
        return Ajax.call([{
            methodname: 'block_xtoscorm_convert',
            args: args
        }])[0];
    }

    /**
     * Trigger file download
     * @param {string} url
     */
    function triggerDownload(url) {
        const link = document.createElement('a');
        link.href = url;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    /**
     * Reset form
     * @param {HTMLFormElement} form
     */
    function resetForm(form) {
        if (form) {
            form.reset();
        }
    }

    /**
     * Safe reload
     */
    function safeReload() {
        window.onbeforeunload = null;

        require(['core_form/changechecker'], function(ChangeChecker) {
            ChangeChecker.resetAllDirtyForms();
            window.location.reload();
        });
    }

    /**
     * Handle redirect
     * @param {string} url
     */
    function handleRedirect(url) {
        window.onbeforeunload = null;

        require(['core_form/changechecker'], function(ChangeChecker) {

            if (ChangeChecker.disableAllChecks) {
                ChangeChecker.disableAllChecks();
            }

            if (ChangeChecker.resetAllFormDirtyStates) {
                ChangeChecker.resetAllFormDirtyStates();
            }

            setTimeout(function() {
                window.location.href = url;
            }, 1000);
        });
    }

    /**
     * Handle API response
     * @param {Object} res
     * @param {HTMLFormElement} form
     */
    function handleResponse(res, form) {

        if (res && res.errorcode === 'limitreached') {
            showAlert('warning', strings.limitreached);
            handleRedirect(res.redirect);
            return;
        }

        if (res && res.url) {
            triggerDownload(res.url);
            resetForm(form);

            setTimeout(function() {
                safeReload();
            }, 1000);

        } else {
            Notification.alert(strings.error, strings.nodownloadurl);
        }
    }

    /**
     * Handle API error
     * @param {Object} error
     */
    function handleError(error) {
        const msg = error && error.message ? error.message : strings.conversionfailed;
        showAlert('danger', msg);
    }

    /**
     * Validate file
     * @param {HTMLInputElement} fileInput
     * @returns {boolean}
     */
    function validateFile(fileInput) {
        if (!fileInput || !fileInput.value) {
            Notification.alert(strings.error, strings.filenotselected);
            return false;
        }
        return true;
    }

    /**
     * Build API args
     * @param {Object} config
     * @param {HTMLInputElement} fileInput
     * @returns {Object}
     */
    function buildArgs(config, fileInput) {
        return {
            fileid: fileInput.value,
            type: config.type,
            scormversion: getRadioValue(config.scorm),
            completion: document.getElementById(config.completion) ?
                document.getElementById(config.completion).value : '',
            fitmode: getRadioValue(config.fit),
            viewmode: getRadioValue(config.view),
            hideprogress: getRadioValue(config.progress)
        };
    }

    /**
     * Get file input
     * @param {Object} config
     * @returns {HTMLInputElement|null}
     */
    function getFileInput(config) {
        const map = {
            pdf: 'pdf_file',
            ppt: 'ppt_file',
            video: 'video_file'
        };

        return document.querySelector('[name="' + map[config.type] + '"]');
    }

    /**
     * Init
     * @param {Object|Array} config
     */
    function init(config) {

        if (Array.isArray(config)) {
            config = config[0];
        }

        const btn = document.getElementById(config.button);
        const form = document.getElementById(config.form);

        if (!btn) {
            return;
        }

        loadStrings().then(function() {

            const fileInput = getFileInput(config);

            btn.addEventListener('click', function(e) {
                e.preventDefault();

                if (!validateFile(fileInput)) {
                    return;
                }

                btn.innerText = strings.processing;
                btn.disabled = true;

                const args = buildArgs(config, fileInput);

                callConvertAPI(args)
                    .done(function(res) {
                        handleResponse(res, form);
                    })
                    .fail(function(error) {
                        handleError(error);
                    })
                    .always(function() {
                        btn.innerText = strings.convertbtn;
                        btn.disabled = false;
                    });
            });
        return null;
        }).catch(Notification.exception);

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-close')) {
                const alert = e.target.closest('.alert');
                if (alert) {
                    alert.remove();
                }
            }
        });
    }

    return {
        init: init
    };
});