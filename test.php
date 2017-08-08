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
	
	require_once 'platform/php/classes/db/DatabaseController.php';	
    require_once 'platform/php/classes/syl/DemoClassForMarcello.php';
	 // Include library files
	use SaveYourLanguage\Database\DatabaseController;
	use SaveYourLanguage\Statistics\DemoClassForMarcello;
	
    $link = DatabaseController::connect();
    $dc = new DatabaseController($link);
	
	$celoClass = new DemoClassForMarcello();
	
	$teststring = "this is some test string";
	$sum = $celoClass->CalculateSum($teststring);
	
	echo $sum;
    // Close database
    DatabaseController::disconnect($link);
    unset($link);
    
    exit();
