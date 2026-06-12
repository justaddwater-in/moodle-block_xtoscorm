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
 *
 * @package    block_xtoscorm
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use block_xtoscorm\form\video_form;
require('../../config.php');

require_login();

$context = context_system::instance();
require_capability('block/xtoscorm:use', $context);
$PAGE->set_context($context);
$url = new moodle_url('/blocks/xtoscorm/video.php', []);
$PAGE->set_url($url);
$PAGE->set_title(get_string('videotoscorm', 'block_xtoscorm') . ' | ' . get_string('pluginname', 'block_xtoscorm'));
$PAGE->set_heading(get_string('pluginname', 'block_xtoscorm'));
$PAGE->set_pagelayout('standard');


$mform = new video_form();

// GET FORM DATA (IMPORTANT).
$data = $mform->get_data();

$draftitemid = 0;

if ($data) {
    $draftitemid = $data->video_file; // MUST match your filepicker name.
}

// LOAD JS AFTER draftitemid is available.
$PAGE->requires->js_call_amd('block_xtoscorm/convert', 'init', [[
    'button' => 'id_videoBtn', // Moodle adds "id_" automatically.
    'fileid' => $draftitemid,
    'form' => 'videoForm',
    'type' => 'video',
    'scorm' => 'scorm_version',
    'completion' => 'id_completion_marker',
    'fit' => null,
    'view' => null,
    'progress' => 'seek_bar',
]]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/my'));
}

echo $OUTPUT->header();
echo '<div id="moodleNotification"></div>';
echo $OUTPUT->heading(
    get_string('videotoscormconverter', 'block_xtoscorm')
);

$mform->display();

echo $OUTPUT->footer();
