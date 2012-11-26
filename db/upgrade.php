<?php
/**
 * Copyright (C) 2010  Moodlerooms Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @copyright  Copyright (c) 2009 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://opensource.org/licenses/gpl-3.0.html     GNU Public License
 */

/**
 * Upgrades
 *
 * @author Chris Stones
 * @package block_gapps
 **/
function xmldb_block_gapps_upgrade($oldversion=0) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();
    $result = true;

    // Checks required for upgrading between 1.9 and 2.0 are found
    // in _self_test() in the block definition then db/install.php is
    // used to clean up data

    if ($oldversion < 2012062401) {
        // Rename table block_gdata_gapps to block_gapps_gdata
        $table_incorrect = new xmldb_table('block_gdata_gapps');
        $table_correct = new xmldb_table('block_gapps_gdata');

        // Conditionally launch rename if correctly named table doesn't exist
        if ($dbman->table_exists($table_incorrect) && !$dbman->table_exists($table_correct)) {
            $dbman->rename_table($table_incorrect, 'block_gapps_gdata');
        }

        // Drop incorrectly named table, if it exists
        if ($dbman->table_exists($table_incorrect)) {
            $dbman->drop_table($table_incorrect);
        }

        // gapps savepoint reached
        upgrade_block_savepoint(true, 2012062401, 'gapps');
    }

    if ($oldversion < 2012112600) {

        // Define table block_gapps_oauth_consumer_token to be renamed to block_gapps_oauth_token
        $table = new xmldb_table('block_gapps_oauth_consumer_token');

        // Launch rename table for block_gapps_oauth_consumer_token
        $dbman->rename_table($table, 'block_gapps_oauth_token');

        // gapps savepoint reached
        upgrade_block_savepoint(true, 2012112600, 'gapps');
    }

    return true;
}
