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
    
    // Load response HTML content
    $content = file_get_contents('../../about.html');
    
} else {
    
    // Redirecto to starfield page
    header('Location: game', true, 302);
    exit();
    
}

// Close db + echo content
DatabaseController::disconnect(Login::$dbConnection);
echo $content;
exit();
