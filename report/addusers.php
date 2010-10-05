<?php
/**
 * Copyright (C) 2010  Moodlerooms Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @copyright  Copyright (c) 2009 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://opensource.org/licenses/gpl-3.0.html     GNU Public License
 */
 
/**
 * A report for viewing user accounts
 *
 * @package block_gapps
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
     * Table setup
     *
     * @return void
     */
    public function table_init() {
        $this->url->params(array('controller' => 'gsync', 'action' => 'addusersview')); 
        $this->table = new mr_html_table($this->preferences, $this->url, 'username');
        $this->table->add_column('id', '', array('display' => false))
                    ->add_column('username',     get_string('username'))
                    ->add_column('firstname',     get_string('firstname'))
                    ->add_column('lastname',     get_string('lastname'))
                    ->add_column('email',        get_string('email'));
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
        $this->table->add_row($row);
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
        global $CFG,$SESSION;

        // recoverying moodle users filter
        $filter = mr_var::instance()->get('blocks_gdata_filter');
        list($filtersql,$fparams) = $filter->get_sql_filter();  //get_sql_filter($extra='', array $params=null)

        // Get all users that are not in our sync table (block_gdata_gapps) or
        // users that are in our sync table but are scheduled to be deleted

        // or admins that we don't want to sync
        $adminids = $this->return_adminids();

        $select = "SELECT ".$fields;
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

        // Can't find a better way to preserve this safely for passing to the controller handler
        if (!substr_count($fields,'COUNT') ) {
            $SESSION->blocks_gapps_report_addusers->fsql = $sql; // store for later option to submit all selected users
            $SESSION->blocks_gapps_report_addusers->fparams = $fparams;
        }

        return array($sql,$fparams);
    }

    public function output_wrapper($tablehtml) {
        global $COURSE,$CFG,$OUTPUT;

        // Now collected the filter form code to wrap our report
        $filter = mr_var::instance()->get('blocks_gdata_filter');
        ob_start();
        $filter->display_add();    //$output .= $this->buffer(array($filter, 'display_add'));
        $filter->display_active(); //$output .= $this->buffer(array($filter, 'display_active'));
        $filterform = ob_get_flush();


        $output  = $OUTPUT->box_start('boxaligncenter boxwidthwide');
        // Don't print the bottom form on empty tables
        if (1 == substr_count($tablehtml,get_string('nothingtodisplay'))) {
             $output .= $OUTPUT->notification('Nothing to display.','');
             $output .= $OUTPUT->box_end();
             return $tablehtml;
        }
        

        $totalusers = $this->max_selectable();
        $allstr       = get_string('selectall',               'block_gapps');
        $nonestr      = get_string('selectnone',              'block_gapps');
        $submitstr    = get_string("submitbuttonaddusers",    'block_gapps');
        $submitallstr = get_string("submitbuttonalladdusers", 'block_gapps',$totalusers);
        $confirmstr   = get_string("confirmaddusers",         'block_gapps',$totalusers);

        $confirmstr   = addslashes_js($confirmstr); // deprecated function.. remove
        //$options      = array(50 => 50, 100 => 100, 250 => 250, 500 => 500, 1000 => 1000);

        // Start of form
        $action = $CFG->wwwroot.'/blocks/gapps/view.php?courseid='.$COURSE->id.'&controller=gsync&action=addusers';
        $output .= "<form class=\"userform\" id=\"userformid2\" action=\"$action\" method=\"post\">";
        $output .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

        // table with it's checkboxes has to appear inside this form
        $output .= $tablehtml;

        $output .= "<p><a href=\"#\" title=\"$allstr\" onclick=\"select_all_in_element_with_id('userformid2', 'checked'); return false;\">$allstr</a> / ";
        $output .= "<a href=\"#\" title=\"$nonestr\" onclick=\"select_all_in_element_with_id('userformid2', null); return false;\">$nonestr</a></p>";
        $output .= "<input type=\"submit\" name=\"users\" value=\"$submitstr\" />&nbsp;&nbsp;";
        $output .= "<input type=\"submit\" name=\"allusers\" value=\"$submitallstr\" onclick=\"return confirm('$confirmstr');\" />";
        $output .= '</form><br />';

        // M2 no pop ups anymore...
        //$output .= popup_form("$CFG->wwwroot/blocks/gdata/index.php?hook=$hook&amp;pagesize=", $options, 'changepagesize',
        //                      $pagesize, '', '', '', true, 'self', get_string('pagesize', 'block_gdata'));
        
        $output .= $OUTPUT->box_end();

        return $output;
    }


    /**
     * Total records all this page that the Select All XXX users can select
     */
     function max_selectable() {
        // obtain filter
        // function is faulty
        $filter = mr_var::instance()->get('blocks_gdata_filter');
        list($filtersql,$fparams) = $filter->get_sql_filter();
        $total = $this->count_records($filtersql); // <-- pass it a filter sql

        //mr_var::instance()->remove('blocks_gdata_filter'); // don't keep duplicating filters
        return $total;
     }

     /**
      *
      * @return <type>
      */
     function get_fsql() {
        return $this->fsql;
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