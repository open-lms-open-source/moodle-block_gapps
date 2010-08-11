<?php
/**
 * GApps Settings (gapps,gmail,gsync)
 * 
 * http://docs.moodle.org/en/Development:Admin_settings#External_pages
 * @author Chris Stones
 * @version $Id$
 * @package blocks/gapps
 **/

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');
global $OUTPUT;

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
$hbutton = $OUTPUT->old_help_icon('gmailhelp', get_string('gmailhelp','block_gapps'),'block_gapps');
$configs[] = new admin_setting_heading('gmail', "Gmail Settings ".$hbutton, "");


$configs[] = new admin_setting_configpasswordunmask('oauthsecret', get_string('oauthsecretstr', 'block_gapps'), get_string('oauthsecretinfo', 'block_gapps'), '');

$configs[] = new admin_setting_configtext('consumer_key', get_string('consumer_key', 'block_gapps'), get_string('consumer_keyinfo', 'block_gapps'), '');


$configs[] = new admin_setting_configtext('msgnumber', get_string('msgnumberunread', 'block_gapps'), get_string('msgnumberunreadinfo', 'block_gapps'), '0', PARAM_RAW, 5);

// Open links in new window
$configs[] = new admin_setting_configcheckbox('newwinlink', get_string('newwinlink', 'block_gapps'), get_string('newwinlinkinfo', 'block_gapps'), '1');

// Choose to Show First and Last Names to Save Space
$configs[] = new admin_setting_configcheckbox('showfirstname', get_string('showfirstname', 'block_gapps'), get_string('showfirstnameinfo', 'block_gapps'), '1');

$configs[] = new admin_setting_configcheckbox('showlastname', get_string('showlastname', 'block_gapps'), get_string('showlastnameinfo', 'block_gapps'), '1');



/********************************************************
 *                      Gsync Settings                  *
 ********************************************************/
$gname = get_string('gsyncblockname','block_gapps');
$hbutton = $OUTPUT->old_help_icon('googlesynchelp', $gname,'block_gapps');
$configs[] = new admin_setting_heading('gsyncheader', $gname.' '.$hbutton, "");


if (!class_exists('admin_setting_special_croninterval')) {
    /**
     * This setting behaves exactly like
     * admin_setting_configtext except it
     * also stores the value from this config
     * as seconds in the cron field of the
     * gdata block record.
     *
     * @package block_gdata
     **/
    class admin_setting_special_croninterval extends admin_setting_configtext {

        /**
         * Set the cron field for the gdata block record
         * to the number of sections set in this setting.
         *
         * @return boolean
         **/
        function config_write($name, $value) {
            global $DB;
            if (empty($value)) {
                $cron = 0;
            } else {
                $cron = $value * MINSECS;
            }
            //$DB->set_field_select('block', 'cron', $cron,'name = ?',array('gdata'));
            // set_field('block', 'cron', $cron, 'name', 'gdata')
            if ($DB->set_field_select('block', 'cron', $cron,'name = ?',array('gdata'))) {
                return parent::config_write($name, $value);
            }
            return false;
        }
    }
}


$configs[] = new admin_setting_configtext('username', get_string('usernamesetting', 'block_gapps'), get_string('usernamesettingdesc', 'block_gapps'), '', PARAM_RAW, 30);
$configs[] = new admin_setting_configpasswordunmask('password', get_string('passwordsetting', 'block_gapps'), get_string('passwordsettingdesc', 'block_gapps'), '');
$configs[] = new admin_setting_configtext('domain', get_string('domainsetting', 'block_gapps'), get_string('domainsettingdesc', 'block_gapps'), '', PARAM_RAW, 30);
$configs[] = new admin_setting_configcheckbox('usedomainemail', get_string('usedomainemailsetting', 'block_gapps'), get_string('usedomainemailsettingdesc', 'block_gapps'), 0);
$configs[] = new admin_setting_configcheckbox('allowevents', get_string('alloweventssetting', 'block_gapps'), get_string('alloweventssettingdesc', 'block_gapps'), 1);
$configs[] = new admin_setting_special_croninterval('croninterval', get_string('cronintervalsetting', 'block_gapps'), get_string('cronintervalsettingdesc', 'block_gapps'), 30, PARAM_INT, 30);
$configs[] = new admin_setting_configtext('cronexpire', get_string('cronexpiresetting', 'block_gapps'), get_string('cronexpiresettingdesc', 'block_gapps'), '24', PARAM_INT, 30);

// How to handle Google Apps Accounts when not syncing (handle guser sync)
$options = array(0 => get_string('donothing', 'block_gapps'),   // do nothing
                 1 => get_string('disableacc', 'block_gapps'),  // disable
                 2 => get_string('deleteacc', 'block_gapps'));  // delete
$configs[] = new admin_setting_configselect('handlegusersync', get_string('hdlgusersync', 'block_gapps'),
                   get_string('hdlgusersyncdesc', 'block_gapps'),0, $options);

// Should admins be kept from syncing?
$configs[] = new admin_setting_configcheckbox('nosyncadmins', get_string('nosyncadminssetting', 'block_gapps'), get_string('nosyncadminssettingdesc', 'block_gapps'), 0);

// Remove admins from sync table right away
global $CFG,$FULLME;
$headaction = '<a href="'.$CFG->wwwroot.'/blocks/gdata/index.php?hook=cleanadmins&return='.$FULLME.'">'.get_string('cleanadminsfromsyncstr','block_gapps').'</a>';
$configs[] = new admin_setting_heading('cleanadminsfromsync',$headaction,'');




// Diagnostics page
//$configs[] = new admin_setting_heading('diagnostics', "Diagnostics Page", $info = "Gsync Settings");


// We are adding each set of configrations to a childadmin page under the blocksettinggapps catgory in plugins > blocks >
//
// Gmail Settings
//$ADMIN->add('blocksettinggapps', new admin_category('blocksettinggappsgmail', get_string('authentication', 'admin')));

// Define the config plugin so it is saved to
// the config_plugin table then add to the settings page
foreach ($configs as $config) {
    $config->plugin = 'blocks/gapps';
    $settings->add($config);
}