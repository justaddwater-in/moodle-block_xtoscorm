// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * XtoSCORM conversion handler.
 *
 * @module     block_xtoscorm/convert
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
            {key: 'conversionfailed', component: 'block_xtoscorm'},
            {key: 'convertbtn', component: 'block_xtoscorm'},
            {key: 'processing', component: 'block_xtoscorm'},
            {key: 'filenotselected', component: 'block_xtoscorm'},
            {key: 'nodownloadurl', component: 'block_xtoscorm'},
            {key: 'error', component: 'core'}
        ]).then(function(results) {
            strings.conversionfailed = results[0];
            strings.convertbtn = results[1];
            strings.processing = results[2];
            strings.filenotselected = results[3];
            strings.nodownloadurl = results[4];
            strings.error = results[5];

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
     * @param {string} allowHtml
     * @returns {Promise}
     */
    function showAlert(type, message, allowHtml = false) {

        const container = document.getElementById('moodleNotification');

        if (!container) {
            return Promise.resolve(null);
        }

        return Templates.render('block_xtoscorm/notification', {
            type,
            message,
            allowHtml
        }).then(function(html, js) {

            Templates.replaceNodeContents(container, html, js);

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
     * Handle API response
     * @param {Object} res
     * @param {HTMLFormElement} form
     */
    function handleResponse(res, form) {

        // API custom error.
        if (res && res.success === false) {

            let msg = `
                <div class="xtoscorm-upgrade-box">
                    <div class="xtoscorm-upgrade-message">
                        ${res.message || strings.conversionfailed}
                    </div>
            `;

            if (res.upgrade_url) {

                msg += `
                    <div class="mt-3">
                        <a href="${res.upgrade_url}"
                        target="_blank"
                        class="btn btn-warning btn-lg fw-bold px-4 py-2 rounded-pill shadow-sm">
                            🚀 Upgrade Plan
                        </a>
                    </div>
                `;
            }

            msg += `</div>`;

            showAlert('warning', msg, true);
            return;
        }

        // Success.
        if (res && res.url) {

            triggerDownload(res.url);

            resetForm(form);

            setTimeout(function() {
                safeReload();
            }, 1000);

            return;
        }

        Notification.alert(
            strings.error,
            strings.nodownloadurl
        );
    }

    /**
     * Handle API error
     * @param {Object} error
     */
    function handleError(error) {

        const msg = (
            error && error.message
        ) ? error.message : strings.conversionfailed;

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