<?php
/*
 * User input
 *
 * A script that gets addressed when a user sends data from a transkription.
 *
 * author           Marcel Arioli
 * copyright        (c) 2017 University of the Arts, Zurich
 * since            v0.0.1
 * link             https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */

namespace SaveYourLanguage;

// Include library files
require_once dirname(__FILE__).'/classes/db/DatabaseController.php';
require_once dirname(__FILE__).'/classes/syl/Statistics.php';
require_once dirname(__FILE__).'/classes/login/Login.php';
    
use SaveYourLanguage\Database\DatabaseController;
use SaveYourLanguage\Statistics\Statistics;
use SaveYourLanguage\Login\Login;

//connect
$link = DatabaseController::connect();
$dc = new DatabaseController($link);

//we recieve transkription only atm
$datastring = $_POST['transkription'];

//we pass the transkription of the user to the calculateSum function that converts the string into an integer (discarding major-letters and spaces).
$statisticsClass = new Statistics();
$sum = $statisticsClass->calculateSum($datastring);

//the variables we need to insert are: playerid, snippedid, datastring, evaluation, sum and status
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
//disconnect
DatabaseController::disconnect($link);
unset($link);

//it's possible that this snipped now reached the needed number of transkriptions, so we do analysedata. analyse data only does stuff if needed.
$statisticsClass->analyseData('transkriptions',$snippedID);

exit();
