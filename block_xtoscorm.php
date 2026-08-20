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
 * Block block_xtoscorm
 *
 * Documentation: {@link https://moodledev.io/docs/apis/plugintypes/blocks}
 *
 * @package    block_xtoscorm
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_xtoscorm extends block_base {
    /**
     * Block initialisation
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_xtoscorm');
    }

    /**
     * Get content
     *
     * @return stdClass
     */
    public function get_content() {
        global $OUTPUT, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        if (!isloggedin() || isguestuser()) {
            $this->content->text = get_string('pleaselogin', 'block_xtoscorm');
            return $this->content;
        }

        $pdfurl   = new moodle_url('/blocks/xtoscorm/pdf.php');
        $ppturl   = new moodle_url('/blocks/xtoscorm/ppt.php');
        $videourl = new moodle_url('/blocks/xtoscorm/video.php');
        $authurl = new moodle_url('/blocks/xtoscorm/auth.php');
        // Description text.
        $description = html_writer::tag(
            'p',
            get_string('blockdescription', 'block_xtoscorm'),
            ['class' => 'xtoscorm-desc text-muted mb-2']
        );

        // Buttons.
        $buttonshtml =
            html_writer::link($pdfurl, get_string('convertpdflink', 'block_xtoscorm'), ['class' => 'btn btn-primary xto-btn']) .
            html_writer::link($ppturl, get_string('convertpptlink', 'block_xtoscorm'), ['class' => 'btn btn-success xto-btn']) .
            html_writer::link($videourl, get_string('convertvideolink', 'block_xtoscorm'), ['class' => 'btn btn-warning xto-btn']);

        $buttons = html_writer::div(
            $buttonshtml,
            'xtoscorm-buttons d-flex flex-wrap gap-2'
        );

        $this->content->text = $description . $buttons;

        $this->content->footer = '';

        return $this->content;
    }
}
