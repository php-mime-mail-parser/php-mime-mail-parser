<?php

return [
    "subject" => "Test message from Netscape Communicator 4.7",
    "from" => [
        "name" => "Doug Sauder",
        "email" => "dwsauder@example.com",
        "is_group" => false,
        "header_value" => 'Doug Sauder <dwsauder@example.com>',
    ],
    "to" => [
        [
        "name" => "Joe Blow",
        "email" => "blow@example.com",
        "is_group" => false,
        ],
        "header_value" => 'Joe Blow <blow@example.com>',
    ],
    "textBody" => [
        "matchType" => "PARTIAL",
        "expectedValue" => "Die Hasen und die",
    ],
    "htmlBody" => [
        "matchType" => "EXACT",
        "expectedValue" => '',
    ],
    "attachments" => []
];
