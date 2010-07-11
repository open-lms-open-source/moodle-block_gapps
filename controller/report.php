<?php
/**
 * Report controller
 *
 * @author Mark Nielsen
 * @version $Id$
 * @package blocks/helloworld
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class block_helloworld_controller_report extends mr_controller_block {
    /**
     * Default screen
     */
    public function view_action() {
        global $CFG, $COURSE;

        #### DEMO CODE ####
        require_once($CFG->dirroot.'/blocks/helloworld/report/users.php');

        $report = new blocks_helloworld_report_users($this->url, $COURSE->id);
        $output = $this->mroutput->render($report);

        // Alternative syntax...
        // $output = $this->mroutput->render(
        //     new blocks_helloworld_report_users($this->url, $COURSE->id)
        // );
        #### DEMO CODE ####

        return $this->output->heading('Demo of mr_report_abstract').
               $this->helper->highlight(__CLASS__, __FUNCTION__, true).
               $output;
    }
}