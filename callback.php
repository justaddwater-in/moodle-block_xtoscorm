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
$PAGE->set_context($context);

global $DB, $USER;

// ...==============================.
// 1. Get params
// ...==============================.
$sessionid = required_param('session_id', PARAM_ALPHANUMEXT);

// ...==============================.
// 2. Validate session_id format
// ...==============================.
if (!preg_match('/^[a-zA-Z0-9_\-]{10,128}$/', $sessionid)) {
    throw new \moodle_exception('invalidsessionid', 'block_xtoscorm');
}

// ...==============================
// 7. Call external API (secure)
// ==============================
$url = "https://api0.xtoscorm.com/api/session?session_id=" . $sessionid;

$curl = new \curl();

$options = [
    'CURLOPT_TIMEOUT' => 10,
];

$response = $curl->get($url, [], $options);

if ($response === false) {
    throw new \moodle_exception('apierror_auth', 'block_xtoscorm');
}

// ...==============================
// 8. Validate JSON response
// ==============================
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new \moodle_exception('apierror_auth', 'block_xtoscorm');
}

if (empty($data['user'])) {
    throw new \moodle_exception('invalidsession', 'block_xtoscorm');
}

$access = $data['access_token'] ?? null;
$refresh = $data['refresh_token'] ?? null;
$expires = $data['expires_in'] ?? 3600;

if (!$access) {
    throw new \moodle_exception('notoken', 'block_xtoscorm');
}

token_manager::save_token(
    $USER->id,
    $access,
    $refresh,
    $expires
);

// ...==============================
// 10. Redirect
// ==============================
redirect(
    new moodle_url('/blocks/xtoscorm/auth.php'),
    get_string('loginsuccess', 'block_xtoscorm')
);
