<?php
/**
 * Sync a single user to gapps
 *
 * @author Mark Nielsen
 * @package block_gapps
 */

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/local/mr/framework/bootstrap.php');
require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');

list($options, $unrecognized) = cli_get_params(
    array('userid' => 0, 'help' => false),
    array('u' => 'userid', 'h' => 'help')
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    echo "Gapps Sync User CLI Script.

Options:
-u, --userid  Sync this user to Google Apps
-h, --help    Print out this help

Example:
/usr/bin/php blocks/gaps/cli/syncuser.php --userid=5
";
    die;
}

$gapps = new blocks_gapps_model_gsync();
$gapps->gapps_connect_via_authorization();
$gapps->sync_user_cli($options['userid']);
exit(0);
