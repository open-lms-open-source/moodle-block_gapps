<?php
/**
 * Base plugin class for plugin type one
 *
 * @author Mark Nielsen
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

require($CFG->dirroot.'/local/mr/bootstrap.php');

abstract class block_helloworld_plugin_one_base_class extends mr_plugin {
    /**
     * Implement abstract method of mr_plugin
     */
    public function get_component() {
        return 'block_helloworld';
    }
}