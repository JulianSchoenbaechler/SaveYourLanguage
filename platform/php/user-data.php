<?php
/**
 * Save Your Language user settings
 *
 * Sequential - Supposed to be called by a AJAX or XMLHttpRequest.
 * Returns success or error status for every action in JSON format.
 * Accepted parameters:
 * POST - task              = Data interpretation
 * POST - publicEmail       = change user settings
 * POST - email             = change user settings
 * POST - name              = change user settings
 * POST - address           = change user settings
 * POST - phone             = change user settings
 * POST - password          = change user settings
 * POST - newPassword       = change user settings
 * POST - confirmPassword   = change user settings
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
require_once 'classes/Config.php';

use SaveYourLanguage\Database\DatabaseController;
use SaveYourLanguage\Login\Login;
use SaveYourLanguage\Login\Crypt;
use SaveYourLanguage\Config;
$userId = Login::isUserLoggedIn();
// Check if user logged in
if ($userId = 1) {
    
    $dc = new DatabaseController(Login::$dbConnection);
    $task = isset($_POST['task']) ? strtolower(trim($_POST['task'])) : 'none';
    
    switch ($task) {
        
        // Change user settings
        case 'settings':
            
            // Get all possible post data
            $publicEmail = isset($_POST['publicEmail']) ? max(0, min(1, (int)$_POST['publicEmail'])) : null;
            $email = isset($_POST['email']) ? filter_var(strtolower(trim($_POST['email'])), FILTER_SANITIZE_EMAIL) : null;
            $name = isset($_POST['name']) ? trim($_POST['name']) : null;
            $address = isset($_POST['address']) ? trim($_POST['address']) : null;
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
            $password = isset($_POST['password']) ? trim($_POST['password']) : null;
            $newPassword = isset($_POST['newPassword']) ? trim($_POST['newPassword']) : null;
            $confirmPassword = isset($_POST['confirmPassword']) ? trim($_POST['confirmPassword']) : null;
            
            // Get current user data
            $userData = $dc->getRow('users', array('id' => $userId));
            
            // User does not exist?
            if ($userData === null) {
                
                // Close db
                DatabaseController::disconnect(Login::$dbConnection);
                
                // Response
                echo json_encode(array('error' => 'login'));
                exit();
                
            }
            
            // Check entered password
            if (password_verify($password, $userData['password'])) {
                
                $updateData = array();
                
                // Email changed -> check mail address and create confirmation though account recovery
                if ($email !== null) {
                    
                    // Valid email address?
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        
                        $recoverString = Crypt::generateString(128);
                        
                        // Save new email temporarily in recover string
                        $updateData['recover'] = $recoverString.'::'.$email;
                        
                        // Blabla send mail
                        // TODO....
                        
                    } else {
                        
                        // Close db
                        DatabaseController::disconnect(Login::$dbConnection);
                        
                        // Response
                        echo json_encode(array('error' => 'mail'));
                        exit();
                        
                    }
                    
                }
                
                // Basic data -> only password confirmation needed, no email confirmation
                $userCryptoKey = Crypt::decryptBlowfish($userData['crypt'], Config::CRYPTO_KEY);
                
                if ($publicEmail !== null)
                    $updateData['public_email'] = $publicEmail;
                
                if ($name !== null)
                    $updateData['name'] = strlen($name) > 0 ? Crypt::encryptAES256($name, $userCryptoKey) : 'none';
                
                if ($address !== null)
                    $updateData['address'] = strlen($address) > 0 ? Crypt::encryptAES256($address, $userCryptoKey) : 'none';
                
                if ($phone !== null)
                    $updateData['phone'] = strlen($phone) > 0 ? Crypt::encryptAES256($phone, $userCryptoKey) : 'none';
                
                // Update password
                if ($newPassword !== null) {
                    
                    if ($newPassword === $confirmPassword) {
                        
                        $updateData['password'] = password_hash($newPassword, PASSWORD_BCRYPT);
                        
                    } else {
                        
                        // Close db
                        DatabaseController::disconnect(Login::$dbConnection);
                        
                        // Response
                        echo json_encode(array('error' => 'confirmation'));
                        exit();
                        
                    }
                    
                }
                
                // Update db entry
                if (count($updateData) > 0)
                    $dc->updateRow('users', $updateData, array('id' => $userId));
                
                // Close db
                DatabaseController::disconnect(Login::$dbConnection);
                
                // Response
                echo json_encode(array('error' => 'none'));
                exit();
                
            } else {
    
                // Close db
                DatabaseController::disconnect(Login::$dbConnection);
                
                // Response
                echo json_encode(array('error' => 'password'));
                exit();
                
            }
            
            break;
        
        // Unresolved task
        default:
            
            // Close db
            DatabaseController::disconnect(Login::$dbConnection);
            
            // Response
            echo json_encode(array('error' => 'notask'));
            exit();
            
            break;
        
    }
    
} else {
    
    // Close db
    DatabaseController::disconnect(Login::$dbConnection);
    
    // Response
    echo json_encode(array('error' => 'login'));
    exit();
    
}
