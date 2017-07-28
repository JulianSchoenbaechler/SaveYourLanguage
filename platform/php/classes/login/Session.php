<?php
/*
 * Session control object
 *
 * A class providing useful functions for handling user sessions.
 *
 * Author           Julian Schoenbaechler
 * Copyright        (c) 2017 University of the Arts, Zurich
 * Included since   v0.0.1
 * Repository       https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage\Login;

// Include connection
require_once dirname(__FILE__).'/../Config.php';

use SaveYourLanguage\Config;


class Session
{
    // Properties
    protected $name;
    
    // Constructor
    public function __construct($sessionName)
    {
        $this->name = $sessionName ? $sessionName : '';
    }
    
    // Start a secure session used for logged in user identification.
    public function startSecureSession()
    {
        if (ini_set('session.use_only_cookies', 1) === false) {
            trigger_error("[Session] 'startSecureSession' is not able to use session cookies.", E_USER_ERROR);
            exit();
        }
        
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params(
            1800,
            $cookieParams['path'],
            $cookieParams['domain'],
            Config::SECURE_CONNECTION,
            true
        );
        session_name($this->name);
        session_start();
        session_regenerate_id(true);
    }
    
    public function closeSession()
    {
        session_write_close();
    }
    
    public function destroySession()
    {
        session_destroy();
    }
}
