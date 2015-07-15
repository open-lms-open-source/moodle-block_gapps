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
 * Google Apps block
 *
 * @package   block_gapps
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_gapps\container;

defined('MOODLE_INTERNAL') || die();

/**
 * Google Apps block definition
 *
 * @package   block_gapps
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_gapps extends block_base {
    /**
     * Block setup
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_gapps');
    }

    /**
     * Has global config
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }
    
    /**
     * Clean up the configs upon deletion of the block
     */
    function before_delete() {
        global $DB;

        $DB->delete_records('config_plugins', array('plugin' => 'blocks/gapps'));
    }

    /**
     * Block content
     */
    function get_content() {
        global $OUTPUT;

        static $sendjs = true;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content         = new stdClass;
        $this->content->text   = '';
        $this->content->footer = '';

        $container = new container();
        $config = $container->get_config();

        if (empty($config->domain) && has_capability('moodle/site:config', context_system::instance())) {
            $this->content->text = $OUTPUT->notification(get_string('domainnotconfigured', 'block_gapps'));
            return $this->content;
        } else if (!isloggedin() || isguestuser() || empty($config->domain)) {
            return $this->content;
        }

        /** @var block_gapps_renderer $renderer */
        $renderer = $this->page->get_renderer('block_gapps');
        $this->content->text = $renderer->google_apps($config->domain);

        if (!empty($config->clientid)) {
            // Only send the JS once, just incase we somehow get two blocks on one page.
            if ($sendjs) {
                $this->content->text .= $renderer->unread_messages_js($config);
                $this->content->text .= $renderer->unread_messages_template($config);
                $sendjs = false;
            }

            $this->content->footer = html_writer::tag('small',
                html_writer::link('#', get_string('authorizeaccess', 'block_gapps'), ['class' => 'authorize']));
        }

        return $this->content;
    }
}
