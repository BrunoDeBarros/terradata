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

    protected $Container = array(
        'Name' => '',
        'Fields' => array()
    );

    function __construct($Table) {
        $this->Container['Name'] = $Table;
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
            'Name' => $Name,
            'HumanName' => $HumanName,
            'ValidationRules' => array()
        );
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

    function addValidationRule($FieldIdentifier, $ValidationRule, $Argument) {
        $this->Container['Fields'][$FieldIdentifier]['ValidationRules'][$ValidationRule] = $Argument;
    }

    function offsetExists($offset) {
        return isset($this->Container[$offset]);
    }

    function offsetUnset($offset) {
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

}