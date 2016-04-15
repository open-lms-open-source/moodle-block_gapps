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
 * Plugin upgrade path
 *
 * @package   block_gapps
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade path
 *
 * @param int $oldversion Upgrading from this version.
 * @return bool
 */
function xmldb_block_gapps_upgrade($oldversion=0) {
    global $DB;

    $dbman = $DB->get_manager();

    // Checks required for upgrading between 1.9 and 2.0 are found
    // in _self_test() in the block definition then db/install.php is
    // used to clean up data.

    if ($oldversion < 2012062401) {
        // Rename table block_gdata_gapps to block_gapps_gdata.
        $tableincorrect = new xmldb_table('block_gdata_gapps');
        $tablecorrect = new xmldb_table('block_gapps_gdata');

        // Conditionally launch rename if correctly named table doesn't exist.
        if ($dbman->table_exists($tableincorrect) && !$dbman->table_exists($tablecorrect)) {
            $dbman->rename_table($tableincorrect, 'block_gapps_gdata');
        }

        // Drop incorrectly named table, if it exists.
        if ($dbman->table_exists($tableincorrect)) {
            $dbman->drop_table($tableincorrect);
        }

        // Gapps savepoint reached.
        upgrade_block_savepoint(true, 2012062401, 'gapps');
    }

    if ($oldversion < 2012112600) {

        // Define table block_gapps_oauth_consumer_token to be renamed to block_gapps_oauth_token.
        $table = new xmldb_table('block_gapps_oauth_consumer_token');

        // Launch rename table for block_gapps_oauth_consumer_token.
        $dbman->rename_table($table, 'block_gapps_oauth_token');

        // Gapps savepoint reached.
        upgrade_block_savepoint(true, 2012112600, 'gapps');
    }

    if ($oldversion < 2013121600) {

        // Changing precision of field password on table block_gapps_gdata to (255).
        $table = new xmldb_table('block_gapps_gdata');
        $field = new xmldb_field('password', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'username');

        // Launch change of precision for field password.
        $dbman->change_field_precision($table, $field);

        // Gapps savepoint reached.
        upgrade_block_savepoint(true, 2013121600, 'gapps');
    }

    if ($oldversion < 2014051403) {

        // Define table block_gapps_oauth_token to be dropped.
        $table = new xmldb_table('block_gapps_oauth_token');

        // Conditionally launch drop table for block_gapps_oauth_token.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Gapps savepoint reached.
        upgrade_block_savepoint(true, 2014051403, 'gapps');
    }

    return true;
}
