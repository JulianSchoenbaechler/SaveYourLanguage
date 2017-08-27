<?php
/**
 * Save Your Language register page
 *
 * Sequential - Register new user
 * Accepted parameters:
 * POST - username          = user data
 * POST - email             = user data
 * POST - password          = user data
 * POST - confirmation      = user data (password)
 * POST - name              = user data
 * POST - address           = user data
 * POST - phone             = user data
 * POST - captcha           = user data
 *
 * @author      Julian Schoenbaechler
 * @copyright   (c) 2017 University of the Arts, Zurich
 * @since       v0.0.1
 * @link        https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage;

// Include library files
require_once 'classes/login/Login.php';
require_once 'classes/login/Captcha.php';

use SaveYourLanguage\Login\Login;
use SaveYourLanguage\Login\Captcha;

// Load response HTML content
$content = file_get_contents('../../register.html');

// Check if user logged in
if ($userId = Login::isUserLoggedIn()) {
    
    header('Location: index', true, 302);
    exit();
    
} else {
    
    // No data provided?
    if (!isset($_POST['username']) || !isset($_POST['email'])) {
        
        // Add verification error and success messages in content
        $content = str_replace('$-error-$', '', $content);
        echo $content;
        exit();
        
    } else {
        
        // Check provided data
        $username = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username'])) : '';
        $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : 'none';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $confirmation = isset($_POST['confirmation']) ? trim($_POST['confirmation']) : '.';
        $name = !empty($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : null;
        $address = !empty($_POST['address']) ? htmlspecialchars(trim($_POST['address'])) : null;
        $phone = !empty($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : null;
        $captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : null;
        
        // Captcha object
        $captchaObj = new Captcha('register');
        
        // Username
        if ((strlen($username) < 3) || (strlen($username) > 25)) {
            
            $content = str_replace('$-error-$', 'Username must be at least 3 characters long but not exceeding 25 characters.', $content);
            echo $content;
            exit();
            
        }
        
        // Email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            
            $content = str_replace('$-error-$', 'Email address is not valid.', $content);
            echo $content;
            exit();
            
        }
        
        // Password
        if (strlen($password) < 5) {
            
            $content = str_replace('$-error-$', 'Please create a stronger password.', $content);
            echo $content;
            exit();
            
        }
        
        // Password confirmation
        if ($password != $confirmation) {
            
            $content = str_replace('$-error-$', 'Your re-entered password is not correct!', $content);
            echo $content;
            exit();
            
        }
        
        // Captcha
        if (!$captchaObj->verifyCaptcha($captcha)) {
            
            $content = str_replace('$-error-$', 'Entered captcha code is not correct!', $content);
            echo $content;
            exit();
            
        }
        
        // Register user
        $success = Login::registerUser($username, $password, $email, false, $name, $address, $phone);
        
        if ($success) {
            
            // Redirecto to index page
            header('Location: index?success=register', true, 302);
            exit();
            
        } else {
            
            $content = str_replace('$-error-$', 'A user with this username or email address already exists!', $content);
            echo $content;
            exit();
            
        }
        
    }
    
}
