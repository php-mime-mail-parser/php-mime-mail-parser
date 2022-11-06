<?php

return [
    "subject" => "[PRJ-OTH] asdf  árvíztűrő tükörfúrógép",
    "from" => [
        "name" => "sendeär",
        "email" => "sender@test.com",
        "is_group" => false,
        "header_value" => 'sendeär <sender@test.com>',
    ],
    "to" => [
        [
        "name" => "test",
        "email" => "test@asdasd.com",
        "is_group" => false,
        ],
        "header_value" => '"test" <test@asdasd.com>',
    ],
    "cc" => null,
    "textBody" => [
        "matchType" => "PARTIAL",
        "expectedValue" => "captured",
    ],
    "htmlBody" => [
        "matchType" => "EXACT",
        "expectedValue" => '',
    ],
    "attachments" => []
];
