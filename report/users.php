<?php
/**
 * A report for viewing user accounts
 *
 * @package blocks/helloworld
 * @author Mark Nielsen
 */
class blocks_helloworld_report_users extends mr_report_abstract {
    /**
     * Report's init routine
     *
     * @return void
     */
    public function init() {
        $this->config->set(array(
            'export' => '**',
            'perpage' => true,
            'perpageopts' => array('all', 1, 5, 10, 50),
        ));
    }

    /**
     * The component string, used for get_string() calls
     *
     * @return string
     */
    public function get_component() {
        return 'block_helloworld';
    }

    /**
     * Filter setup
     *
     * @return void
     */
    public function filter_init() {
        $this->filter = new mr_html_filter($this->preferences, $this->url);
        $this->filter->new_text('username', get_string('username'));
    }

    /**
     * Table setup
     *
     * @return void
     */
    public function table_init() {
        $this->table = new mr_html_table($this->preferences, $this->url, 'username');
        $this->table->add_column('username', get_string('username'))
                    ->add_column('firstname', get_string('firstname'))
                    ->add_column('lastname', get_string('lastname'))
                    ->add_column('email', get_string('email'))
                    ->add_column('lastaccess', get_string('lastaccess'))
                    ->add_format('lastaccess', 'date')
                    ->add_format(array('username', 'firstname', 'lastname', 'email'), 'string');
    }

    /**
     * Report SQL
     */
    public function get_sql($fields, $filtersql, $filterparams) {
        $sql = "SELECT $fields
                  FROM {user}
                 WHERE $filtersql";

        return array($sql, $filterparams);
    }
}