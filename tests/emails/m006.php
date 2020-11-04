<?php

return [
    "subject" => "Mail de 3 196 Ko",
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
            'fileName' => 'attach06',
            'contentType' => 'application/octet-stream',
            'contentDisposition' => 'attachment',
            'size' => 3271754,
            'fileContent' => [
                "matchType" => "PARTIAL",
                "expectedValue" => 'lectus ac leo ullamcorper',
            ],
            'hashHeader' => '5dc6470ab63e86e8f68d88afb11556fe',
            'hashEmbeddedContent' => '',
        ],
    ]
];
