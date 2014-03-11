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
 * Gdata Event hooks
 *
 * @author Mark Nielsen
 * @author Modfied by Chris Stones
 * @package block_gapps
 **/

$observers = array(
    array(
        'eventname' => '\core\event\user_updated',
        'includefile' => '/blocks/gapps/model/gsync.php',
        'callback' => 'blocks_gapps_model_gsync::user_updated_event',
    ),
    array(
        'eventname' => '\core\event\user_deleted',
        'includefile' => '/blocks/gapps/model/gsync.php',
        'callback' => 'blocks_gapps_model_gsync::user_deleted_event',
    ),
    array(
        'eventname' => '\auth_gsaml\event\user_authenticated',
        'includefile' => '/blocks/gapps/model/gsync.php',
        'callback' => 'blocks_gapps_model_gsync::user_authenticated_event',
    ),
    array(
        'eventname' => '\core\event\user_created',
        'includefile' => '/blocks/gapps/model/gsync.php',
        'callback' => 'blocks_gapps_model_gsync::user_created_event',
    ),
);
