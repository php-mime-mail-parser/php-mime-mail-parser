<?php

return [
    "subject" => "Mail de 14 Ko",
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
            'fileName' => 'attach03',
            'contentType' => 'application/octet-stream',
            'contentDisposition' => 'attachment',
            'size' => 13369,
            'fileContent' => [
                "matchType" => "PARTIAL",
                "expectedValue" => 'dolor sit amet',
            ],
            'hashHeader' => '8734417734fabfa783df6fed0ccf7a4a',
            'hashEmbeddedContent' => '',
        ],
    ]
];
