<table id="manage-<?php echo strtolower($records); ?>" class="manage terra-data">
    <caption><?php echo ucwords($records); ?></caption>
    <thead>
        <tr>
            <?php foreach ($fields as $field) { ?>
                <th scope="col"><?php echo $field['HumanName']; ?></th>
            <?php } ?>
        </tr>
    </thead>
    <tfoot>
        <td colspan="<?php echo count($fields); ?>">
            <span class="page-footer">Page <?php echo $page ?> of <?php echo $total_pages; ?></span>
            <span class="rows-footer">Showing <?php echo $rows_per_page; ?> <?php echo $records; ?></span>
        </td>
    </tfoot>
    <tbody>
        <?php foreach ($rows as $row) { ?>
            <tr>
                <?php foreach ($fields as $field) { ?>
                    <td><?php echo $row[$field['Name']]; ?></td>
                <?php } ?>
            </tr>
        <?php } ?>
    </tbody>
</table>