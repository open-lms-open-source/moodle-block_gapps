<?php
/**
 * Copyright (C) 2009  Moodlerooms Inc.
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
 * Google Apps Service
 *
 * Helpful docs:
 *   - http://code.google.com/apis/apps/gdata_provisioning_api_v2.0_reference.html
 *   - http://code.google.com/apis/apps/gdata_provisioning_api_v2.0_reference_php.html
 *   - http://framework.zend.com/manual/en/zend.gdata.gapps.html
 *   - http://framework.zend.com/manual/en/zend.gdata.exception.html
 *   - http://framework.zend.com/manual/en/zend.gdata.html
 *
 * @author Mark Nielsen
 * @author modified by Chris Stones
 * @version $Id$
 * @package block_gapps
 */


require($CFG->dirroot.'/local/mr/bootstrap.php');
require_once($CFG->dirroot.'/blocks/gapps/exception.php');

/**
 * Main Gapps Sync Class. Nearly all the functionality is wrapped in here.
 */
class blocks_gapps_model_gsync {

    /**
     * Password hash function to send
     * to Google Apps.  Tells Google how
     * we are sending our passwords
     *
     * @var string
     */
    const PASSWORD_HASH_FUNCTION = 'MD5';

    /**
     * User sync status: Never been synced
     *
     * @var string
     */
    const STATUS_NEVER = 'never';

    /**
     * User sync status: Everythink is A-OK! <('')>
     *
     * @var string
     */
    const STATUS_OK = 'ok';

    /**
     * User sync status: Username conflict, either two
     * users have the same username in Moodle or in Google Apps
     *
     * @var string
     */
    const STATUS_USERNAME_CONFLICT = 'usernameconflict';

    /**
     * User sync status: an error occured
     * when attempting to make the user's
     * Google Apps account
     *
     * @var string
     */
    const STATUS_ACCOUNT_CREATION_ERROR = 'accountcreationerror';

    /**
     * User sync status: ERROR - catch all, try to use
     * or make more specific status
     *
     * @var string
     */
    const STATUS_ERROR = 'error';

    /**
     * Max number of HTTP clients that can
     * be ran at once
     *
     * @var int
     */
    const MAX_CLIENTS = 5;

    /**
     * HTTP client config
     *
     * @var array
     */
    protected $httpconfig = array('timeout' => 15);

    /**
     * Counters for counting account actions made by
     * the blocks_gapps_model_gsync class
     *
     * @var string
     */
    public $counts = array('created' => 0, 'updated' => 0, 'deleted' => 0, 'errors' => 0,'disabled' => 0, 'restored' => 0);

    /**
     * Required values from the config
     *
     * @var string
     */
    protected $requiredconfig = array('username', 'password', 'domain', 'usedomainemail', 'croninterval');

    /**
     * Configs
     *
     * @var object
     */
    protected $config;

    /**
     * Constructor - makes sure our
     * configs are in place and can
     * connect to Google Apps for us
     *
     * @param boolean $autoconnect Automatically connect to Google Apps
     * @return void
     */
    public function __construct($autoconnect = true) {
        global $CFG;
        mr_bootstrap::zend(); // set php search paths to find our zend lib
        // now make the zend includes
        require_once($CFG->dirroot.'/blocks/gapps/exception.php');
        require_once('Zend/Gdata/Gapps.php');
        require_once('Zend/Gdata/ClientLogin.php');



        if (!$config = get_config('blocks/gapps')) {
            throw new blocks_gapps_exception('notconfigured');
        }
        foreach ($this->requiredconfig as $name) {
            if (!isset($config->$name)) {
                throw new blocks_gapps_exception('missingrequiredconfig', 'block_gapps', $name);
            }
        }
        $this->config = $config;

        $autoconnect and $this->gapps_connect();
    }

    /**
     * Connect to Google Apps using
     * our config credentials
     *
     * @return void
     * @throws blocks_gapps_exception
     */
    public function gapps_connect() { 
        try {
            if (!empty($this->config->authorization)) {
                // Mimic what Zend_Gdata_ClientLogin::getHttpClient returns
                $headers['authorization'] = $this->config->authorization;
                $client = new Zend_Http_Client();
                $useragent = Zend_Gdata_ClientLogin::DEFAULT_SOURCE . ' Zend_Framework_Gdata/' . Zend_Version::VERSION;
                $client->setConfig(array(
                        'strictredirects' => true,
                        'useragent' => $useragent
                    )
                );
                $client->setHeaders($headers);
            } else {
                $client = Zend_Gdata_ClientLogin::getHttpClient("{$this->config->username}@{$this->config->domain}", $this->config->password, Zend_Gdata_Gapps::AUTH_SERVICE_NAME);
            }
            $this->service = new Zend_Gdata_Gapps($client, $this->config->domain);
        } catch (Zend_Gdata_App_AuthException $e) {
            throw new blocks_gapps_exception('authfailed');
        } catch (Zend_Gdata_App_Exception $e) {
            throw new blocks_gapps_exception('gappserror', 'block_gapps', $e->getMessage());
        }
    }

