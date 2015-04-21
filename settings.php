<?php
/**
 * Copyright (C) 2009  Moodlerooms Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @copyright  Copyright (c) 2009 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://opensource.org/licenses/gpl-3.0.html     GNU Public License
 */
 
/**
 * GApps Settings (gapps,gmail,gsync)
 * 
 * http://docs.moodle.org/en/Development:Admin_settings#External_pages
 * @author Chris Stones
 * @version $Id$
 * @package block_gapps
 **/

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

if (!$ADMIN->fulltree) {
    return;
}
//global $OUTPUT;

$configs   = array();

/********************************************************
 *                      GApps Settings                  *
 ********************************************************/
$configs[] = new admin_setting_heading('gapps', "Gapps Settings", "");


$configs[] = new admin_setting_configcheckbox('newwinlink', "New Window Links",
                                              "If selected links will open in new window.", '1');

/********************************************************
 *                      Gmail settings                  *
 ********************************************************/
$hbutton = '';//$OUTPUT->old_help_icon('gmailhelp', get_string('gmailhelp','block_gapps'),'block_gapps');
$configs[] = new admin_setting_heading('gmail', "Gmail Settings ".$hbutton, "");


$configs[] = new admin_setting_configpasswordunmask('oauthsecret', new lang_string('oauthsecretstr', 'block_gapps'), new lang_string('oauthsecretinfo', 'block_gapps'), '');

$configs[] = new admin_setting_configtext('msgnumber', new lang_string('msgnumberunread', 'block_gapps'), new lang_string('msgnumberunreadinfo', 'block_gapps'), '0', PARAM_RAW, 5);

// Open links in new window
$configs[] = new admin_setting_configcheckbox('newwinlink', new lang_string('newwinlink', 'block_gapps'), new lang_string('newwinlinkinfo', 'block_gapps'), '1');

// Choose to Show First and Last Names to Save Space
$configs[] = new admin_setting_configcheckbox('showfirstname', new lang_string('showfirstname', 'block_gapps'), new lang_string('showfirstnameinfo', 'block_gapps'), '1');

$configs[] = new admin_setting_configcheckbox('showlastname', new lang_string('showlastname', 'block_gapps'), new lang_string('showlastnameinfo', 'block_gapps'), '1');



/********************************************************
 *                      Gsync Settings                  *
 ********************************************************/
$gname = new lang_string('gsyncblockname','block_gapps');
$hbutton = '';//$OUTPUT->old_help_icon('googlesynchelp', $gname,'block_gapps');  // $OUTPUT->help_icon() instead
$configs[] = new admin_setting_heading('gsyncheader', $gname.' '.$hbutton, "");
$configs[] = new admin_setting_configtext('username', new lang_string('usernamesetting', 'block_gapps'), new lang_string('usernamesettingdesc', 'block_gapps'), '', PARAM_RAW, 30);
$configs[] = new admin_setting_configpasswordunmask('password', new lang_string('passwordsetting', 'block_gapps'), new lang_string('passwordsettingdesc', 'block_gapps'), '');
$configs[] = new admin_setting_configtext('domain', new lang_string('domainsetting', 'block_gapps'), new lang_string('domainsettingdesc', 'block_gapps'), '', PARAM_RAW, 30);
$configs[] = new admin_setting_configcheckbox('usedomainemail', new lang_string('usedomainemailsetting', 'block_gapps'), new lang_string('usedomainemailsettingdesc', 'block_gapps'), 0);
$configs[] = new admin_setting_configcheckbox('allowevents', new lang_string('alloweventssetting', 'block_gapps'), new lang_string('alloweventssettingdesc', 'block_gapps'), 1);
$configs[] = new admin_setting_configcheckbox('autoadd', new lang_string('autoaddsetting', 'block_gapps'), new lang_string('autoaddsettingdesc', 'block_gapps'), 1);
$configs[] = new admin_setting_configtext('croninterval', new lang_string('cronintervalsetting', 'block_gapps'), new lang_string('cronintervalsettingdesc', 'block_gapps'), 30, PARAM_INT, 30);
$configs[] = new admin_setting_configtext('cronexpire', new lang_string('cronexpiresetting', 'block_gapps'), new lang_string('cronexpiresettingdesc', 'block_gapps'), '24', PARAM_INT, 30);

// How to handle Google Apps Accounts when not syncing (handle guser sync)
$options = array(0 => new lang_string('donothing', 'block_gapps'),   // do nothing
                 1 => new lang_string('disableacc', 'block_gapps'),  // disable
                 2 => new lang_string('deleteacc', 'block_gapps'));  // delete
$configs[] = new admin_setting_configselect('handlegusersync', new lang_string('hdlgusersync', 'block_gapps'),
                   new lang_string('hdlgusersyncdesc', 'block_gapps'),0, $options);

// Should admins be kept from syncing?
$configs[] = new admin_setting_configcheckbox('nosyncadmins', new lang_string('nosyncadminssetting', 'block_gapps'), new lang_string('nosyncadminssettingdesc', 'block_gapps'),1);

// Remove admins from sync table right away
global $CFG,$FULLME;
$headaction = '<a href="'.$CFG->wwwroot.'/blocks/gdata/index.php?hook=cleanadmins&return='.$FULLME.'">'.get_string('cleanadminsfromsyncstr','block_gapps').'</a>';
$configs[] = new admin_setting_heading('cleanadminsfromsync',$headaction,'');

// Define the config plugin so it is saved to
// the config_plugin table then add to the settings page
foreach ($configs as $config) {
    $config->plugin = 'blocks/gapps';
    $settings->add($config);
}