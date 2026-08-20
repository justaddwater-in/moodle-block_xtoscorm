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
    public static function execute_parameters(): external_function_parameters {
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
    public static function execute(
        $fileid,
        $type,
        $scormversion,
        $completion,
        $fitmode,
        $viewmode,
        $hideprogress
    ) {

        global $DB, $USER, $CFG, $PAGE;

        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'fileid' => $fileid,
                'type' => $type,
                'scormversion' => $scormversion,
                'completion' => $completion,
                'fitmode' => $fitmode,
                'viewmode' => $viewmode,
                'hideprogress' => $hideprogress,
            ]
        );

        require_login();

        $context = \context_system::instance();

        self::validate_context($context);

        require_capability('block/xtoscorm:use', $context);

        $PAGE->set_context($context);

        $fileid = $params['fileid'];
        $type = $params['type'];
        $scormversion = $params['scormversion'];
        $completion = $params['completion'];
        $fitmode = $params['fitmode'];
        $viewmode = $params['viewmode'];
        $hideprogress = $params['hideprogress'];

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
        $curl = new \curl();
        $headers = token_manager::build_headers($USER->id);
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
                [
                    'CURLOPT_HTTPHEADER' => $headers,
                    'CURLOPT_CONNECTTIMEOUT' => 10,
                    'CURLOPT_TIMEOUT' => 300,
                ]
            );
        } finally {
            // ALWAYS cleanup.
            if (!empty($tempfile) && file_exists($tempfile)) {
                @unlink($tempfile);
            }
        }

        if ($response === false || $curl->get_errno()) {
            throw new \moodle_exception(
                'conversionapierror',
                'block_xtoscorm'
            );
        }

        if (empty($response)) {
            throw new \moodle_exception(
                'conversionapierror',
                'block_xtoscorm'
            );
        }

        $data = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE || !is_object($data)) {
            throw new \moodle_exception(
                'invalidresponse',
                'block_xtoscorm'
            );
        }

        if (empty($data->file_name) || empty($data->og_file_name)) {
            $errormessage = 'Conversion failed';

            if (!empty($data->detail)) {
                $errormessage = is_string($data->detail)
                    ? $data->detail
                    : 'The conversion service returned an invalid response.';
            }

            return [
                'success' => false,
                'url' => '',
                'message' => $errormessage,
            ];
        }

        $url = new \moodle_url(
            '/blocks/xtoscorm/download_proxy.php',
            [
                'file' => $data->file_name,
                'name' => $data->og_file_name,
                'type' => $type,
            ]
        );

        return [
            'success' => true,
            'url' => $url->out(false),
            'message' => '',
        ];
    }
    /**
     * Summary of execute_returns
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'url' => new external_value(PARAM_URL, 'Download URL', VALUE_OPTIONAL),
            'success' => new external_value(PARAM_BOOL, 'Status'),
            'message' => new external_value(PARAM_RAW, 'Message'),
        ]);
    }
}
