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

require_once 'platform/php/classes/db/DatabaseController.php';  


class DemoClassForMarcello
{
    // Properties
    private $aVariable;
    
    // Constructor
    public function __construct()
    {
        // Dos something
    }
    
    // We asume that there are 3 status for soundfiles: fresh -> no transkritipons, unsolved->more transkritipons required, solved...well, solved
	// (i used a table with ID, status (=fresh,unsolved or solved), path(example: https://uniserver/soundfiles/file00001))
    public function getSoundfile($tableName)
    {
      
		$link = DatabaseController::connect();
		$dc = new DatabaseController($link);
		
		$soundfile = $dc->getRow('$tableName', array(
			'status' => 'unsolved'
		));
		if($soundfile == null){
			  $soundfile = $dc->getRow('$tableName', array(
			'status' => 'fresh'
			));		
		}
		// Close database
		DatabaseController::disconnect($link);
		unset($link);
		
		if($soundfile == null){
			//no unsolved nor fresh files in database, return null?
			return null;
		}else{		
			//echo $soundfile['path'];  shows the path of the soundfile
			//echo 'returned';
			return $soundfile;
		}		
    }
	
	//this method compares sums of all transkriptions of a soundFile and updates the accuracy for that transcription
	public function AnalyseData($tableName, $fileIndex)
	{
			
		//get all transkriptions of the same file
		$datarows = array();
		$datarows = $dc->getRows('$tableName', array(
			'fileIndex' => $fileIndex
		));
		//first check if number of transkriptions was reached:
		if(sizeof($datarows)<5){ // 5 is temporary threshold -> global constant?
			return; //didn't reach threshold, don't analyse anything.
		}
		
		$averagevalue = 0;
		foreach($datarows as $value)
		{
			$averagevalue = $averagevalue + $value['sum'];
		}
		
		foreach($datarows as $value)
		{
			float $accuracy = 0;
			//calculating accuracy in percentage (0-100);
			if($averagevalue < $value['sum']){
				$accuracy = 100*($averagevalue/(float)$value['sum']);
			}else{
				$accuracy = 100*($value['sum']/(float)$averagevalue);
			}
			$dc->updateRow('$tableName', array(
			'evaluation' => (int)$accuracy			
			));			
		}
		
		//since those rows only get updated when threshold was reached, this snipped is done. we need to change the status in the soundfile database.
		$dc->updateRow('soundfiles',array(
			'status' = 'done'
		));		
	}	
	
	//takes a string and returns a sum of its characters
	public function CalculateSum($dataString)
	{
		$dataString = strtolower ($dataString); //makes everything lowercase, usefull because a and A have very different asci values.
		$sum = 0;
		for ($i = 0; $i < strlen($dataString); $i++){
			$value = ord ($dataString[$i])-96;
			if($value < 0){
				$value = 0;
			}
			$sum = $sum + $value; //adding the asci-value of each char. asci-value of a = 97, thats why substraction by 96 needs to hapen, otherwise failures aren't remarkable.
		}
		return $sum;
	}
}