/* global pdfjsLib,*/

define(['core/str',], function(Str) {

    let pdfDoc = null;

    return {
        init: function() {
            let strings = {};

            /**
             * Preload language strings
             * @returns {Promise}
             */
            function loadStrings() {
                return Str.get_strings([
                    {key: 'totalpages', component: 'block_xtoscorm'},
                    {key: 'loading', component: 'block_xtoscorm'},
                    {key: 'errorreadingpdf', component: 'block_xtoscorm'},
                    {key: 'filereaderror', component: 'block_xtoscorm'},
                ]).then(function(results) {
                    strings.totalpages = results[0];


                    return null;
                });
            }
            loadStrings();

            const checkReady = setInterval(() => {

                const completionField = document.querySelector('[name="completion_marker"]');
                const totalPagesText = document.getElementById('totalPagesText');

                if (!completionField || typeof pdfjsLib === 'undefined') {
                    return;
                }

                clearInterval(checkReady);

                // ✅ Set PDF.js worker
                pdfjsLib.GlobalWorkerOptions.workerSrc =
                    M.cfg.wwwroot + '/blocks/xtoscorm/lib/pdfjs/pdf.worker.min.js';

                // ✅ EVENT DELEGATION (IMPORTANT FIX)
                document.addEventListener('change', function(e) {

                    if (!e.target || e.target.type !== 'file') {
                        return;
                    }

                    const fileInput = e.target;

                    if (!fileInput.files || fileInput.files.length === 0) {
                        return;
                    }

                    const file = fileInput.files[0];

                    if (file.type !== 'application/pdf') {
                        return;
                    }

                    // 🔥 RESET UI FIRST
                    if (totalPagesText) {
                        totalPagesText.innerText = 'Loading...';
                    }

                    completionField.value = '';
                    completionField.setAttribute('disabled', true);

                    const reader = new FileReader();

                    reader.onload = function() {

                        try {

                            const typedarray = new Uint8Array(this.result);

                            // 🔥 Destroy previous PDF instance
                            if (pdfDoc) {
                                try {
                                    pdfDoc.destroy();
                                } catch (err) {
                                    // ...ignore destroy errors
                                }
                                pdfDoc = null;
                            }

                            // 🔥 Load new PDF
                            pdfjsLib.getDocument(typedarray).promise
                                .then(function(pdf) {

                                    pdfDoc = pdf;

                                    const totalPages = pdf.numPages;

                                    // ✅ Update UI
                                    if (totalPagesText) {
                                        totalPagesText.innerText = totalPages;
                                    }

                                    completionField.removeAttribute('disabled');
                                    completionField.removeAttribute('readonly');
                                    completionField.value = totalPages;

                                    return null;

                                })
                                .catch(function() {

                                    if (totalPagesText) {
                                        totalPagesText.innerText = strings.errorreadingpdf;
                                    }

                                    return null;
                                });

                        } catch (err) {
                            return null;
                        }
                        return null;
                    };

                    reader.onerror = function() {

                        if (totalPagesText) {
                            totalPagesText.innerText = strings.filereaderror;
                        }

                        return null;
                    };

                    reader.readAsArrayBuffer(file);

                });

            }, 300);
        }
    };
});