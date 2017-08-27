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
require_once 'classes/login/Crypt.php';
require_once 'classes/login/Mail.php';
require_once 'classes/Config.php';

use SaveYourLanguage\Database\DatabaseController;
use SaveYourLanguage\Login\Login;
use SaveYourLanguage\Login\Crypt;
use SaveYourLanguage\Login\Mail;
use SaveYourLanguage\Config;

// Check if user logged in
if ($userId = Login::isUserLoggedIn()) {
    
    $dc = new DatabaseController(Login::$dbConnection);
    $task = isset($_POST['task']) ? strtolower(trim($_POST['task'])) : 'none';
    
    switch ($task) {
        
        // Change user settings
        case 'settings':
            
            // Get all possible post data
            $publicEmail = isset($_POST['publicEmail']) ? max(0, min(1, (int)$_POST['publicEmail'])) : null;
            $email = isset($_POST['email']) ? filter_var(strtolower(htmlspecialchars_decode(trim($_POST['email']))), FILTER_SANITIZE_EMAIL) : null;
            $name = isset($_POST['name']) ? htmlspecialchars_decode(trim($_POST['name'])) : null;
            $address = isset($_POST['address']) ? htmlspecialchars_decode(trim($_POST['address'])) : null;
            $phone = isset($_POST['phone']) ? htmlspecialchars_decode(trim($_POST['phone'])) : null;
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
                
                // User crypto-key for sensitive data
                putenv('USER_CRYPTO_KEY='.Crypt::decryptBlowfish($userData['crypt'], Config::CRYPTO_KEY));
                
                // Email changed -> check mail address and create confirmation though account recovery
                if ($email !== null) {
                    
                    // Valid email address?
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        
                        $recoverString = Crypt::generateString(64);
                        
                        // Save new email temporarily in recover string
                        $updateData['recover'] = Crypt::encryptAES256($recoverString.'::'.$email, getenv('USER_CRYPTO_KEY', true));
                        
                        // Send user a verification mail
                        $msg = file_get_contents('../data/new-mail-template.html');
                        $msg = str_replace('$=verification=$', "https://saveyourlanguage.com/verification?code=$recoverString&email=".$userData['email'], $msg);
                        $msg = str_replace('$=date=$', date("F d, Y", time()), $msg);
                        $mail = new Mail();
                        $mail->addRecipient($email, $name !== null ? $name : $userData['username']);
                        $mail->setSubject('SaveYourLanguage.com - Neue E-Mail Adresse');
                        $mail->setContent($msg);
                        
                        if (!$mail->send()) {
                            
                            echo json_encode(array('error' => 'sendVerification'));
                            exit();
                            
                        }
                        
                    } else {
                        
                        // Close db
                        DatabaseController::disconnect(Login::$dbConnection);
                        
                        // Response
                        echo json_encode(array('error' => 'mail'));
                        exit();
                        
                    }
                    
                }
                
                // Basic data -> only password confirmation needed, no email confirmation
                
                if ($publicEmail !== null)
                    $updateData['public_email'] = $publicEmail;
                
                if ($name !== null)
                    $updateData['name'] = strlen($name) > 0 ? Crypt::encryptAES256(htmlspecialchars($name), getenv('USER_CRYPTO_KEY', true)) : 'none';
                
                if ($address !== null)
                    $updateData['address'] = strlen($address) > 0 ? Crypt::encryptAES256(htmlspecialchars($address), getenv('USER_CRYPTO_KEY', true)) : 'none';
                
                if ($phone !== null)
                    $updateData['phone'] = strlen($phone) > 0 ? Crypt::encryptAES256(htmlspecialchars($phone), getenv('USER_CRYPTO_KEY', true)) : 'none';
                
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
