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
    
    echo $dc->deleteRow('testtabelle', array(
        'geschlecht' => maennlich,
		'name' => alfonse
    ));
    
    // Close database
    DatabaseController::disconnect($link);
    unset($link);
    
    echo 'test';
    exit();
