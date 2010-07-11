<?php
/**
 * Cache controller
 *
 * @author Mark Nielsen
 * @version $Id$
 * @package blocks/helloworld
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class block_helloworld_controller_cache extends mr_controller_block {
    /**
     * Default screen
     *
     * Demo of cache
     */
    public function view_action() {
        $this->print_header();
        echo $this->output->heading('Demo of mr_cache');
        $this->helper->highlight(__CLASS__, __FUNCTION__);
        echo $this->output->box_start('generalbox boxaligncenter boxwidthnormal');

        #### DEMO CODE ####
        // Note: you can make your very own instance of mr_cache!
        // $this->helper->cache is EXACTLY the same as:
        // $cache = new mr_cache('blocks_helloworld_');

        // Try to load cache ID = time_string from cache
        if (!$string = $this->helper->cache->load('time_string')) {
            // Failed to get from cache, create a new string to cache
            $string = 'Last cache: '.userdate(time(), '%A, %d %B %Y, %r');

            // Save the string to cache using cache ID = time_string
            $this->helper->cache($string, 'time_string');

            // Note: the above line is EXACTLY the same as:
            // $this->helper->cache->save($string, 'time_string');
        }
        $this->helper->dump($string, 'Currently cached value');

        // See if a cached ID exists or not
        if (!$this->helper->cache->test('time_string')) {
            // In this case, if we get here, its bad, cache isn't working
            $this->notify->add_string('cache doesnt exist');
        }

        // EXAMPLE:
        // Slightly different code when storing data that fails to pass is_string()
        // Data like this, gets serialized, need to tell load method to unserialize it like so
        // if (!$array = $this->helper->cache->load('array_example', true)) {  // ADDED: true
        //     $array = array('foo', 'bar', $string);
        //
        //     // Save as we normally do, mr_cache will automatically serialize $array
        //     $this->helper->cache($array, 'array_example');
        // }
        // print_object($array);
        #### DEMO CODE ####

        echo $this->output->single_button($this->url->out(false, array('action' => 'delete')), 'Delete cache');
        echo $this->output->box_end();
        $this->print_footer();
    }

    /**
     * Delete cached item
     *
     * Demo of cache
     */
    public function delete_action() {
        // Remove the cached item
        $this->helper->cache->remove('time_string');

        $this->notify->add_string('cache reset', mr_notify::GOOD);
        redirect($this->url);
    }
}