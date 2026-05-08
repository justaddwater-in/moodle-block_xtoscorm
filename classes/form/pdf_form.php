<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class pdf_form
 *
 * @package    block_xtoscorm
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_xtoscorm\form;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
/**
 * Form for uploading pdf and converting it into SCORM package.
 */
class pdf_form extends \moodleform {
    /**
     * Summary of definition
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('filepicker', 'pdf_file', get_string('uploadpdf', 'block_xtoscorm'), null, [
            'accepted_types' => ['.pdf'], 'maxbytes' => 10485760,
        ]);

        $mform->addRule('pdf_file', null, 'required');

        // Completion Marker.
        $mform->addElement('text', 'completion_marker', get_string('completionmarker', 'block_xtoscorm'));
        $mform->setType('completion_marker', PARAM_TEXT);
        // Add placeholder.
        $mform->setDefault('completion_marker', '');
        $mform->updateElementAttr('completion_marker', [
            'placeholder' => 'Upload PDF to enable',
            'disabled' => 'disabled',
            'type' => 'number',
        ]);

        // Add help icon.
        $mform->addHelpButton('completion_marker', 'completionmarker', 'block_xtoscorm');
        $mform->addElement(
            'static',
            'totalpages',
            '',
            '<small class="text-muted">Total pages: <span id="totalPagesText">0</span></small>'
        );
        // SCORM Version.
        $radioarray = [];
        $radioarray[] = $mform->createElement('radio', 'scorm_version', '', 'SCORM 1.2', '1.2');
        $radioarray[] = $mform->createElement('radio', 'scorm_version', '', 'SCORM 2004', '2004');

        $mform->addGroup($radioarray, 'scorm_group', 'SCORM Version', [' '], false);
        $mform->setDefault('scorm_version', '1.2');

        // Fit PDF To.
        $fitarray = [];
        $fitarray[] = $mform->createElement('radio', 'fit_type', '', 'Width', 'width');
        $fitarray[] = $mform->createElement('radio', 'fit_type', '', 'Height', 'height');

        $mform->addGroup($fitarray, 'fit_group', 'Fit PDF To', [' '], false);
        $mform->setDefault('fit_type', 'width');

        // PDF View Mode.
        $viewarray = [];
        $viewarray[] = $mform->createElement('radio', 'view_mode', '', 'Page by Page', 'page');
        $viewarray[] = $mform->createElement('radio', 'view_mode', '', 'Sequential (Scroll)', 'Sequential');

        $mform->addGroup($viewarray, 'view_group', 'PDF View Mode', [' '], false);
        $mform->setDefault('view_mode', 'page');

        $mform->addElement('button', 'pdfBtn', get_string('convertbtn', 'block_xtoscorm'));
        $mform->setAttributes(['id' => 'pdfForm']);
    }
}
