<?php

/**
 * All the functionalty of the gmail service wrapped into one object.
 * The gmail class has to manage a couple of different things.
 * 1 It needs to manage the OAuthTokens in a table
 * 2 Provide endpoints for Oauth protocol
 * 3 use the Modified OAuth Lib
 * 4
 * 5 It needs to parse RSS Atom Feed
 *   SimplePie RSS and Atom Feed Framework.
 *

  0. The Gmail block will display the most recent emails from Gmail within
     Moodle with the following informaiton:
  1. Gmail's email chain information CAN'T GET INFO FROM FEED
  2. The email's Subject
  3. The email's Arrival Date
  5. The Gmail block will display a link to the user's Gmail email
  7. The Gmail block will display a link to Compose a new email in Gmail
  8. The Gmail block will verify that a
     Gmail account exists for the user before displaying their email.
  9. The Gmail block will call the Gmail account creation process
     in the GMail Batch Account library if a Gmail account doesn't exist.


 *
 * @author Chris Stones
 * @version $Id$
 * @package block_gapps
 **/

global $CFG,$USER;

class block_gapps_gmail {
    /**
     *
     * @var <type>
     */
    var $domain;

    /**
     *
     * @var <type>
     */
    var $oauthsecret;

    /**
     *
     * @var <type>
     */
    var $msgnumber;

    /**
     *
     * @var <type>
     */
    var $items;

    /**
     *
     * @var <type>
     */
    var $icons;

    /**
     *
     * @var <type>
     */
    var $feederror;


    /**
     * This is a flat to set if we need the user to regrant access
     * so that the content we return displays the regrant link
     * 
     * @var <type>
     */
    var $regrantaccess;
    
   /**
    *
    */
   function __construct() {
       $this->regrantaccess = false;
       $this->feederror = false;
       $this->items = array();
       $this->icons = array();
       $this->include_required_libs();
       
   }

   /**
    * Since gmail is in a tab we can't display the regrant link in the footer because it would
    * show up for all the tabs. You need to either add it as another option
    * @return <type>
    */
   function get_footer_content() {
      global $CFG;
      $req_token_link = $CFG->wwwroot.'/blocks/gapps/gmail/service/request_token.php';
      $this->items[] = '<span class="notifytiny">('.'<a href="'.$req_token_link.'">'.get_string('refreshtoken','block_gapps').'</a>'.')</span>';
      $this->icons[] = ' ';
   }


  /**
   * Libaries gmail needs as well as important notes about those libs here
   *
   * @global <type> $CFG
   */
   function include_required_libs() {
       global $CFG;
       // try {
       require_once $CFG->dirroot.'/blocks/gapps/gmail/library/OAuthRequester.php';
       require_once $CFG->dirroot.'/blocks/gapps/gmail/library/OAuthException2.php';
       // } catch (Exception $e) {
       //
       //}
   }


   /**
    * Written to be called inside of gapps block_gapps to fill the tab with content.
    * 
    * @global <type> $SESSION
    * @global <type> $CFG
    * @global <type> $USER
    * @return array($icons,$items)
    */
   function get_content() {
        global $SESSION,$CFG,$USER, $DB;

        $this->check_domain_set();
        $this->check_oauthsecret_set();


        // Obtain gmail feed data
        $feederror = false; // be optimisic
        $feeddata = NULL;
        try {
            $feeddata = $this->oauth3_obtain_feed($USER->id);
        } catch (OAuthException2 $e) {
            // simple error for user then when you turn on debugging you see the rest of the message
            if (debugging('',DEBUG_DEVELOPER) ) {
                throw new Exception("Error: Feed could not be obtained. ".$e->getMessage());
            } else {
                throw new Exception(get_string('sorrycannotgetmail','block_gmail'));
            }
        }

        // if we need to regrantaccess we'll display the regrant link
        if ($this->regrantaccess ) {
            return array($this->icons,$this->items);
        }

        // any errors should be thrown out by now to be caught by the gapps main get_content try catch block
        // so feeddata should contain correct data ready for parsing
        $this->parse_feed_data($feeddata);
        
        $this->get_footer_content();

        return array($this->icons,$this->items);
    }

