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

    // Consolidate former block_instances of gmail,gaccess and gdata
    // and make sure only one gapps per parentcontextid
    $insts = $DB->get_records_select('block_instances', " blockname='gmail' OR blockname='gaccess' OR blockname='gdata' ",
                                     null,'id, blockname, parentcontextid');

    // convert instances to gapps and update a single instance in that context while
    // deleting all the extra ones
    $current_cxt = null;
    foreach ($insts as $inst) {
        // are we the first one in a new context?
        if ($current_cxt != $inst->parentcontextid) {
            $current_cxt = $inst->parentcontextid;
            $inst->blockname = 'gapps';
            $DB->update_record('block_instances',$inst);
        } else {
            $DB->delete_records('block_instances', array('id'=>$inst->id));
        }
    }

    // Delete from block table
    $DB->delete_records('block', array('name'=>'gdata'));
    $DB->delete_records('block', array('name'=>'gaccess'));
    $DB->delete_records('block', array('name'=>'gmail'));
    
}