<?php
/**
 * Dump Helper
 *
 * @author Mark Nielsen
 * @package blocks/helloworld
 **/

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class blocks_helloworld_helper_dump extends mr_helper_abstract {
    /**
     * Variable dump with label
     *
     * @param mixed $var Variable to dump
     * @param string $label The label to print
     * @return void
     */
    public function direct($var, $label = '', $return = false) {
        global $OUTPUT;

        $helper = new mr_helper();

        $output  = $OUTPUT->heading($label, 5);
        $output .= $helper->buffer('var_dump', $var);

        if ($return) {
            return $output;
        }
        echo $output;
    }
}