    /**
     * Create a user in Google Apps and
     * update our sync table accordingly
     *
     * @param object $moodleuser Object from {@link moodle_get_user} or {@link moodle_get_users}
     * @param boolean $checkexists Check if the user exists before creating
     * @return void
     */
    public function create_user($moodleuser, $checkexists = true) {
        try {
            // Add account to Google Apps
            $this->gapps_create_user($moodleuser->username, $moodleuser->firstname, $moodleuser->lastname, $moodleuser->password, $checkexists);

            // Update sync table
            $this->moodle_update_user($moodleuser);

        } catch (blocks_gapps_exception $e) {
            // Update users sync status
            $this->moodle_set_status($moodleuser->id, self::STATUS_ACCOUNT_CREATION_ERROR);

            throw new blocks_gapps_exception('gappserror', 'block_gapps', $e->getMessage());
        }
    }

    /**
     * Updates the user in Google Apps and in Moodle
     *
     * @param object $moodleuser Object from {@link moodle_get_user} or {@link moodle_get_users}
     * @param Zend_Gdata_Gapps_UserEntry $gappsuser User entry from Google Apps
     * @return void
     */
    public function update_user($moodleuser, $gappsuser) {
        $this->gapps_update_user($gappsuser, $moodleuser);
        $this->moodle_update_user($moodleuser);
    }

    /**
     * Deletes user from Google Apps and Moodle
     *
     * @param object $moodleuser Object from {@link moodle_get_user} or {@link moodle_get_users}
     * @param mixed $gappsuser User entry from Google Apps or NULL, but!
     *                         Always try to fetch user from Google Apps
     *                         prior to calling this method
     * @return void
     */
    public function delete_user($moodleuser, $gappsuser) {
        if ($gappsuser !== NULL) {
            $this->gapps_delete_user($gappsuser);
        }
        $this->moodle_delete_user($moodleuser->id);
    }

    /**
     * Disables the user on the Google Apps site (suspend)
     * Users with suspended accounts cannot sign in. Information, such as email or calendar
     * invitations, sent to suspended accounts will be blocked.
     *
     * Reference http://code.google.com/googleapps/domain/gdata_provisioning_api_v2.0_reference_php.html#Suspend_User_Example
     *           http://code.google.com/googleapps/domain/gdata_provisioning_api_v2.0_reference.html#client_lib_methods
     * @param object $moodleuser Object from {@link moodle_get_user} or {@link moodle_get_users}
     * @param mixed $gappsuser User entry from Google Apps
     */
    public function gapps_suspend_user($moodleuser, $gappsuser) {
        $username = '';

        
        if (is_string($gappsuser)) {
            // Param is the username string
            $gappsuser = $this->gapps_get_user($gappsuser);
        }

        // when a moodle user is complete dleted gappsuser isn't sent an object
        if ($gappsuser === NULL) {
            $this->moodle_delete_user($moodleuser->id);
            return;
        }

        if ( $gappsuser instanceof Zend_Gdata_Gapps_UserEntry) {
            $loginobj = $gappsuser->getLogin();
            $username = $loginobj->getUsername();
        } else {
            throw new blocks_gapps_exception('gappserror', 'block_gapps', 'gapps_suspend_user expects User Entry Object');
        }

        try {
            $this->service->suspendUser($username);
            $this->moodle_delete_user($moodleuser->id);
        } catch (Zend_Gdata_App_Exception $e) {
            throw new blocks_gapps_exception('gappserror', 'block_gapps', $e->getMessage());
        }
        $this->counts['disabled']++;
    }
    
    /**
     * Tells the Gapp service to restore (un-suspend) a user
     *
     * @param object $moodleuser Object from {@link moodle_get_user} or {@link moodle_get_users}
     * @param mixed $gappsuser User entry from Google Apps
     */
    public function gapps_restore_user($moodleuser, $gappsuser) {
        $username = '';
        
        if ($gappsuser === NULL) {
            return;
        }

        if ($gappsuser instanceof Zend_Gdata_Gapps_UserEntry) {
            $loginobj = $gappsuser->getLogin();
            $username = $loginobj->getUsername();
        } else {
            return; // don't break sync
        }

        try {
            $this->service->restoreUser($username);
        } catch (Zend_Gdata_App_Exception $e) {
            throw new blocks_gapps_exception('gappserror', 'block_gapps', $e->getMessage());
        }

        $this->counts['restored']++;
    }

    /**
     * Is the Gapps User Account Suspended? true/false
     *
     * @param object $gappsuser User entry from Google Apps
     * @return boolean whether or not the gapps user account is suspended
     */
    public function gapps_is_suspended($gappsuser) {
        if ($gappsuser instanceof Zend_Gdata_Gapps_UserEntry) {
            return $gappsuser->getLogin()->getSuspended();
        } else {
            throw new blocks_gapps_exception('gappserror', 'block_gapps', 'gapps_is_suspended expects a User Entry Object');
        }
    }


