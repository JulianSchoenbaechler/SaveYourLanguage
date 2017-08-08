<?php
/**
 * User input
 *
 * A class that gets addressed when a user sends data from a transkription.
 *
 * Author           Marcel Arioli
 * Copyright        (c) 2017 University of the Arts, Zurich
 * Included since   v0.0.1
 * Repository       https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
			//	namespace SaveYourLanguage\sequential;
	
				require_once dirname(__FILE__).'/../db/DatabaseController.php';
    require_once dirname(__FILE__).'/../syl/DemoClassForMarcello.php';
    require_once dirname(__FILE__).'/../login/Login.php';
				use SaveYourLanguage\Database\DatabaseController;
				use SaveYourLanguage\Statistics\DemoClassForMarcello;
				use SaveYourLanguage\Login\Login;


//connect

$link = DatabaseController::connect();
$dc = new DatabaseController($link);
//we recieve transkription only atm

$datastring = $_POST['transkription'];

$celoclass = new DemoClassForMarcello();
$sum = $celoclass->CalculateSum($datastring);//we pass the transkription of the user to the calculateSum function that converts the string into an integer (discarding major-letters and spaces).
//the variables we need to insert are: playerid, snippedid, datastring, evaluation and sum

$playerID = Login::isUserLoggedIn();
$snippedID = 001;
$evaluation = 0;
$dc->insertRow('transkriptions',array(
		'playerid' => $playerID,
		'snippedid' => $snippedID,
		'data' => $datastring,
		'evaluation' => $evaluation,
		'sum' => $sum,
		'status' => 'unsolved'
));
echo 'inserted';

DatabaseController::disconnect($link);
unset($link);
//it's possible that this snipped now reached the needed number of transkriptions, so we do analysedata. analyse data only does stuff if needed.
$celoclass->AnalyseData('transkriptions',$snippedID);
echo 'did analyse';
exit();
