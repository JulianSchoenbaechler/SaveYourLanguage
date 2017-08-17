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
                
                // Load succes page
                echo 'success';
                
            } else {
                
                // The verification code is not correct
                echo 'error: code';
                
            }
            
        } else {
            
            // Internal error -> this is not a email verification string
            // This could be a password recovery attempt
            echo 'error: db string';
            
        }
        
    } else {
        
        // This email does not exist in db
        echo 'error: email';
        
    }
    
} else {
    
    echo 'error: no email';
    
}

DatabaseController::disconnect($link);
exit();
