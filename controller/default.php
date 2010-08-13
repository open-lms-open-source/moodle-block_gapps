<?php
/**
 * Default controller
 *
 * @author Mark Nielsen
 * @author edited by Chris Stones
 * @version $Id$
 * @package blocks/gapps
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class block_gapps_controller_default extends mr_controller_block {

    /**
     * Do common setup routines or use to change defaults
     */
    public function init() {
        // Example, change default for tab
        // $this->tabs->set($this->action);
    }

    /**
     * Require capability for viewing this controller
     */
    public function require_capability() {
        /* Example implementation
        switch ($this->action) {
            case 'view':
                require_capability(...);
                break;
            case 'foo':
                require_capability(...);
                break;
            default:
                require_capability(...);
        }
        */

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
        $tabs->toptab('status', array('controller' => 'gsync','action' => 'status'))
             ->toptab('users', array('controller' => 'gsync','action' => 'usersview'))
             ->toptab('addusers', array('controller' => 'gsync','action' => 'addusers'));   
    }

    /**
     * Default screen
     *
     * Demo of the buffer helper
     * Demo of mr_html_tag and tag helper
     */
    public function view_action() {
        // Note, you can get an instance of mr_html_tag like so:
        $tag = $this->helper->tag();

        // Advanced usage of mr_html_tag, calls are explained below
        return $this->helper->tag->div()->class('centerpara')->close(  // Passing tag contents through close() instead of the opening call: div().  This increases readability.  I can also pass as many params as I want.
            $this->helper->tag->p(  // Below, I can pass as many params as I want, all will be added to the contents of this <p> tag
                $this->helper->world->say_hello(),
                $this->helper->tag->p('Global config:'),  // Don't need to call close(), EG: "$this->helper->tag->p('Global config:')->close()" because the tag will be casted to a string and mr_html_tag implements __toString()
                $this->helper->buffer('print_object', $this->config)
            ), // Same here, don't need to call close()
            $this->output->single_button($this->url->out(false, array('action' => 'saygood')), 'Say good'),
            $this->output->single_button($this->url->out(false, array('action' => 'saybad')), 'Say bad')
        );
    }

    /**
     * Example of setting a positive message and then going back to originating screen
     *
     * Demo of mr_notify
     */
    public function saygood_action() {
        $this->notify->good('good');
        redirect($this->url);
    }

    /**
     * Example of setting a negative message and then going back to originating screen
     *
     * Demo of mr_notify
     */
    public function saybad_action() {
        $this->notify->bad('bad');
        redirect($this->url);
    }

    /**
     * Admin only, restricted access by $this->require_capability()
     */
    public function admin_action() {
        return 'admin only';
    }

    /**
     * HTML demo
     *
     * Demo of mr_html_tag
     * Demo of tag helper
     */
    public function html_action() {
        $this->tabs->set('html');
        $this->print_header();

        echo $this->output->heading('Demo of mr_html_tag');
        $this->helper->highlight(__CLASS__, __FUNCTION__);
        echo $this->output->box_start('generalbox boxaligncenter boxwidthnormal');

        #### DEMO CODE ####
        // New instance
        $tag = new mr_html_tag();
        // Create html with the mr_html_tag instance
        $html = $tag->a('Click me!')
                    ->title('This " should be encoded')
                    ->href('http://google.com')
                    ->close();
        $this->helper->dump($html, 1);

        // Create html with the mr_html_tag::open()
        $html = mr_html_tag::open()->a('Click me!')
                                   ->title('This " should be encoded')
                                   ->href('http://google.com')
                                   ->close();
        $this->helper->dump($html, 2);

        // Create html with the helper
        $html = $this->helper->tag->a('Click me!')
                             ->title('This " should be encoded')
                             ->href('http://google.com')
                             ->close();
        $this->helper->dump($html, 3);

        // Example of using the other special methods
        $link = $tag->a('Click me!')
                    ->title('This " should be encoded')
                    ->href('http://google.com')
                    ->class('foo');

        // Modify attributes (Note, you can do these in bluk
        // with add_attributes(), append_attributes(), prepend_attributes()
        // and remove_attributes())
        $link->prepend_class('bar')
             ->append_class('baz')
             ->remove_title();
        $this->helper->dump($link->get_class(), 4);

        // When $link is casted to a string, it will automatically render to HTML
        $this->helper->dump((string) $link, 5);

        // You can bulk add/modify attributes as well

        // Example of making a tag with no content
        $html = $tag->input()
                    ->type('submit')
                    ->value(get_string('savechanges'))
                    ->close();
        $this->helper->dump($html, 6);

        // You can pass tag content to either the first call or to close()
        $html = $tag->div('This is ')
                    ->class('generalbox')
                    ->close('cool!');
        $this->helper->dump($html, 7);

        // Advanced usage
        // Taking advantage of mr_html_tag::__toString to avoid calling close()
        // Taking advantage of passing multiple tag content strings
        $html = $tag->div()->class('generalbox')->close(
            $tag->p(
                'Hello all!',
                $tag->strong('This is cool and stuffs')
            ),
            $tag->span('Dingo')->class('centerpara')
        );
        $this->helper->dump($html, 8);
        #### DEMO CODE ####

        $this->output->box_end();
        $this->print_footer();
    }
}