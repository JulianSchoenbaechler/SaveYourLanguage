<?php
/**
 * Save Your Language PHP engine configuration
 *
 * A class providing constants and static configuration variables.
 *
 * @author      Julian Schoenbaechler
 * @copyright   (c) 2017 University of the Arts, Zurich
 * @since       v0.0.1
 * @link        https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */

    namespace SaveYourLanguage;
   
    // Error reporting
	error_reporting(1);
	ini_set('display_errors', E_ALL);
    
    // Include library files
    require_once 'platform/php/classes/db/DatabaseController.php';
    
    use SaveYourLanguage\Database\DatabaseController;
    
	
    $link = DatabaseController::connect();
    $dc = new DatabaseController($link);
    
    $soundfile = $dc->getRow('soundfiles', array(
        'status' => unsolved
    ));
	if($soundfile==NULL){
		  $soundfile = $dc->getRow('soundfiles', array(
        'status' => fresh
		));		
	}
    if($soundfile==NULL){
		//no unsolved nor fresh files in database, return null?
		return null;
	}else{		
		//echo $soundfile['path'];  shows the path of the soundfile
		//echo 'returned';
		return $soundfile;
	}
    // Close database
    DatabaseController::disconnect($link);
    unset($link);
    
    echo 'test';
    exit();
