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
require_once dirname(__FILE__).'/DatabaseConnection.php';


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
        
        $types = '';
        $param = array();
        
        // For every value
        foreach ($values as $column => $value) {
            
            // Is the value a string or an integer?
            if (is_string($value)) {
                
                $types .= 's';
                
            } else {
                
                $types .= 'i';
                
            }
            
            // Add parameters for later binding to prepared statement
            $param[] = &$values[$column];
            
            $element = $this->link->real_escape_string($column);
            $sql1 .= "`$element`, ";
            $sql2 .= '?, ';
            
        }
        
        // Create query
        $sql1 = substr($sql1, 0, strlen($sql1) - 2);
        $sql2 = substr($sql2, 0, strlen($sql2) - 2);
        $sql = $sql1.$sql2.")";
        
        // Add types parameter
        array_unshift($param, $types);
        
        // Prepare statement
        if ($stmt = $this->link->prepare($sql)) {
            
            // Bind parameters through array
            call_user_func_array(array($stmt, 'bind_param'), $param);
            
            // Execute and close query
            if ($stmt->execute()  === false)
                printf("MYSQL Statement: Error %s\n", $stmt->error);
            
            $stmt->close();
            
        } else {
            
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
        
        $types = '';
        $param = array();
        
        // For every value
        foreach ($values as $column => $value) {
            
            // Is the value a string or an integer?
            if (is_string($value)) {
                
                $types .= 's';
                
            } else {
                
                $types .= 'i';
                
            }
            
            // Define columns to insert
            $element = $this->link->real_escape_string($column);
            $sql .= "`$element`=?, ";
            
            // Add parameters for later binding to prepared statement
            $param[] = &$values[$column];
            
        }
        
        // First fragment of query
        $sql = substr($sql, 0, strlen($sql) - 2);
        
        // Are there conditions for updating?
        if (count($conditions) > 0) {
            
            $sql .= " WHERE ";
            
            // For every condition
            foreach ($conditions as $column => $value) {
                
                // Is the value a string or an integer?
                if (is_string($value)) {
                    
                    $types .= 's';
                    
                } else {
                    
                    $types .= 'i';
                    
                }
                
                // Define columns to insert
                $element = $this->link->real_escape_string($column);
                $sql .= "`$element`=? AND ";
                
                // Add parameters for later binding to prepared statement
                $param[] = &$conditions[$column];
                
            }
            
            // Second fragment of query
            $sql = substr($sql, 0, strlen($sql) - 5);
            
        } else {
            
            $sql .= " WHERE 1";
            
        }
        
        // Add types parameter
        array_unshift($param, $types);
        
        // Prepare statement
        if ($stmt = $this->link->prepare($sql)) {
            
            // Bind parameters through array
            call_user_func_array(array($stmt, 'bind_param'), $param);
            
            // Execute and close query
            if ($stmt->execute()  === false)
                printf("MYSQL Statement: Error %s\n", $stmt->error);
            
            $stmt->close();
            
        } else {
            
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
        
        $types = '';
        $param = array();
        $row = array();
        
        // For every condition
        foreach ($conditions as $column => $value) {
            
            // Is the value a string or an integer?
            if (is_string($value)) {
                
                $types .= 's';
                
            } else {
                
                $types .= 'i';
                
            }
            
            // Define columns to insert
            $element = $this->link->real_escape_string($column);
            $sql .= "`$element`=? AND ";
            
            // Add parameters for later binding to prepared statement
            $param[] = &$conditions[$column];
            
        }
        
        // Strip 'AND' from query
        $sql = substr($sql, 0, strlen($sql) - 5);
        $sql .= " LIMIT 1";
        
        // Add types parameter
        array_unshift($param, $types);
        
        // Prepare statement
        if ($stmt = $this->link->prepare($sql)) {
            
            // Bind parameters through array
            call_user_func_array(array($stmt, 'bind_param'), $param);
            
            // Execute
            if ($stmt->execute()  === false)
                printf("MYSQL Statement: Error %s\n", $stmt->error);
            
            unset($param);
            
            // Result
            $stmt->store_result();
            
            // Are there any selected rows?
            if ($stmt->num_rows > 0) {
            
                $param = array();
                $meta = $stmt->result_metadata();
                
                // Define parameters for result binding (where to store result values from row)
                // For every field...
                while ($field = $meta->fetch_field())
                    $param[] = &$row[$field->name];
                
                // Bind parameteres through array
                call_user_func_array(array($stmt, 'bind_result'), $param);
                
                // Fetch result for one row
                $stmt->fetch();
                
                // Close statement
                $stmt->close();
                
                // Return database content
                return $row;
        
            } else {
                
                return null;
                
            }
            
        } else {
            
            printf("MYSQL: Error %s\n", $this->link->error);
            
        }
        
        // Error
        return null;
    }
    
    // Get multiple rows from database table returned as array (every element one row)
    public function getRows($table, $conditions = array(), $max = 65535)
    {
        // Check table name
        if (!is_string($table)) {
            trigger_error("[DatabaseController] 'getRow' expected argument 0 to be string.", E_USER_WARNING);
        } else {
            $table = $this->link->real_escape_string($table);
        }
        
        $sql = "SELECT * FROM `$table` WHERE ";
        
        $types = '';
        $param = array();
        $rows = array();
        
        // For every condition
        foreach ($conditions as $column => $value) {
            
            // Is the value a string or an integer?
            if (is_string($value)) {
                
                $types .= 's';
                
            } else {
                
                $types .= 'i';
                
            }
            
            // Define columns to insert
            $element = $this->link->real_escape_string($column);
            $sql .= "`$element`=? AND ";
            
            // Add parameters for later binding to prepared statement
            $param[] = &$conditions[$column];
            
        }
        
        // Strip 'AND' from query
        $sql = substr($sql, 0, strlen($sql) - 5);
        $sql .= " LIMIT ".(string)$max;
        
        // Add types parameter
        array_unshift($param, $types);
        
        // Prepare statement
        if ($stmt = $this->link->prepare($sql)) {
            
            // Bind parameters through array
            call_user_func_array(array($stmt, 'bind_param'), $param);
            
            // Execute
            if ($stmt->execute()  === false)
                printf("MYSQL Statement: Error %s\n", $stmt->error);
            
            unset($param);
            
            // Result
            $stmt->store_result();
            
            // Are there any selected rows?
            if ($stmt->num_rows > 0) {
            
                $param = array();
                $row = array();
                $meta = $stmt->result_metadata();
                
                // Define parameters for result binding (where to store result values from row)
                // For every field...
                while ($field = $meta->fetch_field())
                    $param[] = &$row[$field->name];
                
                // Bind parameteres through array
                call_user_func_array(array($stmt, 'bind_result'), $param);
                
                // Fetch result for all rows
                while ($stmt->fetch())
                    array_push($rows, $row);
                
                // Close statement
                $stmt->close();
                
                // Return database content
                return $rows;
        
            } else {
                
                return null;
                
            }
            
        } else {
            
            printf("MYSQL: Error %s\n", $this->link->error);
            
        }
        
        // Error
        return null;
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
        
        $types = '';
        $param = array();
        
        // For every condition
        foreach ($conditions as $column => $value) {
            
            // Is the value a string or an integer?
            if (is_string($value)) {
                
                $types .= 's';
                
            } else {
                
                $types .= 'i';
                
            }
            
            // Define columns to insert
            $element = $this->link->real_escape_string($column);
            $sql .= "`$element`=? AND ";
            
            // Add parameters for later binding to prepared statement
            $param[] = &$conditions[$column];
            
        }
        
        // Strip 'AND' from query
        $sql = substr($sql, 0, strlen($sql) - 5);
        
        // Add types parameter
        array_unshift($param, $types);
        
        // Prepare statement
        if ($stmt = $this->link->prepare($sql)) {
            
            // Bind parameters through array
            call_user_func_array(array($stmt, 'bind_param'), $param);
            
            // Execute and close query
            if ($stmt->execute()  === false)
                printf("MYSQL Statement: Error %s\n", $stmt->error);
            
            $stmt->close();
            
        } else {
            
            printf("MYSQL: Error %s\n", $this->link->error);
            
        }
    }
}
