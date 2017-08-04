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

use SaveYourLanguage\Database\DatabaseController;


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
    public static function generateNew($canvasX, $canvasY, $numberOfStars = 200)
    {
        // Check arguments
        if (!is_int($canvasX)) {
			trigger_error("[Login] 'registerUser' expected Argument 0 to be Integer", E_USER_WARNING);
		}
        if (!is_int($canvasY)) {
			trigger_error("[Login] 'registerUser' expected Argument 1 to be Integer", E_USER_WARNING);
		}
        if (!is_int($numberOfStars)) {
			trigger_error("[Login] 'registerUser' expected Argument 2 to be Integer", E_USER_WARNING);
		}
        
        // Initialize
        self::init();
        
        // Empty star table in db
        self::$dc->emptyTable('stars');
        self::$dc->emptyTable('userStars');
        
        // Add new stars
        for ($i = 0; $i < $numberOfStars; $i++) {
            
            self::$dc->insertRow('stars', array(
                'x' => mt_rand(0, $canvasX),
                'y' => mt_rand(0, $canvasY),
                'level' => 0
            ));
            
        }
    }
    
    // Add a new star to the users sequence
    public static function addUserToStar($userId, $starId)
    {
        // Check arguments
        if (!is_int($userId)) {
			trigger_error("[Login] 'registerUser' expected Argument 0 to be Integer", E_USER_WARNING);
		}
        if (!is_int($starId)) {
			trigger_error("[Login] 'registerUser' expected Argument 1 to be Integer", E_USER_WARNING);
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
        $oldStar = false;
        
        // Not the first star?
        if ($sequenceNo > 0) {
        
            foreach ($userStars as $star) {
                
                // Found transcribed
                if ($star['starId'] == $starId) {
                    
                    $oldStar = true;
                    break;
                
                }
                
            }
            
        }
        
        if ($oldStar == true)
            return false;
        
        // Enqueue new star for user
        self::$dc->insertRow('userStars', array(
            'userId' => $userId,
            'starId' => $starId,
            'sequence' => $sequenceNo
        ));
        
        // Update star level
        self::$dc->updateRow('stars', array(
            'level' => (int)$star['level'] + 1
        ), array(
            'id' => $starId
        ));
        
        return true;
        
    }
}
