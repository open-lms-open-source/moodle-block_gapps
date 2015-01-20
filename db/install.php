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

    ////////////////////////////////////////////////////////////////////////
    // Code that is really meant to be run when upgrading from 1.9 to 2.0
    // however it's safe to run because what it pulls won't exist
    // and will fail gracefully if we are installing fresh on 2.0


    // Move Config Settings Over
    // Gather settings...
    // gaccess has duplicate newwinlink field so we leave it out and go with the gdata one
    $gdata   = $DB->get_records('config_plugins',array('plugin'=>'blocks/gdata'));
    $gmail   = $DB->get_records('config_plugins',array('plugin'=>'blocks/gmail'));

    // combine settings and insert as gapps
    $sdata = array_merge($gdata,$gmail);
    foreach ($sdata as $row) {
        $row->plugin = 'blocks/gapps';
        if ( $row->name == 'consumer_key' ) {
            continue;
        }
        $DB->insert_record('config_plugins',$row);
    }

    // Delete Old Events
    try {
        // Field "component" does not exist in table "events_handlers" is thrown
        events_uninstall('block/gaccess');
        events_uninstall('block/gdata');
        events_uninstall('block/gmail');
    } catch (Exception $e) {
        print $e->getMessage();
    }

    try {
        // Delete Old Caps
        capabilities_cleanup('block/gaccess');
        capabilities_cleanup('block/gdata');
        capabilities_cleanup('block/gmail');
    } catch (Exception $e) {
        print $e->getMessage();
    }

    // Delete old settings
    $DB->delete_records('config_plugins',array('plugin'=>'blocks/gaccess'));
    $DB->delete_records('config_plugins',array('plugin'=>'blocks/gdata'));
    $DB->delete_records('config_plugins',array('plugin'=>'blocks/gmail'));
    // End of 1.9 to 2.0 code
    ////////////////////////////////////////////////////////////////////////

    // block_gdata_gapps is being renamed to block_gapps_gdata so we collect
    // it's old data and add to the new block
    if ($dbman->table_exists('block_gdata_gapps')) {
        $data = $DB->get_records('block_gdata_gapps');
        foreach ($data as $row) {
            $DB->insert_record('block_gapps_gdata',$row);
        }
        echo $OUTPUT->notification('block_gdata_gapps data transfered to block_gapps_gdata.', 'notifysuccess');

        $dbman->drop_table(new xmldb_table('block_gdata_gapps'));
        echo $OUTPUT->notification('block_gdata_gapps deleted.', 'notifysuccess');

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