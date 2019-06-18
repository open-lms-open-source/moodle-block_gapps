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
 * Testcase for Block Google Apps privacy implementation.
 *
 * @package    block_gapps
 * @author     Juan Felipe Martinez (juan.martinez@blackboard.com)
 * @copyright  Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\request\transform;
use block_gapps\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use \core_privacy\local\request\approved_userlist;


class block_gapps_privacy_provider_testcase extends provider_testcase {

    public function test_get_contexts_for_userid() {

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->assertEmpty(provider::get_contexts_for_userid($user->id));
        $credentials[] = (object)['userid' => $user->id, 'email' => 'test@email.com', 'password' => 'secret'];
        $this->create_credentials($credentials);
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        $usercontext = \context_user::instance($user->id);
        $this->assertEquals($usercontext->id, $contextlist->get_contextids()[0]);
    }

    public function test_export_user_data() {

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $credentials[] = (object)['userid' => $user->id, 'email' => 'test@email.com', 'password' => 'secret'];
        $this->create_credentials($credentials);
        $usercontext = \context_user::instance($user->id);
        $writer = writer::with_context($usercontext);
        $approvedlist = new approved_contextlist($user, 'block_gapps', [$usercontext->id]);
        provider::export_user_data($approvedlist);
        provider::export_user_data($approvedlist);
        $data = $writer->get_data(['block_gapps']);

        $this->assertEquals($data->userid, $user->id);
        $this->assertEquals($data->email, $credentials[0]->email);
        $this->assertEquals($data->password, get_string('privacy_stored_password', 'block_gapps'));

    }

    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        $this->resetAfterTest();
        list($user1, $user2) = $this->delete_setup();
        $this->assertEquals(2, $DB->count_records('tool_googleadmin_users', []));

        $user1context = \context_user::instance($user1->id);
        provider::delete_data_for_all_users_in_context($user1context);
        $this->assertEquals(0, $DB->count_records('tool_googleadmin_users', ['userid' => $user1->id]));
        $this->assertEquals(1, $DB->count_records('tool_googleadmin_users', ['userid' => $user2->id]));

        $user2context = \context_user::instance($user2->id);
        provider::delete_data_for_all_users_in_context($user2context);

        $this->assertEquals(0, $DB->count_records('tool_googleadmin_users', []));
    }

    public function test_delete_data_for_user() {
        global $DB;
        $this->resetAfterTest();
        list($user1, $user2) = $this->delete_setup();
        $this->assertEquals(2, $DB->count_records('tool_googleadmin_users', []));

        $user1context = \context_user::instance($user1->id);
        $approvedcontextlist = new approved_contextlist($user1, 'block_gapps', [$user1context->id]);
        provider::delete_data_for_user($approvedcontextlist);

        $this->assertEquals(0, $DB->count_records('tool_googleadmin_users', ['userid' => $user1->id]));
        $this->assertEquals(1, $DB->count_records('tool_googleadmin_users', ['userid' => $user2->id]));

        $user2context = \context_user::instance($user2->id);
        $approvedcontextlist = new approved_contextlist($user2, 'block_gapps', [$user2context->id]);
        provider::delete_data_for_user($approvedcontextlist);

        $this->assertEquals(0, $DB->count_records('tool_googleadmin_users', []));
    }

    private function create_credentials($credentials) {
        global $DB;
        $DB->insert_records('tool_googleadmin_users', $credentials);
    }

    private function delete_setup() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $credentials[] = (object) ['userid' => $user1->id, 'email' => 'test1@email.com', 'password' => 'secret1'];
        $credentials[] = (object) ['userid' => $user2->id, 'email' => 'test2@email.com', 'password' => 'secret2'];

        $this->create_credentials($credentials);

        return [$user1, $user2];
    }

    public function test_get_users_in_context() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $credentials[] = (object)['userid' => $user->id, 'email' => 'test@email.com', 'password' => 'secret'];
        $this->create_credentials($credentials);
        $usercontext = \context_user::instance($user->id);

        $userlist = new \core_privacy\local\request\userlist($usercontext, 'block_gapps', [$usercontext->id]);
        provider::get_users_in_context($userlist);
        $this->assertCount(1, $userlist->get_userids());
    }

    public function test_delete_data_for_users() {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $credentials[] = (object)['userid' => $user->id, 'email' => 'test@email.com', 'password' => 'secret'];
        $this->create_credentials($credentials);
        $usercontext = \context_user::instance($user->id);

        $approveduserlist = new \core_privacy\local\request\approved_userlist($usercontext, 'block_gapps', [$user->id]);
        provider::delete_data_for_users($approveduserlist);
        $this->assertEquals(0, $DB->count_records('tool_googleadmin_users', ['userid' => $user->id]));
    }
}
