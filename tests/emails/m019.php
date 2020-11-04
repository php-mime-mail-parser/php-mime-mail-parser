<?php

return [
    "subject" => "Re: Maya Ethnobotanicals - Emails",
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
