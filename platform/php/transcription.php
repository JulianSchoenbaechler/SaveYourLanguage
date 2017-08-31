<?php
/**
 * Save Your Language transcription page
 *
 * Sequential - Renders transcription page.
 * Accepted parameters:
 * POST - transcription     = created transcription
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
require_once 'classes/login/Session.php';
require_once 'classes/syl/TranscriptionHandling.php';
require_once 'classes/syl/Starfield.php';

use SaveYourLanguage\Database\DatabaseController;
use SaveYourLanguage\Login\Login;
use SaveYourLanguage\Login\Session;
use SaveYourLanguage\Transcriptions\TranscriptionHandling;
use SaveYourLanguage\Playfield\Starfield;

// Check if user logged in
if ($userId = Login::isUserLoggedIn()) {

    $dc = new DatabaseController(Login::$dbConnection);
    $th = new TranscriptionHandling(Login::$dbConnection);

    // Start new session
    $session = new Session('SaveYourLanguage');
    $session->startSecureSession();

    // Check POST parameters
    $transcription = isset($_POST['transcription']) ? trim($_POST['transcription']) : null;

    // Selected star
    $starId = isset($_SESSION['syl']['star']) && ($_SESSION['syl']['star'] !== null) ? $_SESSION['syl']['star'] : -1;

    // Selected snippet
    $snippetId = isset($_SESSION['syl']['snippet']) && ($_SESSION['syl']['snippet'] !== null) ? $_SESSION['syl']['snippet'] : -1;

    // Player made a transcription
    if (($transcription !== null) && (strlen($transcription) > 0) && ($snippetId >= 0)) {

        $th->addTranscription($transcription, $snippetId, $userId);     // Add transcription
        $th->recalculateValidity($snippetId);                           // Recalculcate validity of snippets
        Starfield::addUserToStar($userId, $starId, $snippetId);         // Save user in starfield

        // Unset session variables
        unset($_SESSION['syl']['star']);
        unset($_SESSION['syl']['snippet']);

        // Close session
        $session->closeSession();

        header('Location: game', true, 302);
        exit();


    // Render transcription page and get new snippet for the player
    } else {

        // Star staged?
        if ($starId >= 0) {

            $row = $dc->getRow('userStars', array('userId' => $userId, 'starId' => $starId));

            // Star not already transcribed?
            if ($row === null) {

                // Get a new snippet for this user and save it in session
                $snippet = $th->getSnippet($userId);

                if ($snippet !== null) {

                    $_SESSION['syl']['snippet'] = (int)$th->getSnippet($userId)['id'];

                    // Load response HTML content
                    $content = file_get_contents('../../transcription.html');

                    // Get user data
                    $row = $dc->getRow('users', array('id' => $userId));

                    // Add username in content
                    $content = str_replace('$-username-$', /*$row['username']*/$_SESSION['syl']['snippet'], $content);

                    // Close session
                    $session->closeSession();

                    echo $content;
                    exit();

                }

            }

        }

        // Close session
        $session->closeSession();

        header('Location: game', true, 302);
        exit();

    }

} else {

    // Close db
    DatabaseController::disconnect(Login::$dbConnection);

    // Response
    header('Location: index', true, 302);
    exit();

}