    /**
     * Renames a Google Apps account by deleting
     * the old one and creating a new one
     *
     * @param object $moodleuser Object from {@link moodle_get_user} or {@link moodle_get_users}
     * @param Zend_Gdata_Gapps_UserEntry $gappsuser User entry from Google Apps
     * @return void
     * @throws blocks_gapps_exception
     */
    public function rename_user($moodleuser, $gappsuser) {
        global $DB;
        if ($DB->record_exists('block_gdata_gapps', array('username' => $moodleuser->username))) {
            // Username conflict - keep old data and update status
            $this->moodle_set_status($moodleuser->id, self::STATUS_USERNAME_CONFLICT);

            throw new blocks_gapps_exception('usernameconflict', 'block_gapps', $moodleuser);
        } else {
            // Delete old account from Google Apps
            $this->gapps_delete_user($gappsuser);

            // Create the new user account
            $this->create_user($moodleuser);
        }
    }

    /**
     * Create a user in Google Apps
     *
     * @param string $username The username to be used, must not exist in Google Apps already
     * @param string $firstname User's first name
     * @param string $lastname User's last name
     * @param string $password User's password in MD5 hash form (Google Apps
     *                         says it also must be 6 chars in length and be ISO-8859-1)
     * @param boolean $checkexists Check if the user exists before creating
     * @return Zend_Gdata_Gapps_UserEntry
     * @throws blocks_gapps_exception
     */
    public function gapps_create_user($username, $firstname, $lastname, $password, $checkexists = true) {
        if ($checkexists) {
            if ($this->gapps_get_user($username) !== NULL) {
                // are they suspended? 
                throw new blocks_gapps_exception('useralreadyexists');
            }
        }
        try {
            $gappsuser = $this->service->createUser($username, $firstname, $lastname, $password, self::PASSWORD_HASH_FUNCTION);
        } catch (Zend_Gdata_Gapps_ServiceException $e) {
            throw new blocks_gapps_exception('gappserror', 'block_gapps', (string) $e);
        } catch (Zend_Gdata_App_Exception $e) {
            throw new blocks_gapps_exception('gappserror', 'block_gapps', $e->getMessage());
        }

        $this->counts['created']++;

        return $gappsuser;
    }

    /**
     * Update a user in Google Apps
     *
     * @param Zend_Gdata_Gapps_UserEntry $gappsuser User entry from Google Apps
     * @param object $moodleuser Object from {@link moodle_get_user} or {@link moodle_get_users}
     * @return void
     * @throws blocks_gapps_exception
     */
    public function gapps_update_user($gappsuser, $moodleuser) {
        $save = false;

        // Restore a user that Moodle has added back to Sync
        if ($moodleuser->lastsync == 0 and $this->gapps_is_suspended($gappsuser) ) {
            $this->gapps_restore_user($moodleuser, $gappsuser);
        }

        if ($gappsuser->name->givenName != $moodleuser->firstname) {
            $gappsuser->name->givenName = $moodleuser->firstname;
            $save = true;
        }
        if ($gappsuser->name->familyName != $moodleuser->lastname) {
            $gappsuser->name->familyName = $moodleuser->lastname;
            $save = true;
        }
        if ($moodleuser->oldpassword != $moodleuser->password) {
            $gappsuser->login->password = $moodleuser->password;
            $gappsuser->login->hashFunctionName = self::PASSWORD_HASH_FUNCTION;
            $save = true;
        }

        // By using save flag we hopefully reduce
        // the number of saves actually called
        if ($save) {
            try {
                $gappsuser->save();
            } catch (Zend_Gdata_App_Exception $e) {
                throw new blocks_gapps_exception('gappserror', 'block_gapps', $e->getMessage());
            }

            $this->counts['updated']++;
        }
    }

    /**
     * Delete user from Google Apps
     *
     * @param mixed $param Either a Zend_Gdata_Gapps_UserEntry or a string that corresponds to the username
     * @return void
     * @throws blocks_gapps_exception
     */
    public function gapps_delete_user($param) {
        if (is_string($param)) {
            // Param is the username string
            $gappsuser = $this->gapps_get_user($param);

            if ($gappsuser === NULL) {
                return; // User doesn't exist
            }
        } else if ($param instanceof Zend_Gdata_Gapps_UserEntry) {
            // Param is the user entry
            $gappsuser = $param;
        } else {
            throw new blocks_gapps_exception('invalidparameter');
        }

        try {
            $gappsuser->delete();
        } catch (Zend_Gdata_App_Exception $e) {
            throw new blocks_gapps_exception('gappserror', 'block_gapps', $e->getMessage());
        }
        $this->counts['deleted']++;
    }

