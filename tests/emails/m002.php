<?php

return [
    "subject" => "Mail avec fichier attaché de 3ko",
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
            'fileName' => 'attach02',
            'contentType' => 'application/octet-stream',
            'contentDisposition' => 'attachment',
            'size' => 2229,
            'fileContent' => [
                "matchType" => "PARTIAL",
                "expectedValue" => 'Lorem ipsum',
            ],
            'hashHeader' => '18f541cc6bf49209d2bf327ecb887355',
            'hashEmbeddedContent' => '',
        ],
    ]
];
