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
 * Class provider
 *
 * @package    block_xtoscorm
 * @copyright  2026 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_xtoscorm\privacy;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;

/**
 * Privacy API provider implementation.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe metadata stored by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table('block_xtoscorm_tokens', [
            'userid' => 'privacy:metadata:block_xtoscorm_tokens:userid',
            'token' => 'privacy:metadata:block_xtoscorm_tokens:token',
            'refreshtoken' => 'privacy:metadata:block_xtoscorm_tokens:refreshtoken',
            'expiresat' => 'privacy:metadata:block_xtoscorm_tokens:expiresat',
            'timecreated' => 'privacy:metadata:block_xtoscorm_tokens:timecreated',
        ], 'privacy:metadata:block_xtoscorm_tokens');

        return $collection;
    }

    /**
     * Get contexts for a given user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();

        if ($DB->record_exists('block_xtoscorm_tokens', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->get_contextids())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        $records = $DB->get_records('block_xtoscorm_tokens', [
            'userid' => $userid,
        ]);

        if (!$records) {
            return;
        }

        $data = [];

        foreach ($records as $record) {
            $data[] = (object)[
                'expiresat' => transform::datetime($record->expiresat),
                'timecreated' => transform::datetime($record->timecreated),
                // IMPORTANT: Do NOT export tokens (security-sensitive).
            ];
        }

        writer::with_context(\context_system::instance())
            ->export_data(
                ['block_xtoscorm'],
                (object)['tokens' => $data]
            );
    }

    /**
     * Delete all user data for the given user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->get_contextids())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        $DB->delete_records('block_xtoscorm_tokens', [
            'userid' => $userid,
        ]);
    }

    /**
     * Delete all data for all users in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        $DB->delete_records('block_xtoscorm_tokens');
    }
}
