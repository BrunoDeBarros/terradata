<?php

/**
 * Terra Data
 *
 * Provides a database-independent data handler.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 2
 * @package Terra
 * @subpackage Data
 * @copyright Copyright (c) 2008-2011 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Terra_Data {

    protected $MySQL_Connection;
    protected $Table;
    protected $Table_Data;
    protected $Page = 1;
    protected $RowsPerPage = 30; 
    /**
     * The name of the primary key field.
     * @var string
     */
    protected $PrimaryKey = '';
    protected static $QueryCount = 0;
    protected static $LastQuery = '';
    protected static $Queries = array();
    protected static $logAllQueries = false;
    protected static $TimeSpentQuerying = 0;

    # Public because people might need access to it, to change the fields after initialization.
    public $Fields = array();

    const RECORD_ARRAY = 50;
    const SINGLE_RECORD_ARRAY = 51;

    public static $ErrorMessages = array(
        'NoValidationRules' => "The <span class=\"field\">%s</span> has no validation rules defined.",
        'Required' => "The <span class=\"field\">%s</span> cannot be empty.",
        'MinChars' => "The <span class=\"field\">%s</span> you have provided must have more than %s characters.",
        'MaxChars' => "The <span class=\"field\">%s</span> you have provided must have less than %s characters.",
        'Unique' => "The <span class=\"field\">%s</span> you have provided already exists.",
        'ExistsIn' => "The <span class=\"field\">%s</span> you have provided does not exist.",
        'InArray' => "You have entered an invalid <span class=\"field\">%s</span>.",
        'Matches' => "The <span class=\"field\">%s</span> you have entered does not match the <span class=\"field\">%s</span> field.",
        'Regex' => "You have entered an invalid <span class=\"field\">%s</span>.",
        'Alphanumeric' => "The <span class=\"field\">%s</span> you have entered can only contain letters and digits.",
        'Numeric' => "The <span class=\"field\">%s</span> you have entered must be a number.",
        'Integer' => "The <span class=\"field\">%s</span> you have entered can only contain digits.",
        'Boolean' => "The <span class=\"field\">%s</span> you have entered is invalid.",
        'Text' => "The <span class=\"field\">%s</span> you have entered must be a piece of text.",
        'Email' => "The <span class=\"field\">%s</span> must be a valid e-mail address."
    );
    protected $ValidationErrors = array();

    function __construct($Table_Data, $MySQL_Connection = null) {

        if (!is_resource($MySQL_Connection)) {
            $MySQL_Connection = Terra_Data_Connection::getConnection();
            if (!is_resource($MySQL_Connection)) {
                throw new Terra_DataException("No working database connection was found.");
            }
        }

        if (is_array($Table_Data) or $Table_Data instanceof Terra_Data_Table) {
            $Table = $Table_Data['Name'];
            if (empty($Fields)) {
                $Fields = $Table_Data['Fields'];
            }
        } else {
            throw new Terra_DataException("The \$Table_Data of a new Terra Data must be either an array or an instance of Terra_Data_Table.");
        }

        $this->Table_Data = $Table_Data;
        $this->MySQL_Connection = $MySQL_Connection;
        $this->Table = $Table;

        $FoundPrimaryKey = false;

        foreach ($Fields as $Key => $Field) {
            if (is_string($Field)) {
                $Field = array('Name' => $Field);
            }

            if (!isset($Field['Name'])) {
                # Use key as field name.
                $Field['Name'] = $Key;
            }

            if (isset($Field['PrimaryKey']) and $Field['PrimaryKey'] == true) {
                if ($FoundPrimaryKey) {
                    throw new Terra_DataException("You cannot define more than one primary key in terra data.");
                } else {
                    $FoundPrimaryKey = true;
                    $this->PrimaryKey = $Field['Name'];
                }
            }

            $Original = array(
                'Name' => $Field['Name'],
                'Default' => '',
                'UpdateIfEmpty' => true,
                'HumanName' => $Field['Name'],
                'Relationship' => false,
                'PrimaryKey' => false,
                'Disabled' => false,
                'ValidationRules' => array()
            );
            $this->Fields[$Field['Name']] = array_merge($Original, $Field);
        }
    }

    public function __destruct() {
        if (self::$logAllQueries and self::getQueryCount() > 0) {
            $fh = @fopen(TERRA_APPDATA_PATH . 'data/logs/query-log-' . $_SERVER['REQUEST_TIME'] . '.txt', 'w');
            if ($fh) {
                fwrite($fh, "--------------------\r\n");
                fwrite($fh, "Executed " . self::getQueryCount() . " queries in " . substr(self::getTimeSpentQuerying(), 0, 7) . " seconds.\r\n");
                fwrite($fh, "--------------------\r\n");
                foreach (self::getAllQueriesLogged() as $query) {
                    fwrite($fh, $query . "\r\n");
                }
            }
        }
    }

    public function __call($name, $arguments) {
        if ((!stristr($name, 'get') OR !stristr($name, 'by')) and (!stristr($name, 'set') OR !stristr($name, 'by'))) {
            throw new Terra_DataException("Invalid method call: Terra_Data::$name() is an invalid method name.");
            return false;
        }

        if (stristr($name, 'set')) {
            $getBetween = self::getBetween($name, 'set', 'By');
            $fieldsToSet = array();

            $where = explode('By', $name);
            $where = explode('And', $where[1]);
            $i = 0;
            $whereClause = self::WhereFactory($this->Table);
            foreach ($where as $oneWhere) {
                $whereClause->_and(self::demethodizeField($oneWhere), $arguments[$i]);
                $i++;
            }

            if (!empty($getBetween)) {
                $fieldsToSetBuffer = explode('And', $getBetween);
                foreach ($fieldsToSetBuffer as $field) {
                    $field = $field;
                    if (!empty($field)) {
                        $fieldsToSet[self::demethodizeField($field)] = $arguments[$i];
                    }
                    $i++;
                }
            }

            return $this->edit($whereClause, $fieldsToSet);
        } elseif (stristr($name, 'get')) {
            $getBetween = self::getBetween($name, 'get', 'By');
            $fieldsToGet = array();

            if (!empty($getBetween)) {
                $fieldsToGetBuffer = explode('And', $getBetween);
                foreach ($fieldsToGetBuffer as $field) {
                    $field = $field;
                    if (!empty($field)) {
                        $fieldsToGet[] = self::demethodizeField($field);
                    }
                }
            }

            $where = explode('By', $name);
            $by = $where[1];
            $where = explode('And', $where[1]);
            $i = 0;
            $whereClause = self::WhereFactory($this->Table);
            foreach ($where as $oneWhere) {
                $whereClause->_and(self::demethodizeField($oneWhere), $arguments[$i]);
                $i++;
            }

            $args = isset($arguments[$i]) ? $arguments[$i] : array();

            if (self::demethodizeField($by) == $this->PrimaryKey and !isset($args['Format'])) {
                $args['Format'] = Terra_Data::SINGLE_RECORD_ARRAY;
            }

            if (self::demethodizeField($by) == $this->PrimaryKey and !isset($args['Rows'])) {
                $args['Rows'] = 1;
            }

            $buffer = $this->getWhere($whereClause, $args);

            if (count($fieldsToGet) == 1) {
                if (isset($args['Format']) and $args['Format'] == Terra_Data::SINGLE_RECORD_ARRAY) {
                    return $buffer[$fieldsToGet[0]];
                } else {
                    return $buffer;
                }
            } else {
                return $buffer;
            }
        } else {
            throw new Terra_Table_Exception("Invalid method call: Terra_Data::$name() is an invalid method name.");
            return false;
        }
    }

    public function getMySQLConnection() {
        return $this->MySQL_Connection;
    }

    public function setMySQLConnection($MySQL_Connection) {
        $this->MySQL_Connection = $MySQL_Connection;
    }

    public function buildUrl($action, $tags = array()) {
        $url = $this->Table_Data['Urls'][$action];
        foreach ($tags as $tag => $replacement) {
            $url = str_ireplace("{{$tag}}", $replacement, $url);
        }
        return $url;
    }
    
    public function setPage($page = 1) {
        $this->Page = $page;
    }
    
    public function setRowsPerPage($rows_per_page = 30) {
        $this->RowsPerPage = $rows_per_page;
    }

    function CreateController() {
        $fields = array();
        $record = $this->Table_Data['Singular'];
        $records = $this->Table_Data['Plural'];
        $form_url = $this->buildUrl('Create');
        $row = array();

        foreach ($this->Fields as $field) {
            if ($field['Create']) {
                $fields[$field['Identifier']] = $field;
                $fields[$field['Identifier']]['Error'] = '';
                $row[$field['Identifier']] = $field['Default'];
            }
        }

        $errors = array();
        if (isset($_POST['submit'])) {
            try {
                $this->create($_POST['fields']);
                header('Location: '.$this->buildUrl('Manage', array('PAGE' => $this->Page, 'ROWS_PER_PAGE' => $this->RowsPerPage)));
            } catch (Terra_Data_ValidationException $e) {
                foreach ($this->getValidationErrors() as $Identifier => $Error) {
                    $fields[$Identifier]['Error'] = $Error;
                }
            }
        }

        include TERRA_APPDATA_PATH . 'data/html_templates/' . $this->Table_Data['HtmlTemplate'] . '/form.php';
    }

    function ManageController($page = 0, $rows_per_page = 0) {
        
        if (!$page) {
            $page = $this->Page;
        }
        
        if (!$rows_per_page) {
            $rows_per_page = $this->RowsPerPage;
        }
        
        $fields = array();
        $fieldsToGet = array();
        $record = $this->Table_Data['Singular'];
        $records = $this->Table_Data['Plural'];
        $total_pages = $this->getPageCount($rows_per_page);

        $primary_key = false;

        foreach ($this->Fields as $field) {
            if ($field['Manage']) {
                $fields[$field['Identifier']] = $field;
                $fieldsToGet[] = $field['Identifier'];
                if ($field['PrimaryKey']) {
                    $primary_key = $field['Identifier'];
                }
            }
        }

        if (!$primary_key) {
            # Get the primary key for URL building purposes, even if it isn't meant to be used in the ManageController.
            $fieldsToGet[] = $this->PrimaryKey;
        }

        $buffer = $this->get(array(
                    'Page' => $page,
                    'Rows' => $rows_per_page,
                    'Fields' => $fieldsToGet
                ));

        $rows = array();
        foreach ($buffer as $row) {

            $id = $row[$this->PrimaryKey];
            if (!$primary_key) {
                # Unset the primary key, so that the ManageController template doesn't see it.
                unset($row[$this->PrimaryKey]);
            }

            $rows[] = array(
                'Fields' => $row,
                'Edit' => $this->buildUrl('Edit', array('ID' => $id)),
                'Delete' => $this->buildUrl('Delete', array('ID' => $id))
            );
        }

        $create = $this->buildUrl('Create');

        include TERRA_APPDATA_PATH . 'data/html_templates/' . $this->Table_Data['HtmlTemplate'] . '/manage.php';
    }

    /**
     * Create a record with the provided data.
     * Returns the ID of the created record on success, false on failure.
     *
     * @uses Terra_Data_QueryException|Terra_Data_ValidationException
     * @param array $data
     * @return int|boolean Insert ID if successful, false otherwise.
     */
    function create($data) {
        $sql = 'INSERT INTO `' . $this->Table . '` (';
        $sql2 = '';

        if (isset($this->Fields['CREATED']) and !isset($data['CREATED'])) {
            $data['CREATED'] = 'NOW()';
        }

        $CreateRelationships = array();

        foreach ($this->Fields as $Name => $Field) {
            if ($Field['Disabled']) {
                continue;
            }
            if (isset($data[$Name])) {
                if ($Field['Relationship']) {
                    if ($Field['CanHave'] == 'One') {

                        $row = new Terra_Data($this->MySQL_Connection, $Field['Table'], array($Field['Field']));
                        if ($row->count(array($Field['Field'] => $data[$Name])) == 0) {
                            $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['ExistsIn'], $this->humanizeField($Name));
                        } else {
                            # Exists, allow creation of relationship.
                            # Value is a string, therefore only one value is allowed.
                            $CreateRelationships[$Name] = $data[$Name];
                        }
                    } elseif ($Field['CanHave'] == 'Many') {
                        foreach ($data[$Name] as $Value) {
                            $row = new Terra_Data($this->MySQL_Connection, $Field['Table'], array($Field['Field']));
                            if ($row->count(array($Field['Field'] => $Value)) == 0) {
                                $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['ExistsIn'], $this->humanizeField($Name));
                            } else {
                                # Exists, allow creation of relationship.
                                if (!isset($CreateRelationships[$Name])) {
                                    $CreateRelationships[$Name] = array();
                                }
                                # Value is an array, therefore an unlimited number of values is allowed.
                                $CreateRelationships[$Name][] = $Value;
                            }
                        }
                    } else {
                        throw new Terra_DataException("The 'CanHave' parameter of the field '$Name' is not valid. It can only be 'One' or 'Many'");
                    }
                } elseif ($this->isValid($Name, $data[$Name], $data)) {
                    # Name is escaped as well because lazy programmers can then use create($_POST) to create a record straight with the post data,
                    # while making sure that they're not opening some big-ass security hole.
                    $Name = self::escape($Name);
                    $data[$Name] = self::escape($data[$Name]);

                    $sql .= '`' . $Name . '`, ';

                    # See if it's a MySQL function. Uses a very strict RegEx that search for capitals and () at the end. Anything else won't work.
                    # Doesn't allow function arguments or anything, but it's good enough for now, I haven't had the need for more yet.
                    if (!preg_match('/^[a-zA-Z_0-9]+\(\)$/', $data[$Name])) {
                        $sql2 .= "'{$data[$Name]}' , ";
                    } else {
                        $sql2 .= "{$data[$Name]} , ";
                    }
                }
            } elseif (isset($Field['Default']) and !empty($Field['Default']) and !$Field['Relationship']) {
                $sql .= '`' . $Name . '`, ';

                if (!preg_match('/^[a-zA-Z_0-9]+\(\)$/', $Field['Default'])) {
                    $sql2 .= "'{$Field['Default']}' , ";
                } else {
                    $sql2 .= "{$Field['Default']} , ";
                }
            } else {
                if (isset($Field['ValidationRules']['Required']) and $Field['ValidationRules']['Required']) {
                    $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['Required'], $this->humanizeField($Name));
                }
            }
        }

        $sql = substr($sql, 0, strlen($sql) - 2) . ' ) VALUES (';
        $sql2 = substr($sql2, 0, strlen($sql2) - 3) . ' );';

        if (empty($this->ValidationErrors)) {
            if ($this->query($sql . $sql2)) {
                $insertID = mysql_insert_id();

                # Create necessary relationships

                $Created = array();
                $Rollback = false;
                $MySQL_Error = '';

                foreach ($CreateRelationships as $Name => $Value) {
                    $relationship = new Terra_Data($this->MySQL_Connection, $this->Fields[$Name]['Rel_Table'], array($this->Fields[$Name]['ID'], $this->Fields[$Name]['REL_ID']));
                    if (!is_array($Value)) {
                        # One relationship.
                        $relationship = $relationship->create(array($this->Fields[$Name]['ID'] => $insertID, $this->Fields[$Name]['REL_ID'] => $Value));
                        if (!$relationship) {
                            # Woah! Something went wrong. Back up, man, you need to get rid of the stuff you already created.
                            $Rollback = true;
                            $MySQL_Error = mysql_error($this->MySQL_Connection);
                            break;
                        } else {
                            # Add created relationship to the list of created relationship, to allow you to roll back in case of a problem.
                            # By the way, yes, I could use transactions in InnoDB. But since most tables I've worked with in my life are MyISAM,
                            # I don't see a need to support InnoDB in Terra Data yet.
                            $Created[] = array('ID' => $Value, 'Name' => $Name);
                        }
                    } else {
                        foreach ($Value as $ID) {
                            $result = $relationship->create(array($this->Fields[$Name]['ID'] => $insertID, $this->Fields[$Name]['REL_ID'] => $ID));
                            if (!$result) {
                                # Woah! Something went wrong. Back up, man, you need to get rid of the stuff you already created.
                                $Rollback = true;
                                $MySQL_Error = mysql_error($this->MySQL_Connection);
                                break 2;
                            } else {
                                # Add created relationship to the list of created relationship, to allow you to roll back in case of a problem.
                                # By the way, yes, I could use transactions in InnoDB. But since most tables I've worked with in my life are MyISAM,
                                # I don't see a need to support InnoDB in Terra Data yet.
                                $Created[] = array('ID' => $Value, 'Name' => $Name);
                            }
                        }
                    }
                }

                if ($Rollback) {
                    # Oh, noes. Something went wrong. Let's delete sutff, then.
                    foreach ($Created as $RelationshipCreated) {

                        $Name = $RelationshipCreated['Name'];
                        $ID = $RelationshipCreated['ID'];

                        $relationship = new Terra_Data($this->MySQL_Connection, $this->Fields[$Name]['Rel_Table'], array($this->Fields[$Name]['ID'], $this->Fields[$Name]['REL_ID']));
                        $relationship->delete(array($this->Fields[$Name]['ID'] => $insertID, $this->Fields[$Name]['REL_ID'] => $ID));
                    }

                    # Delete the created record.
                    $this->delete($insertID);
                    throw new Terra_Data_QueryException("An error occured with MySQL while trying to create the relationships for a new record: \n$MySQL_Error");
                    return false;
                } else {
                    # The insert ID will be zero if the table has no primary key defined. For that, I am specifically forcing it to say true.
                    if ($insertID === false) {
                        return false;
                    } elseif ($insertID === 0) {
                        return true;
                    } else {
                        return $insertID;
                    }
                }
            } else {
                return false;
            }
        } else {
            throw new Terra_Data_ValidationException("The data provided was not valid. Use getValidationErrors() to find out more.");
            return false;
        }
    }

    /**
     * Create a number of records from an array of possible values for each of the fields.
     *
     * For example, using array('USERNAME' => array('Bruno', 'Grace', 'Iain')) for $data
     * would cause all the created records to have one of the three available usernames,
     * randomly assigned. Useful for creating realistic test data.
     *
     *
     * @param array $data
     * @param int $recordsToCreate
     * @return int Number of records created
     */
    public function createRandom($data, $recordsToCreate = 10) {
        $created = 0;
        for ($i = $recordsToCreate; $i > 0; $i--) {
            $buffer = array();
            foreach ($data as $field => $randomValues) {
                $buffer[$field] = $randomValues[rand(0, count($randomValues) - 1)];
            }
            if ($this->create($buffer)) {
                $created++;
            }
        }
        return $created;
    }

    public function edit($IdOrWhereClause, $data) {

        if (is_integer($IdOrWhereClause)) {
            $relID = $IdOrWhereClause;
            $CreateRelationships = array();
            $IdOrWhereClause = self::WhereFactory($this->Table)->_and($this->PrimaryKey, $IdOrWhereClause);
        } else {
            $relID = false;
        }

        if (isset($this->Fields['UPDATED']) and !$this->Fields['UPDATED']['Disabled']) {
            $data['UPDATED'] = 'NOW()';
        }
        $sql = "UPDATE `$this->Table` SET ";

        $set = 0;


        foreach ($data as $Name => $Value) {
            if (isset($this->Fields[$Name])) {

                $Field = &$this->Fields[$Name];

                if (!$this->Fields[$Name]['UpdateIfEmpty'] and (empty($Value) and $Value != '0')) {
                    continue;
                }

                if ($Field['Relationship'] and $relID !== false) {
                    if ($Field['CanHave'] == 'One') {

                        $row = new Terra_Data($this->MySQL_Connection, $Field['Table'], array($Field['Field']));
                        if ($row->count(array($Field['Field'] => $data[$Name])) == 0) {
                            $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['ExistsIn'], $this->humanizeField($Name));
                        } else {
                            # Exists, allow creation of relationship.
                            # Value is a string, therefore only one value is allowed.
                            $CreateRelationships[$Name] = $data[$Name];
                        }
                    } elseif ($Field['CanHave'] == 'Many') {
                        foreach ($data[$Name] as $Value) {
                            $row = new Terra_Data($this->MySQL_Connection, $Field['Table'], array($Field['Field']));
                            if ($row->count(array($Field['Field'] => $Value)) == 0) {
                                $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['ExistsIn'], $this->humanizeField($Name));
                            } else {
                                # Exists, allow creation of relationship.
                                if (!isset($CreateRelationships[$Name])) {
                                    $CreateRelationships[$Name] = array();
                                }
                                # Value is an array, therefore an unlimited number of values is allowed.
                                $CreateRelationships[$Name][] = $Value;
                            }
                        }
                    } else {
                        throw new Terra_DataException("The 'CanHave' parameter of the field '$Name' is not valid. It can only be 'One' or 'Many'");
                    }
                } elseif ($this->isValid($Name, $Value, $data)) {
                    # Name is escaped as well because lazy programmers can then use create($_POST) to create a record straight with the post data,
                    # while making sure that they're not opening some big-ass security hole.
                    $Name = self::escape($Name);
                    $Value = self::escape($Value);

                    $sql .= '`' . $Name . '` = ';

                    # See if it's a MySQL function. Uses a very strict RegEx that search for capitals and () at the end. Anything else won't work.
                    # Doesn't allow function arguments or anything, but it's good enough for now, I haven't had the need for more yet.
                    if (!preg_match('/^[a-zA-Z_0-9]+\(\)$/', $Value)) {
                        $sql .= "'{$Value}' , ";
                    } else {
                        $sql .= "{$Value} , ";
                    }

                    $set++;
                }
            } else {
                if ($this->Fields[$Name]['ValidationRules']['Required']) {
                    $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['Required'], $this->humanizeField($Name));
                }
            }
        }

        if ($set > 0) {
            $sql = substr($sql, 0, strlen($sql) - 3);
            $sql .= $this->buildWhere($IdOrWhereClause);

            if (empty($this->ValidationErrors)) {

                if ($this->query($sql)) {
                    $modified = mysql_affected_rows();

                    if ($relID !== false) {

                        $Created = array();
                        $Rollback = false;
                        $MySQL_Error = '';

                        foreach ($CreateRelationships as $Name => $Value) {
                            $relationship = new Terra_Data($this->MySQL_Connection, $this->Fields[$Name]['Rel_Table'], array($this->Fields[$Name]['ID'], $this->Fields[$Name]['REL_ID']));
                            $relationship->delete(array($this->Fields[$Name]['ID'] => $relID)); # Delete existing relationships.

                            if (!is_array($Value)) {
                                # One relationship.
                                $relationship = $relationship->create(array($this->Fields[$Name]['ID'] => $relID, $this->Fields[$Name]['REL_ID'] => $Value));
                                if (!$relationship) {
                                    # Woah! Something went wrong. Back up, man, you need to get rid of the stuff you already created.
                                    $Rollback = true;
                                    $MySQL_Error = mysql_error($this->MySQL_Connection);
                                    break;
                                } else {
                                    # Add created relationship to the list of created relationship, to allow you to roll back in case of a problem.
                                    # By the way, yes, I could use transactions in InnoDB. But since most tables I've worked with in my life are MyISAM,
                                    # I don't see a need to support InnoDB in Terra Data yet.
                                    $Created[] = array('ID' => $Value, 'Name' => $Name);
                                }
                            } else {
                                foreach ($Value as $ID) {
                                    $result = $relationship->create(array($this->Fields[$Name]['ID'] => $relID, $this->Fields[$Name]['REL_ID'] => $ID));
                                    if (!$result) {
                                        # Woah! Something went wrong. Back up, man, you need to get rid of the stuff you already created.
                                        $Rollback = true;
                                        $MySQL_Error = mysql_error($this->MySQL_Connection);
                                        break 2;
                                    } else {
                                        # Add created relationship to the list of created relationship, to allow you to roll back in case of a problem.
                                        # By the way, yes, I could use transactions in InnoDB. But since most tables I've worked with in my life are MyISAM,
                                        # I don't see a need to support InnoDB in Terra Data yet.
                                        $Created[] = array('ID' => $Value, 'Name' => $Name);
                                    }
                                }
                            }
                        }

                        if ($Rollback) {
                            # Oh, noes. Something went wrong. Let's delete sutff, then.
                            foreach ($Created as $RelationshipCreated) {

                                $Name = $RelationshipCreated['Name'];
                                $ID = $RelationshipCreated['ID'];

                                $relationship = new Terra_Data($this->MySQL_Connection, $this->Fields[$Name]['Rel_Table'], array($this->Fields[$Name]['ID'], $this->Fields[$Name]['REL_ID']));
                                $relationship->delete(array($this->Fields[$Name]['ID'] => $relID, $this->Fields[$Name]['REL_ID'] => $ID));
                            }

                            # Delete the created record.
                            $this->delete($relID);
                            throw new Terra_Data_QueryException("An error occured with MySQL while trying to create the relationships for a new record: \n$MySQL_Error");
                            return false;
                        } else {
                            return $modified;
                        }
                    } else {
                        return $modified;
                    }
                } else {
                    return false;
                }
            } else {
                throw new Terra_Data_ValidationException("The data provided was not valid. Use getValidationErrors() to find out more.");
                return false;
            }
        } else {
            if (count($data) == 0) {
                # It didn't update anything, but it wasn't meant to anyway, so it's all good.
                return true;
            } else {
                return false;
            }
        }
    }

    public function delete($IdOrWhereClause) {

        if (is_integer($IdOrWhereClause)) {
            $relID = $IdOrWhereClause;
            $IdOrWhereClause = self::WhereFactory($this->Table)->_and($this->PrimaryKey, $IdOrWhereClause);
        } else {
            $relID = false;
        }

        if (isset($this->Fields['IS_DELETED']) and !$this->Fields['IS_DELETED']['Disabled']) {
            return $this->edit($IdOrWhereClause, array('IS_DELETED' => 1));
        } else {
            $sql = "DELETE FROM `$this->Table` ";
            $sql .= $this->buildWhere($IdOrWhereClause);
            if ($this->query($sql)) {
                $deleted = mysql_affected_rows();

                # Now, onto deleting the relationships, if need be.
                if ($relID !== false) {
                    foreach ($this->Fields as $Name => $Field) {
                        if ($Field['Relationship']) {
                            $relationship = new Terra_Data($this->MySQL_Connection, $this->Fields[$Name]['Rel_Table'], array($this->Fields[$Name]['ID'], $this->Fields[$Name]['REL_ID']));
                            if (isset($Field['ForbidOrphans']) and $Field['ForbidOrphans']) {

                                $deleteIDs = array();

                                foreach ($relationship->getWhere(array($this->Fields[$Name]['ID'] => $relID), array('OrderBy' => false, 'Rows' => 0)) as $row) {
                                    $deleteIDs[] = $row[$this->Fields[$Name]['REL_ID']];
                                }

                                if (count($deleteIDs) > 0) {
                                    $table = new Terra_Data($this->MySQL_Connection, $this->Fields[$Name]['Table'], array($Field['Field']));
                                    $table->delete(array($Field['Field'] => $deleteIDs));
                                }
                            }

                            $relationship->delete(array($this->Fields[$Name]['ID'] => $relID));
                        }
                    }
                }

                return $deleted;
            } else {
                return false;
            }
        }
    }

    public function restore($IdOrWhereClause) {
        if (is_integer($IdOrWhereClause)) {
            $IdOrWhereClause = self::WhereFactory($this->Table)->_and($this->PrimaryKey, $IdOrWhereClause);
        }

        if (isset($this->Fields['IS_DELETED']) and !$this->Fields['IS_DELETED']['Disabled']) {
            return $this->edit($IdOrWhereClause, array('IS_DELETED' => 0));
        } else {
            throw new Terra_DataException("Cannot restore records that do not have a IS_DELETED field.");
            return false;
        }
    }

    public function count($WhereClause = array()) {
        if (is_array($WhereClause)) {
            $buffer = $WhereClause;
            $WhereClause = new Terra_Data_Where();
            $WhereClause->importFromArray($buffer);
        }

        $result = $this->getWhere($WhereClause, array('Fields' => array('COUNT(*) as count'), 'Format' => Terra_Data::SINGLE_RECORD_ARRAY, 'OrderBy' => false, 'Rows' => 0));
        return (int) $result['count'];
    }

    public function get($args = array()) {
        return $this->getWhere(array(), $args);
    }

    public function getPageCount($rows_per_page = 30, $WhereClause = array()) {
        return ceil($this->count($WhereClause) / $rows_per_page);
    }

    public function getWhere($WhereClause, $args = array()) {

        if (is_array($WhereClause)) {
            $buffer = $WhereClause;
            $WhereClause = new Terra_Data_Where();
            $WhereClause->importFromArray($buffer);
        }

        if (!isset($args['Rows'])) {
            throw new Terra_DataException("Set a number of rows to get, otherwise you might end up with pagination problems. Use 0 for no limit.");
        }
        if (!isset($args['Page'])) {
            $args['Page'] = 1;
        }
        if (!isset($args['OrderBy'])) {
            if (!empty($this->PrimaryKey)) {
                $args['OrderBy'] = array('Field' => $this->PrimaryKey, 'Order' => 'ASC');
            }
        }
        if (!isset($args['Fields'])) {
            $args['Fields'] = array_keys($this->Fields);
        }
        if (!isset($args['Format'])) {
            $args['Format'] = Terra_Data::RECORD_ARRAY;
        }

        $sql = "SELECT ";

        $fields = '';

        $CanHaveMany = array();

        foreach ($args['Fields'] as $FieldName) {
            if (!isset($this->Fields[$FieldName])) {
                $fields .= "$FieldName , ";
            } else {
                $Field = &$this->Fields[$FieldName];
                if ($Field['Relationship']) {
                    if ($Field['CanHave'] == 'One') {

                        if (!isset($Field['Where'])) {
                            $where = null;
                        } else {
                            $where = $Field['Where'];
                        }

                        if (isset($Field['WhereCallback'])) {
                            $array = array('Where' => &$where);
                            self::Callback($Field['WhereCallback'], $array);
                        }

                        $subwhere = self::buildWhere(self::WhereFactory($Field['Rel_Table'])->
                                                _and($Field['ID'], $this->Table . '.' . $Field['Field'], false)->
                                                _and($where));

                        if (!isset($Field['Alias']) and substr($Field['Name'], strlen($Field['Name']) - 3, 3) == '_ID') {
                            $Field['Alias'] = substr($Field['Name'], 0, strlen($Field['Name']) - 3);
                        } elseif (!isset($Field['Alias'])) {
                            $Field['Alias'] = $Field['Name'];
                        }

                        $fields .= " IFNULL((SELECT {$Field['ValueField']} FROM {$Field['Table']} WHERE {$Field['Field']} = (SELECT {$Field['REL_ID']} FROM {$Field['Rel_Table']} $subwhere LIMIT 0, 1) LIMIT 0,1), '{$Field['Default']}') as {$Field['Alias']} , ";

                        if ($Field['Alias'] != $Field['Name']) {
                            # If the alias and the name are different, I want to get both!

                            $fields .= "IFNULL((SELECT {$Field['Field']} FROM {$Field['Table']} WHERE {$Field['Field']} = (SELECT {$Field['REL_ID']} FROM {$Field['Rel_Table']} $subwhere LIMIT 0, 1) LIMIT 0,1), 0) as {$Field['Name']} ,";
                        }
                    } else {
                        # "Can Have Many" Relationship. Only find relationships after initial query gets IDs of all records.
                        $CanHaveMany[] = $Field;
                    }
                } elseif (isset($Field['ValidationRules']['ExistsIn'])) {

                    $ExistsIn = &$Field['ValidationRules']['ExistsIn'];

                    if (!isset($ExistsIn['Where'])) {
                        $where = null;
                    } else {
                        $where = $ExistsIn['Where'];
                    }

                    if (isset($ExistsIn['WhereCallback'])) {
                        $array = array('Where' => &$where);
                        self::Callback($ExistsIn['WhereCallback'], $array);
                    }

                    $where = self::WhereFactory($this->Table)->_and($ExistsIn['Table'] . '.' . $ExistsIn['Field'], $this->Table . '.' . $Field['Name'], false)->_and($where);
                    $where = self::buildWhere($where);

                    $fields .= "IFNULL((SELECT {$ExistsIn['ValueField']} FROM {$ExistsIn['Table']} {$where} LIMIT 0, 1), '{$Field['Default']}') as {$ExistsIn['Alias']}, `$this->Table`.`{$Field['Name']}`, ";
                } else {
                    $fields .= "`$this->Table`." . $Field['Name'] . " , ";
                }
            }
        }

        if (empty($fields)) {
            $fields = "`$this->Table`.* ";
        } else {
            # Remove the ending comma.
            $fields = substr($fields, 0, strlen($fields) - 2);
        }

        $sql .= $fields;
        $sql .= "FROM `$this->Table` ";

        $sql .= $this->buildWhere($WhereClause);

        if (isset($args['OrderBy']) and $args['OrderBy']) {
            $sql .= "ORDER BY `$this->Table`.`" . $args['OrderBy']['Field'] . "` " . $args['OrderBy']['Order'] . " ";
        }

        if ($args['Rows'] != 0) {
            $offset = ($args['Page'] - 1) * $args['Rows'];
            $sql .= " LIMIT $offset, {$args['Rows']}";
        }

        $result = $this->query($sql, $args['Format']);

        if (count($CanHaveMany) != 0) {
            # There are related records that we need to get. Hold on.
            foreach ($CanHaveMany as $Field) {
                if ($args['Format'] == Terra_Data::SINGLE_RECORD_ARRAY) {
                    if (!isset($result[$this->PrimaryKey])) {
                        throw new Terra_DataException("You need to add the Primary Key of the records to the list of fields to get, in order for CanHaveMany relationships to work properly.");
                    }
                    $table = new Terra_Data($this->MySQL_Connection, $Field['Rel_Table'], array($Field['ID'] => array('Name' => $Field['ID']), $Field['REL_ID'] => array('Name' => $Field['REL_ID'])));

                    if (!isset($Field['Where'])) {
                        $where = null;
                    } else {
                        $where = $Field['Where'];
                    }

                    if (isset($Field['WhereCallback'])) {
                        $array = array('Where' => &$where);
                        self::Callback($Field['WhereCallback'], $array);
                    }

                    $where = self::WhereFactory($Field['Rel_Table'])->_and($Field['ID'], $result[$this->PrimaryKey])->_and($where);

                    $sub = $table->getWhere($where, array('Rows' => 0, 'OrderBy' => array('Field' => $Field['REL_ID'], 'Order' => 'ASC'), 'Fields' => array($Field['REL_ID'])));
                    $result[$Field['Name']] = array();
                    foreach ($sub as $row) {
                        $result[$Field['Name']][] = $row[$Field['REL_ID']];
                    }
                } elseif ($args['Format'] == Terra_Data::RECORD_ARRAY) {
                    foreach ($result as $key => $row) {
                        if (!isset($row[$this->PrimaryKey])) {
                            throw new Terra_DataException("You need to add the Primary Key of the records to the list of fields to get, in order for CanHaveMany relationships to work properly.");
                        }
                        $table = new Terra_Data($this->MySQL_Connection, $Field['Rel_Table'], array($Field['ID'] => array('Name' => $Field['ID']), $Field['REL_ID'] => array('Name' => $Field['REL_ID'])));
                        $sub = $table->getWhere(self::WhereFactory($Field['Rel_Table'])->_and($Field['ID'], $row[$this->PrimaryKey]), array('Rows' => 0, 'OrderBy' => array('Field' => $Field['REL_ID'], 'Order' => 'ASC'), 'Fields' => array($Field['REL_ID'])));
                        $result[$key][$Field['Name']] = array();
                        foreach ($sub as $row) {
                            $result[$key][$Field['Name']][] = $row[$Field['REL_ID']];
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function humanizeField($field) {
        if (!empty($this->Fields[$field]['HumanName'])) {
            return $this->Fields[$field]['HumanName'];
        } else {
            return strtolower(str_ireplace('_', ' ', $field));
        }
    }

    public function getValidationErrors() {
        $buffer = $this->ValidationErrors;
        $this->ValidationErrors = array();
        return $buffer;
    }

    /**
     * Checks if a $value is a valid input for the field called $Name,
     * according to validation rules defined when setting the field.
     *
     * $Row is all the input. Useful for validation rules like "Matches" and
     * custom validation rules.
     *
     * @param string $Name
     * @param mixed $value
     * @param array $Row
     * @return boolean
     */
    protected function isValid($Name, &$value, &$Row) {
        $valid = true;

        #if (empty($this->Fields[$Name]['ValidationRules'])) {
        #    $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['NoValidationRules'], $this->humanizeField($Name));
        #    return false;
        #}

        foreach ($this->Fields[$Name]['ValidationRules'] as $validation_rule => $arg) {
            switch ($validation_rule) {
                case 'Required':
                    if ($arg) {
                        if (empty($value) and $value !== 0 and $value !== '0') {
                            $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['Required'], $this->humanizeField($Name), $arg);
                            $valid = false;
                        }
                    }
                    break;
                case 'MinChars':
                    if (strlen($value) < $arg) {
                        $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['MinChars'], $this->humanizeField($Name), $arg);
                        $valid = false;
                    }
                    break;
                case 'MaxChars':
                    if (strlen($value) > $arg) {
                        $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['MaxChars'], $this->humanizeField($Name), $arg);
                        $valid = false;
                    }
                    break;
                case 'Text':
                    # I really don't know when it would not be considered text, but this is here for Scaffolding purposes.
                    break;
                case 'Numeric':
                    if (!is_numeric($value)) {
                        $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['Numeric'], $this->humanizeField($Name), $arg);
                        $valid = false;
                    }
                    break;
                case 'Boolean':
                    if ($value == "1" or $value == "0" or $value === false or $value === true or $value === 1 or $value === 0) {
                        $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['Boolean'], $this->humanizeField($Name), $arg);
                        $valid = false;
                    }
                    break;
                case 'Integer':
                    if (!preg_match("/^[0-9]+$/", $value)) {
                        $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['Integer'], $this->humanizeField($Name));
                        $valid = false;
                    }
                    break;
                case 'ExistsIn':
                    $table = new Terra_Data($this->MySQL_Connection, $arg['Table'], array(
                                $arg['Field'] => array('Name' => $arg['Field'])
                            ));

                    $where = array();
                    if (isset($arg['Where'])) {
                        $where = $arg['Where'];
                    }
                    if (isset($arg['WhereCallback'])) {
                        $array = array('Where' => &$where);
                        self::Callback($arg['WhereCallback'], $array);
                    }

                    $count = $table->count(array_merge($where, array($arg['Field'] => $value)));

                    if ($count == 0) {
                        $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['ExistsIn'], $this->humanizeField($Name));
                        $valid = false;
                    }
                    break;
                case 'NotExistsIn':
                    $table = new Terra_Data($this->MySQL_Connection, $arg['Table'], array(
                                $arg['Field'] => array('Name' => $arg['Field'])
                            ));

                    $where = array();
                    if (isset($arg['Where'])) {
                        $where = $arg['Where'];
                    }
                    if (isset($arg['WhereCallback'])) {
                        $array = array('Where' => &$where);
                        self::Callback($arg['WhereCallback'], $array);
                    }

                    $count = $table->count(array_merge($where, array($arg['Field'] => $value)));

                    if ($count != 0) {
                        $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['ExistsIn'], $this->humanizeField($Name));
                        $valid = false;
                    }
                    break;
                case 'Unique':
                    $results = $this->getWhere(array($Name => $value), array('Rows' => 1));
                    if (count($results) > 0) {
                        $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['Unique'], $this->humanizeField($Name));
                        $valid = false;
                    }
                    break;
                case 'Hash':
                    $value = hash($arg, $value);
                    break;
                case 'Matches':
                    if ($value != $Row[$arg]) {
                        $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['Matches'], $this->humanizeField($Name), $this->humanizeField($arg));
                        $valid = false;
                    }
                    break;
                case 'InArray':
                    $keys = array_keys($arg, $value);
                    if (!in_array($value, $arg) and empty($keys)) {
                        $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['InArray'], $this->humanizeField($Name));
                        $valid = false;
                    }
                    break;
                case 'Regex':
                    if (is_string($arg)) {
                        $arg = array($arg);
                    }
                    foreach ($arg as $regex) {
                        if (!preg_match($regex, $value)) {
                            $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['Regex'], $this->humanizeField($Name));
                            $valid = false;
                        }
                    }
                    break;
                case 'Alphanumeric':
                    if (!preg_match("/^[A-Za-z0-9]*$/", $value)) {
                        $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['Alphanumeric'], $this->humanizeField($Name));
                        $valid = false;
                    }
                    break;
                case 'Email':
                    if (!preg_match("/([\w-\.]+)@((?:[\w]+\.)+)([a-zA-Z]{2,4})/", $value)) {
                        $this->ValidationErrors[$Name] = sprintf(self::$ErrorMessages['Email'], $this->humanizeField($Name));
                        $valid = false;
                    }
                    break;
                default:
                    # Callbacks can accept array('Value', 'Row', 'Field', 'Arg', 'Error', 'Terra_Data').
                    # Value is the value to be validated.
                    # Row is the row of data being processed, either from $_POST or otherwise.
                    # Field is the name of the field being validated.
                    # Arg is the argument of the validation rule.
                    # Error is the error message returned by the callback.
                    # Terra_Data is $this.
                    $error = '';
                    $array = array(
                        'Value' => &$value,
                        'Row' => &$Row,
                        'Arg' => &$arg,
                        'Field' => $Name,
                        'Error' => &$error,
                        'Terra_Data' => &$this
                    );
                    self::Callback($validation_rule, $array);
                    if (!empty($error)) {
                        $this->ValidationErrors[$Name] = $error;
                        $valid = false;
                    }
                    break;
            }
        }
        return $valid;
    }

    function query($sql, $format = Terra_Data::RECORD_ARRAY) {
        $start = microtime();
        $result = mysql_query($sql, $this->MySQL_Connection);
        self::$TimeSpentQuerying = self::$TimeSpentQuerying + (microtime() - $start);
        self::$LastQuery = $sql;

        if (self::$logAllQueries) {
            self::$Queries[] = $sql;
        }

        self::$QueryCount = self::$QueryCount + 1;

        if (is_bool($result)) {
            if ($result) {
                return true;
            } else {
                throw new Terra_Data_QueryException("An error occured with MySQL: \n" . mysql_error() . " \nTrying to execute the following query: \n$sql");
                return false;
            }
        } else {
            switch ($format) {
                case Terra_Data::RECORD_ARRAY:
                    $rows = array();
                    while ($row = mysql_fetch_assoc($result)) {
                        $rows[] = $row;
                    }
                    return $rows;
                    break;
                case Terra_Data::SINGLE_RECORD_ARRAY:
                    return mysql_fetch_assoc($result);
                    break;
            }
        }
    }

    ########################
    ## Static Functions
    ########################
    ## Have no relation to the instance whatsoever.

    public static function StringBeginsWith($string, $search) {
        return (strncmp($string, $search, strlen($search)) == 0);
    }

    public static function buildWhere($Where) {

        if (is_array($Where)) {
            $buffer = $Where;
            $Where = new Terra_Data_Where();
            $Where->importFromArray($buffer);
        }

        $Where = (string) $Where;

        if ($Where == '()') {
            return '';
        }

        return " WHERE " . $Where . " ";
    }

    public static function WhereFactory($Table = null) {
        return new Terra_Data_Where($Table);
    }

    public static function Callback($callback, &$args) {
        if (is_string($callback)) {
            return $callback(&$args);
        } else {
            return $callback[0]->$callback[1](&$args);
        }
    }

    public static function getBetween($content, $start, $end) {
        $r = explode($start, $content);
        if (isset($r[1])) {
            $r = explode($end, $r[1]);
            return $r[0];
        } else {
            return '';
        }
    }

    public static function escape($String) {
        return mysql_escape_string($String);
    }

    public static function demethodizeField($field) {
        # eg. PermissionLevel = PERMISSION_LEVEL
        $pattern = "/(.)([A-Z])/";
        $replacement = "\\1_\\2";
        $field = lcfirst($field);
        return strtoupper(preg_replace($pattern, $replacement, $field));
    }

    public static function getQueryCount() {
        return self::$QueryCount;
    }

    public static function getLastQuery() {
        return self::$LastQuery;
    }

    public static function logAllQueries($set = true) {
        self::$logAllQueries = $set;
    }

    public static function getAllQueriesLogged() {
        return self::$Queries;
    }

    public static function getTimeSpentQuerying() {
        return self::$TimeSpentQuerying;
    }

}