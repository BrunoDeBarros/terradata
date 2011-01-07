<?php
/**
 * Terra Duo Table Interface
 *
 * Provides an interface for Tables.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 0.2
 * @package Terra
 * @subpackage Table
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
interface Terra_Table_Interface {

    function __construct($name = null);

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
     * Removes the default database connection of Terra_Table instances.
     */
    function resetDefaultDatabaseConnection();
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
     * Set a field for use in the database.
     * @param Terra_Table_Field $Field
     */
    function setField(Terra_Table_Field $Field);
    /**
     * Remove a field from the table's definition array.
     * @param string $FieldName
     */
    function unsetField($FieldName);
    /**
     * Get a field's instance.
     * @param string $FieldName
     * @return Terra_Table_Field
     */
    function getField($FieldName);
    /**
     * Get the validation error for a specific field.
     * @param string $FieldName
     * @return string
     */
    function getValidationError($FieldName);
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
     *
     * $IdOrWhereClause can either be an integer specifying the ID of a record,
     * or a where clause array.
     *
     * @param integer|array $IdOrWhereClause
     * @param array $Data
     * @return boolean
     */
    function edit($IdOrWhereClause, $Data);
    /**
     * Delete a record. If there is a IS_DELETED field in the database,
     * it will set it as true, and not delete the record.
     *
     * $IdOrWhereClause can either be an integer specifying the ID of a record,
     * or a where clause array.
     * 
     * @param integer|array $IdOrWhereClause
     */
    function delete($IdOrWhereClause);
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

    /**
     * Remove all the table data associated with the current instance's table.
     * @return boolean
     */
    function resetTableData();

    /**
     * Discover a table. This will look at an existing table and create a
     * table definition array for it. It will try to be as complete as possible.
     * In MySQL, for example, it will add validation rules based on the maximum
     * length of a field, and based on its type (it will use inArray if it's ENUM, etc.)
     *
     * Optionally, it can store the table array in a file in a specified folder.
     * @param string $file
     * @return array
     */
    function discoverTable($file = null);

    /**
     * Create a has many relationship, where the related records have a field
     * that contains the ID of a record of the current table.
     * Useful for one-to-many relationships.
     * @param string $RelatedTable
     * @param string $RelatedTableField
     */
    function hasMany($RelatedTable, $RelatedTableField);
    /**
     * Create a has many relationship, where the related records are
     * connected using a relationship table.
     * Useful for many-to-many relationships.
     * @param <type> $RelatedTable
     * @param <type> $RelationshipTable
     */
    function hasManyWithRelTable($RelatedTable, $RelationshipTable);
    /**
     * Make two records related.
     * Only necessary for relationships with a relationship table.
     *
     * $RelatedRecordIDs can be an integer (the ID of one record) or an array of IDs.
     *
     * @param string $Relationship
     * @param integer $RecordID
     * @param integer|array $RelatedRecordIDs
     */
    function addRelationshipItems($Relationship, $RecordID, $RelatedRecordIDs);
    /**
     * Remove the relationship between two records.
     * Only necessary for relationships with a relationship table.
     *
     * $RelatedRecordIDs can be an integer (the ID of one record) or an array of IDs.
     *
     * @param string $Relationship
     * @param integer $RecordID
     * @param integer|array $RelatedRecordIDs
     */
    function removeRelationshipItems($Relationship, $RecordID, $RelatedRecordIDs);
    /**
     * Get all the records related with a record in this table.
     * @param string $Relationship
     * @param integer $RecordID
     */
    function getRelationshipItems($Relationship, $RecordID);
}