<?php
$Fields = array(
        /*
        'FIELD' => array(
                'Name' => 'FIELD',
                'Default' => '',
                'HumanName' => 'Field',
                'Disabled' => false, # Disables inserting/updating. Does NOT disable getting.
                'ValidationRules' => array(
                        'Required' => true,
                        'MaxChars' => 255
                )
        ),
     *
        */

        'ID' => array(
                'Name' => 'ID',
                'HumanName' => 'Reference #',
                'PrimaryKey' => true,

                'Scaffolding' => array(
                    'Manage' => true,
                ),

                'ValidationRules' => array(
                    'MaxChars' => 255,
                    'Integer',
                    'Unique'
                )
        ),

        'CREATED' => array(
                'Name' => 'CREATED',
                'ValidationRules' => array(
                    'Integer'
                )
        ),

        'UPDATED' => array(
                'Name' => 'UPDATED',
            'ValidationRules' => array(
                    'Integer'
                )
        ),

        'IS_DELETED' => array(
                'Name' => 'IS_DELETED',
            'ValidationRules' => array(
                    'Boolean'
                )
        ),

        'USERNAME' => array(
                'Name' => 'USERNAME',
                'HumanName' => 'Username',
                'ValidationRules' => array(
                        'Required' => true,
                        'MaxChars' => 255
                ),
                'Scaffolding' => array(
                    'Manage' => true,
                ),
        ),

        'PASSWORD' => array(
                'Name' => 'PASSWORD',
                'HumanName' => 'Password',
                'ValidationRules' => array(
                        'Required' => true,
                        'Hash' => 'sha256'
                )
        ),

        'EMAIL' => array(
                'Name' => 'EMAIL',
                'HumanName' => 'E-Mail Address',
                'ValidationRules' => array(
                        'Required' => true,
                        'MaxChars' => 255
                ),
                'Scaffolding' => array(
                    'Manage' => true,
                ),
        ),

        'ARTICLES' => array(
                'Name' => 'ARTICLES',
                'HumanName' => 'Articles',
                'Relationship' => true,
                'CanHave' => 'Many',
                'Table' => 'articles',
                'Rel_Table' => 'articles_users',
                'ID' => 'USER_ID',
                'Field' => 'ID',
                'REL_ID' => 'ARTICLE_ID'
        ),

        'COUNTRY_ID' => array(
                'Name' => 'COUNTRY_ID',
                'HumanName' => 'Country',
                'Relationship' => true,
                'CanHave' => 'One',
                'Table' => 'countries',
                'Field' => 'ID',
                'Rel_Table' => 'countries_users',
                'ID' => 'USER_ID',
                'REL_ID' => 'COUNTRY_ID',
                'Alias' => 'COUNTRY',
                'ValueField' => 'NAME'
        ),

        'NATIONALITY_ID' => array(
                'Name' => 'NATIONALITY_ID',
                'HumanName' => 'Nationality',
                'ValidationRules' => array(
                        'ExistsIn' => array(
                                'Table' => 'countries',
                                'Field' => 'ID',
                                'Alias' => 'NATIONALITY',
                                'ValueField' => 'NAME'
                        )
                )
        ),

        'ADDRESS' => array(
                'Disabled' => true, # Because it's not a real field.
                'Name' => 'ADDRESS',
                'HumanName' => 'Address',
                'Relationship' => true,
                'CanHave' => 'One',
                'Table' => 'user_metadata',
                'Field' => 'ID',
                'Rel_Table' => 'user_metadata',
                'ID' => 'USER_ID',
                'REL_ID' => 'ID',
                'ValueField' => 'VALUE',
                'Where' => Terra_Data::WhereFactory('user_metadata')->_and('KEY', 'ADDRESS')
        ),

        'METADATA' => array(
                'Disabled' => true, # Because it's not a real field.
                'Name' => 'METADATA',
                'HumanName' => 'Metadata',
                'Relationship' => true,
                'CanHave' => 'Many',
                'Field' => 'ID',
                'Table' => 'user_metadata',
                'Rel_Table' => 'user_metadata',
                'ID' => 'USER_ID',
                'REL_ID' => 'ID'
        ),
);
?>