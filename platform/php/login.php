<?php
/**
 * Save Your Language login
 *
 * Sequential - Login and logout
 * Accepted parameters:
 * POST - login             = login data (username or email)
 * POST - password          = login data (password)
 *
 * @author      Julian Schoenbaechler
 * @copyright   (c) 2017 University of the Arts, Zurich
 * @since       v0.0.1
 * @link        https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage;

// Include library files
require_once 'classes/login/Login.php';

use SaveYourLanguage\Login\Login;


// If no usable data provided - logout
if (!isset($_POST['login']) || !isset($_POST['password'])) {
    
    Login::logoutUser();
    header('Location: index', true, 302);
    exit();
    
}

// Check if user logged in
if ($userId = Login::isUserLoggedIn()) {
    
    header('Location: index', true, 302);
    exit();
    
} else {
    
    $login = Login::loginUser(trim($_POST['login']), trim($_POST['password']));
    
    // Login successful?
    if ($login === true) {
        
        header('Location: index', true, 302);
        exit();
        
    } else {
        
        header("Location: index?error=$login", true, 302);
        exit();
        
    }
    
}
