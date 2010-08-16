<?php
/**
 * A report for viewing user accounts
 *
 * @package blocks/gapps
 * @author Chris Stones
 */
class blocks_gapps_report_users extends mr_report_abstract {
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
        return 'block_gapps';
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
                    ->add_column('fullname', get_string('fullname'))
                    ->add_column('email',    get_string('email'))
                    ->add_column('lastsync', get_string('lastsync'))
                    ->add_column('status',   get_string('status'));

    }

    /**
     * Report SQL
     */
    public function get_sql($fields, $filtersql, $filterparams) {
        global $CFG;
        
        $sql = "SELECT $fields
                  FROM {user}
                 WHERE $filtersql";

        // Get all users that are not in our sync table (block_gdata_gapps) that are not scheduled to be deleted
        $select = "SELECT u.id, u.username, u.password, u.firstname, u.lastname, u.email, g.lastsync, g.status";
        $from   = "FROM {$CFG->prefix}user u, {$CFG->prefix}block_gdata_gapps g";
        $where  = "WHERE u.id = g.userid AND g.remove = 0 AND u.deleted = 0";

        // NOT implemented yet.... TODO: 
        // SQL gets a little weird here because the filtersql doesn't do field aliases
//        if ($filtersql = $filter->get_sql_filter()) {
//            $where .= " AND u.id IN (SELECT id FROM {$CFG->prefix}user WHERE $filtersql)";
//        }


        return array($sql, $filterparams);

        /**
         *
     called with... list($select, $from, $where) = $this->get_sql($hook, $filter);


                // Get all users that are not in our sync table (block_gdata_gapps) that are not scheduled to be deleted
                $select = "SELECT u.id, u.username, u.password, u.firstname, u.lastname, u.email, g.lastsync, g.status";
                $from   = "FROM {$CFG->prefix}user u, {$CFG->prefix}block_gdata_gapps g";
                $where  = "WHERE u.id = g.userid AND g.remove = 0 AND u.deleted = 0";

                // SQL gets a little weird here because the filtersql doesn't do field aliases
                if ($filtersql = $filter->get_sql_filter()) {
                    $where .= " AND u.id IN (SELECT id FROM {$CFG->prefix}user WHERE $filtersql)";
                }

         */


    }

    public function output_wrapper($tablehtml) {
        global $COURSE,$CFG,$OUTPUT;
        $formcode = '<br>where forms would show up';

        $filter = $this->filter;

        $output  = print_box_start('boxaligncenter boxwidthwide', '', true);
        $output .= $this->buffer(array($filter, 'display_add'));
        $output .= $this->buffer(array($filter, 'display_active'));

        //if (empty($this->table->data)) {
            // Avoid printing the form on empty tables
        ///    $output .= $this->buffer(array($table, 'print_html'));
        //} else {
            $allstr       = get_string('selectall',            'block_gapps');
            $nonestr      = get_string('selectnone',           'block_gapps');
            $submitstr    = get_string("submitbutton$hook",    'block_gapps');
            $submitallstr = get_string("submitbuttonall$hook", 'block_gapps', $total);
            $confirmstr   = get_string("confirm$hook",         'block_gapps', $total);
            $confirmstr   = addslashes_js($confirmstr); // deprecated function.. remove
            $options      = array(50 => 50, 100 => 100, 250 => 250, 500 => 500, 1000 => 1000);

            $output .= "<form class=\"userform\" id=\"userformid\" action=\"$CFG->wwwroot/blocks/gapps/view.php?courseid=$COURSE->id&controller=gsync&action=users\" method=\"post\">";
            $output .= '<input type="hidden" name="hook" value="'.$hook.'" />';
            $output .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            $output .= $this->buffer(array($table, 'print_html'));
            $output .= "<p><a href=\"#\" title=\"$allstr\" onclick=\"select_all_in('FORM', 'userform', 'userformid'); return false;\">$allstr</a> / ";
            $output .= "<a href=\"#\" title=\"$nonestr\" onclick=\"deselect_all_in('FORM', 'userform', 'userformid'); return false;\">$nonestr</a></p>";
            $output .= "<input type=\"submit\" name=\"users\" value=\"$submitstr\" />&nbsp;&nbsp;";
            $output .= "<input type=\"submit\" name=\"allusers\" value=\"$submitallstr\" onclick=\"return confirm('$confirmstr');\" />";
            $output .= '</form><br />';

            // M2 no pop ups anymore...
            //$output .= popup_form("$CFG->wwwroot/blocks/gdata/index.php?hook=$hook&amp;pagesize=", $options, 'changepagesize',
            //                      $pagesize, '', '', '', true, 'self', get_string('pagesize', 'block_gdata'));
        //}
        $output .= print_box_end(true);

        return $tablehtml.$output;
    }


    /**
     * Assists with calling functions that do no return output
     *
     * @param string $callback First param is a callback
     * @param mixed $argX Keep passing arguments to pass to the callback
     * @return string
     **/
    function buffer() {
        $arguments = func_get_args();
        $callback  = array_shift($arguments);

        ob_start();
        call_user_func_array($callback, $arguments);
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }


}