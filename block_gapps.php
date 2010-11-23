<?php
/**
 * Copyright (C) 2009  Moodlerooms Inc.
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
 * @package block_gapps
 */

/**
 * This block encapsulates all the prior functionality of the Gmail,Gapps and Gdata blocks
 * from the prior release of the Google-Moodle intergration.
 *
 * @author Chris Stones
 * @package block_gapps
 */
class block_gapps extends block_base {
    /**
     * Init
     */
    function init() {
        $this->title   = get_string('blockname', 'block_gapps');

        // Toggle on or off for development
        // purge_all_caches();
        
        // If you want to update the events without modifing version.php use...
        // events_update_definition('block_gapps');
    }

    /**
     * This funtion is the only hook we have before the install/upgrade code  gaccess,gdata and gmail
     * for 2.0 will run. (see lib/upgradelib.php lines 595 and so on)
     *
     * This function checks if we are upgrading from 1.9 series to 2.0 and
     * it renames the tables so data can be migrated on post install
     */
     function _self_test() {
        global $CFG, $DB, $OUTPUT;

        $dbman = $DB->get_manager();

        // Check that we are upgrading from 1.9 to 2.0
        // needs to only run when upgrading from 1.9 to 2.0 and no other time

        // Check that we are upgrading from 1.9 to 2.0 and if we have a gapps already?
        // oldversion will be false if gapps wasn't installed
        // prior so check that it's a boolean value
        // and future that if it's false don't run any update code
        $oldversion = $DB->get_field('block','version',array('name'=>'gapps')); // false if not found
        if ( $CFG->version  < 2010102501 and (is_bool($oldversion) and !$oldversion) ) {

            echo $OUTPUT->notification('Updating gapps from 1.9 to 2.0', 'notifysuccess');
            
            // Don't rename tables if the renamed tables exist
            if ($dbman->table_exists('block_gdata_gapps') and !$dbman->table_exists('block_gdata_gapps_old')) {
                // rename by adding suffix _old
                echo $OUTPUT->notification('block_gdata_gapps exists renaming to block_gdata_gapps_old', 'notifysuccess');
                $dbman->rename_table(new xmldb_table('block_gdata_gapps'), 'block_gdata_gapps_old');
            }


            if ($dbman->table_exists('block_gapps')) {
                $dbman->rename_table(new xmldb_table('block_gapps'),'block_gapps_old');
                echo $OUTPUT->notification('block_gapps renamed to block_gapps_old.', 'notifysuccess');
            }


            if ($dbman->table_exists('block_gapps_oauth_consumer_token')  and !$dbman->table_exists('block_gapps_oauth_consumer_token_old') ) {
                // rename by adding suffix _old
                echo $OUTPUT->notification('block_gapps_oauth_consumer_token exists renaming to block_gapps_oauth_consumer_token_old', 'notifysuccess');
                $dbman->rename_table(new xmldb_table('block_gapps_oauth_consumer_token'), 'block_gapps_oauth_consumer_token_old');
            }


            // Move Config Settings Over
            // Gather settings...
            // gaccess has duplicate newwinlink field so we leave it out  and go with the gdata one
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
            events_uninstall('block/gaccess');
            events_uninstall('block/gdata');
            events_uninstall('block/gmail');

            // Delete Old Caps
            capabilities_cleanup('block/gaccess');
            capabilities_cleanup('block/gdata');
            capabilities_cleanup('block/gmail');

            // Delete old settings
            $DB->delete_records('config_plugins',array('plugin'=>'blocks/gaccess'));
            $DB->delete_records('config_plugins',array('plugin'=>'blocks/gdata'));
            $DB->delete_records('config_plugins',array('plugin'=>'blocks/gmail'));
                        
        }

        return parent::_self_test();
     }
     
    /**
     * Clean up the configs upon deletion of the block
     */
    function before_delete() {
        global $DB;
        $DB->delete_records('config_plugins',array('plugin'=>'blocks/gapps'));
    }

    /**
     * This block can be added to Site, Course, or My Moodle
     * Capabilities determine whether a user an see the tab or not.
     * 
     * @return array settings
     */
    function applicable_formats() {
        return array('all' => true, 'mod' => false, 'my' => true);
    }