    /**
     * Get a user from Google Apps
     *
     * @param string $username The username of the user in Google Apps
     * @return Zend_Gdata_Gapps_UserEntry or NULL if user not found
     * @throws blocks_gapps_exception
     */
    public function gapps_get_user($username) {
        try {
            $gappsuser = $this->service->retrieveUser($username);
        } catch (Zend_Gdata_App_Exception $e) {
            throw new blocks_gapps_exception('gappserror', 'block_gapps', $e->getMessage());
        }
        return $gappsuser;
    }

    /**
     * Get a page of users from Google Apps
     *
     * @return Zend_Gdata_Gapps_UserFeed
     * @throws blocks_gapps_exception
     */
    public function gapps_get_users() {
        try {
            return $this->service->retrieveAllUsers();
        } catch (Zend_Gdata_Gapps_ServiceException $e) {
            throw new blocks_gapps_exception('gappserror', 'block_gapps', (string) $e);
        } catch (Zend_Gdata_App_Exception $e) {
            throw new blocks_gapps_exception('gappserror', 'block_gapps', $e->getMessage());
        }
        return $gappsusers;
    }

    /**
     * Create a user in Moodle's block_gdata_gapps table
     *
     * @param object $user Moodle user record from user table
     * @return object
     * @throws blocks_gapps_exception
     */
    public function moodle_create_user($user) {
        global $DB;
        // Check for existing record first
        if ($record = $DB->get_record('block_gdata_gapps',array( 'userid'=> $user->id))) {
            if ($record->remove == 1) {
                // Was set to be removed... enable it and leave other fields unchanged
                if (!$DB->set_field('block_gdata_gapps', 'remove', 0, array('id'=> $record->id))) {
                    throw new blocks_gapps_exception('setfieldfailed');
                }
            } else {
                // OK, double insert - throw error
                throw new blocks_gapps_exception('useralreadyexists');
            }
        } else {
            // Inserting new - don't allow duplicate usernames as Gapps will not allow it anyways
            if ($DB->record_exists('block_gdata_gapps', array('username'=> $user->username))) {
                throw new blocks_gapps_exception('usernamealreadyexists', 'block_gapps', $user->username);
            }

            $record           = new stdClass;
            $record->userid   = $user->id;
            $record->username = $user->username;
            $record->password = $user->password;
            $record->remove   = 0;
            $record->lastsync = 0;
            $record->status   = self::STATUS_NEVER;

            if (!$DB->insert_record('block_gdata_gapps', $record)) {
                throw new blocks_gapps_exception('insertfailed');
            }
        }
    }

    /**
     * Update a user in Moodle's block_gdata_gapps table and
     * potentially modify the user's email in Moodle's
     * user table
     *
     * @param object $moodleuser Object from {@link moodle_get_user} or {@link moodle_get_users}
     * @param string $status User's sync status, please use one of the STATUS constants defined in this class
     * @return void
     * @throws blocks_gapps_exception
     */
    public function moodle_update_user($moodleuser, $status = self::STATUS_OK) {
        global $DB;

        $record           = new stdClass;
        $record->id       = $moodleuser->id;
        $record->username = $moodleuser->username;
        $record->password = $moodleuser->password;
        $record->lastsync = time();
        $record->status   = $status;

        if (!$DB->update_record('block_gdata_gapps', $record)) {
            throw new blocks_gapps_exception('failedtoupdatesyncrecord', 'block_gapps', $record->id);
        }

        if ($this->config->usedomainemail) {
            $domainemail = "$moodleuser->username@{$this->config->domain}";

            if ($moodleuser->email != $domainemail) {
                if (!$DB->set_field('user', 'email', $domainemail, array('id'=> $moodleuser->userid))) {
                    throw new blocks_gapps_exception('failedtoupdateemail');
                }
            }
        }
    }

    /**
     * Sets a user to be deleted on the next sync
     *
     * @param int $userid ID of the user to be removed
     * @return void
     * @throws blocks_gapps_exception
     */
    public function moodle_remove_user($userid) {
        global $DB;
        if ($id = $DB->get_field('block_gdata_gapps', 'id', array('userid' => $userid))) {
            if (!$DB->set_field('block_gdata_gapps', 'remove', 1, array('id' => $id))) { 
                throw new blocks_gapps_exception('setfieldfailed');
            }
        } else {
            throw new blocks_gapps_exception('invalidparameter');
        }
    }

    /**
     * Delete a user from Moodle's block_gdata_gapps table
     *
     * @param int $id The record ID
     * @return void
     */
    public function moodle_delete_user($id) {
        global $DB;
        if (!$DB->delete_records('block_gdata_gapps', array('id' => $id))) {
            throw new blocks_gapps_exception('failedtodeletesyncrecord', 'block_gapps', $id);
        }
    }

