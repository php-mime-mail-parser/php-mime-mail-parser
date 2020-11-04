<?php

return [
    "subject" => "Mail de 800ko",
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
            'fileName' => 'attach04',
            'contentType' => 'application/octet-stream',
            'contentDisposition' => 'attachment',
            'size' => 817938,
            'fileContent' => [
                "matchType" => "PARTIAL",
                "expectedValue" => 'Phasellus scelerisque',
            ],
            'hashHeader' => 'c0b5348ef825bf62ba2d07d70d4b9560',
            'hashEmbeddedContent' => '',
        ],
    ]
];
