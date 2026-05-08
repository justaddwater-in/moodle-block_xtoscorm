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
 * Class video_form
 *
 * @package    block_xtoscorm
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_xtoscorm\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
/**
 * Form for uploading video and converting it into SCORM package.
 */
class video_form extends \moodleform {
    /**
     * Summary of definition
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        // Upload Video.
        $mform->addElement(
            'filepicker',
            'video_file',
            get_string('uploadvideo', 'block_xtoscorm'),
            null,
            [
                'accepted_types' => ['.mp4'],
                'maxbytes' => 52428800, // 50MB
            ]
        );
        $mform->addRule('video_file', null, 'required');

        // Completion Marker (slider).
        $mform->addElement(
            'text',
            'completion_marker',
            get_string('completionmarker_video', 'block_xtoscorm')
        );
        $mform->setType('completion_marker', PARAM_INT);
        $mform->setDefault('completion_marker', 80);

        // Help text.
        $mform->addHelpButton('completion_marker', 'completionmarker_video', 'block_xtoscorm');

        // SCORM Version.
        $scorm = [];
        $scorm[] = $mform->createElement('radio', 'scorm_version', '', 'SCORM 1.2', '1.2');
        $scorm[] = $mform->createElement('radio', 'scorm_version', '', 'SCORM 2004', '2004');

        $mform->addGroup($scorm, 'scorm_group', get_string('scormversion', 'block_xtoscorm'), [' '], false);
        $mform->setDefault('scorm_version', '1.2');

        // Video Seek Bar.
        $seek = [];
        $seek[] = $mform->createElement('radio', 'seek_bar', '', 'Show', 'show');
        $seek[] = $mform->createElement('radio', 'seek_bar', '', 'Hide', 'hide');

        $mform->addGroup($seek, 'seek_group', get_string('videoseekbar', 'block_xtoscorm'), [' '], false);
        $mform->setDefault('seek_bar', 'show');

        // Submit.
        $mform->addElement('button', 'videoBtn', get_string('convertbtn', 'block_xtoscorm'));
        $mform->setAttributes(['id' => 'videoForm']);
    }
}
