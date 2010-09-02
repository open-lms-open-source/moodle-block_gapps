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
 * gsync View and actions
 *
 * @author Chris Stones
 * @version $Id$
 * @package block_gapps
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class block_gapps_controller_gsync extends mr_controller_block {


    /**
     * Require capability for viewing this controller
     */
    public function require_capability() {
        switch ($this->action) {
            case 'status':
            case 'usersview':
            case 'addusersview':
            case 'viewdiagnostics':
            default:
                require_capability('moodle/site:config', $this->get_context()); // Only admins can see gsync
        }
    }


    /**
     * Default View (so we don't crash if there is an error
     */
    public function view_action() {
        global $OUTPUT;
        $this->tabs->set('status');
        $this->print_header();

        print $this->output->heading('Gsync Default View');
        print $OUTPUT->notification("GSync Default View");
        print $this->output->box_start('generalbox boxaligncenter boxwidthnormal');

        print $this->output->box_end();
        $this->print_footer();
    }

    /**
     * Checks that blocks_gapps_model_gsync() can properly connect to Google Apps
     *
     * @global object $CFG
     * @global object $OUTPUT
     */
    public function status_action() {
        global $CFG,$OUTPUT;
        
        $this->tabs->set('status');
        $this->print_header();

        require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');


        try {
            $gapps = new blocks_gapps_model_gsync();
            print $OUTPUT->notification(get_string('connectionsuccess','block_gapps'),'notifysuccess');
        } catch (blocks_gdata_exception $e) {
            $a = NULL;
            $a->msg = $e->getMessage();
            print $OUTPUT->notification(get_string('gappsconnectiontestfailed','block_gapps',$a));
        }
       
        $this->print_footer();

    }

    /**
     * Displays users currently in the gsync table
     *
     * @global object $COURSE
     * @global object $CFG
     */
    public function usersview_action() {
        global $COURSE, $CFG;

        $this->tabs->set('users');
        $this->print_header();

        require_once($CFG->dirroot.'/blocks/gapps/report/users.php');
        require_once($CFG->dirroot.'/user/filters/lib.php');

        // don't auto run because moodle's userfilter clears the _POST global and we need to save
        // and process those
        $report = new blocks_gapps_report_users($this->url, $COURSE->id,false);
             

        $filter =  new user_filtering(NULL, $this->url);//  array('hook' => $hook, 'pagesize' => $pagesize));
                                    
        mr_var::instance()->set('blocks_gdata_filter', $filter);
        $report->run();
        $output = $this->mroutput->render($report);
        print $output;
        
        $this->print_footer();
    }

    /**
     * Performs the remove selected users actions.
     *
     * @global object $CFG
     * @global object $SESSION
     * @global object $DB
     */
    public function users_action() {
        global $CFG,$SESSION,$DB;
        $this->tabs->set('users');
        $operationstatus = true;
        require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');

        if ($userids = optional_param('userids', 0, PARAM_INT) or optional_param('allusers', '', PARAM_RAW)) {
            if (!confirm_sesskey()) {
                throw new blocks_gdata_exception('confirmsesskeybad', 'error');
            }
            $gapps = new blocks_gapps_model_gsync();

            if (optional_param('allusers', '', PARAM_RAW)) {
                // Obtain sql from the stored filter
                if (isset($SESSION->blocks_gapps_report_users->fsql)) {
                    $fsql = $SESSION->blocks_gapps_report_users->fsql;
                    $fparams = $SESSION->blocks_gapps_report_users->fparams;
                } else {
                    throw new blocks_gdata_exception('missingfiltersql');
                }

                // Bulk processing
                if ($rs = $DB->get_recordset_sql($fsql,$fparams)) {
                    while ($rs->valid()) {
                        $user = $rs->current();
                        $gapps->moodle_remove_user($user->id);
                        $rs->next();
                        add_to_log(SITEID, 'block_gapps', 'gsync:usr_act Bulk mdl remove_user','', 'userid='.$user->id, 0,0);
                    }
                    $rs->close();
                } else {
                    throw new blocks_gdata_exception('invalidparameter');
                }

            } else {
                // Handle ID submit
                foreach ($userids as $userid) {
                    $gapps->moodle_remove_user($userid);
                    add_to_log(SITEID, 'block_gapps', 'gsync:urs_act m_remove_user','', 'userid='.$userid, 0,0);
                }
            }
        }

        $operationstatus and $this->notify->good('changessaved','block_gapps');
        $actionurl = $CFG->wwwroot.'/blocks/gapps/view.php?controller=gsync&action=usersview';//.$COURSE->id;
        redirect($actionurl);
    }

    /**
     * Creates users in the gsync table so that they can sync next time cron runs.
     *
     * @global object $CFG
     * @global object $COURSE
     * @global object $DB
     * @global object $SESSION
     */
    public function addusers_action() {
        global $CFG,$COURSE,$DB,$SESSION;
        $this->tabs->set('addusers');
        $operationstatus = true;

        require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');

        if ($userids = optional_param('userids', 0, PARAM_INT) or optional_param('allusers', '', PARAM_RAW)) {
            if (!confirm_sesskey()) {
                throw new blocks_gdata_exception('confirmsesskeybad', 'error');
            }

            $gapps = new blocks_gapps_model_gsync();

            if (optional_param('allusers', '', PARAM_RAW)) { 
                // Obtain sql from the stored filter
                if (isset($SESSION->blocks_gapps_report_addusers->fsql)) {
                    $fsql = $SESSION->blocks_gapps_report_addusers->fsql;
                    $fparams = $SESSION->blocks_gapps_report_addusers->fparams;
                } else {
                    throw new blocks_gdata_exception('missingfiltersql');
                }
                
                // Bulk processing
                if ($rs = $DB->get_recordset_sql($fsql,$fparams)) {
                    while ($rs->valid()) {
                        $user = $rs->current();
                        // Had to pull in the full user object here. Thus this DB line is diff from original code
                        // because filter didn't have password field
                        $user = $DB->get_record('user',array('id'=>$user->id));

                        $gapps->moodle_create_user($user);
                        $rs->next();

                        add_to_log(SITEID, 'block_gapps', 'gsync:addursBulk create_usr','', 'usr='.$user->id, 0,0);
                    }
                    $rs->close();
                } else {
                    throw new blocks_gdata_exception('invalidparameter');
                }

            } else { 
                // Process selected user IDs
                foreach ($userids as $userid) {
                    // return a user object with only id,username and password
                    if ($user = $DB->get_record('user', array('id'=> $userid), 'id, username, password')) {
                        $gapps->moodle_create_user($user);
                        add_to_log(SITEID, 'block_gapps', 'gsync:addusrs_act m_create_user','', 'suc usr='.$user->id, 0,0);
                    } else {
                        throw new blocks_gdata_exception('invalidparameter');
                    }
                }
            }
        }


        $operationstatus and $this->notify->good('changessaved','block_gapps');
        $actionurl = $CFG->wwwroot.'/blocks/gapps/view.php?controller=gsync&action=addusersview';//.$COURSE->id;
        redirect($actionurl);
    }

    /**
     * Displays the users an admin can add to the gsync table.
     *
     * @global object $CFG
     * @global object $COURSE
     */
    public function addusersview_action() {
        global $CFG,$COURSE;
        $this->tabs->set('addusers');
        $this->print_header();

        require_once($CFG->dirroot.'/blocks/gapps/report/addusers.php');
        require_once($CFG->dirroot.'/user/filters/lib.php');

        // don't auto run because moodle's userfilter clears the _POST global and we need to save
        // and process those
        $report = new blocks_gapps_report_addusers($this->url, $COURSE->id,false);

        $filter =  new user_filtering(NULL, $this->url);

        mr_var::instance()->set('blocks_gdata_filter', $filter);
        $report->run();
        $output = $this->mroutput->render($report);
        print $output;
        $this->print_footer();
    }
    

    /**
     * Testing Interface (to call model/diagnostic.php  you can bring up the dev docs in another tab
     */
    public function viewdiagnostics_action() {
        global $CFG,$COURSE,$OUTPUT,$DB;
        $this->tabs->set('diagnostic');
        $this->print_header();

        $gname = optional_param('gappsname','',PARAM_TEXT);
        require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');
        $gapps = new blocks_gapps_model_gsync();

        $out = '';
        echo $this->output->heading('Gapps User Data');

        print $OUTPUT->box_start('generalbox boxaligncenter boxwidthnormal');

        print "<pre>";
        if (!empty($gname) ) {
            try {
                // http://code.google.com/googleapps/domain/gdata_provisioning_api_v2.0_reference.html
                $guser = $gapps->gapps_get_user($gname); // Zend_Gdata_Gapps_UserEntry

                // collapse
                // should be able to run all the gapps methods that don't set things
                // can't delete a user since google waits 5 days before it really does
                if (empty($guser)) {
                    print "guser object is empty";
                } else {


                    $name  = $guser->getName();   // Zend_Gdata_Gapps_Extension_Name getName ()
                    //
                    print "GivenName: ".$name->getGivenName();
                    print "<br>";
                    print "FamilyName: ".$name->getFamilyName();
                    print "<br>";

                    // Login Informatino
                    $login = $guser->getLogin(); // Zend_Gdata_Gapps_Extension_Login

                    print ($login->getAdmin()) ? "Is Admin" : "not Admin";
                    print "<br>";

                    print "gapps password: ".$login->getPassword();
                    print "<br>";

                    print ($login->getSuspended()) ? "Is Suspended":"Not suspended" ;
                    print "<br>";

                    print $login->getUsername();

                }

                // Data moodle has about this user
                $userid = $DB->get_field('user','id',array('username'=> $gname));
                print "Moodle Data for the user<br>";
                print_object($gapps->moodle_get_user($userid));


            } catch (blocks_gdata_exception $e) {
                print $e->getMessage();
            }

        } else {
            print "prints out info google has on a user when username is given in post";
        }
        print "</pre>";
        
        print $this->gapps_get_user_form();
 
        print $OUTPUT->box_end();
        $this->print_footer();
    }

    /**
     * Debugging form for Retriving Gapps Userdata
     *
     * @global object $CFG
     * @return string
     */
    public function gapps_get_user_form() {
        global $CFG;
        $output = '<br />';
        $action = $CFG->wwwroot.'/blocks/gapps/view.php?controller=gsync&action=viewdiagnostics';
        $output .= "<form class=\"userform\"  action=\"$action\" method=\"post\">";
        $output .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $output .= '<input type="text" name="gappsname" size="30" />';
        $output .= '<input type="submit" value="Retrive Gapps Userdata" />';
        $output .= '</form><br />';
        return $output;
    }

    /**
     * Force GSync cron to run
     *
     * @global object $CFG
     * @global object $DB
     * @global object $OUTPUT
     */
    public function runcron_action() {
        global $CFG,$DB,$OUTPUT;

        $this->tabs->set('diagnostic');
        $this->print_header();

        // now set up and run the gapps cron
        require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');
        $gapps = new blocks_gapps_model_gsync();

        print $OUTPUT->box_start('generalbox boxaligncenter boxwidthnormal');
        print "<pre>";
        $gapps->cron(true); // force run option to true
        print "</pre>";
        print $OUTPUT->box_end();


        $this->print_footer();
        add_to_log(SITEID, 'block_gapps', 'gsync:runcron_act','', '', 0,0);
    }

    /**
     * Force Sync a Given User
     *
     * @global object $CFG
     * @global object $CFG
     * @global object $COURSE
     * @global object $OUTPUT
     * @global object $DB
     */
    public function syncuser_action() {
        global $CFG;

        global $CFG,$COURSE,$OUTPUT,$DB;
        $this->tabs->set('diagnostic');
        $this->print_header();


        $gname = optional_param('gappsname','',PARAM_TEXT);
        print $this->output->heading('Sync a user Directly');
        // if username given run the sync on that name and display results otherwise display form
        print $OUTPUT->box_start('generalbox boxaligncenter boxwidthnormal');
        if (!empty($gname)) {
            print "<pre>";
            require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');
            $gapps = new blocks_gapps_model_gsync(); /// the $gapps makes the code easier to read so leaving as gapps and not $this

            $userid = $DB->get_field('user','id',array('username'=> $gname));
            print "userid: $userid <br>";

            $moodleuser = $gapps->moodle_get_user($userid);

            print "moodleuser data object: <br>";
            print_object($moodleuser);

            print "<hr>";
            $gapps->sync_moodle_user_to_gapps($moodleuser);
            
            print "</pre>";

        } else {
          print $this->gapps_get_syncuser_form();
        }
        // Provides a form to enter a user to preform the sync directly
        
        print $OUTPUT->box_end();

        $this->print_footer();
    }

    /**
     * Force Sync User Form HTML
     *
     * @global object $CFG
     * @return string html for the form
     */
    public function gapps_get_syncuser_form() {
        global $CFG;
        $output = '<br />';
        $action = $CFG->wwwroot.'/blocks/gapps/view.php?controller=gsync&action=syncuser';
        $output .= "<form class=\"userform\"  action=\"$action\" method=\"post\">";
        $output .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $output .= '<input type="text" name="gappsname" size="30" />';
        $output .= '<input type="submit" value="Run Sync on this User" />';
        $output .= '</form><br />';
        return $output;
    }


    /**
     * View this PHPDoc Generated Content.
     *
     * @global object $CFG
     * @global object $COURSE
     * @global object $OUTPUT
     */
    public function viewdocs_action() {
        global $CFG,$COURSE,$OUTPUT;
        $this->tabs->set('diagnostic');
        $this->print_header();

        print $this->output->heading("Gapps Documentation");
        print $OUTPUT->box_start('generalbox boxaligncenter');
        $str = '<iframe src="'.$CFG->wwwroot.'/blocks/gapps/docs/index.html'.'" width="100%" height="600" align="center"> </iframe>';
        print $str;
        print $OUTPUT->box_end();
        
        
        $this->print_footer();
    }

    /**
     * View only Gapps related Moodle logs
     *
     * @global object $CFG
     * @global object $COURSE
     * @global object $OUTPUT
     */
    public function gappslogs_action() {
        global $CFG,$COURSE,$OUTPUT;
        $this->tabs->set('diagnostic');

        require_once($CFG->dirroot.'/blocks/gapps/report/gappslogs.php');
        $report = new blocks_gapps_report_gappslogs($this->url, $COURSE->id);

        $this->print_header();
        print $this->mroutput->render($report);
        $this->print_footer();
    }
}