    /**
     * Called Statically (gsync tab required)
     *
     * Does the current user have
     * the capability to use this
     * block and its features?
     *
     * May change, so using this method
     *
     * @param boolean $required Require the capability (throws error if is user does not have)
     * @return boolean
     */
    function has_capability_for_sync($required = false) {
        if ($required) {
            require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));
        }
        return has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));
    }


    /**
     * Link to view the block
     */
    function get_content() {
        global $CFG, $USER, $COURSE, $OUTPUT, $PAGE;


        // quick and simple way to prevent block
        // from showing up on front page
        if (!isloggedin()) {
            $this->content = NULL;
            return $this->content;
        }

        if ($this->content !== NULL) {
            return $this->content;
        }


        $this->content = new stdClass;

        // using the YUI2 (2.8) tabview js
        $module = array(
            'name'      => 'block_gapps',
            'fullpath'  => '/blocks/gapps/gapps.js',
            'requires'  => array('yui2-tabview','yui2-yahoo','yui2-dom','yui2-event','yui2-element'),
            'strings'   => array(),
        );
        $PAGE->requires->js_init_call('M.block_gapps.tabs.init', null, false, $module);


        // Each Tab has to catch it's own errors since it will have to
        // display that information in it's on tab.

        // just links so doesn't gen errors doesn't need try catch
        $gapps = $this->gapps_get_content(); // Gapps Generate Content

        // Gmail Gen Content
        $gmail = '';
        try {
            $gmail = $this->gmail_get_content();
        } catch ( Exception $e) {
            $gmail = "Error: ".$e->getMessage();
        }

        // Gsync Gen Content
        $gsync = '';
        if( self::has_capability_for_sync() ) {
            $gsync = $this->gsync_get_content();
        }

        
        // form the tabs data object
        $gapps_tab_title = 'Gapps'; // could include alert icons
        $gmail_tab_title = 'Gmail';
        $gsync_tab_title = 'Gsync';

        $tabstorender = array();

        $gapps_tab = NULL;
        $gapps_tab->title = $gapps_tab_title;
        $gapps_tab->content = $gapps;
        $tabstorender[] = $gapps_tab;

        $gmail_tab = NULL;
        $gmail_tab->title = $gmail_tab_title;
        $gmail_tab->content = $gmail;
        $tabstorender[] = $gmail_tab;


        $gsync_tab = NULL;
        $gsync_tab->title = $gsync_tab_title;
        $gsync_tab->content = $gsync;
        $tabstorender[] = $gsync_tab;
        
        $blockcontent = $this->form_tabs($tabstorender,$gapps_tab_title);

        $this->content->text = $blockcontent;
        $this->content->footer = '';

        return $this->content;
    }




    /**
     * This function expects a tab structure consiting of an array of objects
     * each representing a tab.
     * If a tab's content is empty the tab is not shown.
     *
     * @param  object $tabstruct
     * @param  string $selected which tab do you want selected by default?
     * @return string HTML for the YUI to process
     */
    function form_tabs($tabstruct,$selected = 'Gapps') {
        // first remove tabs with empty content
        $temp = array();
        foreach($tabstruct as $tab) {
            if (!empty($tab->content)) {
                $temp[] = $tab;
            }
        }
        $tabstruct = $temp;

        // To support other styles class="yui-skin-sam" may need to change
        $t = '';
        $t .= '<div id="block_gapps_tabs" class="yui-skin-sam">
               <div id="demo" class="yui-navset">
               <ul class="yui-nav">';

        $j = 1;
        foreach($tabstruct as $tab) {

            if ($tab->title == $selected) {
                $t .= '<li class="selected">';
            } else {
                $t .= '<li>';
            }

            $t .= '<a href="#tab'.$j.'"><em><span style="font-size:0.8em;">';

            $t .= $tab->title;
            $t .= '</span></em></a></li>';
            $j++;
        }            
    

        $t .= '</ul>
               <div class="yui-content">';

        $i = 1;
        foreach ($tabstruct as $tab) {
            $t .= '<div id="tab'.$i.'"><p>'.$tab->content.'</p></div>';
            $i++;
        }

        $t .= '</div></div></div>';

        return $t;
    }

    /**
     * Borrowed from Block_list so I can control the block content but still pass in the list params
     *
     * Render the contents of a block_list.
     * @param array $icons the icon for each item.
     * @param array $items the content of each item.
     * @return string HTML
     */
    public function list_block_contents($icons, $items) {
        $row = 0;
        $lis = array();
        foreach ($items as $key => $string) {
            $item = html_writer::start_tag('li', array('class' => 'r' . $row));
            if (!empty($icons[$key])) { //test if the content has an assigned icon
                $item .= html_writer::tag('div', $icons[$key], array('class' => 'icon column c0'));
            }
            $item .= html_writer::tag('div', $string, array('class' => 'column c1'));
            $item .= html_writer::end_tag('li');
            $lis[] = $item;
            $row = 1 - $row; // Flip even/odd.
        }
        return html_writer::tag('ul', implode("\n", $lis), array('class' => 'unlist'));
    }

    /**
     * Gapps Service Links
     *
     * @global object $CFG
     * @global object $OUTPUT
     * @return string html
     */
    function gapps_get_content() {
        global $CFG,$OUTPUT;
        
        $icons = array();
        $items = array();

        $domain = get_config('blocks/gapps','domain');
        if( empty($domain)) {
        	$items[] = get_string('domainnotset','block_gapps');
                $icons[] = '';
    		return $this->list_block_contents($icons, $items);
    	}

        $google_services = array(

        	array(
        	        'service'   => 'Gmail',
        			'relayurl'  => 'http://mail.google.com/a/'.$domain,
        			'icon_name' => 'gmail.png'
        	),

        	array(
        	        'service'   => 'Calendar',
        			'relayurl'  => 'http://www.google.com/calendar/a/'.$domain,
        			'icon_name' => 'calendar.png'
        	),

        	array(
        	        'service'   => 'Docs',
        			'relayurl'  => 'http://docs.google.com/a/'.$domain,
        			'icon_name' => 'gdocs.png'
        	),


                array(
        	        'service'   => 'Start Page',
        			'relayurl'  => 'http://partnerpage.google.com/'.$domain,
        			'icon_name' => 'startpage.png'
        	)
        );

        $newwinlnk = get_config('blocks/gapps','newwinlink');
        if ($newwinlnk) {
            $target = 'target=\"_new\"';
        } else {
            $target = '';
        }

        foreach( $google_services as $gs ) { 
            $items[] = "<a ".$target.". title=\"".$gs['service']."\"  href=\"".$gs['relayurl']."\">".$gs['service']."</a>";

            if ( !empty($gs['icon_name']) ) {
        		$icons[] = "<img src=\"$CFG->wwwroot/blocks/gapps/imgs/".$gs['icon_name']."\" alt=\"".$gs['service']."\" />";
	        } else {
	        	// Default to a check graphic
                        // ".$OUTPUT->pix_url('/i/tick_green_small')."
	        	$icons[] = "<img src=\"$CFG->pixpath/i/tick_green_small.gif\" alt=\"$service\" />";
	        }
        }


        return $this->list_block_contents($icons, $items);
    }

    /**
     * Get Gsync Tab Content
     *
     * @global object $CFG
     * @global object $USER
     * @global object $COURSE
     * @global object $OUTPUT
     * @return string gsync html content
     */
    function gsync_get_content() {
        global $CFG, $USER, $COURSE,$OUTPUT;

        $items = array();
        $icons = array();

        $title = get_string('settings', 'block_gapps');
        $items[] = "<a title=\"$title\" href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=blocksettinggapps\">$title</a>";
        $icons[] = "<img src=\"".$OUTPUT->pix_url('/i/settings')."\" alt=\"$title\" />";

        $title = get_string('status', 'block_gapps');
        $items[] = "<a title=\"$title\" href=\"$CFG->wwwroot/blocks/gapps/view.php?controller=gsync&action=status\">$title</a>";
        $icons[] = "<img src=\"".$OUTPUT->pix_url('/i/tick_green_small')."\" alt=\"$title\" />";

        $title = get_string('userssynced', 'block_gapps');
        $items[] = "<a title=\"$title\" href=\"$CFG->wwwroot/blocks/gapps/view.php?controller=gsync&action=usersview\">$title</a>";
        $icons[] = "<img src=\"".$OUTPUT->pix_url('/i/users')."\" alt=\"$title\" />";

        $title = get_string('addusers', 'block_gapps');
        $items[] = "<a title=\"$title\" href=\"$CFG->wwwroot/blocks/gapps/view.php?controller=gsync&action=addusersview\">$title</a>";
        $icons[] = "<img src=\"".$OUTPUT->pix_url('/i/users')."\" alt=\"$title\" />";

        return $this->list_block_contents($icons, $items);
    }

    /**
     * Get html content for the gmail tab
     *
     * @global object $CFG
     * @return string html content for tab
     */
    function gmail_get_content() {
        global $CFG;
        
        $items = array();
        $icons = array();

        require_once($CFG->dirroot.'/blocks/gapps/model/gmail.php');

        $gmail = new blocks_gapps_model_gmail();

        list($icons,$items) = $gmail->get_content();

        return $this->list_block_contents($icons, $items);
    }



    /**
     * run crons from all components that need to run crons...
     *
     * @return boolean
     */
    function cron() {
        global $CFG;
        mtrace("");
        $status = true;
        
        // Run crons...
        
        // gsync cron

        mtrace("gsync: Cron Running....");
        require_once($CFG->dirroot.'/local/mr/framework/bootstrap.php');
        require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');
        $gapps = new blocks_gapps_model_gsync();
        $status = $gapps->cron();
        mtrace("gsync: Cron Complete.");

        mtrace("blocks/gapps Cron Complete.");
        
        return $status;
    }


}

?>