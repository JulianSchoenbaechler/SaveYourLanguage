<?php
/**
 * User input
 *
 * A class that gets addressed when a user sends data from a transkription.
 *
 * Author           Marcel Arioli
 * Copyright        (c) 2017 University of the Arts, Zurich
 * Included since   v0.0.1
 * Repository       https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
	 // Define namespace
	namespace SaveYourLanguage\Input;

	
	echo 'start';
	
	require_once 'platform/php/classes/db/DatabaseController.php';
	require_once 'platform/php/classes/syl/DemoClassForMarcello.php';
    use SaveYourLanguage\Database\DatabaseController;
	use SaveYourLanguage\Statistics\DemoClassForMarcello;
	
	//connect
	$link = DatabaseController::connect();
    $dc = new DatabaseController($link);
	//we recieve transkription only atm
	$datastring = '';
	$datastring = $_POST['transkription'];
	
	$celoclass = new DemoClassForMarcello();
	$sum = $celoclass->CalculateSum($datastring); //we pass the transkription of the user to the calculateSum function that converts the string into an integer (discarding major-letters and spaces).
	
	//the variables we need to insert are: playerid, snippedid, datastring, evaluation and sum
	$playerid = 0;
	$snippedid = 0;
	$evaluation = 0;
	$dc->insertRow('transkriptions',array( //IMPORTANT! -> change 'transkriptions', thats only a placeholder.
		'playerid' => $playerid,
		'snippedid' => $snippedid,
		'data' => $datastring,
		'evaluation' => $evaluation,
		'sum' => $sum
	));
	
	//it's possible that this snipped now reached the needed number of transkriptions, so we do analysedata. analyse data only does stuff if needed.
	$celoclass->AnalyseData('transkriptions',$snippedid); //IMPORTANT! -> change 'tableName', thats only a placeholder.
	
	// Close database
    DatabaseController::disconnect($link);
    unset($link); 
    exit();