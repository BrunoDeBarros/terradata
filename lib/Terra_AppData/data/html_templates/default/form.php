<form method="post" action="<?php echo $form_url; ?>">
    <fieldset>
        <legend>Legend Name</legend>
        <dl>
            <?php foreach ($fields as $field) { ?>
                <dt><label for="<?php echo $field['Identifier'] ?>"><?php echo $field['HumanName'] ?></label></dt>
                <dd>
                    <?php if (isset($field['ValidationRules']['Text']) and $field['ValidationRules']['MaxChars']) { ?>
                        <input type="text" id="<?php echo $field['Identifier'] ?>" maxlength="<?php echo $field['ValidationRules']['MaxChars']; ?>" name="<?php echo $field['Identifier'] ?>" value="<?php echo $row[$field['Identifier']]; ?>" />
                    <?php } ?>
                </dd>
            <?php } ?>
            <dt><label for="submit">&nbsp;</label></dt>
            <dd><button type="submit" name="submit" id="submit">Create <?php echo $record ?></button></dd>
        </dl>
    </fieldset>
</form>