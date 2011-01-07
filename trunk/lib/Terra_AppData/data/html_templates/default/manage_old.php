<div class="manager">
    <h2>Manage <?php echo $Records; ?></h2>
    <h3><a href="<?php echo $this->prepareURL('Create'); ?>">Create <?php echo $Record; ?></a></h3>
    <?php if (count($Rows) > 0) { ?>
        <table summary="" cellspacing="0">
            <thead>
                <tr>
<?php foreach ($Fields as $Name => $Field) { ?>
                <th><?php echo $this->Data->humanizeField($Name); ?></th>
<?php } ?>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($Rows as $Row) { ?>
            <tr id="record_<?php echo $Row['ID'] ?>">
<?php foreach ($Row as $Field => $FieldValue) { ?>
                <td><?php echo $FieldValue; ?></td>
<?php } ?>
                <td class="actions">
                    <a href="<?php echo $this->prepareURL('Edit', array('{ID}' => $Row['ID'])); ?>" title="Edit">Edit</a> |
<?php if (isset($Row['IS_DELETED']) and $Row['IS_DELETED'] == 1) { ?>
                    <a href="<?php echo $this->prepareURL('Restore', array('{ID}' => $Row['ID'])); ?>" title="Restore">Restore</a>
<?php } else { ?>
                    <a href="<?php echo $this->prepareURL('Delete', array('{ID}' => $Row['ID'])); ?>" class="delete-link" title="Delete">Delete</a>
<?php } ?>
                </td>
            </tr>
<?php } ?>
        </tbody>
    </table>

<?php if ($Pages > 1) { ?>
            <ul class="pagination">
        <?php $upperLimit = (($Page + 5) > $Pages) ? $Pages : $Page + 5;
            $lowerLimit = (($Page - 5) <= 0) ? 1 : $Page - 5; ?>
<?php if ($lowerLimit != 1) { ?>
                <li><a href="<?php echo $this->prepareURL('Manage', array('{PAGE}' => 1, '{ROWS_PER_PAGE}' => $RowsPerPage)) ?>">1</a></li>
                <li>...</li>
        <?php } ?>
        <?php while ($lowerLimit <= $upperLimit) {
 ?>
        <?php if ($lowerLimit != $Page) {
 ?>
                    <li><a href="<?php echo $this->prepareURL('Manage', array('{PAGE}' => $lowerLimit, '{ROWS_PER_PAGE}' => $RowsPerPage)) ?>"><?php echo $lowerLimit; ?></a></li>
        <?php } else {
 ?>
                    <li><?php echo $lowerLimit; ?></li>
<?php } ?>
<?php $lowerLimit++; ?>
        <?php } ?>
<?php if ($Pages != $upperLimit) { ?>
                <li>...</li>
                <li><a href="<?php echo $this->prepareURL('Manage', array('{PAGE}' => $Pages, '{ROWS_PER_PAGE}' => $RowsPerPage)) ?>"><?php echo $Pages ?></a></li>
<?php } ?>
            </ul>
<?php } ?>
<?php } else { ?>
    <p class="warning">There are no records.</p>
<?php } ?>
</div>