#!/usr/bin/php
<?php

/**
 * Prosody XMPP Server External Authentication w/ Panu
 *
 *
 * <http://prosody.im/>
 * <https://code.google.com/p/prosody-modules/wiki/mod_auth_external>
 *
 *
 * @author Ville Korhonen <ville.korhonen@ylioppilastutkinto.fi>
 * @license GPLv3
 * @version 0.0.0
 * @package panu
 */

/*
 Config:
  /etc/prosody/prosody.cfg.lua:
    authentication = "external"
    external_auth_protocol = "generic"
    external_auth_command = "path/to/this/file"


 Commands: (TBD)
  auth:
   $0 auth:username:domain:password
   $0 auth:ville:digabi.fi:mysecretpassword
  isuser:
   $0 isuser:username:domain
   $0 isuser:ville:digabi.fi
  setpass:
   $0 setpass:username:domain:password
   $0 setpass:ville:digabi.fi:mynewsecretpassword

*/

define("XMPP_TOKEN_PREFIX", "xmpp_");
define("SEPARATOR_CHAR", ":");
define("AUTHLOG", "prosody_external.log");

define("ABSPATH", dirname(__FILE__));

// TODO: If this script will be included in panu/current/util/, PANU_ROOT should be changed to ABSPATH . '/../'
define("PANU_ROOT", ABSPATH . "/../panu/current");

require_once(PANU_ROOT . '/panu_lib.php');

/**
 * Convert given domain into Panu token, by prefixing w/ XMPP_TOKEN_PREFIX and converting into lowercase
 * @param string $domain Domain to be converted into token 
 * @return string Returns domain prefixed w/ XMPP_TOKEN_PREFIX and fully lowercased
 */
function domain_to_token($domain) {
    // TODO: Check that domain actually is valid domain?
    return strtolower(XMPP_TOKEN_PREFIX . $domain);
}

/**
 * Check if user exists in domain
 * @param string $user Username
 * @param string $domain Domain
 * @return boolean 0 if failure (user doesn't exist in domain), 1 if success (user exists in domain)
 */
function isuser($user, $domain) {
    global $auth;

    $required_token = domain_to_token($domain);

    $user_data = $auth->get_user_data($user);

    if (is_null($user_data['tokens'])) {
        return 0;
    } elseif (in_array($required_token, $user_data['tokens'])) {
        return 1;
    }

    return 0;
}

/**
 * Change user password
 *
 * @todo Should this be able to create new users?
 * @todo Should this do some sort of testing? (User exists in domain etc.?)
 *
 * @param string $user Username
 * @param string $domain Domain
 * @param string $password New password
 * @return boolean 0 if failure (password wasn't changed), 1 if success (password was changed)
 */
function setpass($user, $domain, $password) {
    // TODO
    return 0;
}

/**
 * Authenticate user (check, that user exists in domain w/ specified password)
 *
 * @param string $user Username
 * @param string $domain Domain
 * @param string $password Password
 * @return boolean 0 if failure (user doesn't exist in domain, or password is invalid), 1 if success (user exists in domain, password is valid)
 */
function auth($user, $domain, $password) {
    global $auth;

    $required_token = domain_to_token($domain);

    // Check that username/password pair is correct, code stolen from apache_external.php
    $tokens = $auth->auth_user($user, $password);

    if (is_null($tokens)) {
        if ($auth->error_count() > 0) {
            echo("Authentication module returns errors. See server log for more details.\n");
            foreach ($auth->get_errors() as $this_error) {
                echo("$this_error\n");
            }
        }
    } elseif (in_array($required_token, $tokens)) {
        return 1;
    }
    return 0;
}

/**
 * CLI
 */
function cli() {
    // Parse STDIN, remove trailing whitespace, split at first SEPARATOR_CHAR, so we get command & "the rest of input"
    $command = explode(SEPARATOR_CHAR, trim(fgets(STDIN)), 2);
    
    // Split username, domain and password (in this order, max. 3 separate pieces, password might contain SEPARATOR_CHARs)
    $params = explode(SEPARATOR_CHAR, $command[1], 3);
    
    // TODO: Is this required?
    $params[0] = clean_username($params[0]);

    switch ($command[0]) {
        case "auth":
            return auth($params[0], $params[1], $params[2]);
            break;
        case "isuser":
            return isuser($params[0], $params[1]);
            break;
        case "setpass":
            return setpass($params[0], $params[1], $params[2]);
            break;
        default:
            return 0;
    }
    return $res;
}

if (php_sapi_name() == 'cli') {echo cli();}
?>
