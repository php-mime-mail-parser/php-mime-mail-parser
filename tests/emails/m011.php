<?php

return [
    "subject" => "Hello World !",
    "from" => [
        "name" => "Name",
        "email" => "name@company.com",
        "is_group" => false,
        "header_value" => 'Name <name@company.com>',
    ],
    "to" => [
        [
        "name" => "Name",
        "email" => "name@company.com",
        "is_group" => false,
        ],
        "header_value" => "Name <name@company.com>",
    ],
    "textBody" => [
        "matchType" => "PARTIAL",
        "expectedValue" => "This is a text body",
    ],
    "htmlBody" => [
        "matchType" => "EXACT",
        "expectedValue" => '',
    ],
    "attachments" => [
        [
            'fileName' => 'file.txt',
            'contentType' => 'text/plain',
            'contentDisposition' => 'attachment',
            'size' => 29,
            'fileContent' => [
                "matchType" => "PARTIAL",
                "expectedValue" => 'This is a file',
            ],
            'hashHeader' => '839d0486dd1b91e520d456bb17c33148',
            'hashEmbeddedContent' => '',
        ],
    ]
];
