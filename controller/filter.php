<?php
/**
 * Filter controller
 *
 * @author Mark Nielsen
 * @version $Id$
 * @package blocks/helloworld
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class block_helloworld_controller_filter extends mr_controller_block {
    /**
     * Default screen
     *
     * Demo of mr_filter et al
     */
    public function view_action() {
        global $COURSE;

        #### DEMO CODE ####
        // Filter values are stored here
        $preferences = new mr_preferences($COURSE->id, 'blocks/helloworld');

        // Create the filter "manager"
        $filter = new mr_html_filter($preferences, $this->url);

        // Add a filter
        $filter->add(new mr_html_filter_text('foo', 'Foo'));

        // Add the same filter, shortcut way
        $filter->new_text('bar', 'Bar');

        // You can chain these too (with add() as well)
        $filter->new_select('baz', 'Baz', array('0' => 'No', '1' => 'Yes'))
               ->new_daterange('bat', 'Bat');

        // Render the filter: This should be done before the print_header() - may redirect
        $output = $this->mroutput->render($filter);

        // Generate SQL based on filter values
        $output .= $this->helper->dump($filter->sql(), 'Filter SQL', true);

        // Access filter values through preferences
        $output .= $this->helper->dump($preferences->get('foo', 'NOT SET'), 'Preference foo', true);
        $output .= $this->helper->dump($preferences->get('bar', 'NOT SET'), 'Preference bar', true);
        #### DEMO CODE ####

        return $this->output->heading('Demo of mr_filter_*').
               $this->helper->highlight(__CLASS__, __FUNCTION__, true).
               $this->output->box($output, 'generalbox boxaligncenter boxwidthnormal');
    }
}