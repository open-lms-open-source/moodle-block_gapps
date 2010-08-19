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
//    public function filter_init() {
//        $this->filter = new mr_html_filter($this->preferences, $this->url);
//        $this->filter->new_text('username', get_string('username'));
//    }

    /**
     * Table setup
     *
     * @return void
     */
    public function table_init() {
        $this->table = new mr_html_table($this->preferences, $this->url, 'username');

//       $this->table->add_column('username', get_string('username'))
//                    ->add_column('fullname', get_string('fullname'))
//                    ->add_column('email',    get_string('email'))
//                    ->add_column('lastsync', get_string('lastsync'))
//                    ->add_column('status',   get_string('status'));

//SELECT u.id, u.username, u.password, u.firstname, u.lastname, u.email, g.lastsync, g.status

        $this->table->add_column('u.username',     get_string('username'))
                    ->add_column('u.firstname',     get_string('firstname'))
                    ->add_column('u.lastname',     get_string('lastname'))
                    ->add_column('u.email',        get_string('email'))
                    ->add_column('g.lastsync',     get_string('lastsync','block_gapps'))
                    ->add_column('g.status', get_string('status','block_gapps'));

    }


    /**
     * Add a row to the table
     *
     * @param mixed $row The row to add
     * @return void
     */
    public function table_fill_row($row) {
        // add checkboxes to the username field
        $row->username = html_writer::checkbox("userids[]", $row->id, false, ' '.$row->username);

        //print_object($row);

        if ($row->lastsync > 0) {
            $lastsync = userdate($row->lastsync);
        } else {
            $lastsync = get_string('never');
        }
        $row->status = get_string("status".$row->status, 'block_gapps');


        $this->table->add_row($row);
        // CONVERTING THIS
        /*
         *                 $username = print_checkbox("userids[]", $user->id, false, s($user->username), s($user->username), '', true);

                // Define table contents based on hook
                switch ($hook) {
                    case 'users':
                        if ($user->lastsync > 0) {
                            $lastsync = userdate($user->lastsync);
                        } else {
                            $lastsync = get_string('never');
                        }

                        $table->add_data(array($username, fullname($user), $user->email, $lastsync, get_string("status$user->status", 'block_gdata')));
                        break;

                    case 'addusers':
                        $table->add_data(array($username, fullname($user), $user->email));
                        break;
                }
         */
    }

    
    /**
     * Report SQL
     */
    public function get_sql($fields, $filtersql, $filterparams) {
        global $CFG;

        // recoverying moodle users filter
        $filter = mr_var::instance()->get('blocks_gdata_filter');
        list($filtersql,$fparams) = $filter->get_sql_filter();  //get_sql_filter($extra='', array $params=null)
        //print_object($filter);die;

//
//        $sql = "SELECT $fields
//                  FROM {$CFG->prefix}user
//                 WHERE $filtersql";
//
//        // Get all users that are not in our sync table (block_gdata_gapps) that are not scheduled to be deleted
//        $select = "SELECT u.id, u.username, u.password, u.firstname, u.lastname, u.email, g.lastsync, g.status";
//        $from   = "FROM {$CFG->prefix}user u, {$CFG->prefix}block_gdata_gapps g";
//        $where  = "WHERE u.id = g.userid AND g.remove = 0 AND u.deleted = 0";
// $sql  = "u.id = g.userid AND g.remove = 0 AND u.deleted = 0";
        // NOT implemented yet.... TODO: 
        // SQL gets a little weird here because the filtersql doesn't do field aliases
//        if ($filtersql = $filter->get_sql_filter()) {
//            $where .= " AND u.id IN (SELECT id FROM {$CFG->prefix}user WHERE $filtersql)";
//        }


     //   $select = "SELECT u.id, u.username, u.password, u.firstname, u.lastname, u.email, g.lastsync, g.status";
        $from   = "FROM {user} u, {block_gdata_gapps} g";
        $where  = "WHERE u.id = g.userid AND g.remove = 0 AND u.deleted = 0";

        // count records needs to make fields COUNT(*) but I need the id passed for the checkboxes

    if (1 != substr_count($fields,'COUNT')) {
        $fields = 'u.id,'.$fields;
    }

    $sql = 'SELECT '.$fields.' FROM {user} WHERE u.id = g.userid AND g.remove = 0 AND u.deleted = 0 '.$filtersql;
    if (empty($filtersql) ) {
         $sql = 'SELECT '.$fields.' FROM {user} u, {block_gdata_gapps} g WHERE u.id = g.userid AND g.remove = 0 AND u.deleted = 0';//.$filtersql;
    }
    return array($sql,$fparams);
       // return array($sql, $filterparams);

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

        // Now collected the filter form code to wrap our report
        $filter = mr_var::instance()->get('blocks_gdata_filter');
        ob_start();
        $filter->display_add();   //$output .= $this->buffer(array($filter, 'display_add'));
        $filter->display_active();//$output .= $this->buffer(array($filter, 'display_active'));
        $filterform = ob_get_flush();


        $output  = $OUTPUT->box_start('boxaligncenter boxwidthwide');
        
        // if "Nothing to display" is inside the html then there are no elements and we don't need the form buttons...
        $nothingtodisplay = get_string('nothingtodisplay');
        if (1 == substr_count($tablehtml,$nothingtodisplay)) {
             $output .= $OUTPUT->notification(get_string('nothingtodisplay'),'');
             $output .= $OUTPUT->box_end();
             return $filterform.$tablehtml;
        }

        $totalusers = $this->max_selectable();//'[totalusersselected]';
        $allstr       = get_string('selectall',            'block_gapps');
        $nonestr      = get_string('selectnone',           'block_gapps');
        $submitstr    = get_string("submitbuttonusers",    'block_gapps');
        $submitallstr = get_string("submitbuttonallusers", 'block_gapps',$totalusers);
        $confirmstr   = get_string("confirmusers",         'block_gapps',$totalusers);

        $confirmstr   = addslashes_js($confirmstr); // deprecated function.. remove
        $options      = array(50 => 50, 100 => 100, 250 => 250, 500 => 500, 1000 => 1000);


        $action = $CFG->wwwroot.'/blocks/gapps/view.php?courseid='.$COURSE->id.'&controller=gsync&action=users';
        $output .= "<form class=\"userform\" id=\"userformid\" action=\"$action\" method=\"post\">";
        $output .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

        // so checkboxes are inside the form
        $output .= $tablehtml;

        $output .= "<p><a href=\"#\" title=\"$allstr\" onclick=\"select_all_in('FORM', 'userform', 'userformid'); return false;\">$allstr</a> / ";
        $output .= "<a href=\"#\" title=\"$nonestr\" onclick=\"deselect_all_in('FORM', 'userform', 'userformid'); return false;\">$nonestr</a></p>";
        $output .= "<input type=\"submit\" name=\"users\" value=\"$submitstr\" />&nbsp;&nbsp;";
        $output .= "<input type=\"submit\" name=\"allusers\" value=\"$submitallstr\" onclick=\"return confirm('$confirmstr');\" />";
        $output .= '</form><br />';

            // M2 no pop ups anymore...
            //$output .= popup_form("$CFG->wwwroot/blocks/gdata/index.php?hook=$hook&amp;pagesize=", $options, 'changepagesize',
            //                      $pagesize, '', '', '', true, 'self', get_string('pagesize', 'block_gdata'));

        $output .= $OUTPUT->box_end();

        return $filterform.$output;
    }

    /**
     * Total records all this page that the Select All XXX users can select
     */
     function max_selectable() {
        // obtain filter
        $filter = mr_var::instance()->get('blocks_gdata_filter');
        list($filtersql,$fparams) = $filter->get_sql_filter();
        $total = $this->count_records($filtersql); // <-- pass it a filter sql
        return $total;
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