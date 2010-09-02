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
 * Default controller
 *
 * @author Mark Nielsen
 * @author edited by Chris Stones
 * @version $Id$
 * @package block_gapps
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class block_gapps_controller_default extends mr_controller_block {

    /**
     * Require capability for viewing this controller
     */
    public function require_capability() {
        // Require admin for our admin action
        switch ($this->action) {
            case 'admin':
                require_capability('moodle/site:config', $this->get_context());
                break;
        }
    }

    /**
     * Define tabs for all controllers
     */
    public static function add_tabs($controller, &$tabs) {
        $tabs->toptab('status',     array('controller' => 'gsync','action' => 'status'))
             ->toptab('users',      array('controller' => 'gsync','action' => 'usersview'))
             ->toptab('addusers',   array('controller' => 'gsync','action' => 'addusersview'))
             ->toptab('diagnostic', array('controller' => 'gsync','action' => 'viewdiagnostics'),has_capability('moodle/site:config', $controller->get_context()))
               ->subtab('runcron',    array('controller' => 'gsync','action' => 'runcron'))
               ->subtab('syncuser',   array('controller' => 'gsync','action' => 'syncuser')) //syncuser
               ->subtab('viewdocs',   array('controller' => 'gsync','action' => 'viewdocs'))
               ->subtab('gappslogs',   array('controller' => 'gsync','action' => 'gappslogs'));

    }

    /**
     * Default screen
     */
    public function view_action() {
                $this->print_header();
                echo "default view action";
                $this->print_footer();
                
    }
}