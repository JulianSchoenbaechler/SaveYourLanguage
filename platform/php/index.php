<?php
/**
 * Save Your Language index page
 *
 * Sequential - Loads index page according on users login status
 * Accepted parameters:
 * GET - error              = error login message index
 * GET - register           = a new user registered successfully
 *
 * @author      Julian Schoenbaechler
 * @copyright   (c) 2017 University of the Arts, Zurich
 * @since       v0.0.1
 * @link        https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage;

// Include library files
require_once 'classes/db/DatabaseController.php';
require_once 'classes/login/Login.php';

use SaveYourLanguage\Database\DatabaseController;
use SaveYourLanguage\Login\Login;

$content = '';

// Check if user logged in
if ($userId = Login::isUserLoggedIn()) {
    
    $dc = new DatabaseController(Login::$dbConnection);
    
    // Load response HTML content
    $content = file_get_contents('../../index-user.html');
    
    // Get user data
    $row = $dc->getRow('users', array('id' => $userId));
    
    // Add username in content
    $content = str_replace('$-username-$', $row['username'], $content);
    
} else {
    
    // Load response HTML content
    $content = file_get_contents('../../index.html');
    
    $error = '';
    $success = '';
    
    // Check for error parameters in url
    if (isset($_GET['error'])) {
        
        switch (trim($_GET['error'])) {
            
            case 'nouser':
                $error = 'This user does not exist! Check your login data.';
                break;
            
            case 'password':
                $error = 'Incorrect password! Check your login data.';
                break;
            
            case 'blocked':
                $error = 'This user is temporarily blocked. If you think this is a mistake, you can always contact our support.';
                break;
            
            default:
                $error = 'An unknown error occured!';
                break;
            
        }
        
    }
    
    // Check for success parameters in url
    if (isset($_GET['success'])) {
        
        switch (trim($_GET['success'])) {
            
            case 'register':
                $success = 'Your user account has been set up successfully! You will receive a verification email containing a hyperlink. ';
                $success .= 'Please click this verification link to complete the registration process.';
                break;
            
            default:
                $success = 'All done!';
                break;
            
        }
        
    }
    
    // Add login error in content
    $content = str_replace('$-error-$', $error, $content);
    $content = str_replace('$-success-$', $success, $content);
    
}

// Close db + echo content
DatabaseController::disconnect(Login::$dbConnection);
echo $content;
exit();
