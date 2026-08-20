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

use block_xtoscorm\token_manager;

require('../../config.php');
require_once($CFG->libdir . '/filelib.php');

require_login();
$context = context_system::instance();
require_capability('block/xtoscorm:use', $context);

// Get params.
$file = required_param('file', PARAM_TEXT);
$name = required_param('name', PARAM_FILE);
$type = required_param('type', PARAM_ALPHA);

if (!preg_match('/^[A-Za-z0-9._-]+$/', $file)) {
    throw new \moodle_exception(
        'invalidfile',
        'block_xtoscorm'
    );
}

$allowedtypes = ['pdf', 'ppt', 'video'];

if (!in_array($type, $allowedtypes, true)) {
    throw new \moodle_exception(
        'invalidtype',
        'block_xtoscorm'
    );
}

// Build headers from token manager.
$headers = token_manager::build_headers($USER->id);

// FastAPI URL.
$url = "https://api.xtoscorm.com/scorm/download/{$type}/moodle/{$file}/" .
    urlencode($name);

// Moodle curl wrapper.
$curl = new \curl();

$options = [
    'CURLOPT_HTTPHEADER' => $headers,
    'CURLOPT_CONNECTTIMEOUT' => 10,
    'CURLOPT_TIMEOUT' => 60,
    'CURLOPT_FOLLOWLOCATION' => true,
];

$response = $curl->get($url, [], $options);

if ($response === false || $curl->get_errno()) {
    throw new \moodle_exception(
        'downloadfailed',
        'block_xtoscorm'
    );
}

// Clean output buffers.
while (ob_get_level()) {
    ob_end_clean();
}

// Download headers.
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . strlen($response));
header('Cache-Control: private, must-revalidate');
header('Pragma: public');

echo $response;
exit;
