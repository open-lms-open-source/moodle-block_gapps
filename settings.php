<?php
/**
 * Global Settings Example
 *
 * @author Mark Nielsen
 * @version $Id$
 * @package blocks/helloworld
 **/

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

$configs   = array();
$configs[] = new admin_setting_configtext('textexample', get_string('textexample', 'block_helloworld'), get_string('textexampledesc', 'block_helloworld'), 'Hello World!');

// Define the config plugin so it is saved to
// the config_plugin table then add to the settings page
foreach ($configs as $config) {
    $config->plugin = 'blocks/helloworld';
    $settings->add($config);
}