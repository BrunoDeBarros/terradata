<?php
/**
 * Terra Duo Table Scaffolding
 *
 * Provides a frontend for database management.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 2010.1.25
 *
 * @copyright Copyright (c) 2008-2010 Bruno De Barros
 *
 * The MIT License
 *
 * Copyright (c) 2008-2010 Bruno De Barros
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class TD_Table_Scaffolding {

    protected $TableFields;
    protected $TableName;
    /**
     * The Table Manager Instance.
     * @var TD_MySQL_Table_Manager
     */
    public $TableManager;
    protected $LayoutFile;
    protected $FormFile;
    protected $ManageTemplateFile;
    protected $ViewTemplateFile;
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
            'View' => '',
            'Edit' => '',
            'Delete' => '',
            'Manage' => '',
            'Create' => ''
    );

    public function setTableConfigs($File) {
        require($File);
        $this->TableFields = $TableFields;
        $this->TableName = $TableName;
        $this->Record = $Record;
        $this->Records = $Records;
        $this->OrderBy = $OrderBy;
        $this->URLS = array(
                'View' => $ViewURL,
                'Edit' => $EditURL,
                'Delete' => $DeleteURL,
                'Manage' => $ManageURL,
                'Create' => $CreateURL,
                'Restore' => $RestoreURL
        );
        if (!isset($TableManager)) {
            $TableManager = 'TD_MySQL_Table_Manager';
        }
        $this->TableManager = new $TableManager($TableName, $TableFields);
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

    public function setDatabaseConnection($connection) {
        $this->TableManager->setDatabaseConnection($connection);
    }

    public function setLayout($File) {
        $this->LayoutFile = $File;
    }

    public function setFormTemplate($File) {
        $this->FormFile = $File;
    }

    public function setManageTemplate($File) {
        $this->ManageTemplateFile = $File;
    }

    public function setViewTemplate($File) {
        $this->ViewTemplateFile = $File;
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
                if ((!isset ($_POST[$FieldName]) or empty($_POST[$FieldName])) and isset ($FieldArray['META']['Default'])) {
                    $add[$FieldName] = $FieldArray['META']['Default'];
                }
            }
            if ($this->TableManager->create(array_merge($add, $_POST))) {
                $this->setSuccessMessage(ucfirst($this->Record).' created successfully.');
                header('Location: '.$this->prepareURL('Manage', array('{PAGE}' => $this->Page, '{ROWS_PER_PAGE}' => $this->RowsPerPage), false));
                die;
            } else {
                ob_start();
                $this->printForm('Create', $_POST);
                $contents = ob_get_contents();
                ob_end_clean();
                $this->PrintInLayout($contents);
            }
        } else {
            ob_start();
            $this->printForm('Create');
            $contents = ob_get_contents();
            ob_end_clean();
            $this->PrintInLayout($contents);
        }
    }

    public function prepareURL($URL, $replace = array('{ID}' => 0)) {
        return str_ireplace(array_keys($replace), array_values($replace), $this->URLS[$URL]);
    }

    public function EditController($ID = null) {
        if (isset ($_POST['SCAFFOLDING'])) {
            $FieldsToEdit = array();
            foreach ($_POST as $FieldName => $FieldValue) {
                if (isset($this->TableFields[$FieldName]['SCAFFOLDING']['Edit']['NoUpdateIfBlank']) and $FieldValue == '') {
                    continue;
                } else {
                    $FieldsToEdit[$FieldName] = $FieldValue;
                }
            }

            if ($this->TableManager->edit($ID, $FieldsToEdit)) {
                $this->setSuccessMessage(ucfirst($this->Record).' edited successfully.');
                header('Location: '.$this->prepareURL('Manage', array('{PAGE}' => $this->Page, '{ROWS_PER_PAGE}' => $this->RowsPerPage), false));
                die;
            } else {
                ob_start();
                $this->printForm('Edit', $_POST);
                $contents = ob_get_contents();
                ob_end_clean();
                $this->PrintInLayout($contents);
            }
        } elseif(!empty($ID) and $this->TableManager->count(array_merge($this->GlobalWhereClause, array('ID' => $ID))) > 0) {
            $Row = $this->TableManager->getWhere(array_merge($this->GlobalWhereClause, array('ID' => $ID)), 1, 1);
            ob_start();

            foreach ($Row[0] as $FieldName => $FieldValue) {
                if (isset ($this->TableFields[$FieldName]['SCAFFOLDING']['onEdit']) and !empty($this->TableFields[$FieldName]['SCAFFOLDING']['onEdit'])) {
                    $Row[0][$FieldName] = $this->callback($this->TableFields[$FieldName]['SCAFFOLDING']['onEdit'], $FieldValue, $Row[0]);
                } else {
                    continue;
                }
            }

            $this->printForm('Edit', $Row[0]);
            $contents = ob_get_contents();
            ob_end_clean();
            $this->PrintInLayout($contents);
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
        if (!empty($ID) and $this->TableManager->count(array_merge($this->GlobalWhereClause, array('ID' => $ID))) > 0) {
            if ($this->TableManager->delete($ID)) {
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
        if (!empty($ID) and $this->TableManager->count(array_merge($this->GlobalWhereClause, array('ID' => $ID))) > 0) {
            if ($this->TableManager->restore($ID)) {
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
        $Count = $this->TableManager->count($this->GlobalWhereClause);
        $Pages = ceil($Count / $RowsPerPage);
        if ($Pages < $Page) {
            $Page = $Pages;
        }

        if ($Page <= 0) {
            $Page = 1;
        }
        foreach ($this->TableManager->getWhere($this->GlobalWhereClause, $RowsPerPage, $Page, $this->OrderBy) as $Row) {
            $buffer = array();
            $buffer['SCAFFOLDING_ID'] = $Row['ID'];
            foreach($this->TableFields as $FieldName => $FieldArray) {
                if (isset($FieldArray['SCAFFOLDING']['Manage']) and $FieldArray['SCAFFOLDING']['Manage']) {

                    if (isset($this->TableFields[$FieldName]['MANAGER']['existsIn'])) {
                        $Row[$FieldName] = $Row[str_ireplace('_ID', '', $FieldName)];
                    }

                    if (isset ($FieldArray['SCAFFOLDING']['onManage']) and !empty($FieldArray['SCAFFOLDING']['onManage'])) {
                        $buffer[$FieldName] = $this->callback($FieldArray['SCAFFOLDING']['onManage'], $Row[$FieldName], $Row);
                    } else {
                        $buffer[$FieldName] = $Row[$FieldName];
                    }
                    if (!isset ($Fields[$FieldName])) {
                        $Fields[$FieldName] = $FieldArray['SCAFFOLDING']['HumanName'];
                    }
                }
            }
            $Rows[] = array_merge($buffer, array('SCAFFOLDING_ORIGINAL' => $Row));
        }
        ob_start();
        include($this->ManageTemplateFile);
        $contents = ob_get_contents();
        ob_end_clean();
        $this->PrintInLayout($contents);
    }

    public function ViewController($ID = null) {
        if (!empty($ID) and $this->TableManager->count(array_merge($this->GlobalWhereClause, array('ID' => $ID))) > 0) {
            $Fields = array();
            $PostArray = $this->TableManager->getWhere(array_merge($this->GlobalWhereClause, array('ID' => $ID)));
            $PostArray = $PostArray[0];
            foreach($this->TableFields as $FieldName => $FieldArray) {
                if (isset($FieldArray['SCAFFOLDING']['View']) and $FieldArray['SCAFFOLDING']['View']) {
                    if (isset ($FieldArray['SCAFFOLDING']['onView']) and !empty($FieldArray['SCAFFOLDING']['onView'])) {
                        $PostArray[$FieldName] = $this->callback($FieldArray['SCAFFOLDING']['onView'], $PostArray[$FieldName], $PostArray);
                    }
                    $Fields[] = array(
                            'Name' => $FieldName,
                            'HumanName' => $FieldArray['SCAFFOLDING']['HumanName'],
                            'Type' => $FieldArray['SCAFFOLDING']['Type']
                    );
                }
            }
            ob_start();
            include($this->ViewTemplateFile);
            $contents = ob_get_contents();
            ob_end_clean();
            $this->PrintInLayout($contents);
        } else {
            $this->setErrorMessage('The '.$this->Record.' you are trying to view does not exist.');
            header('Location: '.$this->prepareURL('Manage', array('{PAGE}' => $this->Page, '{ROWS_PER_PAGE}' => $this->RowsPerPage), false));
            die;
        }
    }

    protected function printForm($action, $PostArray = array('ID' => 0)) {
        $Fields = array();
        $ID = $PostArray['ID'];
        $FormURL = $this->prepareURL($action, array('{ID}' => $ID), false);
        foreach($this->TableFields as $FieldName => $FieldArray) {
            if (isset($FieldArray['SCAFFOLDING'][$action]) and $FieldArray['SCAFFOLDING'][$action]) {
                if (isset($FieldArray['SCAFFOLDING'][$action]['DisableAutofill'])) {
                    unset($PostArray[$FieldName]);
                }
                $Fields[] = array(
                        'Name' => $FieldName,
                        'HumanName' => $FieldArray['SCAFFOLDING']['HumanName'],
                        'Type' => $FieldArray['SCAFFOLDING']['Type']
                );
            }
        }
        include($this->FormFile);
    }

    protected function callback($callback, $arg1 = null, $arg2 = null, $arg3 = null) {
        return $this->TableManager->callback($callback, $arg1, $arg2, $arg3);
    }

    protected function PrintInLayout($contents) {
        if (!empty($this->LayoutFile)) {
            include($this->LayoutFile);
        } else {
            print $contents;
        }
    }

}