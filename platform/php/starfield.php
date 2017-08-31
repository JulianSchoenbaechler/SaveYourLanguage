<?php
/**
 * Save Your Language starfield
 *
 * Sequential - Supposed to be called by an AJAX or XMLHttpRequest.
 * Returns starfield data for every action in JSON format.
 * Accepted parameters:
 * POST - task              = data interpretation
 * POST - user              = requested user
 * POST - star              = transcripe new snippet -> save star in session
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

    $dc = new DatabaseController(Login::$dbConnection);
    $task = isset($_POST['task']) ? strtolower(trim($_POST['task'])) : 'none';

    switch ($task) {

        // Change user settings
        case 'load':

            // Load stars
            $stars = $dc->getRowsOrderedBy('stars', array(), array(), 'id');

            // Stars are stored with coordinates (x and y) from 0 to 1000
            // Divide by ten to get percentage coordinates for starfield
            for ($i = 0; $i < count($stars); $i++) {
                $stars[$i]['x'] /= 10;
                $stars[$i]['y'] /= 10;
            }

            // Close db
            DatabaseController::disconnect(Login::$dbConnection);

            if ($stars === null) {

                // Response
                echo json_encode(array('error' => 'empty'));
                exit();

            }

            // Response
            echo json_encode(array('stars' => $stars));
            exit();

            break;

        // Save snippet in session -> for transcribing later
        case 'transcribe':

            $starId = isset($_POST['star']) ? (int)trim($_POST['star']) : -1;

            if ($starId >= 0) {

                // Start new session and set session variable
                $session = new Session('SaveYourLanguage');
                $session->startSecureSession();
                $_SESSION['syl']['star'] = $starId;
                $session->closeSession();

                // Response
                echo json_encode(array('error' => 'none'));

            } else {

                // Response
                echo json_encode(array('error' => 'nostar'));

            }

            // Close db
            DatabaseController::disconnect(Login::$dbConnection);
            exit();

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
