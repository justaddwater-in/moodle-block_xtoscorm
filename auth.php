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

// This file is part of Moodle - http://moodle.org/.

require('../../config.php');

require_login();

use block_xtoscorm\token_manager;

global $DB, $USER, $PAGE, $OUTPUT, $SESSION;

// -----------------------------
// Page setup
// ...-----------------------------.
$context = context_system::instance();
require_capability('block/xtoscorm:use', $context);

$PAGE->set_context($context);
$PAGE->set_url('/blocks/xtoscorm/auth.php');
$PAGE->set_title(get_string('account', 'block_xtoscorm'));
$PAGE->set_heading(get_string('account', 'block_xtoscorm'));
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
echo html_writer::start_div('container mt-4');

// -----------------------------
// Get valid token (decrypt + refresh handled internally).
// -----------------------------.
$token = token_manager::get_valid_token($USER->id);

if ($token) {
    $curl = new \curl();

    $response = $curl->get(
        'https://auth.xtoscorm.com/api/get-account',
        [],
        [
            'CURLOPT_HTTPHEADER' => [
                "Authorization: Bearer {$token}",
                "Accept: application/json",
            ],
            'CURLOPT_TIMEOUT' => 10,
        ]
    );

    // -----------------------------
    // Handle API failure
    // -----------------------------.
    if ($response === false || empty($response)) {
        echo $OUTPUT->notification(
            get_string('apierror', 'block_xtoscorm'),
            'notifyproblem'
        );
    } else {
        $data = json_decode($response, true);

        // -----------------------------
        // Invalid JSON response
        // -----------------------------.
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo $OUTPUT->notification(
                get_string('invalidresponse', 'block_xtoscorm'),
                'notifyproblem'
            );
        } else if (empty($data['data'])) {
            // Remove broken token.
            $DB->delete_records('block_xtoscorm_tokens', [
                'userid' => $USER->id,
            ]);

            redirect(
                new moodle_url('/blocks/xtoscorm/auth.php'),
                get_string('sessionexpired', 'block_xtoscorm')
            );
        } else {
            $email = $data['data']['email'] ?? 'N/A';
            $name  = $data['data']['displayName'] ?? 'User';

            echo html_writer::tag(
                'h3',
                get_string('loggedinuser', 'block_xtoscorm')
            );

            echo html_writer::start_div('card p-3');
            echo html_writer::tag(
                'p',
                get_string('name', 'block_xtoscorm') . ': ' . s($name)
            );
            echo html_writer::tag(
                'p',
                get_string('email', 'block_xtoscorm') . ': ' . s($email)
            );
            echo html_writer::end_div();

            // Logout button.
            $logouturl = new moodle_url(
                '/blocks/xtoscorm/logout.php',
                ['sesskey' => sesskey()]
            );

            echo html_writer::link(
                $logouturl,
                get_string('logout', 'block_xtoscorm'),
                ['class' => 'btn btn-danger mt-3']
            );
        }
    }
} else {
    // -----------------------------
    // Not connected.
    // Generate a one-time OAuth state value.
    // -----------------------------.
    $state = random_string(64);

    // Store the state in the current Moodle session.
    $SESSION->xtoscorm_oauth_state = $state;

    $authurl = token_manager::get_auth_url($state);

    echo html_writer::tag(
        'h3',
        get_string('connectaccount', 'block_xtoscorm')
    );

    echo html_writer::link(
        $authurl->out(false),
        get_string('connectwithxtoscorm', 'block_xtoscorm'),
        ['class' => 'btn btn-primary mt-3']
    );
}

echo html_writer::end_div();
echo $OUTPUT->footer();
