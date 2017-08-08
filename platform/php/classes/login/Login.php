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
require_once dirname(__FILE__).'/Session.php';
require_once dirname(__FILE__).'/../Config.php';

use SaveYourLanguage\Database\DatabaseController;
use SaveYourLanguage\Config;

class Login
{
    public static $dbConnection;
    
    private static $initialized = false;
    protected static $dc;
    
    // Initialization: DB connection
    protected static function init()
    {
        if (self::$initialized === false) {
            
            self::$dbConnection = DatabaseController::connect();
            self::$dc = new DatabaseController(self::$dbConnection);
            
            self::$initialized = true;
            
        }
    }
    
    /*
     * Creates a new user / player in database
     * Returns true if registration succeeded, otherwise false
     */
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
            'verified' => 0,
            'login_attempts' => 0,
            'last_attempt' => time(),
            'recover' => 'none',
            'crypt' => Crypt::encryptBlowfish(getenv('USER_CRYPTO_KEY', true), Config::CRYPTO_KEY),
            'public_email' => $publicEmail ? 1 : 0,
            'name' => $name == null ? 'none' : Crypt::encryptAES256($name, getenv('USER_CRYPTO_KEY', true)),
            'address' => $address == null ? 'none' : Crypt::encryptAES256($address, getenv('USER_CRYPTO_KEY', true)),
            'phone' => $phone == null ? 'none' : Crypt::encryptAES256($phone, getenv('USER_CRYPTO_KEY', true))
        );
        
        self::$dc->insertRow('users', $newUserRow);
        return true;
    }
    
    /*
     * User login - Verify login data and start session
     * Returns true if user logged in successfully, otherwise an error keyword as string
     *
     * Possible returned error:
     * nouser       = No user for submitted data found
     * password     = The submitted password is not correct
     * blocked      = This account is blocked
     */
    public static function loginUser($login, $password)
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
        
        $row = null;
        
        // If login contains the character '@' -> login with email
        if (strpos($login, '@') === false) {
            
            $row = self::$dc->getRow('users', array('username' => $login));
            
        } else {
            
            $row = self::$dc->getRow('users', array('email' => $login));
            
        }
        
        // User found?
        if ($row !== null) {
            
            // Check password
            if (password_verify($password, $row['password'])) {
                
                // Check if user blocked
                // 3 attempts or more, not longer as 30 minutes ago
                if (((int)$row['login_attempts'] >= 3) && ((int)$row['last_attempt'] > (time() - 1800))) {
                    
                    // User blocked
                    return 'blocked';
                    
                } else {
                    
                    // Start new session
                    $session = new Session('SaveYourLanguage');
                    $session->startSecureSession();
                    $_SESSION['syl']['user'] = $row['id'];
                    $_SESSION['syl']['client'] = password_hash($_SERVER['REMOTE_ADDR'].$row['crypt'], PASSWORD_BCRYPT);
                    $_SESSION['syl']['expire'] = time() + 1800;
                    $session->closeSession();
                    
                    // Update user data in db
                    self::$dc->updateRow('users', array(
                        'login_attempts' => 0,
                        'last_attempt' => time(),
                        'recover' => 'none'
                    ), array('id' => $row['id']));
                    
                    // Login succesful
                    return true;
                    
                }
                
            } else {
                
                // Update user data in db
                self::$dc->updateRow('users', array(
                    'login_attempts' => (int)$row['login_attempts'] + 1,
                    'last_attempt' => time()
                ), array('id' => $row['id']));
                
                // Password incorrect
                return 'password';
                
            }
            
        } else {
            
            // User does not exist
            return 'nouser';
            
        }
    }
    
    /*
     * Logout current user and destroy its session
     */
    public static function logoutUser()
    {
        // Initialize
        self::init();
        
        // Start session
        $session = new Session('SaveYourLanguage');
        $session->startSecureSession();
        $_SESSION['syl']['user'] = -1;
        $_SESSION['syl']['client'] = 'none';
        $_SESSION['syl']['expire'] = time() - 3600;
        $session->closeSession();
        $session->destroySession();
    }
    
    /*
     * Is this user logged in?
     * Returns the user id if logged in, otherwise false
     */
    public static function isUserLoggedIn()
    {
        // Initialize
        self::init();
        
        // Start session
        $session = new Session('SaveYourLanguage');
        $session->startSecureSession();
        
        // Is session set
        if (isset($_SESSION['syl']['user'])) {
            
            // Is session not expired yet?
            if ($_SESSION['syl']['expire'] >= time()) {
                
                $row = $row = self::$dc->getRow('users', array('id' => $_SESSION['syl']['user']));
                
                // User exists?
                if ($row != null) {
                    
                    // Session owner still correct?
                    if (password_verify($_SERVER['REMOTE_ADDR'].$row['crypt'], $_SESSION['syl']['client'])) {
                        
                        $session->closeSession();
                        return $_SESSION['syl']['user'];
                        
                    } else {
                        
                        $session->closeSession();
                        return false;               // Not same user client ip
                        
                    }
                    
                } else {
                    
                    $session->closeSession();
                    return false;               // The user stored in the session can not be found in db
                    
                }
                
            } else {
                
                $session->closeSession();
                return false;               // The user session is expired
                
            }
            
        }
        
        $session->closeSession();
        return false;               // No corresponding session found
        
    }
}