    /**
     *
     * @param <type> $feeddata 
     */
    function parse_feed_data($feeddata) {
        global $USER,$CFG;

        if ($USER->id !== 0) {
            // simplepie lib breaks if included on top level only include when necessary
            require_once($CFG->dirroot.'/blocks/gapps/gmail/simplepie/simplepie.inc');
        }

        // Parse google atom feed
        $feed = new SimplePie();
        $feed->set_raw_data($feeddata);
        $status = $feed->init();
        $msgs = $feed->get_items();

        $domain = get_config('blocks/gapps','consumer_key');

        
        $unreadmsgsstr = get_string('unreadmsgs','block_gapps');
        $composestr    = get_string('compose','block_gapps');
        $inboxstr      = get_string('inbox','block_gapps');

        // Obtain link option
        $newwinlnk = get_config('blocks/gapps','newwinlink');

        $composelink = '<a '.(($newwinlnk)?'target="_new"':'').' href="'.'http://mail.google.com/a/'.$domain.'/?AuthEventSource=SSO#compose">'.$composestr.'</a>';
        $inboxlink = '<a '.(($newwinlnk)?'target="_new"':'').' href="'.'http://mail.google.com/a/'.$domain.'">'.$inboxstr.'</a>';

        $this->items[] = '<span style="font-size:0.8em;">'.$inboxlink.' '.$composelink.' '.$unreadmsgsstr.'('.count($msgs).')'.'</span><br/>';
        $this->icons[] = "<img src=\"$CFG->wwwroot/blocks/gapps/imgs/gmail.png\" alt=\"message\" />";
        
        // Only show as many messages as specified in config
        $countmsg = true;
        if( !$msgnumber = get_config('blocks/gapps','msgnumber')) {
            $countmsg = false; // 0 msg means as many as you want.
        }
        $mc = 0;
        
        foreach( $msgs as $msg) {
            if($countmsg and $mc == $msgnumber) {
                break;
            }
            $mc++;

            // Displaying Message Data
            $author = $msg->get_author();
            $summary = $msg->get_description();

            // Google partners need a special gmail url
            $servicelink = $msg->get_link();
            $servicelink = str_replace('http://mail.google.com/mail','http://mail.google.com/a/'.$domain,$servicelink);


            // To Save Space given them option to show first and last or just last name
            @list($author_first,$author_last) = split(" ",$author->get_name());

            // Show first Name
            if( !$showfirstname = get_config('blocks/gapps','showfirstname')) {
                $author_first = '';
            }

            // Show last Name
            if( !$showlastname = get_config('blocks/gapps','showlastname')) {
                $author_last = '';
            }

            if ($newwinlnk) {
                $text  = ' <a target="_new" title="'.format_string($summary);
                $text .= '" href="'.$servicelink.'">'.format_string($msg->get_title()).'</a> '.$author_first.' '.$author_last;
                $this->items[] = $text;
            } else {
                $text  = ' <a title="'.format_string($summary);
                $text .= '" href="'.$servicelink.'">'.format_string($msg->get_title()).'</a> '.$author_first.' '.$author_last;
                $this->items[]  = $text;
            }

            $this->icons[] = '-'; // May use message icons, for now a simple dash
        }
    }

    // dependency checks

    /**
     * Check that gapps has google apps domain set
     */
    function check_domain_set() {
        if ( !$domain = get_config('blocks/gapps','consumer_key')) {
            throw new Exception(get_string('domainnotset','block_gapps'));
        }
    }

    /**
     * Check that oauthsecret is set
     */
    function check_oauthsecret_set() {
        if( !$this->oauthsecret = get_config('blocks/gapps','oauthsecret') ) {
            throw new Exception(get_string('missingoauthkey','block_gapps'));
        }
    }


    /**
     * Returns the atom feed only requires oauth3/OAuthRequester.php
     *
     * @param <type> $user_id
     * @param <type> $request_uri
     * @return <type>
     */
    function oauth3_obtain_feed($user_id,$request_uri='https://mail.google.com/mail/feed/atom') {
        // Do we have a token for this user???
        // if not return error print "no token found for" exit();
        // if this is a curl call you can't use global user here
        //$user_id= 5;
        //$request_uri = 'https://mail.google.com/mail/feed/atom';
        $feed = '';
        if (!function_exists('getallheaders')) {
            function getallheaders() {
                return array();
            }
        }

        try {
            $store  = OAuthStore::instance('Google');
            $req = new OAuthRequester($request_uri,'GET', $params=null);
            $result = $req->doRequest($user_id);
            // $result is an array of the form: array ('code'=>int, 'headers'=>array(), 'body'=>string)
            $feed = $result['body'];
        } catch (OAuthException2 $e) {
            // depending on the errors we may try to handle them
            if( !$this->handle_oauth3_feed_errors($e->getMessage()) ) {
                if (debugging('',DEBUG_DEVELOPER)) {
                    throw new Exception('oauth3_obtain_feed error: '.$e->getMessage());
                } else {
                    throw new Exception('Gapps was not able to return gmail data. Turn debugging on for more information');
                }
            }
        }

        return $feed;
    }

    /**
     * If the error has to do with a band oauth token then we ask the user
     * to refresh that token by regranting access.
     * If it can't be handled the function returns false. It returns true otherwise.
     *
     * 
     * @param <type> $errormsg
     * @return boolean true if we can handle this error false if we can not
     */
    function handle_oauth3_feed_errors($errormsg) {
        global $CFG;
        
        // Identify Type of Error and handle
        // These use error message strings not moodle strings because they are always in code only
        if(    substr_count($errormsg,'User not in token table')
            or substr_count($errormsg,'User has no token')
            or substr_count($errormsg,'Error 401')
            or substr_count($errormsg,'Unauthorized')) {

            $this->feederror = true;
            $this->regrantaccess = true;

            $req_token_link = $CFG->wwwroot.'/blocks/gapps/gmail/service/request_token.php';

            //$hbutton   = helpbutton('grantaccess', 'grantaccess', 'block_gmail', true, false, '', true, '');
            // helpbutton What does Grant access mean?
            $this->items[] = '<a href="'.$req_token_link.'">'.get_string('grantaccesstoinbox','block_gapps').'</a> ';//.$hbutton;
            $this->icons[] = '-';// regrant icon
            return true; // we can try to handle this error
        }
        
        return false;
    }



}