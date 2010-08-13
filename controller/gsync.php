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
        
        // $this->helper->gapps(); // init Gapps helpder (pulls in Zend libs etc.
        echo "gsync test";
        
        echo $this->output->box_start('generalbox boxaligncenter boxwidthnormal');




        echo $this->output->box_end();
        $this->print_footer();
    }

    public function status_action() {
        global $CFG;
        
        $this->tabs->set('status');
        $this->print_header();

       // require_once($CFG->dirroot.'/blocks/gdata/gapps.php');

        try {
            //$gapps = new blocks_gdata_gapps();
            $this->helper->gapps();

            $this->notify->good('connectionsuccess',NULL, 'block_gapps');
        } catch (blocks_gdata_exception $e) {
            $a = NULL;
            $a->msg = $e->getMessage();
            $this->notify->bad('gappsconnectiontestfailed',$a);
        }
        
        // Output Details on the Status Connection
        // useful for debugging
        


        $this->print_footer();

    }

    public function usersview_action() {
     //       function users_display() {
       // return $this->display_user_table('users');
   // }
        $this->tabs->set('users');
        $this->print_header();

        $this->helper->gapps->display_user_table('users');
        
        $this->print_footer();
    }

    public function users_action() {

        $this->tabs->set('users');
        $this->print_header();

                global $CFG;

//        require_once($CFG->dirroot.'/blocks/gdata/gapps.php');
//
//        if ($userids = optional_param('userids', 0, PARAM_INT) or optional_param('allusers', '', PARAM_RAW)) {
//            if (!confirm_sesskey()) {
//                throw new blocks_gdata_exception('confirmsesskeybad', 'error');
//            }
//            $gapps = new blocks_gdata_gapps(false);
//
//            if (optional_param('allusers', '', PARAM_RAW)) {
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
//            } else {
//                // Handle ID submit
//                foreach ($userids as $userid) {
//                    $gapps->moodle_remove_user($userid);
//                }
//            }
//            redirect($CFG->wwwroot.'/blocks/gdata/index.php?hook=users');
//        }
//
//


        $this->print_footer();
    }

    public function addusers_action() {
        $this->tabs->set('addusers');
        $this->print_header();
        echo "addusers action";
        $this->print_footer();
    }
    
    
    
    
}