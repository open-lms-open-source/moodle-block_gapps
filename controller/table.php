<?php
/**
 * Table controller
 *
 * @author Mark Nielsen
 * @version $Id$
 * @package blocks/helloworld
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class block_helloworld_controller_table extends mr_controller_block {
    /**
     * Default screen
     *
     * Demo of mr_html_table, mr_html_paging, mr_file_export and mr_preferences
     */
    public function view_action() {
        global $DB, $COURSE;

        #### DEMO CODE ####
        // Table stores sorting info in mr_preferences
        // Paging store perpage info in mr_preferences
        $preferences = new mr_preferences($COURSE->id, 'blocks/helloworld');

        // Setup the paging bar
        $paging = new mr_html_paging($preferences, $this->url);
        $paging->set_total($DB->count_records('user'));
        $paging->set_perpageopts(array('all', 1, 5, 50, 100));

        // Setup a new table
        $table = new mr_html_table($preferences, $this->url, 'username');

        // Add columns and column formatting
        $table->add_column('username', get_string('username'))
              ->add_column('firstname', get_string('firstname'))
              ->add_column('lastname', get_string('lastname'))
              ->add_column('email', get_string('email'))
              ->add_column('lastaccess', get_string('lastaccess'))
              ->add_format('lastaccess', 'date')
              ->add_format(array('username', 'firstname', 'lastname', 'email'), 'string');

        // Export handler - autodetects if we are exporting
        $export = new mr_file_export('**', false, $this->url);

        // When exporting, sets the limitfrom and limitnum appropriately
        $paging->set_export($export);

        // When exporting, send column headers to the export
        // And then route all rows to the export as well
        $table->set_export($export);

        // Fetch rows to add to the table
        $rows = $DB->get_records('user', NULL, $table->get_sql_sort(), $table->get_sql_select(),
                                 $paging->get_limitfrom(), $paging->get_limitnum());

        foreach ($rows as $row) {
            $table->add_row($row);
        }
        // If exporting, sends file, if not, does nothing
        $export->send();

        // Render the result
        $output  = $this->mroutput->render($paging);
        $output .= $this->mroutput->render($table);
        $output .= $this->mroutput->render($paging);
        $output .= $this->mroutput->render($export);
        #### DEMO CODE ####

        return $this->output->heading('Demo of mr_table_*').
               $this->helper->highlight(__CLASS__, __FUNCTION__, true).
               $this->output->box($output, 'generalbox boxaligncenter boxwidthnormal');
    }
}