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
 * Class token_manager
 *
 * @package    block_xtoscorm
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This file is part of Moodle - http://moodle.org/.

namespace block_xtoscorm;

use core\encryption;

/**
 * Token manager for XtoSCORM integration.
 *
 * Handles encryption, refresh, and retrieval of tokens.
 *
 * @package    block_xtoscorm
 */
class token_manager {
    /**
     * Get a valid access token for a user.
     *
     * Automatically decrypts and refreshes the token if expired.
     *
     * @param int $userid Moodle user ID
     * @return string|null Decrypted access token or null if unavailable
     */
    public static function get_valid_token(int $userid): ?string {
        global $DB;

        $record = $DB->get_record('block_xtoscorm_tokens', ['userid' => $userid]);

        if (!$record) {
            return null;
        }

        // Decrypt token safely.
        try {
            $token = encryption::decrypt($record->token);
        } catch (\Exception $e) {
            debugging(
                'block_xtoscorm: Failed to decrypt token for user ' . $userid .
                ' - ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );

            // Remove corrupted token to force re-auth.
            $DB->delete_records('block_xtoscorm_tokens', ['userid' => $userid]);

            return null;
        }

        // Check expiry.
        if (!empty($record->expiresat) && $record->expiresat < time()) {
            return self::refresh_token($userid, $record);
        }

        return $token;
    }

    /**
     * Refresh an expired access token using refresh token.
     *
     * @param int $userid Moodle user ID
     * @param \stdClass $record Token DB record
     * @return string|null New access token or null on failure
     */
    private static function refresh_token(int $userid, \stdClass $record): ?string {
        global $DB;

        if (empty($record->refreshtoken)) {
            return null;
        }

        // Decrypt refresh token.
        try {
            $refresh = encryption::decrypt($record->refreshtoken);
        } catch (\Exception $e) {
            debugging(
                'block_xtoscorm: Failed to decrypt refresh token for user ' . $userid,
                DEBUG_DEVELOPER
            );

            return null;
        }

        $curl = new \curl();

        $response = $curl->post(
            'https://api0.xtoscorm.com/oauth/refresh',
            [
                'refresh_token' => $refresh,
            ],
            [
                'CURLOPT_TIMEOUT' => 15,
            ]
        );

        // FIX #1: Handle network failure.
        if ($response === false || $curl->get_errno()) {
            debugging(
                'block_xtoscorm: Token refresh HTTP error for user ' . $userid .
                ' - ' . $curl->error,
                DEBUG_DEVELOPER
            );
            return null;
        }

        // FIX #2: Validate JSON.
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging(
                'block_xtoscorm: Invalid JSON during token refresh for user ' . $userid,
                DEBUG_DEVELOPER
            );
            return null;
        }

        // FIX #3: Validate response structure.
        if (empty($data['access_token'])) {
            debugging(
                'block_xtoscorm: Missing access_token in refresh response for user ' . $userid,
                DEBUG_DEVELOPER
            );
            return null;
        }

        // Save new tokens (encrypted).
        $record->token = encryption::encrypt($data['access_token']);
        $record->refreshtoken = encryption::encrypt($data['refresh_token'] ?? $refresh);
        $record->expiresat = time() + ($data['expires_in'] ?? 3600);

        $DB->update_record('block_xtoscorm_tokens', $record);

        return $data['access_token'];
    }

    /**
     * Save access and refresh tokens for a user.
     *
     * @param int $userid Moodle user ID
     * @param string $access Access token
     * @param string|null $refresh Refresh token
     * @param int $expires Expiry time in seconds
     * @return void
     */
    public static function save_token(
        int $userid,
        string $access,
        ?string $refresh,
        int $expires
    ): void {
        global $DB;

        $record = $DB->get_record('block_xtoscorm_tokens', ['userid' => $userid]);

        $data = new \stdClass();
        $data->userid = $userid;
        $data->token = encryption::encrypt($access);
        $data->refreshtoken = $refresh ? encryption::encrypt($refresh) : null;
        $data->expiresat = time() + $expires;
        $data->timecreated = time();

        if ($record) {
            $data->id = $record->id;
            $DB->update_record('block_xtoscorm_tokens', $data);
        } else {
            $DB->insert_record('block_xtoscorm_tokens', $data);
        }
    }

    /**
     * Build HTTP headers for API requests.
     *
     * @param int $userid Moodle user ID
     * @return string[] HTTP headers
     */
    public static function build_headers(int $userid): array {

        $token = self::get_valid_token($userid);

        if ($token) {
            return ["Authorization: Bearer {$token}"];
        }

        return ["x-free-token: " . self::get_free_token()];
    }

    /**
     * Get free token (session-based).
     *
     * @return string Free API token
     * @throws \moodle_exception
     */
    public static function get_free_token(): string {
        global $SESSION;

        if (!empty($SESSION->xtoscorm_token)) {
            return $SESSION->xtoscorm_token;
        }

        $curl = new \curl();
        $ip = \getremoteaddr();

        $response = $curl->post(
            "https://api.xtoscorm.com/scorm/init",
            [],
            [
                'CURLOPT_HTTPHEADER' => ["X-Forwarded-For: {$ip}"],
                'CURLOPT_TIMEOUT' => 10,
            ]
        );

        if ($response === false || $curl->get_errno()) {
            throw new \moodle_exception('apierror', 'block_xtoscorm');
        }

        $data = json_decode($response);

        if (empty($data->token)) {
            throw new \moodle_exception('notoken', 'block_xtoscorm');
        }

        $SESSION->xtoscorm_token = $data->token;

        return $data->token;
    }

    /**
     * Generate OAuth login URL.
     *
     * @return \moodle_url
     */
    public static function get_auth_url(): \moodle_url {
        $secret = "xtoscorm_super_secret_2026_!@#_ABC123";

        $returnurl = (new \moodle_url('/blocks/xtoscorm/callback.php'))->out(false);

        $data = [
            "return_url" => $returnurl,
            "ts" => time(),
        ];

        $payload = rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $payload, $secret);
        $state = $payload . "." . $signature;

        return new \moodle_url('https://auth.xtoscorm.com/login/oauth/authorize', [
            'client_id' => '63d9ea2119017227a38b',
            'response_type' => 'code',
            'redirect_uri' => 'https://api.xtoscorm.com/oauth/callback',
            'scope' => 'openid profile email',
            'state' => $state,
        ]);
    }
}
