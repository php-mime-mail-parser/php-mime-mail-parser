<?php

return [
    "subject" => "occurs when divided into an array, and the last e of the array! Путін хуйло!!!!!!",
    "from" => [
        "name" => "mail@exemple.com",
        "email" => "mail@exemple.com",
        "is_group" => false,
        "header_value" => 'mail@exemple.com',
        "raw" => 'mail@exemple.com'
    ],
    "to" => [
        [
        "name" => "mail@exemple.com",
        "email" => "mail@exemple.com",
        "is_group" => false,
        ],
        [
        "name" => "mail2@exemple3.com",
        "email" => "mail2@exemple3.com",
        "is_group" => false,
        ],
        [
        "name" => "mail3@exemple2.com",
        "email" => "mail3@exemple2.com",
        "is_group" => false,
        ],
        "header_value" => 'mail@exemple.com, mail2@exemple3.com, mail3@exemple2.com',
    ],
    "textBody" => [
        "matchType" => "PARTIAL",
        "expectedValue" => "mini plain body",
    ],
    "htmlBody" => [
        "matchType" => "EXATC",
        "expectedValue" => '',
    ],
    "attachments" => []
];
