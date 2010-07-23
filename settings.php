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
$configs[] = new admin_setting_configtext('textexample', get_string('textexample', 'block_gapps'), get_string('textexampledesc', 'block_gapps'), 'Gapps Hello World!');

// Define the config plugin so it is saved to
// the config_plugin table then add to the settings page
foreach ($configs as $config) {
    $config->plugin = 'blocks/gapps';
    $settings->add($config);
}