<?php
/*
 * User statistic
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

require_once dirname(__FILE__).'/../db/DatabaseController.php';

use SaveYourLanguage\Database\DatabaseController;

class Statistics
{    
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
	public function analyseData($tableName, $fileIndex)
	{
		$link = DatabaseController::connect();
		$dc = new DatabaseController($link);
		
		//get all transkriptions of the same file
		$datarows = array();
		$datarows = $dc->getRows($tableName, array(
			'snippedid' => $fileIndex
		));
		
		//first check if number of transkriptions was reached:
		if(sizeof($datarows)<5){ // 5 is temporary threshold -> global constant?
			return; //didn't reach threshold, don't analyse anything.
		}
		
		//calculate the average of that soundsnipped.
		$averagevalue = 0;
		$counter = 0;
		foreach($datarows as $value)
		{
			$averagevalue = $averagevalue + $value['sum'];
			$counter = $counter + 1;
		}
		$averagevalue = (float)$averagevalue/(float)$counter;
		
		//calculate the percentage with the average for each transkription of that snipped.
		foreach($datarows as $value)
		{
			$accuracy = 0;
			//calculating accuracy in percentage (0-100);
			if($averagevalue < $value['sum']){
				$accuracy = 100*((float)$averagevalue/(float)$value['sum']);
			}else{
				$accuracy = 100*((float)$value['sum']/(float)$averagevalue);
			}
			//update row needs 2 arrays, first the values, then the conditions.
			$dc->updateRow($tableName, array(
				'evaluation' => (int)$accuracy,
				'status' => 'done'
			), array(
				'snippedid'=>$fileIndex,'sum'=>$value['sum']
			));			
		}
		
		//since those rows only get updated when threshold was reached, this snipped is done. we need to change the status in the soundfile database.
		$dc->updateRow('soundfiles',array(
			'status' => 'done'
		),array(
			'id' => $fileIndex
		));
		
		// Close database
		DatabaseController::disconnect($link);
		unset($link);
	}	
	
	//takes a string and returns a sum of its characters
	public function calculateSum($dataString)
	{
		//makes everything lowercase, usefull because a and A have very different asci values.
		$dataString = strtolower ($dataString); 
		$sum = 0;
		
		for ($i = 0; $i < strlen($dataString); $i++){
			
			$value = ord ($dataString[$i])-96;
			
			if($value < 0){
				$value = 0;
			}
			
			//adding the asci-value of each char. asci-value of a = 97, thats why substraction by 96 needs to hapen, otherwise failures aren't remarkable.
			$sum = $sum + $value; 
		}
		
		return $sum;
	}
	
	//a function that returns an integer between 0-100 that represents the players accuracy of the last timeperiod (of the starfield)
	public function playerAccuracy($playerID)
	{
		//get all transkriptions of the player (if the table gets reseted each week, this is kind of weekly accuracy)
		$datarows = array();
		$datarows = $dc->getRows($tableName, array(
			'playerid' => $playerID
		));
		
		$averagevalue = 0;
		$counter = 0;
		
		foreach($datarows as $value)
		{
			//we only take evaluated transkriptions into consideration. 
		    if($value['status'] === 'done' && $value['evaluation'] > 0){
				
			    $averagevalue = $averagevalue + $value['evaluation'];
			    $counter = $counter + 1;
				
			}
		}
		
		//the averagevalue is the average accuracy of all datarows of that player.
		$averagevalue = (float)$averagevalue/(float)$counter;
		
		return $averagevalue;
	}
	
	    /**
     * a function that returns an array:
     *
     * [0] = count of all transkriptions
     * [1] = count of transkriptions during $timeperiod
     * [2] = boolean (0=false,1=true). True = user is more active than during the last time period, false = less active
     * [3] = the accuracy of the player's transkriptions during the $timeperiod.
     * [4] = boolean (0=less accurate, 1=more accurate than last period)
     * [5] = number of zodiacs during $timeperiod
     *
     * important: if you want to update the playerinfo at the end of a timeperiod, set $setInfo = true
     */
    public function getPlayerInfo($playerID,$tableName,$timeperiod = 30,$setInfo = false)
    {
        $link = DatabaseController::connect();
        $dc = new DatabaseController($link);
        
        //infoarray->contains the return values (all integer)
        $info = array();
        
        //absolute transkriptions count:
        $allrows = $dc->getRows($tableName,array(
            'playerid' => $playerID
        ));
        $info[0] = count($allrows);
        
        $dataRows = array();        
        $dataRows = $dc->getRowsOrderedBy($tableName, array(
            'playerid' => $playerID,
        ),array(),
            'timestamp',
            false,
            $timeperiod
        );
        
        //$stats[0] = this timeperiod stats, stats[1] = from last time period
        $stats = $dc->getRowsOrderedBy('playerstats',array(
            'playerid' => $playerID
        ),array(),
            'timestamp',
            false,
            2
        );
        
        //transkription count during timeperiod
        $info[1] = count($dataRows);
        if($stats[1]['count'] > $info[1]){
            
            //user is less active than during last timeperiod
            $info[2] = 0;
            
        }else{
            
            //user is more active than during last timeperiod
            $info[2] = 1;
            
        }
        
        //player accuracy during this timeperiod
        $statisticclass = new Statistics();
        $info[3] = $statisticclass->getPlayerAccuracy($playerID,$tableName,$timeperiod);
        
        if($info[3] > $stats[1]['accuracy']){
            
            //player is more accurate than during last timeperiod, 1 = true
            $info[4] = 1;
            
        }else{
            
            //player is less accurate than during last timeperiod, 0 = false
            $info[4] = 0;
            
        }
        
        //zodiaccount. $stats[0] are the stats of this timeperiod
        $info[5] = $stats[0]['zodiacs'];
        
        //if setinfo = true, update the playerstats in the playerstats table with the gathered info.
        if($setInfo == true){
            
            //the needed stats are inside the info-array. but the comparison infos don't get saved, thats why we only need info 1, 3 and 5.
            $statisticclass->updatePlayerStats($playerID, array(
                $info[1],
                $info[3],
                $info[5]
            ),
                $stats[0]['timestamp']                                   
            );
            
        }
        
        // Close database
        DatabaseController::disconnect($link);
        unset($link);
        
        //finally returning the info
        return $info;
    }
    
    /**
     *updates the playerstats in the playerstats table
     *
     *$info should be an array with 3 entries:
     *[0] = transkription count (of this timeperiod)
     *[1] = accuracy (of this timeperiod)
     *[2] = zodiac count (of this time period)
     */
    public function updatePlayerStats($playerID, $info, $timestamp)
    {
        $link = DatabaseController::connect();
        $dc = new DatabaseController($link);
        
        $dc->updateRow('playerstats',array(
            'count' => $info[0],
            'accuracy' => $info[1],
            'zodiacs' => $info[2]
        ),array(
            'playerid' => $playerID,
            'timestamp' => $timestamp
        ));
        
        // Close database
        DatabaseController::disconnect($link);
        unset($link);
    }
    
    /**
     *adds the playerstats table a new row with a new timestamp
     */
    public function insertPlayerStats($playerID)
    {
        $link = DatabaseController::connect();
        $dc = new DatabaseController($link);
        
        //get the timestamp
        $timeStamp = time();
        
        //we insert a new row with id and timestamp. the rest of the stats is still empty.
        $dc->insertRow('playerstats',array(
            'playerid' => $playerID,
            'timestamp' => $timeStamp
        ));
        
        // Close database
        DatabaseController::disconnect($link);
        unset($link);        
    }

}
