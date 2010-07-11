<?php
/**
 * World Helper
 *
 * @author Mark Nielsen
 * @package blocks/helloworld
 **/

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class blocks_helloworld_helper_world extends mr_helper_abstract {
    /**
     * Direct call to world helper
     *
     * @return string
     */
    public function direct() {
        return 'Hello direct()!';
    }

    /**
     * Say Hello
     *
     * @return string
     */
    public function say_hello() {
        return 'Hello World!';
    }
}