<?php

return [
    "subject" => "Mail avec fichier attaché de 1ko",
    "from" => [
        "name" => "Name",
        "email" => "name@company.com",
        "is_group" => false,
        "header_value" => "Name <name@company.com>",
        "raw" => "Name <name@company.com>"
    ],
    "to" => [
        [
        "name" => "name@company2.com",
        "email" => "name@company2.com",
        "is_group" => false,
        ],
        "header_value" => "name@company2.com",
    ],
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
            'fileName' => 'attach01',
            'contentType' => 'application/octet-stream',
            'contentDisposition' => 'attachment',
            'size' => 2,
            'fileContent' => [
                "matchType" => "PARTIAL",
                "expectedValue" => 'a',
            ],
            'hashHeader' => '04c1d5793efa97c956d011a8b3309f05',
            'hashEmbeddedContent' => '',
        ],
    ]
];
