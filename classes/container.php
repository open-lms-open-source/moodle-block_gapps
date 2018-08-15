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
 * Container
 *
 * @package   block_gapps
 * @copyright Copyright (c) 2015 Blackboard Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gapps;

defined('MOODLE_INTERNAL') || die();

/**
 * Container
 *
 * @package   block_gapps
 * @copyright Copyright (c) 2015 Blackboard Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class container {
    /**
     * @var mixed[]
     */
    protected $services = [];

    /**
     * @param array $services Pre-load services - really don't use this other than for testing.
     */
    public function __construct(array $services = []) {
        $this->services = $services;
    }

    /**
     * @return config_model
     */
    public function get_config() {
        if (!array_key_exists('config_model', $this->services)) {
            $config = new config_model();
            $data   = get_config('blocks/gapps');
            foreach ($data as $property => $value) {
                if (property_exists($config, $property)) {
                    $config->$property = $value;
                }
            }
            $this->services['config_model'] = $config;
        }
        return $this->services['config_model'];
    }
}
