<?php
/**
 * Terra Duo Table Field
 *
 * Provides an interface to manage Table Field data.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 0.2
 * @package Terra
 * @subpackage Table
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Terra_Table_Field {

    public $Name;
    public $HumanName;
    public $Default;
    public $Disabled = false;
    public $UpdateIfEmpty = true;
    
    public static $ValidationRules = array();
    public static $HumanNames = array();

    public static $ErrorMessages = array(
            'Required' => "The <span class=\"field\">%s</span> cannot be empty.",
            'MinChars' => "The <span class=\"field\">%s</span> you have provided must have more than %s characters.",
            'MaxChars' => "The <span class=\"field\">%s</span> you have provided must have less than %s characters.",
            'Unique' => "The <span class=\"field\">%s</span> you have provided already exists.",
            'ExistsIn' => "The <span class=\"field\">%s</span> you have provided does not exist.",
            'InArray' => "You have entered an invalid <span class=\"field\">%s</span>.",
            'Matches' => "The <span class=\"field\">%s</span> you have entered does not match the <span class=\"field\">%s</span> field.",
            'Regex' => "You have entered an invalid <span class=\"field\">%s</span>.",
            'Alphanumeric' => "The <span class=\"field\">%s</span> you have entered can only contain letters and digits.",
            'Email' => "The <span class=\"field\">%s</span> must be a valid e-mail address."
    );

    public static $Errors = array();
    protected static $ErrorDelimiterStart = '<p class="error">';
    protected static $ErrorDelimiterEnd = '</p>';

    function __construct($name, $default = '', $HumanName = '') {

        self::$ValidationRules[$name] = array();

        if (is_string($name)) {
            $this->Name = $name;
            $this->Default = $default;
            $this->HumanName = $HumanName;
            self::$HumanNames[$this->Name] = $HumanName;
        } elseif(is_array($name)) {
            if (isset($name['Name'])) {
                $this->Name = $name['Name'];
            }

            if (isset($name['Default'])) {
                $this->Default = $name['Default'];
            }

            if (isset($name['HumanName'])) {
                $this->HumanName = $name['HumanName'];
                self::$HumanNames[$this->Name] = $name['HumanName'];
            }

            if (isset($name['Disabled'])) {
                $this->Disabled = $name['Disabled'];
            }

            if (isset($name['ValidationRules'])) {
                self::$ValidationRules[$this->Name] = $name['ValidationRules'];
            }
        } else {
            throw new Terra_Table_Exception("It is impossible to create a Terra_Table_Field providing a \$name of the type ".gettype($name).".
                You can only provide a string, which is the name of the field, or a table field array.");
        }
    }

    function __destruct() {
        if (count(self::$Errors) > 0) {
            print "<div style=\"color: white; font-size: 16px; font-family: sans-serif; margin: 20px; padding: 10px; -webkit-border-radius: 5px; border: 1px solid red; background: red;\">There are still errors.<br /><pre>";
            print_r(self::getAllErrors());
            print "</pre></div>";
        }
    }

    /**
     * Get an array containing ALL errors (Validation and Database).
     * @return array
     */
    public static function getAllErrors() {
        $buffer = self::$Errors;
        self::$Errors = array();
        return $buffer;
    }

    public static function addValidationError($Field, $Message) {
        self::$Errors[$Field] = $Message;
    }

    function isValid(&$value, &$data = array(), &$Terra_Table = null, $DatabaseClass = "MySQL") {
        $valid = true;
        if (!$this->Disabled) {
            foreach(self::$ValidationRules[$this->Name] as $validation_rule => $arg) {
                switch($validation_rule) {
                    case 'Required':
                        if ($arg) {
                            if (empty($value) and $value !== 0 and $value !== '0') {
                                self::$Errors[$this->Name] = sprintf(self::$ErrorMessages['Required'], $this->humanizeField(), $arg);
                                $valid = false;
                            }
                        }
                        break;
                    case 'MinChars':
                        if (strlen($value) < $arg) {
                            self::$Errors[$this->Name] = sprintf(self::$ErrorMessages['MinChars'], $this->humanizeField(), $arg);
                            $valid = false;
                        }
                        break;
                    case 'MaxChars':
                        if (strlen($value) > $arg) {
                            self::$Errors[$this->Name] = sprintf(self::$ErrorMessages['MaxChars'], $this->humanizeField(), $arg);
                            $valid = false;
                        }
                        break;
                    case 'ExistsIn':
                        $table = Terra_Table_Manager::Factory($DatabaseClass, $arg['Table']);
                        $table_field = $table->getField($arg['Field']);
                        if (!($table_field instanceof Terra_Table_Field)) {
                            $table->setField(new Terra_Table_Field($arg['Field']));
                        } else {
                            $table_field->Disabled = false;
                        }
                        $where = array();
                        if (isset($existsIn['Where'])) {
                            $where = $existsIn['Where'];
                        }
                        if (isset($existsIn['WhereCallback'])) {
                            $array = array('Where' => &$where);
                            Terra_Events::QuickEvent($existsIn['WhereCallback'], $array);
                        }

                        $count = $table->count(array_merge($where, array($arg['Field'] => $value)));

                        if ($count == 0) {
                            self::$Errors[$this->Name] = sprintf(self::$ErrorMessages['ExistsIn'], $this->humanizeField());
                            $valid = false;
                        }
                        break;
                    case 'NotExistsIn':
                        $table = Terra_Table_Manager::Factory($DatabaseClass, $arg['Table']);
                        $table_field = $table->getField($arg['Field']);
                        if (!($table_field instanceof Terra_Table_Field)) {
                            $table->setField(new Terra_Table_Field($arg['Field']));
                        } else {
                            $table_field->Disabled = false;
                        }
                        $where = array();
                        if (isset($existsIn['Where'])) {
                            $where = $existsIn['Where'];
                        }
                        if (isset($existsIn['WhereCallback'])) {
                            $array = array('Where' => &$where);
                            Terra_Events::QuickEvent($existsIn['WhereCallback'], $array);
                        }

                        $count = $table->count(array_merge($where, array($arg['Field'] => $value)));

                        if ($count != 0) {
                            self::$Errors[$this->Name] = sprintf(self::$ErrorMessages['Unique'], $this->humanizeField());
                            $valid = false;
                        }
                        break;
                    case 'Unique':
                        if (!$Terra_Table instanceof Terra_Table_Interface) {
                            throw new Terra_Table_Exception("You must provide a valid \$Terra_Table in order to use the 'Unique' validation rule.");
                        }
                        $results = $Terra_Table->getWhere(array($this->Name => $value));
                        if (count($results) > 0) {
                            self::$Errors[$this->Name] = sprintf(self::$ErrorMessages['Unique'], $this->humanizeField());
                            $valid = false;
                        }
                        break;
                    case 'Hash':
                        $value = hash($arg, $value);
                        break;
                    case 'Matches':
                        if (count($data) == 0) {
                            throw new Terra_Table_Exception("You must provide a valid \$data array in order to use the 'Matches' validation rule.");
                        }
                        if ($value != $data[$arg]) {
                            self::$Errors[$this->Name] = sprintf(self::$ErrorMessages['Matches'], $this->humanizeField(), $this->humanizeField($arg));
                            $valid = false;
                        }
                        break;
                    case 'InArray':
                        $keys = array_keys($arg, $value);
                        if (!in_array($value, $arg) and empty($keys)) {
                            self::$Errors[$this->Name] = sprintf(self::$ErrorMessages['InArray'], $this->humanizeField());
                            $valid = false;
                        }
                        break;
                    case 'Regex':
                        foreach ($arg as $regex) {
                            if (!preg_match($regex, $value)) {
                                self::$Errors[$this->Name] = sprintf(self::$ErrorMessages['Regex'], $this->humanizeField());
                                $valid = false;
                            }
                        }
                        break;
                    case 'Alphanumeric':
                        if (!preg_match("/^[A-Za-z0-9]*$/", $value)) {
                            self::$Errors[$this->Name] = sprintf(self::$ErrorMessages['Alphanumeric'], $this->humanizeField());
                            $valid = false;
                        }
                        break;
                    case 'Email':
                        if (!preg_match("/([\w-\.]+)@((?:[\w]+\.)+)([a-zA-Z]{2,4})/", $value)) {
                            self::$Errors[$this->Name] = sprintf(self::$ErrorMessages['Email'], $this->humanizeField());
                            $valid = false;
                        }
                        break;
                    default:
                        if (!$Terra_Table instanceof Terra_Table_Interface) {
                            throw new Terra_Table_Exception("You must provide a valid \$Terra_Table in order to use custom validation rules.");
                        }
                        if (count($data) == 0) {
                            throw new Terra_Table_Exception("You must provide a valid \$data array in order to use custom validation rules.");
                        }
                        # Callbacks can accept array('Value', 'Row', 'Field', 'Arg', 'Error', 'Terra_Table').
                        # Value is the value to be validated.
                        # Row is the row of data being processed, either from $_POST or otherwise.
                        # Field is the name of the field being validated.
                        # Arg is the argument of the validation rule.
                        # Error is the error message returned by the callback.
                        # Terra_Table is $this.
                        $error = '';
                        $array = array(
                                'Value' => &$value,
                                'Row' => &$data,
                                'Arg' => &$arg,
                                'Field' => $this->Name,
                                'Error' => &$error,
                                'Terra_Table' => &$Terra_Table
                        );
                        Terra_Events::QuickEvent($validation_rule, $array);
                        if (!empty($error)) {
                            self::$Errors[$this->Name] = $error;
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

    public static function setErrorDelimiter($start = '<p class="error">', $end = '</p>') {
        self::$ErrorDelimiterStart = $start;
        self::$ErrorDelimiterEnd = $end;
    }

    protected function humanizeField($field = null) {
        if ($field == null) {
            $field = $this->Name;
        }

        if (!empty(self::$HumanNames[$field])) {
            return self::$HumanNames[$field];
        } else {
            return strtolower(str_ireplace('_',' ',$field));
        }
    }

    function Required() {
        self::$ValidationRules[$this->Name]['Required'] = true;
        return $this;
    }

    function resetValidationRules() {
        self::$ValidationRules[$this->Name] = array('Required' => false);
        return $this;
    }

    function MaxChars($MaxChars) {
        self::$ValidationRules[$this->Name]['MaxChars'] = $MaxChars;
        return $this;
    }

    function MinChars($MinChars) {
        self::$ValidationRules[$this->Name]['MinChars'] = $MinChars;
        return $this;
    }

    function ExistsIn($Table, $IdField, $ValueField, $WhereArray = array(), $WhereCallback = null) {
        self::$ValidationRules[$this->Name]['ExistsIn'] = array(
                'Table' => $Table,
                'Field' => $IdField,
                'ValueField' => $ValueField,
                'Where' => $WhereArray,
                'WhereCallback' => $WhereCallback
        );
        return $this;
    }

    function NotExistsIn($Table, $IdField, $ValueField, $WhereArray = array(), $WhereCallback = null) {
        self::$ValidationRules[$this->Name]['NotExistsIn'] = array(
                'Table' => $Table,
                'Field' => $IdField,
                'ValueField' => $ValueField,
                'Where' => $WhereArray,
                'WhereCallback' => $WhereCallback
        );
        return $this;
    }

    function Unique() {
        self::$ValidationRules[$this->Name]['Unique'] = true;
        return $this;
    }

    function Matches($Field) {
        self::$ValidationRules[$this->Name]['Matches'] = $Field;
        return $this;
    }

    function InArray($Array) {
        self::$ValidationRules[$this->Name]['InArray'] = $Array;
        return $this;
    }

    function Regex($Regex) {
        if (!isset(self::$ValidationRules[$this->Name]['Regex'])) {
            self::$ValidationRules[$this->Name]['Regex'] = array();
        }

        self::$ValidationRules[$this->Name]['Regex'][] = $Regex;
        return $this;
    }

    function Hash($Algorithm) {
        self::$ValidationRules[$this->Name]['Hash'] = $Algorithm;
        return $this;
    }

    function Alphanumeric() {
        self::$ValidationRules[$this->Name]['Alphanumeric'] = true;
        return $this;
    }

    function Email() {
        self::$ValidationRules[$this->Name]['Email'] = true;
        return $this;
    }

    function Callback($Name, $Argument) {
        if (!isset(self::$ValidationRules[$this->Name][$Name])) {
            self::$ValidationRules[$this->Name][$Name] = array();
        }

        self::$ValidationRules[$this->Name][$Name] = $Argument;
        return $this;
    }

    function getValidationRule($Rule) {
        return isset(self::$ValidationRules[$this->Name][$Rule]) ? self::$ValidationRules[$this->Name][$Rule] : false;
    }

    function exportArray() {
        return array(
                'Name' => $this->Name,
                'Default' => $this->Default,
                'HumanName' => $this->HumanName,
                'Disabled' => $this->Disabled,
                'ValidationRules' => self::$ValidationRules[$this->Name]
        );
    }

}