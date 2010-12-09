<table>
    <tr>
        <?php foreach ($fields as $field) { ?>
            <th><?php echo $field['HumanName']; ?></th>
        <?php } ?>
    </tr>
    <?php foreach ($rows as $row) { ?>
        <tr>
            <?php foreach ($fields as $field) { ?>
                <td><?php echo $row[$field['Name']]; ?></td>
            <?php } ?>
        </tr>
    <?php } ?>
</table>