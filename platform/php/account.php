<?php
/**
 * Save Your Language user settings
 *
 * Sequential - Account settings page
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
require_once 'classes/Config.php';

use SaveYourLanguage\Database\DatabaseController;
use SaveYourLanguage\Login\Login;
use SaveYourLanguage\Login\Crypt;
use SaveYourLanguage\Config;

// Check if user logged in
if ($userId = Login::isUserLoggedIn()) {
    
    $dc = new DatabaseController(Login::$dbConnection);
    
    // Get user data
    $userData = $dc->getRow('users', array('id' => $userId));
    
    // Store crypto-key
    putenv('USER_CRYPTO_KEY='.Crypt::decryptBlowfish($userData['crypt'], Config::CRYPTO_KEY));
    
    $content = file_get_contents('../../account.html');
    $content = str_replace('$-username-$', $userData['username'], $content);
    
    // User data
    $content = str_replace('$-publicEmail-$', $userData['public_email'] == 1 ? 'checked="checked"' : '', $content);
    $content = str_replace('$-email-$', htmlspecialchars($userData['email']), $content);
    
    // Already escaped special chars but encrypted -> for the following
    $content = str_replace(
        '$-name-$',
        $userData['name'] != 'none' ? Crypt::decryptAES256($userData['name'], getenv('USER_CRYPTO_KEY', true)) : '',
        $content
    );
    $content = str_replace(
        '$-address-$',
        $userData['address'] != 'none' ? Crypt::decryptAES256($userData['address'], getenv('USER_CRYPTO_KEY', true)) : '',
        $content
    );
    $content = str_replace(
        '$-phone-$',
        $userData['phone'] != 'none' ? Crypt::decryptAES256($userData['phone'], getenv('USER_CRYPTO_KEY', true)) : '',
        $content
    );
    
    echo $content;
    exit();
    
} else {
    
    header('Location: index', true, 302);
    exit();
    
}
