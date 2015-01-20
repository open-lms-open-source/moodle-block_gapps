<?php

// file_marked_for_removal

/**
 * oauth3 lib functions
 */

global $CFG;

require_once $CFG->dirroot.'/blocks/gapps/gmail/library/OAuthRequester.php';
require_once $CFG->dirroot.'/blocks/gapps/gmail/library/OAuthException2.php';




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
        if (debugging('',DEBUG_DEVELOPER)) {
            throw new Exception('oauth3_obtain_feed error: '.$e->getMessage());
        } else {
            throw new Exception('Gapps was not able to return gmail data. Turn debugging on for more information');
        }
    }
    
    return $feed;
}


?>