    /**
     * Get Moodle user - this object is used
     * by other methods in this class.
     *
     * @param int $userid ID of the user to grab - must exist in block_gdata_gapps and user tables
     * @return object
     * @throws blocks_gapps_exception
     **/
    public function moodle_get_user($userid) {
        global $CFG,$DB;

        // TODO: Moodle lacks a clean upgrade path for sql code that used the AS ($as = sql_as();)
        $moodleuser = $DB->get_record_sql("SELECT g.username AS oldusername, g.id, g.userid,
                                             g.password AS oldpassword, g.remove, g.lastsync,
                                             g.status, u.username, u.password, u.firstname,
                                             u.lastname, u.email, u.deleted,u.auth
                                        FROM {user} u,
                                             {block_gdata_gapps} g
                                       WHERE u.id = g.userid
                                         AND g.userid = ?",array($userid));

        if ($moodleuser === false) {
            throw new blocks_gapps_exception('nouserfound');
        }
        return $moodleuser;
    }

    /**
     * Get all Moodle users that need to be synced - the
     * objects returned are used by other methods in this class
     *
     * @return ADODB RecordSet
     * @throws blocks_gapps_exception
     **/
    public function moodle_get_users() {
        global $CFG,$DB;

        // TODO: Moodle lacks a clean upgrade path for sql code that used the AS ($as = sql_as();)

        // Only grab those who are out of date according to our cron interval
        $timetocheck = time() - ($this->config->croninterval * MINSECS);

        // Filter Out Admins from sync option
        $adminids = $this->return_adminids();
        $adminfilter = " AND u.id NOT IN ($adminids)";
        if (!get_config('blocks/gapps','nosyncadmins')) {
            $adminfilter = '';
        }
        
        $rs = $DB->get_recordset_sql("SELECT g.username AS oldusername, g.id, g.userid,
                                        g.password AS oldpassword, g.remove, g.lastsync,
                                        g.status, u.username, u.password, u.firstname,
                                        u.lastname, u.email, u.deleted,u.auth
                                   FROM {user} u,
                                        {block_gdata_gapps} g
                                  WHERE u.id = g.userid
                                    AND g.lastsync < ?".$adminfilter,array($timetocheck));

        if ($rs === false) {
            throw new blocks_gapps_exception('nousersfound');
        }
        return $rs;
    }

    /**
     * Returns siteadmins as a comma seperated string
     */
    private function return_adminids() {
        global $CFG;
        $admins = get_admins();
        $adminids = array_keys($admins);
        return implode(',',$adminids);
    }

    /**
     * Set the sync status for a Moodle user
     *
     * @param int $id The record ID
     * @param string $status User's sync status, please use one of the STATUS constants defined in this class
     **/
    public function moodle_set_status($id, $status) {
        global $DB;
        if (!$DB->set_field('block_gdata_gapps', 'status', $status, array('id' => $id))) {
            throw new blocks_gapps_exception('setfieldfailed');
        }
    }

    /**
     * Sync a single Moodle user to Google Apps
     * This is almost the heart of the sync code. Think of it like
     * a train station where you switch all actions based on settings.
     *
     * Sync Rules:
     *   - If a user has been deleted in Moodle their Google Apps Account will be either disabled, deleted or left alone.
     *   - If a user is deleted in Moodle or removed from the sync process, then either do nothing, disable or delete from
     *     Google Apps based upon current admin configuration
     *   - If a user was removed from sync and now is returned to sync restore their google account
     *   - If a user has had their username renamed, delete
     *     the older username from Google Apps and create
     *     a new account with their new username
     *   - If a user does not have an account in Google Apps
     *     with their Moodle username, then create it
     *     or if their previous account was suspended restore it
     *   - Do not allow duplicate usernames to be used for
     *     users in Google Apps and in Moodle's block_apps table
     *   - Default, the user has an account in Google Apps
     *     check first name, last name and password for changes
     *     in Moodle, then update if necessary.
     *
     * @param object $moodleuser Object from {@link moodle_get_user} or {@link moodle_get_users}
     * @param mixed $gappsuser User entry from Google Apps or NULL
     * @param boolean $feedback Provide feedback or not
     * @return void
     * @throws blocks_gapps_exception
     **/
    public function sync_moodle_user_to_gapps($moodleuser, $gappsuser = NULL, $feedback = true) {
        if ($gappsuser === NULL) {
            $gappsuser = $this->gapps_get_user($moodleuser->username);
            add_to_log(SITEID, 'block_gapps', 'sync_func _get_user','', 'guser:NULL usr='.$moodleuser->username, 0,0);
        }

        try {
            // Based on criteria, we will either delete, rename, create
            // or update the user's account (in that order)

            $hdlsync = $this->config->handlegusersync;

            // if a moodle user is set to nologin they are removed from sync
            // and must be added back in manually if their auth is changed again
            if ($moodleuser->auth == 'nologin') {
                // We'll set remove here so the system can use it's preferences
                // to decide what to do about the removal of this user.
                $moodleuser->remove = 1;
            }

            if ($moodleuser->remove == 1 or $moodleuser->deleted == 1) {
                // When Moodle deletes an account, username is changed
                if ($moodleuser->username != $moodleuser->oldusername) {
                    $gappsuser = $moodleuser->oldusername;
                }

                // 0 do nothing upon remove or delete
                if ($hdlsync == 0 ) {
                    // Do nothing to the gapps account but must delete user from sync
                    $this->moodle_delete_user($moodleuser->id);
                    add_to_log(SITEID, 'block_gapps', 'sync mdl_delete_user','', 'Do nothing to gapps account usrid='.$moodleuser->id, 0,0);
                    
                } else if($hdlsync == 1 ) { // Disable GApps Acount
                    $this->gapps_suspend_user($moodleuser, $gappsuser);
                    add_to_log(SITEID, 'block_gapps', 'sync gapps_suspend_user','', 'usrid='.$moodleuser->id, 0,0);
                } else if($hdlsync == 2 ) { // Delete user from gapps 
                    $this->delete_user($moodleuser, $gappsuser);
                    $this->gapps_delete_user($moodleuser->username);
                    add_to_log(SITEID, 'block_gapps', 'sync delete_user','', 'usrid='.$moodleuser->id, 0,0);
                } else {
                    throw new blocks_gapps_exception('handlegappsyncvalueerror', 'block_gapps');
                }

            } else if ($moodleuser->username != $moodleuser->oldusername and $gappsuser !== NULL) {
                $this->rename_user($moodleuser, $gappsuser);
                add_to_log(SITEID, 'block_gapps', 'sync rename_user','', 'usrid='.$moodleuser->id, 0,0);

            } else if ($gappsuser === NULL) {
                $this->create_user($moodleuser, false);
                add_to_log(SITEID, 'block_gapps', 'sync create_user','', 'usrid='.$moodleuser->id, 0,0);
            } else {
                $this->update_user($moodleuser, $gappsuser);
                add_to_log(SITEID, 'block_gapps', 'sync update_user','', 'usrid='.$moodleuser->id, 0,0);
            }
        } catch (blocks_gapps_exception $e) {
            $feedback and mtrace($e->getMessage());

            $this->counts['errors']++;
        }
    }

    /**
     * Sync Moodle users to Google Apps
     *
     * @param int $expire Max execution time, pass zero for no timeout
     * @param boolean $feedback Provide feedback or not
     * @return void
     * @throws blocks_gapps_exception
     **/
    public function sync_moodle_to_gapps($expire = 0, $feedback = true) {
        global $CFG,$DB;

        $feedback and mtrace('Starting Moodle to Google Apps synchronization');

        // Save authorization header to share with HTTP clients
        $auth = $this->service->getStaticHttpClient()->getHeader('authorization');
        set_config('authorization', $auth, 'blocks/gapps');

        $expired = false;   // Flag for when we reached or max execution time
        $clients = array(); // Our HTTP clients

        // Loop through our users from Moodle
        $rs = $this->moodle_get_users();
        while ($rs->valid()) {
            $moodleuser = $rs->current();

            // Check expire time first
            if (!empty($expire) and time() > $expire) {
                $expired = true;
                break;
            }

            // Setup a new http client to process the user
            require_once($CFG->dirroot.'/blocks/gapps/model/http.php');

            $client = new blocks_gdata_http($CFG->wwwroot.'/blocks/gapps/view.php', $this->httpconfig);
            $client->setParameterPost('userid',$moodleuser->userid);
            $client->setParameterPost('controller','gsync');
            $client->setParameterPost('action','rest');


            $clients[] = $client;

            if (count($clients) >= self::MAX_CLIENTS) {
                $this->process_clients($clients, $feedback);
            }
            $rs->next();
        }
        $rs->close();

        // Process any left overs if we have time
        if (!$expired and !empty($clients)) {
            $this->process_clients($clients, $feedback);
        }

        // Want to use a new one next round
        unset_config('authorization', 'blocks/gapps');

        $feedback and mtrace('Number of Google Apps accounts deleted: '.$this->counts['deleted']);
        $feedback and mtrace('Number of Google Apps accounts disabled: '.$this->counts['disabled']);
        $feedback and mtrace('Number of Google Apps accounts restored: '.$this->counts['restored']);
        $feedback and mtrace('Number of Google Apps accounts created: '.$this->counts['created']);
        $feedback and mtrace('Number of Google Apps accounts updated: '.$this->counts['updated']);
        $feedback and mtrace('Number of errors: '.$this->counts['errors']);

        if ($expired) {
            $feedback and mtrace('Synchronization did not complete because the max execution time has expired.  Will continue synchronization on the next cron.');
        }

        $feedback and mtrace('End Moodle to Google Apps synchronization');
    }

    /**
     * Events API Hook for event 'user_updated'
     *
     * If the user is currently being synced to
     * Google Apps, then either update or create
     * their account in Google Apps whenever
     * they edit their account.
     *
     * @param object $user Moodle user record object
     * @return boolean
     **/
    public static function user_updated_event($user) {
        return self::event_handler('user_updated', $user);
    }

    /**
     * Events API Hook for event 'user_deleted'
     *
     * If the user is currently being synced to
     * Google Apps, delete their Google Apps
     * account and their sync record when
     * their account is deleted.
     *
     * @param object $user Moodle user record object
     * @return boolean
     **/
    public static function user_deleted_event($user) {
        return self::event_handler('user_deleted', $user);
    }

    /**
     * Events API Hook for event 'password_changed'
     *
     * If the user is currently being synced to
     * Google Apps, then update their password
     * for the Google Apps account
     *
     * At the moment, core Moodle doesn't trigger
     * this event, but docs say it exists.  So
     * keep this incase core implements it.
     *
     * @param object $user Moodle user record object
     * @return boolean
     **/
    public static function password_changed_event($user) {
        return self::event_handler('password_changed', $user);
    }

    /**
     * Events API Hook for event 'user_created'
     *
     * @param object $user moodle user object
     * @return boolean
     */
    public static function user_created_event($user) {
        return self::event_handler('user_created', $user);
    }

    /**
     * Custom auth/gsaml event handler
     * @param object $eventdata contains a user object and a username
     * @return boolean
     */
    public static function user_authenticated_event($eventdata) {
        return self::event_handler('auth_gsaml_user_authenticated', $eventdata);
    }
    
    /**
     * Event handler: processes all events
     *
     * @param string $event Name of the event
     * @param mixed $eventdata Data passed to the event
     * @return boolean
     **/
    private static function event_handler($event, $eventdata) {
        // Check first to see if events are allowed
        if (get_config('blocks/gapps', 'allowevents')) {
            add_to_log(SITEID, 'block_gapps', 'model:event_handler','', $event.' eventdata->id='.$eventdata->id, 0,0);
            switch ($event) {
                case 'auth_gsaml_user_authenticated':
                    $this->handle_gsaml_user_auth_event($eventdata);
                    break;
                case 'user_created':
                    $user = $eventdata;
                    try {
                        $gapps = new blocks_gapps_model_gsync();
                        $gapps->moodle_create_user($user);
                        $moodleuser = $gapps->moodle_get_user($eventdata->id);
                        $gappsuser  = $gapps->gapps_get_user($moodleuser->oldusername);

                        $gapps->sync_moodle_user_to_gapps($moodleuser, $gappsuser, false);

                    } catch (blocks_gapps_exception $e) {
                        // Do nothing on errors
                        add_to_log(SITEID, 'block_gapps', 'sync event_handler user_created','', 'ERROR:'.substr($e->getMessage(),0,190).' usr='.$eventdata->id, 0,0);
                    }

                    add_to_log(SITEID, 'block_gapps', 'sync event_handler','', 'user_created processed usr='.$eventdata->id, 0,0);
                    break;
                case 'user_deleted':
                case 'user_updated':
                case 'password_changed':
                    try {
                        $gapps      = new blocks_gapps_model_gsync();
                        $moodleuser = $gapps->moodle_get_user($eventdata->id);
                        $gappsuser  = $gapps->gapps_get_user($moodleuser->oldusername);

                        $gapps->sync_moodle_user_to_gapps($moodleuser, $gappsuser, false);

                    } catch (blocks_gapps_exception $e) {
                        // Do nothing on errors
                        add_to_log(SITEID, 'block_gapps', 'sync event_hndler user deluppws','', 'ERROR:'.substr($e->getMessage(),0,190).' usr='.$eventdata->id, 0,0);
                    }
                    add_to_log(SITEID, 'block_gapps', 'sync event_hndler user deluppws','','usr='.$eventdata->id, 0,0);
                    break;
            }
        }

        return true;
    }


    /**
     *  The Google Moodle system will create a Google Apps account
     *  If an account exists in Moodle but not Google Apps.
     *  Hence, verify that user has a google account. If not create one for them.
     *  NOTE: that this may take some time for google to process new users
     *
     *  This function handles the event fired from the auth/gsaml plugin via this
     *  function trigger_gsaml_user_auth_event($user, $username);
     */
    function handle_gsaml_user_auth_event($eventdata) {
        $username = $eventdata->username;
        $user = $eventdata->user;

        try {
            // obtain object and test connect to service
            $g_user = $this->gapps_get_user($username);
            if (empty($g_user)) {

                  // Admins are excluded from this syncing procedure
                 $admins = get_admins();
                 if (!array_key_exists($user->id,$admins) ) {
                     // Create Moodle User in the Gsync system
                     $this->moodle_create_user($user);

                     // Create google user
                     $m_user = $this->moodle_get_user($user->id);
                     $this->create_user($m_user);

                     add_to_log(SITEID, 'block_gapps', 'gsaml create usr','', $user->username, 0,0);
                 }
            }

        } catch (blocks_gapps_exception $e) {
            if (stripos($e->getMessage(),'Error 1100: UserDeletedRecently') ) {
                add_to_log(SITEID, 'block_gapps', 'handle gsaml err','', 'Error 1100: UserDeletedRecently', 0,0);
                // Google does not allow a user to be created after deletion until at least 5 days have passed.
            }
            debugging($e->getMessage());
        }
    }

    /**
     * Processes the response of an array
     * of HTTP clients. Calling this method
     * will cause the script to stall until
     * all clients are done.
     *
     * @param array $clients Array of blocks_gdata_http clients
     * @param boolean $feedback Provide feedback or not
     * @return void
     **/
    private function process_clients(&$clients, $feedback = true) {
        foreach ($clients as $client) {
            try {
                $response = $client->getResponse();
            } catch (Zend_Exception $e) {
                $feedback and mtrace('Failed to get HTTP client response: '.$e->getMessage());
                $this->counts['errors']++;
                continue;
            }

            if ($response->isError()) {
                $feedback and mtrace('Client response error: '.$response->getStatus().' '.$response->getMessage());
                $this->counts['errors']++;
            } else {
                $body = $response->getBody();
                $body = trim($body);

                if (!empty($body) and $body = @unserialize($body)) {
                    // Validate and process counts
                    if (!empty($body['counts']) and is_array($body['counts'])) {
                        foreach ($body['counts'] as $name => $count) {
                            if (array_key_exists($name, $this->counts) and is_numeric($count)) {
                                $this->counts[$name] += $count;
                            }
                        }
                    }
                    // Validate and process message
                    if ($feedback and !empty($body['message']) and
                        $message = clean_param($body['message'], PARAM_TEXT)) {

                        mtrace($message);
                    }
                } else {
                    $feedback and mtrace('Client response body invalid');
                    $this->counts['errors']++;
                }
            }
        }
        // Clear out clients array
        $clients = array();
    }



    /**
     * Rest function that emulates the rest page for accepting user accounts to sync
     **/
    function rest() {
        global $CFG;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Only accept POST requests
            $nomoodlecookie = true;
            require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');

            $response = array('counts' => array('errors' => 1), 'message' => '');

            if ($userid = optional_param('userid', 0, PARAM_INT)) {
                try {
                    // Want to capture output so we
                    // can return it properly
                    ob_start();

                    $gapps = new blocks_gapps_model_gsync(); /// the $gapps makes the code easier to read so leaving as gapps and not $this

                    $moodleuser = $gapps->moodle_get_user($userid);
                    $gapps->sync_moodle_user_to_gapps($moodleuser);

                    $output = ob_get_contents();
                    $output = trim($output);
                    ob_end_clean();

                    if (!empty($output)) {
                        $response['message'] = $output;
                    }
                    $response['counts'] = $gapps->counts;

                } catch (blocks_gapps_exception $e) {
                    $response['message'] = $e->getMessage();
                } catch (Zend_Exception $e) {
                    // Catch Zend_Exception just in case it happens
                    $response['message'] = $e->getMessage();
                }
            } else {
                $response['message'] = 'Invalid userid passed';
            }

            echo serialize($response);
        }

        die;
    }

    /**
     * Gsync Cron
     *
     * @param boolean $testrun a parameter to define if we are debugging our code or not
     * @return boolean true always true so we don't halt the main cron
     */
    function cron($forcerun = false) {
        global $CFG;

        require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');

        // Make sure this is not set
        unset_config('authorization', 'blocks/gapps');

        // The following code prevents the cron method
        // from being ran multiple times when the first
        // is still being executed
        $expire  = get_config('blocks/gapps', 'cronexpire');
        $started = get_config('blocks/gapps', 'cronstarted');

        if (!$forcerun) {
            if (empty($expire) or !is_numeric($expire)) {
                // Not set properly - go to default
                $expire = HOURSECS * 24;
            } else {
                $expire = HOURSECS * $expire;
            }
            if (!empty($started)) {
                $timetocheck = time() - $expire;

                if ($started > $timetocheck) {
                    mtrace('gapps cron haulted: cron is either still running or has not yet expired.  The cron will expire at '.userdate($started + $expire));
                    return true; // Still return true to prevent us from hitting this message every 5 minutes or so
                }
            }
        } else {
            $expire = HOURSECS * 1; // for testing small number of users only
        }

        // Be user to use the same time...
        $now = time();

        // Set the time we started
        set_config('cronstarted', $now, 'blocks/gapps');

        try {
            $gapps = new blocks_gapps_model_gsync();
            $gapps->sync_moodle_to_gapps($now + $expire);
        } catch (blocks_gapps_exception $e) {
            mtrace('Synchronization haulted: '.$e->getMessage());
        } catch (Zend_Exception $e) {
            mtrace('Synchronization haulted: '.$e->getMessage());
        }

        // Zero out our start time to free up the cron
        set_config('cronstarted', 0, 'blocks/gapps');

        // Always remove
        unset_config('authorization', 'blocks/gapps');

        // Always return true
        return true;
    }
    
} // END class blocks_gapps_model_gsync
