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

        // Load stars
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

        // User star sequence
        case 'user':

            $requestedUser = isset($_POST['user']) ? (int)trim($_POST['user']) : -1;
            if ($requestedUser == 0) $requestedUser = $userId;

            // Valid user id?
            if ($requestedUser > 0) {

                $stars = array();

                // Load stars
                $rows = $dc->getRowsOrderedBy('userStars', array('userId' => $requestedUser), array(), 'sequence');

                // Generate path array from user stars
                // Coordinates are divided by 10
                for ($i = 0; $i < count($rows); $i++) {

                    $star = $dc->getRow('stars', array('id' => $rows[$i]['starId']));
                    $stars[$i] = array('x' => $star['x'] / 10, 'y' => $star['y'] / 10, 'connected' => $rows[$i]['connected'] ? true : false);

                }

                // Response
                echo json_encode(array('path' => $stars));

            } else {

                // Response
                echo json_encode(array('error' => 'nouser'));

            }

            // Close db
            DatabaseController::disconnect(Login::$dbConnection);
            exit();

            break;

        // Load player list
        case 'players':

            // Query to gather players list
            $sql = "SELECT `st`.`userId`, `ut`.`username`
                    FROM `userStars` AS `st`
                        INNER JOIN `users` AS `ut`
                        ON `st`.`userId` = `ut`.`id`
                    GROUP BY `st`.`userId`
                    ORDER BY COUNT(`st`.`userId`) DESC
                    LIMIT 3";
            
            // Load users
            $bestPlayers = $dc->executeCustomQuery($sql, array());
            
            $sql = "SELECT `st`.`userId`, `ut`.`username`
                    FROM `userStars` AS `st`
                        INNER JOIN `users` AS `ut`
                        ON `st`.`userId` = `ut`.`id`
                    WHERE `st`.`timestamp` IN (
                        SELECT MAX(`timestamp`) FROM `userStars` WHERE `userId`=`st`.`userId`
                    )";
            
            // Prepare parameters for next query
            $params = array();
            /*
            // Get all already gathered users
            for ($i = 0; $i < count($bestPlayers); $i++) {
                
                if ($i == 0)
                    $sql .= " WHERE `st`.`userId`!=?";
                else
                    $sql .= " AND `st`.`userId`!=?";
                
                $params[] = intval($bestPlayers[$i]['userId']);
                
            }
            */
            $sql .= " ORDER BY `st`.`timestamp` DESC";
            
            // Get currently active players
            $playerList = $dc->executeCustomQuery($sql, $params);
            
            $inactivePlayers = $dc->executeCustomQuery(
                "SELECT `id`, `username`
                FROM `users`
                WHERE `id` NOT IN
                    (SELECT `userId` FROM `userStars`)",
                array()
            );
            
            // Load players that have no transcriptions yet
            if ($inactivePlayers != null)
                $playerList = array_merge($playerList, $inactivePlayers);
            
            $userPosition = 0;
            
            // Replace associative array key to something more readable
            foreach ($playerList as $key => &$player) {
                
                if (isset($player['id'])) {
                    
                    $player['userId'] = $player['id'];
                    unset($player['id']);
                    
                }
                
                if (intval($player['userId']) == $userId) {
                    
                    $userPosition = $key;
                    
                }
                
            }
            
            // Response
            echo json_encode(array(
                'bestPlayers' => $bestPlayers,
                'activePlayers' => $playerList,
                'userPosition' => $userPosition
            ));
            
            // Close db
            DatabaseController::disconnect(Login::$dbConnection);
            exit();

            break;

        // Save star in session -> for transcribing later
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
