<?php
/**
 * Terra Duo MySQL Table
 *
 * Provides basic CRUD functionality for MySQL Tables.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 2010.2.21
 * @package TD
 * @subpackage Table
 * @todo Handle relationships more naturally.
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class TD_Table_MySQL implements TD_Table_Interface {

    protected $TableFields = array();

    protected $TableName;
    protected $OrderBy = array('Field' => 'ID', 'Order' => 'ASC');

    protected $errors = array('VALIDATION' => array(), 'MYSQL' => array());
    protected $error_delimiter_start = '<p class="error">';
    protected $error_delimiter_end = '</p>';

    protected static $QueryCount = 0;
    protected static $LastQuery = '';
    protected static $TimeSpentQuerying = 0;

    protected $error_messages = array(
            'required' => "The <span class=\"field\">%s</span> cannot be empty.",
            'min_chars' => "The <span class=\"field\">%s</span> you have provided must have more than %s characters.",
            'max_chars' => "The <span class=\"field\">%s</span> you have provided must have less than %s characters.",
            'unique' => "The <span class=\"field\">%s</span> you have provided already exists.",
            'existsIn' => "The <span class=\"field\">%s</span> you have provided does not exist.",
            'inArray' => "You have entered an invalid <span class=\"field\">%s</span>.",
            'matches' => "The <span class=\"field\">%s</span> you have entered does not match the <span class=\"field\">%s</span> field.",
            'regex' => "You have entered an invalid <span class=\"field\">%s</span>.",
    );

    protected $DatabaseConnection;

    public function setField($FieldName, $FieldArray) {

        if (isset($this->TableFields[$FieldName])) {
            $Original = $this->TableFields[$FieldName];
        } else {
            $Original = array(
                    'Name' => $FieldName,
                    'Default' => '',
                    'Disabled' => false,
                    'ValidationRules' => array(),
            );
        }

        $this->TableFields[$FieldName] = array_merge($Original, $FieldArray);
    }

    public function unsetField($FieldName) {
        unset($this->TableFields[$FieldName]);
    }

    public function getField($FieldName) {
        if (isset($this->TableFields[$FieldName])) {
            return $this->TableFields[$FieldName];
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
            }

            if (isset($TableArray['OrderBy'])) {
                $this->OrderBy = $TableArray['OrderBy'];
            }

            foreach ($TableArray['Fields'] as $FieldName => $Field) {

                $Original = array(
                        'Name' => $FieldName,
                        'Default' => '',
                        'Disabled' => false,
                        'ValidationRules' => array(),
                );

                $this->TableFields[$FieldName] = array_merge($Original, $Field);
            }
        } else {
            if (file_exists($TableArray)) {
                include $TableArray;
                $this->setTableData($table);
            } else {
                throw new TD_Table_Exception("Could not include $TableArray for use in TD_Table_MySQL->setTableData().");
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
        if (!stristr($name, 'get') OR !stristr($name, 'by')) {
            throw new TD_Table_Exception("Invalid method call: \$TD_Table_MySQL->$name() is an invalid method name.");
            return false;
        }
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

        $args = $arguments[$i];

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

    public function __destruct() {
        if ($this->DataNotValid() or $this->DatabaseError()) {
            print "<div style=\"color: white; font-size: 16px; font-family: sans-serif; margin: 20px; padding: 10px; -webkit-border-radius: 5px; border: 1px solid red; background: red;\">There are still errors.<br /><pre>";
            print_r($this->getAllErrors());
            print "</pre></div>";
        }
    }

    public function getDatabaseConnection() {
        return $this->DatabaseConnection;
    }

    public function setDatabaseConnection($connection) {
        $this->DatabaseConnection = $connection;
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
        return (count($this->errors['VALIDATION']) > 0);
    }

    public function DatabaseError() {
        return (count($this->errors['MYSQL']) > 0);
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

    public function getDatabaseErrors() {
        $return = $this->errors['MYSQL'];
        $this->errors['MYSQL'] = array();
        return $return;
    }

    public function getAllErrors() {
        $buffer = $this->errors;
        $this->errors['VALIDATION'] = array();
        $this->errors['MYSQL'] = array();
        return $buffer;
    }

    protected function isValid($field, &$value, &$data) {
        $valid = true;
        if (isset ($this->TableFields[$field]) and !$this->TableFields[$field]['Disabled']) {
            foreach($this->TableFields[$field]['ValidationRules'] as $validation_rule => $arg) {
                switch($validation_rule) {
                    case 'required':
                        if (empty($value) and $value !== '0') {
                            $this->errors['VALIDATION'][$field] = sprintf($this->error_messages['required'], $this->humanizeField($field));
                            $valid = false;
                        }
                        break;
                    case 'min_chars':
                        if (strlen($value) < $arg) {
                            $this->errors['VALIDATION'][$field] = sprintf($this->error_messages['min_chars'], $this->humanizeField($field), $arg);
                            $valid = false;
                        }
                        break;
                    case 'max_chars':
                        if (strlen($value) > $arg) {
                            $this->errors['VALIDATION'][$field] = sprintf($this->error_messages['max_chars'], $this->humanizeField($field), $arg);
                            $valid = false;
                        }
                        break;
                    case 'ExistsIn':
                        $table = new TD_Table_MySQL();
                        $table->setTableData(array(
                                'Name' => $arg['Table'],
                                'Fields' => array(
                                        $arg['Field'] => array()
                                )
                        ));
                        $table->setDatabaseConnection($this->getDatabaseConnection());

                        $where = array();
                        if (isset($existsIn['Where'])) {
                            $where = $existsIn['Where'];
                        }
                        if (isset($existsIn['WhereCallback'])) {
                            $array = array('Where' => &$where);
                            TD_Events::QuickEvent($existsIn['WhereCallback'], $array);
                        }

                        $count = $table->count(array_merge($where, array($arg['Field'] => $value)));

                        if ($count == 0) {
                            $this->errors['VALIDATION'][$field] = sprintf($this->error_messages['existsIn'], $this->humanizeField($field));
                            $valid = false;
                        }
                        break;
                    case 'NotExistsIn':
                        $table = new TD_Table_MySQL();
                        $table->setTableData(array(
                                'Name' => $arg['Table'],
                                'Fields' => array(
                                        $arg['Field'] => array()
                                )
                        ));
                        $table->setDatabaseConnection($this->getDatabaseConnection());

                        $where = array();
                        if (isset($existsIn['Where'])) {
                            $where = $existsIn['Where'];
                        }
                        if (isset($existsIn['WhereCallback'])) {
                            $array = array('Where' => &$where);
                            TD_Events::QuickEvent($existsIn['WhereCallback'], $array);
                        }

                        $count = $table->count(array_merge($where, array($arg['Field'] => $value)));

                        if ($count != 0) {
                            $this->errors['VALIDATION'][$field] = sprintf($this->error_messages['unique'], $this->humanizeField($field));
                            $valid = false;
                        }
                        break;
                    case 'unique':
                        $results = $this->getWhere(array($field => $value));
                        if (count($results) > 0) {
                            $this->errors['VALIDATION'][$field] = sprintf($this->error_messages['unique'], $this->humanizeField($field));
                            $valid = false;
                        }
                        break;
                    case 'hash':
                        $value = hash($arg, $value);
                        break;
                    case 'matches':
                        $this->isValid($arg, $data[$arg], $data);
                        if ($value != $data[$arg]) {
                            $this->errors['VALIDATION'][$field] = sprintf($this->error_messages['matches'], $this->humanizeField($field), $this->humanizeField($arg));
                            $valid = false;
                        }
                        break;
                    case 'inArray':
                        $keys = array_keys($arg, $value);
                        if (!in_array($value, $arg) and empty($keys)) {
                            $this->errors['VALIDATION'][$field] = sprintf($this->error_messages['inArray'], $this->humanizeField($field));
                            $valid = false;
                        }
                        break;
                    case 'regex':
                        if (!preg_match($arg, $value)) {
                            $this->errors['VALIDATION'][$field] = sprintf($this->error_messages['regex'], $this->humanizeField($field));
                            $valid = false;
                        }
                        break;
                    default:
                    # Callbacks can accept array('Value', 'Row', 'Field', 'Arg', 'Error', 'TD_Table').
                    # Value is the value to be validated.
                    # Row is the row of data being processed, either from $_POST or otherwise.
                    # Field is the name of the field being validated.
                    # Arg is the argument of the validation rule.
                    # Error is the error message returned by the callback.
                    # TD_Table is $this.
                        $error = '';
                        $array = array(
                                'Value' => &$value,
                                'Row' => &$data,
                                'Arg' => &$arg,
                                'Error' => &$error,
                                'TD_Table' => &$this
                        );
                        TD_Events::QuickEvent($validation_rule, $array);
                        if (!empty($error)) {
                            $this->errors['VALIDATION'][$field] = $error;
                            $valid = false;
                        }
                        break;
                }
            }
        } else {
            $valid = false;
        }
        return $valid;
    }

    protected function humanizeField($field) {
        if (isset($this->table_fields[$field]['SCAFFOLDING']['HumanName'])) {
            return $this->table_fields[$field]['SCAFFOLDING']['HumanName'];
        } else {
            return strtolower(str_ireplace('_',' ',$field));
        }
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

        if (isset ($this->TableFields['CREATED']) and !$this->TableFields['CREATED']['Disabled']) {
            $data['CREATED'] = 'NOW()';
        }

        foreach ($this->TableFields as $Name => $Field) {
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
            } else {
                if (isset($Field['ValidationRules']['required'])) {
                    $this->errors['VALIDATION'][$Name] = sprintf($this->error_messages['required'], $this->humanizeField($Name));
                }
            }
        }

        $sql = substr($sql, 0, strlen($sql) - 2).' ) VALUES (';
        $sql2 = substr($sql2, 0, strlen($sql2) - 3).' );';

        if (!$this->DataNotValid() and !$this->DatabaseError()) {
            if ($this->query($sql.$sql2)) {
                return mysql_insert_id();
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function edit($ID, $data) {
        $currentData = $this->getWhere(array('ID' => $ID));
        $currentData = $currentData[0];
        if (isset($this->TableFields['UPDATED']) and !$this->TableFields['UPDATED']['Disabled']) {
            $data['UPDATED'] = 'NOW()';
        }
        $sql = "UPDATE `$this->TableName` SET ";

        $set = 0;


        foreach ($this->TableFields as $Name => $Field) {
            if (isset ($data[$Name])) {
                if ($currentData[$Name] != $data[$Name] and $this->isValid($Name, $data[$Name], $data)) {
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
                if (isset($Field['required'])) {
                    $this->errors['VALIDATION'][$Name] = sprintf($this->error_messages['required'], $this->humanizeField($Name));
                }
            }
        }

        if ($set > 0) {
            $sql = substr($sql, 0, strlen($sql) - 3);
            $sql .= $this->buildWhere(array('ID' => $ID));

            if (!$this->DataNotValid() and !$this->DatabaseError()) {
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

    public function delete($ID) {
        if (isset($this->TableFields['IS_DELETED']) and !$this->TableFields['IS_DELETED']['Disabled']) {
            return $this->edit($ID, array('IS_DELETED' => 1));
        } else {
            $sql = "DELETE FROM `$this->TableName` ";
            $sql .= $this->buildWhere(array('ID' => $ID));
            return $this->query($sql);
        }
    }

    public function restore($ID) {
        if (isset($this->TableFields['IS_DELETED']) and !$this->TableFields['IS_DELETED']['Disabled']) {
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
            TD_Events::trigger('GetRow', $row);
            $rows[] = $row;
        }
        return $rows;
    }

    public function query($sql) {
        if (!is_resource($this->DatabaseConnection)) {
            throw new TD_Table_Exception('To execute a query, you must set a valid database connection, using $TD_Table_MySQL->setDatabaseConnection().');
        }
        $start = microtime();
        $result = mysql_query($sql, $this->DatabaseConnection);
        $this->logQueryTime(microtime() - $start);
        $this->logQuery($sql);

        if (!$result) {
            $this->errors['MYSQL'][] = "An error occured with MySQL:
".mysql_error()."
Trying to execute the following query:
                    $sql";
        }
        return $result;
    }

    protected function buildLeftJoin(&$sql) {
        $leftJoin = '';
        foreach($this->TableFields as $Field) {
            $existsIn = isset($Field['ValidationRules']['ExistsIn']) ? $Field['ValidationRules']['ExistsIn'] : false;
            if ($existsIn) {
                $sql = str_ireplace("`$this->TableName`.".$Field['Name'], "`$this->TableName`.".$Field['Name'].",
                        `".$existsIn['Table']."`.".$existsIn['ValueField']." as
                        ".str_ireplace('_ID', '', $Field['Name']) , $sql);

                $leftJoin .= ' LEFT JOIN ';
                $where = array();
                if (isset($existsIn['Where'])) {
                    $where = $existsIn['Where'];
                }
                if (isset($existsIn['WhereCallback'])) {
                    $array = array('Where' => &$where);
                    TD_Events::QuickEvent($existsIn['WhereCallback'], $array);
                }
                $leftJoin .= ' `'.$existsIn['Table'].'` ON (`'.$this->TableName.'`.'.$Field['Name'].' = `'.$existsIn['Table'].'`.'.$existsIn['Field'];
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
            foreach ($this->TableFields as $Field) {
                $fsize++;
            }
            if ($fsize > 0) {
                foreach ($this->TableFields as $Field) {
                    if ($fsize > 1) {
                        $sql .= "`$this->TableName`.".$Field['Name']." , ";
                        $fsize--;
                    } else {
                        $sql .= "`$this->TableName`.".$Field['Name']." ";
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

}