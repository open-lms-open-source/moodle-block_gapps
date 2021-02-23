<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy implementation for the Block Google Apps.
 *
 * @package    block_gapps
 * @author     Juan Felipe Martinez
 * @copyright  Copyright (c) 2018 Open LMS (https://www.openlms.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gapps\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\plugin\provider as request_provider;
use core_privacy\local\request\writer;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;

defined('MOODLE_INTERNAL') || die();

class provider implements metadata_provider, request_provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int           $userid       The user to search.
     * @return  contextlist   $contextlist  The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        $sql = "SELECT cx.id
                  FROM {context} cx
                  JOIN {tool_googleadmin_users} ga ON ga.userid = cx.instanceid
                 WHERE cx.instanceid = :userid AND cx.contextlevel = :contextlevel";

        $params = [
            'contextlevel' => CONTEXT_USER,
            'userid'  => $userid,
        ];

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $context = \context_user::instance($userid);

        $sql = "SELECT userid, email, password
                FROM {tool_googleadmin_users}
                WHERE userid = :userid";

        $userinfo = $DB->get_record_sql($sql, ['userid' => $userid]);

        if (!empty($userinfo->password)) {
            $userinfo->password = get_string('privacy_stored_password', 'block_gapps');
        }

        $subcontext = ['block_gapps'];

        writer::with_context($context)->export_data($subcontext, $userinfo);
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (empty($context)) {
            return;
        }

        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }

        $DB->delete_records('tool_googleadmin_users', ['userid' => $context->instanceid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $context = \context_user::instance($user->id);

        $contextids = $contextlist->get_contextids();

        if (!in_array($context->id, $contextids)) {
            return;
        }

        $DB->delete_records('tool_googleadmin_users', ['userid' => $user->id]);
    }

    /**
     * Returns meta data about this system.
     *
     * @param   collection $collection The initialised collection to add items to.
     * @return  collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {

        $metadatafields = [
            'userid' => 'privacy:metadata:tool_googleadmin_users:userid',
            'email' => 'privacy:metadata:tool_googleadmin_users:email',
            'password' => 'privacy:metadata:tool_googleadmin_users:password'
        ];

        $collection->add_database_table('tool_googleadmin_users', $metadatafields, 'privacy:metadata:tool_googleadmin_users');

        $externalfields = [
            'userid' => 'privacy:metadata:google_apps:userid',
            'email' => 'privacy:metadata:google_apps:email',
            'password' => 'privacy:metadata:google_apps:password'
        ];

        $collection->add_external_location_link('google_apps', $externalfields, 'privacy:metadata:google_apps');

        return $collection;
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        if ($DB->record_exists('tool_googleadmin_users', ['userid' => $context->instanceid])) {
            $userlist->add_user($context->instanceid);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();

        if ($context instanceof \context_user && in_array($context->instanceid, $userlist->get_userids())) {
            $DB->delete_records('tool_googleadmin_users', ['userid' => $context->instanceid]);
        }
    }
}
