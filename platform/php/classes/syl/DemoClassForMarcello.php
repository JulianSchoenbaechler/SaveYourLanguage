<?php
/*
 * User statistc
 *
 * A class providing useful functions for creating user transcription statistics.
 *
 * Author           Marcel Arioli
 * Copyright        (c) 2017 University of the Arts, Zurich
 * Included since   v0.0.1
 * Repository       https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
 
// Define namespace
namespace SaveYourLanguage\Statistics;

// Shorthand for DatabaseController by namespace
use SaveYourLanguage\Database\DatabaseController;

// Include db connection
require_once 'DatabaseConnection.php';
require_once 'platform/php/classes/db/DatabaseController.php';  


class DemoClassForMarcello
{
    // Properties
    private $aVariable;
    
    // Constructor
    public function __construct($anArgument)
    {
        // Dos something
    }
    
    // We asume that there are 3 status for soundfiles: fresh -> no transkritipons, unsolved->more transkritipons required, solved...well, solved
    public function Get_Soundfile($tablename)
    {
      
		$link = DatabaseController::connect();
		$dc = new DatabaseController($link);
		
		$soundfile = $dc->getRow('$tablename', array(
			'status' => unsolved
		));
		if($soundfile==NULL){
			  $soundfile = $dc->getRow('$tablename', array(
			'status' => fresh
			));		
		}
		// Close database
		DatabaseController::disconnect($link);
		unset($link);
		
		if($soundfile==NULL){
			//no unsolved nor fresh files in database, return null?
			return null;
		}else{		
			//echo $soundfile['path'];  shows the path of the soundfile
			//echo 'returned';
			return $soundfile;
		}		
    }
	
	//------------------------------------------------------------------------what kind of functions do we need?
	
	//get audiofile --> store in variable for replay 
	// Include library files
    
	
	//play (replay) audio file
	
	//type in input -->add char to varchar-string
	
	//display string with blinking line at end 
	
	//correct input with back key
	
	//send data  (int audiofile_id, string data, int user_id)
	
	//get statistics
	
	//modify statistics
	
	//calculate precision (wasn't this uni work?!)
	
	//calculate star size and appearance
	
	//
	
}
