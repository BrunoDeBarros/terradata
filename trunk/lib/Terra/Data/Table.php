<?php

/**
 * Terra Data Table
 * 
 * Facilitates the creation of configuration arrays for Terra Data.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 2
 * @package Terra
 * @subpackage Data
 * @copyright Copyright (c) 2008-2011 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Terra_Data_Table implements ArrayAccess {
    const MANAGE = 'Manage';
    const VIEW = 'View';
    const EDIT = 'Edit';
    const RESTORE = 'Restore';
    const DELETE = 'Delete';
    const CREATE = 'Create';

    protected $Container = array(
        'Name' => '',
        'Singular' => '',
        'HtmlTemplate' => 'default',
        'Urls' => array(
            'Manage' => '',
            'View' => '',
            'Edit' => '',
            'Restore' => '',
            'Delete' => '',
            'Create' => ''
        ),
        'Plural' => '',
        'Fields' => array()
    );

    function __construct($Table, $AutoDiscover = false) {
        $this->Container['Name'] = $Table;
        if ($AutoDiscover) {
            self::discoverTable($Table, null, &$this);
        }
    }

    function setSingular($Singular) {
        $this->Container['Singular'] = $Singular;
    }

    function setPlural($Plural) {
        $this->Container['Plural'] = $Plural;
    }

    function setHtmlTemplate($HtmlTemplate) {
        $this->Container['HtmlTemplate'] = $HtmlTemplate;
    }

    function setManageUrl($Url) {
        return $this->setUrl($Url, 'Manage', array('PAGE', 'ROWS_PER_PAGE'));
    }

    function setViewUrl($Url) {
        return $this->setUrl($Url, 'View', array('ID'));
    }

    function setDeleteUrl($Url) {
        return $this->setUrl($Url, 'Delete', array('ID'));
    }

    function setRestoreUrl($Url) {
        return $this->setUrl($Url, 'Restore', array('ID'));
    }

    function setEditUrl($Url) {
        return $this->setUrl($Url, 'Edit', array('ID'));
    }

    function setCreateUrl($Url) {
        return $this->setUrl($Url, 'Create');
    }

    function setUrl($Url, $UrlType, $RequiredTags = array()) {
        foreach ($RequiredTags as $RequiredTag) {
            if (stristr($Url, "{$RequiredTag}") === false) {
                throw new Terra_DataException("Tried to set a $UrlType URL that was missing {$RequiredTag}.");
                return false;
            }
        }

        $this->Container['Urls'][$UrlType] = $Url;
        return true;
    }

    function addField($Identifier, $Name = null, $HumanName = null, $DisableInsertAndUpdate = false, $PrimaryKey = false) {
        if (empty($Name)) {
            $Name = $Identifier;
        }
        if (empty($HumanName)) {
            $HumanName = $Identifier;
        }

        $this->Container['Fields'][$Identifier] = array(
            'Identifier' => $Identifier,
            'Disabled' => $DisableInsertAndUpdate,
            'PrimaryKey' => $PrimaryKey,
            'Name' => $Name,
            'Manage' => false,
            'Restore' => false,
            'View' => false,
            'Delete' => false,
            'Create' => false,
            'Edit' => false,
            'HumanName' => $HumanName,
            'ValidationRules' => array()
        );
    }

    function allowField($FieldIdentifier, $Action) {
        $this->Container['Fields'][$FieldIdentifier][$Action] = true;
    }

    function addRelationship($FieldIdentifier, $CanHave, $ExternalTable, $ExternalField, $RelationshipTable, $RelationshipTable_RecordIdField, $RelationshipTable_ExternalRecordIdField, $Alias = null, $ValueField = null) {
        $this->Container['Fields'][$FieldIdentifier]['CanHave'] = $CanHave;
        $this->Container['Fields'][$FieldIdentifier]['Table'] = $ExternalTable;
        $this->Container['Fields'][$FieldIdentifier]['Field'] = $ExternalField;
        $this->Container['Fields'][$FieldIdentifier]['Rel_Table'] = $RelationshipTable;
        $this->Container['Fields'][$FieldIdentifier]['ID'] = $RelationshipTable_RecordIdField;
        $this->Container['Fields'][$FieldIdentifier]['REL_ID'] = $RelationshipTable_ExternalRecordIdField;
        if (!empty($Alias)) {
            $this->Container['Fields'][$FieldIdentifier]['Alias'] = $Alias;
        }
        if (!empty($ValueField)) {
            $this->Container['Fields'][$FieldIdentifier]['ValueField'] = $ValueField;
        }
    }

    function existsIn($FieldIdentifier, $ExternalTable, $ExternalField, $Alias = null, $ValueField = null) {
        $this->addValidationRule($FieldIdentifier, 'ExistsIn', array(
            'Table' => $ExternalTable,
            'Field' => $ExternalField,
            'Alias' => $Alias,
            'ValueField' => $ValueField
        ));
    }

    function exportCode($var = 'table') {
        $return = "\${$var} = new Terra_Data_Table('{$this['Name']}');\r\n";
        if (!empty($this['Singular'])) {
            $return .= "\${$var}->setSingular('{$this['Singular']}');\r\n";
        }
        if (!empty($this['Plural'])) {
            $return .= "\${$var}->setPlural('{$this['Plural']}');\r\n";
        }
        if (!empty($this['Urls']['Manage'])) {
            $return .= "\${$var}->setManageUrl('{$this['Urls']['Manage']}');\r\n";
        }
        if (!empty($this['Urls']['View'])) {
            $return .= "\${$var}->setViewUrl('{$this['Urls']['View']}');\r\n";
        }
        if (!empty($this['Urls']['Edit'])) {
            $return .= "\${$var}->setEditUrl('{$this['Urls']['Edit']}');\r\n";
        }
        if (!empty($this['Urls']['Create'])) {
            $return .= "\${$var}->setCreateUrl('{$this['Urls']['Create']}');\r\n";
        }
        if (!empty($this['Urls']['Delete'])) {
            $return .= "\${$var}->setDeleteUrl('{$this['Urls']['Delete']}');\r\n";
        }
        if (!empty($this['Urls']['Restore'])) {
            $return .= "\${$var}->setRestoreUrl('{$this['Urls']['Restore']}');\r\n";
        }
        if ($this['HtmlTemplate'] != 'default') {
            $return .= "\${$var}->setHtmlTemplate('{$this['HtmlTemplate']}');\r\n";
        }
        foreach ($this['Fields'] as $field) {
            $return .= "\${$var}->addField('{$field['Identifier']}', '{$field['Name']}', '{$field['HumanName']}', ".(($field['Disabled']) ? 'true' : 'false').", '{$field['PrimaryKey']}');\r\n";
            foreach ($field['ValidationRules'] as $rule => $arg) {
                if ($rule == 'ExistsIn') {
                    $return .= "\${$var}->existsIn('{$field['Identifier']}', '{$arg['Table']}', '{$arg['Field']}', '{$arg['Alias']}', '{$arg['ValueField']}');\r\n";
                } else {
                    if (is_string($arg) or is_int($arg)) {
                        $return .= "\${$var}->addValidationRule('{$field['Identifier']}', '$rule', '$arg');\r\n";
                    } elseif (is_array($arg)) {
                        $return .= "\${$var}->addValidationRule('{$field['Identifier']}', '$rule', " . self::printArrayAsPhpCode($arg, true) . ");\r\n";
                    }
                }
            }
            if (isset($field['CanHave'])) {
                if (isset($field['Alias'])) {
                    $Alias = "'{$field['Alias']}'";
                } else {
                    $Alias = null;
                }
                if (isset($field['ValueField'])) {
                    $ValueField = "'{$field['ValueField']}'";
                } else {
                    $ValueField = null;
                }
                $return .= "\${$var}->addRelationship('{$field['Identifier']}','{$field['CanHave']}','{$field['Table']}','{$field['Field']}','{$field['Rel_Table']}','{$field['ID']}','{$field['REL_ID']}', $Alias, $ValueField);\r\n";
            }
        }
        
        return $return;
    }

    function exportArray() {
        return (array) $this;
    }

    function addValidationRule($FieldIdentifier, $ValidationRule, $Argument) {
        $this->Container['Fields'][$FieldIdentifier]['ValidationRules'][$ValidationRule] = $Argument;
    }

    public function offsetExists($offset) {
        return isset($this->Container[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->Container[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->Container[$offset]) ? $this->Container[$offset] : null;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->Container[] = $value;
        } else {
            $this->Container[$offset] = $value;
        }
    }

    /**
     * Generate a table configuration array from a database table.
     * 
     * Grabs all the fields in a database table and transforms them into a table configuration array.
     * This is to simplify the process of getting started with Terra Data.
     * If $TerraDataTable is provided, discoverTable will fill it with data, rather than create a new one.
     * 
     * @param string $Table
     * @param resource $Connection
     * @param Terra_Data_Table $TerraDataTable
     * @return Terra_Data_Table
     */
    public static function discoverTable($Table, $Connection = null, $TerraDataTable = null) {
        if (!is_resource($Connection)) {
            $Connection = Terra_Data_Connection::getConnection();
            if (!is_resource($Connection)) {
                throw new Terra_DataException("No working database connection was found.");
            }
        }

        $Result = mysql_query("SHOW COLUMNS FROM $Table", $Connection);

        if ($TerraDataTable instanceof Terra_Data_Table) {
            $Return = &$TerraDataTable;
        } else {
            $Return = new Terra_Data_Table($Table);
        }

        while ($Row = mysql_fetch_assoc($Result)) {
            $Identifier = $Row['Field'];
            $Name = $Row['Field'];
            $HumanName = ucwords(strtolower(str_ireplace('_', ' ', $Row['Field'])));
            $PrimaryKey = ($Row['Key'] == 'PRI') ? true : false;
            $Return->addField($Identifier, $Name, $HumanName, false, $PrimaryKey);

            $Type = explode('(', $Row['Type']);
            if (count($Type) > 1) {
                # There were brackets. $Type[0] is the type, varchar or int. (int) $Type[1] is the MaxChars.
                $Return->addValidationRule($Identifier, 'MaxChars', (int) $Type[1]);
            }
        }
        return $Return;
    }

    /**
     * Print an array (recursive) as PHP code (can be pasted into a php file and it will work).
     * @param array $array
     * @param boolean $return (whether to return or print the output)
     * @return string|boolean (string if $return is true, true otherwise)
     */
    public static function printArrayAsPhpCode($array, $return = false) {
        if (count($array) == 0) {
            if (!$return) {
                print "array()";
                return true;
            } else {
                return "array()";
            }
        }
        $string = "array(";
        if (array_values($array) === $array) {
            $no_keys = true;
            foreach ($array as $value) {
                if (is_int($value)) {
                    $string .= "$value, ";
                } elseif (is_array($value)) {
                    $string .= printArrayInPHPFormat($value, true) . ",\n";
                } elseif (is_string($value)) {
                    $string .= "$value', ";
                } else {
                    trigger_error("Unsupported type of \$value, in index $key.");
                }
            }
        } else {
            $string .="\n";
            foreach ($array as $key => $value) {
                $no_keys = false;
                if (is_int($value)) {
                    $string .= "\"$key\" => $value,\n";
                } elseif (is_array($value)) {
                    $string .= "\"$key\" => " . printArrayInPHPFormat($value, true) . ",\n";
                } elseif (is_string($value)) {
                    $string .= "\"$key\" => '$value',\n";
                } else {
                    trigger_error("Unsupported type of \$value, in index $key.");
                }
            }
        }
        $string = substr($string, 0, strlen($string) - 2); # Remove last comma.
        if (!$no_keys) {
            $string .= "\n";
        }
        $string .= ")";
        if (!$return) {
            print $string;
            return true;
        } else {
            return $string;
        }
    }

}