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
    public function updateRow($table, $values = array(), $equalCond = array(), $notCond = array())
    {
        // Check table name
        if (!is_string($table)) {
            trigger_error("[DatabaseController] 'updateRow' expected argument 0 to be string.", E_USER_WARNING);
        } else {
            $table = $this->link->real_escape_string($table);
        }

        $sql1 = "UPDATE `$table` SET ";

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
            $sql1 .= "`$element`=?, ";

            // Add parameters for later binding to prepared statement
            $param[] = &$values[$column];

        }

        // First fragment of query
        $sql1 = substr($sql1, 0, strlen($sql1) - 2);
        $sql1 .= " WHERE ";

        // Second fragment of the query
        $sql2 = "";

        // Are there equal conditions for updating?
        if (count($equalCond) > 0) {

            // For every condition
            foreach ($equalCond as $column => $value) {

                // Is the value a string or an integer?
                if (is_string($value)) {

                    $types .= 's';

                } else {

                    $types .= 'i';

                }

                // Define columns to insert
                $element = $this->link->real_escape_string($column);
                $sql2 .= "`$element`=? AND ";

                // Add parameters for later binding to prepared statement
                $param[] = &$equalCond[$column];

            }

            // Second fragment of query
            $sql2 = substr($sql2, 0, strlen($sql2) - 5);

        }

        // Are there not conditions for updating?
        if (count($notCond) > 0) {

            $sql2 .= " AND ";

            // For every condition
            foreach ($notCond as $column => $value) {

                // Is the value a string or an integer?
                if (is_string($value)) {

                    $types .= 's';

                } else {

                    $types .= 'i';

                }

                // Define columns to insert
                $element = $this->link->real_escape_string($column);
                $sql2 .= "`$element`!=? AND ";

                // Add parameters for later binding to prepared statement
                $param[] = &$notCond[$column];

            }

            // Second fragment of query
            $sql2 = substr($sql2, 0, strlen($sql2) - 5);

        }

        // Any conditions?
        $sql2 = strlen($sql2) > 0 ? $sql2 : "1";

        $sql = $sql1.$sql2;

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
    public function getRow($table, $equalCond = array(), $notCond = array())
    {
        // Check table name
        if (!is_string($table)) {
            trigger_error("[DatabaseController] 'getRow' expected argument 0 to be string.", E_USER_WARNING);
        } else {
            $table = $this->link->real_escape_string($table);
        }

        $sql1 = "SELECT * FROM `$table` WHERE ";
        $sql2 = "";

        $types = '';
        $param = array();
        $row = array();

        // Are there equal conditions for updating?
        if (count($equalCond) > 0) {

            // For every condition
            foreach ($equalCond as $column => $value) {

                // Is the value a string or an integer?
                if (is_string($value)) {

                    $types .= 's';

                } else {

                    $types .= 'i';

                }

                // Define columns to insert
                $element = $this->link->real_escape_string($column);
                $sql2 .= "`$element`=? AND ";

                // Add parameters for later binding to prepared statement
                $param[] = &$equalCond[$column];

            }

            // Strip 'AND' from query
            $sql2 = substr($sql2, 0, strlen($sql2) - 5);

        }

        // Are there not conditions for updating?
        if (count($notCond) > 0) {

            $sql2 .= " AND ";

            // For every condition
            foreach ($notCond as $column => $value) {

                // Is the value a string or an integer?
                if (is_string($value)) {

                    $types .= 's';

                } else {

                    $types .= 'i';

                }

                // Define columns to insert
                $element = $this->link->real_escape_string($column);
                $sql2 .= "`$element`!=? AND ";

                // Add parameters for later binding to prepared statement
                $param[] = &$notCond[$column];

            }

            // Second fragment of query
            $sql2 = substr($sql2, 0, strlen($sql2) - 5);

        }

        $sql2 = strlen($sql2) > 0 ? $sql2 : "1";
        $sql = $sql1.$sql2." LIMIT 1";

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
                $finalRow = array();
                $meta = $stmt->result_metadata();

                // Define parameters for result binding (where to store result values from row)
                // For every field...
                while ($field = $meta->fetch_field())
                    $param[] = &$row[$field->name];

                // Save field names seperate in array (hard copy to prevent reference mess)
                $fieldNames = array_keys($row);

                // Bind parameteres through array
                call_user_func_array(array($stmt, 'bind_result'), $param);

                // Fetch result for one row
                $stmt->fetch();

                // Hard copy every field
                foreach ($fieldNames as $key)
                    $finalRow[$key] = $row[$key];

                // Close statement
                $stmt->close();

                // Return database content
                return $finalRow;

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
    public function getRows($table, $equalCond = array(), $notCond = array(), $max = 65535)
    {
        // Check table name
        if (!is_string($table)) {
            trigger_error("[DatabaseController] 'getRow' expected argument 0 to be string.", E_USER_WARNING);
        } else {
            $table = $this->link->real_escape_string($table);
        }

        $sql1 = "SELECT * FROM `$table` WHERE ";
        $sql2 = "";

        $types = '';
        $param = array();
        $rows = array();

        // Are there equal conditions for updating?
        if (count($equalCond) > 0) {

            // For every condition
            foreach ($equalCond as $column => $value) {

                // Is the value a string or an integer?
                if (is_string($value)) {

                    $types .= 's';

                } else {

                    $types .= 'i';

                }

                // Define columns to insert
                $element = $this->link->real_escape_string($column);
                $sql2 .= "`$element`=? AND ";

                // Add parameters for later binding to prepared statement
                $param[] = &$equalCond[$column];

            }

            // Strip 'AND' from query
            $sql2 = substr($sql2, 0, strlen($sql2) - 5);

        }

        // Are there not conditions for updating?
        if (count($notCond) > 0) {

            $sql2 .= " AND ";

            // For every condition
            foreach ($notCond as $column => $value) {

                // Is the value a string or an integer?
                if (is_string($value)) {

                    $types .= 's';

                } else {

                    $types .= 'i';

                }

                // Define columns to insert
                $element = $this->link->real_escape_string($column);
                $sql2 .= "`$element`!=? AND ";

                // Add parameters for later binding to prepared statement
                $param[] = &$notCond[$column];

            }

            // Second fragment of query
            $sql2 = substr($sql2, 0, strlen($sql2) - 5);

        }

        $sql2 = strlen($sql2) > 0 ? $sql2 : "1";
        $sql = $sql1.$sql2." LIMIT ".(string)$max;

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

                // Save field names seperate in array (hard copy to prevent reference mess)
                $fieldNames = array_keys($row);

                // Bind parameteres through array
                call_user_func_array(array($stmt, 'bind_result'), $param);

                // Fetch result for all rows
                $i = 0;

                while ($stmt->fetch()) {

                    // Hard copy every field
                    foreach ($fieldNames as $key)
                        $rows[$i][$key] = $row[$key];

                    $i++;

                }

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

    // Get multiple rows from database table returned as array (every element one row)
    // The rows will be read by specified order
    public function getRowsOrderedBy($table, $equalCond = array(), $notCond = array(), $orderBy = null, $ascending = true, $max = 65535)
    {
        // Check table name
        if (!is_string($table)) {
            trigger_error("[DatabaseController] 'getRow' expected argument 0 to be string.", E_USER_WARNING);
        } else {
            $table = $this->link->real_escape_string($table);
        }

        $sql1 = "SELECT * FROM `$table` WHERE ";
        $sql2 = "";

        $types = '';
        $param = array();
        $rows = array();

        // Are there equal conditions for updating?
        if (count($equalCond) > 0) {

            // For every condition
            foreach ($equalCond as $column => $value) {

                // Is the value a string or an integer?
                if (is_string($value)) {

                    $types .= 's';

                } else {

                    $types .= 'i';

                }

                // Define columns to insert
                $element = $this->link->real_escape_string($column);
                $sql2 .= "`$element`=? AND ";

                // Add parameters for later binding to prepared statement
                $param[] = &$equalCond[$column];

            }

            // Strip 'AND' from query
            $sql2 = substr($sql2, 0, strlen($sql2) - 5);

        }

        $sql2 = strlen($sql2) > 0 ? $sql2 : "1";

        // Are there not conditions for updating?
        if (count($notCond) > 0) {

            $sql2 .= " AND ";

            // For every condition
            foreach ($notCond as $column => $value) {

                // Is the value a string or an integer?
                if (is_string($value)) {

                    $types .= 's';

                } else {

                    $types .= 'i';

                }

                // Define columns to insert
                $element = $this->link->real_escape_string($column);
                $sql2 .= "`$element`!=? AND ";

                // Add parameters for later binding to prepared statement
                $param[] = &$notCond[$column];

            }

            // Second fragment of query
            $sql2 = substr($sql2, 0, strlen($sql2) - 5);

        }

        // Are there not conditions for ordering?
        if ($orderBy !== null) {

            $sql2 .= " ORDER BY ";

            $element = $this->link->real_escape_string($orderBy);
            $sql2 .= "`$element`";
            $sql2 .= $ascending ? " ASC" : " DESC";

        }

        $sql = $sql1.$sql2." LIMIT ".(string)$max;

        // Add types parameter
        if (count($param) > 0)
            array_unshift($param, $types); 

        // Prepare statement
        if ($stmt = $this->link->prepare($sql)) {

            // Bind parameters through array
            if (count($param) > 0)
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

                // Save field names seperate in array (hard copy to prevent reference mess)
                $fieldNames = array_keys($row);

                // Bind parameteres through array
                call_user_func_array(array($stmt, 'bind_result'), $param);

                // Fetch result for all rows
                $i = 0;

                while ($stmt->fetch()) {

                    // Hard copy every field
                    foreach ($fieldNames as $key)
                        $rows[$i][$key] = $row[$key];

                    $i++;

                }

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
    public function deleteRow($table, $equalCond = array(), $notCond = array())
    {
        // Check table name
        if (!is_string($table)) {
            trigger_error("[DatabaseController] 'deleteRow' expected argument 0 to be string.", E_USER_WARNING);
        } else {
            $table = $this->link->real_escape_string($table);
        }

        $sql1 = "DELETE FROM `$table` WHERE ";
        $sql2 = "";

        $types = '';
        $param = array();

        // Are there equal conditions for updating?
        if (count($equalCond) > 0) {

            // For every condition
            foreach ($equalCond as $column => $value) {

                // Is the value a string or an integer?
                if (is_string($value)) {

                    $types .= 's';

                } else {

                    $types .= 'i';

                }

                // Define columns to insert
                $element = $this->link->real_escape_string($column);
                $sql2 .= "`$element`=? AND ";

                // Add parameters for later binding to prepared statement
                $param[] = &$equalCond[$column];

            }

            // Strip 'AND' from query
            $sql2 = substr($sql2, 0, strlen($sql2) - 5);

        }

        // Are there not conditions for updating?
        if (count($notCond) > 0) {

            $sql2 .= " AND ";

            // For every condition
            foreach ($notCond as $column => $value) {

                // Is the value a string or an integer?
                if (is_string($value)) {

                    $types .= 's';

                } else {

                    $types .= 'i';

                }

                // Define columns to insert
                $element = $this->link->real_escape_string($column);
                $sql2 .= "`$element`!=? AND ";

                // Add parameters for later binding to prepared statement
                $param[] = &$notCond[$column];

            }

            // Second fragment of query
            $sql2 = substr($sql2, 0, strlen($sql2) - 5);

        }

        $sql2 = strlen($sql2) > 0 ? $sql2 : "1";
        $sql = $sql1.$sql2;

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

    // Empty a whole table
    public function emptyTable($table)
    {
        // Check table name
        if (!is_string($table)) {
            trigger_error("[DatabaseController] 'deleteRow' expected argument 0 to be string.", E_USER_WARNING);
        } else {
            $table = $this->link->real_escape_string($table);
        }

        $sql = "TRUNCATE TABLE `$table`";

        // Execute
        if ($this->link->query($sql) === false) {

            printf("MYSQL: Error %s\n", $this->link->error);

        }
    }

    // Execute custom SQL query
    // Provide valid prepared statements
    // Use this function if you know what you're doing
    public function executeCustomQuery($query, $values = array())
    {
        // Check query
        if (!is_string($query)) {
            trigger_error("[DatabaseController] 'executeCustomQuery' expected argument 0 to be string.", E_USER_WARNING);
        }

        $types = '';
        $param = array();
        $rows = array();

        // For every value
        for ($i = 0; $i < count($values); $i++) {

            // Is the value a string or an integer?
            if (is_string($values[$i])) {

                $types .= 's';

            } else {

                $types .= 'i';

            }

            // Add parameters for later binding to prepared statement
            $param[] = &$values[$i];

        }

        // Add types parameter
        if (count($param) > 0)
            array_unshift($param, $types);

        // Prepare statement
        if ($stmt = $this->link->prepare($query)) {

            // Bind parameters through array
            if (count($param) > 0)
                call_user_func_array(array($stmt, 'bind_param'), $param);

            // Execute and close query
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

                // Save field names seperate in array (hard copy to prevent reference mess)
                $fieldNames = array_keys($row);

                // Bind parameteres through array
                call_user_func_array(array($stmt, 'bind_result'), $param);

                // Fetch result for all rows
                $i = 0;

                while ($stmt->fetch()) {

                    // Hard copy every field
                    foreach ($fieldNames as $key)
                        $rows[$i][$key] = $row[$key];

                    $i++;

                }

                // Close statement
                $stmt->close();

                // Return database content
                return $rows;

            } else {

                // Close statement
                $stmt->close();

                return null;

            }

        } else {

            printf("MYSQL: Error %s\n", $this->link->error);

        }
    }
}
