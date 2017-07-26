<?php
/*
 * Database open/close functions
 *
 * Implementation of opening and closing MySQL database.
 *
 * Author           Julian Schoenbaechler
 * Copyright        (c) 2017 University of the Arts, Zurich
 * Included since   v0.0.1
 * Repository       https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage\Database;

require_once dirname(__FILE__).'/../Config.php';

use SaveYourLanguage\Config;

trait DatabaseConnection
{
    // Open connection
    public static function connect()
    {
        // Database config
        $sqlconfig = array();
        $sqlconfig['username']	= Config::DB_MYSQL_USERNAME;
        $sqlconfig['password']	= Config::DB_MYSQL_PASSWORD;
        $sqlconfig['host']		= Config::DB_MYSQL_HOST;
        $sqlconfig['dbname']	= Config::DB_MYSQL_NAME;
        $sqlconfig['port']		= Config::DB_MYSQL_PORT;
        
        $link = mysqli_init();
        $success = true;
        
        // Connect with MySQL database
        if (is_null($sqlconfig['port'])) {
            $success = $link->real_connect(
                $sqlconfig['host'],
                $sqlconfig['username'],
                $sqlconfig['password'],
                $sqlconfig['dbname'],
                $sqlconfig['port']
            );
        } else {
            $success = $link->real_connect(
                $sqlconfig['host'],
                $sqlconfig['username'],
                $sqlconfig['password'],
                $sqlconfig['dbname']
            );
        }
        
        unset($sqlconfig);

        // Check connection
        if (!$success) {
            printf("Error: Unable to connect to MySQL.".PHP_EOL);
            printf("[Debug] errno: ".mysqli_connect_errno().PHP_EOL);
            printf("[Debug] error: ".mysqli_connect_error().PHP_EOL);
            exit();
        }
        
        // Set encoding
        if (!$link->set_charset("utf8")) {
            printf("MYSQL: Error loading character set utf8: %s\n", $link->error);
            exit();
        }
        
        return $link;
    }
    
    // Close specific connection
    public static function disconnect($link)
    {
        $link->close();
    }
}
