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
    require_once 'classes/db/DatabaseController.php';
    
    use SaveYourLanguage\Database\DatabaseController;
    
    $link = DatabaseController::connect();
    $db = new DatabaseController($link);
    
    echo '<pre>';
    print_r($db->getRowsOrderedBy('stars', array(), array(), 'id', false, 30));
    echo '</pre>';
    
    DatabaseController::disconnect($link);
    
    echo 'finished';
    exit();
    
