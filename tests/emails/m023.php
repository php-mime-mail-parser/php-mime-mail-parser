<?php

return [
    "subject" => "If you can read this you understand the example.",
    "from" => [
        "name" => "Keith Moore",
        "email" => "moore@cs.utk.edu",
        "is_group" => false,
        "header_value" => 'Keith Moore <moore@cs.utk.edu>',
    ],
    "to" => [
        [
        "name" => "Keld Jørn Simonsen",
        "email" => "keld@dkuug.dk",
        "is_group" => false,
        ],
        "header_value" => 'Keld Jørn Simonsen <keld@dkuug.dk>',
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
