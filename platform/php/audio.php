<?php
/**
 * Audio (transcription snippet) file
 *
 * Searches for an audio file according to the clients session and returns that.
 * Client must be logged in!
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

use SaveYourLanguage\Database\DatabaseController;
use SaveYourLanguage\Login\Login;
use SaveYourLanguage\Login\Session;

// Check if user logged in
if ($userId = Login::isUserLoggedIn()) {

    // Start new session
    $session = new Session('SaveYourLanguage');
    $session->startSecureSession();
    $snippetId = isset($_SESSION['syl']['snippet']) && ($_SESSION['syl']['snippet'] !== null) ? $_SESSION['syl']['snippet'] : -1;
    $session->closeSession();

    // Snippet staged?
    if ($snippetId >= 0) {

        $snippet = $dc->getRow('snippets', array('id' => $snippetId));
        $pathPrefix = '';   // TODO replace with config variable
        $file = $pathPrefix.$snippet['path'];

        if(file_exists($file)) {

            header('Content-Type: audio/mpeg');
            header('Content-Disposition: inline;filename="transcribe.mp3"');
            header('Content-length: '.filesize($file));
            header('Cache-Control: no-cache');
            header('Content-Transfer-Encoding: chunked');

            readfile($file);

        } else {

            header("HTTP/1.0 404 Not Found");

        }

        exit();

    }

}

header('HTTP/1.0 404 Not Found');
exit();
