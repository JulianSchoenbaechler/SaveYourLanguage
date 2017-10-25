<?php
/**
 * Save Your Language transcription page
 *
 * Sequential - Supposed to be called by an AJAX or XMLHttpRequest.
 * Creates new transcriptions from JSON data.
 * Accepted parameters:
 * POST - transcription     = created transcription
 * POST - starId            = selected star
 * POST - connect           = should connect new star to previous
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
require_once 'classes/syl/TranscriptionHandling.php';
require_once 'classes/syl/Starfield.php';
require_once 'classes/Config.php';

use SaveYourLanguage\Database\DatabaseController;
use SaveYourLanguage\Login\Login;
use SaveYourLanguage\Transcriptions\TranscriptionHandling;
use SaveYourLanguage\Playfield\Starfield;
use SaveYourLanguage\Config;

// Check if user logged in
if ($userId = Login::isUserLoggedIn()) {

    $dc = new DatabaseController(Login::$dbConnection);
    $th = new TranscriptionHandling(Login::$dbConnection);

    // Check POST parameters
    $transcription = isset($_POST['transcription']) ? trim($_POST['transcription']) : null;
    $starId = isset($_POST['starId']) ? trim($_POST['starId']) : null;
    $connect = isset($_POST['connect']) ? max(min(intval(trim($_POST['connect'])), 1), 0) : 1;

    // New snippet for player
    $snippet = $th->getSnippet($userId);

    if ($snippet !== null) {

        // Check for transcription
        if (($transcription === null) || (strlen($transcription) == 0)) {
            
            // Close db
            DatabaseController::disconnect(Login::$dbConnection);

            // Response
            echo json_encode(array('error' => 'transcription'));
            exit();
            
        }
        
        // Check star id
        if (($starId === null) || (intval($starId) === 0)) {
            
            // Close db
            DatabaseController::disconnect(Login::$dbConnection);

            // Response
            echo json_encode(array('error' => 'star'));
            exit();
            
        }
        
        $snippetId = (int)$snippet['id'];
        $th->addTranscription($transcription, $snippetId, $userId);     // Add transcription
        $th->recalculateValidity($snippetId);                           // Recalculcate validity of snippets
        Starfield::addUserToStar(                                       // Save user in starfield
            $userId,
            intval($starId),
            $snippetId,
            $connect == 1
        );
        
        // Close db
        DatabaseController::disconnect(Login::$dbConnection);

        // Response
        echo json_encode(array('error' => 'none'));
        exit();
        
    }
    
    // Close db
    DatabaseController::disconnect(Login::$dbConnection);

    // Response
    echo json_encode(array('error' => 'nosnippet'));
    exit();

} else {

    // Close db
    DatabaseController::disconnect(Login::$dbConnection);

    // Response
    echo json_encode(array('error' => 'login'));
    exit();

}
