<?php
/**
 * Copyright (C) 2010  Moodlerooms Inc.
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
cli_problem('This has been disabled because the API used has been removed by Google');

//$gapps = new blocks_gapps_model_gsync();
//$gapps->cron();

exit(0);
