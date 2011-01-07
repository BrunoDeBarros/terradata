<?php
/**
 * Terra Duo Table Interface
 *
 * Provides an interface for Tables.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 2010.2.21
 * @package TD
 * @subpackage Table
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
interface TD_Table_Interface {
    function __call($Name, $Arguments);
    /**
     * Get records from a database, without a WHERE clause.
     *
     * $Args = array(
     *  'Rows' => 10,
     *  'Page' => 1,
     *  'OrderBy => array(
     *          'Field' => 'FIELD_NAME',
     *          'Order' => 'DESC'
     *  ),
     * 'Fields' => array(
     *      'FIELD1', 'FIELD2', 'FIELD3 !=', 'FIELD4 >'
     * )
     * );
     * 
     * @param array $Args
     */
    function get($Args = array());
    /**
     * Set a Table's Definition Array.
     *
     * $TableArray = array(
     *  'Name' => 'table_name',
     *  ...,
     *  'Fields' => array(...)
     * )
     *
     * @param <type> $TableArray
     */
    function setTableData($TableArray);
    /**
     * Set the name of the table being used.
     * @param string $TableName
     */
    function setTableName($TableName);
    /**
     * Get the name of the table being used.
     * @return string
     */
    function getTableName();
    /**
     * Store an SQL query for logging purposes.
     * @param string $SQL
     */
    function logQuery($SQL);
    /**
     * Store how long a query took, for logging purposes.
     * @param integer $Microtime
     */
    function logQueryTime($Microtime);
    /**
     * Reset the query statistics.
     */
    function resetQueryStats();
    /**
     * Get the number of queries executed.
     * @return integer
     */
    function getQueryCount();
    /**
     * Get the amount of time spent querying the database, in microseconds.
     * @return float
     */
    function getTimeSpentQuerying();
    /**
     * Get this Table instance's database connection.
     * @return resource
     */
    function getDatabaseConnection();
    /**
     * Set the database connection for this Table instance.
     * @param resource $Connection
     */
    function setDatabaseConnection($Connection);
    /**
     * Set the error delimiters.
     * @param string $Start
     * @param string $End
     */
    function setErrorDelimiter($start = '<p class="error">', $end = '</p>');
    /**
     * Get a string with all validation errors,
     * appropriately delimited.
     * @return string
     */
    function getValidationErrorString();
    /**
     * Check if there were validation errors.
     * @return boolean
     */
    function DataNotValid();
    /**
     * Check if there were database errors.
     * @return boolean
     */
    function DatabaseError();
    /**
     * Set or update a field definition array.
     * @param string $FieldName
     * @param array $FieldArray
     */
    function setField($FieldName, $FieldArray);
    /**
     * Remove a field from the table's definition array.
     * @param string $FieldName
     */
    function unsetField($FieldName);
    /**
     * Get a field definition array.
     * @param string $FieldName
     * @return boolean
     */
    function getField($FieldName);
    /**
     * Get the validation error for a specific field.
     * @param string $FieldName
     * @return string
     */
    function getValidationError($FieldName);
    /**
     * Get an array containing all database errors.
     * @return array
     */
    function getDatabaseErrors();
    /**
     * Get an array containing ALL errors (Validation and Database).
     * @return array
     */
    function getAllErrors();
    /**
     * Create a number of records with the provided random data.
     * Returns the number of records created.
     * @param array $Data
     * @param integer $RecordsToCreate
     * @return integer
     */
    function createFromRandom($Data, $RecordsToCreate = 10);
    /**
     * Create a record with the provided data.
     * Returns the ID of the created record on success, false on failure.
     * @param array $Data
     * @return integer
     */
    function create($Data);
    /**
     * Get the last executed query.
     * @return string
     */
    function getLastQuery();
    /**
     * Edit a record.
     * @param integer $ID
     * @param array $Data
     * @return boolean
     */
    function edit($ID, $Data);
    /**
     * Delete a record. If there is a IS_DELETED field in the database,
     * it will set it as true, and not delete the record.
     * @param integer $ID
     */
    function delete($ID);
    /**
     * Restore a record. Only works if there is a IS_DELETED field in the database,
     * in which case it sets it as false.
     * @param integer $ID
     */
    function restore($ID);
    /**
     * Execute an arbitrary query.
     * @param string $SQL
     */
    function query($SQL);
    /**
     * Get a record, given a where clause array.
     *
     * $WhereClause = array(
     *      'FIELD' => 'value',
     *      'ANOTHER_FIELD >= ' => 5
     * )
     *
     * $Args = array(
     *  'Rows' => 10,
     *  'Page' => 1,
     *  'OrderBy => array(
     *          'Field' => 'FIELD_NAME',
     *          'Order' => 'DESC'
     *  ),
     * 'Fields' => array(
     *      'FIELD1', 'FIELD2', 'FIELD3 !=', 'FIELD4 >'
     * )
     * );
     *
     * @param array $WhereClause
     * @param array $Args
     */
    function getWhere($WhereClause, $Args = array());
    /**
     * Count rows in a table, given a where clause array.
     *
     * $WhereClause = array(
     *      'FIELD' => 'value',
     *      'ANOTHER_FIELD >= ' => 5
     * )
     *
     * @param array $WhereClause
     */
    function count($WhereClause = array());
}