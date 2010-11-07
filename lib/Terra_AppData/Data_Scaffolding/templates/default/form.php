<h2><?php echo $Action.' '.$Record?></h2>
<h3><a href="<?php echo $this->prepareURL('Manage', array('{PAGE}' => $this->CurrentPage, '{ROWS_PER_PAGE}' => $this->RowsPerPage))?>">Go back to the <?php echo $this->Record?> manager</a></h3>
<?php $this->TableManager->setErrorDelimiter('<span class="error">','</span>');?>
<form method="post" action="<?php echo $FormURL?>">
    <div class="form">
        <input type="hidden" name="SCAFFOLDING" value="<?php echo $ID ?>" />
        <input type="hidden" name="ID" value="<?php echo $ID ?>" />
        <?php foreach ($Fields as $Field) { ?>
            <?php $error = $this->TableManager->getValidationError($Field['Name']);?>
        <label for="<?php echo $Field['Name'];?>"><?php echo $Field['HumanName'];?>
                <?php echo (!empty($error)) ? $error : null?>
                <?php if (isset($this->TableFields[$Field['Name']]['SCAFFOLDING']['HumanDescription'])) {?>
            <p class="description"><?php echo $this->TableFields[$Field['Name']]['SCAFFOLDING']['HumanDescription'];?></p>
                    <?php }?>
        </label>
            <?php if ($Field['Type'] == 'text' or $Field['Type'] == 'hidden' or $Field['Type'] == 'password') {?>
        <input id="<?php echo $Field['Name'];?>" type="<?php echo $Field['Type'];?>" name="<?php echo $Field['Name'];?>" value="<?php echo isset($PostArray[$Field['Name']]) ? $PostArray[$Field['Name']] : ((isset($this->TableFields[$Field['Name']]['META']) and isset($this->TableFields[$Field['Name']]['META']['Default'])) ? $this->TableFields[$Field['Name']]['META']['Default'] : null)?>" />
                <?php } elseif($Field['Type'] == 'boolean') {?>
        <div class="radio"><label><input type="radio" value="1" id="<?php echo $Field['Name'];?>" name="<?php echo $Field['Name'];?>" <?php echo ((isset($PostArray[$Field['Name']]) ? $PostArray[$Field['Name']] : null) == 1) ? 'checked="checked"' : null?> /> Yes</label></div>
        <div class="radio"><label><input type="radio" value="0" id="<?php echo $Field['Name'];?>" name="<?php echo $Field['Name'];?>" <?php echo ((isset($PostArray[$Field['Name']]) ? $PostArray[$Field['Name']] : null) == 0) ? 'checked="checked"' : null?> /> No</label></div>
                <?php } elseif($Field['Type'] == 'select') {?>
        <select id="<?php echo $Field['Name'];?>" name="<?php echo $Field['Name'];?>">
                    <?php if(isset($this->TableFields[$Field['Name']]['MANAGER']['inArray'])) {?>
                        <?php foreach($this->TableFields[$Field['Name']]['MANAGER']['inArray'] as $key => $value) {?>
            <option value="<?php echo ($key)?>" <?php echo ((isset($PostArray[$Field['Name']]) ? $PostArray[$Field['Name']] : null) == $key) ? 'selected="selected"' : null?>><?php echo ($value)?></option>
                            <?php }?>
                        <?php }?>

                    <?php if(isset($this->TableFields[$Field['Name']]['MANAGER']['existsIn'])) {?>
                        <?php
                        $where = array();
                        if (isset($this->TableFields[$Field['Name']]['MANAGER']['existsIn']['where_clause_callback'])) $where = $this->callback(null, null, $this->TableFields[$Field['Name']]['MANAGER']['existsIn']['where_clause_callback']);
                        if (isset($this->TableFields[$Field['Name']]['MANAGER']['existsIn']['where_clause'])) $where = $this->TableFields[$Field['Name']]['MANAGER']['existsIn']['where_clause'];
                        $table = new TD_MySQL_Table_Manager($this->TableFields[$Field['Name']]['MANAGER']['existsIn']['table'], array());
                        $table->setDatabaseConnection($this->TableManager->getDatabaseConnection());
                        ?>
                        <?php foreach($table->getWhere($where) as $Row) {?>
            <option value="<?php echo ($Row[$this->TableFields[$Field['Name']]['MANAGER']['existsIn']['field']])?>" <?php echo ((isset($PostArray[$Field['Name']]) ? $PostArray[$Field['Name']] : null) == $Row[$this->TableFields[$Field['Name']]['MANAGER']['existsIn']['field']]) ? 'selected="selected"' : null?>><?php echo ($Row[$this->TableFields[$Field['Name']]['MANAGER']['existsIn']['value_field']])?></option>
                            <?php }?>
                        <?php }?>
        </select>
                <?php }?>
            <?php if (isset($this->TableFields[$Field['Name']]['SCAFFOLDING'][$action]['NoUpdateIfBlank'])) {?>
        <span>If left empty, this field will not be updated.</span>
                <?php }?>
            <?php }?>
        <button type="submit"><?php echo $action;?> <?php echo $this->Record?></button>
    </div>
</form>