<?php
/**
 * gsync View and actions
 *
 * @author Chris Stones
 * @version $Id$
 * @package blocks/gapps
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class block_gapps_controller_gsync extends mr_controller_block {
    /**
     * Default screen
     *
     * Demo of plugins
     */
    public function view_action() {
        // $this->set_headerparams('tab', 'status','title','Status');

        $this->print_header();

        echo $this->output->heading('Demo of gsync controller');
       
        echo "gsync test";
        
        echo $this->output->box_start('generalbox boxaligncenter boxwidthnormal');

        echo $this->output->box_end();
        $this->print_footer();
    }

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
        
        // Output Details on the Status Connection
        // useful for debugging
       
        $this->print_footer();

    }

    public function usersview_action() {
        global $COURSE, $CFG;

        $this->tabs->set('users');
        $this->print_header();

        require_once($CFG->dirroot.'/blocks/gapps/report/users.php');
        require_once($CFG->dirroot.'/user/filters/lib.php');

        // don't auto run because moodle's userfilter clears the _POST global and we need to save
        // and process those
        $report = new blocks_gapps_report_users($this->url, $COURSE->id,false);
             

        $filter =  new user_filtering(NULL, $this->url);//,
                                    //  array('hook' => $hook, 'pagesize' => $pagesize));

        mr_var::instance()->set('blocks_gdata_filter', $filter);
        $report->run();
        $output = $this->mroutput->render($report);
        print $output;
        
        $this->print_footer();
    }

    public function users_action() {
        global $CFG;
        $this->tabs->set('users');
        $operationstatus = true;
        require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');

        //print_object($_POST);


        if ($userids = optional_param('userids', 0, PARAM_INT) or optional_param('allusers', '', PARAM_RAW)) {
            if (!confirm_sesskey()) {
                throw new blocks_gdata_exception('confirmsesskeybad', 'error');
            }
            $gapps = new blocks_gapps_model_gsync(false);

            if (optional_param('allusers', '', PARAM_RAW)) {
                $this->notify->bad('notimplementedyet','block_gapps');
                $operationstatus = false;
//                list($select, $from, $where) = $this->get_sql('users');
//
//                // Bulk processing
//                if ($rs = get_recordset_sql("$select $from $where")) {
//                    while ($user = rs_fetch_next_record($rs)) {
//                        $gapps->moodle_remove_user($user->id);
//                    }
//                    rs_close($rs);
//                } else {
//                    throw new blocks_gdata_exception('invalidparameter');
//                }
            } else {
                // Handle ID submit
                foreach ($userids as $userid) {
                    $gapps->moodle_remove_user($userid);
                }
            }
          //  redirect($CFG->wwwroot.'/blocks/gdata/index.php?hook=users');
        }

        $operationstatus and $this->notify->good('changessaved','block_gapps');
        $actionurl = $CFG->wwwroot.'/blocks/gapps/view.php?controller=gsync&action=usersview';//.$COURSE->id;
        redirect($actionurl);



        // CODE TO CONVERT
        /*
        require_once($CFG->dirroot.'/blocks/gdata/gapps.php');

        if ($userids = optional_param('userids', 0, PARAM_INT) or optional_param('allusers', '', PARAM_RAW)) {
            if (!confirm_sesskey()) {
                throw new blocks_gdata_exception('confirmsesskeybad', 'error');
            }
            $gapps = new blocks_gdata_gapps(false);

            if (optional_param('allusers', '', PARAM_RAW)) {
                list($select, $from, $where) = $this->get_sql('users');

                // Bulk processing
                if ($rs = get_recordset_sql("$select $from $where")) {
                    while ($user = rs_fetch_next_record($rs)) {
                        $gapps->moodle_remove_user($user->id);
                    }
                    rs_close($rs);
                } else {
                    throw new blocks_gdata_exception('invalidparameter');
                }
            } else {
                // Handle ID submit
                foreach ($userids as $userid) {
                    $gapps->moodle_remove_user($userid);
                }
            }
            redirect($CFG->wwwroot.'/blocks/gdata/index.php?hook=users');
        }
        */

    }

    public function addusers_action() {
        global $CFG,$COURSE,$DB;
        $this->tabs->set('addusers');
        $operationstatus = true;

        require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');

        if ($userids = optional_param('userids', 0, PARAM_INT) or optional_param('allusers', '', PARAM_RAW)) {
            if (!confirm_sesskey()) {
                throw new blocks_gdata_exception('confirmsesskeybad', 'error');
            }

            $gapps = new blocks_gapps_model_gsync(false);


            if (optional_param('allusers', '', PARAM_RAW)) { 
                //// process ALL usersides on that page
                $this->notify->bad('notimplementedyet','block_gapps');
                $operationstatus = false;
                //throw new blocks_gdata_exception('notimplementedyet');
//                list($select, $from, $where) = $this->get_sql('addusers'); // need to CONVERT
//
//                // Bulk processing
//                if ($rs = get_recordset_sql("$select $from $where")) {
//                    while ($user = rs_fetch_next_record($rs)) {
//                        $gapps->moodle_create_user($user);
//                    }
//                    rs_close($rs);
//                } else {
//                    throw new blocks_gdata_exception('invalidparameter');
//                }


            } else { 
                // Process selected user IDs
                foreach ($userids as $userid) {
                    // return a user object with only id,username and password
                    if ($user = $DB->get_record('user', array('id'=> $userid), 'id, username, password')) {
                        $gapps->moodle_create_user($user);
                    } else {
                        throw new blocks_gdata_exception('invalidparameter');
                    }
                }
        }
        }


        $operationstatus and $this->notify->good('changessaved','block_gapps');
        $actionurl = $CFG->wwwroot.'/blocks/gapps/view.php?controller=gsync&action=addusersview';//.$COURSE->id;
        redirect($actionurl);

        
        //// CODE to CONVERT
        /*
          global $CFG;

        require_once($CFG->dirroot.'/blocks/gdata/gapps.php');

        if ($userids = optional_param('userids', 0, PARAM_INT) or optional_param('allusers', '', PARAM_RAW)) {
            if (!confirm_sesskey()) {
                throw new blocks_gdata_exception('confirmsesskeybad', 'error');
            }
            $gapps = new blocks_gdata_gapps(false);

            if (optional_param('allusers', '', PARAM_RAW)) {
                list($select, $from, $where) = $this->get_sql('addusers');

                // Bulk processing
                if ($rs = get_recordset_sql("$select $from $where")) {
                    while ($user = rs_fetch_next_record($rs)) {
                        $gapps->moodle_create_user($user);
                    }
                    rs_close($rs);
                } else {
                    throw new blocks_gdata_exception('invalidparameter');
                }
            } else {
                // Process user IDs
                foreach ($userids as $userid) {
                    if ($user = get_record('user', 'id', $userid, '', '', '', '', 'id, username, password')) {
                        $gapps->moodle_create_user($user);
                    } else {
                        throw new blocks_gdata_exception('invalidparameter');
                    }
                }
            }
            redirect($CFG->wwwroot.'/blocks/gdata/index.php?hook=addusers');
        }
        */


        //$this->print_footer();
    }


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
    
    
    
}