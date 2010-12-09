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

    function __construct($Table) {
        $this->Container['Name'] = $Table;
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

}