<?php
/**
 * Terra Duo Table Scaffolding
 *
 * Provides an HTML frontend for table management.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 0.2
 * @package Terra
 * @subpackage Table
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Terra_Table_Scaffolding {

    protected $TableFields;
    /**
     * The Terra_Table Instance.
     * @var Terra_Table_Interface
     */
    public $Table;
    /**
     * The Layout around which all content is wrapped.
     * @var Terra_Layout
     */
    public $BaseLayout;
    /**
     * The Layout for create/edit forms.
     * @var Terra_Layout
     */
    public $FormLayout;
    /**
     * The Layout for managing tables.
     * @var Terra_Layout
     */
    public $ManageLayout;
    /**
     * The Layout for viewing a single record.
     * @var Terra_Layout
     */
    public $ViewLayout;

    protected $Page = 1;
    protected $GlobalWhereClause = array();
    protected $OrderBy = array(
            'FIELD' => 'ID',
            'ORDER' => 'ASC'
    );
    protected $Record = 'record';
    protected $Records = 'records';
    protected $RowsPerPage = 30;
    protected $URLS = array(
            'BaseURL' => '',
            'View' => '',
            'Edit' => '',
            'Delete' => '',
            'Manage' => '',
            'Create' => ''
    );

    public function setTableData($TableArray, $Terra_Table = null) {
        if (is_array($TableArray)) {
            foreach ($TableArray['Fields'] as $Key => $Value) {
                $TableArray['Fields'][$Key] = array_merge(array(
                        'DisplayOnManage' => true,
                        'DisplayOnView' => true,
                        'DisplayOnCreate' => true,
                        'DisplayOnEdit' => true,
                        'DisableAutoFill' => false,
                        'UpdateIfBlank' => true,
                        'HumanName' => '',
                        'Type' => 'text',
                        'Default' => '',
                        'Description' => '',
                        ), $Value);
            }
            $this->TableFields = $TableArray['Fields'];
            $this->Record = $TableArray['Record'];
            if (isset($TableArray['Records'])) {
                $this->Records = $TableArray['Records'];
            } else {
                $this->Records = $TableArray['Name'];
            }
            if (isset($TableArray['OrderBy'])) {
                $this->OrderBy = $TableArray['OrderBy'];
            }

            if (!isset($TableArray['BaseURL'])) {
                throw new Terra_Table_Exception("You didn't set a BaseURL in your table definition!");
            }
            if (!isset($TableArray['View'])) {
                throw new Terra_Table_Exception("You didn't set a View URL in your table definition!");
            }
            if (!isset($TableArray['Edit'])) {
                throw new Terra_Table_Exception("You didn't set an Edit URL in your table definition!");
            }
            if (!isset($TableArray['Delete'])) {
                throw new Terra_Table_Exception("You didn't set a Delete URL in your table definition!");
            }
            if (!isset($TableArray['Manage'])) {
                throw new Terra_Table_Exception("You didn't set a Manage URL in your table definition!");
            }
            if (!isset($TableArray['Create'])) {
                throw new Terra_Table_Exception("You didn't set a Create URL in your table definition!");
            }
            if (!isset($TableArray['Restore'])) {
                throw new Terra_Table_Exception("You didn't set a Restore URL in your table definition!");
            }

            $this->URLS = array(
                    'BaseURL' => $TableArray['BaseURL'],
                    'View' => $TableArray['View'],
                    'Edit' => $TableArray['Edit'],
                    'Delete' => $TableArray['Delete'],
                    'Manage' => $TableArray['Manage'],
                    'Create' => $TableArray['Create'],
                    'Restore' => $TableArray['Restore']
            );

        } else {
            if (file_exists($TableArray)) {
                include $TableArray;
                return $this->setTableData($table);
            } else {
                throw new Terra_Table_Exception("Could not include $TableArray for use in Terra_Table_Scaffolding->setTableData().");
            }
        }

        if ($Terra_Table === null) {
            if (!isset($TableArray['Terra_Table'])) {
                $this->Table = Terra_Table_Manager::Factory('MySQL');
            } elseif (is_string($TableArray['Terra_Table'])) {
                $this->Table = Terra_Table_Manager::Factory($TableArray['Terra_Table']);
            }
        } elseif($Terra_Table instanceof Terra_Table_Interface) {
            $this->Table = $Terra_Table;
        } else {
            throw new Terra_Table_Exception("A value for \$Terra_Table was provided but this value does not implement Terra_Table_Interface.");
            return false;
        }

        $this->Table->setTableData($TableArray);
    }

    public function setRowsPerPage($RowsPerPage) {
        $this->RowsPerPage = $RowsPerPage;
    }

    public function setGlobalWhereClause($where) {
        $this->GlobalWhereClause = $where;
    }

    public function setPage($Page) {
        $this->Page = $Page;
    }

    public function setErrorMessage($Error) {
        $_SESSION['SCAFFOLDING_ERROR'] = $Error;
    }

    public function getErrorMessage() {
        if (isset ($_SESSION['SCAFFOLDING_ERROR'])) {
            $buffer = $_SESSION['SCAFFOLDING_ERROR'];
            unset($_SESSION['SCAFFOLDING_ERROR']);
            return $buffer;
        } else {
            return '';
        }
    }

    public function setSuccessMessage($Success) {
        $_SESSION['SCAFFOLDING_SUCCESS'] = $Success;
    }

    public function getSuccessMessage() {
        if (isset ($_SESSION['SCAFFOLDING_SUCCESS'])) {
            $buffer = $_SESSION['SCAFFOLDING_SUCCESS'];
            unset($_SESSION['SCAFFOLDING_SUCCESS']);
            return $buffer;
        } else {
            return '';
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

    public function prepareURL($URL, $replace = array('{ID}' => 0)) {
        if (is_array($URL)) {
            if (!isset($URL[0]) or !isset($URL[1])) {
                throw new Terra_Table_Exception("If you're trying to use prepareURL(\$args), you need to provide an array in the format: array(url, replace_array)");
                return;
            }
            $replace = $URL[1];
            $URL = $URL[0];
        }
        return $this->URLS['BaseURL'].str_ireplace(array_keys($replace), array_values($replace), $this->URLS[$URL]);
    }

    public function EditController($ID = null) {
        if (isset ($_POST['SCAFFOLDING'])) {
            $FieldsToEdit = array();
            foreach ($_POST as $FieldName => $FieldValue) {
                if (!$this->TableFields[$FieldName]['UpdateIfBlank'] and $FieldValue == '') {
                    continue;
                } else {
                    $FieldsToEdit[$FieldName] = $FieldValue;
                }
            }

            if ($this->Table->edit($ID, $FieldsToEdit)) {
                $this->setSuccessMessage(ucfirst($this->Record).' edited successfully.');
                header('Location: '.$this->prepareURL('Manage', array('{PAGE}' => $this->Page, '{ROWS_PER_PAGE}' => $this->RowsPerPage), false));
                die;
            } else {
                $this->printForm('Edit', $_POST);
            }
        } elseif(!empty($ID) and $this->Table->count(array_merge($this->GlobalWhereClause, array('ID' => $ID))) > 0) {
            $Row = $this->Table->getWhere(array_merge($this->GlobalWhereClause, array('ID' => $ID)), array('Rows' => 1));
            ob_start();

            foreach ($Row[0] as $FieldName => $FieldValue) {
                $Args = array(
                        'Row' => &$Row[0],
                        'Value' => &$FieldValue
                );
                Terra_Events::trigger('Terra_Table_Scaffolding_Edit_'.$FieldName, $Args);
            }

            $this->printForm('Edit', $Row[0]);
        } else {
            $this->setErrorMessage('The '.$this->Record.' you are trying to edit does not exist.');
            header('Location: '.$this->prepareURL('Manage', array('{PAGE}' => $this->Page, '{ROWS_PER_PAGE}' => $this->RowsPerPage), false));
            die;
        }
    }

    public function isAjax() {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    public function DeleteController($ID = null) {
        if (!empty($ID) and $this->Table->count(array_merge($this->GlobalWhereClause, array('ID' => $ID))) > 0) {
            if ($this->Table->delete($ID)) {
                $this->setSuccessMessage(ucfirst($this->Record).' deleted successfully.');
                header('Location: '.$this->prepareURL('Manage', array('{PAGE}' => $this->Page, '{ROWS_PER_PAGE}' => $this->RowsPerPage), false));
                die;
            } else {
                $this->setErrorMessage('A problem occured while attempting to delete the '.$this->Record.'.');
                header('Location: '.$this->prepareURL('Manage', array('{PAGE}' => $this->Page, '{ROWS_PER_PAGE}' => $this->RowsPerPage), false));
                die;
            }
        } else {
            $this->setErrorMessage('The '.$this->Record.' you are trying to delete does not exist.');
            header('Location: '.$this->prepareURL('Manage', array('{PAGE}' => $this->Page, '{ROWS_PER_PAGE}' => $this->RowsPerPage), false));
            die;
        }
    }

    public function RestoreController($ID = null) {
        if (!empty($ID) and $this->Table->count(array_merge($this->GlobalWhereClause, array('ID' => $ID))) > 0) {
            if ($this->Table->restore($ID)) {
                $this->setSuccessMessage(ucfirst($this->Record).' restored successfully.');
                header('Location: '.$this->prepareURL('Manage', array('{PAGE}' => $this->Page, '{ROWS_PER_PAGE}' => $this->RowsPerPage), false));
                die;
            } else {
                $this->setErrorMessage('A problem occured while attempting to restore the '.$this->Record.'.');
                header('Location: '.$this->prepareURL('Manage', array('{PAGE}' => $this->Page, '{ROWS_PER_PAGE}' => $this->RowsPerPage), false));
                die;
            }
        } else {
            $this->setErrorMessage('The '.$this->Record.' you are trying to restore does not exist.');
            header('Location: '.$this->prepareURL('Manage', array('{PAGE}' => $this->Page, '{ROWS_PER_PAGE}' => $this->RowsPerPage), false));
            die;
        }
    }

    public function ManageController($Page = null, $RowsPerPage = null) {
        if ($Page == null) {
            $Page = $this->Page;
        }
        if ($RowsPerPage == null) {
            $RowsPerPage = $this->RowsPerPage;
        }

        $Rows = array();
        $Fields = array();
        $Count = $this->Table->count($this->GlobalWhereClause);
        $Pages = ceil($Count / $RowsPerPage);
        if ($Pages < $Page) {
            $Page = $Pages;
        }

        if ($Page <= 0) {
            $Page = 1;
        }

        $args = array(
                'Rows' => $RowsPerPage,
                'Page' => $Page,
                'OrderBy' => $this->OrderBy
        );

        foreach ($this->Table->getWhere($this->GlobalWhereClause, $args) as $Row) {
            $buffer = array();
            $buffer['SCAFFOLDING_ID'] = $Row['ID'];
            foreach($this->TableFields as $FieldName => $FieldArray) {
                if ($FieldArray['DisplayOnManage']) {

                    if (isset($this->TableFields[$FieldName]['ValidationRules']['ExistsIn'])) {
                        $Row[$FieldName] = $Row[str_ireplace('_ID', '', $FieldName)];
                    }

                    $buffer[$FieldName] = $Row[$FieldName];

                    $Args = array(
                            'Row' => &$Row,
                            'Value' => &$buffer[$FieldName]
                    );
                    Terra_Events::trigger('Terra_Table_Scaffolding_Manage_'.$FieldName, $Args);

                    if (!isset ($Fields[$FieldName])) {
                        $Fields[$FieldName] = empty($FieldArray['HumanName']) ? $FieldName : $FieldArray['HumanName'];
                    }
                }
            }
            $Rows[] = array_merge($buffer, array('ORIGINAL_ROW' => $Row));
        }

        $this->ManageLayout->prepareURL = array(&$this, 'prepareURL');
        $this->ManageLayout->Records = $this->Records;
        $this->ManageLayout->Record = $this->Record;
        $this->ManageLayout->Rows = $Rows;
        $this->ManageLayout->Fields = $Fields;
        $this->ManageLayout->Page = $Page;
        $this->ManageLayout->Pages = $Pages;
        $this->ManageLayout->RowsPerPage = $RowsPerPage;
        $this->ManageLayout->end();
        $this->BaseLayout->success = $this->getSuccessMessage();
        $this->BaseLayout->error = $this->getErrorMessage();
        $this->BaseLayout->end();
    }

    public function ViewController($ID = null) {
        if (!empty($ID) and $this->Table->count(array_merge($this->GlobalWhereClause, array('ID' => $ID))) > 0) {
            $Fields = array();
            $PostArray = $this->Table->getWhere(array_merge($this->GlobalWhereClause, array('ID' => $ID)));
            $PostArray = $PostArray[0];
            foreach($this->TableFields as $FieldName => $FieldArray) {
                if ($FieldArray['DisplayOnView']) {
                    $Args = array(
                            'Row' => &$PostArray,
                            'Value' => &$PostArray[$FieldName]
                    );
                    Terra_Events::trigger('Terra_Table_Scaffolding_View_'.$FieldName, $Args);

                    $Fields[] = array(
                            'Name' => $FieldName,
                            'HumanName' => $FieldArray['HumanName'],
                            'Type' => $FieldArray['Type']
                    );
                }
            }
            $this->ViewLayout->end();
            $this->BaseLayout->end();
        } else {
            $this->setErrorMessage('The '.$this->Record.' you are trying to view does not exist.');
            header('Location: '.$this->prepareURL('Manage', array('{PAGE}' => $this->Page, '{ROWS_PER_PAGE}' => $this->RowsPerPage), false));
            die;
        }
    }

    protected function printForm($Action, $PostArray = array('ID' => 0)) {
        $Fields = array();
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
        $this->BaseLayout->end();
    }

}