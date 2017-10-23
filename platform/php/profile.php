<?php
/**
 * Save Your Language user settings
 *
 * Sequential - Profile page
 * Accepted parameters:
 * GET - id                 = user identifier
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
    
    $otherUser = intval(trim($_GET['id']));
    
    $dc = new DatabaseController(Login::$dbConnection);
    
    // Get user data
    $userData = $dc->getRow('users', array('id' => $otherUser ? $otherUser : $userId));
    
    if ($userData !== null) {
    
        // Store crypto-key
        putenv('USER_CRYPTO_KEY='.Crypt::decryptBlowfish($userData['crypt'], Config::CRYPTO_KEY));
        
        $content = file_get_contents('../../profile.html');
        
        // Write profile info
        $profile = $userData['username'].'<br />';
        $profile .= $userData['name'] != 'none' ? Crypt::decryptAES256($userData['name'], getenv('USER_CRYPTO_KEY', true)).'<br />' : '';
        $profile .= $userData['address'] != 'none' ? Crypt::decryptAES256($userData['address'], getenv('USER_CRYPTO_KEY', true)).'<br />' : '';
        $profile .= $userData['phone'] != 'none' ? Crypt::decryptAES256($userData['phone'], getenv('USER_CRYPTO_KEY', true)).'<br />' : '';
        
        $content = str_replace('$-profile-$', $profile, $content);
        
        // Accuracy
        $transcriptions = $dc->getRows('transcriptions', array('userId' => $otherUser ? $otherUser : $userId));
        $weekCount = 0;
        $accuracy = 0;
        
        for ($i = 0; $i < count($transcriptions); $i++) {
            
            if ($transcriptions[$i]['timestamp'] > (time() - 604800))
                $weekCount++;
            
            $accuracy += $transcriptions[$i]['evaluation'];
            
        }
        
        $accuracy /= count($transcriptions);
        
        // User data
        $content = str_replace('$-weekCount-$', $weekCount, $content);
        $content = str_replace('$-accuracy-$', round($accuracy), $content);
        $content = str_replace('$-count-$', count($transcriptions), $content);
        $content = str_replace('$-editProfile-$', $otherUser ? 'hidden' : '', $content);
        
        echo $content;
        exit();
        
    }
    
    header('Location: game', true, 302);
    exit();
    
} else {
    
    header('Location: index', true, 302);
    exit();
    
}
