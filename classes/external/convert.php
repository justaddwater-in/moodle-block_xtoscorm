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
 * Class convert
 *
 * @package    block_xtoscorm
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_xtoscorm\external;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use block_xtoscorm\token_manager;
/**
 * Summary of convert
 */
class convert extends external_api {
    /**
     * Summary of execute_parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'fileid' => new external_value(PARAM_INT, 'Draft ID'),
            'type'   => new external_value(PARAM_TEXT, 'Type'),

            'scormversion' => new external_value(PARAM_TEXT, '', VALUE_DEFAULT, ''),
            'completion'    => new external_value(PARAM_TEXT, '', VALUE_DEFAULT, ''),
            'fitmode'      => new external_value(PARAM_TEXT, '', VALUE_DEFAULT, ''),
            'viewmode'     => new external_value(PARAM_TEXT, '', VALUE_DEFAULT, ''),
            'hideprogress' => new external_value(PARAM_TEXT, '', VALUE_DEFAULT, ''),
        ]);
    }
    /**
     * Summary of execute
     * @param mixed $fileid
     * @param mixed $type
     * @param mixed $scormversion
     * @param mixed $completion
     * @param mixed $fitmode
     * @param mixed $viewmode
     * @param mixed $hideprogress
     * @throws \moodle_exception
     * @return array{url: string}
     */
    public static function execute($fileid, $type, $scormversion, $completion, $fitmode, $viewmode, $hideprogress) {

        global $DB, $USER, $CFG;
        require_login();

        $fs = get_file_storage();

        $files = $fs->get_area_files(
            \context_user::instance($USER->id)->id,
            'user',
            'draft',
            $fileid,
            'id DESC',
            false
        );

        if (!$files) {
            throw new \moodle_exception('nofilefound', 'block_xtoscorm');
        }
        $headers = token_manager::build_headers($USER->id);
                // API URL.
        $remainingurl = 'https://api.xtoscorm.com/scorm/limit';

        // Call API using curl.
        $curl = new \curl();
        $response = $curl->get($remainingurl, [], ['CURLOPT_HTTPHEADER' => $headers]);

        $remainingdata = json_decode($response, true);

        // Safely get remaining.
        $remaining = $remainingdata['data']['remaining'] ?? null;
        // STOP if limit reached.
        if ($remaining !== null && (int)$remaining === 0) {
            $authurl = token_manager::get_auth_url();

            return [
                'url' => '', // IMPORTANT (keep key present).
                'status' => false,
                'errorcode' => 'limitreached',
                'redirect' => $authurl->out(false),
            ];
        }

        $file = reset($files);

        $tempfile = tempnam(sys_get_temp_dir(), 'upload_');
        $allowedtypes = ['pdf', 'ppt', 'video'];

        if (!in_array($type, $allowedtypes, true)) {
            throw new \moodle_exception('invalidtype', 'block_xtoscorm');
        }
        try {
                $file->copy_content_to($tempfile);

            $fieldname = $type . '_file';

            $postdata = [
                $fieldname => new \CURLFile($tempfile, 'application/octet-stream', $file->get_filename()),
                'scormversion' => $scormversion,
                'source' => 'moodle',
                'type' => $type,
            ];

            switch ($type) {
                case 'pdf':
                    $postdata['page_completion'] = $completion;
                    $postdata['fit_mode'] = $fitmode;
                    $postdata['view_mode'] = $viewmode;
                    $postdata['color_picker'] = '#007BFF';
                    break;

                case 'ppt':
                    $postdata['slide_completion'] = $completion;
                    $postdata['color_picker'] = '#007BFF';
                    break;

                case 'video':
                    $postdata['completion'] = (int)$completion;
                    $postdata['hide_progress'] = $hideprogress == 'show' ? 0 : 1;
                    break;
            }

            $response = $curl->post(
                "https://api.xtoscorm.com/scorm/convert/$type",
                $postdata,
                ['CURLOPT_HTTPHEADER' => $headers]
            );
        } finally {
            // ALWAYS cleanup.
            if (!empty($tempfile) && file_exists($tempfile)) {
                @unlink($tempfile);
            }
        }

        $data = json_decode($response);

        // DEBUG.
        if (empty($data->file_name)) {
                $errormessage = 'Something went wrong during conversion';

            if (!empty($data->detail)) {
                $errormessage = $data->detail;
            }

            throw new \moodle_exception('apierror', 'block_xtoscorm', '', $errormessage);
        }

        $url = $CFG->wwwroot . "/blocks/xtoscorm/download_proxy.php?file=" .
        urlencode($data->file_name) . "&name=" . urlencode($data->og_file_name) . "&type=" . urlencode($type);

        return [
            'url' => $url,
            'status' => true,
            'errorcode' => '',
            'redirect' => '',
        ];
    }
    /**
     * Summary of execute_returns
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'url' => new external_value(PARAM_URL, 'Download URL', VALUE_OPTIONAL),
            'status' => new external_value(PARAM_BOOL, 'Status', VALUE_OPTIONAL),
            'errorcode' => new external_value(PARAM_TEXT, 'Error code', VALUE_OPTIONAL),
            'redirect' => new external_value(PARAM_URL, 'Redirect URL', VALUE_OPTIONAL),
        ]);
    }
}
