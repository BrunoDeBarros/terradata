<div id="manager">
<h1>Manage <?php echo ucfirst($this->Records);?></h1>
<h3><a href="<?php echo $this->prepareURL('Create', array('{ID}' => (isset($this->Row['SCAFFOLDING_ID'])) ? $this->Row['SCAFFOLDING_ID'] : null));?>">Create <?php echo $this->Record;?></a></h3>
<?php if (count($this->Rows) > 0) {?>
<table summary="" cellspacing="0">
    <thead>
        <tr>
                <?php foreach($this->Fields as $Field) {
                    if ($Field != 'SCAFFOLDING_ID' and $Field != 'ORIGINAL_ROW') {?>
            <th><?php echo $Field?></th>
                        <?php }
                }
                ?>
            <th class="actions">Actions</th>
        </tr>
    </thead>
    <tbody>
            <?php foreach($this->Rows as $Row) { ?>
        <tr id="record_<?php echo $Row['SCAFFOLDING_ID']?>">
                    <?php foreach($Row as $Field => $FieldValue) { if (!isset($this->Fields[$Field])) { continue; }
                        if ($Field != 'SCAFFOLDING_ID' and $Field != 'ORIGINAL_ROW') {?>
            <td><?php echo $FieldValue;?></td>
                            <?php }
                    }
                    ?>
            <td class="actions">
                <a href="<?php echo $this->prepareURL('Edit', array('{ID}' => $Row['SCAFFOLDING_ID']));?>" title="">Edit</a> |
                        <?php if (isset($Row['ORIGINAL_ROW']['IS_DELETED']) and $Row['ORIGINAL_ROW']['IS_DELETED'] == 1) {?>
                <a href="<?php echo $this->prepareURL('Restore', array('{ID}' => $Row['SCAFFOLDING_ID']));?>" title="">Restore</a>
                            <?php } else {?>
                <a href="<?php echo $this->prepareURL('Delete', array('{ID}' => $Row['SCAFFOLDING_ID']));?>" class="delete-link" title="">Delete</a>
                            <?php }?>
            </td>
        </tr>
                <?php }?>
    </tbody>
</table>

    <?php if ($this->Pages > 1) {?>
<ul class="pagination">
            <?php $upperLimit = (($this->Page + 5) > $this->Pages) ? $this->Pages : $this->Page + 5;
            $lowerLimit = (($this->Page - 5) <= 0) ? 1 : $this->Page - 5; ?>
            <?php if ($lowerLimit != 1) {?>
    <li><a href="<?php echo $this->prepareURL('Manage', array('{PAGE}' => 1, '{ROWS_PER_PAGE}' => $this->RowsPerPage))?>">1</a></li>
    <li>...</li>
                <?php }?>
            <?php while ($lowerLimit <= $upperLimit) {?>
                <?php if ($lowerLimit != $this->Page) {?>
    <li><a href="<?php echo $this->prepareURL('Manage', array('{PAGE}' => $lowerLimit, '{ROWS_PER_PAGE}' => $this->RowsPerPage))?>"><?php echo $lowerLimit;?></a></li>
                    <?php } else { ?>
    <li><?php echo $lowerLimit;?></li>
                    <?php }?>
                <?php $lowerLimit++; ?>
                <?php }?>
            <?php if ($this->Pages != $upperLimit) {?>
    <li>...</li>
    <li><a href="<?php echo $this->prepareURL('Manage', array('{PAGE}' => $Pages, '{ROWS_PER_PAGE}' => $this->RowsPerPage))?>"><?php echo $this->Pages?></a></li>
                <?php }?>
</ul>
        <?php }?>
    <?php } else {?>
<h1>There are no records.</h1>
    <?php }?>
</div>