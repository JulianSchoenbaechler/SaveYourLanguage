<?php
/**
 * Save Your Language game field
 *
 * Sequential - Loads game page
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
    $content = file_get_contents('../../game.html');
    
    // Get user data
    $row = $dc->getRow('users', array('id' => $userId));
    
    // Add username in content
    $content = str_replace('$-username-$', $row['username'], $content);
    
    echo $content;
    exit();
    
} else {
    
    header('Location: index', true, 302);
    exit();
    
}
