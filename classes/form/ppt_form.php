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
 * Class ppt_form
 *
 * @package    block_xtoscorm
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_xtoscorm\form;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
/**
 * Init of ppt_form
 */
class ppt_form extends \moodleform {
    /**
     * Summary of definition
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        // Upload PPT.
        $mform->addElement(
            'filepicker',
            'ppt_file',
            get_string('uploadppt', 'block_xtoscorm'),
            null,
            [
                'accepted_types' => ['.ppt', '.pptx'],
                'maxbytes' => 10485760, // 10MB
            ]
        );
        $mform->addRule('ppt_file', null, 'required');

        // Completion Marker.
        $mform->addElement(
            'text',
            'completion_marker',
            get_string('completionmarkerppt', 'block_xtoscorm')
        );
        $mform->setType('completion_marker', PARAM_INT);
        $mform->setDefault('completion_marker', 1);

        // Help icon.
        $mform->addHelpButton('completion_marker', 'completionmarkerppt', 'block_xtoscorm');

        // SCORM Version.
        $radioarray = [];
        $radioarray[] = $mform->createElement(
            'radio',
            'scorm_version',
            '',
            get_string('scorm12', 'block_xtoscorm'),
            '1.2'
        );

        $radioarray[] = $mform->createElement(
            'radio',
            'scorm_version',
            '',
            get_string('scorm2004', 'block_xtoscorm'),
            '2004'
        );

        $mform->addGroup($radioarray, 'scorm_group', get_string('scormversion', 'block_xtoscorm'), [' '], false);
        $mform->setDefault('scorm_version', '1.2');

        // Submit button (custom label).
        $mform->addElement('button', 'pptBtn', get_string('convertbtn', 'block_xtoscorm'));
        $mform->setAttributes(['id' => 'pptForm']);
    }
}
