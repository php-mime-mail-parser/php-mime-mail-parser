<?php

return [
    "subject" => "Mail de 1500 Ko",
    "from" => [
        "name" => "Name",
        "email" => "name@company.com",
        "is_group" => false,
        "header_value" => "Name <name@company.com>",
    ],
    "to" => [
        [
        "name" => "name@company2.com",
        "email" => "name@company2.com",
        "is_group" => false,
        ],
        "header_value" => "name@company2.com",
    ],
    "cc" => null,
    "textBody" => [
        "matchType" => "EXACT",
        "expectedValue" => "\n",
    ],
    "htmlBody" => [
        "matchType" => "PARTIAL",
        "expectedValue" => '<div dir="ltr"><br></div>',
    ],
    "attachments" => [
        [
            'fileName' => 'attach05',
            'contentType' => 'application/octet-stream',
            'contentDisposition' => 'attachment',
            'size' => 1635877,
            'fileContent' => [
                "matchType" => "PARTIAL",
                "expectedValue" => 'Aenean ultrices',
            ],
            'hashHeader' => '1ced323befc39ebbc147e7588d11ab08',
            'hashEmbeddedContent' => '',
        ],
    ]
];
