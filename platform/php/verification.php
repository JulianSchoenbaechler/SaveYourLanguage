<?php
/**
 * Save Your Language email verification
 *
 * Sequential - Verifies the email address of a user in the db.
 * Loads HTML template as response.
 * Accepted parameters:
 * GET - code               = Verification code
 * GET - email              = users new email address
 *
 * @author      Julian Schoenbaechler
 * @copyright   (c) 2017 University of the Arts, Zurich
 * @since       v0.0.1
 * @link        https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage;

// Include library files
require_once 'classes/db/DatabaseController.php';
require_once 'classes/login/Crypt.php';
require_once 'classes/Config.php';

use SaveYourLanguage\Database\DatabaseController;
use SaveYourLanguage\Login\Crypt;
use SaveYourLanguage\Config;

// Load response HTML content
$content = file_get_contents('../../verification.html');

// Check if parameters passed
if (isset($_GET['code']) && isset($_GET['email'])) {
    
    $code = trim($_GET['code']);
    $email = trim($_GET['email']);
    
    $link = DatabaseController::connect();
    $dc = new DatabaseController($link);
    
    $row = $dc->getRow('users', array('email' => $email));
    
    if ($row !== null) {
        
        // Decryp recover string in db
        putenv('USER_CRYPTO_KEY='.Crypt::decryptBlowfish($row['crypt'], Config::CRYPTO_KEY));
        $recoverString = ($row['recover'] != 'none') && (strlen($row['recover']) > 0) ?
                         Crypt::decryptAES256($row['recover'], getenv('USER_CRYPTO_KEY', true)) :
                         'none';
        $recoverData =  explode('::', $recoverString, 2);
        
        // There is an email found in recover string
        if (isset($recoverData[1])) {
            
            // Check if user submitted data equals verification code
            if ($code == $recoverData[0]) {
                
                $dc->updateRow('users', array(
                    'email' => $recoverData[1],     // Take new email from recover string -> this email is already escaped
                    'recover' => 'none',            // Delete recover string
                    'verified' => 1                 // Verify user
                ), array('id' => $row['id']));
                
                // Add verification error and success messages in content
                $content = str_replace('$-error-$', '', $content);
                $content = str_replace('$-success-$', 'Your e-mail address has been verified successfully!', $content);
                
            } else {
                
                // The verification code is not correct
                // Add verification error and success messages in content
                $content = str_replace('$-error-$', 'This verification code is not correct!', $content);
                $content = str_replace('$-success-$', '', $content);
                
            }
            
        } else {
            
            // Internal error -> this is not a email verification string
            // This could be a password recovery attempt
            // Add verification error and success messages in content
            $content = str_replace('$-error-$', 'No corresponding e-mail address found...', $content);
            $content = str_replace('$-success-$', '', $content);
            
        }
        
    } else {
        
        // This email does not exist in db
        // Add verification error and success messages in content
        $content = str_replace('$-error-$', 'No corresponding e-mail address found...', $content);
        $content = str_replace('$-success-$', '', $content);
        
    }
    
    DatabaseController::disconnect($link);
    
} else {
    
    // Add verification error and success messages in content
    $content = str_replace('$-error-$', 'This URL is not complete!', $content);
    $content = str_replace('$-success-$', '', $content);
    
}

echo $content;
exit();
