<?php
/**
 * Terra Duo MySQL Table
 *
 * Provides basic CRUD functionality for MySQL Tables.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 0.2
 * @package Terra
 * @subpackage Table
 * @todo Handle relationships more naturally.
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Terra_Table_MySQL implements Terra_Table_Interface {

    protected static $TableData = array();

    protected $TableName;

    protected $errors = array('VALIDATION' => array());
    protected $error_delimiter_start = '<p class="error">';
    protected $error_delimiter_end = '</p>';

    protected static $QueryCount = 0;
    protected static $LastQuery = '';
    protected static $TimeSpentQuerying = 0;

    protected static $DefaultDatabaseConnection;
    protected $DatabaseConnection;

    public function __construct($name = null) {
        $this->TableName = $name;

        if (!isset(self::$TableData[$this->TableName]['Fields'])) {
            self::$TableData[$this->TableName]['Fields'] = array();
        }

        if (!isset(self::$TableData[$this->TableName]['Relationships'])) {
            self::$TableData[$this->TableName]['Relationships'] = array();
        }

        if(!empty(self::$DefaultDatabaseConnection)) {
            $this->DatabaseConnection = &self::$DefaultDatabaseConnection;
        }
    }

    public function discoverTable($file = null) {

    }

    public function resetDefaultDatabaseConnection() {
        self::$DefaultDatabaseConnection = null;
        $this->DatabaseConnection = null;
    }

    public function setField(Terra_Table_Field $Field) {
        self::$TableData[$this->TableName]['Fields'][$Field->Name] = $Field;
    }

    public function unsetField($FieldName) {
        unset(self::$TableData[$this->TableName]['Fields'][$FieldName]);
    }

    public function getField($FieldName) {
        if (isset(self::$TableData[$this->TableName]['Fields'][$FieldName])) {
            $buffer = &self::$TableData[$this->TableName]['Fields'][$FieldName];
            return $buffer;
        } else {
            return false;
        }
    }

    public function getTableName() {
        return $this->TableName;
    }

    public function setTableName($TableName) {
        $this->TableName = $TableName;
    }

    public function setTableData($TableArray) {
        if (is_array($TableArray)) {
            if (isset($TableArray['Name'])) {
                $this->TableName = $TableArray['Name'];
                self::$TableData[$this->TableName]['Name'] = $TableArray['Name'];
            }

            if (isset($TableArray['OrderBy'])) {
                self::$TableData[$this->TableName]['OrderBy'] = $TableArray['OrderBy'];
            }

            if (isset($TableArray['Fields'])) {
                foreach ($TableArray['Fields'] as $Field) {
                    self::$TableData[$this->TableName]['Fields'][$Field->Name] = $Field;
                }
            }
        } else {
            if (file_exists($TableArray)) {
                include $TableArray;
                $this->setTableData($table);
            } else {
                throw new Terra_Table_Exception("Could not include $TableArray for use in Terra_Table_MySQL->setTableData().");
            }
        }
    }

    public function logQuery($sql) {
        self::$QueryCount++;
        self::$LastQuery = $sql;
    }

    public function logQueryTime($microtime) {
        self::$TimeSpentQuerying = $microtime;
    }

    public function resetQueryStats() {
        self::$QueryCount = 0;
        self::$LastQuery = '';
        self::$TimeSpentQuerying = 0;
    }

    public function  __call($name,  $arguments) {
        if ((!stristr($name, 'get') OR !stristr($name, 'by')) and (!stristr($name, 'set') OR !stristr($name, 'by'))) {
            throw new Terra_Table_Exception("Invalid method call: \$Terra_Table_MySQL->$name() is an invalid method name.");
            return false;
        }

        if (stristr($name, 'set')) {
            $getBetween = $this->getBetween($name, 'set', 'By');
            $fieldsToSet = array();
            $where_clause_array = array();

            $where = explode('By', $name);
            $where = explode('And', $where[1]);
            $i = 0;
            foreach ($where as $oneWhere) {
                $where_clause_array[$this->dehumanizeField($oneWhere)] = $arguments[$i];
                $i++;
            }

            if (!empty($getBetween)) {
                $fieldsToSetBuffer = explode('And', $getBetween);
                foreach ($fieldsToSetBuffer as $field) {
                    $field = $field;
                    if (!empty($field)) {
                        $fieldsToSet[$this->dehumanizeField($field)] = $arguments[$i];
                    }
                    $i++;
                }
            }

            return $this->edit($where_clause_array, $fieldsToSet);

        } elseif(stristr($name, 'get')) {
            $getBetween = $this->getBetween($name, 'get', 'By');
            $fieldsToGet = array();
            $where_clause_array = array();

            if (!empty($getBetween)) {
                $fieldsToGetBuffer = explode('And', $getBetween);
                foreach ($fieldsToGetBuffer as $field) {
                    $field = $field;
                    if (!empty($field)) {
                        $fieldsToGet[] = $this->dehumanizeField($field);
                    }
                }
            }

            $where = explode('By', $name);
            $where = explode('And', $where[1]);
            $i = 0;
            foreach ($where as $oneWhere) {
                $where_clause_array[$this->dehumanizeField($oneWhere)] = $arguments[$i];
                $i++;
            }

            $args = isset($arguments[$i]) ? $arguments[$i] : null;

            $buffer = $this->getWhere($where_clause_array, $args);

            if (count($fieldsToGet) == 1) {
                return $buffer[0][$fieldsToGet[0]];
            } else {
                if (count($buffer) == 1) {
                    return $buffer[0];
                } else {
                    return $buffer;
                }

            }
        } else {
            throw new Terra_Table_Exception("Invalid method call: \$Terra_Table_MySQL->$name() is an invalid method name.");
            return false;
        }
    }

    public function dehumanizeField($field) {
        $pattern = "/(.)([A-Z])/";
        $replacement = "\\1_\\2";
        $field = lcfirst($field);
        return strtoupper(preg_replace($pattern, $replacement, $field));
    }

    public function getQueryCount() {
        return self::$QueryCount;
    }

    public function getTimeSpentQuerying() {
        return self::$TimeSpentQuerying;
    }

    public function getLastQuery() {
        return self::$LastQuery;
    }

    public function getBetween($content,$start,$end) {
        $r = explode($start, $content);
        if (isset($r[1])) {
            $r = explode($end, $r[1]);
            return $r[0];
        } else {
            return '';
        }
    }

    public function getDatabaseConnection() {
        return $this->DatabaseConnection;
    }

    public function setDatabaseConnection($connection) {
        if (empty(self::$DefaultDatabaseConnection)) {
            self::$DefaultDatabaseConnection = $connection;
            $this->DatabaseConnection = &self::$DefaultDatabaseConnection;
        } else {
            $this->DatabaseConnection = $connection;
        }

    }

    public function setErrorDelimiter($start = '<p class="error">', $end = '</p>') {
        $this->error_delimiter_start = $start;
        $this->error_delimiter_end = $end;
    }

    public function getValidationErrorString() {
        $return = '';

        foreach ($this->errors['VALIDATION'] as $error) {
            $return .= $this->error_delimiter_start.$error.$this->error_delimiter_end;
        }

        $this->errors['VALIDATION'] = array();

        return $return;
    }

    public function DataNotValid() {
        return (count(Terra_Table_Field::$Errors) > 0);
    }

    public function resetTableData() {
        self::$TableData[$this->TableName]['Fields'] = array();
        return true;
    }

    public function getValidationError($field) {
        if (isset($this->errors['VALIDATION'][$field])) {
            $buffer = $this->errors['VALIDATION'][$field];
            unset($this->errors['VALIDATION'][$field]);
            return $this->error_delimiter_start.$buffer.$this->error_delimiter_end;
        } else {
            return '';
        }
    }

    protected function isValid($field, &$value, &$data) {
        $valid = true;
        if (isset (self::$TableData[$this->TableName]['Fields'][$field])) {
            $valid = self::$TableData[$this->TableName]['Fields'][$field]->isValid(&$value, &$data, &$this);
        } else {
            $valid = false;
        }
        return $valid;
    }

    public function createFromRandom($data, $recordsToCreate = 10) {
        $created = 0;
        for ($i = $recordsToCreate; $i > 0; $i--) {
            $buffer = array();
            foreach($data as $field => $randomValues) {
                $buffer[$field] = $randomValues[rand(0, count($randomValues) - 1)];
            }
            if ($this->create($buffer)) {
                $created++;
            }
        }
        return $created;
    }

    public function create($data) {
        $sql = 'INSERT INTO `'.$this->TableName.'` (';
        $sql2 = '';

        if (isset (self::$TableData[$this->TableName]['Fields']['CREATED']) and !self::$TableData[$this->TableName]['Fields']['CREATED']->Disabled) {
            $data['CREATED'] = 'NOW()';
        }

        foreach (self::$TableData[$this->TableName]['Fields'] as $Name => $Field) {
            if (isset ($data[$Name])) {
                if ($this->isValid($Name, $data[$Name], $data)) {
                    $data[$Name] = mysql_escape_string($data[$Name]);

                    $sql .= '`'.$Name.'`, ';

                    if (!preg_match('/^[a-zA-Z_0-9]+\(\)$/', $data[$Name])) {
                        $sql2 .= "'{$data[$Name]}' , ";
                    } else {
                        $sql2 .= "{$data[$Name]} , ";
                    }
                }
            } elseif(isset($Field->Default) and !empty($Field->Default)) {
                $sql .= '`'.$Name.'`, ';

                if (!preg_match('/^[a-zA-Z_0-9]+\(\)$/', $Field->Default)) {
                    $sql2 .= "'{$Field->Default}' , ";
                } else {
                    $sql2 .= "{$Field->Default} , ";
                }
            } else {
                if ($Field->getValidationRule('Required')) {
                    Terra_Table_Field::addValidationError($Name, sprintf(Terra_Table_Field::$ErrorMessages['Required'], $this->humanizeField($Name)));
                }
            }
        }

        $sql = substr($sql, 0, strlen($sql) - 2).' ) VALUES (';
        $sql2 = substr($sql2, 0, strlen($sql2) - 3).' );';

        if (!$this->DataNotValid()) {
            if ($this->query($sql.$sql2)) {
                return mysql_insert_id();
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    protected function humanizeField($field) {
        if (!empty(self::$TableData[$this->TableName]['Fields'][$field]->HumanName)) {
            return self::$TableData[$this->TableName]['Fields'][$field]->HumanName;
        } else {
            return strtolower(str_ireplace('_',' ',$field));
        }
    }

    public function edit($IdOrWhereClause, $data) {

        if (is_integer($IdOrWhereClause)) {
            $IdOrWhereClause = array('ID' => $IdOrWhereClause);
        }

        if (isset(self::$TableData[$this->TableName]['Fields']['UPDATED']) and !self::$TableData[$this->TableName]['Fields']['UPDATED']->Disabled) {
            $data['UPDATED'] = 'NOW()';
        }
        $sql = "UPDATE `$this->TableName` SET ";

        $set = 0;


        foreach (self::$TableData[$this->TableName]['Fields'] as $Name => $Field) {
            if (isset ($data[$Name])) {
                if (!$Field->UpdateIfEmpty and (empty($data[$Name]) and $data[$Name] != '0')) {
                    continue;
                }

                if ($this->isValid($Name, $data[$Name], $data)) {
                    $data[$Name] = mysql_escape_string($data[$Name]);

                    $sql .= "`$Name` = ";

                    if (!preg_match('/^[a-zA-Z_0-9]+\(\)$/', $data[$Name])) {
                        $sql .= "'{$data[$Name]}' , ";
                    } else {
                        $sql .= "{$data[$Name]} , ";
                    }
                    $set++;
                }
            } else {
                if ($Field->getValidationRule('Required')) {
                    Terra_Table_Field::addValidationError($Name, sprintf(Terra_Table_Field::$ErrorMessages['Required'], $this->humanizeField($Name)));
                }
            }
        }

        if ($set > 0) {
            $sql = substr($sql, 0, strlen($sql) - 3);
            $sql .= $this->buildWhere($IdOrWhereClause);

            if (!$this->DataNotValid()) {
                return $this->query($sql);
            } else {
                return false;
            }
        } else {
            if (count($newData) == 0) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function delete($IdOrWhereClause) {

        if (is_integer($IdOrWhereClause)) {
            $IdOrWhereClause = array('ID' => $IdOrWhereClause);
        }

        if (isset(self::$TableData[$this->TableName]['Fields']['IS_DELETED']) and !self::$TableData[$this->TableName]['Fields']['IS_DELETED']->Disabled) {
            return $this->edit($IdOrWhereClause, array('IS_DELETED' => 1));
        } else {
            $sql = "DELETE FROM `$this->TableName` ";
            $sql .= $this->buildWhere($IdOrWhereClause);
            return $this->query($sql);
        }
    }

    public function restore($ID) {
        if (isset(self::$TableData[$this->TableName]['Fields']['IS_DELETED']) and !self::$TableData[$this->TableName]['Fields']['IS_DELETED']->Disabled) {
            return $this->edit($ID, array('IS_DELETED' => 0));
        } else {
            return false;
        }
    }

    protected function buildWhere($where_clause_array, $table = null) {
        $sql = "WHERE ";
        $fsize = count($where_clause_array);
        if ($table) {
            $table = "$table.";
        } else {
            $table = $this->TableName.'.';
        }
        $built = 0;
        if ($fsize > 0) {
            foreach ($where_clause_array as $field => $value) {
                $buffer = explode(' ', $field);

                $and_or = 'AND';
                $field = '';
                $condition = '=';

                if (isset($buffer[0]) and ($buffer[0] == 'OR' or $buffer[0] == 'AND')) {
                    $and_or = $buffer[0];
                } else {
                    $field = $buffer[0];
                }

                if (isset($buffer[1])) {
                    if (empty($field)) {
                        $field = $buffer[1];
                    } else {
                        $condition = $buffer[1];
                    }
                }

                if (isset($buffer[2]) and $buffer[2] == 0) {
                    $condition = $buffer[2];
                }

                if ($built > 0) {
                    $field = ' '.$and_or.' '.$table.$field.' '.$condition;
                } else {
                    $field = $table.$field.' '.$condition;
                    $built = 1;
                }

                $value = mysql_escape_string($value);

                if ($fsize > 1) {
                    $sql .= $field." '".$value."' ";
                    $fsize--;
                } else {
                    $sql .= $field." '".$value."' ";
                }
            }
            return " $sql ";
        } else {
            return '';
        }
    }

    protected function getRowsInResult($result) {
        $rows = array();
        while($row = mysql_fetch_assoc($result)) {
            Terra_Events::trigger('GetRow', $row);
            $rows[] = $row;
        }
        return $rows;
    }

    public function query($sql) {
        if (!is_resource($this->DatabaseConnection)) {
            throw new Terra_Table_Exception('To execute a query, you must set a valid database connection, using $Terra_Table_MySQL->setDatabaseConnection().');
        }
        $start = microtime();
        $result = mysql_query($sql, $this->DatabaseConnection);
        $this->logQueryTime(microtime() - $start);
        $this->logQuery($sql);

        if (!$result) {
            throw new Terra_Table_DatabaseException("An error occured with MySQL:
".mysql_error()."
Trying to execute the following query:
                    $sql");
        }
        return $result;
    }

    protected function buildLeftJoin(&$sql) {
        $leftJoin = '';
        foreach(self::$TableData[$this->TableName]['Fields'] as $Field) {
            $existsIn = $Field->getValidationRule('ExistsIn');
            if ($existsIn) {
                $sql = str_ireplace("`$this->TableName`.".$Field->Name, "`$this->TableName`.".$Field->Name.",
                        `".$existsIn['Table']."`.".$existsIn['ValueField']." as
                        ".str_ireplace('_ID', '', $Field->Name) , $sql);

                $leftJoin .= ' LEFT JOIN ';
                $where = array();
                if (isset($existsIn['Where'])) {
                    $where = $existsIn['Where'];
                }
                if (isset($existsIn['WhereCallback'])) {
                    $array = array('Where' => &$where);
                    Terra_Events::QuickEvent($existsIn['WhereCallback'], $array);
                }
                $leftJoin .= ' `'.$existsIn['Table'].'` ON (`'.$this->TableName.'`.'.$Field->Name.' = `'.$existsIn['Table'].'`.'.$existsIn['Field'];
                $where = $this->buildWhere($where, $existsIn['Table']);
                if (stristr($where, 'WHERE')) {
                    $leftJoin .= str_ireplace('WHERE', 'AND (', $where.')');
                }
                $leftJoin .= ') ';
            }
        }

        return $leftJoin;
    }

    public function getWhere($WhereClause, $args = array()) {
        if (!isset($args['Rows'])) {
            $args['Rows'] = 10;
        }
        if (!isset($args['Page'])) {
            $args['Page'] = 1;
        }
        if (!isset($args['OrderBy'])) {
            $args['OrderBy'] = array();
        }
        if (!isset($args['Fields'])) {
            $args['Fields'] = array();
        }

        $sql = "SELECT ";
        $fsize = count($args['Fields']);
        if ($fsize > 0) {
            foreach ($args['Fields'] as $field) {
                if ($fsize > 1) {
                    $sql .= "$field ,";
                    $fsize--;
                } else {
                    $sql .= "$field ";
                }
            }
        } else {
            $fsize = 0;
            foreach (self::$TableData[$this->TableName]['Fields'] as $Field) {
                $fsize++;
            }
            if ($fsize > 0) {
                foreach (self::$TableData[$this->TableName]['Fields'] as $Field) {
                    if ($fsize > 1) {
                        $sql .= "`$this->TableName`.".$Field->Name." , ";
                        $fsize--;
                    } else {
                        $sql .= "`$this->TableName`.".$Field->Name." ";
                    }
                }
            } else {
                $sql .= "`$this->TableName`.* ";
            }
        }
        $sql .= "FROM `$this->TableName` ";


        $buffer = $this->buildLeftJoin($sql);
        $sql .= $buffer;

        $sql .= $this->buildWhere($WhereClause);

        if (isset ($args['OrderBy']['Field']) and isset ($args['OrderBy']['Order'])) {
            $sql .= "ORDER BY `$this->TableName`.`".$args['OrderBy']['Field']."` ".$args['OrderBy']['Order']." ";
        }

        $offset = ($args['Page'] - 1) * $args['Rows'];
        $sql .= " LIMIT $offset, {$args['Rows']}";

        $result = $this->query($sql);
        if ($result) {
            return $this->getRowsInResult($result);
        } else {
            return array();
        }
    }

    public function get($args = array()) {
        return $this->getWhere(array(), $args);
    }

    public function count($where_clause_array = array()) {
        $sql = "SELECT COUNT(*) as count ";
        $sql .= "FROM `$this->TableName` ";

        $sql .= $this->buildWhere($where_clause_array);

        $result = $this->query($sql);
        if ($result) {
            $count = $this->getRowsInResult($result);
            return (int) $count[0]['count'];
        } else {
            return 0;
        }
    }

    /**
     * Create a has many relationship, where the related records have a field
     * that contains the ID of a record of the current table.
     * Useful for one-to-many relationships.
     * @param string $RelatedTable
     * @param string $RelatedTableField
     */
    function hasMany($RelatedTable, $RelatedTableField) {
        self::$TableData[$this->TableName]['Relationships'][$RelatedTable] = array(
                'RelType' => 'HasMany',
                'RelatedTable' => $RelatedTable,
                'RelatedTableField' => $RelatedTableField
        );
    }
    /**
     * Create a has many relationship, where the related records are
     * connected using a relationship table.
     * Useful for many-to-many relationships.
     * @param <type> $RelatedTable
     * @param <type> $RelationshipTable
     */
    function hasManyWithRelTable($RelatedTable, $RelationshipTable) {
        self::$TableData[$this->TableName]['Relationships'][$RelatedTable] = array(
                'RelType' => 'HasManyWithRelTable',
                'RelatedTable' => $RelatedTable,
                'RelationshipTable' => $RelationshipTable
        );
    }
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
    function addRelationshipItems($Relationship, $RecordID, $RelatedRecordIDs) {
        if (isset(self::$TableData[$this->TableName]['Relationships'][$RelatedTable])) {
            if (self::$TableData[$this->TableName]['Relationships'][$RelatedTable]['RelType'] == 'HasManyWithRelTable') {
                
            } else {
                throw new Terra_Table_Exception("$Relationship is not a relationship with a relationship table, therefore you can't add records to the relationship manually. Don't worry, it's done automatically for you! :)");
            }
        } else {
            throw new Terra_Table_Exception("$Relationship is not a valid relationship identifier.");
        }
    }
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
    function removeRelationshipItems($Relationship, $RecordID, $RelatedRecordIDs) {

    }
    /**
     * Get all the records related with a record in this table.
     * @param string $Relationship
     * @param integer $RecordID
     */
    function getRelationshipItems($Relationship, $RecordID) {
        
    }

}