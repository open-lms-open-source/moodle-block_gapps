<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin settings
 *
 * @package   block_gapps
 * @copyright Copyright (c) 2015 Blackboard Inc. (http://www.blackboardopenlms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!$ADMIN->fulltree) {
    return;
}

$configs   = array();
$configs[] = new admin_setting_configtext('domain', new lang_string('domainsetting', 'block_gapps'),
    new lang_string('domainsettingdesc', 'block_gapps'), '', PARAM_TEXT, 30);
$configs[] = new admin_setting_configtext('clientid', new lang_string('clientid', 'block_gapps'),
    new lang_string('clientiddesc', 'block_gapps'), '', PARAM_TEXT, 30);
$configs[] = new admin_setting_configcheckbox('newwinlink', new lang_string('newwinlink', 'block_gapps'),
    new lang_string('newwinlinkinfo', 'block_gapps'), '1');
$configs[] = new admin_setting_configtext('msgnumber', new lang_string('msgnumberunread', 'block_gapps'),
    new lang_string('msgnumberunreadinfo', 'block_gapps'), '10', PARAM_INT, 5);
$configs[] = new admin_setting_configcheckbox('showfirstname', new lang_string('showfirstname', 'block_gapps'),
    new lang_string('showfirstnameinfo', 'block_gapps'), '1');
$configs[] = new admin_setting_configcheckbox('showlastname', new lang_string('showlastname', 'block_gapps'),
    new lang_string('showlastnameinfo', 'block_gapps'), '1');

foreach ($configs as $config) {
    // TODO: At a later date, remove this and use "block_gapps/" prefix in setting names.
    $config->plugin = 'blocks/gapps';
    $settings->add($config);
}
