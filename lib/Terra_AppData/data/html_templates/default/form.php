<form method="post" action="<?php echo $form_url; ?>">
    <fieldset>
        <legend>Legend Name</legend>
        <dl>
            <?php foreach ($fields as $field) { ?>
                <dt>
                <label for="field-<?php echo $field['Identifier'] ?>"><?php echo $field['HumanName'] ?></label>
                <?php if ($field['Error']) { ?><span class="error"><?php echo $field['Error']; ?></span><?php } ?>
                </dt>
                <dd>
                    <?php if (isset($field['ValidationRules']['Text']) and isset($field['ValidationRules']['MaxChars'])) { ?>
                        <input type="text" class="Text MaxChars" id="field-<?php echo $field['Identifier'] ?>" maxlength="<?php echo $field['ValidationRules']['MaxChars']; ?>" name="fields[<?php echo $field['Identifier'] ?>]" value="<?php echo $row[$field['Identifier']]; ?>" />
                    <?php } ?>
                </dd>
            <?php } ?>
            <dt><label for="submit">&nbsp;</label></dt>
            <dd><button type="submit" name="submit" id="submit">Create <?php echo $record ?></button></dd>
        </dl>
    </fieldset>
</form>