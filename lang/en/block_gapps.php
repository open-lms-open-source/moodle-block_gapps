<?php
/**
 * Language string
 *
 * @author Mark Nielsen
 * @package blocks/helloworld
 */

$string['bad'] = 'Bad!';
$string['blockname'] = 'Google Apps';

$string['blocks_helloworld_report_users'] = 'Report Title';
$string['blocks_helloworld_report_users_description'] = 'Report Description';
$string['cachetab'] = 'mr_cache';
$string['dbqueuetab'] = 'mr_db_queue';
$string['dbrecordtab'] = 'mr_db_record';
$string['dbtab'] = 'mr_db_*';
$string['dbviewtab'] = 'mr_db_table';
$string['defaultadmintab'] = 'Admin only tab';
$string['defaulttab'] = 'Default Tab';
$string['defaultviewtab'] = 'mr_notify and Global Config';
$string['filtertab'] = 'mr_filter_* and mr_preference';
$string['good'] = 'Good!';
$string['htmltab'] = 'mr_html_tab';
$string['plugin-one'] = 'Plugin One';
$string['plugin-two'] = 'Plugin Two';
$string['pluginname'] = 'Google Apps';

$string['plugintab'] = 'mr_plugin and mr_helper_load';
$string['reporttab'] = 'mr_report_abstract';
$string['tabletab'] = 'mr_html_table_*';
$string['textexample'] = 'Example';
$string['textexampledesc'] = 'Example Desc';
$string['view'] = 'View';


/**
 * GSync Strings formerly from (gdata)
 * Language entries for Google Data Block
 *
 * @author Mark Nielsen
 * @version $Id$
 * @package block_gdata
 **/

