<?php
/*
 * Login class - everything db login related
 *
 * A class providing functions for a secure user login / logout db handling.
 *
 * Author           Julian Schoenbaechler
 * Copyright        (c) 2017 University of the Arts, Zurich
 * Included since   v0.0.1
 * Repository       https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage\Login;

// Include connection
require_once dirname(__FILE__).'/../db/DatabaseController.php';
require_once dirname(__FILE__).'/Crypt.php';
require_once dirname(__FILE__).'/../Config.php';

use SaveYourLanguage\Database\DatabaseController;
use SaveYourLanguage\Config;

class Login
{
    private static $initialized = false;
    protected static $dc;
    
    // Initialization: DB connection
    protected static function init()
    {
        if (self::$initialized === false) {
            
            $link = DatabaseController::connect();
            self::$dc = new DatabaseController($link);
            
            self::$initialized = true;
            
        }
    }
    
    // Creates a new user / player in database
    public static function registerUser($username, $password, $email, $publicEmail, $name = null, $address = null, $phone = null)
    {
        // Check arguments
        if (!is_string($username)) {
			trigger_error("[Login] 'registerUser' expected Argument 0 to be String", E_USER_WARNING);
		}
		if (!is_string($password)) {
			trigger_error("[Login] 'registerUser' expected Argument 1 to be String", E_USER_WARNING);
		}
		if (!is_string($email)) {
			trigger_error("[Login] 'registerUser' expected Argument 2 to be String", E_USER_WARNING);
		}
        if (!is_bool($publicEmail)) {
			trigger_error("[Login] 'registerUser' expected Argument 3 to be Boolean", E_USER_WARNING);
		}
        
        // Initialize
        self::init();
        
        // Check if user currently exists (email or username)
        if ((self::$dc->getRow('users', array('username' => $username)) != null) ||
            (self::$dc->getRow('users', array('email' => $email)) != null)) {
            
            return false;
            
        }
        
        // User crypto-key for sensitive data
        putenv('USER_CRYPTO_KEY='.Crypt::generateCryptoKey());
        
        // Adding new user data
        $newUserRow = array(
            'username' => $username,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'email' => $email,
            'login_attempts' => 0,
            'last_attempt' => time(),
            'crypt' => Crypt::encryptBlowfish(getenv('USER_CRYPTO_KEY', true), Config::CRYPTO_KEY),
            'public_email' => $publicEmail ? 1 : 0,
            'name' => $name == null ? 'none' : Crypt::encryptAES256($name, getenv('USER_CRYPTO_KEY', true)),
            'address' => $address == null ? 'none' : Crypt::encryptAES256($address, getenv('USER_CRYPTO_KEY', true)),
            'phone' => $phone == null ? 'none' : Crypt::encryptAES256($phone, getenv('USER_CRYPTO_KEY', true))
        );
        
        self::$dc->insertRow('users', $newUserRow);
        return true;
    }
    
    
    public static function loginUser($login, $password, $loginWithEmail = false)
    {
        // Check arguments
        if (!is_string($login)) {
			trigger_error("[Login] 'loginUser' expected Argument 0 to be String", E_USER_WARNING);
		}
		if (!is_string($password)) {
			trigger_error("[Login] 'loginUser' expected Argument 1 to be String", E_USER_WARNING);
		}
        
        // Initialize
        self::init();
        
        
    }
    
    /*
    // Properties
    protected $name;
    
    // Constructor
    protected function __construct($sessionName)
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
    */
}
