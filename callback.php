<?php
// This file is part of Moodle - http://moodle.org.
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

namespace block_xtoscorm;

use block_xtoscorm\token_manager;

require('../../config.php');
require_once($CFG->libdir . '/filelib.php');

require_login();

$context = \context_system::instance();
require_capability('block/xtoscorm:use', $context);

$PAGE->set_context($context);

global $DB, $USER, $SESSION;

// 1. Get callback parameters.
$sessionid = required_param('session_id', PARAM_ALPHANUMEXT);
$state = required_param('state', PARAM_ALPHANUMEXT);

// 2. Validate session_id format.
if (!preg_match('/^[a-zA-Z0-9_\-]{10,128}$/', $sessionid)) {
    throw new \moodle_exception(
        'invalidsessionid',
        'block_xtoscorm'
    );
}

// 3. Validate OAuth state.
//
// The state was generated in auth.php before the user was
// redirected to XtoSCORM. It must match the value stored in
// the current Moodle session.
//
// This prevents an attacker from reusing a callback belonging
// to another authentication attempt.
//

if (
    empty($SESSION->xtoscorm_oauth_state) ||
    !hash_equals($SESSION->xtoscorm_oauth_state, $state)
) {
    throw new \moodle_exception(
        'invalidstate',
        'block_xtoscorm'
    );
}

// OAuth state is one-time use.
// Remove it before continuing so the same callback cannot
// be reused.
unset($SESSION->xtoscorm_oauth_state);

// 4. Call external XtoSCORM API.
$url = 'https://api.xtoscorm.com/api/session?session_id=' .
    rawurlencode($sessionid);

$curl = new \curl();

$options = [
    'CURLOPT_TIMEOUT' => 10,
    'CURLOPT_CONNECTTIMEOUT' => 5,
];

$response = $curl->get($url, [], $options);

// 5. Handle API failure.
if ($response === false || $curl->get_errno()) {
    throw new \moodle_exception(
        'apierror_auth',
        'block_xtoscorm'
    );
}

// 6. Validate JSON response.
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    throw new \moodle_exception(
        'apierror_auth',
        'block_xtoscorm'
    );
}

// 7. Validate returned user/session.
if (empty($data['user']) || !is_array($data['user'])) {
    throw new \moodle_exception(
        'invalidsession',
        'block_xtoscorm'
    );
}

// 8. Validate access token.
$access = $data['access_token'] ?? null;
$expires = isset($data['expires_in'])
    ? (int) $data['expires_in']
    : 3600;

if (empty($access)) {
    throw new \moodle_exception(
        'notoken',
        'block_xtoscorm'
    );
}

// Prevent invalid/negative expiry values.
if ($expires <= 0) {
    $expires = 3600;
}

// 9. Save Moodle user's XtoSCORM token.
token_manager::save_token(
    $USER->id,
    $access,
    $expires
);

// 10. Redirect back to account page.
redirect(
    new \moodle_url('/blocks/xtoscorm/auth.php'),
    get_string('loginsuccess', 'block_xtoscorm')
);