$string['addusers'] = 'Add users to sync';
$string['alloweventssetting'] = 'Enable events';
$string['alloweventssettingdesc'] = 'If this setting is enabled, then a Moodle user\'s account will be instantly updated in Google Apps when the user edits his/her account in Moodle\'s standard edit profile interface.  Also, if a Moodle user\'s account is deleted, then the associated Google Apps account will also be instantly deleted.  This only applies to Moodle accounts that are currently being synchronized to Google Apps.  This feature is \"best attempt\" only, so failures will fallback to the cron to perform the synchronization.';
$string['authfailed'] = 'Authentication with Google Apps failed.  Please check your credentials.';
$string['gsyncblockname'] = 'Google User Sync';
$string['checkstatus'] = 'Check status';
$string['confirmaddusers'] = 'Are you sure you want to add all $a users in your search set?';
$string['confirmusers'] = 'Are you sure you want to remove all $a users from the sync list?';
$string['connectionsuccess'] = 'Authentication with Google Apps was successful.';
$string['cronexpiresetting'] = 'Cron expire (hours)';
$string['cronexpiresettingdesc'] = 'When the synchronization runs, it locks the cron from being excecuted again until it has finnished.  This setting is used to determine when that lock has expired.  Consider setting this to a high value especially on first runs with a lot of users.';
$string['cronintervalsetting'] = 'Cron interval (minutes)';
$string['cronintervalsettingdesc'] = 'Enter in how often the Moodle to Google Apps synchronization should be executed on the cron.  Enter zero to stop running the synchronization.';
$string['domainsetting'] = 'Google Apps domain';
$string['domainsettingdesc'] = 'This is the domain associated with your Google Apps account. For example, if you login to Google Apps as \'foo@bar.com\', your domain is \'bar.com\'.';
$string['failedtodeletesyncrecord'] = 'Failed to delete block_gdata_gapps record with id = $a';
$string['failedtoupdateemail'] = 'Failed to update user\'s email with the one from Google Apps';
$string['failedtoupdatesyncrecord'] = 'Failed to update block_gdata_gapps record with id = $a';
$string['gappserror'] = 'Google Apps error: $a';
$string['insertfailed'] = 'Insert failed';
$string['invalidparameter'] = 'Programmer\'s error: invalid parameter passed';
$string['lastsync'] = 'Last sync';
$string['missingrequiredconfig'] = 'Missing required global setting: $a';
$string['notconfigured'] = 'Global settings have not been configured.  Please configure this plugin.';
$string['nouserfound'] = 'User not found';
$string['nousersfound'] = 'No users need to be synchronized now or there are none to be synchronized';
$string['pagesize'] = 'Page size';
$string['passwordsetting'] = 'Google Apps password';
$string['passwordsettingdesc'] = 'This is the password associated with the above username.';
$string['selectall'] = 'Select all';
$string['selectnone'] = 'Select none';
$string['setfieldfailed'] = 'Set field failed';
$string['settings'] = 'Settings';
$string['status'] = 'Status';
$string['statusaccountcreationerror'] = 'Failed to create Google Apps account';
$string['statuserror'] = 'Error';
$string['statusnever'] = 'Never';
$string['statusok'] = 'OK';
$string['statususernameconflict'] = 'Username conflict';
$string['submitbuttonaddusers'] = 'Add users to sync';
$string['submitbuttonalladdusers'] = 'Add all {$a} users';
$string['submitbuttonallusers'] = 'Remove all {$a} users';
$string['submitbuttonusers'] = 'Remove users from sync';
$string['usedomainemailsetting'] = 'Use Google Apps email';
$string['usedomainemailsettingdesc'] = 'Update Moodle\'s user record with the email from the Google Apps domain.  The update will occur during the Moodle to Google Apps synchronization.';
$string['useralreadyexists'] = 'User already exists in the block_gdata_gapps table';
$string['useralreadyexists'] = 'User already exists';
$string['usernameconflict'] = 'Username conflict: Moodle changed username from $a->oldusername to $a->username.  $a->username is already being synced to Goodle Apps';
$string['usernamesetting'] = 'Google Apps username';
$string['usernamesettingdesc'] = 'This is the username (without domain) used to administer your Google Apps account. For example, if you login to Google Apps as \'foo@bar.com\', your username is \'foo\'.';
$string['userssynced'] = 'Users being synced';
$string['cleanadminsfromsyncstr'] = '<a href="$a->actionurl">Clean Existing Admin Users from the Google Sync Now</a>';
$string['cleanadminsfromsync'] = 'Manage Syncing of Admin Accounts';
$string['nosyncadminssettingdesc'] = 'Do not sync admin users.';
$string['nosyncadminssetting'] = 'No Admin Syncing';
$string['cleanadminsfromsyncstr'] = 'Clean Admins from Sync Now';
$string['hdlgusersync'] = 'Action on Sync Removal';
$string['hdlgusersyncdesc'] = 'Google apps user accounts can be either disabled or deleted or left alone upon removal from Moodle sync.';
$string['donothing'] = 'Do nothing';
$string['disableacc'] = 'Disable Google Apps Account';
$string['deleteacc'] = 'Delete the google apps account permanently';
$string['handlegappsyncvalueerror'] = 'The Action on Sync Removal setting (handlegusersync) returned an unknown value.';
$string['gappsconnectiontestfailed'] = 'Gapps Connection Error: {$a->msg}';

$string['blocks_gapps_report_users'] = 'Users being synced';
$string['blocks_gapps_report_addusers'] = 'Add users to sync';

$string['statustab'] = 'Status';
$string['userstab'] = 'Users being synced';
$string['adduserstab'] = 'Add users to sync';




/* Gmail Strings formerly from (gmail) */
$string['gmailhelp'] = 'Gmail Help';
$string['gmailhelp_help'] = 'The following settings control how moodle handles the Google RSS Atom Feed.';

$string['gmailblockname'] = 'Gmail';
//$string['gmail'] = 'gmail';
$string['domainnotset'] = 'Gapps Domain not set.';
$string['oauthsecretstr'] = 'OAuth Consumer Secret';
$string['oauthsecretinfo'] = 'This is the same value you find on Google\'s Manage OAuth Access page under Advanced tools.';
$string['msgnumberunread'] = "Unread Message Count";
$string['msgnumberunreadinfo'] = "The number of unread messages you would like displayed in the gmail block. Leave as zero for no limit.";
$string['sorrycannotgetmail'] = 'Sorry, could not obtain mail.';
$string['compose'] = 'Compose';
$string['inbox'] = 'Inbox';
$string['unreadmsgs'] = 'Unread';

