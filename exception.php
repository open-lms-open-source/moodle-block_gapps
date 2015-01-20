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
 * Google Data Exception Class
 *
 * @copyright  Copyright (c) 2009 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://opensource.org/licenses/gpl-3.0.html     GNU Public Licens
 * @author Mark Nielsen
 * @author Chris B Stones
 * @package block_gapps
 **/
class blocks_gapps_exception extends moodle_exception {
    /**
     * Constructor
     *
     * @param string $identifier The key identifier for the localized string
     * @param string $module The module where the key identifier is stored. If none is specified then moodle.php is used.
     * @param mixed $a An object, string or number that can be used within translation strings
     * @param int $code Error code
     * @return void
     **/
    public function __construct($identifier, $module = 'block_gapps', $a = NULL, $code = 0) {
        parent::__construct($identifier, $module,'', $a, null);
    }
} 
