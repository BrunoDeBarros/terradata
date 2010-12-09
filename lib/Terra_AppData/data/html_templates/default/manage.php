<table id="manage-<?php echo strtolower($records); ?>" class="manage terra-data">
    <caption><?php echo ucwords($records); ?></caption>
    <thead>
        <tr>
            <?php foreach ($fields as $field) { ?>
                <th scope="col"><?php echo $field['HumanName']; ?></th>
            <?php } ?>
                <th scope="col">Actions</th>
        </tr>
    </thead>
    <tfoot>
        <td colspan="<?php echo count($fields) + 1; ?>">
            <span class="page-footer">Page <?php echo $page ?> of <?php echo $total_pages; ?></span>
            <span class="rows-footer">Showing <?php echo count($rows); ?> <?php echo $records; ?></span>
        </td>
    </tfoot>
    <tbody>
        <?php foreach ($rows as $row) { ?>
            <tr>
                <?php foreach ($row['Fields'] as $value) { ?>
                    <td><?php echo $value; ?></td>
                <?php } ?>
                <td class="actions">
                    <a class="edit" href="<?php echo $row['Edit']; ?>" title="Edit <?php echo $record;?>">Edit</a>
                    <a class="delete" href="<?php echo $row['Delete']; ?>" title="Delete <?php echo $record;?>">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>
<a href="<?php echo $create;?>" title="Create <?php echo $record;?>">Create <?php echo $record;?></a>