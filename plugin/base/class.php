<?php
/**
 * Base plugin class
 *
 * @author Mark Nielsen
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

require($CFG->dirroot.'/local/mr/bootstrap.php');

abstract class block_helloworld_plugin_base_class extends mr_plugin {
    /**
     * Helper
     *
     * @var mr_helper
     */
    protected $helper;

    /**
     * Commonly you want to make helper readily available
     */
    public function __construct() {
        $this->helper = new mr_helper('blocks/helloworld');
    }

    /**
     * Implement abstract method of mr_plugin
     */
    public function get_component() {
        return 'block_helloworld';
    }

    /**
     * Custom abstract method
     *
     * @return string
     */
    abstract public function abstract_method();

    /**
     * Parent method
     *
     * @return string
     */
    public function parent_method() {
        return 'Parent method called';
    }
}