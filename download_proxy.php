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
 * TODO describe file download_proxy
 *
 * @package    block_xtoscorm
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use block_xtoscorm\token_manager;
require('../../config.php');
require_login();

// Get params.
$file = required_param('file', PARAM_TEXT);
$name = required_param('name', PARAM_TEXT);
$type = required_param('type', PARAM_TEXT);

// Build headers from token manager (BEST PRACTICE).
$headers = token_manager::build_headers($USER->id);

// FastAPI URL.
$url = "https://api.xtoscorm.com/scorm/download/$type/moodle/$file/" . urlencode($name);

// ...cURL request.
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

if ($response === false) {
    http_response_code(500);
    echo "Error: " . curl_error($ch);
    exit;
}

// Clean buffer.
while (ob_get_level()) {
    ob_end_clean();
}

// Force correct headers.
header('Content-Description: File Transfer');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . strlen($response));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');
header('Expires: 0');

echo $response;
exit;
