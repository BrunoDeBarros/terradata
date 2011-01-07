<?php

class Terra_Data_Scaffolding {

    protected $Data;
    protected $Record;
    protected $Records;
    protected $URLs;

    protected $CurrentPage = 1;
    protected $RowsPerPage = 10;

    public function __construct(Terra_Data $Data, $URLs, $Record = 'record', $Records = 'records') {
        $this->Data = $Data;
        $this->Record = $Record;
        $this->Records = $Records;
        $this->URLs = $URLs;

        # Extend fields to add some default scaffolding values and avoid having to do isset() loads of times.
        foreach ($this->Data->Fields as $Name => $Field) {
            $this->Data->Fields[$Name] = array_merge(array('Scaffolding' => array(
                            'Create' => false,
                            'Edit' => false,
                            'Manage' => false,
                            'View' => false
                            )), $Field);
        }
    }

    public function prepareURL($name, $replacements = array()) {
        if (isset($this->URLs[$name])) {
            $buffer = $this->URLs[$name];
            foreach ($replacements as $tag => $replacement) {
                $buffer = str_ireplace($tag, $replacement, $buffer);
            }
            return $buffer;
        } else {
            throw new Terra_Data_ScaffoldingException("Trying to prepare an URL that isn't set.");
            return false;
        }
    }

    public function CreateController() {
        if (isset ($_POST['SCAFFOLDING'])) {
            $add = $this->GlobalWhereClause;
            foreach ($this->TableFields as $FieldName => $FieldArray) {
                if ((!isset ($_POST[$FieldName]) or empty($_POST[$FieldName])) and isset ($FieldArray['Default'])) {
                    $add[$FieldName] = $FieldArray['Default'];
                }
            }
            if ($this->Table->create(array_merge($add, $_POST))) {
                $this->setSuccessMessage(ucfirst($this->Record).' created successfully.');
                header('Location: '.$this->prepareURL('Manage', array('{PAGE}' => $this->Page, '{ROWS_PER_PAGE}' => $this->RowsPerPage), false));
                die;
            } else {
                $this->printForm('Create', $_POST);
            }
        } else {
            $this->printForm('Create');
        }
    }

    protected function printForm($Action, $PostArray = array('ID' => 0)) {
        $Fields = array();

        $this->Data->getValidationErrors();

        $ID = $PostArray['ID'];
        $FormURL = $this->prepareURL($Action, array('{ID}' => $ID), false);
        foreach($this->TableFields as $FieldName => $FieldArray) {
            if ($FieldArray['DisplayOn'.$Action]) {
                if (isset($FieldArray['DisableAutofill'])) {
                    unset($PostArray[$FieldName]);
                }
                $Fields[] = array(
                        'Name' => $FieldName,
                        'HumanName' => $FieldArray['HumanName'],
                        'Type' => $FieldArray['Type']
                );
            }
        }
        $this->FormLayout->prepareURL = array(&$this, 'prepareURL');
        $this->FormLayout->setErrorDelimiter = array(&$this->Table, 'setErrorDelimiter');
        $this->FormLayout->getValidationError = array(&$this->Table, 'getValidationError');
        $this->FormLayout->PostArray = $PostArray;
        $this->FormLayout->ID = $ID;
        $this->FormLayout->FormURL = $FormURL;
        $this->FormLayout->Fields = $Fields;
        $this->FormLayout->TableFields = $this->TableFields;
        $this->FormLayout->Action = $Action;
        $this->FormLayout->Records = $this->Records;
        $this->FormLayout->Record = $this->Record;
        $this->FormLayout->Page = $this->Page;
        $this->FormLayout->RowsPerPage = $this->RowsPerPage;
        $this->FormLayout->end();
    }

    public function EditController($ID) {

    }

    public function DeleteController($ID) {

    }

    public function RestoreController($ID) {

    }

    public function ViewController($ID) {

    }

    public function ManageController($Page = 1, $RowsPerPage = 10) {
        $Records = $this->Records;
        $Record = $this->Record;
        $Fields = array();
        $FieldsToGet = array();
        foreach ($this->Data->Fields as $Name => $Field) {
            if ($Field['Scaffolding']['Manage']) {
                $Fields[$Name] = $Field;
                $FieldsToGet[] = $Name;
            }
        }

        if (empty($FieldsToGet)) {
            throw new Terra_Data_ScaffoldingException("No fields are set to be shown in the ManageController.");
        }

        $Rows = $this->Data->get(array('Page' => $Page, 'Rows' => $RowsPerPage, 'Fields' => $FieldsToGet));
        $Pages = ceil($this->Data->count() / $RowsPerPage);
        include TERRA_APPDATA_PATH.'Data_Scaffolding/templates/default/manage.php';
    }

}