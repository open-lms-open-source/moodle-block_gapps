<?php
/**
 * db controller
 *
 * @author Mark Nielsen
 * @version $Id$
 * @package blocks/helloworld
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class block_helloworld_controller_db extends mr_controller_block {
    /**
     * Default screen
     *
     * Demo of mr_db_table
     */
    public function view_action() {
        $this->print_header();

        echo $this->output->heading('Demo of mr_db_table');
        $this->helper->highlight(__CLASS__, __FUNCTION__);
        echo $this->output->box_start('generalbox boxaligncenter boxwidthnormal');

        #### DEMO CODE ####
        // Create a new instance for table 'block_helloworld'
        $table = new mr_db_table('block_helloworld');

        // Get table columns
        $result = $table->get_columns();
        $this->helper->dump($result, 'get_columns result');

        // Get detailed column information
        $result = $table->get_metacolumns();
        $this->helper->dump($result, 'get_metacolumns result');

        // Check for existing column
        $result = $table->column_exists('foo');
        $this->helper->dump($result, 'column_exists result');

        // Check non-existent column
        $result = $table->column_exists('baz');
        $this->helper->dump($result, 'column_exists result');

        // Generates a mr_db_record setup for this table
        $result = $table->record();
        $this->helper->dump($result, 'record result');

        // Form handling example:
        // } else if ($data = $mform->get_data()) {
        //     $table = new mr_db_table('tablename');
        //     $table->save($data);
        //     redirect(...);
        // }

        // $table has access to a lot of lib/dmllib.php methods
        // The following routes the call to $DB->get_records()
        $result = $table->get_records();
        $this->helper->dump($result, 'get_records result');
        #### DEMO CODE ####

        echo $this->output->box_end();
        $this->print_footer();
    }

    /**
     * Demo of mr_db_record
     */
    public function record_action() {
        global $DB;

        $this->print_header();

        echo $this->output->heading('Demo of mr_db_record');
        $this->helper->highlight(__CLASS__, __FUNCTION__);
        echo $this->output->box_start('generalbox boxaligncenter boxwidthnormal');

        #### DEMO CODE ####
        // Create a new record
        $record = new mr_db_record('block_helloworld');

        echo $this->output->heading('Insert demo');

        // Example: direct access
        $record = new mr_db_record('block_helloworld');
        $record->name = "It's";
        $record->foo  = 'a';
        $record->bar  = 'boy';
        $this->helper->dump($record, 'It\'s a boy result');  // Data is set

        // Example: Use set() and save()
        $record = new mr_db_record('block_helloworld');
        $data = array(
            'name' => "It's",
            'foo'  => 'a',
            'bar'  => 'boy',
            'ding' => 'pow', // This will be ignored by set() because it's an nonexistent field
        );
        $record->set($data)->save();
        $this->helper->dump($record, 'saved record result');  // id will be set

        // You can iterate a record! Woaaaaah!
        echo $this->output->heading('Iteration demo');
        foreach ($record as $key => $value) {
            print_object("$key = $value");
        }

        // You can count!
        echo $this->output->heading('Count demo');
        $this->helper->dump(count($record), 'count result');

        // You can access it like an array!
        echo $this->output->heading('Array access demo');
        $this->helper->dump($record['name'], 'array access result');

        // Example: working with an existing record
        $default = $DB->get_record('block_helloworld', array('id' => $record->id));
        $record  = new mr_db_record('block_helloworld', $default);
        // Do something to change the record otherwise save() will not do anything (which is good!)
        $record->name = 'Dingo';
        $record->save();

        $result = $DB->get_record('block_helloworld', array('id' => $record->id));
        $this->helper->dump($result, 'save result name = \'Dingo\'');

        // Example: delete a record
        $default = $DB->get_record('block_helloworld', array('id' => $record->id));
        $record  = new mr_db_record('block_helloworld', $default);
        $oldid   = $record->id;
        // Something happens and now you have to delete...
        $record->delete();

        // Let's see what's in the record now (should be empty)
        $this->helper->dump($record, 'how record looks after delete()');

        // See if the record really was deleted
        $result = $DB->get_record('block_helloworld', array('id' => $oldid));
        $this->helper->dump($result, 'record no longer exists');
        #### DEMO CODE ####

        echo $this->output->box_end();
        $this->print_footer();
    }

    /**
     * Demo of mr_db_queue
     */
    public function queue_action() {
        global $DB;

        $this->print_header();

        echo $this->output->heading('Demo of mr_db_queue');
        $this->helper->highlight(__CLASS__, __FUNCTION__);
        echo $this->output->box_start('generalbox boxaligncenter boxwidthnormal');

        #### DEMO CODE ####
        // Setup a new queue
        $queue = new mr_db_queue();

        // Load up the queue with inserts and deletes
        for ($i = 0; $i < 5; $i++) {
            $record = new mr_db_record('block_helloworld');
            $data = array(
                'name' => "It's",  // DO NOT SLASH IT!
                'foo'  => 'a',
                'bar'  => 'boy',
            );
            $record->set($data);
            $queue->add($record); // Could also add an array of records!
        }
        if ($records = $DB->get_records('block_helloworld')) {
            foreach ($records as $record) {
                $record = new mr_db_record('block_helloworld', $record);
                $record->queue_delete();
                $queue->add($record);
            }
        }
        // Show whats in the queue
        $this->helper->dump($queue, 'The loaded queue');

        // Flush the queue, should do everything in 2 transactions
        $queue->flush();

        // Show whats in the queue after flush
        $this->helper->dump($queue, 'The flushed queue');
        #### DEMO CODE ####

        echo $this->output->box_end();
        $this->print_footer();
    }
}