$string['showfirstname'] = 'Show First Name';
$string['showfirstnameinfo'] = 'Show the author\'s first name next to their message.';

$string['showlastname'] = 'Show Last Name';
$string['showlastnameinfo'] = 'Show the author\'s last name next to their message.';

$string['newwinlink'] = 'New Window Links';
$string['newwinlinkinfo'] = 'If selected links will open in new window.';


$string['mustusegoogleauthenticaion'] = 'You must be using Google SSO Authentication for this block to work.';
$string['missingoauthkey'] = 'Missing OAuth Key';

$string['consumer_key'] = 'Google Apps Domain';
$string['consumer_keyinfo'] = 'Enter the domain for your Google Apps Service here.';
$string['use3leggedoauth'] = 'Use 3 Legged OAuth';
$string['use3leggedoauthinfo'] = 'You may choose to authenticate via 2 legged or 3 legged OAuth.';
$string['grantaccesstoinbox'] = 'Grant Access to your Inbox';

$string['refreshtoken'] = 'Refresh Access Token';
$string['refreshfailed'] = 'Failed to delete token record.';

$string['googlesynchelp_help'] = 'Google Sync Help';
$string['googlesynchelp'] = '';
//$string['googlesynchelp'] = <<<EOD
//<h3>Google Apps</h3>
//
//<p>This help file provides brief overview of the different features of the Google Apps block.</p>
//
//<h4>Table of contents:</h4>
//<ul>
//    <li><a href="#status">Google Apps Status</a></li>
//    <li><a href="#users">Users being synced</a></li>
//    <li><a href="#addusers">Add users to sync</a></li>
//</ul>
//
//<h4><a name="status" href="#status">Google Apps Status</a></h4>
//<div class="indent">
//    The <em>Status</em> tab provides feedback on whether or not Moodle can connect to
//    Google Apps using the credentials provided in <em>Site Administration > Modules
//    > Blocks > Google Apps</em>.  The credentials are the values entered for
//    username, password and domain.
//</div>
//
//<h4><a name="users" href="#users">Users being synced</a></h4>
//<div class="indent">
//    The <em>Users being synced</em> tab provides an interface to view all the users
//    that are currently being synchronized to Google Apps.  Here, you can also review the
//    last time an account was synchronized to Google Apps and the status of the account
//    synchronization.  Synchronization takes place according to the cron settings
//    found in <em>Site Administration > Modules > Blocks > Google Apps</em>.
//
//    <p>
//        <strong>Synchronization rules:</strong>
//        <ul>
//            <li>
//                If a user has been deleted in Moodle or has been
//                removed from the synchronization process, then delete
//                the user's account in Google Apps.
//            </li>
//            <li>
//                If a user has had their username renamed in Moodle then
//                delete the old account using the old username from Google
//                Apps and create a new account in Google Apps with the new
//                Moodle username.
//            </li>
//            <li>
//                If a user does not have an account in Google Apps
//                with their Moodle username, then create it.
//            </li>
//            <li>
//                Do not allow duplicate usernames to be used for
//                users in Google Apps and in Moodle.
//            </li>
//            <li>
//                If the Moodle user has an account in Google Apps then
//                check first name, last name and password for changes
//                in Moodle, then update Google Apps if necessary.
//            </li>
//            <li>
//                The password shared by Moodle and Google Apps must
//                be at least 6 characters in length.  Please use
//                <em>Site Administration > Security > Site policies</em>
//                to turn on Password Policy and set Password Length to 6
//                or more characters.
//            </li>
//        </ul>
//    </p>
//</div>
//
//<h4><a name="addusers" href="#addusers">Add users to sync</a></h4>
//<div class="indent">
//    The <em>Add users to sync</em> tab provides an interface to bulk add users
//    to the Moodle to Google Apps synchronization.
//</div>
//EOD;
