<?php

return [
    "subject" => "Up to $30 Off Multivitamins!",
    "from" => [
        "name" => "Vitamart.ca",
        "email" => "service@vitamart.ca",
        "is_group" => false,
        "header_value" => '"Vitamart.ca" <service@vitamart.ca>',
    ],
    "to" => [
        [
        "name" => "me@somewhere.com",
        "email" => "me@somewhere.com",
        "is_group" => false,
        ],
        "header_value" => 'me@somewhere.com',
    ],
    "cc" => null,
    "textBody" => [
        "matchType" => "PARTIAL",
        "expectedValue" => "Hi,",
    ],
    "htmlBody" => [
        "matchType" => "PARTIAL",
        "expectedValue" => '<strong>*How The Sale Works</strong>',
    ],
    "attachments" => []
];
