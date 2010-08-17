<?php
/**
 * A report for viewing user accounts
 *
 * @package blocks/gapps
 * @author Chris Stones
 */
class blocks_gapps_report_addusers extends mr_report_abstract {
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



//SELECT u.id, u.username, u.password, u.firstname, u.lastname, u.email, g.lastsync, g.status
//            case 'addusers':
            //    $table->define_columns(array('username', 'fullname', 'email'));
            //    $table->define_headers(array(get_string('username'), get_string('fullname'), get_string('email')));
             //   break;
//  $table->add_data(array($username, fullname($user), $user->email));
        $this->table->add_column('u.username',     get_string('username'))
                    //->add_column('u.fullname',     get_string('fullname'))
                    ->add_column('u.firstname',     get_string('firstname'))
                    ->add_column('u.lastname',     get_string('lastname'))
                    ->add_column('u.email',        get_string('email'))
                    ->add_column('g.lastsync',     get_string('lastsync','block_gapps'))
                    ->add_column('g.status', get_string('status','block_gapps'));

    }


    /**
     * Returns site admins as a comma seperated string
     */
    private function return_adminids() {
        global $CFG;
        $admins = get_admins();
        $adminids = array_keys($admins);
        return implode(',',$adminids);
    }


    /**
     * Report SQL
     */
    public function get_sql($fields, $filtersql, $filterparams) {
        global $CFG;

        // recoverying moodle users filter
        $filter = mr_var::instance()->get('blocks_gdata_filter');
        list($filtersql,$fparams) = $filter->get_sql_filter();  //get_sql_filter($extra='', array $params=null)

        // Get all users that are not in our sync table (block_gdata_gapps) or
        // users that are in our sync table but are scheduled to be deleted

        // or admins that we don't want to sync
        $adminids = $this->return_adminids();

        // can't define it ilke this becaue the COUNT(*) will ruin it...
        
        $select = "SELECT id, username, password, firstname, lastname, email";

        $from   = "FROM {user}";

        if (get_config('blocks/gapps','nosyncadmins')) {
            // filter out admins from syncing
            $where  = "WHERE id NOT IN (SELECT userid FROM {block_gdata_gapps} WHERE remove = 0) AND deleted = 0 AND username != 'guest'
                       AND id NOT IN ($adminids)";
        } else { // no admin filtering
            $where  = "WHERE id NOT IN (SELECT userid FROM {block_gdata_gapps} WHERE remove = 0) AND deleted = 0 AND username != 'guest'";
        }

        // if filter sql exists..
        if (!empty($filtersql)) {
            $where .= " AND $filtersql";
        }


        $sql = $select.' '.$from.' '.$where;

        return array($sql,$fparams);
    }

    public function output_wrapper($tablehtml) {
        global $COURSE,$CFG,$OUTPUT;
        $formcode = '<br>where forms would show up';

        

        // Now collected the filter form code to wrap our report
        $filter = mr_var::instance()->get('blocks_gdata_filter');
        ob_start();
        $filter->display_add();
        //$filter->display_active();
        $filterform = ob_get_flush();

//$OUTPUT->box_start()
        $output  = $OUTPUT->box_start('boxaligncenter boxwidthwide');
        //$output .= $this->buffer(array($filter, 'display_add'));
        //$output .= $this->buffer(array($filter, 'display_active'));

        //if (empty($this->table->data)) {
            // Avoid printing the form on empty tables
        ///    $output .= $this->buffer(array($table, 'print_html'));
        //} else {
        $totalusers = '[totalusersselected]';
            $allstr       = get_string('selectall',            'block_gapps');
            $nonestr      = get_string('selectnone',           'block_gapps');
            $submitstr    = get_string("submitbuttonaddusers",    'block_gapps');
            $submitallstr = get_string("submitbuttonalladdusers", 'block_gapps',$totalusers);
            $confirmstr   = get_string("confirmusers",         'block_gapps');
            $confirmstr   = addslashes_js($confirmstr); // deprecated function.. remove
            $options      = array(50 => 50, 100 => 100, 250 => 250, 500 => 500, 1000 => 1000);

            $output .= "<form class=\"userform\" id=\"userformid\" action=\"$CFG->wwwroot/blocks/gapps/view.php?courseid=$COURSE->id&controller=gsync&action=users\" method=\"post\">";
            //$output .= '<input type="hidden" name="hook" value="'.$hook.'" />';
            $output .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            //$output .= $this->buffer(array($table, 'print_html'));
            $output .= "<p><a href=\"#\" title=\"$allstr\" onclick=\"select_all_in('FORM', 'userform', 'userformid'); return false;\">$allstr</a> / ";
            $output .= "<a href=\"#\" title=\"$nonestr\" onclick=\"deselect_all_in('FORM', 'userform', 'userformid'); return false;\">$nonestr</a></p>";
            $output .= "<input type=\"submit\" name=\"users\" value=\"$submitstr\" />&nbsp;&nbsp;";
            $output .= "<input type=\"submit\" name=\"allusers\" value=\"$submitallstr\" onclick=\"return confirm('$confirmstr');\" />";
            $output .= '</form><br />';

            // M2 no pop ups anymore...
            //$output .= popup_form("$CFG->wwwroot/blocks/gdata/index.php?hook=$hook&amp;pagesize=", $options, 'changepagesize',
            //                      $pagesize, '', '', '', true, 'self', get_string('pagesize', 'block_gdata'));
        //}
        $output .= $OUTPUT->box_end();

        return $filterform.$tablehtml.$output;
    }



    /**
     * Add a row to the table
     *
     * @param mixed $row The row to add
     * @return void
     */
    //public function table_fill_row($row) {
    //    $this->table->add_row($row);
    //}



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