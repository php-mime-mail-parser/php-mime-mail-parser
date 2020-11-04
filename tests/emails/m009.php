<?php

return [
    "subject" => "Ogone NIEUWE order Maurits PAYID: 951597484 / orderID: 456123 / status: 5",
    "from" => [
        "name" => "Ogone",
        "email" => "noreply@ogone.com",
        "is_group" => false,
        "header_value" => '"Ogone" <noreply@ogone.com>',
        "raw" => '"Ogone" <noreply@ogone.com>'
    ],
    "to" => [
        [
        "name" => "info@testsite.com",
        "email" => "info@testsite.com",
        "is_group" => false,
        ],
        "header_value" => "info@testsite.com",
    ],
    "textBody" => [
        "matchType" => "PARTIAL",
        "expectedValue" => "951597484",
    ],
    "htmlBody" => [
        "matchType" => "EXACT",
        "expectedValue" => '',
    ],
    "attachments" => []
];
