<?php
/**
 * Highlight Helper
 *
 * @author Mark Nielsen
 * @package blocks/helloworld
 **/

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class blocks_helloworld_helper_highlight extends mr_helper_abstract {
    /**
     * This highlights section of a class's function
     * thats surrounded by #### DEMO CODE ####
     *
     * @param string $class The class name
     * @param string $function The function name
     * @param boolean $return Output return
     * @return void
     */
    public function direct($class, $function, $return = false) {
        global $OUTPUT;

        mr_bootstrap::zend();
        require_once('Zend/Reflection/Method.php');

        $method = new Zend_Reflection_Method($class, $function);
        $code   = str_replace('$this->helper->highlight(__CLASS__, __FUNCTION__);', '', $method->getBody());

        $matches = array();
        if (preg_match("/(#### DEMO CODE ####)(.*)(#### DEMO CODE ####)/s", $code, $matches)) {
            $code = $matches[2];
        }
        $code = '        '.trim($code);

        $output  = $OUTPUT->heading('Example Code');
        $output .= $this->highlight($code);
        $output .= $OUTPUT->heading('Example Code Output');

        if ($return) {
            return $output;
        }
        echo $output;
    }

    /**
     * Highlight some PHP code
     *
     * @param string $code The code to highlight
     * @return string
     */
    public function highlight($code) {
        global $OUTPUT;

        $highlighted = highlight_string("<?php\n\n$code\n\n?>", true);
        $highlighted = $OUTPUT->box($highlighted, 'generalbox boxaligncenter boxwidthnormal');

        return $highlighted;
    }
}