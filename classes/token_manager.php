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
namespace block_xtoscorm;

use core\encryption;
use cache;
    /**
     * Summary of token_manager
     */
class token_manager {
    /**
     * Summary of get_valid_token
     * @param int $userid
     * @return string|null
     */
    public static function get_valid_token(int $userid): ?string {
        global $DB;

        $record = $DB->get_record(
            'block_xtoscorm_tokens',
            ['userid' => $userid]
        );

        if (!$record) {
            return null;
        }

        // Token expired.
        if (!empty($record->expiresat) && $record->expiresat < time()) {
            debugging(
                'block_xtoscorm: ' .
                get_string('tokenexpireddebug', 'block_xtoscorm', $userid),
                DEBUG_DEVELOPER
            );

            // Remove expired token.
            $DB->delete_records(
                'block_xtoscorm_tokens',
                ['userid' => $userid]
            );

            return null;
        }

        // Decrypt token safely.
        try {
            return encryption::decrypt($record->token);
        } catch (\Exception $e) {
            debugging(
                'block_xtoscorm: ' .
                get_string(
                    'decryptfaileddebug',
                    'block_xtoscorm',
                    $userid
                ) . ' - ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );

            // Remove corrupted token.
            $DB->delete_records(
                'block_xtoscorm_tokens',
                ['userid' => $userid]
            );

            return null;
        }
    }

    /**
     * Save access token for a user.
     *
     * @param int $userid Moodle user ID
     * @param string $access Access token
     * @param int $expires Expiry time in seconds
     * @return void
     */
    public static function save_token(
        int $userid,
        string $access,
        int $expires
    ): void {
        global $DB;

        $record = $DB->get_record(
            'block_xtoscorm_tokens',
            ['userid' => $userid]
        );

        $data = new \stdClass();

        $data->userid = $userid;
        $data->token = encryption::encrypt($access);
        $data->expiresat = time() + $expires;
        $data->timecreated = time();

        if ($record) {
            $data->id = $record->id;

            $DB->update_record(
                'block_xtoscorm_tokens',
                $data
            );
        } else {
            $DB->insert_record(
                'block_xtoscorm_tokens',
                $data
            );
        }
    }

    /**
     * Build HTTP headers for API requests.
     *
     * @param int $userid Moodle user ID
     * @return array
     */
    public static function build_headers(int $userid): array {

        $token = self::get_valid_token($userid);

        $ip = self::get_client_ip();

        $headers = [
            "X-Real-IP: {$ip}",
            "X-Forwarded-For: {$ip}",
            "X-XTOSCORM-Client-IP:{$ip}",
        ];

        if ($token) {
            $headers[] = "Authorization: Bearer {$token}";

            return $headers;
        }

        $headers[] = "x-free-token: " . self::get_free_token();

        return $headers;
    }

    /**
     * Get free token (session-based).
     *
     * @return string
     * @throws \moodle_exception
     */
    public static function get_free_token(): string {
        $cache = cache::make('block_xtoscorm', 'xtoscorm_token');

        $token = $cache->get('token');

        if (!empty($token)) {
            return $token;
        }

        $curl = new \curl();

        $ip = self::get_client_ip();

        $response = $curl->post(
            "https://api.xtoscorm.com/scorm/init",
            [],
            [
                'CURLOPT_HTTPHEADER' => [
                    "X-Forwarded-For: {$ip}",
                ],
                'CURLOPT_TIMEOUT' => 10,
            ]
        );
        if ($response === false || $curl->get_errno()) {
            throw new \moodle_exception(
                'apierror',
                'block_xtoscorm',
                '',
                null,
                $response
            );
        }

        $data = json_decode($response);

        if (empty($data->token)) {
            throw new \moodle_exception(
                'notoken',
                'block_xtoscorm'
            );
        }

        $cache = \cache::make('block_xtoscorm', 'xtoscorm_token');
        $cache->set('token', $data->token);

        return $data->token;
    }

    /**
     * Generate OAuth login URL.
     *
     * @param string $state One-time OAuth state value.
     * @return \moodle_url
     */
    public static function get_auth_url(string $state): \moodle_url {
        $returnurl = (
            new \moodle_url(
                '/blocks/xtoscorm/callback.php'
            )
        )->out(false);

        return new \moodle_url(
            'https://api.xtoscorm.com/oauth/start',
            [
                'return_url' => $returnurl,
                'state' => $state,
            ]
        );
    }

    /**
     * Get client IP address.
     *
     * @return string
     */
    private static function get_client_ip(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (empty($_SERVER[$header])) {
                continue;
            }

            $ips = explode(',', $_SERVER[$header]);

            foreach ($ips as $ip) {
                $ip = trim($ip);

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
