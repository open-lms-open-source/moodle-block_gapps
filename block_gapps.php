<?php // $Id$
/**
 * helloworld block class definition
 *
 * @author Mark Nielsen
 * @version $Id$
 * @package block_helloworld
 */

class block_gapps extends block_list {
    /**
     * Init
     */
    function init() {
        $this->title   = get_string('blockname', 'block_gapps');
        $this->version = 2010022400;
    }

    /**
     * Link to view the block
     */
    function get_content() {
        global $CFG, $USER, $COURSE, $OUTPUT;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $title = get_string('view', 'block_helloworld');
        $this->content->items[] = html_writer::tag('a', $title, array('title' => $title, 'href' => new moodle_url("/blocks/helloworld/view.php?courseid=$COURSE->id")));
        $this->content->icons[] = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/admin'), 'alt' => $title));

        return $this->content;
    }
}

?>