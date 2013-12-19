<?php
/**
 * Run the Google User Sync cron
 *
 * Note: this should be used for testing purposes.
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
    array('help' => false),
    array('h' => 'help')
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    echo "Gapps Sync User Cron CLI Script.

Options:
-h, --help    Print out this help

Example:
/usr/bin/php blocks/gaps/cli/cron.php
";
    die;
}

$gapps = new blocks_gapps_model_gsync();
$gapps->cron(true);

exit(0);
