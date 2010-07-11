<?php
/**
 * Plugin: One
 *
 * @author Mark Nielsen
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/blocks/helloworld/plugin/base/class.php');

class block_helloworld_plugin_one_class extends block_helloworld_plugin_base_class {
    public function abstract_method() {
        return 'block_helloworld_plugin_one_class';
    }
}