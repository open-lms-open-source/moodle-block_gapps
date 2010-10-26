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
 * Rest page has to be seperate from a controller due to the fact
 * we can't fully control the mr system from ruining headers etc.
 *
 * @author Chris B Stones
 * @package block_gapps
 */
require_once("../../config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // now set up and run the gapps cron
    require_once($CFG->dirroot.'/local/mr/framework/bootstrap.php');
    require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');
    $gapps = new blocks_gapps_model_gsync();
    $gapps->rest();
}
