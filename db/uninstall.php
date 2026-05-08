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
/**
 * Uninstall hook for block_xtoscorm
 *
 * Cleans up plugin configuration.
 *
 * @return bool
 */
function xmldb_block_xtoscorm_uninstall(): bool {

    // Remove all plugin configuration.
    unset_all_config_for_plugin('block_xtoscorm');

    // NOTE:
    // - block_xtoscorm_tokens table is automatically dropped by XMLDB
    // - No need to manually delete DB records
    // - No user preferences are used in this plugin.

    return true;
}
