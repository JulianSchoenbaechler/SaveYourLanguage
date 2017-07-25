<?php
/*
 * Database control functions
 *
 * A class providing useful functions for handling database.
 *
 * Author           Julian Schoenbaechler
 * Copyright        (c) 2017 University of the Arts, Zurich
 * Included since   v0.0.1
 * Repository       https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage\Database;

// Include connection
require_once '../Config.php';
require_once 'DatabaseConnection.php';


class DatabaseController
{
    use DatabaseConnection;
    
    // Properties
    private $link;
    
    // Constructor
    public function __construct($link)
    {
        $this->link = $link;
    }
    
    // Insert row into database table
    public function insertRow($table, $values = array())
    {
        // Check table name
        if (!is_string($table)) {
            trigger_error("[DatabaseController] 'insertRow' expected argument 0 to be string.", E_USER_WARNING);
        } else {
            $table = $this->link->real_escape_string($table);
        }
        
        $sql1 = "INSERT INTO `$table`(";
        $sql2 = ") VALUES (";
        
        // For every value
        foreach ($values as $column => $value) {
            
            // Define columns to insert
            $element = $this->link->real_escape_string($column);
            $sql1 .= "`$element`, ";
            
            // Is the value a string or an integer?
            if (is_string($value)) {
                
                $element = $this->link->real_escape_string($value);
                $sql2 .= "'$element', ";
                
            } else {
                
                $sql2 .= (string)$value.", ";
                
            }
        }
        
        // Create query
        $sql1 = substr($sql1, 0, strlen($sql1) - 2);
        $sql2 = substr($sql2, 0, strlen($sql2) - 2);
        $sql = $sql1.$sql2.")";
        
        // Insert row
        if (!$this->link->query($sql)) {
            printf("MYSQL: Error %s\n", $this->link->error);
        }
    }
    
    // Update row in database table
    public function updateRow($table, $values = array(), $conditions = array())
    {
        // Check table name
        if (!is_string($table)) {
            trigger_error("[DatabaseController] 'updateRow' expected argument 0 to be string.", E_USER_WARNING);
        } else {
            $table = $this->link->real_escape_string($table);
        }
        
        $sql = "UPDATE `$table` SET ";
        
        // For every value
        foreach ($values as $column => $value) {
            
            // Define columns to insert
            $element = $this->link->real_escape_string($column);
            $sql .= "`$element`=";
            
            // Is the value a string or an integer?
            if (is_string($value)) {
                
                $element = $this->link->real_escape_string($value);
                $sql .= "'$element', ";
                
            } else {
                
                $sql .= (string)$value.", ";
                
            }
        }
        
        // First fragment of query
        $sql = substr($sql, 0, strlen($sql) - 2);
        
        // Are there conditions for updating?
        if (count($conditions) > 0) {
            
            $sql .= " WHERE ";
            
            // For every condition
            foreach ($conditions as $column => $value) {
                
                // Define columns to insert
                $element = $this->link->real_escape_string($column);
                $sql .= "`$element`=";
                
                // Is the value a string or an integer?
                if (is_string($value)) {
                    
                    $element = $this->link->real_escape_string($value);
                    $sql .= "'$element' AND ";
                    
                } else {
                    
                    $sql .= (string)$value." AND ";
                    
                }
            }
            
            // Second fragment of query
            $sql = substr($sql, 0, strlen($sql) - 5);
            
        } else {
            
            $sql .= " WHERE 1";
            
        }
        
        // Update row
        if (!$this->link->query($sql)) {
            printf("MYSQL: Error %s\n", $this->link->error);
        }
    }
    
    // Get row content from database table
    public function getRow($table, $conditions = array())
    {
        // Check table name
        if (!is_string($table)) {
            trigger_error("[DatabaseController] 'getRow' expected argument 0 to be string.", E_USER_WARNING);
        } else {
            $table = $this->link->real_escape_string($table);
        }
        
        $sql = "SELECT * FROM `$table` WHERE ";
        
        // For every condition
        foreach ($conditions as $column => $value) {
            
            // Define columns to insert
            $element = $this->link->real_escape_string($column);
            $sql .= "`$element`=";
            
            // Is the value a string or an integer?
            if (is_string($value)) {
                
                $element = $this->link->real_escape_string($value);
                $sql .= "'$element' AND ";
                
            } else {
                
                $sql .= (string)$value." AND ";
                
            }
        }
        
        // Strip 'AND' from query
        $sql = substr($sql, 0, strlen($sql) - 5);
        $sql .= " LIMIT 1";
        
        $result = $this->link->query($sql);
        
        // Check result
        if (!$result) {
            printf("MYSQL: Error %s\n", $this->link->error);
        }
        
        // If row was found
        if ($result->num_rows > 0) {
            
            $row = $result->fetch_array(MYSQLI_ASSOC);
            
            $result->free_result;
            
            // Return database content
            return $row;
        
        } else {
            
            // Not found a matching row
            return null;
            
        }
    }
    
    // Delete a specific row
    public function deleteRow($table, $conditions = array())
    {
        // Check table name
        if (!is_string($table)) {
            trigger_error("[DatabaseController] 'deleteRow' expected argument 0 to be string.", E_USER_WARNING);
        } else {
            $table = $this->link->real_escape_string($table);
        }
        
        $sql = "DELETE FROM `$table` WHERE ";
        
        // For every condition
        foreach ($conditions as $column => $value) {
            
            // Define columns to insert
            $element = $this->link->real_escape_string($column);
            $sql .= "`$element`=";
            
            // Is the value a string or an integer?
            if (is_string($value)) {
                
                $element = $this->link->real_escape_string($value);
                $sql .= "'$element' AND ";
                
            } else {
                
                $sql .= (string)$value." AND ";
                
            }
            
        }
        
        // Strip 'AND' from query
        $sql = substr($sql, 0, strlen($sql) - 5);
        
        // Check result
        if (!$this->link->query($sql)) {
            printf("MYSQL: Error %s\n", $this->link->error);
            return false;
        }
        
        return true;
    }
}
