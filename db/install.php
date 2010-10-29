<?php

/**
 * Migrate the data from the old tables for gmail, gaccess and gdata
 * 
 * @global object $CFG
 * @global object $DB
 * @global object $OUTPUT
 */
function xmldb_block_gapps_install() {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    echo $OUTPUT->notification('block_gapps 2.0 Installing', 'notifysuccess');
    
    // Migrate Data from former tables gmail,gdata,gaccess
    if ($dbman->table_exists('block_gdata_gapps_old')) {
        $data = $DB->get_records('block_gdata_gapps_old');
        foreach ($data as $row) {
            $DB->insert_record('block_gdata_gapps',$row);
        }
        echo $OUTPUT->notification('block_gdata_gapps data transfered.', 'notifysuccess');

        $dbman->drop_table(new xmldb_table('block_gdata_gapps_old'));
        echo $OUTPUT->notification('block_gdata_gapps_old deleted.', 'notifysuccess');

    }

    if ($dbman->table_exists('block_gapps_oauth_consumer_token_old')) {
        $data = $DB->get_records('block_gapps_oauth_consumer_token_old');
        foreach ($data as $row) {
            $DB->insert_record('block_gapps_oauth_consumer_token',$row);
        }
        echo $OUTPUT->notification('block_gapps_oauth_consumer_token data transfered.', 'notifysuccess');

        $dbman->drop_table(new xmldb_table('block_gapps_oauth_consumer_token_old'));
        echo $OUTPUT->notification('block_gapps_oauth_consumer_token_old deleted.', 'notifysuccess');
    }

    // Don't create new and just drop the old
    if ($dbman->table_exists('block_gapps_old')) {
        $dbman->drop_table(new xmldb_table('block_gapps_old'));
        echo $OUTPUT->notification('block_gapps_old deleted.', 'notifysuccess');
    }

}