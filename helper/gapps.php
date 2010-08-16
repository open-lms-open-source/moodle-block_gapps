<?php
/**
 * Highlight Helper
 *
 * @author Mark Nielsen
 * @package blocks/helloworld
 **/

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class blocks_gapps_helper_gapps extends mr_helper_abstract {




    /**
     * Password hash function to send
     * to Google Apps.  Tells Google how
     * we are sending our passwords
     *
     * @var string
     **/
    const PASSWORD_HASH_FUNCTION = 'MD5';

    /**
     * User sync status: Never been synced
     *
     * @var string
     **/
    const STATUS_NEVER = 'never';

    /**
     * User sync status: Everythink is A-OK! <('')>
     *
     * @var string
     **/
    const STATUS_OK = 'ok';

    /**
     * User sync status: Username conflict, either two
     * users have the same username in Moodle or in Google Apps
     *
     * @var string
     **/
    const STATUS_USERNAME_CONFLICT = 'usernameconflict';

    /**
     * User sync status: an error occured
     * when attempting to make the user's
     * Google Apps account
     *
     * @var string
     **/
    const STATUS_ACCOUNT_CREATION_ERROR = 'accountcreationerror';

    /**
     * User sync status: ERROR - catch all, try to use
     * or make more specific status
     *
     * @var string
     **/
    const STATUS_ERROR = 'error';

    /**
     * Max number of HTTP clients that can
     * be ran at once
     *
     * @var int
     **/
    const MAX_CLIENTS = 5;

    /**
     * HTTP client config
     *
     * @var array
     */
    protected $httpconfig = array('timeout' => 15);

    /**
     * Counters for counting account actions made by
     * the blocks_gdata_gapps class
     *
     * @var string
     **/
    public $counts = array('created' => 0, 'updated' => 0, 'deleted' => 0, 'errors' => 0,'disabled' => 0, 'restored' => 0);

    /**
     * Required values from the config
     *
     * @var string
     **/
    protected $requiredconfig = array('username', 'password', 'domain', 'usedomainemail', 'croninterval');

    /**
     * Configs
     *
     * @var object
     **/
    protected $config;

    /**
     * Constructor - makes sure our
     * configs are in place and can
     * connect to Google Apps for us
     *
     * @param boolean $autoconnect Automatically connect to Google Apps
     * @return void
     **/
 //   public function __construct($autoconnect = true) {
        // moved to helder direct function
//        if (!$config = get_config('blocks/gapps')) {
//            throw new blocks_gdata_exception('notconfigured');
//        }
//        foreach ($this->requiredconfig as $name) {
//            if (!isset($config->$name)) {
//                throw new blocks_gdata_exception('missingrequiredconfig', 'block_gapps', $name);
//            }
//        }
//        $this->config = $config;
//
//        $autoconnect and $this->gapps_connect();
//    }


//    public function direct() {
//        return 'Hello direct()!';
//    }

    
    /**
     *
     * @return void
     */
    public function direct($autoconnect = true) {
        global $OUTPUT,$CFG;

        mr_bootstrap::zend();
        //require_once('Zend/Reflection/Method.php');

//        $method = new Zend_Reflection_Method($class, $function);
//        $code   = str_replace('$this->helper->highlight(__CLASS__, __FUNCTION__);', '', $method->getBody());

        // gapps require these to work
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
         * @version $Id$
         * @package block_gdata
         **/

        /**
         * Set include path so Zend Library functions properly
         **/

//        $zendlibpath = $CFG->libdir.'/zend';
//        $includepath = get_include_path();
//        if (strpos($includepath, $zendlibpath) === false) {
//            set_include_path($includepath.PATH_SEPARATOR.$zendlibpath);
//        }

        /**
         * Dependencies
         **/

        require_once($CFG->dirroot.'/blocks/gapps/gdata/http.php');
        require_once($CFG->dirroot.'/blocks/gapps/gdata/exception.php');
//        require_once($CFG->dirroot.'/lib/zend/Zend/Gdata/Gapps.php');
//        require_once($CFG->dirroot.'/lib/zend/Zend/Gdata/ClientLogin.php');
        require_once('Zend/Gdata/Gapps.php');
        require_once('Zend/Gdata/ClientLogin.php');

        // gapps ob constructor code follows....
        if (!$config = get_config('blocks/gapps')) {
            throw new blocks_gdata_exception('notconfigured');
        }
        foreach ($this->requiredconfig as $name) {
            if (!isset($config->$name)) {
                throw new blocks_gdata_exception('missingrequiredconfig', 'block_gapps', $name);
            }
        }
        $this->config = $config;

        $autoconnect and $this->gapps_connect();

        return true; // make this return $self; ?? so that I can use $gapps->like_syntax();

    }


    
    /**
     * Connect to Google Apps using
     * our config credentials
     *
     * @return void
     * @throws blocks_gdata_exception
     **/
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
            throw new blocks_gdata_exception('authfailed');
        } catch (Zend_Gdata_App_Exception $e) {
            throw new blocks_gdata_exception('gappserror', 'block_gapps', $e->getMessage());
        }
    }

    /**
     * Create a user in Google Apps and
     * update our sync table accordingly
     *
     * @param object $moodleuser Object from {@link moodle_get_user} or {@link moodle_get_users}
     * @param boolean $checkexists Check if the user exists before creating
     * @return void
     **/
    public function create_user($moodleuser, $checkexists = true) {
        try {
            // Add account to Google Apps
            $this->gapps_create_user($moodleuser->username, $moodleuser->firstname, $moodleuser->lastname, $moodleuser->password, $checkexists);

            // Update sync table
            $this->moodle_update_user($moodleuser);

        } catch (blocks_gdata_exception $e) {
            // Update users sync status
            $this->moodle_set_status($moodleuser->id, self::STATUS_ACCOUNT_CREATION_ERROR);

            throw new blocks_gdata_exception('gappserror', 'block_gapps', $e->getMessage());
        }
    }

    /**
     * Updates the user in Google Apps and in Moodle
     *
     * @param object $moodleuser Object from {@link moodle_get_user} or {@link moodle_get_users}
     * @param Zend_Gdata_Gapps_UserEntry $gappsuser User entry from Google Apps
     * @return void
     **/
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
     **/
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
            throw new blocks_gdata_exception('gappserror', 'block_gapps', 'gapps_suspend_user expects User Entry Object');
        }

        try {
            $this->service->suspendUser($username);
            $this->moodle_delete_user($moodleuser->id);
        } catch (Zend_Gdata_App_Exception $e) {
            throw new blocks_gdata_exception('gappserror', 'block_gapps', $e->getMessage());
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
            throw new blocks_gdata_exception('gappserror', 'block_gapps', $e->getMessage());
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
            throw new blocks_gdata_exception('gappserror', 'block_gapps', 'gapps_is_suspended expects a User Entry Object');
        }
    }


    /**
     * Renames a Google Apps account by deleting
     * the old one and creating a new one
     *
     * @param object $moodleuser Object from {@link moodle_get_user} or {@link moodle_get_users}
     * @param Zend_Gdata_Gapps_UserEntry $gappsuser User entry from Google Apps
     * @return void
     * @throws blocks_gdata_exception
     **/
    public function rename_user($moodleuser, $gappsuser) {
        if (record_exists('block_gdata_gapps', 'username', $moodleuser->username)) {
            // Username conflict - keep old data and update status
            $this->moodle_set_status($moodleuser->id, self::STATUS_USERNAME_CONFLICT);

            throw new blocks_gdata_exception('usernameconflict', 'block_gapps', $moodleuser);
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
     * @throws blocks_gdata_exception
     **/
    public function gapps_create_user($username, $firstname, $lastname, $password, $checkexists = true) {
        if ($checkexists) {
            if ($this->gapps_get_user($username) !== NULL) {
                // are they suspended?
                throw new blocks_gdata_exception('useralreadyexists');
            }
        }
        try {
            $gappsuser = $this->service->createUser($username, $firstname, $lastname, $password, self::PASSWORD_HASH_FUNCTION);
        } catch (Zend_Gdata_Gapps_ServiceException $e) {
            throw new blocks_gdata_exception('gappserror', 'block_gapps', (string) $e);
        } catch (Zend_Gdata_App_Exception $e) {
            throw new blocks_gdata_exception('gappserror', 'block_gapps', $e->getMessage());
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
     * @throws blocks_gdata_exception
     **/
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
                throw new blocks_gdata_exception('gappserror', 'block_gapps', $e->getMessage());
            }

            $this->counts['updated']++;
        }
    }

    /**
     * Delete user from Google Apps
     *
     * @param mixed $param Either a Zend_Gdata_Gapps_UserEntry or a string that corresponds to the username
     * @return void
     * @throws blocks_gdata_exception
     **/
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
            throw new blocks_gdata_exception('invalidparameter');
        }

        try {
            $gappsuser->delete();
        } catch (Zend_Gdata_App_Exception $e) {
            throw new blocks_gdata_exception('gappserror', 'block_gapps', $e->getMessage());
        }
        $this->counts['deleted']++;
    }

    /**
     * Get a user from Google Apps
     *
     * @param string $username The username of the user in Google Apps
     * @return Zend_Gdata_Gapps_UserEntry or NULL if user not found
     * @throws blocks_gdata_exception
     **/
    public function gapps_get_user($username) {
        try {
            $gappsuser = $this->service->retrieveUser($username);
        } catch (Zend_Gdata_App_Exception $e) {
            throw new blocks_gdata_exception('gappserror', 'block_gapps', $e->getMessage());
        }
        return $gappsuser;
    }

    /**
     * Get a page of users from Google Apps
     *
     * @return Zend_Gdata_Gapps_UserFeed
     * @throws blocks_gdata_exception
     **/
    public function gapps_get_users() {
        try {
            return $this->service->retrieveAllUsers();
        } catch (Zend_Gdata_Gapps_ServiceException $e) {
            throw new blocks_gdata_exception('gappserror', 'block_gapps', (string) $e);
        } catch (Zend_Gdata_App_Exception $e) {
            throw new blocks_gdata_exception('gappserror', 'block_gapps', $e->getMessage());
        }
        return $gappsusers;
    }

    /**
     * Create a user in Moodle's block_gdata_gapps table
     *
     * @param object $user Moodle user record from user table
     * @return object
     * @throws blocks_gdata_exception
     **/
    public function moodle_create_user($user) {
        // Check for existing record first
        if ($record = get_record('block_gdata_gapps', 'userid', $user->id)) {
            if ($record->remove == 1) {
                // Was set to be removed... enable it and leave other fields unchanged
                if (!set_field('block_gdata_gapps', 'remove', 0, 'id', $record->id)) {
                    throw new blocks_gdata_exception('setfieldfailed');
                }
            } else {
                // OK, double insert - throw error
                throw new blocks_gdata_exception('useralreadyexists');
            }
        } else {
            // Inserting new - don't allow duplicate usernames as Gapps will not allow it anyways
            if (record_exists('block_gdata_gapps', 'username', $user->username)) {
                throw new blocks_gdata_exception('usernamealreadyexists', 'block_gapps', $user->username);
            }

            $record           = new stdClass;
            $record->userid   = $user->id;
            $record->username = $user->username;
            $record->password = $user->password;
            $record->remove   = 0;
            $record->lastsync = 0;
            $record->status   = self::STATUS_NEVER;

            if (!insert_record('block_gdata_gapps', $record)) {
                throw new blocks_gdata_exception('insertfailed');
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
     * @throws blocks_gdata_exception
     **/
    public function moodle_update_user($moodleuser, $status = self::STATUS_OK) {
        $record           = new stdClass;
        $record->id       = $moodleuser->id;
        $record->username = $moodleuser->username;
        $record->password = $moodleuser->password;
        $record->lastsync = time();
        $record->status   = $status;

        if (!update_record('block_gdata_gapps', $record)) {
            throw new blocks_gdata_exception('failedtoupdatesyncrecord', 'block_gapps', $record->id);
        }

        if ($this->config->usedomainemail) {
            $domainemail = "$moodleuser->username@{$this->config->domain}";

            if ($moodleuser->email != $domainemail) {
                if (!set_field('user', 'email', $domainemail, 'id', $moodleuser->userid)) {
                    throw new blocks_gdata_exception('failedtoupdateemail');
                }
            }
        }
    }

    /**
     * Sets a user to be deleted on the next sync
     *
     * @param int $userid ID of the user to be removed
     * @return void
     * @throws blocks_gdata_exception
     **/
    public function moodle_remove_user($userid) {
        if ($id = get_field('block_gdata_gapps', 'id', 'userid', $userid)) {
            if (!set_field('block_gdata_gapps', 'remove', 1, 'id', $id)) {
                throw new blocks_gdata_exception('setfieldfailed');
            }
        } else {
            throw new blocks_gdata_exception('invalidparameter');
        }
    }

    /**
     * Delete a user from Moodle's block_gdata_gapps table
     *
     * @param int $id The record ID
     * @return void
     **/
    public function moodle_delete_user($id) {
        if (!delete_records('block_gdata_gapps', 'id', $id)) {
            throw new blocks_gdata_exception('failedtodeletesyncrecord', 'block_gapps', $id);
        }
    }

    /**
     * Get Moodle user - this object is used
     * by other methods in this class.
     *
     * @param int $userid ID of the user to grab - must exist in block_gdata_gapps and user tables
     * @return object
     * @throws blocks_gdata_exception
     **/
    public function moodle_get_user($userid) {
        global $CFG;

        $as = sql_as();

        $moodleuser = get_record_sql("SELECT g.username $as oldusername, g.id, g.userid,
                                             g.password $as oldpassword, g.remove, g.lastsync,
                                             g.status, u.username, u.password, u.firstname,
                                             u.lastname, u.email, u.deleted,u.auth
                                        FROM {$CFG->prefix}user u,
                                             {$CFG->prefix}block_gdata_gapps g
                                       WHERE u.id = g.userid
                                         AND g.userid = $userid");

        if ($moodleuser === false) {
            throw new blocks_gdata_exception('nouserfound');
        }
        return $moodleuser;
    }

    /**
     * Get all Moodle users that need to be synced - the
     * objects returned are used by other methods in this class
     *
     * @return ADODB RecordSet
     * @throws blocks_gdata_exception
     **/
    public function moodle_get_users() {
        global $CFG;

        $as = sql_as();

        // Only grab those who are out of date according to our cron interval
        $timetocheck = time() - ($this->config->croninterval * MINSECS);

        // Filter Out Admins from sync option
        $adminids = $this->return_adminids();
        $adminfilter = " AND u.id NOT IN ($adminids)";
        if (!get_config('blocks/gapps','nosyncadmins')) {
            $adminfilter = '';
        }

        $rs = get_recordset_sql("SELECT g.username $as oldusername, g.id, g.userid,
                                        g.password $as oldpassword, g.remove, g.lastsync,
                                        g.status, u.username, u.password, u.firstname,
                                        u.lastname, u.email, u.deleted,u.auth
                                   FROM {$CFG->prefix}user u,
                                        {$CFG->prefix}block_gdata_gapps g
                                  WHERE u.id = g.userid
                                    AND g.lastsync < $timetocheck".$adminfilter);

        if ($rs === false) {
            throw new blocks_gdata_exception('nousersfound');
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
        if (!set_field('block_gdata_gapps', 'status', $status, 'id', $id)) {
            throw new blocks_gdata_exception('setfieldfailed');
        }
    }

    /**
     * Sync a single Moodle user to Google Apps
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
     * @throws blocks_gdata_exception
     **/
    public function sync_moodle_user_to_gapps($moodleuser, $gappsuser = NULL, $feedback = true) {
        if ($gappsuser === NULL) {
            $gappsuser = $this->gapps_get_user($moodleuser->username);
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

                } else if($hdlsync == 1 ) { // Disable GApps Acount
                    $this->gapps_suspend_user($moodleuser, $gappsuser);

                } else if($hdlsync == 2 ) { // Delete user
                    $this->delete_user($moodleuser, $gappsuser);

                } else {
                    throw new blocks_gdata_exception('handlegappsyncvalueerror', 'block_gapps');
                }

            } else if ($moodleuser->username != $moodleuser->oldusername and $gappsuser !== NULL) {
                $this->rename_user($moodleuser, $gappsuser);

            } else if ($gappsuser === NULL) {
                $this->create_user($moodleuser, false);
            } else {
                $this->update_user($moodleuser, $gappsuser);
            }
        } catch (blocks_gdata_exception $e) {
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
     * @throws blocks_gdata_exception
     **/
    public function sync_moodle_to_gapps($expire = 0, $feedback = true) {
        global $CFG;

        $feedback and mtrace('Starting Moodle to Google Apps synchronization');

        // Save authorization header to share with HTTP clients
        $auth = $this->service->getStaticHttpClient()->getHeader('authorization');
        set_config('authorization', $auth, 'blocks/gapps');

        $expired = false;   // Flag for when we reached or max execution time
        $clients = array(); // Our HTTP clients

        // Loop through our users from Moodle
        $rs = $this->moodle_get_users();
        while ($moodleuser = rs_fetch_next_record($rs)) {
            // Check expire time first
            if (!empty($expire) and time() > $expire) {
                $expired = true;
                break;
            }

            // Setup a new http client to process the user
            $client = new blocks_gdata_http($CFG->wwwroot.'/blocks/gdata/rest.php', $this->httpconfig);
            $client->setParameterPost('userid', $moodleuser->userid);
            $client->request('POST');

            $clients[] = $client;

            if (count($clients) >= self::MAX_CLIENTS) {
                $this->process_clients($clients, $feedback);
            }
        }
        rs_close($rs);

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
        return self::event_handler('user_deleted', $user);
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
     * Event handler: processes all events
     *
     * @param string $event Name of the event
     * @param mixed $eventdata Data passed to the event
     * @return boolean
     **/
    private static function event_handler($event, $eventdata) {
        // Check first to see if events are allowed
        if (get_config('blocks/gapps', 'allowevents')) {

            switch ($event) {
                case 'user_deleted':
                case 'user_updated':
                case 'password_changed':
                    try {
                        $gapps      = new blocks_gdata_gapps();
                        $moodleuser = $gapps->moodle_get_user($eventdata->id);
                        $gappsuser  = $gapps->gapps_get_user($moodleuser->oldusername);

                        $gapps->sync_moodle_user_to_gapps($moodleuser, $gappsuser, false);

                    } catch (blocks_gdata_exception $e) {
                        // Do nothing on errors
                    }
                    break;
            }
        }

        return true;
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
 // END class blocks_gdata_gapps


/////////////////////////////////////////////////////////////
// brought in from blocks_gdata.php
/////////////////////////////////////////////////////////////


    /**
     * Helper method, displays a table
     * of users with checkboxes next to them.
     * Also includes a submit button to take
     * action on those users.
     *
     * @param string $hook The calling hook
     * @return string
     * @todo Not in love with this method, but it works
     **/
    function display_user_table($hook) {
        global $CFG,$DB,$OUTPUT;

        require_once($CFG->libdir.'/tablelib.php');

        $pagesize = optional_param('pagesize', 50, PARAM_INT);

        $table  = new flexible_table("blocks-gdata-$hook");
        $filter = $this->create_filter($hook, $pagesize);

        // Define columns based on hook
        switch($hook) {
            case 'users':
                $table->define_columns(array('username', 'fullname', 'email', 'lastsync', 'status'));
                $table->define_headers(array(get_string('username'), get_string('fullname'), get_string('email'), get_string('lastsync', 'block_gapps'), get_string('status')));
                break;

            case 'addusers':
                $table->define_columns(array('username', 'fullname', 'email'));
                $table->define_headers(array(get_string('username'), get_string('fullname'), get_string('email')));
                break;
        }

        $table->define_baseurl("$CFG->wwwroot/blocks/gdata/index.php?hook=$hook&amp;pagesize=$pagesize");
        
        $table->pageable(true);
        $table->sortable(true, 'username', SORT_DESC);
        $table->set_attribute('width', '100%');
        $table->set_attribute('class', 'flexible generaltable generalbox');
        $table->column_style('action', 'text-align', 'center');
        $table->setup();

        list($select, $from, $where) = $this->get_sql($hook, $filter);



        $total = $DB->count_records_sql("SELECT COUNT(*) $from $where");
/*
 * SELECT COUNT(*) FROM mdl_user u, mdl_block_gdata_gapps g WHERE u.id = g.userid AND g.remove = 0 AND u.deleted = 0 AND u.id IN (SELECT id FROM mdl_user WHERE Array)
[array (
)]
 */
        //$total = count_records_sql("SELECT COUNT(*) $from $where");

        $table->pagesize($pagesize, $total);

        if ($users = $DB->get_records_sql("$select $from $where ORDER BY ".$table->get_sql_sort(),null, $table->get_page_start(), $table->get_page_size())) {
            foreach ($users as $user) {
                $username = print_checkbox("userids[]", $user->id, false, s($user->username), s($user->username), '', true);

                // Define table contents based on hook
                switch ($hook) {
                    case 'users':
                        if ($user->lastsync > 0) {
                            $lastsync = userdate($user->lastsync);
                        } else {
                            $lastsync = get_string('never');
                        }

                        $table->add_data(array($username, fullname($user), $user->email, $lastsync, get_string("status$user->status", 'block_gapps')));
                        break;

                    case 'addusers':
                        $table->add_data(array($username, fullname($user), $user->email));
                        break;
                }
            }
        }

        $output  = $OUTPUT->box_start('boxaligncenter boxwidthwide');
        $output .= $this->buffer(array($filter, 'display_add'));
        $output .= $this->buffer(array($filter, 'display_active'));

        if (empty($table->data)) {
            // Avoid printing the form on empty tables
            $output .= $this->buffer(array($table, 'print_html'));
        } else {
            $allstr       = get_string('selectall', 'block_gapps');
            $nonestr      = get_string('selectnone', 'block_gapps');
            $submitstr    = get_string("submitbutton$hook", 'block_gapps');
            $submitallstr = get_string("submitbuttonall$hook", 'block_gapps', $total);
            $confirmstr   = get_string("confirm$hook", 'block_gapps', $total);
            $confirmstr   = addslashes_js($confirmstr);
            $options      = array(50 => 50, 100 => 100, 250 => 250, 500 => 500, 1000 => 1000);

            $output .= "<form class=\"userform\" id=\"userformid\" action=\"$CFG->wwwroot/blocks/gdata/index.php\" method=\"post\">";
            $output .= '<input type="hidden" name="hook" value="'.$hook.'" />';
            $output .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            $output .= $this->buffer(array($table, 'print_html'));
            $output .= "<p><a href=\"#\" title=\"$allstr\" onclick=\"select_all_in('FORM', 'userform', 'userformid'); return false;\">$allstr</a> / ";
            $output .= "<a href=\"#\" title=\"$nonestr\" onclick=\"deselect_all_in('FORM', 'userform', 'userformid'); return false;\">$nonestr</a></p>";
            $output .= "<input type=\"submit\" name=\"users\" value=\"$submitstr\" />&nbsp;&nbsp;";
            $output .= "<input type=\"submit\" name=\"allusers\" value=\"$submitallstr\" onclick=\"return confirm('$confirmstr');\" />";
            $output .= '</form><br />';
            $output .= popup_form("$CFG->wwwroot/blocks/gdata/index.php?hook=$hook&amp;pagesize=", $options, 'changepagesize',
                                  $pagesize, '', '', '', true, 'self', get_string('pagesize', 'block_gapps'));
        }
        $output .= $OUTPUT->box_end(true);

        return $output;
    }

    /**
     * Create the user filter
     *
     * @param string $hook The calling hook
     * @param int $pagesize Page size
     * @return user_filtering
     * @warning new user_filtering can clear $_POST
     **/
    function create_filter($hook, $pagesize = 50) {
        global $CFG;

        require_once($CFG->dirroot.'/user/filters/lib.php');

        return new user_filtering(NULL, $CFG->wwwroot.'/blocks/gdata/index.php', array('hook' => $hook, 'pagesize' => $pagesize));
    }




    /**
     * Generate SQL for querying user view
     * data
     *
     * All queries must return id, username and
     * password fields from the user table.
     *
     * @param string $hook The calling hook
     * @param user_filtering $filter User filter form
     * @return array
     **/
    function get_sql($hook, $filter = NULL) {
        global $CFG;

        $select = $from = $where = '';

        if ($filter === NULL) {
            $filter = $this->create_filter($hook);
        }

        switch($hook) {
            case 'users':
                // Get all users that are not in our sync table (block_gdata_gapps) that are not scheduled to be deleted
                $select = "SELECT u.id, u.username, u.password, u.firstname, u.lastname, u.email, g.lastsync, g.status";
                $from   = "FROM {$CFG->prefix}user u, {$CFG->prefix}block_gdata_gapps g";
                $where  = "WHERE u.id = g.userid AND g.remove = 0 AND u.deleted = 0";

                // SQL gets a little weird here because the filtersql doesn't do field aliases
                if ($filtersql = $filter->get_sql_filter()) {
                    $where .= " AND u.id IN (SELECT id FROM {$CFG->prefix}user )";// WHERE $filtersql)"; // ChrisStones: Temp change for test
                }
                break;

            case 'addusers':
                // Get all users that are not in our sync table (block_gdata_gapps) or
                // users that are in our sync table but are scheduled to be deleted

                // or admins that we don't want to sync
                $adminids = $this->return_adminids();

                $select = "SELECT id, username, password, firstname, lastname, email";
                $from   = "FROM {$CFG->prefix}user";

                if (get_config('blocks/gdata','nosyncadmins')) {
                    // filter out admins from syncing
                    $where  = "WHERE id NOT IN (SELECT userid FROM {$CFG->prefix}block_gdata_gapps WHERE remove = 0) AND deleted = 0 AND username != 'guest'
                               AND id NOT IN ($adminids)";
                } else { // no admin filtering
                    $where  = "WHERE id NOT IN (SELECT userid FROM {$CFG->prefix}block_gdata_gapps WHERE remove = 0) AND deleted = 0 AND username != 'guest'";
                }

                if ($filtersql = $filter->get_sql_filter()) {
                    $where .= " AND $filtersql";
                }
                break;
        }

        return array($select, $from, $where);
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
 

    /**
     * Highlight some PHP code
     *
     * @param string $code The code to highlight
     * @return string
     */
//    public function highlight($code) {
//       // global $OUTPUT;
//
//        //
//        //$highlighted = $OUTPUT->box($highlighted, 'generalbox boxaligncenter boxwidthnormal');
//
//        //return $highlighted;
//    }


}