<?php
/*
 * Starfield / playfield
 *
 * A class for handling the playfield / starfield.
 *
 * Author           Julian Schoenbaechler
 * Copyright        (c) 2017 University of the Arts, Zurich
 * Included since   v0.0.1
 * Repository       https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage\Playfield;

// Include db controller
require_once dirname(__FILE__).'/../db/DatabaseController.php';
require_once dirname(__FILE__).'/../Config.php';

use SaveYourLanguage\Database\DatabaseController;
use SaveYourLanguage\Config;


class Starfield
{
    private static $initialized = false;
    protected static $dc;

    // Initialization: DB connection
    protected static function init()
    {
        if (self::$initialized === false) {

            $link = DatabaseController::connect();
            self::$dc = new DatabaseController($link);

            self::$initialized = true;

        }
    }

    // Generate completely new starfield / delete old one and all its data
    public static function generateNew($canvasX, $canvasY, $numberOfStars = 256)
    {
        // Check arguments
        if (!is_int($canvasX)) {
			trigger_error("[Starfield] 'generateNew' expected Argument 0 to be Integer", E_USER_WARNING);
		}
        if (!is_int($canvasY)) {
			trigger_error("[Starfield] 'generateNew' expected Argument 1 to be Integer", E_USER_WARNING);
		}
        if (!is_int($numberOfStars)) {
			trigger_error("[Starfield] 'generateNew' expected Argument 2 to be Integer", E_USER_WARNING);
		}

        // Initialize
        self::init();

        // Empty star table in db
        self::$dc->emptyTable('stars');
        self::$dc->emptyTable('userStars');

        // Generate stars not quite random but in cells
        // This prevents stars from overlapping (kind of...)
        $offset = 5;
        $cellsPerSide = sqrt($numberOfStars);
        $cellSizeX = $canvasX / $cellsPerSide;
        $cellSizeY = $canvasY / $cellsPerSide;
        $stars = array();

        // Generate field according to cells
        for ($i = 0; $i < floor($cellsPerSide); $i++) {

            for ($j = 0; $j < floor($cellsPerSide); $j++) {

                $stars[] = array(
                    'x' => mt_rand($i * $cellSizeX + $offset, ($i + 1) * $cellSizeX - $offset),
                    'y' => mt_rand($j * $cellSizeY + $offset, ($j + 1) * $cellSizeY - $offset),
                    'level' => 0
                );

            }

        }

        // Stars left that could not be placed into cells?
        // Number of stars is not a square number
        for ($i = count($stars); $i < $numberOfStars; $i++) {

            // Fill up random
            $stars[] = array(
                'x' => mt_rand(0, $canvasX),
                'y' => mt_rand(0, $canvasY),
                'level' => 0
            );

        }

        shuffle($stars);

        // Add new stars
        for ($i = 0; $i < $numberOfStars; $i++) {

            self::$dc->insertRow('stars', $stars[$i]);

        }
    }

    // Add a new star to the users sequence
    public static function addUserToStar($userId, $starId, $snippetId, $connected = true)
    {
        // Check arguments
        if (!is_int($userId)) {
			trigger_error("[Starfield] 'addUserToStar' expected Argument 0 to be Integer", E_USER_WARNING);
		}
        if (!is_int($starId)) {
			trigger_error("[Starfield] 'addUserToStar' expected Argument 1 to be Integer", E_USER_WARNING);
		}
        if (!is_int($snippetId)) {
			trigger_error("[Starfield] 'addUserToStar' expected Argument 2 to be Integer", E_USER_WARNING);
		}

        // Initialize
        self::init();

        // Get specified star
        $star = self::$dc->getRow('stars', array('id' => $starId));

        if ($star === null)
            return false;

        // Get all current stars from specified user
        $userStars = self::$dc->getRows('userStars', array('userId' => $userId));
        $sequenceNo = $userStars !== null ? count($userStars) : 0;

        // Already transcribed by player itself?
        $oldStar = 0;

        // Not the first star?
        if ($sequenceNo > 0) {

            foreach ($userStars as $star) {

                // Found transcribed
                if ($star['starId'] == $starId)
                    $oldStar++;

            }

        }

        // How many times can a star be transcribed?
        if ($oldStar >= Config::MAX_USER_SELECT_STAR)
            return false;

        // Enqueue new star for user
        self::$dc->insertRow('userStars', array(
            'userId' => $userId,
            'starId' => $starId,
            'snippetId' => $snippetId,
            'sequence' => $sequenceNo,
            'connected' => $connected ? 1 : 0,
            'active' => 1
        ));

        // Update star level
        self::$dc->updateRow('stars', array(
            'level' => (int)$star['level'] + 1
        ), array(
            'id' => $starId
        ));

        return true;

    }

    // Kills the star a user has already made where the snippet was not correct
    public static function killUserStar($userId, $snippetId)
    {
        // Check arguments
        if (!is_int($userId)) {
			trigger_error("[Starfield] 'addUserToStar' expected Argument 0 to be Integer", E_USER_WARNING);
		}
        if (!is_int($snippetId)) {
			trigger_error("[Starfield] 'addUserToStar' expected Argument 1 to be Integer", E_USER_WARNING);
		}

        // Initialize
        self::init();

        // Make star inactive
        self::$dc->updateRow('userStars', array('active' => 0), array('userId' => $userId, 'snippetId' => $snippetId));
    }

    // Is user able to save his/her current starfield?
    // Returns all stars from this user, or false if stars cannot be saved
    public static function userCanSaveStarfield($userId)
    {
        // Check arguments
        if (!is_int($userId)) {
			trigger_error("[Starfield] 'userCanSaveStarfield' expected Argument 0 to be Integer", E_USER_WARNING);
		}

        // Initialize
        self::init();

        // Get all current stars from specified user
        $userStars = self::$dc->getRows('userStars', array('userId' => $userId));
        $sequenceNo = $userStars !== null ? count($userStars) : 0;

        // Are there any stars?
        if ($sequenceNo > 0) {

            $savedField = self::$dc->getRows('userSavedStars', array('userId' => $userId, 'count' => $sequenceNo));

            // Already a copy of this field?
            if ($savedField === null)
                return $userStars;
            else
                return false;

        }

        return false;

    }

    // Save current starfield from a specific user
    public static function userSaveStarfield($userId)
    {
        // Check arguments
        if (!is_int($userId)) {
			trigger_error("[Starfield] 'userSaveStarfield' expected Argument 0 to be Integer", E_USER_WARNING);
		}

        // Can user save starfield?
        if ($userStars = self::userCanSaveStarfield($userId)) {

            $data = array();

            // Get all stars
            foreach ($userStars as $userStar) {

                $data[] = self::$dc->getRow('stars', array('id' => $userStar['starId']));

            }

            // Save into db
            self::$dc->insertRow('userSavedStars', array(
                'userId' => $userId,
                'data' => json_encode($data),       // Save data as JSON
                'timestamp' => time(),
                'count' => count($userStars)
            ));

        }
    }
}
