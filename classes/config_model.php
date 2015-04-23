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
 * Configuration model
 *
 * @package   block_gapps
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gapps;

defined('MOODLE_INTERNAL') || die();

/**
 * Configuration model
 *
 * @package   block_gapps
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config_model {
    /**
     * Google Apps domain
     *
     * EG: "example.com"
     *
     * @var string
     */
    public $domain;

    /**
     * Client ID from the Google Developers Console
     *
     * @var string
     */
    public $clientid;

    /**
     * Open links in popups
     *
     * @var int
     */
    public $newwinlink = 1;

    /**
     * Number of unread Gmail messages to show
     *
     * @var int
     */
    public $msgnumber = 10;

    /**
     * Show Gmail message from user's first name
     *
     * @var int
     */
    public $showfirstname = 1;

    /**
     * Show Gmail message from user's last name
     *
     * @var int
     */
    public $showlastname = 1;

    /**
     * Don't allow an empty number
     *
     * @return int
     */
    public function get_number_of_messages_to_show() {
        return !empty($this->msgnumber) ? $this->msgnumber: 10;
    }
}