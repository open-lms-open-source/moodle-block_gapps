<?php
/**
 * Plugin controller
 *
 * @author Mark Nielsen
 * @version $Id$
 * @package blocks/helloworld
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class block_helloworld_controller_plugin extends mr_controller_block {
    /**
     * Default screen
     *
     * Demo of plugins
     */
    public function view_action() {
        $this->print_header();

        echo $this->output->heading('Demo of mr_plugin and mr_helper_load');
        $this->helper->highlight(__CLASS__, __FUNCTION__);
        echo $this->output->box_start('generalbox boxaligncenter boxwidthnormal');

        #### DEMO CODE ####
        // Load all top-level plugins
        $result = $this->helper->load->plugin();
        $this->helper->dump($result, 'load->plugin() result');

        // Load plugin 'one'
        $result = $this->helper->load->plugin('one');
        $this->helper->dump($result, 'load->plugin(\'one\') result');

        // Inside of blocks/helloworld/plugin/one are more plugins which
        // resemble the Multiple plugin type layout.

        // All sub-plugins of plugin one
        $result = $this->helper->load->plugin('one/*');
        $this->helper->dump($result, 'load->plugin(\'one/*\') result');

        // Load plugin 'one/aa'
        $result = $this->helper->load->plugin('one/aa');
        $this->helper->dump($result, 'load->plugin(\'one/aa\') result');

        echo $this->output->heading('Example call to plugin methods:');

        foreach ($this->helper->load->plugin() as $name => $plugin) {
            echo $this->output->heading("For plugin $name:");
            print_object('name(): '.$plugin->name());
            print_object('type(): '.$plugin->type());
            print_object('parent_method(): '.$plugin->parent_method());
            print_object('abstract_method(): '.$plugin->abstract_method());
        }
        #### DEMO CODE ####

        echo $this->output->box_end();
        $this->print_footer();
    }
}